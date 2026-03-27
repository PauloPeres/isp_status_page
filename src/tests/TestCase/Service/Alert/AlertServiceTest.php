<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service\Alert;

use App\Model\Entity\AlertLog;
use App\Model\Entity\AlertRule;
use App\Model\Entity\Incident;
use App\Model\Entity\Monitor;
use App\Service\Alert\AlertService;
use App\Service\Alert\ChannelInterface;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;

/**
 * AlertService Test Case
 */
class AlertServiceTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Organizations',
        'app.OrganizationUsers',
        'app.Monitors',
        'app.Incidents',
        'app.AlertRules',
        'app.AlertLogs',
        'app.MonitorChecks',
    ];

    /**
     * @var \App\Service\Alert\AlertService
     */
    protected AlertService $alertService;

    /**
     * setUp method
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->alertService = new AlertService();
    }

    /**
     * tearDown method
     */
    protected function tearDown(): void
    {
        unset($this->alertService);
        parent::tearDown();
    }

    /**
     * Test registerChannel adds channel to registry
     */
    public function testRegisterChannel(): void
    {
        $channel = $this->createMockChannel('email', 'Email Channel');

        $this->alertService->registerChannel($channel);

        $this->assertSame($channel, $this->alertService->getChannel('email'));
    }

    /**
     * Test getChannel returns null for unregistered channel
     */
    public function testGetChannelReturnsNullForUnregistered(): void
    {
        $this->assertNull($this->alertService->getChannel('telegram'));
    }

    /**
     * Test shouldTrigger for on_down rule when monitor is down
     */
    public function testShouldTriggerOnDownWhenMonitorIsDown(): void
    {
        $rule = $this->createAlertRule(AlertRule::TRIGGER_ON_DOWN);
        $monitor = $this->createMonitor(Monitor::STATUS_DOWN);

        $this->assertTrue($this->alertService->shouldTrigger($rule, $monitor));
    }

    /**
     * Test shouldTrigger for on_down rule when monitor is up
     */
    public function testShouldTriggerOnDownWhenMonitorIsUp(): void
    {
        $rule = $this->createAlertRule(AlertRule::TRIGGER_ON_DOWN);
        $monitor = $this->createMonitor(Monitor::STATUS_UP);

        $this->assertFalse($this->alertService->shouldTrigger($rule, $monitor));
    }

    /**
     * Test shouldTrigger for on_up rule when monitor is up
     */
    public function testShouldTriggerOnUpWhenMonitorIsUp(): void
    {
        $rule = $this->createAlertRule(AlertRule::TRIGGER_ON_UP);
        $monitor = $this->createMonitor(Monitor::STATUS_UP);

        $this->assertTrue($this->alertService->shouldTrigger($rule, $monitor));
    }

    /**
     * Test shouldTrigger for on_up rule when monitor is down
     */
    public function testShouldTriggerOnUpWhenMonitorIsDown(): void
    {
        $rule = $this->createAlertRule(AlertRule::TRIGGER_ON_UP);
        $monitor = $this->createMonitor(Monitor::STATUS_DOWN);

        $this->assertFalse($this->alertService->shouldTrigger($rule, $monitor));
    }

    /**
     * Test shouldTrigger for on_degraded rule
     */
    public function testShouldTriggerOnDegraded(): void
    {
        $rule = $this->createAlertRule(AlertRule::TRIGGER_ON_DEGRADED);
        $monitorDegraded = $this->createMonitor(Monitor::STATUS_DEGRADED);
        $monitorUp = $this->createMonitor(Monitor::STATUS_UP);

        $this->assertTrue($this->alertService->shouldTrigger($rule, $monitorDegraded));
        $this->assertFalse($this->alertService->shouldTrigger($rule, $monitorUp));
    }

    /**
     * Test shouldTrigger for on_change rule always returns true
     */
    public function testShouldTriggerOnChangeAlwaysTrue(): void
    {
        $rule = $this->createAlertRule(AlertRule::TRIGGER_ON_CHANGE);

        $this->assertTrue($this->alertService->shouldTrigger($rule, $this->createMonitor(Monitor::STATUS_DOWN)));
        $this->assertTrue($this->alertService->shouldTrigger($rule, $this->createMonitor(Monitor::STATUS_UP)));
        $this->assertTrue($this->alertService->shouldTrigger($rule, $this->createMonitor(Monitor::STATUS_DEGRADED)));
    }

    /**
     * Test shouldTrigger returns false for inactive rule
     */
    public function testShouldTriggerReturnsFalseForInactiveRule(): void
    {
        $rule = $this->createAlertRule(AlertRule::TRIGGER_ON_DOWN, false);
        $monitor = $this->createMonitor(Monitor::STATUS_DOWN);

        $this->assertFalse($this->alertService->shouldTrigger($rule, $monitor));
    }

    /**
     * Test checkThrottle allows sending when no previous alerts
     */
    public function testCheckThrottleAllowsWhenNoPreviousAlerts(): void
    {
        $rulesTable = $this->getTableLocator()->get('AlertRules');
        $rule = $rulesTable->get(1);

        // Delete all alert logs for this rule to ensure clean state
        $logsTable = $this->getTableLocator()->get('AlertLogs');
        $logsTable->deleteAll(['alert_rule_id' => $rule->id]);

        $this->assertTrue($this->alertService->checkThrottle($rule));
    }

    /**
     * Test checkThrottle blocks sending within cooldown period
     */
    public function testCheckThrottleBlocksWithinCooldown(): void
    {
        $rulesTable = $this->getTableLocator()->get('AlertRules');
        $rule = $rulesTable->get(1); // throttle_minutes = 5

        // Delete existing logs and create a fresh recent one
        $logsTable = $this->getTableLocator()->get('AlertLogs');
        $logsTable->deleteAll(['alert_rule_id' => $rule->id]);

        $recentLog = $logsTable->newEntity([
            'alert_rule_id' => $rule->id,
            'incident_id' => 1,
            'monitor_id' => 1,
            'channel' => 'email',
            'recipient' => 'admin@example.com',
            'status' => AlertLog::STATUS_SENT,
            'sent_at' => DateTime::now(),
            'created' => DateTime::now(),
        ]);
        $logsTable->save($recentLog);

        $this->assertFalse($this->alertService->checkThrottle($rule));
    }

    /**
     * Test checkThrottle allows sending when cooldown has expired
     */
    public function testCheckThrottleAllowsAfterCooldown(): void
    {
        $rulesTable = $this->getTableLocator()->get('AlertRules');
        $rule = $rulesTable->get(1); // throttle_minutes = 5

        // Delete existing logs and create an old one
        $logsTable = $this->getTableLocator()->get('AlertLogs');
        $logsTable->deleteAll(['alert_rule_id' => $rule->id]);

        $oldLog = $logsTable->newEntity([
            'alert_rule_id' => $rule->id,
            'incident_id' => 1,
            'monitor_id' => 1,
            'channel' => 'email',
            'recipient' => 'admin@example.com',
            'status' => AlertLog::STATUS_SENT,
            'sent_at' => new DateTime('-10 minutes'),
            'created' => new DateTime('-10 minutes'),
        ]);
        $logsTable->save($oldLog);

        $this->assertTrue($this->alertService->checkThrottle($rule));
    }

    /**
     * Test checkThrottle always allows when throttle_minutes is 0
     */
    public function testCheckThrottleAlwaysAllowsWhenZero(): void
    {
        $rulesTable = $this->getTableLocator()->get('AlertRules');
        $rule = $rulesTable->get(2); // throttle_minutes = 0

        $this->assertTrue($this->alertService->checkThrottle($rule));
    }

    /**
     * Test logAlert saves alert log to database
     */
    public function testLogAlertSavesRecord(): void
    {
        $rulesTable = $this->getTableLocator()->get('AlertRules');
        $incidentsTable = $this->getTableLocator()->get('Incidents');

        $rule = $rulesTable->get(1);
        $incident = $incidentsTable->get(1);

        $result = $this->alertService->logAlert(
            $rule,
            $incident,
            AlertLog::STATUS_SENT,
            null,
            'test@example.com'
        );

        $this->assertInstanceOf(AlertLog::class, $result);
        $this->assertEquals(AlertLog::STATUS_SENT, $result->status);
        $this->assertEquals('test@example.com', $result->recipient);
        $this->assertEquals($rule->id, $result->alert_rule_id);
        $this->assertEquals($incident->id, $result->incident_id);
        $this->assertNotNull($result->sent_at);
    }

    /**
     * Test logAlert saves failed status with error message
     */
    public function testLogAlertSavesFailedStatus(): void
    {
        $rulesTable = $this->getTableLocator()->get('AlertRules');
        $incidentsTable = $this->getTableLocator()->get('Incidents');

        $rule = $rulesTable->get(1);
        $incident = $incidentsTable->get(1);

        $result = $this->alertService->logAlert(
            $rule,
            $incident,
            AlertLog::STATUS_FAILED,
            'SMTP connection refused',
            'test@example.com'
        );

        $this->assertInstanceOf(AlertLog::class, $result);
        $this->assertEquals(AlertLog::STATUS_FAILED, $result->status);
        $this->assertEquals('SMTP connection refused', $result->error_message);
        $this->assertNull($result->sent_at);
    }

    /**
     * Test dispatch with mock channel
     */
    public function testDispatchWithMockChannel(): void
    {
        $monitorsTable = $this->getTableLocator()->get('Monitors');
        $monitor = $monitorsTable->get(1);
        $monitor->status = Monitor::STATUS_DOWN;

        // Create an active incident for this monitor
        $incidentsTable = $this->getTableLocator()->get('Incidents');
        $incident = $incidentsTable->newEntity([
            'monitor_id' => $monitor->id,
            'title' => 'Test Incident',
            'description' => 'Test',
            'status' => Incident::STATUS_INVESTIGATING,
            'severity' => Incident::SEVERITY_MAJOR,
            'started_at' => DateTime::now(),
            'auto_created' => true,
        ]);
        $incidentsTable->save($incident);

        // Clear existing alert logs for rule 1 to avoid throttle
        $logsTable = $this->getTableLocator()->get('AlertLogs');
        $logsTable->deleteAll(['alert_rule_id' => 1]);

        // Create mock channel
        $mockChannel = $this->createMock(ChannelInterface::class);
        $mockChannel->method('getType')->willReturn('email');
        $mockChannel->method('getName')->willReturn('Mock Email');
        $mockChannel->method('send')->willReturn([
            'success' => true,
            'results' => [
                ['recipient' => 'admin@example.com', 'status' => 'sent', 'error' => null],
                ['recipient' => 'ops@example.com', 'status' => 'sent', 'error' => null],
            ],
        ]);

        $this->alertService->registerChannel($mockChannel);

        $dispatched = $this->alertService->dispatch($monitor, $incident);

        $this->assertGreaterThanOrEqual(1, $dispatched);
    }

    /**
     * Test dispatch returns 0 when no rules match
     */
    public function testDispatchReturnsZeroWhenNoRulesMatch(): void
    {
        $monitorsTable = $this->getTableLocator()->get('Monitors');
        $monitor = $monitorsTable->get(1);
        $monitor->status = Monitor::STATUS_UP;

        $incidentsTable = $this->getTableLocator()->get('Incidents');
        $incident = $incidentsTable->get(1);

        // Register mock channel but no rules should match on_up with throttle
        // Rule 1 is on_down, so it won't trigger for UP status
        // Rule 2 is on_up but we need to ensure it doesn't conflict
        $mockChannel = $this->createMock(ChannelInterface::class);
        $mockChannel->method('getType')->willReturn('email');
        $mockChannel->method('getName')->willReturn('Mock Email');

        $this->alertService->registerChannel($mockChannel);

        // Monitor 3 has no rules
        $monitor3 = $monitorsTable->get(3);
        $monitor3->status = Monitor::STATUS_DOWN;

        $dispatched = $this->alertService->dispatch($monitor3, $incident);

        $this->assertEquals(0, $dispatched);
    }

    /**
     * Test dispatch handles missing channel gracefully
     */
    public function testDispatchHandlesMissingChannel(): void
    {
        $monitorsTable = $this->getTableLocator()->get('Monitors');
        $monitor = $monitorsTable->get(1);
        $monitor->status = Monitor::STATUS_DOWN;

        $incidentsTable = $this->getTableLocator()->get('Incidents');
        $incident = $incidentsTable->newEntity([
            'monitor_id' => $monitor->id,
            'title' => 'Test Incident',
            'description' => 'Test',
            'status' => Incident::STATUS_INVESTIGATING,
            'severity' => Incident::SEVERITY_MAJOR,
            'started_at' => DateTime::now(),
            'auto_created' => true,
        ]);
        $incidentsTable->save($incident);

        // Clear alert logs to avoid throttle
        $logsTable = $this->getTableLocator()->get('AlertLogs');
        $logsTable->deleteAll(['alert_rule_id' => 1]);

        // Don't register any channels - dispatch should handle gracefully
        $dispatched = $this->alertService->dispatch($monitor, $incident);

        $this->assertEquals(0, $dispatched);
    }

    /**
     * Create a mock ChannelInterface
     *
     * @param string $type Channel type
     * @param string $name Channel name
     * @return \App\Service\Alert\ChannelInterface
     */
    private function createMockChannel(string $type, string $name): ChannelInterface
    {
        $channel = $this->createMock(ChannelInterface::class);
        $channel->method('getType')->willReturn($type);
        $channel->method('getName')->willReturn($name);

        return $channel;
    }

    /**
     * Create an AlertRule entity for testing
     *
     * @param string $triggerOn Trigger type
     * @param bool $active Whether rule is active
     * @return \App\Model\Entity\AlertRule
     */
    private function createAlertRule(string $triggerOn, bool $active = true): AlertRule
    {
        $rule = new AlertRule();
        $rule->id = 99;
        $rule->monitor_id = 1;
        $rule->channel = 'email';
        $rule->trigger_on = $triggerOn;
        $rule->throttle_minutes = 5;
        $rule->recipients = '["test@example.com"]';
        $rule->active = $active;

        return $rule;
    }

    /**
     * Create a Monitor entity for testing
     *
     * @param string $status Monitor status
     * @return \App\Model\Entity\Monitor
     */
    private function createMonitor(string $status): Monitor
    {
        $monitor = new Monitor();
        $monitor->id = 1;
        $monitor->name = 'Test Monitor';
        $monitor->type = 'http';
        $monitor->status = $status;
        $monitor->active = true;

        return $monitor;
    }
}
