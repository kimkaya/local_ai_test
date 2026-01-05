#!/usr/bin/env python3
"""
TTS (Text-to-Speech) 서비스
ElevenLabs를 사용하여 감정이 풍부한 음성으로 변환
"""

import sys
import json
import os
from pathlib import Path

try:
    from elevenlabs import VoiceSettings
    from elevenlabs.client import ElevenLabs
except ImportError:
    print(json.dumps({
        "success": False,
        "error": "ElevenLabs가 설치되지 않았습니다. 'pip install elevenlabs'를 실행하세요."
    }))
    sys.exit(1)


class TTSService:
    def __init__(self):
        self.output_path = Path(__file__).parent.parent / "outputs" / "tts"
        self.output_path.mkdir(parents=True, exist_ok=True)

        # API 키 로드 (환경 변수 또는 설정 파일)
        config_file = Path(__file__).parent.parent / "config.json"
        if config_file.exists():
            with open(config_file, 'r', encoding='utf-8') as f:
                config = json.load(f)
                self.api_key = config.get('elevenlabs_api_key', '')
        else:
            self.api_key = os.getenv('ELEVENLABS_API_KEY', '')

        if self.api_key:
            self.client = ElevenLabs(api_key=self.api_key)
        else:
            self.client = None

    def synthesize(self, text, output_file="output.mp3"):
        """텍스트를 음성으로 변환"""
        try:
            if not self.client:
                return False, "API 키가 설정되지 않았습니다. config.json에 elevenlabs_api_key를 추가하세요.", None

            output_path = self.output_path / output_file

            # ElevenLabs 음성 합성
            # 'Rachel' 음성 사용 (감정 표현 우수, 다국어 지원)
            audio = self.client.text_to_speech.convert(
                voice_id="21m00Tcm4TlvDq8ikWAM",  # Rachel 음성 ID
                text=text,
                model_id="eleven_multilingual_v2",  # 다국어 모델 (한국어 지원)
                voice_settings=VoiceSettings(
                    stability=0.5,  # 안정성 (0.0-1.0)
                    similarity_boost=0.75,  # 유사성 강화
                    style=0.5,  # 스타일 강도
                    use_speaker_boost=True  # 화자 부스트
                )
            )

            # 오디오를 바이트로 변환하여 저장
            with open(output_path, 'wb') as f:
                for chunk in audio:
                    if isinstance(chunk, bytes):
                        f.write(chunk)
                    else:
                        f.write(chunk)

            return True, "음성 생성 완료", str(output_path)
        except Exception as e:
            return False, f"음성 생성 실패: {str(e)}", None


def main():
    """메인 함수"""
    if len(sys.argv) < 2:
        print(json.dumps({
            "success": False,
            "error": "사용법: python tts_service.py <text> [output_filename]"
        }))
        sys.exit(1)

    text = sys.argv[1]
    output_file = sys.argv[2] if len(sys.argv) > 2 else "output.mp3"

    tts = TTSService()
    success, message, audio_path = tts.synthesize(text, output_file)

    result = {
        "success": success,
        "message": message
    }

    if success:
        result["audio_path"] = audio_path
        result["audio_url"] = f"/ai_test_sec/outputs/tts/{output_file}"
    else:
        result["error"] = message

    print(json.dumps(result, ensure_ascii=False))


if __name__ == "__main__":
    main()
