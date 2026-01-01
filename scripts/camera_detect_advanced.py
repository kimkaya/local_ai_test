"""
고도화된 포즈 감지 시스템
- 전신 포즈 (33 키포인트)
- 손가락 (각 손 21 키포인트)
- 얼굴 랜드마크 (468 키포인트)
- 여러 사람 감지
- 포즈 품질 점수
"""
import cv2
import mediapipe as mp
import numpy as np
import sys
import json
import base64
from typing import List, Dict, Optional, Tuple


class AdvancedPoseDetector:
    def __init__(self, model_complexity=1, min_detection_confidence=0.5, min_tracking_confidence=0.5):
        """
        고도화된 포즈 감지기 초기화

        Args:
            model_complexity: 0 (빠름), 1 (균형), 2 (정확)
            min_detection_confidence: 감지 신뢰도 임계값 (0.0-1.0)
            min_tracking_confidence: 추적 신뢰도 임계값 (0.0-1.0)
        """
        # Holistic: 포즈 + 손 + 얼굴 통합 감지
        self.mp_holistic = mp.solutions.holistic
        self.mp_drawing = mp.solutions.drawing_utils
        self.mp_drawing_styles = mp.solutions.drawing_styles

        self.holistic = self.mp_holistic.Holistic(
            static_image_mode=True,
            model_complexity=model_complexity,
            enable_segmentation=False,
            refine_face_landmarks=True,
            min_detection_confidence=min_detection_confidence,
            min_tracking_confidence=min_tracking_confidence
        )

        # 여러 사람 감지용 (포즈만)
        self.mp_pose = mp.solutions.pose
        self.pose_detector = self.mp_pose.Pose(
            static_image_mode=True,
            model_complexity=model_complexity,
            enable_segmentation=False,
            min_detection_confidence=min_detection_confidence
        )

    def calculate_pose_quality(self, landmarks, landmark_type="pose") -> Dict:
        """
        포즈 품질 점수 계산

        Returns:
            dict: {
                "overall_score": float,  # 전체 점수 (0-100)
                "visibility_score": float,  # 가시성 점수
                "presence_score": float,  # 존재 점수
                "coverage": float,  # 감지된 키포인트 비율
                "quality_level": str  # "excellent", "good", "fair", "poor"
            }
        """
        if not landmarks:
            return {
                "overall_score": 0,
                "visibility_score": 0,
                "presence_score": 0,
                "coverage": 0,
                "quality_level": "none"
            }

        visible_count = 0
        present_count = 0
        total_visibility = 0
        total_presence = 0
        total_landmarks = len(landmarks.landmark)

        for landmark in landmarks.landmark:
            if hasattr(landmark, 'visibility'):
                total_visibility += landmark.visibility
                if landmark.visibility > 0.5:
                    visible_count += 1

            if hasattr(landmark, 'presence'):
                total_presence += landmark.presence
                if landmark.presence > 0.5:
                    present_count += 1

        visibility_score = (total_visibility / total_landmarks) * 100 if total_landmarks > 0 else 0
        presence_score = (total_presence / total_landmarks) * 100 if total_landmarks > 0 else 0
        coverage = (visible_count / total_landmarks) * 100 if total_landmarks > 0 else 0

        # 전체 점수 (가중 평균)
        overall_score = (visibility_score * 0.5 + presence_score * 0.3 + coverage * 0.2)

        # 품질 등급
        if overall_score >= 80:
            quality_level = "excellent"
        elif overall_score >= 60:
            quality_level = "good"
        elif overall_score >= 40:
            quality_level = "fair"
        else:
            quality_level = "poor"

        return {
            "overall_score": round(overall_score, 2),
            "visibility_score": round(visibility_score, 2),
            "presence_score": round(presence_score, 2),
            "coverage": round(coverage, 2),
            "quality_level": quality_level,
            "visible_landmarks": visible_count,
            "total_landmarks": total_landmarks
        }

    def draw_advanced_skeleton(self, image: np.ndarray, results,
                              draw_pose=True, draw_hands=True,
                              draw_face=True, colorful=False) -> np.ndarray:
        """
        고도화된 스켈레톤 그리기

        Args:
            image: 그릴 이미지
            results: Holistic 결과
            draw_pose: 포즈 그리기 여부
            draw_hands: 손 그리기 여부
            draw_face: 얼굴 그리기 여부
            colorful: 컬러풀한 스켈레톤 (False면 흰색)

        Returns:
            스켈레톤이 그려진 이미지
        """
        if colorful:
            # OpenPose 스타일 컬러 스켈레톤
            if draw_pose and results.pose_landmarks:
                self.mp_drawing.draw_landmarks(
                    image,
                    results.pose_landmarks,
                    self.mp_holistic.POSE_CONNECTIONS,
                    landmark_drawing_spec=self.mp_drawing_styles.get_default_pose_landmarks_style()
                )

            if draw_hands:
                # 왼손 (빨간색)
                if results.left_hand_landmarks:
                    self.mp_drawing.draw_landmarks(
                        image,
                        results.left_hand_landmarks,
                        self.mp_holistic.HAND_CONNECTIONS,
                        landmark_drawing_spec=mp.solutions.drawing_utils.DrawingSpec(
                            color=(255, 0, 0), thickness=2, circle_radius=2
                        ),
                        connection_drawing_spec=mp.solutions.drawing_utils.DrawingSpec(
                            color=(255, 100, 100), thickness=2
                        )
                    )

                # 오른손 (초록색)
                if results.right_hand_landmarks:
                    self.mp_drawing.draw_landmarks(
                        image,
                        results.right_hand_landmarks,
                        self.mp_holistic.HAND_CONNECTIONS,
                        landmark_drawing_spec=mp.solutions.drawing_utils.DrawingSpec(
                            color=(0, 255, 0), thickness=2, circle_radius=2
                        ),
                        connection_drawing_spec=mp.solutions.drawing_utils.DrawingSpec(
                            color=(100, 255, 100), thickness=2
                        )
                    )

            if draw_face and results.face_landmarks:
                # 얼굴 (파란색)
                self.mp_drawing.draw_landmarks(
                    image,
                    results.face_landmarks,
                    self.mp_holistic.FACEMESH_CONTOURS,
                    landmark_drawing_spec=None,
                    connection_drawing_spec=mp.solutions.drawing_utils.DrawingSpec(
                        color=(80, 110, 255), thickness=1
                    )
                )
        else:
            # ControlNet용 흰색 스켈레톤
            white_spec_landmark = mp.solutions.drawing_utils.DrawingSpec(
                color=(255, 255, 255), thickness=3, circle_radius=3
            )
            white_spec_connection = mp.solutions.drawing_utils.DrawingSpec(
                color=(255, 255, 255), thickness=2
            )

            if draw_pose and results.pose_landmarks:
                self.mp_drawing.draw_landmarks(
                    image, results.pose_landmarks,
                    self.mp_holistic.POSE_CONNECTIONS,
                    landmark_drawing_spec=white_spec_landmark,
                    connection_drawing_spec=white_spec_connection
                )

            if draw_hands:
                if results.left_hand_landmarks:
                    self.mp_drawing.draw_landmarks(
                        image, results.left_hand_landmarks,
                        self.mp_holistic.HAND_CONNECTIONS,
                        landmark_drawing_spec=white_spec_landmark,
                        connection_drawing_spec=white_spec_connection
                    )

                if results.right_hand_landmarks:
                    self.mp_drawing.draw_landmarks(
                        image, results.right_hand_landmarks,
                        self.mp_holistic.HAND_CONNECTIONS,
                        landmark_drawing_spec=white_spec_landmark,
                        connection_drawing_spec=white_spec_connection
                    )

            if draw_face and results.face_landmarks:
                # 얼굴은 테두리만 (전체는 너무 복잡)
                self.mp_drawing.draw_landmarks(
                    image, results.face_landmarks,
                    self.mp_holistic.FACEMESH_CONTOURS,
                    landmark_drawing_spec=None,
                    connection_drawing_spec=mp.solutions.drawing_utils.DrawingSpec(
                        color=(255, 255, 255), thickness=1
                    )
                )

        return image

    def process_single_person(self, image: np.ndarray, output_path: str,
                             draw_hands=True, draw_face=True, colorful=False) -> Dict:
        """
        단일 인물 감지 (포즈 + 손 + 얼굴)

        Args:
            image: 입력 이미지 (BGR)
            output_path: 저장 경로
            draw_hands: 손 그리기 여부
            draw_face: 얼굴 그리기 여부
            colorful: 컬러풀한 스켈레톤

        Returns:
            결과 딕셔너리
        """
        try:
            # RGB로 변환
            image_rgb = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)

            # Holistic 감지
            results = self.holistic.process(image_rgb)

            # 포즈 감지 확인
            if not results.pose_landmarks:
                return {"success": False, "error": "포즈를 감지할 수 없습니다"}

            # 품질 점수 계산
            pose_quality = self.calculate_pose_quality(results.pose_landmarks, "pose")

            # 검은 배경에 스켈레톤 그리기
            h, w = image.shape[:2]
            skeleton_image = np.zeros((h, w, 3), dtype=np.uint8)

            # 스켈레톤 그리기
            skeleton_image = self.draw_advanced_skeleton(
                skeleton_image, results,
                draw_pose=True,
                draw_hands=draw_hands,
                draw_face=draw_face,
                colorful=colorful
            )

            # 저장
            cv2.imwrite(output_path, skeleton_image)

            # Base64 인코딩
            _, buffer = cv2.imencode('.png', skeleton_image)
            skeleton_base64 = base64.b64encode(buffer).decode('utf-8')

            return {
                "success": True,
                "skeleton_path": output_path,
                "skeleton_base64": skeleton_base64,
                "pose_quality": pose_quality,
                "detected_features": {
                    "pose": True,
                    "left_hand": results.left_hand_landmarks is not None,
                    "right_hand": results.right_hand_landmarks is not None,
                    "face": results.face_landmarks is not None
                },
                "message": f"포즈 감지 성공 (품질: {pose_quality['quality_level']})"
            }

        except Exception as e:
            return {"success": False, "error": str(e)}

    def detect_multiple_people(self, image: np.ndarray) -> List[Dict]:
        """
        여러 사람 감지 (간단한 구현)
        주의: MediaPipe는 기본적으로 단일 인물용이므로,
        이미지를 분할하거나 다른 방법이 필요할 수 있음

        현재는 전체 이미지에서 가장 명확한 포즈 하나를 반환
        """
        image_rgb = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)
        results = self.holistic.process(image_rgb)

        people = []
        if results.pose_landmarks:
            quality = self.calculate_pose_quality(results.pose_landmarks, "pose")
            people.append({
                "person_id": 0,
                "pose_landmarks": results.pose_landmarks,
                "quality": quality
            })

        return people

    def process_image(self, image_path: str, output_path: str,
                     draw_hands=True, draw_face=True,
                     colorful=False, min_quality=0.0) -> Dict:
        """
        이미지 파일 처리

        Args:
            image_path: 입력 이미지 경로
            output_path: 출력 이미지 경로
            draw_hands: 손 그리기
            draw_face: 얼굴 그리기
            colorful: 컬러풀한 스켈레톤
            min_quality: 최소 품질 점수 (0-100)
        """
        try:
            image = cv2.imread(image_path)
            if image is None:
                return {"success": False, "error": "이미지를 읽을 수 없습니다"}

            result = self.process_single_person(
                image, output_path,
                draw_hands=draw_hands,
                draw_face=draw_face,
                colorful=colorful
            )

            # 품질 필터링
            if result.get("success") and result.get("pose_quality"):
                if result["pose_quality"]["overall_score"] < min_quality:
                    return {
                        "success": False,
                        "error": f"포즈 품질이 낮습니다 (점수: {result['pose_quality']['overall_score']:.1f})",
                        "pose_quality": result["pose_quality"]
                    }

            return result

        except Exception as e:
            return {"success": False, "error": str(e)}

    def process_webcam_frame(self, frame_base64: str, output_path: str,
                           draw_hands=True, draw_face=True,
                           colorful=False) -> Dict:
        """
        웹캠 프레임 처리 (base64)
        """
        try:
            # Base64 디코딩
            image_data = base64.b64decode(
                frame_base64.split(',')[1] if ',' in frame_base64 else frame_base64
            )
            nparr = np.frombuffer(image_data, np.uint8)
            image = cv2.imdecode(nparr, cv2.IMREAD_COLOR)

            if image is None:
                return {"success": False, "error": "프레임을 디코딩할 수 없습니다"}

            return self.process_single_person(
                image, output_path,
                draw_hands=draw_hands,
                draw_face=draw_face,
                colorful=colorful
            )

        except Exception as e:
            return {"success": False, "error": str(e)}

    def __del__(self):
        """리소스 정리"""
        if hasattr(self, 'holistic'):
            self.holistic.close()
        if hasattr(self, 'pose_detector'):
            self.pose_detector.close()


