@extends('layouts.admin.app')

@section('navbar')
    @include('layouts.admin.navbar')
@endsection

@section('content')
<div class="container">
  <h1>Scheduled WhatsApp Messages</h1>
  <p class="text-muted">Create, list and delete scheduled messages per session.</p>

  <div id="alerts"></div>

  <div class="row">
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <label>Session</label>
          <select id="sessionSelect" class="form-control mb-2"><option value="">Global</option></select>

          <h5>Scheduled</h5>
          <ul id="scheduledList" class="list-group"></ul>
        </div>
      </div>
    </div>

    <div class="col-md-8">
      <div class="card">
        <div class="card-body">
          <h5>Create Scheduled Message</h5>
          <form id="scheduleForm">
            <div class="mb-3">
              <label class="form-label">Number (e.g. 628123...)</label>
              <input id="schedNumber" class="form-control" required />
            </div>
            <div class="mb-3">
              <label class="form-label">Message</label>
              <textarea id="schedMessage" class="form-control" rows="4"></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label">Send At (ISO or local datetime)</label>
              <input id="schedSendAt" type="datetime-local" class="form-control" required />
            </div>
            <div class="mb-3">
              <label class="form-label">Max Attempts</label>
              <input id="schedMaxAttempts" type="number" class="form-control" value="3" />
            </div>
            <div class="d-flex gap-2">
              <button class="btn btn-primary" type="submit">Schedule</button>
              <button id="refreshBtn" class="btn btn-secondary" type="button">Refresh</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const apiBase = 'http://127.0.0.1:3000';

function showAlert(type, msg) {
  const alerts = document.getElementById('alerts');
  const el = document.createElement('div');
  el.className = 'alert alert-' + type;
  el.innerText = msg;
  alerts.appendChild(el);
  setTimeout(() => el.remove(), 5000);
}

async function loadSessions() {
  try {
    const r = await fetch(apiBase + '/sessions');
    if (!r.ok) throw new Error('Failed to load sessions');
    const d = await r.json();
    const select = document.getElementById('sessionSelect');
    select.innerHTML = '<option value="">Global</option>';
    (d.sessions || []).forEach(s => {
      const opt = document.createElement('option');
      opt.value = s.session;
      opt.innerText = s.session + (s.ready ? ' (ready)' : s.hasQr ? ' (needs QR)' : '');
      select.appendChild(opt);
    });
  } catch (e) { showAlert('danger', 'Could not load sessions: ' + e.message); }
}

async function loadScheduled() {
  try {
    const session = document.getElementById('sessionSelect').value || '';
    const url = apiBase + '/scheduled-messages' + (session ? ('?session=' + encodeURIComponent(session)) : '');
    const r = await fetch(url);
    if (!r.ok) throw new Error('Failed to load scheduled messages');
    const d = await r.json();
    const ul = document.getElementById('scheduledList');
    ul.innerHTML = '';
    (d.scheduled || []).forEach(job => {
      const li = document.createElement('li');
      li.className = 'list-group-item';
      li.innerHTML = `<div><strong>${job.number}</strong> <small class="text-muted">${job.sendAt}</small></div>
                      <div>${job.message || ''}</div>
                      <div class="mt-2"><small>Attempts: ${job.attempts || 0} ${job.failed ? ' (failed)' : job.sent ? ' (sent)' : ''}</small></div>
                      <div class="mt-2"><button data-id="${job.id}" class="btn btn-sm btn-danger deleteJobBtn">Delete</button></div>`;
      ul.appendChild(li);
    });
  } catch (e) { showAlert('danger', 'Failed to load scheduled messages: ' + e.message); }
}

window.addEventListener('DOMContentLoaded', async () => {
  await loadSessions();
  const sel = document.getElementById('sessionSelect');
  sel.addEventListener('change', loadScheduled);
  await loadScheduled();

  document.getElementById('refreshBtn').addEventListener('click', loadScheduled);

  document.getElementById('scheduleForm').addEventListener('submit', async (ev) => {
    ev.preventDefault();
    const session = document.getElementById('sessionSelect').value || '';
    const number = document.getElementById('schedNumber').value;
    const message = document.getElementById('schedMessage').value;
    const sendAt = document.getElementById('schedSendAt').value;
    const maxAttempts = parseInt(document.getElementById('schedMaxAttempts').value || '3', 10);
    if (!number || !sendAt) return showAlert('warning', 'Number and sendAt are required');
    try {
      const payload = { session: session || undefined, number, message, sendAt, maxAttempts };
      const r = await fetch(apiBase + '/scheduled-messages', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload) });
      if (!r.ok) throw new Error('Failed to create scheduled message');
      const d = await r.json();
      showAlert('success', 'Scheduled created');
      await loadScheduled();
    } catch (e) { showAlert('danger', 'Failed to schedule: ' + e.message); }
  });

  document.getElementById('scheduledList').addEventListener('click', async (ev) => {
    const btn = ev.target.closest('.deleteJobBtn');
    if (!btn) return;
    const id = btn.getAttribute('data-id');
    if (!confirm('Delete scheduled job ' + id + '?')) return;
    try {
      const r = await fetch(apiBase + '/scheduled-messages/' + encodeURIComponent(id), { method: 'DELETE' });
      if (!r.ok) throw new Error('Delete failed');
      showAlert('success', 'Deleted');
      await loadScheduled();
    } catch (e) { showAlert('danger', 'Failed to delete: ' + e.message); }
  });
});
</script>

@endsection
