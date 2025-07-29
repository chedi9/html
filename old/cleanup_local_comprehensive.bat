@echo off
echo === COMPREHENSIVE WORKSPACE CLEANUP (LOCAL) ===
echo.

REM Check if PowerShell is available
powershell -Command "Get-Host" >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ PowerShell is not available on this system
    echo Please install PowerShell or use the PHP version
    pause
    exit /b 1
)

REM Run the comprehensive PowerShell cleanup script
powershell -ExecutionPolicy Bypass -File "cleanup_local_comprehensive.ps1"

echo.
echo ✅ Comprehensive cleanup completed!
echo.
pause 