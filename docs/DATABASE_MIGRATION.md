# Migração entre Bancos de Dados

Este guia explica como migrar dados entre diferentes sistemas de banco de dados.

## Visão Geral

O ISP Status Page suporta três bancos de dados:

| Banco | Recomendado Para | Max Monitores | Performance |
|-------|------------------|---------------|-------------|
| **SQLite** | Desenvolvimento, Small ISPs | ~100 | Boa |
| **MySQL** | Produção, Medium ISPs | ~500 | Muito Boa |
| **PostgreSQL** | Large ISPs, Enterprise | 1000+ | Excelente |

## Configuração Multi-Database

O banco de dados é configurado via variável de ambiente `DATABASE_URL`:

### SQLite (Padrão)
```env
DATABASE_URL="sqlite:///path/to/database.db"
```

### MySQL/MariaDB
```env
DATABASE_URL="mysql://user:password@localhost/isp_status_page?encoding=utf8mb4&timezone=UTC"
```

### PostgreSQL
```env
DATABASE_URL="postgres://user:password@localhost/isp_status_page?encoding=utf8&timezone=UTC"
```

## Cenários de Migração

### 1. SQLite → MySQL

**Quando migrar:**
- Mais de 100 monitores
- Múltiplos usuários simultâneos
- Necessidade de backup mais robusto
- Performance ficando lenta

**Passos:**

#### 1.1. Preparar MySQL

```bash
# Criar banco de dados
mysql -u root -p

CREATE DATABASE isp_status_page CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'isp_status'@'localhost' IDENTIFIED BY 'senha_segura';
GRANT ALL PRIVILEGES ON isp_status_page.* TO 'isp_status'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

#### 1.2. Exportar dados do SQLite

```bash
# Método 1: Usar .dump do sqlite3
sqlite3 database.db .dump > export.sql

# Método 2: Usar ferramenta de migração
# Instalar: composer require --dev phinx/phinx
```

#### 1.3. Converter SQL para MySQL

O SQL dump do SQLite precisa ser ajustado:

```bash
# Remover comandos incompatíveis
sed -i 's/PRAGMA.*//g' export.sql
sed -i 's/BEGIN TRANSACTION/START TRANSACTION/g' export.sql
sed -i 's/AUTOINCREMENT/AUTO_INCREMENT/g' export.sql

# Ou usar ferramenta automatizada
# https://github.com/dumblob/mysql2sqlite
```

#### 1.4. Executar Migrations no MySQL

```bash
# Atualizar .env ou app_local.php
DATABASE_URL="mysql://isp_status:senha_segura@localhost/isp_status_page"

# Executar migrations (cria estrutura)
bin/cake migrations migrate
```

#### 1.5. Importar Dados

```bash
# Opção 1: Importar SQL convertido
mysql -u isp_status -p isp_status_page < export.sql

# Opção 2: Script PHP de migração (mais seguro)
# Ver scripts/migrate-sqlite-to-mysql.php
php scripts/migrate-sqlite-to-mysql.php
```

#### 1.6. Verificar e Testar

```bash
# Verificar contagem de registros
mysql -u isp_status -p isp_status_page

SELECT 'monitors' as table_name, COUNT(*) as count FROM monitors
UNION ALL
SELECT 'monitor_checks', COUNT(*) FROM monitor_checks
UNION ALL
SELECT 'incidents', COUNT(*) FROM incidents;

# Comparar com SQLite
sqlite3 database.db "SELECT COUNT(*) FROM monitors;"

# Testar aplicação
bin/cake server
# Acesse e verifique se tudo funciona
```

### 2. SQLite → PostgreSQL

Similar ao MySQL, mas com algumas diferenças:

#### 2.1. Preparar PostgreSQL

```bash
# Como usuário postgres
sudo -u postgres psql

CREATE DATABASE isp_status_page;
CREATE USER isp_status WITH PASSWORD 'senha_segura';
GRANT ALL PRIVILEGES ON DATABASE isp_status_page TO isp_status;
\q
```

#### 2.2. Migrar Dados

```bash
# Instalar ferramentas
sudo apt-get install pgloader  # Linux
brew install pgloader          # macOS

# Converter e migrar
pgloader database.db postgresql://isp_status:senha_segura@localhost/isp_status_page
```

#### 2.3. Atualizar Configuração

```env
DATABASE_URL="postgres://isp_status:senha_segura@localhost/isp_status_page"
```

### 3. MySQL → PostgreSQL

```bash
# Usar pgloader
pgloader mysql://user:pass@localhost/isp_status_page \
         postgresql://user:pass@localhost/isp_status_page
