# Sistema de Cadastro de Feriados Municipais

## Visão Geral

Sistema completo para gerenciamento de feriados por tenant (empresa), permitindo configuração personalizada de feriados nacionais, estaduais, municipais e personalizados.

**Data de Implementação:** 27/10/2025
**Integração:** Sistema de Horas Extras (OvertimeService)

---

## 📋 Funcionalidades

### ✅ Tipos de Feriados Suportados

1. **Nacional** - Feriados nacionais do Brasil
2. **Estadual** - Feriados específicos de cada estado
3. **Municipal** - Feriados da cidade da empresa
4. **Personalizado** - Feriados customizados pela empresa

### ✅ Características

- **Feriados Recorrentes:** Repetem automaticamente todo ano (ex: Natal, Ano Novo)
- **Feriados Únicos:** Ocorrem apenas uma vez (ex: ponto facultativo específico)
- **Importação Automática:** Botão para importar 9 feriados nacionais brasileiros
- **Ativação/Desativação:** Controle individual de cada feriado
- **Filtros:** Por tipo, ano e pesquisa textual
- **CRUD Completo:** Criar, editar, visualizar e excluir feriados

---

## 🗄️ Estrutura do Banco de Dados

### Tabela: `holidays`

```sql
CREATE TABLE holidays (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT NOT NULL,              -- FK para tenants
    name VARCHAR(255) NOT NULL,             -- Nome do feriado
    date DATE NOT NULL,                     -- Data do feriado
    type ENUM('national', 'state', 'municipal', 'custom') DEFAULT 'municipal',
    city VARCHAR(255) NULL,                 -- Cidade (feriados municipais)
    state VARCHAR(2) NULL,                  -- UF (feriados estaduais)
    is_recurring BOOLEAN DEFAULT false,     -- Se repete anualmente
    description TEXT NULL,                  -- Descrição/observações
    is_active BOOLEAN DEFAULT true,         -- Ativo/Inativo
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    INDEX (tenant_id, date),
    INDEX (tenant_id, is_active),
    INDEX (type)
);
```

**Migration:** `database/migrations/2025_10_27_203951_create_holidays_table.php`

---

## 📦 Arquivos Criados

### 1. Model
**Arquivo:** `app/Models/Holiday.php`

**Métodos principais:**
- `isHoliday(Carbon $date, int $tenantId): bool` - Verifica se data é feriado
- `getHolidaysInPeriod()` - Retorna feriados de um período
- `createDefaultNationalHolidays()` - Importa feriados nacionais
- Scopes: `forTenant()`, `active()`, `ofType()`, `recurring()`, `forYear()`

### 2. Componente Livewire
**Arquivo:** `resources/views/livewire/admin/holidays/index.blade.php`

**Funcionalidades:**
- CRUD completo (Create, Read, Update, Delete)
- Modal para criar/editar
- Filtros por tipo e ano
- Pesquisa por nome/cidade
- Botão de importação de feriados nacionais
- Toggle de ativação/desativação
- Paginação

### 3. Rota
**Arquivo:** `routes/web.php`

```php
Route::get('/admin/holidays', function () {
    return view('admin.holidays.index');
})->name('admin.holidays.index');
```

### 4. Integração com OvertimeService
**Arquivo:** `app/Services/OvertimeService.php`

**Método atualizado:**
```php
protected function isHoliday(Carbon $date, int $tenantId): bool
{
    return \App\Models\Holiday::isHoliday($date, $tenantId);
}
```

---

## 🎯 Como Usar

### 1. Acessar o Sistema

Após login como administrador, acesse:
```
/admin/holidays
```

### 2. Importar Feriados Nacionais

1. Clique no botão **"Importar Feriados Nacionais"**
2. Sistema importa automaticamente 9 feriados nacionais:
   - Ano Novo (01/01)
   - Tiradentes (21/04)
   - Dia do Trabalho (01/05)
   - Independência do Brasil (07/09)
   - Nossa Senhora Aparecida (12/10)
   - Finados (02/11)
   - Proclamação da República (15/11)
   - Consciência Negra (20/11)
   - Natal (25/12)

3. Todos serão marcados como **recorrentes** (repetem todo ano)

### 3. Cadastrar Feriado Municipal

1. Clique em **"Novo Feriado"**
2. Preencha:
   - **Nome:** Ex: "Aniversário da Cidade"
   - **Data:** Selecione a data
   - **Tipo:** Selecione "Municipal"
   - **Cidade:** Nome da sua cidade
   - **UF:** Sigla do estado (2 letras)
   - **Descrição:** (Opcional)
   - ✅ **Recorrente:** Marque se repete todo ano
   - ✅ **Ativo:** Deixe marcado
3. Clique em **"Criar"**

### 4. Editar Feriado

1. Na lista, clique em **"Editar"** no feriado desejado
2. Altere os dados necessários
3. Clique em **"Salvar"**

### 5. Ativar/Desativar Feriado

- Clique no status "Ativo" ou "Inativo" na lista
- Feriados inativos não são considerados no cálculo de horas extras

