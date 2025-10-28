# 🧪 GUIA DE TESTE - ARQUIVOS AFD E AEJ

## ✅ Migration Executada com Sucesso

A tabela `time_entry_files` foi criada no banco de dados.

---

## 📋 CHECKLIST DE TESTES

### 1. Verificar Estrutura do Banco

Execute no seu cliente SQL (MySQL/phpMyAdmin):

```sql
-- Verificar se a tabela foi criada
DESCRIBE time_entry_files;

-- Verificar se as colunas estão corretas
SELECT
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_KEY
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'time_entry_files'
  AND TABLE_SCHEMA = DATABASE();
```

**Resultado esperado:** Tabela com todas as colunas definidas na migration.

---

### 2. Teste Via Interface Web

#### Passo 1: Acessar a Interface

1. Inicie o servidor (se ainda não estiver rodando):
```bash
php artisan serve
```

2. Acesse no navegador:
```
http://localhost:8000/admin/legal-files
```

#### Passo 2: Gerar AFD de Teste

1. No formulário "Gerar AFD":
   - Data Início: `2025-10-01`
   - Data Fim: `2025-10-31`
   - Clique em **"Gerar AFD"**

2. **Resultado esperado:**
   - Mensagem de sucesso ✅
   - Arquivo aparece na tabela abaixo
   - Ou mensagem informando que não há marcações no período

#### Passo 3: Gerar AEJ de Teste

1. No formulário "Gerar AEJ (Individual)":
   - Selecione um funcionário da lista
   - Data Início: `2025-10-01`
   - Data Fim: `2025-10-31`
   - Clique em **"Gerar AEJ"**

2. **Resultado esperado:**
   - Mensagem de sucesso ✅
   - Arquivo AEJ aparece na tabela

#### Passo 4: Testar Downloads

1. Na tabela de arquivos gerados:
   - Clique no botão azul (📥) para baixar o arquivo `.txt`
   - Se houver assinatura, clique no botão verde (🔒) para baixar `.p7s`
   - Clique no botão amarelo (📦) para baixar ZIP completo

2. **Resultado esperado:**
   - Downloads funcionam corretamente
   - Arquivos têm conteúdo válido (texto tabulado)

---

### 3. Teste Via Tinker (Console)

Execute no terminal:

```bash
php artisan tinker
```

Depois execute os seguintes comandos:

#### Teste 1: Verificar se o modelo funciona

```php
// Verificar modelo
$file = new App\Models\TimeEntryFile();
echo "Modelo carregado: " . get_class($file);

// Verificar se há algum arquivo gerado
$count = App\Models\TimeEntryFile::count();
echo "Total de arquivos: " . $count;
```

#### Teste 2: Gerar AFD Programaticamente

```php
use App\Services\AFDService;
use App\Models\Tenant;

// Buscar primeiro tenant
$tenant = Tenant::first();

if ($tenant) {
    // Instanciar serviço
    $afdService = app(AFDService::class);

    // Gerar AFD
    try {
        $file = $afdService->generateAFD(
            $tenant,
            '2025-10-01',
            '2025-10-31',
            1 // user_id
        );

        if ($file) {
            echo "AFD gerado com sucesso!\n";
            echo "ID: " . $file->id . "\n";
            echo "Registros: " . $file->total_records . "\n";
            echo "Tamanho: " . $file->file_size . " bytes\n";
            echo "Arquivo: " . $file->file_path . "\n";
        } else {
            echo "Nenhum arquivo gerado (sem marcações no período)\n";
        }
    } catch (Exception $e) {
        echo "Erro: " . $e->getMessage() . "\n";
    }
} else {
    echo "Nenhum tenant encontrado\n";
}
```

#### Teste 3: Gerar AEJ Programaticamente

```php
use App\Services\AEJService;
use App\Models\Employee;

// Buscar primeiro funcionário
$employee = Employee::first();

if ($employee) {
    $aejService = app(AEJService::class);

    try {
        $file = $aejService->generateAEJ(
            $employee,
            '2025-10-01',
            '2025-10-31',
            1 // user_id
        );

        if ($file) {
            echo "AEJ gerado com sucesso!\n";
            echo "ID: " . $file->id . "\n";
            echo "Funcionário: " . $employee->name . "\n";
            echo "Registros: " . $file->total_records . "\n";
            echo "Estatísticas: " . json_encode($file->statistics) . "\n";
        } else {
            echo "Nenhum arquivo gerado\n";
        }
    } catch (Exception $e) {
        echo "Erro: " . $e->getMessage() . "\n";
    }
} else {
    echo "Nenhum funcionário encontrado\n";
}
```

#### Teste 4: Verificar Conteúdo do Arquivo

```php
// Buscar último arquivo gerado
$file = App\Models\TimeEntryFile::latest()->first();

if ($file) {
    echo "Tipo: " . $file->file_type . "\n";
    echo "Período: " . $file->period_start->format('d/m/Y') . " a " . $file->period_end->format('d/m/Y') . "\n";
    echo "Assinado: " . ($file->is_signed ? 'SIM' : 'NÃO') . "\n\n";

    // Mostrar primeiras linhas do arquivo
    $content = $file->getFileContent();
    if ($content) {
        $lines = explode("\n", $content);
        echo "Primeiras 5 linhas do arquivo:\n";
        echo "----------------------------------------\n";
        for ($i = 0; $i < min(5, count($lines)); $i++) {
            echo ($i + 1) . ": " . $lines[$i] . "\n";
        }
        echo "----------------------------------------\n";
    }
}
```