```

## Script de Migração Customizado

### scripts/migrate-sqlite-to-mysql.php

```php
<?php
#!/usr/bin/env php
/**
 * Migrate SQLite to MySQL
 * Usage: php scripts/migrate-sqlite-to-mysql.php
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use Cake\Datasource\ConnectionManager;
use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;

// Load configuration
Configure::config('default', new PhpConfig());
Configure::load('app', 'default', false);

echo "ISP Status Page - SQLite to MySQL Migration\n";
echo "===========================================\n\n";

// Connect to SQLite (source)
$sqliteConn = ConnectionManager::get('sqlite', [
    'className' => 'Cake\Database\Connection',
    'driver' => 'Cake\Database\Driver\Sqlite',
    'database' => dirname(__DIR__) . '/database.db',
]);

// Connect to MySQL (destination)
$mysqlUrl = env('DATABASE_URL');
$mysqlConn = ConnectionManager::get('default');

// Tables to migrate (in order due to foreign keys)
$tables = [
    'users',
    'settings',
    'integrations',
    'integration_logs',
    'monitors',
    'monitor_checks',
    'incidents',
    'subscribers',
    'subscriptions',
    'alert_rules',
    'alert_logs',
];

foreach ($tables as $table) {
    echo "Migrating table: $table... ";

    try {
        // Read from SQLite
        $rows = $sqliteConn->execute("SELECT * FROM $table")->fetchAll('assoc');

        if (empty($rows)) {
            echo "SKIP (empty)\n";
            continue;
        }

        // Disable foreign key checks temporarily
        $mysqlConn->execute('SET FOREIGN_KEY_CHECKS=0');

        // Clear existing data (optional, be careful!)
        // $mysqlConn->execute("TRUNCATE TABLE $table");

        // Insert into MySQL
        $count = 0;
        foreach ($rows as $row) {
            $mysqlConn->insert($table, $row);
            $count++;
        }

        // Re-enable foreign key checks
        $mysqlConn->execute('SET FOREIGN_KEY_CHECKS=1');

        echo "OK ($count rows)\n";

    } catch (\Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}

echo "\n===========================================\n";
echo "Migration complete!\n";
echo "Please verify data integrity and test the application.\n";
```

## Docker com MySQL

### docker-compose.mysql.yml

```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
      target: development
    container_name: isp-status-app
    ports:
      - "8765:80"
    volumes:
      - ./src:/var/www/html
    environment:
      - DATABASE_URL=mysql://isp_status:password@db/isp_status_page
    depends_on:
      - db
    networks:
      - isp-status-network

  db:
    image: mysql:8.0
    container_name: isp-status-mysql
    environment:
      MYSQL_ROOT_PASSWORD: rootpassword
      MYSQL_DATABASE: isp_status_page
      MYSQL_USER: isp_status
      MYSQL_PASSWORD: password
    volumes:
      - mysql-data:/var/lib/mysql
    ports:
      - "3306:3306"
    networks:
      - isp-status-network

networks:
  isp-status-network:
    driver: bridge

volumes:
  mysql-data:
    driver: local
```

**Uso:**
```bash
docker-compose -f docker-compose.mysql.yml up -d
docker-compose -f docker-compose.mysql.yml exec app bin/cake migrations migrate
```

## Performance por Banco

### Benchmarks Aproximados

**100 monitores, check a cada 60s:**

| Banco | Writes/min | Latência | CPU | RAM |
|-------|------------|----------|-----|-----|
| SQLite | 100 | 5-10ms | Baixo | 50MB |
| MySQL | 100 | 2-5ms | Médio | 150MB |
| PostgreSQL | 100 | 2-4ms | Médio | 200MB |

**500 monitores:**

| Banco | Writes/min | Latência | CPU | RAM |
|-------|------------|----------|-----|-----|
| SQLite | 500 | 50-100ms | Alto | 100MB |
| MySQL | 500 | 3-8ms | Médio | 300MB |
| PostgreSQL | 500 | 2-6ms | Médio | 400MB |

## Recomendações

### Use SQLite quando:
- Desenvolvimento
- Menos de 100 monitores
- Single server
- Simplicidade é prioridade
- Backup manual é aceitável

### Use MySQL quando:
- Produção
- 100-500 monitores
- Backup automatizado necessário
- Performance consistente importante
- Já tem expertise em MySQL

### Use PostgreSQL quando:
- 500+ monitores
- Queries complexas
- JSON/dados estruturados
- Replicação necessária
- Já tem expertise em PostgreSQL

## Troubleshooting

### Erro: "SQLSTATE[HY000] [2002] No such file or directory"

**Causa**: Socket do MySQL não encontrado

**Solução**:
```env
# Use IP ao invés de localhost
DATABASE_URL="mysql://user:pass@127.0.0.1/dbname"
```

### Erro: "SQLSTATE[42000]: Syntax error"

**Causa**: SQL incompatível entre bancos

**Solução**: Use as migrations do CakePHP, não import direto de SQL

### Performance degradada após migração

**Solução**:
```sql
-- MySQL
ANALYZE TABLE monitors;
OPTIMIZE TABLE monitors;

-- PostgreSQL
VACUUM ANALYZE monitors;
```

## Backup Cross-Database

### Backup Lógico (SQL Dump)

```bash
# SQLite
sqlite3 database.db .dump > backup.sql

# MySQL
mysqldump -u user -p database_name > backup.sql

# PostgreSQL
pg_dump -U user database_name > backup.sql
```

### Backup Físico (Arquivos)

```bash
# SQLite
cp database.db backup-$(date +%Y%m%d).db

# MySQL
# Usar mysqlhotcopy ou copy de /var/lib/mysql

# PostgreSQL
# Usar pg_basebackup
```

## Conclusão

A escolha do banco depende do seu caso de uso. SQLite é perfeito para começar e pequenos deployments. MySQL/PostgreSQL são melhores para produção com volume maior.

A migração é simples usando as ferramentas certas e pode ser feita sem downtime significativo.

---

**Migração facilitada! 🗄️**
