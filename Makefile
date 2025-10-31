# ISP Status Page - Makefile
# Simplifica comandos Docker e desenvolvimento

.PHONY: help dev prod build up down logs shell clean migrate seed test test-all test-unit test-coverage

# Default target
.DEFAULT_GOAL := help

# Colors for output
BLUE := \033[0;34m
GREEN := \033[0;32m
YELLOW := \033[0;33m
RED := \033[0;31m
NC := \033[0m # No Color

help: ## Mostra esta ajuda
	@echo "$(BLUE)ISP Status Page - Comandos Disponíveis$(NC)"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "$(GREEN)%-15s$(NC) %s\n", $$1, $$2}'
	@echo ""

# Development commands
dev: ## Inicia ambiente de desenvolvimento (docker-compose up)
	@echo "$(BLUE)Iniciando ambiente de desenvolvimento...$(NC)"
	docker-compose up -d
	@echo "$(GREEN)✓ Ambiente iniciado!$(NC)"
	@echo "$(YELLOW)Acesse: http://localhost:8765$(NC)"

dev-build: ## Build e inicia ambiente de desenvolvimento
	@echo "$(BLUE)Building e iniciando ambiente...$(NC)"
	docker-compose up -d --build
	@echo "$(GREEN)✓ Ambiente pronto!$(NC)"

dev-logs: ## Mostra logs do ambiente de desenvolvimento
	docker-compose logs -f

dev-down: ## Para ambiente de desenvolvimento
	@echo "$(BLUE)Parando ambiente de desenvolvimento...$(NC)"
	docker-compose down
	@echo "$(GREEN)✓ Ambiente parado!$(NC)"

dev-restart: ## Reinicia ambiente de desenvolvimento
	@echo "$(BLUE)Reiniciando ambiente...$(NC)"
	docker-compose restart
	@echo "$(GREEN)✓ Ambiente reiniciado!$(NC)"

# Production commands
prod: ## Inicia ambiente de produção
	@echo "$(BLUE)Iniciando ambiente de produção...$(NC)"
	docker-compose -f docker-compose.prod.yml up -d
	@echo "$(GREEN)✓ Produção iniciada!$(NC)"

prod-build: ## Build e inicia ambiente de produção
	@echo "$(BLUE)Building produção...$(NC)"
	docker-compose -f docker-compose.prod.yml up -d --build
	@echo "$(GREEN)✓ Produção pronta!$(NC)"

prod-logs: ## Mostra logs de produção
	docker-compose -f docker-compose.prod.yml logs -f

prod-down: ## Para ambiente de produção
	@echo "$(BLUE)Parando produção...$(NC)"
	docker-compose -f docker-compose.prod.yml down
	@echo "$(GREEN)✓ Produção parada!$(NC)"

# Build commands
build: ## Build das imagens Docker
	@echo "$(BLUE)Building imagens...$(NC)"
	docker-compose build
	@echo "$(GREEN)✓ Build completo!$(NC)"

build-prod: ## Build da imagem de produção
	@echo "$(BLUE)Building imagem de produção...$(NC)"
	docker build -t isp-status-page:latest --target production .
	@echo "$(GREEN)✓ Imagem de produção criada!$(NC)"

# Container management
shell: ## Acessa shell do container da aplicação
	@echo "$(BLUE)Acessando shell...$(NC)"
	docker-compose exec app bash

shell-root: ## Acessa shell como root
	@echo "$(BLUE)Acessando shell como root...$(NC)"
	docker-compose exec -u root app bash

logs: ## Mostra todos os logs
	docker-compose logs -f

logs-app: ## Mostra logs apenas da aplicação
	docker-compose logs -f app

logs-cron: ## Mostra logs do cron
	docker-compose logs -f cron

# Database commands
migrate: ## Executa migrations
	@echo "$(BLUE)Executando migrations...$(NC)"
	docker-compose exec app bin/cake migrations migrate
	@echo "$(GREEN)✓ Migrations executadas!$(NC)"

migrate-rollback: ## Rollback última migration
	@echo "$(BLUE)Rollback migration...$(NC)"
	docker-compose exec app bin/cake migrations rollback
	@echo "$(GREEN)✓ Rollback completo!$(NC)"

migrate-status: ## Status das migrations
	docker-compose exec app bin/cake migrations status

seed: ## Executa seeds
	@echo "$(BLUE)Executando seeds...$(NC)"
	docker-compose exec app bin/cake migrations seed
	@echo "$(GREEN)✓ Seeds executados!$(NC)"

db-reset: ## Reset completo do banco (CUIDADO!)
	@echo "$(RED)⚠ Isso vai apagar TODOS os dados!$(NC)"
	@read -p "Tem certeza? [y/N]: " confirm && [ "$$confirm" = "y" ] || exit 1
	docker-compose exec app rm -f database.db
	docker-compose exec app touch database.db
	docker-compose exec app chown www-data:www-data database.db
	docker-compose exec app chmod 666 database.db
	$(MAKE) migrate
	$(MAKE) seed
	@echo "$(GREEN)✓ Banco resetado!$(NC)"

# Development tools
test: ## Executa todos os testes
	@echo "$(BLUE)Executando todos os testes...$(NC)"
	docker-compose exec app vendor/bin/phpunit
	@echo "$(GREEN)✓ Testes completos!$(NC)"

test-unit: ## Executa apenas testes unitários
	@echo "$(BLUE)Executando testes unitários...$(NC)"
	docker-compose exec app vendor/bin/phpunit tests/TestCase/Controller/
	@echo "$(GREEN)✓ Testes unitários completos!$(NC)"

test-controllers: ## Executa testes de controllers
	@echo "$(BLUE)Executando testes de controllers...$(NC)"
	docker-compose exec app vendor/bin/phpunit tests/TestCase/Controller/
	@echo "$(GREEN)✓ Testes de controllers completos!$(NC)"

test-models: ## Executa testes de models
	@echo "$(BLUE)Executando testes de models...$(NC)"
	docker-compose exec app vendor/bin/phpunit tests/TestCase/Model/
	@echo "$(GREEN)✓ Testes de models completos!$(NC)"

test-specific: ## Executa teste específico - use: make test-specific FILE=UsersControllerTest
	@echo "$(BLUE)Executando teste específico...$(NC)"
	docker-compose exec app vendor/bin/phpunit tests/TestCase/Controller/$(FILE).php
	@echo "$(GREEN)✓ Teste completo!$(NC)"

test-coverage: ## Executa testes com coverage HTML
	@echo "$(BLUE)Executando testes com coverage...$(NC)"
	docker-compose exec app vendor/bin/phpunit --coverage-html tmp/coverage
	@echo "$(GREEN)✓ Coverage gerado em tmp/coverage/index.html$(NC)"

test-coverage-text: ## Executa testes com coverage em texto
	@echo "$(BLUE)Executando testes com coverage...$(NC)"
	docker-compose exec app vendor/bin/phpunit --coverage-text

test-watch: ## Executa testes em modo watch (requer phpunit-watcher)
	@echo "$(BLUE)Executando testes em modo watch...$(NC)"
	docker-compose exec app vendor/bin/phpunit-watcher watch

cs-check: ## Verifica padrões de código
	docker-compose exec app vendor/bin/phpcs

cs-fix: ## Corrige padrões de código automaticamente
	docker-compose exec app vendor/bin/phpcbf

console: ## Acessa console CakePHP
	docker-compose exec app bin/cake console

bake: ## Comando bake - use: make bake ARGS="model User"
	docker-compose exec app bin/cake bake $(ARGS)

# Maintenance
clean: ## Limpa cache e arquivos temporários
	@echo "$(BLUE)Limpando cache...$(NC)"
	docker-compose exec app bin/cake cache clear_all
	@echo "$(GREEN)✓ Cache limpo!$(NC)"

clean-all: ## Limpa tudo (containers, volumes, imagens)
	@echo "$(RED)⚠ Isso vai remover containers, volumes e imagens!$(NC)"
	@read -p "Tem certeza? [y/N]: " confirm && [ "$$confirm" = "y" ] || exit 1
	docker-compose down -v
	docker-compose -f docker-compose.prod.yml down -v
	docker rmi isp-status-page:latest || true
	@echo "$(GREEN)✓ Tudo limpo!$(NC)"

backup: ## Cria backup do banco de dados
	@echo "$(BLUE)Criando backup...$(NC)"
	mkdir -p backups
	docker cp isp-status-app:/var/www/html/database.db backups/database-$$(date +%Y%m%d-%H%M%S).db
	@echo "$(GREEN)✓ Backup criado em backups/$(NC)"

restore: ## Restaura backup - use: make restore FILE=backups/database-20241031.db
	@echo "$(BLUE)Restaurando backup $(FILE)...$(NC)"
	docker cp $(FILE) isp-status-app:/var/www/html/database.db
	docker-compose exec app chown www-data:www-data database.db
	@echo "$(GREEN)✓ Backup restaurado!$(NC)"

# Status and info
status: ## Mostra status dos containers
	docker-compose ps

info: ## Mostra informações do ambiente
	@echo "$(BLUE)ISP Status Page - Informações$(NC)"
	@echo ""
	@echo "$(GREEN)Containers:$(NC)"
	@docker-compose ps
	@echo ""
	@echo "$(GREEN)Versão CakePHP:$(NC)"
	@docker-compose exec app bin/cake version 2>/dev/null || echo "Container não está rodando"
	@echo ""
	@echo "$(GREEN)Status do Banco:$(NC)"
	@docker-compose exec app bin/cake migrations status 2>/dev/null || echo "Container não está rodando"

# Quick start
quick-start: ## Setup rápido completo (build + up + migrate + seed)
	@echo "$(BLUE)========================================$(NC)"
	@echo "$(BLUE)ISP Status Page - Quick Start$(NC)"
	@echo "$(BLUE)========================================$(NC)"
	$(MAKE) dev-build
	@sleep 5
	@echo ""
	@echo "$(YELLOW)Aguardando container inicializar...$(NC)"
	@sleep 5
	@echo ""
	@echo "$(GREEN)✓ Setup completo!$(NC)"
	@echo "$(YELLOW)Acesse: http://localhost:8765$(NC)"
	@echo "$(YELLOW)Login padrão: admin / admin123$(NC)"
	@echo ""
	@echo "$(BLUE)Próximos passos:$(NC)"
	@echo "  - make logs      # Ver logs"
	@echo "  - make shell     # Acessar container"
	@echo "  - make migrate   # Executar migrations"
	@echo "  - make test      # Rodar testes"

# Install (first time setup)
install: quick-start ## Instalação completa (alias para quick-start)
