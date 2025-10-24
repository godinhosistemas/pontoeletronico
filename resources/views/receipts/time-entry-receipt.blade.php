<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprovante de Registro de Ponto</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            color: #000;
            padding: 30px;
            background: #fff;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            border: 3px solid {{ $receipt->action_color }};
            border-radius: 10px;
            overflow: hidden;
        }

        .header {
            background: {{ $receipt->action_color }};
            color: #fff;
            padding: 20px;
            text-align: center;
        }

        .header h1 {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .header h2 {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
        }

        .header p {
            font-size: 11px;
            margin-top: 8px;
            opacity: 0.95;
        }

        .content {
            padding: 30px 25px;
        }

        .section {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e5e7eb;
        }

        .section:last-child {
            border-bottom: none;
        }

        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .info-label {
            font-weight: bold;
            color: #374151;
        }

        .info-value {
            color: #1f2937;
            text-align: right;
        }

        .datetime-box {
            background: #f9fafb;
            border: 2px solid {{ $receipt->action_color }};
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            margin: 20px 0;
        }

        .datetime-label {
            font-size: 11px;
            color: #6b7280;
            margin-bottom: 5px;
        }

        .datetime-value {
            font-size: 28px;
            font-weight: bold;
            color: {{ $receipt->action_color }};
            font-family: 'Courier New', monospace;
        }

        .datetime-date {
            font-size: 14px;
            color: #374151;
            margin-top: 5px;
        }

        .authenticator-box {
            background: #1f2937;
            color: #fff;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
        }

        .authenticator-label {
            font-size: 10px;
            margin-bottom: 5px;
            opacity: 0.8;
        }

        .authenticator-code {
            font-size: 20px;
            font-weight: bold;
            font-family: 'Courier New', monospace;
            letter-spacing: 2px;
        }

        .location-info {
            background: #f0f9ff;
            border-left: 4px solid #3b82f6;
            padding: 12px;
            margin: 10px 0;
            font-size: 11px;
        }

        .footer {
            background: #f9fafb;
            padding: 20px 25px;
            border-top: 1px solid #e5e7eb;
        }

        .footer-text {
            font-size: 10px;
            color: #6b7280;
            text-align: center;
            line-height: 1.6;
        }

        .legal-note {
            font-size: 9px;
            color: #9ca3af;
            text-align: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
        }

        .badge {
            display: inline-block;
            background: {{ $receipt->action_color }};
            color: #fff;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .qr-placeholder {
            width: 120px;
            height: 120px;
            background: #f3f4f6;
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            margin: 15px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Comprovante de Registro de Ponto</h1>
            <h2>{{ $receipt->action_name }}</h2>
            <p>Documento gerado conforme Portaria MTP nº 671/2021</p>
        </div>

        <div class="content">
            <!-- Informações da Empresa -->
            <div class="section">
                <div class="section-title">📍 Empresa</div>
                <div class="info-row">
                    <span class="info-label">Razão Social:</span>
                    <span class="info-value">{{ strtoupper($tenant->name) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">CNPJ:</span>
                    <span class="info-value">{{ preg_replace('/[^0-9]/', '', $tenant->cnpj ?? 'N/A') }}</span>
                </div>
                @if($tenant->address)
                <div class="info-row">
                    <span class="info-label">Endereço:</span>
                    <span class="info-value">{{ $tenant->address }}</span>
                </div>
                @endif
            </div>

            <!-- Informações do Colaborador -->
            <div class="section">
                <div class="section-title">👤 Colaborador</div>
                <div class="info-row">
                    <span class="info-label">Nome:</span>
                    <span class="info-value">{{ strtoupper($employee->name) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">CPF:</span>
                    <span class="info-value">{{ preg_replace('/[^0-9]/', '', $employee->cpf ?? 'N/A') }}</span>
                </div>
                @if($employee->registration_number)
                <div class="info-row">
                    <span class="info-label">Matrícula:</span>
                    <span class="info-value">{{ $employee->registration_number }}</span>
                </div>
                @endif
                @if($employee->position)
                <div class="info-row">
                    <span class="info-label">Cargo:</span>
                    <span class="info-value">{{ $employee->position }}</span>
                </div>
                @endif
            </div>

            <!-- Data e Hora do Registro -->
            <div class="section">
                <div class="section-title">🕐 Registro de Ponto</div>
                <div class="badge">{{ $receipt->action_name }}</div>

                <div class="datetime-box">
                    <div class="datetime-label">Horário Registrado</div>
                    <div class="datetime-value">{{ $markedAt->format('H:i:s') }}</div>
                    <div class="datetime-date">{{ $markedAt->locale('pt_BR')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}</div>
                </div>

                <div class="info-row">
                    <span class="info-label">Data:</span>
                    <span class="info-value">{{ $markedAt->format('d/m/Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Hora:</span>
                    <span class="info-value">{{ $markedAt->format('H:i:s') }}</span>
                </div>
                @if($receipt->ip_address)
                <div class="info-row">
                    <span class="info-label">IP:</span>
                    <span class="info-value">{{ $receipt->ip_address }}</span>
                </div>
                @endif
            </div>

            <!-- Geolocalização (se disponível) -->
            @if($receipt->gps_latitude && $receipt->gps_longitude)
            <div class="section">
                <div class="section-title">📍 Localização</div>
                <div class="location-info">
                    <strong>GPS:</strong> {{ $receipt->formatted_location }}<br>
                    <strong>Coordenadas:</strong>
                    {{ number_format($receipt->gps_latitude, 6, ',', '.') }},
                    {{ number_format($receipt->gps_longitude, 6, ',', '.') }}
                </div>
            </div>
            @endif

            <!-- Código Autenticador -->
            <div class="section">
                <div class="section-title">🔐 Autenticação</div>
                <div class="authenticator-box">
                    <div class="authenticator-label">CÓDIGO AUTENTICADOR ÚNICO</div>
                    <div class="authenticator-code">{{ $authenticatorCode }}</div>
                </div>
                <p style="font-size: 10px; color: #6b7280; text-align: center; margin-top: 10px;">
                    Use este código para validar a autenticidade deste comprovante
                </p>
            </div>

            <!-- QR Code Placeholder -->
            <div class="qr-placeholder">
                QR Code
                <br>
                {{ substr($authenticatorCode, 0, 8) }}
            </div>
        </div>

        <div class="footer">
            <div class="footer-text">
                <strong>Comprovante disponível até:</strong>
                {{ $receipt->available_until->format('d/m/Y H:i') }}<br>
                <strong>UUID:</strong> {{ $receipt->uuid }}<br>
                <strong>Gerado em:</strong> {{ $receipt->created_at->format('d/m/Y H:i:s') }}
            </div>

            <div class="legal-note">
                Este documento possui validade legal conforme Portaria MTP nº 671, de 8 de novembro de 2021.<br>
                Sistema: Next Ponto | Empresa: {{ $tenant->name }}<br>
                A autenticidade deste documento pode ser verificada através do código autenticador.
            </div>
        </div>
    </div>
</body>
</html>
