<?php
/**
 * Reports Index - Report selection and download page (P3-010)
 *
 * @var \App\View\AppView $this
 */
?>

<div class="content-header">
    <h1><?= __('Reports') ?></h1>
</div>

<div class="card">
    <h2><?= __('Export Reports') ?></h2>
    <p><?= __('Select a report type, choose a date range, and download as CSV.') ?></p>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-top: 20px;">

    <!-- Uptime Report -->
    <div class="card">
        <h3><?= __('Uptime Report') ?></h3>
        <p><?= __('Per-monitor uptime percentage by day for the selected date range.') ?></p>
        <form method="get" action="<?= $this->Url->build(['action' => 'uptimeReport']) ?>" style="margin-top: 12px;">
            <div class="form-group">
                <label for="uptime-range"><?= __('Date Range') ?></label>
                <select name="range" id="uptime-range" class="form-control">
                    <option value="7"><?= __('Last 7 days') ?></option>
                    <option value="30" selected><?= __('Last 30 days') ?></option>
                    <option value="60"><?= __('Last 60 days') ?></option>
                    <option value="90"><?= __('Last 90 days') ?></option>
                    <option value="180"><?= __('Last 180 days') ?></option>
                    <option value="365"><?= __('Last 365 days') ?></option>
                </select>
            </div>
            <div class="form-actions" style="margin-top: 12px;">
                <button type="submit" class="btn btn-primary"><?= __('Download CSV') ?></button>
            </div>
        </form>
    </div>

    <!-- Incident Report -->
    <div class="card">
        <h3><?= __('Incident History') ?></h3>
        <p><?= __('All incidents with monitor, status, severity, timing, and duration.') ?></p>
        <form method="get" action="<?= $this->Url->build(['action' => 'incidentReport']) ?>" style="margin-top: 12px;">
            <div class="form-group">
                <label for="incident-range"><?= __('Date Range') ?></label>
                <select name="range" id="incident-range" class="form-control">
                    <option value="7"><?= __('Last 7 days') ?></option>
                    <option value="30" selected><?= __('Last 30 days') ?></option>
                    <option value="60"><?= __('Last 60 days') ?></option>
                    <option value="90"><?= __('Last 90 days') ?></option>
                    <option value="180"><?= __('Last 180 days') ?></option>
                    <option value="365"><?= __('Last 365 days') ?></option>
                </select>
            </div>
            <div class="form-actions" style="margin-top: 12px;">
                <button type="submit" class="btn btn-primary"><?= __('Download CSV') ?></button>
            </div>
        </form>
    </div>

    <!-- Response Time Report -->
    <div class="card">
        <h3><?= __('Response Time Report') ?></h3>
        <p><?= __('Average, min, and max response times per monitor per day.') ?></p>
        <form method="get" action="<?= $this->Url->build(['action' => 'responseTimeReport']) ?>" style="margin-top: 12px;">
            <div class="form-group">
                <label for="rt-range"><?= __('Date Range') ?></label>
                <select name="range" id="rt-range" class="form-control">
                    <option value="7"><?= __('Last 7 days') ?></option>
                    <option value="30" selected><?= __('Last 30 days') ?></option>
                    <option value="60"><?= __('Last 60 days') ?></option>
                    <option value="90"><?= __('Last 90 days') ?></option>
                    <option value="180"><?= __('Last 180 days') ?></option>
                    <option value="365"><?= __('Last 365 days') ?></option>
                </select>
            </div>
            <div class="form-actions" style="margin-top: 12px;">
                <button type="submit" class="btn btn-primary"><?= __('Download CSV') ?></button>
            </div>
        </form>
    </div>

</div>
