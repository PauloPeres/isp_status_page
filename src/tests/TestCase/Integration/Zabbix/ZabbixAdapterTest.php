<?php
declare(strict_types=1);

namespace App\Test\TestCase\Integration\Zabbix;

use App\Integration\Zabbix\ZabbixAdapter;
use App\Integration\Zabbix\ZabbixClient;
use App\Integration\Zabbix\ZabbixMapper;
use App\Model\Entity\Monitor;
use App\Service\Check\ZabbixHostChecker;
use App\Service\Check\ZabbixTriggerChecker;
use Cake\Http\Client;
use Cake\Http\Client\Response;
use Cake\TestSuite\TestCase;

/**
 * ZabbixAdapterTest
 *
 * Tests for ZabbixClient, ZabbixAdapter, ZabbixMapper,
 * ZabbixHostChecker, and ZabbixTriggerChecker.
 */
class ZabbixAdapterTest extends TestCase
{
    /**
     * Test ZabbixClient login with successful response
     */
    public function testClientLoginSuccess(): void
    {
        $mockHttpClient = $this->createMockHttpClient([
            'jsonrpc' => '2.0',
            'result' => 'abc123token',
            'id' => 1,
        ]);

        $client = new ZabbixClient(
            'https://zabbix.example.com/api_jsonrpc.php',
            $mockHttpClient
        );

        $token = $client->login('admin', 'password');

        $this->assertEquals('abc123token', $token);
        $this->assertTrue($client->isAuthenticated());
        $this->assertEquals('abc123token', $client->getAuthToken());
    }

