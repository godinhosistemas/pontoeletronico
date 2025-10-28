# 📋 ANÁLISE DE CONFORMIDADE COM A PORTARIA MTP Nº 671/2021

**Sistema:** Ponto Eletrônico Next Ponto
**Data da Análise:** 22 de outubro de 2025
**Legislação:** Portaria MTP nº 671 de 8 de novembro de 2021
**Ministério do Trabalho e Previdência**

---

## 📖 SOBRE A PORTARIA 671

A Portaria MTP nº 671/2021 regulamenta disposições relativas à legislação trabalhista, incluindo:
- Registro eletrônico de ponto (REP)
- Controle de jornada de trabalho
- Geração de relatórios obrigatórios
- Proteção de dados dos trabalhadores (LGPD)

### Artigos Principais para REP:
- **Art. 81:** Especificações do Arquivo Fonte de Dados (AFD)
- **Art. 83:** Especificações do Arquivo Eletrônico de Jornada (AEJ) e Espelho de Ponto
- **Art. 89:** Atestado Técnico e Termo de Responsabilidade
- **Art. 101:** Observância da LGPD

---

## ✅ PONTOS CONFORMES DO SISTEMA

### 1. Folha Espelho de Ponto (Art. 83) ✅

**Status:** 100% Conforme
**Arquivo:** `resources/views/reports/timesheet-mirror.blade.php`

**Requisitos Atendidos:**
- ✅ Identificação do empregador (CNPJ, razão social)
- ✅ Identificação do empregado (nome, CPF, matrícula)
- ✅ Data de admissão e cargo
- ✅ Período de apuração
- ✅ Horários de entrada, saída e intervalos
- ✅ Total de horas trabalhadas por dia
- ✅ Total de horas do período
- ✅ Horas extras (sobrejornada diária)
- ✅ Horas faltosas
- ✅ Identificação de marcações ajustadas
- ✅ Código autenticador
- ✅ Data e responsável pelo fechamento

**Campos do Banco de Dados:**
```php
// TimeEntry Model
'clock_in'              // Entrada
'clock_out'             // Saída
'lunch_start'           // Início do almoço
'lunch_end'             // Fim do almoço
'total_minutes'         // Total em minutos
'total_hours'           // Total em horas
```

---

### 2. Registro de Ajustes e Rastreabilidade ✅

**Status:** 100% Conforme
**Tabela:** `time_entries`

**Requisitos Atendidos:**
- ✅ Armazena horários originais antes de ajustes
- ✅ Armazena horários ajustados separadamente
- ✅ Registra quem fez o ajuste (`adjusted_by`)
- ✅ Registra quando foi feito (`adjusted_at`)
- ✅ Registra justificativa do ajuste (`adjustment_reason`)
- ✅ Flag de identificação de ajuste (`has_adjustment`)

**Campos do Banco de Dados:**
```php
// Horários originais
'original_clock_in'
'original_clock_out'
'original_lunch_start'
'original_lunch_end'

// Horários ajustados
'adjusted_clock_in'
'adjusted_clock_out'
'adjusted_lunch_start'
'adjusted_lunch_end'

// Metadados do ajuste
'has_adjustment'        // Boolean
'adjustment_reason'     // Text
'adjusted_by'          // User ID
'adjusted_at'          // Timestamp
```

---

### 3. Armazenamento de Dados de Marcação ✅

**Status:** 100% Conforme
**Controller:** `app/Http/Controllers/Api/PwaClockController.php`

**Requisitos Atendidos:**
- ✅ Data e hora de cada marcação
- ✅ IP do dispositivo utilizado
- ✅ Foto facial do trabalhador
- ✅ Coordenadas GPS (latitude/longitude)
- ✅ Precisão do GPS (`gps_accuracy`)
- ✅ Distância do local permitido
- ✅ Validação de geolocalização

**Campos do Banco de Dados:**
```php
'ip_address'           // IP da marcação
'gps_latitude'         // Latitude GPS
'gps_longitude'        // Longitude GPS
'gps_accuracy'         // Precisão do GPS
'distance_meters'      // Distância do local permitido
'gps_validated'        // Validação GPS (boolean)
```

**Armazenamento de Fotos:**
```php
// Employee Model
'face_photo'           // Path da última foto facial
'face_descriptor'      // Descritor facial (JSON)

// Storage
storage/app/public/faces/{tenant_id}/face_{employee_id}_{timestamp}_{action}.jpg
```

---

### 4. Cálculos Trabalhistas ✅

**Status:** 100% Conforme
**Model:** `app/Models/TimeEntry.php`

**Requisitos Atendidos:**
- ✅ Cálculo automático de horas trabalhadas
- ✅ Subtração de intervalos
- ✅ Tratamento de virada de dia (meia-noite)
- ✅ Cálculo de horas extras
- ✅ Cálculo de horas faltosas
- ✅ Comparação com jornada esperada

