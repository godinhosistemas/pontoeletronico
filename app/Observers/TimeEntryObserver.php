<?php

namespace App\Observers;

use App\Models\TimeEntry;
use App\Services\OvertimeService;

class TimeEntryObserver
{
    protected $overtimeService;

    public function __construct(OvertimeService $overtimeService)
    {
        $this->overtimeService = $overtimeService;
    }

    /**
     * Handle the TimeEntry "saving" event.
     * Processa antes de salvar para calcular horas extras
     */
    public function saving(TimeEntry $timeEntry): void
    {
        // Recalcula total de horas se houver mudanças nos horários
        if ($timeEntry->isDirty(['clock_in', 'clock_out', 'lunch_start', 'lunch_end'])) {
            $timeEntry->calculateTotalHours();
        }
    }

    /**
     * Handle the TimeEntry "saved" event.
     * Processa após salvar para atualizar horas extras
     */
    public function saved(TimeEntry $timeEntry): void
    {
        // Evita loop infinito verificando se os campos de overtime já foram calculados
        if (!$timeEntry->wasRecentlyCreated && !$timeEntry->isDirty(['clock_in', 'clock_out', 'lunch_start', 'lunch_end', 'total_hours'])) {
            return;
        }

        // Processa horas extras automaticamente
        if ($timeEntry->clock_in && $timeEntry->clock_out && $timeEntry->total_hours > 0) {
            // Usa update sem disparar eventos para evitar loop
            $timeEntry->timestamps = false;
            $this->overtimeService->processTimeEntry($timeEntry);
            $timeEntry->timestamps = true;
        }
    }

    /**
     * Handle the TimeEntry "updated" event.
     * Quando o status muda para aprovado, adiciona ao banco de horas
     */
    public function updated(TimeEntry $timeEntry): void
    {
        // Se o status mudou para aprovado e tem horas extras, adiciona ao banco de horas
        if ($timeEntry->isDirty('status') && $timeEntry->status === 'approved' && $timeEntry->hasOvertime()) {
            $this->overtimeService->addToBankHours($timeEntry);
        }
    }

    /**
     * Handle the TimeEntry "deleted" event.
     */
    public function deleted(TimeEntry $timeEntry): void
    {
        // TODO: Remover do banco de horas se foi aprovado
    }

    /**
     * Handle the TimeEntry "restored" event.
     */
    public function restored(TimeEntry $timeEntry): void
    {
        //
    }

    /**
     * Handle the TimeEntry "force deleted" event.
     */
    public function forceDeleted(TimeEntry $timeEntry): void
    {
        //
    }
}
