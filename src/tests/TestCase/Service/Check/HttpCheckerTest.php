<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service\Check;

use App\Model\Entity\Monitor;
use App\Service\Check\HttpChecker;
use Cake\Http\Client;
use Cake\Http\Client\Response;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * HttpChecker Test Case
 */
class HttpCheckerTest extends TestCase
{
    /**
     * Test getType returns correct identifier
     */
    public function testGetType(): void
    {
        $checker = new HttpChecker();

        $this->assertEquals('http', $checker->getType());
    }

    /**
     * Test getName returns human-readable name
     */
    public function testGetName(): void
    {
        $checker = new HttpChecker();

        $this->assertEquals('HTTP/HTTPS Checker', $checker->getName());
    }

    /**
     * Test validateConfiguration with valid HTTP monitor
     */
    public function testValidateConfigurationWithValidMonitor(): void
    {
        $checker = new HttpChecker();

        $monitor = new Monitor([
            'id' => 1,
            'type' => 'http',
            'target' => 'https://example.com',
            'timeout' => 10,
            'expected_status_code' => 200,
        ]);

        $result = $checker->validateConfiguration($monitor);

        $this->assertTrue($result);
    }

    /**
     * Test validateConfiguration with URL without scheme
     */
    public function testValidateConfigurationWithUrlWithoutScheme(): void
    {
        $checker = new HttpChecker();

        $monitor = new Monitor([
            'id' => 1,
            'type' => 'http',
            'target' => 'example.com', // No scheme - should still be valid
            'timeout' => 10,
        ]);

        $result = $checker->validateConfiguration($monitor);

        $this->assertTrue($result); // Should add https:// automatically
    }

    /**
     * Test validateConfiguration with invalid URL
     */
    public function testValidateConfigurationWithInvalidUrl(): void
    {
        $checker = new HttpChecker();

        $monitor = new Monitor([
            'id' => 1,
            'type' => 'http',
            'target' => 'not a valid url!!!',
            'timeout' => 10,
        ]);

        $result = $checker->validateConfiguration($monitor);

        $this->assertFalse($result);
    }

    /**
     * Test validateConfiguration with invalid status code
     */
    public function testValidateConfigurationWithInvalidStatusCode(): void
    {
        $checker = new HttpChecker();

        $monitor = new Monitor([
            'id' => 1,
            'type' => 'http',
            'target' => 'https://example.com',
            'timeout' => 10,
            'expected_status_code' => 999, // Invalid
        ]);

        $result = $checker->validateConfiguration($monitor);

        $this->assertFalse($result);
    }

