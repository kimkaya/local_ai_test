@echo off
echo Testing SD WebUI API...
curl -s "http://localhost:7861/sdapi/v1/sd-models"
echo.
echo.
pause