    /**
     * Test ZabbixClient login with authentication failure
     */
    public function testClientLoginAuthFailure(): void
    {
        $mockHttpClient = $this->createMockHttpClient([
            'jsonrpc' => '2.0',
            'error' => [
                'code' => -32602,
                'message' => 'Invalid params.',
                'data' => 'Login name or password is incorrect.',
            ],
            'id' => 1,
        ]);

        $client = new ZabbixClient(
            'https://zabbix.example.com/api_jsonrpc.php',
            $mockHttpClient
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Zabbix API error');

        $client->login('wrong', 'credentials');
    }

    /**
     * Test ZabbixClient login with empty token response
     */
    public function testClientLoginEmptyToken(): void
    {
        $mockHttpClient = $this->createMockHttpClient([
            'jsonrpc' => '2.0',
            'result' => '',
            'id' => 1,
        ]);

        $client = new ZabbixClient(
            'https://zabbix.example.com/api_jsonrpc.php',
            $mockHttpClient
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('empty token');

        $client->login('admin', 'password');
    }

    /**
     * Test ZabbixClient call without authentication
     */
    public function testClientCallWithoutAuth(): void
    {
        $mockHttpClient = $this->createMock(Client::class);

        $client = new ZabbixClient(
            'https://zabbix.example.com/api_jsonrpc.php',
            $mockHttpClient
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not authenticated');

        $client->call('host.get', []);
    }

    /**
     * Test ZabbixClient call with successful response
     */
    public function testClientCallSuccess(): void
    {
        $expectedResult = [
            ['hostid' => '10084', 'host' => 'router-01', 'available' => '1'],
        ];

        // Login response first, then host.get response
        $loginResponse = $this->createMockResponse([
            'jsonrpc' => '2.0',
            'result' => 'token123',
            'id' => 1,
        ]);

        $dataResponse = $this->createMockResponse([
            'jsonrpc' => '2.0',
            'result' => $expectedResult,
            'id' => 2,
        ]);

        $mockHttpClient = $this->createMock(Client::class);
        $mockHttpClient->method('post')
            ->willReturnOnConsecutiveCalls($loginResponse, $dataResponse);

        $client = new ZabbixClient(
            'https://zabbix.example.com/api_jsonrpc.php',
            $mockHttpClient
        );

        $client->login('admin', 'password');
        $result = $client->call('host.get', ['hostids' => '10084']);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('10084', $result[0]['hostid']);
    }

    /**
     * Test ZabbixClient with HTTP timeout
     */
    public function testClientCallTimeout(): void
    {
        $loginResponse = $this->createMockResponse([
            'jsonrpc' => '2.0',
            'result' => 'token123',
            'id' => 1,
        ]);

        $mockHttpClient = $this->createMock(Client::class);
        $mockHttpClient->method('post')
            ->willReturnOnConsecutiveCalls(
                $loginResponse,
                $this->throwException(new \Exception('Connection timed out'))
            );

        $client = new ZabbixClient(
            'https://zabbix.example.com/api_jsonrpc.php',
            $mockHttpClient
        );

        $client->login('admin', 'password');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('request failed');

        $client->call('host.get', []);
    }

    /**
     * Test ZabbixClient with non-200 HTTP response
     */
    public function testClientCallHttpError(): void
    {
        $loginResponse = $this->createMockResponse([
            'jsonrpc' => '2.0',
            'result' => 'token123',
            'id' => 1,
        ]);

        $errorResponse = $this->createMock(Response::class);
        $errorResponse->method('getStatusCode')->willReturn(500);

        $mockHttpClient = $this->createMock(Client::class);
        $mockHttpClient->method('post')
            ->willReturnOnConsecutiveCalls($loginResponse, $errorResponse);

        $client = new ZabbixClient(
            'https://zabbix.example.com/api_jsonrpc.php',
            $mockHttpClient
        );

        $client->login('admin', 'password');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('HTTP 500');

        $client->call('host.get', []);
    }

    /**
     * Test ZabbixAdapter testConnection success
     */
    public function testAdapterTestConnectionSuccess(): void
    {
        // Login response, then apiinfo.version response, then logout
        $loginResponse = $this->createMockResponse([
            'jsonrpc' => '2.0',
            'result' => 'token123',
            'id' => 1,
        ]);

        $versionResponse = $this->createMockResponse([
            'jsonrpc' => '2.0',
            'result' => '6.0.0',
            'id' => 2,
        ]);

        $logoutResponse = $this->createMockResponse([
            'jsonrpc' => '2.0',
            'result' => true,
            'id' => 3,
        ]);

        $mockHttpClient = $this->createMock(Client::class);
        $mockHttpClient->method('post')
            ->willReturnOnConsecutiveCalls($loginResponse, $versionResponse, $logoutResponse);

        $client = new ZabbixClient(
            'https://zabbix.example.com/api_jsonrpc.php',
            $mockHttpClient
        );

        $adapter = new ZabbixAdapter([
            'base_url' => 'https://zabbix.example.com/api_jsonrpc.php',
            'username' => 'admin',
            'password' => 'password',
        ], $client);

        $result = $adapter->testConnection();

        $this->assertTrue($result['success']);
        $this->assertEquals('6.0.0', $result['version']);
        $this->assertArrayHasKey('response_time', $result);
    }

    /**
     * Test ZabbixAdapter testConnection failure
     */
    public function testAdapterTestConnectionFailure(): void
    {
        $mockHttpClient = $this->createMock(Client::class);
        $mockHttpClient->method('post')
            ->willThrowException(new \Exception('Connection refused'));

        $client = new ZabbixClient(
            'https://zabbix.example.com/api_jsonrpc.php',
            $mockHttpClient
        );

        $adapter = new ZabbixAdapter([
            'base_url' => 'https://zabbix.example.com/api_jsonrpc.php',
            'username' => 'admin',
            'password' => 'password',
        ], $client);

        $result = $adapter->testConnection();

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /**
     * Test ZabbixAdapter getStatus success
     */
    public function testAdapterGetStatusSuccess(): void
    {
        $loginResponse = $this->createMockResponse([
            'jsonrpc' => '2.0',
            'result' => 'token123',
            'id' => 1,
        ]);

        $hostResponse = $this->createMockResponse([
            'jsonrpc' => '2.0',
            'result' => [
                [
                    'hostid' => '10084',
                    'host' => 'router-01',
                    'name' => 'Router 01',
                    'status' => '0',
                    'available' => '1',
                    'interfaces' => [['ip' => '192.168.1.1']],
                ],
            ],
            'id' => 2,
        ]);

        $mockHttpClient = $this->createMock(Client::class);
        $mockHttpClient->method('post')
            ->willReturnOnConsecutiveCalls($loginResponse, $hostResponse);

        $client = new ZabbixClient(
            'https://zabbix.example.com/api_jsonrpc.php',
            $mockHttpClient
        );

        $adapter = new ZabbixAdapter([
            'base_url' => 'https://zabbix.example.com/api_jsonrpc.php',
            'username' => 'admin',
            'password' => 'password',
        ], $client);

        $result = $adapter->getStatus('10084');

        $this->assertEquals('up', $result['status']);
        $this->assertTrue($result['online']);
        $this->assertEquals('10084', $result['host_id']);
        $this->assertEquals('router-01', $result['host_name']);
    }

    /**
     * Test ZabbixAdapter getName and getType
     */
    public function testAdapterNameAndType(): void
    {
        $adapter = new ZabbixAdapter([
            'base_url' => 'https://zabbix.example.com/api_jsonrpc.php',
            'username' => 'admin',
            'password' => 'password',
        ]);

        $this->assertEquals('Zabbix', $adapter->getName());
        $this->assertEquals('zabbix', $adapter->getType());
        $this->assertFalse($adapter->isConnected());
    }

    /**
     * Test ZabbixMapper mapHostStatus with available host
     */
    public function testMapperHostStatusAvailable(): void
    {
        $host = [
            'hostid' => '10084',
            'host' => 'router-01',
            'available' => '1',
            'status' => '0',
        ];

        $result = ZabbixMapper::mapHostStatus($host);

        $this->assertEquals('up', $result['status']);
        $this->assertTrue($result['online']);
        $this->assertEquals('10084', $result['host_id']);
        $this->assertEquals('router-01', $result['host_name']);
        $this->assertEquals(1, $result['available']);
    }

    /**
     * Test ZabbixMapper mapHostStatus with unavailable host
     */
    public function testMapperHostStatusUnavailable(): void
    {
        $host = [
            'hostid' => '10085',
            'host' => 'router-02',
            'available' => '2',
            'status' => '0',
        ];

        $result = ZabbixMapper::mapHostStatus($host);

        $this->assertEquals('down', $result['status']);
        $this->assertFalse($result['online']);
        $this->assertEquals('Host is unavailable', $result['message']);
    }

    /**
     * Test ZabbixMapper mapHostStatus with unknown availability
     */
    public function testMapperHostStatusUnknown(): void
    {
        $host = [
            'hostid' => '10086',
            'host' => 'router-03',
            'available' => '0',
        ];

        $result = ZabbixMapper::mapHostStatus($host);

        $this->assertEquals('unknown', $result['status']);
        $this->assertFalse($result['online']);
    }

    /**
     * Test ZabbixMapper mapTriggerStatus with OK trigger
     */
    public function testMapperTriggerStatusOk(): void
    {
        $trigger = [
            'triggerid' => '13926',
            'description' => 'High CPU usage',
            'priority' => '4',
            'value' => '0',
            'lastchange' => '1698752400',
            'hosts' => [['name' => 'router-01']],
        ];

        $result = ZabbixMapper::mapTriggerStatus($trigger);

        $this->assertEquals('up', $result['status']);
        $this->assertFalse($result['problem']);
        $this->assertEquals('13926', $result['trigger_id']);
        $this->assertEquals('Trigger OK', $result['message']);
        $this->assertEquals('critical', $result['severity']);
    }

    /**
     * Test ZabbixMapper mapTriggerStatus with PROBLEM trigger
     */
    public function testMapperTriggerStatusProblem(): void
    {
        $trigger = [
            'triggerid' => '13927',
            'description' => 'Interface down',
            'priority' => '3',
            'value' => '1',
            'lastchange' => '1698752500',
            'hosts' => [['name' => 'switch-01']],
        ];

        $result = ZabbixMapper::mapTriggerStatus($trigger);

        $this->assertEquals('down', $result['status']);
        $this->assertTrue($result['problem']);
        $this->assertStringContainsString('Interface down', $result['message']);
        $this->assertEquals('major', $result['severity']);
        $this->assertContains('switch-01', $result['hosts']);
    }

    /**
     * Test ZabbixMapper mapSeverity mappings
     */
    public function testMapperSeverityMappings(): void
    {
        $this->assertEquals('critical', ZabbixMapper::mapSeverity(5));
        $this->assertEquals('critical', ZabbixMapper::mapSeverity(4));
        $this->assertEquals('major', ZabbixMapper::mapSeverity(3));
        $this->assertEquals('minor', ZabbixMapper::mapSeverity(2));
        $this->assertEquals('maintenance', ZabbixMapper::mapSeverity(1));
        $this->assertEquals('minor', ZabbixMapper::mapSeverity(0));
    }

    /**
     * Test ZabbixMapper mapTriggers with multiple triggers
     */
    public function testMapperMapTriggers(): void
    {
        $triggers = [
            ['triggerid' => '1', 'description' => 'T1', 'priority' => '4', 'value' => '1'],
            ['triggerid' => '2', 'description' => 'T2', 'priority' => '2', 'value' => '0'],
        ];

        $result = ZabbixMapper::mapTriggers($triggers);

        $this->assertCount(2, $result);
        $this->assertEquals('down', $result[0]['status']);
        $this->assertEquals('up', $result[1]['status']);
    }

    /**
     * Test ZabbixHostChecker with available host
     */
    public function testHostCheckerAvailableHost(): void
    {
        $adapter = $this->createMockAdapter([
            'status' => 'up',
            'online' => true,
            'host_id' => '10084',
            'host_name' => 'router-01',
            'available' => 1,
            'message' => 'Host is available',
            'metadata' => [],
        ]);

        $checker = new ZabbixHostChecker($adapter);

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'Zabbix Router Check',
            'type' => 'zabbix_host',
            'configuration' => json_encode(['host_id' => '10084']),
            'timeout' => 30,
        ]);

        $result = $checker->check($monitor);

        $this->assertEquals('up', $result['status']);
        $this->assertNull($result['error_message']);
        $this->assertIsInt($result['response_time']);
    }

    /**
     * Test ZabbixHostChecker with unavailable host
     */
    public function testHostCheckerUnavailableHost(): void
    {
        $adapter = $this->createMockAdapter([
            'status' => 'down',
            'online' => false,
            'host_id' => '10085',
            'host_name' => 'router-02',
            'available' => 2,
            'message' => 'Host is unavailable',
            'metadata' => [],
        ]);

        $checker = new ZabbixHostChecker($adapter);

        $monitor = new Monitor([
            'id' => 2,
            'name' => 'Zabbix Router Check',
            'type' => 'zabbix_host',
            'configuration' => json_encode(['host_id' => '10085']),
            'timeout' => 30,
        ]);

        $result = $checker->check($monitor);

        $this->assertEquals('down', $result['status']);
        $this->assertStringContainsString('unavailable', $result['error_message']);
    }

    /**
     * Test ZabbixHostChecker with missing host_id
     */
    public function testHostCheckerMissingHostId(): void
    {
        $adapter = $this->createMock(ZabbixAdapter::class);

        $checker = new ZabbixHostChecker($adapter);

        $monitor = new Monitor([
            'id' => 3,
            'name' => 'Bad Config',
            'type' => 'zabbix_host',
            'configuration' => json_encode([]),
            'timeout' => 30,
        ]);

        $result = $checker->check($monitor);

        $this->assertEquals('down', $result['status']);
        $this->assertStringContainsString('Missing host_id', $result['error_message']);
    }

    /**
     * Test ZabbixHostChecker getType and getName
     */
    public function testHostCheckerTypeAndName(): void
    {
        $adapter = $this->createMock(ZabbixAdapter::class);
        $checker = new ZabbixHostChecker($adapter);

        $this->assertEquals('zabbix_host', $checker->getType());
        $this->assertEquals('Zabbix Host Checker', $checker->getName());
    }

    /**
     * Test ZabbixHostChecker validateConfiguration
     */
    public function testHostCheckerValidateConfiguration(): void
    {
        $adapter = $this->createMock(ZabbixAdapter::class);
        $checker = new ZabbixHostChecker($adapter);

        $validMonitor = new Monitor([
            'id' => 1,
            'configuration' => json_encode(['host_id' => '10084']),
            'timeout' => 30,
        ]);

        $invalidMonitor = new Monitor([
            'id' => 2,
            'configuration' => json_encode([]),
            'timeout' => 30,
        ]);

        $this->assertTrue($checker->validateConfiguration($validMonitor));
        $this->assertFalse($checker->validateConfiguration($invalidMonitor));
    }

    /**
     * Test ZabbixTriggerChecker with OK trigger
     */
    public function testTriggerCheckerOkTrigger(): void
    {
        $adapter = $this->createMock(ZabbixAdapter::class);
        $adapter->method('getTrigger')
            ->willReturn([
                'status' => 'up',
                'problem' => false,
                'trigger_id' => '13926',
                'description' => 'High CPU usage',
                'priority' => 4,
                'severity' => 'critical',
                'value' => 0,
                'last_change' => '2024-10-31 12:00:00',
                'message' => 'Trigger OK',
                'hosts' => ['router-01'],
            ]);

        $checker = new ZabbixTriggerChecker($adapter);

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'CPU Trigger Check',
            'type' => 'zabbix_trigger',
            'configuration' => json_encode(['trigger_id' => '13926']),
            'timeout' => 30,
        ]);

        $result = $checker->check($monitor);

        $this->assertEquals('up', $result['status']);
        $this->assertNull($result['error_message']);
    }