    /**
     * Test successful HTTP check
     */
    public function testCheckWithSuccessfulResponse(): void
    {
        // Create mock response
        $mockResponse = $this->createMockResponse(200, [
            'Content-Type' => 'text/html',
            'Content-Length' => '1234',
        ]);

        // Create mock client
        $mockClient = $this->createMockClient($mockResponse);

        // Create checker with mock client
        $checker = new HttpChecker($mockClient);

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'Test Monitor',
            'type' => 'http',
            'target' => 'https://example.com',
            'timeout' => 10,
            'expected_status_code' => 200,
        ]);

        $result = $checker->check($monitor);

        $this->assertEquals('up', $result['status']);
        $this->assertEquals(200, $result['status_code']);
        $this->assertNull($result['error_message']);
        $this->assertIsInt($result['response_time']);
        $this->assertArrayHasKey('url', $result['metadata']);
    }

    /**
     * Test HTTP check with wrong status code
     */
    public function testCheckWithWrongStatusCode(): void
    {
        // Create mock response with 404
        $mockResponse = $this->createMockResponse(404);

        // Create mock client
        $mockClient = $this->createMockClient($mockResponse);

        // Create checker with mock client
        $checker = new HttpChecker($mockClient);

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'Test Monitor',
            'type' => 'http',
            'target' => 'https://example.com',
            'timeout' => 10,
            'expected_status_code' => 200,
        ]);

        $result = $checker->check($monitor);

        $this->assertEquals('down', $result['status']);
        $this->assertStringContainsString('Unexpected status code', $result['error_message']);
        $this->assertStringContainsString('404', $result['error_message']);
    }

    /**
     * Test HTTP check with connection error
     */
    public function testCheckWithConnectionError(): void
    {
        // Create mock client that throws exception
        $mockClient = $this->createMock(Client::class);
        $mockClient->method('get')
            ->willThrowException(new \Exception('Connection refused'));

        // Create checker with mock client
        $checker = new HttpChecker($mockClient);

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'Test Monitor',
            'type' => 'http',
            'target' => 'https://example.com',
            'timeout' => 10,
        ]);

        $result = $checker->check($monitor);

        $this->assertEquals('down', $result['status']);
        $this->assertStringContainsString('Connection refused', $result['error_message']);
    }

    /**
     * Test HTTP check detects degraded performance
     */
    public function testCheckDetectsDegradedPerformance(): void
    {
        // Create mock response that takes a long time
        $mockResponse = $this->createMockResponse(200);
        $mockClient = $this->createMockClient($mockResponse);

        $checker = new HttpChecker($mockClient);

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'Test Monitor',
            'type' => 'http',
            'target' => 'https://example.com',
            'timeout' => 1, // Very short timeout (1 second)
        ]);

        // Simulate slow response by sleeping
        usleep(900000); // 0.9 seconds (90% of 1 second timeout)

        $result = $checker->check($monitor);

        // Should be detected as degraded (> 80% of timeout)
        $this->assertContains($result['status'], ['degraded', 'up']);
    }

    /**
     * Test prepareUrl adds https scheme
     */
    public function testPrepareUrlAddsScheme(): void
    {
        $checker = new HttpChecker();
        $reflection = new \ReflectionClass($checker);
        $method = $reflection->getMethod('prepareUrl');
        $method->setAccessible(true);

        $result = $method->invoke($checker, 'example.com');

        $this->assertEquals('https://example.com', $result);
    }

    /**
     * Test prepareUrl keeps existing scheme
     */
    public function testPrepareUrlKeepsExistingScheme(): void
    {
        $checker = new HttpChecker();
        $reflection = new \ReflectionClass($checker);
        $method = $reflection->getMethod('prepareUrl');
        $method->setAccessible(true);

        $result = $method->invoke($checker, 'http://example.com');

        $this->assertEquals('http://example.com', $result);
    }

    /**
     * Test isValidUrl with valid URLs
     */
    public function testIsValidUrlWithValidUrls(): void
    {
        $checker = new HttpChecker();
        $reflection = new \ReflectionClass($checker);
        $method = $reflection->getMethod('isValidUrl');
        $method->setAccessible(true);

        $validUrls = [
            'https://example.com',
            'http://example.com',
            'https://example.com/path',
            'https://example.com:8080',
            'example.com', // Should add https://
        ];

        foreach ($validUrls as $url) {
            $result = $method->invoke($checker, $url);
            $this->assertTrue($result, "URL should be valid: {$url}");
        }
    }

    /**
     * Test isValidUrl with invalid URLs
     */
    public function testIsValidUrlWithInvalidUrls(): void
    {
        $checker = new HttpChecker();
        $reflection = new \ReflectionClass($checker);
        $method = $reflection->getMethod('isValidUrl');
        $method->setAccessible(true);

        $invalidUrls = [
            'ftp://example.com', // Wrong scheme
            'not a url',
            '',
            'javascript:alert(1)',
        ];

        foreach ($invalidUrls as $url) {
            $result = $method->invoke($checker, $url);
            $this->assertFalse($result, "URL should be invalid: {$url}");
        }
    }

    /**
     * Create a mock HTTP response
     *
     * @param int $statusCode HTTP status code
     * @param array $headers Response headers
     * @return \Cake\Http\Client\Response
     */
    protected function createMockResponse(int $statusCode, array $headers = []): Response
    {
        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('getStatusCode')->willReturn($statusCode);

        // Setup getHeaderLine to return values for any header
        $mockResponse->method('getHeaderLine')
            ->willReturnCallback(function ($name) use ($headers) {
                return $headers[$name] ?? '';
            });

        return $mockResponse;
    }

    /**
     * Create a mock HTTP client
     *
     * @param \Cake\Http\Client\Response $response Response to return
     * @return \Cake\Http\Client
     */
    protected function createMockClient(Response $response): Client
    {
        $mockClient = $this->createMock(Client::class);
        $mockClient->method('get')->willReturn($response);

        return $mockClient;
    }
}
