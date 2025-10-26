# Dockerfile para Laravel - Sistema de Ponto Eletrônico
# Otimizado para produção

# Stage 1: Build assets
FROM node:20-alpine AS node-builder

WORKDIR /app

# Copiar package.json e package-lock.json
COPY package*.json ./

# Instalar dependências do Node
# Usa npm ci se package-lock.json existir, senão usa npm install
RUN if [ -f package-lock.json ]; then \
        npm ci --omit=dev; \
    else \
        npm install --production; \
    fi

# Copiar código fonte
COPY . .

# Build assets (se existir script build)
RUN if grep -q '"build"' package.json; then \
        npm run build; \
    else \
        echo "No build script found, skipping..."; \
    fi


# Stage 2: PHP Application
FROM php:8.3-fpm-alpine

# Instalar dependências do sistema
RUN apk add --no-cache \
    bash \
    curl \
    git \
    zip \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    postgresql-dev \
    icu-dev \
    oniguruma-dev \
    libxml2-dev \
    supervisor \
    nginx \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        pdo_pgsql \
        gd \
        zip \
        intl \
        mbstring \
        xml \
        bcmath \
        opcache \
        pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis

# Configurar PHP para produção
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Criar usuário e grupo
RUN addgroup -g 1000 www && \
    adduser -D -u 1000 -G www www

# Definir diretório de trabalho
WORKDIR /var/www/html

# Copiar composer files
COPY composer.json composer.lock ./

# Instalar dependências do Composer (sem dev)
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --optimize-autoloader

# Copiar código da aplicação
COPY --chown=www:www . .

# Copiar assets buildados do Node
COPY --from=node-builder --chown=www:www /app/public/build ./public/build

# Finalizar instalação do Composer
RUN composer dump-autoload --optimize --classmap-authoritative

# Configurar permissões
RUN chown -R www:www /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Copiar configurações do Nginx e Supervisor
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Criar script de entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Expor portas
EXPOSE 80

# Healthcheck
HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

# Entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

# Comando padrão
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
