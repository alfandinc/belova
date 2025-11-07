@echo off
cd /d "%~dp0"
set WHATSAPP_AUTO_INIT=true
set WHATSAPP_MAX_SESSIONS=20
set WHATSAPP_SYNC_TOKEN=a1f5d9c3b7e24f6a9c8d3e1b0f2a7c6d
set WHATSAPP_LARAVEL_POLL_SECONDS=30
set WHATSAPP_LARAVEL_URL=http://localhost/belova/public/index.php
start "WhatsAppService" node server.js
