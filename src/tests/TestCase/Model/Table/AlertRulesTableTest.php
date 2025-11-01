<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Entity\AlertRule;
use App\Model\Table\AlertRulesTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\AlertRulesTable Test Case
 */
class AlertRulesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\AlertRulesTable
     */
    protected $AlertRules;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.AlertRules',
        'app.Monitors',
        'app.AlertLogs',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('AlertRules') ? [] : ['className' => AlertRulesTable::class];
        $this->AlertRules = TableRegistry::getTableLocator()->get('AlertRules', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->AlertRules);

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
            'monitor_id' => 1,
            'channel' => AlertRule::CHANNEL_EMAIL,
            'trigger_on' => AlertRule::TRIGGER_ON_DOWN,
            'throttle_minutes' => 5,
            'recipients' => '["test@example.com"]',
            'active' => true,
        ];

        $alertRule = $this->AlertRules->newEntity($data);
        $this->assertEmpty($alertRule->getErrors());
    }

    /**
     * Test validation fails with invalid channel
     *
     * @return void
     */
    public function testValidationFailsWithInvalidChannel(): void
    {
        $data = [
            'monitor_id' => 1,
            'channel' => 'invalid_channel',
            'trigger_on' => AlertRule::TRIGGER_ON_DOWN,
            'throttle_minutes' => 5,
            'recipients' => '["test@example.com"]',
            'active' => true,
        ];

        $alertRule = $this->AlertRules->newEntity($data);
        $this->assertNotEmpty($alertRule->getErrors());
        $this->assertArrayHasKey('channel', $alertRule->getErrors());
    }

    /**
     * Test validation fails with invalid trigger
     *
     * @return void
     */
    public function testValidationFailsWithInvalidTrigger(): void
    {
        $data = [
            'monitor_id' => 1,
            'channel' => AlertRule::CHANNEL_EMAIL,
            'trigger_on' => 'invalid_trigger',
            'throttle_minutes' => 5,
            'recipients' => '["test@example.com"]',
            'active' => true,
        ];

        $alertRule = $this->AlertRules->newEntity($data);
        $this->assertNotEmpty($alertRule->getErrors());
        $this->assertArrayHasKey('trigger_on', $alertRule->getErrors());
    }

    /**
     * Test findActive finder
     *
     * @return void
     */
    public function testFindActive(): void
    {
        $query = $this->AlertRules->find('active');
        $result = $query->all();

        $this->assertCount(3, $result);
        foreach ($result as $rule) {
            $this->assertTrue($rule->active);
        }
    }

    /**
     * Test getActiveRulesForMonitor method
     *
     * @return void
     */
    public function testGetActiveRulesForMonitor(): void
    {
        $rules = $this->AlertRules->getActiveRulesForMonitor(1);

        $this->assertCount(2, $rules);
        foreach ($rules as $rule) {
            $this->assertEquals(1, $rule->monitor_id);
            $this->assertTrue($rule->active);
        }
    }
}
