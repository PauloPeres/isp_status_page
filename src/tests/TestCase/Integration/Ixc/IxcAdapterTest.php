<?php
declare(strict_types=1);

namespace App\Test\TestCase\Integration\Ixc;

use App\Integration\Ixc\IxcAdapter;
use App\Integration\Ixc\IxcClient;
use App\Integration\Ixc\IxcMapper;
use App\Model\Entity\Monitor;
use App\Service\Check\IxcEquipmentChecker;
use App\Service\Check\IxcServiceChecker;
use Cake\TestSuite\TestCase;

/**
 * IXC Integration Adapter Test Case
 *
 * Tests the IXC adapter, client, mapper, and checkers with mocked HTTP calls.
 */
class IxcAdapterTest extends TestCase
{
    // ─── IxcAdapter Tests ──────────────────────────────────────────

    /**
     * Test adapter getName returns correct name
     */
    public function testGetName(): void
    {
        $adapter = new IxcAdapter([
            'base_url' => 'https://ixc.example.com/api',
            'username' => 'user',
            'password' => 'pass',
        ]);

        $this->assertEquals('IXC Soft', $adapter->getName());
    }

    /**
     * Test adapter getType returns correct type
     */
    public function testGetType(): void
    {
        $adapter = new IxcAdapter([
            'base_url' => 'https://ixc.example.com/api',
            'username' => 'user',
            'password' => 'pass',
        ]);

        $this->assertEquals('ixc', $adapter->getType());
    }

    /**
     * Test adapter is not connected initially
     */
    public function testIsNotConnectedInitially(): void
    {
        $adapter = new IxcAdapter([
            'base_url' => 'https://ixc.example.com/api',
            'username' => 'user',
            'password' => 'pass',
        ]);

        $this->assertFalse($adapter->isConnected());
    }

    /**
     * Test successful connection
     */
    public function testConnectSuccess(): void
    {
        $mockClient = $this->createMock(IxcClient::class);
        $mockClient->method('authenticate')->willReturn(true);

        $adapter = new IxcAdapter([
            'base_url' => 'https://ixc.example.com/api',
            'username' => 'user',
            'password' => 'pass',
        ], $mockClient);

        $result = $adapter->connect();

        $this->assertTrue($result);
        $this->assertTrue($adapter->isConnected());
    }

    /**
     * Test failed connection (authentication failure)
     */
    public function testConnectAuthFailure(): void
    {
        $mockClient = $this->createMock(IxcClient::class);
        $mockClient->method('authenticate')
            ->willThrowException(new \RuntimeException('IXC authentication failed: Access denied'));

        $adapter = new IxcAdapter([
            'base_url' => 'https://ixc.example.com/api',
            'username' => 'user',
            'password' => 'wrongpass',
        ], $mockClient);

        $result = $adapter->connect();

        $this->assertFalse($result);
        $this->assertFalse($adapter->isConnected());
    }

    /**
     * Test successful testConnection
     */
    public function testTestConnectionSuccess(): void
    {
        $mockClient = $this->createMock(IxcClient::class);
        $mockClient->method('authenticate')->willReturn(true);

        $adapter = new IxcAdapter([
            'base_url' => 'https://ixc.example.com/api',
            'username' => 'user',
            'password' => 'pass',
        ], $mockClient);

        $result = $adapter->testConnection();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('response_time', $result);
    }

    /**
     * Test failed testConnection
     */
    public function testTestConnectionFailure(): void
    {
        $mockClient = $this->createMock(IxcClient::class);
        $mockClient->method('authenticate')
            ->willThrowException(new \RuntimeException('Connection timeout'));

        $adapter = new IxcAdapter([
            'base_url' => 'https://ixc.example.com/api',
            'username' => 'user',
            'password' => 'pass',
        ], $mockClient);

        $result = $adapter->testConnection();

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('response_time', $result);
    }

