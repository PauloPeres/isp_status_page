# Plano de Internacionalização (i18n)

**Data**: 14/11/2024
**Idioma Padrão**: Português Brasil (pt_BR)
**Idiomas Planejados**: EN, ES (futuro)

---

## 1. Configuração Base

### 1.1 Estrutura de Arquivos
```
src/Locale/
├── pt_BR/
│   ├── default.po          # Traduções gerais do sistema
│   ├── admin.po            # Painel administrativo
│   ├── monitors.po         # Monitores
│   ├── incidents.po        # Incidentes
│   ├── checks.po           # Verificações
│   ├── subscribers.po      # Assinantes
│   ├── users.po            # Usuários
│   ├── settings.po         # Configurações
│   ├── emails.po           # E-mails
│   └── validation.po       # Mensagens de validação
├── en/                     # (futuro)
└── es/                     # (futuro)
```

### 1.2 Configuração CakePHP
- `config/app.php`: Definir locale padrão como `pt_BR`
- `config/bootstrap.php`: Carregar plugin I18n
- Configurar timezone para `America/Sao_Paulo`

---

## 2. Mapeamento de Páginas (38 templates)

### 2.1 Admin (1 template)
- [x] `Admin/index.php` - Dashboard principal

### 2.2 Monitors (6 templates)
- [ ] `Monitors/index.php` - Listagem de monitores
- [ ] `Monitors/view.php` - Visualizar monitor
- [ ] `Monitors/add.php` - Adicionar monitor
- [ ] `Monitors/edit.php` - Editar monitor

### 2.3 Incidents (5 templates)
- [ ] `Incidents/index.php` - Listagem de incidentes
- [ ] `Incidents/view.php` - Visualizar incidente
- [ ] `Incidents/add.php` - Adicionar incidente
- [ ] `Incidents/edit.php` - Editar incidente

### 2.4 Checks (2 templates)
- [ ] `Checks/index.php` - Listagem de verificações
- [ ] `Checks/view.php` - Visualizar verificação

### 2.5 Subscribers (6 templates)
- [ ] `Subscribers/index.php` - Listagem de assinantes
- [ ] `Subscribers/view.php` - Visualizar assinante
- [ ] `Subscribers/add.php` - Adicionar assinante
- [ ] `Subscribers/subscribe.php` - Formulário de inscrição
- [ ] `Subscribers/verify.php` - Verificar email
- [ ] `Subscribers/unsubscribe.php` - Cancelar inscrição

### 2.6 Users (4 templates)
- [ ] `Users/index.php` - Listagem de usuários
- [ ] `Users/view.php` - Visualizar perfil
- [ ] `Users/add.php` - Adicionar usuário
- [ ] `Users/edit.php` - Editar perfil
- [ ] `Users/login.php` - Tela de login

### 2.7 Settings (2 templates)
- [ ] `Settings/index.php` - Configurações gerais
- [ ] `Settings/edit.php` - Editar configurações

### 2.8 Status (1 template)
- [ ] `Status/index.php` - Página pública de status

### 2.9 EmailLogs (2 templates)
- [ ] `EmailLogs/index.php` - Listagem de logs de email
- [ ] `EmailLogs/view.php` - Visualizar log de email

### 2.10 Layout e Elements (9+ templates)
- [ ] `layout/admin.php` - Layout administrativo
- [ ] `layout/default.php` - Layout padrão
- [ ] `layout/public.php` - Layout público
- [ ] `element/admin/navbar.php` - Barra de navegação
- [ ] `element/admin/sidebar.php` - Menu lateral
- [ ] `element/admin/footer.php` - Rodapé
- [ ] `element/flash/error.php` - Mensagem de erro
- [ ] `element/flash/success.php` - Mensagem de sucesso

### 2.11 Emails (templates de email)
- [ ] `email/html/incident_notification.php`
- [ ] `email/html/subscriber_verification.php`
- [ ] `email/html/monitor_down.php`
- [ ] `email/html/monitor_up.php`

