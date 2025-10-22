<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TimeEntry;

class RecalculateTimeEntries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'timeentry:recalculate {--all : Recalcula todos os registros}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalcula o total de horas de registros de ponto';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando recálculo de horas trabalhadas...');

        $query = TimeEntry::whereNotNull('clock_in')
            ->whereNotNull('clock_out');

        // Se não passar --all, recalcula apenas registros com total_hours <= 0 ou null
        if (!$this->option('all')) {
            $query->where(function($q) {
                $q->where('total_hours', '<=', 0)
                  ->orWhereNull('total_hours');
            });
        }

        $entries = $query->get();
        $total = $entries->count();

        if ($total === 0) {
            $this->warn('Nenhum registro encontrado para recalcular.');
            return 0;
        }

        $this->info("Encontrados {$total} registros para recalcular.");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $updated = 0;
        $errors = 0;

        foreach ($entries as $entry) {
            try {
                $oldHours = $entry->total_hours;
                $entry->calculateTotalHours();
                $entry->save();

                if ($oldHours != $entry->total_hours) {
                    $updated++;
                }

                $bar->advance();
            } catch (\Exception $e) {
                $errors++;
                $this->error("\nErro ao processar registro ID {$entry->id}: " . $e->getMessage());
            }
        }

        $bar->finish();

        $this->newLine(2);
        $this->info("Recálculo concluído!");
        $this->info("Total processado: {$total}");
        $this->info("Atualizados: {$updated}");

        if ($errors > 0) {
            $this->warn("Erros: {$errors}");
        }

        return 0;
    }
}
