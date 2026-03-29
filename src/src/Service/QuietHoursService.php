<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * QuietHoursService
 *
 * Determines whether alert notifications should be suppressed based on
 * the organization's quiet hours configuration. Quiet hours allow orgs
 * to silence non-critical (or all) alerts during specified time windows,
 * typically overnight or on weekends.
 */
class QuietHoursService
{
    use LocatorAwareTrait;

    /**
     * Check if the given organization is currently within its quiet hours window.
     *
     * @param int $orgId The organization ID.
     * @return bool True if the current time falls within the quiet hours window.
     */
    public function isInQuietHours(int $orgId): bool
    {
        try {
            $orgsTable = $this->fetchTable('Organizations');
            $org = $orgsTable->find()
                ->select([
                    'quiet_hours_enabled',
                    'quiet_hours_start',
                    'quiet_hours_end',
                    'quiet_hours_timezone',
                ])
                ->where(['id' => $orgId])
                ->first();

            if (!$org) {
                return false;
            }

            if (empty($org->quiet_hours_enabled)) {
                return false;
            }

            $timezone = $org->quiet_hours_timezone ?: 'UTC';
            $start = $org->quiet_hours_start ?: '22:00';
            $end = $org->quiet_hours_end ?: '08:00';

            try {
                $tz = new \DateTimeZone($timezone);
            } catch (\Exception $e) {
                Log::warning("QuietHoursService: Invalid timezone '{$timezone}' for org {$orgId}, falling back to UTC");
                $tz = new \DateTimeZone('UTC');
            }

            $now = new \DateTime('now', $tz);
            $currentTime = $now->format('H:i');

            // Handle overnight periods (e.g., 22:00 - 08:00 crosses midnight)
            if ($start <= $end) {
                // Same-day window (e.g., 09:00 - 17:00)
                return $currentTime >= $start && $currentTime < $end;
            }

            // Overnight window (e.g., 22:00 - 08:00)
            return $currentTime >= $start || $currentTime < $end;
        } catch (\Exception $e) {
            Log::error("QuietHoursService: Error checking quiet hours for org {$orgId}: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Determine whether an alert of the given severity should be suppressed
     * for the specified organization.
     *
     * @param int $orgId The organization ID.
     * @param string $severity The alert severity (e.g., 'critical', 'warning', 'info').
     * @return bool True if the alert should be suppressed.
     */
    public function shouldSuppressAlert(int $orgId, string $severity): bool
    {
        if (!$this->isInQuietHours($orgId)) {
            return false;
        }

        try {
            $orgsTable = $this->fetchTable('Organizations');
            $org = $orgsTable->find()
                ->select(['quiet_hours_suppress_level'])
                ->where(['id' => $orgId])
                ->first();

            $level = $org->quiet_hours_suppress_level ?? 'non_critical';

            return match ($level) {
                'all' => true,
                'non_critical' => $severity !== 'critical',
                'none' => false,
                default => false,
            };
        } catch (\Exception $e) {
            Log::error("QuietHoursService: Error checking suppress level for org {$orgId}: {$e->getMessage()}");

            return false;
        }
    }
}
