<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// Rotas de autenticação
Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');
});

// Rota inicial - redireciona para dashboard ou login
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('admin.dashboard');
    }
    return redirect()->route('login');
});

// Rotas administrativas protegidas
Route::middleware(['auth', 'tenant.active'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    // Rotas apenas para Super Admin
    Route::middleware(['role:super-admin'])->group(function () {
        // Tenants
        Route::get('/tenants', function () {
            return view('admin.tenants.index');
        })->name('tenants.index');

        // Plans
        Route::get('/plans', function () {
            return view('admin.plans.index');
        })->name('plans.index');

        // Subscriptions
        Route::get('/subscriptions', function () {
            return view('admin.subscriptions.index');
        })->name('subscriptions.index');
    });

    // Employees - disponível para admins tenant também
    Route::get('/employees', function () {
        return view('admin.employees.index');
    })->name('employees.index')->middleware(['can:employees.view', 'log.auth']);

    // Work Schedules - Jornadas de Trabalho
    Route::get('/work-schedules', function () {
        return view('admin.work-schedules.index');
    })->name('work-schedules.index');

    // Time Entries - Aprovação de pontos (apenas gestores)
    Route::get('/timesheet/approvals', function () {
        return view('admin.timesheet.approvals');
    })->name('timesheet.approvals')->middleware('can:timesheet.approve');

    // Time Entries - Relatórios (gestores e admins)
    Route::get('/timesheet/reports', function () {
        return view('admin.timesheet.reports');
    })->name('timesheet.reports')->middleware('can:reports.view');

    // Exportações de Relatórios
    Route::get('/timesheet/reports/export/pdf', [App\Http\Controllers\Admin\TimesheetReportController::class, 'exportPdf'])
        ->name('reports.export.pdf')->middleware('can:reports.export');
    Route::get('/timesheet/reports/export/excel', [App\Http\Controllers\Admin\TimesheetReportController::class, 'exportExcel'])
        ->name('reports.export.excel')->middleware('can:reports.export');
    Route::get('/timesheet/reports/export/mirror', [App\Http\Controllers\Admin\TimesheetReportController::class, 'exportTimesheetMirror'])
        ->name('reports.export.mirror')->middleware('can:reports.export');
});

// Rotas para funcionários (registro de ponto)
Route::middleware(['auth', 'tenant.active'])->prefix('employee')->name('employee.')->group(function () {
    // Registro de Ponto
    Route::get('/clock-in', function () {
        return view('employee.clock-in');
    })->name('clock-in');
});

// Rota PWA (sem autenticação - usa código único)
Route::get('/pwa/clock', function () {
    return view('pwa.clock');
})->name('pwa.clock');

// API Routes para PWA
Route::prefix('api/pwa')->name('api.pwa.')->group(function () {
    Route::post('/validate-code', [App\Http\Controllers\Api\PwaClockController::class, 'validateCode'])->name('validate-code');
    Route::get('/today-entry/{employeeId}', [App\Http\Controllers\Api\PwaClockController::class, 'getTodayEntry'])->name('today-entry');
    Route::post('/register-clock', [App\Http\Controllers\Api\PwaClockController::class, 'registerClock'])->name('register-clock');
    Route::post('/sync', [App\Http\Controllers\Api\PwaClockController::class, 'syncClockEntries'])->name('sync');

    // Rotas de reconhecimento facial
    Route::post('/save-face-descriptor', [App\Http\Controllers\Api\PwaClockController::class, 'saveFaceDescriptor'])->name('save-face-descriptor');
    Route::post('/validate-face', [App\Http\Controllers\Api\PwaClockController::class, 'validateFaceRecognition'])->name('validate-face');

    // Rota de validação de geolocalização
    Route::post('/validate-geolocation', [App\Http\Controllers\Api\PwaClockController::class, 'validateGeolocation'])->name('validate-geolocation');
});

// Rota administrativa para configurar geofence (protegida com autenticação)
Route::post('/api/admin/employees/{id}/set-geofence', [App\Http\Controllers\Api\PwaClockController::class, 'setGeofence'])
    ->middleware(['auth'])
    ->name('admin.employees.set-geofence');

