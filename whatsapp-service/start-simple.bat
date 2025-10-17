@echo off
REM Canonical start script for WhatsApp service (safe, minimal)
cd /d "%~dp0"
set PATH=C:\Program Files\nodejs;%PATH%
set NODE_OPTIONS=--openssl-legacy-provider
echo Starting WhatsApp service (canonical)...
start "WhatsApp Service" cmd /k "node server.js"