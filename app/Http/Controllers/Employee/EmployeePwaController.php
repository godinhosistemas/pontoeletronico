<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\TimeEntryReceipt;
use App\Services\ReceiptService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class EmployeePwaController extends Controller
{
    protected $receiptService;

    public function __construct(ReceiptService $receiptService)
    {
        $this->receiptService = $receiptService;
    }

    /**
     * Tela de login do PWA do funcionário
     */
    public function login()
    {
        return view('employee.pwa.login');
    }

    /**
     * Valida código de acesso e retorna dados do funcionário
     */
    public function authenticate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'unique_code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Código inválido'
            ], 422);
        }

        $employee = Employee::where('unique_code', $request->unique_code)
            ->where('is_active', true)
            ->first();

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Código não encontrado ou funcionário inativo'
            ], 404);
        }

        // Retorna token de sessão (simplificado)
        $sessionToken = encrypt([
            'employee_id' => $employee->id,
            'expires_at' => now()->addHours(24)->timestamp,
        ]);

        return response()->json([
            'success' => true,
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'position' => $employee->position,
                'registration_number' => $employee->registration_number,
                'department' => $employee->department,
            ],
            'session_token' => $sessionToken,
        ]);
    }

    /**
     * Dashboard do funcionário
     */
    public function dashboard(Request $request)
    {
        $sessionToken = $request->query('token');

        if (!$sessionToken) {
            return redirect()->route('employee.pwa.login');
        }

        try {
            $sessionData = decrypt($sessionToken);

            if ($sessionData['expires_at'] < now()->timestamp) {
                return redirect()->route('employee.pwa.login')
                    ->with('error', 'Sessão expirada. Faça login novamente.');
            }

            $employee = Employee::find($sessionData['employee_id']);

            if (!$employee) {
                return redirect()->route('employee.pwa.login');
            }

            return view('employee.pwa.dashboard', [
                'employee' => $employee,
                'sessionToken' => $sessionToken,
            ]);
        } catch (\Exception $e) {
            return redirect()->route('employee.pwa.login')
                ->with('error', 'Sessão inválida');
        }
    }

    /**
     * API: Lista comprovantes do mês atual
     */
    public function getReceipts(Request $request)
    {
        $sessionToken = $request->header('X-Session-Token') ?? $request->input('token');

        if (!$sessionToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token de sessão não fornecido'
            ], 401);
        }

        try {
            $sessionData = decrypt($sessionToken);

            if ($sessionData['expires_at'] < now()->timestamp) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sessão expirada'
                ], 401);
            }

            $receipts = $this->receiptService->getEmployeeCurrentMonthReceipts($sessionData['employee_id']);

            return response()->json([
                'success' => true,
                'receipts' => $receipts->map(function ($receipt) {
                    return [
                        'uuid' => $receipt->uuid,
                        'action' => $receipt->action,
                        'action_name' => $receipt->action_name,
                        'action_color' => $receipt->action_color,
                        'marked_at' => $receipt->marked_at->format('d/m/Y H:i:s'),
                        'marked_at_short' => $receipt->marked_at->format('H:i'),
                        'marked_date' => $receipt->marked_at->format('d/m/Y'),
                        'authenticator_code' => $receipt->authenticator_code,
                        'is_available' => $receipt->isAvailable(),
                        'available_until' => $receipt->available_until->format('d/m/Y H:i'),
                        'has_gps' => $receipt->gps_latitude && $receipt->gps_longitude,
                        'formatted_location' => $receipt->formatted_location,
                        'download_url' => $receipt->download_url,
                        'view_url' => $receipt->view_url,
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar comprovantes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Visualizar comprovante específico
     */
    public function viewReceipt($uuid)
    {
        $receipt = TimeEntryReceipt::where('uuid', $uuid)
            ->with(['employee', 'tenant', 'timeEntry'])
            ->first();

        if (!$receipt) {
            abort(404, 'Comprovante não encontrado');
        }

        if (!$receipt->isAvailable()) {
            return view('employee.pwa.receipt-expired', compact('receipt'));
        }

        return view('employee.pwa.receipt-view', compact('receipt'));
    }

    /**
     * Download do PDF do comprovante
     */
    public function downloadReceipt($uuid)
    {
        $receipt = TimeEntryReceipt::where('uuid', $uuid)->first();

        if (!$receipt) {
            abort(404, 'Comprovante não encontrado');
        }

        if (!$receipt->isAvailable()) {
            abort(410, 'Comprovante expirado (disponível apenas por 48 horas)');
        }

        if (!$receipt->pdf_path || !Storage::exists($receipt->pdf_path)) {
            // Regenera o PDF se não existir
            $this->receiptService->generatePDF($receipt);
        }

        $fileName = sprintf(
            'comprovante_%s_%s.pdf',
            $receipt->marked_at->format('Y-m-d_H-i-s'),
            $receipt->action
        );

        return Storage::download($receipt->pdf_path, $fileName);
    }

    /**
     * Validar autenticador (público)
     */
    public function validateAuthenticator(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'authenticator_code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Código autenticador inválido'
            ], 422);
        }

        $receipt = $this->receiptService->getByAuthenticator($request->authenticator_code);

        if (!$receipt) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => 'Código autenticador não encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'valid' => true,
            'receipt' => [
                'employee_name' => $receipt->employee->name,
                'tenant_name' => $receipt->tenant->name,
                'action' => $receipt->action_name,
                'marked_at' => $receipt->marked_at->format('d/m/Y H:i:s'),
                'is_available' => $receipt->isAvailable(),
            ]
        ]);
    }
}