**Método de Cálculo:**
```php
public function calculateTotalHours(): void
{
    // Calcula diferença entre clock_in e clock_out
    // Subtrai tempo de almoço (lunch_start até lunch_end)
    // Trata casos de virada de dia
    // Armazena em total_minutes e total_hours
}
```

---

### 5. Sistema de Jornadas de Trabalho ✅

**Status:** 100% Conforme
**Model:** `app/Models/WorkSchedule.php`

**Requisitos Atendidos:**
- ✅ Jornadas configuráveis por funcionário
- ✅ Dias de trabalho personalizáveis
- ✅ Horários específicos por dia da semana
- ✅ Intervalos configuráveis
- ✅ Carga horária semanal e mensal

**Estrutura:**
```php
'name'              // Nome da jornada (ex: "44h Semanais")
'code'              // Código (ex: "44H")
'weekly_hours'      // Horas semanais
'monthly_hours'     // Horas mensais
'days_config'       // JSON com config de cada dia
'break_minutes'     // Minutos de intervalo
```

---

### 6. Sistema de Aprovação ✅

**Status:** 100% Conforme
**Model:** `app/Models/TimeEntry.php`

**Requisitos Atendidos:**
- ✅ Workflow de aprovação (pending → approved/rejected)
- ✅ Registro de quem aprovou (`approved_by`)
- ✅ Data e hora da aprovação (`approved_at`)
- ✅ Estados: pending, approved, rejected

**Campos:**
```php
'status'           // pending, approved, rejected
'approved_by'      // User ID do aprovador
'approved_at'      // Timestamp da aprovação
```

---

## ❌ PONTOS DE NÃO CONFORMIDADE CRÍTICOS

### 1. Arquivo Fonte de Dados (AFD) - Art. 81 ❌

**Status:** NÃO IMPLEMENTADO
**Obrigatoriedade:** CRÍTICA
**Prazo:** Obrigatório desde 10/02/2022

**O que é:**
O AFD é um arquivo texto que contém todos os registros de ponto em formato padronizado, com assinatura digital, que serve como prova legal das marcações.

**Requisitos da Portaria:**
- Formato: Texto ISO 8859-1 ASCII
- Estrutura conforme especificação técnica do gov.br
- Assinatura digital CAdES (CMS Advanced Electronic Signature)
- Arquivo separado de assinatura (.p7s, detached)
- Certificado digital ICP-Brasil

**Registros que devem constar no AFD:**
- Tipo 1: Cabeçalho (dados do empregador)
- Tipo 2: Registro de marcação de ponto
- Tipo 3: Ajustes de marcação
- Tipo 4: Marcação de empregado
- Tipo 5: Trailer (totalizadores)

**Implementação Necessária:**

1. **Criar classe de geração do AFD:**
```php
// app/Services/AFDService.php
class AFDService
{
    public function generateAFD($tenantId, $startDate, $endDate)
    {
        // Gerar arquivo AFD conforme especificação
        // Retornar conteúdo do arquivo
    }

    public function signAFD($afdContent)
    {
        // Assinar com certificado ICP-Brasil
        // Gerar arquivo .p7s
    }
}
```

2. **Endpoint para download:**
```php
Route::get('/api/admin/afd/download', [AFDController::class, 'download']);
```

3. **Especificações técnicas:**
- Layout disponível em: https://www.gov.br/trabalho-e-emprego
- Documento: "Especificações do Arquivo Fonte de Dados do sistema de registro eletrônico de ponto"

**Campos necessários no AFD:**
- NSR (Número Sequencial do Registro)
- Tipo de registro
- Data e hora da marcação
- PIS do empregado
- Tipo de marcação (E=Entrada, S=Saída)
- Motivo (ajuste, inclusão, etc.)

**Prioridade:** 🔴 URGENTE - SEM ISSO O SISTEMA NÃO ESTÁ LEGAL

---

### 2. Arquivo Eletrônico de Jornada (AEJ) - Art. 83 ❌

**Status:** NÃO IMPLEMENTADO
**Obrigatoriedade:** CRÍTICA
**Prazo:** Obrigatório desde 11/01/2023

**O que é:**
O AEJ é um arquivo gerado pelo programa de tratamento de ponto que contém os dados processados da jornada de trabalho, incluindo totalizações e tratamentos aplicados.

**Requisitos da Portaria:**
- Formato: Texto ISO 8859-1 ASCII
- Estrutura conforme especificação técnica do gov.br
- Assinatura digital CAdES
- Arquivo separado de assinatura (.p7s)
- Deve ser gerado pelo "programa de tratamento"

**Diferença entre AFD e AEJ:**
- **AFD:** Dados brutos das marcações (direto do REP)
- **AEJ:** Dados processados e tratados (cálculos, totalizações, ajustes aplicados)

**Conteúdo do AEJ:**
- Identificação do empregador
- Identificação do empregado
- Período de apuração
- Marcações processadas (com ajustes aplicados)
- Total de horas normais
- Total de horas extras
- Total de horas faltosas
- Detalhamento de ajustes

