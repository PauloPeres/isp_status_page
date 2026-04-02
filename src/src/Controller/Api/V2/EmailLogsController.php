<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

use App\Model\Entity\AlertRule;

/**
 * EmailLogsController — API v2
 *
 * Exposes alert_logs as "email logs" for the frontend.
 * Maps alert_log fields to the shape the frontend expects.
 */
class EmailLogsController extends AppController
{
    /**
     * GET /api/v2/email-logs
     *
     * List email/alert logs with search, filtering, and pagination.
     *
     * Query params:
     *   - search: filter by recipient
     *   - status: filter by status (sent, failed, queued)
     *   - channel: filter by channel (email, whatsapp, telegram, sms, phone)
     *   - page: page number (default 1)
     *   - limit: items per page (default 25, max 100)
     *
     * @return void
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('AlertLogs');
        $query = $table->find()
            ->contain(['Monitors', 'Incidents'])
            ->orderBy(['AlertLogs.created' => 'DESC']);

        // Search by recipient
        $search = $this->request->getQuery('search');
        if ($search) {
            $search = str_replace(['%', '_'], ['\\%', '\\_'], $search);
            $query->where([
                'AlertLogs.recipient LIKE' => '%' . $search . '%',
            ]);
        }

        // Filter by status
        $status = $this->request->getQuery('status');
        if ($status) {
            $query->where(['AlertLogs.status' => $status]);
        }

        // Filter by channel
        $channel = $this->request->getQuery('channel');
        if ($channel) {
            $query->where(['AlertLogs.channel' => $channel]);
        }

        $page = max(1, (int)$this->request->getQuery('page', 1));
        $limit = min((int)$this->request->getQuery('limit', 25), 100);

        $total = $query->count();
        $alertLogs = $query->limit($limit)->offset(($page - 1) * $limit)->toArray();

        // Map alert_logs to the shape the frontend expects
        $emailLogs = array_map(function ($log) {
            $monitorName = $log->monitor->name ?? 'Unknown Monitor';
            $incidentTitle = $log->incident->title ?? null;

            // Build a subject line from monitor/incident info
            $subject = $incidentTitle
                ? sprintf('Alert: %s — %s', $monitorName, $incidentTitle)
                : sprintf('Alert: %s', $monitorName);

            return [
                'id' => $log->id,
                'to_email' => $log->recipient,
                'subject' => $subject,
                'status' => $log->status,
                'channel' => $log->channel,
                'error_message' => $log->error_message,
                'sent_at' => $log->sent_at ? $log->sent_at->toIso8601String() : null,
                'created' => $log->created ? $log->created->toIso8601String() : null,
                'monitor_name' => $monitorName,
            ];
        }, $alertLogs);

        $this->success([
            'email_logs' => $emailLogs,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int)ceil($total / $limit),
            ],
        ]);
    }
}
