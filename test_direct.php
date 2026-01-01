<?php
// Direct minimal test with error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'C:\xampp\htdocs\ai_test_sec\php_errors.log');

// Set POST data
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['action'] = 'pose_image';
$_POST['image'] = 'test_image_data';
$_POST['prompt'] = 'test prompt';

echo "Starting test...\n";

// Capture any output
ob_start();

try {
    include 'api/ai_service.php';
    $output = ob_get_clean();
    echo "Output captured: " . strlen($output) . " bytes\n";
    echo "Content: $output\n";
} catch (Exception $e) {
    ob_end_clean();
    echo "Exception: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>
