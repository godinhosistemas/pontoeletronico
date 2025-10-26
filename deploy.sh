#!/bin/bash
# Script de Deploy - Ponto Eletrônico
# Uso: ./deploy.sh [environment]
# Exemplo: ./deploy.sh production

set -e

ENVIRONMENT=${1:-production}
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

echo "================================================"
echo "🚀 Deploy - Sistema de Ponto Eletrônico"
echo "================================================"
echo "Environment: $ENVIRONMENT"
echo "Timestamp: $TIMESTAMP"
echo "================================================"

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Função de log
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Verificar se está na branch correta
if [ "$ENVIRONMENT" = "production" ]; then
    CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
    if [ "$CURRENT_BRANCH" != "main" ] && [ "$CURRENT_BRANCH" != "master" ]; then
        log_error "Production deploy deve ser feito da branch main/master!"
        log_warning "Branch atual: $CURRENT_BRANCH"
        read -p "Continuar mesmo assim? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            exit 1
        fi
    fi
fi

# Verificar se arquivo .env existe
if [ ! -f ".env" ]; then
    log_error "Arquivo .env não encontrado!"
    log_info "Copiando .env.${ENVIRONMENT}.example para .env"
    if [ -f ".env.${ENVIRONMENT}.example" ]; then
        cp ".env.${ENVIRONMENT}.example" .env
    else
        cp .env.example .env
    fi
    log_warning "Configure o arquivo .env antes de continuar!"
    exit 1
fi

# Fazer backup do banco de dados (produção)
if [ "$ENVIRONMENT" = "production" ]; then
    log_info "Criando backup do banco de dados..."
    docker-compose exec -T db mysqldump -u root -p${DB_ROOT_PASSWORD} ${DB_DATABASE} > "backups/db_backup_${TIMESTAMP}.sql" 2>/dev/null || log_warning "Backup do banco falhou ou não foi possível"
fi

# Pull das últimas alterações
log_info "Obtendo últimas alterações do repositório..."
git pull origin $(git rev-parse --abbrev-ref HEAD)

# Build das imagens Docker
log_info "Construindo imagens Docker..."
docker-compose build --no-cache

# Parar containers antigos
log_info "Parando containers antigos..."
docker-compose down

# Iniciar novos containers
log_info "Iniciando novos containers..."
docker-compose up -d

# Aguardar containers estarem prontos
log_info "Aguardando containers ficarem prontos..."
sleep 10

# Verificar saúde dos containers
log_info "Verificando saúde dos containers..."
docker-compose ps

# Executar migrations
log_info "Executando migrations..."
docker-compose exec -T app php artisan migrate --force

# Limpar caches
log_info "Limpando e otimizando caches..."
docker-compose exec -T app php artisan config:cache
docker-compose exec -T app php artisan route:cache
docker-compose exec -T app php artisan view:cache

# Restart queue workers
log_info "Reiniciando queue workers..."
docker-compose restart queue

# Verificar logs
log_info "Verificando logs recentes..."
docker-compose logs --tail=50 app

echo "================================================"
log_info "✅ Deploy concluído com sucesso!"
echo "================================================"
echo ""
echo "Comandos úteis:"
echo "  - Ver logs: docker-compose logs -f"
echo "  - Status: docker-compose ps"
echo "  - Entrar no container: docker-compose exec app bash"
echo ""
