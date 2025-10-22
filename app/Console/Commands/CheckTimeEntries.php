<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TimeEntry;
use App\Models\Employee;

class CheckTimeEntries extends Command
{
    protected $signature = 'check:entries';
    protected $description = 'Verifica registros de ponto no banco';

    public function handle()
    {
        $this->info('=== VERIFICAÇÃO DE REGISTROS DE PONTO ===');
        $this->newLine();

        $totalEntries = TimeEntry::count();
        $this->info("Total de registros: {$totalEntries}");

        if ($totalEntries === 0) {
            $this->warn('NENHUM REGISTRO ENCONTRADO!');
            $this->info('Você precisa criar registros de ponto primeiro.');
            return 0;
        }

        $this->newLine();
        $this->info('Últimos 5 registros:');
        $this->newLine();

        $entries = TimeEntry::with('employee')->latest()->take(5)->get();

        foreach ($entries as $entry) {
            $this->line("ID: {$entry->id}");
            $this->line("  Funcionário: {$entry->employee->name} (ID: {$entry->employee_id})");
            $this->line("  Data: {$entry->date}");
            $this->line("  Entrada: " . ($entry->formatted_clock_in ?? 'NULL'));
            $this->line("  Saída: " . ($entry->formatted_clock_out ?? 'NULL'));
            $this->line("  Almoço: " . ($entry->formatted_lunch_start ?? 'NULL') . " - " . ($entry->formatted_lunch_end ?? 'NULL'));
            $this->line("  Total horas: {$entry->formatted_total_hours} ({$entry->total_hours}h decimal)");
            $this->line("  Status: {$entry->status}");
            $this->newLine();
        }

        $this->info('Funcionários cadastrados:');
        $employees = Employee::all();
        foreach ($employees as $emp) {
            $entriesCount = TimeEntry::where('employee_id', $emp->id)->count();
            $this->line("  - {$emp->name} (ID: {$emp->id}) - {$entriesCount} registros");
        }

        return 0;
    }
}
