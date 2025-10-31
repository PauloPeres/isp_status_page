# Guia de Design - ISP Status Page

Este documento define os padrões visuais, paleta de cores e diretrizes de interface para o ISP Status Page.

## 🎨 Paleta de Cores

A paleta foi criada para manter consistência visual, boa legibilidade e profissionalismo, ideal para dashboards, páginas de status e interfaces web modernas.

### 1. Cores Primárias

| Cor | Hex | RGB | Uso |
|-----|-----|-----|-----|
| **Azul Principal** | `#1E88E5` | `rgb(30, 136, 229)` | Cabeçalhos, botões principais, links |
| **Verde Sucesso** | `#43A047` | `rgb(67, 160, 71)` | Status "Online", sucesso em verificações |
| **Cinza Escuro** | `#263238` | `rgb(38, 50, 56)` | Texto principal e títulos |
| **Branco** | `#FFFFFF` | `rgb(255, 255, 255)` | Fundo principal, contraste e limpeza visual |

### 2. Cores Secundárias

| Cor | Hex | RGB | Uso |
|-----|-----|-----|-----|
| **Azul Claro** | `#90CAF9` | `rgb(144, 202, 249)` | Realces, hover states, gráficos secundários |
| **Amarelo Alerta** | `#FDD835` | `rgb(253, 216, 53)` | Avisos, "Degradação parcial" |
| **Vermelho Erro** | `#E53935` | `rgb(229, 57, 53)` | Status "Offline", falhas críticas |

### 3. Tons Neutros

| Cor | Hex | RGB | Uso |
|-----|-----|-----|-----|
| **Cinza Claro** | `#ECEFF1` | `rgb(236, 239, 241)` | Fundo de painéis, bordas sutis |
| **Cinza Médio** | `#B0BEC5` | `rgb(176, 190, 197)` | Ícones, divisores, textos secundários |

---

## 🧭 Aplicação Visual

### Navbar e Header
- **Background**: Azul `#1E88E5`
- **Texto**: Branco `#FFFFFF`
- **Logo**: Branco ou transparente

### Dashboard Cards
- **Fundo**: Branco `#FFFFFF`
- **Bordas**: Cinza Claro `#ECEFF1`
- **Sombras**: `rgba(0, 0, 0, 0.08)` para depth suave

### Indicadores de Status

| Status | Cor | Hex | Ícone |
|--------|-----|-----|-------|
| 🟢 **Online** | Verde | `#43A047` | Círculo preenchido |
| 🟡 **Degradado** | Amarelo | `#FDD835` | Círculo com alerta |
| 🔴 **Offline** | Vermelho | `#E53935` | Círculo com X |
| ⚪ **Desconhecido** | Cinza Médio | `#B0BEC5` | Círculo vazio |

### Botões

#### Botão Primário
```css
background: #1E88E5;
color: #FFFFFF;
border: none;
hover: #1976D2;
```

#### Botão Sucesso
```css
background: #43A047;
color: #FFFFFF;
border: none;
hover: #388E3C;
```

#### Botão Erro
```css
background: #E53935;
color: #FFFFFF;
border: none;
hover: #D32F2F;
```

#### Botão Secundário
```css
background: transparent;
color: #1E88E5;
border: 2px solid #1E88E5;
hover: background #ECEFF1;
```

### Alertas e Notificações

#### Sucesso
```css
background: #E8F5E9;
color: #2E7D32;
border-left: 4px solid #43A047;
```

#### Aviso
```css
background: #FFFDE7;
color: #F57F17;
border-left: 4px solid #FDD835;
```

#### Erro
```css
background: #FFEBEE;
color: #C62828;
border-left: 4px solid #E53935;
```

#### Informação
```css
background: #E3F2FD;
color: #1565C0;
border-left: 4px solid #1E88E5;
```

---

## 📊 Gráficos e Visualizações

### Uptime Chart
- **90-100%**: Verde `#43A047`
- **70-89%**: Amarelo `#FDD835`
- **0-69%**: Vermelho `#E53935`

### Response Time Chart
- **Linha**: Azul `#1E88E5`
- **Área**: Azul Claro `#90CAF9` com opacidade 0.3
- **Grid**: Cinza Claro `#ECEFF1`

---

## 🔤 Typography

### Fontes
```css
font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto,
             Oxygen, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
```

### Tamanhos e Pesos

