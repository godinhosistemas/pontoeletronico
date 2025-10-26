#!/bin/bash
# Script de Setup Inicial - Ponto Eletrônico
# Uso: ./setup.sh

set -e

echo "================================================"
echo "⚙️  Setup Inicial - Sistema de Ponto Eletrônico"
echo "================================================"

# Cores
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Verificar se Docker está instalado
if ! command -v docker &> /dev/null; then
    log_warning "Docker não está instalado!"
    echo "Por favor, instale o Docker: https://docs.docker.com/get-docker/"
    exit 1
fi

# Verificar se Docker Compose está instalado
if ! command -v docker-compose &> /dev/null; then
    log_warning "Docker Compose não está instalado!"
    echo "Por favor, instale o Docker Compose: https://docs.docker.com/compose/install/"
    exit 1
fi

# Criar arquivo .env se não existir
if [ ! -f ".env" ]; then
    log_info "Criando arquivo .env..."
    if [ -f ".env.production.example" ]; then
        cp .env.production.example .env
    else
        cp .env.example .env
    fi
    log_warning "Configure o arquivo .env antes de continuar!"
fi

# Criar diretórios necessários
log_info "Criando diretórios necessários..."
mkdir -p storage/app/public
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache
mkdir -p backups

# Definir permissões
log_info "Configurando permissões..."
chmod -R 775 storage
chmod -R 775 bootstrap/cache
chmod +x deploy.sh
chmod +x docker/entrypoint.sh

# Build das imagens
log_info "Construindo imagens Docker..."
docker-compose build

# Iniciar containers
log_info "Iniciando containers..."
docker-compose up -d

# Aguardar banco de dados
log_info "Aguardando banco de dados ficar pronto..."
sleep 15

# Instalar dependências
log_info "Instalando dependências do Composer..."
docker-compose exec app composer install --optimize-autoloader --no-dev

# Gerar chave da aplicação
log_info "Gerando chave da aplicação..."
docker-compose exec app php artisan key:generate --force

# Executar migrations
log_info "Executando migrations..."
docker-compose exec app php artisan migrate:fresh --seed --force

# Link de storage
log_info "Criando link de storage..."
docker-compose exec app php artisan storage:link

# Otimizar aplicação
log_info "Otimizando aplicação..."
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache

echo ""
echo "================================================"
log_info "✅ Setup concluído com sucesso!"
echo "================================================"
echo ""
echo "Aplicação disponível em: http://localhost:8000"
echo ""
echo "Credenciais padrão (Super Admin):"
echo "  Email: admin@admin.com"
echo "  Senha: password"
echo ""
echo "PhpMyAdmin: http://localhost:8080"
echo ""
echo "Comandos úteis:"
echo "  - Ver logs: docker-compose logs -f"
echo "  - Status: docker-compose ps"
echo "  - Parar: docker-compose down"
echo "  - Restart: docker-compose restart"
echo ""
