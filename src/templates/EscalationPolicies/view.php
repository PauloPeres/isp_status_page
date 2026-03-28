<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\EscalationPolicy $escalationPolicy
 */
$this->assign('title', __('Escalation Policy: {0}', h($escalationPolicy->name)));
?>

<?= $this->element('admin/breadcrumb', ['breadcrumbs' => [
    ['title' => __('Escalation Policies'), 'url' => $this->Url->build(['controller' => 'EscalationPolicies', 'action' => 'index'])],
    ['title' => h($escalationPolicy->name), 'url' => null],
]]) ?>

<div class="escalation-policies-view">
    <div class="page-header">
        <div>
            <h1><?= h($escalationPolicy->name) ?></h1>
            <?php if ($escalationPolicy->description): ?>
                <p><?= h($escalationPolicy->description) ?></p>
            <?php endif; ?>
        </div>
        <div style="display: flex; gap: 8px;">
            <?= $this->Html->link(
                __('Edit'),
                ['action' => 'edit', $escalationPolicy->id],
                ['class' => 'btn btn-primary']
            ) ?>
            <?= $this->Html->link(
                '&larr; ' . __('Back'),
                ['action' => 'index'],
                ['class' => 'btn btn-secondary', 'escape' => false]
            ) ?>
        </div>
    </div>

    <!-- Policy Status -->
    <div class="status-badges">
        <?php if ($escalationPolicy->active): ?>
            <span class="badge badge-success"><?= __('Active') ?></span>
        <?php else: ?>
            <span class="badge badge-secondary"><?= __('Inactive') ?></span>
        <?php endif; ?>

        <?php if ($escalationPolicy->repeat_enabled): ?>
            <span class="badge badge-warning"><?= __('Repeat every {0} min', $escalationPolicy->repeat_after_minutes) ?></span>
        <?php endif; ?>

        <span class="badge badge-info"><?= $escalationPolicy->getStepCount() ?> <?= __('step(s)') ?></span>
        <span class="badge badge-secondary"><?= $escalationPolicy->getMonitorCount() ?> <?= __('monitor(s)') ?></span>
    </div>

    <!-- Escalation Timeline -->
    <div class="card">
        <h3 class="card-title"><?= __('Escalation Timeline') ?></h3>

        <?php if (!empty($escalationPolicy->escalation_steps)): ?>
            <div class="timeline">
                <?php foreach ($escalationPolicy->escalation_steps as $step): ?>
                    <div class="timeline-step">
                        <div class="timeline-marker">
                            <div class="timeline-dot"></div>
                            <?php if ($step !== end($escalationPolicy->escalation_steps)): ?>
                                <div class="timeline-line"></div>
                            <?php endif; ?>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-time">
                                <?= $step->wait_minutes ?> <?= __('min') ?>
                            </div>
                            <div class="timeline-arrow">&rarr;</div>
                            <div class="timeline-detail">
                                <span class="channel-badge channel-<?= h($step->channel) ?>"><?= h($step->getChannelName()) ?></span>
                                <span class="timeline-recipients"><?= __('to') ?> <?= h($step->getRecipientsSummary()) ?></span>
                            </div>
                        </div>
                        <?php if ($step->message_template): ?>
                            <div class="timeline-message">
                                <small><?= h($step->message_template) ?></small>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <?php if ($escalationPolicy->repeat_enabled): ?>
                    <div class="timeline-step timeline-repeat">
                        <div class="timeline-marker">
                            <div class="timeline-dot timeline-dot-repeat"></div>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-time">
                                +<?= $escalationPolicy->repeat_after_minutes ?> <?= __('min') ?>
                            </div>
                            <div class="timeline-arrow">&rarr;</div>
                            <div class="timeline-detail">
                                <span class="text-muted"><?= __('Repeat from Step 1') ?></span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p class="text-muted"><?= __('No escalation steps defined.') ?></p>
        <?php endif; ?>
    </div>

    <!-- Monitors Using This Policy -->
    <div class="card">
        <h3 class="card-title"><?= __('Monitors Using This Policy') ?></h3>

        <?php if (!empty($escalationPolicy->monitors)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th><?= __('Monitor') ?></th>
                        <th><?= __('Type') ?></th>
                        <th><?= __('Status') ?></th>
                        <th><?= __('Active') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($escalationPolicy->monitors as $monitor): ?>
                    <tr>
                        <td>
                            <?= $this->Html->link(
                                h($monitor->name),
                                ['controller' => 'Monitors', 'action' => 'view', $monitor->id],
                                ['class' => 'link-primary']
                            ) ?>
                        </td>
                        <td><span class="badge badge-info"><?= h(strtoupper($monitor->type)) ?></span></td>
                        <td>
                            <span class="badge badge-<?= $monitor->getStatusBadgeClass() ?>">
                                <?= h(ucfirst($monitor->status)) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($monitor->active): ?>
                                <span class="badge badge-success"><?= __('Active') ?></span>
                            <?php else: ?>
                                <span class="badge badge-secondary"><?= __('Inactive') ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-muted"><?= __('No monitors are using this policy yet.') ?></p>
            <p class="form-help"><?= __('Assign this policy to monitors in their edit page.') ?></p>
        <?php endif; ?>
    </div>

    <!-- Policy Info -->
    <div class="card">
        <h3 class="card-title"><?= __('Policy Information') ?></h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label"><?= __('Created') ?></span>
                <span><?= $escalationPolicy->created->nice() ?></span>
            </div>
            <div class="info-item">
                <span class="info-label"><?= __('Last Modified') ?></span>
                <span><?= $escalationPolicy->modified->nice() ?></span>
            </div>
        </div>
    </div>
</div>

<style>
.escalation-policies-view .page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 16px;
}
.escalation-policies-view .page-header h1 {
    font-size: 24px;
    margin-bottom: 4px;
}
.status-badges {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 24px;
}
.card-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--color-dark);
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--color-gray-light);
}

