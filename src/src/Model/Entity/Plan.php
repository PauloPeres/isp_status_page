<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Plan Entity
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $stripe_price_id_monthly
 * @property string|null $stripe_price_id_yearly
 * @property int $price_monthly
 * @property int $price_yearly
 * @property int $monitor_limit
 * @property int $check_interval_min
 * @property int $team_member_limit
 * @property int $status_page_limit
 * @property int $api_rate_limit
 * @property int $data_retention_days
 * @property string|null $features
 * @property int $display_order
 * @property bool $active
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property bool $is_free
 * @property \App\Model\Entity\Organization[] $organizations
 */
class Plan extends Entity
{
    /**
     * Plan slug constants
     */
    public const SLUG_FREE = 'free';
    public const SLUG_PRO = 'pro';
    public const SLUG_BUSINESS = 'business';

    /**
     * Value indicating unlimited for a limit field
     */
    public const UNLIMITED = -1;

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'name' => true,
        'slug' => true,
        'stripe_price_id_monthly' => true,
        'stripe_price_id_yearly' => true,
        'price_monthly' => true,
        'price_yearly' => true,
        'monitor_limit' => true,
        'check_interval_min' => true,
        'team_member_limit' => true,
        'status_page_limit' => true,
        'api_rate_limit' => true,
        'data_retention_days' => true,
        'features' => true,
        'display_order' => true,
        'active' => true,
        'created' => true,
        'modified' => true,
        'organizations' => true,
    ];

    /**
     * Virtual fields to expose
     *
     * @var array<string>
     */
    protected array $_virtual = ['is_free'];

    /**
     * Check if a limit field is set to unlimited
     *
     * @param string $field The limit field name (e.g., 'monitor_limit', 'team_member_limit')
     * @return bool
     */
    public function isUnlimited(string $field): bool
    {
        if (!$this->has($field)) {
            return false;
        }

        return (int)$this->get($field) === self::UNLIMITED;
    }

    /**
     * Get the monthly price formatted as currency string
     *
     * @return string Formatted price (e.g., "$15.00")
     */
    public function getMonthlyPriceFormatted(): string
    {
        return sprintf('$%s', number_format($this->price_monthly / 100, 2));
    }

    /**
     * Get the yearly price formatted as currency string
     *
     * @return string Formatted price (e.g., "$144.00")
     */
    public function getYearlyPriceFormatted(): string
    {
        return sprintf('$%s', number_format($this->price_yearly / 100, 2));
    }

    /**
     * Get the features as an associative array
     *
     * @return array<string, bool>
     */
    public function getFeatures(): array
    {
        if (empty($this->features)) {
            return [];
        }

        if (is_array($this->features)) {
            return $this->features;
        }

        $decoded = json_decode($this->features, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Check if this plan has a specific feature enabled
     *
     * @param string $feature The feature key to check
     * @return bool
     */
    public function hasFeature(string $feature): bool
    {
        $features = $this->getFeatures();

        return !empty($features[$feature]);
    }

    /**
     * Virtual field getter for is_free
     *
     * @return bool
     */
    protected function _getIsFree(): bool
    {
        return $this->slug === self::SLUG_FREE;
    }

    /**
     * Set features from array
     *
     * @param array|string $value Features array or JSON string
     * @return string
     */
    protected function _setFeatures(array|string $value): string
    {
        if (is_array($value)) {
            return json_encode($value);
        }

        return $value;
    }
}
