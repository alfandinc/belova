const { default: makeWASocket, DisconnectReason, useMultiFileAuthState } = require('@whiskeysockets/baileys');
const express = require('express');
const cors = require('cors');
const qrcode = require('qrcode-terminal');

const app = express();
app.use(express.json());
app.use(cors());

let sock;

async function connectToWhatsApp() {
    const { state, saveCreds } = await useMultiFileAuthState('auth_info_baileys');
    
    sock = makeWASocket({
        auth: state,
        browser: ['Belova Clinic', 'Chrome', '1.0.0']
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
            const shouldReconnect = (lastDisconnect?.error)?.output?.statusCode !== DisconnectReason.loggedOut;
            console.log('Connection closed, reconnecting:', shouldReconnect);
            if(shouldReconnect) {
                connectToWhatsApp();
            }
        } else if(connection === 'open') {
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

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
    console.log(`🚀 WhatsApp service running on port ${PORT}`);
    console.log('🔗 Starting WhatsApp connection...');
    connectToWhatsApp();
});