/* Timeline */
.timeline {
    position: relative;
    padding-left: 0;
}
.timeline-step {
    display: flex;
    align-items: flex-start;
    margin-bottom: 0;
    position: relative;
    min-height: 60px;
}
.timeline-marker {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-right: 16px;
    min-width: 20px;
}
.timeline-dot {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: var(--color-primary);
    border: 3px solid #fff;
    box-shadow: 0 0 0 2px var(--color-primary);
    flex-shrink: 0;
    z-index: 1;
}
.timeline-dot-repeat {
    background: var(--color-warning);
    box-shadow: 0 0 0 2px var(--color-warning);
}
.timeline-line {
    width: 2px;
    flex-grow: 1;
    min-height: 30px;
    background: var(--color-primary);
    opacity: 0.3;
}
.timeline-content {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 4px 0 16px;
    flex-wrap: wrap;
}
.timeline-time {
    font-weight: 700;
    font-size: 16px;
    color: var(--color-dark);
    min-width: 60px;
}
.timeline-arrow {
    font-size: 18px;
    color: var(--color-gray-medium);
}
.timeline-detail {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.timeline-recipients {
    color: var(--color-gray-medium);
    font-size: 14px;
}
.timeline-message {
    margin-left: 36px;
    margin-top: -8px;
    margin-bottom: 16px;
    padding: 8px 12px;
    background: var(--color-gray-light);
    border-radius: var(--radius-md);
    font-style: italic;
    color: var(--color-gray-medium);
}
.timeline-repeat {
    opacity: 0.7;
    border-top: 1px dashed var(--color-gray-medium);
    padding-top: 12px;
}

/* Channel badges */
.channel-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #fff;
}
.channel-email { background: #1E88E5; }
.channel-slack { background: #4A154B; }
.channel-discord { background: #5865F2; }
.channel-telegram { background: #0088cc; }
.channel-webhook { background: #546E7A; }
.channel-sms { background: #43A047; }

.link-primary {
    color: var(--color-primary);
    font-weight: 600;
    text-decoration: none;
}
.link-primary:hover {
    text-decoration: underline;
}
.text-muted {
    color: var(--color-gray-medium);
}
.form-help {
    font-size: 13px;
    color: var(--color-gray-medium);
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}
.info-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.info-label {
    font-size: 13px;
    font-weight: 600;
    color: var(--color-gray-medium);
}

@media (max-width: 768px) {
    .escalation-policies-view .page-header {
        flex-direction: column;
        gap: 12px;
    }
    .timeline-content {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
    }
    .timeline-arrow {
        display: none;
    }
}
</style>
