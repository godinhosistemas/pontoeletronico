# 🔐 Reconhecimento Facial - Guia de Integração

## ✅ O que foi implementado

1. **✅ Biblioteca face-api.js** - Downloaded em `public/face-api.min.js`
2. **✅ Módulo de Reconhecimento** - `public/js/face-recognition.js`
3. **✅ API Backend** - 3 novos endpoints no `PwaClockController`
4. **✅ Banco de Dados** - Campo `face_descriptor` na tabela `employees`
5. **✅ Rotas API** - Cadastro e validação facial

## 📋 Como Integrar no PWA (`clock.blade.php`)

### 1. Adicionar Scripts no `<head>` da página

```html
<!-- Face API -->
<script defer src="/face-api.min.js"></script>
<script defer src="/js/face-recognition.js"></script>
```

### 2. Adicionar Canvas de Detecção (após o vídeo)

Procure por `<video id="video"` e adicione logo após:

```html
<video id="video" autoplay playsinline style="display: none;"></video>

<!-- Canvas para desenhar detecção facial -->
<canvas id="face-canvas" style="position: absolute; top: 0; left: 0;"></canvas>

<canvas id="canvas"></canvas>
```

### 3. Adicionar Status de Reconhecimento Facial

Adicione dentro do `#video-container`:

```html
<div class="face-status" id="face-status" style="display: none;">
    <div class="face-status-icon" id="face-status-icon">👤</div>
    <div class="face-status-text" id="face-status-text">Detectando rosto...</div>
</div>
```

E o CSS:

```css
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
```

### 4. JavaScript - Inicializar Face Recognition

Adicione no final do `<script>`, após a função `updateDateTime()`:

```javascript
// ====================
// RECONHECIMENTO FACIAL
// ====================

let faceRecognitionEnabled = false;
let employeeFaceDescriptor = null;
let faceValidated = false;

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

// Atualiza a função startCamera() para incluir detecção facial
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
        };
    } catch (error) {
        console.error('Erro ao acessar câmera:', error);
        playErrorSound();
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

// Atualiza função captureAndRegister para incluir validação facial
async function captureAndRegister(action) {
    // Verifica se o rosto foi validado
    if (faceRecognitionEnabled && !faceValidated) {
        alert('⚠️ Aguarde a validação facial antes de registrar o ponto!');
        return;
    }

    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const context = canvas.getContext('2d');

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;

    context.save();
    context.scale(-1, 1);
    context.drawImage(video, -canvas.width, 0, canvas.width, canvas.height);
    context.restore();

    canvas.toBlob(async (blob) => {
        const formData = new FormData();
        formData.append('photo', blob, 'face.jpg');
        formData.append('employee_id', currentEmployee.id);
        formData.append('action', action);

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
                playCameraShutterSound();
                setTimeout(() => {
                    resetToInitialScreen();
                }, 1000);
            } else {
                playErrorSound();
                alert(data.message || 'Erro ao registrar ponto');
            }
        } catch (error) {
            console.error('Erro ao registrar:', error);
            playErrorSound();
        }
    }, 'image/jpeg', 0.9);
}

// Atualiza resetToInitialScreen para parar detecção
function resetToInitialScreen() {
    // Para detecção facial
    stopFaceDetection();

    // Para a câmera
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
    }

    // Reseta variáveis
    currentEmployee = null;
    currentCode = '';
    faceValidated = false;

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
```

## 🚀 Como Testar

1. **Acesse o PWA**: `http://localhost:8000/pwa/clock`
2. **Digite o código do funcionário**: Ex: 936923
3. **Aguarde a detecção facial**: Box verde aparecerá ao redor do rosto
4. **Primeiro uso**: Rosto será cadastrado automaticamente
5. **Próximos usos**: Sistema validará se é o mesmo rosto
6. **Registre o ponto**: Clique em "Registrar Entrada"

## 📊 Endpoints API Criados

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| POST | `/api/pwa/save-face-descriptor` | Cadastra descritor facial do funcionário |
| POST | `/api/pwa/validate-face` | Valida se o rosto detectado corresponde ao funcionário |

## 🔒 Segurança Implementada

1. **Liveness Detection**: Detecta expressões faciais
2. **Threshold 0.6**: Similaridade mínima de 60%
3. **Confiança Mínima**: 50% de confiança na detecção
4. **Descritor 128D**: "Impressão digital" facial única

## 🎯 Próximas Melhorias

- [ ] Liveness Detection avançado (piscar olhos)
- [ ] Múltiplos rostos cadastrados
- [ ] Histórico de tentativas de fraude
- [ ] Dashboard de reconhecimentos
- [ ] Alertas de tentativa de acesso não autorizado
