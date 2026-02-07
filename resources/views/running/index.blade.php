@extends('layouts.erm.app')

@section('title', 'Running')

@section('navbar')
    @include('layouts.erm.navbar-ngaji')
@endsection

@section('content')
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h3>Running - Index</h3>
            <p class="text-muted">This is the Running section index page. More features will be added here.</p>
        </div>
    </div>

    <div class="row mt-3 justify-content-center">
        <div class="col-12 col-lg-10">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="mr-2">
                            <a href="#" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#importModal"><i class="fas fa-file-upload"></i> Import Peserta</a>
                        </div>
                        <div class="ml-2">
                            <a href="#" id="btnExportCsv" class="btn btn-sm btn-outline-secondary"><i class="fas fa-file-csv"></i> Export CSV (All)</a>
                        </div>
                        <div class="ml-2">
                            <button id="btnSendSelected" class="btn btn-sm btn-outline-success"><i class="fas fa-paper-plane"></i> Send Selected</button>
                        </div>
                        <div class="ml-3">
                            <div class="form-group mb-0">
                                <div class="d-flex">
                                    <select id="status_filter" class="form-control form-control-sm mr-2">
                                        <option value="all">All</option>
                                        <option value="non verified" selected>Non Verified</option>
                                        <option value="verified">Verified</option>
                                    </select>
                                    <select id="sent_filter" class="form-control form-control-sm">
                                        <option value="not_sent" selected>Not Sent</option>
                                        <option value="all">All</option>
                                        <option value="sent">Sent</option>
                                    </select>
                                    <select id="wa_session_select" class="form-control form-control-sm ml-2" title="Select WA session to use">
                                        <option value="">(Auto)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="ml-auto" style="max-width:420px;">
                            <div class="input-group">
                                <input type="text" id="verify_code" class="form-control" placeholder="Type peserta code to verify" aria-label="Verify code" autofocus>
                                <div class="input-group-append">
                                    <span class="input-group-text">Code</span>
                                </div>
                            </div>
                            <small class="form-text text-muted">Type the unique code shown in the table; a confirmation will appear if matched.</small>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered" id="peserta-table" style="width:100%">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="select-all"></th>
                                    <th>Code</th>
                                    <th>Identitas peserta</th>
                                    <th>Ukuran Kaos</th>
                                    <th>Notes</th>
                                    <th>Kategori</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    
                    <!-- Ticket Preview Modal -->
                    <div class="modal fade" id="ticketModal" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Ticket Preview</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body text-center" id="ticketModalBody">
                                    <!-- fragment loaded here -->
                                </div>
                                <div class="modal-footer">
                                    <button type="button" id="downloadTicketBtn" class="btn btn-primary">Download Image</button>
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1" role="dialog" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="POST" action="{{ route('running.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importModalLabel">Import Peserta (CSV)</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="csv_file">CSV File</label>
                        <input type="file" name="csv_file" id="csv_file" accept=".csv" class="form-control-file" required>
                        <small class="form-text text-muted">CSV columns: nama_peserta (or nama), kategori (header optional). Uploaded CSV should contain only these two columns; status will be set to 'non verified' automatically.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Verify input moved into page (no modal) -->
@endsection

@push('scripts')
<script>
    // load barcode & html2canvas libs for modal preview + download
</script>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
    $(function(){
        // autofocus verify code input on page load so keyboard input goes there
        try { setTimeout(function(){ var $vc = $('#verify_code'); if ($vc && $vc.length) { $vc.focus(); $vc.select && $vc.select(); } }, 150); } catch(e) {}

        // expose whether the current user is an Admin so we can show/hide privileged buttons
        var isAdmin = @json(auth()->user()->hasRole('Admin'));
        var pesertaTable = $('#peserta-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route('running.data') }}',
                type: 'GET',
                data: function(d){
                    // send current status filter
                    d.status = $('#status_filter').val() || 'non verified';
                    d.sent = $('#sent_filter').val() || 'not_sent';
                }
            },
                columns: [
                { data: null, orderable: false, searchable: false, render: function(data, type, row){
                        return '<input type="checkbox" class="row-select" value="' + row.id + '">';
                    }
                },
                { data: 'unique_code', name: 'unique_code', render: function(data, type, row){
                        var out = data || '';
                        try {
                            if (row.sent_logs_count && parseInt(row.sent_logs_count) > 0) {
                                out = out + ' <i class="fas fa-check-circle text-success" title="Message sent"></i>';
                            }
                        } catch(e) {}
                        return out;
                    }
                },
                { data: 'nama_peserta', name: 'nama_peserta', render: function(data, type, row){
                        var name = data || '';
                        var phone = row.no_hp ? '<div class="text-muted small">' + row.no_hp + '</div>' : '';
                        var email = row.email ? '<div class="text-muted small">' + row.email + '</div>' : '';
                        return '<div><strong>' + name + '</strong>' + phone + email + '</div>';
                    }
                },
                { data: 'ukuran_kaos', name: 'ukuran_kaos' },
                { data: 'notes', name: 'notes' },
                { data: 'kategori', name: 'kategori' },
                { data: 'status', name: 'status', render: function(data, type, row){
                        var s = (data || '').toString();
                        var label = s.split(' ').map(function(w){ return w.charAt(0).toUpperCase()+w.slice(1); }).join(' ');
                        var lower = s.toLowerCase().trim();
                        var cls = 'secondary';
                        if (lower === 'verified') {
                            cls = 'success';
                        } else if (lower === 'non verified' || lower === 'non-verified' || lower.indexOf('non') !== -1) {
                            cls = 'danger';
                        }
                        var badge = '<span class="badge badge-' + cls + '">' + label + '</span>';

                        // show verified_at under the badge if present
                        var verified = row && row.verified_at ? row.verified_at : null;
                        if (verified) {
                            var dt = new Date(verified);
                            if (!isNaN(dt.getTime())) {
                                var day = dt.getDate();
                                var month = dt.getMonth();
                                var year = dt.getFullYear();
                                var hour = dt.getHours();
                                var minute = dt.getMinutes();
                                var months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                                var mm = months[month] || '';
                                var minStr = (minute < 10) ? '0' + minute : minute;
                                var formatted = day + ' ' + mm + ' ' + year + ' ' + hour + '.' + minStr;
                                return badge + '<div class="text-muted small mt-1">' + formatted + '</div>';
                            }
                        }
                        return badge;
                    }
                },
                        { data: null, orderable: false, searchable: false, render: function(data, type, row){
                                var actions = '<div class="btn-group" role="group">';
                                if (isAdmin) {
                                    // Hidden: Generate and Send removed from actions; keep Open WA and Mark Sent
                                    actions += '<button class="btn btn-sm btn-outline-info btn-open-wa" data-id="' + row.id + '" data-to="' + (row.no_hp || '') + '"><i class="fab fa-whatsapp"></i> Open WA</button>';
                                    actions += '<button class="btn btn-sm btn-outline-success btn-mark-sent" data-id="' + row.id + '"><i class="fas fa-check-circle"></i> Mark Sent</button>';
                                }
                                actions += '<button class="btn btn-sm btn-outline-warning btn-verify" data-id="' + row.id + '"><i class="fas fa-check"></i> Verif</button>';
                                actions += '</div>';
                                return actions;
                            }
                        }
            ],
            order: [[1, 'desc']],
            responsive: true
        });

        // load available WA sessions from local bot and refresh periodically
        async function loadWaSessions() {
            var $sel = $('#wa_session_select');
            var prev = $sel.val();
            $sel.prop('disabled', true);
            try {
                const resp = await fetch('http://localhost:3000/sessions');
                if (!resp.ok) throw new Error('Bad response ' + resp.status);
                const list = await resp.json();

                // build HTML once so we can compare and avoid rewriting when identical
                var html = '';
                html += '<option value="">(Auto)</option>';
                list.forEach(function(s){
                    var label = s.id + ' (' + (s.status || 'unknown') + ')';
                    html += '<option value="' + (s.id || '') + '">' + label + '</option>';
                });

                // only replace options if changed to avoid clearing user's selection
                if ($sel.data('lastHtml') !== html) {
                    $sel.html(html);
                    $sel.data('lastHtml', html);
                    // try to restore previous selection if still available
                    if (prev && $sel.find('option[value="' + prev + '"]').length) {
                        $sel.val(prev);
                    } else {
                        $sel.val('');
                    }
                } else {
                    // unchanged — keep current value (or previous)
                    if (prev) $sel.val(prev);
                }
            } catch (e) {
                // ignore — bot may be down
                if (!$sel.data('lastHtml')) {
                    $sel.empty();
                    $sel.append($('<option>').attr('value','').text('(Auto)'));
                    $sel.data('lastHtml', $sel.html());
                }
                // restore previous if possible
                if (prev) $sel.val(prev);
            } finally {
                $sel.prop('disabled', false);
            }
        }
        // initial load and periodic refresh
        loadWaSessions();
        setInterval(loadWaSessions, 10000);

        // hide privileged bulk-send if not admin
        try { if (!isAdmin) { $('#btnSendSelected').hide(); } } catch(e) {}

        // AJAX import submission
        $('#importModal form').on('submit', function(e){
            e.preventDefault();
            var form = this;
            var fd = new FormData(form);

            // disable submit button
            var $btn = $(form).find('button[type=submit]');
            $btn.prop('disabled', true).text('Uploading...');

            $.ajax({
                url: $(form).attr('action'),
                method: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function(resp){
                    var msg = (resp && resp.message) ? resp.message : 'Import completed.';
                    $('#importModal').modal('hide');
                    // reset file input
                    $(form).trigger('reset');
                    // show toast
                    try {
                        Swal.fire({ icon: 'success', title: 'Import Success', text: msg, timer: 2500, showConfirmButton: false });
                    } catch (e) {
                        alert(msg);
                    }
                    // reload datatable without resetting paging
                    pesertaTable.ajax.reload(null, false);
                },
                error: function(xhr){
                    var text = 'Import failed.';
                    if (xhr && xhr.responseJSON && xhr.responseJSON.message) text = xhr.responseJSON.message;
                    else if (xhr && xhr.responseText) text = xhr.responseText;
                    try {
                        Swal.fire({ icon: 'error', title: 'Import Error', text: text });
                    } catch (e) {
                        alert(text);
                    }
                },
                complete: function(){
                    $btn.prop('disabled', false).text('Upload');
                }
            });
        });
        // reload datatable when status filter changes
        $('#status_filter').on('change', function(){
            pesertaTable.ajax.reload();
        });

        $('#sent_filter').on('change', function(){
            pesertaTable.ajax.reload();
        });

        // Export CSV (All): always export entire dataset regardless of current table page
        $('#btnExportCsv').on('click', function(e){
            e.preventDefault();
            var url = '{{ route('running.export_csv') }}' + '?status=all&export_all=1';
            window.location = url;
        });
        
        // open modal preview when Generate clicked
        var currentTicketPesertaId = null;
        $(document).on('click', '.btn-generate', function(e){
            e.preventDefault();
            var id = $(this).data('id');
            currentTicketPesertaId = id;
            var url = '{{ route('running.ticket.html', ['id' => '__id__']) }}'.replace('__id__', id);
            $('#ticketModalBody').html('<div class="text-center">Loading preview&hellip;</div>');
            $('#ticketModal').modal('show');
            $.get(url).done(function(html){
                $('#ticketModalBody').html(html);
                // render barcode inside modal (smaller margin and slightly smaller height)
                var code = $('#modal-unique-code').text().trim();
                try {
                    JsBarcode('#modal-barcode', code, { format: 'CODE128', displayValue: false, width: 2.5, height: 100, margin: 2 });
                } catch(e) { console.error(e); }
            }).fail(function(){
                $('#ticketModalBody').html('<div class="text-danger">Failed to load preview.</div>');
            });
        });

        // Verification modal handling
        // append modal HTML to page
        var verifyModalHtml = '\n<div class="modal fade" id="verifyModal" tabindex="-1" role="dialog" aria-hidden="true">'
            + '<div class="modal-dialog modal-sm modal-dialog-centered" role="document">'
            + '<div class="modal-content">'
            + '<div class="modal-header"><h5 class="modal-title">Verify Peserta</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>'
            + '<div class="modal-body">'
            + '<input type="hidden" id="verify_peserta_id" value="">'
            + '<div class="form-group"><label for="verifyNotes">Notes (optional)</label><textarea id="verifyNotes" class="form-control" rows="4" placeholder="Enter notes..."></textarea></div>'
            + '</div>'
            + '<div class="modal-footer">'
            + '<button type="button" id="confirmVerifyBtn" class="btn btn-primary">OK, Verify</button>'
            + '<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>'
            + '</div></div></div></div>';
        $('body').append(verifyModalHtml);

        // open verify modal
        $(document).on('click', '.btn-verify', function(e){
            e.preventDefault();
            var id = $(this).data('id');
            $('#verify_peserta_id').val(id);
            $('#verifyNotes').val('');
            $('#verifyModal').modal('show');
        });

        // submit verify
        $(document).on('click', '#confirmVerifyBtn', function(e){
            e.preventDefault();
            var id = $('#verify_peserta_id').val();
            var notes = $('#verifyNotes').val() || '';
            var $btn = $(this);
            $btn.prop('disabled', true).text('Saving...');
            $.ajax({
                url: '{{ url('/running') }}/' + encodeURIComponent(id) + '/verify-with-notes',
                method: 'POST',
                data: { notes: notes, _token: $('meta[name="csrf-token"]').attr('content') },
                success: function(resp){
                    if (resp && resp.ok) {
                        try { Swal.fire({ icon: 'success', title: 'Verified', text: resp.message || 'Peserta verified', timer: 1500, showConfirmButton: false }); } catch(e) {}
                        $('#verifyModal').modal('hide');
                        pesertaTable.ajax.reload(null, false);
                    } else {
                        var msg = (resp && resp.message) ? resp.message : 'Failed to verify';
                        try { Swal.fire({ icon: 'error', title: 'Error', text: msg }); } catch(e) { alert(msg); }
                    }
                },
                error: function(xhr){
                    var text = 'Request failed';
                    if (xhr && xhr.responseJSON && xhr.responseJSON.message) text = xhr.responseJSON.message;
                    try { Swal.fire({ icon: 'error', title: 'Error', text: text }); } catch(e) { alert(text); }
                },
                complete: function(){ $btn.prop('disabled', false).text('OK, Verify'); }
            });
        });

        // download ticket as image and store on server for sending
        $('#downloadTicketBtn').on('click', function(){
            var $page = $('#ticketModalBody').find('.ticket-page').first();
            if (!$page.length) return alert('No ticket to download');
            var $btn = $(this);
            $btn.prop('disabled', true).text('Generating...');
            html2canvas($page[0], { scale: 2 }).then(function(canvas){
                var dataUrl = canvas.toDataURL('image/png');
                // trigger download for user
                var link = document.createElement('a');
                link.href = dataUrl;
                link.download = 'ticket.png';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                // also upload to server and attach to pending scheduled messages
                if (currentTicketPesertaId) {
                    $.ajax({
                        url: '{{ route('running.store_ticket_image') }}',
                        method: 'POST',
                        data: { peserta_id: currentTicketPesertaId, image_data: dataUrl, _token: $('meta[name="csrf-token"]').attr('content') },
                        success: function(resp){
                            if (resp && resp.ok) {
                                try { Swal.fire({ icon: 'success', title: 'Saved', text: 'Ticket image saved and attached to queued messages.', timer: 1500, showConfirmButton: false }); } catch(e) {}
                                pesertaTable.ajax.reload(null, false);
                            } else {
                                var msg = (resp && resp.message) ? resp.message : 'Failed to save image';
                                try { Swal.fire({ icon: 'error', title: 'Save Error', text: msg }); } catch(e) { alert(msg); }
                            }
                        },
                        error: function(xhr){
                            var text = 'Upload failed';
                            if (xhr && xhr.responseJSON && xhr.responseJSON.message) text = xhr.responseJSON.message;
                            try { Swal.fire({ icon: 'error', title: 'Save Error', text: text }); } catch(e) { alert(text); }
                        },
                        complete: function(){
                            $btn.prop('disabled', false).html('Download Image');
                        }
                    });
                } else {
                    $btn.prop('disabled', false).html('Download Image');
                }
            }).catch(function(err){
                console.error(err);
                $btn.prop('disabled', false).html('Download Image');
                alert('Failed to generate image');
            });
        });
        // Auto-find code as user types and show preview confirmation
        (function(){
            var timer = null;
            var $input = $('#verify_code');

            $input.on('input', function(){
                var val = $(this).val().trim();
                clearTimeout(timer);
                if (!val) return; // nothing to do
                // debounce 500ms
                timer = setTimeout(function(){
                    // call find endpoint
                    $.ajax({
                        url: '{{ route('running.find') }}',
                        method: 'GET',
                        data: { code: val },
                        success: function(res){
                            if (res && res.ok && res.data) {
                                var p = res.data;
                                // if already verified, show info with timestamp
                                if (p.status && p.status.toString().toLowerCase().trim() === 'verified') {
                                    var formatted = '';
                                    if (p.verified_at) {
                                        var dtv = new Date(p.verified_at);
                                        if (!isNaN(dtv.getTime())) {
                                            var dayv = dtv.getDate();
                                            var monthv = dtv.getMonth();
                                            var yearv = dtv.getFullYear();
                                            var hourv = dtv.getHours();
                                            var minutev = dtv.getMinutes();
                                            var monthsv = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                                            var mmv = monthsv[monthv] || '';
                                            var minStrv = (minutev < 10) ? '0' + minutev : minutev;
                                            formatted = dayv + ' ' + mmv + ' ' + yearv + ' ' + hourv + '.' + minStrv;
                                        }
                                    }
                                    var infoHtml = '<table class="table table-sm table-borderless mb-0">'
                                        + '<tr><th style="width:35%;text-align:left">Code</th><td style="text-align:left">' + (p.unique_code || '') + '</td></tr>'
                                        + '<tr><th style="text-align:left">Nama</th><td style="text-align:left">' + (p.nama_peserta || '') + '</td></tr>'
                                        + '<tr><th style="text-align:left">No. HP</th><td style="text-align:left">' + (p.no_hp || '-') + '</td></tr>'
                                        + '<tr><th style="text-align:left">Email</th><td style="text-align:left">' + (p.email || '-') + '</td></tr>'
                                        + '<tr><th style="text-align:left">Ukuran Kaos</th><td style="text-align:left">' + (p.ukuran_kaos || '-') + '</td></tr>'
                                        + '<tr><th style="text-align:left">Kategori</th><td style="text-align:left">' + (p.kategori || '') + '</td></tr>';
                                    if (formatted) {
                                        infoHtml += '<tr><th style="text-align:left">Verified At</th><td style="text-align:left">' + formatted + '</td></tr>';
                                    } else {
                                        infoHtml += '<tr><th style="text-align:left">Verified</th><td style="text-align:left">Yes</td></tr>';
                                    }
                                    infoHtml += '</table>';

                                    Swal.fire({
                                        title: 'Kode sudah digunakan',
                                        html: infoHtml,
                                        icon: 'info',
                                        confirmButtonText: 'OK'
                                    });
                                    return;
                                }

                                var html = '<table class="table table-sm table-borderless mb-0">'
                                    + '<tr><th style="width:35%;text-align:left">Code</th><td style="text-align:left">' + (p.unique_code || '') + '</td></tr>'
                                    + '<tr><th style="text-align:left">Nama</th><td style="text-align:left">' + (p.nama_peserta || '') + '</td></tr>'
                                    + '<tr><th style="text-align:left">No. HP</th><td style="text-align:left">' + (p.no_hp || '-') + '</td></tr>'
                                    + '<tr><th style="text-align:left">Email</th><td style="text-align:left">' + (p.email || '-') + '</td></tr>'
                                    + '<tr><th style="text-align:left">Ukuran Kaos</th><td style="text-align:left">' + (p.ukuran_kaos || '-') + '</td></tr>'
                                    + '<tr><th style="text-align:left">Kategori</th><td style="text-align:left">' + (p.kategori || '') + '</td></tr>'
                                    + '<tr><th style="text-align:left">Status</th><td style="text-align:left">' + (p.status || '') + '</td></tr>'
                                    + '</table>';

                                Swal.fire({
                                    title: 'Confirm Verification',
                                    html: html,
                                    icon: 'question',
                                    showCancelButton: true,
                                    confirmButtonText: 'OK, Verify',
                                    cancelButtonText: 'Cancel'
                                }).then(function(result){
                                    if (result.value) {
                                        // perform verification POST
                                        $.ajax({
                                            url: '{{ route('running.verify') }}',
                                            method: 'POST',
                                            data: { code: p.unique_code, _token: $('meta[name="csrf-token"]').attr('content') },
                                            success: function(resp2){
                                                var msg = (resp2 && resp2.message) ? resp2.message : 'Verified.';
                                                try { Swal.fire({ icon: 'success', title: 'Verified', text: msg, timer: 1800, showConfirmButton: false }); } catch(e) { alert(msg); }
                                                pesertaTable.ajax.reload(null, false);
                                                // clear inline input
                                                $('#verify_code').val('').blur();
                                            },
                                            error: function(xhr2){
                                                var text = 'Verification failed.';
                                                if (xhr2 && xhr2.responseJSON && xhr2.responseJSON.message) text = xhr2.responseJSON.message;
                                                try { Swal.fire({ icon: 'error', title: 'Verify Error', text: text }); } catch(e) { alert(text); }
                                            }
                                        });
                                    }
                                });
                            }
                        },
                        error: function(){
                            // code not found or server error; ignore silently (or you can show small feedback)
                        }
                    });
                }, 500);
            });
        })();

        // select all checkbox behavior
        $('#select-all').on('change', function(){
            var checked = $(this).is(':checked');
            $('#peserta-table').find('input.row-select').prop('checked', checked);
        });

        // single send button: generate image (offscreen), upload, then enqueue and send
        $(document).on('click', '.btn-send', function(e){
            e.preventDefault();
            var id = $(this).data('id');
            var to = $(this).data('to') || '';
            if (!id) return;
            var $btn = $(this);
            $btn.prop('disabled', true).text('Preparing...');

            // fetch ticket fragment HTML
            var url = '{{ route('running.ticket.html', ['id' => '__id__']) }}'.replace('__id__', id);
            $.get(url).done(function(html){
                // create offscreen container
                var $off = $('<div style="position:fixed;left:-9999px;top:0;" id="_ticket_offscreen"></div>');
                $('body').append($off);
                $off.html(html);
                // render barcode inside offscreen
                try {
                    var code = $off.find('#modal-unique-code').text().trim();
                    JsBarcode($off.find('#modal-barcode')[0], code, { format: 'CODE128', displayValue: false, width: 2.5, height: 100, margin: 2 });
                } catch (e) { console.error('barcode render failed', e); }

                // ensure styles/images load, then capture
                setTimeout(function(){
                    var el = $off.find('.ticket-page')[0];
                    if (!el) {
                        $off.remove();
                        $btn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Send');
                        return alert('Failed to prepare ticket');
                    }
                    html2canvas(el, { scale: 2 }).then(function(canvas){
                        var dataUrl = canvas.toDataURL('image/png');
                        // upload to server
                        $.ajax({
                            url: '{{ route('running.store_ticket_image') }}',
                            method: 'POST',
                            data: { peserta_id: id, image_data: dataUrl, _token: $('meta[name="csrf-token"]').attr('content') },
                            success: function(resp){
                                if (resp && resp.ok) {
                                    // then enqueue scheduled send with returned image_path
                                    $.ajax({
                                        url: '{{ route('running.send_whatsapp') }}',
                                        method: 'POST',
                                        data: { peserta_id: id, to: to, image_path: resp.image_path, client_id: $('#wa_session_select').val() || null, _token: $('meta[name="csrf-token"]').attr('content') },
                                        success: function(r2){
                                            if (r2 && r2.ok) {
                                                try { Swal.fire({ icon: 'success', title: 'Queued', text: 'Ticket queued and will be sent shortly.', timer: 1500, showConfirmButton: false }); } catch(e) {}
                                                pesertaTable.ajax.reload(null, false);
                                            } else {
                                                var msg = (r2 && r2.message) ? r2.message : 'Failed to queue message';
                                                try { Swal.fire({ icon: 'error', title: 'Error', text: msg }); } catch(e) { alert(msg); }
                                            }
                                        },
                                        error: function(){ try { Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to enqueue message' }); } catch(e) { alert('Failed to enqueue message'); } }
                                    });
                                } else {
                                    var msg = (resp && resp.message) ? resp.message : 'Failed to save image';
                                    try { Swal.fire({ icon: 'error', title: 'Save Error', text: msg }); } catch(e) { alert(msg); }
                                }
                            },
                            error: function(){ try { Swal.fire({ icon: 'error', title: 'Save Error', text: 'Failed to upload image' }); } catch(e) { alert('Failed to upload image'); } },
                            complete: function(){
                                $off.remove();
                                $btn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Send');
                            }
                        });
                    }).catch(function(err){
                        console.error(err);
                        $off.remove();
                        $btn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Send');
                        try { Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to render ticket image' }); } catch(e) { alert('Failed to render ticket image'); }
                    });
                }, 600);
            }).fail(function(){
                $btn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Send');
                try { Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to load ticket preview' }); } catch(e) { alert('Failed to load ticket preview'); }
            });
        });

        // open WA (manual) button: generate image, upload, then open WhatsApp with prefilled message
        // Note: wa.me / web.whatsapp.com cannot auto-attach files; we provide public links to the image and waiver
        var defaultWaiverUrl = '{{ asset("img/templates/WAIVER-BELOVAPREMIERERUN.pdf") }}';
        $(document).on('click', '.btn-open-wa', function(e){
            e.preventDefault();
            var id = $(this).data('id');
            var to = $(this).data('to') || '';
            if (!id) return;
            var $btn = $(this);
            $btn.prop('disabled', true).text('Preparing...');

            // fetch ticket fragment
            var url = '{{ route('running.ticket.html', ['id' => '__id__']) }}'.replace('__id__', id);
            $.get(url).done(function(html){
                var $off = $('<div style="position:fixed;left:-9999px;top:0;" id="_ticket_offscreen_wa_' + id + '"></div>');
                $('body').append($off);
                $off.html(html);
                try {
                    var code = $off.find('#modal-unique-code').text().trim();
                    JsBarcode($off.find('#modal-barcode')[0], code, { format: 'CODE128', displayValue: false, width: 2.5, height: 100, margin: 2 });
                } catch (e) { console.error('barcode render failed', e); }

                setTimeout(function(){
                    var el = $off.find('.ticket-page')[0];
                    if (!el) { $off.remove(); $btn.prop('disabled', false).html('<i class="fab fa-whatsapp"></i> Open WA'); return alert('Failed to prepare ticket'); }
                    html2canvas(el, { scale: 2 }).then(function(canvas){
                        var dataUrl = canvas.toDataURL('image/png');
                        // upload to server to get a public URL
                        $.ajax({
                            url: '{{ route('running.store_ticket_image') }}',
                            method: 'POST',
                            data: { peserta_id: id, image_data: dataUrl, _token: $('meta[name="csrf-token"]').attr('content') },
                            success: function(resp){
                                if (resp && resp.ok) {
                                    var publicUrl = resp.public_url || '';
                                    var waiverUrl = defaultWaiverUrl || '';
                                    // build templated message (keep consistent with server template)
                                    // build emoji characters at runtime to avoid file-encoding issues
                                    var EMOJI = {
                                        wave: String.fromCodePoint(0x1F44B),
                                        starstruck: String.fromCodePoint(0x1F929),
                                        calendar: String.fromCodePoint(0x1F4C5),
                                        alarm: String.fromCodePoint(0x23F0),
                                        pin: String.fromCodePoint(0x1F4CD),
                                        runner: String.fromCodePoint(0x1F3C3),
                                        sparkles: String.fromCodePoint(0x2728)
                                    };

                                    var template = 'Halo {peserta_name} !' + EMOJI.wave + '\n\n'
                                        + 'Terimakasih banyak sudah melakukan pendaftaran di Belova Premiere Run 2 Wellness ' + EMOJI.starstruck + '\n'
                                        + 'Pengambilan racepack Belova Premiere Run 2 Wellness akan dilaksanakan pada :\n\n'
                                        + EMOJI.calendar + ' Jumat, 13 Februari 2026\n'
                                        + EMOJI.alarm + ' 10.00 – 15.00 WIB\ndan\n'
                                        + EMOJI.calendar + ' Sabtu, 14 Februari 2026\n'
                                        + EMOJI.alarm + ' 12.00 – 20.00 WIB\n\n'
                                        + EMOJI.pin + ' Lokasi : Klinik Utama Premiere Belova\nJl. Melon Raya 1 no. 27 Karangasem, Laweyan, Surakarta\n\n'
                                        + 'Saat pengambilan racepack, peserta wajib menunjukkan Registration Ticket serta menyerahkan formulir Waiver yang telah dicetak dan ditandatangani kepada panitia di lokasi pengambilan racepack.\n\n'
                                        + 'Sampai jumpa di Belova Premiere Run 2 Wellness tanggal 15 Februari 2026 nanti! ' + EMOJI.runner + EMOJI.sparkles;
                                    // replace name
                                    // Extract peserta name from the ticket fragment; fallback to trimmed text
                                    var name = '';
                                    try {
                                        name = $off.find('.ticket-identity').find('div').first().find('span').text() || '';
                                    } catch(e) { name = ''; }
                                    var messageText = template.replace('{peserta_name}', (name || '').toString().trim());
                                    // do NOT append image/waiver links to the prepared message
                                    // we open the generated ticket image in a separate tab for manual review/attach

                                    // always use wa.me link which works across platforms
                                    var plain = String(to).replace(/[^0-9]/g, '');
                                    var encoded = encodeURIComponent(messageText);
                                    var waLink = 'https://wa.me/' + plain + '?text=' + encoded;

                                    // copy prepared message to clipboard, open ticket image, then open chat (without text)
                                    (async function(){
                                        try {
                                            // attempt Clipboard API
                                            if (navigator.clipboard && navigator.clipboard.writeText) {
                                                await navigator.clipboard.writeText(messageText);
                                            } else {
                                                // fallback: textarea copy
                                                var ta = document.createElement('textarea');
                                                ta.style.position = 'fixed'; ta.style.left = '-9999px';
                                                ta.value = messageText;
                                                document.body.appendChild(ta);
                                                ta.select();
                                                document.execCommand('copy');
                                                document.body.removeChild(ta);
                                            }
                                            try { Swal.fire({ icon: 'success', title: 'Copied', text: 'Message copied to clipboard. Paste it into WhatsApp.', timer: 1800, showConfirmButton: false }); } catch(e) {}
                                        } catch (e) {
                                            try { Swal.fire({ icon: 'warning', title: 'Copy failed', text: 'Could not copy message to clipboard. The message will still be opened in WhatsApp (you may need to paste manually).', timer: 2500, showConfirmButton: false }); } catch(e2) {}
                                        }

                                        // prepare preview URL and plain phone
                                        var plainOnly = String(to).replace(/[^0-9]/g, '');
                                        var previewUrl = '{{ route('running.wa_preview') }}'
                                            + '?phone=' + encodeURIComponent(plainOnly)
                                            + '&message=' + encodeURIComponent(messageText)
                                            + (publicUrl ? ('&image=' + encodeURIComponent(publicUrl)) : '');

                                        // Try a synchronous copy first (textarea + execCommand) so subsequent window.open calls
                                        // remain considered user-initiated and avoid popup blocking.
                                        var didCopySync = false;
                                        try {
                                            var ta = document.createElement('textarea');
                                            ta.style.position = 'fixed'; ta.style.left = '-9999px'; ta.style.top = '0';
                                            ta.value = messageText;
                                            document.body.appendChild(ta);
                                            ta.focus(); ta.select();
                                            didCopySync = document.execCommand && document.execCommand('copy');
                                            document.body.removeChild(ta);
                                        } catch (e) {
                                            didCopySync = false;
                                        }

                                        // Open in desired order: wa.me (without text), preview, then ticket image last (so it appears on top)
                                        var waOnly = 'https://wa.me/' + plainOnly;
                                        window.open(waOnly, '_blank');
                                        // open preview second
                                        window.open(previewUrl, '_blank');
                                        // open ticket image last (use window features to encourage separate window)
                                        if (publicUrl) window.open(publicUrl, '_blank', 'noopener,noreferrer,width=900,height=1200');

                                        // Attempt async clipboard write as well (best-effort) and show a toast
                                        if (!didCopySync && navigator.clipboard && navigator.clipboard.writeText) {
                                            navigator.clipboard.writeText(messageText).then(function(){
                                                try { Swal.fire({ icon: 'success', title: 'Copied', text: 'Message copied to clipboard. Paste it into WhatsApp.', timer: 1800, showConfirmButton: false }); } catch(e) {}
                                            }).catch(function(){
                                                try { Swal.fire({ icon: 'warning', title: 'Copy failed', text: 'Could not copy message to clipboard. Please copy manually from the preview page.', timer: 2500, showConfirmButton: false }); } catch(e) {}
                                            });
                                        } else if (didCopySync) {
                                            try { Swal.fire({ icon: 'success', title: 'Copied', text: 'Message copied to clipboard. Paste it into WhatsApp.', timer: 1200, showConfirmButton: false }); } catch(e) {}
                                        } else {
                                            try { Swal.fire({ icon: 'info', title: 'Ready', text: 'Preview opened. Copy the message from the preview page.', timer: 1800, showConfirmButton: false }); } catch(e) {}
                                        }

                                        pesertaTable.ajax.reload(null, false);
                                    })();
                                } else {
                                    var msg = (resp && resp.message) ? resp.message : 'Failed to save image';
                                    try { Swal.fire({ icon: 'error', title: 'Save Error', text: msg }); } catch(e) { alert(msg); }
                                }
                            },
                            error: function(){ try { Swal.fire({ icon: 'error', title: 'Save Error', text: 'Failed to upload image' }); } catch(e) { alert('Failed to upload image'); } },
                            complete: function(){ $off.remove(); $btn.prop('disabled', false).html('<i class="fab fa-whatsapp"></i> Open WA'); }
                        });
                    }).catch(function(err){ console.error(err); $off.remove(); $btn.prop('disabled', false).html('<i class="fab fa-whatsapp"></i> Open WA'); try { Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to render ticket image' }); } catch(e) { alert('Failed to render ticket image'); } });
                }, 600);
            }).fail(function(){ $btn.prop('disabled', false).html('<i class="fab fa-whatsapp"></i> Open WA'); try { Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to load ticket preview' }); } catch(e) { alert('Failed to load ticket preview'); } });
        });

        // mark as sent button (manual)
        $(document).on('click', '.btn-mark-sent', function(e){
            e.preventDefault();
            var id = $(this).data('id');
            if (!id) return;
            var $btn = $(this);
            try {
                Swal.fire({
                    title: 'Mark as Sent?',
                    text: 'This will mark the peserta as having received the message (manual override).',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, mark sent'
                }).then(function(res){
                    if (!res || !res.value) return;
                    $btn.prop('disabled', true).text('Marking...');
                    $.ajax({
                        url: '{{ url('/running') }}/' + encodeURIComponent(id) + '/mark-sent',
                        method: 'POST',
                        data: { _token: $('meta[name="csrf-token"]').attr('content') },
                        success: function(resp){
                            if (resp && resp.ok) {
                                try { Swal.fire({ icon: 'success', title: 'Marked', text: resp.message || 'Peserta marked as sent', timer: 1500, showConfirmButton: false }); } catch(e) {}
                                pesertaTable.ajax.reload(null, false);
                            } else {
                                var msg = (resp && resp.message) ? resp.message : 'Failed to mark';
                                try { Swal.fire({ icon: 'error', title: 'Error', text: msg }); } catch(e) { alert(msg); }
                            }
                        },
                        error: function(xhr){
                            var text = 'Request failed.';
                            if (xhr && xhr.responseJSON && xhr.responseJSON.message) text = xhr.responseJSON.message;
                            try { Swal.fire({ icon: 'error', title: 'Error', text: text }); } catch(e) { alert(text); }
                        },
                        complete: function(){ $btn.prop('disabled', false).html('<i class="fas fa-check-circle"></i> Mark Sent'); }
                    });
                });
            } catch(e) { alert('Action failed'); }
        });

        // bulk send selected: generate image, upload, then enqueue per peserta sequentially
        $('#btnSendSelected').on('click', function(){
            var ids = [];
            $('#peserta-table').find('input.row-select:checked').each(function(){ ids.push(parseInt($(this).val())); });
            if (!ids.length) {
                try { Swal.fire({ icon: 'info', title: 'No selection', text: 'Please select at least one peserta.' }); } catch(e) { alert('Please select at least one peserta.'); }
                return;
            }
            var $btn = $(this);
            $btn.prop('disabled', true).text('Preparing...');

            // helper to process one peserta: fetch fragment, render barcode, capture, upload, enqueue
            function processOne(id) {
                return new Promise(function(resolve){
                    var url = '{{ route('running.ticket.html', ['id' => '__id__']) }}'.replace('__id__', id);
                    $.get(url).done(function(html){
                        var $off = $('<div style="position:fixed;left:-9999px;top:0;" id="_ticket_offscreen_' + id + '"></div>');
                        $('body').append($off);
                        $off.html(html);
                        try {
                            var code = $off.find('#modal-unique-code').text().trim();
                            JsBarcode($off.find('#modal-barcode')[0], code, { format: 'CODE128', displayValue: false, width: 2.5, height: 100, margin: 2 });
                        } catch (e) { console.error('barcode render failed', e); }

                        setTimeout(function(){
                            var el = $off.find('.ticket-page')[0];
                            if (!el) {
                                $off.remove();
                                return resolve({ ok: false, id: id, message: 'Failed to prepare ticket' });
                            }
                            html2canvas(el, { scale: 2 }).then(function(canvas){
                                var dataUrl = canvas.toDataURL('image/png');
                                // upload
                                $.ajax({
                                    url: '{{ route('running.store_ticket_image') }}',
                                    method: 'POST',
                                    data: { peserta_id: id, image_data: dataUrl, _token: $('meta[name="csrf-token"]').attr('content') },
                                    success: function(resp){
                                        if (resp && resp.ok) {
                                            // enqueue send with returned image_path
                                            $.ajax({
                                                url: '{{ route('running.send_whatsapp') }}',
                                                method: 'POST',
                                                    data: { peserta_id: id, image_path: resp.image_path, client_id: $('#wa_session_select').val() || null, _token: $('meta[name="csrf-token"]').attr('content') },
                                                success: function(r2){
                                                    $off.remove();
                                                    if (r2 && r2.ok) return resolve({ ok: true, id: id });
                                                    return resolve({ ok: false, id: id, message: (r2 && r2.message) ? r2.message : 'Failed to enqueue' });
                                                },
                                                error: function(){ $off.remove(); return resolve({ ok: false, id: id, message: 'Enqueue error' }); }
                                            });
                                        } else {
                                            $off.remove();
                                            return resolve({ ok: false, id: id, message: (resp && resp.message) ? resp.message : 'Failed to save image' });
                                        }
                                    },
                                    error: function(){ $off.remove(); return resolve({ ok: false, id: id, message: 'Upload failed' }); }
                                });
                            }).catch(function(err){
                                console.error(err);
                                $off.remove();
                                return resolve({ ok: false, id: id, message: 'Render failed' });
                            });
                        }, 600);
                    }).fail(function(){ return resolve({ ok: false, id: id, message: 'Failed to load ticket fragment' }); });
                });
            }

            // sequentially process all ids to avoid browser overload
            (async function(){
                var results = [];
                for (var i = 0; i < ids.length; i++) {
                    $btn.text('Processing ' + (i+1) + ' / ' + ids.length + '...');
                    try {
                        // small delay between items
                        await new Promise(r=>setTimeout(r, 250));
                        var res = await processOne(ids[i]);
                        results.push(res);
                    } catch(e) {
                        results.push({ ok: false, id: ids[i], message: 'Unexpected error' });
                    }
                }

                var successCount = results.filter(r=>r.ok).length;
                var failCount = results.length - successCount;
                var msg = 'Queued ' + successCount + ' messages.' + (failCount ? (' ' + failCount + ' failed.') : '');
                try { Swal.fire({ icon: (failCount? 'warning':'success'), title: 'Bulk Send Complete', text: msg, timer: 3000, showConfirmButton: false }); } catch(e) { alert(msg); }
                pesertaTable.ajax.reload(null, false);
                $btn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Send Selected');
            })();
        });
    });
</script>
@endpush


