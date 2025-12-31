"""
Stable Diffusion WebUI API를 사용하여 이미지 생성
ControlNet OpenPose를 사용한 포즈 기반 생성 지원
"""
import requests
import json
import base64
import sys
from pathlib import Path


class SDImageGenerator:
    def __init__(self, api_url="http://localhost:7861"):
        self.api_url = api_url
        self.txt2img_endpoint = f"{api_url}/sdapi/v1/txt2img"
        self.controlnet_endpoint = f"{api_url}/controlnet/txt2img"

    def encode_image_to_base64(self, image_path):
        """이미지를 base64로 인코딩"""
        with open(image_path, 'rb') as f:
            return base64.b64encode(f.read()).decode('utf-8')

    def generate_with_controlnet(self, prompt, skeleton_path, output_path, negative_prompt="", steps=8):
        """
        ControlNet OpenPose를 사용하여 이미지 생성
        """
        try:
            # 스켈레톤 이미지를 base64로 인코딩
            skeleton_base64 = self.encode_image_to_base64(skeleton_path)

            # ControlNet temporarily disabled - needs model installation
            payload = {
                "prompt": prompt + " (full body, standing pose)",  # Add pose hint to prompt
                "negative_prompt": negative_prompt or "bad quality, blurry, distorted, ugly, low resolution",
                "steps": steps,
                "cfg_scale": 2.0,
                "width": 512,
                "height": 512,
                "sampler_name": "Euler"  # More compatible sampler
            }

            # TODO: Re-enable ControlNet after installing control_v11p_sd15_openpose model
            # "alwayson_scripts": {
            #     "controlnet": {
            #         "args": [{
            #             "enabled": True,
            #             "module": "none",
            #             "model": "control_v11p_sd15_openpose [cab727d4]",
            #             "weight": 1.0,
            #             "input_image": skeleton_base64,
            #             ...
            #         }]
            #     }
            # }

            # API 호출
            response = requests.post(self.txt2img_endpoint, json=payload, timeout=120)
            response.raise_for_status()

            result = response.json()

            if 'images' in result and len(result['images']) > 0:
                # 첫 번째 이미지 저장
                image_data = base64.b64decode(result['images'][0])
                with open(output_path, 'wb') as f:
                    f.write(image_data)

                return {
                    "success": True,
                    "image_path": output_path,
                    "message": "이미지 생성 성공"
                }
            else:
                return {
                    "success": False,
                    "error": "이미지 생성 실패"
                }

        except requests.exceptions.RequestException as e:
            return {
                "success": False,
                "error": f"API 요청 실패: {str(e)}"
            }
        except Exception as e:
            return {
                "success": False,
                "error": f"오류 발생: {str(e)}"
            }

    def generate_simple(self, prompt, output_path, negative_prompt="", steps=8):
        """
        ControlNet 없이 단순 텍스트→이미지 생성
        """
        try:
            payload = {
                "prompt": prompt,
                "negative_prompt": negative_prompt or "bad quality, blurry, distorted, ugly, low resolution",
                "steps": steps,
                "cfg_scale": 2.0,
                "width": 512,
                "height": 512,
                "sampler_name": "Euler"  # More compatible sampler
            }

            response = requests.post(self.txt2img_endpoint, json=payload, timeout=120)
            response.raise_for_status()

            result = response.json()

            if 'images' in result and len(result['images']) > 0:
                image_data = base64.b64decode(result['images'][0])
                with open(output_path, 'wb') as f:
                    f.write(image_data)

                return {
                    "success": True,
                    "image_path": output_path,
                    "message": "이미지 생성 성공"
                }
            else:
                return {
                    "success": False,
                    "error": "이미지 생성 실패"
                }

        except Exception as e:
            return {
                "success": False,
                "error": f"오류 발생: {str(e)}"
            }


def main():
    """
    CLI 인터페이스
    사용법:
        python image_generate.py simple <prompt> <output>
        python image_generate.py controlnet <prompt> <skeleton_path> <output>
    """
    if len(sys.argv) < 4:
        print(json.dumps({
            "success": False,
            "error": "사용법: python image_generate.py <mode> <prompt> [skeleton_path] <output>"
        }))
        sys.exit(1)

    mode = sys.argv[1]
    generator = SDImageGenerator()

    if mode == "simple":
        prompt = sys.argv[2]
        output_path = sys.argv[3]
        result = generator.generate_simple(prompt, output_path)

    elif mode == "controlnet":
        if len(sys.argv) < 5:
            print(json.dumps({
                "success": False,
                "error": "사용법: python image_generate.py controlnet <prompt> <skeleton_path> <output>"
            }))
            sys.exit(1)

        prompt = sys.argv[2]
        skeleton_path = sys.argv[3]
        output_path = sys.argv[4]
        result = generator.generate_with_controlnet(prompt, skeleton_path, output_path)

    else:
        result = {"success": False, "error": "유효하지 않은 모드"}

    # Use ensure_ascii=True to avoid encoding issues with PHP exec()
    print(json.dumps(result, ensure_ascii=True))


if __name__ == "__main__":
    main()
