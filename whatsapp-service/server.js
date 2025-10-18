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

  c.initialize();
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
