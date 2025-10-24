<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="theme-color" content="#1e3a8a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Meus Comprovantes">

    <title>Meus Comprovantes - Login</title>

    <link rel="manifest" href="/manifest.json">
    <link rel="icon" type="image/png" sizes="192x192" href="/images/icon-192x192.png">
    <link rel="apple-touch-icon" href="/images/icon-192x192.png">

    @vite(['resources/css/app.css'])

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(180deg, #0f172a 0%, #1e3a8a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 400px;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo {
            font-size: 48px;
            margin-bottom: 10px;
        }

        .title {
            font-size: 32px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 8px;
        }

        .subtitle {
            font-size: 16px;
            color: #cbd5e1;
        }

        .card {
            background: #fff;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .form-group {
            margin-bottom: 24px;
        }

        .label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }

        .code-display {
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin-bottom: 24px;
        }

        .code-input {
            font-size: 42px;
            font-weight: 700;
            color: #1f2937;
            font-family: 'Courier New', monospace;
            letter-spacing: 8px;
            min-height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .numpad {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }

        .numpad-btn {
            aspect-ratio: 1;
            border: none;
            border-radius: 12px;
            background: linear-gradient(145deg, #f3f4f6, #e5e7eb);
            color: #1f2937;
            font-size: 32px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            user-select: none;
        }

        .numpad-btn:active {
            transform: scale(0.95);
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
        }

        .numpad-btn.clear {
            background: linear-gradient(145deg, #fee2e2, #fecaca);
            color: #dc2626;
            font-size: 24px;
        }

        .numpad-btn.confirm {
            background: linear-gradient(145deg, #10b981, #059669);
            color: #fff;
            font-size: 24px;
        }

        .btn-primary {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(145deg, #2563eb, #1e40af);
            color: #fff;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.5);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .loading.show {
            display: block;
        }

        .spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 4px solid rgba(37, 99, 235, 0.2);
            border-radius: 50%;
            border-top-color: #2563eb;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .error {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: none;
        }

        .error.show {
            display: block;
        }

        .footer {
            text-align: center;
            margin-top: 24px;
            color: #cbd5e1;
            font-size: 12px;
        }

        .info-box {
            background: #dbeafe;
            border-left: 4px solid #3b82f6;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
        }

        .info-box p {
            font-size: 14px;
            color: #1e40af;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">📋</div>
            <h1 class="title">Meus Comprovantes</h1>
            <p class="subtitle">Acesse seus registros de ponto</p>
        </div>

        <div class="card">
            <div class="info-box">
                <p><strong>Digite seu código de acesso</strong><br>
                Use o mesmo código que você utiliza para registrar o ponto</p>
            </div>

            <div id="error" class="error"></div>

            <form id="loginForm" onsubmit="return false;">
                <div class="form-group">
                    <label class="label">Código de Acesso</label>
                    <div class="code-display">
                        <div class="code-input" id="codeDisplay">______</div>
                    </div>
                </div>

                <div class="numpad">
                    <button type="button" class="numpad-btn" onclick="addDigit('1')">1</button>
                    <button type="button" class="numpad-btn" onclick="addDigit('2')">2</button>
                    <button type="button" class="numpad-btn" onclick="addDigit('3')">3</button>
                    <button type="button" class="numpad-btn" onclick="addDigit('4')">4</button>
                    <button type="button" class="numpad-btn" onclick="addDigit('5')">5</button>
                    <button type="button" class="numpad-btn" onclick="addDigit('6')">6</button>
                    <button type="button" class="numpad-btn" onclick="addDigit('7')">7</button>
                    <button type="button" class="numpad-btn" onclick="addDigit('8')">8</button>
                    <button type="button" class="numpad-btn" onclick="addDigit('9')">9</button>
                    <button type="button" class="numpad-btn clear" onclick="clearCode()">✕</button>
                    <button type="button" class="numpad-btn" onclick="addDigit('0')">0</button>
                    <button type="button" class="numpad-btn confirm" onclick="login()">→</button>
                </div>

                <button type="button" class="btn-primary" id="loginBtn" onclick="login()">
                    ACESSAR COMPROVANTES
                </button>
            </form>

            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p style="margin-top: 12px; color: #6b7280;">Autenticando...</p>
            </div>
        </div>

        <div class="footer">
            <p>Next Ponto - Sistema de Ponto Eletrônico<br>
            Conforme Portaria MTP nº 671/2021</p>
        </div>
    </div>

    <script>
        let currentCode = '';
        const maxLength = 6;

        function addDigit(digit) {
            if (currentCode.length < maxLength) {
                currentCode += digit;
                updateDisplay();
            }
        }

        function clearCode() {
            currentCode = '';
            updateDisplay();
            hideError();
        }

        function updateDisplay() {
            const display = currentCode.padEnd(maxLength, '_');
            document.getElementById('codeDisplay').textContent = display;
        }

        function showError(message) {
            const errorEl = document.getElementById('error');
            errorEl.textContent = message;
            errorEl.classList.add('show');
        }

        function hideError() {
            const errorEl = document.getElementById('error');
            errorEl.classList.remove('show');
        }

        function showLoading() {
            document.getElementById('loginForm').style.display = 'none';
            document.getElementById('loading').classList.add('show');
        }

        function hideLoading() {
            document.getElementById('loginForm').style.display = 'block';
            document.getElementById('loading').classList.remove('show');
        }

        async function login() {
            if (currentCode.length === 0) {
                showError('Digite seu código de acesso');
                return;
            }

            hideError();
            showLoading();

            try {
                const response = await fetch('{{ route('employee.pwa.authenticate') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        unique_code: currentCode
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // Redireciona para o dashboard
                    window.location.href = '{{ route('employee.pwa.dashboard') }}?token=' + encodeURIComponent(data.session_token);
                } else {
                    hideLoading();
                    showError(data.message || 'Código inválido. Tente novamente.');
                    clearCode();
                }
            } catch (error) {
                hideLoading();
                showError('Erro ao fazer login. Verifique sua conexão e tente novamente.');
                console.error('Erro:', error);
            }
        }

        // Permite usar Enter para fazer login
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                login();
            }
        });
    </script>
</body>
</html>
