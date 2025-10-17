// Workaround for Node.js v22 crypto assertion failure on Windows
process.env.NODE_OPTIONS = '--openssl-legacy-provider';

const express = require('express');
const bodyParser = require('body-parser');
const cors = require('cors');
const qrcode = require('qrcode');
const qrcodeTerminal = require('qrcode-terminal');
const { Client, LocalAuth, MessageMedia } = require('whatsapp-web.js');

const app = express();
app.use(bodyParser.json());
app.use(cors());

const client = new Client({
  authStrategy: new LocalAuth({ clientId: 'belova' }),
  puppeteer: { headless: true }
});

let lastQr = null;
let ready = false;

client.on('qr', (qr) => {
  lastQr = qr;
  console.log('QR RECEIVED');
  try {
    qrcodeTerminal.generate(qr, { small: true });
  } catch (e) {
    // ignore
  }
});

client.on('ready', () => {
  ready = true;
  console.log('WhatsApp client ready');
});

client.on('auth_failure', (msg) => {
  console.error('AUTH FAILURE', msg);
});

client.on('disconnected', (reason) => {
  ready = false;
  console.warn('WhatsApp client disconnected:', reason);
});

client.initialize();

app.get('/status', async (req, res) => {
  try {
    if (ready) return res.json({ status: 'ready' });
    if (lastQr) {
      const dataUrl = await qrcode.toDataURL(lastQr);
      // also provide a simple HTML page to view the QR
      return res.json({ status: 'qr', qrcode: dataUrl, qr_url: '/qr' });
    }
    return res.json({ status: 'initializing' });
  } catch (e) {
    return res.status(500).json({ status: 'error', error: e.message });
  }
});

// Simple QR page
app.get('/qr', async (req, res) => {
  if (!lastQr) return res.status(404).send('No QR available');
  try {
    const dataUrl = await qrcode.toDataURL(lastQr);
    return res.send(`<!doctype html><html><body style="display:flex;align-items:center;justify-content:center;height:100vh;"><img src="${dataUrl}" alt="qr"/></body></html>`);
  } catch (e) {
    return res.status(500).send('Error generating QR');
  }
});

app.post('/send', async (req, res) => {
  const { number, message } = req.body || {};
  if (!number) return res.status(400).json({ success: false, error: 'number is required' });

  try {
    if (!ready) return res.status(503).json({ success: false, error: 'WhatsApp client not ready' });

    // WhatsApp expects number in format 62812...@c.us for phone
    const sanitized = (number + '').replace(/[^0-9]/g, '');
    const id = sanitized.includes('@c.us') ? sanitized : `${sanitized}@c.us`;

    const sent = await client.sendMessage(id, message || '');
    return res.json({ success: true, id: sent.id._serialized });
  } catch (e) {
    console.error('Send error', e);
    return res.status(500).json({ success: false, error: e.message });
  }
});

const port = process.env.PORT || 3000;

const server = app.listen(port, '127.0.0.1', (err) => {
  if (err) {
    console.error('Failed to start server:', err);
    process.exit(1);
  }
  console.log(`WhatsApp service listening on 127.0.0.1:${port}`);
});

// Handle server errors gracefully
server.on('error', (err) => {
  if (err.code === 'EADDRINUSE') {
    console.error(`Port ${port} is already in use. Please stop other services or change the port.`);
    console.error('Try running: taskkill /F /IM node.exe');
    process.exit(1);
  } else {
    console.error('Server error:', err);
    process.exit(1);
  }
});
