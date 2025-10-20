// Workaround for Node.js v22 crypto assertion failure on Windows
process.env.NODE_OPTIONS = '--openssl-legacy-provider';

const express = require('express');
const bodyParser = require('body-parser');
const cors = require('cors');
const qrcode = require('qrcode');
const qrcodeTerminal = require('qrcode-terminal');
const { Client, LocalAuth, MessageMedia } = require('whatsapp-web.js');
const fs = require('fs');
const path = require('path');

const app = express();
app.use(bodyParser.json());
app.use(cors());

// Global handlers to avoid process exit on unexpected errors
process.on('uncaughtException', (err) => {
  console.error('Uncaught exception:', err && err.stack ? err.stack : err);
  // do not exit -- keep service alive; log for debugging
});
process.on('unhandledRejection', (reason, p) => {
  console.error('Unhandled Rejection at:', p, 'reason:', reason);
});

// Multi-session support: manage multiple clients by clientId
const clients = new Map(); // clientId -> { client, lastQr, ready }
// Simple in-memory conversation state: key = `${clientId}::${from}` -> { expecting: 'choice' }
const conversations = new Map();

// Bot flows persisted to disk
const globalFlowsFile = path.join(__dirname, 'bot-flows.json');
let globalFlows = [];
// sessionId -> flows array
const flowsMap = new Map();

function ensureDefaultFlows(arr) {
  if (Array.isArray(arr) && arr.length) return arr;
  return [{
    id: 'main_menu',
    name: 'Main Menu',
    triggers: ['/start', 'menu', 'hi', 'hello'],
    choices: [
      { key: '1', label: 'Product info', reply: 'Our product is ...' },
      { key: '2', label: 'Contact support', reply: 'Support will contact you shortly.' }
    ],
    fallback: 'Invalid choice. Please reply with the number of your selection.'
  }];
}

function loadGlobalFlows() {
  try {
    if (fs.existsSync(globalFlowsFile)) {
      const raw = fs.readFileSync(globalFlowsFile, 'utf8');
      globalFlows = ensureDefaultFlows(JSON.parse(raw || '[]'));
      console.log(`Loaded ${globalFlows.length} global bot flows from ${globalFlowsFile}`);
    } else {
      globalFlows = ensureDefaultFlows([]);
      saveGlobalFlows();
    }
  } catch (e) {
    console.error('Failed to load global flows:', e);
    globalFlows = ensureDefaultFlows([]);
  }
}

function saveGlobalFlows() {
  try {
    fs.writeFileSync(globalFlowsFile, JSON.stringify(globalFlows, null, 2), 'utf8');
    console.log(`Saved ${globalFlows.length} global bot flows to ${globalFlowsFile}`);
    return true;
  } catch (e) {
    console.error('Failed to save global flows:', e);
    return false;
  }
}

function getFlowsFileForSession(session) {
  if (!session) return globalFlowsFile;
  const safe = session.replace(/[^a-zA-Z0-9-_]/g, '_');
  return path.join(__dirname, `bot-flows.${safe}.json`);
}

function loadFlowsFor(session) {
  try {
    if (!session) return globalFlows;
    const file = getFlowsFileForSession(session);
    if (fs.existsSync(file)) {
      const raw = fs.readFileSync(file, 'utf8');
      const f = ensureDefaultFlows(JSON.parse(raw || '[]'));
      flowsMap.set(session, f);
      console.log(`Loaded ${f.length} bot flows for session ${session} from ${file}`);
      return f;
    }
    // fallback to global
    flowsMap.set(session, globalFlows);
    return globalFlows;
  } catch (e) {
    console.error('Failed to load flows for session', session, e);
    flowsMap.set(session, globalFlows);
    return globalFlows;
  }
}

function saveFlowsFor(session, arr) {
  try {
    if (!Array.isArray(arr)) arr = [];
    if (!session) {
      globalFlows = ensureDefaultFlows(arr);
      return saveGlobalFlows();
    }
    const file = getFlowsFileForSession(session);
    fs.writeFileSync(file, JSON.stringify(arr, null, 2), 'utf8');
    flowsMap.set(session, ensureDefaultFlows(arr));
    console.log(`Saved ${arr.length} bot flows for session ${session} to ${file}`);
    return true;
  } catch (e) {
    console.error('Failed to save flows for session', session, e);
    return false;
  }
}

