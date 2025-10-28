<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Folha de Ponto - {{ $employee->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
            color: #000;
            padding: 15px;
        }

        .header {
            margin-bottom: 15px;
        }

        .header h1 {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 10px;
        }

        .info-item {
            display: flex;
        }

        .info-label {
            font-weight: bold;
            margin-right: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
            margin-bottom: 15px;
        }

        th {
            background: #000;
            color: #fff;
            padding: 4px 2px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #000;
        }

        td {
            padding: 3px 2px;
            border: 1px solid #000;
            text-align: center;
        }

        .day-cell {
            font-weight: bold;
            text-align: left;
            padding-left: 3px;
            white-space: nowrap;
        }

        .time-cell {
            font-family: 'Courier New', monospace;
        }

        .marcacoes-cell {
            font-size: 7px;
            text-align: left;
            padding-left: 2px;
        }

        .obs-cell {
            text-align: center;
        }

        .totals {
            margin: 15px 0;
            display: flex;
            gap: 20px;
            justify-content: center;
        }

        .total-box {
            border: 2px solid #000;
            padding: 8px 15px;
            text-align: center;
        }

        .total-label {
            font-size: 9px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .total-value {
            font-size: 16px;
            font-weight: bold;
        }

        .footer {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #000;
        }

        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            margin-top: 40px;
        }

        .signature-line {
            border-top: 1px solid #000;
            padding-top: 5px;
            text-align: center;
            font-size: 9px;
        }

        .print-info {
            text-align: center;
            font-size: 8px;
            margin-top: 10px;
            color: #666;
        }

        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .print-button:hover {
            background: #1e40af;
        }

        @media print {
            body {
                padding: 0;
            }

            .print-button {
                display: none;
            }

            table {
                page-break-inside: auto;
            }

            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            @page {
                margin: 1cm;
                size: A4;
            }
        }
    </style>
</head>
<body>
    <button class="print-button" onclick="window.print()">🖨️ Imprimir / Salvar PDF</button>

    <div class="header">
        <h1>FOLHA DE PONTO</h1>

        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Colaborador(a):</span>
                <span>{{ strtoupper($employee->name) }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">CPF:</span>
                <span>{{ preg_replace('/[^0-9]/', '', $employee->cpf ?? '---') }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Período:</span>
                <span>{{ strtoupper($period) }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Código Autenticador:</span>
                <span>{{ $authenticator }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Empresa:</span>
                <span>{{ strtoupper($tenant->name) }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">CNPJ:</span>
                <span>{{ preg_replace('/[^0-9]/', '', $tenant->cnpj ?? '---') }}</span>
            </div>
        </div>

        <div class="info-item">
            <span class="info-label">Jornada:</span>
            <span>
                @if($workSchedule)
                    {{ $workSchedule->name }} ({{ $workSchedule->code }}) |
                    Semanal: {{ sprintf('%02d:00h', $expectedWeeklyHours) }} |
                    Diária: {{ sprintf('%02d:%02d', floor($expectedDailyHours), round(($expectedDailyHours - floor($expectedDailyHours)) * 60)) }}h
                    @if($workSchedule->break_minutes > 0)
                        | Intervalo: {{ sprintf('%02d:%02d', floor($workSchedule->break_minutes / 60), $workSchedule->break_minutes % 60) }}h
                    @endif
                @else
                    Semanal: 44:00h | Diária: 08:48h (Padrão CLT)
                @endif
            </span>
        </div>

        <div class="info-item" style="margin-top: 5px;">
            <span class="info-label">Fechamento:</span>
            <span>Em {{ $generatedAt }} por {{ strtoupper($generatedBy) }}</span>
        </div>

        <div style="margin-top: 8px; font-size: 8px;">
            <strong>Marcações Relógio:</strong> E=Entrada, S=Saída, N=Nenhuma
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 80px;">Dia</th>
                <th style="width: 45px;">Entrada</th>
                <th style="width: 45px;">Saída</th>
                <th style="width: 45px;">Entrada</th>
                <th style="width: 45px;">Saída</th>
                <th style="width: 45px;">Interv.</th>
                <th style="width: 45px;">Total</th>
                <th style="width: 45px;">Extra</th>
                <th style="width: 45px;">Faltosos</th>
                <th style="width: 50px;">Sobreaviso</th>
                <th style="width: 120px;">Marcações</th>
                <th style="width: 80px;">Observações</th>
            </tr>
        </thead>
        <tbody>
            @foreach($allDays as $day)
            <tr>
                <td class="day-cell">
                    Dia {{ str_pad($day['day_number'], 2, '0', STR_PAD_LEFT) }} - {{ ucfirst($day['day_name']) }}
                </td>

                @php
                    $firstEntry = isset($day['entries']) && $day['entries']->isNotEmpty()
                        ? $day['entries']->first()
                        : null;

                    // Extrai os horários do registro
                    $entrada1 = null;
                    $saida1 = null;
                    $entrada2 = null;
                    $saida2 = null;
                    $hasAdjustment = false;

                    if ($firstEntry) {
                        $hasAdjustment = $firstEntry->has_adjustment ?? false;

                        if ($hasAdjustment) {
                            // Se houve ajuste, usa os valores ajustados
                            $entrada1 = $firstEntry->formatTime('adjusted_clock_in');
                            $saida1 = $firstEntry->formatTime('adjusted_lunch_start');
                            $entrada2 = $firstEntry->formatTime('adjusted_lunch_end');
                            $saida2 = $firstEntry->formatTime('adjusted_clock_out');
                        } else {
                            // Se não houve ajuste, usa os valores normais
                            $entrada1 = $firstEntry->formatted_clock_in ?? $firstEntry->formatTime('clock_in');
                            $saida1 = $firstEntry->formatted_lunch_start ?? $firstEntry->formatTime('lunch_start');
                            $entrada2 = $firstEntry->formatted_lunch_end ?? $firstEntry->formatTime('lunch_end');
                            $saida2 = $firstEntry->formatted_clock_out ?? $firstEntry->formatTime('clock_out');
                        }
                    }

                    // Calcula intervalo em minutos
                    $intervalo = '----';
                    $intervaloMinutes = 0;
                    if ($saida1 && $entrada2) {
                        try {
                            $date = $day['date']->format('Y-m-d');
                            $lunch_start = \Carbon\Carbon::parse($date . ' ' . $saida1);
                            $lunch_end = \Carbon\Carbon::parse($date . ' ' . $entrada2);

                            // Se fim do almoço for menor que início, passou da meia-noite
                            if ($lunch_end->lessThan($lunch_start)) {
                                $lunch_end->addDay();
                            }

                            $intervaloMinutes = $lunch_start->diffInMinutes($lunch_end);
                            $hours = floor($intervaloMinutes / 60);
                            $mins = $intervaloMinutes % 60;
                            $intervalo = sprintf('%02d:%02d', $hours, $mins);
                        } catch (\Exception $e) {
                            $intervalo = '----';
                        }
                    }

                    // Total de horas trabalhadas no dia
                    $totalHoras = $firstEntry ? ($firstEntry->total_hours ?? 0) : 0;

                    // Jornada esperada do dia baseado na configuração
                    $dayExpectedHours = 0;

                    if ($workSchedule) {
                        // Verifica se o funcionário trabalha neste dia da semana
                        $dayName = strtolower($day['date']->locale('en')->dayName);
                        if ($workSchedule->worksOnDay($dayName)) {
                            // Verifica se há configuração específica para este dia
                            $dayConfig = $workSchedule->getDayConfig($dayName);
                            if ($dayConfig && isset($dayConfig['hours'])) {
                                $dayExpectedHours = $dayConfig['hours'];
                            } else {
                                $dayExpectedHours = $expectedDailyHours;
                            }
                        }
                    } else {
                        // Fallback: não conta sábado=6 e domingo=0
                        $dayExpectedHours = in_array($day['date']->dayOfWeek, [0, 6]) ? 0 : $expectedDailyHours;
                    }

                    // Horas extras (acima da jornada)
                    $extra = ($totalHoras > $dayExpectedHours && $dayExpectedHours > 0) ? ($totalHoras - $dayExpectedHours) : 0;

                    // Horas faltosas (abaixo da jornada)
                    $faltosos = ($dayExpectedHours > 0 && $totalHoras < $dayExpectedHours) ? ($dayExpectedHours - $totalHoras) : 0;

                    // Função para formatar horas decimais em HH:MM
                    $formatHours = function($hours) {
                        if ($hours <= 0) return '00:00';
                        $h = floor($hours);
                        $m = round(($hours - $h) * 60);
                        return sprintf('%02d:%02d', $h, $m);
                    };
                @endphp

                <td class="time-cell">{{ $entrada1 ?: '----' }}</td>
                <td class="time-cell">{{ $saida1 ?: '----' }}</td>
                <td class="time-cell">{{ $entrada2 ?: '----' }}</td>
                <td class="time-cell">{{ $saida2 ?: '----' }}</td>
                <td class="time-cell">{{ $intervalo }}</td>
                <td class="time-cell"><strong>{{ $totalHoras > 0 ? $formatHours($totalHoras) : '----' }}</strong></td>
                <td class="time-cell">{{ $extra > 0.1 ? $formatHours($extra) : '----' }}</td>
                <td class="time-cell">{{ $faltosos > 0.1 ? $formatHours($faltosos) : '----' }}</td>
                <td class="time-cell">----</td>

                <td class="marcacoes-cell">
                    @if($firstEntry)
                        @if($entrada1) E{{ $entrada1 }} @endif
                        @if($saida1) S{{ $saida1 }} @endif
                        @if($entrada2) E{{ $entrada2 }} @endif
                        @if($saida2) S{{ $saida2 }} @endif
                    @else
                        N
                    @endif
                </td>

                <td class="obs-cell">
                    @if($hasAdjustment)
                        <strong style="color: #7c3aed;">AJUSTADO</strong>
                    @else
                        ----------
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div class="total-box">
            <div class="total-label">HORAS DO PERÍODO:</div>
            <div class="total-value">
                @php
                    $h = floor($totalHours);
                    $m = round(($totalHours - $h) * 60);
                @endphp
                {{ sprintf('%d:%02d', $h, $m) }}
            </div>
        </div>

        <div class="total-box">
            <div class="total-label">SOBREJORNADAS DIÁRIAS:</div>
            <div class="total-value">
                @php
                    $h = floor($overtimeHours);
                    $m = round(($overtimeHours - $h) * 60);
                @endphp
                {{ sprintf('%d:%02d', $h, $m) }}
            </div>
        </div>

        @php
            // Coleta todos os entries de todos os dias
            $allEntriesCollection = collect();
            foreach ($allDays as $day) {
                $allEntriesCollection = $allEntriesCollection->merge($day['entries']);
            }

            // Calcula horas extras por tipo
            $normalOvertimeHours = $allEntriesCollection->where('overtime_type', 'normal')->sum('overtime_hours') ?? 0;
            $nightOvertimeHours = $allEntriesCollection->where('overtime_type', 'night')->sum('overtime_hours') ?? 0;
            $holidayOvertimeHours = $allEntriesCollection->where('overtime_type', 'holiday')->sum('overtime_hours') ?? 0;
            $cltViolations = $allEntriesCollection->where('clt_limit_exceeded', true)->count();

            // Banco de horas (se existir)
            $bankHoursBalance = 0;
            if (class_exists('App\Models\OvertimeBalance')) {
                $period = $dateFrom->format('Y-m');
                $balance = \App\Models\OvertimeBalance::forEmployee($employee->id)
                    ->forPeriod($period)
                    ->first();
                $bankHoursBalance = $balance ? $balance->balance_hours : 0;
            }
        @endphp

        @if($normalOvertimeHours > 0 || $nightOvertimeHours > 0 || $holidayOvertimeHours > 0)
            <div class="total-box" style="background-color: #f3e5f5;">
                <div class="total-label">HE Normal (50%):</div>
                <div class="total-value">
                    @php
                        $h = floor($normalOvertimeHours);
                        $m = round(($normalOvertimeHours - $h) * 60);
                    @endphp
                    {{ sprintf('%d:%02d', $h, $m) }}
                </div>
            </div>

            @if($nightOvertimeHours > 0)
                <div class="total-box" style="background-color: #e8eaf6;">
                    <div class="total-label">HE Noturna (20%):</div>
                    <div class="total-value">
                        @php
                            $h = floor($nightOvertimeHours);
                            $m = round(($nightOvertimeHours - $h) * 60);
                        @endphp
                        {{ sprintf('%d:%02d', $h, $m) }}
                    </div>
                </div>
            @endif

            @if($holidayOvertimeHours > 0)
                <div class="total-box" style="background-color: #ffebee;">
                    <div class="total-label">HE Feriado/Domingo (100%):</div>
                    <div class="total-value">
                        @php
                            $h = floor($holidayOvertimeHours);
                            $m = round(($holidayOvertimeHours - $h) * 60);
                        @endphp
                        {{ sprintf('%d:%02d', $h, $m) }}
                    </div>
                </div>
            @endif
        @endif

        @if($bankHoursBalance > 0)
            <div class="total-box" style="background-color: #e8f5e9;">
                <div class="total-label">BANCO DE HORAS:</div>
                <div class="total-value">
                    @php
                        $h = floor($bankHoursBalance);
                        $m = round(($bankHoursBalance - $h) * 60);
                    @endphp
                    {{ sprintf('%d:%02d', $h, $m) }}
                </div>
            </div>
        @endif

        @if($cltViolations > 0)
            <div class="total-box" style="background-color: #fff3e0; border: 2px solid #ff9800;">
                <div class="total-label">⚠️ VIOLAÇÕES CLT (>2h/dia):</div>
                <div class="total-value">{{ $cltViolations }} dia(s)</div>
            </div>
        @endif

        <div class="total-box">
            <div class="total-label">HORAS FALTOSAS:</div>
            <div class="total-value">
                @php
                    $h = floor($missingHours);
                    $m = round(($missingHours - $h) * 60);
                @endphp
                {{ sprintf('%d:%02d', $h, $m) }}
            </div>
        </div>

        <div class="total-box">
            <div class="total-label">HORAS EM SOBREAVISO:</div>
            <div class="total-value">00:00</div>
        </div>
    </div>

    <div class="footer">
        @php
            $adjustedEntries = collect($allDays)
                ->pluck('entries')
                ->flatten()
                ->filter(fn($entry) => $entry && $entry->has_adjustment)
                ->values();
        @endphp

        <div style="font-size: 9px; margin-bottom: 15px;">
            <strong>AJUSTES:</strong>
            @if($adjustedEntries->isEmpty())
                Não foram inseridos acréscimos nem abatimentos nesta folha de ponto.
            @else
                Foram realizados {{ $adjustedEntries->count() }} ajuste(s) nesta folha de ponto:

                @foreach($adjustedEntries as $adjustedEntry)
                <div style="margin-top: 8px; padding: 8px; border: 1px solid #7c3aed; background: #faf5ff;">
                    <div style="display: flex; gap: 15px; margin-bottom: 4px;">
                        <span><strong>Data:</strong> {{ $adjustedEntry->date->format('d/m/Y') }} ({{ ucfirst($adjustedEntry->date->locale('pt_BR')->dayName) }})</span>
                        <span><strong>Ajustado por:</strong> {{ $adjustedEntry->adjuster->name ?? 'Sistema' }}</span>
                        <span><strong>Em:</strong> {{ $adjustedEntry->adjusted_at?->format('d/m/Y H:i') }}</span>
                    </div>

                    <div style="margin-bottom: 4px;">
                        <strong>Horários Originais:</strong>
                        Entrada: {{ $adjustedEntry->formatTime('original_clock_in') ?? '----' }} |
                        Saída Almoço: {{ $adjustedEntry->formatTime('original_lunch_start') ?? '----' }} |
                        Retorno Almoço: {{ $adjustedEntry->formatTime('original_lunch_end') ?? '----' }} |
                        Saída: {{ $adjustedEntry->formatTime('original_clock_out') ?? '----' }}
                    </div>

                    <div style="margin-bottom: 4px;">
                        <strong style="color: #7c3aed;">Horários Ajustados:</strong>
                        Entrada: {{ $adjustedEntry->formatTime('adjusted_clock_in') ?? '----' }} |
                        Saída Almoço: {{ $adjustedEntry->formatTime('adjusted_lunch_start') ?? '----' }} |
                        Retorno Almoço: {{ $adjustedEntry->formatTime('adjusted_lunch_end') ?? '----' }} |
                        Saída: {{ $adjustedEntry->formatTime('adjusted_clock_out') ?? '----' }}
                    </div>

                    <div>
                        <strong>Justificativa:</strong> {{ $adjustedEntry->adjustment_reason }}
                    </div>
                </div>
                @endforeach
            @endif
        </div>

        <div class="signatures">
            <div>
                <div class="signature-line">
                    {{ strtoupper($employee->name) }}<br>
                    {{ preg_replace('/[^0-9]/', '', $employee->cpf ?? '') }}
                </div>
            </div>
            <div>
                <div class="signature-line">
                    {{ strtoupper($tenant->name) }}<br>
                    {{ preg_replace('/[^0-9]/', '', $tenant->cnpj ?? '') }}
                </div>
            </div>
        </div>

        <div class="print-info">
            Folha impressa em {{ $generatedAt }} por {{ strtoupper($generatedBy) }}, autenticador {{ $authenticator }}
        </div>
    </div>
</body>
</html>
