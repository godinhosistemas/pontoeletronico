<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="theme-color" content="#1e3a8a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Meus Comprovantes - {{ $employee->name }}</title>

    @vite(['resources/css/app.css'])

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f3f4f6;
            min-height: 100vh;
            padding-bottom: 80px;
        }

        .header {
            background: linear-gradient(145deg, #1e3a8a, #1e40af);
            color: #fff;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .welcome {
            font-size: 14px;
            opacity: 0.9;
        }

        .employee-name {
            font-size: 24px;
            font-weight: 700;
            margin: 4px 0;
        }

        .employee-info {
            font-size: 13px;
            opacity: 0.8;
        }

        .logout-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: #fff;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
        }

        .container {
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }

        .month-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .month-title {
            font-size: 20px;
            font-weight: 700;
            color: #1f2937;
        }

        .month-count {
            background: #dbeafe;
            color: #1e40af;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .receipts-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .receipt-card {
            background: #fff;
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: all 0.2s ease;
            border-left: 4px solid;
        }

        .receipt-card:active {
            transform: scale(0.98);
        }

        .receipt-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .receipt-action {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .action-badge {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .action-name {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
        }

        .receipt-time {
            font-size: 24px;
            font-weight: 700;
            font-family: 'Courier New', monospace;
        }

        .receipt-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            font-size: 13px;
            color: #6b7280;
        }

        .receipt-detail {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .loading {
            text-align: center;
            padding: 40px 20px;
            color: #6b7280;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-icon {
            font-size: 64px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .empty-text {
            font-size: 18px;
            color: #6b7280;
            margin-bottom: 8px;
        }

        .empty-subtext {
            font-size: 14px;
            color: #9ca3af;
        }

        .error-banner {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-top">
            <div>
                <div class="welcome">Olá,</div>
                <div class="employee-name">{{ $employee->name }}</div>
                <div class="employee-info">
                    @if($employee->position) {{ $employee->position }} @endif
                    @if($employee->registration_number) • Mat. {{ $employee->registration_number }} @endif
                </div>
            </div>
            <button class="logout-btn" onclick="logout()">
                Sair
            </button>
        </div>
    </div>

    <div class="container">
        <div class="month-header">
            <h2 class="month-title">Registros de {{ now()->locale('pt_BR')->isoFormat('MMMM/YYYY') }}</h2>
            <span class="month-count" id="receiptCount">-</span>
        </div>

        <div id="error" class="error-banner" style="display: none;"></div>

        <div id="loading" class="loading">
            <div style="font-size: 40px; margin-bottom: 16px;">⏳</div>
            <div>Carregando seus comprovantes...</div>
        </div>

        <div id="emptyState" class="empty-state" style="display: none;">
            <div class="empty-icon">📋</div>
            <div class="empty-text">Nenhum registro este mês</div>
            <div class="empty-subtext">Seus comprovantes de ponto aparecerão aqui</div>
        </div>

        <div id="receiptsList" class="receipts-list"></div>
    </div>

    <script>
        const sessionToken = '{{ $sessionToken }}';
        let receipts = [];

        // Carrega os comprovantes ao abrir a página
        window.addEventListener('load', loadReceipts);

        async function loadReceipts() {
            try {
                const response = await fetch('{{ route('employee.api.receipts') }}?token=' + encodeURIComponent(sessionToken));
                const data = await response.json();

                if (data.success) {
                    receipts = data.receipts;
                    renderReceipts();
                } else {
                    showError(data.message || 'Erro ao carregar comprovantes');
                }
            } catch (error) {
                showError('Erro ao conectar com o servidor');
                console.error('Erro:', error);
            } finally {
                document.getElementById('loading').style.display = 'none';
            }
        }

        function renderReceipts() {
            const container = document.getElementById('receiptsList');
            const countEl = document.getElementById('receiptCount');

            if (receipts.length === 0) {
                document.getElementById('emptyState').style.display = 'block';
                countEl.textContent = '0 registros';
                return;
            }

            countEl.textContent = receipts.length + ' registro' + (receipts.length !== 1 ? 's' : '');

            // Agrupa por data
            const grouped = groupByDate(receipts);

            let html = '';

            Object.keys(grouped).forEach(date => {
                const dayReceipts = grouped[date];

                html += `
                    <div style="margin-bottom: 24px;">
                        <div style="font-size: 14px; font-weight: 600; color: #6b7280; margin-bottom: 12px; padding-left: 4px;">
                            ${formatDateHeader(dayReceipts[0].marked_at)}
                        </div>
                `;

                dayReceipts.forEach(receipt => {
                    const icon = getActionIcon(receipt.action);
                    const bgColor = receipt.action_color + '20';

                    html += `
                        <div class="receipt-card"
                             style="border-left-color: ${receipt.action_color};"
                             onclick="viewReceipt('${receipt.uuid}')">
                            <div class="receipt-header">
                                <div class="receipt-action">
                                    <div class="action-badge" style="background: ${bgColor};">
                                        ${icon}
                                    </div>
                                    <div>
                                        <div class="action-name">${receipt.action_name}</div>
                                        <div style="font-size: 12px; color: #9ca3af;">
                                            ${receipt.marked_date}
                                        </div>
                                    </div>
                                </div>
                                <div class="receipt-time" style="color: ${receipt.action_color};">
                                    ${receipt.marked_at_short}
                                </div>
                            </div>
                            <div class="receipt-details">
                                <div class="receipt-detail">
                                    🔐 ${receipt.authenticator_code.substring(0, 8)}...
                                </div>
                                ${receipt.has_gps ? `
                                    <div class="receipt-detail">
                                        📍 GPS Registrado
                                    </div>
                                ` : ''}
                                <div class="receipt-detail">
                                    ⏰ Válido até ${receipt.available_until}
                                </div>
                                <div class="receipt-detail" style="color: ${receipt.is_available ? '#10b981' : '#dc2626'};">
                                    ${receipt.is_available ? '✅ Disponível' : '❌ Expirado'}
                                </div>
                            </div>
                        </div>
                    `;
                });

                html += '</div>';
            });

            container.innerHTML = html;
        }

        function groupByDate(receipts) {
            return receipts.reduce((groups, receipt) => {
                const date = receipt.marked_date;
                if (!groups[date]) {
                    groups[date] = [];
                }
                groups[date].push(receipt);
                return groups;
            }, {});
        }

        function formatDateHeader(dateTimeString) {
            const date = new Date(dateTimeString.split(' ')[0].split('/').reverse().join('-'));
            const today = new Date();
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);

            if (date.toDateString() === today.toDateString()) {
                return 'Hoje';
            } else if (date.toDateString() === yesterday.toDateString()) {
                return 'Ontem';
            } else {
                const days = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
                return days[date.getDay()] + ', ' + dateTimeString.split(' ')[0];
            }
        }

        function getActionIcon(action) {
            const icons = {
                'clock_in': '🟢',
                'clock_out': '🔴',
                'lunch_start': '🟡',
                'lunch_end': '🔵'
            };
            return icons[action] || '⚪';
        }

        function viewReceipt(uuid) {
            window.location.href = `/employee/receipt/${uuid}`;
        }

        function showError(message) {
            const errorEl = document.getElementById('error');
            errorEl.textContent = message;
            errorEl.style.display = 'block';
        }

        function logout() {
            if (confirm('Deseja realmente sair?')) {
                window.location.href = '{{ route('employee.pwa.login') }}';
            }
        }

        // Atualiza automaticamente a cada 2 minutos
        setInterval(loadReceipts, 120000);
    </script>
</body>
</html>
