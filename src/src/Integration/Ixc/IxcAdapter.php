<?php
declare(strict_types=1);

namespace App\Integration\Ixc;

use App\Integration\AbstractIntegration;

/**
 * IXC Integration Adapter
 *
 * Implements IntegrationInterface for IXC Soft API integration.
 * Provides methods to connect, test connection, retrieve service status,
 * equipment status, and metrics from the IXC platform.
 *
 * @see docs/API_INTEGRATIONS.md for IXC API specifications
 */
class IxcAdapter extends AbstractIntegration
{
    /**
     * @var string
     */
    protected string $name = 'IXC Soft';

    /**
     * @var string
     */
    protected string $type = 'ixc';

    /**
     * IXC API Client
     *
     * @var \App\Integration\Ixc\IxcClient
     */
    protected IxcClient $client;

    /**
     * Constructor
     *
     * @param array<string, mixed> $config Integration configuration
     * @param \App\Integration\Ixc\IxcClient|null $client Optional IXC client (for testing)
     */
    public function __construct(array $config = [], ?IxcClient $client = null)
    {
        parent::__construct($config);

        if ($client !== null) {
            $this->client = $client;
        }
    }

    /**
     * @inheritDoc
     */
    public function connect(): bool
    {
        try {
            $this->validateConfig(['base_url', 'username', 'password']);

            if (!isset($this->client)) {
                $this->client = new IxcClient(
                    $this->config['base_url'],
                    $this->config['username'],
                    $this->config['password'],
                    (int)($this->config['timeout'] ?? 30)
                );
            }

            $result = $this->client->authenticate();
            $this->connected = $result;

            $this->logInfo('Connected to IXC API', [
                'base_url' => $this->config['base_url'],
            ]);

            return $this->connected;
        } catch (\InvalidArgumentException $e) {
            $this->connected = false;

            throw $e;
        } catch (\Exception $e) {
            $this->connected = false;
            $this->setLastError($e->getMessage());
            $this->logError('Failed to connect to IXC API: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function testConnection(): array
    {
        $startTime = microtime(true);

        try {
            $connected = $this->connect();
            $responseTime = (microtime(true) - $startTime) * 1000;

            if ($connected) {
                return $this->buildSuccessResponse('Connection established successfully', [
                    'response_time' => round($responseTime, 2),
                ]);
            }

            return $this->buildErrorResponse(
                $this->getLastError() ?? 'Failed to connect to IXC',
                ['response_time' => round($responseTime, 2)]
            );
        } catch (\Exception $e) {
            $responseTime = (microtime(true) - $startTime) * 1000;

            return $this->buildErrorResponse($e->getMessage(), [
                'response_time' => round($responseTime, 2),
            ]);
        }
    }

    /**
     * @inheritDoc
     */
    public function getStatus(string $resourceId): array
    {
        try {
            $this->ensureConnected();

            $data = $this->client->get("/services/{$resourceId}/status");

            return IxcMapper::mapServiceStatus($data);
        } catch (\Exception $e) {
            $this->logError("Failed to get status for resource {$resourceId}: " . $e->getMessage());

            return [
                'status' => 'unknown',
                'online' => false,
                'message' => $e->getMessage(),
                'last_seen' => null,
                'metadata' => [],
            ];
        }
    }

    /**
     * Get equipment status
     *
     * Retrieves the status of a specific network equipment (OLT, router, etc.)
     *
     * @param string $equipmentId Equipment identifier
     * @return array Equipment status data
     */
    public function getEquipmentStatus(string $equipmentId): array
    {
        try {
            $this->ensureConnected();

            $data = $this->client->get("/network/equipment/{$equipmentId}/status");

            return IxcMapper::mapEquipmentStatus($data);
        } catch (\Exception $e) {
            $this->logError("Failed to get equipment status for {$equipmentId}: " . $e->getMessage());

            return [
                'status' => 'unknown',
                'online' => false,
                'message' => $e->getMessage(),
                'last_seen' => null,
                'metadata' => [],
            ];
        }
    }

    /**
     * @inheritDoc
     */
    public function getMetrics(string $resourceId, array $params = []): array
    {
        try {
            $this->ensureConnected();

            $endpoint = $params['endpoint'] ?? 'services';

            if ($endpoint === 'equipment') {
                $data = $this->client->get("/network/equipment/{$resourceId}/status");
                $mapped = IxcMapper::mapEquipmentStatus($data);
            } else {
                $data = $this->client->get("/services/{$resourceId}/status");
                $mapped = IxcMapper::mapServiceStatus($data);
            }

            return [
                'metrics' => $mapped['metadata'] ?? [],
                'timestamp' => date('c'),
                'resource_id' => $resourceId,
                'metadata' => [
                    'status' => $mapped['status'],
                    'online' => $mapped['online'],
                ],
            ];
        } catch (\Exception $e) {
            $this->logError("Failed to get metrics for resource {$resourceId}: " . $e->getMessage());

            return [
                'metrics' => [],
                'timestamp' => date('c'),
                'resource_id' => $resourceId,
                'metadata' => ['error' => $e->getMessage()],
            ];
        }
    }

    /**
     * Get critical tickets
     *
     * Retrieves open critical tickets from IXC
     *
     * @return array List of critical tickets
     */
    public function getCriticalTickets(): array
    {
        try {
            $this->ensureConnected();

            $data = $this->client->get('/tickets', [
                'status' => 'open',
                'priority' => 'critical',
            ]);

            return IxcMapper::mapTickets($data);
        } catch (\Exception $e) {
            $this->logError('Failed to get critical tickets: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * @inheritDoc
     */
    public function disconnect(): void
    {
        $this->connected = false;
        $this->logDebug('Disconnected from IXC API');
    }

    /**
     * Ensure we are connected before making API calls
     *
     * @return void
     * @throws \RuntimeException If not connected and cannot connect
     */
    protected function ensureConnected(): void
    {
        if (!$this->connected) {
            if (!$this->connect()) {
                throw new \RuntimeException(
                    'Not connected to IXC API: ' . ($this->getLastError() ?? 'Unknown error')
                );
            }
        }
    }

    /**
     * Get the IXC client instance
     *
     * @return \App\Integration\Ixc\IxcClient
     */
    public function getClient(): IxcClient
    {
        return $this->client;
    }
}