### 2.12 Error (páginas de erro)
- [ ] `Error/error400.php` - Bad Request
- [ ] `Error/error404.php` - Não encontrado
- [ ] `Error/error500.php` - Erro interno

---

## 3. Categorias de Tradução

### 3.1 Elementos de Interface
```php
// Botões
__('Adicionar')
__('Editar')
__('Excluir')
__('Salvar')
__('Cancelar')
__('Voltar')
__('Ver')
__('Filtrar')
__('Limpar')

// Navegação
__('Dashboard')
__('Monitores')
__('Incidentes')
__('Verificações')
__('Assinantes')
__('Usuários')
__('Configurações')
__('Sair')

// Status
__('Ativo')
__('Inativo')
__('Online')
__('Offline')
__('Resolvido')
__('Em Andamento')
```

### 3.2 Mensagens do Sistema
```php
// Flash Messages (Controllers)
__('Registro salvo com sucesso.')
__('Erro ao salvar o registro.')
__('Registro excluído com sucesso.')
__('Erro ao excluir o registro.')
__('Operação realizada com sucesso.')

// Validações
__('Campo obrigatório.')
__('Email inválido.')
__('Senha deve ter no mínimo 8 caracteres.')
__('As senhas não coincidem.')
__('Usuário ou senha inválidos.')
```

### 3.3 Títulos e Labels
```php
// Títulos de Página
__('Lista de Monitores')
__('Adicionar Monitor')
__('Editar Monitor')
__('Detalhes do Monitor')

// Labels de Formulário
__('Nome')
__('Email')
__('Senha')
__('Descrição')
__('Status')
__('Tipo')
```

### 3.4 Tabelas
```php
// Headers de Tabela
__('ID')
__('Nome')
__('Status')
__('Criado em')
__('Última Atualização')
__('Ações')

// Paginação
__('Primeira')
__('Anterior')
__('Próxima')
__('Última')
__('Mostrando {start} a {end} de {count} registros')
```

---

## 4. Estratégia de Implementação

### Fase 1: Configuração Base (1 sessão)
1. Configurar `config/app.php` com locale `pt_BR`
2. Criar estrutura de pastas `src/Locale/pt_BR/`
3. Criar arquivos `.po` base
4. Configurar helper `__()` em templates

### Fase 2: Core do Sistema (2 sessões)
1. **Layout e Elements** (navbar, sidebar, footer)
2. **Login e Autenticação**
3. **Dashboard Admin**

### Fase 3: Módulos Principais (6 sessões - 1 por módulo)
1. **Monitors** (index, view, add, edit)
2. **Incidents** (index, view, add, edit)
3. **Checks** (index, view)
4. **Subscribers** (index, view, add, subscribe, verify, unsubscribe)
5. **Users** (index, view, add, edit)
6. **Settings** (index, edit)

### Fase 4: Secundários (2 sessões)
1. **EmailLogs** (index, view)
2. **Status Page** (index)
3. **Error Pages** (400, 404, 500)

### Fase 5: Controllers e Validações (1 sessão)
1. Traduzir mensagens Flash em Controllers
2. Traduzir validações em Models
3. Traduzir emails

### Fase 6: Testes e Refinamento (1 sessão)
1. Testar todas as páginas
2. Corrigir textos faltantes
3. Documentar chaves de tradução

---

## 5. Convenções de Nomenclatura

### 5.1 Chaves de Tradução
```php
// Formato: domínio.contexto.chave
'monitors.list.title'           => 'Lista de Monitores'
'monitors.add.button'           => 'Adicionar Monitor'
'monitors.form.name'            => 'Nome'
'monitors.message.saved'        => 'Monitor salvo com sucesso'

// Comum (sem domínio)
'button.save'                   => 'Salvar'
'button.cancel'                 => 'Cancelar'
'message.confirm_delete'        => 'Tem certeza que deseja excluir?'
```

