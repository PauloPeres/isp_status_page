<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * ChatMessage Entity
 *
 * @property int $id
 * @property int $conversation_id
 * @property string $role
 * @property string|null $content
 * @property string|null $tool_calls
 * @property string|null $tool_results
 * @property int $input_tokens
 * @property int $output_tokens
 * @property \Cake\I18n\DateTime $created
 *
 * @property \App\Model\Entity\ChatConversation $chat_conversation
 */
class ChatMessage extends Entity
{
    /**
     * Message roles
     */
    public const ROLE_USER = 'user';
    public const ROLE_ASSISTANT = 'assistant';
    public const ROLE_SYSTEM = 'system';

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'conversation_id' => true,
        'role' => true,
        'content' => true,
        'tool_calls' => true,
        'tool_results' => true,
        'input_tokens' => true,
        'output_tokens' => true,
        'created' => true,
    ];
}