---

### 4. Verificar Arquivos Físicos Criados

No Windows Explorer ou via comando:

```bash
# Listar arquivos AFD gerados
dir storage\app\afd

# Listar arquivos AEJ gerados
dir storage\app\aej
```

**Ou via PowerShell:**

```powershell
Get-ChildItem -Path "storage\app\afd" -Recurse
Get-ChildItem -Path "storage\app\aej" -Recurse
```

**Resultado esperado:**
- Arquivos `.txt` com nomes no formato:
  - AFD: `AFD_CNPJ_YYYYMMDD_YYYYMMDD.txt`
  - AEJ: `AEJ_CNPJ_MATRICULA_YYYYMMDD_YYYYMMDD.txt`
- Arquivos `.p7s` (se certificado digital estiver configurado)

---

### 5. Verificar Logs

Se houver algum erro, verificar os logs:

```bash
# Ver últimas linhas do log
tail -n 50 storage/logs/laravel.log

# No Windows (PowerShell):
Get-Content storage\logs\laravel.log -Tail 50
```

---

## 🐛 TROUBLESHOOTING

### Problema: "Nenhum arquivo gerado"

**Causa:** Não há marcações de ponto no período selecionado.

**Solução:**
1. Verificar se há registros em `time_entries`:
```sql
SELECT COUNT(*) FROM time_entries
WHERE date BETWEEN '2025-10-01' AND '2025-10-31';
```

2. Se não houver marcações, criar marcações de teste ou usar período diferente.

### Problema: "Erro ao criar diretório"

**Causa:** Permissões de escrita.

**Solução:**
```bash
# Linux/Mac
chmod -R 775 storage/app

# Windows
# Dar permissão total ao usuário atual nas pastas storage/app
```

### Problema: "Certificado não disponível"

**Causa:** Certificado digital não está configurado.

**Observação:** Isso é normal! Os arquivos serão gerados SEM assinatura digital.
- Para testes, isso é aceitável
- Para produção, você precisará configurar o certificado

**Resultado:**
- `is_signed` = `false`
- `signature_path` = `null`
- Arquivo `.txt` é gerado normalmente
- Arquivo `.p7s` NÃO é gerado

---

## ✅ VALIDAÇÃO DOS ARQUIVOS GERADOS

### Validar Formato AFD

Abra o arquivo `.txt` gerado e verifique:

1. **Codificação:** ISO-8859-1 (caracteres acentuados corretos)
2. **Separador:** TAB entre campos
3. **Quebra de linha:** \r\n (Windows)
4. **Primeira linha:** Deve começar com NSR 000000001 e tipo 1 (header)
5. **Última linha:** Tipo 9 (trailer) com total de registros

**Exemplo esperado:**
```
000000001	1	AFD	01	...
000000002	2	CNPJ	...
...
000000999	9	000000999
```

### Validar Formato AEJ

Similar ao AFD, mas com tipos de registro diferentes:

1. **Header:** Tipo 1
2. **Empregador:** Tipo 2
3. **Empregado:** Tipo 3
4. **Jornada:** Tipo 4
5. **Marcações:** Tipos 5, 6, 7
6. **Totais:** Tipo 8
7. **Trailer:** Tipo 9

---

## 📊 DADOS DE TESTE

Se precisar criar dados de teste para validar os arquivos:

```php
// Criar marcação de teste via Tinker
use App\Models\TimeEntry;
use App\Models\Employee;
use Carbon\Carbon;

$employee = Employee::first();

if ($employee) {
    TimeEntry::create([
        'employee_id' => $employee->id,
        'date' => Carbon::today(),
        'clock_in' => '08:00:00',
        'lunch_start' => '12:00:00',
        'lunch_end' => '13:00:00',
        'clock_out' => '17:00:00',
        'total_minutes' => 480,
        'total_hours' => 8.0,
        'status' => 'approved',
        'ip_address' => '127.0.0.1',
    ]);

    echo "Marcação de teste criada!\n";
}
```

---

## 🎯 PRÓXIMOS PASSOS APÓS TESTES

1. ✅ Confirmar que arquivos são gerados corretamente
2. ✅ Validar formato dos arquivos
3. ⚠️ Configurar certificado digital ICP-Brasil (para assinatura)
4. ⚠️ Testar assinatura digital (após certificado configurado)
5. ⚠️ Validar arquivos assinados com ferramentas oficiais
6. ✅ Criar rotina de geração mensal automática (opcional)

---

## 📞 SUPORTE

Se encontrar problemas:

1. Verificar logs: `storage/logs/laravel.log`
2. Verificar permissões: pastas `storage/app/afd` e `storage/app/aej`
3. Verificar configuração: arquivo `.env`
4. Consultar documentação: `ARQUIVOS_AFD_AEJ_IMPLEMENTADO.md`

---

**Status:** ✅ Sistema implementado e migration executada com sucesso!
**Próximo passo:** Realizar testes acima para validar funcionamento.
