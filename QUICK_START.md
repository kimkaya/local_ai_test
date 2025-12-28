# AI 키오스크 빠른 시작 가이드

## 설치 완료 항목

- [x] Python 3.11 설치 완료
- [x] Python 가상환경 생성 완료
- [x] Python 패키지 설치 완료 (OpenCV, MediaPipe, Pillow, requests)
- [x] Ollama 설치 완료
- [x] Stable Diffusion WebUI 클론 완료
- [x] ControlNet 확장 설치 완료
- [x] SD WebUI 저사양 최적화 설정 완료

## 아직 필요한 작업

### 1. Phi-3 Mini 모델 다운로드

PowerShell을 **새로 열어서** 실행:

```powershell
ollama pull phi3:mini
```

첫 실행 시 Ollama 서비스가 자동으로 시작됩니다.

### 2. SD 1.5 Turbo 모델 다운로드 (4GB)

**옵션 A: PowerShell로 다운로드**
```powershell
cd C:\xampp\htdocs\ai_test_sec\sd-webui\models\Stable-diffusion

$url = "https://huggingface.co/stabilityai/sd-turbo/resolve/main/sd_turbo.safetensors"
Invoke-WebRequest -Uri $url -OutFile "sd_turbo.safetensors"
```

**옵션 B: 수동 다운로드**
1. 브라우저에서 https://huggingface.co/stabilityai/sd-turbo/tree/main 접속
2. `sd_turbo.safetensors` 파일 다운로드
3. `C:\xampp\htdocs\ai_test_sec\sd-webui\models\Stable-diffusion\` 폴더에 복사

### 3. ControlNet OpenPose 모델 다운로드 (2.8GB)

**옵션 A: PowerShell로 다운로드**
```powershell
cd C:\xampp\htdocs\ai_test_sec\sd-webui\extensions\sd-webui-controlnet\models

$url = "https://huggingface.co/lllyasviel/ControlNet-v1-1/resolve/main/control_v11p_sd15_openpose.pth"
Invoke-WebRequest -Uri $url -OutFile "control_v11p_sd15_openpose.pth"
```

**옵션 B: 수동 다운로드**
1. 브라우저에서 https://huggingface.co/lllyasviel/ControlNet-v1-1/tree/main 접속
2. `control_v11p_sd15_openpose.pth` 파일 다운로드
3. `C:\xampp\htdocs\ai_test_sec\sd-webui\extensions\sd-webui-controlnet\models\` 폴더에 복사

---

## 실행 방법

### 1단계: Ollama 확인

PowerShell에서:
```powershell
ollama list
```

Phi-3 Mini가 목록에 있으면 OK!

### 2단계: Stable Diffusion WebUI 실행

**새 PowerShell 창**을 열고:
```powershell
cd C:\xampp\htdocs\ai_test_sec\sd-webui
.\webui-user.bat
```

첫 실행 시 필요한 패키지를 자동으로 설치합니다 (5-10분 소요).
완료되면 http://localhost:7860 에서 접속 가능합니다.

### 3단계: Apache 시작

XAMPP Control Panel 열고 Apache Start

### 4단계: 키오스크 접속

브라우저에서 http://localhost/ai_test_sec/ 접속

---

## 테스트

```powershell
cd C:\xampp\htdocs\ai_test_sec

# API 테스트
.\test_api.ps1
```

---

## 문제 해결

### Ollama 명령어를 찾을 수 없음

PowerShell을 완전히 닫고 다시 열어보세요. 환경변수가 업데이트됩니다.

또는 직접 경로 지정:
```powershell
& "C:\Users\$env:USERNAME\AppData\Local\Programs\Ollama\ollama.exe" pull phi3:mini
```

### SD WebUI 실행 오류

GPU가 없거나 메모리 부족 시, `webui-user.bat`에 다음 추가:
```batch
set COMMANDLINE_ARGS=--api --lowvram --no-half
```

### 포트 충돌

- Ollama: 11434
- SD WebUI: 7860
- Apache: 80

다른 프로그램이 이 포트를 사용 중이면 충돌할 수 있습니다.

---

## 서비스 URL

- **Ollama API**: http://localhost:11434
- **SD WebUI**: http://localhost:7860
- **AI 키오스크**: http://localhost/ai_test_sec/

---

## 다음 단계

1. 모델 다운로드 완료 (위 2, 3번)
2. 서비스 실행 (위 실행 방법 참고)
3. 키오스크 접속 및 테스트

모든 준비가 완료되면 `start_services.ps1`을 실행하여 한 번에 모든 서비스를 시작할 수 있습니다!

```powershell
cd C:\xampp\htdocs\ai_test_sec
.\start_services.ps1
```
