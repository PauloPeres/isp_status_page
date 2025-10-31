<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Monitor $monitor
 */
$this->assign('title', 'Novo Monitor');
?>

<div class="monitors-form">
    <div class="page-header">
        <div>
            <h1>➕ Novo Monitor</h1>
            <p>Configure um novo serviço para monitoramento</p>
        </div>
        <?= $this->Html->link(
            '← Voltar',
            ['action' => 'index'],
            ['class' => 'btn btn-secondary']
        ) ?>
    </div>

    <div class="card">
        <?= $this->Form->create($monitor) ?>

        <div class="form-section">
            <h3 class="form-section-title">Informações Básicas</h3>

            <?= $this->Form->control('name', [
                'label' => 'Nome do Monitor *',
                'placeholder' => 'Ex: Website Principal',
                'required' => true,
                'class' => 'form-control',
            ]) ?>

            <?= $this->Form->control('description', [
                'label' => 'Descrição',
                'placeholder' => 'Breve descrição do que está sendo monitorado',
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
                    'checked' => true,
                ]) ?>
            </div>
        </div>

        <div class="form-section">
            <h3 class="form-section-title">Configuração do Alvo</h3>

            <?= $this->Form->control('target', [
                'label' => 'Alvo *',
                'placeholder' => 'https://exemplo.com ou 192.168.1.1',
                'required' => true,
                'class' => 'form-control',
                'help' => 'URL completa para HTTP, hostname/IP para Ping e Port',
            ]) ?>

            <!-- HTTP Specific Fields -->
            <div id="http-fields" class="monitor-type-fields">
                <?= $this->Form->control('expected_status_code', [
                    'label' => 'Código HTTP Esperado',
                    'type' => 'number',
                    'default' => 200,
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
                    'default' => 30,
                    'min' => 10,
                    'max' => 3600,
                    'required' => true,
                    'class' => 'form-control',
                    'help' => 'Frequência de verificação (mínimo 10s)',
                ]) ?>

                <?= $this->Form->control('timeout', [
                    'label' => 'Timeout (segundos) *',
                    'type' => 'number',
                    'default' => 10,
                    'min' => 1,
                    'max' => 60,
                    'required' => true,
                    'class' => 'form-control',
                    'help' => 'Tempo máximo de espera',
                ]) ?>
            </div>
        </div>

        <div class="form-actions">
            <?= $this->Form->button('💾 Salvar Monitor', ['class' => 'btn btn-primary']) ?>
            <?= $this->Html->link(
                'Cancelar',
                ['action' => 'index'],
                ['class' => 'btn btn-secondary']
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

@media (max-width: 768px) {
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
