<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service\Check;

use App\Model\Entity\Monitor;
use App\Service\Check\HeartbeatChecker;
use Cake\I18n\DateTime;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * HeartbeatChecker Test Case
 */
class HeartbeatCheckerTest extends TestCase
{
    /**
     * @var \App\Service\Check\HeartbeatChecker
     */
    protected HeartbeatChecker $checker;

    /**
     * setUp method
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->checker = new HeartbeatChecker();
    }

    /**
     * Test getType returns correct identifier
     */
    public function testGetType(): void
    {
        $this->assertEquals('heartbeat', $this->checker->getType());
    }

    /**
     * Test getName returns human-readable name
     */
    public function testGetName(): void
    {
        $this->assertEquals('Heartbeat Checker', $this->checker->getName());
    }

    /**
     * Test validateConfiguration with valid monitor
     */
    public function testValidateConfigurationWithValidMonitor(): void
    {
        $monitor = new Monitor([
            'id' => 1,
            'name' => 'Test Heartbeat',
            'type' => 'heartbeat',
        ]);

        $this->assertTrue($this->checker->validateConfiguration($monitor));
    }

    /**
     * Test validateConfiguration with missing ID
     */
    public function testValidateConfigurationWithMissingId(): void
    {
        $monitor = new Monitor([
            'name' => 'Test Heartbeat',
            'type' => 'heartbeat',
        ]);

        $this->assertFalse($this->checker->validateConfiguration($monitor));
    }

    /**
     * Test heartbeat within interval returns success
     */
    public function testHeartbeatWithinIntervalReturnsSuccess(): void
    {
        // Create a mock heartbeat that was pinged recently (1 minute ago)
        $heartbeatEntity = new Entity([
            'id' => 1,
            'monitor_id' => 1,
            'token' => str_repeat('a', 64),
            'last_ping_at' => DateTime::now()->subMinutes(1),
            'expected_interval' => 300,
            'grace_period' => 60,
        ]);

        // Mock the HeartbeatsTable
        $mockTable = $this->createMock(Table::class);
        $mockQuery = $this->getMockBuilder(\Cake\ORM\Query\SelectQuery::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['where', 'first'])
            ->getMock();
        $mockQuery->method('where')->willReturnSelf();
        $mockQuery->method('first')->willReturn($heartbeatEntity);
        $mockTable->method('find')->willReturn($mockQuery);

        TableRegistry::getTableLocator()->set('Heartbeats', $mockTable);

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'Test Heartbeat',
            'type' => 'heartbeat',
        ]);

        $result = $this->checker->check($monitor);

        $this->assertEquals('up', $result['status']);
        $this->assertNull($result['error_message']);
        $this->assertArrayHasKey('last_ping_at', $result['metadata']);
        $this->assertArrayHasKey('last_ping_ago_seconds', $result['metadata']);
    }

    /**
     * Test heartbeat overdue returns down
     */
    public function testHeartbeatOverdueReturnsDown(): void
    {
        // Create a mock heartbeat that was pinged a long time ago (10 minutes ago, 5min interval)
        $heartbeatEntity = new Entity([
            'id' => 1,
            'monitor_id' => 1,
            'token' => str_repeat('b', 64),
            'last_ping_at' => DateTime::now()->subMinutes(10),
            'expected_interval' => 300, // 5 minutes
            'grace_period' => 60,       // 1 minute
        ]);

        $mockTable = $this->createMock(Table::class);
        $mockQuery = $this->getMockBuilder(\Cake\ORM\Query\SelectQuery::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['where', 'first'])
            ->getMock();
        $mockQuery->method('where')->willReturnSelf();
        $mockQuery->method('first')->willReturn($heartbeatEntity);
        $mockTable->method('find')->willReturn($mockQuery);

        TableRegistry::getTableLocator()->set('Heartbeats', $mockTable);

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'Test Heartbeat',
            'type' => 'heartbeat',
        ]);

        $result = $this->checker->check($monitor);

        $this->assertEquals('down', $result['status']);
        $this->assertStringContainsString('overdue', $result['error_message']);
        $this->assertArrayHasKey('overdue_seconds', $result['metadata']);
    }

    /**
     * Test heartbeat with grace period — ping is past interval but within grace
     */
    public function testHeartbeatWithGracePeriodReturnsSuccess(): void
    {
        // Ping was 5.5 minutes ago, interval is 5 min, grace is 2 min => still within deadline
        $heartbeatEntity = new Entity([
            'id' => 1,
            'monitor_id' => 1,
            'token' => str_repeat('c', 64),
            'last_ping_at' => DateTime::now()->subSeconds(330), // 5.5 minutes
            'expected_interval' => 300, // 5 minutes
            'grace_period' => 120,      // 2 minutes => deadline is 7 minutes
        ]);

        $mockTable = $this->createMock(Table::class);
        $mockQuery = $this->getMockBuilder(\Cake\ORM\Query\SelectQuery::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['where', 'first'])
            ->getMock();
        $mockQuery->method('where')->willReturnSelf();
        $mockQuery->method('first')->willReturn($heartbeatEntity);
        $mockTable->method('find')->willReturn($mockQuery);

        TableRegistry::getTableLocator()->set('Heartbeats', $mockTable);

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'Test Heartbeat',
            'type' => 'heartbeat',
        ]);

        $result = $this->checker->check($monitor);

        $this->assertEquals('up', $result['status']);
        $this->assertNull($result['error_message']);
    }

    /**
     * Test heartbeat that was never pinged returns down
     */
    public function testHeartbeatNeverPingedReturnsDown(): void
    {
        $heartbeatEntity = new Entity([
            'id' => 1,
            'monitor_id' => 1,
            'token' => str_repeat('d', 64),
            'last_ping_at' => null,
            'expected_interval' => 300,
            'grace_period' => 60,
        ]);

        $mockTable = $this->createMock(Table::class);
        $mockQuery = $this->getMockBuilder(\Cake\ORM\Query\SelectQuery::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['where', 'first'])
            ->getMock();
        $mockQuery->method('where')->willReturnSelf();
        $mockQuery->method('first')->willReturn($heartbeatEntity);
        $mockTable->method('find')->willReturn($mockQuery);

        TableRegistry::getTableLocator()->set('Heartbeats', $mockTable);

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'Test Heartbeat',
            'type' => 'heartbeat',
        ]);

        $result = $this->checker->check($monitor);

        $this->assertEquals('down', $result['status']);
        $this->assertStringContainsString('never been pinged', $result['error_message']);
    }

    /**
     * Test heartbeat with no heartbeat record returns error
     */
    public function testHeartbeatNoRecordReturnsDown(): void
    {
        $mockTable = $this->createMock(Table::class);
        $mockQuery = $this->getMockBuilder(\Cake\ORM\Query\SelectQuery::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['where', 'first'])
            ->getMock();
        $mockQuery->method('where')->willReturnSelf();
        $mockQuery->method('first')->willReturn(null);
        $mockTable->method('find')->willReturn($mockQuery);

        TableRegistry::getTableLocator()->set('Heartbeats', $mockTable);

        $monitor = new Monitor([
            'id' => 999,
            'name' => 'Test Heartbeat',
            'type' => 'heartbeat',
        ]);

        $result = $this->checker->check($monitor);

        $this->assertEquals('down', $result['status']);
        $this->assertStringContainsString('No heartbeat configuration', $result['error_message']);
    }

    /**
     * tearDown method
     */
    public function tearDown(): void
    {
        TableRegistry::getTableLocator()->clear();
        parent::tearDown();
    }
}
