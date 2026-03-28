<?php
/**
 * Super Admin — Security Audit Logs (TASK-AUTH-018)
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\SecurityAuditLog> $logs
 * @var array $eventTypes
 * @var string|null $eventType
 */
$this->assign('title', __('Security Logs'));

$badgeColors = [
    'login_success' => '#22c55e',
    'login_failed' => '#ef4444',
    'login_locked' => '#dc2626',
    'password_changed' => '#3b82f6',
    'password_reset_requested' => '#f59e0b',
    'password_reset_completed' => '#3b82f6',
    'registration' => '#8b5cf6',
    'email_verified' => '#22c55e',
    'oauth_login' => '#6366f1',
    'oauth_link' => '#6366f1',
    'api_key_created' => '#0ea5e9',
    'api_key_revoked' => '#f97316',
    'impersonation_start' => '#dc2626',
    'impersonation_stop' => '#f59e0b',
    'role_changed' => '#e11d48',
    'user_created' => '#22c55e',
    'user_deleted' => '#ef4444',
];
?>

<div class="dashboard-header">
    <h1><?= __('Security Audit Logs') ?></h1>
    <p><?= __('Track all security-relevant events across the platform') ?></p>
</div>

<!-- Filter Bar -->
<div class="table-card" style="margin-bottom: 24px; padding: 16px;">
    <form method="get" action="<?= $this->Url->build(['prefix' => 'SuperAdmin', 'controller' => 'SecurityLogs', 'action' => 'index']) ?>" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
        <label for="event_type" style="font-weight: 600;"><?= __('Event Type') ?>:</label>
        <select name="event_type" id="event_type" style="padding: 8px 12px; border-radius: 6px; border: 1px solid #d1d5db; font-size: 14px;">
            <option value=""><?= __('All Events') ?></option>
            <?php foreach ($eventTypes as $type): ?>
                <option value="<?= h($type) ?>" <?= ($eventType === $type) ? 'selected' : '' ?>><?= h($type) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary" style="padding: 8px 16px; border-radius: 6px; background: #1E88E5; color: #fff; border: none; cursor: pointer; font-size: 14px;"><?= __('Filter') ?></button>
        <?php if ($eventType): ?>
            <a href="<?= $this->Url->build(['prefix' => 'SuperAdmin', 'controller' => 'SecurityLogs', 'action' => 'index']) ?>" style="padding: 8px 16px; font-size: 14px; color: #6b7280; text-decoration: none;"><?= __('Clear') ?></a>
        <?php endif; ?>
    </form>
</div>

<!-- Logs Table -->
<div class="table-card">
    <?php if (count($logs) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th><?= __('Time') ?></th>
                    <th><?= __('Event Type') ?></th>
                    <th><?= __('User') ?></th>
                    <th><?= __('IP Address') ?></th>
                    <th><?= __('Details') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td style="white-space: nowrap;">
                            <?= $log->created ? h($log->created->format('Y-m-d H:i:s')) : '-' ?>
                        </td>
                        <td>
                            <?php
                            $color = $badgeColors[$log->event_type] ?? '#6b7280';
                            ?>
                            <span style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; color: #fff; background: <?= $color ?>;">
                                <?= h($log->event_type) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($log->user): ?>
                                <?= h($log->user->username) ?>
                                <br><small style="color: #6b7280;"><?= h($log->user->email) ?></small>
                            <?php else: ?>
                                <span style="color: #9ca3af;">-</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-family: monospace; font-size: 13px;"><?= h($log->ip_address) ?></td>
                        <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 13px;">
                            <?php if ($log->details): ?>
                                <code title="<?= h($log->details) ?>"><?= h($log->details) ?></code>
                            <?php else: ?>
                                <span style="color: #9ca3af;">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="paginator" style="margin-top: 16px; text-align: center;">
            <?= $this->Paginator->prev('< ' . __('Previous')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__('Next') . ' >') ?>
            <p style="margin-top: 8px; color: #6b7280; font-size: 13px;">
                <?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?>
            </p>
        </div>
    <?php else: ?>
        <div class="empty-state" style="text-align: center; padding: 40px; color: #6b7280;">
            <?= __('No security logs found.') ?>
        </div>
    <?php endif; ?>
</div>
