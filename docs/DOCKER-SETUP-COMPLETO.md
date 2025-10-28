# Setup Docker Completo - Sistema de Ponto Eletrônico

## 📦 Arquivos Criados

Este guia documenta todos os arquivos criados para deploy Docker na Hostinger.

### Estrutura de Arquivos

```
pontoeletronico/
├── Dockerfile                      # Imagem Docker otimizada para produção
├── docker-compose.yml              # Orquestração de containers
├── .dockerignore                   # Arquivos ignorados no build
├── Makefile                        # Comandos simplificados
├── setup.sh                        # Script de setup inicial
├── deploy.sh                       # Script de deploy automático
├── .env.production.example         # Variáveis de ambiente de produção
│
├── docker/
│   ├── nginx/
│   │   ├── nginx.conf             # Configuração global do Nginx
│   │   └── default.conf           # Virtual host Laravel
│   ├── php/
│   │   ├── php.ini                # Configuração PHP
│   │   └── opcache.ini            # Configuração OPcache
│   ├── mysql/
│   │   └── my.cnf                 # Configuração MySQL
│   ├── supervisor/
│   │   └── supervisord.conf       # Supervisor (Nginx + PHP-FPM)
│   └── entrypoint.sh              # Script de inicialização
│
├── .github/
│   └── workflows/
│       └── deploy.yml             # GitHub Actions CI/CD
│
├── DEPLOY.md                       # Documentação completa de deploy
└── README.docker.md                # Guia rápido Docker
```

---

## 🚀 Deploy na Hostinger - Passo a Passo

### 1️⃣ Preparar VPS Hostinger

```bash
# Conectar ao VPS
ssh root@SEU_IP_HOSTINGER

# Atualizar sistema
apt update && apt upgrade -y

# Instalar Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sh get-docker.sh

# Instalar Docker Compose
curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
chmod +x /usr/local/bin/docker-compose

# Verificar instalação
docker --version
docker-compose --version
```

### 2️⃣ Clonar Projeto

```bash
# Criar diretório
mkdir -p /var/www/pontoeletronico
cd /var/www/pontoeletronico

# Clonar repositório
git clone https://github.com/seu-usuario11/pontoeletronico.git .
```

### 3️⃣ Configurar Ambiente

```bash
# Copiar arquivo de ambiente
cp .env.production.example .env

# Editar configurações
nano .env
```

**Configurações obrigatórias:**

```env
# Domínio
APP_URL=https://seu-dominio.com.br

# Banco de dados (senhas fortes!)
DB_DATABASE=pontoeletronico
DB_USERNAME=pontouser
DB_PASSWORD=SenhaForte123!@#
DB_ROOT_PASSWORD=RootSenhaForte456!@#

# Redis
REDIS_PASSWORD=RedisSenhaForte789!@#

# Email
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=seu-email@gmail.com
MAIL_PASSWORD=sua-senha-app-gmail
MAIL_FROM_ADDRESS=seu-email@gmail.com

# Mercado Pago
MERCADOPAGO_PUBLIC_KEY=APP_USR-xxxxx
MERCADOPAGO_ACCESS_TOKEN=APP_USR-xxxxx
MERCADOPAGO_WEBHOOK_SECRET=seu-secret-webhook
```

### 4️⃣ Configurar Firewall

```bash
# Configurar UFW
ufw allow 22/tcp    # SSH
ufw allow 80/tcp    # HTTP
ufw allow 443/tcp   # HTTPS
ufw enable
```

### 5️⃣ Executar Setup

```bash
# Dar permissão de execução
chmod +x setup.sh deploy.sh docker/entrypoint.sh

# Executar setup inicial
./setup.sh
```

O script irá:
- ✅ Criar diretórios necessários
- ✅ Configurar permissões
- ✅ Construir imagens Docker
- ✅ Iniciar containers
- ✅ Executar migrations e seeders
- ✅ Otimizar aplicação

### 6️⃣ Configurar Domínio

**No painel da Hostinger:**

1. Vá em DNS/Nameservers
2. Adicione registro A:
   ```
   Type: A
   Name: @
   Value: SEU_IP_VPS
   TTL: 3600
   ```
