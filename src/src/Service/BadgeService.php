<?php
declare(strict_types=1);

namespace App\Service;

use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Badge Service
 *
 * Generates shields.io-style SVG badges for monitor uptime, status, and response time.
 */
class BadgeService
{
    use LocatorAwareTrait;

    /**
     * Generate uptime badge SVG
     *
     * @param int $monitorId Monitor ID
     * @param int $days Number of days to calculate uptime over
     * @return string SVG string
     */
    public function generateUptime(int $monitorId, int $days = 30): string
    {
        try {
            $monitorsTable = $this->fetchTable('Monitors');
            $monitor = $monitorsTable->get($monitorId);

            $uptime = $this->calculateUptime($monitorId, $days);
            $uptimeStr = number_format($uptime, 1) . '%';

            // Color based on uptime
            if ($uptime >= 99.5) {
                $color = '#43A047'; // green
            } elseif ($uptime >= 95.0) {
                $color = '#FDD835'; // yellow
            } else {
                $color = '#E53935'; // red
            }

            return $this->generateBadgeSvg('uptime', $uptimeStr, $color);
        } catch (\Exception $e) {
            Log::error("BadgeService::generateUptime failed: " . $e->getMessage());

            return $this->generateErrorBadge('error');
        }
    }

    /**
     * Generate status badge SVG
     *
     * @param int $monitorId Monitor ID
     * @return string SVG string
     */
    public function generateStatus(int $monitorId): string
    {
        try {
            $monitorsTable = $this->fetchTable('Monitors');
            $monitor = $monitorsTable->get($monitorId);

            $statusText = strtolower($monitor->status);
            $color = match ($monitor->status) {
                'up' => '#43A047',
                'down' => '#E53935',
                'degraded' => '#FDD835',
                default => '#9E9E9E',
            };

            return $this->generateBadgeSvg('status', $statusText, $color);
        } catch (\Exception $e) {
            Log::error("BadgeService::generateStatus failed: " . $e->getMessage());

            return $this->generateErrorBadge('error');
        }
    }

    /**
     * Generate response time badge SVG
     *
     * @param int $monitorId Monitor ID
     * @return string SVG string
     */
    public function generateResponseTime(int $monitorId): string
    {
        try {
            $avgResponseTime = $this->calculateAvgResponseTime($monitorId);
            $responseStr = $avgResponseTime . 'ms';

            // Color based on response time
            if ($avgResponseTime < 200) {
                $color = '#43A047'; // green
            } elseif ($avgResponseTime < 500) {
                $color = '#FDD835'; // yellow
            } else {
                $color = '#E53935'; // red
            }

            return $this->generateBadgeSvg('response time', $responseStr, $color);
        } catch (\Exception $e) {
            Log::error("BadgeService::generateResponseTime failed: " . $e->getMessage());

            return $this->generateErrorBadge('error');
        }
    }

    /**
     * Generate an error badge SVG
     *
     * @param string $message Error message to display
     * @return string SVG string
     */
    public function generateErrorBadge(string $message): string
    {
        return $this->generateBadgeSvg('monitor', $message, '#9E9E9E');
    }

    /**
     * Calculate uptime percentage for a monitor over a given number of days
     *
     * @param int $monitorId Monitor ID
     * @param int $days Number of days
     * @return float Uptime percentage
     */
    private function calculateUptime(int $monitorId, int $days): float
    {
        $checksTable = $this->fetchTable('MonitorChecks');
        $since = DateTime::now()->modify("-{$days} days");

        $totalChecks = $checksTable->find()
            ->where([
                'monitor_id' => $monitorId,
                'created >=' => $since,
            ])
            ->count();

        if ($totalChecks === 0) {
            return 100.0;
        }

        $successChecks = $checksTable->find()
            ->where([
                'monitor_id' => $monitorId,
                'status' => 'up',
                'created >=' => $since,
            ])
            ->count();

        return round(($successChecks / $totalChecks) * 100, 2);
    }

    /**
     * Calculate average response time for a monitor (last 24h)
     *
     * @param int $monitorId Monitor ID
     * @return int Average response time in milliseconds
     */
    private function calculateAvgResponseTime(int $monitorId): int
    {
        $checksTable = $this->fetchTable('MonitorChecks');
        $since = DateTime::now()->modify('-24 hours');

        $result = $checksTable->find()
            ->where([
                'monitor_id' => $monitorId,
                'created >=' => $since,
                'response_time IS NOT' => null,
            ])
            ->select(['avg_time' => $checksTable->find()->func()->avg('response_time')])
            ->disableHydration()
            ->first();

        $avg = $result['avg_time'] ?? 0;

        return (int)round((float)$avg);
    }

    /**
     * Generate a shields.io-style SVG badge
     *
     * @param string $label Left side label
     * @param string $value Right side value
     * @param string $color Color for the value background
     * @return string SVG string
     */
    private function generateBadgeSvg(string $label, string $value, string $color): string
    {
        $labelWidth = max(strlen($label) * 6.5 + 10, 40);
        $valueWidth = max(strlen($value) * 6.5 + 10, 40);
        $totalWidth = $labelWidth + $valueWidth;

        $labelX = $labelWidth / 2;
        $valueX = $labelWidth + ($valueWidth / 2);

        $labelEsc = htmlspecialchars($label, ENT_XML1);
        $valueEsc = htmlspecialchars($value, ENT_XML1);
        $colorEsc = htmlspecialchars($color, ENT_XML1);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$totalWidth}" height="20" role="img" aria-label="{$labelEsc}: {$valueEsc}">
  <title>{$labelEsc}: {$valueEsc}</title>
  <linearGradient id="s" x2="0" y2="100%">
    <stop offset="0" stop-color="#bbb" stop-opacity=".1"/>
    <stop offset="1" stop-opacity=".1"/>
  </linearGradient>
  <clipPath id="r">
    <rect width="{$totalWidth}" height="20" rx="3" fill="#fff"/>
  </clipPath>
  <g clip-path="url(#r)">
    <rect width="{$labelWidth}" height="20" fill="#555"/>
    <rect x="{$labelWidth}" width="{$valueWidth}" height="20" fill="{$colorEsc}"/>
    <rect width="{$totalWidth}" height="20" fill="url(#s)"/>
  </g>
  <g fill="#fff" text-anchor="middle" font-family="Verdana,Geneva,DejaVu Sans,sans-serif" text-rendering="geometricPrecision" font-size="110">
    <text aria-hidden="true" x="{$labelX}0" y="150" fill="#010101" fill-opacity=".3" transform="scale(.1)" textLength="{$labelWidth}0">{$labelEsc}</text>
    <text x="{$labelX}0" y="140" transform="scale(.1)" fill="#fff" textLength="{$labelWidth}0">{$labelEsc}</text>
    <text aria-hidden="true" x="{$valueX}0" y="150" fill="#010101" fill-opacity=".3" transform="scale(.1)" textLength="{$valueWidth}0">{$valueEsc}</text>
    <text x="{$valueX}0" y="140" transform="scale(.1)" fill="#fff" textLength="{$valueWidth}0">{$valueEsc}</text>
  </g>
</svg>
SVG;
    }
}
