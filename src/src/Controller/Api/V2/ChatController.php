<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

use App\Service\Assistant\AssistantService;
use App\Service\PlanService;
use Cake\I18n\DateTime;

/**
 * Chat Controller
 *
 * API endpoints for the AI Chat Assistant.
 * Handles conversation CRUD and message sending.
 */
class ChatController extends AppController
{
    /**
     * Daily message limit per user.
     */
    private const DAILY_MESSAGE_LIMIT = 100;

    /**
     * Create a new conversation.
     *
     * POST /api/v2/chat/conversations
     *
     * @return void
     */
    public function createConversation(): void
    {
        $this->request->allowMethod(['POST']);

        if (!$this->checkAiChatFeature()) {
            return;
        }

        $conversationsTable = $this->fetchTable('ChatConversations');

        $data = [
            'organization_id' => $this->currentOrgId,
            'user_id' => $this->currentUserId,
            'title' => $this->request->getData('title', 'New conversation'),
            'status' => 'active',
            'message_count' => 0,
            'input_tokens_used' => 0,
            'output_tokens_used' => 0,
        ];

        $conversation = $conversationsTable->newEntity($data);
        if (!$conversationsTable->save($conversation)) {
            $this->error('Failed to create conversation.', 400, $conversation->getErrors());

            return;
        }

        $this->success([
            'conversation' => [
                'id' => $conversation->public_id,
                'title' => $conversation->title,
                'status' => $conversation->status,
                'message_count' => $conversation->message_count,
                'created' => $conversation->created ? $conversation->created->toIso8601String() : null,
            ],
        ], 201);
    }

