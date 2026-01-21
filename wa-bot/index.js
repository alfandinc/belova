const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode-terminal');
const express = require('express');
const fs = require('fs');
const path = require('path');
const fetch = require('node-fetch');

const app = express();
app.use(express.json());
app.get('/', (req, res) => res.send('WA Bot running'));

// Session source URLs (Laravel endpoint)
const ENV_URL = process.env.WA_SESSIONS_URL || process.env.WA_BOT_SESSIONS_URL || null;
const CANDIDATE_URLS = [
  ENV_URL,
  'http://localhost/wa-sessions',
  'http://localhost/belova/wa-sessions',
  'http://localhost/belova/public/wa-sessions',
];

let sessionIds = [];
const clients = {};
const statuses = {};

function createClientForId(id) {
  if (clients[id]) return;
  const client = new Client({
    authStrategy: new LocalAuth({ clientId: id }),
    puppeteer: { headless: true }
  });

  statuses[id] = 'initializing';

  client.on('qr', (qr) => {
    qrcode.generate(qr, { small: true });
    console.log(`[${id}] QR received — scan with WhatsApp mobile app.`);
    statuses[id] = 'qr';
  });

  client.on('ready', () => {
    console.log(`[${id}] WhatsApp client is ready`);
    statuses[id] = 'ready';
  });

  client.on('authenticated', () => {
    statuses[id] = 'authenticated';
  });

  client.on('auth_failure', (err) => {
    console.error(`[${id}] auth failure:`, err);
    statuses[id] = 'auth_failure';
  });

  client.on('disconnected', (reason) => {
    console.log(`[${id}] disconnected:`, reason);
    statuses[id] = 'disconnected';
  });

  client.on('message', msg => {
    if (msg && msg.body && msg.body.toLowerCase() === 'ping') {
      msg.reply('pong');
    }
  });

  // forward incoming messages to Laravel for logging
  client.on('message', async msg => {
    try {
      const rawObj = { original: msg };
      const normalize = (addr) => (addr ? String(addr).split('@')[0] : null);
      rawObj.meta = {
        from_normalized: normalize(msg.from || msg.author || null),
        to_normalized: normalize(msg.to || null)
      };

      const payload = {
        session_client_id: id,
        direction: 'in',
        from: msg.from || msg.author || null,
        to: msg.to || null,
        body: msg.body || null,
        message_id: msg.id && msg.id._serialized ? msg.id._serialized : null,
        raw: JSON.stringify(rawObj)
      };
      await logMessageToServer(payload);
    } catch (e) {
      console.warn(`[${id}] failed to log incoming message:`, e && e.message ? e.message : e);
    }
  });

  clients[id] = client;
  client.initialize().catch(err => {
    console.error(`[${id}] initialize error:`, err && err.message ? err.message : err);
    statuses[id] = 'error';
  });
}

async function refreshSessionsFromServer() {
  let lastError = null;
  for (const url of CANDIDATE_URLS) {
    if (!url) continue;
    try {
      const resp = await fetch(url);
      if (!resp.ok) throw new Error('Bad response ' + resp.status + ' from ' + url);
      const list = await resp.json();
      const ids = (Array.isArray(list) ? list.map(s => s.id) : []);

      // remember which URL worked
      if (ENV_URL !== url) console.log('Using sessions URL:', url);

    // create new clients
    for (const id of ids) {
      if (!sessionIds.includes(id)) {
        console.log('Adding session', id);
        createClientForId(id);
      }
    }

    // remove deleted sessions
    for (const old of [...sessionIds]) {
      if (!ids.includes(old)) {
        console.log('Removing session', old);
        const client = clients[old];
        if (client) {
          try { client.destroy(); } catch(e){}
          delete clients[old];
        }
        delete statuses[old];
      }
    }

      sessionIds = ids;
      return; // success
    } catch (e) {
      lastError = e;
      // try next candidate
    }
  }
  console.warn('Failed to refresh sessions from server:', lastError && lastError.message ? lastError.message : lastError);
}

