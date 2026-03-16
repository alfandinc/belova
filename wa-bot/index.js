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
const normalizeWaAddr = (addr) => (addr ? String(addr).split('@')[0] : null);
const normalizePhoneDigits = (value) => {
  if (!value) return null;
  const digits = String(value).replace(/[^0-9]/g, '');
  return digits || null;
};
const runtimeLogPath = path.join(__dirname, 'runtime.log');
const WA_BOT_HEADLESS = (process.env.WA_BOT_HEADLESS || 'false').toLowerCase() === 'true';
const WA_BOT_EXECUTABLE_PATH = process.env.WA_BOT_EXECUTABLE_PATH || null;

function getPuppeteerOptions() {
  const options = {
    headless: WA_BOT_HEADLESS,
    args: [
      '--disable-dev-shm-usage',
      '--no-first-run',
      '--no-default-browser-check',
      '--disable-background-networking',
      '--disable-background-timer-throttling',
      '--disable-backgrounding-occluded-windows',
      '--disable-renderer-backgrounding'
    ]
  };

  if (WA_BOT_EXECUTABLE_PATH) {
    options.executablePath = WA_BOT_EXECUTABLE_PATH;
  }

  return options;
}

function appendRuntimeLog(message, extra) {
  try {
    const line = `[${new Date().toISOString()}] ${message}${extra ? ' ' + extra : ''}\n`;
    fs.appendFileSync(runtimeLogPath, line, 'utf8');
  } catch (e) {
    // best-effort only
  }
}

function toSerializable(value, depth = 0, seen = new WeakSet()) {
  if (value === null || typeof value === 'undefined') return value;
  if (depth > 4) return '[max-depth]';
  if (typeof value === 'string' || typeof value === 'number' || typeof value === 'boolean') return value;
  if (typeof value === 'bigint') return value.toString();
  if (value instanceof Date) return value.toISOString();
  if (Buffer.isBuffer(value)) return `[buffer:${value.length}]`;
  if (Array.isArray(value)) return value.map(item => toSerializable(item, depth + 1, seen));
  if (typeof value === 'object') {
    if (seen.has(value)) return '[circular]';
    seen.add(value);
    const out = {};
    for (const [key, child] of Object.entries(value)) {
      if (typeof child === 'function') continue;
      out[key] = toSerializable(child, depth + 1, seen);
    }
    seen.delete(value);
    return out;
  }
  return String(value);
}

function safeJsonStringify(value) {
  return JSON.stringify(toSerializable(value));
}

appendRuntimeLog('bot_start', safeJsonStringify({ pid: process.pid, cwd: process.cwd(), dirname: __dirname }));

process.on('uncaughtException', (error) => {
  appendRuntimeLog('process_uncaught_exception', safeJsonStringify({
    message: error && error.message ? error.message : String(error),
    stack: error && error.stack ? error.stack : null
  }));
  console.error('Uncaught exception:', error && error.stack ? error.stack : error);
});

process.on('unhandledRejection', (reason) => {
  appendRuntimeLog('process_unhandled_rejection', safeJsonStringify({
    reason: reason && reason.message ? reason.message : String(reason),
    stack: reason && reason.stack ? reason.stack : null
  }));
  console.error('Unhandled rejection:', reason && reason.stack ? reason.stack : reason);
});

process.on('exit', (code) => {
  appendRuntimeLog('process_exit', safeJsonStringify({ code }));
});

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
const recentIncomingMessageIds = new Set();
const incomingPollers = {};
const incomingPollStates = {};
let activeSessionsUrl = null;

function rememberIncomingMessageId(messageId) {
  if (!messageId) return false;
  if (recentIncomingMessageIds.has(messageId)) return true;
  recentIncomingMessageIds.add(messageId);
  setTimeout(() => recentIncomingMessageIds.delete(messageId), 5 * 60 * 1000);
  return false;
}

