<?php
declare(strict_types=1);

namespace App\Service\Check;

use App\Model\Entity\Monitor;
use Cake\Http\Client;
use Cake\Log\Log;

/**
 * HTTP Checker
 *
 * Performs HTTP/HTTPS checks on web endpoints.
 * Validates response status codes, measures response time,
 * and can optionally validate response content.
 */
class HttpChecker extends AbstractChecker
{
    /**
     * HTTP Client instance
     *
     * @var \Cake\Http\Client
     */
    protected Client $client;

    /**
     * Default headers to send with requests
     *
     * @var array
     */
    protected array $defaultHeaders = [
        'User-Agent' => 'ISP-Status-Page-Monitor/1.0',
        'Accept' => '*/*',
    ];

    /**
     * Constructor
     *
     * @param \Cake\Http\Client|null $client HTTP client instance (for testing)
     */
    public function __construct(?Client $client = null)
    {
        $this->client = $client ?? new Client();
    }

    /**
     * Execute HTTP check
     *
     * @param \App\Model\Entity\Monitor $monitor The monitor to check
     * @return array Check result
     */
    protected function executeCheck(Monitor $monitor): array
    {
        $startTime = microtime(true);

        try {
            // Parse target URL
            $url = $this->prepareUrl($monitor->target);

            // Prepare request options
            $options = $this->buildRequestOptions($monitor);

            // Execute HTTP request
            Log::debug("Making HTTP request to: {$url}", [
                'monitor_id' => $monitor->id,
                'timeout' => $monitor->timeout,
            ]);

            $response = $this->client->get($url, [], $options);

            // Calculate response time
            $responseTime = $this->calculateResponseTime($startTime);

            // Get status code
            $statusCode = $response->getStatusCode();

            Log::debug("HTTP response received", [
                'monitor_id' => $monitor->id,
                'status_code' => $statusCode,
                'response_time' => $responseTime,
            ]);

            // Validate status code
            $expectedStatusCode = $monitor->expected_status_code ?? 200;

            if ($statusCode !== $expectedStatusCode) {
                return $this->buildErrorResult(
                    "Unexpected status code: {$statusCode} (expected {$expectedStatusCode})",
                    $responseTime,
                    [
                        'status_code' => $statusCode,
                        'expected' => $expectedStatusCode,
                        'url' => $url,
                    ]
                );
            }

            // Check if response is degraded (slow)
            if ($this->isDegraded($monitor, $responseTime)) {
                return $this->buildDegradedResult(
                    $responseTime,
                    "Response time is high ({$responseTime}ms)",
                    $statusCode,
                    [
                        'url' => $url,
                        'threshold' => $monitor->timeout * 1000 * 0.8,
                    ]
                );
            }

            // Success!
            return $this->buildSuccessResult(
                $responseTime,
                $statusCode,
                [
                    'url' => $url,
                    'content_type' => $response->getHeaderLine('Content-Type'),
                    'content_length' => $response->getHeaderLine('Content-Length'),
                ]
            );
        } catch (\Exception $e) {
            $responseTime = $this->calculateResponseTime($startTime);

            Log::error("HTTP check failed for {$monitor->target}", [
                'monitor_id' => $monitor->id,
                'error' => $e->getMessage(),
            ]);

            return $this->buildErrorResult(
                $this->formatErrorMessage($e),
                $responseTime,
                [
                    'url' => $monitor->target,
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

        // Validate target is a valid URL
        if (!$this->isValidUrl($monitor->target)) {
            Log::warning("Monitor {$monitor->id} has invalid URL: {$monitor->target}");

            return false;
        }

        // Validate expected status code is valid
        if (isset($monitor->expected_status_code)) {
            $code = $monitor->expected_status_code;
            if ($code < 100 || $code > 599) {
                Log::warning("Monitor {$monitor->id} has invalid expected status code: {$code}");

                return false;
            }
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
        return 'http';
    }

    /**
     * Get human-readable checker name
     *
     * @return string Checker name
     */
    public function getName(): string
    {
        return 'HTTP/HTTPS Checker';
    }

    /**
     * Prepare URL for request
     *
     * Ensures URL has a scheme (defaults to https://)
     *
     * @param string $url Target URL
     * @return string Prepared URL
     */
    protected function prepareUrl(string $url): string
    {
        $url = trim($url);

        // Add scheme if missing
        if (!preg_match('~^https?://~i', $url)) {
            $url = 'https://' . $url;
        }

        return $url;
    }

    /**
     * Build request options
     *
     * @param \App\Model\Entity\Monitor $monitor The monitor
     * @return array Request options
     */
    protected function buildRequestOptions(Monitor $monitor): array
    {
        $options = [
            'timeout' => $monitor->timeout,
            'headers' => $this->defaultHeaders,
            'redirect' => true, // Follow redirects
            'ssl_verify_peer' => true,
            'ssl_verify_host' => true,
        ];

        // Parse custom configuration if present
        if (!empty($monitor->configuration)) {
            $config = is_string($monitor->configuration)
                ? json_decode($monitor->configuration, true)
                : $monitor->configuration;

            if (is_array($config)) {
                // Merge custom headers
                if (isset($config['headers']) && is_array($config['headers'])) {
                    $options['headers'] = array_merge(
                        $options['headers'],
                        $config['headers']
                    );
                }

                // Allow disabling SSL verification (for self-signed certs)
                if (isset($config['verify_ssl']) && $config['verify_ssl'] === false) {
                    $options['ssl_verify_peer'] = false;
                    $options['ssl_verify_host'] = false;
                }

                // Custom HTTP method
                if (isset($config['method'])) {
                    $options['method'] = strtoupper($config['method']);
                }
            }
        }

        return $options;
    }

    /**
     * Check if URL is valid
     *
     * @param string $url URL to validate
     * @return bool True if valid
     */
    protected function isValidUrl(string $url): bool
    {
        $url = trim($url);

        // Parse original URL to check for invalid schemes first
        $parts = parse_url($url);

        // If URL already has a scheme, it must be http or https
        if (isset($parts['scheme'])) {
            $scheme = strtolower($parts['scheme']);
            if (!in_array($scheme, ['http', 'https'], true)) {
                return false; // Reject ftp://, javascript:, etc.
            }
        }

        // Prepare URL (adds https:// if no scheme)
        $preparedUrl = $this->prepareUrl($url);

        // Validate URL format
        if (!filter_var($preparedUrl, FILTER_VALIDATE_URL)) {
            return false;
        }

        // Parse prepared URL
        $parts = parse_url($preparedUrl);

        // Must have scheme and host
        if (empty($parts['scheme']) || empty($parts['host'])) {
            return false;
        }

        // Final scheme check (should always be http/https at this point)
        $scheme = strtolower($parts['scheme']);
        if (!in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        return true;
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
            '/Connection refused/' => 'Connection refused - service may be down',
            '/Connection timed out/' => 'Connection timeout - service is not responding',
            '/Could not resolve host/' => 'DNS resolution failed - hostname not found',
            '/SSL certificate problem/' => 'SSL certificate error',
            '/Failed to connect/' => 'Failed to connect to server',
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
