@echo off
echo Starting CSS compilation...

REM Create optimized directory if it doesn't exist
if not exist "css\optimized" mkdir "css\optimized"

REM Copy the build.css file to optimized directory as main.css
copy "css\build.css" "css\optimized\main.css"

REM Create a simple minified version (remove comments and extra spaces)
powershell -Command "(Get-Content 'css\build.css') -replace '/\*.*?\*/', '' -replace '\s+', ' ' -replace ';\s*', ';' -replace '\s*{\s*', '{' -replace '\s*}\s*', '}' | Set-Content 'css\optimized\main.min.css'"

echo CSS compilation completed!
echo Files created:
echo - css\optimized\main.css (development version)
echo - css\optimized\main.min.css (minified version)
pause