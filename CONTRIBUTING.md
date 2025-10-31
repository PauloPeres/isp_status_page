# Guia de Contribuição

Obrigado por considerar contribuir para o ISP Status Page! Este documento fornece diretrizes para contribuir com o projeto.

## Código de Conduta

Este projeto segue princípios de respeito mútuo e colaboração. Mantenha comunicações profissionais e construtivas.

## Como Contribuir

### 1. Encontre uma Tarefa

Consulte [docs/TASKS.md](docs/TASKS.md) para ver tarefas disponíveis. Tarefas marcadas com 🔴 estão disponíveis.

### 2. Configure o Ambiente

```bash
# Clone o repositório
git clone https://github.com/seu-usuario/isp_status_page.git
cd isp_status_page

# Instale dependências (após TASK-000 ser completada)
composer install

# Configure o banco de dados
cp config/app_local.example.php config/app_local.php
# Edite config/app_local.php conforme necessário

# Execute migrations
bin/cake migrations migrate

# Execute seeds
bin/cake migrations seed

# Inicie o servidor
bin/cake server
```

### 3. Crie uma Branch

```bash
# Formato: task-XXX-description
git checkout -b task-101-user-model
```

### 4. Desenvolva

Siga os padrões de código do projeto:
- PSR-12 para PHP
- Convenções do CakePHP
- Documentação inline (PHPDoc)
- Testes unitários para lógica de negócio
- Testes de integração para controllers

### 5. Execute Testes

```bash
# Todos os testes
vendor/bin/phpunit

# Teste específico
vendor/bin/phpunit tests/TestCase/Model/Table/UsersTableTest.php

# Com coverage
vendor/bin/phpunit --coverage-html tmp/coverage
```

### 6. Verifique o Código

```bash
# Linting
vendor/bin/phpcs src/ --standard=PSR12

# Fix automático
vendor/bin/phpcbf src/ --standard=PSR12

# Static analysis (se configurado)
vendor/bin/phpstan analyse src/
```

### 7. Commit

Siga o padrão de commits:

```bash
git add .
git commit -m "TASK-101: Implement User model with validations

- Add User entity with password hashing
- Add UsersTable with validation rules
- Create unit tests
- Add fixture for testing"
```

**Formato de commit**:
```
TASK-XXX: Título curto (max 50 chars)

- Descrição detalhada em bullet points
- O que foi feito
- Por que foi feito
- Considerações especiais
```

### 8. Push e Pull Request

```bash
git push origin task-101-user-model
```

Abra um Pull Request no GitHub com:
- Título: `TASK-101: User Model Implementation`
- Descrição detalhada
- Link para a tarefa
- Screenshots (se aplicável)
- Checklist de critérios de aceite

**Template de PR**:
```markdown
## Descrição
Implementação do Model User conforme TASK-101.

## Tarefa Relacionada
TASK-101: User Model e Entity

## Mudanças
- Criado User entity com hash automático de senha
- Criado UsersTable com validações
- Adicionado testes unitários
- Criado fixture

## Checklist
- [x] Model criado com validações
- [x] Senha é hash automaticamente
- [x] Testes unitários passando
- [x] Fixture funcional
- [x] Documentação inline
- [x] PSR-12 compliant

## Como Testar
1. Execute `vendor/bin/phpunit tests/TestCase/Model/Table/UsersTableTest.php`
2. Verifique que todos os testes passam
3. Tente criar um usuário via console: `bin/cake console`
```

## Padrões de Código

### PHP

**PSR-12 Compliance**:
```php
<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class UsersTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('users');
        $this->setDisplayField('username');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('username')
            ->maxLength('username', 100)
            ->requirePresence('username', 'create')
            ->notEmptyString('username')
            ->add('username', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        return $validator;
    }
}
```

**Nomenclatura**:
- Classes: `PascalCase`
- Métodos: `camelCase`
- Variáveis: `camelCase`
- Constantes: `UPPER_SNAKE_CASE`

**Documentação**:
```php
/**
 * Valida e hash a senha do usuário
 *
 * @param string $password Senha em texto plano
 * @return string Senha hasheada
 * @throws \InvalidArgumentException Se senha for muito curta
 */
protected function hashPassword(string $password): string
{
    if (strlen($password) < 8) {
        throw new \InvalidArgumentException('Senha deve ter no mínimo 8 caracteres');
    }

    return (new DefaultPasswordHasher())->hash($password);
}
```

### JavaScript

**Padrões**:
- ES6+ syntax
- Comentários claros
- Nomes descritivos

```javascript
/**
 * Atualiza o formulário baseado no tipo de monitor selecionado
 * @param {string} monitorType - Tipo do monitor (http, ping, port)
 */
function updateMonitorForm(monitorType) {
    const formSections = document.querySelectorAll('.monitor-type-section');

    formSections.forEach(section => {
        section.style.display = 'none';
    });

    const targetSection = document.getElementById(`${monitorType}-section`);
    if (targetSection) {
        targetSection.style.display = 'block';
    }
}
```

### CSS/Tailwind

Se usando Tailwind, prefira classes utilitárias:
```html
<div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">Monitor Status</h2>
    <p class="text-gray-600">All systems operational</p>
</div>
```

Para CSS customizado:
```css
/* Use BEM naming */
.monitor-card {
    /* Estilos base */
}

.monitor-card__title {
    /* Estilos do título */
}

.monitor-card--critical {
    /* Variante crítica */
}
```

## Testes

### Testes Unitários

Para Models e Services:

```php
<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\UsersTable;
use Cake\TestSuite\TestCase;

class UsersTableTest extends TestCase
{
    protected $fixtures = [
        'app.Users',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->Users = $this->getTableLocator()->get('Users');
    }

    public function testPasswordIsHashedAutomatically()
    {
        $user = $this->Users->newEntity([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'user'
        ]);

        $this->Users->save($user);

        $this->assertNotEquals('password123', $user->password);
        $this->assertEquals(60, strlen($user->password)); // bcrypt length
    }
}
```

### Testes de Integração

Para Controllers:

```php
<?php
namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class UsersControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected $fixtures = [
        'app.Users',
    ];

    public function testLoginSuccess()
    {
        $this->post('/users/login', [
            'username' => 'admin',
            'password' => 'admin123'
        ]);

        $this->assertResponseOk();
        $this->assertSession('admin', 'Auth.User.username');
        $this->assertRedirect(['controller' => 'Dashboard', 'action' => 'index']);
    }

    public function testLoginFail()
    {
        $this->post('/users/login', [
            'username' => 'admin',
            'password' => 'wrongpassword'
        ]);

        $this->assertResponseOk();
        $this->assertSession(null, 'Auth.User');
        $this->assertFlashMessage('Credenciais inválidas');
    }
}
```

## Documentação

### Inline Documentation

Todo método público deve ter PHPDoc:

```php
/**
 * Executa verificação de um monitor HTTP
 *
 * Faz request HTTP para a URL configurada e valida
 * status code e conteúdo conforme esperado.
 *
 * @param \App\Model\Entity\Monitor $monitor Monitor a ser verificado
 * @return array Resultado da verificação com status e métricas
 * @throws \RuntimeException Se configuração for inválida
 */
public function check(Monitor $monitor): array
{
    // Implementation
}
```

### README de Módulos

Módulos complexos devem ter README próprio:

```markdown
# HTTP Checker

## Descrição
Implementa verificação de endpoints HTTP/HTTPS.

## Uso
```php
$checker = new HttpChecker();
$result = $checker->check($monitor);
```

## Configuração
- `url`: URL a ser verificada
- `method`: GET, POST, etc
- `expected_status`: Array de códigos válidos
- `timeout`: Timeout em segundos

## Testes
Execute: `vendor/bin/phpunit tests/TestCase/Service/Check/HttpCheckerTest.php`
```

## Dependências

### Adicionando Dependências

Use Composer:

```bash
composer require vendor/package
```

Documente no PR por que a dependência é necessária.

### Dependências Permitidas

- CakePHP plugins oficiais
- Bibliotecas bem mantidas e estáveis
- MIT/Apache/BSD licensed

### Dependências a Evitar

- Packages abandonados
- Packages com vulnerabilidades conhecidas
- GPL licensed (conflita com Apache)

## Git Workflow

### Branches

- `main`: Código estável
- `feature/task-XXX`: Features em desenvolvimento
- `hotfix/issue-XXX`: Correções urgentes

### Commits

Commits devem ser:
- **Atômicos**: Uma mudança lógica por commit
- **Descritivos**: Mensagem clara do que foi feito
- **Testados**: Código deve funcionar

### Pull Requests

PRs devem:
- Ter título descritivo
- Referenciar tarefa/issue
- Incluir testes
- Passar no CI
- Ter pelo menos 1 aprovação

## Code Review

### Como Revisor

- Seja construtivo e educado
- Foque no código, não na pessoa
- Sugira melhorias específicas
- Aprove se estiver de acordo com os padrões

**Checklist de Review**:
- [ ] Código segue PSR-12
- [ ] Testes incluídos e passando
- [ ] Documentação adequada
- [ ] Sem código comentado desnecessário
- [ ] Sem debugging statements (var_dump, console.log)
- [ ] Sem credenciais hardcoded
- [ ] Performance adequada
- [ ] Segurança considerada

### Como Contributor

- Responda a comentários prontamente
- Seja receptivo a sugestões
- Faça mudanças solicitadas
- Marque conversas como resolvidas

## Segurança

### Reportando Vulnerabilidades

**NÃO** abra issue pública. Envie email para:
security@example.com (ajustar)

### Práticas Seguras

- Nunca commite credenciais
- Use prepared statements (ORM faz isso)
- Valide input do usuário
- Escape output
- Use HTTPS em produção
- Mantenha dependências atualizadas

```php
// BOM
$user = $this->Users->get($id);

// RUIM
$user = $this->Users->query("SELECT * FROM users WHERE id = $id"); // SQL injection
```

## Performance

### Considerações

- Limite queries no banco (use eager loading)
- Use cache apropriadamente
- Evite N+1 queries
- Profile código crítico

```php
// BOM - Eager loading
$monitors = $this->Monitors->find()
    ->contain(['MonitorChecks' => function($q) {
        return $q->order(['created' => 'DESC'])->limit(10);
    }])
    ->all();

// RUIM - N+1
$monitors = $this->Monitors->find()->all();
foreach ($monitors as $monitor) {
    $checks = $monitor->monitor_checks; // Query por monitor!
}
```

## Ajuda

### Recursos

- [Documentação CakePHP](https://book.cakephp.org)
- [Documentação do Projeto](docs/)
- [PSR-12](https://www.php-fig.org/psr/psr-12/)

### Contato

- Issues no GitHub
- Discussões no GitHub Discussions
- Email: (a definir)

## Licença

Ao contribuir, você concorda que suas contribuições serão licenciadas sob Apache License 2.0.

---

**Obrigado por contribuir! 🚀**