    /**
     * Test ZabbixTriggerChecker with PROBLEM trigger
     */
    public function testTriggerCheckerProblemTrigger(): void
    {
        $adapter = $this->createMock(ZabbixAdapter::class);
        $adapter->method('getTrigger')
            ->willReturn([
                'status' => 'down',
                'problem' => true,
                'trigger_id' => '13927',
                'description' => 'Interface down on switch-01',
                'priority' => 4,
                'severity' => 'critical',
                'value' => 1,
                'last_change' => '2024-10-31 12:30:00',
                'message' => 'Trigger in PROBLEM state',
                'hosts' => ['switch-01'],
            ]);

        $checker = new ZabbixTriggerChecker($adapter);

        $monitor = new Monitor([
            'id' => 2,
            'name' => 'Interface Trigger Check',
            'type' => 'zabbix_trigger',
            'configuration' => json_encode(['trigger_id' => '13927']),
            'timeout' => 30,
        ]);

        $result = $checker->check($monitor);

        $this->assertEquals('down', $result['status']);
        $this->assertStringContainsString('PROBLEM', $result['error_message']);
        $this->assertStringContainsString('Interface down', $result['error_message']);
    }

    /**
     * Test ZabbixTriggerChecker with missing trigger_id
     */
    public function testTriggerCheckerMissingTriggerId(): void
    {
        $adapter = $this->createMock(ZabbixAdapter::class);

        $checker = new ZabbixTriggerChecker($adapter);

        $monitor = new Monitor([
            'id' => 3,
            'name' => 'Bad Config',
            'type' => 'zabbix_trigger',
            'configuration' => json_encode([]),
            'timeout' => 30,
        ]);

        $result = $checker->check($monitor);

        $this->assertEquals('down', $result['status']);
        $this->assertStringContainsString('Missing trigger_id', $result['error_message']);
    }

