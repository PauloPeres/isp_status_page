<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * CheckRegion Entity
 *
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $endpoint_url
 * @property bool $active
 * @property \Cake\I18n\DateTime $created
 *
 * @property \App\Model\Entity\MonitorCheck[] $monitor_checks
 */
class CheckRegion extends Entity
{
    /**
     * Region code constants.
     */
    public const REGION_US_EAST_1 = 'us-east-1';
    public const REGION_EU_WEST_1 = 'eu-west-1';
    public const REGION_AP_SOUTHEAST_1 = 'ap-southeast-1';

    /**
     * Fields that can be mass assigned.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'name' => true,
        'code' => true,
        'endpoint_url' => true,
        'active' => true,
        'created' => true,
    ];

    /**
     * Check if this region is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active === true;
    }
}
