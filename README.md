# AI 키오스크 프로젝트

Windows 노트북에서 실행되는 AI 키오스크 시스템입니다.

## 주요 기능

1. **AI 챗봇** - Ollama + Phi-3 Mini로 대화
2. **이미지 생성** - Stable Diffusion으로 AI 이미지 생성
3. **카메라 포즈 인식** - MediaPipe로 포즈 감지 후 ControlNet으로 이미지 합성

## 빠른 시작

### 1. 자동 설치 (PowerShell)

```powershell
cd C:\xampp\htdocs\ai_test_sec
.\install.ps1
```

### 2. 수동 설치

자세한 설치 가이드는 [SETUP_GUIDE.md](SETUP_GUIDE.md)를 참고하세요.

### 3. Ollama 설치 및 모델 다운로드

```powershell
# Ollama 설치 (https://ollama.ai/download)
winget install Ollama.Ollama

# Phi-3 Mini 모델 다운로드
ollama pull phi3:mini

# Ollama 서비스 확인
ollama list
```

### 4. Stable Diffusion WebUI 설치

```powershell
# SD WebUI 첫 실행
cd sd-webui
.\webui-user.bat
```

**webui-user.bat 편집 (저사양 최적화):**
```batch
set COMMANDLINE_ARGS=--api --xformers --medvram --opt-split-attention
```

### 5. 모델 다운로드

#### SD Turbo 모델 (4GB)
- 다운로드: https://huggingface.co/stabilityai/sd-turbo/resolve/main/sd_turbo.safetensors
- 위치: `sd-webui\models\Stable-diffusion\sd_turbo.safetensors`

#### ControlNet OpenPose 모델 (2.8GB)
- 다운로드: https://huggingface.co/lllyasviel/ControlNet-v1-1/resolve/main/control_v11p_sd15_openpose.pth
- 위치: `sd-webui\extensions\sd-webui-controlnet\models\control_v11p_sd15_openpose.pth`

### 6. ControlNet 확장 설치

```powershell
cd sd-webui\extensions
git clone https://github.com/Mikubill/sd-webui-controlnet.git
```

### 7. 서비스 실행

#### Terminal 1: Stable Diffusion WebUI
```powershell
cd C:\xampp\htdocs\ai_test_sec\sd-webui
.\webui-user.bat
```

#### Terminal 2: Apache (XAMPP Control Panel에서 실행)
- XAMPP Control Panel 열기
- Apache Start 클릭

### 8. 접속

브라우저에서 http://localhost/ai_test_sec/ 접속

## 테스트

```powershell
.\test_api.ps1
```

## 디렉토리 구조

```
C:\xampp\htdocs\ai_test_sec\
├── venv/                      # Python 가상환경
├── sd-webui/                  # Stable Diffusion WebUI
│   ├── models/
│   │   └── Stable-diffusion/
│   │       └── sd_turbo.safetensors
│   └── extensions/
│       └── sd-webui-controlnet/
│           └── models/
│               └── control_v11p_sd15_openpose.pth
├── scripts/                   # Python 스크립트
│   ├── camera_detect.py       # 포즈 감지
│   └── image_generate.py      # 이미지 생성
├── api/                       # PHP API
│   └── ai_service.php         # 통합 API
├── js/                        # JavaScript
│   └── kiosk.js               # 클라이언트
├── css/                       # 스타일
│   └── style.css
├── uploads/                   # 업로드된 이미지
├── outputs/                   # 생성된 이미지
├── index.html                 # 메인 페이지
├── requirements.txt           # Python 패키지
├── install.ps1                # 설치 스크립트
├── test_api.ps1               # 테스트 스크립트
├── README.md                  # 이 파일
└── SETUP_GUIDE.md             # 상세 설치 가이드
```

## API 엔드포인트

### 헬스 체크
```
GET /api/ai_service.php?action=health
```

### 챗봇
```
POST /api/ai_service.php
action=chat
message=안녕하세요
```

### 포즈 감지
```
POST /api/ai_service.php
action=detect_pose
image=<base64_image>
```

### 이미지 생성 (단순)
```
POST /api/ai_service.php
action=generate_image
prompt=a beautiful landscape
mode=simple
```

### 이미지 생성 (포즈 기반)
```
POST /api/ai_service.php
action=pose_image
image=<base64_image>
prompt=a superhero in action pose
```

## 사용 가이드

### 챗봇 사용
1. 챗봇 탭 클릭
2. 메시지 입력 후 전송
3. AI 응답 확인

### 포즈 기반 이미지 생성
1. **카메라 탭**에서:
   - 카메라 시작
   - 포즈를 취하고 사진 촬영
2. **이미지 생성 탭**에서:
   - 프롬프트 입력 (예: "a superhero in action pose")
   - "포즈 기반 이미지 생성" 클릭
   - 결과 확인 (감지된 포즈 + 생성된 이미지)

### 단순 이미지 생성
1. 이미지 생성 탭
2. 프롬프트 입력
3. "텍스트로 이미지 생성" 클릭

## 프롬프트 예시

- `a professional businessman in suit, office background`
- `a cute cat wearing glasses, cartoon style`
- `a beautiful anime girl, standing pose, detailed, high quality`
- `a superhero in action pose, dynamic lighting, dramatic scene`

## 시스템 요구사항

- **OS**: Windows 10/11
- **RAM**: 8-16GB
- **디스크**: 약 15GB (모델 포함)
- **Python**: 3.10 이상
- **Git**: 최신 버전
- **XAMPP**: Apache + PHP

## 총 용량

- Ollama + Phi-3 Mini: 2.3GB
- SD 1.5 Turbo: 4GB
- ControlNet OpenPose: 2.8GB
- OpenCV + MediaPipe: 150MB
- SD WebUI: 약 2GB
- 기타: 약 1GB
- **총합**: 약 12-13GB

## 문제 해결

### Ollama 연결 실패
- Ollama 서비스가 실행 중인지 확인
- `ollama serve` 명령으로 수동 실행

### SD WebUI API 오류
- SD WebUI가 `--api` 플래그로 실행되었는지 확인
- http://localhost:7860 접속 확인

### Python 패키지 오류
```powershell
.\venv\Scripts\Activate.ps1
pip install -r requirements.txt --upgrade
```

### 포즈 감지 실패
- 전신이 보이도록 촬영
- 조명이 충분한 환경에서 촬영
- 배경이 단순한 환경 권장

### 이미지 생성 느림
- GPU가 없으면 CPU로 실행되어 느릴 수 있음
- `--lowvram` 또는 `--medvram` 옵션 사용
- 생성 스텝 수를 줄임 (기본 8)

## 라이선스

이 프로젝트는 교육 및 개인 사용 목적으로 제공됩니다.

## 크레딧

- [Ollama](https://ollama.ai/) - LLM 실행 엔진
- [Stable Diffusion WebUI](https://github.com/AUTOMATIC1111/stable-diffusion-webui) - 이미지 생성
- [MediaPipe](https://google.github.io/mediapipe/) - 포즈 감지
- [ControlNet](https://github.com/lllyasviel/ControlNet) - 이미지 제어
