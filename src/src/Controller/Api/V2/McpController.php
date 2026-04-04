<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

use App\Model\Entity\ApiKey;
use App\Service\ApiKeyService;
use App\Service\Mcp\McpResourceProvider;
use App\Service\Mcp\McpToolAdapter;
use App\Service\PlanService;
use App\Tenant\TenantContext;
use Cake\Event\EventInterface;
use Cake\Log\LogTrait;

/**
 * McpController
 *
 * Implements the MCP (Model Context Protocol) server using Streamable HTTP transport.
 * Single endpoint: POST /api/v2/mcp
 *
 * Authenticates via API key (Bearer token) instead of JWT.
 * Handles JSON-RPC 2.0 messages as defined by the MCP specification.
 */
class McpController extends AppController
{
    use LogTrait;

    /**
     * MCP protocol version supported by this server.
     */
    private const PROTOCOL_VERSION = '2024-11-05';

    /**
     * Server info returned during initialization.
     */
    private const SERVER_INFO = [
        'name' => 'KeepUp MCP Server',
        'version' => '1.0.0',
    ];

    /**
     * The authenticated API key entity.
     *
     * @var \App\Model\Entity\ApiKey|null
     */
    private ?ApiKey $apiKey = null;

    /**
     * The user's role derived from API key permissions.
     *
     * @var string
     */
    private string $apiKeyRole = 'viewer';

    /**
     * Before filter — authenticate via API key instead of JWT.
     *
     * The MCP endpoint uses API key authentication (Bearer sk_live_...).
     * JWT payload from JwtAuthMiddleware will be null for this path since
     * it is excluded. We handle auth manually here.
     *
     * @param \Cake\Event\EventInterface $event The event.
     * @return \Cake\Http\Response|null
     */
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        // The parent sets jwtPayload to null for excluded paths — that's fine.
        // We authenticate via API key instead.
        $authHeader = $this->request->getHeaderLine('Authorization');
        if (!str_starts_with($authHeader, 'Bearer ')) {
            return $this->earlyJsonResponse(
                401,
                $this->jsonRpcError(null, -32000, 'Missing or invalid Authorization header. Use: Bearer sk_live_...')
            );
        }

        $token = substr($authHeader, 7);

        $apiKeyService = new ApiKeyService();
        $this->apiKey = $apiKeyService->validate($token);

        if (!$this->apiKey) {
            return $this->earlyJsonResponse(
                401,
                $this->jsonRpcError(null, -32000, 'Invalid or expired API key')
            );
        }

        // Check plan gating — MCP requires api_access feature
        $planService = new PlanService();
        if (!$planService->canUseFeature($this->apiKey->organization_id, 'api_access')) {
            return $this->earlyJsonResponse(
                402,
                $this->jsonRpcError(null, -32000, 'MCP server access requires a Business plan or above. Please upgrade your plan.')
            );
        }

        // Set tenant context
        TenantContext::setCurrentOrgId($this->apiKey->organization_id);

        // Derive role from permissions
        $this->currentOrgId = $this->apiKey->organization_id;
        $this->currentUserId = $this->apiKey->user_id;
        $this->apiKeyRole = McpToolAdapter::permissionsToRole($this->apiKey->getPermissions());