function createClient(clientId) {
  if (clients.has(clientId)) return clients.get(clientId);

  // Ensure LocalAuth stores data under the server folder (stable path)
  const authRoot = path.join(__dirname, '.wwebjs_auth');
  if (!fs.existsSync(authRoot)) {
    try { fs.mkdirSync(authRoot, { recursive: true }); } catch (e) { /* ignore */ }
  }

  const c = new Client({
    authStrategy: new LocalAuth({ clientId, dataPath: authRoot }),
    puppeteer: { headless: true }
  });

  const state = { client: c, lastQr: null, ready: false };

  c.on('qr', (qr) => {
    state.lastQr = qr;
    console.log(`[${clientId}] QR RECEIVED`);
    try {
      qrcodeTerminal.generate(qr, { small: true });
    } catch (e) {
      // ignore
    }
  });

  c.on('ready', () => {
    state.ready = true;
    // clear last QR once the client is ready — no longer needed
    state.lastQr = null;
    console.log(`[${clientId}] WhatsApp client ready`);
  });

  c.on('auth_failure', (msg) => {
    console.error(`[${clientId}] AUTH FAILURE`, msg);
  });

  c.on('disconnected', (reason) => {
    state.ready = false;
    console.warn(`[${clientId}] WhatsApp client disconnected:`, reason);
  });

  // Basic chatbot handler: send a simple menu and handle choice replies (1 or 2)
  c.on('message', async (msg) => {
    try {
      const from = msg.from; // e.g., 62812...@c.us
      const bodyRaw = (msg.body || '') + '';
      const body = bodyRaw.trim().toLowerCase();
      const key = `${clientId}::${from}`;

      // Lookup flows for this client (session-specific or global)
      const sessionFlows = flowsMap.get(clientId) || globalFlows;

      // Check flows: triggers
      let handled = false;
      for (const f of sessionFlows) {
        if (f.triggers && f.triggers.some(t => (t + '').toLowerCase() === body)) {
          // send menu
          const menu = (f.choices || []).map(ci => `${ci.key}. ${ci.label}`).join('\n');
          await c.sendMessage(from, `${f.name}\nPlease choose an option:\n${menu}`);
          conversations.set(key, { expecting: 'choice', flowId: f.id });
          handled = true;
          break;
        }
      }
      if (handled) return;

      const conv = conversations.get(key);
      if (conv && conv.expecting === 'choice') {
        const f = (sessionFlows || []).find(x => x.id === conv.flowId);
        if (!f) {
          conversations.delete(key);
          return;
        }
        const choice = (f.choices || []).find(ci => (ci.key + '') === body);
        if (choice) {
          await c.sendMessage(from, choice.reply || '');
          conversations.delete(key);
          return;
        }
        // fallback
        await c.sendMessage(from, f.fallback || 'Invalid choice.');
        return;
      }

      // You can extend here: add keyword-based auto-replies, or forward messages to a worker

    } catch (e) {
      console.error(`[${clientId}] message handler error`, e && e.stack ? e.stack : e);
    }
  });

  c.initialize();
  // load per-session flows into memory for this client
  try { loadFlowsFor(clientId); } catch (e) { /* ignore */ }
  clients.set(clientId, state);
  return state;
}

// Initialize default session so behavior stays compatible: 'belova'
createClient('belova');

