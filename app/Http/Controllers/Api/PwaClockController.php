<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\TimeEntry;
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
                'clock_in' => $entry->clock_in,
                'clock_out' => $entry->clock_out,
                'lunch_start' => $entry->lunch_start,
                'lunch_end' => $entry->lunch_end,
                'total_hours' => $entry->total_hours,
            ] : null
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
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $employee = Employee::findOrFail($request->employee_id);

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

        // Salva entrada
        $entry->save();

        return response()->json([
            'success' => true,
            'message' => $message,
            'entry' => [
                'clock_in' => $entry->clock_in,
                'clock_out' => $entry->clock_out,
                'lunch_start' => $entry->lunch_start,
                'lunch_end' => $entry->lunch_end,
                'total_hours' => $entry->total_hours,
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
