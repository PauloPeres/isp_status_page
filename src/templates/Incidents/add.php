<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Incident $incident
 * @var array $monitors
 */
$this->assign('title', __d('incidents', 'New Incident'));
?>

<?= $this->element('admin/breadcrumb', ['breadcrumbs' => [
    ['title' => __('Incidents'), 'url' => $this->Url->build(['controller' => 'Incidents', 'action' => 'index'])],
    ['title' => __d('incidents', 'New Incident'), 'url' => null],
]]) ?>

<style>
.incident-form { max-width: 700px; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; margin-bottom: 6px; font-weight: 500; color: var(--color-dark); }
.form-control { width: 100%; padding: 10px 12px; border: 2px solid var(--color-gray-light); border-radius: var(--radius-md); font-size: 15px; transition: border-color 0.2s; box-sizing: border-box; }
.form-control:focus { outline: none; border-color: var(--color-primary); box-shadow: 0 0 0 3px rgba(30, 136, 229, 0.1); }
.form-actions { display: flex; gap: 12px; padding-top: 16px; }
@media (max-width: 768px) {
    .incident-form { max-width: 100%; }
    .form-actions { flex-direction: column; }
    .form-actions .btn { width: 100%; min-height: 44px; }
    .form-control { font-size: 16px; min-height: 44px; }
}
</style>

<div class="incident-form">
    <div class="page-header">
        <div>
            <h1><?= __d('incidents', 'New Incident') ?></h1>
            <p><?= __d('incidents', 'Create an incident manually') ?></p>
        </div>
        <?= $this->Html->link(
            __('Back'),
            ['action' => 'index'],
            ['class' => 'btn btn-secondary']
        ) ?>
    </div>

    <div class="card">
        <?= $this->Form->create($incident) ?>

        <div class="form-group">
            <label><?= __d('incidents', 'Monitor') ?> *</label>
            <?= $this->Form->select('monitor_id', $monitors, [
                'class' => 'form-control',
                'empty' => __d('incidents', '-- Select Monitor --'),
                'required' => true,
            ]) ?>
        </div>

        <div class="form-group">
            <label><?= __d('incidents', 'Title') ?> *</label>
            <?= $this->Form->text('title', [
                'class' => 'form-control',
                'placeholder' => __d('incidents', 'Brief description of the incident'),
                'required' => true,
            ]) ?>
        </div>

        <div class="form-group">
            <label><?= __d('incidents', 'Description') ?></label>
            <?= $this->Form->textarea('description', [
                'class' => 'form-control',
                'rows' => 4,
                'placeholder' => __d('incidents', 'Detailed description of the incident...'),
            ]) ?>
        </div>

        <div class="form-group">
            <label><?= __d('incidents', 'Severity') ?> *</label>
            <?= $this->Form->select('severity', [
                'critical' => __d('incidents', 'Critical'),
                'major' => __d('incidents', 'Major'),
                'minor' => __d('incidents', 'Minor'),
            ], [
                'class' => 'form-control',
                'required' => true,
            ]) ?>
        </div>

        <div class="form-group">
            <label><?= __d('incidents', 'Status') ?> *</label>
            <?= $this->Form->select('status', [
                'investigating' => __d('incidents', 'Investigating'),
                'identified' => __d('incidents', 'Identified'),
                'monitoring' => __d('incidents', 'Monitoring'),
            ], [
                'class' => 'form-control',
                'required' => true,
            ]) ?>
        </div>

        <?= $this->Form->hidden('started_at', ['value' => date('Y-m-d H:i:s')]) ?>
        <?= $this->Form->hidden('auto_created', ['value' => '0']) ?>

        <div class="form-actions">
            <?= $this->Form->button(__d('incidents', 'Create Incident'), ['class' => 'btn btn-primary']) ?>
            <?= $this->Html->link(__('Cancel'), ['action' => 'index'], ['class' => 'btn btn-secondary']) ?>
        </div>

        <?= $this->Form->end() ?>
    </div>
</div>
