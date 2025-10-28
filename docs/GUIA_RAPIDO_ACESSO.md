# 🚀 Guia Rápido de Acesso - Sistema de Ponto Eletrônico

## 📍 Como Acessar as Novas Funcionalidades

### 🏢 Menu Lateral do Sistema

Após fazer login como administrador, você encontrará no **menu lateral esquerdo** as seguintes seções:

---

## 📋 Estrutura do Menu

### 1️⃣ **Dashboard**
- 🏠 Visão geral do sistema
- Estatísticas e resumos

### 2️⃣ **Administração** (Apenas Super Admin)
- 🏢 Empresas
- 📦 Planos
- 📄 Assinaturas

### 3️⃣ **Financeiro** (Apenas Super Admin)
- 💰 Faturas
- 💳 Gateways de Pagamento

### 4️⃣ **Gestão** ⭐
- 👥 **Funcionários**
- ⏰ **Jornadas de Trabalho**

### 5️⃣ **Horas Extras** ⭐ NOVO!
- 📅 **Feriados** ← Clique aqui!

### 6️⃣ **Ponto Eletrônico**
- ⏱️ Registrar Ponto
- ✅ Aprovar Pontos
- 📊 Relatórios
- 📁 AFD / AEJ (Arquivos Legais)

---

## 🎯 Acessando o Gerenciamento de Feriados

### Via Menu:
1. Faça login no sistema
2. No menu lateral, procure por **"Horas Extras"** (ícone de calendário)
3. Clique para expandir o menu
4. Clique em **"Feriados"**

### Via URL Direta:
```
http://seu-dominio.com/admin/holidays
```

---

## 📅 Sistema de Feriados - Início Rápido

### 🚀 Primeiros Passos

#### 1. Importar Feriados Nacionais (Recomendado!)
1. Acesse: **Menu → Horas Extras → Feriados**
2. Clique no botão azul: **"Importar Feriados Nacionais"**
3. ✅ Pronto! 9 feriados nacionais foram importados automaticamente

**Feriados importados:**
- ✅ Ano Novo (01/01)
- ✅ Tiradentes (21/04)
- ✅ Dia do Trabalho (01/05)
- ✅ Independência (07/09)
- ✅ Nossa Senhora Aparecida (12/10)
- ✅ Finados (02/11)
- ✅ Proclamação da República (15/11)
- ✅ Consciência Negra (20/11)
- ✅ Natal (25/12)

#### 2. Cadastrar Feriado Municipal
1. Clique no botão verde: **"Novo Feriado"**
2. Preencha os dados:
   ```
   Nome: Aniversário da Cidade
   Data: 15/06/2025
   Tipo: Municipal
   Cidade: Sua Cidade
   UF: SP
   ☑️ Recorrente (para repetir todo ano)
   ☑️ Ativo
   ```
3. Clique em **"Criar"**

#### 3. Filtrar Feriados
Use os filtros no topo da tela:
- 🔍 **Pesquisar:** Nome ou cidade
- 📑 **Tipo:** Nacional/Estadual/Municipal/Personalizado
- 📆 **Ano:** Selecione o ano desejado

---

## 📊 Sistema de Horas Extras - Como Funciona

### ⚙️ Processamento Automático

O sistema **detecta automaticamente** quando um funcionário trabalha além da jornada:

#### Exemplo Prático:
```
Jornada Normal: 8 horas/dia
Trabalhado: 10 horas

✅ Sistema detecta: 2 horas extras
✅ Tipo: Hora Extra Normal (50%)
✅ Status: Pendente aprovação
✅ Quando aprovado → Vai para o Banco de Horas
```

### 🎯 Tipos de Hora Extra

| Tipo | Adicional | Quando Ocorre |
|------|-----------|---------------|
| **Normal** | 50% | Dias úteis acima da jornada |
| **Noturna** | 20% | Entre 22h e 5h |
| **Feriado/Domingo** | 100% | Trabalho em feriados ou domingos |

### 📋 Validações da CLT

✅ **Limite diário:** Máximo 2 horas extras/dia
- Sistema alerta quando exceder
- Exibe aviso na Folha Espelho

