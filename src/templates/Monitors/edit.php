<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Monitor $monitor
 */
$this->assign('title', 'Editar Monitor');
?>

<div class="monitors-form">
    <div class="page-header">
        <div>
            <h1>✏️ Editar Monitor</h1>
            <p>Atualize as configurações do monitor: <strong><?= h($monitor->name) ?></strong></p>
        </div>
        <div style="display: flex; gap: 8px;">
            <?= $this->Html->link(
                '👁️ Ver Detalhes',
                ['action' => 'view', $monitor->id],
                ['class' => 'btn btn-secondary']
            ) ?>
            <?= $this->Html->link(
                '← Voltar',
                ['action' => 'index'],
                ['class' => 'btn btn-secondary']
            ) ?>
        </div>
    </div>

    <div class="card">
        <?= $this->Form->create($monitor) ?>

        <div class="form-section">
            <h3 class="form-section-title">Informações Básicas</h3>

            <?= $this->Form->control('name', [
                'label' => 'Nome do Monitor *',
                'required' => true,
                'class' => 'form-control',
            ]) ?>

            <?= $this->Form->control('description', [
                'label' => 'Descrição',
                'type' => 'textarea',
                'rows' => 3,
                'class' => 'form-control',
            ]) ?>

            <div class="form-row">
                <?= $this->Form->control('type', [
                    'label' => 'Tipo de Monitor *',
                    'options' => [
                        'http' => 'HTTP/HTTPS',
                        'ping' => 'Ping (ICMP)',
                        'port' => 'Porta (TCP/UDP)',
                    ],
                    'required' => true,
                    'class' => 'form-control',
                    'id' => 'monitor-type',
                ]) ?>

                <?= $this->Form->control('active', [
                    'label' => 'Ativo',
                    'type' => 'checkbox',
                ]) ?>
            </div>
        </div>

        <div class="form-section">
            <h3 class="form-section-title">Configuração do Alvo</h3>

            <?= $this->Form->control('target', [
                'label' => 'Alvo *',
                'required' => true,
                'class' => 'form-control',
                'help' => 'URL completa para HTTP, hostname/IP para Ping e Port',
            ]) ?>

            <!-- HTTP Specific Fields -->
            <div id="http-fields" class="monitor-type-fields">
                <?= $this->Form->control('expected_status_code', [
                    'label' => 'Código HTTP Esperado',
                    'type' => 'number',
                    'class' => 'form-control',
                    'help' => 'Código de status HTTP esperado (ex: 200, 301)',
                ]) ?>
            </div>

            <!-- Port Specific Fields -->
            <div id="port-fields" class="monitor-type-fields" style="display:none;">
                <?= $this->Form->control('port', [
                    'label' => 'Porta',
                    'type' => 'number',
                    'min' => 1,
                    'max' => 65535,
                    'class' => 'form-control',
                    'help' => 'Número da porta TCP/UDP (1-65535)',
                ]) ?>
            </div>
        </div>

        <div class="form-section">
            <h3 class="form-section-title">Configurações de Verificação</h3>

            <div class="form-row">
                <?= $this->Form->control('interval', [
                    'label' => 'Intervalo (segundos) *',
                    'type' => 'number',
                    'min' => 10,
                    'max' => 3600,
                    'required' => true,
                    'class' => 'form-control',
                    'help' => 'Frequência de verificação (mínimo 10s)',
                ]) ?>

                <?= $this->Form->control('timeout', [
                    'label' => 'Timeout (segundos) *',
                    'type' => 'number',
                    'min' => 1,
                    'max' => 60,
                    'required' => true,
                    'class' => 'form-control',
                    'help' => 'Tempo máximo de espera',
                ]) ?>
            </div>
        </div>

        <div class="form-section">
            <h3 class="form-section-title">Informações do Sistema</h3>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Status Atual:</span>
                    <span class="badge badge-<?= $monitor->status === 'up' ? 'success' : ($monitor->status === 'down' ? 'error' : 'info') ?>">
                        <?= h(ucfirst($monitor->status)) ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Última Verificação:</span>
                    <span><?= $monitor->last_check ? $monitor->last_check->format('d/m/Y H:i:s') : 'Nunca' ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Criado em:</span>
                    <span><?= $monitor->created->format('d/m/Y H:i:s') ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Última Atualização:</span>
                    <span><?= $monitor->modified->format('d/m/Y H:i:s') ?></span>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <?= $this->Form->button('💾 Salvar Alterações', ['class' => 'btn btn-primary']) ?>
            <?= $this->Html->link(
                'Cancelar',
                ['action' => 'index'],
                ['class' => 'btn btn-secondary']
            ) ?>
            <?= $this->Form->postLink(
                '🗑️ Excluir',
                ['action' => 'delete', $monitor->id],
                [
                    'class' => 'btn btn-error',
                    'confirm' => 'Tem certeza que deseja excluir este monitor? Esta ação não pode ser desfeita.'
                ]
            ) ?>
        </div>

        <?= $this->Form->end() ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('monitor-type');
    const httpFields = document.getElementById('http-fields');
    const portFields = document.getElementById('port-fields');

    function updateFields() {
        const type = typeSelect.value;

        // Hide all type-specific fields
        httpFields.style.display = 'none';
        portFields.style.display = 'none';

        // Show relevant fields
        if (type === 'http') {
            httpFields.style.display = 'block';
        } else if (type === 'port') {
            portFields.style.display = 'block';
        }
    }

    typeSelect.addEventListener('change', updateFields);
    updateFields(); // Initial call
});
</script>

<style>
.monitors-form {
    max-width: 800px;
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

.form-control {
    width: 100%;
    padding: 12px;
    border: 2px solid var(--color-gray-light);
    border-radius: var(--radius-md);
    font-size: 15px;
    transition: all 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(30, 136, 229, 0.1);
}

.form-control[type="checkbox"] {
    width: auto;
    margin-top: 8px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.form-actions {
    display: flex;
    gap: 16px;
    padding-top: 24px;
}

.monitor-type-fields {
    margin-top: 16px;
    padding: 16px;
    background: var(--color-gray-light);
    border-radius: var(--radius-md);
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    padding: 16px;
    background: var(--color-gray-light);
    border-radius: var(--radius-md);
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
    .page-header {
        flex-direction: column;
    }

    .page-header > div {
        width: 100%;
    }

    .form-row {
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
