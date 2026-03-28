<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Cache\Cache;

/**
 * MonitorCacheService
 *
 * Manages caching for monitor-related data including uptime calculations,
 * dashboard summaries, and badge SVGs. Provides invalidation helpers
 * to be called after check results are saved.
 *
 * TTL values:
 * - Uptime: 60 seconds (frequently updated by checks)
 * - Dashboard summary: 30 seconds (near real-time)
 * - Badge SVG: 300 seconds (5 minutes, external consumers)
 */
class MonitorCacheService
{
    /**
     * Cache TTL constants (in seconds) — used for documentation.
     * Actual TTL is controlled by the cache engine config duration.
     * We use Cache::write which respects the engine default duration,
     * so these serve as logical grouping references.
     */
    private const UPTIME_TTL = 60;
    private const DASHBOARD_TTL = 30;
    private const BADGE_TTL = 300;
    private const PREFIX = 'monitor_';

    /**
     * Get cached uptime or compute it via UptimeCalculationService.
     *
     * @param int $monitorId Monitor ID
     * @param int $days Number of days
     * @return float Uptime percentage
     */
    public function getUptime(int $monitorId, int $days = 30): float
    {
        $key = self::PREFIX . "uptime_{$monitorId}_{$days}";
        $cached = Cache::read($key, 'default');
        if ($cached !== null && $cached !== false) {
            return (float)$cached;
        }

        $service = new UptimeCalculationService();
        $value = $service->getUptime($monitorId, $days);
        Cache::write($key, $value, 'default');

        return $value;
    }

    /**
     * Get cached average response time or compute it.
     *
     * @param int $monitorId Monitor ID
     * @param int $days Number of days
     * @return float|null Average response time in ms
     */
    public function getAvgResponseTime(int $monitorId, int $days = 1): ?float
    {
        $key = self::PREFIX . "response_{$monitorId}_{$days}";
        $cached = Cache::read($key, 'default');
        if ($cached !== null && $cached !== false) {
            return (float)$cached;
        }

        $service = new UptimeCalculationService();
        $value = $service->getAvgResponseTime($monitorId, $days);
        if ($value !== null) {
            Cache::write($key, $value, 'default');
        }

        return $value;
    }

    /**
     * Cache dashboard summary data. Uses Cache::remember pattern.
     *
     * @param int $orgId Organization ID
     * @param callable $compute Callback that returns the summary array
     * @return array Dashboard summary data
     */
    public function getDashboardSummary(int $orgId, callable $compute): array
    {
        $key = self::PREFIX . "dashboard_{$orgId}";

        return Cache::remember($key, $compute, 'default');
    }

    /**
     * Cache badge SVG output.
     *
     * @param string $type Badge type (uptime, status, response)
     * @param int $monitorId Monitor ID
     * @param callable $compute Callback that returns the SVG string
     * @return string SVG string
     */
    public function getBadgeSvg(string $type, int $monitorId, callable $compute): string
    {
        $key = self::PREFIX . "badge_{$type}_{$monitorId}";
        $cached = Cache::read($key, 'default');
        if ($cached !== null && $cached !== false) {
            return (string)$cached;
        }

        $value = $compute();
        Cache::write($key, $value, 'default');

        return $value;
    }

    /**
     * Invalidate all cache entries for a monitor.
     * Called after a new check result is saved.
     *
     * @param int $monitorId Monitor ID
     * @return void
     */
    public function invalidateMonitor(int $monitorId): void
    {
        // Uptime caches for common day ranges
        Cache::delete(self::PREFIX . "uptime_{$monitorId}_1", 'default');
        Cache::delete(self::PREFIX . "uptime_{$monitorId}_7", 'default');
        Cache::delete(self::PREFIX . "uptime_{$monitorId}_30", 'default');

        // Response time caches
        Cache::delete(self::PREFIX . "response_{$monitorId}_1", 'default');
        Cache::delete(self::PREFIX . "response_{$monitorId}_7", 'default');
        Cache::delete(self::PREFIX . "response_{$monitorId}_30", 'default');

        // Badge caches
        Cache::delete(self::PREFIX . "badge_uptime_{$monitorId}", 'default');
        Cache::delete(self::PREFIX . "badge_status_{$monitorId}", 'default');
        Cache::delete(self::PREFIX . "badge_response_{$monitorId}", 'default');
    }

    /**
     * Invalidate dashboard cache for an organization.
     *
     * @param int $orgId Organization ID
     * @return void
     */
    public function invalidateDashboard(int $orgId): void
    {
        Cache::delete(self::PREFIX . "dashboard_{$orgId}", 'default');
    }
}
