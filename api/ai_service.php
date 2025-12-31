<?php
/**
 * AI 키오스크 API
 * - 챗봇 (Ollama)
 * - 이미지 생성 (Stable Diffusion)
 * - 카메라 포즈 감지 (MediaPipe)
 */

// 실행 시간 제한 늘리기 (포즈 감지 및 이미지 생성은 시간이 오래 걸림)
set_time_limit(300); // 5분
ini_set('max_execution_time', '300');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// OPTIONS 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 설정
define('OLLAMA_API', 'http://localhost:11434');
define('SD_API', 'http://localhost:7861');
define('PYTHON_PATH', 'C:\\xampp\\htdocs\\ai_test_sec\\venv\\Scripts\\python.exe');
define('SCRIPT_PATH', 'C:\\xampp\\htdocs\\ai_test_sec\\scripts');
define('UPLOAD_PATH', 'C:\\xampp\\htdocs\\ai_test_sec\\uploads');
define('OUTPUT_PATH', 'C:\\xampp\\htdocs\\ai_test_sec\\outputs');

// 디렉토리 생성
if (!file_exists(UPLOAD_PATH)) mkdir(UPLOAD_PATH, 0777, true);
if (!file_exists(OUTPUT_PATH)) mkdir(OUTPUT_PATH, 0777, true);

/**
 * Ollama API 호출 - 챗봇
 */
function chat_with_ollama($message, $model = 'phi3:mini') {
    $url = OLLAMA_API . '/api/generate';

    $data = [
        'model' => $model,
        'prompt' => $message,
        'stream' => false
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['success' => false, 'error' => "Ollama 연결 실패: $error"];
    }

    curl_close($ch);

    if ($http_code !== 200) {
        return ['success' => false, 'error' => "Ollama API 오류 (HTTP $http_code)"];
    }

    $result = json_decode($response, true);

    if (isset($result['response'])) {
        return [
            'success' => true,
            'message' => $result['response']
        ];
    } else {
        return ['success' => false, 'error' => 'Ollama 응답 형식 오류'];
    }
}

/**
 * 카메라 포즈 감지
 */
function detect_pose($image_data) {
    $timestamp = time();
    $temp_image = UPLOAD_PATH . "/webcam_$timestamp.jpg";
    $skeleton_image = OUTPUT_PATH . "/skeleton_$timestamp.png";

    // Base64 이미지 저장
    $image_parts = explode(',', $image_data);
    $image_base64 = isset($image_parts[1]) ? $image_parts[1] : $image_data;
    $decoded = base64_decode($image_base64);

    if ($decoded === false) {
        return ['success' => false, 'error' => '이미지 디코딩 실패'];
    }

    file_put_contents($temp_image, $decoded);

    // Python 스크립트 실행
    $script = SCRIPT_PATH . '\\camera_detect.py';
    $command = '"' . PYTHON_PATH . '" "' . $script . '" file "' . $temp_image . '" "' . $skeleton_image . '" 2>&1';

    exec($command, $output, $return_var);

    // Filter out non-JSON lines (like TensorFlow INFO messages)
    $json_lines = [];
    foreach ($output as $line) {
        $trimmed = trim($line);
        if (!empty($trimmed) && strlen($trimmed) > 0) {
            $first_char = $trimmed[0];
            if ($first_char === '{' || $first_char === '[') {
                $json_lines[] = $line;
            }
        }
    }

    $output_str = implode("\n", $json_lines);

    // JSON 파싱
    $result = json_decode($output_str, true);

    // JSON 파싱 실패 시 원본 출력 반환
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'success' => false,
            'error' => "JSON 파싱 실패",
            'raw_output' => $output_str,
            'command' => $command
        ];
    }

    if ($result && isset($result['success']) && $result['success']) {
        return [
            'success' => true,
            'skeleton_path' => $skeleton_image,
            'skeleton_url' => '/ai_test_sec/outputs/' . basename($skeleton_image),
            'message' => '포즈 감지 성공'
        ];
    } else {
        $error = $result['error'] ?? $output_str;
        return ['success' => false, 'error' => "포즈 감지 실패: $error"];
    }
}

/**
 * 이미지 생성
 */
