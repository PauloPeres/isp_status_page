<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\ORM\Entity;

/**
 * StatusPage Entity
 *
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property string $slug
 * @property string|null $custom_domain
 * @property string|null $theme
 * @property string|null $header_text
 * @property string|null $footer_text
 * @property string|null $monitors
 * @property bool $show_uptime_chart
 * @property bool $show_incident_history
 * @property string|null $password
 * @property bool $active
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Organization $organization
 */
class StatusPage extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_hidden = ['password'];

    protected array $_accessible = [
        'organization_id' => true,
        'name' => true,
        'slug' => true,
        'custom_domain' => true,
        'theme' => true,
        'header_text' => true,
        'footer_text' => true,
        'monitors' => true,
        'show_uptime_chart' => true,
        'show_incident_history' => true,
        'password' => true,
        'active' => true,
        'language' => true,
        'created' => true,
        'modified' => true,
    ];

    protected function _setPassword(?string $password): ?string
    {
        if ($password === null || $password === '') {
            return null;
        }

        return (new DefaultPasswordHasher())->hash($password);
    }

    /**
     * Get monitors as array of IDs
     *
     * @return array
     */
    public function getMonitorIds(): array
    {
        if (empty($this->monitors)) {
            return [];
        }

        if (is_array($this->monitors)) {
            return $this->monitors;
        }

        $decoded = json_decode($this->monitors, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Get theme configuration as array
     *
     * @return array
     */
    public function getThemeConfig(): array
    {
        if (empty($this->theme)) {
            return [];
        }

        if (is_array($this->theme)) {
            return $this->theme;
        }

        $decoded = json_decode($this->theme, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Check if status page is password protected
     *
     * @return bool
     */
    public function isPasswordProtected(): bool
    {
        return !empty($this->password);
    }

    /**
     * Check if status page is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active === true;
    }
}