        return null;
    }

    /**
     * Return an early JSON response from beforeFilter.
     *
     * Sets the response body directly since CakePHP's view serialization
     * does not run when beforeFilter returns a response object.
     *
     * @param int $status HTTP status code.
     * @param array $body The response body array.
     * @return \Cake\Http\Response The response.
     */
    private function earlyJsonResponse(int $status, array $body): \Cake\Http\Response
    {
        $this->autoRender = false;
        $this->response = $this->response
            ->withStatus($status)
            ->withType('application/json')
            ->withStringBody(json_encode($body));

        return $this->response;
    }

    /**
     * Handle MCP JSON-RPC messages.
     *
     * Single endpoint: POST /api/v2/mcp
     * Accepts JSON-RPC 2.0 requests and dispatches to the appropriate handler.
     *
     * @return void
     */
    public function handle(): void
    {
        if (!$this->apiKey) {
            // Auth failed in beforeFilter — response already set
            return;
        }

        // Try CakePHP's parsed body first, then fall back to raw body stream.
        // CakePHP may have already parsed the JSON body into an array.
        $message = $this->request->getParsedBody();
        if (empty($message) || !is_array($message)) {
            $body = (string)$this->request->getBody();
            $message = json_decode($body, true);
        }

        if (!is_array($message) || !isset($message['method'])) {
            $this->set('response', $this->jsonRpcError(
                $message['id'] ?? null,
                -32700,
                'Parse error: invalid JSON-RPC message'
            ));
            $this->response = $this->response->withStatus(400);
            $this->viewBuilder()->setOption('serialize', 'response');

            return;
        }

        $id = $message['id'] ?? null;
        $method = $message['method'];
        $params = $message['params'] ?? [];

        $result = match ($method) {
            'initialize' => $this->handleInitialize($id, $params),
            'tools/list' => $this->handleToolsList($id),
            'tools/call' => $this->handleToolsCall($id, $params),
            'resources/list' => $this->handleResourcesList($id),
            'resources/read' => $this->handleResourcesRead($id, $params),
            'ping' => $this->handlePing($id),
            'notifications/initialized' => null, // Notification — no response
            default => $this->jsonRpcError($id, -32601, "Method not found: {$method}"),
        };

        if ($result === null) {
            // Notification — return empty 202 response
            $this->response = $this->response->withStatus(202)->withStringBody('');
            $this->autoRender = false;

            return;
        }

        $this->set('response', $result);
        $this->viewBuilder()->setOption('serialize', 'response');
    }

    /**
     * Info endpoint — returns MCP server configuration for users.
     *
     * GET /api/v2/mcp/info
     * Returns the MCP server URL, transport type, and Claude Desktop config example.
     *
     * @return void
     */
    public function info(): void
    {
        if (!$this->apiKey) {
            return;
        }

        $baseUrl = $this->request->scheme() . '://' . $this->request->host();
        $mcpUrl = $baseUrl . '/api/v2/mcp';

        $this->set('response', [
            'success' => true,
            'data' => [
                'mcp_server_url' => $mcpUrl,
                'transport' => 'streamable-http',
                'auth' => 'Bearer YOUR_API_KEY',
                'protocol_version' => self::PROTOCOL_VERSION,
                'server_info' => self::SERVER_INFO,
                'claude_desktop_config' => [
                    'mcpServers' => [
                        'keepup' => [
                            'url' => $mcpUrl,
                            'transport' => 'streamable-http',
                            'headers' => [
                                'Authorization' => 'Bearer YOUR_API_KEY',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        $this->viewBuilder()->setOption('serialize', 'response');
    }

    /**
     * Handle the 'initialize' method — client handshake.
     *
     * @param mixed $id The JSON-RPC request ID.
     * @param array $params The request params.
     * @return array The JSON-RPC response.
     */
    private function handleInitialize(mixed $id, array $params): array
    {
        $this->log(
            'MCP initialize from client: ' . ($params['clientInfo']['name'] ?? 'unknown'),
            'info'
        );

        return $this->jsonRpcSuccess($id, [
            'protocolVersion' => self::PROTOCOL_VERSION,
            'capabilities' => [
                'tools' => new \stdClass(),
                'resources' => new \stdClass(),
            ],
            'serverInfo' => self::SERVER_INFO,
        ]);
    }

    /**
     * Handle 'tools/list' — return available tools.
     *
     * @param mixed $id The JSON-RPC request ID.
     * @return array The JSON-RPC response.
     */
    private function handleToolsList(mixed $id): array
    {
        $adapter = new McpToolAdapter();
        $tools = $adapter->getToolsList($this->apiKeyRole);

        return $this->jsonRpcSuccess($id, [
            'tools' => $tools,
        ]);
    }

    /**
     * Handle 'tools/call' — execute a tool.
     *
     * @param mixed $id The JSON-RPC request ID.
     * @param array $params The request params with 'name' and 'arguments'.
     * @return array The JSON-RPC response.
     */
    private function handleToolsCall(mixed $id, array $params): array
    {
        $toolName = $params['name'] ?? '';
        $arguments = $params['arguments'] ?? [];

        if (empty($toolName)) {
            return $this->jsonRpcError($id, -32602, 'Invalid params: missing tool name');
        }

        $adapter = new McpToolAdapter();
        $result = $adapter->executeTool(
            $toolName,
            $arguments,
            $this->currentOrgId,
            $this->currentUserId,
            $this->apiKeyRole
        );

        return $this->jsonRpcSuccess($id, $result);
    }

    /**
     * Handle 'resources/list' — list available resources.
     *
     * @param mixed $id The JSON-RPC request ID.
     * @return array The JSON-RPC response.
     */
    private function handleResourcesList(mixed $id): array
    {
        $provider = new McpResourceProvider();

        return $this->jsonRpcSuccess($id, [
            'resources' => $provider->listResources(),
        ]);
    }

    /**
     * Handle 'resources/read' — read a resource by URI.
     *
     * @param mixed $id The JSON-RPC request ID.
     * @param array $params The request params with 'uri'.
     * @return array The JSON-RPC response.
     */
    private function handleResourcesRead(mixed $id, array $params): array
    {
        $uri = $params['uri'] ?? '';

        if (empty($uri)) {
            return $this->jsonRpcError($id, -32602, 'Invalid params: missing resource URI');
        }

        $provider = new McpResourceProvider();
        $result = $provider->readResource($uri, $this->currentOrgId);

        if (isset($result['error'])) {
            return $this->jsonRpcError($id, -32002, $result['error']);
        }

        return $this->jsonRpcSuccess($id, $result);
    }

    /**
     * Handle 'ping' — health check.
     *
     * @param mixed $id The JSON-RPC request ID.
     * @return array The JSON-RPC response.
     */
    private function handlePing(mixed $id): array
    {
        return $this->jsonRpcSuccess($id, new \stdClass());
    }

    /**
     * Build a JSON-RPC 2.0 success response.
     *
     * @param mixed $id The request ID.
     * @param mixed $result The result payload.
     * @return array The JSON-RPC response.
     */
    private function jsonRpcSuccess(mixed $id, mixed $result): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $result,
        ];
    }

    /**
     * Build a JSON-RPC 2.0 error response.
     *
     * @param mixed $id The request ID (may be null).
     * @param int $code The error code.
     * @param string $message The error message.
     * @param mixed $data Optional additional error data.
     * @return array The JSON-RPC response.
     */
    private function jsonRpcError(mixed $id, int $code, string $message, mixed $data = null): array
    {
        $error = [
            'code' => $code,
            'message' => $message,
        ];

        if ($data !== null) {
            $error['data'] = $data;
        }

        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => $error,
        ];
    }
}
