<?php
declare(strict_types=1);

namespace App\Integration;

use Cake\Log\Log;
use Psr\Log\LogLevel;

/**
 * AbstractIntegration
 *
 * Base abstract class providing common functionality for all integrations.
 * Concrete integration adapters should extend this class and implement
 * the remaining abstract methods.
 */
abstract class AbstractIntegration implements IntegrationInterface
{
    /**
     * Integration configuration
     *
     * @var array<string, mixed>
     */
    protected array $config = [];

    /**
     * Connection status
     *
     * @var bool
     */
    protected bool $connected = false;

    /**
     * Last error message
     *
     * @var string|null
     */
    protected ?string $lastError = null;

    /**
     * Integration name (human-readable)
     *
     * @var string
     */
    protected string $name = 'Generic Integration';

    /**
     * Integration type identifier
     *
     * @var string
     */
    protected string $type = 'generic';

    /**
     * Enable debug logging
     *
     * @var bool
     */
    protected bool $debug = false;

    /**
     * Constructor
     *
     * @param array<string, mixed> $config Integration configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->debug = $config['debug'] ?? false;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @inheritDoc
     */
    public function isConnected(): bool
    {
        return $this->connected;
    }

    /**
     * Get last error message
     *
     * @return string|null
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Set last error message
     *
     * @param string|null $error Error message
     * @return void
     */
    protected function setLastError(?string $error): void
    {
        $this->lastError = $error;
    }

    /**
     * Get configuration value
     *
     * @param string $key Configuration key
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    protected function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Set configuration value
     *
     * @param string $key Configuration key
     * @param mixed $value Configuration value
     * @return void
     */
    protected function setConfig(string $key, mixed $value): void
    {
        $this->config[$key] = $value;
    }

    /**
     * Log a message
     *
     * @param string $level Log level (debug, info, warning, error)
     * @param string $message Log message
     * @param array<string, mixed> $context Additional context
     * @return void
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        if ($level === LogLevel::DEBUG && !$this->debug) {
            return;
        }

        $context['integration'] = $this->type;
        $context['integration_name'] = $this->name;

        Log::write($level, $message, $context);
    }

    /**
     * Log debug message
     *
     * @param string $message Log message
     * @param array<string, mixed> $context Additional context
     * @return void
     */
    protected function logDebug(string $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * Log info message
     *
     * @param string $message Log message
     * @param array<string, mixed> $context Additional context
     * @return void
     */
    protected function logInfo(string $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * Log warning message
     *
     * @param string $message Log message
     * @param array<string, mixed> $context Additional context
     * @return void
     */
    protected function logWarning(string $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * Log error message
     *
     * @param string $message Log message
     * @param array<string, mixed> $context Additional context
     * @return void
     */
    protected function logError(string $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * Validate required configuration keys
     *
     * @param array<string> $requiredKeys Required configuration keys
     * @return bool True if all required keys are present
     * @throws \InvalidArgumentException if required keys are missing
     */
    protected function validateConfig(array $requiredKeys): bool
    {
        $missing = [];

        foreach ($requiredKeys as $key) {
            if (!isset($this->config[$key]) || $this->config[$key] === '') {
                $missing[] = $key;
            }
        }

        if (!empty($missing)) {
            $message = sprintf(
                'Missing required configuration keys for %s integration: %s',
                $this->name,
                implode(', ', $missing)
            );

            $this->setLastError($message);
            $this->logError($message);

            throw new \InvalidArgumentException($message);
        }

        return true;
    }

    /**
     * Build error response array
     *
     * @param string $message Error message
     * @param array<string, mixed> $additional Additional data
     * @return array<string, mixed>
     */
    protected function buildErrorResponse(string $message, array $additional = []): array
    {
        $this->setLastError($message);
        $this->logError($message, $additional);

        return array_merge([
            'success' => false,
            'error' => $message,
        ], $additional);
    }

    /**
     * Build success response array
     *
     * @param string $message Success message
     * @param array<string, mixed> $data Additional data
     * @return array<string, mixed>
     */
    protected function buildSuccessResponse(string $message, array $data = []): array
    {
        $this->setLastError(null);
        $this->logInfo($message, $data);

        return array_merge([
            'success' => true,
            'message' => $message,
        ], $data);
    }

    /**
     * Abstract methods to be implemented by concrete classes
     */

    /**
     * @inheritDoc
     */
    abstract public function connect(): bool;

    /**
     * @inheritDoc
     */
    abstract public function testConnection(): array;

    /**
     * @inheritDoc
     */
    abstract public function getStatus(string $resourceId): array;

    /**
     * @inheritDoc
     */
    abstract public function getMetrics(string $resourceId, array $params = []): array;

    /**
     * @inheritDoc
     */
    abstract public function disconnect(): void;
}
