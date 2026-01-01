<?php
/**
 * 고도화된 포즈 감지 테스트
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// 테스트 이미지 (작은 사람 이미지 - base64)
$test_image = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwA/VVAAA=';

echo "=== 고도화된 포즈 감지 테스트 ===\n\n";

// 옵션들 테스트
$test_cases = [
    [
        'name' => '기본 (포즈만)',
        'options' => [
            'advanced' => true,
            'draw_hands' => false,
            'draw_face' => false
        ]
    ],
    [
        'name' => '포즈 + 손',
        'options' => [
            'advanced' => true,
            'draw_hands' => true,
            'draw_face' => false
        ]
    ],
    [
        'name' => '포즈 + 얼굴',
        'options' => [
            'advanced' => true,
            'draw_hands' => false,
            'draw_face' => true
        ]
    ],
    [
        'name' => '전체 (포즈 + 손 + 얼굴)',
        'options' => [
            'advanced' => true,
            'draw_hands' => true,
            'draw_face' => true
        ]
    ],
    [
        'name' => '구버전 (비교용)',
        'options' => [
            'advanced' => false
        ]
    ]
];

foreach ($test_cases as $test) {
    echo "\n--- 테스트: {$test['name']} ---\n";

    $_POST['action'] = 'detect_pose';
    $_POST['image'] = $test_image;

    foreach ($test['options'] as $key => $value) {
        $_POST[$key] = $value;
    }

    ob_start();
    include 'api/ai_service.php';
    $output = ob_get_clean();

    echo "결과:\n";
    $result = json_decode($output, true);
    if ($result) {
        echo "성공: " . ($result['success'] ? 'Yes' : 'No') . "\n";

        if (isset($result['pose_quality'])) {
            echo "품질 점수: {$result['pose_quality']['overall_score']}\n";
            echo "품질 등급: {$result['pose_quality']['quality_level']}\n";
            echo "가시성: {$result['pose_quality']['visibility_score']}%\n";
        }

        if (isset($result['detected_features'])) {
            echo "감지된 기능:\n";
            echo "  - 포즈: " . ($result['detected_features']['pose'] ? 'O' : 'X') . "\n";
            echo "  - 왼손: " . ($result['detected_features']['left_hand'] ? 'O' : 'X') . "\n";
            echo "  - 오른손: " . ($result['detected_features']['right_hand'] ? 'O' : 'X') . "\n";
            echo "  - 얼굴: " . ($result['detected_features']['face'] ? 'O' : 'X') . "\n";
        }

        if (isset($result['error'])) {
            echo "에러: {$result['error']}\n";
        }

        if (isset($result['skeleton_url'])) {
            echo "스켈레톤 이미지: {$result['skeleton_url']}\n";
        }
    } else {
        echo "JSON 파싱 실패\n";
        echo "출력:\n$output\n";
    }

    // POST 데이터 초기화
    foreach ($test['options'] as $key => $value) {
        unset($_POST[$key]);
    }
}

echo "\n\n=== 테스트 완료 ===\n";
echo "\n사용 가능한 API 엔드포인트:\n";
echo "POST /api/ai_service.php\n";
echo "  action=detect_pose\n";
echo "  image=<base64>\n";
echo "  advanced=true|false (기본: true)\n";
echo "  draw_hands=true|false (기본: true)\n";
echo "  draw_face=true|false (기본: true)\n";
?>
