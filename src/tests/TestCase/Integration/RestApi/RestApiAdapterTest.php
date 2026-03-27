<?php
declare(strict_types=1);

namespace App\Test\TestCase\Integration\RestApi;

use App\Integration\RestApi\RestApiAdapter;
use App\Integration\RestApi\RestApiClient;
use Cake\Http\Client;
use Cake\Http\Client\Response;
use Cake\TestSuite\TestCase;

/**
 * RestApiAdapter Test Case
 */
class RestApiAdapterTest extends TestCase
{
    /**
     * Test getName returns correct name
     */
    public function testGetName(): void
    {
        $adapter = new RestApiAdapter([
            'base_url' => 'https://api.example.com',
        ]);

        $this->assertEquals('REST API', $adapter->getName());
    }

    /**
     * Test getType returns correct type
     */
    public function testGetType(): void
    {
        $adapter = new RestApiAdapter([
            'base_url' => 'https://api.example.com',
        ]);

        $this->assertEquals('rest_api', $adapter->getType());
    }

    /**
     * Test connect succeeds with valid config
     */
    public function testConnectWithValidConfig(): void
    {
        $adapter = new RestApiAdapter([
            'base_url' => 'https://api.example.com',
        ]);

        $result = $adapter->connect();

        $this->assertTrue($result);
        $this->assertTrue($adapter->isConnected());
    }

    /**
     * Test connect fails without base_url
     */
    public function testConnectFailsWithoutBaseUrl(): void
    {
        $adapter = new RestApiAdapter([]);

        $this->expectException(\InvalidArgumentException::class);
        $adapter->connect();
    }

    /**
     * Test disconnect resets state
     */
    public function testDisconnect(): void
    {
        $adapter = new RestApiAdapter([
            'base_url' => 'https://api.example.com',
        ]);

        $adapter->connect();
        $this->assertTrue($adapter->isConnected());

        $adapter->disconnect();
        $this->assertFalse($adapter->isConnected());
        $this->assertNull($adapter->getClient());
    }

    /**
     * Test testConnection with successful response
     */
    public function testTestConnectionSuccess(): void
    {
        $mockResponse = $this->createMockResponse(200, '{"status":"ok"}');
        $mockClient = $this->createMockHttpClient($mockResponse);

        $restApiClient = new RestApiClient(
            'https://api.example.com',
            [],
            30,
            $mockClient
        );

        $adapter = new RestApiAdapter([
            'base_url' => 'https://api.example.com',
        ]);
        $adapter->setClient($restApiClient);

        $result = $adapter->testConnection();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('status_code', $result);
        $this->assertEquals(200, $result['status_code']);
    }

    /**
     * Test testConnection with failed response
     */
    public function testTestConnectionFailure(): void
    {
        $mockResponse = $this->createMockResponse(500, 'Internal Server Error');
        $mockClient = $this->createMockHttpClient($mockResponse);

        $restApiClient = new RestApiClient(
            'https://api.example.com',
            [],
            30,
            $mockClient
        );

        $adapter = new RestApiAdapter([
            'base_url' => 'https://api.example.com',
        ]);
        $adapter->setClient($restApiClient);

        $result = $adapter->testConnection();

        $this->assertFalse($result['success']);
    }

    /**
     * Test testConnection with connection error
     */
    public function testTestConnectionWithException(): void
    {
        $mockClient = $this->createMock(Client::class);
        $mockClient->method('get')
            ->willThrowException(new \Exception('Connection refused'));

        $restApiClient = new RestApiClient(
            'https://api.example.com',
            [],
            30,
            $mockClient
        );

        $adapter = new RestApiAdapter([
            'base_url' => 'https://api.example.com',
        ]);
        $adapter->setClient($restApiClient);

        $result = $adapter->testConnection();

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Connection refused', $result['error']);
    }

    /**
     * Test resolveJsonPath with simple path
     */
    public function testResolveJsonPathSimple(): void
    {
        $adapter = new RestApiAdapter(['base_url' => 'https://api.example.com']);

        $data = ['status' => 'ok', 'version' => '1.0'];
        $result = $adapter->resolveJsonPath($data, 'status');

        $this->assertEquals('ok', $result);
    }

    /**
     * Test resolveJsonPath with nested path
     */
    public function testResolveJsonPathNested(): void
    {
        $adapter = new RestApiAdapter(['base_url' => 'https://api.example.com']);

        $data = [
            'data' => [
                'health' => [
                    'status' => 'operational',
                ],
            ],
        ];

        $result = $adapter->resolveJsonPath($data, 'data.health.status');

        $this->assertEquals('operational', $result);
    }

    /**
     * Test resolveJsonPath returns null for missing path
     */
    public function testResolveJsonPathMissing(): void
    {
        $adapter = new RestApiAdapter(['base_url' => 'https://api.example.com']);

        $data = ['status' => 'ok'];
        $result = $adapter->resolveJsonPath($data, 'data.missing.path');

        $this->assertNull($result);
    }