**Implementação Necessária:**

1. **Criar classe de geração do AEJ:**
```php
// app/Services/AEJService.php
class AEJService
{
    public function generateAEJ($employeeId, $startDate, $endDate)
    {
        // Buscar todas as marcações do período
        // Aplicar cálculos e tratamentos
        // Gerar arquivo AEJ conforme especificação
        // Retornar conteúdo do arquivo
    }

    public function signAEJ($aejContent)
    {
        // Assinar com certificado ICP-Brasil
        // Gerar arquivo .p7s
    }
}
```

2. **Endpoint para download:**
```php
Route::get('/api/admin/aej/download', [AEJController::class, 'download']);
```

3. **Especificações técnicas:**
- Layout disponível em: https://www.gov.br/trabalho-e-emprego
- Documento: "Especificações do Arquivo Eletrônico de Jornada do programa de tratamento de registro de ponto"

**Prioridade:** 🔴 URGENTE - SEM ISSO O SISTEMA NÃO ESTÁ LEGAL

---

### 3. Comprovante de Registro de Ponto ❌

**Status:** NÃO IMPLEMENTADO
**Obrigatoriedade:** CRÍTICA
**Prazo:** Obrigatório desde 10/12/2021

**O que é:**
Documento que deve ser disponibilizado ao trabalhador imediatamente após cada marcação de ponto, comprovando o registro.

**Requisitos da Portaria (Art. 83, § 3º):**
- Formato: PDF com assinatura PAdES
- Disponível imediatamente após a marcação
- Acessível sem necessidade de solicitação prévia
- Disponível por no mínimo 48 horas
- Pode ser impresso ou eletrônico

**Conteúdo Obrigatório:**
- Identificação do empregador (CNPJ, razão social)
- Identificação do empregado (nome, CPF/PIS)
- Data e hora da marcação (precisão de segundos)
- Tipo de marcação (Entrada, Saída, etc.)
- Local da marcação (se disponível)
- Código de autenticação único
- Assinatura digital PAdES (PDF Advanced Electronic Signature)

**Implementação Necessária:**

1. **Criar gerador de comprovante:**
```php
// app/Services/ComprovanteService.php
class ComprovanteService
{
    public function generateComprovante($timeEntry, $action)
    {
        // Gerar PDF com dados da marcação
        // Aplicar assinatura PAdES
        // Retornar PDF assinado
    }
}
```

2. **Modificar controller de registro:**
```php
// app/Http/Controllers/Api/PwaClockController.php
public function registerClock(Request $request)
{
    // ... registro do ponto ...

    // Gerar comprovante
    $comprovante = $this->comprovanteService->generateComprovante($entry, $action);

    // Armazenar comprovante (disponível por 48h)
    Storage::put("comprovantes/{$uuid}.pdf", $comprovante);

    return response()->json([
        'success' => true,
        'comprovante_url' => route('comprovante.download', $uuid),
    ]);
}
```

3. **Interface no PWA:**
```javascript
// Após registrar ponto, mostrar link para download do comprovante
if (data.comprovante_url) {
    // Mostrar botão "Baixar Comprovante"
    // Ou abrir automaticamente em nova aba
}
```

**Layout do Comprovante:**
```
┌─────────────────────────────────────────┐
│  COMPROVANTE DE REGISTRO DE PONTO       │
│─────────────────────────────────────────│
│  Empresa: EMPRESA XYZ LTDA              │
│  CNPJ: 00.000.000/0001-00               │
│                                         │
│  Colaborador: JOÃO DA SILVA             │
│  CPF: 000.000.000-00                    │
│                                         │
│  Data: 22/10/2025                       │
│  Hora: 08:15:32                         │
│  Tipo: ENTRADA                          │
│                                         │
│  Local: Matriz - São Paulo/SP           │
│  GPS: -23.5505, -46.6333               │
│                                         │
│  Autenticador: A1B2C3D4E5F6             │
│─────────────────────────────────────────│
│  Este documento possui validade legal   │
│  conforme Portaria MTP nº 671/2021      │
│                                         │
│  Assinado digitalmente                  │
└─────────────────────────────────────────┘
```

**Prioridade:** 🔴 URGENTE - SEM ISSO O SISTEMA NÃO ESTÁ LEGAL

---

### 4. Certificado Digital ICP-Brasil ❌

**Status:** NÃO OBTIDO
**Obrigatoriedade:** CRÍTICA
**Prazo:** Obrigatório desde 10/02/2022

**O que é:**
Certificado digital emitido por Autoridade Certificadora credenciada pelo ICP-Brasil, necessário para assinar digitalmente os arquivos AFD, AEJ e comprovantes.

**Requisitos da Portaria:**
- Certificado digital ICP-Brasil tipo A1 ou A3
- Usado para assinatura CAdES (AFD e AEJ)
- Usado para assinatura PAdES (Comprovantes)
- Deve estar válido e dentro da cadeia de confiança ICP-Brasil

