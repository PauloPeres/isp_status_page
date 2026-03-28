<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\AlertLog> $emailLogs
 * @var array $stats
 * @var string $period
 */
$this->assign('title', __('Logs de Emails'));
?>

<!-- Styles provided by admin.css -->

<div class="email-logs-header">
    <h2><?= __('Logs de Emails') ?></h2>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card-mini">
        <div class="stat-label"><?= __('Total Enviados') ?></div>
        <div class="stat-value info"><?= number_format($stats['total']) ?></div>
    </div>
    <div class="stat-card-mini">
        <div class="stat-label"><?= __('Sucesso') ?></div>
        <div class="stat-value success"><?= number_format($stats['sent']) ?></div>
    </div>
    <div class="stat-card-mini">
        <div class="stat-label"><?= __('Falhas') ?></div>
        <div class="stat-value error"><?= number_format($stats['failed']) ?></div>
    </div>
    <div class="stat-card-mini">
        <div class="stat-label"><?= __('Taxa de Sucesso') ?></div>
        <div class="stat-value <?= $stats['successRate'] >= 95 ? 'success' : ($stats['successRate'] >= 80 ? 'info' : 'error') ?>">
            <?= number_format($stats['successRate'], 1) ?>%
        </div>
    </div>
    <div class="stat-card-mini">
        <div class="stat-label"><?= __('Hoje') ?></div>
        <div class="stat-value info"><?= number_format($stats['today']) ?></div>
    </div>
</div>

<!-- Filters -->
<div class="filters-card">
    <?= $this->Form->create(null, ['type' => 'get', 'id' => 'filters-form']) ?>
    <div class="filters-row">
        <div class="filter-group">
            <label><?= __('Buscar') ?></label>
            <?= $this->Form->control('search', [
                'label' => false,
                'placeholder' => __('Email ou assunto...'),
                'value' => $this->request->getQuery('search'),
                'class' => 'form-control',
            ]) ?>
        </div>

        <div class="filter-group">
            <label><?= __('Status') ?></label>
            <?= $this->Form->control('status', [
                'options' => [
                    '' => __('Todos'),
                    'sent' => __('Enviados'),
                    'failed' => __('Falhas'),
                    'queued' => __('Na Fila'),
                ],
                'default' => $this->request->getQuery('status'),
                'label' => false,
                'class' => 'form-control',
            ]) ?>
        </div>

        <div class="filter-group">
            <label><?= __('Período') ?></label>
            <?= $this->Form->control('period', [
                'options' => [
                    '24h' => __('Últimas 24 horas'),
                    '7d' => __('Últimos 7 dias'),
                    '30d' => __('Últimos 30 dias'),
                    'all' => __('Todos'),
                ],
                'default' => $period,
                'label' => false,
                'class' => 'form-control',
            ]) ?>
        </div>

        <div class="filter-buttons">
            <?= $this->Form->button(__('Filtrar'), ['type' => 'submit', 'class' => 'btn-filter']) ?>
            <?= $this->Html->link(__('Limpar'), ['action' => 'index'], ['class' => 'btn-clear']) ?>
        </div>
    </div>
    <?= $this->Form->end() ?>
</div>

<!-- Email Logs Table -->
<div class="email-logs-table">
    <?php if ($emailLogs->count() > 0): ?>
        <table>
            <thead>
                <tr>
                    <th><?= __('Data/Hora') ?></th>
                    <th><?= __('Destinatário') ?></th>
                    <th><?= __('Assunto') ?></th>
                    <th><?= __('Status') ?></th>
                    <th><?= __('Monitor') ?></th>
                    <th style="text-align: right;"><?= __('Ações') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($emailLogs as $log): ?>
                    <tr>
                        <td>
                            <span class="local-datetime" data-utc="<?= $log->created->format('c') ?>"></span>
                        </td>
                        <td>
                            <strong><?= h($log->recipient) ?></strong>
                        </td>
                        <td>
                            <?php if (isset($log->monitor)): ?>
                                <span><?= h($log->monitor->name) ?></span>
                                <?php if (isset($log->incident)): ?>
                                    <br><span style="color: #999; font-size: 12px;"><?= __('Incidente') ?> #<?= h($log->incident->id) ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color: #999;">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($log->status === 'sent'): ?>
                                <span class="badge badge-success"><?= __('Enviado') ?></span>
                                <?php if ($log->sent_at): ?>
                                    <br><span style="color: #999; font-size: 12px;">
                                        <span class="local-datetime" data-utc="<?= $log->sent_at->format('c') ?>"></span>
                                    </span>
                                <?php endif; ?>
                            <?php elseif ($log->status === 'failed'): ?>
                                <span class="badge badge-danger"><?= __('Falha') ?></span>
                            <?php elseif ($log->status === 'queued'): ?>
                                <span class="badge badge-warning"><?= __('Na Fila') ?></span>
                            <?php else: ?>
                                <span class="badge badge-info"><?= h(ucfirst($log->status)) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (isset($log->monitor)): ?>
                                <?= $this->Html->link(
                                    h($log->monitor->name),
                                    ['controller' => 'Monitors', 'action' => 'view', $log->monitor->id],
                                    ['class' => 'monitor-link']
                                ) ?>
                                <br>
                                <span style="color: #999; font-size: 12px;">
                                    <?= h(strtoupper($log->monitor->type)) ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #999;">-</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <div class="action-buttons">
                                <?= $this->Html->link(
                                    __('Ver'),
                                    ['action' => 'view', $log->id],
                                    ['class' => 'btn-action btn-action-view', 'title' => __('Ver detalhes')]
                                ) ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <?= $this->element('empty_state', [
            'icon' => '📧',
            'title' => __('No email logs yet'),
        ]) ?>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($emailLogs->count() > 0): ?>
    <div class="pagination">
        <?= $this->Paginator->first(__('« Primeira')) ?>
        <?= $this->Paginator->prev(__('‹ Anterior')) ?>
        <?= $this->Paginator->numbers() ?>
        <?= $this->Paginator->next(__('Próxima ›')) ?>
        <?= $this->Paginator->last(__('Última »')) ?>
    </div>
    <div class="pagination-info">
        <?= $this->Paginator->counter(__('Página {{page}} de {{pages}}, mostrando {{current}} registro(s) de {{count}} no total')) ?>
    </div>
<?php endif; ?>
