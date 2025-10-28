# Jornadas de Trabalho - Implementação Completa

## Status: ✅ COMPLETO E FUNCIONAL

Sistema completo de cadastro e gestão de jornadas de trabalho no painel admin-tenant.

---

## 📦 Arquivos Criados/Modificados

### 1. **Database - Migrations**

#### `database/migrations/2025_10_21_214455_create_work_schedules_table.php`
Tabela principal de jornadas de trabalho com:
- **Informações básicas**: `name`, `code`, `description`
- **Carga horária**: `weekly_hours`, `daily_hours`, `break_minutes`
- **Horários padrão**: `default_start_time`, `default_end_time`, `default_break_start`, `default_break_end`
- **Configuração por dia**: `days_config` (JSON) - permite configurar cada dia da semana individualmente
- **Tolerâncias**: `tolerance_minutes_entry`, `tolerance_minutes_exit`
- **Configurações extras**: `consider_holidays`, `allow_overtime`, `is_active`
- **Relacionamentos**: `tenant_id` (foreign key)
- **Soft deletes**: Permite exclusão lógica

#### `database/migrations/2025_10_21_215708_add_work_schedule_id_to_employees_table.php`
Adiciona relacionamento de funcionário com jornada:
- Campo `work_schedule_id` (nullable, foreign key)
- `nullOnDelete` - se jornada for excluída, funcionário não perde dados

**Status**: ✅ Migrations executadas com sucesso

---

### 2. **Models**

#### `app/Models/WorkSchedule.php`
Model completo com:
- **SoftDeletes** trait
- **Fillable fields** - todos os campos configuráveis
- **Casts** - conversão automática de tipos (boolean, integer, array)
- **Relacionamentos**:
  - `tenant()` - BelongsTo Tenant
  - `employees()` - HasMany Employee
- **Helper methods**:
  - `getDayConfig(string $day)` - retorna config de um dia específico
  - `worksOnDay(string $day)` - verifica se trabalha em determinado dia
  - `getWorkingDays()` - retorna array com dias que trabalha

#### `app/Models/Employee.php` (atualizado)
- Adicionado `work_schedule_id` ao `$fillable`
- Adicionado relacionamento `workSchedule()` - BelongsTo WorkSchedule

---

### 3. **Views - Livewire Component**

#### `resources/views/livewire/admin/work-schedules/index.blade.php`
Component Livewire Volt completo (1.300+ linhas) com:

**Funcionalidades:**
- ✅ **Listagem paginada** de jornadas
- ✅ **Busca em tempo real** (nome, código, descrição)
- ✅ **Modal de criação/edição** com todos os campos
- ✅ **Exclusão** com confirmação (verifica se há funcionários vinculados)
- ✅ **Toggle de status** (ativar/desativar)
- ✅ **Validação completa** de formulário
- ✅ **Flash messages** (sucesso/erro)
- ✅ **Responsivo** e moderno (Tailwind CSS)

**Campos do Formulário:**
1. **Informações Básicas**
   - Nome da Jornada
   - Código (único, uppercase)
   - Descrição (opcional)
   - Status (ativo/inativo)

2. **Carga Horária**
   - Horas semanais (1-60)
   - Horas diárias (1-24)
   - Intervalo em minutos (0-480)

3. **Horários Padrão**
   - Horário de entrada (time picker)
   - Horário de saída (time picker)
   - Início do intervalo
   - Fim do intervalo

4. **Dias de Trabalho**
   - Checkboxes para cada dia da semana
   - Segunda a Domingo
   - Padrão: Segunda a Sexta ativados

5. **Tolerâncias**
   - Tolerância entrada (0-120 min)
   - Tolerância saída (0-120 min)

6. **Configurações Adicionais**
   - Considerar feriados (checkbox)
   - Permitir horas extras (checkbox)

**Design:**
- Gradientes azul/índigo
- Cards coloridos por seção (cinza, azul, índigo, verde, amarelo, roxo)
- Modal responsivo com scroll
- Sticky header e footer no modal
- Hover effects e transições suaves
- Ícones SVG consistentes

---

