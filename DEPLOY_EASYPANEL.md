# 🚀 Deploy do Sistema de Ponto Eletrônico no Easypanel

Guia completo para fazer deploy do sistema em uma VPS usando Easypanel.

---

## 📋 Pré-requisitos

### No seu computador local:
- Git instalado
- Acesso ao repositório do projeto
- Chave SSH configurada no GitHub/GitLab

### Na VPS:
- VPS com Ubuntu 20.04+ ou Debian 11+
- Mínimo 2GB RAM (recomendado 4GB+)
- 20GB+ de espaço em disco
- Easypanel instalado
- Domínio apontado para o IP da VPS

---

## 1️⃣ Preparação Inicial

### 1.1. Verificar arquivos Docker

Certifique-se de que os seguintes arquivos estão no seu projeto:

```bash
ls -la
```

Você deve ter:
- ✅ `Dockerfile`
- ✅ `docker-compose.yml`
- ✅ `.env.example`
- ✅ `deploy.sh`

### 1.2. Preparar o repositório Git

```bash
# Fazer commit de todas as alterações
git add .
git commit -m "Preparar para deploy"
git push origin main
```

---

## 2️⃣ Configuração no Easypanel

### 2.1. Acessar Easypanel

1. Acesse o painel: `https://seu-ip-ou-dominio:3000`
2. Faça login com suas credenciais
3. Você verá o dashboard do Easypanel

### 2.2. Criar Novo Projeto

1. Clique em **"+ New Project"**
2. Digite o nome: `ponto-eletronico`
3. Clique em **"Create Project"**

### 2.3. Adicionar Serviços

#### A) Adicionar MySQL

1. Dentro do projeto, clique em **"+ Add Service"**
2. Selecione **"Database"**
3. Escolha **"MySQL 8.0"**
4. Configure:
   ```
   Nome: ponto-db
   Database: pontoeletronico
   Username: pontouser
   Password: [senha-forte-segura]
   Root Password: [senha-root-segura]
   ```
5. Clique em **"Create"**

#### B) Adicionar Redis

1. Clique em **"+ Add Service"**
2. Selecione **"Database"**
3. Escolha **"Redis 7"**
4. Configure:
   ```
   Nome: ponto-redis
   Password: [senha-redis-segura]
   ```
5. Clique em **"Create"**

#### C) Adicionar Aplicação Laravel

1. Clique em **"+ Add Service"**
2. Selecione **"App"**
3. Escolha **"From Git Repository"**
4. Configure:

**General:**
```
Nome: ponto-app
Repository: https://github.com/seu-usuario/pontoeletronico.git
Branch: main
Build Method: Dockerfile
```

**Build Settings:**
```
Dockerfile Path: ./Dockerfile
Build Context: .
```

**Port:**
```
Container Port: 80
Public Port: 80
```

---

## 3️⃣ Configurar Variáveis de Ambiente

### 3.1. No Easypanel

Vá até a aplicação `ponto-app` → **Environment Variables** e adicione:

```env
# Aplicação
APP_NAME="Sistema de Ponto Eletrônico"
APP_ENV=production
APP_KEY=base64:GERE_UMA_CHAVE_NOVA
APP_DEBUG=false
APP_URL=https://seu-dominio.com

# Banco de Dados
DB_CONNECTION=mysql
DB_HOST=ponto-db
DB_PORT=3306
DB_DATABASE=pontoeletronico
DB_USERNAME=pontouser
DB_PASSWORD=sua-senha-mysql
DB_ROOT_PASSWORD=sua-senha-root-mysql

# Redis
REDIS_HOST=ponto-redis
REDIS_PASSWORD=sua-senha-redis
REDIS_PORT=6379

# Cache & Queue
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Timezone
APP_TIMEZONE=America/Sao_Paulo
LOG_CHANNEL=daily

# Email (Opcional - Configure depois)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=seu-email@gmail.com
MAIL_PASSWORD=sua-senha-app
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@seu-dominio.com
MAIL_FROM_NAME="Ponto Eletrônico"

# Billing (Se usar)
STRIPE_KEY=sua-chave-stripe
STRIPE_SECRET=sua-chave-secreta
```

