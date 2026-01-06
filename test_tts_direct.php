<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// POST 데이터 시뮬레이션
$_POST['action'] = 'tts';
$_POST['text'] = '테스트입니다';

echo "Starting TTS test...\n";
flush();

// ai_service.php 포함
include 'api/ai_service.php';
?>
