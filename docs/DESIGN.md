# Design System - ISP Status Page

Este documento define o design system e padrões visuais implementados no projeto ISP Status Page.

## Índice
- [Cores](#cores)
- [Tipografia](#tipografia)
- [Espaçamentos](#espaçamentos)
- [Botões](#botões)
- [Cards](#cards)
- [Filtros](#filtros)
- [Tabelas](#tabelas)
- [Badges](#badges)
- [Paginação](#paginação)
- [Responsividade](#responsividade)

---

## Cores

### Cores Principais
```css
Azul (Primário):    #3b82f6
Verde (Sucesso):    #22c55e
Vermelho (Erro):    #ef4444
Laranja (Warning):  #f59e0b
Roxo (Toggle):      #8b5cf6
```

### Cores Secundárias
```css
Cinza Escuro:       #333
Cinza Médio:        #666
Cinza Claro:        #999
Cinza Background:   #f8f9fa
```

### Cores de Borda
```css
Borda Principal:    #e0e0e0
Borda Clara:        #f0f0f0
Borda Input:        #d0d0d0
```

### Hover States
```css
Azul Hover:         #2563eb
Verde Hover:        #16a34a
Vermelho Hover:     #dc2626
Laranja Hover:      #d97706
Roxo Hover:         #7c3aed
Cinza Hover:        #4b5563
Background Hover:   #f8f9fa
```

---

## Tipografia

### Tamanhos de Fonte
```css
12px - Labels, badges, botões pequenos
13px - Descrições secundárias, texto auxiliar
14px - Corpo de texto padrão
16px - Títulos de seção
18px - Subtítulos
28px - Valores de estatísticas
```

### Pesos de Fonte
```css
400 - Normal (corpo de texto)
500 - Medium (botões, links importantes)
600 - Semibold (labels, títulos de card)
700 - Bold (valores de estatísticas)
```

### Text Transform
```css
uppercase - Labels de estatísticas, headers de tabela
capitalize - Badges, status
normal - Corpo de texto
```

---

## Espaçamentos

### Sistema de Espaçamento (múltiplos de 4px)
```css
4px  - Gap mínimo entre elementos inline
8px  - Gap entre botões, badges
12px - Padding interno de elementos pequenos
16px - Gap entre cards, padding de cards
20px - Padding de filtros
24px - Margin entre seções
32px - Margin bottom de headers
```

### Aplicações Comuns
```css
Gap entre botões:           8px
Padding de botões:          8px 16px (vertical horizontal)
Padding de cards:           16px, 20px, 24px
Margin bottom de sections:  24px
Grid gap:                   16px
```

---

## Botões

### ⚠️ REGRA IMPORTANTE: NUNCA USE ÍCONES EM BOTÕES

**Todos os botões devem usar APENAS TEXTO para manter consistência visual.**

❌ **Errado:**
```php
'👁️ Ver'
'✏️ Editar'
'🗑️ Excluir'
'← Voltar'
```

✅ **Correto:**
```php
'Ver'
'Editar'
'Excluir'
'Voltar'
```

### Botões de Ação em Tabelas
```css
.btn-action {
    padding: 4px 12px;
    border-radius: 4px;
    font-size: 12px;
    text-decoration: none;
    border: none;
    cursor: pointer;
    font-weight: 500;
    display: inline-block;
}
```

#### Variações de Cores
```css
/* Ver */
.btn-action-view {
    background: #3b82f6;
    color: white;
}
.btn-action-view:hover {
    background: #2563eb;
}

/* Editar */
.btn-action-edit {
    background: #f59e0b;
    color: white;
}
.btn-action-edit:hover {
    background: #d97706;
}

/* Resolver */
.btn-action-resolve {
    background: #22c55e;
    color: white;
}
.btn-action-resolve:hover {
    background: #16a34a;
}

/* Ativar/Desativar */
.btn-action-toggle {
    background: #8b5cf6;
    color: white;
}
.btn-action-toggle:hover {
    background: #7c3aed;
}

/* Excluir */
.btn-action-danger {
    background: #ef4444;
    color: white;
}
.btn-action-danger:hover {
    background: #dc2626;
}
```

### Botões Principais (Headers)
```css
.btn {
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    border: none;
    cursor: pointer;
    display: inline-block;
}

/* Botão Primário */
.btn-primary {
    background: #f59e0b;
    color: white;
}

/* Botão Secundário */
.btn-secondary {
    background: #6b7280;
    color: white;
}

/* Botão Sucesso */
.btn-success {
    background: #22c55e;
    color: white;
}
```

### Botões de Filtro
```css
.btn-filter {
    padding: 8px 16px;
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
}

.btn-clear {
    padding: 8px 16px;
    background: white;
    color: #666;
    border: 1px solid #d0d0d0;
    border-radius: 6px;
    font-size: 14px;
}
```

### Container de Botões
```css
.action-buttons {
    display: flex;
    gap: 4px;
    justify-content: flex-end;
}
```

---

## Cards

### Card Base
```css
.card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
```

### Cards de Estatísticas
```css
.stat-card-mini {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.stat-label {
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
    font-weight: 600;
    margin-bottom: 8px;
}

.stat-value {
    font-size: 28px;
    font-weight: bold;
    color: #333;
}

.stat-value.success { color: #22c55e; }
.stat-value.error { color: #ef4444; }
.stat-value.info { color: #3b82f6; }
.stat-value.warning { color: #f59e0b; }
```

### Grid de Estatísticas
```css
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
```

---

## Filtros

### Container de Filtros
```css
.filters-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 24px;
}

.filters-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 16px;
}
```

### Grupos de Filtro
```css
.filter-group {
    display: flex;
    flex-direction: column;
}

.filter-group label {
    font-size: 13px;
    font-weight: 600;
    color: #444;
    margin-bottom: 6px;
}

.filter-group input,
.filter-group select {
    padding: 8px 12px;
    border: 1px solid #d0d0d0;
    border-radius: 6px;
    font-size: 14px;
    background: white;
}
```

---

## Tabelas

### Container de Tabela
```css
.table-container {
    width: 100%;
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    overflow: hidden;
}

.table-container table {
    width: 100%;
    border-collapse: collapse;
}
```

### Headers de Tabela
```css
.table-container th {
    background: #f8f9fa;
    padding: 12px 16px;
    text-align: left;
    font-size: 13px;
    font-weight: 600;
    color: #666;
    text-transform: uppercase;
    border-bottom: 2px solid #e0e0e0;
}
```

### Células de Tabela
```css
.table-container td {
    padding: 12px 16px;
    border-bottom: 1px solid #f0f0f0;
    font-size: 14px;
    vertical-align: middle;
}

.table-container tr:last-child td {
    border-bottom: none;
}

.table-container tbody tr:hover {
    background: #f8f9fa;
}
```

---

## Badges

### Badge Base
```css
.badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    white-space: nowrap;
}
```

### Variações de Badge
```css
.badge-success {
    background: #dcfce7;
    color: #16a34a;
}

.badge-danger {
    background: #fee2e2;
    color: #dc2626;
}

.badge-warning {
    background: #fef3c7;
    color: #d97706;
}

.badge-info {
    background: #dbeafe;
    color: #1d4ed8;
}

.badge-secondary {
    background: #f3f4f6;
    color: #6b7280;
}
```

### Badge Large
```css
.badge-lg {
    font-size: 14px;
    padding: 8px 16px;
}
```

---

## Paginação

### Container de Paginação
```css
.pagination {
    margin-top: 24px;
    display: flex;
    justify-content: center;
    gap: 8px;
    flex-wrap: wrap;
}
```

### Links de Paginação
```css
.pagination a,
.pagination span {
    padding: 8px 12px;
    border: 1px solid #d0d0d0;
    border-radius: 4px;
    color: #666;
    text-decoration: none;
    font-size: 14px;
}

.pagination a:hover {
    background: #f8f9fa;
    border-color: #3b82f6;
    color: #3b82f6;
}

.pagination .active {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

.pagination .disabled {
    color: #ccc;
    cursor: not-allowed;
}
```

### Informação de Paginação
```css
.pagination-info {
    text-align: center;
    margin-top: 12px;
    font-size: 13px;
    color: #666;
}
```

---

## Border Radius

### Padrões de Border Radius
```css
4px  - Pequeno (botões de ação, inputs, badges pequenos)
6px  - Médio (botões principais, inputs)
8px  - Grande (cards, containers)
12px - Extra grande (badges)
50%  - Circular (indicadores de status, avatares)
```

---

## Sombras

### Box Shadows
```css
/* Sombra Suave (cards) */
box-shadow: 0 1px 3px rgba(0,0,0,0.05);

/* Sombra de Status (indicadores) */
box-shadow: 0 0 8px rgba(34, 197, 94, 0.4);  /* Verde */
box-shadow: 0 0 8px rgba(239, 68, 68, 0.4);  /* Vermelho */
```

---

## Responsividade

### Breakpoints
```css
768px  - Mobile (tablets)
992px  - Desktop pequeno
1200px - Desktop grande
```

### Media Queries Padrão
```css
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }

    .filters-row {
        grid-template-columns: 1fr;
    }

    .table-container {
        overflow-x: auto;
    }

    .action-buttons {
        flex-direction: column;
    }
}
```

---

## Estados Especiais

### Empty State
```css
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.empty-state-icon {
    font-size: 48px;
    margin-bottom: 16px;
}
```

### Status Indicators
```css
.status-indicator {
    display: inline-block;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin-right: 8px;
}

.status-up {
    background: #22c55e;
    box-shadow: 0 0 8px rgba(34, 197, 94, 0.4);
}

.status-down {
    background: #ef4444;
    box-shadow: 0 0 8px rgba(239, 68, 68, 0.4);
}

.status-unknown {
    background: #999;
}
```

### Timeline
```css
.timeline {
    position: relative;
    padding-left: 40px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e0e0e0;
}

.timeline-marker {
    position: absolute;
    left: -40px;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border: 3px solid #999;
    z-index: 1;
}
```

---

## Regras de Consistência

### 1. ⚠️ NUNCA use ícones em botões
❌ **Errado:** `'👁️ Ver'`, `'✏️ Editar'`, `'🗑️ Excluir'`, `'← Voltar'`
✅ **Correto:** `'Ver'`, `'Editar'`, `'Excluir'`, `'Voltar'`

### 2. ✅ SEMPRE use as cores padronizadas
- **Ver**: Azul (`#3b82f6`)
- **Editar**: Laranja (`#f59e0b`)
- **Resolver**: Verde (`#22c55e`)
- **Ativar/Desativar**: Roxo (`#8b5cf6`)
- **Excluir**: Vermelho (`#ef4444`)

### 3. ✅ SEMPRE use espaçamentos múltiplos de 4px
- Gap entre botões: `8px`
- Padding de cards: `16px`, `20px`, `24px`
- Margin entre seções: `24px`

### 4. ✅ SEMPRE use border-radius consistente
- Botões pequenos: `4px`
- Botões médios: `6px`
- Cards: `8px`
- Badges: `12px`

### 5. ✅ SEMPRE implemente hover states
Todos os elementos interativos devem ter estado hover com escurecimento da cor.

### 6. ✅ SEMPRE use CSS inline nos templates
Para facilitar manutenção, todo CSS deve estar inline no próprio template `.php`.

### 7. ✅ SEMPRE torne a UI responsiva
Todas as grids devem adaptar para `1fr` em mobile (`max-width: 768px`).

---

## Exemplos de Uso

### Botões de Ação (Tabelas)
```php
<div class="action-buttons">
    <?= $this->Html->link(
        'Ver',
        ['action' => 'view', $id],
        ['class' => 'btn-action btn-action-view']
    ) ?>
    <?= $this->Html->link(
        'Editar',
        ['action' => 'edit', $id],
        ['class' => 'btn-action btn-action-edit']
    ) ?>
    <?= $this->Form->postLink(
        'Excluir',
        ['action' => 'delete', $id],
        ['class' => 'btn-action btn-action-danger', 'confirm' => '...']
    ) ?>
</div>
```

### Cards de Estatísticas
```php
<div class="stats-grid">
    <div class="stat-card-mini">
        <div class="stat-label">Total</div>
        <div class="stat-value info"><?= number_format($total) ?></div>
    </div>
    <div class="stat-card-mini">
        <div class="stat-label">Ativos</div>
        <div class="stat-value success"><?= number_format($active) ?></div>
    </div>
</div>
```

### Filtros
```php
<div class="filters-card">
    <?= $this->Form->create(null, ['type' => 'get']) ?>
    <div class="filters-row">
        <div class="filter-group">
            <label>Status</label>
            <?= $this->Form->control('status', ['label' => false]) ?>
        </div>
        <!-- Mais filtros -->
    </div>
    <div class="filter-buttons">
        <?= $this->Form->button('Filtrar', ['class' => 'btn-filter']) ?>
        <?= $this->Html->link('Limpar', ['action' => 'index'], ['class' => 'btn-clear']) ?>
    </div>
    <?= $this->Form->end() ?>
</div>
```

---

## Checklist de Implementação

Ao criar uma nova view, certifique-se de:

- [ ] Usar CSS inline no template
- [ ] Implementar cards de estatísticas se aplicável
- [ ] Implementar filtros com layout em grid
- [ ] **Usar botões com texto (SEM ÍCONES)**
- [ ] Aplicar cores padronizadas aos botões
- [ ] Implementar hover states em todos os elementos interativos
- [ ] Adicionar paginação estilizada com contador
- [ ] Tornar a UI responsiva (mobile-first)
- [ ] Usar espaçamentos múltiplos de 4px
- [ ] Aplicar border-radius consistente
- [ ] Testar em mobile (< 768px)

---

**Última atualização**: 31/10/2025
**Versão**: 2.0.0
**Nota**: Este documento reflete o design system IMPLEMENTADO no código, não o planejado inicialmente.
