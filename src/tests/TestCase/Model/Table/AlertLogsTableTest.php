<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Entity\AlertLog;
use App\Model\Table\AlertLogsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\AlertLogsTable Test Case
 */
class AlertLogsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\AlertLogsTable
     */
    protected $AlertLogs;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.AlertLogs',
        'app.AlertRules',
        'app.Monitors',
        'app.Incidents',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('AlertLogs') ? [] : ['className' => AlertLogsTable::class];
        $this->AlertLogs = TableRegistry::getTableLocator()->get('AlertLogs', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->AlertLogs);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault(): void
    {
        $data = [
            'alert_rule_id' => 1,
            'monitor_id' => 1,
            'channel' => 'email',
            'recipient' => 'test@example.com',
            'status' => AlertLog::STATUS_SENT,
        ];

        $alertLog = $this->AlertLogs->newEntity($data);
        $this->assertEmpty($alertLog->getErrors());
    }

    /**
     * Test validation fails with invalid status
     *
     * @return void
     */
    public function testValidationFailsWithInvalidStatus(): void
    {
        $data = [
            'alert_rule_id' => 1,
            'monitor_id' => 1,
            'channel' => 'email',
            'recipient' => 'test@example.com',
            'status' => 'invalid_status',
        ];

        $alertLog = $this->AlertLogs->newEntity($data);
        $this->assertNotEmpty($alertLog->getErrors());
        $this->assertArrayHasKey('status', $alertLog->getErrors());
    }

    /**
     * Test findByStatus finder
     *
     * @return void
     */
    public function testFindByStatus(): void
    {
        $query = $this->AlertLogs->find('byStatus', status: AlertLog::STATUS_SENT);
        $result = $query->all();

        $this->assertCount(2, $result);
        foreach ($result as $log) {
            $this->assertEquals(AlertLog::STATUS_SENT, $log->status);
        }
    }

    /**
     * Test getStatistics method
     *
     * @return void
     */
    public function testGetStatistics(): void
    {
        // Test with default period (30 days) - fixtures use dynamic dates
        $stats = $this->AlertLogs->getStatistics();

        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('sent', $stats);
        $this->assertArrayHasKey('failed', $stats);
        $this->assertArrayHasKey('queued', $stats);
        $this->assertArrayHasKey('success_rate', $stats);

        $this->assertEquals(4, $stats['total']);
        $this->assertEquals(2, $stats['sent']);
        $this->assertEquals(1, $stats['failed']);
        $this->assertEquals(1, $stats['queued']);
        $this->assertEquals(50.0, $stats['success_rate']);
    }

    /**
     * Test getLastLogForMonitor method
     *
     * @return void
     */
    public function testGetLastLogForMonitor(): void
    {
        $log = $this->AlertLogs->getLastLogForMonitor(1);

        $this->assertNotNull($log);
        $this->assertEquals(1, $log->monitor_id);
        $this->assertEquals(3, $log->id); // Most recent based on created date
    }
}
