<?php
/**
 * Uptime History Bar Element
 *
 * Displays a horizontal bar of daily uptime segments (like UptimeRobot).
 *
 * @var array $uptimeData — array of ['date' => 'Y-m-d', 'uptime' => 0-100, 'checks' => int]
 * @var int $days — number of days (default 30)
 * @var bool $compact — compact mode for list views (default false)
 */
$days = $days ?? 30;
$compact = $compact ?? false;
?>
<div class="uptime-bar<?= $compact ? ' uptime-bar-compact' : '' ?>" title="<?= __('Last %d days', $days) ?>">
    <?php foreach ($uptimeData as $day): ?>
        <?php
        $color = '#22c55e'; // green
        if ($day['uptime'] < 100 && $day['uptime'] >= 99) {
            $color = '#84cc16'; // light green
        }
        if ($day['uptime'] < 99 && $day['uptime'] >= 95) {
            $color = '#eab308'; // yellow
        }
        if ($day['uptime'] < 95) {
            $color = '#ef4444'; // red
        }
        if ($day['checks'] === 0) {
            $color = '#d1d5db'; // grey (no data)
        }
        ?>
        <div class="uptime-bar-segment"
             style="background: <?= $color ?>"
             title="<?= h($day['date']) ?>: <?= number_format($day['uptime'], 1) ?>% (<?= $day['checks'] ?> checks)">
        </div>
    <?php endforeach; ?>
</div>
<?php if (!$compact): ?>
<div class="uptime-bar-labels">
    <span><?= __('%d days ago', $days) ?></span>
    <span class="uptime-bar-percentage"><?= number_format(array_sum(array_column($uptimeData, 'uptime')) / max(count($uptimeData), 1), 2) ?>%</span>
    <span><?= __('Today') ?></span>
</div>
<?php endif; ?>
