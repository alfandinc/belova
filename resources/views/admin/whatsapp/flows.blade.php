@extends('layouts.admin.app')

@section('navbar')
    @include('layouts.admin.navbar')
@endsection

@section('content')
<div class="container">
  <h1>WhatsApp Bot Flows</h1>
  <p class="text-muted">Create and edit chatbot flows. Changes are persisted to <code>whatsapp-service/bot-flows.json</code>.</p>

  <div id="alerts"></div>

  <div class="row">
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <h5>Flows</h5>
          <div class="mb-2">
            <label class="form-label">Session</label>
            <select id="sessionSelect" class="form-control">
              <option value="">Global</option>
            </select>
          </div>
          <ul id="flowsList" class="list-group"></ul>
          <button id="newFlowBtn" class="btn btn-primary btn-sm mt-2">New Flow</button>
        </div>
      </div>
    </div>

    <div class="col-md-8">
      <div class="card">
        <div class="card-body">
          <h5 id="editorTitle">Flow Editor</h5>
          <form id="flowForm">
            <input type="hidden" id="flowIdInput" />

            <div class="mb-3">
              <label class="form-label">Name</label>
              <input id="flowName" class="form-control" />
            </div>

            <div class="mb-3">
              <label class="form-label">Triggers (comma separated, e.g. /start, menu, hi)</label>
              <input id="flowTriggers" class="form-control" />
            </div>

            <div class="mb-3">
              <label class="form-label">Choices (one per line, format: key|label|reply)</label>
              <textarea id="flowChoices" rows="6" class="form-control" placeholder="1|Product info|Our product is ...\n2|Contact support|Support will contact you"></textarea>
            </div>

            <div class="mb-3">
              <label class="form-label">Fallback message</label>
              <input id="flowFallback" class="form-control" />
            </div>

            <div class="d-flex gap-2">
              <button id="saveFlowBtn" class="btn btn-success" type="submit">Save Flow</button>
              <button id="deleteFlowBtn" class="btn btn-danger" type="button">Delete Flow</button>
              <button id="cancelEditBtn" class="btn btn-secondary" type="button">Cancel</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const apiBase = 'http://127.0.0.1:3000';
let flows = [];
let editingId = null;

function showAlert(type, msg) {
  const alerts = document.getElementById('alerts');
  const el = document.createElement('div');
  el.className = 'alert alert-' + type + '';
  el.innerText = msg;
  alerts.appendChild(el);
  setTimeout(() => el.remove(), 5000);
}

async function loadFlows(session = '') {
  try {
    const url = apiBase + '/bot-flows' + (session ? ('?session=' + encodeURIComponent(session)) : '');
    const r = await fetch(url);
    if (!r.ok) throw new Error('Failed to load flows');
    const data = await r.json();
    flows = data.flows || [];
    renderFlowsList();
    if (flows.length) loadIntoEditor(flows[0].id);
    else clearEditor();
  } catch (e) { showAlert('danger', 'Error loading flows: ' + e.message); }
}

function renderFlowsList() {
  const ul = document.getElementById('flowsList');
  ul.innerHTML = '';
  flows.forEach(f => {
    const li = document.createElement('li');
    li.className = 'list-group-item d-flex justify-content-between align-items-center';
    li.innerHTML = `<div><strong>${f.name || f.id}</strong><br/><small class="text-muted">${f.id}</small></div><div><button data-id="${f.id}" class="btn btn-sm btn-outline-primary selectFlowBtn">Edit</button></div>`;
    ul.appendChild(li);
  });
}

function loadIntoEditor(id) {
  const f = flows.find(x => x.id === id);
  if (!f) return showAlert('warning', 'Flow not found');
  editingId = f.id;
  document.getElementById('flowIdInput').value = f.id;
  document.getElementById('flowName').value = f.name || '';
  document.getElementById('flowTriggers').value = (f.triggers || []).join(', ');
  document.getElementById('flowChoices').value = (f.choices || []).map(c => `${c.key}|${c.label}|${c.reply}`).join('\n');
  document.getElementById('flowFallback').value = f.fallback || '';
  document.getElementById('editorTitle').innerText = 'Editing: ' + (f.name || f.id);
}

