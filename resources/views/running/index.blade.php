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
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fas fa-tools"></i> Options
                                </button>
                                <div class="dropdown-menu">
                                    <a href="#" class="dropdown-item" data-toggle="modal" data-target="#importModal"><i class="fas fa-file-upload mr-1"></i> Import Peserta</a>
                                    <a href="#" id="btnExportCsv" class="dropdown-item"><i class="fas fa-file-csv mr-1"></i> Export CSV (All)</a>
                                    <a href="#" id="btnExportExcel" class="dropdown-item"><i class="fas fa-file-excel mr-1"></i> Export Excel (All)</a>
                                </div>
                            </div>
                        </div>
                        <div class="ml-2">
                            <button id="btnSendSelected" class="btn btn-sm btn-outline-success"><i class="fas fa-paper-plane"></i> Send Selected</button>
                        </div>
                        <div class="ml-3">
                            <div class="form-group mb-0 d-flex">
                                <select id="status_filter" class="form-control form-control-sm mr-2">
                                    <option value="all">All</option>
                                    <option value="non verified" selected>Non Verified</option>
                                    <option value="verified">Verified</option>
                                </select>
                                <select id="email_sent_filter" class="form-control form-control-sm" title="Filter by email sent status">
                                    <option value="not_sent">Email Not Sent</option>
                                    <option value="sent">Email Sent</option>
                                    <option value="all" selected>All Emails</option>
                                </select>
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
                <!-- Message Template Modal -->
                <div class="modal fade" id="messageTemplateModal" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Email Message Template</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-7">
                                        <div class="mb-3">
                                            <strong>To:</strong>
                                            <div class="d-flex align-items-center mt-1">
                                                <div class="text-monospace flex-grow-1" id="mt_to_display"></div>
                                                <button id="mt_copy_email" type="button" class="btn btn-outline-primary btn-sm ml-2">Copy Email (Q)</button>
                                            </div>
                                            <input type="text" id="mt_email_raw" class="d-none" value="">
                                        </div>

                                        <div class="mb-3">
                                            <strong>Subject:</strong>
                                            <div class="d-flex mt-1">
                                                <input type="text" id="mt_subject" class="form-control" readonly>
                                                <button id="mt_copy_subject" type="button" class="btn btn-outline-primary btn-sm ml-2">Copy Subject (W)</button>
                                            </div>
                                        </div>

                                        <div class="mb-2">
                                            <strong>Body:</strong>
                                            <textarea id="mt_body" class="form-control mt-1" rows="12" readonly style="white-space:pre-wrap;word-break:break-word;"></textarea>
                                            <div class="mt-2 text-right">
                                                <button id="mt_copy_body" type="button" class="btn btn-primary btn-sm">Copy Body (E)</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="mb-2 text-center">
                                            <strong>Ticket image to attach:</strong>
                                        </div>
                                        <div class="text-center">
                                            <div class="d-inline-block" style="border:1px dashed #ddd;padding:6px;background:#fafafa;">
                                                <img id="mt_ticket_image" src="" alt="Ticket Image" class="img-fluid" style="max-height:480px;cursor:grab;">
                                            </div>
                                            <div class="mt-2 small text-muted">You can drag this image into your email composer.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" id="mt_mark_sent" class="btn btn-success">Check</button>
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
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
                    // send current status + email_sent filters
                    d.status = $('#status_filter').val() || 'non verified';
                    d.email_sent = $('#email_sent_filter').val() || 'all';
                }
            },
                columns: [
                { data: null, orderable: false, searchable: false, render: function(data, type, row){
                        return '<input type="checkbox" class="row-select" value="' + row.id + '">';
                    }
                },
                { data: 'unique_code', name: 'unique_code', render: function(data, type, row){
                        return data || '';
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
                            // Admin-only extra actions grouped under an Options dropdown
                            actions += '<div class="btn-group" role="group">'
                                + '<button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'
                                + '<i class="fas fa-ellipsis-h"></i> Options'
                                + '</button>'
                                + '<div class="dropdown-menu dropdown-menu-right">';

                            actions += '<a href="#" class="dropdown-item btn-open-wa" data-id="' + row.id + '" data-to="' + (row.email || '') + '"><i class="fas fa-envelope mr-1"></i> Message Template</a>';

                            var emailLabel = (row.email_sent && String(row.email_sent) !== '0') ? 'Email Sent: Yes' : 'Email Sent: No';
                            actions += '<a href="#" class="dropdown-item btn-toggle-email-sent" data-id="' + row.id + '"><i class="fas fa-envelope mr-1"></i> ' + emailLabel + '</a>';

                            actions += '</div></div>';
                        }

                        // Always show Verify button
                        actions += '<button class="btn btn-sm btn-outline-warning btn-verify" data-id="' + row.id + '"><i class="fas fa-check"></i> Verif</button>';
                        actions += '</div>';
                        return actions;
                    }
                }
            ],
            order: [[1, 'desc']],
            responsive: true
        });

        // WA sessions are now handled automatically by the bot; no manual selector

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
        // reload datatable when status or email-sent filter changes
        $('#status_filter, #email_sent_filter').on('change', function(){
            pesertaTable.ajax.reload();
        });

        // Helpers for message template modal copy buttons + keyboard shortcuts (Q/W/E when modal open)
        function mtCopyFromElement(id) {
            var el = document.getElementById(id);
            if (!el) return;
            var text = el.value || el.innerText || '';
            (async function(){
                try {
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        await navigator.clipboard.writeText(text);
                    } else {
                        var ta = document.createElement('textarea');
                        ta.style.position = 'fixed'; ta.style.left = '-9999px';
                        ta.value = text;
                        document.body.appendChild(ta);
                        ta.select();
                        document.execCommand('copy');
                        document.body.removeChild(ta);
                    }
                    // success is silent on purpose; user preferred no popup
                } catch (e) {
                    alert('Copy failed. Please select and copy manually.');
                }
            })();
        }

        $('#mt_copy_email').on('click', function(){ mtCopyFromElement('mt_email_raw'); });
        $('#mt_copy_subject').on('click', function(){ mtCopyFromElement('mt_subject'); });
        $('#mt_copy_body').on('click', function(){ mtCopyFromElement('mt_body'); });

        // Mark email as sent from inside the Message Template modal
        $('#mt_mark_sent').on('click', function(){
            if (!messageTemplatePesertaId) {
                $('#messageTemplateModal').modal('hide');
                return;
            }
            var $btn = $(this);
            $btn.prop('disabled', true).text('Saving...');
            $.ajax({
                url: '{{ url('/running') }}/' + encodeURIComponent(messageTemplatePesertaId) + '/mark-email-sent',
                method: 'POST',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: function(resp){
                    if (resp && resp.ok) {
                        $('#messageTemplateModal').modal('hide');
                        if (typeof pesertaTable !== 'undefined') {
                            pesertaTable.ajax.reload(null, false);
                        }
                    } else {
                        var msg = (resp && resp.message) ? resp.message : 'Failed to mark email as sent';
                        try { Swal.fire({ icon: 'error', title: 'Error', text: msg }); } catch(e) { alert(msg); }
                    }
                },
                error: function(xhr){
                    var text = 'Request failed';
                    if (xhr && xhr.responseJSON && xhr.responseJSON.message) text = xhr.responseJSON.message;
                    try { Swal.fire({ icon: 'error', title: 'Error', text: text }); } catch(e) { alert(text); }
                },
                complete: function(){
                    $btn.prop('disabled', false).text('Check');
                }
            });
        });

        $(document).on('keydown', function(ev){
            if (!$('#messageTemplateModal').hasClass('show')) return;
            if (ev.ctrlKey || ev.altKey || ev.metaKey) return;
            var key = (ev.key || '').toLowerCase();
            if (key === 'q') {
                ev.preventDefault();
                mtCopyFromElement('mt_email_raw');
            } else if (key === 'w') {
                ev.preventDefault();
                mtCopyFromElement('mt_subject');
            } else if (key === 'e') {
                ev.preventDefault();
                mtCopyFromElement('mt_body');
            }
        });

        // Export CSV (All): always export entire dataset regardless of current table page
        $('#btnExportCsv').on('click', function(e){
            e.preventDefault();
            var url = '{{ route('running.export_csv') }}' + '?status=all&export_all=1';
            window.location = url;
        });

        // Export Excel (All): same data, Excel-friendly headers/extension
        $('#btnExportExcel').on('click', function(e){
            e.preventDefault();
            var url = '{{ route('running.export_csv') }}' + '?status=all&export_all=1&as=excel';
            window.location = url;
        });

        // Toggle email_sent status
        $('#peserta-table').on('click', '.btn-toggle-email-sent', function(e){
            e.preventDefault();
            var id = $(this).data('id');
            if (!id) return;
            var $btn = $(this);
            $btn.prop('disabled', true).text('Updating...');
            $.ajax({
                url: '{{ url('/running') }}/' + encodeURIComponent(id) + '/toggle-email-sent',
                method: 'POST',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: function(resp){
                    if (resp && resp.ok) {
                        pesertaTable.ajax.reload(null, false);
                    } else {
                        var msg = (resp && resp.message) ? resp.message : 'Failed to update email status';
                        try { Swal.fire({ icon: 'error', title: 'Error', text: msg }); } catch(e) { alert(msg); }
                    }
                },
                error: function(xhr){
                    var text = 'Request failed';
                    if (xhr && xhr.responseJSON && xhr.responseJSON.message) text = xhr.responseJSON.message;
                    try { Swal.fire({ icon: 'error', title: 'Error', text: text }); } catch(e) { alert(text); }
                },
                complete: function(){
                    $btn.prop('disabled', false).html('<i class="fas fa-envelope"></i>');
                }
            });
        });
        
        // open modal preview when Generate clicked
        var currentTicketPesertaId = null;
        var messageTemplatePesertaId = null; // tracks peserta for the Email Message Template modal
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
                                        data: { peserta_id: id, to: to, image_path: resp.image_path, client_id: null, _token: $('meta[name="csrf-token"]').attr('content') },
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

        // message template button: generate ticket image, upload, then open an email-friendly template preview
        $(document).on('click', '.btn-open-wa', function(e){
            e.preventDefault();
            var id = $(this).data('id');
            var to = $(this).data('to') || '';
            if (!id) return;
            messageTemplatePesertaId = id;
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
                    if (!el) { $off.remove(); $btn.prop('disabled', false).html('<i class="fas fa-envelope"></i> Message Template'); return alert('Failed to prepare ticket'); }
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
                                        + 'Sampai jumpa di Belova Premiere Run 2 Wellness tanggal 15 Februari 2026 nanti! ' + EMOJI.runner + EMOJI.sparkles + '\n\n';
                                    // replace name
                                    // Extract peserta name from the ticket fragment; fallback to trimmed text
                                    var name = '';
                                    try {
                                        name = $off.find('.ticket-identity').find('div').first().find('span').text() || '';
                                    } catch(e) { name = ''; }
                                    var messageText = template.replace('{peserta_name}', (name || '').toString().trim());
                                    // do NOT append image/waiver links directly; the ticket image will be opened separately

                                    // subject line for email
                                    var subject = 'Belova Premiere Run 2 Wellness - Registration & Racepack Information';

                                    // copy prepared message to clipboard, then show in-page message template modal
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
                                            try { Swal.fire({ icon: 'success', title: 'Copied', text: 'Message copied to clipboard. Paste it into your email.', timer: 1800, showConfirmButton: false }); } catch(e) {}
                                        } catch (e) {
                                            try { Swal.fire({ icon: 'warning', title: 'Copy failed', text: 'Could not copy message to clipboard. The template will still open so you can copy manually.', timer: 2500, showConfirmButton: false }); } catch(e2) {}
                                        }

                                        // Populate and show the message template modal
                                        var email = String(to || '').trim();
                                        var toDisplay = '';
                                        if (name && name.toString().trim()) {
                                            toDisplay = name.toString().trim();
                                            if (email) {
                                                toDisplay += ' <' + email + '>';
                                            }
                                        } else if (email) {
                                            toDisplay = email;
                                        }

                                        $('#mt_to_display').text(toDisplay);
                                        $('#mt_email_raw').val(email);
                                        $('#mt_subject').val(subject);
                                        $('#mt_body').val(messageText);
                                        if (publicUrl) {
                                            $('#mt_ticket_image').attr('src', publicUrl).show();
                                        } else {
                                            $('#mt_ticket_image').attr('src', '').hide();
                                        }
                                        $('#messageTemplateModal').modal('show');

                                        // Try a synchronous copy first so clipboard has the body immediately
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

                                        // Attempt async clipboard write as well (best-effort) and show a toast
                                        if (!didCopySync && navigator.clipboard && navigator.clipboard.writeText) {
                                            navigator.clipboard.writeText(messageText).then(function(){
                                                try { Swal.fire({ icon: 'success', title: 'Copied', text: 'Message copied to clipboard. Paste it into your email.', timer: 1800, showConfirmButton: false }); } catch(e) {}
                                            }).catch(function(){
                                                try { Swal.fire({ icon: 'warning', title: 'Copy failed', text: 'Could not copy message to clipboard. Please copy manually from the preview page.', timer: 2500, showConfirmButton: false }); } catch(e) {}
                                            });
                                        } else if (didCopySync) {
                                            try { Swal.fire({ icon: 'success', title: 'Copied', text: 'Message copied to clipboard. Paste it into your email.', timer: 1200, showConfirmButton: false }); } catch(e) {}
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
                            complete: function(){ $off.remove(); $btn.prop('disabled', false).html('<i class="fas fa-envelope"></i> Message Template'); }
                        });
                    }).catch(function(err){ console.error(err); $off.remove(); $btn.prop('disabled', false).html('<i class="fas fa-envelope"></i> Message Template'); try { Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to render ticket image' }); } catch(e) { alert('Failed to render ticket image'); } });
                }, 600);
            }).fail(function(){ $btn.prop('disabled', false).html('<i class="fas fa-envelope"></i> Message Template'); try { Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to load ticket preview' }); } catch(e) { alert('Failed to load ticket preview'); } });
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
                                                    data: { peserta_id: id, image_path: resp.image_path, client_id: null, _token: $('meta[name="csrf-token"]').attr('content') },
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


