<?php
declare(strict_types=1);

namespace App\Service\Check;

use App\Model\Entity\Monitor;
use Cake\Log\Log;

/**
 * Ping Checker
 *
 * Performs ICMP ping checks on hosts.
 * Cross-platform support for Linux, macOS, and Windows.
 * Extracts latency and packet loss information.
 */
class PingChecker extends AbstractChecker
{
    /**
     * Number of ping packets to send
     *
     * @var int
     */
    protected int $packetCount = 4;

    /**
     * Execute ping check
     *
     * @param \App\Model\Entity\Monitor $monitor The monitor to check
     * @return array Check result
     */
    protected function executeCheck(Monitor $monitor): array
    {
        $startTime = microtime(true);

        try {
            // Validate target host
            $host = $this->prepareHost($monitor->target);

            // Build ping command based on OS
            $command = $this->buildPingCommand($host, $monitor->timeout);

            // Execute ping
            Log::debug("Executing ping command", [
                'monitor_id' => $monitor->id,
                'host' => $host,
                'command' => $command,
            ]);

            $output = $this->executePing($command);
            $responseTime = $this->calculateResponseTime($startTime);

            // Parse ping output
            $result = $this->parsePingOutput($output);

            if ($result['success'] === false) {
                return $this->buildErrorResult(
                    $result['error'] ?? 'Ping failed - host unreachable',
                    $responseTime,
                    [
                        'host' => $host,
                        'packet_loss' => $result['packet_loss'] ?? 100,
                    ]
                );
            }

            // Check if degraded (high packet loss or high latency)
            if ($result['packet_loss'] > 0 || $this->isDegraded($monitor, (int)$result['avg_latency'])) {
                $reason = $result['packet_loss'] > 0
                    ? "Packet loss: {$result['packet_loss']}%"
                    : "High latency: {$result['avg_latency']}ms";

                return $this->buildDegradedResult(
                    (int)$result['avg_latency'],
                    $reason,
                    null,
                    [
                        'host' => $host,
                        'packet_loss' => $result['packet_loss'],
                        'min_latency' => $result['min_latency'],
                        'avg_latency' => $result['avg_latency'],
                        'max_latency' => $result['max_latency'],
                    ]
                );
            }

            // Success!
            return $this->buildSuccessResult(
                (int)$result['avg_latency'],
                null,
                [
                    'host' => $host,
                    'packet_loss' => $result['packet_loss'],
                    'min_latency' => $result['min_latency'],
                    'avg_latency' => $result['avg_latency'],
                    'max_latency' => $result['max_latency'],
                    'packets_sent' => $this->packetCount,
                ]
            );
        } catch (\Exception $e) {
            $responseTime = $this->calculateResponseTime($startTime);

            Log::error("Ping check failed for {$monitor->target}", [
                'monitor_id' => $monitor->id,
                'error' => $e->getMessage(),
            ]);

            return $this->buildErrorResult(
                $this->formatErrorMessage($e),
                $responseTime,
                [
                    'host' => $monitor->target,
                    'exception_type' => get_class($e),
                ]
            );
        }
    }

    /**
     * Validate monitor configuration
     *
     * @param \App\Model\Entity\Monitor $monitor The monitor to validate
     * @return bool True if valid
     */
    public function validateConfiguration(Monitor $monitor): bool
    {
        // Call parent validation first
        if (!parent::validateConfiguration($monitor)) {
            return false;
        }

        // Validate target is a valid hostname or IP
        if (!$this->isValidHost($monitor->target)) {
            Log::warning("Monitor {$monitor->id} has invalid host: {$monitor->target}");

            return false;
        }

        return true;
    }

    /**
     * Get checker type identifier
     *
     * @return string Checker type
     */
    public function getType(): string
    {
        return 'ping';
    }

    /**
     * Get human-readable checker name
     *
     * @return string Checker name
     */
    public function getName(): string
    {
        return 'Ping/ICMP Checker';
    }

    /**
     * Prepare host for ping
     *
     * Removes scheme and path if present
     *
     * @param string $host Target host
     * @return string Prepared host
     */
    protected function prepareHost(string $host): string
    {
        $host = trim($host);

        // Remove scheme if present (http://, https://)
        $host = preg_replace('~^https?://~i', '', $host);

        // Remove path if present
        $host = preg_replace('~/.*$~', '', $host);

        // Remove port if present (but not for IPv6)
        // IPv6 addresses contain colons, so we need to be careful
        // Only remove port if it's at the end and preceded by a non-colon character
        // This prevents breaking IPv6 addresses like 2001:4860:4860::8888
        if (!preg_match('/^[0-9a-f:]+$/i', $host)) {
            // Not an IPv6 address, safe to remove port
            $host = preg_replace('~:\d+$~', '', $host);
        }

        return $host;
    }

    /**
     * Build ping command based on operating system
     *
     * @param string $host Target host
     * @param int $timeout Timeout in seconds
     * @return string Ping command
     */
    protected function buildPingCommand(string $host, int $timeout): string
    {
        $os = PHP_OS_FAMILY;

        // Escape host for shell
        $escapedHost = escapeshellarg($host);

        if ($os === 'Windows') {
            // Windows: ping -n count -w timeout_ms host
            $timeoutMs = $timeout * 1000;

            return "ping -n {$this->packetCount} -w {$timeoutMs} {$escapedHost}";
        } elseif ($os === 'Darwin') {
            // macOS: ping -c count -t timeout host
            return "ping -c {$this->packetCount} -t {$timeout} {$escapedHost}";
        } else {
            // Linux/Unix: ping -c count -W timeout host
            return "ping -c {$this->packetCount} -W {$timeout} {$escapedHost}";
        }
    }

