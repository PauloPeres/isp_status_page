<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Model\Entity\Incident;
use App\Model\Entity\Monitor;
use App\Service\IncidentService;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;

/**
 * IncidentService Test Case
 */
class IncidentServiceTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Monitors',
        'app.Incidents',
        'app.MonitorChecks',
    ];

    /**
     * @var \App\Service\IncidentService
     */
    protected IncidentService $incidentService;

    /**
     * setUp method
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->incidentService = new IncidentService();
    }

    /**
     * tearDown method
     */
    protected function tearDown(): void
    {
        unset($this->incidentService);
        parent::tearDown();
    }

    /**
     * Test createIncident creates new incident when monitor goes down
     */
    public function testCreateIncidentCreatesNewIncident(): void
    {
        $monitorsTable = $this->getTableLocator()->get('Monitors');
        $monitor = $monitorsTable->get(1);

        $incident = $this->incidentService->createIncident($monitor);

        $this->assertInstanceOf(Incident::class, $incident);
        $this->assertEquals($monitor->id, $incident->monitor_id);
        $this->assertEquals(Incident::STATUS_INVESTIGATING, $incident->status);
        $this->assertTrue($incident->auto_created);
        $this->assertNotNull($incident->started_at);
        $this->assertStringContainsString($monitor->name, $incident->title);
    }

    /**
     * Test createIncident returns null when active incident already exists
     */
    public function testCreateIncidentReturnsNullWhenActiveIncidentExists(): void
    {
        $monitorsTable = $this->getTableLocator()->get('Monitors');
        $monitor = $monitorsTable->get(1);

        // Create first incident
        $firstIncident = $this->incidentService->createIncident($monitor);
        $this->assertInstanceOf(Incident::class, $firstIncident);

        // Try to create second incident for same monitor
        $secondIncident = $this->incidentService->createIncident($monitor);
        $this->assertNull($secondIncident);
    }

    /**
     * Test updateIncident changes status
     */
    public function testUpdateIncidentChangesStatus(): void
    {
        $monitorsTable = $this->getTableLocator()->get('Monitors');
        $monitor = $monitorsTable->get(1);

        $incident = $this->incidentService->createIncident($monitor);
        $this->assertEquals(Incident::STATUS_INVESTIGATING, $incident->status);

        $updated = $this->incidentService->updateIncident(
            $incident,
            Incident::STATUS_IDENTIFIED,
            'We identified the issue'
        );

        $this->assertNotFalse($updated);
        $this->assertEquals(Incident::STATUS_IDENTIFIED, $updated->status);
        $this->assertEquals('We identified the issue', $updated->description);
        $this->assertNotNull($updated->identified_at);
    }

    /**
     * Test resolveIncident marks incident as resolved
     */
    public function testResolveIncidentMarksAsResolved(): void
    {
        $monitorsTable = $this->getTableLocator()->get('Monitors');
        $monitor = $monitorsTable->get(1);

        $incident = $this->incidentService->createIncident($monitor);
        $this->assertFalse($incident->isResolved());

        $resolved = $this->incidentService->resolveIncident($incident);

        $this->assertNotFalse($resolved);
        $this->assertEquals(Incident::STATUS_RESOLVED, $resolved->status);
        $this->assertNotNull($resolved->resolved_at);
        $this->assertNotNull($resolved->duration);
        $this->assertGreaterThanOrEqual(0, $resolved->duration);
        $this->assertTrue($resolved->isResolved());
    }

    /**
     * Test resolveIncident doesn't fail if incident already resolved
     */
    public function testResolveIncidentIdempotent(): void
    {
        $monitorsTable = $this->getTableLocator()->get('Monitors');
        $monitor = $monitorsTable->get(1);

        $incident = $this->incidentService->createIncident($monitor);

        // Resolve once
        $resolved1 = $this->incidentService->resolveIncident($incident);
        $this->assertNotFalse($resolved1);

        $firstResolvedAt = $resolved1->resolved_at;

        // Resolve again - should return the incident unchanged
        $resolved2 = $this->incidentService->resolveIncident($resolved1);
        $this->assertEquals($resolved1->id, $resolved2->id);
        $this->assertEquals($firstResolvedAt, $resolved2->resolved_at);
    }

    /**
     * Test autoResolveIncidents resolves all active incidents for monitor
     */
    public function testAutoResolveIncidentsResolvesActive(): void
    {
        $monitorsTable = $this->getTableLocator()->get('Monitors');
        $monitor = $monitorsTable->get(1);

        // Create incident
        $incident = $this->incidentService->createIncident($monitor);
        $this->assertFalse($incident->isResolved());

        // Auto-resolve
        $resolvedCount = $this->incidentService->autoResolveIncidents($monitor);

        $this->assertEquals(1, $resolvedCount);

        // Verify incident is now resolved
        $incidentsTable = $this->getTableLocator()->get('Incidents');
        $updatedIncident = $incidentsTable->get($incident->id);
        $this->assertTrue($updatedIncident->isResolved());
    }

    /**
     * Test autoResolveIncidents returns 0 when no active incidents
     */
    public function testAutoResolveIncidentsReturnsZeroWhenNoneActive(): void
    {
        $monitorsTable = $this->getTableLocator()->get('Monitors');
        $monitor = $monitorsTable->get(1);

        $resolvedCount = $this->incidentService->autoResolveIncidents($monitor);

        $this->assertEquals(0, $resolvedCount);
    }

    /**
     * Test getActiveIncidents returns only unresolved incidents
     */
    public function testGetActiveIncidentsReturnsOnlyUnresolved(): void
    {
        $monitorsTable = $this->getTableLocator()->get('Monitors');
        $monitor1 = $monitorsTable->get(1);
        $monitor2 = $monitorsTable->get(2);

        // Create two incidents
        $incident1 = $this->incidentService->createIncident($monitor1);
        $incident2 = $this->incidentService->createIncident($monitor2);

        // Resolve one
        $resolved = $this->incidentService->resolveIncident($incident1);
        $this->assertTrue($resolved->isResolved());

        // Get active incidents directly from the service (fresh query)
        $activeCount = $this->incidentService->getActiveIncidents()->count();

        $this->assertEquals(1, $activeCount);
    }

    /**
     * Test getActiveIncidentForMonitor returns active incident for specific monitor
     */
    public function testGetActiveIncidentForMonitorReturnsCorrectIncident(): void
    {
        $monitorsTable = $this->getTableLocator()->get('Monitors');
        $monitor1 = $monitorsTable->get(1);
        $monitor2 = $monitorsTable->get(2);

        // Create incidents for both monitors
        $incident1 = $this->incidentService->createIncident($monitor1);
        $incident2 = $this->incidentService->createIncident($monitor2);

        // Get active incident for monitor 1
        $activeIncident = $this->incidentService->getActiveIncidentForMonitor($monitor1->id);

        $this->assertNotNull($activeIncident);
        $this->assertEquals($incident1->id, $activeIncident->id);
        $this->assertEquals($monitor1->id, $activeIncident->monitor_id);
    }

    /**
     * Test getActiveIncidentForMonitor returns null when no active incident
     */
    public function testGetActiveIncidentForMonitorReturnsNullWhenNone(): void
    {
        $activeIncident = $this->incidentService->getActiveIncidentForMonitor(1);

        $this->assertNull($activeIncident);
    }

    /**
     * Test incident severity is determined correctly
     */
    public function testIncidentSeverityIsDetermined(): void
    {
        $monitorsTable = $this->getTableLocator()->get('Monitors');
        $monitor = $monitorsTable->get(1);

        $incident = $this->incidentService->createIncident($monitor);

        // For now, all incidents are major
        $this->assertEquals(Incident::SEVERITY_MAJOR, $incident->severity);
    }

    /**
     * Test incident entity helper methods
     */
    public function testIncidentEntityHelpers(): void
    {
        $monitorsTable = $this->getTableLocator()->get('Monitors');
        $monitor = $monitorsTable->get(1);

        $incident = $this->incidentService->createIncident($monitor);

        // Test isOngoing
        $this->assertTrue($incident->isOngoing());
        $this->assertFalse($incident->isResolved());

        // Test getSeverityBadgeClass
        $this->assertEquals('warning', $incident->getSeverityBadgeClass());

        // Test getStatusName
        $this->assertEquals('Investigating', $incident->getStatusName());

        // Resolve and test again
        $resolved = $this->incidentService->resolveIncident($incident);
        $this->assertFalse($resolved->isOngoing());
        $this->assertTrue($resolved->isResolved());
        $this->assertEquals('Resolved', $resolved->getStatusName());
    }
}