### 4. **Views - Wrapper**

#### `resources/views/admin/work-schedules/index.blade.php`
View simples que extends o layout principal e carrega o Livewire component.

---

### 5. **Routes**

#### `routes/web.php` (modificado)
Nova rota adicionada no grupo admin:
```php
Route::get('/work-schedules', function () {
    return view('admin.work-schedules.index');
})->name('work-schedules.index');
```

**Middleware aplicado:**
- `auth` - requer autenticação
- `tenant.active` - tenant deve estar ativo

**Acesso:** Todos os usuários autenticados do tenant podem acessar

---

### 6. **Navigation Menu**

#### `resources/views/layouts/app.blade.php` (modificado)
Menu adicionado na seção "Gestão", logo após "Funcionários":
- Ícone de calendário
- Label: "Jornadas"
- Highlight automático quando rota ativa
- Gradiente azul quando selecionado

---

## 🎨 Interface Visual

### Tela de Listagem
- **Header**: Título + botão "Nova Jornada"
- **Busca**: Campo com ícone de lupa
- **Tabela**: 7 colunas
  1. Nome (com descrição abaixo)
  2. Código (badge azul)
  3. Horas Semanais (ex: "44h/sem")
  4. Horário Padrão (ex: "08:00 - 17:00")
  5. Dias Ativos (ex: "5 dias")
  6. Status (badge verde/vermelho com toggle)
  7. Ações (editar/excluir)
- **Paginação**: Links do Laravel
- **Empty state**: Mensagem + ícone quando vazio

### Modal de Cadastro/Edição
- **Layout**: 2 colunas responsivo
- **Seções coloridas**:
  - Cinza: Informações básicas
  - Azul: Carga horária
  - Índigo: Horários padrão
  - Verde: Dias de trabalho
  - Amarelo: Tolerâncias
  - Roxo: Configurações adicionais
- **Validação**: Mensagens em vermelho abaixo dos campos
- **Botões**: Cancelar (cinza) + Salvar (gradiente azul)

---

## 🔧 Como Usar

### 1. Acessar o Módulo
```
http://localhost:8000/admin/work-schedules
```

### 2. Criar Nova Jornada
1. Clique em "Nova Jornada"
2. Preencha os campos obrigatórios:
   - Nome (ex: "Jornada Padrão 44h")
   - Código (ex: "JOR-001")
   - Horas semanais (ex: 44)
   - Horas diárias (ex: 8)
   - Intervalo (ex: 60 minutos)
3. Configure horários (opcional)
4. Selecione dias de trabalho
5. Ajuste tolerâncias
6. Marque opções adicionais
7. Clique em "Salvar"

### 3. Editar Jornada
1. Clique no ícone de lápis na linha da jornada
2. Altere os campos desejados
3. Clique em "Atualizar"

### 4. Excluir Jornada
1. Clique no ícone de lixeira
2. Confirme a exclusão
3. **Atenção**: Não é possível excluir jornadas com funcionários vinculados

### 5. Ativar/Desativar
- Clique no badge de status (verde/vermelho)
- Alterna entre Ativo/Inativo instantaneamente

### 6. Buscar Jornadas
- Digite no campo de busca
- Busca automática por: nome, código ou descrição

---

## 📊 Estrutura do Banco de Dados

### Tabela: `work_schedules`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | BIGINT | ID único |
| `tenant_id` | BIGINT | FK para tenants |
| `name` | VARCHAR | Nome da jornada |
| `code` | VARCHAR | Código único |
| `description` | TEXT | Descrição (opcional) |
| `weekly_hours` | INTEGER | Horas semanais |
| `daily_hours` | INTEGER | Horas diárias |
| `break_minutes` | INTEGER | Minutos de intervalo |
| `default_start_time` | TIME | Horário entrada padrão |
| `default_end_time` | TIME | Horário saída padrão |
| `default_break_start` | TIME | Início intervalo |
| `default_break_end` | TIME | Fim intervalo |
| `days_config` | JSON | Configuração por dia |
| `tolerance_minutes_entry` | INTEGER | Tolerância entrada |
| `tolerance_minutes_exit` | INTEGER | Tolerância saída |
| `consider_holidays` | BOOLEAN | Considera feriados |
| `allow_overtime` | BOOLEAN | Permite horas extras |
| `is_active` | BOOLEAN | Status ativo |
| `created_at` | TIMESTAMP | Data criação |
| `updated_at` | TIMESTAMP | Data atualização |
| `deleted_at` | TIMESTAMP | Soft delete |

