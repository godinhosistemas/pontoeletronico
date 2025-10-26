# Makefile - Sistema de Ponto Eletrônico
# Comandos simplificados para gerenciar a aplicação Docker

.PHONY: help build up down restart logs shell db-shell migrate fresh seed cache-clear backup deploy

# Variáveis
COMPOSE=docker-compose
APP_CONTAINER=app
DB_CONTAINER=db
QUEUE_CONTAINER=queue

help: ## Mostra esta mensagem de ajuda
	@echo "Sistema de Ponto Eletrônico - Comandos disponíveis:"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

# ==================================
# Setup e Build
# ==================================

setup: ## Setup inicial da aplicação
	@echo "🚀 Executando setup inicial..."
	./setup.sh

build: ## Build das imagens Docker
	@echo "🔨 Building images..."
	$(COMPOSE) build --no-cache

build-fast: ## Build rápido (com cache)
	@echo "⚡ Fast building..."
	$(COMPOSE) build

# ==================================
# Containers
# ==================================

up: ## Inicia todos os containers
	@echo "▶️  Iniciando containers..."
	$(COMPOSE) up -d
	@echo "✅ Containers iniciados!"

down: ## Para todos os containers
	@echo "⏹️  Parando containers..."
	$(COMPOSE) down
	@echo "✅ Containers parados!"

restart: ## Reinicia todos os containers
	@echo "🔄 Reiniciando containers..."
	$(COMPOSE) restart
	@echo "✅ Containers reiniciados!"

ps: ## Lista containers em execução
	@$(COMPOSE) ps

stats: ## Mostra estatísticas dos containers
	@docker stats --no-stream

# ==================================
# Logs
# ==================================

logs: ## Mostra logs de todos os containers
	@$(COMPOSE) logs -f

logs-app: ## Mostra logs da aplicação
	@$(COMPOSE) logs -f $(APP_CONTAINER)

logs-queue: ## Mostra logs da queue
	@$(COMPOSE) logs -f $(QUEUE_CONTAINER)

logs-db: ## Mostra logs do banco de dados
	@$(COMPOSE) logs -f $(DB_CONTAINER)

# ==================================
# Shell Access
# ==================================

shell: ## Acessa shell do container da aplicação
	@$(COMPOSE) exec $(APP_CONTAINER) bash

db-shell: ## Acessa MySQL shell
	@$(COMPOSE) exec $(DB_CONTAINER) mysql -u root -p

redis-cli: ## Acessa Redis CLI
	@$(COMPOSE) exec redis redis-cli

# ==================================
# Laravel Artisan
# ==================================

artisan: ## Executa comando artisan (use: make artisan cmd="migrate")
	@$(COMPOSE) exec $(APP_CONTAINER) php artisan $(cmd)

migrate: ## Executa migrations
	@echo "🔄 Executando migrations..."
	@$(COMPOSE) exec $(APP_CONTAINER) php artisan migrate --force

migrate-fresh: ## Recria banco de dados (CUIDADO!)
	@echo "⚠️  ATENÇÃO: Isso irá APAGAR todos os dados!"
	@read -p "Tem certeza? [y/N]: " confirm && [ "$$confirm" = "y" ]
	@$(COMPOSE) exec $(APP_CONTAINER) php artisan migrate:fresh --force

seed: ## Executa seeders
	@echo "🌱 Executando seeders..."
	@$(COMPOSE) exec $(APP_CONTAINER) php artisan db:seed --force

fresh: ## Recria banco com seeders (CUIDADO!)
	@echo "⚠️  ATENÇÃO: Isso irá APAGAR todos os dados!"
	@read -p "Tem certeza? [y/N]: " confirm && [ "$$confirm" = "y" ]
	@$(COMPOSE) exec $(APP_CONTAINER) php artisan migrate:fresh --seed --force

tinker: ## Abre Laravel Tinker
	@$(COMPOSE) exec $(APP_CONTAINER) php artisan tinker

# ==================================
# Cache
# ==================================

cache-clear: ## Limpa todos os caches
	@echo "🧹 Limpando caches..."
	@$(COMPOSE) exec $(APP_CONTAINER) php artisan config:clear
	@$(COMPOSE) exec $(APP_CONTAINER) php artisan route:clear
	@$(COMPOSE) exec $(APP_CONTAINER) php artisan view:clear
	@$(COMPOSE) exec $(APP_CONTAINER) php artisan cache:clear
	@echo "✅ Caches limpos!"

