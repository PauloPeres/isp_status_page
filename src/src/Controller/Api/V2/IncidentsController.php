<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

use App\Service\IncidentService;
use Cake\I18n\DateTime;

/**
 * IncidentsController — API v2
 *
 * CRUD, acknowledge, and timeline updates for the Angular SPA.
 *
 * TASK-NG-005
 */
class IncidentsController extends AppController
{
    /**
     * Incident service instance.
     *
     * @var \App\Service\IncidentService
     */
    protected IncidentService $incidentService;

    /**
     * Initialize method.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->incidentService = new IncidentService();
    }

    /**
     * GET /api/v2/incidents
     *
     * List incidents with status, severity, monitor, and search filters plus pagination.
     *
     * @return void
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        $incidentsTable = $this->fetchTable('Incidents');
        $query = $incidentsTable->find()
            ->contain(['Monitors'])
            ->orderBy(['Incidents.started_at' => 'DESC']);

        // Filter by status
        $status = $this->request->getQuery('status');
        if ($status) {
            if ($status === 'active') {
                $query->where(['Incidents.status !=' => 'resolved']);
            } else {
                $query->where(['Incidents.status' => $status]);
            }
        }

        // Filter by severity
        $severity = $this->request->getQuery('severity');
        if ($severity) {
            $query->where(['Incidents.severity' => $severity]);
        }

        // Filter by monitor
        $monitorId = $this->request->getQuery('monitor_id');
        if ($monitorId) {
            $query->where(['Incidents.monitor_id' => (int)$monitorId]);
        }

        // Filter by auto_created
        $autoCreated = $this->request->getQuery('auto_created');
        if ($autoCreated !== null && $autoCreated !== '') {
            $query->where(['Incidents.auto_created' => (bool)$autoCreated]);
        }

        // Search by title or description
        $search = $this->request->getQuery('search');
        if ($search) {
            $search = str_replace(['%', '_'], ['\\%', '\\_'], $search);
            $query->where([
                'OR' => [
                    'Incidents.title LIKE' => '%' . $search . '%',
                    'Incidents.description LIKE' => '%' . $search . '%',
                ],
            ]);
        }

        $page = max(1, (int)$this->request->getQuery('page', 1));
        $limit = min((int)$this->request->getQuery('limit', 25), 100);

        $total = $query->count();
        $items = $query->limit($limit)->offset(($page - 1) * $limit)->toArray();

        $this->success([
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int)ceil($total / $limit),
            ],
        ]);
    }

    /**
     * GET /api/v2/incidents/{id}
     *
     * Single incident with monitor, acknowledged-by user, alert logs,
     * and incident updates timeline.
     *
     * @param string $id Incident ID.
     * @return void
     */
    public function view(string $id): void
    {
        $this->request->allowMethod(['get']);

        $incidentsTable = $this->fetchTable('Incidents');

        try {
            $incident = $incidentsTable->get($id, contain: [
                'Monitors',
                'AcknowledgedByUsers',
                'AlertLogs' => function ($q) {
                    return $q->orderBy(['created' => 'DESC']);
                },
            ]);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            $this->error('Incident not found', 404);

            return;
        }

        // Load incident updates
        $incidentUpdates = $this->fetchTable('IncidentUpdates')
            ->find()
            ->contain(['Users'])
            ->where(['IncidentUpdates.incident_id' => $id])
            ->orderBy(['IncidentUpdates.created' => 'ASC'])
            ->toArray();

        // Build a structured timeline
        $timeline = $this->buildTimeline($incident);

        $this->success([
            'incident' => $incident,
            'updates' => $incidentUpdates,
            'timeline' => $timeline,
        ]);
    }

