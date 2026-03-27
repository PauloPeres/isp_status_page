<?php
declare(strict_types=1);

namespace App\Integration\Zabbix;

use App\Integration\AbstractIntegration;

/**
 * ZabbixAdapter
 *
 * Integration adapter for Zabbix monitoring system.
 * Implements IntegrationInterface using the JSON-RPC 2.0 client
 * to communicate with Zabbix API.
 *
 * Configuration keys:
 * - base_url: Zabbix API URL (e.g., https://zabbix.example.com/api_jsonrpc.php)
 * - username: API username
 * - password: API password
 *
 * @see docs/API_INTEGRATIONS.md for Zabbix API specifications
 */
class ZabbixAdapter extends AbstractIntegration
{
    /**
     * @var string
     */
    protected string $name = 'Zabbix';

    /**
     * @var string
     */
    protected string $type = 'zabbix';

    /**
     * Zabbix JSON-RPC client
     *
     * @var \App\Integration\Zabbix\ZabbixClient
     */
    protected ZabbixClient $client;

    /**
     * Constructor
     *
     * @param array<string, mixed> $config Integration configuration
     * @param \App\Integration\Zabbix\ZabbixClient|null $client Optional client instance (for testing)
     */
    public function __construct(array $config = [], ?ZabbixClient $client = null)
    {
        parent::__construct($config);

        $this->client = $client ?? new ZabbixClient(
            $config['base_url'] ?? '',
            null,
            (int)($config['timeout'] ?? 30)
        );
    }

    /**
     * @inheritDoc
     */
    public function connect(): bool
    {
        try {
            $this->validateConfig(['base_url', 'username', 'password']);

            $this->client->login(
                $this->config['username'],
                $this->config['password']
            );

            $this->connected = true;
            $this->setLastError(null);

            $this->logInfo('Connected to Zabbix API', [
                'url' => $this->config['base_url'],
            ]);

            return true;
        } catch (\Exception $e) {
            $this->connected = false;
            $this->setLastError($e->getMessage());
            $this->logError('Failed to connect to Zabbix: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function testConnection(): array
    {
        $startTime = microtime(true);

        try {
            $connected = $this->connect();

            if (!$connected) {
                return $this->buildErrorResponse(
                    $this->getLastError() ?? 'Connection failed'
                );
            }

            // Attempt a simple API call to verify connectivity
            $apiInfo = $this->client->call('apiinfo.version', []);

            $responseTime = (microtime(true) - $startTime) * 1000;

            // Logout after test
            $this->disconnect();

            return $this->buildSuccessResponse('Conexao com Zabbix estabelecida', [
                'response_time' => round($responseTime, 2),
                'version' => $apiInfo,
            ]);
        } catch (\Exception $e) {
            $responseTime = (microtime(true) - $startTime) * 1000;

            return $this->buildErrorResponse($e->getMessage(), [
                'response_time' => round($responseTime, 2),
            ]);
        }
    }

    /**
     * @inheritDoc
     */
    public function getStatus(string $resourceId): array
    {
        try {
            $this->ensureConnected();

            $hosts = $this->client->call('host.get', [
                'hostids' => $resourceId,
                'output' => ['hostid', 'host', 'name', 'status', 'available'],
                'selectInterfaces' => ['ip'],
            ]);

            if (empty($hosts) || !is_array($hosts)) {
                return $this->buildErrorResponse("Host not found: {$resourceId}");
            }

            return ZabbixMapper::mapHostStatus($hosts[0]);
        } catch (\Exception $e) {
            return $this->buildErrorResponse($e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function getMetrics(string $resourceId, array $params = []): array
    {
        try {
            $this->ensureConnected();

            $metricKey = $params['metric_key'] ?? null;

            $itemParams = [
                'output' => ['itemid', 'name', 'key_', 'units', 'lastvalue'],
                'hostids' => $resourceId,
            ];

            if ($metricKey) {
                $itemParams['search'] = ['key_' => $metricKey];
            }

            $items = $this->client->call('item.get', $itemParams);

            $metrics = [];
            if (is_array($items)) {
                foreach ($items as $item) {
                    $metrics[] = ZabbixMapper::mapMetric($item, [
                        'value' => $item['lastvalue'] ?? null,
                        'clock' => time(),
                    ]);
                }
            }

            return [
                'metrics' => $metrics,
                'timestamp' => date('Y-m-d H:i:s'),
                'resource_id' => $resourceId,
            ];
        } catch (\Exception $e) {
            return $this->buildErrorResponse($e->getMessage());
        }
    }

    /**
     * Get active triggers for a host
     *
     * @param string|null $hostId Optional host ID to filter triggers
     * @return array<array> Mapped trigger data
     */
    public function getActiveTriggers(?string $hostId = null): array
    {
        try {
            $this->ensureConnected();

            $params = [
                'output' => 'extend',
                'filter' => ['value' => 1],
                'selectHosts' => ['name'],
                'sortfield' => 'priority',
                'sortorder' => 'DESC',
            ];

            if ($hostId !== null) {
                $params['hostids'] = $hostId;
            }

            $triggers = $this->client->call('trigger.get', $params);

            if (!is_array($triggers)) {
                return [];
            }

            return ZabbixMapper::mapTriggers($triggers);
        } catch (\Exception $e) {
            $this->logError('Failed to get active triggers: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * Get a specific trigger by ID
     *
     * @param string $triggerId Zabbix trigger ID
     * @return array Mapped trigger data
     */
    public function getTrigger(string $triggerId): array
    {
        try {
            $this->ensureConnected();

            $triggers = $this->client->call('trigger.get', [
                'triggerids' => $triggerId,
                'output' => 'extend',
                'selectHosts' => ['name'],
            ]);

            if (empty($triggers) || !is_array($triggers)) {
                return $this->buildErrorResponse("Trigger not found: {$triggerId}");
            }

            return ZabbixMapper::mapTriggerStatus($triggers[0]);
        } catch (\Exception $e) {
            return $this->buildErrorResponse($e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function disconnect(): void
    {
        if ($this->connected) {
            $this->client->logout();
            $this->connected = false;
            $this->logDebug('Disconnected from Zabbix API');
        }
    }

    /**
     * Ensure the client is connected, connecting if necessary
     *
     * @return void
     * @throws \RuntimeException If connection fails
     */
    protected function ensureConnected(): void
    {
        if (!$this->connected) {
            if (!$this->connect()) {
                throw new \RuntimeException(
                    'Failed to connect to Zabbix: ' . ($this->getLastError() ?? 'Unknown error')
                );
            }
        }
    }

    /**
     * Get the underlying Zabbix client
     *
     * @return \App\Integration\Zabbix\ZabbixClient
     */
    public function getClient(): ZabbixClient
    {
        return $this->client;
    }
}