✅ **Banco de Horas:** Expira em 1 ano
- Acumula automaticamente ao aprovar
- Comando: `php artisan overtime:expire`

---

## 📊 Relatórios com Horas Extras

### Folha Espelho Atualizada

Acesse: **Menu → Ponto Eletrônico → Relatórios**

Novos totalizadores exibidos:

📦 **Totalizadores Adicionais:**
- 🟣 **HE Normal (50%):** Total de horas extras normais
- 🔵 **HE Noturna (20%):** Total de horas extras noturnas
- 🔴 **HE Feriado/Domingo (100%):** Total de horas em feriados
- 🟢 **Banco de Horas:** Saldo atual acumulado
- ⚠️ **Violações CLT:** Dias que excederam 2h/dia

---

## 🔑 Permissões

### Quem Pode Acessar:

#### Feriados:
- ✅ Administradores (Super Admin)
- ✅ Gestores com permissão `employees.view`

#### Horas Extras:
- ✅ Processamento automático para todos
- ✅ Relatórios: Gestores com permissão `reports.view`
- ✅ Aprovação: Gestores com permissão `timesheet.approve`

---

## 🎨 Interface Visual

### Menu Lateral - Seção "Horas Extras"

```
╔════════════════════════════════╗
║  📋 Horas Extras              ║
║    ↓ (clique para expandir)   ║
║    📅 Feriados                ║
╚════════════════════════════════╝
```

**Ícone:** Calendário com marcações
**Cor:** Cinza claro (menu fechado) / Branco (menu aberto)
**Hover:** Fundo cinza escuro

### Tela de Feriados

```
+----------------------------------------------------------+
|  📅 Gerenciar Feriados                                   |
|  Configure os feriados municipais da sua empresa         |
|                                                           |
|  [📥 Importar Feriados]  [➕ Novo Feriado]              |
+----------------------------------------------------------+
|  Pesquisar: [____]  Tipo: [____]  Ano: [2025]          |
+----------------------------------------------------------+
|  Tabela com feriados cadastrados...                     |
+----------------------------------------------------------+
```

---

## 🆘 Resolução de Problemas

### ❓ Não vejo a opção "Horas Extras" no menu

**Possíveis causas:**
1. Usuário sem permissão `employees.view`
2. Menu está minimizado (clique para expandir)

**Solução:**
- Verifique suas permissões com o administrador
- Faça logout e login novamente

### ❓ Feriados não estão sendo considerados

**Verificar:**
1. Feriado está **ativo** (status verde)
2. Data está correta
3. Empresa (tenant) está correto

**Como testar:**
```php
// Via Tinker
$date = Carbon::parse('2025-12-25');
$tenantId = auth()->user()->tenant_id;
Holiday::isHoliday($date, $tenantId); // Deve retornar true
```

### ❓ Horas extras não calculadas

**Verificar:**
1. Funcionário tem jornada configurada
2. Registro de ponto tem horário de entrada E saída
3. Total de horas > jornada esperada

**Como reprocessar:**
```bash
php artisan tinker
$service = app(App\Services\OvertimeService::class);
$service->reprocessPeriod(1, '2025-10-01', '2025-10-31');
```

---

## 📱 Acesso Mobile (PWA)

O sistema é **Progressive Web App (PWA)**, funciona perfeitamente em dispositivos móveis!

### Como Instalar no Celular:

**Android (Chrome):**
1. Acesse o sistema pelo navegador
2. Menu (⋮) → "Instalar aplicativo"
3. Confirme

**iOS (Safari):**
1. Acesse o sistema pelo navegador
2. Botão compartilhar (⬆️)
3. "Adicionar à Tela de Início"

---

## 🎯 Fluxo Completo de Uso

### Para Administradores:

1. **Configurar Sistema:**
   - ✅ Importar feriados nacionais
   - ✅ Cadastrar feriados municipais
   - ✅ Configurar jornadas de trabalho

2. **Gerenciar Funcionários:**
   - ✅ Cadastrar funcionários
   - ✅ Vincular jornada de trabalho
   - ✅ Habilitar horas extras na jornada

