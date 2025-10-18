@extends('layouts.admin.app')

@section('navbar')
    @include('layouts.admin.navbar')
@endsection

@section('content')
<div class="container">
    <div class="card shadow-sm">
        <div class="card-body">
            <h3 class="card-title">WhatsApp - Send Message</h3>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                    @if(strpos(session('error'), 'Node.js v22') !== false)
                        <br><br>
                        <strong>Quick Fix:</strong>
                        <ol>
                            <li>Download Node.js v18 LTS from <a href="https://nodejs.org/" target="_blank">nodejs.org</a></li>
                            <li>Uninstall current Node.js v22 from Windows Settings > Apps</li>
                            <li>Install Node.js v18 LTS</li>
                            <li>Restart this page and try again</li>
                        </ol>
                    @endif
                </div>
            @endif
            @if(session('warning'))
                <div class="alert alert-warning">{{ session('warning') }}</div>
            @endif

            <!-- Service Control Section -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5>Service Control</h5>
                    <div class="btn-group" role="group">
                        <form method="POST" action="{{ route('admin.whatsapp.start') }}" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-success">Start Service</button>
                        </form>
                    </div>
                    <small class="form-text text-muted">Use these buttons to start or stop the WhatsApp background service. Click Debug Info to troubleshoot issues.</small>
                </div>
            </div>

            

            <hr>
            <h5>Sessions</h5>
            <div id="sessions-panel">
                <div class="d-flex mb-2 align-items-center">
                    <div>
                        <button id="refresh-sessions" class="btn btn-sm btn-secondary">Refresh</button>
                        <label class="ml-2">Auto-refresh <input type="checkbox" id="auto-refresh" checked></label>
                    </div>
                    <div class="ml-3">
                        <input type="text" id="new-session-id" placeholder="new-session-id" class="form-control d-inline-block" style="width:200px;">
                        <button id="create-session" class="btn btn-sm btn-success">Create</button>
                    </div>
                    <div class="ml-auto text-muted">Auto-refresh interval: <span id="refresh-interval">5</span>s</div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Session</th>
                                <th>Status</th>
                                <th>QR</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="sessions-tbody">
                            <tr><td colspan="4">Loading sessions...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <hr>

            <!-- Send Message Section -->
            <h5>Send Message</h5>
            <form method="POST" action="{{ route('admin.whatsapp.send') }}">
                @csrf
                <div class="form-group">
                    <label for="session">Session (optional)</label>
                    <input type="text" name="session" id="session" class="form-control" placeholder="Session id (e.g. belova, wa2)">
                    <small class="form-text text-muted">Leave empty to use default session <code>belova</code>.</small>
                </div>
                <div class="form-group">
                    <label for="number">Phone number (with country code, e.g. 62812...)</label>
                    <input type="text" name="number" id="number" class="form-control" placeholder="62812..." required>
                </div>

                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea name="message" id="message" rows="5" class="form-control" placeholder="Your message"></textarea>
                </div>

                <button class="btn btn-primary" type="submit">Send</button>
            </form>

            <script>
                let autoRefreshTimer = null;
                const baseUrl = '{{ env('WHATSAPP_SERVICE_URL', 'http://127.0.0.1:3000') }}';

                function badge(text, type) {
                    const cls = type === 'success' ? 'badge-success' : (type === 'warning' ? 'badge-warning' : 'badge-secondary');
                    return `<span class="badge ${cls}">${text}</span>`;
                }

                async function loadSessions() {
                    const tbody = document.getElementById('sessions-tbody');
                    try {
                        const res = await fetch(baseUrl + '/sessions');
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        const json = await res.json();
                        const sessions = json.sessions || [];
                        if (!sessions.length) {
                            tbody.innerHTML = '<tr><td colspan="4">No sessions</td></tr>';
                            return;
                        }

                        tbody.innerHTML = sessions.map(s => {
                            const status = s.ready ? badge('ready', 'success') : badge('not ready', 'warning');
                            const qr = s.hasQr ? badge('qr available', 'warning') : (s.ready ? badge('connected', 'success') : badge('none', 'secondary'));
                            const qrLink = `<a href="${baseUrl}/qr?session=${encodeURIComponent(s.session)}" target="_blank" class="btn btn-sm btn-outline-secondary">QR</a>`;
                            const logoutBtn = `<button data-session="${s.session}" class="btn btn-sm btn-outline-warning logout-session">Logout</button>`;
                            const deleteBtn = `<button data-session="${s.session}" class="btn btn-sm btn-outline-danger delete-session">Delete</button>`;
                            return `<tr>
                                <td><strong>${s.session}</strong></td>
                                <td>${status}</td>
                                <td>${qr} ${s.hasQr ? qrLink : ''}</td>
                                <td>${logoutBtn} ${deleteBtn}</td>
                            </tr>`;
                        }).join('');

                        attachSessionHandlers();
                    } catch (e) {
                        tbody.innerHTML = `<tr><td colspan="4">Failed to load sessions: ${e.message}</td></tr>`;
                    }
                }

                function attachSessionHandlers() {
                    document.querySelectorAll('.logout-session').forEach(btn => {
                        if (btn.dataset.attached) return; btn.dataset.attached = '1';
                        btn.addEventListener('click', async (e) => {
                            const session = e.currentTarget.getAttribute('data-session');
                            if (!confirm('Logout session ' + session + '?')) return;
                            try {
                                const r = await fetch(baseUrl + '/sessions/logout', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ session }) });
                                const txt = await r.text();
                                try { const j = JSON.parse(txt); alert(j.message || JSON.stringify(j)); } catch (err2) { alert('Response: ' + txt); }
                                loadSessions();
                            } catch (err) { alert('Logout failed: ' + err.message); }
                        });
                    });

                    document.querySelectorAll('.delete-session').forEach(btn => {
                        if (btn.dataset.attached) return; btn.dataset.attached = '1';
                        btn.addEventListener('click', async (e) => {
                            const session = e.currentTarget.getAttribute('data-session');
                            if (!confirm('Delete session ' + session + ' and remove auth data?')) return;
                            try {
                                const r = await fetch(baseUrl + '/sessions', { method: 'DELETE', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ session }) });
                                const txt = await r.text();
                                try { const j = JSON.parse(txt); alert(j.message || JSON.stringify(j)); } catch (err2) { alert('Response: ' + txt); }
                                loadSessions();
                            } catch (err) { alert('Delete failed: ' + err.message); }
                        });
                    });
                }

                document.getElementById('refresh-sessions').addEventListener('click', (e) => { e.preventDefault(); loadSessions(); });
                document.getElementById('create-session').addEventListener('click', async (e) => {
                    e.preventDefault();
                    const id = document.getElementById('new-session-id').value.trim();
                    if (!id) return alert('Enter a session id');
                    try {
                        const res = await fetch(baseUrl + '/sessions', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ session: id }) });
                        const txt = await res.text();
                        try { const json = JSON.parse(txt); alert(json.message || JSON.stringify(json)); } catch (err2) { alert('Response: ' + txt); }
                        loadSessions();
                    } catch (e) { alert('Failed to create session: ' + e.message); }
                });

                const autoCheckbox = document.getElementById('auto-refresh');
                const intervalDisplay = document.getElementById('refresh-interval');
                function startAutoRefresh() {
                    if (autoRefreshTimer) clearInterval(autoRefreshTimer);
                    autoRefreshTimer = setInterval(loadSessions, 5000);
                }
                function stopAutoRefresh() { if (autoRefreshTimer) clearInterval(autoRefreshTimer); autoRefreshTimer = null; }
                autoCheckbox.addEventListener('change', () => { if (autoCheckbox.checked) startAutoRefresh(); else stopAutoRefresh(); });

                // init
                loadSessions();
                if (autoCheckbox.checked) startAutoRefresh();
                window.addEventListener('beforeunload', () => { stopAutoRefresh(); });
            </script>
        </div>
    </div>
</div>
@endsection