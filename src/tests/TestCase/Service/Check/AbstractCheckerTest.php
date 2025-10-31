<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service\Check;

use App\Model\Entity\Monitor;
use App\Service\Check\AbstractChecker;
use Cake\TestSuite\TestCase;

/**
 * AbstractChecker Test Case
 */
class AbstractCheckerTest extends TestCase
{
    /**
     * Test buildSuccessResult
     */
    public function testBuildSuccessResult(): void
    {
        $checker = new ConcreteChecker();

        $result = $checker->testBuildSuccessResult(100, 200, ['key' => 'value']);

        $this->assertEquals('up', $result['status']);
        $this->assertEquals(100, $result['response_time']);
        $this->assertEquals(200, $result['status_code']);
        $this->assertNull($result['error_message']);
        $this->assertEquals(['key' => 'value'], $result['metadata']);
    }

    /**
     * Test buildErrorResult
     */
    public function testBuildErrorResult(): void
    {
        $checker = new ConcreteChecker();

        $result = $checker->testBuildErrorResult('Error occurred', 50, ['key' => 'value']);

        $this->assertEquals('down', $result['status']);
        $this->assertEquals(50, $result['response_time']);
        $this->assertNull($result['status_code']);
        $this->assertEquals('Error occurred', $result['error_message']);
        $this->assertEquals(['key' => 'value'], $result['metadata']);
    }

    /**
     * Test buildDegradedResult
     */
    public function testBuildDegradedResult(): void
    {
        $checker = new ConcreteChecker();

        $result = $checker->testBuildDegradedResult(800, 'Slow response', 200, ['key' => 'value']);

        $this->assertEquals('degraded', $result['status']);
        $this->assertEquals(800, $result['response_time']);
        $this->assertEquals(200, $result['status_code']);
        $this->assertEquals('Slow response', $result['error_message']);
        $this->assertEquals(['key' => 'value'], $result['metadata']);
    }

    /**
     * Test isDegraded returns true when response time exceeds threshold
     */
    public function testIsDegradedReturnsTrueWhenSlow(): void
    {
        $checker = new ConcreteChecker();

        $monitor = new Monitor([
            'timeout' => 10, // 10 seconds = 10000ms
        ]);

        // 80% of 10000 = 8000ms
        $result = $checker->testIsDegraded($monitor, 8500);

        $this->assertTrue($result);
    }

    /**
     * Test isDegraded returns false when response time is acceptable
     */
    public function testIsDegradedReturnsFalseWhenFast(): void
    {
        $checker = new ConcreteChecker();

        $monitor = new Monitor([
            'timeout' => 10,
        ]);

        $result = $checker->testIsDegraded($monitor, 5000);

        $this->assertFalse($result);
    }

    /**
     * Test validateConfiguration with valid monitor
     */
    public function testValidateConfigurationWithValidMonitor(): void
    {
        $checker = new ConcreteChecker();

        $monitor = new Monitor([
            'target' => 'http://example.com',
            'timeout' => 10,
        ]);

        $result = $checker->validateConfiguration($monitor);

        $this->assertTrue($result);
    }

    /**
     * Test validateConfiguration with empty target
     */
    public function testValidateConfigurationWithEmptyTarget(): void
    {
        $checker = new ConcreteChecker();

        $monitor = new Monitor([
            'target' => '',
            'timeout' => 10,
        ]);

        $result = $checker->validateConfiguration($monitor);

        $this->assertFalse($result);
    }

    /**
     * Test validateConfiguration with invalid timeout
     */
    public function testValidateConfigurationWithInvalidTimeout(): void
    {
        $checker = new ConcreteChecker();

        $monitor = new Monitor([
            'target' => 'http://example.com',
            'timeout' => 0,
        ]);

        $result = $checker->validateConfiguration($monitor);

        $this->assertFalse($result);
    }

    /**
     * Test getType returns lowercase class name without Checker
     */
    public function testGetTypeReturnsCorrectIdentifier(): void
    {
        $checker = new ConcreteChecker();

        $result = $checker->getType();

        $this->assertEquals('concrete', $result);
    }

    /**
     * Test getName returns human-readable name
     */
    public function testGetNameReturnsReadableName(): void
    {
        $checker = new ConcreteChecker();

        $result = $checker->getName();

        $this->assertEquals('Concrete Checker', $result);
    }

    /**
     * Test check method with successful execution
     */
    public function testCheckWithSuccessfulExecution(): void
    {
        $checker = new ConcreteChecker();

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'Test Monitor',
            'type' => 'concrete',
            'target' => 'http://example.com',
            'timeout' => 10,
        ]);

        $result = $checker->check($monitor);

        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('response_time', $result);
        $this->assertEquals('up', $result['status']);
    }

    /**
     * Test check method with exception
     */
    public function testCheckWithException(): void
    {
        $checker = new FailingChecker();

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'Test Monitor',
            'type' => 'failing',
            'target' => 'http://example.com',
            'timeout' => 10,
        ]);

        $result = $checker->check($monitor);

        $this->assertEquals('down', $result['status']);
        $this->assertNotNull($result['error_message']);
        $this->assertStringContainsString('Test exception', $result['error_message']);
    }
}

/**
 * Concrete implementation for testing AbstractChecker
 */
class ConcreteChecker extends AbstractChecker
{
    protected function executeCheck(Monitor $monitor): array
    {
        return $this->buildSuccessResult(100, 200);
    }

    // Public wrappers for testing protected methods
    public function testBuildSuccessResult(int $responseTime, ?int $statusCode = null, array $metadata = []): array
    {
        return $this->buildSuccessResult($responseTime, $statusCode, $metadata);
    }

    public function testBuildErrorResult(string $errorMessage, int $responseTime = 0, array $metadata = []): array
    {
        return $this->buildErrorResult($errorMessage, $responseTime, $metadata);
    }

    public function testBuildDegradedResult(int $responseTime, string $reason, ?int $statusCode = null, array $metadata = []): array
    {
        return $this->buildDegradedResult($responseTime, $reason, $statusCode, $metadata);
    }

    public function testIsDegraded(Monitor $monitor, int $responseTime): bool
    {
        return $this->isDegraded($monitor, $responseTime);
    }
}

/**
 * Failing checker for testing exception handling
 */
class FailingChecker extends AbstractChecker
{
    protected function executeCheck(Monitor $monitor): array
    {
        throw new \Exception('Test exception');
    }
}
