<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Alert\AlertService;
use App\Service\IncidentService;
use App\Service\SettingService;
use Cake\I18n\DateTime;
use Cake\Log\Log;

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
     * Before filter — allow public acknowledge action (token-based auth)
     *
     * @param \Cake\Event\EventInterface $event The event
     * @return \Cake\Http\Response|null|void
     */
    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);
        $this->Authentication->addUnauthenticatedActions(['acknowledge']);
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
                'AcknowledgedByUsers',
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
                $this->Flash->success(__d('incidents', 'The incident has been updated.'));

                return $this->redirect(['action' => 'view', $id]);
            }

            $this->Flash->error(__d('incidents', 'The incident could not be updated. Please, try again.'));
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
            $this->Flash->warning(__d('incidents', 'This incident is already resolved.'));

            return $this->redirect(['action' => 'view', $id]);
        }

        $resolved = $this->incidentService->resolveIncident($incident);

        if ($resolved) {
            $this->Flash->success(__d('incidents', 'The incident has been resolved.'));
        } else {
            $this->Flash->error(__d('incidents', 'The incident could not be resolved. Please, try again.'));
        }

        return $this->redirect(['action' => 'view', $id]);
    }

    /**
     * Acknowledge an incident via public token link (no auth required)
     *
     * GET /incidents/acknowledge/{id}/{token}
     *
     * @param string|null $id Incident id
     * @param string|null $token Acknowledgement token
     * @return \Cake\Http\Response|null|void Renders view or redirects
     */
    public function acknowledge($id = null, $token = null)
    {
        $this->viewBuilder()->setLayout('public');

        try {
            $incident = $this->Incidents->get($id, [
                'contain' => ['Monitors', 'AcknowledgedByUsers'],
            ]);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            $this->Flash->error(__d('incidents', 'Incident not found.'));
            $this->set('error', __d('incidents', 'Incident not found.'));
            $this->set('incident', null);

            return;
        }

        // Validate token
        if (empty($token) || $token !== $incident->acknowledgement_token) {
            $this->Flash->error(__d('incidents', 'Invalid acknowledgement token.'));
            $this->set('error', __d('incidents', 'Invalid acknowledgement token.'));
            $this->set('incident', $incident);

            return;
        }

        // Check if already acknowledged
        if ($incident->isAcknowledged()) {
            $this->Flash->warning(__d('incidents', 'This incident has already been acknowledged.'));
            $this->set('error', null);
            $this->set('incident', $incident);
            $this->set('alreadyAcknowledged', true);

            return;
        }

        // Check token expiry (24h after incident creation)
        if (!$incident->isTokenValid()) {
            $this->Flash->error(__d('incidents', 'The acknowledgement token has expired (valid for 24h).'));
            $this->set('error', __d('incidents', 'The acknowledgement token has expired.'));
            $this->set('incident', $incident);

            return;
        }

        // Perform acknowledgement
        $incident->acknowledgeBy(null, \App\Model\Entity\Incident::ACK_VIA_EMAIL);

        if ($this->Incidents->save($incident)) {
            $this->Flash->success(__d('incidents', 'Incident acknowledged successfully.'));

            // Notify other recipients
            $this->notifyAcknowledgement($incident, 'Email link');

            $this->set('error', null);
            $this->set('incident', $incident);
            $this->set('success', true);

            return;
        }

        $this->Flash->error(__d('incidents', 'Error acknowledging incident.'));
        $this->set('error', __d('incidents', 'Error acknowledging incident.'));
        $this->set('incident', $incident);
    }

    /**
     * Acknowledge an incident from the admin panel (requires authentication)
     *
     * POST /incidents/{id}/acknowledge-admin
     *
     * @param string|null $id Incident id
     * @return \Cake\Http\Response|null Redirects to view
     */
    public function acknowledgeAdmin($id = null)
    {
        $this->request->allowMethod(['post']);

        $incident = $this->Incidents->get($id, [
            'contain' => ['Monitors'],
        ]);

        if ($incident->isAcknowledged()) {
            $this->Flash->warning(__d('incidents', 'This incident has already been acknowledged.'));

            return $this->redirect(['action' => 'view', $id]);
        }

        // Get the authenticated user
        $user = $this->Authentication->getIdentity();
        $userId = $user ? (int)$user->getIdentifier() : null;

        $incident->acknowledgeBy($userId, \App\Model\Entity\Incident::ACK_VIA_WEB);

        if ($this->Incidents->save($incident)) {
            $this->Flash->success(__d('incidents', 'Incident acknowledged successfully.'));

            // Notify other recipients
            $userName = $user ? ($user->get('username') ?? 'Admin') : 'Admin';
            $this->notifyAcknowledgement($incident, $userName);
        } else {
            $this->Flash->error(__d('incidents', 'Error acknowledging incident.'));
        }

        return $this->redirect(['action' => 'view', $id]);
    }

    /**
     * Notify other alert recipients that an incident was acknowledged
     *
     * @param \App\Model\Entity\Incident $incident The acknowledged incident
     * @param string $acknowledgedBy Who acknowledged it
     * @return void
     */
    protected function notifyAcknowledgement(\App\Model\Entity\Incident $incident, string $acknowledgedBy): void
    {
        try {
            $settingService = new SettingService();
            $siteName = $settingService->get('site_name', 'ISP Status');
            $siteUrl = $settingService->get('site_url', '');

            // Load monitor if not already loaded
            if (!$incident->monitor) {
                $incident = $this->Incidents->get($incident->id, [
                    'contain' => ['Monitors'],
                ]);
            }

            // Find alert rules for this monitor to get recipient list
            $alertRules = $this->Incidents->Monitors->AlertRules->find()
                ->where([
                    'monitor_id' => $incident->monitor_id,
                    'active' => true,
                    'channel' => 'email',
                ])
                ->all();

            foreach ($alertRules as $rule) {
                $recipients = json_decode($rule->recipients, true);
                if (!is_array($recipients)) {
                    continue;
                }

                foreach ($recipients as $email) {
                    try {
                        $mailer = new \Cake\Mailer\Mailer('default');
                        $mailer
                            ->setFrom(
                                $settingService->get('email_from', 'noreply@example.com'),
                                $settingService->get('email_from_name', $siteName)
                            )
                            ->setTo($email)
                            ->setSubject("[{$siteName}] Incidente Reconhecido - {$incident->monitor->name}")
                            ->setViewVars([
                                'monitor' => $incident->monitor,
                                'incident' => $incident,
                                'acknowledgedBy' => $acknowledgedBy,
                                'acknowledgedAt' => $incident->acknowledged_at
                                    ? $incident->acknowledged_at->format('d/m/Y H:i:s')
                                    : DateTime::now()->format('d/m/Y H:i:s'),
                                'acknowledgedVia' => $incident->acknowledged_via ?? 'web',
                                'siteName' => $siteName,
                            ])
                            ->viewBuilder()
                            ->setTemplate('incident_acknowledged')
                            ->setLayout('default');

                        $mailer->deliver();
                    } catch (\Exception $e) {
                        Log::error("Failed to send acknowledgement notification to {$email}: {$e->getMessage()}");
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to notify acknowledgement: {$e->getMessage()}");
        }
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

        // Incident acknowledged
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
                'icon' => '&#x2714;',
                'color' => 'info',
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
