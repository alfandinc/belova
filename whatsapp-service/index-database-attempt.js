const { default: makeWASocket, DisconnectReason } = require('@whiskeysockets/baileys');
const express = require('express');
const cors = require('cors');
const qrcode = require('qrcode-terminal');
const fs = require('fs');
const path = require('path');
const DatabaseSessionAuthState = require('./database-session-auth');

const app = express();
app.use(express.json());
app.use(cors());

let sock;
let isConnecting = false;
let dbAuth;

// Initialize database auth handler
dbAuth = new DatabaseSessionAuthState();

// Custom auth state that uses database and prevents file creation
async function useDatabaseAuthState() {
    const authDir = 'auth_info_baileys';
    
    // Nuclear cleanup before starting
    await dbAuth.nuclearCleanup();
    
    // Load state from database
    const creds = await dbAuth.loadCreds();
    const keys = await dbAuth.loadKeys();
    
    const state = {
        creds: creds,
        keys: keys
    };
    
    // Custom save credentials function
    const saveCreds = async () => {
        await dbAuth.saveCreds(state.creds);
        // Immediate nuclear cleanup after saving
        setTimeout(() => dbAuth.nuclearCleanup(), 500);
    };
    
    return { state, saveCreds };
}

// Override Baileys file operations to prevent session file creation
function interceptFileOperations() {
    const originalWriteFileSync = fs.writeFileSync;
    const originalWriteFile = fs.writeFile;
    
    // Intercept synchronous file writes
    fs.writeFileSync = function(filePath, data, options) {
        const fileName = path.basename(filePath);
        
        // Block session file creation
        if (fileName.startsWith('session-') && fileName.endsWith('.json')) {
            console.log(`🚫 BLOCKED session file creation: ${fileName}`);
            
            // Extract session ID and save to database instead
            const sessionId = fileName.replace('session-', '').replace('.json', '');
            try {
                const sessionData = typeof data === 'string' ? JSON.parse(data) : data;
                dbAuth.writeSessionData(sessionId, sessionData);
            } catch (err) {
                console.log(`⚠️ Error saving session ${sessionId} to database:`, err.message);
            }
            return;
        }
        
        // Block all other auth file creation
        if (filePath.includes('auth_info_baileys')) {
            console.log(`🚫 BLOCKED auth file creation: ${fileName}`);
            return;
        }
        
        // Allow other files
        return originalWriteFileSync.call(this, filePath, data, options);
    };
    
    // Intercept asynchronous file writes
    fs.writeFile = function(filePath, data, options, callback) {
        if (typeof options === 'function') {
            callback = options;
            options = {};
        }
        
        const fileName = path.basename(filePath);
        
        // Block session file creation
        if (fileName.startsWith('session-') && fileName.endsWith('.json')) {
            console.log(`🚫 BLOCKED async session file creation: ${fileName}`);
            
            // Extract session ID and save to database instead
            const sessionId = fileName.replace('session-', '').replace('.json', '');
            try {
                const sessionData = typeof data === 'string' ? JSON.parse(data) : data;
                dbAuth.writeSessionData(sessionId, sessionData);
            } catch (err) {
                console.log(`⚠️ Error saving session ${sessionId} to database:`, err.message);
            }
            
            if (callback) callback(null);
            return;
        }
        
        // Block all other auth file creation
        if (filePath.includes('auth_info_baileys')) {
            console.log(`🚫 BLOCKED async auth file creation: ${fileName}`);
            if (callback) callback(null);
            return;
        }
        
        // Allow other files
        return originalWriteFile.call(this, filePath, data, options, callback);
    };
    
    console.log('🛡️ File operation interceptor activated - NO auth files will be created');
}

async function connectToWhatsApp() {
    if (isConnecting) {
        console.log('⏳ Connection already in progress...');
        return;
    }
    
    isConnecting = true;
    
    try {
        console.log('🗄️ Using DATABASE-ONLY authentication with ZERO file creation...');
        
        // Activate file operation interceptor
        interceptFileOperations();
        
        // Start file monitor to remove any files that somehow get created
        dbAuth.startFileMonitor();
        
        // Nuclear cleanup before connection
        await dbAuth.nuclearCleanup();
        
        // Use database auth state
        const { state, saveCreds } = await useDatabaseAuthState();
        
        sock = makeWASocket({
            auth: state,
            browser: ['Belova Clinic', 'Chrome', '1.0.0'],
            printQRInTerminal: false,
            defaultQueryTimeoutMs: 60000,
            connectTimeoutMs: 60000,
            keepAliveIntervalMs: 30000,
            generateHighQualityLinkPreview: false,
            syncFullHistory: false,
            // Limit pre-key generation to minimum
            maxPreKeys: 1
        });

        // Handle credentials update with database save and nuclear cleanup
        sock.ev.on('creds.update', async () => {
            await saveCreds();
            console.log('💾 Credentials updated in database');
            // Nuclear cleanup after each update
            setTimeout(() => dbAuth.nuclearCleanup(), 1000);
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
                    // Nuclear cleanup before reconnecting
                    setTimeout(async () => {
                        await dbAuth.nuclearCleanup();
                        connectToWhatsApp();
                    }, 5000);
                }
            } else if(connection === 'open') {
                isConnecting = false;
                console.log('✅ WhatsApp connection opened - Ready to send messages!');
                console.log('🗄️ All auth data stored in DATABASE - NO FILES CREATED!');
                // Nuclear cleanup after successful connection
                setTimeout(() => dbAuth.nuclearCleanup(), 2000);
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
        auth_method: 'Database-only (NO FILES)'
    });
});

app.get('/health', (req, res) => {
    res.json({ 
        status: 'running',
        timestamp: new Date().toISOString(),
        whatsapp_connected: sock && sock.ws.readyState === sock.ws.OPEN,
        auth_method: 'Database-only (NO FILES)',
        files_created: 'NONE'
    });
});

// Nuclear cleanup endpoint
app.post('/nuclear-cleanup', async (req, res) => {
    console.log('☢️ Nuclear cleanup request received');
    await dbAuth.nuclearCleanup();
    res.json({ 
        success: true, 
        message: 'Nuclear cleanup completed - all files removed' 
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

// Continuous nuclear cleanup - every 30 seconds
setInterval(async () => {
    await dbAuth.nuclearCleanup();
}, 30 * 1000);

// Handle process termination
process.on('SIGINT', async () => {
    console.log('\\n🛑 Received SIGINT, shutting down gracefully...');
    await dbAuth.nuclearCleanup();
    if (sock) {
        sock.ws.close();
    }
    process.exit(0);
});

process.on('SIGTERM', async () => {
    console.log('\\n🛑 Received SIGTERM, shutting down gracefully...');
    await dbAuth.nuclearCleanup();
    if (sock) {
        sock.ws.close();
    }
    process.exit(0);
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, async () => {
    console.log(`🚀 WhatsApp service running on port ${PORT}`);
    console.log('🗄️ Starting WhatsApp connection with DATABASE-ONLY authentication...');
    console.log('🚫 ZERO files will be created - everything stored in database!');
    
    // Initial nuclear cleanup
    await dbAuth.nuclearCleanup();
    
    connectToWhatsApp();
});