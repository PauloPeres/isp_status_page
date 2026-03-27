<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service\Check;

use App\Model\Entity\Monitor;
use App\Service\Check\KeywordChecker;
use Cake\Http\Client;
use Cake\Http\Client\Response;
use Cake\TestSuite\TestCase;

/**
 * KeywordChecker Test Case
 */
class KeywordCheckerTest extends TestCase
{
    /**
     * Test getType returns correct identifier
     */
    public function testGetType(): void
    {
        $checker = new KeywordChecker();

        $this->assertEquals('keyword', $checker->getType());
    }

    /**
     * Test getName returns human-readable name
     */
    public function testGetName(): void
    {
        $checker = new KeywordChecker();

        $this->assertEquals('Keyword Checker', $checker->getName());
    }

    /**
     * Test keyword found = success (contains mode)
     */
    public function testKeywordFoundReturnsSuccess(): void
    {
        $mockResponse = $this->createMockResponse(200, 'The service is running and healthy');
        $mockClient = $this->createMockClient($mockResponse);

        $checker = new KeywordChecker($mockClient);

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'Keyword Test',
            'type' => 'keyword',
            'target' => 'https://example.com',
            'timeout' => 10,
            'configuration' => json_encode([
                'url' => 'https://example.com/health',
                'keyword' => 'healthy',
                'keyword_type' => 'contains',
            ]),
        ]);

        $result = $checker->check($monitor);

        $this->assertEquals('up', $result['status']);
        $this->assertNull($result['error_message']);
        $this->assertTrue($result['metadata']['keyword_found']);
    }

    /**
     * Test keyword not found = down (contains mode)
     */
    public function testKeywordNotFoundReturnsDown(): void
    {
        $mockResponse = $this->createMockResponse(200, 'The service is running fine');
        $mockClient = $this->createMockClient($mockResponse);

        $checker = new KeywordChecker($mockClient);

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'Keyword Test',
            'type' => 'keyword',
            'target' => 'https://example.com',
            'timeout' => 10,
            'configuration' => json_encode([
                'url' => 'https://example.com/health',
                'keyword' => 'error_occurred',
                'keyword_type' => 'contains',
            ]),
        ]);

        $result = $checker->check($monitor);

        $this->assertEquals('down', $result['status']);
        $this->assertStringContainsString('not found', $result['error_message']);
        $this->assertFalse($result['metadata']['keyword_found']);
    }

    /**
     * Test not_contains mode — keyword absent = success
     */
    public function testNotContainsModeKeywordAbsentReturnsSuccess(): void
    {
        $mockResponse = $this->createMockResponse(200, 'Everything is working perfectly');
        $mockClient = $this->createMockClient($mockResponse);

        $checker = new KeywordChecker($mockClient);

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'Keyword Test',
            'type' => 'keyword',
            'target' => 'https://example.com',
            'timeout' => 10,
            'configuration' => json_encode([
                'url' => 'https://example.com/health',
                'keyword' => 'error',
                'keyword_type' => 'not_contains',
            ]),
        ]);

        $result = $checker->check($monitor);

        $this->assertEquals('up', $result['status']);
        $this->assertNull($result['error_message']);
    }

    /**
     * Test not_contains mode — keyword present = down
     */
    public function testNotContainsModeKeywordPresentReturnsDown(): void
    {
        $mockResponse = $this->createMockResponse(200, 'An error occurred in the system');
        $mockClient = $this->createMockClient($mockResponse);

        $checker = new KeywordChecker($mockClient);

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'Keyword Test',
            'type' => 'keyword',
            'target' => 'https://example.com',
            'timeout' => 10,
            'configuration' => json_encode([
                'url' => 'https://example.com/health',
                'keyword' => 'error',
                'keyword_type' => 'not_contains',
            ]),
        ]);

        $result = $checker->check($monitor);

        $this->assertEquals('down', $result['status']);
        $this->assertStringContainsString('was found', $result['error_message']);
    }

    /**
     * Test validateConfiguration with missing keyword
     */
    public function testValidateConfigurationMissingKeyword(): void
    {
        $checker = new KeywordChecker();

        $monitor = new Monitor([
            'id' => 1,
            'type' => 'keyword',
            'target' => 'https://example.com',
            'timeout' => 10,
            'configuration' => json_encode([
                'url' => 'https://example.com/health',
            ]),
        ]);

        $this->assertFalse($checker->validateConfiguration($monitor));
    }

    /**
     * Test validateConfiguration with valid config
     */
    public function testValidateConfigurationValid(): void
    {
        $checker = new KeywordChecker();

        $monitor = new Monitor([
            'id' => 1,
            'type' => 'keyword',
            'target' => 'https://example.com',
            'timeout' => 10,
            'configuration' => json_encode([
                'url' => 'https://example.com/health',
                'keyword' => 'healthy',
                'keyword_type' => 'contains',
            ]),
        ]);

        $this->assertTrue($checker->validateConfiguration($monitor));
    }

    /**
     * Test connection error returns down
     */
    public function testConnectionErrorReturnsDown(): void
    {
        $mockClient = $this->createMock(Client::class);
        $mockClient->method('get')
            ->willThrowException(new \Exception('Connection refused'));

        $checker = new KeywordChecker($mockClient);

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'Keyword Test',
            'type' => 'keyword',
            'target' => 'https://example.com',
            'timeout' => 10,
            'configuration' => json_encode([
                'url' => 'https://example.com/health',
                'keyword' => 'healthy',
                'keyword_type' => 'contains',
            ]),
        ]);

        $result = $checker->check($monitor);

        $this->assertEquals('down', $result['status']);
        $this->assertStringContainsString('Connection refused', $result['error_message']);
    }

    /**
     * Create a mock HTTP response with a body
     *
     * @param int $statusCode HTTP status code
     * @param string $body Response body
     * @return \Cake\Http\Client\Response
     */
    protected function createMockResponse(int $statusCode, string $body): Response
    {
        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('getStatusCode')->willReturn($statusCode);
        $mockResponse->method('getStringBody')->willReturn($body);
        $mockResponse->method('getHeaderLine')->willReturn('');

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
