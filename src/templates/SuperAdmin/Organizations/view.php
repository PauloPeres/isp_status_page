<?php
/**
 * Super Admin — Organization Detail
 * TASK-SA-009
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Organization $org
 * @var \Cake\Collection\CollectionInterface $monitors
 * @var \Cake\Collection\CollectionInterface $recentChecks
 * @var \Cake\Collection\CollectionInterface $recentIncidents
 * @var \App\Model\Entity\NotificationCredit $credits
 * @var array $creditUsage
 * @var \Cake\Collection\CollectionInterface $creditTransactions
 */
$this->assign('title', h($org->name));
?>

<div class="dashboard-header">
    <div>
        <h1><?= h($org->name) ?></h1>
        <p><code><?= h($org->slug) ?></code></p>
    </div>
    <div class="header-actions">
        <?= $this->Form->postLink(
            __('Impersonate'),
            ['action' => 'impersonate', $org->id],
            [
                'class' => 'btn btn-warning',
                'confirm' => __('Impersonate organization "{0}"?', h($org->name)),
            ]
        ) ?>
        <?= $this->Html->link(
            __('Back to List'),
            ['action' => 'index'],
            ['class' => 'btn btn-secondary']
        ) ?>
    </div>
</div>

<!-- Organization Info Card -->
<div class="summary-grid">
    <div class="summary-card">
        <div class="card-label"><?= __('Plan') ?></div>
        <div class="card-value">
            <?php
            $planClass = match ($org->plan) {
                'business' => 'badge-info',
                'pro' => 'badge-success',
                default => 'badge-secondary',
            };
            ?>
            <span class="badge <?= $planClass ?>"><?= h(ucfirst($org->plan)) ?></span>
        </div>
    </div>
    <div class="summary-card">
        <div class="card-label"><?= __('Active') ?></div>
        <div class="card-value">
            <?php if ($org->active): ?>
                <span class="badge badge-success"><?= __('Yes') ?></span>
            <?php else: ?>
                <span class="badge badge-danger"><?= __('No') ?></span>
            <?php endif; ?>
        </div>
    </div>
    <div class="summary-card">
        <div class="card-label"><?= __('Monitors') ?></div>
        <div class="card-value" style="color: #3b82f6;"><?= $monitors->count() ?></div>
    </div>
    <div class="summary-card">
        <div class="card-label"><?= __('Team Members') ?></div>
        <div class="card-value total"><?= count($org->organization_users ?? []) ?></div>
    </div>
</div>

