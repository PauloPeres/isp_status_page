<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * NotificationSchedule Entity (C-05)
 *
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property string|null $channels JSON array of channel types
 * @property string|null $severities JSON array of severities
 * @property string $action suppress|allow
 * @property string|null $days_of_week JSON array of day numbers (0=Sun)
 * @property string $start_time HH:MM
 * @property string $end_time HH:MM
 * @property string $timezone
 * @property bool $active
 */
class NotificationSchedule extends Entity
{
    protected array $_accessible = [
        'organization_id' => true,
        'name' => true,
        'channels' => true,
        'severities' => true,
        'action' => true,
        'days_of_week' => true,
        'start_time' => true,
        'end_time' => true,
        'timezone' => true,
        'active' => true,
    ];

    /**
     * Get channels as array.
     */
    public function getChannelsList(): array
    {
        if (empty($this->channels)) {
            return []; // empty = all channels
        }
        $decoded = json_decode($this->channels, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Get severities as array.
     */
    public function getSeveritiesList(): array
    {
        if (empty($this->severities)) {
            return []; // empty = all severities
        }
        $decoded = json_decode($this->severities, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Get days of week as array.
     */
    public function getDaysOfWeekList(): array
    {
        if (empty($this->days_of_week)) {
            return []; // empty = every day
        }
        $decoded = json_decode($this->days_of_week, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Check if this schedule applies to a given channel.
     */
    public function appliesToChannel(string $channel): bool
    {
        $channels = $this->getChannelsList();
        return empty($channels) || in_array($channel, $channels, true);
    }

    /**
     * Check if this schedule applies to a given severity.
     */
    public function appliesToSeverity(string $severity): bool
    {
        $severities = $this->getSeveritiesList();
        return empty($severities) || in_array($severity, $severities, true);
    }

    /**
     * Check if the current time falls within this schedule's window.
     */
    public function isCurrentlyActive(): bool
    {
        try {
            $tz = new \DateTimeZone($this->timezone ?: 'UTC');
        } catch (\Exception $e) {
            $tz = new \DateTimeZone('UTC');
        }

        $now = new \DateTime('now', $tz);

        // Check day of week
        $days = $this->getDaysOfWeekList();
        if (!empty($days) && !in_array((int)$now->format('w'), $days, true)) {
            return false;
        }

        // Check time window
        $currentTime = $now->format('H:i');
        $start = $this->start_time;
        $end = $this->end_time;

        if ($start <= $end) {
            return $currentTime >= $start && $currentTime < $end;
        }

        // Overnight window
        return $currentTime >= $start || $currentTime < $end;
    }
}
