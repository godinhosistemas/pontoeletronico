# 📍 Como Cadastrar o Perímetro de Geolocalização

Este documento explica **3 formas diferentes** de cadastrar as coordenadas GPS e o perímetro permitido para os funcionários registrarem ponto.

---

## 🎯 O que precisa ser configurado?

Para cada funcionário que deve ter validação de geolocalização, você precisa configurar:

1. **Latitude e Longitude** do local de trabalho (empresa/escritório)
2. **Raio permitido** em metros (padrão: 100m)
3. **Se é obrigatório** validar a geolocalização

---

## ✅ Opção 1: Via Banco de Dados (Mais Rápida)

### Passo 1: Obter as coordenadas do local de trabalho

**Método A - Google Maps:**
1. Acesse https://maps.google.com
2. Procure pelo endereço da empresa
3. Clique com botão direito no local exato
4. Clique em "Copiar coordenadas" ou copie o primeiro número que aparece

Exemplo: `-23.550520, -46.633308`

**Método B - GPS do Celular:**
1. Vá até o local de trabalho
2. Abra o console do navegador (F12)
3. Cole este código:
```javascript
navigator.geolocation.getCurrentPosition(pos => {
  console.log('Latitude:', pos.coords.latitude);
  console.log('Longitude:', pos.coords.longitude);
  console.log('SQL:', `UPDATE employees SET allowed_latitude = ${pos.coords.latitude}, allowed_longitude = ${pos.coords.longitude}, geofence_radius = 100, require_geolocation = 1 WHERE id = 1;`);
});
```

### Passo 2: Executar SQL no banco

Abra seu cliente SQLite (DB Browser, DBeaver, etc.) e execute:

```sql
-- Para UM funcionário específico
UPDATE employees
SET
  allowed_latitude = -23.550520,    -- ⬅️ COLE A LATITUDE AQUI
  allowed_longitude = -46.633308,   -- ⬅️ COLE A LONGITUDE AQUI
  geofence_radius = 100,            -- Raio em metros (100m = 1 quarteirão)
  require_geolocation = 1           -- 1 = obrigatório, 0 = opcional
WHERE id = 1;                       -- ⬅️ ID DO FUNCIONÁRIO
```

```sql
-- Para TODOS os funcionários ao mesmo tempo
UPDATE employees
SET
  allowed_latitude = -23.550520,
  allowed_longitude = -46.633308,
  geofence_radius = 100,
  require_geolocation = 1
WHERE tenant_id = 1;                -- ⬅️ ID DO TENANT (empresa)
```

**Pronto!** Configuração completa.

---

## ✅ Opção 2: Via Artisan Command (Linha de Comando)

Vou criar um comando Artisan para facilitar o cadastro via terminal.

### Executar comando:

```bash
php artisan employee:set-geofence 1 -23.550520 -46.633308 --radius=100 --required
```

Parâmetros:
- `1` = ID do funcionário
- `-23.550520` = Latitude
- `-46.633308` = Longitude
- `--radius=100` = Raio em metros (opcional, padrão 100)
- `--required` = Torna obrigatório (opcional)

### Comando para todos os funcionários de um tenant:

```bash
php artisan employee:set-geofence --tenant=1 -23.550520 -46.633308 --radius=100 --required
```

**⚠️ Nota:** Este comando ainda precisa ser criado. Veja a seção "Criando o Comando" abaixo.

---

## ✅ Opção 3: Via API REST (Programático)

Use esta opção se quiser integrar com outro sistema ou criar uma interface própria.

### Endpoint:
```
POST /api/admin/employees/{id}/set-geofence
```

### Headers:
```
Content-Type: application/json
X-CSRF-TOKEN: {token}
Authorization: Bearer {token}  (se usar autenticação API)
```

### Body (JSON):
```json
{
  "latitude": -23.550520,
  "longitude": -46.633308,
  "radius": 100,
  "require_geolocation": true
}
```

### Exemplo com cURL:
```bash
curl -X POST http://localhost:8000/api/admin/employees/1/set-geofence \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: {seu-token}" \
  -d '{
    "latitude": -23.550520,
    "longitude": -46.633308,
    "radius": 100,
    "require_geolocation": true
  }'
```

### Exemplo com JavaScript (Fetch):
```javascript
fetch('/api/admin/employees/1/set-geofence', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
  },
  body: JSON.stringify({
    latitude: -23.550520,
    longitude: -46.633308,
    radius: 100,
    require_geolocation: true
  })
})
.then(r => r.json())
.then(data => console.log(data));
```

**⚠️ Nota:** Este endpoint ainda precisa ser criado. Veja a seção "Criando a API" abaixo.

---

## 📊 Verificar Configuração Atual

### Via SQL:
```sql
SELECT
  id,
  name,
  allowed_latitude,
  allowed_longitude,
  geofence_radius,
  require_geolocation
FROM employees
WHERE id = 1;
```

### Via Artisan (futuro):
```bash
php artisan employee:show-geofence 1
```

---

## 🗺️ Interface Visual (Futuro)

Estou preparando uma interface administrativa onde você poderá:

1. Ver um **mapa interativo**
2. **Clicar no mapa** para selecionar o local
3. **Ajustar o raio** visualmente com um círculo
4. **Ativar/desativar** a obrigatoriedade
5. Salvar tudo com um clique

**Status:** 🚧 Em desenvolvimento

---

## 💡 Dicas Importantes

