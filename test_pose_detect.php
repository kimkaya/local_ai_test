<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('PYTHON_PATH', 'C:\\xampp\\htdocs\\ai_test_sec\\venv\\Scripts\\python.exe');
define('SCRIPT_PATH', 'C:\\xampp\\htdocs\\ai_test_sec\\scripts');
define('UPLOAD_PATH', 'C:\\xampp\\htdocs\\ai_test_sec\\uploads');
define('OUTPUT_PATH', 'C:\\xampp\\htdocs\\ai_test_sec\\outputs');

// Create directories if needed
if (!file_exists(UPLOAD_PATH)) mkdir(UPLOAD_PATH, 0777, true);
if (!file_exists(OUTPUT_PATH)) mkdir(OUTPUT_PATH, 0777, true);

echo "=== Testing Pose Detection ===\n\n";

// Test image (1x1 pixel JPEG)
$image_data = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwA/VVAAA=';

$timestamp = time();
$temp_image = UPLOAD_PATH . "\\webcam_$timestamp.jpg";
$skeleton_image = OUTPUT_PATH . "\\skeleton_$timestamp.png";

echo "1. Decoding image...\n";
$image_parts = explode(',', $image_data);
$image_base64 = isset($image_parts[1]) ? $image_parts[1] : $image_data;
$decoded = base64_decode($image_base64);

if ($decoded === false) {
    die("ERROR: Base64 decode failed\n");
}

echo "   Decoded: " . strlen($decoded) . " bytes\n";

echo "2. Saving temp image to: $temp_image\n";
file_put_contents($temp_image, $decoded);

if (!file_exists($temp_image)) {
    die("ERROR: Failed to save temp image\n");
}

echo "   File saved: " . filesize($temp_image) . " bytes\n";

echo "3. Running Python pose detection...\n";
$script = SCRIPT_PATH . '\\camera_detect.py';
$command = '"' . PYTHON_PATH . '" "' . $script . '" file "' . $temp_image . '" "' . $skeleton_image . '" 2>&1';

echo "   Command: $command\n\n";

$start_time = microtime(true);
exec($command, $output, $return_var);
$end_time = microtime(true);

echo "4. Execution complete\n";
echo "   Time: " . round($end_time - $start_time, 2) . " seconds\n";
echo "   Return code: $return_var\n";
echo "   Output lines: " . count($output) . "\n\n";

echo "5. Python output:\n";
echo "---\n";
$output_str = implode("\n", $output);
echo $output_str;
echo "\n---\n\n";

echo "6. Parsing JSON...\n";
$result = json_decode($output_str, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "   JSON parse error: " . json_last_error_msg() . "\n";
    echo "   Raw output: $output_str\n";
} else {
    echo "   Parsed successfully:\n";
    print_r($result);
}

echo "\n=== Test Complete ===\n";
?>
