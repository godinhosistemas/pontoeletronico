<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OvertimeService;

class ExpireOvertimeBalance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'overtime:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expira bancos de horas vencidos (CLT: 1 ano)';

    protected $overtimeService;

    public function __construct(OvertimeService $overtimeService)
    {
        parent::__construct();
        $this->overtimeService = $overtimeService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Verificando bancos de horas vencidos...');

        $expiredCount = $this->overtimeService->expireBankHours();

        if ($expiredCount > 0) {
            $this->info("✓ {$expiredCount} banco(s) de horas expirado(s) com sucesso.");
        } else {
            $this->info('Nenhum banco de horas vencido encontrado.');
        }

        return 0;
    }
}
