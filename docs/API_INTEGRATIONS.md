# Integrações com APIs Externas

## Visão Geral

O sistema ISP Status Page foi projetado para integrar-se com diversos sistemas de gestão e monitoramento utilizados por provedores de internet. Esta documentação detalha as integrações disponíveis e planejadas.

## Arquitetura de Integração

### Padrão Adapter

Todas as integrações seguem o padrão Adapter para manter consistência:

```php
// src/Integration/IntegrationInterface.php
interface IntegrationInterface {
    public function connect(): bool;
    public function testConnection(): array;
    public function getStatus(string $resourceId): array;
    public function getMetrics(string $resourceId, array $params = []): array;
    public function disconnect(): void;
}
```

### Estrutura de Diretórios

```
src/Integration/
├── IntegrationInterface.php
├── AbstractIntegration.php
├── Ixc/
│   ├── IxcAdapter.php
│   ├── IxcClient.php
│   └── IxcMapper.php
├── Zabbix/
│   ├── ZabbixAdapter.php
│   ├── ZabbixClient.php
│   └── ZabbixMapper.php
└── RestApi/
    ├── RestApiAdapter.php
    └── RestApiClient.php
```

## 1. Integração com IXC Soft

### Sobre o IXC

IXC Soft é um dos principais sistemas de gestão para provedores de internet no Brasil, oferecendo controle de clientes, financeiro, suporte técnico e NOC.

### Endpoints Relevantes

#### Autenticação
```http
POST /api/v1/auth
Content-Type: application/json

{
    "username": "api_user",
    "password": "api_password"
}

Response:
{
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "expires_at": "2024-12-31T23:59:59Z"
}
```

#### Status de Serviços
```http
GET /api/v1/services/{service_id}/status
Authorization: Bearer {token}

Response:
{
    "service_id": "12345",
    "status": "active",
    "customer_name": "Cliente Exemplo",
    "plan": "100MB",
    "connection_status": "online",
    "last_seen": "2024-10-31T10:30:00Z",
    "signal_quality": 85,
    "equipment": {
        "mac": "AA:BB:CC:DD:EE:FF",
        "ip": "192.168.1.100",
        "status": "online"
    }
}
```

#### Tickets de Suporte
```http
GET /api/v1/tickets
Authorization: Bearer {token}

Parameters:
- status: open, in_progress, closed
- priority: low, medium, high, critical
- created_after: ISO 8601 date

Response:
{
    "data": [
        {
            "id": "T-12345",
            "subject": "Sem internet",
            "status": "open",
            "priority": "high",
            "customer_id": "12345",
            "created_at": "2024-10-31T09:00:00Z",
            "category": "technical"
        }
    ],
    "meta": {
        "total": 15,
        "page": 1
    }
}
```

#### Status de Equipamentos (OLT/POP)
```http
GET /api/v1/network/equipment/{equipment_id}/status
Authorization: Bearer {token}

Response:
{
    "equipment_id": "OLT-01",
    "type": "OLT",
    "name": "OLT Centro",
    "status": "online",
    "cpu_usage": 45,
    "memory_usage": 60,
    "uptime": 2592000,
    "ports_total": 16,
    "ports_active": 14,
    "temperature": 38.5,
    "last_update": "2024-10-31T10:35:00Z"
}
```

### Implementação do Adapter

```php
// src/Integration/Ixc/IxcAdapter.php
namespace App\Integration\Ixc;

use App\Integration\IntegrationInterface;

class IxcAdapter implements IntegrationInterface {

    private IxcClient $client;
    private array $config;

    public function __construct(array $config) {
        $this->config = $config;
        $this->client = new IxcClient(
            $config['base_url'],
            $config['username'],
            $config['password']
        );
    }

    public function connect(): bool {
        return $this->client->authenticate();
    }

    public function testConnection(): array {
        try {
            $this->connect();
            return [
                'success' => true,
                'message' => 'Conexão estabelecida com sucesso'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getStatus(string $resourceId): array {
        $data = $this->client->get("/services/{$resourceId}/status");
        return IxcMapper::mapServiceStatus($data);
    }

    // Método específico do IXC
    public function getEquipmentStatus(string $equipmentId): array {
        $data = $this->client->get("/network/equipment/{$equipmentId}/status");
        return IxcMapper::mapEquipmentStatus($data);
    }

    public function getCriticalTickets(): array {
        $data = $this->client->get('/tickets', [
            'status' => 'open',
            'priority' => 'critical'
        ]);
        return IxcMapper::mapTickets($data);
    }
}
```

