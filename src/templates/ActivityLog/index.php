<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\ORM\ResultSet $logs
 * @var string|null $eventType
 * @var array $eventTypes
 */
$this->assign('title', __('Activity Log'));

/**
 * Get badge class for event type
 */
function getEventBadgeClass(string $eventType): string
{
    return match (true) {
        str_contains($eventType, 'login_success') => 'badge-success',
        str_contains($eventType, 'login_fail') => 'badge-danger',
        str_contains($eventType, 'logout') => 'badge-secondary',
        str_contains($eventType, 'password') => 'badge-warning',
        str_contains($eventType, '2fa') => 'badge-info',
        str_contains($eventType, 'delete') => 'badge-danger',
        str_contains($eventType, 'create') => 'badge-success',
        str_contains($eventType, 'update') => 'badge-primary',
        str_contains($eventType, 'invite') => 'badge-info',
        str_contains($eventType, 'api_key') => 'badge-warning',
        default => 'badge-secondary',
    };
}

/**
 * Format event type for display
 */
function formatEventType(string $eventType): string
{
    return ucwords(str_replace('_', ' ', $eventType));
}
?>

<style>
    .activity-log-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 16px;
    }

    .activity-log-header h2 {
        margin: 0;
        font-size: 24px;
        font-weight: 700;
    }

    .filter-form {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .filter-form select {
        padding: 8px 12px;
        border: 1px solid #d0d0d0;
        border-radius: 6px;
        font-size: 14px;
        min-width: 200px;
    }

    .filter-form .btn-filter {
        padding: 8px 16px;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        background: #3b82f6;
        color: white;
    }

    .filter-form .btn-filter:hover {
        background: #2563eb;
    }

    .filter-form .btn-clear {
        padding: 8px 16px;
        border: 1px solid #d0d0d0;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        background: white;
        color: #333;
    }

    .filter-form .btn-clear:hover {
        background: #f3f4f6;
    }

    .log-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        overflow: hidden;
    }

    .log-table th {
        background: #f8f9fa;
        padding: 12px 16px;
        text-align: left;
        font-size: 13px;
        font-weight: 600;
        color: #555;
        border-bottom: 2px solid #e0e0e0;
        white-space: nowrap;
    }

    .log-table td {
        padding: 12px 16px;
        font-size: 14px;
        color: #333;
        border-bottom: 1px solid #f0f0f0;
        vertical-align: top;
    }

    .log-table tr:last-child td {
        border-bottom: none;
    }

    .log-table tr:hover {
        background: #f8f9fa;
    }

    .badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
    }

    .badge-success { background: #dcfce7; color: #166534; }
    .badge-danger { background: #fee2e2; color: #991b1b; }
    .badge-warning { background: #fef9c3; color: #854d0e; }
    .badge-info { background: #dbeafe; color: #1e40af; }
    .badge-primary { background: #e0e7ff; color: #3730a3; }
    .badge-secondary { background: #f3f4f6; color: #374151; }

    .details-cell {
        max-width: 300px;
        word-break: break-word;
        font-size: 13px;
        color: #666;
    }

    .ip-cell {
        font-family: monospace;
        font-size: 13px;
        color: #666;
    }

    .time-cell {
        white-space: nowrap;
        font-size: 13px;
        color: #666;
    }

    .user-cell {
        white-space: nowrap;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #999;
    }

    .empty-state p {
        font-size: 16px;
        margin: 0;
    }

    .pagination-wrapper {
        display: flex;
        justify-content: center;
        padding: 20px 0;
    }

    .pagination-wrapper .paginator {
        display: flex;
        gap: 4px;
        align-items: center;
    }

    .pagination-wrapper a,
    .pagination-wrapper span {
        display: inline-block;
        padding: 8px 14px;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        font-size: 14px;
        text-decoration: none;
        color: #333;
    }

    .pagination-wrapper a:hover {
        background: #f3f4f6;
    }

    .pagination-wrapper .current {
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
    }

    .pagination-wrapper .disabled {
        color: #ccc;
        pointer-events: none;
    }

    @media (max-width: 768px) {
        .activity-log-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .filter-form {
            width: 100%;
        }

        .filter-form select {
            flex: 1;
            min-width: auto;
        }

        .log-table {
            display: block;
            overflow-x: auto;
        }
    }
</style>

<div class="activity-log-header">
    <h2><?= __('Activity Log') ?></h2>

    <form class="filter-form" method="get">
        <select name="event_type">
            <option value=""><?= __('All Events') ?></option>
            <?php if (!empty($eventTypes)): ?>
                <?php foreach ($eventTypes as $type): ?>
                    <option value="<?= h($type) ?>" <?= ($eventType === $type) ? 'selected' : '' ?>>
                        <?= h(formatEventType($type)) ?>
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        <button type="submit" class="btn-filter"><?= __('Filter') ?></button>
        <?php if ($eventType): ?>
            <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn-clear"><?= __('Clear') ?></a>
        <?php endif; ?>
    </form>
</div>

<?php if ($logs->count() > 0): ?>
    <table class="log-table">
        <thead>
            <tr>
                <th><?= __('Time') ?></th>
                <th><?= __('Event') ?></th>
                <th><?= __('User') ?></th>
                <th><?= __('IP Address') ?></th>
                <th><?= __('Details') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td class="time-cell">
                        <?= h($log->created ? $log->created->format('Y-m-d H:i:s') : '-') ?>
                    </td>
                    <td>
                        <span class="badge <?= getEventBadgeClass($log->event_type) ?>">
                            <?= h(formatEventType($log->event_type)) ?>
                        </span>
                    </td>
                    <td class="user-cell">
                        <?php if (!empty($log->user)): ?>
                            <?= h($log->user->name ?? $log->user->email ?? '-') ?>
                        <?php else: ?>
                            <span style="color: #999;"><?= __('System') ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="ip-cell">
                        <?= h($log->ip_address ?? '-') ?>
                    </td>
                    <td class="details-cell">
                        <?php
                        $details = $log->details;
                        if ($details) {
                            $decoded = json_decode($details, true);
                            if (is_array($decoded)) {
                                $parts = [];
                                foreach ($decoded as $key => $value) {
                                    if (is_string($value) || is_numeric($value)) {
                                        $parts[] = h($key) . ': ' . h((string)$value);
                                    }
                                }
                                echo implode(', ', $parts) ?: h($details);
                            } else {
                                echo h($details);
                            }
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="pagination-wrapper">
        <div class="paginator">
            <?= $this->Paginator->prev('< ' . __('Previous')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__('Next') . ' >') ?>
        </div>
    </div>

    <p style="text-align: center; color: #999; font-size: 13px; margin-top: 8px;">
        <?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?>
    </p>
<?php else: ?>
    <div class="empty-state">
        <p><?= __('No activity log entries found.') ?></p>
        <?php if ($eventType): ?>
            <p style="margin-top: 12px;">
                <a href="<?= $this->Url->build(['action' => 'index']) ?>"><?= __('Clear filter to see all events') ?></a>
            </p>
        <?php endif; ?>
    </div>
<?php endif; ?>
