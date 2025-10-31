<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service\Check;

use App\Model\Entity\Monitor;
use App\Service\Check\AbstractChecker;
use App\Service\Check\CheckService;
use Cake\TestSuite\TestCase;

/**
 * CheckService Test Case
 */
class CheckServiceTest extends TestCase
{
    /**
     * @var \App\Service\Check\CheckService
     */
    protected CheckService $checkService;

    /**
     * setUp method
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->checkService = new CheckService();
    }

    /**
     * tearDown method
     */
    protected function tearDown(): void
    {
        unset($this->checkService);
        parent::tearDown();
    }

    /**
     * Test registerChecker method
     */
    public function testRegisterChecker(): void
    {
        $checker = new MockChecker();

        $this->checkService->registerChecker($checker);

        $this->assertTrue($this->checkService->hasChecker('mock'));
        $this->assertInstanceOf(MockChecker::class, $this->checkService->getChecker('mock'));
    }

    /**
     * Test getChecker returns null for unregistered type
     */
    public function testGetCheckerReturnsNullForUnregisteredType(): void
    {
        $result = $this->checkService->getChecker('nonexistent');

        $this->assertNull($result);
    }

    /**
     * Test hasChecker returns false for unregistered type
     */
    public function testHasCheckerReturnsFalseForUnregisteredType(): void
    {
        $result = $this->checkService->hasChecker('nonexistent');

        $this->assertFalse($result);
    }

    /**
     * Test getCheckers returns all registered checkers
     */
    public function testGetCheckersReturnsAllRegistered(): void
    {
        $checker1 = new MockChecker();
        $checker2 = new AnotherMockChecker();

        $this->checkService->registerChecker($checker1);
        $this->checkService->registerChecker($checker2);

        $checkers = $this->checkService->getCheckers();

        $this->assertCount(2, $checkers);
        $this->assertArrayHasKey('mock', $checkers);
        $this->assertArrayHasKey('anothermock', $checkers);
    }

    /**
     * Test executeCheck with valid monitor
     */
    public function testExecuteCheckWithValidMonitor(): void
    {
        $checker = new MockChecker();
        $this->checkService->registerChecker($checker);

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'Test Monitor',
            'type' => 'mock',
            'target' => 'http://example.com',
            'timeout' => 10,
        ]);

        $result = $this->checkService->executeCheck($monitor);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('response_time', $result);
        $this->assertArrayHasKey('checked_at', $result);
        $this->assertEquals('up', $result['status']);
    }

    /**
     * Test executeCheck throws exception for missing type
     */
    public function testExecuteCheckThrowsExceptionForMissingType(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('has no type defined');

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'Test Monitor',
            'type' => null,
        ]);

        $this->checkService->executeCheck($monitor);
    }

    /**
     * Test executeCheck throws exception for unregistered checker
     */
    public function testExecuteCheckThrowsExceptionForUnregisteredChecker(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No checker registered for type');

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'Test Monitor',
            'type' => 'nonexistent',
            'target' => 'http://example.com',
        ]);

        $this->checkService->executeCheck($monitor);
    }

    /**
     * Test executeChecks with multiple monitors
     */
    public function testExecuteChecksWithMultipleMonitors(): void
    {
        $checker = new MockChecker();
        $this->checkService->registerChecker($checker);

        $monitors = [
            new Monitor([
                'id' => 1,
                'name' => 'Monitor 1',
                'type' => 'mock',
                'target' => 'http://example.com',
                'timeout' => 10,
            ]),
            new Monitor([
                'id' => 2,
                'name' => 'Monitor 2',
                'type' => 'mock',
                'target' => 'http://example.org',
                'timeout' => 10,
            ]),
        ];

        $results = $this->checkService->executeChecks($monitors);

        $this->assertCount(2, $results);
        $this->assertArrayHasKey(1, $results);
        $this->assertArrayHasKey(2, $results);
        $this->assertEquals('up', $results[1]['status']);
        $this->assertEquals('up', $results[2]['status']);
    }

    /**
     * Test validateMonitorConfiguration
     */
    public function testValidateMonitorConfiguration(): void
    {
        $checker = new MockChecker();
        $this->checkService->registerChecker($checker);

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'Test Monitor',
            'type' => 'mock',
            'target' => 'http://example.com',
            'timeout' => 10,
        ]);

        $result = $this->checkService->validateMonitorConfiguration($monitor);

        $this->assertTrue($result);
    }

    /**
     * Test validateMonitorConfiguration with invalid configuration
     */
    public function testValidateMonitorConfigurationWithInvalidConfig(): void
    {
        $checker = new MockChecker();
        $this->checkService->registerChecker($checker);

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'Test Monitor',
            'type' => 'mock',
            'target' => '', // Invalid: empty target
            'timeout' => 10,
        ]);

        $result = $this->checkService->validateMonitorConfiguration($monitor);

        $this->assertFalse($result);
    }

    /**
     * Test getStatistics
     */
    public function testGetStatistics(): void
    {
        $checker1 = new MockChecker();
        $checker2 = new AnotherMockChecker();

        $this->checkService->registerChecker($checker1);
        $this->checkService->registerChecker($checker2);

        $stats = $this->checkService->getStatistics();

        $this->assertArrayHasKey('total_checkers', $stats);
        $this->assertArrayHasKey('checker_types', $stats);
        $this->assertArrayHasKey('checkers', $stats);
        $this->assertEquals(2, $stats['total_checkers']);
        $this->assertContains('mock', $stats['checker_types']);
        $this->assertContains('anothermock', $stats['checker_types']);
    }
}

/**
 * Mock Checker for testing
 */
class MockChecker extends AbstractChecker
{
    protected function executeCheck(Monitor $monitor): array
    {
        return $this->buildSuccessResult(100, 200);
    }
}

/**
 * Another Mock Checker for testing
 */
class AnotherMockChecker extends AbstractChecker
{
    protected function executeCheck(Monitor $monitor): array
    {
        return $this->buildSuccessResult(150, 200);
    }
}
