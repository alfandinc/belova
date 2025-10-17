Minimal WhatsApp microservice using whatsapp-web.js (wwebjs)

## Node.js Compatibility Notice
**IMPORTANT**: Node.js v22 has compatibility issues on Windows. If you encounter crypto assertion failures, please:

1. **Recommended**: Downgrade to Node.js v18 LTS or v20 LTS
   - Download from: https://nodejs.org/
   - Choose LTS version (18.19.0 or 20.x.x)

2. **Alternative**: Use the provided batch file
   - Run `start-service.bat` instead of `npm start`

## Quick start:

1. From the `whatsapp-service` directory run:

   npm install

2. Start the service:
   - `server.js` - Node.js service entrypoint
   - `start-simple.bat` - canonical Windows start script (will open a visible cmd window)
   - `backups/` - contains archived copies of older start scripts and cleanup notes

   Cleanup performed: older redundant batch scripts and README_CLEANUP were archived into `backups/`. Use `start-simple.bat` to start the service on Windows. To restore an archived script, copy it from `backups/` back into this folder.

3. First run will require scanning the QR code. The service exposes /status which returns a `qrcode` data URL when not yet authenticated. Open the URL in a browser to scan.

4. Send messages by POST /send with JSON body:

   { "number": "62812...", "message": "Hello" }

## Troubleshooting:
- If you get crypto assertion failures, downgrade Node.js to v18 LTS
- Make sure Node and npm are installed on the server
- For production, run with a process manager and secure the endpoints
