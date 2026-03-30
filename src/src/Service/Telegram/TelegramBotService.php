<?php
declare(strict_types=1);

namespace App\Service\Telegram;

use Cake\Http\Client;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * TelegramBotService (C-04)
 *
 * Handles incoming Telegram bot commands for interactive monitoring.
 *
 * Supported commands:
 *   /status       — Show all monitors summary (up/down/degraded counts)
 *   /monitors     — List all monitors with current status
 *   /incidents    — List active incidents
 *   /ack <id>     — Acknowledge an incident by ID
 *   /check <name> — Show details for a specific monitor
 *   /pause <name> — Pause a monitor
 *   /resume <name>— Resume a paused monitor
 *   /help         — Show available commands
 */
class TelegramBotService
{
    use LocatorAwareTrait;

    protected Client $httpClient;
    protected string $botToken;

    public function __construct(string $botToken, ?Client $httpClient = null)
    {
        $this->botToken = $botToken;
        $this->httpClient = $httpClient ?? new Client();
    }

    /**
     * Process an incoming Telegram update (webhook payload).
     *
     * @param array $update The Telegram update object
     * @param int $organizationId Organization context
     * @return void
     */
    public function processUpdate(array $update, int $organizationId): void
    {
        $message = $update['message'] ?? $update['callback_query']['message'] ?? null;
        $text = $update['message']['text'] ?? $update['callback_query']['data'] ?? '';
        $chatId = (string)($message['chat']['id'] ?? '');

        if (empty($chatId) || empty($text)) {
            return;
        }

        $parts = preg_split('/\s+/', trim($text), 2);
        $command = strtolower($parts[0] ?? '');
        $args = $parts[1] ?? '';

        // Strip @botname from commands
        $command = preg_replace('/@.*$/', '', $command);

        try {
            $response = match ($command) {
                '/status' => $this->cmdStatus($organizationId),
                '/monitors' => $this->cmdMonitors($organizationId),
                '/incidents' => $this->cmdIncidents($organizationId),
                '/ack' => $this->cmdAcknowledge($args, $organizationId),
                '/check' => $this->cmdCheck($args, $organizationId),
                '/pause' => $this->cmdPause($args, $organizationId),
                '/resume' => $this->cmdResume($args, $organizationId),
                '/help', '/start' => $this->cmdHelp(),
                default => null,
            };

            if ($response !== null) {
                $this->sendMessage($chatId, $response);
            }
        } catch (\Exception $e) {
            Log::error("Telegram bot error: {$e->getMessage()}");
            $this->sendMessage($chatId, "\xE2\x9A\xA0\xEF\xB8\x8F Error: {$e->getMessage()}");
        }
    }

    /**
     * /status — Summary of all monitors.
     */
    protected function cmdStatus(int $orgId): string
    {
        $monitors = $this->fetchTable('Monitors')->find()
            ->where(['organization_id' => $orgId, 'active' => true])
            ->all();

        $total = $monitors->count();
        $up = $down = $degraded = $unknown = 0;

        foreach ($monitors as $m) {
            match ($m->status) {
                'up' => $up++,
                'down' => $down++,
                'degraded' => $degraded++,
                default => $unknown++,
            };
        }

        $incidents = $this->fetchTable('Incidents')->find()
            ->where([
                'organization_id' => $orgId,
                'status !=' => 'resolved',
            ])
            ->count();

        $lines = [
            "\xF0\x9F\x93\x8A <b>Status Overview</b>",
            '',
            "\xF0\x9F\x9F\xA2 Up: {$up}",
            "\xF0\x9F\x94\xB4 Down: {$down}",
        ];

        if ($degraded > 0) {
            $lines[] = "\xF0\x9F\x9F\xA1 Degraded: {$degraded}";
        }
        if ($unknown > 0) {
            $lines[] = "\xE2\x9A\xAA Unknown: {$unknown}";
        }

        $lines[] = '';
        $lines[] = "Total: {$total} monitors";

        if ($incidents > 0) {
            $lines[] = "\xE2\x9A\xA0\xEF\xB8\x8F {$incidents} active incident(s)";
        }

        return implode("\n", $lines);
    }

