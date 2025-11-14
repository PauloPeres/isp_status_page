<?php
/**
 * Incident Timeline Element
 *
 * @var \App\View\AppView $this
 * @var \Cake\Collection\CollectionInterface $incidents
 */

if ($incidents->count() === 0) {
    return;
}
?>

<div class="incidents-section">
    <h3 class="section-title">🚨 <?= __('Incidentes Recentes') ?></h3>

    <div class="incident-timeline">
        <?php foreach ($incidents as $incident): ?>
            <div class="timeline-item <?= h($incident->status) ?>">
                <div class="timeline-marker">
                    <span class="timeline-dot <?= h($incident->status) ?>"></span>
                </div>
                <div class="timeline-content">
                    <div class="incident-header">
                        <h4 class="incident-title"><?= h($incident->title) ?></h4>
                        <span class="incident-status-badge <?= h($incident->status) ?>">
                            <?php
                            if ($incident->status === 'resolved') {
                                echo '✅ ' . __('Resolvido');
                            } elseif ($incident->status === 'investigating') {
                                echo '🔍 ' . __('Investigando');
                            } elseif ($incident->status === 'identified') {
                                echo '⚠️ ' . __('Identificado');
                            } else {
                                echo '🔄 ' . ucfirst($incident->status);
                            }
                            ?>
                        </span>
                    </div>

                    <?php if ($incident->description): ?>
                        <div class="incident-description">
                            <?= nl2br(h($incident->description)) ?>
                        </div>
                    <?php endif; ?>

                    <div class="incident-meta">
                        <span class="meta-item">
                            <span class="meta-icon">📅</span>
                            <span class="meta-text">
                                <?= __('Iniciado:') ?> <span class="local-datetime" data-utc="<?= $incident->started_at->format('c') ?>"></span>
                            </span>
                        </span>

                        <?php if ($incident->resolved_at): ?>
                            <span class="meta-item resolved">
                                <span class="meta-icon">✅</span>
                                <span class="meta-text">
                                    <?= __('Resolvido:') ?> <span class="local-datetime" data-utc="<?= $incident->resolved_at->format('c') ?>"></span>
                                </span>
                            </span>

                            <?php if ($incident->duration): ?>
                                <span class="meta-item">
                                    <span class="meta-icon">⏱️</span>
                                    <span class="meta-text">
                                        <?= __('Duração:') ?> <?= gmdate('H\h i\m', $incident->duration) ?>
                                    </span>
                                </span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="meta-item ongoing">
                                <span class="meta-icon">🔴</span>
                                <span class="meta-text"><?= __('Em andamento') ?></span>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="incidents-footer">
        <?= $this->Html->link(
            __('Ver Histórico Completo') . ' →',
            ['action' => 'history'],
            ['class' => 'btn-view-history']
        ) ?>
    </div>
</div>