    /**
     * Test ZabbixTriggerChecker with adapter error
     */
    public function testTriggerCheckerAdapterError(): void
    {
        $adapter = $this->createMock(ZabbixAdapter::class);
        $adapter->method('getTrigger')
            ->willReturn([
                'success' => false,
                'error' => 'Trigger not found: 99999',
            ]);

        $checker = new ZabbixTriggerChecker($adapter);

        $monitor = new Monitor([
            'id' => 4,
            'name' => 'Missing Trigger',
            'type' => 'zabbix_trigger',
            'configuration' => json_encode(['trigger_id' => '99999']),
            'timeout' => 30,
        ]);

        $result = $checker->check($monitor);

        $this->assertEquals('down', $result['status']);
        $this->assertStringContainsString('Trigger not found', $result['error_message']);
    }

    /**
     * Test ZabbixTriggerChecker getType and getName
     */
    public function testTriggerCheckerTypeAndName(): void
    {
        $adapter = $this->createMock(ZabbixAdapter::class);
        $checker = new ZabbixTriggerChecker($adapter);

        $this->assertEquals('zabbix_trigger', $checker->getType());
        $this->assertEquals('Zabbix Trigger Checker', $checker->getName());
    }

    /**
     * Test ZabbixTriggerChecker validateConfiguration
     */
    public function testTriggerCheckerValidateConfiguration(): void
    {
        $adapter = $this->createMock(ZabbixAdapter::class);
        $checker = new ZabbixTriggerChecker($adapter);

        $validMonitor = new Monitor([
            'id' => 1,
            'configuration' => json_encode(['trigger_id' => '13926']),
            'timeout' => 30,
        ]);

        $invalidMonitor = new Monitor([
            'id' => 2,
            'configuration' => json_encode([]),
            'timeout' => 30,
        ]);

        $this->assertTrue($checker->validateConfiguration($validMonitor));
        $this->assertFalse($checker->validateConfiguration($invalidMonitor));
    }

