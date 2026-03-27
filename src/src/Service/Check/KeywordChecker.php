<?php
declare(strict_types=1);

namespace App\Service\Check;

use App\Model\Entity\Monitor;
use Cake\Http\Client;
use Cake\Log\Log;

/**
 * Keyword Checker
 *
 * Makes an HTTP request to a URL and checks if the response body
 * contains (or does not contain) a specified keyword. Extends HttpChecker
 * to reuse HTTP request logic.
 */
class KeywordChecker extends AbstractChecker
{
    /**
     * HTTP Client instance
     *
     * @var \Cake\Http\Client
     */
    protected Client $client;

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
     * Execute keyword check
     *
     * Makes HTTP request to the configured URL and checks if the
     * response body contains (or doesn't contain) the keyword.
     *
     * @param \App\Model\Entity\Monitor $monitor The monitor to check
     * @return array Check result
     */
    protected function executeCheck(Monitor $monitor): array
    {
        $startTime = microtime(true);
        $config = $monitor->getConfiguration();

        $url = $config['url'] ?? $monitor->target;
        $keyword = $config['keyword'] ?? '';
        $keywordType = $config['keyword_type'] ?? 'contains';

        try {
            $options = [
                'timeout' => $monitor->timeout,
                'headers' => [
                    'User-Agent' => 'ISP-Status-Page-Monitor/1.0',
                    'Accept' => '*/*',
                ],
                'redirect' => true,
                'ssl_verify_peer' => true,
                'ssl_verify_host' => true,
            ];

            Log::debug("Making keyword check request to: {$url}", [
                'monitor_id' => $monitor->id,
                'keyword' => $keyword,
                'keyword_type' => $keywordType,
            ]);

            $response = $this->client->get($url, [], $options);
            $responseTime = $this->calculateResponseTime($startTime);
            $statusCode = $response->getStatusCode();
            $body = $response->getStringBody();

            // Check keyword presence
            $keywordFound = stripos($body, $keyword) !== false;

            $keywordCheckPassed = match ($keywordType) {
                'not_contains' => !$keywordFound,
                default => $keywordFound, // 'contains'
            };

            if ($keywordCheckPassed) {
                return $this->buildSuccessResult(
                    $responseTime,
                    $statusCode,
                    [
                        'url' => $url,
                        'keyword' => $keyword,
                        'keyword_type' => $keywordType,
                        'keyword_found' => $keywordFound,
                    ]
                );
            }

            $message = $keywordType === 'not_contains'
                ? "Keyword '{$keyword}' was found in response (expected not_contains)"
                : "Keyword '{$keyword}' not found in response";

            return $this->buildErrorResult(
                $message,
                $responseTime,
                [
                    'url' => $url,
                    'keyword' => $keyword,
                    'keyword_type' => $keywordType,
                    'keyword_found' => $keywordFound,
                    'status_code' => $statusCode,
                ]
            );
        } catch (\Exception $e) {
            $responseTime = $this->calculateResponseTime($startTime);

            Log::error("Keyword check failed for {$url}", [
                'monitor_id' => $monitor->id,
                'error' => $e->getMessage(),
            ]);

            return $this->buildErrorResult(
                $e->getMessage(),
                $responseTime,
                [
                    'url' => $url,
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
        // Need a target URL
        $config = $monitor->getConfiguration();
        $url = $config['url'] ?? $monitor->target ?? '';

        if (empty($url)) {
            Log::warning("Keyword monitor {$monitor->id} has no URL configured");

            return false;
        }

        // Need a keyword
        $keyword = $config['keyword'] ?? '';
        if (empty($keyword)) {
            Log::warning("Keyword monitor {$monitor->id} has no keyword configured");

            return false;
        }

        // Validate keyword_type if present
        $keywordType = $config['keyword_type'] ?? 'contains';
        if (!in_array($keywordType, ['contains', 'not_contains'], true)) {
            Log::warning("Keyword monitor {$monitor->id} has invalid keyword_type: {$keywordType}");

            return false;
        }

        // Need a timeout
        if (empty($monitor->timeout) || $monitor->timeout <= 0) {
            Log::warning("Keyword monitor {$monitor->id} has invalid timeout");

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
        return 'keyword';
    }

    /**
     * Get human-readable checker name
     *
     * @return string Checker name
     */
    public function getName(): string
    {
        return 'Keyword Checker';
    }
}
