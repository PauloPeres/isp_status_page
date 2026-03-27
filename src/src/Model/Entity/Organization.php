<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\DateTime;
use Cake\ORM\Entity;

/**
 * Organization Entity
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $plan
 * @property string|null $stripe_customer_id
 * @property string|null $stripe_subscription_id
 * @property \Cake\I18n\DateTime|null $trial_ends_at
 * @property string $timezone
 * @property string $language
 * @property string|null $custom_domain
 * @property string|null $logo_url
 * @property string|null $settings
 * @property bool $active
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\OrganizationUser[] $organization_users
 * @property \App\Model\Entity\Monitor[] $monitors
 * @property \App\Model\Entity\Incident[] $incidents
 * @property \App\Model\Entity\Integration[] $integrations
 * @property \App\Model\Entity\AlertRule[] $alert_rules
 * @property \App\Model\Entity\Subscriber[] $subscribers
 */
class Organization extends Entity
{
    /**
     * Plan constants
     */
    public const PLAN_FREE = 'free';
    public const PLAN_PRO = 'pro';
    public const PLAN_BUSINESS = 'business';

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'name' => true,
        'slug' => true,
        'plan' => true,
        'stripe_customer_id' => true,
        'stripe_subscription_id' => true,
        'trial_ends_at' => true,
        'timezone' => true,
        'language' => true,
        'custom_domain' => true,
        'logo_url' => true,
        'settings' => true,
        'active' => true,
        'created' => true,
        'modified' => true,
        'organization_users' => true,
        'monitors' => true,
        'incidents' => true,
        'integrations' => true,
        'alert_rules' => true,
        'subscribers' => true,
    ];

    /**
     * Virtual fields to expose
     *
     * @var array<string>
     */
    protected array $_virtual = ['is_free_plan', 'is_pro_plan', 'is_business_plan', 'is_trial_active'];

    /**
     * Check if organization is on the free plan
     *
     * @return bool
     */
    public function isFreePlan(): bool
    {
        return $this->plan === self::PLAN_FREE;
    }

    /**
     * Virtual field getter for is_free_plan
     *
     * @return bool
     */
    protected function _getIsFreePlan(): bool
    {
        return $this->isFreePlan();
    }

    /**
     * Check if organization is on the pro plan
     *
     * @return bool
     */
    public function isProPlan(): bool
    {
        return $this->plan === self::PLAN_PRO;
    }

    /**
     * Virtual field getter for is_pro_plan
     *
     * @return bool
     */
    protected function _getIsProPlan(): bool
    {
        return $this->isProPlan();
    }

    /**
     * Check if organization is on the business plan
     *
     * @return bool
     */
    public function isBusinessPlan(): bool
    {
        return $this->plan === self::PLAN_BUSINESS;
    }

    /**
     * Virtual field getter for is_business_plan
     *
     * @return bool
     */
    protected function _getIsBusinessPlan(): bool
    {
        return $this->isBusinessPlan();
    }

    /**
     * Check if the trial period is still active
     *
     * @return bool
     */
    public function isTrialActive(): bool
    {
        if ($this->trial_ends_at === null) {
            return false;
        }

        $now = new DateTime();

        return $this->trial_ends_at->greaterThan($now);
    }

    /**
     * Virtual field getter for is_trial_active
     *
     * @return bool
     */
    protected function _getIsTrialActive(): bool
    {
        return $this->isTrialActive();
    }

    /**
     * Check if organization is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active === true;
    }

    /**
     * Get settings as array
     *
     * @return array
     */
    public function getSettings(): array
    {
        if (empty($this->settings)) {
            return [];
        }

        if (is_array($this->settings)) {
            return $this->settings;
        }

        $decoded = json_decode($this->settings, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Set settings from array
     *
     * @param array|string $value Settings array or JSON string
     * @return string
     */
    protected function _setSettings(array|string $value): string
    {
        if (is_array($value)) {
            return json_encode($value);
        }

        return $value;
    }
}
