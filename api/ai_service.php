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
 * 파일 트랜잭션 클래스
 * 생성된 파일들을 추적하고 에러 발생 시 롤백
 */
class FileTransaction {
    private $files = [];
    private $committed = false;

    public function addFile($filepath) {
        if (file_exists($filepath)) {
            $this->files[] = $filepath;
        }
    }

    public function commit() {
        $this->committed = true;
        $this->files = [];
    }

    public function rollback() {
        if (!$this->committed) {
            foreach ($this->files as $file) {
                if (file_exists($file)) {
                    @unlink($file);
                }
            }
        }
        $this->files = [];
    }

    public function __destruct() {
        // 객체 소멸 시 자동 롤백 (commit되지 않은 경우)
        if (!$this->committed) {
            $this->rollback();
        }
    }
}

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
 * 카메라 포즈 감지 (고도화 버전)
 */
function detect_pose($image_data, $advanced = true, $draw_hands = true, $draw_face = true, $external_transaction = null) {
    // 외부 트랜잭션이 있으면 사용, 없으면 새로 생성
    $transaction = $external_transaction ?? new FileTransaction();
    $is_own_transaction = ($external_transaction === null);

    try {
        $timestamp = time();
        $temp_image = UPLOAD_PATH . "/webcam_$timestamp.jpg";
        $skeleton_image = OUTPUT_PATH . "/skeleton_$timestamp.png";

        // Base64 이미지 저장
        $image_parts = explode(',', $image_data);
        $image_base64 = isset($image_parts[1]) ? $image_parts[1] : $image_data;
        $decoded = base64_decode($image_base64);

        if ($decoded === false) {
            throw new Exception('이미지 디코딩 실패');
        }

        file_put_contents($temp_image, $decoded);
        $transaction->addFile($temp_image);

        // Python 스크립트 선택 (고도화 버전 또는 기본 버전)
        $script = $advanced ?
            SCRIPT_PATH . '\\camera_detect_advanced.py' :
            SCRIPT_PATH . '\\camera_detect.py';

        // 고도화 버전 옵션
        $options = json_encode([
            'draw_hands' => $draw_hands,
            'draw_face' => $draw_face,
            'colorful' => false,
            'min_quality' => 30.0
        ]);

        if ($advanced) {
            $command = '"' . PYTHON_PATH . '" "' . $script . '" file "' . $temp_image . '" "' . $skeleton_image . '" \'' . addslashes($options) . '\' 2>&1';
        } else {
            $command = '"' . PYTHON_PATH . '" "' . $script . '" file "' . $temp_image . '" "' . $skeleton_image . '" 2>&1';
        }

        exec($command, $output, $return_var);

        // skeleton_image가 생성되었다면 트랜잭션에 추가
        if (file_exists($skeleton_image)) {
            $transaction->addFile($skeleton_image);
        }

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
            throw new Exception("JSON 파싱 실패: " . $output_str);
        }

        if ($result && isset($result['success']) && $result['success']) {
            // 자체 트랜잭션일 때만 커밋
            if ($is_own_transaction) {
                $transaction->commit();
            }
            return [
                'success' => true,
                'skeleton_path' => $skeleton_image,
                'skeleton_url' => '/ai_test_sec/outputs/' . basename($skeleton_image),
                'message' => '포즈 감지 성공'
            ];
        } else {
            $error = $result['error'] ?? $output_str;
            throw new Exception("포즈 감지 실패: $error");
        }
    } catch (Exception $e) {
        // 자체 트랜잭션일 때만 롤백하고 에러 반환
        if ($is_own_transaction) {
            $transaction->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
        // 외부 트랜잭션을 위해 예외 다시 던지기
        throw $e;
    }
}

/**
 * 이미지 생성
 */
function generate_image($prompt, $skeleton_path = null, $mode = 'simple', $external_transaction = null) {
    // 외부 트랜잭션이 있으면 사용, 없으면 새로 생성
    $transaction = $external_transaction ?? new FileTransaction();
    $is_own_transaction = ($external_transaction === null);

    try {
        $timestamp = time();
        $output_image = OUTPUT_PATH . "/generated_$timestamp.png";

        $script = SCRIPT_PATH . '\\image_generate.py';

        if ($mode === 'controlnet' && $skeleton_path) {
            $command = '"' . PYTHON_PATH . '" "' . $script . '" controlnet "' . $prompt . '" "' . $skeleton_path . '" "' . $output_image . '" 2>&1';
        } else {
            $command = '"' . PYTHON_PATH . '" "' . $script . '" simple "' . $prompt . '" "' . $output_image . '" 2>&1';
        }

        exec($command, $output, $return_var);

        // output_image가 생성되었다면 트랜잭션에 추가
        if (file_exists($output_image)) {
            $transaction->addFile($output_image);
        }

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
            throw new Exception("JSON 파싱 실패: " . $output_str);
        }

        if ($result && isset($result['success']) && $result['success']) {
            // 자체 트랜잭션일 때만 커밋
            if ($is_own_transaction) {
                $transaction->commit();
            }
            return [
                'success' => true,
                'image_path' => $output_image,
                'image_url' => '/ai_test_sec/outputs/' . basename($output_image),
                'message' => '이미지 생성 성공'
            ];
        } else {
            $error = $result['error'] ?? $output_str;
            throw new Exception("이미지 생성 실패: $error");
        }
    } catch (Exception $e) {
        // 자체 트랜잭션일 때만 롤백하고 에러 반환
        if ($is_own_transaction) {
            $transaction->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
        // 외부 트랜잭션을 위해 예외 다시 던지기
        throw $e;
    }
}