    /**
     * Test ZabbixMapper mapMetric
     */
    public function testMapperMapMetric(): void
    {
        $item = [
            'itemid' => '23296',
            'key_' => 'system.cpu.util',
            'name' => 'CPU utilization',
            'units' => '%',
        ];

        $lastValue = [
            'value' => '45.5',
            'clock' => '1698752400',
        ];

        $result = ZabbixMapper::mapMetric($item, $lastValue);

        $this->assertEquals('23296', $result['item_id']);
        $this->assertEquals('system.cpu.util', $result['key']);
        $this->assertEquals('CPU utilization', $result['name']);
        $this->assertEquals(45.5, $result['value']);
        $this->assertEquals('%', $result['units']);
    }

    /**
     * Test ZabbixMapper mapMetric with null last value
     */
    public function testMapperMapMetricNoLastValue(): void
    {
        $item = [
            'itemid' => '23296',
            'key_' => 'system.cpu.util',
            'name' => 'CPU utilization',
            'units' => '%',
        ];

        $result = ZabbixMapper::mapMetric($item, null);

        $this->assertNull($result['value']);
    }

    /**
     * Test ZabbixMapper mapHostStatus with empty data
     */
    public function testMapperHostStatusEmptyData(): void
    {
        $result = ZabbixMapper::mapHostStatus([]);

        $this->assertEquals('unknown', $result['status']);
        $this->assertFalse($result['online']);
        $this->assertEquals('', $result['host_id']);
    }