3. Adicione www (opcional):
   ```
   Type: CNAME
   Name: www
   Value: seu-dominio.com.br
   TTL: 3600
   ```

### 7️⃣ Configurar SSL (HTTPS)

```bash
# Instalar Certbot
apt install certbot -y

# Parar Nginx temporariamente
docker-compose stop app

# Obter certificado
certbot certonly --standalone -d seu-dominio.com.br -d www.seu-dominio.com.br

# Atualizar docker-compose.yml
nano docker-compose.yml
```

Adicionar volumes SSL:
```yaml
app:
  volumes:
    - /etc/letsencrypt:/etc/letsencrypt:ro
  ports:
    - "80:80"
    - "443:443"
```

Atualizar `docker/nginx/default.conf`:
```nginx
server {
    listen 80;
    server_name seu-dominio.com.br;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name seu-dominio.com.br;

    ssl_certificate /etc/letsencrypt/live/seu-dominio.com.br/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/seu-dominio.com.br/privkey.pem;

    # ... resto da configuração
}
```

Reiniciar:
```bash
docker-compose up -d --build
```

Configurar renovação automática:
```bash
crontab -e
# Adicionar:
0 3 * * * certbot renew --quiet && docker-compose restart app
```

---

## 📋 Comandos Úteis

### Usando Makefile (Recomendado)

```bash
make help              # Ver todos os comandos
make up                # Iniciar containers
make down              # Parar containers
make restart           # Reiniciar containers
make logs              # Ver logs
make shell             # Acessar container
make migrate           # Executar migrations
make cache-clear       # Limpar caches
make cache-optimize    # Otimizar caches
make backup            # Backup do banco
make deploy            # Deploy automático
```

### Docker Compose

```bash
# Iniciar
docker-compose up -d

# Parar
docker-compose down

# Ver logs
docker-compose logs -f

# Status
docker-compose ps

# Executar comando
docker-compose exec app php artisan migrate

# Rebuild
docker-compose build --no-cache
docker-compose up -d --force-recreate
```

---

## 🔧 Manutenção

### Deploy de Atualizações

```bash
cd /var/www/pontoeletronico
./deploy.sh production
```

### Backup Automático

Criar script de backup:
```bash
nano /usr/local/bin/backup-pontoeletronico.sh
```

Conteúdo:
```bash
#!/bin/bash
cd /var/www/pontoeletronico
docker-compose exec -T db mysqldump -u root -p${DB_ROOT_PASSWORD} ${DB_DATABASE} | gzip > backups/backup_$(date +%Y%m%d_%H%M%S).sql.gz
find backups/ -name "*.sql.gz" -mtime +7 -delete
```

Tornar executável e agendar:
```bash
chmod +x /usr/local/bin/backup-pontoeletronico.sh
crontab -e
# Adicionar (backup diário às 3h):
0 3 * * * /usr/local/bin/backup-pontoeletronico.sh
```

### Monitoramento

```bash
# Ver uso de recursos
docker stats

# Health check
curl http://localhost:8000/health

# Logs em tempo real
docker-compose logs -f app

# Ver erros Laravel
docker-compose exec app tail -f storage/logs/laravel.log
```

---

## 🔒 Segurança

### Checklist de Segurança

- [x] Senhas fortes configuradas
- [ ] SSL/HTTPS configurado
- [x] Firewall (UFW) ativo
- [ ] SSH com chave pública
- [ ] Fail2ban instalado
- [x] Backups automáticos
- [x] Webhook secrets configurados
- [x] Validação HMAC implementada

### Configurar SSH com Chave

```bash
# No seu computador local
ssh-keygen -t ed25519 -C "seu-email@example.com"
ssh-copy-id root@SEU_IP_HOSTINGER

# No servidor, desabilitar senha
nano /etc/ssh/sshd_config
# Alterar:
PasswordAuthentication no

systemctl restart sshd
```

### Instalar Fail2ban

```bash
apt install fail2ban -y
systemctl enable fail2ban
systemctl start fail2ban
```

---

## 📊 Containers

### Aplicação Principal
- **Container:** pontoeletronico-app
- **Função:** Laravel + Nginx + PHP-FPM
- **Porta:** 80 (interno), 8000 (externo)