| Elemento | Tamanho | Peso | Cor |
|----------|---------|------|-----|
| H1 (Page Title) | 32px | 700 (Bold) | `#263238` |
| H2 (Section) | 24px | 600 (SemiBold) | `#263238` |
| H3 (Subsection) | 20px | 600 (SemiBold) | `#263238` |
| Body Text | 15px | 400 (Regular) | `#263238` |
| Small Text | 13px | 400 (Regular) | `#B0BEC5` |
| Label | 14px | 600 (SemiBold) | `#263238` |

---

## 🎯 Espaçamento e Grid

### Sistema de Espaçamento
Baseado em múltiplos de 8px:

| Nome | Valor | Uso |
|------|-------|-----|
| `xs` | 4px | Espaçamento mínimo |
| `sm` | 8px | Elementos próximos |
| `md` | 16px | Padrão entre elementos |
| `lg` | 24px | Entre seções |
| `xl` | 32px | Entre blocos principais |
| `2xl` | 48px | Separação máxima |

### Border Radius
- **Pequeno**: 4px (badges, tags)
- **Médio**: 8px (inputs, botões)
- **Grande**: 12px (cards, modais)
- **Extra Grande**: 20px (elementos destacados)

---

## 📱 Responsividade

### Breakpoints
```css
/* Mobile */
@media (max-width: 640px)

/* Tablet */
@media (min-width: 641px) and (max-width: 1024px)

/* Desktop */
@media (min-width: 1025px)
```

### Container Max-Width
- Mobile: 100% (com padding 20px)
- Tablet: 720px
- Desktop: 1140px

---

## ✨ Efeitos e Animações

### Transitions
```css
transition: all 0.3s ease;
```

### Hover States
- **Elevação**: `transform: translateY(-2px)`
- **Sombra**: `box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15)`

### Focus States
```css
outline: none;
border-color: #1E88E5;
box-shadow: 0 0 0 3px rgba(30, 136, 229, 0.1);
```

---

## 🖼️ Exemplos de Componentes

### Status Card
```html
<div style="
    background: #FFFFFF;
    border: 1px solid #ECEFF1;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
">
    <h3 style="color: #263238;">Monitor Name</h3>
    <span style="
        display: inline-block;
        background: #43A047;
        color: #FFFFFF;
        padding: 4px 12px;
        border-radius: 4px;
        font-size: 13px;
    ">Online</span>
</div>
```

### Monitor List Item
```html
<div style="
    background: #FFFFFF;
    border-left: 4px solid #43A047;
    padding: 16px;
    margin-bottom: 8px;
    border-radius: 8px;
">
    <span style="color: #263238; font-weight: 600;">Service Name</span>
    <span style="color: #B0BEC5; font-size: 13px;">Last check: 2 min ago</span>
</div>
```

---

## 📋 Checklist de Implementação

Ao criar novos componentes, certifique-se de:

- [ ] Usar cores da paleta oficial
- [ ] Aplicar espaçamento consistente (múltiplos de 8px)
- [ ] Incluir estados de hover/focus/active
- [ ] Testar em mobile, tablet e desktop
- [ ] Verificar contraste de cores (WCAG AA mínimo)
- [ ] Usar border-radius consistente
- [ ] Aplicar sombras sutis quando apropriado
- [ ] Garantir texto legível em todos os fundos

---

## 🔗 Referências CSS

### Variáveis CSS Recomendadas

```css
:root {
    /* Cores Primárias */
    --color-primary: #1E88E5;
    --color-success: #43A047;
    --color-dark: #263238;
    --color-white: #FFFFFF;

    /* Cores Secundárias */
    --color-primary-light: #90CAF9;
    --color-warning: #FDD835;
    --color-error: #E53935;

    /* Tons Neutros */
    --color-gray-light: #ECEFF1;
    --color-gray-medium: #B0BEC5;

    /* Hover States */
    --color-primary-hover: #1976D2;
    --color-success-hover: #388E3C;
    --color-error-hover: #D32F2F;

    /* Espaçamento */
    --space-xs: 4px;
    --space-sm: 8px;
    --space-md: 16px;
    --space-lg: 24px;
    --space-xl: 32px;
    --space-2xl: 48px;

    /* Border Radius */
    --radius-sm: 4px;
    --radius-md: 8px;
    --radius-lg: 12px;
    --radius-xl: 20px;

    /* Sombras */
    --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.08);
    --shadow-md: 0 2px 8px rgba(0, 0, 0, 0.08);
    --shadow-lg: 0 4px 12px rgba(0, 0, 0, 0.15);
}
```

---

**Última atualização**: 31 de Outubro de 2025
**Versão**: 1.0
