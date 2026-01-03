#!/usr/bin/env python3
"""
Piper TTS 모델 다운로드 스크립트
"""

import requests
import sys
from pathlib import Path


def download_file(url, destination):
    """파일 다운로드"""
    print(f"다운로드 중: {url}")
    response = requests.get(url, stream=True)
    response.raise_for_status()

    total_size = int(response.headers.get('content-length', 0))
    downloaded = 0

    with open(destination, 'wb') as f:
        for chunk in response.iter_content(chunk_size=8192):
            if chunk:
                f.write(chunk)
                downloaded += len(chunk)
                if total_size > 0:
                    percent = (downloaded / total_size) * 100
                    print(f"\r진행률: {percent:.1f}%", end='', flush=True)

    print("\n다운로드 완료!")


def main():
    """메인 함수"""
    # 모델 저장 경로
    model_dir = Path(__file__).parent.parent / "models" / "tts"
    model_dir.mkdir(parents=True, exist_ok=True)

    # Piper 공식 모델 저장소 URL
    base_url = "https://huggingface.co/rhasspy/piper-voices/resolve/main"

    # 영어 모델 (품질 좋음, 빠름)
    model_name = "en_US-lessac-medium"

    print(f"Piper TTS 모델 다운로드: {model_name}")
    print(f"저장 위치: {model_dir}")

    try:
        # ONNX 모델 파일
        model_url = f"{base_url}/en/en_US/lessac/medium/en_US-lessac-medium.onnx"
        model_path = model_dir / f"{model_name}.onnx"
        download_file(model_url, model_path)

        # 설정 파일
        config_url = f"{base_url}/en/en_US/lessac/medium/en_US-lessac-medium.onnx.json"
        config_path = model_dir / f"{model_name}.onnx.json"
        download_file(config_url, config_path)

        print("\n모델 다운로드 완료!")
        print(f"모델 파일: {model_path}")
        print(f"설정 파일: {config_path}")

    except Exception as e:
        print(f"\n오류 발생: {str(e)}", file=sys.stderr)
        sys.exit(1)


if __name__ == "__main__":
    main()
