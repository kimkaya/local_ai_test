<?php
/**
 * Direct test of pose_image API endpoint
 */

// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test image data (small 1x1 pixel base64 encoded JPEG)
$test_image = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwA/VVAAA=';

echo "=== Testing pose_image API ===\n\n";

// Simulate POST request
$_POST['action'] = 'pose_image';
$_POST['image'] = $test_image;
$_POST['prompt'] = 'test person';

echo "Calling ai_service.php with:\n";
echo "- action: pose_image\n";
echo "- prompt: {$_POST['prompt']}\n";
echo "- image length: " . strlen($_POST['image']) . " chars\n\n";

// Capture output
ob_start();
include 'api/ai_service.php';
$output = ob_get_clean();

echo "=== API Response ===\n";
echo "Output length: " . strlen($output) . " bytes\n";
echo "Output:\n";
echo $output;
echo "\n\n=== End ===\n";
?>
