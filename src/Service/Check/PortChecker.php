<?php
declare(strict_types=1);

namespace App\Service\Check;

use App\Model\Entity\Monitor;
use Cake\Log\Log;

/**
 * Port Checker
 *
 * Performs TCP port connectivity checks.
 * Validates that a specific port is open and responding on a host.
 * Measures connection time.
 */
class PortChecker extends AbstractChecker
{
    /**
     * Execute port check
     *
     * @param \App\Model\Entity\Monitor $monitor The monitor to check
     * @return array Check result
     */
    protected function executeCheck(Monitor $monitor): array
    {
        $startTime = microtime(true);

        try {
            // Parse target into host and port
            [$host, $port] = $this->parseTarget($monitor->target);

            // Attempt TCP connection
            Log::debug("Attempting TCP connection", [
                'monitor_id' => $monitor->id,
                'host' => $host,
                'port' => $port,
                'timeout' => $monitor->timeout,
            ]);

            $connected = $this->connectToPort($host, $port, $monitor->timeout);
            $responseTime = $this->calculateResponseTime($startTime);

            if (!$connected) {
                return $this->buildErrorResult(
                    "Port {$port} is closed or filtered on {$host}",
                    $responseTime,
                    [
                        'host' => $host,
                        'port' => $port,
                    ]
                );
            }

            Log::debug("TCP connection successful", [
                'monitor_id' => $monitor->id,
                'host' => $host,
                'port' => $port,
                'response_time' => $responseTime,
            ]);

            // Check if degraded (slow connection)
            if ($this->isDegraded($monitor, $responseTime)) {
                return $this->buildDegradedResult(
                    $responseTime,
                    "Connection time is high ({$responseTime}ms)",
                    null,
                    [
                        'host' => $host,
                        'port' => $port,
                        'threshold' => $monitor->timeout * 1000 * 0.8,
                    ]
                );
            }

            // Success!
            return $this->buildSuccessResult(
                $responseTime,
                null,
                [
                    'host' => $host,
                    'port' => $port,
                    'protocol' => 'tcp',
                ]
            );
        } catch (\Exception $e) {
            $responseTime = $this->calculateResponseTime($startTime);

            Log::error("Port check failed for {$monitor->target}", [
                'monitor_id' => $monitor->id,
                'error' => $e->getMessage(),
            ]);

            return $this->buildErrorResult(
                $this->formatErrorMessage($e),
                $responseTime,
                [
                    'target' => $monitor->target,
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

        // Validate target format (must be host:port)
        if (!$this->isValidTarget($monitor->target)) {
            Log::warning("Monitor {$monitor->id} has invalid target: {$monitor->target}");

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
        return 'port';
    }

    /**
     * Get human-readable checker name
     *
     * @return string Checker name
     */
    public function getName(): string
    {
        return 'Port/TCP Checker';
    }

    /**
     * Parse target into host and port
     *
     * @param string $target Target string (host:port)
     * @return array [host, port]
     * @throws \InvalidArgumentException If target format is invalid
     */
    protected function parseTarget(string $target): array
    {
        $target = trim($target);

        // Handle IPv6 addresses with port: [2001:db8::1]:8080
        if (preg_match('/^\[([^\]]+)\]:(\d+)$/', $target, $matches)) {
            return [$matches[1], (int)$matches[2]];
        }

        // Handle standard host:port format
        if (preg_match('/^(.+):(\d+)$/', $target, $matches)) {
            return [$matches[1], (int)$matches[2]];
        }

        throw new \InvalidArgumentException("Invalid target format: {$target}. Expected host:port");
    }

    /**
     * Connect to port via TCP socket
     *
     * @param string $host Target host
     * @param int $port Target port
     * @param int $timeout Timeout in seconds
     * @return bool True if connection successful
     */
    protected function connectToPort(string $host, int $port, int $timeout): bool
    {
        // Use stream_socket_client for better control
        $errno = 0;
        $errstr = '';

        // For IPv6, wrap host in brackets
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $host = "[{$host}]";
        }

        $socket = @stream_socket_client(
            "tcp://{$host}:{$port}",
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT
        );

        if ($socket === false) {
            Log::debug("Failed to connect to port", [
                'host' => $host,
                'port' => $port,
                'errno' => $errno,
                'error' => $errstr,
            ]);

            return false;
        }

        // Close the connection
        fclose($socket);

        return true;
    }

    /**
     * Check if target is valid
     *
     * @param string $target Target to validate
     * @return bool True if valid
     */
    protected function isValidTarget(string $target): bool
    {
        try {
            [$host, $port] = $this->parseTarget($target);

            // Validate port range
            if ($port < 1 || $port > 65535) {
                return false;
            }

            // Validate host is not empty
            if (empty($host)) {
                return false;
            }

            // Check if valid IP address or hostname
            if (filter_var($host, FILTER_VALIDATE_IP)) {
                return true;
            }

            // Check if valid hostname
            if (preg_match('/^[a-z0-9]([a-z0-9\-]{0,61}[a-z0-9])?(\.[a-z0-9]([a-z0-9\-]{0,61}[a-z0-9])?)*$/i', $host)) {
                return true;
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
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
            '/Connection refused/' => 'Connection refused - port may be closed',
            '/Connection timed out/' => 'Connection timeout - host not responding',
            '/Invalid target format/' => $message, // Keep original for format errors
            '/No route to host/' => 'Host unreachable - no network route',
            '/Network is unreachable/' => 'Network unreachable',
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