// Optional: auto-initialize existing session folders on startup.
// Controlled by environment variables:
// WHATSAPP_AUTO_INIT=true  -> enable auto-init
// WHATSAPP_MAX_SESSIONS=5 -> max number of sessions to init (default 5)
try {
  const autoInit = (process.env.WHATSAPP_AUTO_INIT === '1' || process.env.WHATSAPP_AUTO_INIT === 'true');
  const maxInit = parseInt(process.env.WHATSAPP_MAX_SESSIONS || '5', 10) || 5;
  if (autoInit) {
    // find .wwebjs_auth directories under project root (depth-limited)
    const projectRoot = __dirname; // whatsapp-service folder
    function findAuthRoots(dir, depth) {
      const results = [];
      if (depth < 0) return results;
      try {
        const entries = fs.readdirSync(dir, { withFileTypes: true });
        for (const e of entries) {
          try {
            if (e.isDirectory()) {
              const full = path.join(dir, e.name);
              if (e.name === '.wwebjs_auth') {
                results.push(full);
              } else {
                results.push(...findAuthRoots(full, depth - 1));
              }
            }
          } catch (er) { /* ignore individual entry errors */ }
        }
      } catch (er) { /* ignore */ }
      return results;
    }

    const roots = findAuthRoots(projectRoot, 3); // search up to depth 3
    // always include the canonical location
    const canonical = path.join(__dirname, '.wwebjs_auth');
    if (!roots.includes(canonical)) roots.unshift(canonical);

    let allFound = [];
    for (const authRoot of roots) {
      if (!fs.existsSync(authRoot)) continue;
      try {
        const items = fs.readdirSync(authRoot, { withFileTypes: true }).filter(d => d.isDirectory()).map(d => d.name);
        const found = items.map(n => n.replace(/^session-/, ''))
                           .filter(s => s && !clients.has(s));
        if (found.length) {
          console.log(`Auto-init: found ${found.length} sessions in ${authRoot}`);
          allFound.push(...found.map(s => ({ s, authRoot })));
        }
      } catch (e) { /* ignore */ }
    }

    if (allFound.length) console.log(`Auto-init sessions: will initialize up to ${maxInit} sessions (${allFound.length} found)`);
    for (const entry of allFound.slice(0, maxInit)) {
      const sid = entry.s;
      try {
        createClient(sid);
        console.log(`Auto-initialized session: ${sid}`);
      } catch (e) {
        console.error(`Failed to auto-init session ${sid}:`, e.message || e);
      }
    }
    if (allFound.length > maxInit) console.warn(`Skipped ${allFound.length - maxInit} sessions due to WHATSAPP_MAX_SESSIONS limit`);
  }
} catch (e) {
  console.error('Auto-init check failed:', e.message || e);
}

// Load global flows at startup
loadGlobalFlows();

// Bot flows management endpoints (support optional ?session=ID)
app.get('/bot-flows', (req, res) => {
  const session = (req.query && req.query.session) ? ('' + req.query.session) : '';
  try {
    const f = session ? (flowsMap.get(session) || loadFlowsFor(session)) : globalFlows;
    return res.json({ flows: f });
  } catch (e) {
    return res.status(500).json({ success: false, error: e && e.message ? e.message : String(e) });
  }
});

app.post('/bot-flows', (req, res) => {
  const newFlows = req.body && req.body.flows;
  const session = (req.query && req.query.session) ? ('' + req.query.session) : '';
  if (!Array.isArray(newFlows)) return res.status(400).json({ success: false, error: 'flows (array) is required' });
  const ok = saveFlowsFor(session, newFlows);
  return ok ? res.json({ success: true, flows: newFlows }) : res.status(500).json({ success: false, error: 'Failed to save flows' });
});

// Scheduled messages endpoints
app.get('/scheduled-messages', (req, res) => {
  // optional ?session= to filter
  const session = (req.query && req.query.session) ? ('' + req.query.session) : null;
  const list = session ? scheduled.filter(s => (s.session || 'belova') === session) : scheduled;
  return res.json({ scheduled: list });
});

app.post('/scheduled-messages', (req, res) => {
  const { session, number, message, sendAt, maxAttempts } = req.body || {};
  if (!number || !sendAt) return res.status(400).json({ success: false, error: 'number and sendAt are required' });
  const id = 'job_' + Date.now() + '_' + Math.random().toString(36).slice(2,8);
  const job = {
    id, session: session || 'belova', number: (number + ''), message: message || '', sendAt: new Date(sendAt).toISOString(), createdAt: new Date().toISOString(), attempts: 0, maxAttempts: maxAttempts || 3, sent: false
  };
  scheduled.push(job);
  saveScheduled();
  return res.json({ success: true, job });
});

app.delete('/scheduled-messages/:id', (req, res) => {
  const id = req.params.id;
  const idx = scheduled.findIndex(s => s.id === id);
  if (idx === -1) return res.status(404).json({ success: false, error: 'not found' });
  const job = scheduled.splice(idx, 1)[0];
  saveScheduled();
  return res.json({ success: true, job });
});

app.get('/status', async (req, res) => {
  try {
    const clientId = (req.query.session || 'belova') + '';
    const state = clients.get(clientId) || null;
    if (!state) {
      // no client yet for this id
      return res.json({ status: 'not_initialized', session: clientId });
    }

    if (state.ready) return res.json({ status: 'ready', session: clientId });
    if (state.lastQr) {
      const dataUrl = await qrcode.toDataURL(state.lastQr);
      // also provide a simple HTML page to view the QR
      return res.json({ status: 'qr', qrcode: dataUrl, qr_url: `/qr?session=${encodeURIComponent(clientId)}`, session: clientId });
    }
    return res.json({ status: 'initializing', session: clientId });
  } catch (e) {
    return res.status(500).json({ status: 'error', error: e.message });
  }
});

