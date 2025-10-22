<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="theme-color" content="#1e3a8a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Ponto Digital">

    <title>Ponto Digital - Registro Facial</title>

    <link rel="manifest" href="/manifest.json">
    <link rel="icon" type="image/png" sizes="192x192" href="/images/icon-192x192.png">
    <link rel="apple-touch-icon" href="/images/icon-192x192.png">

    @vite(['resources/css/app.css'])

    <!-- Face Recognition API -->
    <script defer src="/face-api.min.js"></script>
    <script defer src="/js/face-recognition.js"></script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f172a;
            min-height: 100vh;
            overflow: hidden;
        }

        #app {
            display: flex;
            height: 100vh;
            width: 100vw;
        }

        /* LADO ESQUERDO - CÂMERA */
        #left-panel {
            flex: 0 0 60%;
            position: relative;
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #video-container {
            position: relative;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scaleX(-1);
        }

        #canvas {
            display: none;
        }

        /* Linha guia horizontal */
        .face-guide {
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 2px;
            background: #ef4444;
            transform: translateY(-50%);
            box-shadow: 0 0 10px rgba(239, 68, 68, 0.8);
            z-index: 10;
        }

        /* Mensagem na parte inferior da câmera */
        .camera-message {
            position: absolute;
            bottom: 20px;
            left: 0;
            right: 0;
            text-align: center;
            background: rgba(251, 191, 36, 0.95);
            padding: 12px 20px;
            color: #000;
            font-size: 18px;
            font-weight: 600;
            letter-spacing: 0.5px;
            z-index: 10;
        }

        /* LADO DIREITO - PAINEL DE CONTROLE */
        #right-panel {
            flex: 0 0 40%;
            background: linear-gradient(180deg, #1e3a8a 0%, #0f172a 100%);
            display: flex;
            flex-direction: column;
            padding: 30px;
            position: relative;
        }

        /* Logo e Header */
        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo {
            font-size: 42px;
            font-weight: 700;
            color: #fff;
            text-transform: lowercase;
            letter-spacing: 2px;
            margin-bottom: 10px;
            text-shadow: 0 0 20px rgba(59, 130, 246, 0.5);
        }

        .datetime {
            font-size: 24px;
            color: #cbd5e1;
            font-weight: 500;
        }

        /* Teclado Numérico */
        .numpad-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            max-width: 400px;
            margin: 0 auto;
            width: 100%;
        }

        .numpad {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .numpad-btn {
            aspect-ratio: 1;
            border: none;
            border-radius: 12px;
            background: linear-gradient(145deg, #2563eb, #1e40af);
            color: #fff;
            font-size: 48px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.4);
            user-select: none;
        }

        .numpad-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.6);
        }

        .numpad-btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 10px rgba(37, 99, 235, 0.4);
        }

        .numpad-btn.clear {
            background: linear-gradient(145deg, #dc2626, #991b1b);
            font-size: 36px;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.4);
        }

        .numpad-btn.confirm {
            background: linear-gradient(145deg, #16a34a, #15803d);
            font-size: 36px;
            box-shadow: 0 4px 15px rgba(22, 163, 74, 0.4);
        }

        .numpad-btn.clear:hover {
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.6);
        }

        .numpad-btn.confirm:hover {
            box-shadow: 0 6px 20px rgba(22, 163, 74, 0.6);
        }

        /* Display do código digitado */
        .code-display {
            background: rgba(15, 23, 42, 0.8);
            border: 2px solid #334155;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
        }

        .code-input {
            font-size: 36px;
            color: #fff;
            font-weight: 600;
            letter-spacing: 8px;
            font-family: 'Courier New', monospace;
            min-height: 50px;
        }

        /* Versão */
        .version {
            text-align: center;
            color: #64748b;
            font-size: 14px;
            margin-top: 10px;
        }

        /* Painel de ações (quando validado) */
        #action-panel {
            display: none;
            flex-direction: column;
            gap: 15px;
            max-width: 400px;
            margin: 0 auto;
            width: 100%;
        }

        .employee-card {
            background: rgba(15, 23, 42, 0.8);
            border: 2px solid #334155;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .employee-name {
            font-size: 24px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 5px;
        }

        .employee-info {
            font-size: 14px;
            color: #94a3b8;
        }

        .action-btn {
            padding: 18px;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            color: #fff;
        }

        .action-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .action-btn.entrada {
            background: linear-gradient(145deg, #16a34a, #15803d);
        }

        .action-btn.almoco {
            background: linear-gradient(145deg, #eab308, #ca8a04);
        }

        .action-btn.saida {
            background: linear-gradient(145deg, #dc2626, #991b1b);
        }

        .action-btn:not(:disabled):hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        .action-btn:not(:disabled):active {
            transform: translateY(0);
        }

        /* Registro de hoje */
        .today-entry {
            background: rgba(15, 23, 42, 0.8);
            border: 2px solid #334155;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .today-entry h4 {
            color: #cbd5e1;
            font-size: 14px;
            margin-bottom: 10px;
            text-align: center;
        }

        .entry-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .entry-item {
            text-align: center;
            padding: 8px;
            background: rgba(30, 58, 138, 0.3);
            border-radius: 8px;
        }

        .entry-label {
            font-size: 11px;
            color: #94a3b8;
            margin-bottom: 3px;
        }

        .entry-time {
            font-size: 16px;
            font-weight: 700;
            color: #fff;
        }

        .logout-btn {
            padding: 12px;
            background: rgba(220, 38, 38, 0.2);
            border: 1px solid #dc2626;
            color: #fca5a5;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 10px;
        }

        .logout-btn:hover {
            background: rgba(220, 38, 38, 0.3);
        }

        /* Loading */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Placeholder para câmera */
        .camera-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #64748b;
            font-size: 24px;
            gap: 20px;
        }

        .camera-icon {
            font-size: 80px;
        }

        /* Face Recognition Status */
        .face-status {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(0, 0, 0, 0.8);
            padding: 15px 20px;
            border-radius: 10px;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 20;
        }

        .face-status-icon {
            font-size: 24px;
        }

        .face-status-text {
            font-size: 14px;
            font-weight: 600;
        }

        .face-detected {
            background: rgba(22, 163, 74, 0.9) !important;
        }

        .face-not-detected {
            background: rgba(220, 38, 38, 0.9) !important;
        }

        #face-canvas {
            position: absolute;
            top: 0;
            left: 0;
            z-index: 5;
        }

        /* GPS Status */
        .gps-status {
            position: absolute;
            top: 70px;
            left: 20px;
            background: rgba(0, 0, 0, 0.8);
            padding: 12px 16px;
            border-radius: 10px;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 20;
        }

        .gps-status-icon {
            font-size: 20px;
        }

        .gps-status-text {
            font-size: 12px;
            font-weight: 600;
        }

        .gps-validated {
            background: rgba(22, 163, 74, 0.9) !important;
        }

        .gps-not-validated {
            background: rgba(220, 38, 38, 0.9) !important;
        }

        .gps-pending {
            background: rgba(234, 179, 8, 0.9) !important;
        }

        /* Responsivo */
        @media (max-width: 1024px) {
            #app {
                flex-direction: column;
            }

            #left-panel, #right-panel {
                flex: 0 0 50%;
            }
        }

        @media (orientation: portrait) {
            #app {
                flex-direction: column;
            }

            #left-panel {
                flex: 0 0 40%;
            }

            #right-panel {
                flex: 0 0 60%;
            }
        }
    </style>
