<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * IntegrationLog Entity
 *
 * @property int $id
 * @property int $integration_id
 * @property string $action
 * @property string $status
 * @property string|null $message
 * @property string|null $details
 * @property \Cake\I18n\DateTime $created
 *
 * @property \App\Model\Entity\Integration $integration
 */
class IntegrationLog extends Entity
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
        'integration_id' => true,
        'action' => true,
        'status' => true,
        'message' => true,
        'details' => true,
        'created' => true,
        'integration' => true,
    ];
}
