<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Subscriber Entity
 *
 * @property int $id
 * @property string $email
 * @property string|null $name
 * @property string|null $verification_token
 * @property bool $verified
 * @property \Cake\I18n\DateTime|null $verified_at
 * @property bool $active
 * @property string|null $unsubscribe_token
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Subscription[] $subscriptions
 */
class Subscriber extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'email' => true,
        'name' => true,
        'verification_token' => true,
        'verified' => true,
        'verified_at' => true,
        'active' => true,
        'unsubscribe_token' => true,
        'created' => true,
        'modified' => true,
        'subscriptions' => true,
    ];

    /**
     * Check if subscriber is verified
     *
     * @return bool
     */
    public function isVerified(): bool
    {
        return $this->verified === true;
    }

    /**
     * Check if subscriber is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active === true;
    }

    /**
     * Check if subscriber can receive notifications
     *
     * @return bool
     */
    public function canReceiveNotifications(): bool
    {
        return $this->isVerified() && $this->isActive();
    }

    /**
     * Generate verification token
     *
     * @return string
     */
    public function generateVerificationToken(): string
    {
        $this->verification_token = bin2hex(random_bytes(32));

        return $this->verification_token;
    }

    /**
     * Generate unsubscribe token
     *
     * @return string
     */
    public function generateUnsubscribeToken(): string
    {
        $this->unsubscribe_token = bin2hex(random_bytes(32));

        return $this->unsubscribe_token;
    }
}
