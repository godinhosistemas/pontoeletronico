# Gestão Avançada de Horas Extras - Sistema de Ponto Eletrônico

## Visão Geral

Este documento descreve as melhorias implementadas no sistema de gestão de horas extras, incluindo marcação automática, validação de limites da CLT, tipos diferenciados de hora extra e sistema de banco de horas.

**Data de Implementação:** 27/10/2025
**Versão:** 2.0

---

## 📋 Índice

1. [Funcionalidades Implementadas](#funcionalidades-implementadas)
2. [Estrutura do Banco de Dados](#estrutura-do-banco-de-dados)
3. [Models e Relacionamentos](#models-e-relacionamentos)
4. [Serviços e Lógica de Negócio](#serviços-e-lógica-de-negócio)
5. [Processamento Automático](#processamento-automático)
6. [Interface e Visualização](#interface-e-visualização)
7. [Comandos Artisan](#comandos-artisan)
8. [Regras da CLT Implementadas](#regras-da-clt-implementadas)
9. [Como Usar](#como-usar)
10. [Exemplos de Código](#exemplos-de-código)

---

## 🚀 Funcionalidades Implementadas

### ✅ 1. Marcação Automática de Tipo Overtime
- **Status:** ✅ Implementado
- **Descrição:** O sistema detecta automaticamente quando um registro de ponto excede a jornada esperada e marca como 'overtime'
- **Arquivo:** `app/Observers/TimeEntryObserver.php`

### ✅ 2. Tipos Diferenciados de Hora Extra
- **Status:** ✅ Implementado
- **Tipos disponíveis:**
  - **Normal (50%):** Hora extra comum, durante dias úteis
  - **Noturna (20%):** Hora extra realizada entre 22h e 5h
  - **Feriado/Domingo (100%):** Hora extra em domingos e feriados
- **Arquivo:** `app/Services/OvertimeService.php`

### ✅ 3. Validação de Limites da CLT
- **Status:** ✅ Implementado
- **Regra:** Máximo de 2 horas extras por dia
- **Comportamento:**
  - Sistema valida automaticamente
  - Marca registros que excedem o limite
  - Adiciona notas sobre a violação
  - Exibe alerta visual nos relatórios
- **Arquivo:** `app/Services/OvertimeService.php` (método `validateCltLimit`)

### ✅ 4. Sistema de Banco de Horas
- **Status:** ✅ Implementado
- **Funcionalidades:**
  - Acumula horas extras aprovadas automaticamente
  - Controla saldo por período (mês/ano)
  - Permite compensação de horas
  - Expiração automática após 1 ano (conforme CLT)
  - Status: ativo, compensado, expirado
- **Arquivos:**
  - Model: `app/Models/OvertimeBalance.php`
  - Serviço: `app/Services/OvertimeService.php`
  - Comando: `app/Console/Commands/ExpireOvertimeBalance.php`

### ✅ 5. Relatórios Aprimorados
- **Status:** ✅ Implementado
- **Exibição na Folha Espelho:**
  - Total de horas extras por tipo
  - Saldo do banco de horas
  - Indicador de violações CLT
  - Cores diferenciadas por tipo
- **Arquivo:** `resources/views/reports/timesheet-mirror.blade.php`

---

## 🗄️ Estrutura do Banco de Dados

### Novos Campos em `time_entries`

```sql
-- Campos de horas extras
overtime_type ENUM('none', 'normal', 'night', 'holiday') DEFAULT 'none'
overtime_hours DECIMAL(5,2) DEFAULT 0
overtime_percentage DECIMAL(5,2) NULL
is_night_shift BOOLEAN DEFAULT false

-- Campos de validação CLT
clt_limit_validated BOOLEAN DEFAULT false
clt_limit_exceeded BOOLEAN DEFAULT false
clt_violation_notes TEXT NULL
```

**Migration:** `database/migrations/2025_10_27_195557_add_overtime_enhancements_to_time_entries_table.php`

### Nova Tabela `overtime_balances`

```sql
CREATE TABLE overtime_balances (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    employee_id BIGINT NOT NULL,
    tenant_id BIGINT NOT NULL,
    period VARCHAR(7) NOT NULL,              -- Formato: YYYY-MM
    earned_hours DECIMAL(8,2) DEFAULT 0,     -- Horas acumuladas
    compensated_hours DECIMAL(8,2) DEFAULT 0, -- Horas compensadas
    balance_hours DECIMAL(8,2) DEFAULT 0,    -- Saldo atual
    status ENUM('active', 'expired', 'compensated') DEFAULT 'active',
    expiration_date DATE NULL,               -- Data de expiração (1 ano)
    notes TEXT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    UNIQUE(employee_id, period)
);
```

**Migration:** `database/migrations/2025_10_27_195615_create_overtime_balances_table.php`

---

## 📦 Models e Relacionamentos

### TimeEntry (Atualizado)

**Arquivo:** `app/Models/TimeEntry.php`

**Novos atributos:**
```php
$fillable = [
    // ... campos anteriores
    'overtime_type',
    'overtime_hours',
    'overtime_percentage',
    'is_night_shift',
    'clt_limit_validated',
    'clt_limit_exceeded',
    'clt_violation_notes',
];

$casts = [
    // ... casts anteriores
    'overtime_hours' => 'decimal:2',
    'overtime_percentage' => 'decimal:2',
    'is_night_shift' => 'boolean',
    'clt_limit_validated' => 'boolean',
    'clt_limit_exceeded' => 'boolean',
];
```

**Novos métodos:**
- `getOvertimeTypeTextAttribute()` - Retorna texto do tipo de hora extra
- `getOvertimeTypeColorAttribute()` - Retorna cor para UI
- `getFormattedOvertimeHoursAttribute()` - Formata horas extras em HH:MM
- `hasOvertime()` - Verifica se tem horas extras
- `scopeWithOvertime($query)` - Filtra registros com horas extras
- `scopeExceededCltLimit($query)` - Filtra violações CLT

### OvertimeBalance (Novo)

**Arquivo:** `app/Models/OvertimeBalance.php`

**Relacionamentos:**
```php
belongsTo(Employee::class)
belongsTo(Tenant::class)
```

**Métodos principais:**
- `addEarnedHours(float $hours)` - Adiciona horas ao banco
- `compensateHours(float $hours)` - Compensa horas do banco
- `isExpired()` - Verifica se está expirado
- `markAsExpired()` - Marca como expirado
- `scopeForEmployee($query, $employeeId)` - Filtra por funcionário
- `scopeForPeriod($query, $period)` - Filtra por período
- `scopeActive($query)` - Filtra apenas ativos

---

## ⚙️ Serviços e Lógica de Negócio

### OvertimeService

**Arquivo:** `app/Services/OvertimeService.php`

#### Constantes da CLT
```php
const CLT_MAX_DAILY_OVERTIME = 2;           // 2 horas/dia
const NIGHT_SHIFT_START = '22:00:00';       // 22h
const NIGHT_SHIFT_END = '05:00:00';         // 5h
const OVERTIME_PERCENTAGE_NORMAL = 50;      // 50%
const OVERTIME_PERCENTAGE_NIGHT = 20;       // 20%
const OVERTIME_PERCENTAGE_HOLIDAY = 100;    // 100%
const BANK_HOURS_EXPIRATION_MONTHS = 12;    // 1 ano
```

#### Métodos Principais

##### `processTimeEntry(TimeEntry $timeEntry): void`
Processa um registro de ponto e calcula horas extras automaticamente.

**Fluxo:**
1. Obtém jornada esperada do funcionário
2. Calcula se há horas extras
3. Determina o tipo de hora extra
4. Valida limite CLT
5. Atualiza o registro
6. Adiciona ao banco de horas se aprovado

##### `determineOvertimeType(TimeEntry $timeEntry): string`
Determina o tipo de hora extra baseado no horário e dia.

**Lógica:**
- Se domingo ou feriado → 'holiday'
- Se período noturno (22h-5h) → 'night'
- Caso contrário → 'normal'

##### `validateCltLimit(TimeEntry $timeEntry, float $overtimeHours): void`
Valida se as horas extras estão dentro do limite da CLT.

**Ação se exceder:**
- Marca `clt_limit_exceeded = true`
- Adiciona nota explicativa em `clt_violation_notes`

##### `addToBankHours(TimeEntry $timeEntry): void`
Adiciona horas extras ao banco de horas do funcionário.

**Comportamento:**
- Cria ou atualiza registro de `OvertimeBalance` para o período
- Define data de expiração (1 ano)
- Incrementa saldo

##### `compensateHours(Employee $employee, float $hours, string $period = null): bool`
Compensa horas do banco de horas.

##### `getTotalBankHours(Employee $employee): float`
Obtém o saldo total de banco de horas do funcionário.

##### `expireBankHours(): int`
Expira bancos de horas vencidos (executado via comando Artisan).

##### `getOvertimeReport(int $employeeId, string $startDate, string $endDate): array`
Gera relatório completo de horas extras por funcionário.

**Retorna:**
```php
[
    'total_overtime_hours' => float,
    'normal_overtime' => float,
    'night_overtime' => float,
    'holiday_overtime' => float,
    'clt_violations' => int,
    'entries' => Collection,
]
```

---

## 🔄 Processamento Automático

### TimeEntryObserver

**Arquivo:** `app/Observers/TimeEntryObserver.php`

**Eventos observados:**

#### `saving(TimeEntry $timeEntry)`
- Recalcula `total_hours` se horários mudaram
- Executado **antes** de salvar

#### `saved(TimeEntry $timeEntry)`
- Processa horas extras automaticamente
- Chama `OvertimeService::processTimeEntry()`
- Executado **após** salvar

#### `updated(TimeEntry $timeEntry)`
- Se status mudou para 'approved' e tem horas extras
- Adiciona ao banco de horas automaticamente

**Registrado em:** `app/Providers/AppServiceProvider.php`

```php
public function boot(): void
{
    TimeEntry::observe(TimeEntryObserver::class);
}
```

---

## 🎨 Interface e Visualização

### Folha Espelho (Timesheet Mirror)

**Arquivo:** `resources/views/reports/timesheet-mirror.blade.php`

#### Novos Totalizadores

**HE Normal (50%):**
- Cor: Roxo claro (`#f3e5f5`)
- Exibe: Total de horas extras normais

**HE Noturna (20%):**
- Cor: Índigo claro (`#e8eaf6`)
- Exibe: Total de horas extras noturnas

**HE Feriado/Domingo (100%):**
- Cor: Vermelho claro (`#ffebee`)
- Exibe: Total de horas extras em feriados/domingos

**Banco de Horas:**
- Cor: Verde claro (`#e8f5e9`)
- Exibe: Saldo atual do banco de horas

**Violações CLT:**
- Cor: Laranja (`#fff3e0`)
- Borda: Laranja escuro (`#ff9800`)
- Ícone: ⚠️
- Exibe: Quantidade de dias que excederam 2h/dia

#### Exemplo de Código da View

```php
@php
    $normalOvertimeHours = $allEntries->where('overtime_type', 'normal')->sum('overtime_hours') ?? 0;
    $nightOvertimeHours = $allEntries->where('overtime_type', 'night')->sum('overtime_hours') ?? 0;
    $holidayOvertimeHours = $allEntries->where('overtime_type', 'holiday')->sum('overtime_hours') ?? 0;
    $cltViolations = $allEntries->where('clt_limit_exceeded', true)->count();
@endphp
```

---

## 🖥️ Comandos Artisan

### Expirar Banco de Horas

```bash
php artisan overtime:expire
```

**Descrição:** Expira bancos de horas vencidos (CLT: 1 ano)

**Quando executar:**
- Via cron job diário
- Manualmente quando necessário

**Exemplo de agendamento no `app/Console/Kernel.php`:**

```php
protected function schedule(Schedule $schedule)
{
    // Expira bancos de horas todo dia às 2h da manhã
    $schedule->command('overtime:expire')->dailyAt('02:00');
}
```

---

## ⚖️ Regras da CLT Implementadas

### 1. Limite de Horas Extras Diárias
**Artigo 59 da CLT:** Máximo de 2 horas extras por dia

**Implementação:**
- Validação automática em `OvertimeService::validateCltLimit()`
- Marca registros que excedem
- Exibe alerta visual nos relatórios

### 2. Adicional de Hora Extra
**Artigo 59 da CLT:** Mínimo de 50% sobre a hora normal

**Implementação:**
- Hora Extra Normal: 50%
- Hora Extra Noturna: 20% adicional
- Hora Extra Feriado/Domingo: 100%

### 3. Adicional Noturno
**Artigo 73 da CLT:** Trabalho entre 22h e 5h tem adicional de 20%

**Implementação:**
- Detecção automática de turno noturno
- Campo `is_night_shift` marcado
- Tipo 'night' com 20% adicional

### 4. Banco de Horas
**Artigo 59-A da CLT:** Compensação em até 12 meses (1 ano)

**Implementação:**
- Sistema de banco de horas com expiração automática
- Data de expiração: 1 ano após acúmulo
- Comando para expirar automaticamente
- Status: ativo, compensado, expirado

### 5. Trabalho em Domingo e Feriado
**Artigo 70 da CLT:** Remuneração em dobro (100%)

**Implementação:**
- Detecção automática de domingos
- Tipo 'holiday' com 100% adicional
- TODO: Integração com calendário de feriados

---

## 📖 Como Usar

### 1. Registro de Ponto Automático

Quando um funcionário bate ponto, o sistema automaticamente:

```php
// Funcionário faz registro de ponto
$timeEntry = TimeEntry::create([
    'employee_id' => $employee->id,
    'date' => '2025-10-27',
    'clock_in' => '08:00',
    'clock_out' => '19:00',  // 11 horas (3h extras)
    'lunch_start' => '12:00',
    'lunch_end' => '13:00',
]);

// Observer detecta automaticamente e processa:
// - Calcula total_hours = 10h
// - Jornada esperada = 8h
// - overtime_hours = 2h
// - overtime_type = 'normal'
// - overtime_percentage = 50
// - clt_limit_validated = true
// - clt_limit_exceeded = false (2h está dentro do limite)
```

### 2. Aprovação de Horas Extras

```php
$timeEntry->approve($userId);

// Observer detecta mudança de status e:
// - Adiciona 2h ao banco de horas do funcionário
// - Cria/atualiza OvertimeBalance para o período 2025-10
// - Define expiração para 2026-10
```

### 3. Compensação de Horas

```php
use App\Services\OvertimeService;

$overtimeService = app(OvertimeService::class);

// Compensa 4 horas do banco
$success = $overtimeService->compensateHours($employee, 4.0, '2025-10');

if ($success) {
    echo "Horas compensadas com sucesso!";
}
```

### 4. Consultar Saldo do Banco de Horas

```php
$overtimeService = app(OvertimeService::class);

// Saldo total
$totalHours = $overtimeService->getTotalBankHours($employee);

// Saldo de um período específico
$balance = $overtimeService->getBankHoursByPeriod($employee, '2025-10');

if ($balance) {
    echo "Saldo: {$balance->balance_hours}h";
    echo "Status: {$balance->status_text}";
    echo "Expira em: {$balance->expiration_date->format('d/m/Y')}";
}
```

### 5. Relatório de Horas Extras

```php
$overtimeService = app(OvertimeService::class);

$report = $overtimeService->getOvertimeReport(
    employeeId: $employee->id,
    startDate: '2025-10-01',
    endDate: '2025-10-31'
);

echo "Total de horas extras: {$report['total_overtime_hours']}h";
echo "HE Normal (50%): {$report['normal_overtime']}h";
echo "HE Noturna (20%): {$report['night_overtime']}h";
echo "HE Feriado (100%): {$report['holiday_overtime']}h";
echo "Violações CLT: {$report['clt_violations']} dias";
```

### 6. Reprocessar Período

```php
// Útil quando há mudança na jornada de trabalho
$overtimeService = app(OvertimeService::class);

$count = $overtimeService->reprocessPeriod(
    employeeId: $employee->id,
    startDate: '2025-10-01',
    endDate: '2025-10-31'
);

echo "Reprocessados {$count} registros";
```

---

## 💻 Exemplos de Código

### Exemplo 1: Criar Registro com Hora Extra Noturna

```php
$timeEntry = TimeEntry::create([
    'employee_id' => 1,
    'tenant_id' => 1,
    'date' => Carbon::today(),
    'clock_in' => '22:00',  // Início do turno noturno
    'clock_out' => '06:00',  // 8 horas (turno noturno)
    'total_hours' => 8,
]);

// Após processamento automático:
// - overtime_type = 'night' (detectado turno 22h-5h)
// - is_night_shift = true
// - overtime_percentage = 20
```

### Exemplo 2: Verificar Violações CLT

```php
// Buscar todos os registros que excedem 2h/dia
$violations = TimeEntry::exceededCltLimit()
    ->forEmployee($employee->id)
    ->forPeriod($startDate, $endDate)
    ->get();

foreach ($violations as $entry) {
    echo "Data: {$entry->date->format('d/m/Y')}";
    echo "Horas extras: {$entry->overtime_hours}h";
    echo "Violação: {$entry->clt_violation_notes}";
}
```

### Exemplo 3: Expirar Manualmente

```php
$overtimeService = app(OvertimeService::class);
$expired = $overtimeService->expireBankHours();
echo "Expirados: {$expired} bancos de horas";
```

### Exemplo 4: Calcular Valor Monetário

```php
$overtimeService = app(OvertimeService::class);

$baseHourlyRate = 50.00; // R$ 50/hora
$overtimeHours = 10;
$overtimeType = 'normal'; // 50%

$value = $overtimeService->calculateOvertimeValue(
    $baseHourlyRate,
    $overtimeHours,
    $overtimeType
);

// Resultado: R$ 750 (50 * 10 * 1.5)
echo "Valor a pagar: R$ " . number_format($value, 2, ',', '.');
```

---

## 🔧 Manutenção e Troubleshooting

### Problema: Observer não está sendo chamado

**Solução:** Verificar se o Observer está registrado no `AppServiceProvider`

```php
// app/Providers/AppServiceProvider.php
public function boot(): void
{
    TimeEntry::observe(TimeEntryObserver::class);
}
```

### Problema: Horas extras não calculadas automaticamente

**Verificar:**
1. Se o funcionário tem `work_schedule_id` configurado
2. Se `allow_overtime` está habilitado na jornada
3. Se `total_hours` foi calculado corretamente
4. Logs de erro no Laravel

**Debug:**
```php
$timeEntry = TimeEntry::find($id);
dd([
    'total_hours' => $timeEntry->total_hours,
    'employee' => $timeEntry->employee,
    'workSchedule' => $timeEntry->employee->workSchedule,
    'overtime_hours' => $timeEntry->overtime_hours,
    'overtime_type' => $timeEntry->overtime_type,
]);
```

### Problema: Banco de horas não acumulando

**Verificar:**
1. Se o status do registro é 'approved'
2. Se `overtime_hours > 0`
3. Se o método `addToBankHours()` foi chamado

**Forçar reprocessamento:**
```php
$overtimeService = app(OvertimeService::class);
$overtimeService->addToBankHours($timeEntry);
```

---

## 📊 Estatísticas e Métricas

### Campos Monitorados

- **Total de horas extras por período**
- **Distribuição por tipo (normal, noturna, feriado)**
- **Taxa de violações CLT**
- **Saldo médio de banco de horas**
- **Taxa de expiração de banco de horas**

### Queries Úteis

```sql
-- Top funcionários com mais horas extras
SELECT e.name, SUM(te.overtime_hours) as total
FROM time_entries te
JOIN employees e ON e.id = te.employee_id
WHERE te.overtime_hours > 0
GROUP BY e.id
ORDER BY total DESC;

-- Violações CLT por período
SELECT DATE_FORMAT(date, '%Y-%m') as period, COUNT(*) as violations
FROM time_entries
WHERE clt_limit_exceeded = true
GROUP BY period;

-- Bancos de horas expirados
SELECT COUNT(*) as expired_count, SUM(balance_hours) as lost_hours
FROM overtime_balances
WHERE status = 'expired';
```

---

## 🚀 Melhorias Futuras

### Sugestões de Implementação

1. **Integração com Calendário de Feriados**
   - API de feriados nacionais e municipais
   - Configuração personalizada por tenant

2. **Notificações Automáticas**
   - Alerta quando banco de horas está próximo de expirar
   - Notificação de violações CLT para gestores

3. **Dashboard de Horas Extras**
   - Gráficos de tendências
   - Comparativo entre funcionários/departamentos

4. **Aprovação em Lote**
   - Interface para aprovar múltiplas horas extras
   - Filtros avançados

5. **Exportação para Folha de Pagamento**
   - Integração com sistemas de RH
   - Formato CSV/XML para importação

6. **Regras Customizáveis**
   - Permitir configuração de porcentagens por tenant
   - Limites personalizados de horas extras

---

## 📞 Suporte

Para dúvidas ou problemas com o sistema de gestão de horas extras:

- **Documentação Técnica:** Este arquivo
- **Código-fonte:** Veja os arquivos mencionados neste documento
- **Testes:** Execute `php artisan test` para validar funcionalidades

---

## 📝 Changelog

### v2.0 - 27/10/2025
- ✅ Implementação inicial do sistema de horas extras
- ✅ Marcação automática de tipo overtime
- ✅ Validação de limites CLT
- ✅ Tipos diferenciados (normal, noturna, feriado)
- ✅ Sistema de banco de horas
- ✅ Relatórios aprimorados
- ✅ Comando de expiração automática
- ✅ Observer para processamento automático

---

**Documento gerado em:** 27 de outubro de 2025
**Autor:** Sistema de Ponto Eletrônico - Time de Desenvolvimento
