<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

use Cake\I18n\DateTime;

/**
 * EventsController
 *
 * Provides a Server-Sent Events (SSE) stream endpoint for real-time
 * updates of monitor status changes and incident creation.
 */
class EventsController extends AppController
{
    /**
     * SSE stream endpoint.
     *
     * GET /api/v2/events/stream
     *
     * Sends events: monitor_status, incident_created, heartbeat
     *
     * @return void
     */
    public function stream(): void
    {
        // Disable CakePHP view rendering
        $this->autoRender = false;

        // Send SSE headers immediately
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        // Flush initial connection with retry interval
        echo "retry: 5000\n\n";
        if (ob_get_level()) {
            ob_flush();
        }
        flush();

        $orgId = $this->currentOrgId;
        $lastCheck = DateTime::now();
        $iterations = 0;
        $maxIterations = 60; // 5 minutes max (5s interval x 60)

        while ($iterations < $maxIterations && !connection_aborted()) {
            $events = $this->getNewEvents($orgId, $lastCheck);

            foreach ($events as $event) {
                echo "event: {$event['type']}\n";
                echo 'data: ' . json_encode($event['data']) . "\n\n";
            }

            // Always send a heartbeat so connection stays alive
            echo "event: heartbeat\n";
            echo 'data: ' . json_encode(['time' => DateTime::now()->format('c')]) . "\n\n";

            if (ob_get_level()) {
                ob_flush();
            }
            flush();

            $lastCheck = DateTime::now();
            $iterations++;
            sleep(5);
        }

        exit;
    }

    /**
     * Fetch new events since the given timestamp.
     *
     * @param int $orgId The organization ID to filter by.
     * @param \Cake\I18n\DateTime $since Only return events after this time.
     * @return array<array{type: string, data: array}>
     */
    private function getNewEvents(int $orgId, DateTime $since): array
    {
        $events = [];

        // Require a valid org ID — refuse to serve events without tenant context
        if ($orgId <= 0) {
            return $events;
        }

        // Check for new monitor status changes (uses normal tenant scoping)
        $monitors = $this->fetchTable('Monitors')->find()
            ->select(['id', 'name', 'status', 'modified'])
            ->where([
                'Monitors.organization_id' => $orgId,
                'modified >' => $since->format('Y-m-d H:i:s'),
            ])
            ->all();

        foreach ($monitors as $monitor) {
            $events[] = [
                'type' => 'monitor_status',
                'data' => [
                    'id' => $monitor->id,
                    'name' => $monitor->name,
                    'status' => $monitor->status,
                ],
            ];
        }

        // Check for new incidents (uses normal tenant scoping)
        $incidents = $this->fetchTable('Incidents')->find()
            ->select(['id', 'title', 'severity', 'status', 'created'])
            ->where([
                'Incidents.organization_id' => $orgId,
                'created >' => $since->format('Y-m-d H:i:s'),
            ])
            ->all();

        foreach ($incidents as $incident) {
            $events[] = [
                'type' => 'incident_created',
                'data' => [
                    'id' => $incident->id,
                    'title' => $incident->title,
                    'severity' => $incident->severity,
                    'status' => $incident->status,
                ],
            ];
        }

        return $events;
    }
}