### Raio Recomendado:
- **50m** = Apenas dentro do prédio
- **100m** = Prédio + estacionamento (RECOMENDADO)
- **200m** = Quarteirão inteiro
- **500m** = Área ampla (comércio próximo)

### GPS pode variar:
- Em ambientes fechados: ±10-50m
- Em ambientes abertos: ±5-15m
- **Nunca use raio menor que 50m** para evitar falsos negativos

### Require Geolocation:
- `true` (1) = **Obrigatório** - Funcionário não pode registrar ponto se estiver fora
- `false` (0) = **Opcional** - Sistema registra GPS mas não bloqueia

---

## 🔧 Criar o Comando Artisan (Opção 2)

Se quiser usar a Opção 2, execute este código para criar o comando:

### 1. Criar o arquivo do comando:
```bash
php artisan make:command SetEmployeeGeofence
```

### 2. Editar `app/Console/Commands/SetEmployeeGeofence.php`:

```php
<?php

namespace App\Console\Commands;

use App\Models\Employee;
use Illuminate\Console\Command;

class SetEmployeeGeofence extends Command
{
    protected $signature = 'employee:set-geofence
                            {employee? : ID do funcionário}
                            {latitude : Latitude do local}
                            {longitude : Longitude do local}
                            {--radius=100 : Raio permitido em metros}
                            {--required : Torna a geolocalização obrigatória}
                            {--tenant= : ID do tenant (para aplicar em todos)}';

    protected $description = 'Define o perímetro de geolocalização para funcionários';

    public function handle()
    {
        $latitude = $this->argument('latitude');
        $longitude = $this->argument('longitude');
        $radius = $this->option('radius');
        $required = $this->option('required');
        $tenantId = $this->option('tenant');

        if ($tenantId) {
            // Aplica para todos do tenant
            $count = Employee::where('tenant_id', $tenantId)->update([
                'allowed_latitude' => $latitude,
                'allowed_longitude' => $longitude,
                'geofence_radius' => $radius,
                'require_geolocation' => $required
            ]);

            $this->info("✅ Geofence configurado para {$count} funcionários do tenant {$tenantId}");
        } else {
            // Aplica para um funcionário
            $employeeId = $this->argument('employee');
            $employee = Employee::findOrFail($employeeId);

            $employee->update([
                'allowed_latitude' => $latitude,
                'allowed_longitude' => $longitude,
                'geofence_radius' => $radius,
                'require_geolocation' => $required
            ]);

            $this->info("✅ Geofence configurado para {$employee->name}");
        }

        $this->table(
            ['Campo', 'Valor'],
            [
                ['Latitude', $latitude],
                ['Longitude', $longitude],
                ['Raio', $radius . 'm'],
                ['Obrigatório', $required ? 'Sim' : 'Não']
            ]
        );

        return Command::SUCCESS;
    }
}
```

---

## 🌐 Criar a API (Opção 3)

### 1. Adicionar ao `PwaClockController.php`:

```php
/**
 * Define o perímetro de geolocalização para um funcionário
 */
public function setGeofence(Request $request, $employeeId)
{
    $validator = Validator::make($request->all(), [
        'latitude' => 'required|numeric|between:-90,90',
        'longitude' => 'required|numeric|between:-180,180',
        'radius' => 'nullable|integer|min:10|max:5000',
        'require_geolocation' => 'nullable|boolean',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Dados inválidos',
            'errors' => $validator->errors()
        ], 422);
    }

    $employee = Employee::findOrFail($employeeId);

    $employee->update([
        'allowed_latitude' => $request->latitude,
        'allowed_longitude' => $request->longitude,
        'geofence_radius' => $request->radius ?? 100,
        'require_geolocation' => $request->require_geolocation ?? false,
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Geofence configurado com sucesso!',
        'employee' => [
            'id' => $employee->id,
            'name' => $employee->name,
            'allowed_latitude' => $employee->allowed_latitude,
            'allowed_longitude' => $employee->allowed_longitude,
            'geofence_radius' => $employee->geofence_radius,
            'require_geolocation' => $employee->require_geolocation,
        ]
    ]);
}
```

### 2. Adicionar rota em `routes/web.php`:

```php
Route::post('/admin/employees/{id}/set-geofence', [App\Http\Controllers\Api\PwaClockController::class, 'setGeofence'])
    ->middleware(['auth', 'can:employees.edit'])
    ->name('admin.employees.set-geofence');
```

---

## ⚡ Resumo Rápido

**Para testar rapidamente:**

1. Pegue as coordenadas no Google Maps
2. Execute este SQL (ajuste os valores):
```sql
UPDATE employees
SET allowed_latitude = -23.550520,
    allowed_longitude = -46.633308,
    geofence_radius = 100,
    require_geolocation = 1
WHERE id = 1;
```
3. Acesse o PWA e teste!

**Pronto!** O funcionário agora só poderá registrar ponto se estiver dentro do raio de 100m das coordenadas configuradas.

---

## 📞 Obtendo Coordenadas Rapidamente

### Método Mais Rápido (Google Maps):
1. Google Maps → https://maps.google.com
2. Procure o endereço
3. Botão direito no local → Copiar coordenadas
4. Cole no SQL

**Exemplo de coordenadas:**
- Av. Paulista, São Paulo: `-23.561414, -46.656139`
- Copacabana, Rio de Janeiro: `-22.971177, -43.182543`
- Centro, Brasília: `-15.799999, -47.864163`

---

**Dúvidas?** Consulte `GEOLOCALIZACAO_IMPLEMENTADA.md` para mais detalhes técnicos.