### 5.2 Uso no Código
```php
// Simples
<?= __('Salvar') ?>

// Com domínio
<?= __d('monitors', 'Lista de Monitores') ?>

// Com variáveis
<?= __('Mostrando {0} de {1} registros', [$start, $total]) ?>

// Com pluralização
<?= __n('{0} monitor', '{0} monitores', $count, $count) ?>
```

---

## 6. Arquivos de Configuração

### 6.1 `config/app.php`
```php
'defaultLocale' => env('APP_DEFAULT_LOCALE', 'pt_BR'),
'App' => [
    'defaultLocale' => 'pt_BR',
    'defaultTimezone' => 'America/Sao_Paulo',
],
```

### 6.2 `config/bootstrap.php`
```php
use Cake\I18n\I18n;

// Configurar locale
I18n::setLocale('pt_BR');
```

---

## 7. Checklist de Implementação

### Configuração
- [ ] Criar estrutura de pastas `src/Locale/pt_BR/`
- [ ] Configurar `app.php` com locale padrão
- [ ] Configurar timezone
- [ ] Criar arquivo `default.po` base

### Templates (por prioridade)
- [ ] Layout admin (navbar, sidebar, footer)
- [ ] Users/login.php
- [ ] Admin/index.php (Dashboard)
- [ ] Monitors/* (4 templates)
- [ ] Incidents/* (4 templates)
- [ ] Checks/* (2 templates)
- [ ] Subscribers/* (6 templates)
- [ ] Users/* (4 templates)
- [ ] Settings/* (2 templates)
- [ ] EmailLogs/* (2 templates)
- [ ] Status/index.php
- [ ] Error pages (3 templates)

### Controllers
- [ ] MonitorsController - mensagens Flash
- [ ] IncidentsController - mensagens Flash
- [ ] ChecksController - mensagens Flash
- [ ] SubscribersController - mensagens Flash
- [ ] UsersController - mensagens Flash
- [ ] SettingsController - mensagens Flash

### Models/Validações
- [ ] Monitor - regras de validação
- [ ] Incident - regras de validação
- [ ] Subscriber - regras de validação
- [ ] User - regras de validação
- [ ] Setting - regras de validação

### Emails
- [ ] incident_notification.php
- [ ] subscriber_verification.php
- [ ] monitor_down.php
- [ ] monitor_up.php

---

## 8. Exemplo de Arquivo .po

```po
# src/Locale/pt_BR/default.po
msgid ""
msgstr ""
"Project-Id-Version: ISP Status Page\n"
"Language: pt_BR\n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"
"Plural-Forms: nplurals=2; plural=(n > 1);\n"

# Botões
msgid "Save"
msgstr "Salvar"

msgid "Cancel"
msgstr "Cancelar"

msgid "Delete"
msgstr "Excluir"

# Mensagens
msgid "Record saved successfully."
msgstr "Registro salvo com sucesso."

msgid "Error saving record."
msgstr "Erro ao salvar o registro."
```

---

## 9. Estimativa de Tempo

| Fase | Descrição | Tempo Estimado |
|------|-----------|----------------|
| 1 | Configuração Base | 30 min |
| 2 | Core (Layout, Login, Dashboard) | 1h |
| 3 | Monitors | 45 min |
| 3 | Incidents | 45 min |
| 3 | Checks | 30 min |
| 3 | Subscribers | 1h |
| 3 | Users | 45 min |
| 3 | Settings | 30 min |
| 4 | EmailLogs + Status + Errors | 45 min |
| 5 | Controllers + Models | 1h |
| 6 | Testes + Refinamento | 30 min |
| **TOTAL** | | **~8h** |

---

## 10. Próximos Passos

1. ✅ Criar este documento de planejamento
2. ⏳ Configurar sistema i18n
3. ⏳ Criar estrutura de arquivos .po
4. ⏳ Implementar Fase 1 (Configuração)
5. ⏳ Implementar Fase 2 (Core)
6. ⏳ Implementar Fases 3-6 (Módulos)

---

**Status**: 📋 Planejamento Concluído
**Próxima Ação**: Configurar sistema i18n
