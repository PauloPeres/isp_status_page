<?php
declare(strict_types=1);

namespace App\Service\Assistant;

use Cake\Http\Client;
use Cake\I18n\DateTime;
use Cake\Log\LogTrait;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * AssistantService
 *
 * Main orchestration service for the AI Chat Assistant.
 * Handles conversation management, Claude API communication,
 * and tool execution loop.
 */
class AssistantService
{
    use LocatorAwareTrait;
    use LogTrait;

    /**
     * Claude API endpoint.
     */
    private const API_URL = 'https://api.anthropic.com/v1/messages';

    /**
     * Claude model to use.
     */
    private const MODEL = 'claude-sonnet-4-20250514';

    /**
     * Maximum tokens for response.
     */
    private const MAX_TOKENS = 4096;

    /**
     * Maximum tool execution iterations to prevent infinite loops.
     */
    private const MAX_TOOL_ITERATIONS = 10;

    /**
     * Maximum conversation messages to load for context.
     */
    private const MAX_CONTEXT_MESSAGES = 20;

    /**
     * @var \App\Service\Assistant\ToolRegistry
     */
    private ToolRegistry $toolRegistry;

    /**
     * @var \App\Service\Assistant\ToolExecutor
     */
    private ToolExecutor $toolExecutor;

    /**
     * @var \App\Service\Assistant\PromptBuilder
     */
    private PromptBuilder $promptBuilder;

