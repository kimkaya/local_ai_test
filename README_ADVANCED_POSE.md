# 고도화된 포즈 인식 시스템

## 개요
MediaPipe Holistic을 사용한 고급 포즈 감지 시스템으로 다음 기능을 제공합니다:

## 주요 기능

### 1. 전신 포즈 감지 (33 키포인트)
- 머리, 어깨, 팔꿈치, 손목
- 엉덩이, 무릎, 발목
- 눈, 코, 입

### 2. 손가락 인식 (각 손 21 키포인트)
- 손목
- 엄지, 검지, 중지, 약지, 새끼손가락 각 4개 관절
- 손 제스처 감지 가능

### 3. 얼굴 랜드마크 (468 키포인트)
- 얼굴 윤곽
- 눈썹, 눈, 코, 입
- 시선 및 표정 분석 가능

### 4. 포즈 품질 평가
- **overall_score**: 전체 품질 점수 (0-100)
- **visibility_score**: 키포인트 가시성
- **presence_score**: 키포인트 존재 확률
- **coverage**: 감지된 키포인트 비율
- **quality_level**: 품질 등급 (excellent/good/fair/poor)

### 5. 여러 사람 감지 (실험적)
- 한 프레임에서 여러 사람 감지 (기본 구현)

## API 사용법

### 엔드포인트
```
POST /api/ai_service.php
```

### 파라미터

#### 1. 포즈 감지만 (detect_pose)
```json
{
  "action": "detect_pose",
  "image": "data:image/jpeg;base64,...",
  "advanced": true,
  "draw_hands": true,
  "draw_face": true
}
```

#### 2. 포즈 기반 이미지 생성 (pose_image)
```json
{
  "action": "pose_image",
  "image": "data:image/jpeg;base64,...",
  "prompt": "a beautiful anime character",
  "advanced": true,
  "draw_hands": true,
  "draw_face": true
}
```

### 응답 예시

#### 성공 응답
```json
{
  "success": true,
  "skeleton_path": "C:\\xampp\\htdocs\\ai_test_sec\\outputs\\skeleton_1234567890.png",
  "skeleton_base64": "iVBORw0KGgoAAAANS...",
  "pose_quality": {
    "overall_score": 85.5,
    "visibility_score": 92.3,
    "presence_score": 88.7,
    "coverage": 95.0,
    "quality_level": "excellent",
    "visible_landmarks": 31,
    "total_landmarks": 33
  },
  "detected_features": {
    "pose": true,
    "left_hand": true,
    "right_hand": true,
    "face": true
  },
  "message": "포즈 감지 성공 (품질: excellent)"
}
```

#### 실패 응답
```json
{
  "success": false,
  "error": "포즈를 감지할 수 없습니다"
}
```

## Python CLI 사용법

### 기본 사용
```bash
python camera_detect_advanced.py file input.jpg output.png
```

### 옵션과 함께
```bash
python camera_detect_advanced.py file input.jpg output.png '{"draw_hands": true, "draw_face": true, "colorful": false, "min_quality": 30.0}'
```

### 웹캠 모드
```bash
python camera_detect_advanced.py webcam <base64_data> output.png
```

## 옵션 설명

### advanced (boolean, 기본: true)
- `true`: 고도화된 감지 (손, 얼굴 포함)
- `false`: 기본 포즈만 감지

### draw_hands (boolean, 기본: true)
- `true`: 손가락 스켈레톤 그리기
- `false`: 손 스켈레톤 제외

### draw_face (boolean, 기본: true)
- `true`: 얼굴 랜드마크 그리기
- `false`: 얼굴 랜드마크 제외

### colorful (boolean, 기본: false)
- `true`: 컬러풀한 스켈레톤 (포즈=기본색, 손=빨강/초록, 얼굴=파랑)
- `false`: 흰색 스켈레톤 (ControlNet용)

### min_quality (float, 기본: 0.0)
- 최소 품질 점수 (0-100)
- 이 점수 미만이면 감지 실패 처리

## 성능 최적화

### 모델 복잡도
- `model_complexity=0`: 빠른 속도, 낮은 정확도
- `model_complexity=1`: 균형 (권장)
- `model_complexity=2`: 높은 정확도, 느린 속도

현재 설정: `model_complexity=1` (균형)

### 신뢰도 임계값
- `min_detection_confidence=0.5`: 감지 최소 신뢰도
- `min_tracking_confidence=0.5`: 추적 최소 신뢰도

## 비교: 기본 vs 고도화

| 기능 | 기본 버전 | 고도화 버전 |
|------|----------|-------------|
| 포즈 키포인트 | 33개 | 33개 |
| 손가락 | ❌ | ✅ 각 손 21개 |
| 얼굴 | ❌ | ✅ 468개 |
| 품질 점수 | ❌ | ✅ |
| 여러 사람 | ❌ | ✅ (실험적) |
| 처리 속도 | 빠름 | 중간 |

## 테스트

### PHP 테스트
```bash
php test_advanced_pose.php
```

### 직접 Python 테스트
```bash
cd scripts
python camera_detect_advanced.py file ../test_image.jpg ../output_skeleton.png
```

## 활용 사례

### 1. AI 아바타 생성
포즈 + 손 + 얼굴을 모두 감지하여 정교한 아바타 이미지 생성

### 2. 댄스/운동 자세 분석
포즈 품질 점수로 자세의 정확도 평가

### 3. 제스처 인식
손가락 키포인트로 손 제스처 인식

### 4. 표정 분석
얼굴 랜드마크로 감정/표정 분석

## 제한사항

1. **여러 사람 감지**: MediaPipe는 기본적으로 단일 인물용. 다중 인물은 제한적
2. **처리 속도**: 고도화 버전은 기본 버전보다 2-3배 느림
3. **메모리**: 얼굴 랜드마크 468개 포함 시 메모리 사용량 증가

## 문제 해결

### 포즈를 감지할 수 없습니다
- 이미지가 너무 어둡거나 흐림
- 사람이 너무 작거나 잘림
- 카메라와의 거리가 너무 가깝거나 멈

해결: 조명 개선, 카메라 위치 조정

### 손/얼굴이 감지되지 않음
- 손이나 얼굴이 가려짐
- 이미지 해상도가 낮음

해결: `draw_hands=false`, `draw_face=false`로 비활성화

### 품질 점수가 낮음
- 포즈가 불완전하거나 일부만 보임

해결: `min_quality`를 낮추거나 포즈를 더 명확하게

## 라이센스
MediaPipe: Apache License 2.0
