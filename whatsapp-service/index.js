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

async function connectToWhatsApp() {
    if (isConnecting) {
        console.log('⏳ Connection already in progress...');
        return;
    }
    
    isConnecting = true;
    
    try {
        const { state, saveCreds } = await useMultiFileAuthState(authDir);
        
        sock = makeWASocket({
            auth: state,
            browser: ['Belova Clinic', 'Chrome', '1.0.0'],
            printQRInTerminal: false,
            defaultQueryTimeoutMs: 60000,
            connectTimeoutMs: 60000,
            keepAliveIntervalMs: 30000,
            // Limit the number of pre-keys
            generateHighQualityLinkPreview: false,
            syncFullHistory: false
        });

        sock.ev.on('creds.update', saveCreds);
        
        sock.ev.on('connection.update', (update) => {
            const { connection, lastDisconnect, qr } = update;
            
            if (qr) {
                console.log('\n📱 SCAN THIS QR CODE WITH YOUR WHATSAPP:\n');
                qrcode.generate(qr, { small: true });
                console.log('\n📱 Open WhatsApp on your phone > Linked Devices > Link a Device\n');
            }
            
            if(connection === 'close') {
                isConnecting = false;
                const shouldReconnect = (lastDisconnect?.error)?.output?.statusCode !== DisconnectReason.loggedOut;
                console.log('Connection closed, reconnecting:', shouldReconnect);
                
                if(shouldReconnect) {
                    // Add delay before reconnecting to prevent rapid reconnection
                    setTimeout(() => {
                        connectToWhatsApp();
                    }, 5000);
                }
            } else if(connection === 'open') {
                isConnecting = false;
                console.log('✅ WhatsApp connection opened - Ready to send messages!');
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
        
        // Validate input
        if (!number || !message) {
            return res.status(400).json({ 
                success: false, 
                error: 'Number and message are required' 
            });
        }
        
        // Check if socket exists and is connected
        if (!sock) {
            return res.status(503).json({ 
                success: false, 
                error: 'WhatsApp socket not initialized' 
            });
        }
        
        if (!sock.ws || sock.ws.readyState !== sock.ws.OPEN) {
            return res.status(503).json({ 
                success: false, 
                error: 'WhatsApp not connected (readyState: ' + (sock.ws?.readyState || 'undefined') + ')' 
            });
        }

        // Format the WhatsApp ID
        const id = number.includes('@s.whatsapp.net') ? number : `${number}@s.whatsapp.net`;
        
        console.log(`📤 Attempting to send message to ${id}`);
        
        // Send the message
        const result = await sock.sendMessage(id, { text: message });
        
        console.log(`✅ Message sent successfully to ${number}: ${message.substring(0, 50)}...`);
        console.log('📋 Send result:', result);
        
        res.json({ 
            success: true, 
            message: 'Message sent successfully',
            messageId: result?.key?.id || 'unknown'
        });
    } catch (error) {
        console.error('❌ Send message error:', error);
        console.error('❌ Error details:', {
            name: error.name,
            message: error.message,
            stack: error.stack?.substring(0, 500)
        });
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

// Cleanup function to prevent session conflicts
function cleanupOldSessions() {
    try {
        const files = fs.readdirSync(authDir);
        const now = Date.now();
        const maxAge = 24 * 60 * 60 * 1000; // 24 hours
        
        files.forEach(file => {
            const filePath = path.join(authDir, file);
            const stats = fs.statSync(filePath);
            
            // Remove old pre-key files (keep only recent ones)
            if (file.startsWith('pre-key-') && (now - stats.mtime.getTime()) > maxAge) {
                fs.unlinkSync(filePath);
                console.log(`🗑️ Cleaned up old pre-key: ${file}`);
            }
        });
    } catch (error) {
        console.log('⚠️ Error during cleanup:', error.message);
    }
}

// Handle process termination
process.on('SIGINT', () => {
    console.log('\n🛑 Received SIGINT, shutting down gracefully...');
    if (sock) {
        sock.ws.close();
    }
    process.exit(0);
});

process.on('SIGTERM', () => {
    console.log('\n🛑 Received SIGTERM, shutting down gracefully...');
    if (sock) {
        sock.ws.close();
    }
    process.exit(0);
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
    console.log(`🚀 WhatsApp service running on port ${PORT}`);
    console.log('🔗 Starting WhatsApp connection...');
    
    // Clean up old sessions before connecting
    cleanupOldSessions();
    
    connectToWhatsApp();
});