### Exemplo de `days_config` (JSON):
```json
{
  "monday": {
    "active": true,
    "label": "Segunda-feira"
  },
  "tuesday": {
    "active": true,
    "label": "Terça-feira"
  },
  "wednesday": {
    "active": true,
    "label": "Quarta-feira"
  },
  "thursday": {
    "active": true,
    "label": "Quinta-feira"
  },
  "friday": {
    "active": true,
    "label": "Sexta-feira"
  },
  "saturday": {
    "active": false,
    "label": "Sábado"
  },
  "sunday": {
    "active": false,
    "label": "Domingo"
  }
}
```

### Tabela: `employees` (campo adicionado)

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `work_schedule_id` | BIGINT | FK para work_schedules (nullable) |

---

## 🔗 Relacionamentos

```
Tenant (1) ──── (N) WorkSchedule
WorkSchedule (1) ──── (N) Employee
Tenant (1) ──── (N) Employee
```

---

## 🎯 Validações Implementadas

### Backend (Livewire)
- `name`: required, string, max 255
- `code`: required, string, max 50, unique (exceto na edição)
- `description`: nullable, string
- `weekly_hours`: required, integer, 1-60
- `daily_hours`: required, integer, 1-24
- `break_minutes`: required, integer, 0-480
- `default_start_time`: nullable, formato H:i
- `default_end_time`: nullable, formato H:i
- `default_break_start`: nullable, formato H:i
- `default_break_end`: nullable, formato H:i
- `tolerance_minutes_entry`: required, integer, 0-120
- `tolerance_minutes_exit`: required, integer, 0-120

### Regras de Negócio
- ✅ Código sempre em uppercase
- ✅ Verifica funcionários vinculados antes de excluir
- ✅ Soft delete (não apaga permanentemente)
- ✅ Tenant isolado (apenas jornadas do próprio tenant)

---

## 🚀 Próximos Passos Sugeridos

### 1. Integração com Funcionários
- [ ] Adicionar select de jornada no cadastro de funcionários
- [ ] Permitir vincular/desvincular jornada na edição
- [ ] Mostrar jornada atual na listagem de funcionários

### 2. Horários Personalizados por Dia
- [ ] Permitir configurar horários diferentes para cada dia da semana
- [ ] Ex: Segunda 8h-17h, Sexta 8h-16h

### 3. Cálculo de Horas
- [ ] Usar jornada para calcular horas trabalhadas
- [ ] Calcular horas extras automaticamente
- [ ] Aplicar tolerâncias nos registros de ponto

### 4. Feriados
- [ ] Integrar com API de feriados (Invertexto)
- [ ] Cadastro manual de feriados por tenant
- [ ] Não contabilizar trabalho em feriados (se `consider_holidays` ativo)

### 5. Relatórios
- [ ] Relatório de horas por jornada
- [ ] Comparativo de jornadas
- [ ] Funcionários por jornada

### 6. Dashboard
- [ ] Card com total de jornadas ativas
- [ ] Gráfico de funcionários por jornada
- [ ] Jornadas mais usadas

### 7. Validações Avançadas
- [ ] Validar se horário de saída > horário de entrada
- [ ] Validar se intervalo está dentro do expediente
- [ ] Alerta se horas semanais extrapolam limite legal

### 8. Exportação
- [ ] Exportar jornadas para PDF
- [ ] Exportar jornadas para Excel
- [ ] Template de importação em massa

---

## 📝 Exemplos de Uso

### Jornada CLT Padrão (44h semanais)
```
Nome: Jornada CLT 44h
Código: CLT-44
Horas Semanais: 44
Horas Diárias: 8 (8h48m na prática)
Intervalo: 60 minutos
Horário: 08:00 - 17:00
Dias: Segunda a Sexta
Tolerância: 10 minutos
```

