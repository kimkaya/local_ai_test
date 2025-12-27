# AI 키오스크 설치 가이드 (Windows)

## 시스템 요구사항
- Windows 10/11
- 8-16GB RAM
- 약 15GB 디스크 공간 (모델 포함)
- Python 3.10 이상
- Git
- XAMPP (Apache + PHP)

---

## 1단계: Ollama + Phi-3 Mini 설치

### Ollama 설치
```powershell
# Ollama 다운로드 및 설치
winget install Ollama.Ollama
# 또는 https://ollama.ai/download 에서 수동 다운로드
```

### Phi-3 Mini 모델 다운로드 (2.3GB)
```powershell
# Ollama 실행 후
ollama pull phi3:mini
```

### 테스트
```powershell
# 테스트 실행
ollama run phi3:mini "안녕하세요"

# API 서버 실행 (백그라운드)
# Ollama는 자동으로 localhost:11434에서 실행됩니다
```

---

## 2단계: Python 환경 설정

```powershell
# 현재 디렉토리로 이동
cd C:\xampp\htdocs\ai_test_sec

# Python 가상환경 생성
python -m venv venv

# 가상환경 활성화
.\venv\Scripts\Activate.ps1

# pip 업그레이드
python -m pip install --upgrade pip
```

---

## 3단계: Stable Diffusion WebUI 설치

```powershell
# Git으로 클론
git clone https://github.com/AUTOMATIC1111/stable-diffusion-webui.git sd-webui
cd sd-webui

# 첫 실행 (필수 패키지 자동 설치)
# GPU 없으면 --skip-torch-cuda-test 추가
.\webui-user.bat

# 첫 실행 후 Ctrl+C로 종료
```

### SD 1.5 Turbo 모델 다운로드 (4GB)
```powershell
# models/Stable-diffusion 폴더에 다운로드
cd models\Stable-diffusion

# PowerShell로 다운로드
Invoke-WebRequest -Uri "https://huggingface.co/stabilityai/sd-turbo/resolve/main/sd_turbo.safetensors" -OutFile "sd_turbo.safetensors"

# 또는 수동 다운로드:
# https://huggingface.co/stabilityai/sd-turbo/tree/main
```

### 저사양 최적화 설정
`webui-user.bat` 파일 수정:
```batch
@echo off

set PYTHON=
set GIT=
set VENV_DIR=
set COMMANDLINE_ARGS=--api --xformers --medvram --opt-split-attention

call webui.bat
```

---

## 4단계: ControlNet 확장 설치

```powershell
# sd-webui 디렉토리에서
cd extensions
git clone https://github.com/Mikubill/sd-webui-controlnet.git

# ControlNet OpenPose 모델 다운로드 (2.8GB)
cd sd-webui-controlnet\models
Invoke-WebRequest -Uri "https://huggingface.co/lllyasviel/ControlNet-v1-1/resolve/main/control_v11p_sd15_openpose.pth" -OutFile "control_v11p_sd15_openpose.pth"
```

---

## 5단계: OpenCV + MediaPipe 설치

```powershell
# 프로젝트 루트로 돌아가기
cd C:\xampp\htdocs\ai_test_sec

# 가상환경 활성화
.\venv\Scripts\Activate.ps1

# 패키지 설치
pip install opencv-python mediapipe pillow requests numpy
```

---

## 6단계: SD WebUI 실행 (API 모드)

```powershell
cd C:\xampp\htdocs\ai_test_sec\sd-webui

# API 모드로 실행
.\webui-user.bat

# 브라우저에서 확인: http://localhost:7860
# API 문서: http://localhost:7860/docs
```

---

## 7단계: Apache/PHP 설정

### PHP 확장 활성화
`C:\xampp\php\php.ini` 파일에서 다음 주석 제거:
```ini
extension=curl
extension=mbstring
extension=openssl
```

### Apache 재시작
XAMPP Control Panel에서 Apache Stop → Start

---

## 실행 순서

1. **Ollama 실행** (자동 시작됨 - localhost:11434)
2. **SD WebUI 실행**:
   ```powershell
   cd C:\xampp\htdocs\ai_test_sec\sd-webui
   .\webui-user.bat
   ```
3. **Apache 실행** (XAMPP Control Panel)
4. **브라우저 열기**: http://localhost/ai_test_sec/

---

## 포트 정보
- Ollama: localhost:11434
- SD WebUI: localhost:7860
- Apache (키오스크): localhost:80

---

## 문제 해결

### Python 가상환경 활성화 오류
```powershell
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
```

### GPU 메모리 부족
`webui-user.bat`에 `--lowvram` 추가

### 모델 다운로드 실패
직접 브라우저에서 다운로드 후 해당 폴더에 복사

---

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
│   ├── camera_detect.py
│   └── image_generate.py
├── api/                       # PHP API
│   └── ai_service.php
├── js/                        # JavaScript
│   └── kiosk.js
├── css/                       # 스타일
│   └── style.css
├── uploads/                   # 업로드된 이미지
├── outputs/                   # 생성된 이미지
└── index.html                 # 메인 페이지
```