3. **Aprovar Pontos:**
   - ✅ Acessar "Ponto Eletrônico → Aprovar"
   - ✅ Revisar horas extras calculadas
   - ✅ Aprovar (vai para banco de horas automaticamente)

4. **Gerar Relatórios:**
   - ✅ Folha Espelho com detalhamento
   - ✅ Exportar AFD/AEJ com horas extras
   - ✅ Verificar violações CLT

### Para Funcionários:

1. **Registrar Ponto:**
   - ✅ Acessar "Ponto Eletrônico → Registrar"
   - ✅ Bater entrada, almoço e saída
   - ✅ Sistema calcula automaticamente

2. **Acompanhar:**
   - ✅ Ver histórico de pontos
   - ✅ Verificar horas extras (após aprovação)

---

## 📚 Documentação Completa

Para informações técnicas detalhadas, consulte:

- 📄 **GESTAO_HORAS_EXTRAS_IMPLEMENTADO.md** - Sistema de horas extras
- 📄 **FERIADOS_IMPLEMENTADO.md** - Sistema de feriados
- 📄 **JORNADAS_TRABALHO_IMPLEMENTADO.md** - Jornadas de trabalho
- 📄 **FOLHA_ESPELHO_ATUALIZADA.md** - Relatórios

---

## 🔗 Links Rápidos

| Funcionalidade | URL | Permissão Necessária |
|----------------|-----|----------------------|
| Feriados | `/admin/holidays` | `employees.view` |
| Funcionários | `/admin/employees` | `employees.view` |
| Jornadas | `/admin/work-schedules` | `employees.view` |
| Aprovar Pontos | `/admin/timesheet/approvals` | `timesheet.approve` |
| Relatórios | `/admin/timesheet/reports` | `reports.view` |
| AFD/AEJ | `/admin/legal-files` | Todos |

---

## ⚡ Dicas e Atalhos

### Teclado:
- **Ctrl + K:** Busca rápida (se implementado)
- **Esc:** Fechar modais
- **Tab:** Navegar entre campos

### Performance:
- ✅ Sistema usa cache para consultas frequentes
- ✅ Feriados recorrentes são otimizados
- ✅ Relatórios são gerados sob demanda

### Manutenção:
```bash
# Expirar bancos de horas vencidos
php artisan overtime:expire

# Limpar cache
php artisan cache:clear

# Reprocessar horas extras de um período
# (via código ou console)
```

---

## 🎉 Novidades Implementadas

✅ **Outubro 2025:**
- Sistema completo de horas extras
- Marcação automática de tipo overtime
- Validação de limites CLT (2h/dia)
- Tipos diferenciados (normal, noturna, feriado)
- Sistema de banco de horas
- Cadastro de feriados municipais
- Relatórios aprimorados

---

## 📞 Suporte

**Dúvidas ou problemas?**

1. Consulte a documentação técnica (arquivos `.md`)
2. Verifique os logs: `storage/logs/laravel.log`
3. Entre em contato com o suporte técnico

---

**Sistema desenvolvido por:** Godinho Sistemas Ltda.
**Website:** www.nextsystems.com.br
**Última atualização:** 27/10/2025

---

## ✨ Resumo Visual

```
┌─────────────────────────────────────────────────────┐
│                  MENU LATERAL                       │
├─────────────────────────────────────────────────────┤
│  🏠 Dashboard                                       │
│  🏢 Administração (Super Admin)                    │
│  💰 Financeiro (Super Admin)                       │
│  👥 Gestão                                          │
│     ├─ Funcionários                                │
│     └─ Jornadas                                    │
│  📋 Horas Extras ⭐ NOVO                            │
│     └─ 📅 Feriados ← CLIQUE AQUI!                  │
│  ⏱️ Ponto Eletrônico                               │
│     ├─ Registrar                                   │
│     ├─ Aprovar                                     │
│     ├─ Relatórios                                  │
│     └─ AFD / AEJ                                   │
└─────────────────────────────────────────────────────┘
```

---

**Pronto para usar! 🚀**