### Jornada 6x1 (Comercial)
```
Nome: Jornada 6x1 Comercial
Código: 6X1-COM
Horas Semanais: 44
Horas Diárias: 7 (7h20m na prática)
Intervalo: 60 minutos
Horário: 09:00 - 17:00
Dias: Segunda a Sábado
Tolerância: 5 minutos
```

### Jornada Meio Período
```
Nome: Meio Período Manhã
Código: MP-MANHA
Horas Semanais: 20
Horas Diárias: 4
Intervalo: 0 minutos
Horário: 08:00 - 12:00
Dias: Segunda a Sexta
Tolerância: 10 minutos
```

### Jornada Turnos (12x36)
```
Nome: Jornada 12x36
Código: 12X36
Horas Semanais: 42
Horas Diárias: 12
Intervalo: 60 minutos
Horário: 07:00 - 19:00
Dias: Configuração alternada (via days_config)
Tolerância: 15 minutos
```

---

## 🎨 Screenshots (Descrição)

### Tela de Listagem
```
╔════════════════════════════════════════════════════════════╗
║  Jornadas de Trabalho              [+ Nova Jornada]        ║
║  Gerencie as escalas e horários de trabalho                ║
╠════════════════════════════════════════════════════════════╣
║  [🔍 Buscar por nome, código ou descrição...]              ║
╠═══════╦════════╦═════════╦══════════╦═══════╦═══════╦═════╣
║ Nome  │ Código │ H/Sem   │ Horário  │ Dias  │ Status│ Ações║
╠═══════╬════════╬═════════╬══════════╬═══════╬═══════╬═════╣
║ CLT   │ CLT-44 │ 44h/sem │ 08-17h   │ 5dias │ Ativo │ ✏️🗑️ ║
║ 6x1   │ 6X1-01 │ 44h/sem │ 09-17h   │ 6dias │ Ativo │ ✏️🗑️ ║
║ Meio  │ MP-001 │ 20h/sem │ 08-12h   │ 5dias │Inativo│ ✏️🗑️ ║
╚═══════╩════════╩═════════╩══════════╩═══════╩═══════╩═════╝
```

---

## 🐛 Tratamento de Erros

### Exclusão Protegida
```
❌ Não é possível excluir esta jornada pois existem
   funcionários vinculados.
```

### Código Duplicado
```
❌ O campo código já está em uso.
```

### Validação de Campos
```
❌ O campo horas semanais deve ser entre 1 e 60.
```

---

## 📞 Suporte

### Logs
- **Laravel Logs**: `storage/logs/laravel.log`
- **Browser Console**: Livewire debugger (F12)

### Troubleshooting

**Problema:** Modal não abre
- **Solução**: Verifique se Livewire está carregado (`@livewireScripts`)

**Problema:** Jornada não salva
- **Solução**: Verifique validações no console e logs do Laravel

**Problema:** Não aparece no menu
- **Solução**: Verifique permissões do usuário e rota em `web.php`

---

## 🏆 Tecnologias Utilizadas

- **Backend**: Laravel 11
- **Frontend**: Livewire Volt 3
- **UI**: Tailwind CSS 3
- **Database**: MySQL/PostgreSQL
- **Icons**: Heroicons (SVG)

---

## ✅ Checklist de Implementação

- [x] Migration `create_work_schedules_table`
- [x] Migration `add_work_schedule_id_to_employees`
- [x] Model `WorkSchedule` completo
- [x] Model `Employee` atualizado
- [x] Livewire Component com CRUD
- [x] View wrapper
- [x] Rota registrada
- [x] Menu de navegação
- [x] Relacionamentos configurados
- [x] Validações backend
- [x] Flash messages
- [x] Soft deletes
- [x] Design responsivo
- [x] Documentação completa

---

**Desenvolvido por**: Claude Code (Anthropic)
**Data**: 21/10/2025
**Versão**: 1.0.0
**Status**: ✅ Produção Ready
