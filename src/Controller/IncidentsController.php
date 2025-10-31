<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\IncidentService;

/**
 * Incidents Controller
 *
 * Controller for managing incidents in the admin panel.
 * Allows viewing, filtering, and manually managing incidents.
 *
 * @property \App\Model\Table\IncidentsTable $Incidents
 */
class IncidentsController extends AppController
{
    /**
     * Incident service instance
     *
     * @var \App\Service\IncidentService
     */
    protected IncidentService $incidentService;

    /**
     * Initialize method
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->incidentService = new IncidentService();
    }

    /**
     * Index method
     *
     * Lists all incidents with optional filters.
     * Supports filtering by status, monitor, severity, and date range.
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $this->viewBuilder()->setLayout('admin');

        // Build query with filters
        $query = $this->Incidents->find()
            ->contain(['Monitors']);

        // Filter by status
        if ($this->request->getQuery('status')) {
            $status = $this->request->getQuery('status');
            if ($status === 'active') {
                $query->find('active');
            } else {
                $query->where(['Incidents.status' => $status]);
            }
        }

        // Filter by monitor
        if ($this->request->getQuery('monitor_id')) {
            $query->where(['Incidents.monitor_id' => $this->request->getQuery('monitor_id')]);
        }

        // Filter by severity
        if ($this->request->getQuery('severity')) {
            $query->where(['Incidents.severity' => $this->request->getQuery('severity')]);
        }

        // Filter by auto-created
        if ($this->request->getQuery('auto_created') !== null) {
            $query->where(['Incidents.auto_created' => (bool)$this->request->getQuery('auto_created')]);
        }

        // Search by title or description
        if ($this->request->getQuery('search')) {
            $search = $this->request->getQuery('search');
            $query->where([
                'OR' => [
                    'Incidents.title LIKE' => '%' . $search . '%',
                    'Incidents.description LIKE' => '%' . $search . '%',
                ]
            ]);
        }

        // Order by most recent first
        $query->orderBy(['Incidents.started_at' => 'DESC']);

        $incidents = $this->paginate($query);

        // Statistics
        $stats = [
            'total' => $this->Incidents->find()->count(),
            'active' => $this->Incidents->find('active')->count(),
            'resolved' => $this->Incidents->find()
                ->where(['status' => \App\Model\Entity\Incident::STATUS_RESOLVED])
                ->count(),
            'critical' => $this->Incidents->find()
                ->where(['severity' => \App\Model\Entity\Incident::SEVERITY_CRITICAL])
                ->count(),
        ];

        // Get list of monitors for filter dropdown
        $monitors = $this->Incidents->Monitors
            ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
            ->orderBy(['name' => 'ASC'])
            ->toArray();

        $this->set(compact('incidents', 'stats', 'monitors'));
    }

    /**
     * View method
     *
     * Displays incident details including timeline of status changes
     * and related monitor information.
     *
     * @param string|null $id Incident id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $this->viewBuilder()->setLayout('admin');

        $incident = $this->Incidents->get($id, [
            'contain' => [
                'Monitors',
                'AlertLogs' => function ($q) {
                    return $q->orderBy(['created' => 'DESC']);
                },
            ],
        ]);

        // Build timeline of events
        $timeline = $this->buildTimeline($incident);

        // Get monitor's recent checks
        $recentChecks = $this->Incidents->Monitors->MonitorChecks
            ->find()
            ->where(['monitor_id' => $incident->monitor_id])
            ->orderBy(['checked_at' => 'DESC'])
            ->limit(20)
            ->all();

        $this->set(compact('incident', 'timeline', 'recentChecks'));
    }

    /**
     * Edit method
     *
     * Allows manual update of incident status and description.
     *
     * @param string|null $id Incident id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $this->viewBuilder()->setLayout('admin');

        $incident = $this->Incidents->get($id, [
            'contain' => ['Monitors'],
        ]);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();

            // Use IncidentService to update incident
            $newStatus = $data['status'] ?? $incident->status;
            $description = $data['description'] ?? null;

            $updated = $this->incidentService->updateIncident($incident, $newStatus, $description);

            if ($updated) {
                $this->Flash->success(__('The incident has been updated.'));

                return $this->redirect(['action' => 'view', $id]);
            }

            $this->Flash->error(__('The incident could not be updated. Please, try again.'));
        }

        $this->set(compact('incident'));
    }

    /**
     * Resolve method
     *
     * Quickly resolves an incident (marks as resolved and calculates duration).
     *
     * @param string|null $id Incident id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function resolve($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);

        $incident = $this->Incidents->get($id);

        if ($incident->isResolved()) {
            $this->Flash->warning(__('This incident is already resolved.'));

            return $this->redirect(['action' => 'view', $id]);
        }

        $resolved = $this->incidentService->resolveIncident($incident);

        if ($resolved) {
            $this->Flash->success(__('The incident has been resolved.'));
        } else {
            $this->Flash->error(__('The incident could not be resolved. Please, try again.'));
        }

        return $this->redirect(['action' => 'view', $id]);
    }

    /**
     * Build timeline of incident events
     *
     * Creates a chronological timeline of all events related to the incident
     * including creation, status changes, and resolution.
     *
     * @param \App\Model\Entity\Incident $incident The incident entity
     * @return array Timeline entries
     */
    protected function buildTimeline(\App\Model\Entity\Incident $incident): array
    {
        $timeline = [];

        // Incident created
        $timeline[] = [
            'timestamp' => $incident->started_at,
            'type' => 'created',
            'title' => 'Incident Created',
            'description' => $incident->auto_created
                ? 'Automatically created when monitor went down'
                : 'Manually created',
            'icon' => '🚨',
            'color' => 'danger',
        ];

        // Incident identified
        if ($incident->identified_at) {
            $timeline[] = [
                'timestamp' => $incident->identified_at,
                'type' => 'identified',
                'title' => 'Incident Identified',
                'description' => 'Status changed to identified',
                'icon' => '🔍',
                'color' => 'warning',
            ];
        }

        // Incident resolved
        if ($incident->resolved_at) {
            $durationText = $this->formatDuration($incident->duration);
            $timeline[] = [
                'timestamp' => $incident->resolved_at,
                'type' => 'resolved',
                'title' => 'Incident Resolved',
                'description' => "Duration: {$durationText}",
                'icon' => '✅',
                'color' => 'success',
            ];
        }

        // Sort by timestamp descending (most recent first)
        usort($timeline, function ($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });

        return $timeline;
    }

    /**
     * Format duration in seconds to human-readable format
     *
     * @param int|null $seconds Duration in seconds
     * @return string Formatted duration
     */
    protected function formatDuration(?int $seconds): string
    {
        if ($seconds === null) {
            return 'N/A';
        }

        if ($seconds < 60) {
            return "{$seconds} seconds";
        }

        $minutes = floor($seconds / 60);
        if ($minutes < 60) {
            return "{$minutes} minutes";
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($hours < 24) {
            return $remainingMinutes > 0
                ? "{$hours}h {$remainingMinutes}m"
                : "{$hours} hours";
        }

        $days = floor($hours / 24);
        $remainingHours = $hours % 24;

        return $remainingHours > 0
            ? "{$days}d {$remainingHours}h"
            : "{$days} days";
    }
}