    /**
     * Test getStatus with successful service response
     */
    public function testGetStatusSuccess(): void
    {
        $mockClient = $this->createMock(IxcClient::class);
        $mockClient->method('authenticate')->willReturn(true);
        $mockClient->method('get')
            ->willReturn([
                'service_id' => '12345',
                'status' => 'active',
                'customer_name' => 'Cliente Teste',
                'plan' => '100MB',
                'connection_status' => 'online',
                'last_seen' => '2024-10-31T10:30:00Z',
                'signal_quality' => 85,
                'equipment' => [
                    'mac' => 'AA:BB:CC:DD:EE:FF',
                    'ip' => '192.168.1.100',
                    'status' => 'online',
                ],
            ]);

        $adapter = new IxcAdapter([
            'base_url' => 'https://ixc.example.com/api',
            'username' => 'user',
            'password' => 'pass',
        ], $mockClient);

        $result = $adapter->getStatus('12345');

        $this->assertEquals('up', $result['status']);
        $this->assertTrue($result['online']);
        $this->assertArrayHasKey('metadata', $result);
        $this->assertEquals('12345', $result['metadata']['service_id']);
        $this->assertEquals('Cliente Teste', $result['metadata']['customer_name']);
    }

    /**
     * Test getStatus with failed service (offline)
     */
    public function testGetStatusOffline(): void
    {
        $mockClient = $this->createMock(IxcClient::class);
        $mockClient->method('authenticate')->willReturn(true);
        $mockClient->method('get')
            ->willReturn([
                'service_id' => '12345',
                'status' => 'active',
                'customer_name' => 'Cliente Teste',
                'plan' => '100MB',
                'connection_status' => 'offline',
                'last_seen' => '2024-10-31T08:00:00Z',
            ]);

        $adapter = new IxcAdapter([
            'base_url' => 'https://ixc.example.com/api',
            'username' => 'user',
            'password' => 'pass',
        ], $mockClient);

        $result = $adapter->getStatus('12345');

        $this->assertEquals('down', $result['status']);
        $this->assertFalse($result['online']);
    }

    /**
     * Test getStatus when API request fails
     */
    public function testGetStatusApiError(): void
    {
        $mockClient = $this->createMock(IxcClient::class);
        $mockClient->method('authenticate')->willReturn(true);
        $mockClient->method('get')
            ->willThrowException(new \RuntimeException('IXC API request failed: timeout'));

        $adapter = new IxcAdapter([
            'base_url' => 'https://ixc.example.com/api',
            'username' => 'user',
            'password' => 'pass',
        ], $mockClient);

        $result = $adapter->getStatus('12345');

        $this->assertEquals('unknown', $result['status']);
        $this->assertFalse($result['online']);
        $this->assertStringContainsString('timeout', $result['message']);
    }

    /**
     * Test getEquipmentStatus with successful response
     */
    public function testGetEquipmentStatusSuccess(): void
    {
        $mockClient = $this->createMock(IxcClient::class);
        $mockClient->method('authenticate')->willReturn(true);
        $mockClient->method('get')
            ->willReturn([
                'equipment_id' => 'OLT-01',
                'type' => 'OLT',
                'name' => 'OLT Centro',
                'status' => 'online',
                'cpu_usage' => 45,
                'memory_usage' => 60,
                'uptime' => 2592000,
                'ports_total' => 16,
                'ports_active' => 14,
                'temperature' => 38.5,
                'last_update' => '2024-10-31T10:35:00Z',
            ]);

        $adapter = new IxcAdapter([
            'base_url' => 'https://ixc.example.com/api',
            'username' => 'user',
            'password' => 'pass',
        ], $mockClient);

        $result = $adapter->getEquipmentStatus('OLT-01');

        $this->assertEquals('up', $result['status']);
        $this->assertTrue($result['online']);
        $this->assertEquals('OLT-01', $result['metadata']['equipment_id']);
        $this->assertEquals(45, $result['metadata']['cpu_usage']);
    }