### Banco de Dados
- **Container:** pontoeletronico-db
- **Função:** MySQL 8.0
- **Porta:** 3306
- **Volume:** db-data

### Cache/Queue
- **Container:** pontoeletronico-redis
- **Função:** Redis
- **Porta:** 6379
- **Volume:** redis-data

### Queue Worker
- **Container:** pontoeletronico-queue
- **Função:** Processar jobs assíncronos

### Scheduler
- **Container:** pontoeletronico-scheduler
- **Função:** Executar tarefas agendadas (cron)

### PhpMyAdmin (Opcional)
- **Container:** pontoeletronico-phpmyadmin
- **Função:** Interface web MySQL
- **Porta:** 8080
- **Perfil:** debug (não inicia por padrão)

Para iniciar PhpMyAdmin:
```bash
docker-compose --profile debug up -d phpmyadmin
```

---

## 🚨 Troubleshooting

### Container não inicia

```bash
# Ver logs detalhados
docker-compose logs app

# Verificar configuração
docker-compose config

# Rebuild forçado
docker-compose build --no-cache
docker-compose up -d --force-recreate
```

### Erro de permissão

```bash
make permissions
# OU
docker-compose exec app chown -R www:www storage bootstrap/cache
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

### Banco não conecta

```bash
# Verificar se está rodando
docker-compose ps db

# Testar conexão
docker-compose exec app php artisan db:show

# Ver logs
docker-compose logs db
```

### Aplicação lenta

```bash
# Otimizar
make cache-optimize

# Verificar recursos
docker stats

# Ver processos
docker-compose exec app top
```

---

## 📱 Acessar Aplicação

### URLs

- **Aplicação:** https://seu-dominio.com.br
- **API:** https://seu-dominio.com.br/api
- **Admin:** https://seu-dominio.com.br/admin/dashboard
- **Health:** https://seu-dominio.com.br/health
- **PhpMyAdmin:** http://seu-ip:8080 (se habilitado)

### Credenciais Padrão (Super Admin)

```
Email: admin@admin.com
Senha: password
```

⚠️ **IMPORTANTE:** Alterar senha imediatamente após primeiro login!

---

## 🔄 CI/CD com GitHub Actions

Configurar secrets no GitHub:
1. Vá em Settings > Secrets and variables > Actions
2. Adicione:
   - `DEPLOY_HOST`: IP do servidor
   - `DEPLOY_USER`: usuário SSH (geralmente root)
   - `DEPLOY_SSH_KEY`: chave privada SSH

Cada push na branch `main` irá automaticamente fazer deploy.

---

## 📞 Suporte

### Logs

```bash
# Laravel
docker-compose exec app tail -f storage/logs/laravel.log

# Nginx
docker-compose exec app tail -f /var/log/nginx/error.log

# MySQL
docker-compose logs db

# Queue
docker-compose logs queue
```

### Comandos de Diagnóstico

```bash
# Health check completo
make health

# Ver todos os containers
docker ps -a

# Uso de disco
df -h
docker system df

# Processos
docker-compose top
```

---

## ✅ Checklist Final

Antes de colocar em produção:

- [ ] Domínio configurado e apontando para VPS
- [ ] SSL/HTTPS configurado e funcionando
- [ ] Variáveis de ambiente configuradas
- [ ] Senhas fortes definidas
- [ ] Migrations executadas
- [ ] Super admin criado e senha alterada
- [ ] Backups automáticos configurados
- [ ] Firewall configurado
- [ ] Logs sendo monitorados
- [ ] Health check respondendo
- [ ] Payment gateways testados
- [ ] Webhooks do Mercado Pago configurados
- [ ] Email SMTP testado
- [ ] Queue workers rodando
- [ ] Scheduler funcionando

---

## 📚 Documentação Adicional

- [DEPLOY.md](DEPLOY.md) - Guia completo de deploy
- [README.docker.md](README.docker.md) - Guia rápido Docker
- `make help` - Lista de todos os comandos disponíveis

---

**Setup criado por Claude Code em 25/10/2025**

Sistema de Ponto Eletrônico - Pronto para produção na Hostinger! 🚀
