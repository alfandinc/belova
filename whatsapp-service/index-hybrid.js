const { default: makeWASocket, DisconnectReason, useMultiFileAuthState } = require('@whiskeysockets/baileys');
const express = require('express');
const cors = require('cors');
const qrcode = require('qrcode-terminal');
const fs = require('fs');
const path = require('path');

const app = express();
app.use(express.json());
app.use(cors());

let sock;
let isConnecting = false;

// Ensure auth directory exists
const authDir = 'auth_info_baileys';
if (!fs.existsSync(authDir)) {
    fs.mkdirSync(authDir, { recursive: true });
}

// SESSION FILE INTERCEPTOR - Prevent session file creation
function interceptSessionFiles() {
    const originalWriteFileSync = fs.writeFileSync;
    const originalWriteFile = fs.writeFile;
    
    // Intercept synchronous file writes
    fs.writeFileSync = function(filePath, data, options) {
        const fileName = path.basename(filePath);
        
        // BLOCK ALL SESSION FILES
        if (fileName.startsWith('session-') && fileName.endsWith('.json')) {
            console.log(`🚫 BLOCKED session file creation: ${fileName}`);
            console.log(`💾 Session data would be saved to database (simulated)`);
            return; // Don't create the file
        }
        
        // Allow creds.json and other essential files
        return originalWriteFileSync.call(this, filePath, data, options);
    };
    
    // Intercept asynchronous file writes
    fs.writeFile = function(filePath, data, options, callback) {
        if (typeof options === 'function') {
            callback = options;
            options = {};
        }
        
        const fileName = path.basename(filePath);
        
        // BLOCK ALL SESSION FILES
        if (fileName.startsWith('session-') && fileName.endsWith('.json')) {
            console.log(`🚫 BLOCKED async session file creation: ${fileName}`);
            console.log(`💾 Session data would be saved to database (simulated)`);
            if (callback) callback(null);
            return; // Don't create the file
        }
        
        // Allow other files
        return originalWriteFile.call(this, filePath, data, options, callback);
    };
    
    console.log('🛡️ Session file interceptor activated - NO session files will be created!');
}

// Aggressive cleanup for session files only
function aggressiveSessionCleanup() {
    try {
        if (!fs.existsSync(authDir)) return;
        
        const files = fs.readdirSync(authDir);
        let sessionFilesRemoved = 0;
        
        for (const file of files) {
            // Only remove session files, keep creds and other essential files
            if (file.startsWith('session-') && file.endsWith('.json')) {
                try {
                    fs.unlinkSync(path.join(authDir, file));
                    console.log(`🗑️ Removed session file: ${file}`);
                    sessionFilesRemoved++;
                } catch (err) {
                    console.log(`⚠️ Could not remove session file ${file}:`, err.message);
                }
            }
        }
        
        if (sessionFilesRemoved > 0) {
            console.log(`✅ Session cleanup: removed ${sessionFilesRemoved} session files`);
        }
        
        const remainingFiles = fs.readdirSync(authDir);
        console.log(`📊 Auth files remaining: ${remainingFiles.length} (session files: ${remainingFiles.filter(f => f.startsWith('session-')).length})`);
        
    } catch (error) {
        console.log('⚠️ Error during session cleanup:', error.message);
    }
}

async function connectToWhatsApp() {
    if (isConnecting) {
        console.log('⏳ Connection already in progress...');
        return;
    }
    
    isConnecting = true;
    
    try {
        console.log('🛡️ Using HYBRID authentication - creds.json file + NO session files...');
        
        // Activate session file interceptor
        interceptSessionFiles();
        
        // Clean up any existing session files
        aggressiveSessionCleanup();
        
        // Use normal file-based auth for creds, but intercept session files
        const { state, saveCreds } = await useMultiFileAuthState(authDir);
        
        sock = makeWASocket({
            auth: state,
            browser: ['Belova Clinic', 'Chrome', '1.0.0'],
            printQRInTerminal: false,
            defaultQueryTimeoutMs: 60000,
            connectTimeoutMs: 60000,
            keepAliveIntervalMs: 30000,
            generateHighQualityLinkPreview: false,
            syncFullHistory: false,
            // Limit pre-key generation
            maxPreKeys: 2
        });

        // Enhanced creds saving with session cleanup
        sock.ev.on('creds.update', async () => {
            await saveCreds();
            console.log('💾 Credentials updated');
            // Cleanup session files after each save
            setTimeout(() => aggressiveSessionCleanup(), 1000);
        });
        
        sock.ev.on('connection.update', (update) => {
            const { connection, lastDisconnect, qr } = update;
            
            if (qr) {
                console.log('\\n📱 SCAN THIS QR CODE WITH YOUR WHATSAPP:\\n');
                qrcode.generate(qr, { small: true });
                console.log('\\n📱 Open WhatsApp on your phone > Linked Devices > Link a Device\\n');
            }
            
            if(connection === 'close') {
                isConnecting = false;
                const shouldReconnect = (lastDisconnect?.error)?.output?.statusCode !== DisconnectReason.loggedOut;
                console.log('Connection closed, reconnecting:', shouldReconnect);
                
                if(shouldReconnect) {
                    // Clean up session files before reconnecting
                    aggressiveSessionCleanup();
                    setTimeout(() => {
                        connectToWhatsApp();
                    }, 5000);
                }
            } else if(connection === 'open') {
                isConnecting = false;
                console.log('✅ WhatsApp connection opened - Ready to send messages!');
                console.log('🛡️ Session files are BLOCKED - only creds.json allowed!');
                // Cleanup session files after successful connection
                setTimeout(() => aggressiveSessionCleanup(), 2000);
            }
        });

        // Handle incoming messages and forward to Laravel webhook
        sock.ev.on('messages.upsert', async (m) => {
            console.log('📩 New message received:', JSON.stringify(m, undefined, 2));
            
            // Process incoming messages
            const messages = m.messages || [];
            for (const message of messages) {
                // Skip messages from ourselves
                if (message.key.fromMe) continue;
                
                // Extract message data
                const from = message.key.remoteJid;
                const messageText = message.message?.conversation || 
                                  message.message?.extendedTextMessage?.text || '';
                
                if (from && messageText) {
                    console.log(`📨 Processing message from ${from}: ${messageText}`);
                    
                    // Forward to Laravel webhook
                    try {
                        const axios = require('axios');
                        const webhookUrl = 'http://localhost/belova/public/api/whatsapp/webhook';
                        
                        const webhookData = {
                            from: from,
                            message: messageText,
                            timestamp: Date.now(),
                            messageId: message.key.id
                        };
                        
                        console.log('🔄 Forwarding to webhook:', webhookUrl);
                        const response = await axios.post(webhookUrl, webhookData, {
                            timeout: 10000,
                            headers: {
                                'Content-Type': 'application/json',
                                'User-Agent': 'WhatsApp-Baileys-Webhook/1.0'
                            }
                        });
                        
                        console.log('✅ Webhook response:', response.data);
                    } catch (webhookError) {
                        console.error('❌ Webhook error:', webhookError.message);
                        if (webhookError.response) {
                            console.error('❌ Response status:', webhookError.response.status);
                            console.error('❌ Response data:', webhookError.response.data);
                        }
                    }
                }
            }
        });
    
    } catch (error) {
        console.error('❌ Error connecting to WhatsApp:', error.message);
    } finally {
        isConnecting = false;
    }
}

