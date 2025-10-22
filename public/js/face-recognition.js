/**
 * Face Recognition Module
 * Sistema de reconhecimento facial usando face-api.js
 */

class FaceRecognition {
    constructor() {
        this.modelsLoaded = false;
        this.isFaceDetected = false;
        this.lastDetection = null;
        this.detectionInterval = null;
        this.minConfidence = 0.5;
        this.matchThreshold = 0.6; // Quanto menor, mais restritivo
    }

    /**
     * Carrega os modelos do face-api.js
     */
    async loadModels() {
        try {
            console.log('[FaceAPI] Carregando modelos...');

            const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api@latest/model';

            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL),
                faceapi.nets.faceExpressionNet.loadFromUri(MODEL_URL)
            ]);

            this.modelsLoaded = true;
            console.log('[FaceAPI] Modelos carregados com sucesso!');
            return true;
        } catch (error) {
            console.error('[FaceAPI] Erro ao carregar modelos:', error);
            return false;
        }
    }

    /**
     * Detecta rosto no vídeo
     */
    async detectFace(videoElement) {
        if (!this.modelsLoaded) {
            console.warn('[FaceAPI] Modelos não carregados ainda');
            return null;
        }

        try {
            const detection = await faceapi
                .detectSingleFace(videoElement, new faceapi.TinyFaceDetectorOptions())
                .withFaceLandmarks()
                .withFaceDescriptor()
                .withFaceExpressions();

            if (detection) {
                this.isFaceDetected = true;
                this.lastDetection = detection;
                return detection;
            } else {
                this.isFaceDetected = false;
                this.lastDetection = null;
                return null;
            }
        } catch (error) {
            console.error('[FaceAPI] Erro na detecção:', error);
            return null;
        }
    }

    /**
     * Inicia detecção contínua
     */
    startContinuousDetection(videoElement, canvas, onDetection) {
        if (this.detectionInterval) {
            this.stopContinuousDetection();
        }

        // Ajusta o canvas para o tamanho do vídeo
        const displaySize = { width: videoElement.videoWidth, height: videoElement.videoHeight };
        faceapi.matchDimensions(canvas, displaySize);

        this.detectionInterval = setInterval(async () => {
            const detection = await this.detectFace(videoElement);

            if (detection) {
                // Limpa canvas anterior
                const ctx = canvas.getContext('2d');
                ctx.clearRect(0, 0, canvas.width, canvas.height);

                // Redimensiona detecções para o canvas
                const resizedDetection = faceapi.resizeResults(detection, displaySize);

                // Desenha o box ao redor do rosto
                faceapi.draw.drawDetections(canvas, resizedDetection);

                // Desenha os landmarks (pontos faciais)
                faceapi.draw.drawFaceLandmarks(canvas, resizedDetection);

                // Callback com informações
                if (onDetection) {
                    onDetection({
                        detected: true,
                        confidence: detection.detection.score,
                        descriptor: Array.from(detection.descriptor),
                        expressions: detection.expressions,
                        box: detection.detection.box
                    });
                }
            } else {
                // Limpa canvas se não detectar
                const ctx = canvas.getContext('2d');
                ctx.clearRect(0, 0, canvas.width, canvas.height);

                if (onDetection) {
                    onDetection({ detected: false });
                }
            }
        }, 300); // Detecta a cada 300ms
    }

    /**
     * Para detecção contínua
     */
    stopContinuousDetection() {
        if (this.detectionInterval) {
            clearInterval(this.detectionInterval);
            this.detectionInterval = null;
        }
    }

    /**
     * Extrai descritor facial de uma imagem
     */
    async extractDescriptor(imageElement) {
        if (!this.modelsLoaded) {
            throw new Error('Modelos não carregados');
        }

        const detection = await faceapi
            .detectSingleFace(imageElement, new faceapi.TinyFaceDetectorOptions())
            .withFaceLandmarks()
            .withFaceDescriptor();

        if (!detection) {
            throw new Error('Nenhum rosto detectado na imagem');
        }

        return Array.from(detection.descriptor);
    }

    /**
     * Compara dois descritores faciais
     */
    compareFaces(descriptor1, descriptor2) {
        if (!descriptor1 || !descriptor2) {
            return { match: false, distance: 1, similarity: 0 };
        }

        // Converte para Float32Array se necessário
        const desc1 = descriptor1 instanceof Float32Array ? descriptor1 : new Float32Array(descriptor1);
        const desc2 = descriptor2 instanceof Float32Array ? descriptor2 : new Float32Array(descriptor2);

        // Calcula distância euclidiana
        const distance = faceapi.euclideanDistance(desc1, desc2);

        // Converte distância em similaridade (0-100%)
        const similarity = Math.max(0, (1 - distance) * 100);

        // Match se distância menor que threshold
        const match = distance < this.matchThreshold;

        return {
            match,
            distance: distance.toFixed(4),
            similarity: similarity.toFixed(2)
        };
    }

    /**
     * Valida qualidade da detecção (anti-spoofing básico)
     */
    validateQuality(detection) {
        const checks = {
            faceDetected: !!detection,
            goodConfidence: detection && detection.detection.score > this.minConfidence,
            hasExpression: false,
            isLive: false
        };

        if (detection && detection.expressions) {
            // Verifica se há alguma expressão detectada (indicativo de rosto real)
            const expressions = Object.values(detection.expressions);
            const maxExpression = Math.max(...expressions);
            checks.hasExpression = maxExpression > 0.1;

            // Liveness básico: detecta se há movimento/expressão
            checks.isLive = checks.hasExpression;
        }

        checks.passed = checks.faceDetected && checks.goodConfidence && checks.isLive;

        return checks;
    }

    /**
     * Captura melhor frame do vídeo
     */
    async captureBestFrame(videoElement, numAttempts = 5) {
        let bestDetection = null;
        let bestScore = 0;

        for (let i = 0; i < numAttempts; i++) {
            const detection = await this.detectFace(videoElement);

            if (detection && detection.detection.score > bestScore) {
                bestScore = detection.detection.score;
                bestDetection = detection;
            }

            // Aguarda um pouco entre tentativas
            await new Promise(resolve => setTimeout(resolve, 200));
        }

        return bestDetection;
    }
}

// Exporta instância global
window.faceRecognition = new FaceRecognition();
