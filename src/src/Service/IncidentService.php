<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Incident;
use App\Model\Entity\Monitor;
use App\Model\Table\IncidentsTable;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Psr\Log\LoggerAwareTrait;

/**
 * Incident Service
 *
 * Manages incident lifecycle:
 * - Auto-creates incidents when monitors go DOWN
 * - Auto-resolves incidents when monitors come back UP
 * - Calculates incident duration
 * - Provides incident query methods
 */
class IncidentService
{
    use LocatorAwareTrait;
    use LoggerAwareTrait;

    /**
     * Incidents table instance
     *
     * @var \App\Model\Table\IncidentsTable
     */
    private IncidentsTable $Incidents;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->Incidents = $this->fetchTable('Incidents');
    }

    /**
     * Create a new incident for a monitor that went DOWN
     *
     * Checks if there's already an active incident for this monitor.
     * If not, creates a new incident.
     *
     * @param \App\Model\Entity\Monitor $monitor The monitor that went down
     * @return \App\Model\Entity\Incident|null The created incident or null if already exists
     */
    public function createIncident(Monitor $monitor): ?Incident
    {
        // Check if there's already an active incident for this monitor
        $existingIncident = $this->Incidents
            ->find('activeByMonitor', ['monitorId' => $monitor->id])
            ->first();

        if ($existingIncident) {
            if ($this->logger) {
                $this->logger->debug('Active incident already exists for monitor', [
                    'monitor_id' => $monitor->id,
                    'incident_id' => $existingIncident->id,
                ]);
            }

            return null;
        }

        // Create new incident
        $incident = $this->Incidents->newEntity([
            'monitor_id' => $monitor->id,
            'title' => sprintf('%s is DOWN', $monitor->name),
            'description' => sprintf(
                'Monitor "%s" (%s) has been detected as DOWN.',
                $monitor->name,
                $monitor->url
            ),
            'status' => Incident::STATUS_INVESTIGATING,
            'severity' => $this->determineSeverity($monitor),
            'started_at' => DateTime::now(),
            'auto_created' => true,
        ]);

        $savedIncident = $this->Incidents->save($incident);

        if ($savedIncident) {
            if ($this->logger) {
                $this->logger->info('Incident created automatically', [
                    'incident_id' => $savedIncident->id,
                    'monitor_id' => $monitor->id,
                    'monitor_name' => $monitor->name,
                ]);
            }

            return $savedIncident;
        }

        if ($this->logger) {
            $this->logger->error('Failed to create incident', [
                'monitor_id' => $monitor->id,
                'errors' => $incident->getErrors(),
            ]);
        }

        return null;
    }

    /**
     * Update an incident's status
     *
     * @param \App\Model\Entity\Incident $incident The incident to update
     * @param string $status The new status
     * @param string|null $description Optional description for the update
     * @return \App\Model\Entity\Incident|false Updated incident or false on failure
     */
    public function updateIncident(Incident $incident, string $status, ?string $description = null): Incident|false
    {
        $incident->status = $status;

        if ($description !== null) {
            $incident->description = $description;
        }

        // Set identified_at timestamp when status changes to identified
        if ($status === Incident::STATUS_IDENTIFIED && $incident->identified_at === null) {
            $incident->identified_at = DateTime::now();
        }

        $result = $this->Incidents->save($incident);

        if ($result) {
            if ($this->logger) {
                $this->logger->info('Incident status updated', [
                    'incident_id' => $incident->id,
                    'new_status' => $status,
                ]);
            }

            return $result;
        }

        if ($this->logger) {
            $this->logger->error('Failed to update incident', [
                'incident_id' => $incident->id,
                'errors' => $incident->getErrors(),
            ]);
        }

        return false;
    }

    /**
     * Resolve an incident (mark as resolved and calculate duration)
     *
     * @param \App\Model\Entity\Incident $incident The incident to resolve
     * @return \App\Model\Entity\Incident|false Resolved incident or false on failure
     */
    public function resolveIncident(Incident $incident): Incident|false
    {
        // Don't resolve if already resolved
        if ($incident->isResolved()) {
            return $incident;
        }

        $now = DateTime::now();

        $incident->status = Incident::STATUS_RESOLVED;
        $incident->resolved_at = $now;

        // Calculate duration in seconds
        $incident->duration = $now->diffInSeconds($incident->started_at);

        $result = $this->Incidents->save($incident);

        if ($result) {
            if ($this->logger) {
                $this->logger->info('Incident resolved automatically', [
                    'incident_id' => $incident->id,
                    'monitor_id' => $incident->monitor_id,
                    'duration' => $incident->duration,
                ]);
            }

            return $result;
        }

        if ($this->logger) {
            $this->logger->error('Failed to resolve incident', [
                'incident_id' => $incident->id,
                'errors' => $incident->getErrors(),
            ]);
        }

        return false;
    }

    /**
     * Auto-resolve incidents for a monitor that came back UP
     *
     * Finds all active incidents for the monitor and resolves them.
     *
     * @param \App\Model\Entity\Monitor $monitor The monitor that came back up
     * @return int Number of incidents resolved
     */
    public function autoResolveIncidents(Monitor $monitor): int
    {
        $activeIncidents = $this->Incidents
            ->find('activeByMonitor', ['monitorId' => $monitor->id])
            ->all();

        $resolvedCount = 0;

        foreach ($activeIncidents as $incident) {
            if ($this->resolveIncident($incident)) {
                $resolvedCount++;
            }
        }

        if ($resolvedCount > 0 && $this->logger) {
            $this->logger->info('Auto-resolved incidents for monitor', [
                'monitor_id' => $monitor->id,
                'monitor_name' => $monitor->name,
                'resolved_count' => $resolvedCount,
            ]);
        }

        return $resolvedCount;
    }

    /**
     * Get all active (unresolved) incidents
     *
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function getActiveIncidents()
    {
        return $this->Incidents
            ->find('active')
            ->contain(['Monitors'])
            ->orderBy(['Incidents.started_at' => 'DESC']);
    }

    /**
     * Get active incident for a specific monitor
     *
     * @param int $monitorId Monitor ID
     * @return \App\Model\Entity\Incident|null
     */
    public function getActiveIncidentForMonitor(int $monitorId): ?Incident
    {
        return $this->Incidents
            ->find('activeByMonitor', ['monitorId' => $monitorId])
            ->first();
    }

    /**
     * Determine severity based on monitor configuration
     *
     * This is a simple implementation. In a real system, you might want to
     * consider factors like:
     * - Monitor type (critical services vs informational)
     * - Time of day
     * - Historical downtime patterns
     * - Impact on other monitors
     *
     * @param \App\Model\Entity\Monitor $monitor The monitor
     * @return string Severity level
     */
    private function determineSeverity(Monitor $monitor): string
    {
        // For now, all incidents are major
        // This can be enhanced later with monitor-specific configuration
        return Incident::SEVERITY_MAJOR;
    }
}
