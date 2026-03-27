<?php
declare(strict_types=1);

namespace App\Integration\Ixc;

/**
 * IXC Data Mapper
 *
 * Transforms IXC API response data into the internal standardized format
 * used by the ISP Status Page application.
 */
class IxcMapper
{
    /**
     * Map IXC service status response to internal format
     *
     * @param array<string, mixed> $data Raw IXC API service status data
     * @return array{
     *     status: string,
     *     online: bool,
     *     message: string,
     *     last_seen: string|null,
     *     metadata: array
     * }
     */
    public static function mapServiceStatus(array $data): array
    {
        $connectionStatus = strtolower($data['connection_status'] ?? 'unknown');
        $serviceStatus = strtolower($data['status'] ?? 'unknown');

        $online = $connectionStatus === 'online' && $serviceStatus === 'active';
        $status = self::resolveServiceStatus($connectionStatus, $serviceStatus);

        return [
            'status' => $status,
            'online' => $online,
            'message' => self::buildServiceMessage($data),
            'last_seen' => $data['last_seen'] ?? null,
            'metadata' => [
                'service_id' => $data['service_id'] ?? null,
                'customer_name' => $data['customer_name'] ?? null,
                'plan' => $data['plan'] ?? null,
                'connection_status' => $connectionStatus,
                'service_status' => $serviceStatus,
                'signal_quality' => $data['signal_quality'] ?? null,
                'equipment' => $data['equipment'] ?? [],
            ],
        ];
    }

    /**
     * Map IXC equipment status response to internal format
     *
     * @param array<string, mixed> $data Raw IXC API equipment status data
     * @return array{
     *     status: string,
     *     online: bool,
     *     message: string,
     *     last_seen: string|null,
     *     metadata: array
     * }
     */
    public static function mapEquipmentStatus(array $data): array
    {
        $equipmentStatus = strtolower($data['status'] ?? 'unknown');
        $online = $equipmentStatus === 'online';

        $status = match ($equipmentStatus) {
            'online' => 'up',
            'offline' => 'down',
            'warning', 'degraded' => 'degraded',
            default => 'unknown',
        };

        // Check for threshold violations that indicate degraded status
        if ($status === 'up') {
            $status = self::checkEquipmentThresholds($data);
        }

        return [
            'status' => $status,
            'online' => $online,
            'message' => self::buildEquipmentMessage($data),
            'last_seen' => $data['last_update'] ?? null,
            'metadata' => [
                'equipment_id' => $data['equipment_id'] ?? null,
                'type' => $data['type'] ?? null,
                'name' => $data['name'] ?? null,
                'cpu_usage' => $data['cpu_usage'] ?? null,
                'memory_usage' => $data['memory_usage'] ?? null,
                'temperature' => $data['temperature'] ?? null,
                'uptime' => $data['uptime'] ?? null,
                'ports_total' => $data['ports_total'] ?? null,
                'ports_active' => $data['ports_active'] ?? null,
            ],
        ];
    }

    /**
     * Map IXC tickets response to internal format
     *
     * @param array<string, mixed> $data Raw IXC API tickets data
     * @return array<int, array>
     */
    public static function mapTickets(array $data): array
    {
        $tickets = $data['data'] ?? [];
        $mapped = [];

        foreach ($tickets as $ticket) {
            $mapped[] = [
                'id' => $ticket['id'] ?? null,
                'subject' => $ticket['subject'] ?? '',
                'status' => $ticket['status'] ?? 'unknown',
                'priority' => $ticket['priority'] ?? 'low',
                'customer_id' => $ticket['customer_id'] ?? null,
                'created_at' => $ticket['created_at'] ?? null,
                'category' => $ticket['category'] ?? null,
            ];
        }

        return $mapped;
    }

    /**
     * Map internal status to IXC-compatible format
     *
     * @param string $internalStatus Internal status ('up', 'down', 'degraded', 'unknown')
     * @return string IXC status
     */
    public static function mapToIxcStatus(string $internalStatus): string
    {
        return match ($internalStatus) {
            'up' => 'online',
            'down' => 'offline',
            'degraded' => 'warning',
            default => 'unknown',
        };
    }

    /**
     * Resolve the overall service status from connection and service statuses
     *
     * @param string $connectionStatus Connection status
     * @param string $serviceStatus Service status
     * @return string Internal status
     */
    protected static function resolveServiceStatus(string $connectionStatus, string $serviceStatus): string
    {
        if ($connectionStatus === 'online' && $serviceStatus === 'active') {
            return 'up';
        }

        if ($connectionStatus === 'offline' || $serviceStatus === 'disabled' || $serviceStatus === 'cancelled') {
            return 'down';
        }

        if ($serviceStatus === 'suspended' || $connectionStatus === 'intermittent') {
            return 'degraded';
        }

        return 'unknown';
    }

    /**
     * Build a human-readable service status message
     *
     * @param array<string, mixed> $data Service data
     * @return string Status message
     */
    protected static function buildServiceMessage(array $data): string
    {
        $serviceName = $data['customer_name'] ?? 'Unknown';
        $plan = $data['plan'] ?? '';
        $connectionStatus = $data['connection_status'] ?? 'unknown';

        $message = "Service for {$serviceName}";
        if ($plan) {
            $message .= " ({$plan})";
        }
        $message .= " is {$connectionStatus}";

        if (isset($data['signal_quality'])) {
            $message .= " - Signal: {$data['signal_quality']}%";
        }

        return $message;
    }

    /**
     * Build a human-readable equipment status message
     *
     * @param array<string, mixed> $data Equipment data
     * @return string Status message
     */
    protected static function buildEquipmentMessage(array $data): string
    {
        $name = $data['name'] ?? $data['equipment_id'] ?? 'Unknown';
        $type = $data['type'] ?? '';
        $status = $data['status'] ?? 'unknown';

        $message = "{$type} {$name} is {$status}";

        $metrics = [];
        if (isset($data['cpu_usage'])) {
            $metrics[] = "CPU: {$data['cpu_usage']}%";
        }
        if (isset($data['memory_usage'])) {
            $metrics[] = "Memory: {$data['memory_usage']}%";
        }
        if (isset($data['temperature'])) {
            $metrics[] = "Temp: {$data['temperature']}C";
        }

        if (!empty($metrics)) {
            $message .= ' (' . implode(', ', $metrics) . ')';
        }

        return $message;
    }

    /**
     * Check equipment metrics against default thresholds
     *
     * Returns 'degraded' if any metric exceeds its threshold, otherwise 'up'.
     *
     * @param array<string, mixed> $data Equipment data
     * @param array<string, int|float> $thresholds Custom thresholds
     * @return string Status ('up' or 'degraded')
     */
    protected static function checkEquipmentThresholds(
        array $data,
        array $thresholds = []
    ): string {
        $defaults = [
            'cpu_usage' => 90,
            'memory_usage' => 90,
            'temperature' => 70,
        ];

        $thresholds = array_merge($defaults, $thresholds);

        if (isset($data['cpu_usage']) && $data['cpu_usage'] >= $thresholds['cpu_usage']) {
            return 'degraded';
        }

        if (isset($data['memory_usage']) && $data['memory_usage'] >= $thresholds['memory_usage']) {
            return 'degraded';
        }

        if (isset($data['temperature']) && $data['temperature'] >= $thresholds['temperature']) {
            return 'degraded';
        }

        return 'up';
    }
}