function buildSerializableMessageSnapshot(msg) {
  const data = (msg && msg._data) ? msg._data : {};
  return {
    id: msg && msg.id && msg.id._serialized ? msg.id._serialized : null,
    from: msg && msg.from ? msg.from : (data.from ? data.from._serialized || data.from : null),
    to: msg && msg.to ? msg.to : (data.to ? data.to._serialized || data.to : null),
    author: msg && msg.author ? msg.author : null,
    body: msg && typeof msg.body !== 'undefined' ? msg.body : (typeof data.body !== 'undefined' ? data.body : null),
    type: msg && msg.type ? msg.type : (data.type || null),
    timestamp: msg && msg.timestamp ? msg.timestamp : (data.t || null),
    fromMe: !!(msg && msg.fromMe),
    hasMedia: !!(msg && msg.hasMedia),
    hasQuotedMsg: !!(msg && msg.hasQuotedMsg),
    data: toSerializable(data)
  };
}

function getMessageTimestampSeconds(msg) {
  if (!msg) return null;
  if (msg.timestamp) return Number(msg.timestamp) || null;
  if (msg._data && msg._data.t) return Number(msg._data.t) || null;
  return null;
}

function shouldCapturePolledMessage(msg, minimumTimestampSeconds) {
  if (!msg || msg.fromMe) return false;

  const messageId = msg && msg.id && msg.id._serialized ? msg.id._serialized : null;
  if (!messageId) return false;

  const timestamp = getMessageTimestampSeconds(msg);
  if (!timestamp) return false;

  return timestamp >= minimumTimestampSeconds;
}

function stopIncomingPolling(sessionId) {
  if (incomingPollers[sessionId]) {
    clearInterval(incomingPollers[sessionId]);
    delete incomingPollers[sessionId];
  }

  delete incomingPollStates[sessionId];
}

async function pollIncomingMessages(sessionId) {
  const client = clients[sessionId];
  if (!client) return;

  const state = incomingPollStates[sessionId] || {
    startedAtSeconds: Math.floor(Date.now() / 1000) - 90,
    lastPollAtSeconds: null,
    inFlight: false
  };

  if (state.inFlight) return;
  state.inFlight = true;
  incomingPollStates[sessionId] = state;

  try {
    const chats = await client.getChats();
    const minimumTimestampSeconds = Math.max(
      state.startedAtSeconds,
      (state.lastPollAtSeconds || state.startedAtSeconds) - 30
    );

    const recentChats = chats
      .filter(chat => !chat.isGroup)
      .sort((left, right) => {
        const leftTs = left && left.lastMessage && left.lastMessage.timestamp ? Number(left.lastMessage.timestamp) : 0;
        const rightTs = right && right.lastMessage && right.lastMessage.timestamp ? Number(right.lastMessage.timestamp) : 0;
        return rightTs - leftTs;
      })
      .slice(0, 15);

    for (const chat of recentChats) {
      let messages = [];
      try {
        messages = await chat.fetchMessages({ limit: 5 });
      } catch (error) {
        appendRuntimeLog('incoming_poll_chat_failed', safeJsonStringify({
          sessionId,
          chatId: chat && chat.id && chat.id._serialized ? chat.id._serialized : null,
          error: error && error.message ? error.message : String(error)
        }));
        continue;
      }

      for (const message of messages) {
        if (!shouldCapturePolledMessage(message, minimumTimestampSeconds)) {
          continue;
        }

        try {
          await forwardIncomingMessage(sessionId, message);
        } catch (error) {
          appendRuntimeLog('incoming_poll_forward_failed', safeJsonStringify({
            sessionId,
            messageId: message && message.id && message.id._serialized ? message.id._serialized : null,
            error: error && error.message ? error.message : String(error)
          }));
        }
      }
    }

    state.lastPollAtSeconds = Math.floor(Date.now() / 1000);
  } catch (error) {
    appendRuntimeLog('incoming_poll_failed', safeJsonStringify({
      sessionId,
      error: error && error.message ? error.message : String(error),
      stack: error && error.stack ? error.stack : null
    }));
  } finally {
    state.inFlight = false;
  }
}