### 6. Excluir Feriado

1. Clique em **"Excluir"**
2. Confirme a exclusão

---

## 🔗 Integração com Sistema de Horas Extras

### Como Funciona

Quando um funcionário registra ponto em um feriado:

1. **TimeEntry** é criado normalmente
2. **TimeEntryObserver** detecta e processa
3. **OvertimeService::determineOvertimeType()** é chamado
4. Verifica se a data é domingo OU feriado:
   ```php
   if ($date->isSunday() || $this->isHoliday($date, $timeEntry->tenant_id)) {
       return 'holiday'; // Tipo: Feriado/Domingo (100%)
   }
   ```
5. Se for feriado:
   - `overtime_type` = 'holiday'
   - `overtime_percentage` = 100 (100% de adicional)
   - Hora extra calculada com adicional de 100%

### Exemplo Prático

```php
// Feriado: 25/12/2025 (Natal)
// Funcionário trabalha 8 horas
// Jornada normal: 8 horas
// Horas extras: 0h (não passou da jornada)
// MAS: Tipo = 'holiday' (trabalhou em feriado)
// Adicional: 100% sobre as 8 horas trabalhadas
```

---

## 💻 Exemplos de Código

### Verificar se uma data é feriado

```php
use App\Models\Holiday;
use Carbon\Carbon;

$date = Carbon::parse('2025-12-25'); // Natal
$tenantId = auth()->user()->tenant_id;

if (Holiday::isHoliday($date, $tenantId)) {
    echo "É feriado!";
}
```

### Obter feriados de um mês

```php
$startDate = Carbon::parse('2025-12-01');
$endDate = Carbon::parse('2025-12-31');
$tenantId = 1;

$holidays = Holiday::getHolidaysInPeriod($startDate, $endDate, $tenantId);

foreach ($holidays as $holiday) {
    echo "{$holiday->name} - {$holiday->formatted_date}\n";
}
```

### Criar feriado programaticamente

```php
Holiday::create([
    'tenant_id' => 1,
    'name' => 'Aniversário da Cidade',
    'date' => '2025-06-15',
    'type' => 'municipal',
    'city' => 'São Paulo',
    'state' => 'SP',
    'is_recurring' => true,
    'description' => 'Feriado municipal',
    'is_active' => true,
]);
```

### Importar feriados nacionais

```php
$count = Holiday::createDefaultNationalHolidays(
    tenantId: 1,
    year: 2025
);

echo "Importados {$count} feriados nacionais";
```

---

## 📊 Interface do Usuário

### Tela Principal

- **Cabeçalho:**
  - Título: "Gerenciar Feriados"
  - Botões: "Importar Feriados Nacionais" (azul) | "Novo Feriado" (verde)

- **Filtros:**
  - Pesquisar: Campo de texto
  - Tipo: Dropdown (Todos, Nacional, Estadual, Municipal, Personalizado)
  - Ano: Dropdown (Ano atual - 1 até + 2)

- **Tabela:**
  - Colunas: Data | Nome | Tipo | Localidade | Recorrente | Status | Ações
  - Badges coloridos por tipo:
    - 🔵 Nacional (azul)
    - 🟢 Estadual (verde)
    - 🟣 Municipal (roxo)
    - ⚪ Personalizado (cinza)

### Modal de Criar/Editar

**Campos:**
1. Nome do Feriado * (obrigatório)
2. Data * (obrigatório)
3. Tipo * (dropdown)
4. Cidade (opcional)
5. UF (opcional, max 2 caracteres)
6. Descrição (textarea, opcional)
7. ✅ Recorrente (checkbox)
8. ✅ Ativo (checkbox)

**Botões:**
- "Criar" / "Salvar" (roxo)
- "Cancelar" (branco)

---

## 🎨 Estilos e Cores

| Tipo | Cor Badge | Hex |
|------|-----------|-----|
| Nacional | Azul | `bg-blue-100 text-blue-800` |
| Estadual | Verde | `bg-green-100 text-green-800` |
| Municipal | Roxo | `bg-purple-100 text-purple-800` |
| Personalizado | Cinza | `bg-gray-100 text-gray-800` |

---

## 🔧 Manutenção

### Adicionar Novos Feriados Nacionais

Edite o método `createDefaultNationalHolidays()` no model `Holiday.php`:

```php
$nationalHolidays = [
    // ... feriados existentes
    ['name' => 'Novo Feriado', 'date' => "{$year}-01-15", 'is_recurring' => true],
];
```

### Limpar Feriados Antigos

```sql
-- Deletar feriados não recorrentes de anos anteriores
DELETE FROM holidays
WHERE is_recurring = false
AND YEAR(date) < YEAR(CURDATE());
```

### Desativar Feriados em Massa

```sql
-- Desativar todos feriados municipais
UPDATE holidays
SET is_active = false
WHERE type = 'municipal';
```

---

## ⚠️ Observações Importantes

### Feriados Recorrentes

