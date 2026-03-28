<?php
/**
 * Scheduled Reports Index (P4-010)
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\ScheduledReport> $scheduledReports
 */
?>

<div class="content-header">
    <h1><?= __('Scheduled Reports') ?></h1>
    <div class="content-header-actions">
        <?= $this->Html->link(
            __('+ New Report'),
            ['action' => 'add'],
            ['class' => 'btn btn-primary']
        ) ?>
    </div>
</div>

<div class="card">
    <p><?= __('Configure automated email reports to receive weekly or monthly uptime summaries.') ?></p>

    <?php if (count($scheduledReports) === 0): ?>
        <div style="text-align: center; padding: 40px 20px; color: #888;">
            <p style="font-size: 18px; margin-bottom: 8px;"><?= __('No scheduled reports yet') ?></p>
            <p><?= __('Create a new report to start receiving automated uptime summaries via email.') ?></p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th><?= __('Name') ?></th>
                        <th><?= __('Frequency') ?></th>
                        <th><?= __('Recipients') ?></th>
                        <th><?= __('Last Sent') ?></th>
                        <th><?= __('Next Send') ?></th>
                        <th><?= __('Active') ?></th>
                        <th><?= __('Actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($scheduledReports as $report): ?>
                    <tr>
                        <td>
                            <strong><?= h($report->name) ?></strong>
                        </td>
                        <td>
                            <span class="badge <?= $report->frequency === 'weekly' ? 'badge-info' : 'badge-primary' ?>">
                                <?= $report->getFrequencyLabel() ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            $recipients = $report->getRecipientsArray();
                            $count = count($recipients);
                            if ($count > 0) {
                                echo h($recipients[0]);
                                if ($count > 1) {
                                    echo ' <span style="color: #888;">+' . ($count - 1) . ' ' . __('more') . '</span>';
                                }
                            } else {
                                echo '<span style="color: #999;">' . __('None') . '</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <?php if ($report->last_sent_at): ?>
                                <span title="<?= h($report->last_sent_at->format('Y-m-d H:i:s')) ?>">
                                    <?= $report->last_sent_at->timeAgoInWords() ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #999;"><?= __('Never') ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($report->next_send_at): ?>
                                <span title="<?= h($report->next_send_at->format('Y-m-d H:i:s')) ?>">
                                    <?= $report->next_send_at->format('M j, Y g:ia') ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #999;">--</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($report->active): ?>
                                <span class="badge badge-success"><?= __('Active') ?></span>
                            <?php else: ?>
                                <span class="badge badge-secondary"><?= __('Inactive') ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                <?= $this->Html->link(
                                    __('Preview'),
                                    ['action' => 'preview', $report->id],
                                    ['class' => 'btn btn-sm btn-secondary']
                                ) ?>
                                <?= $this->Html->link(
                                    __('Edit'),
                                    ['action' => 'edit', $report->id],
                                    ['class' => 'btn btn-sm btn-primary']
                                ) ?>
                                <?= $this->Form->postLink(
                                    __('Send Now'),
                                    ['action' => 'sendNow', $report->id],
                                    [
                                        'class' => 'btn btn-sm btn-success',
                                        'confirm' => __('Send this report now to all recipients?'),
                                    ]
                                ) ?>
                                <?= $this->Form->postLink(
                                    __('Delete'),
                                    ['action' => 'delete', $report->id],
                                    [
                                        'class' => 'btn btn-sm btn-danger',
                                        'confirm' => __('Are you sure? This action cannot be undone.'),
                                    ]
                                ) ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