// Rota de logout (POST)
Route::post('/logout', function () {
    try {
        \Log::info('Logout iniciado', ['user_id' => auth()->id(), 'user_email' => auth()->user()->email]);

        auth()->guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        request()->session()->flush();

        \Log::info('Logout concluído com sucesso');

        return redirect()->route('login')->with('status', 'Você saiu com sucesso!');
    } catch (\Exception $e) {
        \Log::error('Erro no logout: ' . $e->getMessage());
        return redirect()->route('login')->withErrors(['error' => 'Erro ao fazer logout. Por favor, tente novamente.']);
    }
})->middleware('auth')->name('logout');

// Rota de logout alternativa (GET) - para casos onde o CSRF expira
Route::get('/logout', function () {
    \Log::info('Logout GET iniciado', ['user_id' => auth()->id()]);

    auth()->guard('web')->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    request()->session()->flush();

    \Log::info('Logout GET concluído');

    return redirect()->route('login')->with('status', 'Você saiu com sucesso!');
})->middleware('auth')->name('logout.get');

// ============================================================
// ROTAS DO PWA DO FUNCIONÁRIO (Employee Self-Service)
// ============================================================

use App\Http\Controllers\Employee\EmployeePwaController;

// Tela de login do funcionário
Route::get('/employee', [EmployeePwaController::class, 'login'])->name('employee.pwa.login');

// Autenticação do funcionário
Route::post('/employee/authenticate', [EmployeePwaController::class, 'authenticate'])->name('employee.pwa.authenticate');

// Dashboard do funcionário (após login)
Route::get('/employee/dashboard', [EmployeePwaController::class, 'dashboard'])->name('employee.pwa.dashboard');

// API para buscar comprovantes do mês
Route::get('/employee/api/receipts', [EmployeePwaController::class, 'getReceipts'])->name('employee.api.receipts');

// Visualizar comprovante específico
Route::get('/employee/receipt/{uuid}', [EmployeePwaController::class, 'viewReceipt'])->name('employee.receipt.view');

// Download do PDF do comprovante
Route::get('/employee/receipt/{uuid}/download', [EmployeePwaController::class, 'downloadReceipt'])->name('employee.receipt.download');

// Validar autenticador (público)
Route::post('/api/public/validate-authenticator', [EmployeePwaController::class, 'validateAuthenticator'])->name('api.validate.authenticator');

// ============================================================
// ROTAS DE ARQUIVOS LEGAIS (AFD E AEJ) - Portaria 671/2021
// ============================================================

use App\Http\Controllers\Admin\LegalFilesController;

Route::middleware(['auth', 'tenant.active'])->prefix('admin')->name('admin.')->group(function () {
    // Listagem de arquivos legais
    Route::get('/legal-files', [LegalFilesController::class, 'index'])->name('legal-files.index');

    // Visualizar detalhes de um arquivo
    Route::get('/legal-files/{file}', [LegalFilesController::class, 'show'])->name('legal-files.show');

    // Geração de arquivos
    Route::post('/legal-files/generate-afd', [LegalFilesController::class, 'generateAFD'])->name('legal-files.generate-afd');
    Route::post('/legal-files/generate-aej', [LegalFilesController::class, 'generateAEJ'])->name('legal-files.generate-aej');
    Route::post('/legal-files/generate-bulk-aej', [LegalFilesController::class, 'generateBulkAEJ'])->name('legal-files.generate-bulk-aej');

    // Downloads
    Route::get('/legal-files/{file}/download', [LegalFilesController::class, 'download'])->name('legal-files.download');
    Route::get('/legal-files/{file}/download-signature', [LegalFilesController::class, 'downloadSignature'])->name('legal-files.download-signature');
    Route::get('/legal-files/{file}/download-bundle', [LegalFilesController::class, 'downloadBundle'])->name('legal-files.download-bundle');

    // Deletar arquivo
    Route::delete('/legal-files/{file}', [LegalFilesController::class, 'destroy'])->name('legal-files.destroy');

    // Estatísticas
    Route::get('/legal-files-statistics', [LegalFilesController::class, 'statistics'])->name('legal-files.statistics');
});

