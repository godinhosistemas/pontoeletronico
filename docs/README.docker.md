# Docker - Sistema de Ponto Eletrônico

Guia rápido para trabalhar com Docker neste projeto.

## Quick Start

```bash
# 1. Setup inicial (primeira vez)
make setup

# 2. Iniciar aplicação
make up

# 3. Acessar aplicação
# Browser: http://localhost:8000
```

## Comandos Principais

### Gerenciamento de Containers

```bash
make up              # Inicia containers
make down            # Para containers
make restart         # Reinicia containers
make ps              # Lista containers
make logs            # Ver logs em tempo real
make stats           # Estatísticas de uso
```

### Acesso aos Containers

```bash
make shell           # Shell da aplicação (bash)
make db-shell        # MySQL shell
make redis-cli       # Redis CLI
```

### Laravel Artisan

```bash
make migrate         # Executar migrations
make seed            # Executar seeders
make fresh           # Recriar banco com seeders
make tinker          # Abrir Laravel Tinker

# Comando customizado
make artisan cmd="route:list"
```

### Cache

```bash
make cache-clear     # Limpar todos os caches
make cache-optimize  # Otimizar caches (produção)
```

### Queue

```bash
make queue-restart   # Reiniciar queue workers
make queue-work      # Executar worker manualmente
make queue-failed    # Listar jobs que falharam
make queue-retry     # Retry de jobs falhados
```

### Banco de Dados

```bash
make backup                      # Criar backup
make restore file=backup.sql     # Restaurar backup
```

### Deploy

```bash
make deploy                      # Deploy produção
make deploy-staging              # Deploy staging
```

### Testes

```bash
make test                        # Executar testes
make test-coverage               # Testes com coverage
```

### Limpeza

```bash
make clean                       # Limpar recursos Docker
make clean-logs                  # Limpar logs antigos
```

### Desenvolvimento

```bash
make npm-install                 # Instalar dependências NPM
make npm-dev                     # Vite dev server
make npm-build                   # Build assets
make composer-install            # Instalar dependências
```

## Estrutura dos Containers

```
pontoeletronico-app         # Aplicação Laravel + Nginx + PHP-FPM
pontoeletronico-db          # MySQL 8.0
pontoeletronico-redis       # Redis (cache + queue)
pontoeletronico-queue       # Queue worker
pontoeletronico-scheduler   # Cron scheduler
pontoeletronico-phpmyadmin  # PhpMyAdmin (debug)
```

## Portas

| Serviço      | Porta Local | Porta Container |
|--------------|-------------|-----------------|
| App (HTTP)   | 8000        | 80              |
| MySQL        | 3306        | 3306            |
| Redis        | 6379        | 6379            |
| PhpMyAdmin   | 8080        | 80              |

## Volumes

```yaml
storage/              # Arquivos de storage
bootstrap/cache/      # Cache do framework
public/uploads/       # Uploads de usuários
db-data/             # Dados do MySQL
redis-data/          # Dados do Redis
```

## Variáveis de Ambiente

Edite o arquivo `.env` na raiz do projeto:

```env
APP_PORT=8000            # Porta HTTP da aplicação
DB_PORT=3306             # Porta MySQL
REDIS_PORT=6379          # Porta Redis
PMA_PORT=8080            # Porta PhpMyAdmin
```

## Logs

### Ver logs em tempo real
```bash
# Todos os serviços
docker-compose logs -f

# Serviço específico
docker-compose logs -f app
docker-compose logs -f db
docker-compose logs -f queue
```

### Logs do Laravel
```bash
# Dentro do container
make shell
tail -f storage/logs/laravel.log

# Ou diretamente
docker-compose exec app tail -f storage/logs/laravel.log
```

### Logs do Nginx
```bash
docker-compose exec app tail -f /var/log/nginx/error.log
docker-compose exec app tail -f /var/log/nginx/access.log
```

## Troubleshooting

### Container não inicia

```bash
# Ver logs detalhados
docker-compose logs app

# Verificar status
docker-compose ps

# Rebuild forçado
make build
```

### Erro de permissão

```bash
# Corrigir permissões
make permissions

# Ou manualmente
make shell
chown -R www:www storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### Banco de dados não conecta

```bash
# Verificar se banco está rodando
docker-compose ps db

# Testar conexão
make shell
php artisan db:show

# Ver logs do MySQL
docker-compose logs db
```

### Queue não processa

```bash
# Ver logs da queue
make logs-queue

# Restart queue worker
make queue-restart

# Processar manualmente
make queue-work
```

### Limpar tudo e recomeçar

```bash
# Para containers e remove volumes
docker-compose down -v

# Remove imagens
docker rmi pontoeletronico:latest

# Limpa sistema Docker
make clean

# Rebuild e restart
make build
make up
make migrate
```

## Performance

### Otimizar para Produção

```bash
# Otimizar caches
make cache-optimize

# Otimizar autoloader
docker-compose exec app composer dump-autoload --optimize --classmap-authoritative

# Habilitar OPcache (já configurado)
# Ver: docker/php/opcache.ini
```

### Monitorar Recursos

```bash
# Uso de recursos em tempo real
docker stats

# Ou via Makefile
make stats

# Espaço em disco
docker system df
```

## Backup e Restore

### Backup Completo

```bash
# Backup do banco
make backup

# Backup de arquivos
tar -czf backup_$(date +%Y%m%d).tar.gz storage/ .env backups/
```

### Restore

```bash
# Restore banco
make restore file=backups/backup_20250125_120000.sql

# Restore arquivos
tar -xzf backup_20250125.tar.gz
```

## Segurança

### Checklist

- [ ] Senhas fortes no .env
- [ ] SSL/HTTPS configurado
- [ ] Firewall configurado
- [ ] SSH com chave pública
- [ ] Volumes com permissões corretas
- [ ] Webhook secrets configurados
- [ ] Backups automatizados

### Atualizar Dependências

```bash
# Atualizar imagens base
docker-compose pull

# Rebuild com novas versões
make build

# Atualizar packages PHP
make composer-update

# Atualizar packages Node
docker-compose exec app npm update
```

## CI/CD

### GitHub Actions (exemplo)

```yaml
name: Deploy to Production

on:
  push:
    branches: [ main ]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2

      - name: Deploy to server
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.HOST }}
          username: ${{ secrets.USERNAME }}
          key: ${{ secrets.SSH_KEY }}
          script: |
            cd /var/www/pontoeletronico
            git pull origin main
            make deploy
```

## Links Úteis

- [Documentação Docker](https://docs.docker.com/)
- [Docker Compose Reference](https://docs.docker.com/compose/compose-file/)
- [Laravel Docker](https://laravel.com/docs/deployment#docker)
- [Deploy Completo](DEPLOY.md)

## Suporte

- Ver logs: `make logs`
- Health check: `make health`
- Shell access: `make shell`
- Todos comandos: `make help`
