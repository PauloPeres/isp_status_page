<?php
/**
 * @var \App\View\AppView $this
 * @var array $groupedIncidents
 */
use Cake\Core\Configure;

$siteName = Configure::read('Settings.site_name', 'ISP Status');
$this->assign('title', __('Histórico de Incidentes'));
?>

<style>
    .history-header {
        text-align: center;
        padding: 40px 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px;
        margin-bottom: 40px;
    }

    .history-header h1 {
        margin: 0 0 10px 0;
        font-size: 32px;
        font-weight: 700;
    }

    .history-header p {
        margin: 0;
        font-size: 16px;
        opacity: 0.95;
    }

    .timeline {
        position: relative;
        max-width: 900px;
        margin: 0 auto;
    }

    .timeline::before {
        content: '';
        position: absolute;
        left: 30px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: linear-gradient(to bottom, #667eea, #764ba2);
    }

    .timeline-date {
        margin-bottom: 30px;
        position: relative;
    }

    .date-badge {
        display: inline-block;
        background: white;
        padding: 8px 20px;
        border-radius: 20px;
        font-weight: 600;
        color: #667eea;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        margin-left: 70px;
        margin-bottom: 15px;
        font-size: 14px;
        border: 2px solid #667eea;
    }

    .incident-item {
        position: relative;
        margin-left: 70px;
        margin-bottom: 20px;
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        border-left: 4px solid #667eea;
        transition: all 0.3s ease;
    }

    .incident-item:hover {
        transform: translateX(5px);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
    }

    .incident-item::before {
        content: '';
        position: absolute;
        left: -57px;
        top: 25px;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: white;
        border: 3px solid #667eea;
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.2);
    }

    .incident-item.resolved::before {
        background: #10b981;
        border-color: #10b981;
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.2);
    }

    .incident-item.resolved {
        border-left-color: #10b981;
        opacity: 0.85;
    }

    .incident-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 12px;
        gap: 15px;
    }

    .incident-title {
        flex: 1;
        font-size: 18px;
        font-weight: 600;
        color: #1f2937;
        margin: 0;
    }

    .incident-badges {
        display: flex;
        gap: 8px;
        flex-shrink: 0;
    }

    .badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
    }

    .badge-resolved {
        background: #d1fae5;
        color: #065f46;
    }

    .badge-investigating {
        background: #fef3c7;
        color: #92400e;
    }

    .badge-ongoing {
        background: #fee2e2;
        color: #991b1b;
    }

    .badge-major {
        background: #fee2e2;
        color: #991b1b;
    }

    .badge-minor {
        background: #fef3c7;
        color: #92400e;
    }

    .incident-description {
        color: #6b7280;
        font-size: 14px;
        line-height: 1.6;
        margin-bottom: 12px;
    }

    .incident-meta {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
        font-size: 13px;
        color: #9ca3af;
    }

    .meta-item {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .meta-item strong {
        color: #6b7280;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    }

    .empty-state-icon {
        font-size: 64px;
        margin-bottom: 20px;
        opacity: 0.5;
    }

    .empty-state h3 {
        margin: 0 0 10px 0;
        font-size: 20px;
        color: #1f2937;
    }

    .empty-state p {
        margin: 0;
        color: #6b7280;
        font-size: 14px;
    }

    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background: white;
        color: #667eea;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transition: all 0.2s;
        margin-bottom: 30px;
    }

    .back-link:hover {
        background: #667eea;
        color: white;
        transform: translateX(-5px);
    }

    @media (max-width: 768px) {
        .history-header h1 {
            font-size: 24px;
        }

        .timeline::before {
            left: 20px;
        }

        .date-badge {
            margin-left: 50px;
            font-size: 13px;
            padding: 6px 15px;
        }

        .incident-item {
            margin-left: 50px;
            padding: 15px;
        }

        .incident-item::before {
            left: -43px;
            width: 12px;
            height: 12px;
        }

        .incident-header {
            flex-direction: column;
            gap: 10px;
        }

        .incident-badges {
            align-self: flex-start;
        }

        .incident-title {
            font-size: 16px;
        }

        .incident-meta {
            flex-direction: column;
            gap: 8px;
        }
    }
</style>

<div class="container">
    <?= $this->Html->link(
        '← ' . __('Voltar para Status'),
        ['controller' => 'Status', 'action' => 'index'],
        ['class' => 'back-link']
    ) ?>

    <div class="history-header">
        <h1>📜 <?= __('Histórico de Incidentes') ?></h1>
        <p><?= __('Últimos 30 dias de incidentes reportados') ?></p>
    </div>

    <div class="timeline">
        <?php if (empty($groupedIncidents)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">🎉</div>
                <h3><?= __('Nenhum incidente registrado') ?></h3>
                <p><?= __('Não houve incidentes nos últimos 30 dias. Todos os sistemas estão operando normalmente!') ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($groupedIncidents as $date => $incidents): ?>
                <div class="timeline-date">
                    <div class="date-badge">
                        📅 <?= date('d/m/Y - l', strtotime($date)) ?>
                    </div>

                    <?php foreach ($incidents as $incident): ?>
                        <?php
                        $isResolved = $incident->status === 'resolved';
                        $statusClass = match($incident->status) {
                            'resolved' => 'resolved',
                            'investigating' => 'investigating',
                            default => 'ongoing'
                        };
                        $statusLabel = match($incident->status) {
                            'resolved' => '✅ Resolvido',
                            'investigating' => '🔍 Investigando',
                            'identified' => '🔍 Identificado',
                            default => '🚨 Em andamento'
                        };
                        $severityClass = match($incident->severity) {
                            'critical' => 'major',
                            'major' => 'major',
                            'minor' => 'minor',
                            default => 'minor'
                        };
                        $severityLabel = match($incident->severity) {
                            'critical' => 'Crítico',
                            'major' => 'Importante',
                            'minor' => 'Menor',
                            default => 'Manutenção'
                        };

                        // Calculate duration
                        $duration = '';
                        if ($incident->resolved_at) {
                            $start = $incident->started_at ?: $incident->created;
                            $end = $incident->resolved_at;
                            $diff = $start->diff($end);

                            if ($diff->d > 0) {
                                $duration = $diff->d . 'd ' . $diff->h . 'h';
                            } elseif ($diff->h > 0) {
                                $duration = $diff->h . 'h ' . $diff->i . 'm';
                            } else {
                                $duration = $diff->i . 'm';
                            }
                        }
                        ?>
                        <div class="incident-item <?= $statusClass ?>">
                            <div class="incident-header">
                                <h3 class="incident-title"><?= h($incident->title) ?></h3>
                                <div class="incident-badges">
                                    <span class="badge badge-<?= $statusClass ?>">
                                        <?= $statusLabel ?>
                                    </span>
                                    <span class="badge badge-<?= $severityClass ?>">
                                        <?= $severityLabel ?>
                                    </span>
                                </div>
                            </div>

                            <?php if ($incident->description): ?>
                                <p class="incident-description"><?= h($incident->description) ?></p>
                            <?php endif; ?>

                            <div class="incident-meta">
                                <div class="meta-item">
                                    <strong><?= __('Início:') ?></strong>
                                    <span><?= $incident->created->format('d/m/Y H:i') ?></span>
                                </div>

                                <?php if ($incident->resolved_at): ?>
                                    <div class="meta-item">
                                        <strong><?= __('Resolvido:') ?></strong>
                                        <span><?= $incident->resolved_at->format('d/m/Y H:i') ?></span>
                                    </div>
                                    <?php if ($duration): ?>
                                        <div class="meta-item">
                                            <strong><?= __('Duração:') ?></strong>
                                            <span><?= $duration ?></span>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if ($incident->auto_created): ?>
                                    <div class="meta-item">
                                        <span>🤖 <?= __('Auto-criado') ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
