# Guia Docker - ISP Status Page

Este documento detalha como usar Docker para desenvolvimento e produção.

## Requisitos

- **Docker**: 20.10+
- **Docker Compose**: 2.0+

### Instalação do Docker

#### Linux (Ubuntu/Debian)
```bash
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER
```

#### macOS
```bash
brew install docker docker-compose
# ou baixe Docker Desktop de https://www.docker.com/products/docker-desktop
```

#### Windows
Baixe Docker Desktop de https://www.docker.com/products/docker-desktop

## Quick Start - Desenvolvimento

### Opção 1: Usando Makefile (Recomendado)

```bash
# Instalação completa em um comando
make quick-start

# Ou passo a passo
make dev-build   # Build e inicia containers
make logs        # Ver logs
make shell       # Acessar container
```

### Opção 2: Docker Compose Direto

```bash
# Iniciar ambiente
docker-compose up -d

# Ver logs
docker-compose logs -f

# Acessar shell
docker-compose exec app bash
```

Acesse: **http://localhost:8765**

## Comandos Úteis (Makefile)

### Desenvolvimento

```bash
make help            # Lista todos os comandos disponíveis
make dev             # Inicia ambiente de desenvolvimento
make dev-build       # Build + iniciar
make dev-logs        # Ver logs
make dev-down        # Para ambiente
make dev-restart     # Reiniciar
```

### Banco de Dados

```bash
make migrate         # Executar migrations
make migrate-status  # Ver status das migrations
make seed            # Executar seeds
make db-reset        # Reset completo (CUIDADO!)
make backup          # Backup do banco
```

### Desenvolvimento

```bash
make shell           # Acessar shell do container
make console         # Console CakePHP
make test            # Rodar testes
make cs-check        # Verificar padrões de código
make cs-fix          # Corrigir código automaticamente
```

### Bake (Gerador)

```bash
make bake ARGS="model User"
make bake ARGS="controller Users"
make bake ARGS="migration CreateUsers"
```

### Limpeza

```bash
make clean           # Limpar cache
make clean-all       # Limpar tudo (containers + volumes)
```

## Estrutura Docker

### Containers

O ambiente cria 3 containers principais:

1. **app** (`isp-status-app`): Aplicacao PHP + Apache
   - Porta: 8765 -> 80
   - Apache com mod_rewrite
   - PHP 8.4 com extensoes necessarias (pdo_pgsql, pgsql, phpredis, intl, mbstring, curl, xml)
   - Composer
   - Aguarda PostgreSQL estar pronto antes de iniciar

2. **postgres** (`isp-status-postgres`): PostgreSQL 16
   - Porta: 5432
   - Banco de dados principal da aplicacao
   - Volume persistente para dados
   - Healthcheck via pg_isready

3. **redis** (`isp-status-redis`): Redis 7
   - Porta: 6379
   - Cache, sessoes e fila de jobs
   - Databases: 0 (cache default), 1 (cache _cake_model_), 2 (cache _cake_routes_), 3 (sessoes)
   - Healthcheck via redis-cli ping

### Volumes

Desenvolvimento monta os seguintes volumes:
- `./src` -> `/var/www/html` (codigo fonte - hot reload)
- `./src/logs` -> `/var/www/html/logs` (logs)
- `postgres-data` -> `/var/lib/postgresql/data` (dados PostgreSQL)
- `redis-data` -> `/data` (dados Redis)

### Network

Usa network bridge `isp-status-network` para comunicacao entre containers.

### Infraestrutura

| Servico    | Imagem           | Porta | Finalidade                        |
|-----------|------------------|-------|-----------------------------------|
| app       | php:8.4-apache   | 8765  | Aplicacao CakePHP                 |
| postgres  | postgres:16      | 5432  | Banco de dados principal          |
| redis     | redis:7-alpine   | 6379  | Cache, sessoes, fila de jobs      |

## Desenvolvimento com Hot Reload

O código fonte é montado como volume, permitindo **hot reload**:

```bash
# Edite arquivos localmente
vim src/src/Controller/UsersController.php

# Mudanças são refletidas imediatamente no container
# Apenas recarregue o navegador
```

## Migrations e Seeds

### Via Makefile

```bash
# Criar migration
make bake ARGS="migration CreateUsers"

# Editar migration
vim src/config/Migrations/YYYYMMDDHHMMSS_CreateUsers.php

# Executar
make migrate

# Ver status
make migrate-status

# Rollback
make migrate-rollback
```

### Via Docker Compose

```bash
docker-compose exec app bin/cake bake migration CreateUsers
docker-compose exec app bin/cake migrations migrate
docker-compose exec app bin/cake migrations status
```

## Testes

```bash
# Todos os testes
make test

# Com coverage
make test-coverage

# Acessar coverage
open src/tmp/coverage/index.html
```

## Console CakePHP

```bash
# Via Makefile
make console

# Via Docker Compose
docker-compose exec app bin/cake console
```

## Logs

### Ver Logs

```bash
# Todos os logs
make logs

# Apenas app
make logs-app

# Apenas cron
make logs-cron

# Logs do CakePHP (dentro do container)
make shell
tail -f logs/error.log
tail -f logs/cron.log
```

### Localização dos Logs

Dentro do container:
- `/var/www/html/logs/error.log` - Erros da aplicação
- `/var/www/html/logs/debug.log` - Debug
- `/var/www/html/logs/cron.log` - Cron jobs
- `/var/www/html/logs/cleanup.log` - Limpeza
- `/var/www/html/logs/backup.log` - Backups

Local (montado):
- `./src/logs/`

## Produção

### Build da Imagem

```bash
# Build
make build-prod

# Ou manualmente
docker build -t isp-status-page:latest --target production .
```

