@extends('layouts.erm.app')

@section('navbar')
    @include('layouts.satusehat.navbar')
@endsection

@section('title', 'SatuSehat - Klinik Configs')

@section('content')
    <div class="container-fluid">
        <div class="row mt-4">
            <div class="col-12">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Konfigurasi Klinik SatuSehat</h4>
                        <p class="card-text">Daftar konfigurasi base clinic untuk integrasi SatuSehat.</p>
                            <button id="btnAdd" class="btn btn-primary mb-3">Tambah Konfigurasi</button>
                        <div class="table-responsive">
                            <table id="clinicTable" class="table table-striped table-bordered" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Klinik</th>
                                        <th>Base URL</th>
                                        <th>Auth URL</th>
                                        <th>Organization ID</th>
                                        <th>Actions</th>
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

    <!-- Modal for create/edit -->
    <div class="modal fade" id="clinicModal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="clinicModalLabel">Tambah Konfigurasi</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          </div>
          <div class="modal-body">
            <form id="clinicForm">
                @csrf
                <input type="hidden" id="config_id" name="config_id" />
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="klinik_id">Klinik (opsional)</label>
                        <select name="klinik_id" id="klinik_id" class="form-control"></select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="organization_id">Organization ID</label>
                        <input type="text" name="organization_id" id="organization_id" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label for="base_url">Base URL</label>
                    <input type="text" name="base_url" id="base_url" class="form-control" value="https://api-satusehat.kemkes.go.id/fhir-r4/v1">
                </div>
                <div class="form-group">
                    <label for="auth_url">Auth URL</label>
                    <input type="text" name="auth_url" id="auth_url" class="form-control" value="https://api-satusehat.kemkes.go.id/oauth2/v1">
                </div>
                <div class="form-group">
                    <label for="consent_url">Consent URL</label>
                    <input type="text" name="consent_url" id="consent_url" class="form-control" value="https://api-satusehat.kemkes.go.id/consent/v1">
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="client_id">Client ID</label>
                        <input type="text" name="client_id" id="client_id" class="form-control">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="client_secret">Client Secret</label>
                        <input type="text" name="client_secret" id="client_secret" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label for="token">Token (JSON)</label>
                    <textarea name="token" id="token" class="form-control" rows="4"></textarea>
                </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            <button type="button" id="saveClinicBtn" class="btn btn-primary">Simpan</button>
          </div>
        </div>
      </div>
    </div>

    @push('scripts')
    <script>
    $(function(){
        // CSRF for AJAX
        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

        // initialize clinic select options (fetch clinics list)
        function loadKlinikOptions() {
            return $.get('{{ route('marketing.clinics') }}')
                .then(function(data){
                    // marketing.clinics returns array of clinics (see existing endpoint)
                    var select = $('#klinik_id');
                    select.empty();
                    select.append('<option value="">-- Pilih Klinik --</option>');
                    (data.data || data).forEach(function(k){
                        select.append('<option value="'+k.id+'">'+k.nama+'</option>');
                    });
                }).catch(function(){
                    // ignore
                });
        }

        var table = $('#clinicTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{!! route('satusehat.clinics.data') !!}',
            columns: [
                { data: 'id', name: 'id' },
                { data: 'klinik', name: 'klinik' },
                { data: 'base_url', name: 'base_url' },
                { data: 'auth_url', name: 'auth_url' },
                { data: 'organization_id', name: 'organization_id' },
                { data: 'actions', name: 'actions', orderable: false, searchable: false },
            ],
            order: [[0,'desc']]
        });

        $('#btnAdd').on('click', function(){
            $('#clinicForm')[0].reset();
            $('#config_id').val('');
            $('#clinicModalLabel').text('Tambah Konfigurasi');
            loadKlinikOptions();
            $('#clinicModal').modal('show');
        });

        // Edit
        $('#clinicTable').on('click', '.btn-edit', function(){
            var id = $(this).data('id');
            $.get('/satusehat/clinics/' + id + '/edit', function(res){
                $('#clinicForm')[0].reset();
                $('#config_id').val(res.id);
                $('#klinik_id').val(res.klinik_id);
                $('#base_url').val(res.base_url);
                $('#auth_url').val(res.auth_url);
                $('#consent_url').val(res.consent_url);
                $('#client_id').val(res.client_id);
                $('#client_secret').val(res.client_secret);
                $('#organization_id').val(res.organization_id);
                $('#token').val(res.token);
                loadKlinikOptions().then(function(){ $('#klinik_id').val(res.klinik_id); });
                $('#clinicModalLabel').text('Edit Konfigurasi');
                $('#clinicModal').modal('show');
            });
        });

        // Save (store/update)
        $('#saveClinicBtn').on('click', function(){
            var id = $('#config_id').val();
            var url = id ? '/satusehat/clinics/' + id : '/satusehat/clinics';
            var type = id ? 'PUT' : 'POST';
            var data = {
                klinik_id: $('#klinik_id').val(),
                base_url: $('#base_url').val(),
                auth_url: $('#auth_url').val(),
                consent_url: $('#consent_url').val(),
                client_id: $('#client_id').val(),
                client_secret: $('#client_secret').val(),
                organization_id: $('#organization_id').val(),
                token: $('#token').val()
            };

            $.ajax({
                url: url,
                method: type,
                data: data,
                success: function(res){
                    $('#clinicModal').modal('hide');
                    table.ajax.reload(null, false);
                    Swal.fire({icon:'success', title: res.message || 'Berhasil'});
                },
                error: function(xhr){
                    var msg = 'Gagal menyimpan';
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        msg = Object.values(xhr.responseJSON.errors).map(function(v){ return v.join(' '); }).join('\n');
                    }
                    Swal.fire({icon:'error', title:'Error', text: msg});
                }
            });
        });

        // Delete
        $('#clinicTable').on('click', '.btn-delete', function(){
            var id = $(this).data('id');
            Swal.fire({
                title: 'Hapus?',
                text: 'Konfigurasi akan dihapus',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus'
            }).then(function(result){
                if (result.isConfirmed) {
                    $.ajax({ url: '/satusehat/clinics/' + id, method: 'DELETE', success: function(res){
                        table.ajax.reload(null, false);
                        Swal.fire({icon:'success', title: res.message || 'Dihapus'});
                    }, error: function(){ Swal.fire({icon:'error', title:'Gagal'}); } });
                }
            });
        });

        // Token request
        $('#clinicTable').on('click', '.btn-token', function(){
            var id = $(this).data('id');
            Swal.fire({title: 'Mengambil token...', didOpen: () => { Swal.showLoading(); }});
            $.post('/satusehat/clinics/' + id + '/token', {}, function(res){
                Swal.close();
                if (res.ok) {
                    Swal.fire({icon:'success', title: res.message || 'Token diterima'});
                    table.ajax.reload(null, false);
                } else {
                    Swal.fire({icon:'error', title: 'Gagal', text: res.message || 'Gagal mengambil token'});
                }
            }).fail(function(xhr){
                Swal.close();
                var msg = 'Gagal mengambil token';
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                Swal.fire({icon:'error', title: 'Error', text: msg});
            });
        });
    });
    </script>
    @endpush

@endsection
