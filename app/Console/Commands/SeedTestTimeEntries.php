<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TimeEntry;
use App\Models\Employee;
use Carbon\Carbon;

class SeedTestTimeEntries extends Command
{
    protected $signature = 'seed:test-entries {employee_id} {month?}';
    protected $description = 'Cria registros de teste para um funcionário';

    public function handle()
    {
        $employeeId = $this->argument('employee_id');
        $month = $this->argument('month') ?? now()->format('Y-m');

        $employee = Employee::find($employeeId);

        if (!$employee) {
            $this->error("Funcionário ID {$employeeId} não encontrado!");
            return 1;
        }

        $this->info("Criando registros de teste para: {$employee->name}");
        $this->info("Mês: {$month}");
        $this->newLine();

        $date = Carbon::parse($month . '-01');
        $endDate = $date->copy()->endOfMonth();

        $created = 0;

        while ($date->lte($endDate)) {
            // Pula finais de semana
            if (!in_array($date->dayOfWeek, [0, 6])) {
                // Horários variados
                $clockIn = sprintf('%02d:%02d:00', rand(7, 9), rand(0, 59));
                $lunchStart = '12:00:00';
                $lunchEnd = '13:00:00';
                $clockOut = sprintf('%02d:%02d:00', rand(17, 19), rand(0, 59));

                $entry = TimeEntry::create([
                    'employee_id' => $employee->id,
                    'tenant_id' => $employee->tenant_id,
                    'date' => $date->format('Y-m-d'),
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                    'lunch_start' => $lunchStart,
                    'lunch_end' => $lunchEnd,
                    'type' => 'normal',
                    'status' => 'approved',
                    'ip_address' => '127.0.0.1',
                ]);

                $entry->calculateTotalHours();
                $entry->save();

                $this->line("✓ {$date->format('d/m/Y')} - {$clockIn} até {$clockOut} - {$entry->total_hours}h");
                $created++;
            }

            $date->addDay();
        }

        $this->newLine();
        $this->info("✓ {$created} registros criados com sucesso!");

        return 0;
    }
}