    /**
     * /monitors — List all monitors.
     */
    protected function cmdMonitors(int $orgId): string
    {
        $monitors = $this->fetchTable('Monitors')->find()
            ->where(['organization_id' => $orgId, 'active' => true])
            ->orderBy(['status' => 'ASC', 'name' => 'ASC'])
            ->limit(30)
            ->all();

        if ($monitors->isEmpty()) {
            return "No monitors configured.";
        }

        $lines = ["\xF0\x9F\x96\xA5 <b>Monitors</b>", ''];

        foreach ($monitors as $m) {
            $emoji = match ($m->status) {
                'up' => "\xF0\x9F\x9F\xA2",
                'down' => "\xF0\x9F\x94\xB4",
                'degraded' => "\xF0\x9F\x9F\xA1",
                default => "\xE2\x9A\xAA",
            };
            $lines[] = "{$emoji} <b>{$this->esc($m->name)}</b> — {$m->status} ({$m->type})";
        }

        return implode("\n", $lines);
    }

    /**
     * /incidents — List active incidents.
     */
    protected function cmdIncidents(int $orgId): string
    {
        $incidents = $this->fetchTable('Incidents')->find()
            ->where([
                'Incidents.organization_id' => $orgId,
                'Incidents.status !=' => 'resolved',
            ])
            ->contain(['Monitors' => ['fields' => ['id', 'name']]])
            ->orderBy(['Incidents.created' => 'DESC'])
            ->limit(10)
            ->all();

        if ($incidents->isEmpty()) {
            return "\xF0\x9F\x9F\xA2 No active incidents. All systems operational.";
        }

        $lines = ["\xE2\x9A\xA0\xEF\xB8\x8F <b>Active Incidents</b>", ''];

        foreach ($incidents as $inc) {
            $sevEmoji = match ($inc->severity) {
                'critical' => "\xF0\x9F\x94\xB4",
                'major' => "\xF0\x9F\x9F\xA0",
                'minor' => "\xF0\x9F\x9F\xA1",
                default => "\xE2\x9A\xAA",
            };
            $monitor = $inc->monitor->name ?? 'Unknown';
            $ackStatus = $inc->acknowledged_at ? ' \xE2\x9C\x85' : '';
            $lines[] = "{$sevEmoji} <b>#{$inc->id}</b> {$this->esc($inc->title)}{$ackStatus}";
            $lines[] = "   {$this->esc($monitor)} — {$inc->status}";
        }

        $lines[] = '';
        $lines[] = "Use /ack &lt;id&gt; to acknowledge an incident.";

        return implode("\n", $lines);
    }

    /**
     * /ack <id> — Acknowledge an incident.
     */
    protected function cmdAcknowledge(string $args, int $orgId): string
    {
        $id = (int)trim($args);
        if ($id <= 0) {
            return "Usage: /ack &lt;incident_id&gt;\nExample: /ack 42";
        }

        $table = $this->fetchTable('Incidents');

        try {
            $incident = $table->find()
                ->where(['Incidents.id' => $id, 'Incidents.organization_id' => $orgId])
                ->first();
        } catch (\Exception $e) {
            return "Incident #{$id} not found.";
        }

        if (!$incident) {
            return "Incident #{$id} not found.";
        }

        if ($incident->acknowledged_at) {
            return "Incident #{$id} is already acknowledged.";
        }

        if ($incident->status === 'resolved') {
            return "Incident #{$id} is already resolved.";
        }

        $incident->set('acknowledged_at', new \Cake\I18n\DateTime());
        $incident->set('acknowledged_via', 'telegram');

        if ($table->save($incident)) {
            return "\xE2\x9C\x85 Incident #{$id} acknowledged via Telegram.";
        }

        return "\xE2\x9D\x8C Failed to acknowledge incident #{$id}.";
    }