    /**
     * Test validateResponse with status code check
     */
    public function testValidateResponseStatusCode(): void
    {
        $adapter = new RestApiAdapter([
            'base_url' => 'https://api.example.com',
            'expected_status' => 200,
        ]);
        $adapter->connect();

        $mockResponse = $this->createMockResponse(200, '{"status":"ok"}');
        $result = $adapter->validateResponse($mockResponse);

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['checks']);
        $this->assertTrue($result['checks'][0]['passed']);
    }

    /**
     * Test validateResponse with failed status code check
     */
    public function testValidateResponseStatusCodeFailed(): void
    {
        $adapter = new RestApiAdapter([
            'base_url' => 'https://api.example.com',
            'expected_status' => 200,
        ]);
        $adapter->connect();

        $mockResponse = $this->createMockResponse(404, 'Not Found');
        $result = $adapter->validateResponse($mockResponse);

        $this->assertFalse($result['success']);
    }

    /**
     * Test validateResponse with json_path check
     */
    public function testValidateResponseJsonPath(): void
    {
        $adapter = new RestApiAdapter([
            'base_url' => 'https://api.example.com',
            'json_path' => 'status',
            'expected_value' => 'ok',
        ]);
        $adapter->connect();

        $mockResponse = $this->createMockResponse(200, '{"status":"ok"}');
        $result = $adapter->validateResponse($mockResponse);

        $this->assertTrue($result['success']);
    }

    /**
     * Test validateResponse with failed json_path check
     */
    public function testValidateResponseJsonPathFailed(): void
    {
        $adapter = new RestApiAdapter([
            'base_url' => 'https://api.example.com',
            'json_path' => 'status',
            'expected_value' => 'ok',
        ]);
        $adapter->connect();

        $mockResponse = $this->createMockResponse(200, '{"status":"error"}');
        $result = $adapter->validateResponse($mockResponse);

        $this->assertFalse($result['success']);
    }

    /**
     * Test validateResponse with content_contains check
     */
    public function testValidateResponseContentContains(): void
    {
        $adapter = new RestApiAdapter([
            'base_url' => 'https://api.example.com',
            'content_contains' => 'healthy',
        ]);
        $adapter->connect();

        $mockResponse = $this->createMockResponse(200, '{"status":"healthy"}');
        $result = $adapter->validateResponse($mockResponse);

        $this->assertTrue($result['success']);
    }

    /**
     * Test validateResponse with failed content_contains check
     */
    public function testValidateResponseContentContainsFailed(): void
    {
        $adapter = new RestApiAdapter([
            'base_url' => 'https://api.example.com',
            'content_contains' => 'healthy',
        ]);
        $adapter->connect();

        $mockResponse = $this->createMockResponse(200, '{"status":"error"}');
        $result = $adapter->validateResponse($mockResponse);

        $this->assertFalse($result['success']);
    }

    /**
     * Test getStatus with successful response
     */
    public function testGetStatusSuccess(): void
    {
        $mockResponse = $this->createMockResponse(200, '{"status":"ok"}');
        $mockClient = $this->createMockHttpClient($mockResponse);

        $restApiClient = new RestApiClient(
            'https://api.example.com',
            [],
            30,
            $mockClient
        );

        $adapter = new RestApiAdapter([
            'base_url' => 'https://api.example.com',
        ]);
        $adapter->setClient($restApiClient);

        $result = $adapter->getStatus('test-resource');

        $this->assertEquals('up', $result['status']);
        $this->assertTrue($result['online']);
    }

    /**
     * Test getMetrics returns metrics array
     */
    public function testGetMetrics(): void
    {
        $mockResponse = $this->createMockResponse(200, '{"cpu":45,"memory":60}');
        $mockClient = $this->createMockHttpClient($mockResponse);

        $restApiClient = new RestApiClient(
            'https://api.example.com',
            [],
            30,
            $mockClient
        );

        $adapter = new RestApiAdapter([
            'base_url' => 'https://api.example.com',
        ]);
        $adapter->setClient($restApiClient);

        $result = $adapter->getMetrics('test-resource');

        $this->assertArrayHasKey('metrics', $result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertArrayHasKey('resource_id', $result);
        $this->assertEquals('test-resource', $result['resource_id']);
    }

    /**
     * Create a mock HTTP response
     *
     * @param int $statusCode HTTP status code
     * @param string $body Response body
     * @return \Cake\Http\Client\Response
     */
    protected function createMockResponse(int $statusCode, string $body = ''): Response
    {
        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('getStatusCode')->willReturn($statusCode);
        $mockResponse->method('getStringBody')->willReturn($body);
        $mockResponse->method('getJson')->willReturn(
            json_decode($body, true) ?: null
        );
        $mockResponse->method('getHeaderLine')->willReturnCallback(
            function ($name) {
                return match (strtolower($name)) {
                    'content-type' => 'application/json',
                    default => '',
                };
            }
        );
        $mockResponse->method('isOk')->willReturn($statusCode >= 200 && $statusCode < 300);

        return $mockResponse;
    }

    /**
     * Create a mock HTTP client
     *
     * @param \Cake\Http\Client\Response $response Response to return
     * @return \Cake\Http\Client
     */
    protected function createMockHttpClient(Response $response): Client
    {
        $mockClient = $this->createMock(Client::class);
        $mockClient->method('get')->willReturn($response);
        $mockClient->method('post')->willReturn($response);
        $mockClient->method('put')->willReturn($response);
        $mockClient->method('delete')->willReturn($response);
        $mockClient->method('patch')->willReturn($response);

        return $mockClient;
    }
}
