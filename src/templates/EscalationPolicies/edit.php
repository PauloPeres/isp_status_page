<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\EscalationPolicy $escalationPolicy
 */
$this->assign('title', __('Edit Escalation Policy'));
?>

<?= $this->element('admin/breadcrumb', ['breadcrumbs' => [
    ['title' => __('Escalation Policies'), 'url' => $this->Url->build(['controller' => 'EscalationPolicies', 'action' => 'index'])],
    ['title' => __('Edit: {0}', h($escalationPolicy->name)), 'url' => null],
]]) ?>

<div class="escalation-policies-form">
    <div class="page-header">
        <div>
            <h1><?= __('Edit Escalation Policy') ?></h1>
            <p><?= __('Modify escalation steps for: {0}', '<strong>' . h($escalationPolicy->name) . '</strong>') ?></p>
        </div>
        <div style="display: flex; gap: 8px;">
            <?= $this->Html->link(
                __('View'),
                ['action' => 'view', $escalationPolicy->id],
                ['class' => 'btn btn-secondary']
            ) ?>
            <?= $this->Html->link(
                '&larr; ' . __('Back'),
                ['action' => 'index'],
                ['class' => 'btn btn-secondary', 'escape' => false]
            ) ?>
        </div>
    </div>

    <div class="card">
        <?= $this->Form->create($escalationPolicy) ?>

        <div class="form-section">
            <h3 class="form-section-title"><?= __('Policy Details') ?></h3>

            <div class="form-group">
                <label><?= __('Policy Name') ?> *</label>
                <?= $this->Form->text('name', [
                    'required' => true,
                    'class' => 'form-control',
                ]) ?>
            </div>

            <div class="form-group">
                <label><?= __('Description') ?></label>
                <?= $this->Form->textarea('description', [
                    'rows' => 3,
                    'class' => 'form-control',
                ]) ?>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>
                        <?= $this->Form->checkbox('repeat_enabled', ['id' => 'repeat-enabled', 'checked' => $escalationPolicy->repeat_enabled]) ?>
                        <?= __('Repeat escalation cycle') ?>
                    </label>
                    <small class="form-help"><?= __('Restart the escalation from step 1 after all steps have been executed') ?></small>
                </div>

                <div class="form-group" id="repeat-after-group" style="<?= $escalationPolicy->repeat_enabled ? '' : 'display: none;' ?>">
                    <label><?= __('Repeat after (minutes)') ?></label>
                    <?= $this->Form->number('repeat_after_minutes', [
                        'min' => 1,
                        'class' => 'form-control',
                    ]) ?>
                    <small class="form-help"><?= __('Minutes to wait after the last step before repeating') ?></small>
                </div>
            </div>

            <div class="form-group">
                <label>
                    <?= $this->Form->checkbox('active', ['checked' => $escalationPolicy->active]) ?>
                    <?= __('Active') ?>
                </label>
                <small class="form-help"><?= __('Inactive policies will not trigger escalation steps') ?></small>
            </div>
        </div>

        <div class="form-section">
            <h3 class="form-section-title"><?= __('Escalation Steps') ?></h3>
            <p class="form-help"><?= __('Define the sequence of alerts. Each step triggers if the incident is NOT acknowledged within the wait time.') ?></p>

            <div id="steps-container">
                <?php
                $existingSteps = $escalationPolicy->escalation_steps ?? [];
                if (empty($existingSteps)) {
                    $existingSteps = [(object)['wait_minutes' => 0, 'channel' => 'email', 'recipients' => '[]', 'message_template' => '']];
                }
                foreach ($existingSteps as $idx => $step):
                    $stepRecipients = '';
                    if (is_object($step) && method_exists($step, 'getRecipients')) {
                        $stepRecipients = implode(', ', $step->getRecipients());
                    } elseif (is_object($step) && !empty($step->recipients)) {
                        $decoded = json_decode($step->recipients, true);
                        $stepRecipients = is_array($decoded) ? implode(', ', $decoded) : $step->recipients;
                    }
                ?>
                <div class="step-row" data-step-index="<?= $idx ?>">
                    <div class="step-header">
                        <span class="step-number"><?= __('Step') ?> <span class="step-num"><?= $idx + 1 ?></span></span>
                        <button type="button" class="btn btn-sm btn-error remove-step-btn" onclick="removeStep(this)" <?= count($existingSteps) <= 1 ? 'style="display:none"' : '' ?>><?= __('Remove') ?></button>
                    </div>
                    <div class="step-fields">
                        <div class="form-group">
                            <label><?= __('Wait (minutes)') ?></label>
                            <input type="number" name="steps[<?= $idx ?>][wait_minutes]" value="<?= h($step->wait_minutes ?? 0) ?>" min="0" class="form-control" required>
                            <small class="form-help"><?= __('Minutes from incident start') ?></small>
                        </div>
                        <div class="form-group">
                            <label><?= __('Channel') ?></label>
                            <select name="steps[<?= $idx ?>][channel]" class="form-control" required>
                                <?php
                                $channels = ['email' => 'Email', 'slack' => 'Slack', 'discord' => 'Discord', 'telegram' => 'Telegram', 'webhook' => 'Webhook', 'sms' => 'SMS'];
                                foreach ($channels as $val => $label):
                                ?>
                                <option value="<?= $val ?>" <?= ($step->channel ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group form-group-wide">
                            <label><?= __('Recipients') ?></label>
                            <input type="text" name="steps[<?= $idx ?>][recipients]" class="form-control" value="<?= h($stepRecipients) ?>" placeholder="<?= __('e.g. team@company.com, oncall@company.com') ?>" required>
                            <small class="form-help"><?= __('Comma-separated list of recipients') ?></small>
                        </div>
                        <div class="form-group form-group-wide">
                            <label><?= __('Custom message (optional)') ?></label>
                            <input type="text" name="steps[<?= $idx ?>][message_template]" class="form-control" value="<?= h($step->message_template ?? '') ?>" placeholder="<?= __('Optional custom message for this step') ?>">
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <button type="button" class="btn btn-secondary" id="add-step-btn" onclick="addStep()">
                + <?= __('Add Step') ?>
            </button>
        </div>

        <div class="form-actions">
            <?= $this->Form->button(__('Save Changes'), ['class' => 'btn btn-primary']) ?>
            <?= $this->Html->link(
                __('Cancel'),
                ['action' => 'index'],
                ['class' => 'btn btn-secondary']
            ) ?>
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $escalationPolicy->id],
                [
                    'class' => 'btn btn-error',
                    'confirm' => __('Are you sure? This action cannot be undone.'),
                ]
            ) ?>
        </div>

        <?= $this->Form->end() ?>
    </div>