// initial fetch and periodic refresh
refreshSessionsFromServer();
setInterval(refreshSessionsFromServer, 10000);

// List sessions and their statuses
app.get('/sessions', (req, res) => {
  const out = sessionIds.map(id => ({ id, status: statuses[id] || 'unknown' }));
  res.json(out);
});

// Send message endpoint: accepts { from, to, message }
app.post('/send', async (req, res) => {
  const { from, to, message } = req.body || {};
  if (!to || !message) return res.status(400).json({ error: 'to and message are required' });

  const sessionId = from || sessionIds[0];
  const client = clients[sessionId];
  if (!client) return res.status(400).json({ error: 'invalid_from', message: 'Requested from session does not exist' });
  if (statuses[sessionId] !== 'ready' && statuses[sessionId] !== 'authenticated') {
    return res.status(400).json({ error: 'not_ready', message: `Session ${sessionId} not ready` });
  }

  try {
    const plain = String(to).replace(/[^0-9]/g, '');
    let numberId = null;
    try {
      numberId = await client.getNumberId(plain);
    } catch (e) {
      console.warn(`[${sessionId}] getNumberId failed:`, e && e.message ? e.message : e);
    }

    if (!numberId) {
      console.error(`[${sessionId}] Attempt to send to unregistered number:`, to);
      return res.status(400).json({ error: 'number_not_registered', message: 'Target number is not a WhatsApp user' });
    }

    const chatId = numberId._serialized || `${plain}@c.us`;
    console.log(`[${sessionId}] Sending message to`, chatId);

    let sent;
    try {
      sent = await client.sendMessage(chatId, message, { sendSeen: false });
    } catch (e) {
      console.warn(`[${sessionId}] send with sendSeen:false failed, retrying without option:`, e && e.message ? e.message : e);
      sent = await client.sendMessage(chatId, message);
    }

    // log outgoing message to server (best-effort)
    (async () => {
      try {
        const normalize = (addr) => (addr ? String(addr).split('@')[0] : null);
        const rawObj = { original: sent || {} };
        rawObj.meta = {
          from_normalized: normalize(client.info && client.info.me ? client.info.me._serialized : sessionId),
          to_normalized: normalize(chatId)
        };

        await logMessageToServer({
          session_client_id: sessionId,
          direction: 'out',
          from: client.info && client.info.me ? client.info.me._serialized : sessionId,
          to: chatId,
          body: message,
          message_id: sent && sent.id && sent.id._serialized ? sent.id._serialized : null,
          raw: JSON.stringify(rawObj)
        });
      } catch (e) {
        console.warn(`[${sessionId}] failed to log outgoing message:`, e && e.message ? e.message : e);
      }
    })();

    return res.json({ ok: true, id: sent && sent.id ? sent.id._serialized : null });
  } catch (err) {
    console.error(`[${sessionId}] Error sending message:`, err && err.stack ? err.stack : err);
    return res.status(500).json({ error: err.message || String(err) });
  }
});

const port = process.env.PORT || 3000;
app.listen(port, () => console.log(`Health server listening on ${port}`));

// Message logging helper — tries multiple candidate endpoints (WAMP paths included)
const ENV_MSG_URL = process.env.WA_MESSAGES_URL || process.env.WA_BOT_MESSAGES_URL || null;
const MSG_CANDIDATE_URLS = [
  ENV_MSG_URL,
  'http://localhost/wa-messages',
  'http://localhost/belova/wa-messages',
  'http://localhost/belova/public/wa-messages'
];

async function logMessageToServer(payload) {
  let lastErr = null;
  for (const url of MSG_CANDIDATE_URLS) {
    if (!url) continue;
    try {
      const resp = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      if (resp.ok) return true;
      lastErr = new Error('Bad response ' + resp.status + ' from ' + url);
    } catch (e) {
      lastErr = e;
    }
  }
  throw lastErr;
}