**Tipos de Certificado:**
- **A1:** Arquivo digital, validade de 1 ano, armazenado no servidor
- **A3:** Token/cartão inteligente, validade de 1 a 5 anos, mais seguro

**Como Obter:**

1. **Contratar certificado digital:**
   - Procurar Autoridade Certificadora credenciada (Serasa, Certisign, etc.)
   - Escolher tipo: e-CNPJ A1 ou A3
   - Custo aproximado: R$ 200 a R$ 600/ano

2. **Validação presencial:**
   - Comparecer ao posto de atendimento
   - Apresentar documentos da empresa
   - Validar identidade do responsável

3. **Instalação:**
   - A1: Instalar no servidor Laravel
   - A3: Conectar token/cartão ao servidor

**Implementação no Sistema:**

```php
// config/certificate.php
return [
    'type' => env('CERTIFICATE_TYPE', 'A1'), // A1 ou A3
    'path' => env('CERTIFICATE_PATH'),       // Path do certificado A1
    'password' => env('CERTIFICATE_PASSWORD'),
    'pin' => env('CERTIFICATE_PIN'),         // PIN do token A3
];
```

**Bibliotecas PHP para Assinatura Digital:**
- `signer/signer` - Assinatura de PDFs e arquivos
- `phpSecLib` - Criptografia e certificados
- `OpenSSL` - Funções nativas do PHP

**Prioridade:** 🔴 URGENTE - BLOQUEADOR PARA AFD/AEJ/COMPROVANTES

---

### 5. Sincronização com Horário Legal Brasileiro (HBL) ❌

**Status:** NÃO IMPLEMENTADO
**Obrigatoriedade:** ALTA
**Prazo:** Obrigatório para REP-P

**O que é:**
Sistema de sincronização automática com servidor de horário oficial do Observatório Nacional (NTP).

**Requisitos da Portaria:**
- Sincronização automática com servidor NTP brasileiro
- Exibição de horas, minutos E SEGUNDOS
- Registro de cada sincronização no AFD
- Tolerância máxima de desvio: não especificada

**Servidores NTP Oficiais do Brasil:**
- `a.st1.ntp.br`
- `b.st1.ntp.br`
- `c.st1.ntp.br`
- `d.st1.ntp.br`
- `ntp.br` (pool)

**Implementação Necessária:**

1. **Sincronização no backend:**
```php
// app/Services/NTPService.php
class NTPService
{
    private $ntpServers = [
        'a.st1.ntp.br',
        'b.st1.ntp.br',
        'c.st1.ntp.br',
    ];

    public function syncTime()
    {
        // Conectar ao servidor NTP
        // Obter horário oficial
        // Registrar sincronização
        // Retornar offset se houver diferença
    }

    public function getCurrentTime()
    {
        // Retornar horário sincronizado com NTP
        // Incluir milissegundos
    }
}
```

2. **Exibir segundos no PWA:**
```javascript
// resources/views/pwa/clock.blade.php
function updateDateTime() {
    const now = new Date();
    const time = now.toLocaleTimeString('pt-BR', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'  // ← ADICIONAR SEGUNDOS
    });
    // ...
}
```

3. **Task agendada para sincronização:**
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Sincronizar a cada 6 horas
    $schedule->call(function () {
        app(NTPService::class)->syncTime();
    })->everyFourHours();
}
```

4. **Armazenar logs de sincronização:**
```php
// Migration
Schema::create('ntp_sync_logs', function (Blueprint $table) {
    $table->id();
    $table->timestamp('sync_at');
    $table->string('ntp_server');
    $table->integer('offset_ms'); // Diferença em milissegundos
    $table->boolean('success');
    $table->text('error')->nullable();
});
```

**Prioridade:** 🟡 ALTA - REQUISITO TÉCNICO IMPORTANTE

---

### 6. Registro no INPI ❌

**Status:** NÃO REGISTRADO
**Obrigatoriedade:** ALTA
**Prazo:** Obrigatório para REP-P

**O que é:**
Registro do programa de computador no Instituto Nacional da Propriedade Industrial.

**Requisitos da Portaria:**
- Software deve ser registrado no INPI
- Número de registro deve ser informado ao MTP
- Registro é do programa, não da empresa

**Como Registrar:**

1. **Acessar sistema do INPI:**
   - https://www.gov.br/inpi/pt-br/servicos/programas-de-computador

2. **Documentação necessária:**
   - Requerimento de registro
   - Resumo do programa (hash SHA-256 do código-fonte)
   - Declaração de autoria
   - Comprovante de pagamento (GRU)

3. **Custo:**
   - Pessoa jurídica: ~R$ 415,00
   - Prazo de análise: ~7 dias úteis

4. **Após registro:**
   - Recebe certificado de registro
   - Número de registro deve constar no Atestado Técnico

**Implementação no Sistema:**
```php
// config/app.php
return [
    'inpi_registration' => env('INPI_REGISTRATION_NUMBER'),
    // Ex: BR512023000000-0
];
```

**Prioridade:** 🟡 ALTA - REQUISITO LEGAL DE PROPRIEDADE

---

### 7. Atestado Técnico e Termo de Responsabilidade ❌

**Status:** NÃO GERADO
**Obrigatoriedade:** ALTA
**Prazo:** Obrigatório desde 10/02/2022

**O que é:**
Documento fornecido pelo fabricante/desenvolvedor do sistema ao cliente, atestando conformidade com a Portaria 671.

**Requisitos da Portaria (Art. 89):**
- Modelo conforme especificação do gov.br
- Assinado pelo responsável técnico
- Fornecido à empresa usuária
- Deve ser mantido disponível para fiscalização

**Conteúdo Obrigatório:**
- Identificação do fabricante/desenvolvedor
- Identificação da empresa usuária
- Descrição do sistema
- Número do registro no INPI
- Declaração de conformidade com a Portaria 671
- Especificações técnicas do sistema
- Responsável técnico (CREA/CORECON/etc se aplicável)

**Implementação Necessária:**

1. **Criar gerador de atestado:**
```php
// app/Services/AtestadoTecnicoService.php
class AtestadoTecnicoService
{
    public function generateAtestado($tenant)
    {
        // Gerar PDF do atestado técnico
        // Incluir dados do desenvolvedor
        // Incluir dados do cliente
        // Incluir especificações técnicas
        // Retornar PDF
    }
}
```

2. **Template do atestado:**
```blade
<!-- resources/views/documents/atestado-tecnico.blade.php -->
<h1>ATESTADO TÉCNICO E TERMO DE RESPONSABILIDADE</h1>
<h2>Sistema de Registro Eletrônico de Ponto - REP-P</h2>

