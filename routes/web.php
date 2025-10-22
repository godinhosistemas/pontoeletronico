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