    /**
     * Test getMetrics returns metrics data
     */
    public function testGetMetrics(): void
    {
        $mockClient = $this->createMock(IxcClient::class);
        $mockClient->method('authenticate')->willReturn(true);
        $mockClient->method('get')
            ->willReturn([
                'service_id' => '12345',
                'status' => 'active',
                'customer_name' => 'Cliente Teste',
                'plan' => '100MB',
                'connection_status' => 'online',
                'signal_quality' => 85,
            ]);

        $adapter = new IxcAdapter([
            'base_url' => 'https://ixc.example.com/api',
            'username' => 'user',
            'password' => 'pass',
        ], $mockClient);

        $result = $adapter->getMetrics('12345');

        $this->assertArrayHasKey('metrics', $result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertEquals('12345', $result['resource_id']);
    }

    /**
     * Test disconnect
     */
    public function testDisconnect(): void
    {
        $mockClient = $this->createMock(IxcClient::class);
        $mockClient->method('authenticate')->willReturn(true);

        $adapter = new IxcAdapter([
            'base_url' => 'https://ixc.example.com/api',
            'username' => 'user',
            'password' => 'pass',
        ], $mockClient);

        $adapter->connect();
        $this->assertTrue($adapter->isConnected());

        $adapter->disconnect();
        $this->assertFalse($adapter->isConnected());
    }

    /**
     * Test connect throws on missing configuration
     */
    public function testConnectMissingConfig(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $adapter = new IxcAdapter([]);
        $adapter->connect();
    }

    // ─── IxcMapper Tests ───────────────────────────────────────────

    /**
     * Test mapServiceStatus with active service
     */
    public function testMapServiceStatusActive(): void
    {
        $data = [
            'service_id' => '12345',
            'status' => 'active',
            'customer_name' => 'Cliente Teste',
            'plan' => '100MB',
            'connection_status' => 'online',
            'last_seen' => '2024-10-31T10:30:00Z',
            'signal_quality' => 85,
            'equipment' => ['mac' => 'AA:BB:CC:DD:EE:FF'],
        ];

        $result = IxcMapper::mapServiceStatus($data);

        $this->assertEquals('up', $result['status']);
        $this->assertTrue($result['online']);
        $this->assertStringContainsString('Cliente Teste', $result['message']);
        $this->assertEquals('2024-10-31T10:30:00Z', $result['last_seen']);
        $this->assertEquals('12345', $result['metadata']['service_id']);
    }

    /**
     * Test mapServiceStatus with offline service
     */
    public function testMapServiceStatusOffline(): void
    {
        $data = [
            'service_id' => '12345',
            'status' => 'active',
            'connection_status' => 'offline',
        ];

        $result = IxcMapper::mapServiceStatus($data);

        $this->assertEquals('down', $result['status']);
        $this->assertFalse($result['online']);
    }

    /**
     * Test mapServiceStatus with suspended service
     */
    public function testMapServiceStatusSuspended(): void
    {
        $data = [
            'service_id' => '12345',
            'status' => 'suspended',
            'connection_status' => 'online',
        ];

        $result = IxcMapper::mapServiceStatus($data);

        $this->assertEquals('degraded', $result['status']);
    }

    /**
     * Test mapEquipmentStatus with online equipment
     */
    public function testMapEquipmentStatusOnline(): void
    {
        $data = [
            'equipment_id' => 'OLT-01',
            'type' => 'OLT',
            'name' => 'OLT Centro',
            'status' => 'online',
            'cpu_usage' => 45,
            'memory_usage' => 60,
            'temperature' => 38.5,
            'uptime' => 2592000,
            'ports_total' => 16,
            'ports_active' => 14,
            'last_update' => '2024-10-31T10:35:00Z',
        ];

        $result = IxcMapper::mapEquipmentStatus($data);

        $this->assertEquals('up', $result['status']);
        $this->assertTrue($result['online']);
        $this->assertStringContainsString('OLT Centro', $result['message']);
        $this->assertEquals(45, $result['metadata']['cpu_usage']);
    }

    /**
     * Test mapEquipmentStatus with offline equipment
     */
    public function testMapEquipmentStatusOffline(): void
    {
        $data = [
            'equipment_id' => 'OLT-01',
            'type' => 'OLT',
            'name' => 'OLT Centro',
            'status' => 'offline',
        ];

        $result = IxcMapper::mapEquipmentStatus($data);

        $this->assertEquals('down', $result['status']);
        $this->assertFalse($result['online']);
    }

    /**
     * Test mapEquipmentStatus detects high CPU threshold
     */
    public function testMapEquipmentStatusHighCpu(): void
    {
        $data = [
            'equipment_id' => 'OLT-01',
            'type' => 'OLT',
            'name' => 'OLT Centro',
            'status' => 'online',
            'cpu_usage' => 95,
            'memory_usage' => 60,
            'temperature' => 38.5,
        ];

        $result = IxcMapper::mapEquipmentStatus($data);

        $this->assertEquals('degraded', $result['status']);
    }

    /**
     * Test mapTickets
     */
    public function testMapTickets(): void
    {
        $data = [
            'data' => [
                [
                    'id' => 'T-001',
                    'subject' => 'Sem internet',
                    'status' => 'open',
                    'priority' => 'critical',
                    'customer_id' => '12345',
                    'created_at' => '2024-10-31T09:00:00Z',
                    'category' => 'technical',
                ],
            ],
            'meta' => ['total' => 1],
        ];

        $result = IxcMapper::mapTickets($data);

        $this->assertCount(1, $result);
        $this->assertEquals('T-001', $result[0]['id']);
        $this->assertEquals('critical', $result[0]['priority']);
    }

    // ─── IxcClient Tests ───────────────────────────────────────────

    /**
     * Test client generates token correctly
     */
    public function testClientTokenGeneration(): void
    {
        $client = new IxcClient(
            'https://ixc.example.com/api',
            'admin',
            'secret123'
        );

        $expectedToken = base64_encode('admin:' . md5('secret123'));
        $this->assertEquals($expectedToken, $client->getToken());
    }

    /**
     * Test client returns correct base URL
     */
    public function testClientBaseUrl(): void
    {
        $client = new IxcClient(
            'https://ixc.example.com/api/',
            'admin',
            'secret123'
        );

        // Should strip trailing slash
        $this->assertEquals('https://ixc.example.com/api', $client->getBaseUrl());
    }

    /**
     * Test client is not authenticated initially
     */
    public function testClientNotAuthenticatedInitially(): void
    {
        $client = new IxcClient(
            'https://ixc.example.com/api',
            'admin',
            'secret123'
        );

        $this->assertFalse($client->isAuthenticated());
    }

    // ─── IxcServiceChecker Tests ───────────────────────────────────

    /**
     * Test IxcServiceChecker getType
     */
    public function testServiceCheckerGetType(): void
    {
        $checker = new IxcServiceChecker();

        $this->assertEquals('ixc_service', $checker->getType());
    }

    /**
     * Test IxcServiceChecker getName
     */
    public function testServiceCheckerGetName(): void
    {
        $checker = new IxcServiceChecker();

        $this->assertEquals('IXC Service Checker', $checker->getName());
    }

    /**
     * Test IxcServiceChecker validates configuration - valid
     */
    public function testServiceCheckerValidConfigurationValid(): void
    {
        $checker = new IxcServiceChecker();

        $monitor = new Monitor([
            'id' => 1,
            'type' => 'ixc_service',
            'timeout' => 30,
            'configuration' => json_encode([
                'integration_id' => 1,
                'service_id' => '12345',
            ]),
        ]);

        $this->assertTrue($checker->validateConfiguration($monitor));
    }

    /**
     * Test IxcServiceChecker validates configuration - missing service_id
     */
    public function testServiceCheckerValidConfigurationMissingServiceId(): void
    {
        $checker = new IxcServiceChecker();

        $monitor = new Monitor([
            'id' => 1,
            'type' => 'ixc_service',
            'timeout' => 30,
            'configuration' => json_encode([
                'integration_id' => 1,
            ]),
        ]);

        $this->assertFalse($checker->validateConfiguration($monitor));
    }

    /**
     * Test IxcServiceChecker check with successful response
     */
    public function testServiceCheckerCheckSuccess(): void
    {
        $mockAdapter = $this->createMock(IxcAdapter::class);
        $mockAdapter->method('getStatus')->willReturn([
            'status' => 'up',
            'online' => true,
            'message' => 'Service is online',
            'last_seen' => '2024-10-31T10:30:00Z',
            'metadata' => [
                'service_id' => '12345',
                'customer_name' => 'Cliente Teste',
            ],
        ]);

        $checker = new IxcServiceChecker($mockAdapter);

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'IXC Service Test',
            'type' => 'ixc_service',
            'timeout' => 30,
            'configuration' => json_encode([
                'integration_id' => 1,
                'service_id' => '12345',
            ]),
        ]);

