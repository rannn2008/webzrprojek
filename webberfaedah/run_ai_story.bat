@echo off
REM ================================================
REM AI Story Generator — Auto Runner
REM Jalankan script ini via Windows Task Scheduler
REM setiap hari pukul 07:00, 13:00, dan 19:00
REM ================================================

set PHP_PATH=C:\xampp\php\php.exe
set SCRIPT_PATH=C:\xampp\htdocs\webberfaedah\api\ai_story_generator.php

echo [%DATE% %TIME%] Memulai AI Story Generator...
"%PHP_PATH%" "%SCRIPT_PATH%"
echo [%DATE% %TIME%] Selesai.
