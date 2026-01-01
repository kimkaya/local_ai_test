@echo off
echo Downloading Stable Diffusion 1.5 model (compatible version)...
echo This will download approximately 4GB file
echo.

cd /d "C:\xampp\htdocs\ai_test_sec\sd-webui\models\Stable-diffusion"

curl -L -o "v1-5-pruned-emaonly.safetensors" "https://huggingface.co/runwayml/stable-diffusion-v1-5/resolve/main/v1-5-pruned-emaonly.safetensors"

echo.
echo Download complete! The model is saved in:
echo %CD%\v1-5-pruned-emaonly.safetensors
echo.
echo Now restart SD WebUI with webui-user.bat
pause