</head>
<body>
    <div id="app">
        <!-- PAINEL ESQUERDO - CÂMERA -->
        <div id="left-panel">
            <div id="video-container">
                <div class="camera-placeholder" id="camera-placeholder">
                    <div class="camera-icon">📷</div>
                    <div>Aguardando validação...</div>
                </div>
                <video id="video" autoplay playsinline style="display: none;"></video>

                <!-- Canvas para desenhar detecção facial -->
                <canvas id="face-canvas" style="position: absolute; top: 0; left: 0;"></canvas>

                <canvas id="canvas"></canvas>
                <div class="face-guide" id="face-guide" style="display: none;"></div>
                <div class="camera-message" id="camera-message" style="display: none;">
                    APROXIME SEU CARTÃO DE IDENTIFICAÇÃO
                </div>

                <!-- Status de Reconhecimento Facial -->
                <div class="face-status" id="face-status" style="display: none;">
                    <div class="face-status-icon" id="face-status-icon">👤</div>
                    <div class="face-status-text" id="face-status-text">Detectando rosto...</div>
                </div>

                <!-- Status de Geolocalização -->
                <div class="gps-status" id="gps-status" style="display: none;">
                    <div class="gps-status-icon" id="gps-status-icon">📍</div>
                    <div class="gps-status-text" id="gps-status-text">Aguardando GPS...</div>
                </div>
            </div>
        </div>

        <!-- PAINEL DIREITO - CONTROLE -->
        <div id="right-panel">
            <div class="header">
                <div class="logo">Next Ponto</div>
                <div class="datetime" id="datetime">00/00/0000 00:00</div>
            </div>

            <!-- TECLADO NUMÉRICO -->
            <div class="numpad-container" id="numpad-container">
                <div class="code-display">
                    <div class="code-input" id="code-display">______</div>
                </div>

                <div class="numpad">
                    <button class="numpad-btn" onclick="addDigit('1')">1</button>
                    <button class="numpad-btn" onclick="addDigit('2')">2</button>
                    <button class="numpad-btn" onclick="addDigit('3')">3</button>
                    <button class="numpad-btn" onclick="addDigit('4')">4</button>
                    <button class="numpad-btn" onclick="addDigit('5')">5</button>
                    <button class="numpad-btn" onclick="addDigit('6')">6</button>
                    <button class="numpad-btn" onclick="addDigit('7')">7</button>
                    <button class="numpad-btn" onclick="addDigit('8')">8</button>
                    <button class="numpad-btn" onclick="addDigit('9')">9</button>
                    <button class="numpad-btn clear" onclick="clearCode()">✕</button>
                    <button class="numpad-btn" onclick="addDigit('0')">0</button>
                    <button class="numpad-btn confirm" onclick="validateCode()">→</button>
                </div>

                <div class="version">Next Ponto V1.1a</div>
            </div>

            <!-- PAINEL DE AÇÕES (após validação) -->
            <div id="action-panel">
                <div class="employee-card">
                    <div class="employee-name" id="employee-name">-</div>
                    <div class="employee-info" id="employee-info">-</div>
                </div>

                <div class="today-entry" id="today-entry" style="display: none;">
                    <h4>REGISTRO DE HOJE</h4>
                    <div class="entry-grid">
                        <div class="entry-item">
                            <div class="entry-label">Entrada</div>
                            <div class="entry-time" id="entry-clock-in">--:--</div>
                        </div>
                        <div class="entry-item">
                            <div class="entry-label">Saída</div>
                            <div class="entry-time" id="entry-clock-out">--:--</div>
                        </div>
                        <div class="entry-item">
                            <div class="entry-label">Início Almoço</div>
                            <div class="entry-time" id="entry-lunch-start">--:--</div>
                        </div>
                        <div class="entry-item">
                            <div class="entry-label">Fim Almoço</div>
                            <div class="entry-time" id="entry-lunch-end">--:--</div>
                        </div>
                    </div>
                </div>

                <button class="action-btn entrada" id="btn-clock-in" onclick="captureAndRegister('clock_in')">
                    ✓ REGISTRAR ENTRADA
                </button>
                <button class="action-btn almoco" id="btn-lunch-start" onclick="captureAndRegister('lunch_start')" disabled>
                    ⏸ INICIAR ALMOÇO
                </button>
                <button class="action-btn almoco" id="btn-lunch-end" onclick="captureAndRegister('lunch_end')" disabled>
                    ▶ FIM ALMOÇO
                </button>
                <button class="action-btn saida" id="btn-clock-out" onclick="captureAndRegister('clock_out')" disabled>
                    ✓ REGISTRAR SAÍDA
                </button>

                <button class="logout-btn" onclick="logout()">
                    ← SAIR / TROCAR USUÁRIO
                </button>
            </div>
        </div>
    </div>

    <script>
        let stream = null;
        let currentEmployee = null;
        let currentCode = '';

        // Cria contexto de áudio para sinais sonoros
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();

        // Função para reproduzir som de flash de câmera
        function playCameraShutterSound() {
            const now = audioContext.currentTime;

            // Som mecânico do obturador (inicial)
            const noise1 = audioContext.createOscillator();
            const noiseGain1 = audioContext.createGain();
            const noiseFilter1 = audioContext.createBiquadFilter();

            noise1.type = 'square';
            noise1.frequency.value = 200;
            noiseFilter1.type = 'bandpass';
            noiseFilter1.frequency.value = 800;

            noise1.connect(noiseFilter1);
            noiseFilter1.connect(noiseGain1);
            noiseGain1.connect(audioContext.destination);

            noiseGain1.gain.setValueAtTime(0.4, now);
            noiseGain1.gain.exponentialRampToValueAtTime(0.01, now + 0.05);

            noise1.start(now);
            noise1.stop(now + 0.05);

            // Som do flash (brilhante)
            const flash = audioContext.createOscillator();
            const flashGain = audioContext.createGain();

            flash.type = 'sine';
            flash.frequency.setValueAtTime(1800, now + 0.02);
            flash.frequency.exponentialRampToValueAtTime(2400, now + 0.08);

            flash.connect(flashGain);
            flashGain.connect(audioContext.destination);

            flashGain.gain.setValueAtTime(0, now + 0.02);
            flashGain.gain.linearRampToValueAtTime(0.35, now + 0.04);
            flashGain.gain.exponentialRampToValueAtTime(0.01, now + 0.25);

            flash.start(now + 0.02);
            flash.stop(now + 0.25);

            // Som mecânico do obturador (final)
            setTimeout(() => {
                const noise2 = audioContext.createOscillator();
                const noiseGain2 = audioContext.createGain();
                const noiseFilter2 = audioContext.createBiquadFilter();

                noise2.type = 'square';
                noise2.frequency.value = 180;
                noiseFilter2.type = 'bandpass';
                noiseFilter2.frequency.value = 700;

                noise2.connect(noiseFilter2);
                noiseFilter2.connect(noiseGain2);
                noiseGain2.connect(audioContext.destination);

                noiseGain2.gain.setValueAtTime(0.3, audioContext.currentTime);
                noiseGain2.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.04);

                noise2.start(audioContext.currentTime);
                noise2.stop(audioContext.currentTime + 0.04);
            }, 180);
        }

        // Função para reproduzir som de sucesso (validação de código)
        function playSuccessSound() {
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            oscillator.frequency.value = 800;
            oscillator.type = 'sine';

            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);

            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.5);

            // Segundo tom
            setTimeout(() => {
                const osc2 = audioContext.createOscillator();
                const gain2 = audioContext.createGain();

                osc2.connect(gain2);
                gain2.connect(audioContext.destination);

                osc2.frequency.value = 1000;
                osc2.type = 'sine';

                gain2.gain.setValueAtTime(0.3, audioContext.currentTime);
                gain2.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);

                osc2.start(audioContext.currentTime);
                osc2.stop(audioContext.currentTime + 0.5);
            }, 100);
        }

        // Função para reproduzir som de erro
        function playErrorSound() {
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            oscillator.frequency.value = 300;
            oscillator.type = 'sawtooth';

            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);

            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.5);
        }

        // Atualiza data/hora
        function updateDateTime() {
            const now = new Date();
            const date = now.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' });
            const time = now.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
            document.getElementById('datetime').textContent = `${date} ${time}`;
        }
        setInterval(updateDateTime, 1000);
        updateDateTime();

        // ====================
        // RECONHECIMENTO FACIAL
        // ====================

        let faceRecognitionEnabled = false;
        let employeeFaceDescriptor = null;
        let faceValidated = false;

        // ====================
        // GEOLOCALIZAÇÃO
        // ====================

        let gpsEnabled = false;
        let gpsValidated = false;
        let currentPosition = null;
        let watchId = null;

        // Carrega modelos quando a página carregar
        window.addEventListener('load', async () => {
            try {
                console.log('[Face] Carregando modelos...');
                const loaded = await window.faceRecognition.loadModels();
                if (loaded) {
                    faceRecognitionEnabled = true;
                    console.log('[Face] Reconhecimento facial ativado!');
                }
            } catch (error) {
                console.error('[Face] Erro ao carregar:', error);
                faceRecognitionEnabled = false;
            }
        });

        // Adiciona dígito
        function addDigit(digit) {
            if (currentCode.length < 6) {
                currentCode += digit;
                updateDisplay();
            }
        }

        // Limpa código
        function clearCode() {
            currentCode = '';
            updateDisplay();
        }

        // Atualiza display
        function updateDisplay() {
            const display = currentCode.padEnd(6, '_');
            document.getElementById('code-display').textContent = display;
        }

        // Valida código único
        async function validateCode() {
            if (currentCode.length === 0) {
                return;
            }

            try {
                const response = await fetch('/api/pwa/validate-code', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ unique_code: currentCode })
                });

                const data = await response.json();

                if (data.success) {
                    // Som de sucesso na validação
                    playSuccessSound();

                    currentEmployee = data.employee;
                    showEmployeePanel(data.employee);
                    await loadTodayEntry();
                    await startCamera();
                } else {
                    // Som de erro na validação
                    playErrorSound();
                    clearCode();
                }
            } catch (error) {
                console.error('Erro ao validar código:', error);
                // Som de erro
                playErrorSound();
                clearCode();
            }
        }

        // Mostra painel do funcionário
        function showEmployeePanel(employee) {
            document.getElementById('numpad-container').style.display = 'none';
            document.getElementById('action-panel').style.display = 'flex';

            document.getElementById('employee-name').textContent = employee.name;
            document.getElementById('employee-info').textContent =
                `${employee.position || 'Sem cargo'} • Matrícula: ${employee.registration_number}`;
        }

        // Formata hora para exibição (HH:MM)
        function formatTime(timeString) {
            if (!timeString) return '--:--';

            // Se for uma string de hora (HH:MM:SS ou HH:MM)
            if (typeof timeString === 'string') {
                // Remove segundos se existir
                const timePart = timeString.split(' ').pop(); // Pega apenas a parte da hora se vier com data
                return timePart.substring(0, 5);
            }

            return '--:--';
        }

        // Carrega registro de hoje
        async function loadTodayEntry() {
            try {
                const response = await fetch(`/api/pwa/today-entry/${currentEmployee.id}`, {
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();

                if (data.entry) {
                    document.getElementById('today-entry').style.display = 'block';
                    document.getElementById('entry-clock-in').textContent = formatTime(data.entry.clock_in);
                    document.getElementById('entry-clock-out').textContent = formatTime(data.entry.clock_out);
                    document.getElementById('entry-lunch-start').textContent = formatTime(data.entry.lunch_start);
                    document.getElementById('entry-lunch-end').textContent = formatTime(data.entry.lunch_end);

                    updateButtonStates(data.entry);
                }
            } catch (error) {
                console.error('Erro ao carregar registro:', error);
            }
        }

        // Atualiza estado dos botões
        function updateButtonStates(entry) {
            const btnClockIn = document.getElementById('btn-clock-in');
            const btnLunchStart = document.getElementById('btn-lunch-start');
            const btnLunchEnd = document.getElementById('btn-lunch-end');
            const btnClockOut = document.getElementById('btn-clock-out');

            if (entry.clock_in) {
                btnClockIn.disabled = true;
                btnLunchStart.disabled = false;
            }

            if (entry.lunch_start) {
                btnLunchStart.disabled = true;
                btnLunchEnd.disabled = false;
            }

            if (entry.lunch_end) {
                btnLunchEnd.disabled = true;
            }

            if (entry.clock_in && !entry.clock_out) {
                btnClockOut.disabled = false;
            }

            if (entry.clock_out) {
                btnClockOut.disabled = true;
            }
        }

        // Inicia câmera
        async function startCamera() {
            try {
                stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: 'user',
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    }
                });

                const video = document.getElementById('video');
                video.srcObject = stream;
                video.style.display = 'block';

                document.getElementById('camera-placeholder').style.display = 'none';
                document.getElementById('face-guide').style.display = 'block';
                document.getElementById('camera-message').style.display = 'block';

                // Aguarda vídeo estar pronto
                video.onloadedmetadata = () => {
                    // Inicia detecção facial se habilitado
                    if (faceRecognitionEnabled && currentEmployee) {
                        startFaceDetection();
                    }

                    // Inicia monitoramento GPS
                    startGpsTracking();
                };
            } catch (error) {
                console.error('Erro ao acessar câmera:', error);
                // Som de erro
                playErrorSound();
                // Volta para tela inicial após 2 segundos
                setTimeout(() => {
                    resetToInitialScreen();
                }, 2000);
            }
        }

        // Inicia detecção facial contínua
        function startFaceDetection() {
            if (!faceRecognitionEnabled) return;

            const video = document.getElementById('video');
            const canvas = document.getElementById('face-canvas');
            const faceStatus = document.getElementById('face-status');

            faceStatus.style.display = 'flex';

            window.faceRecognition.startContinuousDetection(video, canvas, async (result) => {
                const statusIcon = document.getElementById('face-status-icon');
                const statusText = document.getElementById('face-status-text');

                if (result.detected) {
                    faceStatus.classList.add('face-detected');
                    faceStatus.classList.remove('face-not-detected');
                    statusIcon.textContent = '✅';
                    statusText.textContent = `Rosto detectado (${(result.confidence * 100).toFixed(0)}%)`;

                    // Se ainda não validou, valida o rosto
                    if (!faceValidated && result.descriptor) {
                        await validateEmployeeFace(result.descriptor);
                    }
                } else {
                    faceStatus.classList.remove('face-detected');
                    faceStatus.classList.add('face-not-detected');
                    statusIcon.textContent = '❌';
                    statusText.textContent = 'Nenhum rosto detectado';
                    faceValidated = false;
                }
            });
        }

        // Para detecção facial
        function stopFaceDetection() {
            if (window.faceRecognition) {
                window.faceRecognition.stopContinuousDetection();
            }
            const faceStatus = document.getElementById('face-status');
            if (faceStatus) {
                faceStatus.style.display = 'none';
            }
        }

        // Valida rosto do funcionário
        async function validateEmployeeFace(descriptor) {
            try {
                const response = await fetch('/api/pwa/validate-face', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        employee_id: currentEmployee.id,
                        descriptor: descriptor
                    })
                });

                const data = await response.json();

                if (data.match) {
                    faceValidated = true;
                    console.log(`[Face] ✅ Validado! Similaridade: ${data.similarity}%`);

                    // Atualiza status visual
                    const statusText = document.getElementById('face-status-text');
                    statusText.textContent = `Rosto reconhecido (${data.similarity}% match)`;

                    // Som de sucesso
                    playSuccessSound();
                } else if (data.needs_registration) {
                    // Funcionário não tem rosto cadastrado - cadastra automaticamente
                    console.log('[Face] Cadastrando rosto pela primeira vez...');
                    await saveFaceDescriptor(descriptor);
                } else {
                    console.warn(`[Face] ❌ Rosto não reconhecido. Similaridade: ${data.similarity}%`);
                    faceValidated = false;
                }
            } catch (error) {
                console.error('[Face] Erro na validação:', error);
            }
        }

        // Salva descritor facial (primeiro uso)
        async function saveFaceDescriptor(descriptor) {
            try {
                const response = await fetch('/api/pwa/save-face-descriptor', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        employee_id: currentEmployee.id,
                        descriptor: descriptor
                    })
                });

                const data = await response.json();

                if (data.success) {
                    console.log('[Face] ✅ Descritor facial cadastrado!');
                    faceValidated = true;
                    playSuccessSound();

                    const statusText = document.getElementById('face-status-text');
                    statusText.textContent = 'Rosto cadastrado com sucesso!';
                }
            } catch (error) {
                console.error('[Face] Erro ao salvar descritor:', error);
            }
        }

        // ====================
        // FUNÇÕES DE GEOLOCALIZAÇÃO
        // ====================

        // Inicia monitoramento de GPS
        function startGpsTracking() {
            if (!navigator.geolocation) {
                console.warn('[GPS] Geolocalização não suportada');
                return;
            }

            gpsEnabled = true;
            console.log('[GPS] Iniciando monitoramento...');

            // Configurações de alta precisão
            const options = {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            };

            // Monitora posição continuamente
            watchId = navigator.geolocation.watchPosition(
                (position) => {
                    currentPosition = {
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy
                    };

                    console.log('[GPS] Posição atualizada:', currentPosition);

                    // Valida automaticamente se exigido
                    if (currentEmployee && currentEmployee.require_geolocation) {
                        validateGeolocation();
                    }
                },
                (error) => {
                    console.error('[GPS] Erro:', error.message);
                    gpsEnabled = false;
                    gpsValidated = false;
                },
                options
            );
        }

        // Para monitoramento de GPS
        function stopGpsTracking() {
            if (watchId !== null) {
                navigator.geolocation.clearWatch(watchId);
                watchId = null;
                currentPosition = null;
                gpsEnabled = false;
                gpsValidated = false;
                hideGpsStatus();
                console.log('[GPS] Monitoramento parado');
            }
        }

        // Valida geolocalização
        async function validateGeolocation() {
            if (!currentPosition) {
                console.warn('[GPS] Posição não disponível');
                return;
            }

            try {
                const response = await fetch('/api/pwa/validate-geolocation', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        employee_id: currentEmployee.id,
                        latitude: currentPosition.latitude,
                        longitude: currentPosition.longitude,
                        accuracy: currentPosition.accuracy
                    })
                });

                const data = await response.json();

                gpsValidated = data.validated;

                console.log('[GPS] Validação:', data);

                // Atualiza feedback visual
                updateGpsStatus(data);

            } catch (error) {
                console.error('[GPS] Erro na validação:', error);
                gpsValidated = false;
            }
        }

        // Atualiza status visual do GPS
        function updateGpsStatus(data) {
            const gpsStatus = document.getElementById('gps-status');
            const gpsIcon = document.getElementById('gps-status-icon');
            const gpsText = document.getElementById('gps-status-text');

            if (!gpsStatus) return;

            // Mostra o status
            gpsStatus.style.display = 'flex';

            // Remove classes anteriores
            gpsStatus.classList.remove('gps-validated', 'gps-not-validated', 'gps-pending');

            if (data.validated) {
                gpsStatus.classList.add('gps-validated');
                gpsIcon.textContent = '✅';
                gpsText.textContent = `Localização OK (${data.distance}m)`;
                console.log(`[GPS] ✅ ${data.message}`);
            } else if (data.distance !== null) {
                gpsStatus.classList.add('gps-not-validated');
                gpsIcon.textContent = '❌';
                gpsText.textContent = `Fora do local (${data.distance}m)`;
                console.warn(`[GPS] ❌ ${data.message}`);
            } else {
                gpsStatus.classList.add('gps-pending');
                gpsIcon.textContent = '📍';
                gpsText.textContent = 'Aguardando GPS...';
            }
        }

        // Esconde status GPS
        function hideGpsStatus() {
            const gpsStatus = document.getElementById('gps-status');
            if (gpsStatus) {
                gpsStatus.style.display = 'none';
            }
        }

        // Captura foto e registra ponto
        async function captureAndRegister(action) {
            // Verifica se o rosto foi validado
            if (faceRecognitionEnabled && !faceValidated) {
                alert('⚠️ Aguarde a validação facial antes de registrar o ponto!');
                return;
            }

            // Verifica se a geolocalização foi validada (se exigida)
            if (currentEmployee.require_geolocation && !gpsValidated) {
                alert('⚠️ Aguarde a validação de localização antes de registrar o ponto!');
                return;
            }

            const video = document.getElementById('video');
            const canvas = document.getElementById('canvas');
            const context = canvas.getContext('2d');

            // Define dimensões do canvas
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;

            // Captura frame (inverte horizontalmente para corrigir o espelhamento)
            context.save();
            context.scale(-1, 1);
            context.drawImage(video, -canvas.width, 0, canvas.width, canvas.height);
            context.restore();

            // Converte para blob
            canvas.toBlob(async (blob) => {
                const formData = new FormData();
                formData.append('photo', blob, 'face.jpg');
                formData.append('employee_id', currentEmployee.id);
                formData.append('action', action);

                // Adiciona dados GPS se disponíveis
                if (currentPosition) {
                    formData.append('latitude', currentPosition.latitude);
                    formData.append('longitude', currentPosition.longitude);
                    formData.append('gps_accuracy', currentPosition.accuracy);
                }

                try {
                    const response = await fetch('/api/pwa/register-clock', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Reproduz som de flash de câmera
                        playCameraShutterSound();

                        // Aguarda 1 segundo e volta para tela inicial
                        setTimeout(() => {
                            resetToInitialScreen();
                        }, 1000);
                    } else {
                        // Reproduz som de erro
                        playErrorSound();
                    }
                } catch (error) {
                    console.error('Erro ao registrar:', error);
                    // Reproduz som de erro
                    playErrorSound();
                }
            }, 'image/jpeg', 0.9);
        }

        // Reseta para tela inicial
        function resetToInitialScreen() {
            // Para detecção facial
            stopFaceDetection();

            // Para GPS tracking
            stopGpsTracking();

            // Para a câmera
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
            }

            // Reseta variáveis
            currentEmployee = null;
            currentCode = '';
            faceValidated = false;
            gpsValidated = false;

            // Esconde vídeo e mostra placeholder
            document.getElementById('video').style.display = 'none';
            document.getElementById('camera-placeholder').style.display = 'flex';
            document.getElementById('face-guide').style.display = 'none';
            document.getElementById('camera-message').style.display = 'none';

            // Esconde painel de ações e mostra teclado
            document.getElementById('action-panel').style.display = 'none';
            document.getElementById('numpad-container').style.display = 'flex';

            // Limpa display
            updateDisplay();
        }

        // Logout
        function logout() {
            resetToInitialScreen();
        }

        // Registra Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(reg => console.log('Service Worker registrado:', reg))
                    .catch(err => console.log('Erro ao registrar Service Worker:', err));
            });
        }
    </script>
</body>
</html>
