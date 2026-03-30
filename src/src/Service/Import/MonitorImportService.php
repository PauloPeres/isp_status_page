<?php
declare(strict_types=1);

namespace App\Service\Import;

use Cake\Log\Log;

/**
 * MonitorImportService (C-03)
 *
 * Parses competitor monitor export formats and normalizes them
 * into the standard ISP Status Page monitor structure.
 *
 * Supported formats: UptimeRobot, Pingdom, BetterUptime, generic CSV.
 */
class MonitorImportService
{
    /**
     * Detect the format of the import data and parse it.
     *
     * @param string $content Raw content (CSV or JSON)
     * @param string|null $format Force a specific format (uptimerobot, pingdom, betteruptime, csv)
     * @return array{monitors: array, errors: array, format: string}
     */
    public function parse(string $content, ?string $format = null): array
    {
        if ($format === null) {
            $format = $this->detectFormat($content);
        }

        return match ($format) {
            'uptimerobot' => $this->parseUptimeRobot($content),
            'pingdom' => $this->parsePingdom($content),
            'betteruptime' => $this->parseBetterUptime($content),
            default => $this->parseGenericCsv($content),
        };
    }

    /**
     * Auto-detect the import format from content structure.
     */
    public function detectFormat(string $content): string
    {
        $trimmed = trim($content);

        // Check if JSON
        if (str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[')) {
            $decoded = json_decode($trimmed, true);
            if ($decoded !== null) {
                // UptimeRobot JSON export has "stat" and "monitors" keys
                if (isset($decoded['stat']) && isset($decoded['monitors'])) {
                    return 'uptimerobot';
                }
                // BetterUptime JSON has "data" array with "attributes"
                if (isset($decoded['data']) && is_array($decoded['data'])) {
                    $first = $decoded['data'][0] ?? [];
                    if (isset($first['attributes'])) {
                        return 'betteruptime';
                    }
                }
            }
        }

        // Check CSV headers
        $firstLine = strtolower(trim(explode("\n", $trimmed)[0] ?? ''));

        // Pingdom CSV export has "name,hostname,type,resolution"
        if (str_contains($firstLine, 'hostname') && str_contains($firstLine, 'resolution')) {
            return 'pingdom';
        }

        // UptimeRobot CSV has "URL,Friendly Name,Type"
        if (str_contains($firstLine, 'friendly name') && str_contains($firstLine, 'url')) {
            return 'uptimerobot';
        }

        return 'csv';
    }

    /**
     * Parse UptimeRobot export (JSON or CSV).
     *
     * UptimeRobot JSON structure:
     * { "stat": "ok", "monitors": [{ "friendly_name": "", "url": "", "type": 1, ... }] }
     *
     * UptimeRobot types: 1=HTTP(s), 2=keyword, 3=ping, 4=port
     */
    private function parseUptimeRobot(string $content): array
    {
        $monitors = [];
        $errors = [];

        $trimmed = trim($content);

        // JSON format
        if (str_starts_with($trimmed, '{')) {
            $decoded = json_decode($trimmed, true);
            if (!$decoded || !isset($decoded['monitors'])) {
                return ['monitors' => [], 'errors' => ['Invalid UptimeRobot JSON format'], 'format' => 'uptimerobot'];
            }

            foreach ($decoded['monitors'] as $i => $m) {
                try {
                    $monitors[] = $this->mapUptimeRobotMonitor($m);
                } catch (\Exception $e) {
                    $errors[] = "Row {$i}: {$e->getMessage()}";
                }
            }

            return ['monitors' => $monitors, 'errors' => $errors, 'format' => 'uptimerobot'];
        }

        // CSV format: URL,Friendly Name,Type,Sub Type,Keyword Type,Keyword Value,HTTP Method,Port,Interval
        $lines = array_filter(explode("\n", $content), 'trim');
        $header = str_getcsv(strtolower(array_shift($lines)));

        foreach ($lines as $i => $line) {
            $row = str_getcsv($line);
            if (count($row) < count($header)) {
                continue;
            }
            $data = array_combine($header, $row);

            try {
                $typeNum = (int)($data['type'] ?? 1);
                $monitors[] = [
                    'name' => $data['friendly name'] ?? $data['name'] ?? "Monitor " . ($i + 1),
                    'type' => $this->mapUptimeRobotType($typeNum),
                    'configuration' => $this->buildConfig(
                        $this->mapUptimeRobotType($typeNum),
                        $data['url'] ?? '',
                        (int)($data['port'] ?? 0)
                    ),
                    'check_interval' => (int)($data['interval'] ?? 300),
                    'active' => true,
                ];
            } catch (\Exception $e) {
                $errors[] = "Row " . ($i + 1) . ": {$e->getMessage()}";
            }
        }

        return ['monitors' => $monitors, 'errors' => $errors, 'format' => 'uptimerobot'];
    }

    /**
     * Parse Pingdom export (CSV).
     *
     * Format: Name,Hostname,Type,Resolution,ContactIds,SendToEmail,...
     */
    private function parsePingdom(string $content): array
    {
        $monitors = [];
        $errors = [];

        $lines = array_filter(explode("\n", $content), 'trim');
        $header = str_getcsv(strtolower(array_shift($lines)));

        foreach ($lines as $i => $line) {
            $row = str_getcsv($line);
            if (count($row) < 2) {
                continue;
            }
            $data = array_combine($header, array_pad($row, count($header), ''));

            try {
                $type = strtolower($data['type'] ?? 'http');
                $hostname = $data['hostname'] ?? '';
                $resolution = (int)($data['resolution'] ?? 5);

                $mappedType = match ($type) {
                    'http', 'https' => 'http',
                    'httpcustom' => 'http',
                    'tcp' => 'port',
                    'ping' => 'ping',
                    'dns' => 'http',
                    default => 'http',
                };

                $config = $this->buildConfig($mappedType, $hostname, (int)($data['port'] ?? 0));

                // Pingdom uses URL if http type
                if ($mappedType === 'http' && !str_starts_with($hostname, 'http')) {
                    $config['url'] = "https://{$hostname}";
                }

                $monitors[] = [
                    'name' => $data['name'] ?? $hostname,
                    'type' => $mappedType,
                    'configuration' => $config,
                    'check_interval' => $resolution * 60, // Pingdom uses minutes
                    'active' => true,
                ];
            } catch (\Exception $e) {
                $errors[] = "Row " . ($i + 1) . ": {$e->getMessage()}";
            }
        }

        return ['monitors' => $monitors, 'errors' => $errors, 'format' => 'pingdom'];
    }

    /**
     * Parse BetterUptime export (JSON API format).
     *
     * Format: { "data": [{ "id": "...", "type": "monitor", "attributes": { ... } }] }
     */
    private function parseBetterUptime(string $content): array
    {
        $monitors = [];
        $errors = [];

        $decoded = json_decode(trim($content), true);
        if (!$decoded || !isset($decoded['data'])) {
            return ['monitors' => [], 'errors' => ['Invalid BetterUptime JSON format'], 'format' => 'betteruptime'];
        }

        foreach ($decoded['data'] as $i => $item) {
            $attrs = $item['attributes'] ?? [];

            try {
                $monitorType = strtolower($attrs['monitor_type'] ?? 'status');
                $mappedType = match ($monitorType) {
                    'status', 'expected_status_code' => 'http',
                    'keyword', 'keyword_absence' => 'http',
                    'ping', 'icmp' => 'ping',
                    'tcp' => 'port',
                    'udp' => 'port',
                    default => 'http',
                };

                $url = $attrs['url'] ?? '';
                $config = $this->buildConfig($mappedType, $url, (int)($attrs['port'] ?? 0));

                if (isset($attrs['expected_status_codes'])) {
                    $config['expected_status_code'] = (int)$attrs['expected_status_codes'][0];
                }

                $monitors[] = [
                    'name' => $attrs['pronounceable_name'] ?? $attrs['url'] ?? "Monitor " . ($i + 1),
                    'type' => $mappedType,
                    'configuration' => $config,
                    'check_interval' => (int)($attrs['check_frequency'] ?? 180),
                    'active' => !($attrs['paused'] ?? false),
                ];
            } catch (\Exception $e) {
                $errors[] = "Item {$i}: {$e->getMessage()}";
            }
        }

        return ['monitors' => $monitors, 'errors' => $errors, 'format' => 'betteruptime'];
    }

    /**
     * Parse generic CSV (ISP Status Page native format).
     */
    private function parseGenericCsv(string $content): array
    {
        $monitors = [];
        $errors = [];

        $lines = array_filter(explode("\n", $content), 'trim');
        if (count($lines) < 2) {
            return ['monitors' => [], 'errors' => ['CSV must have header + at least one row'], 'format' => 'csv'];
        }

        $header = str_getcsv(strtolower(array_shift($lines)));

        foreach ($lines as $i => $line) {
            $row = str_getcsv($line);
            if (count($row) < 2) {
                continue;
            }
            $data = array_combine($header, array_pad($row, count($header), ''));

            try {
                $type = $data['type'] ?? 'http';
                $url = $data['url'] ?? $data['host'] ?? '';
                $port = (int)($data['port'] ?? 0);

                $monitors[] = [
                    'name' => $data['name'] ?? "Monitor " . ($i + 1),
                    'type' => $type,
                    'configuration' => $this->buildConfig($type, $url, $port),
                    'check_interval' => (int)($data['check_interval'] ?? $data['interval'] ?? 300),
                    'tags' => !empty($data['tags']) ? $data['tags'] : null,
                    'active' => true,
                ];
            } catch (\Exception $e) {
                $errors[] = "Row " . ($i + 1) . ": {$e->getMessage()}";
            }
        }

        return ['monitors' => $monitors, 'errors' => $errors, 'format' => 'csv'];
    }

    /**
     * Map UptimeRobot JSON monitor to standard format.
     */
    private function mapUptimeRobotMonitor(array $m): array
    {
        $typeNum = (int)($m['type'] ?? 1);
        $type = $this->mapUptimeRobotType($typeNum);
        $url = $m['url'] ?? '';
        $port = (int)($m['port'] ?? 0);

        return [
            'name' => $m['friendly_name'] ?? $url,
            'type' => $type,
            'configuration' => $this->buildConfig($type, $url, $port),
            'check_interval' => (int)($m['interval'] ?? 300),
            'active' => ($m['status'] ?? 0) !== 0,
        ];
    }

    /**
     * Map UptimeRobot numeric type to ISP Status type.
     */
    private function mapUptimeRobotType(int $type): string
    {
        return match ($type) {
            1 => 'http',
            2 => 'http', // keyword check → HTTP with content check
            3 => 'ping',
            4 => 'port',
            default => 'http',
        };
    }

    /**
     * Build configuration object based on monitor type.
     */
    private function buildConfig(string $type, string $target, int $port = 0): array
    {
        return match ($type) {
            'http' => ['url' => $target, 'method' => 'GET', 'expected_status_code' => 200],
            'ping' => ['host' => $this->extractHost($target)],
            'port' => ['host' => $this->extractHost($target), 'port' => $port ?: 80],
            default => ['url' => $target],
        };
    }

    /**
     * Extract hostname from a URL.
     */
    private function extractHost(string $url): string
    {
        if (str_starts_with($url, 'http')) {
            return parse_url($url, PHP_URL_HOST) ?: $url;
        }

        return $url;
    }
}
