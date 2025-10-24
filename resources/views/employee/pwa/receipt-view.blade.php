<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comprovante - {{ $receipt->action_name }}</title>
    @vite(['resources/css/app.css'])
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f3f4f6; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .header { background: {{ $receipt->action_color }}; color: #fff; padding: 24px; text-align: center; }
        .content { padding: 24px; }
        .section { margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid #e5e7eb; }
        .label { font-size: 12px; color: #6b7280; margin-bottom: 4px; }
        .value { font-size: 16px; color: #1f2937; font-weight: 600; }
        .time-box { background: #f9fafb; border: 2px solid {{ $receipt->action_color }}; border-radius: 12px; padding: 20px; text-align: center; margin: 20px 0; }
        .time-value { font-size: 48px; font-weight: 700; color: {{ $receipt->action_color }}; font-family: 'Courier New', monospace; }
        .buttons { display: flex; gap: 12px; margin-top: 24px; }
        .btn { flex: 1; padding: 14px; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-secondary { background: #e5e7eb; color: #374151; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="font-size: 24px; margin-bottom: 8px;">{{ $receipt->action_name }}</h1>
            <p style="font-size: 14px; opacity: 0.9;">Comprovante de Registro de Ponto</p>
        </div>

        <div class="content">
            <div class="time-box">
                <div style="font-size: 12px; color: #6b7280; margin-bottom: 8px;">HORÁRIO REGISTRADO</div>
                <div class="time-value">{{ $receipt->marked_at->format('H:i:s') }}</div>
                <div style="font-size: 14px; color: #374151; margin-top: 8px;">
                    {{ $receipt->marked_at->locale('pt_BR')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
                </div>
            </div>

            <div class="section">
                <div class="label">COLABORADOR</div>
                <div class="value">{{ strtoupper($receipt->employee->name) }}</div>
                <div class="label" style="margin-top: 12px;">CPF</div>
                <div class="value">{{ $receipt->employee->cpf }}</div>
            </div>

            <div class="section">
                <div class="label">EMPRESA</div>
                <div class="value">{{ strtoupper($receipt->tenant->name) }}</div>
                <div class="label" style="margin-top: 12px;">CNPJ</div>
                <div class="value">{{ $receipt->tenant->cnpj }}</div>
            </div>

            @if($receipt->gps_latitude && $receipt->gps_longitude)
            <div class="section">
                <div class="label">LOCALIZAÇÃO GPS</div>
                <div class="value" style="font-size: 14px;">{{ $receipt->formatted_location }}</div>
            </div>
            @endif

            <div class="section" style="border-bottom: none;">
                <div class="label">CÓDIGO AUTENTICADOR</div>
                <div class="value" style="font-family: 'Courier New', monospace; letter-spacing: 2px;">
                    {{ $receipt->authenticator_code }}
                </div>
                <div style="font-size: 12px; color: #9ca3af; margin-top: 8px;">
                    Use este código para validar a autenticidade deste comprovante
                </div>
            </div>

            <div class="buttons">
                <button class="btn btn-secondary" onclick="history.back()">← Voltar</button>
                <a href="{{ $receipt->download_url }}" class="btn btn-primary" style="text-align: center; text-decoration: none;">
                    📄 Baixar PDF
                </a>
            </div>

            <div style="margin-top: 24px; padding: 16px; background: #f9fafb; border-radius: 8px; font-size: 12px; color: #6b7280; text-align: center;">
                <strong>Disponível até:</strong> {{ $receipt->available_until->format('d/m/Y H:i') }}<br>
                Este documento possui validade legal conforme Portaria MTP nº 671/2021
            </div>
        </div>
    </div>
</body>
</html>
