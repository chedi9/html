@echo off
echo 🧹 تنظيف مساحة العمل (الإصدار المحلي)
echo.

REM Check if PowerShell is available
powershell -Command "Get-Host" >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ PowerShell غير متوفر على هذا النظام
    echo يرجى تثبيت PowerShell أو استخدام الإصدار المطلوب PHP
    pause
    exit /b 1
)

REM Run the PowerShell cleanup script
powershell -ExecutionPolicy Bypass -File "cleanup_workspace.ps1"

echo.
echo ✅ تم الانتهاء من التنظيف!
echo.
pause 