    /**
     * @var \Cake\Http\Client
     */
    private Client $httpClient;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->toolRegistry = new ToolRegistry();
        $this->toolExecutor = new ToolExecutor();
        $this->promptBuilder = new PromptBuilder();
        $this->httpClient = new Client();
    }

    /**
     * Send a message and get a response.
     *
     * @param int $orgId Organization ID.
     * @param int $userId User ID.
     * @param string $role User's role.
     * @param string $conversationPublicId Conversation public_id (UUID).
     * @param string $message The user's message.
     * @return array The response data.
     */
    public function chat(int $orgId, int $userId, string $role, string $conversationPublicId, string $message): array
    {
        $conversationsTable = $this->fetchTable('ChatConversations');
        $messagesTable = $this->fetchTable('ChatMessages');

        // Load or validate conversation
        $conversation = $conversationsTable->find('byPublicId', publicId: $conversationPublicId)
            ->where([
                'ChatConversations.organization_id' => $orgId,
                'ChatConversations.user_id' => $userId,
            ])
            ->first();

        if (!$conversation) {
            return ['error' => 'Conversation not found.', 'status' => 404];
        }

        if ($conversation->status !== 'active') {
            return ['error' => 'Conversation is archived.', 'status' => 400];
        }

        // Load conversation history
        $history = $messagesTable->find()
            ->where(['ChatMessages.conversation_id' => $conversation->id])
            ->orderBy(['ChatMessages.created' => 'ASC', 'ChatMessages.id' => 'ASC'])
            ->limit(self::MAX_CONTEXT_MESSAGES)
            ->toArray();

        // Build messages for Claude API
        $apiMessages = $this->buildApiMessages($history);

        // Add new user message
        $apiMessages[] = ['role' => 'user', 'content' => $message];

        // Build system prompt
        $systemPrompt = $this->promptBuilder->build($orgId, $userId, $role);

        // Get tools for user role
        $tools = $this->toolRegistry->getToolsForRole($role);

        // Call Claude API with tool loop
        $apiKey = $this->getApiKey();
        if (!$apiKey) {
            return ['error' => 'AI assistant is not configured. Missing API key.', 'status' => 503];
        }

        $totalInputTokens = 0;
        $totalOutputTokens = 0;
        $toolCallsLog = [];
        $toolResultsLog = [];
        $assistantContent = '';

        $iterations = 0;
        while ($iterations < self::MAX_TOOL_ITERATIONS) {
            $iterations++;

            $response = $this->callClaudeApi($apiKey, $systemPrompt, $apiMessages, $tools);

            if (isset($response['error'])) {
                $this->log("Claude API error: {$response['error']}", 'error');

                return ['error' => 'AI service temporarily unavailable.', 'status' => 502];
            }

            $totalInputTokens += $response['usage']['input_tokens'] ?? 0;
            $totalOutputTokens += $response['usage']['output_tokens'] ?? 0;

            $contentBlocks = $response['content'] ?? [];
            $stopReason = $response['stop_reason'] ?? 'end_turn';

            // Check if there are tool calls
            $hasToolUse = false;
            $toolUseBlocks = [];
            $textContent = '';

            foreach ($contentBlocks as $block) {
                if (($block['type'] ?? '') === 'tool_use') {
                    $hasToolUse = true;
                    $toolUseBlocks[] = $block;
                } elseif (($block['type'] ?? '') === 'text') {
                    $textContent .= $block['text'] ?? '';
                }
            }

            if (!$hasToolUse || $stopReason !== 'tool_use') {
                // Final response - extract text
                $assistantContent = $textContent;
                break;
            }

            // Execute tools and continue the loop
            $apiMessages[] = ['role' => 'assistant', 'content' => $contentBlocks];

            $toolResults = [];
            foreach ($toolUseBlocks as $toolBlock) {
                $toolName = $toolBlock['name'] ?? '';
                $toolInput = $toolBlock['input'] ?? [];
                $toolId = $toolBlock['id'] ?? '';

                $toolCallsLog[] = ['name' => $toolName, 'input' => $toolInput];

                $result = $this->toolExecutor->execute($toolName, $toolInput, $orgId, $userId, $role);

                $toolResultsLog[] = ['name' => $toolName, 'result' => $result];

                $toolResults[] = [
                    'type' => 'tool_result',
                    'tool_use_id' => $toolId,
                    'content' => json_encode($result),
                ];
            }

            $apiMessages[] = ['role' => 'user', 'content' => $toolResults];
        }

        // Save user message
        $userMsg = $messagesTable->newEntity([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $message,
            'input_tokens' => 0,
            'output_tokens' => 0,
        ]);
        $messagesTable->save($userMsg);

        // Save assistant message
        $assistantMsg = $messagesTable->newEntity([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $assistantContent,
            'tool_calls' => !empty($toolCallsLog) ? json_encode($toolCallsLog) : null,
            'tool_results' => !empty($toolResultsLog) ? json_encode($toolResultsLog) : null,
            'input_tokens' => $totalInputTokens,
            'output_tokens' => $totalOutputTokens,
        ]);
        $messagesTable->save($assistantMsg);

        // Update conversation counts
        $conversation->message_count = ($conversation->message_count ?? 0) + 2;
        $conversation->input_tokens_used = ($conversation->input_tokens_used ?? 0) + $totalInputTokens;
        $conversation->output_tokens_used = ($conversation->output_tokens_used ?? 0) + $totalOutputTokens;

        // Auto-generate title from first user message
        if ($conversation->title === 'New conversation' && $conversation->message_count <= 2) {
            $conversation->title = mb_substr($message, 0, 100);
            if (mb_strlen($message) > 100) {
                $conversation->title .= '...';
            }
        }

        $conversationsTable->save($conversation);

        return [
            'message' => $assistantContent,
            'conversation_id' => $conversation->public_id,
            'tokens' => [
                'input' => $totalInputTokens,
                'output' => $totalOutputTokens,
            ],
            'tool_calls_count' => count($toolCallsLog),
        ];
    }

    /**
     * Build API messages from conversation history.
     *
     * @param array $history Array of ChatMessage entities.
     * @return array Messages formatted for Claude API.
     */
    private function buildApiMessages(array $history): array
    {
        $messages = [];

        foreach ($history as $msg) {
            if ($msg->role === 'system') {
                continue; // System messages go in the system parameter
            }

            $entry = [
                'role' => $msg->role,
                'content' => $msg->content ?? '',
            ];

            $messages[] = $entry;
        }

        return $messages;
    }

    /**
     * Call the Claude API.
     *
     * @param string $apiKey The API key.
     * @param string $systemPrompt The system prompt.
     * @param array $messages The conversation messages.
     * @param array $tools The tool definitions.
     * @return array The API response.
     */
    private function callClaudeApi(string $apiKey, string $systemPrompt, array $messages, array $tools): array
    {
        $body = [
            'model' => self::MODEL,
            'max_tokens' => self::MAX_TOKENS,
            'system' => $systemPrompt,
            'messages' => $messages,
        ];

        if (!empty($tools)) {
            $body['tools'] = $tools;
        }

        try {
            $response = $this->httpClient->post(self::API_URL, json_encode($body), [
                'headers' => [
                    'x-api-key' => $apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ],
                'timeout' => 120,
            ]);

            $responseBody = $response->getStringBody();
            $data = json_decode($responseBody, true);

            if (!$response->isOk()) {
                $errorMsg = $data['error']['message'] ?? $responseBody;
                $this->log("Claude API HTTP {$response->getStatusCode()}: {$errorMsg}", 'error');

                return ['error' => $errorMsg];
            }

            return $data;
        } catch (\Throwable $e) {
            $this->log("Claude API request failed: {$e->getMessage()}", 'error');

            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get the Claude API key from environment.
     *
     * @return string|null
     */
    private function getApiKey(): ?string
    {
        $key = env('ANTHROPIC_API_KEY') ?: env('CLAUDE_API_KEY');

        return $key ?: null;
    }
}