### Casos de Uso

#### Monitor 1: Status de Serviço Específico
Verificar se um serviço específico está ativo e com boa qualidade de sinal.

#### Monitor 2: OLT/POP Status
Monitorar status de equipamento de rede (OLT, roteador, etc).

#### Monitor 3: Tickets Críticos
Alertar se houver tickets críticos abertos acima de um threshold.

### Configuração no Sistema

```json
{
    "integration_id": 1,
    "type": "ixc",
    "monitor_type": "equipment_status",
    "equipment_id": "OLT-01",
    "thresholds": {
        "cpu_usage": 80,
        "memory_usage": 85,
        "temperature": 65
    }
}
```

## 2. Integração com Zabbix

### Sobre o Zabbix

Zabbix é uma plataforma de monitoramento enterprise open-source amplamente utilizada para monitorar redes, servidores e aplicações.

### API JSON-RPC

Zabbix utiliza JSON-RPC 2.0 para sua API.

#### Autenticação
```http
POST /api_jsonrpc.php
Content-Type: application/json-rpc

{
    "jsonrpc": "2.0",
    "method": "user.login",
    "params": {
        "username": "api_user",
        "password": "api_password"
    },
    "id": 1
}

Response:
{
    "jsonrpc": "2.0",
    "result": "0424bd59b807674191e7d77572075f33",
    "id": 1
}
```

#### Listar Hosts
```json
{
    "jsonrpc": "2.0",
    "method": "host.get",
    "params": {
        "output": ["hostid", "host", "status"],
        "selectInterfaces": ["ip"]
    },
    "auth": "0424bd59b807674191e7d77572075f33",
    "id": 2
}

Response:
{
    "jsonrpc": "2.0",
    "result": [
        {
            "hostid": "10084",
            "host": "router-01",
            "status": "0",
            "interfaces": [
                {"ip": "192.168.1.1"}
            ]
        }
    ],
    "id": 2
}
```

#### Obter Triggers Ativas
```json
{
    "jsonrpc": "2.0",
    "method": "trigger.get",
    "params": {
        "output": "extend",
        "filter": {
            "value": 1
        },
        "selectHosts": ["name"],
        "sortfield": "priority",
        "sortorder": "DESC"
    },
    "auth": "0424bd59b807674191e7d77572075f33",
    "id": 3
}

Response:
{
    "jsonrpc": "2.0",
    "result": [
        {
            "triggerid": "13926",
            "description": "High CPU usage on router-01",
            "priority": "4",
            "value": "1",
            "lastchange": "1698752400",
            "hosts": [
                {"name": "router-01"}
            ]
        }
    ],
    "id": 3
}
```

#### Obter Items (Métricas)
```json
{
    "jsonrpc": "2.0",
    "method": "item.get",
    "params": {
        "output": "extend",
        "hostids": "10084",
        "search": {
            "key_": "system.cpu.util"
        }
    },
    "auth": "0424bd59b807674191e7d77572075f33",
    "id": 4
}
```

#### Obter Último Valor
```json
{
    "jsonrpc": "2.0",
    "method": "history.get",
    "params": {
        "output": "extend",
        "history": 0,
        "itemids": "23296",
        "sortfield": "clock",
        "sortorder": "DESC",
        "limit": 1
    },
    "auth": "0424bd59b807674191e7d77572075f33",
    "id": 5
}
```

### Implementação do Adapter