### Deploy com Docker Compose

```bash
# Copiar e configurar variáveis
cp .env.docker.example .env.docker
nano .env.docker

# IMPORTANTE: Gerar security salt
php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"
# Copiar resultado para .env.docker

# Iniciar produção
make prod

# Ou
docker-compose -f docker-compose.prod.yml up -d
```

### Deploy Manual (Docker Run)

```bash
# Build
docker build -t isp-status-page:latest --target production .

# Run
docker run -d \
  --name isp-status-app \
  -p 80:80 \
  -v isp-status-db:/var/www/html/database.db \
  -v isp-status-logs:/var/www/html/logs \
  -e SECURITY_SALT="sua-security-salt-aqui" \
  isp-status-page:latest
```

### Variáveis de Ambiente (Produção)

Edite `.env.docker` ou defina via `docker-compose`:

```env
APP_NAME=ISP Status Page
APP_DEBUG=false
SECURITY_SALT=change-this-to-random-64-char-hex
EMAIL_HOST=smtp.seuservidor.com
EMAIL_PORT=587
EMAIL_USERNAME=noreply@seudominio.com
EMAIL_PASSWORD=senha-segura
EMAIL_FROM=noreply@seudominio.com
```

## Publish no Docker Hub

```bash
# Tag
docker tag isp-status-page:latest seuusuario/isp-status-page:1.0.0
docker tag isp-status-page:latest seuusuario/isp-status-page:latest

# Push
docker push seuusuario/isp-status-page:1.0.0
docker push seuusuario/isp-status-page:latest
```

## Uso da Imagem Pública

Quando publicado no Docker Hub:

```bash
# Pull
docker pull seuusuario/isp-status-page:latest

# Run
docker run -d \
  --name isp-status \
  -p 80:80 \
  -v $(pwd)/database.db:/var/www/html/database.db \
  -e SECURITY_SALT="sua-salt" \
  seuusuario/isp-status-page:latest
```

## Troubleshooting

### Container não inicia

```bash
# Ver logs
make logs

# Ver status
make status

# Rebuild
make dev-build
```

### Erro de permissões

```bash
# Acessar como root e corrigir
make shell-root
chown -R www-data:www-data /var/www/html
chmod -R 777 /var/www/html/tmp
chmod -R 777 /var/www/html/logs
chmod 666 /var/www/html/database.db
```

### Migrations não executam

```bash
# Verificar se migrations existem
make shell
ls config/Migrations/

# Executar manualmente
make migrate

# Ver status
make migrate-status
```

### Porta 8765 em uso

Edite `docker-compose.yml`:
```yaml
ports:
  - "8080:80"  # Mude para outra porta
```

### Cron não está executando

```bash
# Ver logs do cron
make logs-cron

# Acessar container cron
docker-compose exec cron bash

# Verificar crontab
crontab -l

# Ver logs do cron
tail -f /var/www/html/logs/cron.log
```

## Performance

### Otimizações para Produção

1. **Usar build multi-stage** (já configurado)
2. **Volumes nomeados** ao invés de bind mounts
3. **Logs limitados**:
   ```yaml
   logging:
     driver: "json-file"
     options:
       max-size: "10m"
       max-file: "3"
   ```
4. **Health checks** configurados
5. **Restart policies**: `restart: always`

### Monitoramento

```bash
# Stats em tempo real
docker stats isp-status-app

# Uso de recursos
docker-compose top
```

## Backup e Restore

### Backup

```bash
# Via Makefile
make backup

# Manual (pg_dump)
docker-compose exec postgres pg_dump -U isp_status isp_status_page > backup-$(date +%Y%m%d).sql
```

### Restore

```bash
# Via Makefile
make restore FILE=backups/database-20241031.sql

# Manual (psql)
docker-compose exec -T postgres psql -U isp_status isp_status_page < backup-20241031.sql
```

## Docker Compose (Referencia)

Exemplo da configuracao atual com 3 servicos:

```yaml
services:
  app:
    build: .
    ports:
      - "8765:80"
    environment:
      DATABASE_URL: "postgres://isp_status:isp_status@postgres:5432/isp_status_page"
      REDIS_HOST: redis
      REDIS_PORT: 6379
    depends_on:
      postgres:
        condition: service_healthy
      redis:
        condition: service_healthy
    volumes:
      - ./src:/var/www/html
    networks:
      - isp-status-network

  postgres:
    image: postgres:16
    environment:
      POSTGRES_USER: isp_status
      POSTGRES_PASSWORD: isp_status
      POSTGRES_DB: isp_status_page
    volumes:
      - postgres-data:/var/lib/postgresql/data
    ports:
      - "5432:5432"
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U isp_status"]
      interval: 5s
      timeout: 5s
      retries: 5
    networks:
      - isp-status-network

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"
    volumes:
      - redis-data:/data
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 5s
      timeout: 5s
      retries: 5
    networks:
      - isp-status-network

volumes:
  postgres-data:
  redis-data:

networks:
  isp-status-network:
```

## CI/CD com Docker

Exemplo GitHub Actions:

```yaml
name: Docker Build

on: [push]

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Build image
        run: docker build -t isp-status-page:latest .
      - name: Run tests
        run: |
          docker-compose up -d
          docker-compose exec -T app vendor/bin/phpunit
```

## Recursos Adicionais

- [Docker Documentation](https://docs.docker.com)
- [Docker Compose Reference](https://docs.docker.com/compose/compose-file/)
- [CakePHP Docker](https://book.cakephp.org/5/en/installation.html#docker)

## Suporte

- **Issues**: GitHub Issues
- **Documentação**: `docs/`
- **Makefile Help**: `make help`

---

**Docker configurado e pronto para uso! 🐳**
