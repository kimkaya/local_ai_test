<?php
// TTS 직접 테스트
define('PYTHON_PATH', 'C:\\xampp\\htdocs\\ai_test_sec\\venv\\Scripts\\python.exe');
define('SCRIPT_PATH', 'C:\\xampp\\htdocs\\ai_test_sec\\scripts');

$text = "테스트입니다";
$timestamp = time() . '_' . rand(1000, 9999);
$output_file = "tts_$timestamp.mp3";

$script = SCRIPT_PATH . '\\tts_service.py';
$command = '"' . PYTHON_PATH . '" "' . $script . '" "' . addslashes($text) . '" "' . $output_file . '" 2>&1';

echo "Command: $command\n\n";

exec($command, $output, $return_var);

echo "Return code: $return_var\n";
echo "Output:\n";
print_r($output);
?>