/**
 * 포즈 기반 이미지 생성 (통합)
 */
function generate_pose_image($image_data, $prompt, $advanced = true, $draw_hands = true, $draw_face = true) {
    $transaction = new FileTransaction();

    try {
        // 1. 포즈 감지 (고도화 버전) - 트랜잭션 전달
        $pose_result = detect_pose($image_data, $advanced, $draw_hands, $draw_face, $transaction);

        if (!$pose_result['success']) {
            throw new Exception($pose_result['error']);
        }

        // 2. 이미지 생성 - 트랜잭션 전달
        $gen_result = generate_image($prompt, $pose_result['skeleton_path'], 'controlnet', $transaction);

        if (!$gen_result['success']) {
            throw new Exception($gen_result['error']);
        }

        // 모든 작업 성공 시 트랜잭션 커밋
        $transaction->commit();

        // 결과 통합
        return [
            'success' => true,
            'skeleton_url' => $pose_result['skeleton_url'],
            'image_url' => $gen_result['image_url'],
            'message' => '포즈 기반 이미지 생성 완료'
        ];
    } catch (Exception $e) {
        $transaction->rollback(); // 에러 시 모든 파일 삭제
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * TTS (Text-to-Speech) - 텍스트를 음성으로 변환
 */
function text_to_speech($text) {
    $transaction = new FileTransaction();

    try {
        error_log("text_to_speech 함수 시작");
        $timestamp = time() . '_' . rand(1000, 9999);
        $output_file = "tts_$timestamp.mp3";
        $full_output_path = OUTPUT_PATH . '\\' . $output_file;

        $script = SCRIPT_PATH . '\\tts_service.py';
        $command = '"' . PYTHON_PATH . '" "' . $script . '" "' . addslashes($text) . '" "' . $output_file . '" 2>&1';

        error_log("TTS 명령어: " . $command);

        $start_time = microtime(true);
        exec($command, $output, $return_var);
        $elapsed = microtime(true) - $start_time;

        error_log("TTS 실행 완료 (소요시간: " . round($elapsed, 2) . "초)");
        error_log("TTS Python 출력 라인 수: " . count($output));
        error_log("TTS Python 전체 출력: " . implode("\n", $output));

        // 생성된 파일 트랜잭션에 추가
        if (file_exists($full_output_path)) {
            $transaction->addFile($full_output_path);
        }

        // JSON 라인만 필터링
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
        error_log("TTS JSON 문자열: " . $output_str);
        $result = json_decode($output_str, true);

        // JSON 파싱 실패 시
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("TTS JSON 파싱 실패: " . json_last_error_msg());
            throw new Exception("TTS 서비스 오류: " . $output_str);
        }

        // 결과 검증
        if (!isset($result['success']) || !$result['success']) {
            $error = $result['error'] ?? 'Unknown error';
            throw new Exception("TTS 실패: " . $error);
        }

        $transaction->commit(); // 성공 시 트랜잭션 커밋
        error_log("TTS 성공, 결과 반환");
        return $result;
    } catch (Exception $e) {
        $transaction->rollback(); // 에러 시 자동 롤백
        error_log("TTS 에러: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
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
        $advanced = $_POST['advanced'] ?? true;
        $draw_hands = $_POST['draw_hands'] ?? true;
        $draw_face = $_POST['draw_face'] ?? true;

        if (empty($image_data)) {
            echo json_encode(['success' => false, 'error' => '이미지 데이터가 비어있습니다']);
            exit;
        }

        $result = detect_pose($image_data, $advanced, $draw_hands, $draw_face);
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
        $advanced = $_POST['advanced'] ?? true;
        $draw_hands = $_POST['draw_hands'] ?? true;
        $draw_face = $_POST['draw_face'] ?? true;

        if (empty($image_data) || empty($prompt)) {
            echo json_encode(['success' => false, 'error' => '이미지 또는 프롬프트가 비어있습니다']);
            exit;
        }

        $result = generate_pose_image($image_data, $prompt, $advanced, $draw_hands, $draw_face);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        break;

    case 'tts':
        error_log("TTS 요청 시작");
        $text = $_POST['text'] ?? '';

        if (empty($text)) {
            error_log("TTS 오류: 텍스트 비어있음");
            echo json_encode(['success' => false, 'error' => '텍스트가 비어있습니다']);
            exit;
        }

        error_log("TTS 텍스트: " . $text);
        $result = text_to_speech($text);
        error_log("TTS 결과: " . json_encode($result));
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
            'available_actions' => ['chat', 'detect_pose', 'generate_image', 'pose_image', 'tts', 'health']
        ]);
        break;
}
?>