    /**
     * POST /api/v2/incidents
     *
     * Create a manual incident.
     *
     * @return void
     */
    public function add(): void
    {
        $this->request->allowMethod(['post']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $incidentsTable = $this->fetchTable('Incidents');
        $data = $this->request->getData();

        // Set defaults for manual incident
        if (!isset($data['auto_created'])) {
            $data['auto_created'] = false;
        }
        if (!isset($data['started_at'])) {
            $data['started_at'] = DateTime::now();
        }
        if (!isset($data['status'])) {
            $data['status'] = 'investigating';
        }
        if (!isset($data['severity'])) {
            $data['severity'] = 'minor';
        }

        $incident = $incidentsTable->newEntity($data);

        if ($incidentsTable->save($incident)) {
            $this->success(['incident' => $incident], 201);
        } else {
            $this->error('Unable to create incident', 422, $incident->getErrors());
        }
    }

    /**
     * PUT /api/v2/incidents/{id}
     *
     * Update incident status and/or description.
     *
     * @param string $id Incident ID.
     * @return void
     */
    public function edit(string $id): void
    {
        $this->request->allowMethod(['put', 'patch']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $incidentsTable = $this->fetchTable('Incidents');

        try {
            $incident = $incidentsTable->get($id, contain: ['Monitors']);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            $this->error('Incident not found', 404);

            return;
        }

        $data = $this->request->getData();
        $newStatus = $data['status'] ?? $incident->status;
        $description = $data['description'] ?? null;

        $updated = $this->incidentService->updateIncident($incident, $newStatus, $description);

        if ($updated) {
            // Reload to get fresh data
            $incident = $incidentsTable->get($id, contain: ['Monitors']);
            $this->success(['incident' => $incident]);
        } else {
            $this->error('Unable to update incident', 422, $incident->getErrors());
        }
    }

    /**
     * DELETE /api/v2/incidents/{id}
     *
     * Delete an incident.
     *
     * @param string $id Incident ID.
     * @return void
     */
    public function delete(string $id): void
    {
        $this->request->allowMethod(['delete']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $incidentsTable = $this->fetchTable('Incidents');

        try {
            $incident = $incidentsTable->get($id);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            $this->error('Incident not found', 404);

            return;
        }

        if ($incidentsTable->delete($incident)) {
            $this->success(['message' => 'Incident deleted']);
        } else {
            $this->error('Unable to delete incident', 500);
        }
    }

    /**
     * POST /api/v2/incidents/{id}/acknowledge
     *
     * Acknowledge an incident from the authenticated user.
     *
     * @param string $id Incident ID.
     * @return void
     */
    public function acknowledge(string $id): void
    {
        $this->request->allowMethod(['post']);

        $incidentsTable = $this->fetchTable('Incidents');

        try {
            $incident = $incidentsTable->get($id, contain: ['Monitors']);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            $this->error('Incident not found', 404);

            return;
        }

        if ($incident->isAcknowledged()) {
            $this->error('This incident has already been acknowledged', 409);

            return;
        }

        $incident->acknowledgeBy($this->currentUserId, \App\Model\Entity\Incident::ACK_VIA_WEB);

        if ($incidentsTable->save($incident)) {
            // Create timeline entry
            try {
                $usersTable = $this->fetchTable('Users');
                $user = $usersTable->get($this->currentUserId);
                $userName = $user->username ?? 'User';
            } catch (\Exception $e) {
                $userName = 'User';
            }
            $this->incidentService->createAcknowledgementUpdate($incident, $userName, 'api');

            $this->success(['incident' => $incident]);
        } else {
            $this->error('Unable to acknowledge incident', 500);
        }
    }

    /**
     * POST /api/v2/incidents/{id}/updates
     *
     * Add a timeline update to an incident.
     *
     * Expected body: { "status": "investigating|identified|monitoring|resolved|update", "message": "...", "is_public": true }
     *
     * @param string $id Incident ID.
     * @return void
     */
    public function addUpdate(string $id): void
    {
        $this->request->allowMethod(['post']);

        if (!$this->requireRole(['owner', 'admin', 'member'])) {
            return;
        }

        $incidentsTable = $this->fetchTable('Incidents');

        try {
            $incident = $incidentsTable->get($id);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            $this->error('Incident not found', 404);

            return;
        }

        $updatesTable = $this->fetchTable('IncidentUpdates');
        $update = $updatesTable->newEntity([
            'incident_id' => (int)$id,
            'organization_id' => $incident->organization_id,
            'user_id' => $this->currentUserId,
            'status' => $this->request->getData('status', 'update'),
            'message' => $this->request->getData('message'),
            'is_public' => (bool)$this->request->getData('is_public', true),
            'source' => 'api',
        ]);

        if (!$updatesTable->save($update)) {
            $this->error('Unable to add update', 422, $update->getErrors());

            return;
        }

        // If the status is a meaningful state change, update the incident too
        $statusValue = $update->status;
        if (in_array($statusValue, ['investigating', 'identified', 'monitoring', 'resolved'])) {
            $incident->set('status', $statusValue);
            if ($statusValue === 'identified' && $incident->identified_at === null) {
                $incident->set('identified_at', DateTime::now());
            }
            if ($statusValue === 'resolved') {
                $incident->set('resolved_at', DateTime::now());
                if ($incident->started_at) {
                    $incident->set('duration', DateTime::now()->diffInSeconds($incident->started_at));
                }
            }
            $incidentsTable->save($incident);
        }

        $this->success(['update' => $update], 201);
    }

    /**
     * Build a structured timeline for an incident.
     *
     * @param \App\Model\Entity\Incident $incident The incident entity.
     * @return array Timeline entries sorted most-recent first.
     */
    private function buildTimeline(\App\Model\Entity\Incident $incident): array
    {
        $timeline = [];

        $timeline[] = [
            'timestamp' => $incident->started_at,
            'type' => 'created',
            'title' => 'Incident Created',
            'description' => $incident->auto_created
                ? 'Automatically created when monitor went down'
                : 'Manually created',
        ];

        if ($incident->identified_at) {
            $timeline[] = [
                'timestamp' => $incident->identified_at,
                'type' => 'identified',
                'title' => 'Incident Identified',
                'description' => 'Status changed to identified',
            ];
        }

        if ($incident->acknowledged_at) {
            $ackDescription = 'Acknowledged via ' . ($incident->acknowledged_via ?? 'unknown');
            if ($incident->acknowledged_by_user) {
                $ackDescription .= ' by ' . $incident->acknowledged_by_user->username;
            }
            $timeline[] = [
                'timestamp' => $incident->acknowledged_at,
                'type' => 'acknowledged',
                'title' => 'Incident Acknowledged',
                'description' => $ackDescription,
            ];
        }

        if ($incident->resolved_at) {
            $duration = $incident->duration;
            $durationText = $duration !== null ? $this->formatDuration($duration) : 'N/A';
            $timeline[] = [
                'timestamp' => $incident->resolved_at,
                'type' => 'resolved',
                'title' => 'Incident Resolved',
                'description' => "Duration: {$durationText}",
            ];
        }

        usort($timeline, function ($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });

        return $timeline;
    }

    /**
     * Format duration in seconds to human-readable format.
     *
     * @param int $seconds Duration in seconds.
     * @return string Formatted duration.
     */
    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds} seconds";
        }

        $minutes = (int)floor($seconds / 60);
        if ($minutes < 60) {
            return "{$minutes} minutes";
        }

        $hours = (int)floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($hours < 24) {
            return $remainingMinutes > 0
                ? "{$hours}h {$remainingMinutes}m"
                : "{$hours} hours";
        }

        $days = (int)floor($hours / 24);
        $remainingHours = $hours % 24;

        return $remainingHours > 0
            ? "{$days}d {$remainingHours}h"
            : "{$days} days";
    }
}
