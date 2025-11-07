@extends('layouts.admin.app')

@section('navbar')
    @include('layouts.admin.navbar')
@endsection

@section('content')
<div class="container-fluid">
  <h1>Scheduled WhatsApp Messages</h1>
  <p class="text-muted">Create, list and delete scheduled messages per session.</p>

  <div id="alerts"></div>

  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-body">
          <h5>Create Scheduled Message</h5>
          <form id="scheduleForm">
            <div class="row">
              <div class="col-md-4 mb-3">
                <label class="form-label">Session</label>
                <select id="sessionSelect" class="form-control"><option value="">Global</option></select>
              </div>
              <div class="col-md-8 mb-3">
                <label class="form-label">Number (e.g. 628123...)</label>
                <input id="schedNumber" class="form-control" required />
              </div>
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
            <div class="d-flex justify-content-end">
              <button class="btn btn-primary" type="submit">Schedule</button>
              <button id="refreshBtn" class="btn btn-secondary ml-2" type="button">Refresh</button>
            </div>
          </form>

          <hr />
          <h5>Scheduled</h5>
          <table id="scheduledTable" class="table table-sm table-striped" style="width:100%">
            <thead>
              <tr>
                <th>Number</th>
                <th>Message</th>
                <th>Send At</th>
                <th>Attempts</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const apiBase = '{{ url('/admin/api/whatsapp') }}';

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
  const r = await fetch(apiBase + '/sessions', { credentials: 'same-origin' });
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

let scheduledTable = null;
function initScheduledTable() {
  if (scheduledTable) scheduledTable.destroy();
  scheduledTable = $('#scheduledTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: apiBase + '/scheduled-data',
      data: function(d) { d.session = document.getElementById('sessionSelect').value || ''; }
    },
    columns: [
      { data: 'number', name: 'number' },
      { data: 'message', name: 'message' },
      { data: 'send_at', name: 'send_at' },
      { data: 'attempts', name: 'attempts' },
      { data: 'status', name: 'status', render: function(data, type, row) {
          if (!data) return '';
          var s = String(data).toLowerCase();
          if (s === 'sent' || s === 'delivered') return '<span class="badge badge-success">' + data + '</span>';
          if (s === 'failed' || s === 'error') return '<span class="badge badge-danger">' + data + '</span>';
          return '<span class="badge badge-secondary">' + data + '</span>';
        }
      },
      { data: 'actions', name: 'actions', orderable: false, searchable: false }
    ],
    order: [[2, 'asc']]
  });

  // Auto refresh every 10s to pick up changes
  setInterval(() => { if (scheduledTable) scheduledTable.ajax.reload(null, false); }, 10000);
}

window.addEventListener('DOMContentLoaded', async () => {
  await loadSessions();
  const sel = document.getElementById('sessionSelect');
  initScheduledTable();
  // reload table when session changes
  sel.addEventListener('change', () => { if (scheduledTable) scheduledTable.ajax.reload(); });

  document.getElementById('refreshBtn').addEventListener('click', () => { if (scheduledTable) scheduledTable.ajax.reload(); });

  document.getElementById('scheduleForm').addEventListener('submit', async (ev) => {
    ev.preventDefault();
    const session = document.getElementById('sessionSelect').value || '';
    const number = document.getElementById('schedNumber').value;
    const message = document.getElementById('schedMessage').value;
    const sendAt = document.getElementById('schedSendAt').value;
    const maxAttempts = parseInt(document.getElementById('schedMaxAttempts').value || '3', 10);
    if (!number || !sendAt) return showAlert('warning', 'Number and sendAt are required');
    try {
      const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
      const payload = { session: session || undefined, number, message, sendAt, maxAttempts };
  const r = await fetch(apiBase + '/scheduled', { method: 'POST', credentials: 'same-origin', headers: {'Content-Type':'application/json','X-CSRF-TOKEN': csrf}, body: JSON.stringify(payload) });
      if (!r.ok) throw new Error('Failed to create scheduled message');
      const d = await r.json();
      showAlert('success', 'Scheduled created');
      if (scheduledTable) scheduledTable.ajax.reload();
    } catch (e) { showAlert('danger', 'Failed to schedule: ' + e.message); }
  });
  // delegated delete handler for rows in DataTable
  $('#scheduledTable tbody').on('click', '.deleteJobBtn', async function (ev) {
    const btn = ev.currentTarget;
    const id = btn.getAttribute('data-id');
    if (!id) return;
    if (!confirm('Delete scheduled job ' + id + '?')) return;
    try {
      const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
      const r = await fetch(apiBase + '/scheduled/' + encodeURIComponent(id), { method: 'DELETE', credentials: 'same-origin', headers: {'X-CSRF-TOKEN': csrf} });
      if (!r.ok) throw new Error('Delete failed');
      showAlert('success', 'Deleted');
      if (scheduledTable) scheduledTable.ajax.reload();
    } catch (e) { showAlert('danger', 'Failed to delete: ' + e.message); }
  });
});
</script>

@endsection
