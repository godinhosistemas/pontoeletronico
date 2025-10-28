# Sistema de Billing Completo - Implementado

## Visão Geral

Sistema completo de cobrança e pagamentos para SaaS implementado com suporte a múltiplos gateways de pagamento (Asaas, Mercado Pago), métodos de pagamento (Boleto, PIX) e automação de processos.

## Estrutura Implementada

### 1. Database - Migrations

✅ **Migrations criadas e executadas:**

- `2025_10_23_100001_create_payment_gateways_table.php` - Cadastro de gateways
- `2025_10_23_100002_create_invoices_table.php` - Faturas
- `2025_10_23_100003_create_payments_table.php` - Pagamentos
- `2025_10_23_100004_create_payment_webhooks_table.php` - Webhooks

### 2. Models

✅ **Modelos criados:**

- `PaymentGateway` - app/Models/PaymentGateway.php
  - Criptografia automática de API keys
  - Métodos para cálculo de taxas
  - Suporte a múltiplos métodos de pagamento

- `Invoice` - app/Models/Invoice.php
  - Geração automática de números
  - Verificação de vencimento
  - Sistema de lembretes
  - Marcação automática de status

- `Payment` - app/Models/Payment.php
  - Suporte a Boleto e PIX
  - Rastreamento de transações
  - Event observers para atualizar faturas

- `PaymentWebhook` - app/Models/PaymentWebhook.php
  - Registro de webhooks recebidos
  - Controle de tentativas
  - Status de processamento

### 3. Services - Payment Gateway Integration

✅ **Serviços de pagamento criados:**

- `PaymentGatewayInterface` - app/Services/Payment/PaymentGatewayInterface.php
  - Interface padrão para todos os gateways

- `AsaasGateway` - app/Services/Payment/Gateways/AsaasGateway.php
  - Integração completa com Asaas
  - Suporte a Boleto e PIX
  - Criação automática de clientes
  - Processamento de webhooks

- `MercadoPagoGateway` - app/Services/Payment/Gateways/MercadoPagoGateway.php
  - Integração completa com Mercado Pago
  - Suporte a Boleto e PIX
  - Processamento de webhooks

- `PaymentService` - app/Services/Payment/PaymentService.php
  - Orquestrador de gateways
  - Seleção automática de gateway padrão
  - Verificação de suporte a métodos

### 4. Controllers

✅ **Controllers criados:**

- `PaymentGatewayController` - app/Http/Controllers/Admin/PaymentGatewayController.php
  - CRUD completo de gateways (Super Admin)
  - Ativar/desativar gateways
  - Definir gateway padrão

- `BillingController` - app/Http/Controllers/Tenant/BillingController.php
  - Listagem de faturas do tenant
  - Tela de pagamento
  - Processamento de pagamentos
  - Consulta de status
  - Download de boleto

- `WebhookController` - app/Http/Controllers/WebhookController.php
  - Recebimento de webhooks Asaas
  - Recebimento de webhooks Mercado Pago
  - Registro e processamento automático

### 5. Views - Livewire Components

✅ **Componentes Livewire criados:**

- `admin.payment-gateways.index` - resources/views/livewire/admin/payment-gateways/index.blade.php
  - Interface completa de gerenciamento de gateways
  - Modal para criar/editar gateways
  - Listagem com filtros e ações
  - Design seguindo padrão do projeto (Tailwind + gradientes)

- `tenant.billing-notification` - resources/views/livewire/tenant/billing-notification.blade.php
  - Notificações inteligentes de vencimento
  - Alertas com cores diferentes por urgência
  - Botões de ação para pagamento

- `tenant.billing.index` - resources/views/livewire/tenant/billing/index.blade.php
  - Listagem de faturas do tenant
  - Filtros por status
  - Ações de pagamento e detalhes

### 6. Jobs Automáticos

✅ **Jobs criados e agendados:**

- `GenerateMonthlyInvoices` - app/Jobs/GenerateMonthlyInvoices.php
  - Gera faturas automaticamente todo dia 1 do mês
  - Calcula valores baseado no plano
  - Suporta cobranças extras e descontos
  - Agendado: Todo dia 1 às 00:00

- `SendPaymentReminders` - app/Jobs/SendPaymentReminders.php
  - Envia lembretes de pagamento por email
  - Suporte para WhatsApp/SMS (preparado)
  - Mensagens diferentes por urgência
  - Agendado: Diariamente às 09:00

- `ProcessOverdueInvoices` - app/Jobs/ProcessOverdueInvoices.php
  - Marca faturas vencidas
  - Suspende assinaturas após período de carência
  - Desativa tenants inadimplentes
  - Envia notificações de suspensão
  - Agendado: Diariamente às 10:00