function generate_image($prompt, $skeleton_path = null, $mode = 'simple') {
    $timestamp = time();
    $output_image = OUTPUT_PATH . "/generated_$timestamp.png";

    $script = SCRIPT_PATH . '\\image_generate.py';

    if ($mode === 'controlnet' && $skeleton_path) {
        $command = '"' . PYTHON_PATH . '" "' . $script . '" controlnet "' . $prompt . '" "' . $skeleton_path . '" "' . $output_image . '" 2>&1';
    } else {
        $command = '"' . PYTHON_PATH . '" "' . $script . '" simple "' . $prompt . '" "' . $output_image . '" 2>&1';
    }

    exec($command, $output, $return_var);

    // Filter out non-JSON lines (like TensorFlow INFO messages)
    $json_lines = [];
    foreach ($output as $line) {
        $trimmed = trim($line);
        if (!empty($trimmed) && strlen($trimmed) > 0) {
            $first_char = $trimmed[0];
            if ($first_char === '{' || $first_char === '[') {
                $json_lines[] = $line;
            }
        }
    }

    $output_str = implode("\n", $json_lines);
    $result = json_decode($output_str, true);

    // JSON 파싱 실패 시 원본 출력 반환
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'success' => false,
            'error' => "JSON 파싱 실패",
            'raw_output' => $output_str,
            'command' => $command
        ];
    }

    if ($result && isset($result['success']) && $result['success']) {
        return [
            'success' => true,
            'image_path' => $output_image,
            'image_url' => '/ai_test_sec/outputs/' . basename($output_image),
            'message' => '이미지 생성 성공'
        ];
    } else {
        $error = $result['error'] ?? $output_str;
        return ['success' => false, 'error' => "이미지 생성 실패: $error"];
    }
}

/**
 * 포즈 기반 이미지 생성 (통합)
 */
function generate_pose_image($image_data, $prompt) {
    // 1. 포즈 감지
    $pose_result = detect_pose($image_data);

    if (!$pose_result['success']) {
        return $pose_result;
    }

    // 2. 이미지 생성
    $gen_result = generate_image($prompt, $pose_result['skeleton_path'], 'controlnet');

    if (!$gen_result['success']) {
        return $gen_result;
    }

    // 결과 통합
    return [
        'success' => true,
        'skeleton_url' => $pose_result['skeleton_url'],
        'image_url' => $gen_result['image_url'],
        'message' => '포즈 기반 이미지 생성 완료'
    ];
}

// API 라우팅
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'chat':
        $message = $_POST['message'] ?? '';
        if (empty($message)) {
            echo json_encode(['success' => false, 'error' => '메시지가 비어있습니다']);
            exit;
        }

        $result = chat_with_ollama($message);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        break;

    case 'detect_pose':
        $image_data = $_POST['image'] ?? '';
        if (empty($image_data)) {
            echo json_encode(['success' => false, 'error' => '이미지 데이터가 비어있습니다']);
            exit;
        }

        $result = detect_pose($image_data);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        break;

    case 'generate_image':
        $prompt = $_POST['prompt'] ?? '';
        $mode = $_POST['mode'] ?? 'simple';
        $skeleton_path = $_POST['skeleton_path'] ?? null;

        if (empty($prompt)) {
            echo json_encode(['success' => false, 'error' => '프롬프트가 비어있습니다']);
            exit;
        }

        $result = generate_image($prompt, $skeleton_path, $mode);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        break;

    case 'pose_image':
        $image_data = $_POST['image'] ?? '';
        $prompt = $_POST['prompt'] ?? '';

        if (empty($image_data) || empty($prompt)) {
            echo json_encode(['success' => false, 'error' => '이미지 또는 프롬프트가 비어있습니다']);
            exit;
        }

        $result = generate_pose_image($image_data, $prompt);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        break;

    case 'health':
        // 헬스 체크
        $ollama_ok = @file_get_contents(OLLAMA_API . '/api/tags') !== false;
        $sd_ok = @file_get_contents(SD_API . '/sdapi/v1/sd-models') !== false;

        echo json_encode([
            'success' => true,
            'services' => [
                'ollama' => $ollama_ok,
                'stable_diffusion' => $sd_ok,
                'python' => file_exists(PYTHON_PATH)
            ]
        ]);
        break;

    default:
        echo json_encode([
            'success' => false,
            'error' => '유효하지 않은 액션',
            'available_actions' => ['chat', 'detect_pose', 'generate_image', 'pose_image', 'health']
        ]);
        break;
}
?>
