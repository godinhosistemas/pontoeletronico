<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Ponto - {{ $tenant->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #2563eb;
        }

        .header h1 {
            color: #1e40af;
            font-size: 24px;
            margin-bottom: 10px;
        }

        .header .company {
            font-size: 16px;
            color: #64748b;
            margin-bottom: 5px;
        }

        .header .period {
            font-size: 14px;
            color: #475569;
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #2563eb;
        }

        .summary-card h3 {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .summary-card .value {
            font-size: 24px;
            font-weight: bold;
            color: #1e40af;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        th {
            background: #1e40af;
            color: white;
            padding: 10px 8px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
        }

        td {
            padding: 8px;
            border-bottom: 1px solid #e2e8f0;
        }

        tr:hover {
            background: #f8fafc;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
        }

        .badge-normal {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-overtime {
            background: #e9d5ff;
            color: #6b21a8;
        }

        .badge-adjusted {
            background: #faf5ff;
            color: #7c3aed;
            border: 1px solid #7c3aed;
        }

        .adjustments-section {
            margin-top: 30px;
            padding: 15px;
            background: #faf5ff;
            border: 1px solid #7c3aed;
            border-radius: 8px;
        }

        .adjustments-section h2 {
            color: #7c3aed;
            font-size: 16px;
            margin-bottom: 15px;
        }

        .adjustment-item {
            margin-bottom: 15px;
            padding: 10px;
            background: white;
            border-left: 3px solid #7c3aed;
        }

        .adjustment-item .date {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .adjustment-item .details {
            font-size: 10px;
            color: #64748b;
        }

        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e2e8f0;
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
        }

        .footer .generated {
            margin-bottom: 5px;
        }

        @media print {
            body {
                padding: 0;
            }

            .header {
                page-break-after: avoid;
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
            }
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
        }

        .print-button:hover {
            background: #1e40af;
        }

        @media print {
            .print-button {
                display: none;
            }
        }
    </style>
</head>
<body>
    <button class="print-button" onclick="window.print()">🖨️ Imprimir / Salvar PDF</button>

    <div class="header">
        <h1>Relatório de Registros de Ponto</h1>
        <div class="company">{{ $tenant->name }}</div>
        <div class="period">Período: {{ $dateFrom }} até {{ $dateTo }}</div>
    </div>

    <div class="summary">
        <div class="summary-card">
            <h3>Total de Registros</h3>
            <div class="value">{{ $summary['total_days'] }}</div>
        </div>
        <div class="summary-card">
            <h3>Total de Horas</h3>
            <div class="value">
                @php
                    $h = floor($summary['total_hours']);
                    $m = round(($summary['total_hours'] - $h) * 60);
                @endphp
                {{ sprintf('%d:%02d', $h, $m) }}
            </div>
        </div>
        <div class="summary-card">
            <h3>Média por Dia</h3>
            <div class="value">
                @php
                    $h = floor($summary['avg_hours']);
                    $m = round(($summary['avg_hours'] - $h) * 60);
                @endphp
                {{ sprintf('%d:%02d', $h, $m) }}
            </div>
        </div>
        <div class="summary-card">
            <h3>Funcionários</h3>
            <div class="value">{{ $summary['employees_count'] }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Funcionário</th>
                <th>Matrícula</th>
                <th class="text-center">Entrada</th>
                <th class="text-center">Saída</th>
                <th class="text-center">Almoço</th>
                <th class="text-right">Total (h)</th>
                <th class="text-center">Tipo</th>
                <th class="text-center">Ajustado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($entries as $entry)
            @php
                // Determina os horários a exibir (ajustados ou originais)
                $clockIn = $entry->has_adjustment ? ($entry->formatTime('adjusted_clock_in') ?? '--:--') : ($entry->formatted_clock_in ?? '--:--');
                $clockOut = $entry->has_adjustment ? ($entry->formatTime('adjusted_clock_out') ?? '--:--') : ($entry->formatted_clock_out ?? '--:--');
                $lunchStart = $entry->has_adjustment ? ($entry->formatTime('adjusted_lunch_start') ?? '--:--') : ($entry->formatted_lunch_start ?? '--:--');
                $lunchEnd = $entry->has_adjustment ? ($entry->formatTime('adjusted_lunch_end') ?? '--:--') : ($entry->formatted_lunch_end ?? '--:--');
            @endphp
            <tr>
                <td>{{ \Carbon\Carbon::parse($entry->date)->format('d/m/Y') }}</td>
                <td>{{ $entry->employee->name }}</td>
                <td>{{ $entry->employee->registration_number }}</td>
                <td class="text-center">{{ $clockIn }}</td>
                <td class="text-center">{{ $clockOut }}</td>
                <td class="text-center">
                    @if($lunchStart && $lunchEnd && $lunchStart != '--:--' && $lunchEnd != '--:--')
                    {{ $lunchStart }} - {{ $lunchEnd }}
                    @else
                    -
                    @endif
                </td>
                <td class="text-right">
                    <strong>{{ $entry->total_hours ? $entry->formatted_total_hours : '-' }}</strong>
                </td>
                <td class="text-center">
                    <span class="badge badge-{{ $entry->type }}">{{ ucfirst($entry->type) }}</span>
                </td>
                <td class="text-center">
                    @if($entry->has_adjustment)
                    <span class="badge badge-adjusted">Sim</span>
                    @else
                    -
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="text-center" style="padding: 30px;">
                    Nenhum registro encontrado para o período selecionado
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @php
        $adjustedEntries = $entries->filter(fn($entry) => $entry->has_adjustment);
    @endphp

    @if($adjustedEntries->isNotEmpty())
    <div class="adjustments-section">
        <h2>Ajustes Realizados ({{ $adjustedEntries->count() }})</h2>
        @foreach($adjustedEntries as $adjustedEntry)
        <div class="adjustment-item">
            <div class="date">
                {{ \Carbon\Carbon::parse($adjustedEntry->date)->format('d/m/Y') }} - {{ $adjustedEntry->employee->name }}
            </div>
            <div class="details">
                <strong>Horários Originais:</strong>
                Entrada: {{ $adjustedEntry->formatTime('original_clock_in') ?? '--:--' }} |
                Saída Almoço: {{ $adjustedEntry->formatTime('original_lunch_start') ?? '--:--' }} |
                Retorno Almoço: {{ $adjustedEntry->formatTime('original_lunch_end') ?? '--:--' }} |
                Saída: {{ $adjustedEntry->formatTime('original_clock_out') ?? '--:--' }}
            </div>
            <div class="details" style="color: #7c3aed; margin-top: 5px;">
                <strong>Horários Ajustados:</strong>
                Entrada: {{ $adjustedEntry->formatTime('adjusted_clock_in') ?? '--:--' }} |
                Saída Almoço: {{ $adjustedEntry->formatTime('adjusted_lunch_start') ?? '--:--' }} |
                Retorno Almoço: {{ $adjustedEntry->formatTime('adjusted_lunch_end') ?? '--:--' }} |
                Saída: {{ $adjustedEntry->formatTime('adjusted_clock_out') ?? '--:--' }}
            </div>
            <div class="details" style="margin-top: 5px;">
                <strong>Justificativa:</strong> {{ $adjustedEntry->adjustment_reason }}
            </div>
            <div class="details" style="margin-top: 5px;">
                <strong>Ajustado por:</strong> {{ $adjustedEntry->adjuster->name ?? 'Sistema' }} em {{ $adjustedEntry->adjusted_at?->format('d/m/Y H:i') }}
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <div class="footer">
        <div class="generated">Relatório gerado em: {{ $generatedAt }}</div>
        <div>Sistema de Ponto Eletrônico - {{ $tenant->name }}</div>
    </div>
</body>
</html>
