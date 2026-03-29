<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * RefreshToken Entity
 *
 * @property int $id
 * @property int $user_id
 * @property string $token_hash
 * @property \Cake\I18n\DateTime $expires_at
 * @property \Cake\I18n\DateTime|null $revoked_at
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Cake\I18n\DateTime $created
 *
 * @property \App\Model\Entity\User $user
 */
class RefreshToken extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'user_id' => true,
        'token_hash' => true,
        'expires_at' => true,
        'revoked_at' => true,
        'ip_address' => true,
        'user_agent' => true,
        'created' => true,
        'user' => true,
    ];

    /**
     * Fields that are hidden from serialization.
     *
     * @var array<string>
     */
    protected array $_hidden = [
        'token_hash',
    ];

    /**
     * Check if the token has been revoked.
     *
     * @return bool
     */
    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    /**
     * Check if the token has expired.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
