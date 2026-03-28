<?php
/**
 * @var \App\View\AppView $this
 */
$this->assign('title', __d('monitors', 'Import Monitors'));
?>

<div class="monitors-header">
    <h2>🖥️ <?= __d('monitors', 'Import Monitors from CSV') ?></h2>
    <?= $this->Html->link(
        __d('monitors', 'Back to Monitors'),
        ['action' => 'index'],
        ['class' => 'btn-add', 'style' => 'background: #6c757d;']
    ) ?>
</div>

<div class="card" style="max-width: 700px; margin: 0 auto; padding: 24px; background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
    <h3 style="margin-top: 0;"><?= __d('monitors', 'Upload CSV File') ?></h3>

    <p style="color: #666; margin-bottom: 16px;">
        <?= __d('monitors', 'Upload a CSV file to create multiple monitors at once. The file must include a header row.') ?>
    </p>

    <div style="background: #f5f5f5; border-radius: 6px; padding: 16px; margin-bottom: 20px;">
        <h4 style="margin: 0 0 8px 0;"><?= __d('monitors', 'Required Columns') ?></h4>
        <ul style="margin: 0; padding-left: 20px; color: #555;">
            <li><strong>name</strong> — <?= __d('monitors', 'Monitor name (required)') ?></li>
        </ul>

        <h4 style="margin: 12px 0 8px 0;"><?= __d('monitors', 'Optional Columns') ?></h4>
        <ul style="margin: 0; padding-left: 20px; color: #555;">
            <li><strong>type</strong> — <?= __d('monitors', 'http, ping, or port (default: http)') ?></li>
            <li><strong>url</strong> — <?= __d('monitors', 'URL for HTTP monitors') ?></li>
            <li><strong>host</strong> — <?= __d('monitors', 'Hostname for Ping/Port monitors') ?></li>
            <li><strong>port</strong> — <?= __d('monitors', 'Port number for Port monitors') ?></li>
            <li><strong>check_interval</strong> — <?= __d('monitors', 'Check interval in seconds (default: 300)') ?></li>
            <li><strong>tags</strong> — <?= __d('monitors', 'Tags separated by semicolons (e.g. production;web)') ?></li>
        </ul>
    </div>

    <div style="background: #e8f5e9; border-radius: 6px; padding: 16px; margin-bottom: 20px;">
        <h4 style="margin: 0 0 8px 0;"><?= __d('monitors', 'Example CSV') ?></h4>
        <pre style="margin: 0; font-size: 13px; color: #333; overflow-x: auto;">name,type,url,host,port,check_interval,tags
My Website,http,https://example.com,,,300,production;web
Database Server,ping,,db.example.com,,60,infrastructure
Redis Cache,port,,redis.example.com,6379,120,infrastructure;cache</pre>
    </div>

    <?= $this->Form->create(null, ['type' => 'file', 'id' => 'import-form']) ?>
    <div style="margin-bottom: 16px;">
        <label for="csv_file" style="display: block; margin-bottom: 6px; font-weight: 600;">
            <?= __d('monitors', 'CSV File') ?>
        </label>
        <input type="file" name="csv_file" id="csv_file" accept=".csv" required
               style="display: block; width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
    </div>
    <div style="display: flex; gap: 8px;">
        <?= $this->Form->button(__d('monitors', 'Import Monitors'), [
            'type' => 'submit',
            'class' => 'btn-add',
        ]) ?>
        <?= $this->Html->link(
            __('Cancel'),
            ['action' => 'index'],
            ['class' => 'btn-clear']
        ) ?>
    </div>
    <?= $this->Form->end() ?>
</div>
