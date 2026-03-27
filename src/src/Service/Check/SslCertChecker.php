<?php
declare(strict_types=1);

namespace App\Service\Check;

use App\Model\Entity\Monitor;
use Cake\Log\Log;

/**
 * SSL Certificate Checker
 *
 * Checks SSL certificate validity, expiration, and chain for a given host.
 * Uses PHP's stream_socket_client with SSL context to retrieve certificate info.
 */
class SslCertChecker extends AbstractChecker
{
    /**
     * Stream socket client callable (for testing)
     *
     * @var callable|null
     */
    protected $socketFactory = null;

    /**
     * Constructor
     *
     * @param callable|null $socketFactory Optional factory for creating SSL connections (for testing)
     */
    public function __construct(?callable $socketFactory = null)
    {
        $this->socketFactory = $socketFactory;
    }

    /**
     * Execute SSL certificate check
     *
     * @param \App\Model\Entity\Monitor $monitor The monitor to check
     * @return array Check result
     */
    protected function executeCheck(Monitor $monitor): array
    {
        $startTime = microtime(true);
        $config = $monitor->getConfiguration();

        $host = $config['host'] ?? $monitor->target ?? '';
        $port = (int)($config['port'] ?? 443);
        $warningDays = (int)($config['warning_days'] ?? 30);

        try {
            $certInfo = $this->getCertificateInfo($host, $port, $monitor->timeout ?? 10);
            $responseTime = $this->calculateResponseTime($startTime);

            if ($certInfo === null) {
                return $this->buildErrorResult(
                    "Could not retrieve SSL certificate from {$host}:{$port}",
                    $responseTime,
                    ['host' => $host, 'port' => $port]
                );
            }

            // Parse certificate dates
            $validFrom = new \DateTime('@' . $certInfo['validFrom_time_t']);
            $validTo = new \DateTime('@' . $certInfo['validTo_time_t']);
            $now = new \DateTime();

            // Calculate days remaining
            $daysRemaining = (int)$now->diff($validTo)->format('%r%a');

            $metadata = [
                'host' => $host,
                'port' => $port,
                'issuer' => $certInfo['issuer']['O'] ?? $certInfo['issuer']['CN'] ?? 'Unknown',
                'subject' => $certInfo['subject']['CN'] ?? 'Unknown',
                'valid_from' => $validFrom->format('Y-m-d H:i:s'),
                'valid_to' => $validTo->format('Y-m-d H:i:s'),
                'days_remaining' => $daysRemaining,
            ];

            // Check if certificate is expired
            if ($daysRemaining < 0) {
                Log::warning("SSL certificate expired for {$host}:{$port}, expired {$daysRemaining} days ago");

                return $this->buildErrorResult(
                    "SSL certificate expired {$daysRemaining} days ago",
                    $responseTime,
                    $metadata
                );
            }

            // Check if certificate is not yet valid
            if ($validFrom > $now) {
                return $this->buildErrorResult(
                    'SSL certificate is not yet valid',
                    $responseTime,
                    $metadata
                );
            }

            // Check if certificate is expiring soon (within warning_days)
            if ($daysRemaining <= $warningDays) {
                Log::info("SSL certificate for {$host}:{$port} expires in {$daysRemaining} days");

                return $this->buildDegradedResult(
                    $responseTime,
                    "SSL certificate expires in {$daysRemaining} days",
                    null,
                    $metadata
                );
            }

            // Certificate is valid and not expiring soon
            return $this->buildSuccessResult(
                $responseTime,
                null,
                $metadata
            );
        } catch (\Exception $e) {
            $responseTime = $this->calculateResponseTime($startTime);

            Log::error("SSL certificate check failed for {$host}:{$port}", [
                'monitor_id' => $monitor->id,
                'error' => $e->getMessage(),
            ]);

            return $this->buildErrorResult(
                $e->getMessage(),
                $responseTime,
                ['host' => $host, 'port' => $port]
            );
        }
    }

    /**
     * Get certificate information from a remote host
     *
     * @param string $host The hostname to connect to
     * @param int $port The port number (default 443)
     * @param int $timeout Connection timeout in seconds
     * @return array|null Certificate info array or null on failure
     */
    protected function getCertificateInfo(string $host, int $port, int $timeout): ?array
    {
        if ($this->socketFactory !== null) {
            return ($this->socketFactory)($host, $port, $timeout);
        }

        $context = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $errno = 0;
        $errstr = '';
        $stream = @stream_socket_client(
            "ssl://{$host}:{$port}",
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if ($stream === false) {
            Log::error("Failed to connect to {$host}:{$port}: {$errstr} (errno: {$errno})");

            return null;
        }

        $params = stream_context_get_params($stream);
        fclose($stream);

        if (!isset($params['options']['ssl']['peer_certificate'])) {
            return null;
        }

        $certResource = $params['options']['ssl']['peer_certificate'];
        $certInfo = openssl_x509_parse($certResource);

        return $certInfo ?: null;
    }

    /**
     * Validate monitor configuration
     *
     * @param \App\Model\Entity\Monitor $monitor The monitor to validate
     * @return bool True if valid
     */
    public function validateConfiguration(Monitor $monitor): bool
    {
        $config = $monitor->getConfiguration();
        $host = $config['host'] ?? $monitor->target ?? '';

        if (empty($host)) {
            Log::warning("SSL monitor {$monitor->id} has no host configured");

            return false;
        }

        // Validate port if provided
        $port = $config['port'] ?? 443;
        if ($port < 1 || $port > 65535) {
            Log::warning("SSL monitor {$monitor->id} has invalid port: {$port}");

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
        return 'ssl';
    }

    /**
     * Get human-readable checker name
     *
     * @return string Checker name
     */
    public function getName(): string
    {
        return 'SSL Certificate Checker';
    }
}