</div>

<script>
var stepIndex = <?= count($existingSteps) ?>;

function addStep() {
    var container = document.getElementById('steps-container');
    var stepNum = container.querySelectorAll('.step-row').length + 1;
    var html = `
    <div class="step-row" data-step-index="${stepIndex}">
        <div class="step-header">
            <span class="step-number"><?= __('Step') ?> <span class="step-num">${stepNum}</span></span>
            <button type="button" class="btn btn-sm btn-error remove-step-btn" onclick="removeStep(this)"><?= __('Remove') ?></button>
        </div>
        <div class="step-fields">
            <div class="form-group">
                <label><?= __('Wait (minutes)') ?></label>
                <input type="number" name="steps[${stepIndex}][wait_minutes]" value="${stepNum * 5}" min="0" class="form-control" required>
                <small class="form-help"><?= __('Minutes from incident start') ?></small>
            </div>
            <div class="form-group">
                <label><?= __('Channel') ?></label>
                <select name="steps[${stepIndex}][channel]" class="form-control" required>
                    <option value="email"><?= __('Email') ?></option>
                    <option value="slack"><?= __('Slack') ?></option>
                    <option value="discord"><?= __('Discord') ?></option>
                    <option value="telegram"><?= __('Telegram') ?></option>
                    <option value="webhook"><?= __('Webhook') ?></option>
                    <option value="sms"><?= __('SMS') ?></option>
                </select>
            </div>
            <div class="form-group form-group-wide">
                <label><?= __('Recipients') ?></label>
                <input type="text" name="steps[${stepIndex}][recipients]" class="form-control" placeholder="<?= __('e.g. team@company.com, oncall@company.com') ?>" required>
                <small class="form-help"><?= __('Comma-separated list of recipients') ?></small>
            </div>
            <div class="form-group form-group-wide">
                <label><?= __('Custom message (optional)') ?></label>
                <input type="text" name="steps[${stepIndex}][message_template]" class="form-control" placeholder="<?= __('Optional custom message for this step') ?>">
            </div>
        </div>
    </div>`;

    container.insertAdjacentHTML('beforeend', html);
    stepIndex++;
    updateStepNumbers();
    updateRemoveButtons();
}

