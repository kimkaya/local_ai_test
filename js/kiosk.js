/**
 * AI 키오스크 클라이언트
 * - 챗봇 인터페이스
 * - 웹캠 캡처 및 포즈 감지
 * - 이미지 생성
 */

const API_URL = '/ai_test_sec/api/ai_service.php';

class AIKiosk {
    constructor() {
        this.videoStream = null;
        this.currentTab = 'chat';
        this.capturedImage = null;

        // 음성 인식 관련
        this.recognition = null;
        this.isListening = false;
        this.synthesis = window.speechSynthesis;
        this.isSpeaking = false;

        this.init();
    }

    init() {
        this.setupEventListeners();
        this.initVoiceRecognition();
        this.checkHealth();
        this.switchTab('chat');
    }

    setupEventListeners() {
        // 탭 전환
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const tab = e.target.dataset.tab;
                this.switchTab(tab);
            });
        });

        // 챗봇
        document.getElementById('chat-send').addEventListener('click', () => this.sendChat());
        document.getElementById('chat-input').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this.sendChat();
        });

        // 카메라
        document.getElementById('camera-start').addEventListener('click', () => this.startCamera());
        document.getElementById('camera-stop').addEventListener('click', () => this.stopCamera());
        document.getElementById('camera-capture').addEventListener('click', () => this.captureImage());

        // 이미지 생성
        document.getElementById('generate-simple').addEventListener('click', () => this.generateSimpleImage());
        document.getElementById('generate-pose').addEventListener('click', () => this.generatePoseImage());

        // 음성 제어
        document.getElementById('voice-input-btn').addEventListener('click', () => this.toggleVoiceInput());
        document.getElementById('voice-output-btn').addEventListener('click', () => this.toggleVoiceOutput());
    }

    switchTab(tab) {
        this.currentTab = tab;

        // 탭 버튼 활성화
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.tab === tab) {
                btn.classList.add('active');
            }
        });

        // 탭 컨텐츠 표시
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });
        document.getElementById(`${tab}-tab`).classList.add('active');

        // 카메라 탭이 아니면 카메라 중지
        if (tab !== 'camera' && this.videoStream) {
            this.stopCamera();
        }
    }

    async checkHealth() {
        try {
            const response = await fetch(`${API_URL}?action=health`);
            const data = await response.json();

            const statusDiv = document.getElementById('service-status');
            if (data.success) {
                const services = data.services;
                statusDiv.innerHTML = `
                    <div class="status-item ${services.ollama ? 'ok' : 'error'}">
                        Ollama: ${services.ollama ? '✓' : '✗'}
                    </div>
                    <div class="status-item ${services.stable_diffusion ? 'ok' : 'error'}">
                        SD WebUI: ${services.stable_diffusion ? '✓' : '✗'}
                    </div>
                    <div class="status-item ${services.python ? 'ok' : 'error'}">
                        Python: ${services.python ? '✓' : '✗'}
                    </div>
                `;
            } else {
                statusDiv.innerHTML = '<div class="status-item error">서비스 상태 확인 실패</div>';
            }
        } catch (error) {
            console.error('Health check failed:', error);
        }
    }

    // === 챗봇 기능 ===

    async sendChat() {
        const input = document.getElementById('chat-input');
        const message = input.value.trim();

        if (!message) return;

        this.addChatMessage('user', message);
        input.value = '';

        this.showLoading('chat-messages', '답변 생성 중...');

        try {
            const formData = new FormData();
            formData.append('action', 'chat');
            formData.append('message', message);

            const response = await fetch(API_URL, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            this.hideLoading('chat-messages');

            if (data.success) {
                this.addChatMessage('bot', data.message);
                // 음성 출력이 활성화되어 있으면 응답을 읽어줌
                this.speakText(data.message);
            } else {
                this.addChatMessage('bot', `오류: ${data.error}`);
            }
        } catch (error) {
            this.hideLoading('chat-messages');
            this.addChatMessage('bot', `오류: ${error.message}`);
        }
    }

    addChatMessage(role, message) {
        const messagesDiv = document.getElementById('chat-messages');
        const messageDiv = document.createElement('div');
        messageDiv.className = `chat-message ${role}`;
        messageDiv.textContent = message;
        messagesDiv.appendChild(messageDiv);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }

    // === 음성 인식/합성 기능 ===

    initVoiceRecognition() {
        // Web Speech API 지원 확인
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

        if (!SpeechRecognition) {
            console.warn('음성 인식이 지원되지 않는 브라우저입니다.');
            return;
        }

        this.recognition = new SpeechRecognition();
        this.recognition.lang = 'ko-KR'; // 한국어 설정
        this.recognition.continuous = false; // 연속 인식 비활성화
        this.recognition.interimResults = false; // 중간 결과 비활성화

        // 음성 인식 시작 이벤트
        this.recognition.onstart = () => {
            this.isListening = true;
            this.updateVoiceInputButton();
            console.log('음성 인식 시작');
        };

        // 음성 인식 결과
        this.recognition.onresult = (event) => {
            const transcript = event.results[0][0].transcript;
            console.log('인식된 텍스트:', transcript);

            // 채팅 입력창에 텍스트 설정 및 전송
            const input = document.getElementById('chat-input');
            input.value = transcript;
            this.sendChat();
        };

        // 음성 인식 종료
        this.recognition.onend = () => {
            this.isListening = false;
            this.updateVoiceInputButton();
            console.log('음성 인식 종료');
        };

        // 에러 처리
        this.recognition.onerror = (event) => {
            console.error('음성 인식 오류:', event.error);
            this.isListening = false;
            this.updateVoiceInputButton();

            let errorMsg = '음성 인식 오류가 발생했습니다.';
            if (event.error === 'no-speech') {
                errorMsg = '음성이 감지되지 않았습니다. 다시 시도해주세요.';
            } else if (event.error === 'audio-capture') {
                errorMsg = '마이크에 접근할 수 없습니다.';
            } else if (event.error === 'not-allowed') {
                errorMsg = '마이크 권한이 거부되었습니다.';
            }

            this.addChatMessage('bot', errorMsg);
        };
    }

    toggleVoiceInput() {
        if (!this.recognition) {
            alert('이 브라우저는 음성 인식을 지원하지 않습니다.');
            return;
        }

        if (this.isListening) {
            this.recognition.stop();
        } else {
            this.recognition.start();
        }
    }

    updateVoiceInputButton() {
        const btn = document.getElementById('voice-input-btn');
        if (this.isListening) {
            btn.textContent = '🎤 음성 인식 중...';
            btn.classList.add('listening');
        } else {
            btn.textContent = '🎤 음성 입력';
            btn.classList.remove('listening');
        }
    }

    toggleVoiceOutput() {
        const btn = document.getElementById('voice-output-btn');

        // 현재 재생 중인 음성이 있으면 중지
        if (this.synthesis.speaking) {
            this.synthesis.cancel();
        }

        // 음성 출력 활성화 상태 토글
        this.isSpeaking = !this.isSpeaking;

        if (this.isSpeaking) {
            btn.textContent = '🔊 음성 출력 ON';
            btn.classList.add('speaking');
        } else {
            btn.textContent = '🔊 음성 출력';
            btn.classList.remove('speaking');
        }
    }

    async speakText(text) {
        // 음성 출력이 비활성화되어 있으면 실행하지 않음
        if (!this.isSpeaking) return;

        try {
            // 로컬 TTS API 호출
            const formData = new FormData();
            formData.append('action', 'tts');
            formData.append('text', text);

            const response = await fetch(API_URL, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success && data.audio_url) {
                // 오디오 파일 재생
                const audio = new Audio(data.audio_url);

                audio.onplay = () => {
                    console.log('음성 출력 시작:', text);
                };

                audio.onended = () => {
                    console.log('음성 출력 완료');
                };

                audio.onerror = (event) => {
                    console.error('음성 재생 오류:', event);
                    // 오류 시 Web Speech API로 폴백
                    this.speakTextFallback(text);
                };

                await audio.play();
            } else {
                // TTS API 실패 시 Web Speech API로 폴백
                console.warn('TTS API 실패, Web Speech API 사용:', data.error);
                this.speakTextFallback(text);
            }
        } catch (error) {
            console.error('TTS 오류:', error);
            // 오류 시 Web Speech API로 폴백
            this.speakTextFallback(text);
        }
    }

    speakTextFallback(text) {
        // Web Speech API를 사용한 폴백
        this.synthesis.cancel();

        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'ko-KR';
        utterance.rate = 1.0;
        utterance.pitch = 1.0;
        utterance.volume = 1.0;

        utterance.onstart = () => {
            console.log('Web Speech API 음성 출력 시작');
        };

        utterance.onend = () => {
            console.log('Web Speech API 음성 출력 완료');
        };

        utterance.onerror = (event) => {
            console.error('Web Speech API 오류:', event.error);
        };

        this.synthesis.speak(utterance);
    }

    // === 카메라 기능 ===

    async startCamera() {
        try {
            this.videoStream = await navigator.mediaDevices.getUserMedia({
                video: { width: 640, height: 480 }
            });

            const video = document.getElementById('camera-preview');
            video.srcObject = this.videoStream;
            video.play();

            document.getElementById('camera-start').disabled = true;
            document.getElementById('camera-stop').disabled = false;
            document.getElementById('camera-capture').disabled = false;

            this.showMessage('camera-status', '카메라 시작됨', 'success');
        } catch (error) {
            this.showMessage('camera-status', `카메라 오류: ${error.message}`, 'error');
        }
    }

    stopCamera() {
        if (this.videoStream) {
            this.videoStream.getTracks().forEach(track => track.stop());
            this.videoStream = null;

            const video = document.getElementById('camera-preview');
            video.srcObject = null;

            document.getElementById('camera-start').disabled = false;
            document.getElementById('camera-stop').disabled = true;
            document.getElementById('camera-capture').disabled = true;

            this.showMessage('camera-status', '카메라 중지됨', 'info');
        }
    }

    captureImage() {
        const video = document.getElementById('camera-preview');
        const canvas = document.getElementById('camera-canvas');
        const ctx = canvas.getContext('2d');

        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        ctx.drawImage(video, 0, 0);

        this.capturedImage = canvas.toDataURL('image/jpeg', 0.9);

        // 캡처된 이미지 표시
        const capturedDiv = document.getElementById('captured-image');
        capturedDiv.innerHTML = `<img src="${this.capturedImage}" alt="Captured" style="max-width: 100%;">`;

        this.showMessage('camera-status', '이미지 캡처 완료', 'success');
    }

    // === 이미지 생성 기능 ===

    async generateSimpleImage() {
        const prompt = document.getElementById('prompt-input').value.trim();

        if (!prompt) {
            this.showMessage('generation-status', '프롬프트를 입력하세요', 'error');
            return;
        }

        this.showLoading('generation-result', '이미지 생성 중... (약 10-30초 소요)');

        try {
            const formData = new FormData();
            formData.append('action', 'generate_image');
            formData.append('prompt', prompt);
            formData.append('mode', 'simple');

            const response = await fetch(API_URL, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            this.hideLoading('generation-result');

            if (data.success) {
                document.getElementById('generation-result').innerHTML = `
                    <img src="${data.image_url}?t=${Date.now()}" alt="Generated" style="max-width: 100%;">
                `;
                this.showMessage('generation-status', '이미지 생성 완료', 'success');
            } else {
                this.showMessage('generation-status', `오류: ${data.error}`, 'error');
            }
        } catch (error) {
            this.hideLoading('generation-result');
            this.showMessage('generation-status', `오류: ${error.message}`, 'error');
        }
    }

    async generatePoseImage() {
        const prompt = document.getElementById('prompt-input').value.trim();

        if (!prompt) {
            this.showMessage('generation-status', '프롬프트를 입력하세요', 'error');
            return;
        }

        if (!this.capturedImage) {
            this.showMessage('generation-status', '먼저 카메라에서 사진을 촬영하세요', 'error');
            return;
        }

        this.showLoading('generation-result', '포즈 감지 및 이미지 생성 중... (약 20-40초 소요)');

        try {
            const formData = new FormData();
            formData.append('action', 'pose_image');
            formData.append('prompt', prompt);
            formData.append('image', this.capturedImage);

            const response = await fetch(API_URL, {
                method: 'POST',
                body: formData
            });

            // 응답 텍스트 먼저 확인
            const responseText = await response.text();
            console.log('Server response:', responseText);

            this.hideLoading('generation-result');

            // JSON 파싱 시도
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (e) {
                console.error('JSON parse error:', e);
                this.showMessage('generation-status', `서버 응답 오류: ${responseText.substring(0, 200)}`, 'error');
                return;
            }

            if (data.success) {
                document.getElementById('generation-result').innerHTML = `
                    <div class="result-grid">
                        <div>
                            <h4>감지된 포즈</h4>
                            <img src="${data.skeleton_url}?t=${Date.now()}" alt="Skeleton" style="max-width: 100%;">
                        </div>
                        <div>
                            <h4>생성된 이미지</h4>
                            <img src="${data.image_url}?t=${Date.now()}" alt="Generated" style="max-width: 100%;">
                        </div>
                    </div>
                `;
                this.showMessage('generation-status', '포즈 기반 이미지 생성 완료', 'success');
            } else {
                // 상세 에러 메시지 표시
                let errorMsg = data.error || '알 수 없는 오류';
                if (data.raw_output) {
                    console.error('Python output:', data.raw_output);
                    errorMsg += `\n\n상세: ${data.raw_output}`;
                }
                this.showMessage('generation-status', `오류: ${errorMsg}`, 'error');
            }
        } catch (error) {
            this.hideLoading('generation-result');
            console.error('Full error:', error);
            this.showMessage('generation-status', `오류: ${error.message}`, 'error');
        }
    }

    // === 유틸리티 ===

    showLoading(elementId, message) {
        const element = document.getElementById(elementId);
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'loading';
        loadingDiv.innerHTML = `
            <div class="spinner"></div>
            <p>${message}</p>
        `;
        loadingDiv.id = `${elementId}-loading`;
        element.appendChild(loadingDiv);
    }

    hideLoading(elementId) {
        const loadingDiv = document.getElementById(`${elementId}-loading`);
        if (loadingDiv) {
            loadingDiv.remove();
        }
    }

    showMessage(elementId, message, type = 'info') {
        const element = document.getElementById(elementId);
        element.className = `message ${type}`;
        element.textContent = message;
        element.style.display = 'block';

        setTimeout(() => {
            element.style.display = 'none';
        }, 5000);
    }
}

// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', () => {
    window.kiosk = new AIKiosk();
});