### 3.2. Gerar APP_KEY

No seu computador local:

```bash
php artisan key:generate --show
```

Copie a chave gerada (começa com `base64:`) e cole na variável `APP_KEY`.

---

## 4️⃣ Configurar Domínio e SSL

### 4.1. Configurar Domínio

1. No seu provedor de domínio (Registro.br, GoDaddy, etc.)
2. Adicione um registro DNS tipo **A**:
   ```
   Nome: @ ou ponto
   Tipo: A
   Valor: IP-DA-SUA-VPS
   TTL: 3600
   ```

3. (Opcional) Adicione subdomínio www:
   ```
   Nome: www
   Tipo: CNAME
   Valor: seu-dominio.com
   TTL: 3600
   ```

### 4.2. Configurar SSL no Easypanel

1. Na aplicação `ponto-app`
2. Vá em **Domains**
3. Clique em **"+ Add Domain"**
4. Digite: `seu-dominio.com`
5. Marque **"Enable SSL (Let's Encrypt)"**
6. Clique em **"Add"**

---

## 5️⃣ Deploy da Aplicação

### 5.1. Fazer o Build

1. No Easypanel, vá até `ponto-app`
2. Clique em **"Deploy"**
3. Aguarde o build completar (pode levar 5-10 minutos)
4. Monitore os logs em **"Logs"**

### 5.2. Executar Migrations

Após o deploy bem-sucedido:

1. No Easypanel, vá até `ponto-app`
2. Clique em **"Console"** ou **"Terminal"**
3. Execute:

```bash
php artisan migrate --force
```

### 5.3. Criar Usuário Admin

```bash
php artisan db:seed --class=AdminSeeder
```

Ou crie manualmente:

```bash
php artisan tinker
```

Depois execute:

```php
$user = new App\Models\User();
$user->name = 'Administrador';
$user->email = 'admin@exemplo.com';
$user->password = bcrypt('senha-segura');
$user->email_verified_at = now();
$user->save();
$user->assignRole('super-admin');
```

### 5.4. Otimizar Aplicação

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

---

## 6️⃣ Configurar Queue Worker

### 6.1. Adicionar Serviço Queue

1. Clique em **"+ Add Service"**
2. Selecione **"App"**
3. Configure:

**General:**
```
Nome: ponto-queue
Repository: [mesmo repositório]
Branch: main
Build Method: Dockerfile
```

**Command Override:**
```bash
php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
```

**Environment Variables:**
```
(Copie as mesmas variáveis de ponto-app)
```

### 6.2. Adicionar Scheduler (Cron)

1. Clique em **"+ Add Service"**
2. Configure:

**Command Override:**
```bash
bash -c "while true; do php artisan schedule:run --verbose --no-interaction & sleep 60; done"
```

---

## 7️⃣ Configurar Storage e Uploads

### 7.1. Criar Link Simbólico

No console da aplicação:

```bash
php artisan storage:link
```

### 7.2. Configurar Permissões

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

---

## 8️⃣ Verificação e Testes

### 8.1. Verificar Status

```bash
# Ver logs da aplicação
php artisan log:tail

# Verificar conexão com banco
php artisan migrate:status

# Testar queue
php artisan queue:work --once
```

### 8.2. Acessar a Aplicação

1. Abra o navegador
2. Acesse: `https://seu-dominio.com`
3. Faça login com as credenciais do admin
4. Verifique todas as funcionalidades

---

## 9️⃣ Monitoramento

### 9.1. Logs no Easypanel

- **Application Logs**: `ponto-app` → Logs
- **Queue Logs**: `ponto-queue` → Logs
- **Database Logs**: `ponto-db` → Logs

### 9.2. Logs do Laravel

```bash
# Dentro do container
tail -f storage/logs/laravel.log
```

---

## 🔟 Backup e Segurança

### 10.1. Backup Automático

Configure backup no Easypanel:

1. Vá em `ponto-db`
2. Configure backup diário
3. Ou use script manual:

```bash
# Backup do banco
docker exec ponto-db mysqldump -u root -p pontoeletronico > backup_$(date +%Y%m%d).sql

# Backup dos uploads
tar -czf uploads_$(date +%Y%m%d).tar.gz storage/app/public/
```

### 10.2. Segurança

```bash
# No console da aplicação
php artisan optimize:clear
php artisan cache:clear
php artisan config:cache
```

Certifique-se de:
- ✅ APP_DEBUG=false em produção
- ✅ SSL habilitado
- ✅ Senhas fortes no banco
- ✅ Firewall configurado na VPS
- ✅ Atualizações regulares

---

## 🔄 Atualização da Aplicação

### Opção 1: Via Easypanel (Recomendado)

1. Faça commit das alterações no Git
2. Push para o repositório
3. No Easypanel, vá em `ponto-app`
4. Clique em **"Deploy"**
5. Aguarde o novo build
6. Execute migrations se necessário

### Opção 2: Deploy Automático

Configure webhook no GitHub/GitLab:

1. No Easypanel, copie a URL do webhook
2. No GitHub/GitLab: Settings → Webhooks
3. Cole a URL e salve
4. Agora cada push fará deploy automático

---

## 🐛 Troubleshooting

### Problema: Build falha

**Solução:**
```bash
# Verifique os logs do build
# Certifique-se que o Dockerfile está correto
# Verifique dependências no composer.json
```

### Problema: Erro 500

**Solução:**
```bash
# Ver logs
php artisan log:tail

# Limpar cache
php artisan optimize:clear

# Verificar permissões
chmod -R 775 storage bootstrap/cache
```

### Problema: Banco de dados não conecta

**Solução:**
```bash
# Verificar se o serviço MySQL está rodando
# Verificar variáveis DB_HOST, DB_DATABASE, etc.
# Testar conexão
php artisan tinker
DB::connection()->getPdo();
```

### Problema: Queue não processa

**Solução:**
```bash
# Reiniciar queue worker
# No Easypanel: ponto-queue → Restart

# Verificar conexão Redis
php artisan queue:monitor
```

### Problema: Assets não carregam

**Solução:**
```bash
# Refazer build
npm run build

# Verificar link simbólico
php artisan storage:link

# Verificar permissões
chmod -R 755 public/build
```

---

## 📞 Suporte

### Documentação Útil

- **Laravel**: https://laravel.com/docs
- **Easypanel**: https://easypanel.io/docs
- **Docker**: https://docs.docker.com

### Comandos Úteis

```bash
# Entrar no console da aplicação
docker exec -it ponto-app bash

# Ver todos os containers
docker ps -a

# Ver logs em tempo real
docker logs -f ponto-app

# Reiniciar aplicação
docker restart ponto-app

# Verificar espaço em disco
df -h

# Verificar uso de memória
free -h
```

---

## ✅ Checklist Final

Antes de considerar o deploy completo, verifique:

- [ ] Aplicação acessível via HTTPS
- [ ] Login funcionando
- [ ] Cadastro de funcionários OK
- [ ] Registro de ponto funcionando
- [ ] Relatórios gerando corretamente
- [ ] Emails sendo enviados (se configurado)
- [ ] Queue processando jobs
- [ ] Scheduler executando tarefas
- [ ] Backup configurado
- [ ] Monitoramento ativo
- [ ] SSL válido
- [ ] Logs sem erros críticos

---

## 🎉 Deploy Concluído!

Parabéns! Seu Sistema de Ponto Eletrônico está no ar!

**Credenciais padrão (TROQUE IMEDIATAMENTE):**
```
Email: admin@exemplo.com
Senha: senha-segura
```

**URLs importantes:**
- Aplicação: https://seu-dominio.com
- Easypanel: https://seu-ip:3000
- PhpMyAdmin: https://seu-dominio.com:8080 (se habilitado)

---

**Desenvolvido por:** Godinho Sistemas Ltda.
**Suporte:** www.nextsystems.com.br
**Data:** 2025
