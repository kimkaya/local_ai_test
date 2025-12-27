# AI 키오스크 서비스 시작 스크립트
# PowerShell에서 실행: .\start_services.ps1

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  AI 키오스크 서비스 시작" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# 1. Ollama 확인
Write-Host "[1/3] Ollama 서비스 확인..." -ForegroundColor Yellow
try {
    $ollamaTest = Invoke-WebRequest -Uri "http://localhost:11434/api/tags" -UseBasicParsing -TimeoutSec 5
    Write-Host "  Ollama 실행 중" -ForegroundColor Green
} catch {
    Write-Host "  Ollama가 실행되지 않았습니다" -ForegroundColor Red
    Write-Host "  Ollama를 시작하려면 새 터미널에서 'ollama serve' 실행" -ForegroundColor Yellow
}

Write-Host ""

# 2. Stable Diffusion WebUI 시작
Write-Host "[2/3] Stable Diffusion WebUI 시작..." -ForegroundColor Yellow
$sdPath = "C:\xampp\htdocs\ai_test_sec\sd-webui\webui-user.bat"

if (Test-Path $sdPath) {
    Write-Host "  SD WebUI를 새 창에서 시작합니다..." -ForegroundColor Yellow
    Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd C:\xampp\htdocs\ai_test_sec\sd-webui; .\webui-user.bat"
    Write-Host "  SD WebUI 시작됨 (새 창)" -ForegroundColor Green
    Write-Host "  준비되면 http://localhost:7860 에서 확인하세요" -ForegroundColor Cyan
} else {
    Write-Host "  SD WebUI를 찾을 수 없습니다" -ForegroundColor Red
    Write-Host "  먼저 설치를 완료하세요: install.ps1" -ForegroundColor Yellow
}

Write-Host ""

# 3. Apache 상태 확인
Write-Host "[3/3] Apache 서비스 확인..." -ForegroundColor Yellow
try {
    $apacheTest = Invoke-WebRequest -Uri "http://localhost/ai_test_sec/" -UseBasicParsing -TimeoutSec 5
    Write-Host "  Apache 실행 중" -ForegroundColor Green
} catch {
    Write-Host "  Apache가 실행되지 않았습니다" -ForegroundColor Red
    Write-Host "  XAMPP Control Panel에서 Apache를 시작하세요" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  서비스 상태 요약" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "서비스 URL:" -ForegroundColor Yellow
Write-Host "  - Ollama API:   http://localhost:11434" -ForegroundColor White
Write-Host "  - SD WebUI:     http://localhost:7860" -ForegroundColor White
Write-Host "  - AI 키오스크:  http://localhost/ai_test_sec/" -ForegroundColor Green
Write-Host ""

Write-Host "다음 단계:" -ForegroundColor Yellow
Write-Host "1. SD WebUI가 완전히 로드될 때까지 기다리세요 (1-2분)" -ForegroundColor White
Write-Host "2. 브라우저에서 http://localhost/ai_test_sec/ 접속" -ForegroundColor White
Write-Host "3. 서비스 상태를 확인하세요 (페이지 상단)" -ForegroundColor White
Write-Host ""

# 대기
Write-Host "서비스가 시작되는 동안 잠시 기다립니다..." -ForegroundColor Yellow
Start-Sleep -Seconds 5

# 브라우저 열기
Write-Host "기본 브라우저에서 키오스크를 엽니다..." -ForegroundColor Yellow
Start-Process "http://localhost/ai_test_sec/"

Write-Host ""
Write-Host "즐거운 시간 되세요!" -ForegroundColor Green
