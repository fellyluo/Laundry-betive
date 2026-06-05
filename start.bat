@echo off
REM ====================================================
REM  LaundryPro (Laravel) - launcher
REM  Double-click this file to start the app, then open
REM  http://127.0.0.1:8000 in your browser.
REM ====================================================
cd /d "%~dp0"
echo Menjalankan LaundryPro di http://127.0.0.1:8000 ...
echo Tekan CTRL+C untuk berhenti.
"C:\xampp\php\php.exe" artisan serve --host=127.0.0.1 --port=8000
pause