### 7. Routes

✅ **Rotas criadas em routes/web.php:**

**Super Admin - Gateways:**
- GET `/admin/payment-gateways` - Listagem de gateways
- POST `/admin/payment-gateways/{id}/toggle-active` - Ativar/desativar
- POST `/admin/payment-gateways/{id}/set-default` - Definir padrão

**Tenant - Billing:**
- GET `/tenant/billing` - Lista de faturas
- GET `/tenant/billing/invoices/{invoice}` - Detalhes da fatura
- GET `/tenant/billing/invoices/{invoice}/payment` - Tela de pagamento
- POST `/tenant/billing/invoices/{invoice}/process-payment` - Processar pagamento
- GET `/tenant/billing/payments/{payment}` - Detalhes do pagamento
- GET `/tenant/billing/payments/{payment}/check-status` - Verificar status
- GET `/tenant/billing/payments/{payment}/download-boleto` - Download boleto

**Webhooks (públicos):**
- POST `/webhooks/asaas` - Webhook Asaas
- POST `/webhooks/mercadopago` - Webhook Mercado Pago

### 8. Schedule - Agendamento

✅ **Configurado em routes/console.php:**

```php
// Gerar faturas mensais - Todo dia 1 às 00:00
Schedule::job(new \App\Jobs\GenerateMonthlyInvoices())
    ->monthlyOn(1, '00:00')
    ->name('billing:generate-invoices')
    ->withoutOverlapping()
    ->onOneServer();

// Enviar lembretes de pagamento - Todos os dias às 09:00
Schedule::job(new \App\Jobs\SendPaymentReminders())
    ->dailyAt('09:00')
    ->name('billing:send-reminders')
    ->withoutOverlapping()
    ->onOneServer();

// Processar faturas vencidas - Todos os dias às 10:00
Schedule::job(new \App\Jobs\ProcessOverdueInvoices())
    ->dailyAt('10:00')
    ->name('billing:process-overdue')
    ->withoutOverlapping()
    ->onOneServer();
```

## Fluxo de Funcionamento

### 1. Configuração Inicial (Super Admin)

1. Acessar `/admin/payment-gateways`
2. Cadastrar gateway de pagamento (Asaas ou Mercado Pago)
3. Inserir credenciais (API Key, API Secret)
4. Selecionar métodos de pagamento suportados
5. Definir taxas (opcional)
6. Marcar como ativo e/ou padrão

### 2. Geração de Faturas (Automático)

1. Todo dia 1 do mês às 00:00, o job `GenerateMonthlyInvoices` executa
2. Busca todas as assinaturas ativas
3. Calcula valores baseado no plano
4. Aplica descontos e taxas
5. Cria fatura com vencimento configurado (padrão: dia 10)

### 3. Notificações ao Cliente (Tenant)

1. Ao fazer login, vê notificações no dashboard
2. Alertas com cores diferentes:
   - Vermelho: Faturas vencidas
   - Laranja: Vence em até 3 dias
   - Amarelo: Vence em 4-7 dias
   - Azul: Mais de 7 dias
3. Pode acessar `/tenant/billing` para ver todas as faturas

### 4. Processo de Pagamento

1. Cliente clica em "Pagar" na fatura
2. Seleciona método (Boleto ou PIX)
3. Sistema cria pagamento no gateway selecionado
4. Gateway retorna:
   - **Boleto**: URL, código de barras, linha digitável
   - **PIX**: QR Code, chave PIX
5. Cliente visualiza dados do pagamento e efetua

### 5. Confirmação de Pagamento

1. Gateway processa pagamento
2. Envia webhook para `/webhooks/asaas` ou `/webhooks/mercadopago`
3. Sistema registra webhook em `payment_webhooks`
4. Atualiza status do pagamento
5. Marca fatura como paga
6. Atualiza data de último pagamento da assinatura

### 6. Lembretes Automáticos

1. Todo dia às 09:00, o job `SendPaymentReminders` executa
2. Busca faturas que precisam de lembrete (7, 3, 0, -1, -3, -7 dias)
3. Envia email com mensagem personalizada
4. Registra envio para não duplicar no mesmo dia

### 7. Suspensão por Inadimplência

1. Todo dia às 10:00, o job `ProcessOverdueInvoices` executa
2. Marca faturas vencidas como "overdue"
3. Se vencida há mais de 7 dias (configurável):
   - Suspende assinatura (status = 'suspended')
   - Desativa tenant (active = false)
   - Envia email de notificação de suspensão
4. Acesso ao sistema é bloqueado pelo middleware `tenant.active`

## Recursos Implementados

