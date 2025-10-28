<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NtpSyncService;

class CheckNtpSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'time:check
                            {--sync : Exibe informações de sincronização com servidores NTP}
                            {--servers : Lista os servidores NTP brasileiros disponíveis}
                            {--timezone : Verifica a configuração de timezone}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica e exibe informações sobre sincronização de tempo com servidores NTP brasileiros';

    /**
     * Execute the console command.
     */
    public function handle(NtpSyncService $ntpService)
    {
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║      Verificação de Sincronização de Tempo - NTP Brasil      ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->newLine();

        // Exibe horário local atual
        $this->info('🕐 Horário Local do Sistema:');
        $this->line('   Data/Hora: ' . now()->format('d/m/Y H:i:s'));
        $this->line('   Timezone:  ' . config('app.timezone'));
        $this->newLine();

        // Verifica timezone
        if ($this->option('timezone')) {
            $this->checkTimezone($ntpService);
            return 0;
        }

        // Lista servidores
        if ($this->option('servers')) {
            $this->listServers($ntpService);
            return 0;
        }

        // Verifica sincronização (padrão ou com --sync)
        if ($this->option('sync') || !$this->option('servers') && !$this->option('timezone')) {
            $this->checkSynchronization($ntpService);
            return 0;
        }

        return 0;
    }

    /**
     * Verifica a sincronização com servidores NTP
     */
    private function checkSynchronization(NtpSyncService $ntpService)
    {
        $this->info('🌐 Verificando sincronização com servidores NTP...');
        $this->newLine();

        $bar = $this->output->createProgressBar(3);
        $bar->start();

        // Passo 1: Obter horário sincronizado
        $bar->advance();
        $syncResult = $ntpService->checkSync();

        // Passo 2: Processar resultados
        $bar->advance();

        // Passo 3: Exibir informações
        $bar->advance();
        $bar->finish();
        $this->newLine(2);

        if ($syncResult['synchronized']) {
            $this->info('✅ Sistema SINCRONIZADO com servidores NTP brasileiros');
            $this->line('   Fonte: ' . ($syncResult['source'] ?? 'desconhecida'));
            $this->line('   Diferença: ' . abs($syncResult['offset_seconds']) . ' segundo(s)');
        } else {
            $this->warn('⚠️  ' . $syncResult['message']);
            if (isset($syncResult['offset_seconds'])) {
                $this->line('   Diferença: ' . abs($syncResult['offset_seconds']) . ' segundo(s)');
            }
        }

        $this->newLine();
        $this->table(
            ['Origem', 'Horário'],
            [
                ['Local', $syncResult['local_time']],
                ['NTP', $syncResult['ntp_time'] ?? 'N/A'],
            ]
        );
    }

    /**
     * Lista os servidores NTP brasileiros
     */
    private function listServers(NtpSyncService $ntpService)
    {
        $info = $ntpService->getNtpServerInfo();

        $this->info('📡 Servidores NTP Brasileiros Oficiais:');
        $this->newLine();

        foreach ($info['servers'] as $index => $server) {
            $this->line('   ' . ($index + 1) . '. ' . $server);
        }

        $this->newLine();
        $this->info('ℹ️  Informações:');
        $this->line('   Organização: ' . $info['organization']);
        $this->line('   Website:     ' . $info['website']);
        $this->line('   Descrição:   ' . $info['note']);
    }

    /**
     * Verifica a configuração de timezone
     */
    private function checkTimezone(NtpSyncService $ntpService)
    {
        $timezoneInfo = $ntpService->checkTimezone();

        $this->info('🌍 Configuração de Timezone:');
        $this->newLine();

        $this->table(
            ['Configuração', 'Valor'],
            [
                ['Timezone Configurado', $timezoneInfo['configured_timezone']],
                ['Timezone PHP', $timezoneInfo['php_timezone']],
                ['É Timezone Brasil?', $timezoneInfo['is_brazil_timezone'] ? 'Sim ✓' : 'Não ✗'],
                ['Recomendado', $timezoneInfo['recommended']],
                ['Data/Hora Atual', $timezoneInfo['current_datetime']],
            ]
        );

        if (!$timezoneInfo['is_brazil_timezone']) {
            $this->newLine();
            $this->warn('⚠️  O timezone configurado não é do Brasil!');
            $this->info('   Para corrigir, edite o arquivo config/app.php:');
            $this->line("   'timezone' => 'America/Sao_Paulo',");
            $this->newLine();
            $this->info('   Timezones disponíveis no Brasil:');
            foreach ($timezoneInfo['available_brazil_timezones'] as $tz) {
                $this->line('   • ' . $tz);
            }
        } else {
            $this->newLine();
            $this->info('✅ Timezone configurado corretamente!');
        }
    }
}
