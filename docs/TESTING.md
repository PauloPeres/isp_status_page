# Estratégia de Testes - ISP Status Page

Este documento descreve a estratégia de testes do projeto, tipos de testes implementados e como executá-los.

## 📋 Índice

1. [Visão Geral](#visão-geral)
2. [Tipos de Testes](#tipos-de-testes)
3. [Estrutura de Testes](#estrutura-de-testes)
4. [Executando Testes](#executando-testes)
5. [Fixtures](#fixtures)
6. [Boas Práticas](#boas-práticas)
7. [Coverage](#coverage)

## Visão Geral

O projeto utiliza **PHPUnit** como framework de testes, integrado ao CakePHP. Os testes são executados em um banco de dados SQLite separado em memória, garantindo isolamento e velocidade.

### Objetivos dos Testes

- ✅ Detectar bugs e regressões antecipadamente
- ✅ Documentar comportamento esperado do código
- ✅ Facilitar refatoração com confiança
- ✅ Garantir qualidade do código

## Tipos de Testes

### 1. Testes de Controller (Integration Tests)

Testam requisições HTTP completas, incluindo roteamento, autenticação e renderização de views.

**Exemplo:**
```php
public function testLoginPostValid(): void
{
    $this->enableCsrfToken();
    $this->post('/users/login', [
        'username' => 'admin',
        'password' => 'admin123',
    ]);

    $this->assertRedirect(['controller' => 'Admin', 'action' => 'index']);
    $this->assertSession('admin', 'Auth.username');
}
```

### 2. Testes de Model (Unit Tests)

Testam lógica de negócio, validações, comportamentos e relacionamentos.

**Exemplo:**
```php
public function testValidationFails(): void
{
    $user = $this->Users->newEntity([
        'username' => '', // Invalid
    ]);

    $this->assertFalse($this->Users->save($user));
    $this->assertNotEmpty($user->getErrors());
}
```

### 3. Testes de Service (Unit Tests)

Testam serviços e lógica de aplicação isoladamente.

**Exemplo:**
```php
public function testSettingServiceGetWithCache(): void
{
    $value = $this->SettingService->get('site_name');

    $this->assertEquals('ISP Status', $value);
    $this->assertTrue(Cache::read('setting.site_name') !== false);
}
```

## Estrutura de Testes

```
tests/
├── TestCase/
│   ├── Controller/          # Testes de controllers
│   │   ├── UsersControllerTest.php
│   │   ├── AdminControllerTest.php
│   │   └── StatusControllerTest.php
│   ├── Model/
│   │   ├── Table/           # Testes de tables
│   │   └── Entity/          # Testes de entities
│   └── Service/             # Testes de services
├── Fixture/                 # Dados de teste
│   ├── UsersFixture.php
│   ├── MonitorsFixture.php
│   └── IncidentsFixture.php
└── bootstrap.php            # Bootstrap de testes
```

## Executando Testes

### Comandos Make (Recomendado)

```bash
# Executar todos os testes
make test

# Executar apenas testes de controllers
make test-controllers

# Executar apenas testes de models
make test-models

# Executar teste específico
make test-specific FILE=UsersControllerTest

# Executar com coverage HTML
make test-coverage

# Executar com coverage em texto
make test-coverage-text
```

### Comandos Diretos (dentro do container)

```bash
# Todos os testes
docker-compose exec app vendor/bin/phpunit

# Teste específico
docker-compose exec app vendor/bin/phpunit tests/TestCase/Controller/UsersControllerTest.php

# Apenas um método
docker-compose exec app vendor/bin/phpunit --filter testLoginPostValid

# Com mais detalhes
docker-compose exec app vendor/bin/phpunit --verbose

# Com coverage
docker-compose exec app vendor/bin/phpunit --coverage-html tmp/coverage
```

### Comandos Locais (sem Docker)

```bash
cd src

# Todos os testes
vendor/bin/phpunit

# Teste específico
vendor/bin/phpunit tests/TestCase/Controller/UsersControllerTest.php

# Com coverage
vendor/bin/phpunit --coverage-text
```

## Fixtures

Fixtures são dados de teste que populam o banco de dados temporário antes de cada teste.

### Estrutura de uma Fixture

```php
<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class UsersFixture extends TestFixture
{
    public array $records = [
        [
            'id' => 1,
            'username' => 'admin',
            'password' => '$2y$10$...', // Hash de 'admin123'
            'email' => 'admin@example.com',
            'name' => 'Administrator',
            'active' => true,
        ],
    ];
}
```

### Fixtures Disponíveis

- **UsersFixture**: 3 usuários (admin, user, inactive)
- **MonitorsFixture**: 3 monitores (2 ativos, 1 inativo)
- **IncidentsFixture**: 2 incidentes (1 resolvido, 1 ativo)
- **SubscribersFixture**: 3 inscritos (2 ativos, 1 inativo)
- **MonitorChecksFixture**: 3 verificações de monitores

## Boas Práticas

### 1. Nomes de Testes Descritivos

✅ **BOM:**
```php
public function testLoginPostWithValidCredentialsRedirectsToAdmin(): void
```

❌ **RUIM:**
```php
public function testLogin(): void
```

### 2. Arrange-Act-Assert (AAA)

```php
public function testUserCanBeCreated(): void
{
    // Arrange
    $data = [
        'username' => 'newuser',
        'email' => 'new@example.com',
    ];

    // Act
    $user = $this->Users->newEntity($data);
    $result = $this->Users->save($user);

    // Assert
    $this->assertTrue((bool)$result);
    $this->assertEquals('newuser', $user->username);
}
```

### 3. Testar Casos de Sucesso e Falha

```php
public function testLoginWithValidCredentials(): void { ... }
public function testLoginWithInvalidCredentials(): void { ... }
public function testLoginWithInactiveUser(): void { ... }
```

### 4. Usar Fixtures para Isolamento

```php
protected array $fixtures = [
    'app.Users',
    'app.Monitors',
];
```

### 5. Testar Autenticação

```php
// Simular usuário logado
$this->session([
    'Auth' => [
        'id' => 1,
        'username' => 'admin',
    ]
]);

$this->get('/admin');
$this->assertResponseOk();
```

### 6. Habilitar CSRF em Testes POST

```php
$this->enableCsrfToken();
$this->enableSecurityToken();

$this->post('/users/add', $data);
```

## Coverage

Coverage mostra quanto do código está coberto por testes.

### Gerar Coverage HTML

```bash
make test-coverage
```

Acesse: `src/tmp/coverage/index.html`

### Coverage em Texto

```bash
make test-coverage-text
```

### Metas de Coverage

| Tipo | Meta Mínima | Meta Ideal |
|------|-------------|------------|
| **Controllers** | 70% | 85% |
| **Models** | 80% | 90% |
| **Services** | 85% | 95% |
| **Geral** | 75% | 85% |

## Testes Implementados

### ✅ UsersController
- `testLoginGet()` - Página de login carrega
- `testLoginPostValid()` - Login com credenciais válidas
- `testLoginPostInvalid()` - Login com credenciais inválidas
- `testLogout()` - Logout funciona
- `testIndexAuthenticated()` - Lista usuários (autenticado)
- `testIndexUnauthenticated()` - Redireciona se não autenticado
- `testAddGet()` - Formulário de adicionar
- `testAddPostValid()` - Adicionar usuário com dados válidos
- `testEditGet()` - Formulário de editar
- `testDelete()` - Deletar usuário

### ✅ AdminController
- `testIndexUnauthenticated()` - Redireciona se não autenticado
- `testIndexAuthenticated()` - Dashboard carrega
- `testIndexStatistics()` - Estatísticas calculadas corretamente
- `testIndexRecentMonitors()` - Monitores recentes carregados
- `testIndexRecentIncidents()` - Incidentes recentes carregados
- `testIndexUsesAdminLayout()` - Usa layout admin

### ✅ StatusController
- `testIndexPublicAccess()` - Página pública acessível
- `testIndexUsesPublicLayout()` - Usa layout público
- `testIndexDisplaysSystemStatus()` - Mostra status do sistema
- `testIndexSetsViewVariables()` - Define variáveis de view
- `testIndexShowsOnlyActiveMonitors()` - Mostra apenas monitores ativos
- `testIndexCalculatesStatistics()` - Calcula estatísticas corretamente
- `testHistoryPublicAccess()` - Histórico acessível
- `testHistorySetsGroupedIncidents()` - Agrupa incidentes por data

## Próximos Passos

### Testes a Implementar

1. **Model Tests**
   - UserTable validações
   - MonitorTable validações
   - IncidentTable validações
   - Relacionamentos

2. **Service Tests**
   - SettingService
   - NotificationService (quando implementado)
   - MonitorService (quando implementado)

3. **Command Tests**
   - CheckMonitorsCommand (quando implementado)
   - SendAlertsCommand (quando implementado)

4. **Integration Tests**
   - Fluxo completo de criação de incidente
   - Fluxo de notificação de assinantes
   - Fluxo de verificação de monitores

## Troubleshooting

### Erro: "Fixture not found"

```bash
# Verificar se a tabela existe no banco de testes
docker-compose exec app bin/cake migrations status

# Re-executar migrations no ambiente de teste
docker-compose exec app vendor/bin/phpunit --migrate
```

### Erro: "Authentication component not found"

Certifique-se de que o AppController carrega o componente:

```php
public function initialize(): void
{
    parent::initialize();
    $this->loadComponent('Authentication.Authentication');
}
```

### Erro: "Identity helper not found"

Certifique-se de que o AppView carrega o helper:

```php
public function initialize(): void
{
    parent::initialize();
    $this->loadHelper('Authentication.Identity');
}
```

## Recursos

- [CakePHP Testing Guide](https://book.cakephp.org/5/en/development/testing.html)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Test Driven Development (TDD)](https://martinfowler.com/bliki/TestDrivenDevelopment.html)

---

**Última atualização**: 31 de Outubro de 2025
**Versão**: 1.0