### ✅ Gestão de Gateways
- Cadastro de múltiplos gateways
- Suporte a Asaas e Mercado Pago
- Ambiente sandbox/produção
- Criptografia de credenciais
- Gateway padrão configurável

### ✅ Métodos de Pagamento
- Boleto bancário
- PIX
- Cartão de crédito (preparado, não implementado)

### ✅ Gestão de Faturas
- Geração automática mensal
- Números sequenciais por ano
- Cálculo de descontos e taxas
- Itens detalhados
- Rastreamento de período

### ✅ Sistema de Pagamentos
- Integração completa com gateways
- Rastreamento de transações
- Dados específicos por método (boleto/PIX)
- Cálculo de taxas
- Valor líquido

### ✅ Webhooks
- Recebimento automático
- Registro de tentativas
- Processamento assíncrono
- Tratamento de erros

### ✅ Notificações
- Dashboard com alertas visuais
- Cores por urgência
- Lembretes por email
- Preparado para WhatsApp/SMS

### ✅ Automação
- Geração automática de faturas
- Envio de lembretes
- Suspensão de inadimplentes
- Schedule configurado

### ✅ Interface do Usuário
- Design moderno com Tailwind CSS
- Gradientes consistentes com projeto
- Componentes Livewire reativos
- Modal para CRUD de gateways
- Filtros e paginação

## Configurações Necessárias

### 1. Credenciais dos Gateways

Acessar `/admin/payment-gateways` e cadastrar:

**Asaas:**
- API Key (obtida em https://asaas.com/api)
- Ambiente: Sandbox ou Produção

**Mercado Pago:**
- Access Token (obtido em https://mercadopago.com.br/developers)
- Ambiente: Produção (Mercado Pago não tem sandbox separado)

### 2. Configurar Cron

Adicionar no crontab do servidor:

```bash
* * * * * cd /caminho/para/projeto && php artisan schedule:run >> /dev/null 2>&1
```

### 3. Configurar Queue (Opcional)

Para processar jobs em background:

```bash
php artisan queue:work --tries=3
```

### 4. Configurar Email

Ajustar `.env` com credenciais SMTP:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=seu_usuario
MAIL_PASSWORD=sua_senha
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@seuapp.com
MAIL_FROM_NAME="${APP_NAME}"
```

## URLs dos Webhooks

Configurar nos painéis dos gateways:

**Asaas:**
- URL: `https://seudominio.com/webhooks/asaas`
- Eventos: PAYMENT_RECEIVED, PAYMENT_CONFIRMED, PAYMENT_OVERDUE, PAYMENT_REFUNDED

**Mercado Pago:**
- URL: `https://seudominio.com/webhooks/mercadopago`
- Eventos: payment

## Próximos Passos (Opcional)

### Melhorias Futuras:

1. **Integração com mais gateways:**
   - Pagar.me
   - Stripe
   - PagSeguro

2. **Métodos de pagamento adicionais:**
   - Cartão de crédito com parcelamento
   - Débito automático
   - Transferência bancária

3. **Relatórios financeiros:**
   - Dashboard de receitas
   - Gráficos de inadimplência
   - Previsão de faturamento

4. **Funcionalidades extras:**
   - Reembolsos
   - Estornos
   - Cupons de desconto
   - Planos de trial

5. **Notificações:**
   - WhatsApp via Evolution API
   - SMS via Twilio
   - Notificações push

## Testes

### Testar Geração de Faturas:

```bash
php artisan tinker
>>> \App\Jobs\GenerateMonthlyInvoices::dispatch()
```

### Testar Lembretes:

```bash
php artisan tinker
>>> \App\Jobs\SendPaymentReminders::dispatch()
```

### Testar Suspensão:

```bash
php artisan tinker
>>> \App\Jobs\ProcessOverdueInvoices::dispatch()
```

### Testar Webhook Asaas:

```bash
curl -X POST https://seudominio.com/webhooks/asaas \
  -H "Content-Type: application/json" \
  -d '{
    "event": "PAYMENT_RECEIVED",
    "payment": {
      "id": "pay_123",
      "status": "RECEIVED"
    }
  }'
```

## Conclusão

Sistema completo de billing implementado com:

- ✅ 4 migrations executadas
- ✅ 4 models criados
- ✅ 4 services de pagamento
- ✅ 3 controllers
- ✅ 3 componentes Livewire
- ✅ 3 jobs automáticos agendados
- ✅ 12 rotas configuradas
- ✅ 2 gateways integrados (Asaas e Mercado Pago)
- ✅ 2 métodos de pagamento (Boleto e PIX)
- ✅ Sistema de webhooks
- ✅ Notificações automáticas
- ✅ Suspensão de inadimplentes

O sistema está pronto para uso em produção após configurar as credenciais dos gateways e o cron do servidor.