// Debug: list registered routes (for troubleshooting)
app.get('/_debug/routes', (req, res) => {
  try {
    const routes = [];
    if (app && app._router && Array.isArray(app._router.stack)) {
      app._router.stack.forEach((middleware) => {
        if (middleware.route) {
          // routes registered directly on the app
          const methods = Object.keys(middleware.route.methods).join(',');
          routes.push({ path: middleware.route.path, methods });
        } else if (middleware.name === 'router' && middleware.handle && middleware.handle.stack) {
          // router middleware
          middleware.handle.stack.forEach((handler) => {
            if (handler.route) {
              const methods = Object.keys(handler.route.methods).join(',');
              routes.push({ path: handler.route.path, methods });
            }
          });
        }
      });
    }
    return res.json({ routes });
  } catch (e) {
    return res.status(500).json({ error: String(e) });
  }
});

// Simple QR page
app.get('/qr', async (req, res) => {
  try {
    const clientId = (req.query.session || 'belova') + '';
    const state = clients.get(clientId) || null;
    if (!state || !state.lastQr) return res.status(404).send('No QR available for session: ' + clientId);
    const dataUrl = await qrcode.toDataURL(state.lastQr);
    return res.send(`<!doctype html><html><body style="display:flex;align-items:center;justify-content:center;height:100vh;"><img src="${dataUrl}" alt="qr for ${clientId}"/></body></html>`);
  } catch (e) {
    return res.status(500).send('Error generating QR');
  }
});

app.post('/send', async (req, res) => {
  const { number, message, session } = req.body || {};
  if (!number) return res.status(400).json({ success: false, error: 'number is required' });

  const clientId = (session || 'belova') + '';
  const state = clients.get(clientId);
  if (!state) return res.status(404).json({ success: false, error: `Session '${clientId}' not found. Create or initialize the session first.` });

  try {
    if (!state.ready) return res.status(503).json({ success: false, error: `WhatsApp client for session '${clientId}' not ready` });

    // WhatsApp expects number in format 62812...@c.us for phone
    const sanitized = (number + '').replace(/[^0-9]/g, '');
    const id = sanitized.includes('@c.us') ? sanitized : `${sanitized}@c.us`;

    const sent = await state.client.sendMessage(id, message || '');
    return res.json({ success: true, id: sent.id._serialized, session: clientId });
  } catch (e) {
    console.error(`[${clientId}] Send error`, e);
    return res.status(500).json({ success: false, error: e.message });
  }
});

// Sessions management endpoints
app.get('/sessions', (req, res) => {
  const list = [];
  for (const [id, st] of clients.entries()) {
    // only report hasQr if a QR exists and the client is not already ready
    const hasQr = !!st.lastQr && !st.ready;
    list.push({ session: id, ready: !!st.ready, hasQr });
  }
  return res.json({ sessions: list });
});

app.post('/sessions', (req, res) => {
  const { session } = req.body || {};
  if (!session || typeof session !== 'string') return res.status(400).json({ success: false, error: 'session (string) is required' });
  const safeId = session.replace(/[^a-zA-Z0-9-_]/g, '_');
  if (clients.has(safeId)) return res.json({ success: true, session: safeId, message: 'Session already initialized' });
  createClient(safeId);
  return res.json({ success: true, session: safeId, message: 'Session initialized' });
});

// Logout a session (instruct client to logout from WhatsApp)
app.post('/sessions/logout', async (req, res) => {
  const { session } = req.body || {};
  if (!session) return res.status(400).json({ success: false, error: 'session is required' });
  const state = clients.get(session);
  if (!state) return res.status(404).json({ success: false, error: 'session not found' });
  try {
    if (state.client && typeof state.client.logout === 'function') {
      await state.client.logout();
    }
    state.ready = false;
    state.lastQr = null;
    return res.json({ success: true, session, message: 'Logged out' });
  } catch (e) {
    console.error(`[${session}] Logout error`, e);
    return res.status(500).json({ success: false, error: e.message });
  }
});

