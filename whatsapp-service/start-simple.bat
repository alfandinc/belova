@echo off
REM Canonical start script for WhatsApp service (safe, minimal)
cd /d "%~dp0"
set PATH=C:\Program Files\nodejs;%PATH%
set NODE_OPTIONS=--openssl-legacy-provider
echo Starting WhatsApp service (canonical)...
rem Enable auto-initialize of existing sessions on start
set WHATSAPP_AUTO_INIT=true
set WHATSAPP_MAX_SESSIONS=20

start "WhatsApp Service" cmd /k "node server.js"