<h3>FABRICANTE/DESENVOLVEDOR</h3>
<p>Razão Social: [NOME DA EMPRESA DESENVOLVEDORA]</p>
<p>CNPJ: [CNPJ]</p>
<p>Endereço: [ENDEREÇO]</p>

<h3>EMPRESA USUÁRIA</h3>
<p>Razão Social: {{ $tenant->name }}</p>
<p>CNPJ: {{ $tenant->cnpj }}</p>

<h3>SISTEMA</h3>
<p>Nome: Next Ponto</p>
<p>Versão: 1.0</p>
<p>Registro INPI: [NÚMERO]</p>

<h3>DECLARAÇÃO DE CONFORMIDADE</h3>
<p>Declaramos que o sistema acima identificado está em conformidade
com a Portaria MTP nº 671, de 8 de novembro de 2021...</p>
```

3. **Download no painel admin:**
```php
Route::get('/admin/atestado-tecnico/download', [AtestadoController::class, 'download']);
```

**Modelo oficial disponível em:**
https://www.gov.br/trabalho-e-emprego (buscar "Atestado Técnico Portaria 671")

**Prioridade:** 🟡 ALTA - DOCUMENTO LEGAL OBRIGATÓRIO

---

## ⚠️ PONTOS DE NÃO CONFORMIDADE MENORES

### 8. Política de Privacidade e Adequação à LGPD ⚠️

**Status:** PARCIALMENTE CONFORME
**Obrigatoriedade:** ALTA (Art. 101)
**Lei:** Lei Geral de Proteção de Dados (Lei nº 13.709/2018)

**Situação Atual:**
- ✅ Sistema coleta dados com finalidade específica (controle de ponto)
- ✅ Dados armazenados de forma segura
- ⚠️ Falta política de privacidade explícita
- ⚠️ Falta termo de consentimento para dados biométricos
- ⚠️ Falta termo de consentimento para geolocalização
- ⚠️ Falta procedimento de exclusão de dados (direito ao esquecimento)
- ⚠️ Falta designação de DPO (Data Protection Officer)

**Dados Sensíveis Coletados:**
- Foto facial (dado biométrico - **SENSÍVEL**)
- Descritor facial (dado biométrico - **SENSÍVEL**)
- Localização GPS (dado pessoal)
- CPF (dado pessoal)
- IP de acesso (dado pessoal)

**Implementação Necessária:**

1. **Criar política de privacidade:**
```php
// resources/views/legal/privacy-policy.blade.php
// Documento completo explicando:
// - Quais dados são coletados
// - Finalidade de cada dado
// - Base legal (cumprimento de obrigação legal trabalhista)
// - Prazo de retenção
// - Direitos do titular
// - Como exercer direitos
```

2. **Termo de consentimento:**
```blade
<!-- resources/views/components/consent-modal.blade.php -->
<div class="modal">
    <h2>Consentimento para Coleta de Dados</h2>

    <h3>Reconhecimento Facial</h3>
    <p>Para registrar seu ponto, coletaremos e armazenaremos
    sua foto facial e descritor biométrico...</p>

    <h3>Geolocalização</h3>
    <p>Para validar o local de trabalho, coletaremos suas
    coordenadas GPS...</p>

    <label>
        <input type="checkbox" required>
        Li e concordo com a Política de Privacidade e autorizo
        a coleta dos dados acima para fins de controle de ponto.
    </label>

    <button>Aceitar e Continuar</button>