// ============================================================
// ROTAS DE BILLING E PAGAMENTOS
// ============================================================

use App\Http\Controllers\Admin\PaymentGatewayController;
use App\Http\Controllers\Tenant\BillingController;
use App\Http\Controllers\WebhookController;

// Rotas de Super Admin - Billing
Route::middleware(['auth', 'role:super-admin'])->prefix('admin')->name('admin.')->group(function () {
    // Gateways de Pagamento
    Route::get('/payment-gateways', function () {
        return view('admin.payment-gateways.index');
    })->name('payment-gateways.index');

    Route::post('/payment-gateways/{paymentGateway}/toggle-active', [PaymentGatewayController::class, 'toggleActive'])
        ->name('payment-gateways.toggle-active');

    Route::post('/payment-gateways/{paymentGateway}/set-default', [PaymentGatewayController::class, 'setDefault'])
        ->name('payment-gateways.set-default');

    // Faturas
    Route::get('/invoices', [\App\Http\Controllers\Admin\InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/{invoice}', [\App\Http\Controllers\Admin\InvoiceController::class, 'show'])->name('invoices.show');
    Route::post('/invoices/{invoice}/mark-as-paid', [\App\Http\Controllers\Admin\InvoiceController::class, 'markAsPaid'])->name('invoices.mark-as-paid');
    Route::post('/invoices/{invoice}/cancel', [\App\Http\Controllers\Admin\InvoiceController::class, 'cancel'])->name('invoices.cancel');
    Route::post('/invoices/{invoice}/send-second-copy', [\App\Http\Controllers\Admin\InvoiceController::class, 'sendSecondCopy'])->name('invoices.send-second-copy');
    Route::post('/invoices/{invoice}/generate-payment', [\App\Http\Controllers\Admin\InvoiceController::class, 'generatePayment'])->name('invoices.generate-payment');
    Route::get('/invoices/{invoice}/download-pdf', [\App\Http\Controllers\Admin\InvoiceController::class, 'downloadPdf'])->name('invoices.download-pdf');

    // Pagamentos
    Route::get('/payments', [\App\Http\Controllers\Admin\PaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/{payment}', [\App\Http\Controllers\Admin\PaymentController::class, 'show'])->name('payments.show');
    Route::post('/payments/{payment}/refresh-status', [\App\Http\Controllers\Admin\PaymentController::class, 'refreshStatus'])->name('payments.refresh-status');
    Route::post('/payments/{payment}/cancel', [\App\Http\Controllers\Admin\PaymentController::class, 'cancel'])->name('payments.cancel');
    Route::get('/payments/{payment}/download-boleto', [\App\Http\Controllers\Admin\PaymentController::class, 'downloadBoleto'])->name('payments.download-boleto');
});

// Rotas de Tenant - Billing (Faturas e Pagamentos)
Route::middleware(['auth', 'tenant.active'])->prefix('tenant')->name('tenant.')->group(function () {
    Route::get('/billing', function () {
        return view('tenant.billing.index');
    })->name('billing.index');

    Route::get('/billing/invoices/{invoice}', [BillingController::class, 'show'])->name('billing.show');
    Route::get('/billing/invoices/{invoice}/payment', [BillingController::class, 'payment'])->name('billing.payment');
    Route::post('/billing/invoices/{invoice}/process-payment', [BillingController::class, 'processPayment'])->name('billing.process-payment');
    Route::get('/billing/payments/{payment}', [BillingController::class, 'paymentDetails'])->name('billing.payment-details');
    Route::get('/billing/payments/{payment}/check-status', [BillingController::class, 'checkPaymentStatus'])->name('billing.check-payment-status');
    Route::get('/billing/payments/{payment}/download-boleto', [BillingController::class, 'downloadBoleto'])->name('billing.download-boleto');
});

// Webhooks (públicos - sem autenticação)
Route::post('/webhooks/asaas', [WebhookController::class, 'asaas'])->name('webhook.asaas');
Route::post('/webhooks/mercadopago', [WebhookController::class, 'mercadopago'])->name('webhook.mercadopago');
