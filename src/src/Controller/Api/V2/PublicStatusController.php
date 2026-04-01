<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

/**
 * PublicStatusController
 *
 * Unauthenticated endpoint for live status page data.
 * Used by status-page-live.js for polling updates.
 */
class PublicStatusController extends AppController
{
    /**
     * GET /api/v2/public/status/{slug}
     *
     * Returns current status data for a public status page.
     * No JWT required — this is a public endpoint.
     *
     * @param string $slug Status page slug.
     * @return void
     */
    public function status(string $slug): void
    {
        $this->request->allowMethod(['get']);

        // Rate limiting
        $ip = $this->request->clientIp();
        $cacheKey = 'public_api_rate_' . md5($ip);
        $requests = (int)\Cake\Cache\Cache::read($cacheKey, '_cake_core_') ?: 0;
        if ($requests > 120) {
            $this->error('Rate limit exceeded', 429);
            return;
        }
        \Cake\Cache\Cache::write($cacheKey, $requests + 1, '_cake_core_');

        $statusPagesTable = $this->fetchTable('StatusPages');
        $statusPage = $statusPagesTable->find()
            ->where(['slug' => $slug, 'active' => true])
            ->first();

        if (!$statusPage) {
            $this->error('Status page not found', 404);

            return;
        }

        // Password protection: require password header
        if ($statusPage->isPasswordProtected()) {
            $password = $this->request->getHeaderLine('X-Status-Password');
            if (!(new \Authentication\PasswordHasher\DefaultPasswordHasher())->check($password, $statusPage->password)) {
                $this->error('Password required', 401);

                return;
            }
        }

        // Load monitors
        $monitorIds = $statusPage->getMonitorIds();
        $monitors = [];
        if (!empty($monitorIds)) {
            $monitorsTable = $this->fetchTable('Monitors');
            $monitorEntities = $monitorsTable->find()
                ->where(['id IN' => $monitorIds, 'active' => true])
                ->orderBy(['name' => 'ASC'])
                ->all()
                ->toArray();

            foreach ($monitorEntities as $monitor) {
                $monitors[] = [
                    'id' => $monitor->id,
                    'name' => $monitor->name,
                    'status' => $monitor->status ?? 'unknown',
                    'uptime_percentage' => $monitor->uptime_percentage ?? 0,
                ];
            }
        }

        // Calculate overall status
        $allUp = true;
        $anyDown = false;
        foreach ($monitors as $m) {
            if ($m['status'] === 'down') {
                $anyDown = true;
                $allUp = false;
            } elseif ($m['status'] !== 'up') {
                $allUp = false;
            }
        }

        if (empty($monitors)) {
            $overallStatus = 'unknown';
        } elseif ($allUp) {
            $overallStatus = 'up';
        } elseif ($anyDown) {
            $overallStatus = 'down';
        } else {
            $overallStatus = 'degraded';
        }

        // Load recent incidents
        $incidents = [];
        if ($statusPage->show_incident_history && !empty($monitorIds)) {
            $incidentsTable = $this->fetchTable('Incidents');
            $incidentEntities = $incidentsTable->find()
                ->where(['monitor_id IN' => $monitorIds])
                ->orderBy(['created' => 'DESC'])
                ->limit(10)
                ->all()
                ->toArray();

            foreach ($incidentEntities as $incident) {
                $incidents[] = [
                    'id' => $incident->id,
                    'title' => $incident->title,
                    'status' => $incident->status,
                    'severity' => $incident->severity ?? 'minor',
                    'created' => $incident->created->format('c'),
                ];
            }
        }

        $this->success([
            'overall_status' => $overallStatus,
            'monitors' => $monitors,
            'incidents' => $incidents,
        ]);
    }

    /**
     * POST /api/v2/public/status/{slug}/subscribe
     *
     * Subscribe an email to incident updates for this status page.
     */
    public function subscribe(string $slug): void
    {
        $this->request->allowMethod(['post']);

        // Rate limiting
        $ip = $this->request->clientIp();
        $cacheKey = 'public_api_rate_' . md5($ip);
        $requests = (int)\Cake\Cache\Cache::read($cacheKey, '_cake_core_') ?: 0;
        if ($requests > 120) {
            $this->error('Rate limit exceeded', 429);
            return;
        }
        \Cake\Cache\Cache::write($cacheKey, $requests + 1, '_cake_core_');

        $statusPagesTable = $this->fetchTable('StatusPages');
        $statusPage = $statusPagesTable->find()
            ->where(['StatusPages.slug' => $slug, 'StatusPages.active' => true])
            ->first();

        if (!$statusPage) {
            $this->error('Status page not found', 404);
            return;
        }

        $email = $this->request->getData('email');
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Valid email is required', 400);
            return;
        }

        try {
            $subscribersTable = $this->fetchTable('Subscribers');

            // Check if already subscribed
            $existing = $subscribersTable->find()
                ->where([
                    'Subscribers.email' => $email,
                    'Subscribers.organization_id' => $statusPage->organization_id,
                ])
                ->first();

            if ($existing) {
                $this->success(['message' => 'Subscribed! You will receive incident updates.']);
                return;
            }

            $subscriber = $subscribersTable->newEntity([
                'organization_id' => $statusPage->organization_id,
                'email' => $email,
                'verified' => false,
                'active' => true,
            ]);

            if ($subscribersTable->save($subscriber)) {
                $this->success(['message' => 'Subscribed successfully! You will receive incident updates.'], 201);
            } else {
                $this->error('Failed to subscribe. Please try again.', 422);
            }
        } catch (\Exception $e) {
            $this->error('Failed to subscribe. Please try again.', 500);
        }
    }
}
