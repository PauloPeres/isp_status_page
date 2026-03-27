<?php
declare(strict_types=1);

namespace App\Integration\Zabbix;

/**
 * ZabbixMapper
 *
 * Maps Zabbix API response data to the internal format used by ISP Status Page.
 * Translates Zabbix-specific fields, statuses, and severity levels to
 * standardized internal representations.
 */
class ZabbixMapper
{
    /**
     * Zabbix host availability statuses
     */
    public const HOST_AVAILABLE = 1;
    public const HOST_UNAVAILABLE = 2;
    public const HOST_UNKNOWN = 0;

    /**
     * Zabbix trigger values
     */
    public const TRIGGER_OK = 0;
    public const TRIGGER_PROBLEM = 1;

    /**
     * Zabbix trigger priorities
     */
    public const PRIORITY_NOT_CLASSIFIED = 0;
    public const PRIORITY_INFORMATION = 1;
    public const PRIORITY_WARNING = 2;
    public const PRIORITY_AVERAGE = 3;
    public const PRIORITY_HIGH = 4;
    public const PRIORITY_DISASTER = 5;

    /**
     * Map Zabbix host data to internal status format
     *
     * @param array<string, mixed> $host Zabbix host data from host.get
     * @return array{
     *     status: string,
     *     online: bool,
     *     host_id: string,
     *     host_name: string,
     *     available: int,
     *     message: string,
     *     metadata: array
     * }
     */
    public static function mapHostStatus(array $host): array
    {
        $available = (int)($host['available'] ?? self::HOST_UNKNOWN);

        $status = match ($available) {
            self::HOST_AVAILABLE => 'up',
            self::HOST_UNAVAILABLE => 'down',
            default => 'unknown',
        };

        $message = match ($available) {
            self::HOST_AVAILABLE => 'Host is available',
            self::HOST_UNAVAILABLE => 'Host is unavailable',
            default => 'Host availability unknown',
        };

        return [
            'status' => $status,
            'online' => $available === self::HOST_AVAILABLE,
            'host_id' => $host['hostid'] ?? '',
            'host_name' => $host['host'] ?? $host['name'] ?? '',
            'available' => $available,
            'message' => $message,
            'metadata' => [
                'status' => $host['status'] ?? null,
                'interfaces' => $host['interfaces'] ?? [],
            ],
        ];
    }

    /**
     * Map Zabbix trigger data to internal format
     *
     * @param array<string, mixed> $trigger Zabbix trigger data from trigger.get
     * @return array{
     *     status: string,
     *     problem: bool,
     *     trigger_id: string,
     *     description: string,
     *     priority: int,
     *     severity: string,
     *     value: int,
     *     last_change: string,
     *     message: string,
     *     hosts: array
     * }
     */
    public static function mapTriggerStatus(array $trigger): array
    {
        $value = (int)($trigger['value'] ?? self::TRIGGER_OK);
        $priority = (int)($trigger['priority'] ?? self::PRIORITY_NOT_CLASSIFIED);

        $status = $value === self::TRIGGER_OK ? 'up' : 'down';
        $problem = $value === self::TRIGGER_PROBLEM;

        $message = $problem
            ? 'Trigger in PROBLEM state: ' . ($trigger['description'] ?? 'Unknown trigger')
            : 'Trigger OK';

        $hosts = [];
        if (!empty($trigger['hosts']) && is_array($trigger['hosts'])) {
            foreach ($trigger['hosts'] as $host) {
                $hosts[] = $host['name'] ?? $host['host'] ?? '';
            }
        }

        return [
            'status' => $status,
            'problem' => $problem,
            'trigger_id' => $trigger['triggerid'] ?? '',
            'description' => $trigger['description'] ?? '',
            'priority' => $priority,
            'severity' => self::mapSeverity($priority),
            'value' => $value,
            'last_change' => isset($trigger['lastchange'])
                ? date('Y-m-d H:i:s', (int)$trigger['lastchange'])
                : '',
            'message' => $message,
            'hosts' => $hosts,
        ];
    }

    /**
     * Map multiple triggers to internal format
     *
     * @param array<array<string, mixed>> $triggers Array of Zabbix trigger data
     * @return array<array>
     */
    public static function mapTriggers(array $triggers): array
    {
        return array_map(
            [self::class, 'mapTriggerStatus'],
            $triggers
        );
    }

    /**
     * Map Zabbix priority level to internal severity string
     *
     * @param int $priority Zabbix trigger priority (0-5)
     * @return string Internal severity ('critical', 'major', 'minor', 'maintenance')
     */
    public static function mapSeverity(int $priority): string
    {
        return match ($priority) {
            self::PRIORITY_DISASTER => 'critical',
            self::PRIORITY_HIGH => 'critical',
            self::PRIORITY_AVERAGE => 'major',
            self::PRIORITY_WARNING => 'minor',
            self::PRIORITY_INFORMATION => 'maintenance',
            default => 'minor',
        };
    }

    /**
     * Map Zabbix item/metric data to internal format
     *
     * @param array<string, mixed> $item Zabbix item data
     * @param array<string, mixed>|null $lastValue Last history value
     * @return array{
     *     item_id: string,
     *     key: string,
     *     name: string,
     *     value: float|null,
     *     units: string,
     *     last_update: string
     * }
     */
    public static function mapMetric(array $item, ?array $lastValue = null): array
    {
        return [
            'item_id' => $item['itemid'] ?? '',
            'key' => $item['key_'] ?? '',
            'name' => $item['name'] ?? '',
            'value' => $lastValue !== null ? (float)($lastValue['value'] ?? 0) : null,
            'units' => $item['units'] ?? '',
            'last_update' => isset($lastValue['clock'])
                ? date('Y-m-d H:i:s', (int)$lastValue['clock'])
                : '',
        ];
    }
}
