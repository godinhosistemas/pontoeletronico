<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\TimeEntry;
use App\Services\ReceiptService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PwaClockController extends Controller
{
    /**
     * Valida código único do colaborador
     */
    public function validateCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'unique_code' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Código inválido',
                'errors' => $validator->errors()
            ], 422);
        }

        $code = $request->unique_code;

        \Log::info('Validando código PWA', [
            'code_received' => $code,
            'code_length' => strlen($code),
        ]);

        // Buscar funcionário pelo código (sem case sensitive para números)
        $employee = Employee::where('unique_code', $code)
            ->where('is_active', true)
            ->first();

        \Log::info('Resultado busca funcionário', [
            'found' => $employee ? true : false,
            'employee_id' => $employee ? $employee->id : null,
            'total_employees' => Employee::count(),
            'active_employees' => Employee::where('is_active', true)->count(),
        ]);

        if (!$employee) {
            // Tentar buscar qualquer funcionário com esse código para debug
            $anyEmployee = Employee::where('unique_code', $code)->first();
            \Log::warning('Código não encontrado ou funcionário inativo', [
                'code' => $code,
                'exists_but_inactive' => $anyEmployee ? true : false,
                'employee_status' => $anyEmployee ? $anyEmployee->status : null,
                'employee_is_active' => $anyEmployee ? $anyEmployee->is_active : null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Código não encontrado ou funcionário inativo'
            ], 404);
        }

        // Verifica se o tenant está ativo
        if (!$employee->tenant->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa inativa. Entre em contato com o suporte.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'position' => $employee->position,
                'registration_number' => $employee->registration_number,
                'department' => $employee->department,
            ]
        ]);
    }

    /**
     * Retorna registro de hoje do colaborador
     */
    public function getTodayEntry($employeeId)
    {
        $employee = Employee::findOrFail($employeeId);

        $entry = TimeEntry::where('employee_id', $employee->id)
            ->where('date', today())
            ->first();

        return response()->json([
            'success' => true,
            'entry' => $entry ? [
                'clock_in' => $entry->formatted_clock_in,
                'clock_out' => $entry->formatted_clock_out,
                'lunch_start' => $entry->formatted_lunch_start,
                'lunch_end' => $entry->formatted_lunch_end,
                'total_hours' => $entry->total_hours,
            ] : null
        ]);
    }

    /**
     * Salva descritor facial do funcionário (cadastro inicial)
     */
    public function saveFaceDescriptor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'descriptor' => 'required|array',
            'descriptor.*' => 'numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $employee = Employee::findOrFail($request->employee_id);

        // Salva o descritor facial como JSON
        $employee->face_descriptor = json_encode($request->descriptor);
        $employee->save();

        \Log::info('Descritor facial cadastrado', [
            'employee_id' => $employee->id,
            'descriptor_length' => count($request->descriptor)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Descritor facial cadastrado com sucesso!'
        ]);
    }

    /**
     * Valida reconhecimento facial
     */
    public function validateFaceRecognition(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'descriptor' => 'required|array',
            'descriptor.*' => 'numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $employee = Employee::findOrFail($request->employee_id);

        // Verifica se tem descritor cadastrado
        if (!$employee->face_descriptor) {
            return response()->json([
                'success' => false,
                'message' => 'Funcionário não possui reconhecimento facial cadastrado. Por favor, cadastre primeiro.',
                'needs_registration' => true
            ], 422);
        }

        // Decodifica o descritor cadastrado
        $storedDescriptor = json_decode($employee->face_descriptor, true);
        $currentDescriptor = $request->descriptor;

        // Calcula distância euclidiana entre os descritores
        $distance = $this->calculateEuclideanDistance($storedDescriptor, $currentDescriptor);

        // Threshold: 0.6 (quanto menor, mais restritivo)
        $threshold = 0.6;
        $match = $distance < $threshold;

        // Calcula similaridade em porcentagem
        $similarity = max(0, (1 - $distance) * 100);

        \Log::info('Validação facial', [
            'employee_id' => $employee->id,
            'distance' => $distance,
            'similarity' => $similarity,
            'match' => $match
        ]);

        return response()->json([
            'success' => $match,
            'match' => $match,
            'similarity' => round($similarity, 2),
            'distance' => round($distance, 4),
            'message' => $match
                ? 'Reconhecimento facial validado com sucesso!'
                : 'Rosto não reconhecido. Tente novamente ou contate o RH.'
        ]);
    }

    /**
     * Calcula distância euclidiana entre dois vetores
     */
    private function calculateEuclideanDistance($vector1, $vector2)
    {
        if (count($vector1) !== count($vector2)) {
            throw new \Exception('Vetores de tamanhos diferentes');
        }

        $sum = 0;
        for ($i = 0; $i < count($vector1); $i++) {
            $diff = $vector1[$i] - $vector2[$i];
            $sum += $diff * $diff;
        }

        return sqrt($sum);
    }

    /**
     * Calcula distância entre duas coordenadas GPS usando a fórmula de Haversine
     * Retorna a distância em metros
     */
    private function calculateGpsDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // Raio da Terra em metros

        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $latDiff = deg2rad($lat2 - $lat1);
        $lonDiff = deg2rad($lon2 - $lon1);

        $a = sin($latDiff / 2) * sin($latDiff / 2) +
             cos($lat1Rad) * cos($lat2Rad) *
             sin($lonDiff / 2) * sin($lonDiff / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c; // Distância em metros
    }

    /**
     * Valida se a localização GPS está dentro do raio permitido
     */
    public function validateGeolocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $employee = Employee::findOrFail($request->employee_id);

        // Se não exige geolocalização, aprova automaticamente
        if (!$employee->require_geolocation) {
            return response()->json([
                'success' => true,
                'validated' => true,
                'message' => 'Geolocalização não obrigatória',
                'distance' => null
            ]);
        }

        // Se exige mas não tem coordenadas configuradas
        if (!$employee->allowed_latitude || !$employee->allowed_longitude) {
            return response()->json([
                'success' => false,
                'validated' => false,
                'message' => 'Local permitido não configurado. Entre em contato com o RH.',
                'distance' => null
            ], 422);
        }

        // Calcula distância
        $distance = $this->calculateGpsDistance(
            $employee->allowed_latitude,
            $employee->allowed_longitude,
            $request->latitude,
            $request->longitude
        );

        $radius = $employee->geofence_radius ?? 100; // Padrão 100 metros
        $validated = $distance <= $radius;

        \Log::info('Validação de geolocalização', [
            'employee_id' => $employee->id,
            'distance' => $distance,
            'radius' => $radius,
            'validated' => $validated
        ]);

        return response()->json([
            'success' => $validated,
            'validated' => $validated,
            'distance' => round($distance, 2),
            'radius' => $radius,
            'message' => $validated
                ? "Localização validada! Você está a " . round($distance, 0) . "m do local permitido."
                : "⚠️ Você está fora do local permitido! Distância: " . round($distance, 0) . "m (máximo: {$radius}m)"
        ]);
    }

    /**
     * Registra ponto com foto facial
     */
    public function registerClock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'action' => 'required|in:clock_in,clock_out,lunch_start,lunch_end',
            'photo' => 'required|image|max:5120', // 5MB
            'descriptor' => 'array', // Opcional: descritor para validação adicional
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'gps_accuracy' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $employee = Employee::findOrFail($request->employee_id);

        // Valida geolocalização se exigida
        $gpsValidated = false;
        $distance = null;

        if ($employee->require_geolocation) {
            if (!$request->latitude || !$request->longitude) {
                return response()->json([
                    'success' => false,
                    'message' => 'Geolocalização obrigatória. Por favor, ative o GPS.'
                ], 422);
            }

            if (!$employee->allowed_latitude || !$employee->allowed_longitude) {
                return response()->json([
                    'success' => false,
                    'message' => 'Local permitido não configurado. Entre em contato com o RH.'
                ], 422);
            }

            $distance = $this->calculateGpsDistance(
                $employee->allowed_latitude,
                $employee->allowed_longitude,
                $request->latitude,
                $request->longitude
            );

            $radius = $employee->geofence_radius ?? 100;
            $gpsValidated = $distance <= $radius;

            if (!$gpsValidated) {
                return response()->json([
                    'success' => false,
                    'message' => "⚠️ Você está fora do local permitido! Distância: " . round($distance, 0) . "m (máximo: {$radius}m)",
                    'distance' => round($distance, 2)
                ], 422);
            }
        }

        // Busca ou cria registro de hoje
        $entry = TimeEntry::firstOrCreate([
            'employee_id' => $employee->id,
            'tenant_id' => $employee->tenant_id,
            'date' => today(),
        ], [
            'type' => 'normal',
            'status' => 'pending',
        ]);

        $action = $request->action;
        $currentTime = now()->format('H:i:s');

        // Validações específicas por ação
        switch ($action) {
            case 'clock_in':
                if ($entry->clock_in) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Entrada já registrada hoje'
                    ], 422);
                }
                $entry->clock_in = $currentTime;
                $message = 'Entrada registrada com sucesso às ' . now()->format('H:i');
                break;

            case 'lunch_start':
                if (!$entry->clock_in) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Você precisa registrar entrada primeiro'
                    ], 422);
                }
                if ($entry->lunch_start) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Início do almoço já registrado'
                    ], 422);
                }
                $entry->lunch_start = $currentTime;
                $message = 'Início do almoço registrado às ' . now()->format('H:i');
                break;

            case 'lunch_end':
                if (!$entry->lunch_start) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Você precisa iniciar o almoço primeiro'
                    ], 422);
                }
                if ($entry->lunch_end) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Fim do almoço já registrado'
                    ], 422);
                }
                $entry->lunch_end = $currentTime;
                $message = 'Fim do almoço registrado às ' . now()->format('H:i');
                break;

            case 'clock_out':
                if (!$entry->clock_in) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Você precisa registrar entrada primeiro'
                    ], 422);
                }
                if ($entry->clock_out) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Saída já registrada hoje'
                    ], 422);
                }
                $entry->clock_out = $currentTime;
                $entry->calculateTotalHours();
                $message = 'Saída registrada com sucesso às ' . now()->format('H:i');
                break;
        }

        // Salva foto facial
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            $filename = 'face_' . $employee->id . '_' . time() . '_' . $action . '.' . $photo->getClientOriginalExtension();
            $path = $photo->storeAs('faces/' . $employee->tenant_id, $filename, 'public');

            // Armazena o path da última foto
            $employee->face_photo = $path;
            $employee->save();
        }

        // Registra IP
        $entry->ip_address = $request->ip();

        // Armazena dados GPS se fornecidos
        if ($request->latitude && $request->longitude) {
            $entry->gps_latitude = $request->latitude;
            $entry->gps_longitude = $request->longitude;
            $entry->gps_accuracy = $request->gps_accuracy;
            $entry->distance_meters = $distance ? round($distance) : null;
            $entry->gps_validated = $gpsValidated || !$employee->require_geolocation;
        }

        // Salva entrada
        $entry->save();

        // Gera comprovante de registro de ponto
        $receiptService = app(ReceiptService::class);
        $receipt = $receiptService->generateReceipt($entry, $employee, $action, [
            'ip_address' => $request->ip(),
            'gps_latitude' => $request->latitude,
            'gps_longitude' => $request->longitude,
            'gps_accuracy' => $request->gps_accuracy,
            'photo_path' => $path ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => $message,
            'entry' => [
                'clock_in' => $entry->formatted_clock_in,
                'clock_out' => $entry->formatted_clock_out,
                'lunch_start' => $entry->formatted_lunch_start,
                'lunch_end' => $entry->formatted_lunch_end,
                'total_hours' => $entry->total_hours,
            ],
            'receipt' => [
                'uuid' => $receipt->uuid,
                'authenticator_code' => $receipt->authenticator_code,
                'download_url' => $receipt->download_url,
                'view_url' => $receipt->view_url,
                'available_until' => $receipt->available_until->format('d/m/Y H:i'),
            ]
        ]);
    }

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

        \Log::info('Geofence configurado', [
            'employee_id' => $employee->id,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'radius' => $request->radius ?? 100
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

    /**
     * Sincroniza registros offline (para background sync)
     */
    public function syncClockEntries(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'entries' => 'required|array',
            'entries.*.employee_id' => 'required|exists:employees,id',
            'entries.*.action' => 'required|in:clock_in,clock_out,lunch_start,lunch_end',
            'entries.*.timestamp' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $synced = [];
        $errors = [];

        foreach ($request->entries as $entryData) {
            try {
                $employee = Employee::findOrFail($entryData['employee_id']);

                $entry = TimeEntry::firstOrCreate([
                    'employee_id' => $employee->id,
                    'tenant_id' => $employee->tenant_id,
                    'date' => \Carbon\Carbon::parse($entryData['timestamp'])->toDateString(),
                ], [
                    'type' => 'normal',
                    'status' => 'pending',
                ]);

                $action = $entryData['action'];
                $time = \Carbon\Carbon::parse($entryData['timestamp'])->format('H:i:s');

                $entry->{$action} = $time;

                if ($action === 'clock_out') {
                    $entry->calculateTotalHours();
                }

                $entry->save();

                $synced[] = $entryData;
            } catch (\Exception $e) {
                $errors[] = [
                    'entry' => $entryData,
                    'error' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'success' => true,
            'synced' => count($synced),
            'errors' => $errors
        ]);
    }
}