function ensureIncomingPolling(sessionId) {
  if (incomingPollers[sessionId]) return;

  incomingPollStates[sessionId] = {
    startedAtSeconds: Math.floor(Date.now() / 1000) - 90,
    lastPollAtSeconds: null,
    inFlight: false
  };

  incomingPollers[sessionId] = setInterval(() => {
    if (statuses[sessionId] !== 'ready' && statuses[sessionId] !== 'authenticated') {
      return;
    }

    pollIncomingMessages(sessionId);
  }, 15000);

  pollIncomingMessages(sessionId);
}

async function forwardIncomingMessage(sessionId, msg) {
  const messageId = msg && msg.id && msg.id._serialized ? msg.id._serialized : null;
  if (rememberIncomingMessageId(messageId)) return;

  const snapshot = buildSerializableMessageSnapshot(msg);
  const fromAddr = snapshot.from || snapshot.author || null;
  const toAddr = snapshot.to || null;

  const rawObj = { original: snapshot };
  rawObj.meta = {
    from_normalized: normalizeWaAddr(fromAddr),
    to_normalized: normalizeWaAddr(toAddr),
    remote_wa_id: fromAddr
  };

  const payload = {
    session_client_id: sessionId,
    direction: 'in',
    from: fromAddr,
    to: toAddr,
    body: snapshot.body || null,
    message_id: messageId,
    remote_wa_id: fromAddr,
    raw: safeJsonStringify(rawObj)
  };

  await logMessageToServer(payload);
  appendRuntimeLog('incoming_logged', safeJsonStringify({ sessionId, messageId }));
}

async function inspectClientPage(client) {
  try {
    const page = client.pupPage;
    if (!page) {
      return { hasPage: false };
    }

    return await page.evaluate(() => ({
      hasPage: true,
      url: window.location.href,
      title: document.title,
      readyState: document.readyState,
      hasWWebJS: typeof window.WWebJS !== 'undefined',
      hasStore: typeof window.Store !== 'undefined',
      appState: typeof window.Store !== 'undefined' && window.Store.AppState ? window.Store.AppState.state : null,
      socketState: typeof window.Store !== 'undefined' && window.Store.Socket ? window.Store.Socket.state : null,
      wid: typeof window.Store !== 'undefined' && window.Store.User ? window.Store.User.getMaybeMePnUser && window.Store.User.getMaybeMePnUser() : null
    }));
  } catch (error) {
    return {
      hasPage: false,
      error: error && error.message ? error.message : String(error)
    };
  }
}

function looksConnected(pageState) {
  if (!pageState || !pageState.hasPage || !pageState.hasStore || !pageState.hasWWebJS) {
    return false;
  }

  const candidates = [pageState.appState, pageState.socketState]
    .filter(Boolean)
    .map(value => String(value).toUpperCase());

  return candidates.includes('CONNECTED') || candidates.includes('OPEN') || candidates.includes('OPENING');
}

function markSessionReady(sessionId, pageState, reason) {
  statuses[sessionId] = 'ready';
  appendRuntimeLog(reason, safeJsonStringify({ sessionId, pageState }));
  ensureIncomingPolling(sessionId);
}

