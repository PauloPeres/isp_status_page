<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Setting Entity
 *
 * @property int $id
 * @property string $key
 * @property string|null $value
 * @property string $type
 * @property string|null $description
 * @property \Cake\I18n\DateTime $modified
 */
class Setting extends Entity
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
        'key' => true,
        'value' => true,
        'type' => true,
        'description' => true,
        'modified' => true,
    ];

    /**
     * Get the typed value based on the type field
     *
     * @return mixed
     */
    public function getTypedValue(): mixed
    {
        if ($this->value === null) {
            return null;
        }

        return match ($this->type) {
            'integer' => (int)$this->value,
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($this->value, true),
            default => $this->value,
        };
    }

    /**
     * Set value and automatically determine type if not set
     *
     * @param mixed $value The value to set
     * @return string|null
     */
    protected function _setValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        // Auto-detect type if not already set
        if (empty($this->type)) {
            $this->type = match (true) {
                is_int($value) => 'integer',
                is_bool($value) => 'boolean',
                is_array($value) => 'json',
                default => 'string',
            };
        }

        // Convert value to string for storage
        return match ($this->type) {
            'integer' => (string)$value,
            'boolean' => $value ? 'true' : 'false',
            'json' => json_encode($value),
            default => (string)$value,
        };
    }
}