        $result = $checker->check($monitor);

        $this->assertEquals('up', $result['status']);
        $this->assertNull($result['error_message']);
        $this->assertIsInt($result['response_time']);
    }

    /**
     * Test IxcServiceChecker check with failed response
     */
    public function testServiceCheckerCheckFailure(): void
    {
        $mockAdapter = $this->createMock(IxcAdapter::class);
        $mockAdapter->method('getStatus')->willReturn([
            'status' => 'down',
            'online' => false,
            'message' => 'Service is offline',
            'last_seen' => '2024-10-31T08:00:00Z',
            'metadata' => [],
        ]);

        $checker = new IxcServiceChecker($mockAdapter);

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'IXC Service Test',
            'type' => 'ixc_service',
            'timeout' => 30,
            'configuration' => json_encode([
                'integration_id' => 1,
                'service_id' => '12345',
            ]),
        ]);

        $result = $checker->check($monitor);

        $this->assertEquals('down', $result['status']);
        $this->assertNotNull($result['error_message']);
    }

    /**
     * Test IxcServiceChecker check with timeout (exception)
     */
    public function testServiceCheckerCheckTimeout(): void
    {
        $mockAdapter = $this->createMock(IxcAdapter::class);
        $mockAdapter->method('getStatus')
            ->willThrowException(new \RuntimeException('Connection timed out'));

        $checker = new IxcServiceChecker($mockAdapter);

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'IXC Service Test',
            'type' => 'ixc_service',
            'timeout' => 30,
            'configuration' => json_encode([
                'integration_id' => 1,
                'service_id' => '12345',
            ]),
        ]);

        $result = $checker->check($monitor);

        $this->assertEquals('down', $result['status']);
        $this->assertStringContainsString('timed out', $result['error_message']);
    }

    /**
     * Test IxcServiceChecker check with missing service_id
     */
    public function testServiceCheckerCheckMissingServiceId(): void
    {
        $checker = new IxcServiceChecker();

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'IXC Service Test',
            'type' => 'ixc_service',
            'timeout' => 30,
            'target' => 'ixc://service',
            'configuration' => json_encode([
                'integration_id' => 1,
            ]),
        ]);

        $result = $checker->check($monitor);

        $this->assertEquals('down', $result['status']);
        $this->assertStringContainsString('service_id', $result['error_message']);
    }

    // ─── IxcEquipmentChecker Tests ─────────────────────────────────

    /**
     * Test IxcEquipmentChecker getType
     */
    public function testEquipmentCheckerGetType(): void
    {
        $checker = new IxcEquipmentChecker();

        $this->assertEquals('ixc_equipment', $checker->getType());
    }

    /**
     * Test IxcEquipmentChecker getName
     */
    public function testEquipmentCheckerGetName(): void
    {
        $checker = new IxcEquipmentChecker();

        $this->assertEquals('IXC Equipment Checker', $checker->getName());
    }

    /**
     * Test IxcEquipmentChecker validates configuration - valid
     */
    public function testEquipmentCheckerValidConfigurationValid(): void
    {
        $checker = new IxcEquipmentChecker();

        $monitor = new Monitor([
            'id' => 1,
            'type' => 'ixc_equipment',
            'timeout' => 30,
            'configuration' => json_encode([
                'integration_id' => 1,
                'equipment_id' => 'OLT-01',
            ]),
        ]);

        $this->assertTrue($checker->validateConfiguration($monitor));
    }

    /**
     * Test IxcEquipmentChecker validates configuration - missing equipment_id
     */
    public function testEquipmentCheckerValidConfigurationMissingEquipmentId(): void
    {
        $checker = new IxcEquipmentChecker();

        $monitor = new Monitor([
            'id' => 1,
            'type' => 'ixc_equipment',
            'timeout' => 30,
            'configuration' => json_encode([
                'integration_id' => 1,
            ]),
        ]);

        $this->assertFalse($checker->validateConfiguration($monitor));
    }

    /**
     * Test IxcEquipmentChecker check with successful response
     */
    public function testEquipmentCheckerCheckSuccess(): void
    {
        $mockAdapter = $this->createMock(IxcAdapter::class);
        $mockAdapter->method('getEquipmentStatus')->willReturn([
            'status' => 'up',
            'online' => true,
            'message' => 'OLT Centro is online',
            'last_seen' => '2024-10-31T10:35:00Z',
            'metadata' => [
                'equipment_id' => 'OLT-01',
                'cpu_usage' => 45,
                'memory_usage' => 60,
                'temperature' => 38.5,
            ],
        ]);

        $checker = new IxcEquipmentChecker($mockAdapter);

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'OLT Centro Monitor',
            'type' => 'ixc_equipment',
            'timeout' => 30,
            'configuration' => json_encode([
                'integration_id' => 1,
                'equipment_id' => 'OLT-01',
            ]),
        ]);

        $result = $checker->check($monitor);

        $this->assertEquals('up', $result['status']);
        $this->assertNull($result['error_message']);
        $this->assertIsInt($result['response_time']);
    }

    /**
     * Test IxcEquipmentChecker check with offline equipment
     */
    public function testEquipmentCheckerCheckOffline(): void
    {
        $mockAdapter = $this->createMock(IxcAdapter::class);
        $mockAdapter->method('getEquipmentStatus')->willReturn([
            'status' => 'down',
            'online' => false,
            'message' => 'OLT Centro is offline',
            'metadata' => [],
        ]);

        $checker = new IxcEquipmentChecker($mockAdapter);

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'OLT Centro Monitor',
            'type' => 'ixc_equipment',
            'timeout' => 30,
            'configuration' => json_encode([
                'integration_id' => 1,
                'equipment_id' => 'OLT-01',
            ]),
        ]);

        $result = $checker->check($monitor);

        $this->assertEquals('down', $result['status']);
        $this->assertNotNull($result['error_message']);
    }

    /**
     * Test IxcEquipmentChecker check detects threshold violation
     */
    public function testEquipmentCheckerCheckThresholdViolation(): void
    {
        $mockAdapter = $this->createMock(IxcAdapter::class);
        $mockAdapter->method('getEquipmentStatus')->willReturn([
            'status' => 'up',
            'online' => true,
            'message' => 'OLT Centro is online',
            'metadata' => [
                'equipment_id' => 'OLT-01',
                'cpu_usage' => 95,
                'memory_usage' => 60,
                'temperature' => 38.5,
            ],
        ]);

        $checker = new IxcEquipmentChecker($mockAdapter);

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'OLT Centro Monitor',
            'type' => 'ixc_equipment',
            'timeout' => 30,
            'configuration' => json_encode([
                'integration_id' => 1,
                'equipment_id' => 'OLT-01',
                'thresholds' => [
                    'cpu_usage' => 80,
                    'memory_usage' => 85,
                    'temperature' => 65,
                ],
            ]),
        ]);

        $result = $checker->check($monitor);

        $this->assertEquals('degraded', $result['status']);
        $this->assertStringContainsString('CPU', $result['error_message']);
    }

    /**
     * Test IxcEquipmentChecker check with timeout (exception)
     */
    public function testEquipmentCheckerCheckTimeout(): void
    {
        $mockAdapter = $this->createMock(IxcAdapter::class);
        $mockAdapter->method('getEquipmentStatus')
            ->willThrowException(new \RuntimeException('Connection timed out'));

        $checker = new IxcEquipmentChecker($mockAdapter);

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'OLT Centro Monitor',
            'type' => 'ixc_equipment',
            'timeout' => 30,
            'configuration' => json_encode([
                'integration_id' => 1,
                'equipment_id' => 'OLT-01',
            ]),
        ]);

        $result = $checker->check($monitor);

        $this->assertEquals('down', $result['status']);
        $this->assertStringContainsString('timed out', $result['error_message']);
    }

    /**
     * Test IxcEquipmentChecker check with missing equipment_id
     */
    public function testEquipmentCheckerCheckMissingEquipmentId(): void
    {
        $checker = new IxcEquipmentChecker();

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'OLT Monitor',
            'type' => 'ixc_equipment',
            'timeout' => 30,
            'target' => 'ixc://equipment',
            'configuration' => json_encode([
                'integration_id' => 1,
            ]),
        ]);

        $result = $checker->check($monitor);

        $this->assertEquals('down', $result['status']);
        $this->assertStringContainsString('equipment_id', $result['error_message']);
    }
}