```php
// src/Integration/Zabbix/ZabbixAdapter.php
namespace App\Integration\Zabbix;

use App\Integration\IntegrationInterface;

class ZabbixAdapter implements IntegrationInterface {

    private ZabbixClient $client;
    private array $config;
    private ?string $authToken = null;

    public function __construct(array $config) {
        $this->config = $config;
        $this->client = new ZabbixClient($config['base_url']);
    }

    public function connect(): bool {
        $this->authToken = $this->client->authenticate(
            $this->config['username'],
            $this->config['password']
        );
        return $this->authToken !== null;
    }

    public function testConnection(): array {
        try {
            $this->connect();
            return [
                'success' => true,
                'message' => 'Conexão com Zabbix estabelecida'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getStatus(string $resourceId): array {
        // resourceId pode ser hostid
        $host = $this->client->call('host.get', [
            'hostids' => $resourceId,
            'output' => ['hostid', 'host', 'status', 'available']
        ], $this->authToken);

        return ZabbixMapper::mapHostStatus($host[0] ?? []);
    }

    public function getActiveTriggers(string $hostId = null): array {
        $params = [
            'output' => 'extend',
            'filter' => ['value' => 1],
            'selectHosts' => ['name'],
            'sortfield' => 'priority',
            'sortorder' => 'DESC'
        ];

        if ($hostId) {
            $params['hostids'] = $hostId;
        }

        $triggers = $this->client->call('trigger.get', $params, $this->authToken);
        return ZabbixMapper::mapTriggers($triggers);
    }

    public function getMetricValue(string $hostId, string $metricKey): ?float {
        // Primeiro encontrar o itemid
        $items = $this->client->call('item.get', [
            'output' => ['itemid'],
            'hostids' => $hostId,
            'search' => ['key_' => $metricKey]
        ], $this->authToken);

        if (empty($items)) {
            return null;
        }

        $itemId = $items[0]['itemid'];

        // Pegar último valor
        $history = $this->client->call('history.get', [
            'output' => 'extend',
            'history' => 0,
            'itemids' => $itemId,
            'sortfield' => 'clock',
            'sortorder' => 'DESC',
            'limit' => 1
        ], $this->authToken);

        return !empty($history) ? (float)$history[0]['value'] : null;
    }
}
```

### Casos de Uso

#### Monitor 1: Status de Host
Verificar se um host específico está UP no Zabbix.

#### Monitor 2: Triggers Críticas
Alertar se houver triggers críticas ativas.

#### Monitor 3: Métrica Específica
Monitorar valor de uma métrica específica (CPU, memória, bandwidth, etc).

### Configuração no Sistema

```json
{
    "integration_id": 2,
    "type": "zabbix",
    "monitor_type": "host_status",
    "host_id": "10084",
    "check_triggers": true,
    "trigger_severity_threshold": 3
}
```

```json
{
    "integration_id": 2,
    "type": "zabbix",
    "monitor_type": "metric",
    "host_id": "10084",
    "metric_key": "system.cpu.util",
    "threshold": 85,
    "comparison": "greater_than"
}
```

## 3. Integração REST API Genérica

### Visão Geral

Para sistemas que não possuem adapter específico, o sistema oferece integração genérica via REST API.

### Configuração

```json
{
    "type": "rest_api",
    "method": "GET",
    "url": "https://api.example.com/health",
    "headers": {
        "Authorization": "Bearer {token}",
        "Content-Type": "application/json"
    },
    "expected_status": [200, 201],
    "json_path": "status",
    "expected_value": "ok",
    "timeout": 30
}
```

### Validações Suportadas

#### 1. Status HTTP
```json
{
    "validation_type": "status_code",
    "expected_status": [200, 201, 204]
}
```

#### 2. Conteúdo da Resposta
```json
{
    "validation_type": "content",
    "expected_content": "OK",
    "match_type": "exact"  // exact, contains, regex
}
```

#### 3. JSON Path
```json
{
    "validation_type": "json_path",
    "json_path": "data.status",
    "expected_value": "operational",
    "comparison": "equals"  // equals, not_equals, greater_than, less_than
}
```

#### 4. Response Time
```json
{
    "validation_type": "response_time",
    "max_response_time": 1000  // milisegundos
}
```

### Implementação