    /**
     * /check <name> — Show monitor details.
     */
    protected function cmdCheck(string $args, int $orgId): string
    {
        $name = trim($args);
        if (empty($name)) {
            return "Usage: /check &lt;monitor_name&gt;\nExample: /check My Website";
        }

        $escapedName = str_replace(['%', '_'], ['\\%', '\\_'], strtolower($name));
        $monitor = $this->fetchTable('Monitors')->find()
            ->where([
                'organization_id' => $orgId,
                'LOWER(name) LIKE' => '%' . $escapedName . '%',
            ])
            ->first();

        if (!$monitor) {
            return "No monitor found matching \"{$this->esc($name)}\".";
        }

        $emoji = match ($monitor->status) {
            'up' => "\xF0\x9F\x9F\xA2",
            'down' => "\xF0\x9F\x94\xB4",
            'degraded' => "\xF0\x9F\x9F\xA1",
            default => "\xE2\x9A\xAA",
        };

        $lastCheck = $monitor->last_check_at
            ? $monitor->last_check_at->format('Y-m-d H:i:s')
            : 'Never';

        $uptime = $monitor->uptime_percentage !== null
            ? number_format((float)$monitor->uptime_percentage, 2) . '%'
            : 'N/A';

        $lines = [
            "{$emoji} <b>{$this->esc($monitor->name)}</b>",
            '',
            "<b>Status:</b> {$monitor->status}",
            "<b>Type:</b> {$monitor->type}",
            "<b>Uptime (24h):</b> {$uptime}",
            "<b>Last Check:</b> {$lastCheck}",
            "<b>Interval:</b> {$monitor->check_interval}s",
            "<b>Active:</b> " . ($monitor->active ? 'Yes' : 'No'),
        ];

        return implode("\n", $lines);
    }

    /**
     * /pause <name> — Pause a monitor.
     */
    protected function cmdPause(string $args, int $orgId): string
    {
        return $this->toggleMonitor($args, $orgId, false);
    }

    /**
     * /resume <name> — Resume a monitor.
     */
    protected function cmdResume(string $args, int $orgId): string
    {
        return $this->toggleMonitor($args, $orgId, true);
    }

    /**
     * /help — Show available commands.
     */
    protected function cmdHelp(): string
    {
        return implode("\n", [
            "\xF0\x9F\xA4\x96 <b>ISP Status Bot Commands</b>",
            '',
            '/status — Overview (up/down/degraded counts)',
            '/monitors — List all active monitors',
            '/incidents — List active incidents',
            '/ack &lt;id&gt; — Acknowledge an incident',
            '/check &lt;name&gt; — Monitor details by name',
            '/pause &lt;name&gt; — Pause a monitor',
            '/resume &lt;name&gt; — Resume a paused monitor',
            '/help — Show this message',
        ]);
    }

    /**
     * Toggle monitor active state.
     */
    protected function toggleMonitor(string $args, int $orgId, bool $active): string
    {
        $name = trim($args);
        $action = $active ? 'resume' : 'pause';

        if (empty($name)) {
            return "Usage: /{$action} &lt;monitor_name&gt;";
        }

        $escapedName = str_replace(['%', '_'], ['\\%', '\\_'], strtolower($name));
        $table = $this->fetchTable('Monitors');
        $monitor = $table->find()
            ->where([
                'organization_id' => $orgId,
                'LOWER(name) LIKE' => '%' . $escapedName . '%',
            ])
            ->first();

        if (!$monitor) {
            return "No monitor found matching \"{$this->esc($name)}\".";
        }

        if ($monitor->active === $active) {
            $state = $active ? 'already active' : 'already paused';
            return "\xE2\x84\xB9\xEF\xB8\x8F <b>{$this->esc($monitor->name)}</b> is {$state}.";
        }

        $monitor->set('active', $active);
        if ($table->save($monitor)) {
            $emoji = $active ? "\xE2\x96\xB6\xEF\xB8\x8F" : "\xE2\x8F\xB8\xEF\xB8\x8F";
            $verb = $active ? 'resumed' : 'paused';
            return "{$emoji} <b>{$this->esc($monitor->name)}</b> has been {$verb}.";
        }

        return "\xE2\x9D\x8C Failed to {$action} monitor.";
    }

    /**
     * Send a message to a Telegram chat.
     */
    public function sendMessage(string $chatId, string $text, ?array $replyMarkup = null): bool
    {
        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";

        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ];

        if ($replyMarkup) {
            $data['reply_markup'] = json_encode($replyMarkup);
        }

        try {
            $response = $this->httpClient->post($url, json_encode($data), ['type' => 'application/json']);
            return $response->isOk();
        } catch (\Exception $e) {
            Log::error("Telegram send failed: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Escape HTML for Telegram messages.
     */
    protected function esc(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
