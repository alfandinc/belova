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
                    <div class="d-flex justify-content-start mb-3">
                        <div class="mr-2">
                            <a href="#" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#importModal"><i class="fas fa-file-upload"></i> Import Peserta</a>
                        </div>
                        <div>
                            <a href="#" class="btn btn-sm btn-outline-success" data-toggle="modal" data-target="#verifyModal"><i class="fas fa-check-circle"></i> Verif Peserta</a>
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

<!-- Verify Modal -->
<div class="modal fade" id="verifyModal" tabindex="-1" role="dialog" aria-labelledby="verifyModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="verifyForm" method="POST" action="{{ route('running.verify') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="verifyModalLabel">Verify Peserta</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="verify_code">Enter Code</label>
                        <input type="text" name="code" id="verify_code" class="form-control" placeholder="Enter peserta code" required>
                        <small class="form-text text-muted">Type the unique code shown in the table to verify a peserta.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success">Verify</button>
                </div>
            </div>
        </form>
    </div>
</div>
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

                // Verify form submission via AJAX
                $('#verifyForm').on('submit', function(e){
                    e.preventDefault();
                    var form = this;
                    var $submit = $(form).find('button[type=submit]');
                    $submit.prop('disabled', true).text('Verifying...');

                    $.ajax({
                        url: $(form).attr('action'),
                        method: 'POST',
                        data: $(form).serialize(),
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                        success: function(resp){
                            var msg = (resp && resp.message) ? resp.message : 'Verification completed.';
                            $('#verifyModal').modal('hide');
                            $(form).trigger('reset');
                            try {
                                Swal.fire({ icon: 'success', title: 'Verified', text: msg, timer: 2000, showConfirmButton: false });
                            } catch(e) { alert(msg); }
                            pesertaTable.ajax.reload(null, false);
                        },
                        error: function(xhr){
                            var text = 'Verification failed.';
                            if (xhr && xhr.responseJSON && xhr.responseJSON.message) text = xhr.responseJSON.message;
                            else if (xhr && xhr.responseText) text = xhr.responseText;
                            try {
                                Swal.fire({ icon: 'error', title: 'Verify Error', text: text });
                            } catch(e) { alert(text); }
                        },
                        complete: function(){
                            $submit.prop('disabled', false).text('Verify');
                        }
                    });
                });
    });
</script>
@endpush


