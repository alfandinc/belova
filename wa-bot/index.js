const { Client, LocalAuth, MessageMedia } = require('whatsapp-web.js');
const qrcode = require('qrcode-terminal');
const express = require('express');
const fs = require('fs');
const path = require('path');
const fetch = require('node-fetch');

const app = express();
app.use(express.json());
// allow simple CORS for local dev so browser can query /sessions
app.use((req, res, next) => {
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET,POST,OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');
  if (req.method === 'OPTIONS') return res.sendStatus(200);
  next();
});
app.get('/', (req, res) => res.send('WA Bot running'));

// Small sleep helper (compatible across Puppeteer versions)
const sleep = (ms) => new Promise(resolve => setTimeout(resolve, ms));

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

// helper: check whether the whatsapp-web.js injection helper is available on the page
async function isWWebJSInjected(client) {
  try {
    const page = client.pupPage;
    if (!page) return false;
    const t = await page.evaluate(() => (typeof window.WWebJS));
    return t !== 'undefined';
  } catch (e) {
    return false;
  }
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

// Send message endpoint: accepts { from, to, message } or { from, to, image_url, caption }
app.post('/send', async (req, res) => {
  const { from, to, message, image_url } = req.body || {};
  if (!to || (!message && !image_url)) return res.status(400).json({ error: 'to and message or image_url are required' });

  const sessionId = from || sessionIds[0];
  const client = clients[sessionId];
  if (!client) return res.status(400).json({ error: 'invalid_from', message: 'Requested from session does not exist' });
  if (statuses[sessionId] !== 'ready' && statuses[sessionId] !== 'authenticated') {
    return res.status(400).json({ error: 'not_ready', message: `Session ${sessionId} not ready` });
  }

  // quick diagnostic: ensure the injection helper exists in page context
  try {
    const injected = await isWWebJSInjected(client);
    if (!injected) {
      console.error(`[${sessionId}] window.WWebJS NOT injected — send-ticket aborted`);
      return res.status(500).json({ error: 'wwebjs_not_injected', message: 'WA helper not available in page context; restart bot or update whatsapp-web.js/puppeteer' });
    }
  } catch (e) {
    console.warn(`[${sessionId}] wwebjs injection check failed:`, e && e.message ? e.message : e);
  }

  // quick diagnostic: ensure the injection helper exists in page context
  try {
    const injected = await isWWebJSInjected(client);
    if (!injected) {
      console.error(`[${sessionId}] window.WWebJS NOT injected — send aborted`);
      return res.status(500).json({ error: 'wwebjs_not_injected', message: 'WA helper not available in page context; restart bot or update whatsapp-web.js/puppeteer' });
    }
  } catch (e) {
    console.warn(`[${sessionId}] wwebjs injection check failed:`, e && e.message ? e.message : e);
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
    // if image_url provided, fetch it and send as media with optional caption
    if (req.body && req.body.image_url) {
      try {
        const imageUrl = req.body.image_url;
        const caption = req.body.caption || '';
        const res = await fetch(imageUrl);
        if (!res.ok) throw new Error('Failed to fetch image: ' + res.status);
        const buf = await res.buffer();
        const mime = res.headers.get('content-type') || 'image/jpeg';
        const filename = path.basename(new URL(imageUrl).pathname) || ('image.' + mime.split('/')[1] || 'jpg');
        const base64 = buf.toString('base64');
        const media = new MessageMedia(mime, base64, filename);
        try {
          sent = await client.sendMessage(chatId, media, { caption: caption, sendSeen: false });
        } catch (e1) {
          console.warn(`[${sessionId}] direct media send failed, attempting file fallback:`, e1 && e1.message ? e1.message : e1);
          // if the failure is the known markedUnread TypeError, attempt a retry with minimal options
          if (e1 && e1.message && e1.message.includes('markedUnread')) {
            try {
              console.warn(`[${sessionId}] detected markedUnread error, retrying media send without caption/options`);
              sent = await client.sendMessage(chatId, media, { sendSeen: false });
            } catch (eRetry) {
              console.warn(`[${sessionId}] retry after markedUnread failed:`, eRetry && eRetry.message ? eRetry.message : eRetry);
            }
          }
          // fallback: write to temp file and use MessageMedia.fromFilePath
          try {
            const tmpDir = fs.mkdtempSync(path.join(require('os').tmpdir(), 'wa-'));
            const tmpPath = path.join(tmpDir, filename);
            fs.writeFileSync(tmpPath, buf);
            const media2 = MessageMedia.fromFilePath(tmpPath);
            try {
              sent = await client.sendMessage(chatId, media2, { caption: caption });
            } finally {
              // cleanup
              try { fs.unlinkSync(tmpPath); } catch(e){}
              try { fs.rmdirSync(tmpDir); } catch(e){}
            }
          } catch (e2) {
            console.error(`[${sessionId}] file-fallback media send failed:`, e2 && e2.stack ? e2.stack : e2);
            throw e2;
          }
        }
      } catch (e) {
        console.error(`[${sessionId}] failed to send image:`, e && e.message ? e.message : e);
        return res.status(500).json({ error: 'failed_fetch_image', message: e.message || String(e) });
      }
    } else {
      try {
        sent = await client.sendMessage(chatId, message, { sendSeen: false });
      } catch (e) {
        console.warn(`[${sessionId}] send with sendSeen:false failed, retrying without option:`, e && e.message ? e.message : e);
        sent = await client.sendMessage(chatId, message);
      }
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

// Send ticket endpoint: accepts { from, to, peserta_id }
app.post('/send-ticket', async (req, res) => {
  const { from, to, peserta_id } = req.body || {};
  if (!to || !peserta_id) return res.status(400).json({ error: 'to and peserta_id are required' });

  const sessionId = from || sessionIds[0];
  const client = clients[sessionId];
  if (!client) return res.status(400).json({ error: 'invalid_from', message: 'Requested from session does not exist' });
  if (statuses[sessionId] !== 'ready' && statuses[sessionId] !== 'authenticated') {
    return res.status(400).json({ error: 'not_ready', message: `Session ${sessionId} not ready` });
  }

  // candidate Laravel base URLs
  const baseCandidates = [
    process.env.WHATSAPP_LARAVEL_URL || null,
    'http://localhost/belova',
    'http://localhost/belova/public',
    'http://localhost'
  ].filter(u => !!u);

  let ticketUrl = null;
  let lastErr = null;
  for (const base of baseCandidates) {
    try {
      const token = process.env.WA_BOT_TOKEN || null;
      const baseTrim = base.replace(/\/$/, '');
      const candidates = [];
      // always try public endpoint first (include token if available)
      let publicUrl = baseTrim + '/running/ticket-html-public/' + encodeURIComponent(peserta_id);
      if (token) publicUrl += '?wa_bot_token=' + encodeURIComponent(token);
      candidates.push(publicUrl);
      // fallback to auth-protected fragment (may return login page if not accessible)
      candidates.push(baseTrim + '/running/ticket-html/' + encodeURIComponent(peserta_id));

      for (const url of candidates) {
        try {
          const r = await fetch(url);
          if (r.ok) { ticketUrl = url; break; }
          lastErr = new Error('Bad response ' + r.status + ' from ' + url);
        } catch (ee) { lastErr = ee; }
      }
      if (ticketUrl) break;
    } catch (e) { lastErr = e; }
  }

  if (!ticketUrl) {
    console.error('Failed to locate ticket HTML URL:', lastErr && lastErr.message ? lastErr.message : lastErr);
    return res.status(500).json({ error: 'no_ticket_url', message: lastErr && lastErr.message ? lastErr.message : 'Failed to locate ticket URL' });
  }

  try {
    const plain = String(to).replace(/[^0-9]/g, '');
    let numberId = null;
    try { numberId = await client.getNumberId(plain); } catch(e) { console.warn(`[${sessionId}] getNumberId failed:`, e && e.message ? e.message : e); }
    if (!numberId) return res.status(400).json({ error: 'number_not_registered', message: 'Target number is not a WhatsApp user' });
    const chatId = numberId._serialized || `${plain}@c.us`;

    // If Laravel provided a pre-generated image_path, prefer sending that file directly
    if (req.body && req.body.image_path) {
      const imgPath = req.body.image_path;
      try {
        if (fs.existsSync(imgPath)) {
          // If a message text is provided, send it first
          if (req.body.message) {
            try {
              await client.sendMessage(chatId, req.body.message, { sendSeen: false });
            } catch (eMsg) {
              console.warn(`[${sessionId}] send text before image failed, retrying without options:`, eMsg && eMsg.message ? eMsg.message : eMsg);
              try { await client.sendMessage(chatId, req.body.message); } catch(e2) { console.warn('text send failed', e2 && e2.message ? e2.message : e2); }
            }
          }

          const media = MessageMedia.fromFilePath(imgPath);
          let sent = null;
          try {
            sent = await client.sendMessage(chatId, media, { caption: '', sendSeen: false });
          } catch (e) {
            console.warn(`[${sessionId}] send ticket from file with options failed, retrying without options:`, e && e.message ? e.message : e);
            sent = await client.sendMessage(chatId, media);
          }

          // attempt to send a document (waiver) after the image, if available
          try {
            let docPath = null;
            if (req.body && req.body.document_path) {
              docPath = req.body.document_path;
            } else {
              const defaultDoc = path.resolve(__dirname, '../public/img/templates/WAIVER-BELOVAPREMIERERUN.pdf');
              if (fs.existsSync(defaultDoc)) docPath = defaultDoc;
            }

            if (docPath && fs.existsSync(docPath)) {
              try {
                const docMedia = MessageMedia.fromFilePath(docPath);
                try {
                  await client.sendMessage(chatId, docMedia, { sendMediaAsDocument: true, fileName: path.basename(docPath), sendSeen: false });
                } catch (eDoc) {
                  console.warn(`[${sessionId}] document send with options failed, retrying without options:`, eDoc && eDoc.message ? eDoc.message : eDoc);
                  await client.sendMessage(chatId, docMedia, { sendMediaAsDocument: true, fileName: path.basename(docPath) });
                }
              } catch (eDoc2) {
                console.error(`[${sessionId}] failed to prepare/send document:`, eDoc2 && eDoc2.stack ? eDoc2.stack : eDoc2);
              }
            }
          } catch (eDocOuter) {
            console.warn(`[${sessionId}] document send check failed:`, eDocOuter && eDocOuter.message ? eDocOuter.message : eDocOuter);
          }

          return res.json({ ok: true, id: sent && sent.id ? sent.id._serialized : null });
        } else {
          console.warn(`[${sessionId}] provided image_path does not exist: ${imgPath}`);
        }
      } catch (e) {
        console.error(`[${sessionId}] error while sending image_path file:`, e && e.stack ? e.stack : e);
      }
    }

    // fallback: render via Puppeteer (existing flow)
    const browser = client.pupBrowser;
    if (!browser) return res.status(500).json({ error: 'no_browser', message: 'Puppeteer browser not available' });

    const page = await browser.newPage();
    try {
      await page.setViewport({ width: 900, height: 1200 });
      await page.goto(ticketUrl, { waitUntil: 'networkidle0', timeout: 30000 });
      // small delay to allow dynamic rendering (compatible fallback)
      await sleep(500);
      const buf = await page.screenshot({ fullPage: true, type: 'png' });
      const base64 = buf.toString('base64');
      const media = new MessageMedia('image/png', base64, `ticket-${peserta_id}.png`);

      let sent = null;
      try {
        sent = await client.sendMessage(chatId, media, { caption: 'Registration Ticket', sendSeen: false });
      } catch (e) {
        console.warn(`[${sessionId}] send ticket with options failed, retrying without options:`, e && e.message ? e.message : e);
        sent = await client.sendMessage(chatId, media);
      }

      // attempt to send the waiver document after the screenshot image
      try {
        const defaultDoc = path.resolve(__dirname, '../public/img/templates/WAIVER-BELOVAPREMIERERUN.pdf');
        let docPath = null;
        if (req.body && req.body.document_path) docPath = req.body.document_path;
        else if (fs.existsSync(defaultDoc)) docPath = defaultDoc;

        if (docPath && fs.existsSync(docPath)) {
          try {
            const docMedia = MessageMedia.fromFilePath(docPath);
            try {
              await client.sendMessage(chatId, docMedia, { sendMediaAsDocument: true, fileName: path.basename(docPath), sendSeen: false });
            } catch (eDoc) {
              console.warn(`[${sessionId}] document send with options failed, retrying without options:`, eDoc && eDoc.message ? eDoc.message : eDoc);
              await client.sendMessage(chatId, docMedia, { sendMediaAsDocument: true, fileName: path.basename(docPath) });
            }
          } catch (eDoc2) {
            console.error(`[${sessionId}] failed to prepare/send document:`, eDoc2 && eDoc2.stack ? eDoc2.stack : eDoc2);
          }
        }
      } catch (eDocOuter) {
        console.warn(`[${sessionId}] document send check failed:`, eDocOuter && eDocOuter.message ? eDocOuter.message : eDocOuter);
      }

      return res.json({ ok: true, id: sent && sent.id ? sent.id._serialized : null });
    } finally {
      try { await page.close(); } catch(e){}
    }
  } catch (err) {
    console.error('Error sending ticket:', err && err.stack ? err.stack : err);
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