    /**
     * Create a mock HTTP client that returns a specific JSON-RPC response
     *
     * @param array $responseBody Response body array
     * @return \Cake\Http\Client
     */
    protected function createMockHttpClient(array $responseBody): Client
    {
        $mockResponse = $this->createMockResponse($responseBody);

        $mockClient = $this->createMock(Client::class);
        $mockClient->method('post')->willReturn($mockResponse);

        return $mockClient;
    }

    /**
     * Create a mock HTTP response
     *
     * @param array $body Response body as array (will be JSON encoded)
     * @param int $statusCode HTTP status code
     * @return \Cake\Http\Client\Response
     */
    protected function createMockResponse(array $body, int $statusCode = 200): Response
    {
        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('getStatusCode')->willReturn($statusCode);
        $mockResponse->method('getStringBody')->willReturn(json_encode($body));

        return $mockResponse;
    }

    /**
     * Create a mock ZabbixAdapter that returns specific host status
     *
     * @param array $statusData Status data to return from getStatus
     * @return \App\Integration\Zabbix\ZabbixAdapter
     */
    protected function createMockAdapter(array $statusData): ZabbixAdapter
    {
        $adapter = $this->createMock(ZabbixAdapter::class);
        $adapter->method('getStatus')->willReturn($statusData);
        $adapter->method('connect')->willReturn(true);
        $adapter->method('isConnected')->willReturn(true);

        return $adapter;
    }
}
