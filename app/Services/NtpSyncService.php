<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Serviço de Sincronização com Servidores NTP Brasileiros
 *
 * Este serviço permite sincronizar o horário do servidor com servidores NTP oficiais do Brasil.
 * Usa a API WorldTimeAPI como alternativa aos servidores NTP tradicionais (porta 123).
 */
class NtpSyncService
{
    /**
     * Servidores NTP brasileiros oficiais
     * Fonte: NTP.br - Núcleo de Informação e Coordenação do Ponto BR
     */
    private const BRAZIL_NTP_SERVERS = [
        'a.st1.ntp.br',
        'b.st1.ntp.br',
        'c.st1.ntp.br',
        'd.st1.ntp.br',
        'gps.ntp.br',
        'a.ntp.br',
        'b.ntp.br',
        'c.ntp.br',
    ];

    /**
     * APIs de tempo alternativas (HTTP-based)
     */
    private const TIME_APIs = [
        'https://worldtimeapi.org/api/timezone/America/Sao_Paulo',
        'http://worldclockapi.com/api/json/America/Sao_Paulo/now',
    ];

    /**
     * Obtém o horário sincronizado com servidores NTP brasileiros
     *
     * @return array|false Retorna array com informações de tempo ou false em caso de erro
     */
    public function getSyncedTime()
    {
        // Tenta obter horário de APIs HTTP primeiro (mais fácil através de firewalls)
        $result = $this->getTimeFromAPI();

        if ($result) {
            Log::info('Horário sincronizado com sucesso via API', $result);
            return $result;
        }

        // Se falhar, usa o horário local configurado
        Log::warning('Não foi possível sincronizar com servidores externos. Usando horário local.');

        return [
            'timestamp' => time(),
            'datetime' => now()->format('Y-m-d H:i:s'),
            'timezone' => config('app.timezone'),
            'source' => 'local',
            'synced' => false,
        ];
    }

    /**
     * Obtém horário de APIs HTTP de tempo mundial
     *
     * @return array|false
     */
    private function getTimeFromAPI()
    {
        foreach (self::TIME_APIs as $apiUrl) {
            try {
                $response = Http::timeout(5)->get($apiUrl);

                if ($response->successful()) {
                    $data = $response->json();

                    // WorldTimeAPI format
                    if (isset($data['unixtime'])) {
                        return [
                            'timestamp' => $data['unixtime'],
                            'datetime' => $data['datetime'] ?? null,
                            'timezone' => $data['timezone'] ?? 'America/Sao_Paulo',
                            'source' => 'worldtimeapi',
                            'synced' => true,
                            'offset' => $this->calculateOffset($data['unixtime']),
                        ];
                    }

                    // WorldClockAPI format
                    if (isset($data['currentDateTime'])) {
                        $datetime = Carbon::parse($data['currentDateTime']);
                        return [
                            'timestamp' => $datetime->timestamp,
                            'datetime' => $datetime->format('Y-m-d H:i:s'),
                            'timezone' => 'America/Sao_Paulo',
                            'source' => 'worldclockapi',
                            'synced' => true,
                            'offset' => $this->calculateOffset($datetime->timestamp),
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::debug("Falha ao consultar API de tempo: {$apiUrl} - " . $e->getMessage());
                continue;
            }
        }

        return false;
    }

    /**
     * Calcula a diferença (offset) entre o horário local e o horário sincronizado
     *
     * @param int $syncedTimestamp
     * @return int Diferença em segundos
     */
    private function calculateOffset($syncedTimestamp): int
    {
        $localTimestamp = time();
        return $syncedTimestamp - $localTimestamp;
    }

    /**
     * Verifica se o horário local está sincronizado (diferença menor que 5 segundos)
     *
     * @return array
     */
    public function checkSync(): array
    {
        $syncedTime = $this->getSyncedTime();

        if (!$syncedTime || !$syncedTime['synced']) {
            return [
                'synchronized' => false,
                'message' => 'Não foi possível conectar aos servidores de tempo',
                'local_time' => now()->format('Y-m-d H:i:s'),
            ];
        }

        $offset = abs($syncedTime['offset'] ?? 0);
        $isSynced = $offset < 5; // Tolerância de 5 segundos

        return [
            'synchronized' => $isSynced,
            'offset_seconds' => $syncedTime['offset'] ?? 0,
            'message' => $isSynced
                ? 'Sistema sincronizado com sucesso'
                : "Sistema com diferença de {$offset} segundos",
            'local_time' => now()->format('Y-m-d H:i:s'),
            'ntp_time' => $syncedTime['datetime'],
            'source' => $syncedTime['source'],
        ];
    }

    /**
     * Obtém informações detalhadas sobre servidores NTP brasileiros
     *
     * @return array
     */
    public function getNtpServerInfo(): array
    {
        return [
            'servers' => self::BRAZIL_NTP_SERVERS,
            'description' => 'Servidores NTP oficiais do NTP.br',
            'organization' => 'Núcleo de Informação e Coordenação do Ponto BR',
            'website' => 'https://ntp.br/',
            'note' => 'Servidores mantidos pelo CGI.br para sincronização de horário no Brasil',
        ];
    }

    /**
     * Verifica se o timezone está configurado corretamente para o Brasil
     *
     * @return array
     */
    public function checkTimezone(): array
    {
        $configuredTimezone = config('app.timezone');
        $phpTimezone = date_default_timezone_get();

        $brazilTimezones = [
            'America/Sao_Paulo',
            'America/Rio_Branco',
            'America/Manaus',
            'America/Cuiaba',
            'America/Recife',
            'America/Bahia',
            'America/Fortaleza',
            'America/Belem',
            'America/Maceio',
            'America/Noronha',
        ];

        $isCorrect = in_array($configuredTimezone, $brazilTimezones);

        return [
            'configured_timezone' => $configuredTimezone,
            'php_timezone' => $phpTimezone,
            'is_brazil_timezone' => $isCorrect,
            'recommended' => 'America/Sao_Paulo',
            'current_datetime' => now()->format('Y-m-d H:i:s'),
            'available_brazil_timezones' => $brazilTimezones,
        ];
    }
}
