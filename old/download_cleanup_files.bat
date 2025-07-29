@echo off
echo === DOWNLOAD CLEANUP FILES ===
echo.

REM Create a downloads folder
if not exist "cleanup_downloads" mkdir cleanup_downloads

echo Copying cleanup files to local downloads folder...
echo.

REM Copy the cleanup scripts
copy "cleanup_server.php" "cleanup_downloads\"
copy "cleanup_local_comprehensive.ps1" "cleanup_downloads\"
copy "cleanup_local_comprehensive.bat" "cleanup_downloads\"

echo ✅ Files copied to cleanup_downloads folder:
echo.
echo 📁 cleanup_server.php - Server-side cleanup (PHP)
echo 📁 cleanup_local_comprehensive.ps1 - Local cleanup (PowerShell)
echo 📁 cleanup_local_comprehensive.bat - Local cleanup runner
echo.

echo 🎯 USAGE INSTRUCTIONS:
echo.
echo FOR SERVER (with PHP):
echo 1. Upload cleanup_server.php to your server
echo 2. Run it in browser: yourdomain.com/cleanup_server.php
echo 3. Delete the script after use
echo.
echo FOR LOCAL PC (Windows):
echo 1. Double-click cleanup_local_comprehensive.bat
echo 2. Or run: powershell -ExecutionPolicy Bypass -File "cleanup_local_comprehensive.ps1"
echo 3. Delete the scripts after use
echo.

echo 📂 Files are ready in: cleanup_downloads\
echo.
pause 