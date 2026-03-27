<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;
use Authentication\PasswordHasher\DefaultPasswordHasher;

/**
 * User Entity
 *
 * @property int $id
 * @property string $username
 * @property string $password
 * @property string $email
 * @property string $role
 * @property bool $active
 * @property bool $force_password_change
 * @property \Cake\I18n\DateTime|null $last_login
 * @property string|null $reset_token
 * @property \Cake\I18n\DateTime|null $reset_token_expires
 * @property bool $email_verified
 * @property string|null $email_verification_token
 * @property \Cake\I18n\DateTime|null $email_verification_sent_at
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 */
class User extends Entity
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
        'username' => true,
        'password' => true,
        'email' => true,
        'role' => true,
        'active' => true,
        'organization_id' => true,
        'force_password_change' => true,
        'last_login' => true,
        'reset_token' => true,
        'reset_token_expires' => true,
        'email_verified' => true,
        'email_verification_token' => true,
        'email_verification_sent_at' => true,
        'oauth_provider' => true,
        'oauth_id' => true,
        'language' => true,
        'timezone' => true,
        'created' => true,
        'modified' => true,
    ];

    /**
     * Fields that are excluded from JSON versions of the entity.
     *
     * @var array<string>
     */
    protected array $_hidden = [
        'password',
    ];

    /**
     * Automatically hash passwords when they are changed.
     *
     * @param string $password The password to hash
     * @return string|null Hashed password
     */
    protected function _setPassword(string $password): ?string
    {
        if (strlen($password) > 0) {
            return (new DefaultPasswordHasher())->hash($password);
        }
        return null;
    }

    /**
     * Check if user is admin
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active === true;
    }

    /**
     * Get user's full role name
     *
     * @return string
     */
    public function getRoleName(): string
    {
        $roles = [
            'admin' => 'Administrator',
            'user' => 'User',
            'viewer' => 'Viewer',
        ];

        return $roles[$this->role] ?? 'Unknown';
    }

    /**
     * Generate a unique reset token and set expiration time
     *
     * @param int $expirationHours Number of hours until token expires (default: 1)
     * @return void
     */
    public function generateResetToken(int $expirationHours = 1): void
    {
        $this->reset_token = bin2hex(random_bytes(32)); // 64 character hex string
        $this->reset_token_expires = new \DateTime("+{$expirationHours} hours");
    }

    /**
     * Check if reset token is still valid
     *
     * @return bool
     */
    public function isResetTokenValid(): bool
    {
        if (!$this->reset_token || !$this->reset_token_expires) {
            return false;
        }

        $now = new \DateTime();
        return $this->reset_token_expires > $now;
    }

    /**
     * Clear reset token after successful password reset
     *
     * @return void
     */
    public function clearResetToken(): void
    {
        $this->reset_token = null;
        $this->reset_token_expires = null;
    }

    /**
     * Generate a unique email verification token
     *
     * @return void
     */
    public function generateEmailVerificationToken(): void
    {
        $this->email_verification_token = bin2hex(random_bytes(32)); // 64 character hex string
        $this->email_verification_sent_at = new \DateTime();
        $this->email_verified = false;
    }

    /**
     * Mark email as verified and clear the verification token
     *
     * @return void
     */
    public function markEmailVerified(): void
    {
        $this->email_verified = true;
        $this->email_verification_token = null;
    }

    /**
     * Check if email verification token is still valid (24 hours)
     *
     * @return bool
     */
    public function isEmailVerificationTokenValid(): bool
    {
        if (!$this->email_verification_token || !$this->email_verification_sent_at) {
            return false;
        }

        $sentAt = $this->email_verification_sent_at;
        if ($sentAt instanceof \Cake\I18n\DateTime) {
            $expires = $sentAt->modify('+24 hours');
        } else {
            $expires = (new \DateTime($sentAt->format('Y-m-d H:i:s')))->modify('+24 hours');
        }

        return new \DateTime() < $expires;
    }
}
