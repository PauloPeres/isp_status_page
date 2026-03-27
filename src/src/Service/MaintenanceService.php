<?php
declare(strict_types=1);

namespace App\Service;

use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Maintenance Service
 *
 * Provides methods to check if a monitor is currently in a maintenance window
 * and whether alerts should be suppressed.
 */
class MaintenanceService
{
    use LocatorAwareTrait;

    /**
     * Check if a monitor is currently in an active maintenance window
     *
     * @param int $monitorId The monitor ID to check
     * @return bool True if the monitor has an active maintenance window
     */
    public function isInMaintenance(int $monitorId): bool
    {
        try {
            $maintenanceTable = $this->fetchTable('MaintenanceWindows');
            $now = DateTime::now();

            // Find active maintenance windows that overlap with current time
            $windows = $maintenanceTable->find()
                ->where([
                    'status IN' => ['scheduled', 'in_progress'],
                    'starts_at <=' => $now,
                    'ends_at >=' => $now,
                ])
                ->all();

            foreach ($windows as $window) {
                if ($window->affectsMonitor($monitorId)) {
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            Log::error("MaintenanceService::isInMaintenance failed: " . $e->getMessage());

            return false;
        }
    }

    /**
     * Check if alerts should be suppressed for a monitor
     *
     * Returns true if the monitor is in an active maintenance window
     * that has auto_suppress_alerts enabled.
     *
     * @param int $monitorId The monitor ID to check
     * @return bool True if alerts should be suppressed
     */
    public function shouldSuppressAlert(int $monitorId): bool
    {
        try {
            $maintenanceTable = $this->fetchTable('MaintenanceWindows');
            $now = DateTime::now();

            // Find active maintenance windows with alert suppression
            $windows = $maintenanceTable->find()
                ->where([
                    'status IN' => ['scheduled', 'in_progress'],
                    'starts_at <=' => $now,
                    'ends_at >=' => $now,
                    'auto_suppress_alerts' => true,
                ])
                ->all();

            foreach ($windows as $window) {
                if ($window->affectsMonitor($monitorId)) {
                    Log::debug("Alert suppressed for monitor {$monitorId} due to maintenance window {$window->id}: {$window->title}");

                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            Log::error("MaintenanceService::shouldSuppressAlert failed: " . $e->getMessage());

            return false;
        }
    }
}