function removeStep(btn) {
    var row = btn.closest('.step-row');
    row.remove();
    updateStepNumbers();
    updateRemoveButtons();
}

function updateStepNumbers() {
    var rows = document.querySelectorAll('#steps-container .step-row');
    rows.forEach(function(row, index) {
        row.querySelector('.step-num').textContent = index + 1;
    });
}

function updateRemoveButtons() {
    var rows = document.querySelectorAll('#steps-container .step-row');
    rows.forEach(function(row) {
        var btn = row.querySelector('.remove-step-btn');
        btn.style.display = rows.length > 1 ? 'inline-block' : 'none';
    });
}

// Repeat toggle
document.getElementById('repeat-enabled').addEventListener('change', function() {
    document.getElementById('repeat-after-group').style.display = this.checked ? 'block' : 'none';
});

// Initialize
updateRemoveButtons();
</script>

<style>
.escalation-policies-form {
    max-width: 800px;
}
.escalation-policies-form .page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 24px;
}
.form-section {
    margin-bottom: 32px;
    padding-bottom: 32px;
    border-bottom: 1px solid var(--color-gray-light);
}
.form-section:last-of-type {
    border-bottom: none;
}
.form-section-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--color-dark);
    margin-bottom: 16px;
}
.form-group {
    margin-bottom: 16px;
}
.form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
    color: var(--color-dark);
}
.form-help {
    display: block;
    margin-top: 4px;
    font-size: 13px;
    color: var(--color-gray-medium);
}
.form-control {
    width: 100%;
    padding: 10px 12px;
    border: 2px solid var(--color-gray-light);
    border-radius: var(--radius-md);
    font-size: 14px;
    transition: border-color 0.2s;
}
.form-control:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(30, 136, 229, 0.1);
}
.form-control[type="checkbox"] {
    width: auto;
}
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
.form-actions {
    display: flex;
    gap: 12px;
    padding-top: 24px;
}
.step-row {
    background: var(--color-gray-light);
    border-radius: var(--radius-md);
    padding: 16px;
    margin-bottom: 12px;
    border-left: 4px solid var(--color-primary);
}
.step-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}
.step-number {
    font-weight: 700;
    font-size: 14px;
    color: var(--color-primary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.step-fields {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}
.step-fields .form-group-wide {
    grid-column: 1 / -1;
}
.step-fields .form-group {
    margin-bottom: 0;
}
#add-step-btn {
    margin-top: 8px;
}
@media (max-width: 768px) {
    .escalation-policies-form {
        max-width: 100%;
    }
    .form-row, .step-fields {
        grid-template-columns: 1fr;
    }
    .form-actions {
        flex-direction: column;
    }
    .form-actions .btn {
        width: 100%;
    }
}
</style>
