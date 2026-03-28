<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Model\Entity\Incident;
use App\Service\EscalationService;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;

/**
 * App\Service\EscalationService Test Case
 */
class EscalationServiceTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Organizations',
        'app.OrganizationUsers',
        'app.Users',
        'app.Monitors',
        'app.MonitorChecks',
        'app.Incidents',
        'app.AlertRules',
        'app.AlertLogs',
        'app.EscalationPolicies',
        'app.EscalationSteps',
    ];

    /**
     * @var \App\Service\EscalationService
     */
    protected EscalationService $service;

    /**
     * setUp method
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EscalationService();
    }

    /**
     * Test processEscalation returns null when monitor has no escalation policy
     */
    public function testProcessEscalationNoPolicy(): void
    {
        // Monitor 1 has no escalation_policy_id set in fixture
        $incident = new Incident();
        $incident->id = 2;
        $incident->monitor_id = 1;
        $incident->started_at = new DateTime('-30 minutes');
        $incident->status = 'investigating';

        $result = $this->service->processEscalation($incident);
        $this->assertNull($result);
    }

    /**
     * Test processEscalation returns null when policy is inactive
     */
    public function testProcessEscalationInactivePolicy(): void
    {
        // Set monitor 2 to use the inactive policy (id=2)
        $monitorsTable = $this->getTableLocator()->get('Monitors');
        $monitor = $monitorsTable->get(2);
        $monitor->escalation_policy_id = 2;
        $monitorsTable->save($monitor);

        $incident = new Incident();
        $incident->id = 2;
        $incident->monitor_id = 2;
        $incident->started_at = new DateTime('-30 minutes');
        $incident->status = 'investigating';

        $result = $this->service->processEscalation($incident);
        $this->assertNull($result);
    }
}