```php
// src/Integration/RestApi/RestApiAdapter.php
namespace App\Integration\RestApi;

use App\Integration\IntegrationInterface;
use Cake\Http\Client;

class RestApiAdapter implements IntegrationInterface {

    private Client $http;
    private array $config;

    public function __construct(array $config) {
        $this->config = $config;
        $this->http = new Client([
            'timeout' => $config['timeout'] ?? 30
        ]);
    }

    public function testConnection(): array {
        try {
            $response = $this->http->request(
                $this->config['method'],
                $this->config['url'],
                [],
                ['headers' => $this->config['headers'] ?? []]
            );

            return [
                'success' => $response->isOk(),
                'status_code' => $response->getStatusCode(),
                'response_time' => $response->getHeaderLine('X-Response-Time')
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getStatus(string $resourceId): array {
        $url = str_replace('{resource_id}', $resourceId, $this->config['url']);

        $startTime = microtime(true);
        $response = $this->http->request(
            $this->config['method'],
            $url,
            $this->config['body'] ?? [],
            ['headers' => $this->config['headers'] ?? []]
        );
        $responseTime = (microtime(true) - $startTime) * 1000;

        $validations = $this->validate($response);

        return [
            'status' => $validations['success'] ? 'up' : 'down',
            'response_time' => $responseTime,
            'status_code' => $response->getStatusCode(),
            'validations' => $validations
        ];
    }

    private function validate($response): array {
        $validations = ['success' => true, 'checks' => []];

        // Status code check
        if (isset($this->config['expected_status'])) {
            $check = in_array(
                $response->getStatusCode(),
                $this->config['expected_status']
            );
            $validations['checks'][] = [
                'type' => 'status_code',
                'passed' => $check
            ];
            if (!$check) $validations['success'] = false;
        }

        // Content check
        if (isset($this->config['expected_content'])) {
            $body = $response->getStringBody();
            $check = str_contains($body, $this->config['expected_content']);
            $validations['checks'][] = [
                'type' => 'content',
                'passed' => $check
            ];
            if (!$check) $validations['success'] = false;
        }

        // JSON path check
        if (isset($this->config['json_path'])) {
            $json = $response->getJson();
            $value = $this->getJsonPath($json, $this->config['json_path']);
            $check = ($value == $this->config['expected_value']);
            $validations['checks'][] = [
                'type' => 'json_path',
                'passed' => $check,
                'expected' => $this->config['expected_value'],
                'actual' => $value
            ];
            if (!$check) $validations['success'] = false;
        }

        return $validations;
    }

    private function getJsonPath(array $data, string $path) {
        $keys = explode('.', $path);
        $value = $data;

        foreach ($keys as $key) {
            if (!isset($value[$key])) {
                return null;
            }
            $value = $value[$key];
        }

        return $value;
    }
}
```

## 4. Futuras Integrações (Roadmap)

### WhatsApp Business API
- Envio de notificações via WhatsApp
- Confirmação de leitura
- Respostas automáticas

### Telegram Bot API
- Notificações via bot
- Comandos para consultar status
- Grupos de notificação

### SMS Gateway
- Integração com provedores (Twilio, Nexmo, etc)
- Envio de SMS para alertas críticos

### Sistema de Telefonia
- Ligações automáticas via IVR
- Escalação de equipes

## Testes de Integração

### Estrutura de Testes

```
tests/TestCase/Integration/
├── IxcAdapterTest.php
├── ZabbixAdapterTest.php
└── RestApiAdapterTest.php
```

### Mocks e Fixtures

Utilizar VCR ou similar para gravar/reproduzir respostas das APIs durante testes.

## Segurança

### Credenciais
- Sempre armazenar credenciais criptografadas
- Usar Security component do CakePHP
- Nunca logar credenciais

### Rate Limiting
- Respeitar rate limits das APIs
- Implementar backoff exponencial em caso de 429
- Cache de resultados quando possível

### Timeouts
- Configurar timeouts apropriados
- Não bloquear verificações por APIs lentas
- Falhar gracefully

## Logging

Todas as chamadas de API devem ser logadas em `integration_logs`:
- Timestamp
- Endpoint chamado
- Status da resposta
- Tempo de resposta
- Erros (se houver)

## Configuração no Painel Admin

Interface para:
1. Adicionar nova integração
2. Testar conexão
3. Ver logs de integração
4. Ativar/desativar integração
5. Sincronizar dados

## Exemplos de Uso

### Criar Monitor IXC via UI

1. Admin → Integrações → Nova Integração
2. Tipo: IXC
3. Configurar credenciais
4. Testar conexão
5. Admin → Monitores → Novo Monitor
6. Tipo: IXC Equipment
7. Selecionar integração IXC
8. Configurar equipment_id e thresholds
9. Salvar

O sistema automaticamente:
- Agenda verificações
- Executa checks via IXC API
- Processa resultados
- Atualiza status
- Dispara alertas se necessário
