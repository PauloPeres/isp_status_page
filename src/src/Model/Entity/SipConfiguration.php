<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\Core\Configure;
use Cake\ORM\Entity;
use Cake\Utility\Security;

/**
 * SipConfiguration Entity
 *
 * Stores per-organization SIP trunk configuration for custom voice providers.
 *
 * @property int $id
 * @property int $organization_id
 * @property string $provider
 * @property string|null $sip_host
 * @property int|null $sip_port
 * @property string|null $sip_username
 * @property string|null $sip_password
 * @property string|null $sip_transport
 * @property string|null $caller_id
 * @property string|null $twilio_trunk_sid
 * @property bool $active
 * @property \Cake\I18n\DateTime|null $last_tested_at
 * @property string|null $last_test_result
 * @property string $public_id
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Organization $organization
 */
class SipConfiguration extends Entity
{
    /**
     * Provider constants
     */
    public const PROVIDER_KEEPUP_DEFAULT = 'keepup_default';
    public const PROVIDER_TWILIO_TRUNK = 'twilio_trunk';
    public const PROVIDER_CUSTOM_SIP = 'custom_sip';

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'organization_id' => true,
        'provider' => true,
        'sip_host' => true,
        'sip_port' => true,
        'sip_username' => true,
        'sip_password' => true,
        'sip_transport' => true,
        'caller_id' => true,
        'twilio_trunk_sid' => true,
        'active' => true,
        'last_tested_at' => true,
        'last_test_result' => true,
        'public_id' => true,
        'created' => true,
        'modified' => true,
    ];

    /**
     * Hidden fields excluded from JSON/array output.
     *
     * @var list<string>
     */
    protected array $_hidden = [
        'sip_password',
    ];

    /**
     * Encrypt the SIP password before storing.
     *
     * @param string|null $value The plain-text password
     * @return string|null The encrypted password
     */
    protected function _setSipPassword(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $key = Security::getSalt();

        return base64_encode(Security::encrypt($value, $key));
    }

    /**
     * Decrypt the SIP password when reading.
     *
     * @return string|null The decrypted password
     */
    protected function _getSipPassword(): ?string
    {
        $raw = $this->_fields['sip_password'] ?? null;
        if ($raw === null || $raw === '') {
            return null;
        }

        try {
            $key = Security::getSalt();
            $decoded = base64_decode($raw, true);
            if ($decoded === false) {
                return null;
            }

            return Security::decrypt($decoded, $key);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get the raw encrypted password value (for internal use).
     *
     * @return string|null
     */
    public function getRawSipPassword(): ?string
    {
        return $this->_fields['sip_password'] ?? null;
    }

    /**
     * Check if this configuration is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active === true;
    }

    /**
     * Check if this uses a Twilio SIP trunk.
     *
     * @return bool
     */
    public function isTwilioTrunk(): bool
    {
        return $this->provider === self::PROVIDER_TWILIO_TRUNK
            && !empty($this->twilio_trunk_sid);
    }
}