function createClientForId(id) {
  if (clients[id]) return;
  const client = new Client({
    authStrategy: new LocalAuth({ clientId: id }),
    puppeteer: getPuppeteerOptions()
  });

  statuses[id] = 'initializing';

  client.on('qr', (qr) => {
    qrcode.generate(qr, { small: true });
    console.log(`[${id}] QR received — scan with WhatsApp mobile app.`);
    statuses[id] = 'qr';
  });

  client.on('ready', () => {
    console.log(`[${id}] WhatsApp client is ready`);
    markSessionReady(id, null, 'client_ready');
  });

  client.on('authenticated', () => {
    statuses[id] = 'authenticated';

    setTimeout(async () => {
      if (statuses[id] !== 'authenticated') {
        return;
      }

      const pageState = await inspectClientPage(client);

      if (statuses[id] === 'authenticated' && looksConnected(pageState)) {
        markSessionReady(id, pageState, 'client_ready_fallback');
      }
    }, 15000);

    setTimeout(async () => {
      if (statuses[id] !== 'authenticated') {
        return;
      }

      const pageState = await inspectClientPage(client);

      if (statuses[id] === 'authenticated' && looksConnected(pageState)) {
        markSessionReady(id, { ...pageState, recheck: true }, 'client_ready_fallback');
      }
    }, 30000);
  });

  client.on('auth_failure', (err) => {
    console.error(`[${id}] auth failure:`, err);
    statuses[id] = 'auth_failure';
    appendRuntimeLog('client_auth_failure', safeJsonStringify({ sessionId: id, error: err && err.message ? err.message : String(err) }));
  });

  client.on('error', (err) => {
    console.error(`[${id}] client error:`, err);
    statuses[id] = 'error';
    appendRuntimeLog('client_error', safeJsonStringify({
      sessionId: id,
      error: err && err.message ? err.message : String(err),
      stack: err && err.stack ? err.stack : null
    }));
  });

  client.on('disconnected', (reason) => {
    console.log(`[${id}] disconnected:`, reason);
    statuses[id] = 'disconnected';
    stopIncomingPolling(id);
    appendRuntimeLog('client_disconnected', safeJsonStringify({ sessionId: id, reason }));
  });

  client.on('message', msg => {
    if (msg && msg.body && msg.body.toLowerCase() === 'ping') {
      msg.reply('pong');
    }
  });

  // forward incoming messages to Laravel for logging
  client.on('message', async msg => {
    try {
      if (msg && msg.fromMe) return;
      await forwardIncomingMessage(id, msg);
    } catch (e) {
      appendRuntimeLog('incoming_log_failed', safeJsonStringify({ sessionId: id, error: e && e.message ? e.message : String(e) }));
      console.warn(`[${id}] failed to log incoming message:`, e && e.message ? e.message : e);
    }
  });

  client.on('message_create', async msg => {
    try {
      if (!msg || msg.fromMe) return;
      await forwardIncomingMessage(id, msg);
    } catch (e) {
      appendRuntimeLog('incoming_create_failed', safeJsonStringify({ sessionId: id, error: e && e.message ? e.message : String(e) }));
      console.warn(`[${id}] failed to log incoming message_create:`, e && e.message ? e.message : e);
    }
  });

  clients[id] = client;
  client.initialize().catch(err => {
    console.error(`[${id}] initialize error:`, err && err.message ? err.message : err);
    statuses[id] = 'error';
    appendRuntimeLog('client_initialize_error', safeJsonStringify({ sessionId: id, error: err && err.message ? err.message : String(err) }));
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

      if (activeSessionsUrl !== url) {
        activeSessionsUrl = url;
        console.log('Using sessions URL:', url);
      }

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
        stopIncomingPolling(old);
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
        const requestedTo = normalizePhoneDigits(to) || normalizeWaAddr(to);
        const rawObj = { original: sent || {} };
        rawObj.meta = {
          from_normalized: normalizeWaAddr(client.info && client.info.me ? client.info.me._serialized : sessionId),
          to_normalized: requestedTo || normalizeWaAddr(chatId),
          requested_to: to || null,
          requested_to_normalized: requestedTo,
          remote_wa_id: chatId,
          visitation_id: req.body && req.body.visitation_id ? String(req.body.visitation_id) : null,
          pasien_id: req.body && req.body.pasien_id ? String(req.body.pasien_id) : null
        };

        await logMessageToServer({
          session_client_id: sessionId,
          direction: 'out',
          from: client.info && client.info.me ? client.info.me._serialized : sessionId,
          to: requestedTo || chatId,
          body: req.body.caption || message || null,
          message_id: sent && sent.id && sent.id._serialized ? sent.id._serialized : null,
          remote_wa_id: chatId,
          visitation_id: req.body && req.body.visitation_id ? String(req.body.visitation_id) : null,
          pasien_id: req.body && req.body.pasien_id ? String(req.body.pasien_id) : null,
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