// API endpoints
app.post('/send-message', async (req, res) => {
    try {
        const { number, message } = req.body;
        
        if (!sock || sock.ws.readyState !== sock.ws.OPEN) {
            return res.status(503).json({ 
                success: false, 
                error: 'WhatsApp not connected' 
            });
        }
        
        if (!number || !message) {
            return res.status(400).json({ 
                success: false, 
                error: 'Number and message are required' 
            });
        }
        
        // Clean phone number format
        let cleanNumber = number.replace(/[^0-9]/g, '');
        if (cleanNumber.startsWith('0')) {
            cleanNumber = '62' + cleanNumber.substring(1);
        }
        
        const jid = cleanNumber + '@s.whatsapp.net';
        
        await sock.sendMessage(jid, { 
            text: message 
        });
        
        res.json({ 
            success: true, 
            message: 'Message sent successfully',
            to: jid
        });
        
    } catch (error) {
        console.error('Error sending message:', error);
        res.status(500).json({ 
            success: false, 
            error: error.message || 'Unknown error occurred'
        });
    }
});

app.get('/status', (req, res) => {
    const connected = sock && sock.ws.readyState === sock.ws.OPEN;
    res.json({ 
        connected,
        status: connected ? 'Connected' : 'Disconnected',
        auth_method: 'Hybrid (creds.json + NO session files)'
    });
});

app.get('/health', (req, res) => {
    res.json({ 
        status: 'running',
        timestamp: new Date().toISOString(),
        whatsapp_connected: sock && sock.ws.readyState === sock.ws.OPEN,
        auth_method: 'Hybrid (creds.json + NO session files)',
        session_files_blocked: true
    });
});

// Session cleanup endpoint
app.post('/cleanup-sessions', async (req, res) => {
    console.log('🧹 Manual session cleanup request received');
    aggressiveSessionCleanup();
    res.json({ 
        success: true, 
        message: 'Session files cleanup completed' 
    });
});

// Graceful shutdown endpoint
app.post('/shutdown', (req, res) => {
    console.log('🛑 Shutdown request received');
    res.json({ 
        success: true, 
        message: 'Shutting down gracefully...' 
    });
    
    // Close WhatsApp connection if exists
    if (sock) {
        try {
            sock.ws.close();
            sock = null;
            console.log('📱 WhatsApp connection closed');
        } catch (error) {
            console.log('⚠️ Error closing WhatsApp connection:', error.message);
        }
    }
    
    // Close server gracefully
    setTimeout(() => {
        console.log('🛑 Server shutting down...');
        process.exit(0);
    }, 1000);
});

// Session cleanup every 1 minute
setInterval(() => {
    aggressiveSessionCleanup();
}, 1 * 60 * 1000);

// Handle process termination
process.on('SIGINT', () => {
    console.log('\\n🛑 Received SIGINT, shutting down gracefully...');
    aggressiveSessionCleanup();
    if (sock) {
        sock.ws.close();
    }
    process.exit(0);
});

process.on('SIGTERM', () => {
    console.log('\\n🛑 Received SIGTERM, shutting down gracefully...');
    aggressiveSessionCleanup();
    if (sock) {
        sock.ws.close();
    }
    process.exit(0);
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
    console.log(`🚀 WhatsApp service running on port ${PORT}`);
    console.log('🛡️ Starting WhatsApp connection with SESSION FILE BLOCKING...');
    console.log('📄 Only creds.json allowed - ALL session files will be blocked!');
    
    // Initial session cleanup
    aggressiveSessionCleanup();
    
    connectToWhatsApp();
});