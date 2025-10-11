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

// SUPER AGGRESSIVE cleanup function
function superAggressiveCleanup() {
    try {
        if (!fs.existsSync(authDir)) return;
        
        const files = fs.readdirSync(authDir);
        const now = Date.now();
        
        // Only keep absolutely essential files
        const essentialFiles = ['creds.json'];
        const maxPreKeys = 2; // Only 2 pre-keys
        const maxSenderKeys = 2; // Only 2 sender-keys  
        const maxSessionFiles = 2; // Only 2 session files
        const maxAppStateKeys = 1; // Only 1 app-state key
        
        let preKeyCount = 0;
        let senderKeyCount = 0;
        let sessionCount = 0;
        let appStateKeyCount = 0;
        
        // Sort files by modification time (newest first)
        const sortedFiles = files
            .map(file => ({
                name: file,
                path: path.join(authDir, file),
                mtime: fs.statSync(path.join(authDir, file)).mtime.getTime()
            }))
            .sort((a, b) => b.mtime - a.mtime);
        
        for (const file of sortedFiles) {
            try {
                // Always keep essential files
                if (essentialFiles.includes(file.name)) {
                    continue;
                }
                
                // Aggressive pre-key management (only keep 2)
                if (file.name.startsWith('pre-key-')) {
                    preKeyCount++;
                    if (preKeyCount > maxPreKeys) {
                        fs.unlinkSync(file.path);
                        console.log(`🗑️ Removed excess pre-key: ${file.name}`);
                        continue;
                    }
                }
                
                // Aggressive sender-key management (only keep 2)
                if (file.name.startsWith('sender-key-')) {
                    senderKeyCount++;
                    if (senderKeyCount > maxSenderKeys) {
                        fs.unlinkSync(file.path);
                        console.log(`🗑️ Removed excess sender-key: ${file.name}`);
                        continue;
                    }
                }
                
                // Aggressive session management (only keep 2)
                if (file.name.startsWith('session-')) {
                    sessionCount++;
                    if (sessionCount > maxSessionFiles) {
                        fs.unlinkSync(file.path);
                        console.log(`🗑️ Removed excess session: ${file.name}`);
                        continue;
                    }
                }
                
                // Aggressive app-state-sync management (only keep 1)
                if (file.name.startsWith('app-state-sync-key-')) {
                    appStateKeyCount++;
                    if (appStateKeyCount > maxAppStateKeys) {
                        fs.unlinkSync(file.path);
                        console.log(`🗑️ Removed excess app-state-sync-key: ${file.name}`);
                        continue;
                    }
                }
                
                // Remove files older than 15 minutes (very aggressive)
                const maxAge = 15 * 60 * 1000; // 15 minutes
                if ((now - file.mtime) > maxAge) {
                    fs.unlinkSync(file.path);
                    console.log(`🗑️ Removed old file: ${file.name}`);
                    continue;
                }
                
                // Remove app-state-sync-version files if more than 2
                if (file.name.startsWith('app-state-sync-version-')) {
                    const versionFiles = sortedFiles.filter(f => f.name.startsWith('app-state-sync-version-'));
                    if (versionFiles.length > 2 && versionFiles.indexOf(file) >= 2) {
                        fs.unlinkSync(file.path);
                        console.log(`🗑️ Removed excess app-state-sync-version: ${file.name}`);
                    }
                }
                
            } catch (fileError) {
                console.log(`⚠️ Error processing file ${file.name}:`, fileError.message);
            }
        }
        
        // Final check - if still too many files, nuclear cleanup
        const remainingFiles = fs.readdirSync(authDir);
        console.log(`📊 Auth files: ${remainingFiles.length} remaining after super aggressive cleanup`);
        
        if (remainingFiles.length > 10) {
            console.log(`🚨 NUCLEAR CLEANUP: Still too many files (${remainingFiles.length}), removing all except creds.json`);
            nuclearCleanup();
        }
        
    } catch (error) {
        console.log('⚠️ Error during super aggressive cleanup:', error.message);
    }
}

// Nuclear cleanup - remove everything except creds.json
function nuclearCleanup() {
    try {
        const files = fs.readdirSync(authDir);
        const nuclearEssential = ['creds.json'];
        
        for (const file of files) {
            if (!nuclearEssential.includes(file)) {
                try {
                    fs.unlinkSync(path.join(authDir, file));
                    console.log(`☢️ Nuclear removed: ${file}`);
                } catch (err) {
                    console.log(`⚠️ Could not nuclear remove ${file}:`, err.message);
                }
            }
        }
        
        console.log(`☢️ Nuclear cleanup completed. Files remaining: ${fs.readdirSync(authDir).length}`);
    } catch (error) {
        console.log('⚠️ Error during nuclear cleanup:', error.message);
    }
}

async function connectToWhatsApp() {
    if (isConnecting) {
        console.log('⏳ Connection already in progress...');
        return;
    }
    
    isConnecting = true;
    
    try {
        console.log('📁 Using file-based authentication with SUPER AGGRESSIVE cleanup...');
        
        // Super aggressive cleanup before connection
        superAggressiveCleanup();
        
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
            // Extremely limit pre-key generation
            maxPreKeys: 2
        });

        // Enhanced creds saving with immediate cleanup
        sock.ev.on('creds.update', async () => {
            await saveCreds();
            // Immediate cleanup after each save
            setTimeout(() => superAggressiveCleanup(), 500);
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
                    // Clean up before reconnecting
                    superAggressiveCleanup();
                    setTimeout(() => {
                        connectToWhatsApp();
                    }, 5000);
                }
            } else if(connection === 'open') {
                isConnecting = false;
                console.log('✅ WhatsApp connection opened - Ready to send messages!');
                // Cleanup after successful connection
                setTimeout(() => superAggressiveCleanup(), 2000);
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
        status: connected ? 'Connected' : 'Disconnected'
    });
});

app.get('/health', (req, res) => {
    res.json({ 
        status: 'running',
        timestamp: new Date().toISOString(),
        whatsapp_connected: sock && sock.ws.readyState === sock.ws.OPEN
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

// VERY frequent cleanup - every 1 minute
setInterval(() => {
    superAggressiveCleanup();
}, 1 * 60 * 1000);

// Handle process termination
process.on('SIGINT', () => {
    console.log('\\n🛑 Received SIGINT, shutting down gracefully...');
    if (sock) {
        sock.ws.close();
    }
    process.exit(0);
});

process.on('SIGTERM', () => {
    console.log('\\n🛑 Received SIGTERM, shutting down gracefully...');
    if (sock) {
        sock.ws.close();
    }
    process.exit(0);
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
    console.log(`🚀 WhatsApp service running on port ${PORT}`);
    console.log('🔗 Starting WhatsApp connection with SUPER AGGRESSIVE file management...');
    
    // Initial cleanup
    superAggressiveCleanup();
    
    connectToWhatsApp();
});