    /**
     * Execute ping command
     *
     * @param string $command Command to execute
     * @return string Command output
     */
    protected function executePing(string $command): string
    {
        $output = shell_exec($command . ' 2>&1');

        if ($output === null) {
            throw new \RuntimeException('Failed to execute ping command');
        }

        return $output;
    }

    /**
     * Parse ping output
     *
     * Extracts latency and packet loss from ping output
     *
     * @param string $output Ping command output
     * @return array Parsed result
     */
    protected function parsePingOutput(string $output): array
    {
        $os = PHP_OS_FAMILY;

        if ($os === 'Windows') {
            return $this->parseWindowsPingOutput($output);
        } else {
            return $this->parseUnixPingOutput($output);
        }
    }

    /**
     * Parse Unix/Linux/macOS ping output
     *
     * @param string $output Ping output
     * @return array Parsed result
     */
    protected function parseUnixPingOutput(string $output): array
    {
        $result = [
            'success' => false,
            'packet_loss' => 100,
            'min_latency' => 0,
            'avg_latency' => 0,
            'max_latency' => 0,
        ];

        // Extract packet loss: "4 packets transmitted, 4 received, 0% packet loss"
        if (preg_match('/(\d+)% packet loss/', $output, $matches)) {
            $result['packet_loss'] = (int)$matches[1];
        }

        // Extract latency: "rtt min/avg/max/mdev = 10.123/15.456/20.789/3.456 ms"
        if (preg_match('/rtt min\/avg\/max\/(?:mdev|stddev) = ([\d.]+)\/([\d.]+)\/([\d.]+)/', $output, $matches)) {
            $result['success'] = true;
            $result['min_latency'] = (float)$matches[1];
            $result['avg_latency'] = (float)$matches[2];
            $result['max_latency'] = (float)$matches[3];
        }

        // Alternative format: "round-trip min/avg/max = 10.123/15.456/20.789 ms"
        if (!$result['success'] && preg_match('/round-trip min\/avg\/max = ([\d.]+)\/([\d.]+)\/([\d.]+)/', $output, $matches)) {
            $result['success'] = true;
            $result['min_latency'] = (float)$matches[1];
            $result['avg_latency'] = (float)$matches[2];
            $result['max_latency'] = (float)$matches[3];
        }

        // If no latency found but no packet loss, it's an error
        if (!$result['success'] && $result['packet_loss'] < 100) {
            $result['error'] = 'Could not parse ping latency from output';
        }

        // If 100% packet loss, it's a failure
        if ($result['packet_loss'] >= 100) {
            $result['success'] = false;
            $result['error'] = 'Host unreachable - 100% packet loss';
        }

        return $result;
    }

    /**
     * Parse Windows ping output
     *
     * @param string $output Ping output
     * @return array Parsed result
     */
    protected function parseWindowsPingOutput(string $output): array
    {
        $result = [
            'success' => false,
            'packet_loss' => 100,
            'min_latency' => 0,
            'avg_latency' => 0,
            'max_latency' => 0,
        ];

        // Extract packet loss: "Packets: Sent = 4, Received = 4, Lost = 0 (0% loss)"
        if (preg_match('/Lost = \d+ \((\d+)% loss\)/', $output, $matches)) {
            $result['packet_loss'] = (int)$matches[1];
        }

        // Extract latency: "Minimum = 10ms, Maximum = 20ms, Average = 15ms"
        if (preg_match('/Minimum = (\d+)ms, Maximum = (\d+)ms, Average = (\d+)ms/', $output, $matches)) {
            $result['success'] = true;
            $result['min_latency'] = (float)$matches[1];
            $result['max_latency'] = (float)$matches[2];
            $result['avg_latency'] = (float)$matches[3];
        }

        // If 100% packet loss, it's a failure
        if ($result['packet_loss'] >= 100) {
            $result['success'] = false;
            $result['error'] = 'Host unreachable - 100% packet loss';
        }

        return $result;
    }

    /**
     * Check if host is valid
     *
     * @param string $host Host to validate
     * @return bool True if valid
     */
    protected function isValidHost(string $host): bool
    {
        // Prepare host (remove scheme, path, port)
        $preparedHost = $this->prepareHost($host);

        // Check if empty
        if (empty($preparedHost)) {
            return false;
        }

        // Check if valid IP address (IPv4 or IPv6)
        if (filter_var($preparedHost, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {
            return true;
        }

        // Check if valid hostname (basic validation)
        // Hostname rules:
        // - Can contain letters, numbers, hyphens, and dots
        // - Cannot start or end with hyphen
        // - Each label (part between dots) max 63 chars
        // - Must have at least one dot
        if (preg_match('/^([a-z0-9]([a-z0-9\-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i', $preparedHost)) {
            return true;
        }

        return false;
    }

    /**
     * Format exception message for user display
     *
     * @param \Exception $e Exception
     * @return string Formatted error message
     */
    protected function formatErrorMessage(\Exception $e): string
    {
        $message = $e->getMessage();

        // Common error patterns
        $patterns = [
            '/Failed to execute/' => 'Failed to execute ping command',
            '/unknown host/i' => 'Host not found - DNS resolution failed',
            '/cannot resolve/i' => 'Host not found - DNS resolution failed',
        ];

        foreach ($patterns as $pattern => $replacement) {
            if (preg_match($pattern, $message)) {
                return $replacement;
            }
        }

        // Return original message if no pattern matches
        return $message;
    }
}
