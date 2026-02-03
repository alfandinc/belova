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
                        <div class="ml-3">
                            <div class="form-group mb-0">
                                <select id="status_filter" class="form-control form-control-sm">
                                    <option value="all">All</option>
                                    <option value="non verified" selected>Non Verified</option>
                                    <option value="verified">Verified</option>
                                </select>
                            </div>
                        </div>
                        <div class="ml-auto" style="max-width:420px;">
                            <div class="input-group">
                                <input type="text" id="verify_code" class="form-control" placeholder="Type peserta code to verify" aria-label="Verify code">
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
                                    <th>ID</th>
                                    <th>Code</th>
                                    <th>Nama Peserta</th>
                                    <th>No HP</th>
                                    <th>Email</th>
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
        var pesertaTable = $('#peserta-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route('running.data') }}',
                type: 'GET',
                data: function(d){
                    // send current status filter
                    d.status = $('#status_filter').val() || 'non verified';
                }
            },
                columns: [
                { data: 'id', name: 'id' },
                { data: 'unique_code', name: 'unique_code' },
                { data: 'nama_peserta', name: 'nama_peserta' },
                { data: 'no_hp', name: 'no_hp' },
                { data: 'email', name: 'email' },
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
                        return '<button class="btn btn-sm btn-outline-primary btn-generate" data-id="' + row.id + '"><i class="fas fa-print"></i> Generate</button>';
                    }
                }
            ],
            order: [[0, 'desc']],
            responsive: true
        });

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
        
        // open modal preview when Generate clicked
        $(document).on('click', '.btn-generate', function(e){
            e.preventDefault();
            var id = $(this).data('id');
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

        // download ticket as image
        $('#downloadTicketBtn').on('click', function(){
            var $page = $('#ticketModalBody').find('.ticket-page').first();
            if (!$page.length) return alert('No ticket to download');
            html2canvas($page[0], { scale: 2 }).then(function(canvas){
                var dataUrl = canvas.toDataURL('image/png');
                var link = document.createElement('a');
                link.href = dataUrl;
                link.download = 'ticket.png';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }).catch(function(err){
                console.error(err);
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
                                    var infoHtml = '<div style="text-align:left">'
                                        + '<p><strong>Code:</strong> ' + (p.unique_code || '') + '</p>'
                                        + '<p><strong>Nama:</strong> ' + (p.nama_peserta || '') + '</p>'
                                        + '<p><strong>Kategori:</strong> ' + (p.kategori || '') + '</p>';
                                    if (formatted) {
                                        infoHtml += '<p><strong>Peserta sudah terverifikasi pada:</strong> ' + formatted + '</p>';
                                    } else {
                                        infoHtml += '<p><strong>Peserta sudah terverifikasi.</strong></p>';
                                    }
                                    infoHtml += '</div>';

                                    Swal.fire({
                                        title: 'Kode sudah digunakan',
                                        html: infoHtml,
                                        icon: 'info',
                                        confirmButtonText: 'OK'
                                    });
                                    return;
                                }

                                var html = '<div style="text-align:left">'
                                    + '<p><strong>Code:</strong> ' + (p.unique_code || '') + '</p>'
                                    + '<p><strong>Nama:</strong> ' + (p.nama_peserta || '') + '</p>'
                                    + '<p><strong>Kategori:</strong> ' + (p.kategori || '') + '</p>'
                                    + '<p><strong>Status:</strong> ' + (p.status || '') + '</p>'
                                    + '</div>';

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
    });
</script>
@endpush


