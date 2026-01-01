import requests
import json

url = "http://localhost:7861/sdapi/v1/txt2img"

payload = {
    "prompt": "a beautiful cat",
    "negative_prompt": "bad quality",
    "steps": 4,
    "cfg_scale": 2.0,
    "width": 512,
    "height": 512,
    "sampler_name": "Euler"
}

print("Testing simple image generation...")
print(f"URL: {url}")
print(f"Payload: {json.dumps(payload, indent=2)}")

try:
    response = requests.post(url, json=payload, timeout=120)
    print(f"\nStatus Code: {response.status_code}")

    if response.status_code == 200:
        print("SUCCESS! Image generation works!")
        result = response.json()
        print(f"Response keys: {result.keys()}")
    else:
        print(f"ERROR: {response.status_code}")
        print(f"Response: {response.text}")

except Exception as e:
    print(f"Exception: {e}")