function clearEditor() {
  editingId = null;
  document.getElementById('flowIdInput').value = '';
  document.getElementById('flowName').value = '';
  document.getElementById('flowTriggers').value = '';
  document.getElementById('flowChoices').value = '';
  document.getElementById('flowFallback').value = '';
  document.getElementById('editorTitle').innerText = 'Flow Editor';
}

function parseChoices(text) {
  return text.split('\n').map(line => line.trim()).filter(l => l).map(line => {
    const parts = line.split('|');
    return { key: (parts[0] || '').trim(), label: (parts[1] || '').trim(), reply: (parts[2] || '').trim() };
  });
}

async function saveFlowsToServer(newFlows, session = '') {
  const url = apiBase + '/bot-flows' + (session ? ('?session=' + encodeURIComponent(session)) : '');
  const r = await fetch(url, {
    method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ flows: newFlows })
  });
  if (!r.ok) throw new Error('Save failed');
  const data = await r.json();
  return data.flows;
}

async function loadSessions() {
  try {
    const r = await fetch(apiBase + '/sessions');
    if (!r.ok) throw new Error('Failed to load sessions');
    const d = await r.json();
    const select = document.getElementById('sessionSelect');
    // clear leaving Global option
    select.innerHTML = '<option value="">Global</option>';
    (d.sessions || []).forEach(s => {
      const opt = document.createElement('option');
      opt.value = s.session;
      opt.innerText = s.session + (s.ready ? ' (ready)' : s.hasQr ? ' (needs QR)' : '');
      select.appendChild(opt);
    });
  } catch (e) { showAlert('warning', 'Could not load sessions: ' + e.message); }
}

// event handlers
window.addEventListener('DOMContentLoaded', async () => {
  await loadSessions();
  const sel = document.getElementById('sessionSelect');
  sel.addEventListener('change', () => loadFlows(sel.value));
  await loadFlows(sel.value);

  document.getElementById('newFlowBtn').addEventListener('click', () => {
    clearEditor();
    document.getElementById('flowIdInput').value = 'flow_' + Date.now();
  });

  document.getElementById('flowsList').addEventListener('click', (ev) => {
    const btn = ev.target.closest('.selectFlowBtn');
    if (!btn) return;
    const id = btn.getAttribute('data-id');
    loadIntoEditor(id);
  });

  document.getElementById('cancelEditBtn').addEventListener('click', () => clearEditor());

  document.getElementById('flowForm').addEventListener('submit', async (ev) => {
    ev.preventDefault();
    const id = document.getElementById('flowIdInput').value || ('flow_' + Date.now());
    const name = document.getElementById('flowName').value || id;
    const triggers = (document.getElementById('flowTriggers').value || '').split(',').map(s => s.trim()).filter(Boolean);
    const choices = parseChoices(document.getElementById('flowChoices').value || '');
    const fallback = document.getElementById('flowFallback').value || '';

    // replace or add
    const idx = flows.findIndex(x => x.id === id);
    const newFlow = { id, name, triggers, choices, fallback };
    if (idx >= 0) flows[idx] = newFlow; else flows.push(newFlow);

    try {
      const session = document.getElementById('sessionSelect').value || '';
      const updated = await saveFlowsToServer(flows, session);
      flows = updated;
      renderFlowsList();
      showAlert('success', 'Flows saved');
      loadIntoEditor(id);
    } catch (e) {
      showAlert('danger', 'Failed to save flows: ' + e.message);
    }
  });

  document.getElementById('deleteFlowBtn').addEventListener('click', async () => {
    const id = document.getElementById('flowIdInput').value;
    if (!id) return showAlert('warning', 'No flow selected');
    if (!confirm('Delete flow ' + id + '? This cannot be undone.')) return;
    const idx = flows.findIndex(x => x.id === id);
    if (idx >= 0) flows.splice(idx, 1);
    try {
      const session = document.getElementById('sessionSelect').value || '';
      const updated = await saveFlowsToServer(flows, session);
      flows = updated;
      renderFlowsList();
      clearEditor();
      showAlert('success', 'Flow deleted');
    } catch (e) {
      showAlert('danger', 'Failed to delete flow: ' + e.message);
    }
  });
});
</script>

@endsection