<!-- Org Details -->
<div class="tables-grid">
    <div class="table-card">
        <h3><?= __('Organization Details') ?></h3>
        <table class="admin-table detail-table">
            <tbody>
                <tr>
                    <th><?= __('ID') ?></th>
                    <td><?= h($org->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Name') ?></th>
                    <td><?= h($org->name) ?></td>
                </tr>
                <tr>
                    <th><?= __('Slug') ?></th>
                    <td><code><?= h($org->slug) ?></code></td>
                </tr>
                <tr>
                    <th><?= __('Plan') ?></th>
                    <td><span class="badge <?= $planClass ?>"><?= h(ucfirst($org->plan)) ?></span></td>
                </tr>
                <tr>
                    <th><?= __('Stripe Customer ID') ?></th>
                    <td><code><?= h($org->stripe_customer_id ?: '-') ?></code></td>
                </tr>
                <tr>
                    <th><?= __('Stripe Subscription ID') ?></th>
                    <td><code><?= h($org->stripe_subscription_id ?: '-') ?></code></td>
                </tr>
                <tr>
                    <th><?= __('Custom Domain') ?></th>
                    <td><?= h($org->custom_domain ?: '-') ?></td>
                </tr>
                <tr>
                    <th><?= __('Timezone') ?></th>
                    <td><?= h($org->timezone) ?></td>
                </tr>
                <tr>
                    <th><?= __('Language') ?></th>
                    <td><?= h($org->language) ?></td>
                </tr>
                <tr>
                    <th><?= __('Trial Ends At') ?></th>
                    <td>
                        <?php if ($org->trial_ends_at): ?>
                            <?= $org->trial_ends_at->nice() ?>
                            <?php if ($org->is_trial_active): ?>
                                <span class="badge badge-warning"><?= __('Active') ?></span>
                            <?php else: ?>
                                <span class="badge badge-secondary"><?= __('Expired') ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?= __('Created') ?></th>
                    <td>
                        <?= $org->created ? $org->created->nice() : '-' ?>
                    </td>
                </tr>
                <tr>
                    <th><?= __('Modified') ?></th>
                    <td>
                        <?= $org->modified ? $org->modified->nice() : '-' ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Notification Credits -->
<div class="table-card" style="margin-top: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h3 style="margin: 0;"><?= __('Notification Credits') ?></h3>
    </div>

    <div class="summary-grid" style="margin-bottom: 16px;">
        <div class="summary-card">
            <div class="card-label"><?= __('Current Balance') ?></div>
            <div class="card-value" style="color: #3b82f6; font-size: 1.5rem;"><?= h($credits->balance) ?></div>
        </div>
        <div class="summary-card">
            <div class="card-label"><?= __('Monthly Grant') ?></div>
            <div class="card-value"><?= h($credits->monthly_grant) ?></div>
        </div>
        <div class="summary-card">
            <div class="card-label"><?= __('Used This Month') ?></div>
            <div class="card-value" style="color: #ef4444;"><?= h($creditUsage['used'] ?? 0) ?></div>
        </div>
        <div class="summary-card">
            <div class="card-label"><?= __('Auto-Recharge') ?></div>
            <div class="card-value">
                <?php if ($credits->auto_recharge): ?>
                    <span class="badge badge-success"><?= __('On') ?></span>
                <?php else: ?>
                    <span class="badge badge-secondary"><?= __('Off') ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Grant Credits Form -->
    <div style="background: #f8fafc; border-radius: 8px; padding: 16px; margin-bottom: 16px;">
        <h4 style="margin-top: 0; margin-bottom: 12px;"><?= __('Grant Credits') ?></h4>
        <?= $this->Form->create(null, [
            'url' => ['action' => 'grantCredits', $org->id],
            'style' => 'display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;',
        ]) ?>
            <div style="flex: 0 0 120px;">
                <label style="display: block; font-size: 0.85rem; margin-bottom: 4px; color: #64748b;"><?= __('Amount') ?></label>
                <?= $this->Form->number('amount', [
                    'min' => 1,
                    'value' => 50,
                    'style' => 'width: 100%; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px;',
                    'required' => true,
                ]) ?>
            </div>
            <div style="flex: 1; min-width: 200px;">
                <label style="display: block; font-size: 0.85rem; margin-bottom: 4px; color: #64748b;"><?= __('Reason') ?></label>
                <?= $this->Form->text('reason', [
                    'value' => 'Manual grant by admin',
                    'style' => 'width: 100%; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px;',
                ]) ?>
            </div>
            <button type="submit" class="btn btn-primary" style="padding: 8px 20px; white-space: nowrap;"
                onclick="return confirm('<?= __('Grant credits to this organization?') ?>')">
                <?= __('Grant Credits') ?>
            </button>
        <?= $this->Form->end() ?>
    </div>

    <!-- Recent Credit Transactions -->
    <?php if (!$creditTransactions->isEmpty()): ?>
    <h4><?= __('Recent Credit Transactions') ?></h4>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th><?= __('Date') ?></th>
                    <th><?= __('Type') ?></th>
                    <th><?= __('Amount') ?></th>
                    <th><?= __('Balance After') ?></th>
                    <th><?= __('Channel') ?></th>
                    <th><?= __('Description') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($creditTransactions as $tx): ?>
                <tr>
                    <td>
                        <?php if ($tx->created): ?>
                            <span class="utc-datetime" data-utc="<?= $tx->created->format('c') ?>">
                                <?= $tx->created->nice() ?>
                            </span>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $typeBadge = match ($tx->type) {
                            'usage' => 'badge-danger',
                            'purchase' => 'badge-info',
                            'monthly_grant' => 'badge-success',
                            'manual_adjustment' => 'badge-warning',
                            'refund' => 'badge-secondary',
                            default => 'badge-secondary',
                        };
                        ?>
                        <span class="badge <?= $typeBadge ?>"><?= h(ucfirst(str_replace('_', ' ', $tx->type))) ?></span>
                    </td>
                    <td>
                        <span style="color: <?= $tx->amount >= 0 ? '#22c55e' : '#ef4444' ?>; font-weight: 600;">
                            <?= $tx->amount >= 0 ? '+' : '' ?><?= h($tx->amount) ?>
                        </span>
                    </td>
                    <td><?= h($tx->balance_after) ?></td>
                    <td><?= $tx->channel ? h(ucfirst($tx->channel)) : '-' ?></td>
                    <td><?= h($tx->description ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <p style="color: #94a3b8; text-align: center; padding: 16px;"><?= __('No credit transactions yet.') ?></p>
    <?php endif; ?>
</div>

<!-- Team Members -->
<div class="table-card" style="margin-top: 24px;">
    <h3><?= __('Team Members') ?></h3>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th><?= __('Username') ?></th>
                    <th><?= __('Email') ?></th>
                    <th><?= __('Role') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($org->organization_users)): ?>
                <tr>
                    <td colspan="3" style="text-align: center; padding: 16px; color: #94a3b8;">
                        <?= __('No team members.') ?>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($org->organization_users as $orgUser): ?>
                    <tr>
                        <td><?= h($orgUser->user->username ?? '-') ?></td>
                        <td><?= h($orgUser->user->email ?? '-') ?></td>
                        <td>
                            <?php
                            $roleClass = match ($orgUser->role ?? '') {
                                'owner' => 'badge-danger',
                                'admin' => 'badge-warning',
                                'member' => 'badge-success',
                                default => 'badge-secondary',
                            };
                            ?>
                            <span class="badge <?= $roleClass ?>"><?= h(ucfirst($orgUser->role ?? 'member')) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Monitors -->
<div class="table-card" style="margin-top: 24px;">
    <h3><?= __('Monitors') ?></h3>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th><?= __('Name') ?></th>
                    <th><?= __('Type') ?></th>
                    <th><?= __('Status') ?></th>
                    <th><?= __('Last Check') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($monitors->count() === 0): ?>
                <tr>
                    <td colspan="4" style="text-align: center; padding: 16px; color: #94a3b8;">
                        <?= __('No monitors configured.') ?>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($monitors as $monitor): ?>
                    <tr>
                        <td><strong><?= h($monitor->name) ?></strong></td>
                        <td><span class="badge badge-secondary"><?= h(strtoupper($monitor->type ?? '-')) ?></span></td>
                        <td>
                            <?php
                            $statusClass = match ($monitor->status ?? 'unknown') {
                                'up' => 'badge-success',
                                'down' => 'badge-danger',
                                'degraded' => 'badge-warning',
                                default => 'badge-secondary',
                            };
                            ?>
                            <span class="badge <?= $statusClass ?>"><?= h(ucfirst($monitor->status ?? 'unknown')) ?></span>
                        </td>
                        <td>
                            <?php if (!empty($monitor->last_checked_at)): ?>
                                <span class="utc-datetime" data-utc="<?= $monitor->last_checked_at->format('c') ?>">
                                    <?= $monitor->last_checked_at->nice() ?>
                                </span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Recent Activity -->
<div class="tables-grid" style="margin-top: 24px;">
    <!-- Recent Checks -->
    <div class="table-card">
        <h3><?= __('Recent Checks') ?></h3>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th><?= __('Status') ?></th>
                        <th><?= __('Response Time') ?></th>
                        <th><?= __('Checked At') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recentChecks->count() === 0): ?>
                    <tr>
                        <td colspan="3" style="text-align: center; padding: 16px; color: #94a3b8;">
                            <?= __('No checks recorded.') ?>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($recentChecks as $check): ?>
                        <tr>
                            <td>
                                <?php
                                $checkClass = match ($check->status ?? 'unknown') {
                                    'up' => 'badge-success',
                                    'down' => 'badge-danger',
                                    'degraded' => 'badge-warning',
                                    default => 'badge-secondary',
                                };
                                ?>
                                <span class="badge <?= $checkClass ?>"><?= h(ucfirst($check->status ?? 'unknown')) ?></span>
                            </td>
                            <td><?= $check->response_time !== null ? number_format($check->response_time, 0) . ' ms' : '-' ?></td>
                            <td>
                                <span class="utc-datetime" data-utc="<?= $check->checked_at ? $check->checked_at->format('c') : '' ?>">
                                    <?= $check->checked_at ? $check->checked_at->nice() : '-' ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Incidents -->
    <div class="table-card">
        <h3><?= __('Recent Incidents') ?></h3>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th><?= __('Title') ?></th>
                        <th><?= __('Status') ?></th>
                        <th><?= __('Created') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recentIncidents->count() === 0): ?>
                    <tr>
                        <td colspan="3" style="text-align: center; padding: 16px; color: #94a3b8;">
                            <?= __('No incidents recorded.') ?>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($recentIncidents as $incident): ?>
                        <tr>
                            <td><strong><?= h($incident->title ?? '-') ?></strong></td>
                            <td>
                                <?php
                                $incClass = match ($incident->status ?? '') {
                                    'resolved' => 'badge-success',
                                    'investigating' => 'badge-danger',
                                    'monitoring' => 'badge-warning',
                                    default => 'badge-secondary',
                                };
                                ?>
                                <span class="badge <?= $incClass ?>"><?= h(ucfirst($incident->status ?? 'unknown')) ?></span>
                            </td>
                            <td>
                                <span class="utc-datetime" data-utc="<?= $incident->created ? $incident->created->format('c') : '' ?>">
                                    <?= $incident->created ? $incident->created->nice() : '-' ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
