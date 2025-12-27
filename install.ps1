# AI 키오스크 빠른 설치 스크립트
# PowerShell에서 실행: .\install.ps1

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  AI 키오스크 설치 시작" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# 현재 디렉토리 확인
$projectPath = "C:\xampp\htdocs\ai_test_sec"
Set-Location $projectPath

Write-Host "[1/5] Python 가상환경 생성..." -ForegroundColor Yellow

# Python 가상환경 생성
if (Test-Path "venv") {
    Write-Host "  가상환경이 이미 존재합니다." -ForegroundColor Green
} else {
    python -m venv venv
    Write-Host "  가상환경 생성 완료" -ForegroundColor Green
}

Write-Host ""
Write-Host "[2/5] Python 패키지 설치..." -ForegroundColor Yellow

# 가상환경 활성화 및 패키지 설치
& "$projectPath\venv\Scripts\python.exe" -m pip install --upgrade pip
& "$projectPath\venv\Scripts\python.exe" -m pip install -r requirements.txt

Write-Host "  패키지 설치 완료" -ForegroundColor Green

Write-Host ""
Write-Host "[3/5] Ollama 설치 확인..." -ForegroundColor Yellow

# Ollama 확인
$ollamaInstalled = Get-Command ollama -ErrorAction SilentlyContinue
if ($ollamaInstalled) {
    Write-Host "  Ollama가 설치되어 있습니다." -ForegroundColor Green

    Write-Host "  Phi-3 Mini 모델 다운로드 중..." -ForegroundColor Yellow
    ollama pull phi3:mini
    Write-Host "  모델 다운로드 완료" -ForegroundColor Green
} else {
    Write-Host "  경고: Ollama가 설치되어 있지 않습니다." -ForegroundColor Red
    Write-Host "  다음 링크에서 설치하세요: https://ollama.ai/download" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "[4/5] Stable Diffusion WebUI 클론..." -ForegroundColor Yellow

# SD WebUI 클론
if (Test-Path "sd-webui") {
    Write-Host "  SD WebUI가 이미 존재합니다." -ForegroundColor Green
} else {
    git clone https://github.com/AUTOMATIC1111/stable-diffusion-webui.git sd-webui
    Write-Host "  SD WebUI 클론 완료" -ForegroundColor Green
}

Write-Host ""
Write-Host "[5/5] 디렉토리 구조 확인..." -ForegroundColor Yellow

# 필요한 디렉토리 생성
$directories = @("scripts", "api", "js", "css", "uploads", "outputs")
foreach ($dir in $directories) {
    if (!(Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir | Out-Null
    }
}

Write-Host "  디렉토리 구조 확인 완료" -ForegroundColor Green

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  설치 완료!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "다음 단계:" -ForegroundColor Yellow
Write-Host "1. SD 모델 다운로드:" -ForegroundColor White
Write-Host "   - sd-webui\models\Stable-diffusion 폴더에" -ForegroundColor White
Write-Host "   - sd_turbo.safetensors 파일을 다운로드하세요" -ForegroundColor White
Write-Host "   - https://huggingface.co/stabilityai/sd-turbo" -ForegroundColor Cyan
Write-Host ""
Write-Host "2. ControlNet 모델 다운로드:" -ForegroundColor White
Write-Host "   - sd-webui\extensions\sd-webui-controlnet\models 폴더에" -ForegroundColor White
Write-Host "   - control_v11p_sd15_openpose.pth 파일을 다운로드하세요" -ForegroundColor White
Write-Host "   - https://huggingface.co/lllyasviel/ControlNet-v1-1" -ForegroundColor Cyan
Write-Host ""
Write-Host "3. SD WebUI 첫 실행:" -ForegroundColor White
Write-Host "   cd sd-webui" -ForegroundColor Cyan
Write-Host "   .\webui-user.bat" -ForegroundColor Cyan
Write-Host ""
Write-Host "4. webui-user.bat 편집 (저사양 최적화):" -ForegroundColor White
Write-Host "   set COMMANDLINE_ARGS=--api --xformers --medvram" -ForegroundColor Cyan
Write-Host ""
Write-Host "5. Apache 시작 (XAMPP Control Panel)" -ForegroundColor White
Write-Host ""
Write-Host "6. 브라우저에서 접속:" -ForegroundColor White
Write-Host "   http://localhost/ai_test_sec/" -ForegroundColor Cyan
Write-Host ""

Write-Host "자세한 내용은 SETUP_GUIDE.md를 참고하세요" -ForegroundColor Yellow
