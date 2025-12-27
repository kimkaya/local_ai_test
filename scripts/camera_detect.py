"""
카메라에서 얼굴/포즈를 감지하고 OpenPose 스켈레톤 이미지를 생성
"""
import cv2
import mediapipe as mp
import numpy as np
from PIL import Image
import sys
import json
import base64
from io import BytesIO

class PoseDetector:
    def __init__(self):
        self.mp_pose = mp.solutions.pose
        self.mp_drawing = mp.solutions.drawing_utils
        self.pose = self.mp_pose.Pose(
            static_image_mode=True,
            model_complexity=1,
            enable_segmentation=False,
            min_detection_confidence=0.5
        )

    def process_image(self, image_path, output_path):
        """
        이미지에서 포즈를 감지하고 OpenPose 스타일 스켈레톤 생성
        """
        try:
            # 이미지 읽기
            image = cv2.imread(image_path)
            if image is None:
                return {"success": False, "error": "이미지를 읽을 수 없습니다"}

            # RGB로 변환
            image_rgb = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)

            # 포즈 감지
            results = self.pose.process(image_rgb)

            if not results.pose_landmarks:
                return {"success": False, "error": "포즈를 감지할 수 없습니다"}

            # 검은 배경에 스켈레톤만 그리기 (ControlNet OpenPose 형식)
            h, w = image.shape[:2]
            skeleton_image = np.zeros((h, w, 3), dtype=np.uint8)

            # 스켈레톤 그리기
            self.mp_drawing.draw_landmarks(
                skeleton_image,
                results.pose_landmarks,
                self.mp_pose.POSE_CONNECTIONS,
                landmark_drawing_spec=self.mp_drawing.DrawingSpec(
                    color=(255, 255, 255),
                    thickness=2,
                    circle_radius=2
                ),
                connection_drawing_spec=self.mp_drawing.DrawingSpec(
                    color=(255, 255, 255),
                    thickness=2
                )
            )

            # 저장
            cv2.imwrite(output_path, skeleton_image)

            # Base64 인코딩 (프론트엔드 프리뷰용)
            _, buffer = cv2.imencode('.png', skeleton_image)
            skeleton_base64 = base64.b64encode(buffer).decode('utf-8')

            return {
                "success": True,
                "skeleton_path": output_path,
                "skeleton_base64": skeleton_base64,
                "message": "포즈 감지 성공"
            }

        except Exception as e:
            return {"success": False, "error": str(e)}

    def process_webcam_frame(self, frame_base64, output_path):
        """
        웹캠 프레임 (base64)에서 포즈 감지
        """
        try:
            # Base64 디코딩
            image_data = base64.b64decode(frame_base64.split(',')[1] if ',' in frame_base64 else frame_base64)
            nparr = np.frombuffer(image_data, np.uint8)
            image = cv2.imdecode(nparr, cv2.IMREAD_COLOR)

            if image is None:
                return {"success": False, "error": "프레임을 디코딩할 수 없습니다"}

            # RGB로 변환
            image_rgb = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)

            # 포즈 감지
            results = self.pose.process(image_rgb)

            if not results.pose_landmarks:
                return {"success": False, "error": "포즈를 감지할 수 없습니다"}

            # 스켈레톤 이미지 생성
            h, w = image.shape[:2]
            skeleton_image = np.zeros((h, w, 3), dtype=np.uint8)

            self.mp_drawing.draw_landmarks(
                skeleton_image,
                results.pose_landmarks,
                self.mp_pose.POSE_CONNECTIONS,
                landmark_drawing_spec=self.mp_drawing.DrawingSpec(
                    color=(255, 255, 255),
                    thickness=3,
                    circle_radius=3
                ),
                connection_drawing_spec=self.mp_drawing.DrawingSpec(
                    color=(255, 255, 255),
                    thickness=3
                )
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
                "message": "포즈 감지 성공"
            }

        except Exception as e:
            return {"success": False, "error": str(e)}


def main():
    """
    CLI 인터페이스
    사용법: python camera_detect.py <mode> <input> <output>
    mode: file 또는 webcam
    """
    if len(sys.argv) < 4:
        print(json.dumps({
            "success": False,
            "error": "사용법: python camera_detect.py <mode> <input> <output>"
        }))
        sys.exit(1)

    mode = sys.argv[1]
    input_data = sys.argv[2]
    output_path = sys.argv[3]

    detector = PoseDetector()

    if mode == "file":
        result = detector.process_image(input_data, output_path)
    elif mode == "webcam":
        result = detector.process_webcam_frame(input_data, output_path)
    else:
        result = {"success": False, "error": "유효하지 않은 모드"}

    print(json.dumps(result, ensure_ascii=False))


if __name__ == "__main__":
    main()
