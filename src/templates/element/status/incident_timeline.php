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
    <h3 class="section-title">🚨 <?= __('Recent Incidents') ?></h3>

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
                                echo '✅ ' . __('Resolved');
                            } elseif ($incident->status === 'investigating') {
                                echo '🔍 ' . __('Investigating');
                            } elseif ($incident->status === 'identified') {
                                echo '⚠️ ' . __('Identified');
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
                                <?= __('Started:') ?> <span class="local-datetime" data-utc="<?= $incident->started_at->format('c') ?>"></span>
                            </span>
                        </span>

                        <?php if ($incident->resolved_at): ?>
                            <span class="meta-item resolved">
                                <span class="meta-icon">✅</span>
                                <span class="meta-text">
                                    <?= __('Resolved:') ?> <span class="local-datetime" data-utc="<?= $incident->resolved_at->format('c') ?>"></span>
                                </span>
                            </span>

                            <?php if ($incident->duration): ?>
                                <span class="meta-item">
                                    <span class="meta-icon">⏱️</span>
                                    <span class="meta-text">
                                        <?= __('Duration:') ?> <?= gmdate('H\h i\m', $incident->duration) ?>
                                    </span>
                                </span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="meta-item ongoing">
                                <span class="meta-icon">🔴</span>
                                <span class="meta-text"><?= __('In progress') ?></span>
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($incident->incident_updates)): ?>
                    <div class="public-incident-updates">
                        <?php foreach ($incident->incident_updates as $update): ?>
                        <div class="public-update-entry public-update-<?= h($update->status) ?>">
                            <div class="public-update-dot"></div>
                            <div class="public-update-content">
                                <span class="public-update-status public-update-status-<?= h($update->getStatusBadgeClass()) ?>"><?= h($update->getStatusLabel()) ?></span>
                                <span class="public-update-time">
                                    <span class="local-datetime" data-utc="<?= $update->created->format('c') ?>"></span>
                                </span>
                                <?php if ($update->user): ?>
                                    <span class="public-update-author"><?= __('by {0}', h($update->user->username)) ?></span>
                                <?php endif; ?>
                                <div class="public-update-message"><?= nl2br(h($update->message)) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="incidents-footer">
        <?= $this->Html->link(
            __('View Full History') . ' →',
            ['action' => 'history'],
            ['class' => 'btn-view-history']
        ) ?>
    </div>
</div>