// Delete a session and remove its LocalAuth data
app.delete('/sessions', async (req, res) => {
  const session = (req.body && req.body.session) || req.query.session;
  if (!session) return res.status(400).json({ success: false, error: 'session is required' });
  const state = clients.get(session);
  try {
    // If a client exists, attempt a graceful logout/destroy first.
    if (state && state.client) {
      try {
        if (typeof state.client.logout === 'function') {
          await state.client.logout();
        }
      } catch (e) {
        console.warn(`Logout for session ${session} failed (continuing):`, e && e.message ? e.message : e);
      }

      try {
        if (typeof state.client.destroy === 'function') {
          await state.client.destroy();
        }
      } catch (e) {
        console.warn(`Destroy for session ${session} failed (continuing):`, e && e.message ? e.message : e);
      }

      // Remove from clients map to prevent further event handling
      try { clients.delete(session); } catch (e) { /* ignore */ }
    }

    // remove auth folder - LocalAuth may use folder names like 'session-<id>'
    const authRoot = path.join(__dirname, '.wwebjs_auth');
    const candidates = [path.join(authRoot, `session-${session}`), path.join(authRoot, session)];
    let removed = false;
    for (const sessionPath of candidates) {
      try {
        if (fs.existsSync(sessionPath)) {
          // attempt removal; if it fails, log but continue to next candidate
          try {
            fs.rmSync(sessionPath, { recursive: true, force: true });
            removed = true;
          } catch (e) {
            console.error(`Failed to remove session folder ${sessionPath}:`, e);
          }
        }
      } catch (e) {
        console.error(`Error checking/removing session path ${sessionPath}:`, e);
      }
    }

    if (!removed) {
      return res.status(404).json({ success: false, error: 'Session folder not found or could not be removed' });
    }

    return res.json({ success: true, session, message: 'Session deleted' });
  } catch (e) {
    console.error('Delete session error', e);
    return res.status(500).json({ success: false, error: e && e.message ? e.message : String(e) });
  }
});

const port = process.env.PORT || 3000;

// Scheduled messages persistence
const scheduledFile = path.join(__dirname, 'scheduled-messages.json');
let scheduled = [];

function loadScheduled() {
  try {
    if (fs.existsSync(scheduledFile)) {
      scheduled = JSON.parse(fs.readFileSync(scheduledFile, 'utf8') || '[]');
      console.log(`Loaded ${scheduled.length} scheduled messages from ${scheduledFile}`);
    } else {
      scheduled = [];
      saveScheduled();
    }
  } catch (e) {
    console.error('Failed to load scheduled messages:', e);
    scheduled = [];
  }
}

function saveScheduled() {
  try {
    fs.writeFileSync(scheduledFile, JSON.stringify(scheduled, null, 2), 'utf8');
    return true;
  } catch (e) {
    console.error('Failed to save scheduled messages:', e);
    return false;
  }
}

// Background scheduler: check every 30s
const SCHEDULER_INTERVAL = parseInt(process.env.SCHEDULER_INTERVAL_SECONDS || '30', 10);
function startScheduler() {
  setInterval(async () => {
    const now = Date.now();
    const due = scheduled.filter(s => !s.sent && new Date(s.sendAt).getTime() <= now);
    for (const job of due) {
      // attempt to send
      try {
        const sessionId = job.session || 'belova';
        const state = clients.get(sessionId);
        if (!state || !state.ready) {
          // increment attempt and skip
          job.attempts = (job.attempts || 0) + 1;
          job.lastError = job.lastError || 'session_not_ready';
          if ((job.attempts || 0) >= (job.maxAttempts || 3)) {
            job.sent = true;
            job.failed = true;
            job.failedAt = new Date().toISOString();
          }
          continue;
        }

        // send message
        const sanitized = (job.number + '').replace(/[^0-9]/g, '');
        const id = sanitized.includes('@c.us') ? sanitized : `${sanitized}@c.us`;
        await state.client.sendMessage(id, job.message || '');
        job.sent = true;
        job.sentAt = new Date().toISOString();
      } catch (e) {
        job.attempts = (job.attempts || 0) + 1;
        job.lastError = (e && e.message) ? e.message : String(e);
        if ((job.attempts || 0) >= (job.maxAttempts || 3)) {
          job.sent = true;
          job.failed = true;
          job.failedAt = new Date().toISOString();
        }
      }
    }
    // persist changes if any due jobs were modified
    if (due.length) saveScheduled();
  }, SCHEDULER_INTERVAL * 1000);
}

// load scheduled messages now
loadScheduled();
startScheduler();

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
