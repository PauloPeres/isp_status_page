<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\NotificationSchedule;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * NotificationScheduleService (C-05)
 *
 * Evaluates notification schedules to determine whether an alert
 * should be suppressed or allowed based on channel, severity,
 * time window, and day of week.
 *
 * This extends the simpler QuietHoursService with per-channel,
 * per-severity granularity.
 */
class NotificationScheduleService
{
    use LocatorAwareTrait;

    /**
     * Check if an alert should be suppressed based on notification schedules.
     *
     * @param int $orgId Organization ID
     * @param string $channel Alert channel type (email, slack, telegram, etc.)
     * @param string $severity Incident severity (critical, major, minor, maintenance)
     * @return bool True if the alert should be suppressed.
     */
    public function shouldSuppress(int $orgId, string $channel, string $severity): bool
    {
        try {
            $schedules = $this->getActiveSchedules($orgId);

            if (empty($schedules)) {
                return false;
            }

            foreach ($schedules as $schedule) {
                // Check if schedule applies to this channel + severity
                if (!$schedule->appliesToChannel($channel)) {
                    continue;
                }
                if (!$schedule->appliesToSeverity($severity)) {
                    continue;
                }

                $inWindow = $schedule->isCurrentlyActive();

                if ($schedule->action === 'suppress' && $inWindow) {
                    Log::debug("Alert suppressed by schedule '{$schedule->name}' for org {$orgId}, channel {$channel}, severity {$severity}");
                    return true;
                }

                if ($schedule->action === 'allow' && !$inWindow) {
                    Log::debug("Alert suppressed (outside allow window) by schedule '{$schedule->name}' for org {$orgId}, channel {$channel}, severity {$severity}");
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            Log::error("NotificationScheduleService error: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Get active notification schedules for an organization.
     *
     * @param int $orgId
     * @return NotificationSchedule[]
     */
    protected function getActiveSchedules(int $orgId): array
    {
        $table = $this->fetchTable('NotificationSchedules');

        return $table->find()
            ->where([
                'NotificationSchedules.organization_id' => $orgId,
                'NotificationSchedules.active' => true,
            ])
            ->all()
            ->toArray();
    }
}