    /**
     * List user's conversations.
     *
     * GET /api/v2/chat/conversations
     *
     * @return void
     */
    public function listConversations(): void
    {
        $this->request->allowMethod(['GET']);

        if (!$this->checkAiChatFeature()) {
            return;
        }

        $conversationsTable = $this->fetchTable('ChatConversations');

        $query = $conversationsTable->find()
            ->where([
                'ChatConversations.organization_id' => $this->currentOrgId,
                'ChatConversations.user_id' => $this->currentUserId,
            ])
            ->orderBy(['ChatConversations.modified' => 'DESC']);

        $status = $this->request->getQuery('status');
        if ($status) {
            $query->where(['ChatConversations.status' => $status]);
        }

        $limit = min((int)($this->request->getQuery('limit', '20')), 50);
        $page = max((int)($this->request->getQuery('page', '1')), 1);
        $query->limit($limit)->offset(($page - 1) * $limit);

        $conversations = $query->toArray();

        $this->success([
            'conversations' => array_map(function ($c) {
                return [
                    'id' => $c->public_id,
                    'title' => $c->title,
                    'status' => $c->status,
                    'message_count' => $c->message_count,
                    'input_tokens_used' => $c->input_tokens_used,
                    'output_tokens_used' => $c->output_tokens_used,
                    'created' => $c->created ? $c->created->toIso8601String() : null,
                    'modified' => $c->modified ? $c->modified->toIso8601String() : null,
                ];
            }, $conversations),
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * Get a conversation with messages.
     *
     * GET /api/v2/chat/conversations/{id}
     *
     * @param string $id Conversation public_id.
     * @return void
     */
    public function viewConversation(string $id): void
    {
        $this->request->allowMethod(['GET']);

        if (!$this->checkAiChatFeature()) {
            return;
        }

        $conversationsTable = $this->fetchTable('ChatConversations');

        $conversation = $conversationsTable->find('byPublicId', publicId: $id)
            ->contain(['ChatMessages' => [
                'sort' => ['ChatMessages.created' => 'ASC', 'ChatMessages.id' => 'ASC'],
            ]])
            ->where([
                'ChatConversations.organization_id' => $this->currentOrgId,
                'ChatConversations.user_id' => $this->currentUserId,
            ])
            ->first();

        if (!$conversation) {
            $this->error('Conversation not found.', 404);

            return;
        }

        $messages = [];
        if (!empty($conversation->chat_messages)) {
            foreach ($conversation->chat_messages as $msg) {
                $messages[] = [
                    'id' => $msg->id,
                    'role' => $msg->role,
                    'content' => $msg->content,
                    'tool_calls' => $msg->tool_calls ? json_decode($msg->tool_calls, true) : null,
                    'tool_results' => $msg->tool_results ? json_decode($msg->tool_results, true) : null,
                    'input_tokens' => $msg->input_tokens,
                    'output_tokens' => $msg->output_tokens,
                    'created' => $msg->created ? $msg->created->toIso8601String() : null,
                ];
            }
        }

        $this->success([
            'conversation' => [
                'id' => $conversation->public_id,
                'title' => $conversation->title,
                'status' => $conversation->status,
                'message_count' => $conversation->message_count,
                'input_tokens_used' => $conversation->input_tokens_used,
                'output_tokens_used' => $conversation->output_tokens_used,
                'created' => $conversation->created ? $conversation->created->toIso8601String() : null,
                'modified' => $conversation->modified ? $conversation->modified->toIso8601String() : null,
            ],
            'messages' => $messages,
        ]);
    }

    /**
     * Delete a conversation.
     *
     * DELETE /api/v2/chat/conversations/{id}
     *
     * @param string $id Conversation public_id.
     * @return void
     */
    public function deleteConversation(string $id): void
    {
        $this->request->allowMethod(['DELETE']);

        if (!$this->checkAiChatFeature()) {
            return;
        }

        $conversationsTable = $this->fetchTable('ChatConversations');

        $conversation = $conversationsTable->find('byPublicId', publicId: $id)
            ->where([
                'ChatConversations.organization_id' => $this->currentOrgId,
                'ChatConversations.user_id' => $this->currentUserId,
            ])
            ->first();

        if (!$conversation) {
            $this->error('Conversation not found.', 404);

            return;
        }

        if ($conversationsTable->delete($conversation)) {
            $this->success(['message' => 'Conversation deleted.']);
        } else {
            $this->error('Failed to delete conversation.', 500);
        }
    }

    /**
     * Send a message to a conversation and get a response.
     *
     * POST /api/v2/chat/conversations/{id}/messages
     *
     * @param string $id Conversation public_id.
     * @return void
     */
    public function sendMessage(string $id): void
    {
        $this->request->allowMethod(['POST']);

        if (!$this->checkAiChatFeature()) {
            return;
        }

        $message = $this->request->getData('message');
        if (empty($message) || !is_string($message)) {
            $this->error('Message is required.', 400);

            return;
        }

        // Enforce message length limit
        if (mb_strlen($message) > 10000) {
            $this->error('Message is too long. Maximum 10,000 characters.', 400);

            return;
        }

        // Check daily message limit
        if (!$this->checkDailyMessageLimit()) {
            $this->error('Daily message limit reached. Please try again tomorrow.', 429);

            return;
        }

        $assistantService = new AssistantService();
        $result = $assistantService->chat(
            $this->currentOrgId,
            $this->currentUserId,
            $this->currentRole,
            $id,
            $message
        );

        if (isset($result['error'])) {
            $status = $result['status'] ?? 500;
            $this->error($result['error'], $status);

            return;
        }

        $this->success([
            'message' => $result['message'],
            'conversation_id' => $result['conversation_id'],
            'tokens' => $result['tokens'] ?? null,
            'tool_calls_count' => $result['tool_calls_count'] ?? 0,
        ]);
    }

    /**
     * Check if the ai_chat feature is available for the current organization.
     *
     * @return bool
     */
    private function checkAiChatFeature(): bool
    {
        try {
            $planService = new PlanService();
            $check = $planService->checkFeature($this->currentOrgId, 'ai_chat');

            if (!$check['allowed']) {
                $this->planLimitError(
                    'AI Chat is not available on your current plan. Please upgrade to access this feature.',
                    $check
                );

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            // If plan check fails, allow access (fail open for development)
            return true;
        }
    }

    /**
     * Check if the user has exceeded their daily message limit.
     *
     * @return bool True if within limit.
     */
    private function checkDailyMessageLimit(): bool
    {
        $messagesTable = $this->fetchTable('ChatMessages');
        $conversationsTable = $this->fetchTable('ChatConversations');

        $today = new DateTime('today');

        // Count user messages sent today across all conversations
        $todayCount = $messagesTable->find()
            ->innerJoin(
                ['ChatConversations' => 'chat_conversations'],
                [
                    'ChatConversations.id = ChatMessages.conversation_id',
                    'ChatConversations.organization_id' => $this->currentOrgId,
                    'ChatConversations.user_id' => $this->currentUserId,
                ]
            )
            ->where([
                'ChatMessages.role' => 'user',
                'ChatMessages.created >=' => $today,
            ])
            ->count();

        return $todayCount < self::DAILY_MESSAGE_LIMIT;
    }
}
