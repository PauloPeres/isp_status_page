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
            if ($password !== $statusPage->password) {
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
}
