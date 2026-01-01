<?php
// Debug version of ai_service with logging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$log_file = 'C:\xampp\htdocs\ai_test_sec\debug.log';

function debug_log($message) {
    global $log_file;
    file_put_contents($log_file, date('[Y-m-d H:i:s] ') . $message . "\n", FILE_APPEND);
}

debug_log("=== Script started ===");
debug_log("REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'not set'));
debug_log("POST data: " . json_encode($_POST));
debug_log("GET data: " . json_encode($_GET));

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

debug_log("Headers sent");

// OPTIONS 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    debug_log("OPTIONS request - exiting");
    http_response_code(200);
    exit();
}

debug_log("Not OPTIONS request");

// 설정
define('PYTHON_PATH', 'C:\\xampp\\htdocs\\ai_test_sec\\venv\\Scripts\\python.exe');
define('SCRIPT_PATH', 'C:\\xampp\\htdocs\\ai_test_sec\\scripts');
define('UPLOAD_PATH', 'C:\\xampp\\htdocs\\ai_test_sec\\uploads');
define('OUTPUT_PATH', 'C:\\xampp\\htdocs\\ai_test_sec\\outputs');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
debug_log("Action: " . $action);

switch ($action) {
    case 'pose_image':
        debug_log("Entered pose_image case");
        $image_data = $_POST['image'] ?? '';
        $prompt = $_POST['prompt'] ?? '';

        debug_log("Image data length: " . strlen($image_data));
        debug_log("Prompt: " . $prompt);

        if (empty($image_data) || empty($prompt)) {
            debug_log("Empty data - returning error");
            echo json_encode(['success' => false, 'error' => '이미지 또는 프롬프트가 비어있습니다']);
            exit;
        }

        debug_log("Calling detect_pose...");

        // Simplified detect_pose for testing
        $timestamp = time();
        $temp_image = UPLOAD_PATH . "\\webcam_$timestamp.jpg";
        $skeleton_image = OUTPUT_PATH . "\\skeleton_$timestamp.png";

        $image_parts = explode(',', $image_data);
        $image_base64 = isset($image_parts[1]) ? $image_parts[1] : $image_data;
        $decoded = base64_decode($image_base64);

        debug_log("Decoded image: " . ($decoded === false ? 'FAILED' : strlen($decoded) . ' bytes'));

        if ($decoded === false) {
            debug_log("Base64 decode failed - returning error");
            echo json_encode(['success' => false, 'error' => '이미지 디코딩 실패']);
            exit;
        }

        debug_log("Saving temp image to: " . $temp_image);
        file_put_contents($temp_image, $decoded);

        debug_log("Returning test success");
        echo json_encode([
            'success' => true,
            'message' => 'Debug test successful',
            'temp_image' => $temp_image
        ], JSON_UNESCAPED_UNICODE);
        break;

    default:
        debug_log("Default case - unknown action");
        echo json_encode([
            'success' => false,
            'error' => '유효하지 않은 액션: ' . $action,
            'available_actions' => ['pose_image']
        ]);
        break;
}

debug_log("=== Script finished ===");
?>