cache-optimize: ## Otimiza caches para produção
	@echo "⚡ Otimizando caches..."
	@$(COMPOSE) exec $(APP_CONTAINER) php artisan config:cache
	@$(COMPOSE) exec $(APP_CONTAINER) php artisan route:cache
	@$(COMPOSE) exec $(APP_CONTAINER) php artisan view:cache
	@echo "✅ Caches otimizados!"

# ==================================
# Queue
# ==================================

queue-restart: ## Reinicia queue workers
	@echo "🔄 Reiniciando queue workers..."
	@$(COMPOSE) restart $(QUEUE_CONTAINER)
	@echo "✅ Queue workers reiniciados!"

queue-work: ## Executa queue worker manualmente
	@$(COMPOSE) exec $(APP_CONTAINER) php artisan queue:work

queue-failed: ## Lista jobs que falharam
	@$(COMPOSE) exec $(APP_CONTAINER) php artisan queue:failed

queue-retry: ## Tenta novamente jobs que falharam
	@$(COMPOSE) exec $(APP_CONTAINER) php artisan queue:retry all

# ==================================
# Banco de Dados
# ==================================

backup: ## Faz backup do banco de dados
	@echo "💾 Criando backup do banco de dados..."
	@mkdir -p backups
	@$(COMPOSE) exec -T $(DB_CONTAINER) mysqldump -u root -p$${DB_ROOT_PASSWORD} $${DB_DATABASE} > backups/backup_$$(date +%Y%m%d_%H%M%S).sql
	@echo "✅ Backup criado em backups/"

restore: ## Restaura backup (use: make restore file=backup.sql)
	@echo "📥 Restaurando backup..."
	@$(COMPOSE) exec -T $(DB_CONTAINER) mysql -u root -p$${DB_ROOT_PASSWORD} $${DB_DATABASE} < $(file)
	@echo "✅ Backup restaurado!"

# ==================================
# Testes
# ==================================

test: ## Executa testes
	@$(COMPOSE) exec $(APP_CONTAINER) php artisan test

test-coverage: ## Executa testes com coverage
	@$(COMPOSE) exec $(APP_CONTAINER) php artisan test --coverage

# ==================================
# Deploy
# ==================================

deploy: ## Deploy em produção
	@echo "🚀 Executando deploy..."
	./deploy.sh production

deploy-staging: ## Deploy em staging
	@echo "🚀 Executando deploy (staging)..."
	./deploy.sh staging

# ==================================
# Limpeza
# ==================================

clean: ## Limpa containers, volumes e imagens não utilizados
	@echo "🧹 Limpando recursos Docker não utilizados..."
	@docker system prune -af --volumes
	@echo "✅ Limpeza concluída!"

clean-logs: ## Limpa logs antigos
	@echo "🧹 Limpando logs..."
	@find storage/logs -name "*.log" -type f -mtime +30 -delete
	@echo "✅ Logs limpos!"

# ==================================
# Desenvolvimento
# ==================================

npm-install: ## Instala dependências NPM
	@$(COMPOSE) exec $(APP_CONTAINER) npm install

npm-dev: ## Executa Vite dev server
	@$(COMPOSE) exec $(APP_CONTAINER) npm run dev

npm-build: ## Build assets para produção
	@$(COMPOSE) exec $(APP_CONTAINER) npm run build

composer-install: ## Instala dependências Composer
	@$(COMPOSE) exec $(APP_CONTAINER) composer install

composer-update: ## Atualiza dependências Composer
	@$(COMPOSE) exec $(APP_CONTAINER) composer update

# ==================================
# Permissões
# ==================================

permissions: ## Corrige permissões de storage e cache
	@echo "🔒 Corrigindo permissões..."
	@$(COMPOSE) exec $(APP_CONTAINER) chown -R www:www /var/www/html/storage
	@$(COMPOSE) exec $(APP_CONTAINER) chown -R www:www /var/www/html/bootstrap/cache
	@$(COMPOSE) exec $(APP_CONTAINER) chmod -R 775 /var/www/html/storage
	@$(COMPOSE) exec $(APP_CONTAINER) chmod -R 775 /var/www/html/bootstrap/cache
	@echo "✅ Permissões corrigidas!"

# ==================================
# Monitoring
# ==================================

health: ## Verifica saúde da aplicação
	@echo "🏥 Verificando saúde dos serviços..."
	@curl -f http://localhost:8000/health || echo "❌ App não está saudável"
	@$(COMPOSE) exec $(DB_CONTAINER) mysqladmin ping -h localhost || echo "❌ Database não está saudável"
	@$(COMPOSE) exec redis redis-cli ping || echo "❌ Redis não está saudável"
	@echo "✅ Verificação concluída!"
