#!/bin/bash
set -e

echo "🚀 Starting Ponto Eletrônico Application..."

# Criar diretórios de log se não existirem
mkdir -p /var/log/php
mkdir -p /var/log/nginx
mkdir -p /var/log/supervisor

# Aguardar banco de dados estar pronto
echo "⏳ Waiting for database..."
until php artisan db:show 2>/dev/null; do
    echo "Database is unavailable - sleeping"
    sleep 2
done
echo "✅ Database is ready!"

# Executar migrations
if [ "${APP_ENV}" = "production" ]; then
    echo "🔄 Running migrations..."
    php artisan migrate --force --no-interaction
else
    echo "🔄 Running migrations with seeds..."
    php artisan migrate:fresh --seed --force --no-interaction
fi

# Limpar e otimizar caches
echo "🧹 Clearing and optimizing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

if [ "${APP_ENV}" = "production" ]; then
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
fi

# Otimizar autoloader
echo "⚡ Optimizing autoloader..."
composer dump-autoload --optimize --classmap-authoritative

# Link de storage
if [ ! -L /var/www/html/public/storage ]; then
    echo "🔗 Creating storage link..."
    php artisan storage:link
fi

# Ajustar permissões
echo "🔒 Setting permissions..."
chown -R www:www /var/www/html/storage
chown -R www:www /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

# Verificar chave da aplicação
if [ -z "${APP_KEY}" ] || [ "${APP_KEY}" = "base64:GENERATED_APP_KEY" ]; then
    echo "🔑 Generating application key..."
    php artisan key:generate --force
fi

echo "✅ Application is ready!"

# Executar comando passado
exec "$@"