</div>
```

3. **Armazenar consentimento:**
```php
// Migration
Schema::create('employee_consents', function (Blueprint $table) {
    $table->id();
    $table->foreignId('employee_id')->constrained();
    $table->string('consent_type'); // 'facial', 'gps', 'data_processing'
    $table->boolean('granted');
    $table->text('consent_text'); // Texto aceito
    $table->timestamp('granted_at');
    $table->ipAddress('ip_address');
});
```

4. **Implementar direitos do titular:**

**Direito de Acesso:**
```php
// Permitir funcionário baixar todos seus dados
Route::get('/meus-dados/download', [EmployeeDataController::class, 'exportData']);
```

**Direito de Exclusão (após término do contrato):**
```php
// Anonimizar dados após prazo legal
// Manter apenas dados necessários por lei (5 anos após término)
public function anonymizeEmployee($employeeId)
{
    $employee = Employee::find($employeeId);

    // Manter registros de ponto (obrigação legal - 5 anos)
    // Excluir/anonimizar dados não essenciais
    $employee->update([
        'face_descriptor' => null,
        'face_photo' => null,
        // Manter: name, cpf, registros de ponto
    ]);

    // Deletar fotos faciais
    Storage::deleteDirectory("faces/{$employee->tenant_id}");
}
```

5. **Designar DPO (opcional mas recomendado):**
```php
// config/lgpd.php
return [
    'dpo' => [
        'name' => env('DPO_NAME'),
        'email' => env('DPO_EMAIL'),
        'phone' => env('DPO_PHONE'),
    ],
    'retention_period' => 5, // anos após término
];
```

**Bases Legais (LGPD Art. 7º):**
- Cumprimento de obrigação legal (Art. 74 da CLT - controle de ponto)
- Consentimento do titular (para dados biométricos e GPS)

**Documentos a Criar:**
- `PRIVACY_POLICY.md` - Política de privacidade completa
- `CONSENT_FORM.md` - Modelo de termo de consentimento
- `DATA_RETENTION_POLICY.md` - Política de retenção de dados
- `DPO_DESIGNATION.md` - Designação do encarregado de dados

**Prioridade:** 🟡 ALTA - PROTEÇÃO LEGAL E COMPLIANCE

---

### 9. Melhorias no Código Autenticador ⚠️

**Status:** PARCIALMENTE CONFORME
**Situação Atual:** Sistema gera código autenticador na folha espelho

**Código Atual:**
```php
// Gera código aleatório para autenticação
$authenticator = strtoupper(substr(md5(uniqid(rand(), true)), 0, 12));
```

**Possíveis Melhorias:**

1. **Usar hash criptográfico dos dados:**
```php
// Gerar hash baseado nos dados da folha espelho
$dataToHash =
    $employee->cpf .
    $tenant->cnpj .
    $period .
    $totalHours .
    $generatedAt;

$authenticator = strtoupper(substr(
    hash('sha256', $dataToHash . config('app.key')),
    0, 16
));
```

2. **Permitir validação externa:**
```php
// Endpoint público para validar autenticador
Route::post('/api/public/validate-authenticator', function(Request $request) {
    $authenticator = $request->input('authenticator');

    // Buscar no banco ou recalcular
    $isValid = /* verificar se existe */;

    return response()->json([
        'valid' => $isValid,
        'employee' => /* dados se válido */,
    ]);
});
```

3. **QR Code no comprovante:**
```php
// Adicionar QR code com link para validação
use SimpleSoftwareIO\QrCode\Facades\QrCode;