- Feriados marcados como **recorrentes** são consultados pelo **dia e mês**, ignorando o ano
- Exemplo: Natal (25/12/2025) será considerado feriado em **todos os anos** no dia 25/12

### Feriados por Tenant

- Cada empresa (tenant) tem seus próprios feriados
- Feriados não são compartilhados entre tenants
- Um tenant pode ter feriados diferentes de outro

### Validação de Horas Extras

- O sistema verifica automaticamente se a data é feriado
- Trabalho em feriado = adicional de **100%**
- Trabalho em domingo = adicional de **100%**
- Ambos usam o mesmo tipo: `overtime_type = 'holiday'`

### Performance

- Índices criados em `(tenant_id, date)` e `(tenant_id, is_active)`
- Consultas otimizadas para busca rápida
- Paginação de 15 registros por página

---

## 📱 Acesso pelo Menu

Para adicionar ao menu de navegação, edite o arquivo de layout:

**Arquivo:** `resources/views/layouts/app.blade.php` ou `navigation.blade.php`

```html
<li>
    <a href="{{ route('admin.holidays.index') }}"
       class="nav-link {{ request()->routeIs('admin.holidays.*') ? 'active' : '' }}">
        📅 Feriados
    </a>
</li>
```

---

## 🚀 Melhorias Futuras

### Sugestões de Implementação

1. **API Externa de Feriados**
   - Integrar com API pública de feriados brasileiros
   - Atualização automática anual

2. **Exportação**
   - Exportar feriados para Excel/CSV
   - Importar feriados de arquivo

3. **Notificações**
   - Alertar sobre feriados próximos
   - Notificar funcionários sobre feriados

4. **Histórico**
   - Registrar alterações em feriados
   - Auditoria de criação/edição

5. **Feriados Móveis**
   - Suporte a feriados que mudam de data (Carnaval, Corpus Christi, etc.)
   - Cálculo automático baseado em algoritmo

6. **Permissões**
   - Controle de acesso por perfil
   - Apenas admin pode editar feriados nacionais

---

## 📝 Testes

### Teste Manual

1. **Importar Feriados:**
   - Acesse /admin/holidays
   - Clique em "Importar Feriados Nacionais"
   - Verifique se 9 feriados foram criados

2. **Criar Feriado Municipal:**
   - Clique em "Novo Feriado"
   - Preencha dados de feriado da sua cidade
   - Verifique se aparece na lista

3. **Testar Hora Extra em Feriado:**
   - Crie um feriado para hoje
   - Registre um ponto com 8h trabalhadas
   - Verifique se `overtime_type` = 'holiday'

4. **Testar Feriado Recorrente:**
   - Crie feriado em 2025 marcado como recorrente
   - Consulte se é feriado em 2026 (mesmo dia/mês)
   - Deve retornar `true`

---

## 🐛 Troubleshooting

### Problema: Feriado não está sendo detectado

**Verificar:**
1. Feriado está `is_active = true`?
2. `tenant_id` correto?
3. Data exata ou recorrente?

**Debug:**
```php
$date = Carbon::parse('2025-12-25');
$tenantId = 1;

$holiday = Holiday::where('tenant_id', $tenantId)
    ->where('is_active', true)
    ->whereDate('date', $date)
    ->first();

dd($holiday);
```

### Problema: Importação não funciona

**Verificar:**
1. User está autenticado?
2. `tenant_id` existe?
3. Verificar logs do Laravel

**Solução:**
```php
// Executar manualmente
Holiday::createDefaultNationalHolidays(1, 2025);
```

### Problema: Feriado recorrente não funciona

**Verificar:**
- Campo `is_recurring` está `true`
- Método `isHoliday()` usa `whereMonth()` e `whereDay()`

**Query de teste:**
```sql
SELECT * FROM holidays
WHERE tenant_id = 1
AND is_recurring = true
AND MONTH(date) = 12
AND DAY(date) = 25;
```

---

## 📞 Resumo Técnico

| Item | Valor |
|------|-------|
| **Tabelas criadas** | 1 (holidays) |
| **Models criados** | 1 (Holiday) |
| **Views criadas** | 1 (Livewire Volt) |
| **Rotas adicionadas** | 1 |
| **Métodos no Model** | 15+ |
| **Tipos de feriado** | 4 (nacional, estadual, municipal, custom) |
| **Feriados nacionais padrão** | 9 |
| **Integração** | OvertimeService |

---

## ✅ Checklist de Implementação

- [x] Migration criada e executada
- [x] Model Holiday implementado
- [x] Métodos de consulta (isHoliday, getHolidaysInPeriod)
- [x] Método de importação de feriados nacionais
- [x] Componente Livewire completo
- [x] CRUD completo (Create, Read, Update, Delete)
- [x] Filtros (tipo, ano, pesquisa)
- [x] Interface responsiva
- [x] Rota adicionada
- [x] Integração com OvertimeService
- [x] Documentação criada

---

**Documento gerado em:** 27 de outubro de 2025
**Sistema:** Ponto Eletrônico - Time de Desenvolvimento