def main():
    """
    CLI 인터페이스
    사용법: python camera_detect_advanced.py <mode> <input> <output> [options]

    mode: file 또는 webcam
    options (JSON): {"draw_hands": true, "draw_face": true, "colorful": false}
    """
    if len(sys.argv) < 4:
        print(json.dumps({
            "success": False,
            "error": "사용법: python camera_detect_advanced.py <mode> <input> <output> [options_json]"
        }, ensure_ascii=True))
        sys.exit(1)

    mode = sys.argv[1]
    input_data = sys.argv[2]
    output_path = sys.argv[3]

    # 옵션 파싱
    options = {
        "draw_hands": True,
        "draw_face": True,
        "colorful": False,
        "min_quality": 0.0
    }

    if len(sys.argv) >= 5:
        try:
            user_options = json.loads(sys.argv[4])
            options.update(user_options)
        except:
            pass

    detector = AdvancedPoseDetector(
        model_complexity=1,
        min_detection_confidence=0.5
    )

    if mode == "file":
        result = detector.process_image(
            input_data, output_path,
            draw_hands=options.get("draw_hands", True),
            draw_face=options.get("draw_face", True),
            colorful=options.get("colorful", False),
            min_quality=options.get("min_quality", 0.0)
        )
    elif mode == "webcam":
        result = detector.process_webcam_frame(
            input_data, output_path,
            draw_hands=options.get("draw_hands", True),
            draw_face=options.get("draw_face", True),
            colorful=options.get("colorful", False)
        )
    else:
        result = {"success": False, "error": "유효하지 않은 모드"}

    print(json.dumps(result, ensure_ascii=True))


if __name__ == "__main__":
    main()