$qrCode = QrCode::size(100)->generate(
    route('validate.authenticator', ['code' => $authenticator])
);
```

**Prioridade:** 🟢 MÉDIA - MELHORIA DE SEGURANÇA

---

## 📊 RESUMO EXECUTIVO

### Scorecard de Conformidade

| Categoria | Status | Conformidade | Prioridade |
|-----------|--------|--------------|------------|
| **Folha Espelho** | ✅ Conforme | 100% | - |
| **Registro de Ajustes** | ✅ Conforme | 100% | - |
| **Armazenamento Básico** | ✅ Conforme | 100% | - |
| **Cálculos Trabalhistas** | ✅ Conforme | 100% | - |
| **Sistema de Jornadas** | ✅ Conforme | 100% | - |
| **AFD (Arquivo Fonte de Dados)** | ❌ Não Conforme | 0% | 🔴 URGENTE |
| **AEJ (Arquivo Eletrônico de Jornada)** | ❌ Não Conforme | 0% | 🔴 URGENTE |
| **Comprovante de Ponto** | ❌ Não Conforme | 0% | 🔴 URGENTE |
| **Certificado Digital ICP-Brasil** | ❌ Não Obtido | 0% | 🔴 URGENTE |
| **Sincronização NTP/HBL** | ❌ Não Implementado | 0% | 🟡 ALTA |
| **Registro INPI** | ❌ Não Registrado | 0% | 🟡 ALTA |
| **Atestado Técnico** | ❌ Não Gerado | 0% | 🟡 ALTA |
| **LGPD / Privacidade** | ⚠️ Parcial | 50% | 🟡 ALTA |
| **Código Autenticador** | ⚠️ Parcial | 70% | 🟢 MÉDIA |

**CONFORMIDADE GERAL: 40%**

---

## 🎯 PLANO DE IMPLEMENTAÇÃO

### FASE 1 - CRÍTICO (30 dias) 🔴

**Objetivo:** Tornar o sistema legalmente utilizável

1. **Obter Certificado Digital ICP-Brasil** (5 dias)
   - Contratar certificado e-CNPJ A1 ou A3
   - Realizar validação presencial
   - Instalar no servidor

2. **Implementar Comprovante de Registro de Ponto** (7 dias)
   - Criar gerador de PDF
   - Implementar assinatura PAdES
   - Integrar com PWA
   - Testar workflow completo

3. **Implementar Geração de AFD** (10 dias)
   - Estudar especificações técnicas do MTP
   - Criar serviço de geração de AFD
   - Implementar assinatura CAdES
   - Criar endpoints de download
   - Testes extensivos

4. **Implementar Geração de AEJ** (8 dias)
   - Estudar especificações técnicas do MTP
   - Criar serviço de geração de AEJ
   - Implementar assinatura CAdES
   - Integrar com folha espelho
   - Testes extensivos

**Entregáveis:**
- [ ] Certificado digital instalado e funcionando
- [ ] Comprovantes sendo gerados a cada marcação
- [ ] AFD disponível para download no admin
- [ ] AEJ disponível para download no admin
- [ ] Documentação de uso

---

### FASE 2 - IMPORTANTE (30 dias) 🟡

**Objetivo:** Conformidade técnica e legal completa

1. **Registrar Software no INPI** (15 dias)
   - Preparar documentação
   - Submeter pedido de registro
   - Aguardar aprovação
   - Atualizar sistema com número de registro

2. **Implementar Sincronização NTP** (7 dias)
   - Criar serviço de sincronização NTP
   - Implementar logging de sincronizações
   - Adicionar exibição de segundos no PWA
   - Criar task agendada
   - Monitoramento de desvios

3. **Gerar Atestado Técnico** (5 dias)
   - Criar template do atestado
   - Implementar geração de PDF
   - Disponibilizar no painel admin
   - Enviar aos clientes atuais

4. **Adequação à LGPD** (10 dias)
   - Redigir política de privacidade
   - Criar termos de consentimento
   - Implementar tela de aceite
   - Armazenar consentimentos
   - Implementar exportação de dados
   - Implementar anonimização pós-contrato

**Entregáveis:**
- [ ] Número de registro INPI
- [ ] Sincronização NTP ativa e monitorada
- [ ] Atestado técnico disponível
- [ ] Política de privacidade publicada
- [ ] Sistema de consentimento funcionando
- [ ] Compliance LGPD completo

---

### FASE 3 - MELHORIAS (15 dias) 🟢

**Objetivo:** Otimizações e melhorias de segurança

1. **Melhorar Código Autenticador** (3 dias)
   - Implementar hash criptográfico
   - Criar endpoint de validação pública
   - Adicionar QR code nos comprovantes

2. **Documentação Completa** (5 dias)
   - Manual do usuário
   - Manual do administrador
   - Guia de conformidade
   - FAQ

3. **Treinamento** (5 dias)
   - Material de treinamento para RH
   - Vídeos explicativos
   - Suporte pós-implantação

**Entregáveis:**
- [ ] Sistema de validação de autenticadores
- [ ] QR codes implementados
- [ ] Documentação completa
- [ ] Material de treinamento

---

## 📚 REFERÊNCIAS E LINKS ÚTEIS

### Legislação
- **Portaria MTP nº 671/2021:** https://www.gov.br/trabalho-e-emprego/pt-br/assuntos/legislacao/portarias-1/portarias-vigentes-3
- **CLT - Art. 74:** Obrigatoriedade do controle de ponto
- **Lei nº 13.709/2018:** Lei Geral de Proteção de Dados (LGPD)

### Especificações Técnicas
- **AFD - Especificações:** https://www.gov.br/trabalho-e-emprego (buscar "Especificações AFD")
- **AEJ - Especificações:** https://www.gov.br/trabalho-e-emprego (buscar "Especificações AEJ")
- **Atestado Técnico - Modelo:** https://www.gov.br/trabalho-e-emprego (buscar "Modelo Atestado Técnico")

### Certificação
- **ICP-Brasil:** https://www.gov.br/iti/pt-br/assuntos/icp-brasil
- **Lista de Autoridades Certificadoras:** https://www.gov.br/iti/pt-br/assuntos/repositorio/autoridades-certificadoras
- **INPI - Registro de Software:** https://www.gov.br/inpi/pt-br/servicos/programas-de-computador

### Horário Oficial
- **Observatório Nacional:** https://www.gov.br/observatorio/pt-br
- **Servidores NTP Brasil:** http://ntp.br/

### LGPD
- **ANPD - Autoridade Nacional:** https://www.gov.br/anpd/pt-br
- **Guia de Boas Práticas:** https://www.gov.br/anpd/pt-br/documentos-e-publicacoes/guias
- **Portal da LGPD:** https://www.lgpdbrasil.com.br/

### Bibliotecas e Ferramentas
- **PHP Signer (Assinatura Digital):** https://github.com/mpdf/mpdf
- **OpenSSL:** https://www.php.net/manual/pt_BR/book.openssl.php
- **Carbon (Datas):** https://carbon.nesbot.com/
- **DOMPDF:** https://github.com/dompdf/dompdf

---

## 💡 CONSIDERAÇÕES FINAIS

### Pontos Fortes do Sistema Atual
- ✅ Estrutura de banco de dados bem planejada
- ✅ Folha espelho completa e conforme
- ✅ Sistema de ajustes robusto e auditável
- ✅ Cálculos trabalhistas precisos
- ✅ Interface PWA moderna e funcional
- ✅ Geolocalização e reconhecimento facial implementados

### Principais Gaps
- ❌ Falta de arquivos obrigatórios (AFD, AEJ)
- ❌ Falta de certificação digital
- ❌ Falta de comprovantes de marcação
- ❌ Falta de sincronização oficial de horário

### Riscos de Não Conformidade
- ⚠️ **Multas trabalhistas:** R$ 4.000 a R$ 40.000 por estabelecimento
- ⚠️ **Invalidação de registros:** Pontos podem ser desconsiderados em processos
- ⚠️ **Processos trabalhistas:** Funcionários podem alegar falta de controle
- ⚠️ **Multas LGPD:** Até 2% do faturamento (máx. R$ 50 milhões)

### Próximos Passos Recomendados
1. 🔴 **URGENTE:** Iniciar FASE 1 imediatamente
2. 🟡 **IMPORTANTE:** Contratar certificado digital esta semana
3. 🟢 **PLANEJAMENTO:** Definir cronograma detalhado com equipe
4. 📋 **COMUNICAÇÃO:** Informar clientes sobre adequações em andamento

---

**Documento gerado em:** 22 de outubro de 2025
**Próxima revisão:** Após conclusão da FASE 1
**Responsável:** Equipe de Desenvolvimento Next Ponto

---

## 📞 CONTATOS ÚTEIS

### Suporte Técnico
- **Ministério do Trabalho:** https://www.gov.br/trabalho-e-emprego/pt-br/canais_atendimento/fale-conosco
- **Telefone:** 158 (Central de Atendimento)

### Certificação Digital
- **Serasa Experian:** (11) 2847-5600
- **Certisign:** (11) 3500-2100
- **Valid:** 0800 979 2000

### LGPD
- **ANPD:** anpd@anpd.gov.br
- **Ouvidoria:** https://www.gov.br/anpd/pt-br/canais_atendimento/ouvidoria

### INPI
- **Call Center:** (21) 3037-3000
- **Email:** faleconosco@inpi.gov.br

---

## ✅ CHECKLIST DE CONFORMIDADE

Use este checklist para acompanhar o progresso:

### Documentação
- [ ] Política de Privacidade criada
- [ ] Termo de Consentimento criado
- [ ] Atestado Técnico gerado
- [ ] Manual do usuário elaborado
- [ ] Documentação técnica atualizada

### Certificações
- [ ] Certificado digital ICP-Brasil obtido
- [ ] Certificado instalado no servidor
- [ ] Registro INPI solicitado
- [ ] Registro INPI aprovado
- [ ] Número de registro atualizado no sistema

### Funcionalidades
- [ ] AFD implementado
- [ ] AEJ implementado
- [ ] Comprovante de ponto implementado
- [ ] Assinatura digital CAdES implementada
- [ ] Assinatura digital PAdES implementada
- [ ] Sincronização NTP implementada
- [ ] Exibição de segundos no relógio
- [ ] Sistema de consentimento LGPD
- [ ] Exportação de dados do funcionário
- [ ] Anonimização pós-contrato

### Testes
- [ ] AFD testado e validado
- [ ] AEJ testado e validado
- [ ] Comprovantes testados
- [ ] Assinaturas digitais validadas
- [ ] Sincronização NTP validada
- [ ] Testes de carga realizados
- [ ] Testes de segurança realizados

### Deploy
- [ ] Backup do sistema atual
- [ ] Deploy em ambiente de staging
- [ ] Testes de homologação
- [ ] Deploy em produção
- [ ] Treinamento de usuários
- [ ] Monitoramento ativo

---

**FIM DO DOCUMENTO**
