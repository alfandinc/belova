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
                                    <th>Kategori</th>
                                    <th>Status</th>
                                    <th>Verified At</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
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
    $(function(){
        var pesertaTable = $('#peserta-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route('running.data') }}',
                type: 'GET'
            },
            columns: [
                { data: 'id', name: 'id' },
                { data: 'unique_code', name: 'unique_code' },
                { data: 'nama_peserta', name: 'nama_peserta' },
                { data: 'kategori', name: 'kategori' },
                { data: 'status', name: 'status' },
                { data: 'verified_at', name: 'verified_at' },
                { data: 'created_at', name: 'created_at' }
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


