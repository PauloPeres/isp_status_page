<?php
/**
 * Scheduled Report HTML Email Template (P4-010)
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ScheduledReport $report
 * @var array $data
 * @var string $orgName
 * @var string $periodLabel
 * @var string $siteName
 * @var string $manageUrl
 */

$summary = $data['summary'] ?? [];
$monitors = $data['monitors'] ?? [];
$slaStatus = $data['sla_status'] ?? [];
$period = $data['period'] ?? [];
$frequencyLabel = ($period['frequency'] ?? 'weekly') === 'monthly' ? __('Monthly') : __('Weekly');
?>
<div style="font-family: Arial, Helvetica, sans-serif; max-width: 700px; margin: 0 auto; background-color: #f5f5f5; padding: 20px;">

    <!-- Header -->
    <div style="background-color: #1E88E5; color: #ffffff; padding: 24px 30px; text-align: center; border-radius: 8px 8px 0 0;">
        <h1 style="margin: 0; font-size: 22px; font-weight: 600;"><?= h($frequencyLabel) ?> <?= __('Uptime Report') ?></h1>
        <p style="margin: 8px 0 0 0; font-size: 14px; opacity: 0.9;"><?= h($orgName) ?> &mdash; <?= h($periodLabel) ?></p>
    </div>

    <div style="background-color: #ffffff; padding: 30px; border: 1px solid #e0e0e0; border-top: none;">

        <!-- Summary Cards -->
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 24px;">
            <tr>
                <td style="width: 25%; text-align: center; padding: 16px 8px; background-color: #f8f9fa; border-radius: 6px;">
                    <div style="font-size: 28px; font-weight: bold; color: #1E88E5;"><?= (int)($summary['total_monitors'] ?? 0) ?></div>
                    <div style="font-size: 12px; color: #666; margin-top: 4px;"><?= __('Monitors') ?></div>
                </td>
                <td style="width: 25%; text-align: center; padding: 16px 8px; background-color: #f8f9fa; border-radius: 6px;">
                    <div style="font-size: 28px; font-weight: bold; color: <?= ($summary['avg_uptime'] ?? 0) >= 99.9 ? '#43A047' : (($summary['avg_uptime'] ?? 0) >= 95 ? '#FDD835' : '#E53935') ?>;">
                        <?= number_format($summary['avg_uptime'] ?? 0, 2) ?>%
                    </div>
                    <div style="font-size: 12px; color: #666; margin-top: 4px;"><?= __('Avg Uptime') ?></div>
                </td>
                <td style="width: 25%; text-align: center; padding: 16px 8px; background-color: #f8f9fa; border-radius: 6px;">
                    <div style="font-size: 28px; font-weight: bold; color: #1E88E5;"><?= number_format($summary['avg_response_time'] ?? 0, 0) ?> ms</div>
                    <div style="font-size: 12px; color: #666; margin-top: 4px;"><?= __('Avg Response') ?></div>
                </td>
                <td style="width: 25%; text-align: center; padding: 16px 8px; background-color: #f8f9fa; border-radius: 6px;">
                    <div style="font-size: 28px; font-weight: bold; color: <?= ($summary['total_incidents'] ?? 0) > 0 ? '#E53935' : '#43A047' ?>;"><?= (int)($summary['total_incidents'] ?? 0) ?></div>
                    <div style="font-size: 12px; color: #666; margin-top: 4px;"><?= __('Incidents') ?></div>
                </td>
            </tr>
        </table>

        <!-- Per-Monitor Table -->
        <?php if (!empty($monitors)): ?>
        <h2 style="font-size: 16px; color: #333; margin: 24px 0 12px 0; border-bottom: 2px solid #1E88E5; padding-bottom: 8px;">
            <?= __('Monitor Details') ?>
        </h2>
        <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
            <thead>
                <tr style="background-color: #f8f9fa;">
                    <th style="padding: 10px 8px; text-align: left; border-bottom: 2px solid #dee2e6; color: #555;"><?= __('Monitor') ?></th>
                    <?php if ($report->include_uptime): ?>
                    <th style="padding: 10px 8px; text-align: center; border-bottom: 2px solid #dee2e6; color: #555;"><?= __('Uptime') ?></th>
                    <?php endif; ?>
                    <?php if ($report->include_response_time): ?>
                    <th style="padding: 10px 8px; text-align: center; border-bottom: 2px solid #dee2e6; color: #555;"><?= __('Avg Response') ?></th>
                    <?php endif; ?>
                    <?php if ($report->include_incidents): ?>
                    <th style="padding: 10px 8px; text-align: center; border-bottom: 2px solid #dee2e6; color: #555;"><?= __('Incidents') ?></th>
                    <?php endif; ?>
                    <th style="padding: 10px 8px; text-align: center; border-bottom: 2px solid #dee2e6; color: #555;"><?= __('Status') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($monitors as $i => $mon): ?>
                <tr style="<?= $i % 2 === 1 ? 'background-color: #fafafa;' : '' ?>">
                    <td style="padding: 10px 8px; border-bottom: 1px solid #eee; font-weight: 500; color: #333;">
                        <?= h($mon['name']) ?>
                    </td>
                    <?php if ($report->include_uptime): ?>
                    <td style="padding: 10px 8px; border-bottom: 1px solid #eee; text-align: center;">
                        <?php if ($mon['uptime'] !== null): ?>
                            <span style="color: <?= $mon['uptime'] >= 99.9 ? '#43A047' : ($mon['uptime'] >= 95 ? '#F9A825' : '#E53935') ?>; font-weight: bold;">
                                <?= number_format($mon['uptime'], 2) ?>%
                            </span>
                        <?php else: ?>
                            <span style="color: #999;">--</span>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                    <?php if ($report->include_response_time): ?>
                    <td style="padding: 10px 8px; border-bottom: 1px solid #eee; text-align: center; color: #555;">
                        <?php if ($mon['avg_response'] !== null): ?>
                            <?= number_format($mon['avg_response'], 0) ?> ms
                        <?php else: ?>
                            <span style="color: #999;">--</span>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                    <?php if ($report->include_incidents): ?>
                    <td style="padding: 10px 8px; border-bottom: 1px solid #eee; text-align: center;">
                        <?php if ($mon['incidents'] > 0): ?>
                            <span style="color: #E53935; font-weight: bold;"><?= (int)$mon['incidents'] ?></span>
                        <?php else: ?>
                            <span style="color: #43A047;">0</span>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                    <td style="padding: 10px 8px; border-bottom: 1px solid #eee; text-align: center;">
                        <?php
                        $statusColors = [
                            'operational' => ['bg' => '#E8F5E9', 'text' => '#2E7D32', 'label' => __('Operational')],
                            'degraded' => ['bg' => '#FFF8E1', 'text' => '#F57F17', 'label' => __('Degraded')],
                            'down' => ['bg' => '#FFEBEE', 'text' => '#C62828', 'label' => __('Down')],
                            'unknown' => ['bg' => '#F5F5F5', 'text' => '#757575', 'label' => __('Unknown')],
                        ];
                        $sc = $statusColors[$mon['status']] ?? $statusColors['unknown'];
                        ?>
                        <span style="display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; background-color: <?= $sc['bg'] ?>; color: <?= $sc['text'] ?>;">
                            <?= $sc['label'] ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <!-- SLA Section -->
        <?php if ($report->include_sla && !empty($slaStatus)): ?>
        <h2 style="font-size: 16px; color: #333; margin: 28px 0 12px 0; border-bottom: 2px solid #1E88E5; padding-bottom: 8px;">
            <?= __('SLA Status') ?>
        </h2>
        <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
            <thead>
                <tr style="background-color: #f8f9fa;">
                    <th style="padding: 10px 8px; text-align: left; border-bottom: 2px solid #dee2e6; color: #555;"><?= __('SLA') ?></th>
                    <th style="padding: 10px 8px; text-align: left; border-bottom: 2px solid #dee2e6; color: #555;"><?= __('Monitor') ?></th>
                    <th style="padding: 10px 8px; text-align: center; border-bottom: 2px solid #dee2e6; color: #555;"><?= __('Target') ?></th>
                    <th style="padding: 10px 8px; text-align: center; border-bottom: 2px solid #dee2e6; color: #555;"><?= __('Actual') ?></th>
                    <th style="padding: 10px 8px; text-align: center; border-bottom: 2px solid #dee2e6; color: #555;"><?= __('Status') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($slaStatus as $sla): ?>
                <tr>
                    <td style="padding: 10px 8px; border-bottom: 1px solid #eee; font-weight: 500;"><?= h($sla['name']) ?></td>
                    <td style="padding: 10px 8px; border-bottom: 1px solid #eee; color: #555;"><?= h($sla['monitor_name']) ?></td>
                    <td style="padding: 10px 8px; border-bottom: 1px solid #eee; text-align: center;"><?= number_format($sla['target'], 2) ?>%</td>
                    <td style="padding: 10px 8px; border-bottom: 1px solid #eee; text-align: center; font-weight: bold; color: <?= $sla['actual'] >= $sla['target'] ? '#43A047' : '#E53935' ?>;">
                        <?= number_format($sla['actual'], 2) ?>%
                    </td>
                    <td style="padding: 10px 8px; border-bottom: 1px solid #eee; text-align: center;">
                        <?php
                        $slaColors = [
                            'compliant' => ['bg' => '#E8F5E9', 'text' => '#2E7D32', 'label' => __('Compliant')],
                            'warning' => ['bg' => '#FFF8E1', 'text' => '#F57F17', 'label' => __('Warning')],
                            'breached' => ['bg' => '#FFEBEE', 'text' => '#C62828', 'label' => __('Breached')],
                        ];
                        $slaSc = $slaColors[$sla['status']] ?? ['bg' => '#F5F5F5', 'text' => '#757575', 'label' => ucfirst($sla['status'])];
                        ?>
                        <span style="display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; background-color: <?= $slaSc['bg'] ?>; color: <?= $slaSc['text'] ?>;">
                            <?= $slaSc['label'] ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <div style="padding: 16px 30px; text-align: center; border: 1px solid #e0e0e0; border-top: none; border-radius: 0 0 8px 8px; background-color: #fafafa;">
        <p style="font-size: 12px; color: #999; margin: 0;">
            <?= __('This report was automatically generated by {0}.', h($siteName)) ?>
        </p>
        <?php if (!empty($manageUrl)): ?>
        <p style="font-size: 12px; margin: 8px 0 0 0;">
            <a href="<?= h($manageUrl) ?>" style="color: #1E88E5; text-decoration: none;"><?= __('Manage report settings') ?></a>
        </p>
        <?php endif; ?>
    </div>
</div>
