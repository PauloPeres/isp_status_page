<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * ChatConversation Entity
 *
 * @property int $id
 * @property string $public_id
 * @property int $organization_id
 * @property int $user_id
 * @property string $title
 * @property int $message_count
 * @property int $input_tokens_used
 * @property int $output_tokens_used
 * @property string $status
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Organization $organization
 * @property \App\Model\Entity\User $user
 * @property \App\Model\Entity\ChatMessage[] $chat_messages
 */
class ChatConversation extends Entity
{
    /**
     * Conversation statuses
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ARCHIVED = 'archived';

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'public_id' => true,
        'organization_id' => true,
        'user_id' => true,
        'title' => true,
        'message_count' => true,
        'input_tokens_used' => true,
        'output_tokens_used' => true,
        'status' => true,
        'created' => true,
        'modified' => true,
        'chat_messages' => true,
    ];
}
