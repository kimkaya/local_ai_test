# API 테스트 스크립트
# PowerShell에서 실행: .\test_api.ps1

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  AI 키오스크 API 테스트" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$apiUrl = "http://localhost/ai_test_sec/api/ai_service.php"

Write-Host "[1/4] 헬스 체크 테스트..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "$apiUrl`?action=health" -Method GET -UseBasicParsing
    $result = $response.Content | ConvertFrom-Json

    if ($result.success) {
        Write-Host "  헬스 체크 성공" -ForegroundColor Green
        Write-Host "  Ollama: $($result.services.ollama)" -ForegroundColor $(if ($result.services.ollama) { "Green" } else { "Red" })
        Write-Host "  SD WebUI: $($result.services.stable_diffusion)" -ForegroundColor $(if ($result.services.stable_diffusion) { "Green" } else { "Red" })
        Write-Host "  Python: $($result.services.python)" -ForegroundColor $(if ($result.services.python) { "Green" } else { "Red" })
    } else {
        Write-Host "  헬스 체크 실패" -ForegroundColor Red
    }
} catch {
    Write-Host "  오류: $_" -ForegroundColor Red
}

Write-Host ""
Write-Host "[2/4] 챗봇 테스트..." -ForegroundColor Yellow
try {
    $body = @{
        action = "chat"
        message = "Hello, how are you?"
    }
    $response = Invoke-WebRequest -Uri $apiUrl -Method POST -Body $body -UseBasicParsing
    $result = $response.Content | ConvertFrom-Json

    if ($result.success) {
        Write-Host "  챗봇 응답 성공" -ForegroundColor Green
        Write-Host "  응답: $($result.message)" -ForegroundColor Cyan
    } else {
        Write-Host "  챗봇 응답 실패: $($result.error)" -ForegroundColor Red
    }
} catch {
    Write-Host "  오류: $_" -ForegroundColor Red
}

Write-Host ""
Write-Host "[3/4] Python 스크립트 테스트..." -ForegroundColor Yellow
$pythonPath = "C:\xampp\htdocs\ai_test_sec\venv\Scripts\python.exe"
if (Test-Path $pythonPath) {
    Write-Host "  Python 경로 확인됨" -ForegroundColor Green

    # 패키지 확인
    $packages = @("opencv-python", "mediapipe", "Pillow", "requests")
    foreach ($pkg in $packages) {
        $installed = & $pythonPath -m pip show $pkg 2>$null
        if ($installed) {
            Write-Host "  $pkg 설치됨" -ForegroundColor Green
        } else {
            Write-Host "  $pkg 미설치" -ForegroundColor Red
        }
    }
} else {
    Write-Host "  Python 가상환경을 찾을 수 없습니다" -ForegroundColor Red
}

Write-Host ""
Write-Host "[4/4] 디렉토리 권한 테스트..." -ForegroundColor Yellow
$uploadPath = "C:\xampp\htdocs\ai_test_sec\uploads"
$outputPath = "C:\xampp\htdocs\ai_test_sec\outputs"

foreach ($path in @($uploadPath, $outputPath)) {
    if (Test-Path $path) {
        try {
            $testFile = Join-Path $path "test.txt"
            "test" | Out-File -FilePath $testFile
            Remove-Item $testFile
            Write-Host "  $path 쓰기 권한 확인" -ForegroundColor Green
        } catch {
            Write-Host "  $path 쓰기 권한 없음" -ForegroundColor Red
        }
    } else {
        Write-Host "  $path 디렉토리 없음" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  테스트 완료" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "서비스 상태:" -ForegroundColor Yellow
Write-Host "  - Ollama: http://localhost:11434" -ForegroundColor White
Write-Host "  - SD WebUI: http://localhost:7860" -ForegroundColor White
Write-Host "  - 키오스크: http://localhost/ai_test_sec/" -ForegroundColor White
Write-Host ""
