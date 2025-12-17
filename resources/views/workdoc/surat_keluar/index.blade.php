@extends('layouts.workdoc.app')

@section('navbar')
    @include('layouts.workdoc.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Surat Keluar</h4>
                    <button class="btn btn-primary mb-3" id="btnNew">Buat Surat</button>
                    <table class="table table-bordered" id="suratTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>No Surat</th>
                                <th>Instansi</th>
                                <th>Jenis</th>
                                <th>Status</th>
                                <th>Diajukan For</th>
                                <th>Created By</th>
                                <th>Tgl Dibuat</th>
                                <th>Lampiran</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@include('workdoc.surat_keluar._form_modal')

@endsection

@section('scripts')
<script>
    $(function(){
        var table = $('#suratTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('workdoc.surat-keluar.list') }}",
            columns: [
                { data: 'id', name: 'id' },
                { data: 'no_surat', name: 'no_surat' },
                    { data: 'instansi', name: 'instansi' },
                { data: 'jenis_surat', name: 'jenis_surat' },
                { data: 'status', name: 'status' },
                { data: 'diajukan_for', name: 'diajukan_for' },
                { data: 'created_by', name: 'created_by' },
                { data: 'tgl_dibuat', name: 'tgl_dibuat' },
                { data: 'lampiran_link', name: 'lampiran', orderable:false, searchable:false },
                { data: 'action', name: 'action', orderable:false, searchable:false }
            ]
        });

        // load jenis options into select
        function loadJenisOptions(selected) {
            $.getJSON("{{ route('workdoc.surat-jenis.list') }}", function(res){
                var opts = '<option value="">-- Pilih Jenis --</option>';
                res.data.forEach(function(j){
                    // use nama as value to store in surat_keluar.jenis_surat
                    var sel = selected && selected == j.nama ? ' selected' : '';
                    opts += '<option value="'+j.nama+'"'+sel+'>'+j.nama+(j.singkatan? ' ('+j.singkatan+')':'')+'</option>';
                });
                $('#jenis_surat').html(opts);
            });
        }

        // load instansi options (static list)
        function loadInstansiOptions(selected) {
            var opts = '<option value="">-- Pilih Instansi --</option>';
            var list = ['Premiere Belova','Belova Skincare','BCL'];
            list.forEach(function(i){
                var sel = selected && selected == i ? ' selected' : '';
                opts += '<option value="'+i+'"'+sel+'>'+i+'</option>';
            });
            $('#instansi').html(opts);
        }

        // initial load
        loadJenisOptions();
        loadInstansiOptions();
        // load diajukan_for options (users with role Ceo)
        function loadDiajukanForOptions(selected) {
            $.getJSON("{{ route('workdoc.surat-diajukan-for.list') }}", function(res){
                var opts = '<option value="">-- Pilih User --</option>';
                res.data.forEach(function(u){
                    var sel = selected && selected == u.id ? ' selected' : '';
                    opts += '<option value="'+u.id+'"'+sel+'>'+u.name+'</option>';
                });
                $('#diajukan_for').html(opts);
            });
        }

        loadDiajukanForOptions();

        $('#btnNew').on('click', function(){
            $('#suratKeluarForm')[0].reset();
            $('#sk_id').val('');
            $('#existingLampiran').html('');
            loadInstansiOptions();
            $('#suratKeluarModal').modal('show');
        });

        $(document).on('click', '.btn-edit', function(){
            var id = $(this).data('id');
            $.getJSON('/workdoc/surat-keluar/'+id, function(res){
                var d = res.data;
                $('#sk_id').val(d.id);
                $('#no_surat').val(d.no_surat);
                loadInstansiOptions(d.instansi);
                $('#deskripsi').val(d.deskripsi);
                $('#diajukan_for').val(d.diajukan_for);
                // set date part only (assumes stored format 'YYYY-MM-DD HH:MM:SS')
                if (d.tgl_dibuat) {
                    var parts = d.tgl_dibuat.split(' ');
                    $('#tgl_dibuat').val(parts[0]);
                } else {
                    $('#tgl_dibuat').val('');
                }
                // reload jenis options and select the correct one
                loadJenisOptions(d.jenis_surat);
                // reload diajukan_for options and select the current user id
                loadDiajukanForOptions(d.diajukan_for);
                $('#no_surat').val(d.no_surat);
                if (d.lampiran) {
                    $('#existingLampiran').html('<a href="/workdoc/surat-keluar/'+d.id+'/download">'+d.lampiran.split('/').pop()+'</a>');
                } else { $('#existingLampiran').html(''); }
                $('#suratKeluarModal').modal('show');
            });
        });

        // try to auto-generate no_surat when instansi, jenis_surat and tgl_dibuat present
        function tryGenerateNoSurat() {
            var inst = $('#instansi').val();
            var jenis = $('#jenis_surat').val();
            var tgl = $('#tgl_dibuat').val();
            // if editing existing record and it already has no_surat, don't overwrite
            if ($('#sk_id').val() && $('#no_surat').val()) return;
            if (!inst || !jenis || !tgl) return;
            $.getJSON("{{ route('workdoc.surat-keluar.generate_number') }}", { instansi: inst, jenis_surat: jenis, tgl_dibuat: tgl })
                .done(function(res){
                    if (res.data && res.data.no_surat) {
                        $('#no_surat').val(res.data.no_surat);
                    }
                })
                .fail(function(xhr){
                    console.error('generate-number error', xhr.status, xhr.responseText);
                });
        }

        // bind change events
        $(document).on('change', '#instansi, #jenis_surat, #tgl_dibuat', function(){
            tryGenerateNoSurat();
        });

        $('#suratKeluarForm').on('submit', function(e){
            e.preventDefault();
            var id = $('#sk_id').val();
            var url = id ? '/workdoc/surat-keluar/'+id : '/workdoc/surat-keluar';
            var formData = new FormData(this);
            if (id) formData.append('_method', 'PUT');
            $.ajax({
                url: url,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function(){
                    $('#suratKeluarModal').modal('hide');
                    table.ajax.reload(null, false);
                    Swal.fire('Berhasil','Data tersimpan','success');
                },
                error: function(xhr){
                    Swal.fire('Error','Terjadi kesalahan','error');
                }
            });
        });

        $(document).on('click', '.btn-delete', function(){
            var id = $(this).data('id');
            Swal.fire({
                title: 'Hapus?',
                text: 'Yakin ingin menghapus surat ini?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus'
            }).then((result) => {
                if (result.value) {
                    $.ajax({
                        url: '/workdoc/surat-keluar/'+id,
                        method: 'POST',
                        data: { _method: 'DELETE', _token: $('meta[name="csrf-token"]').attr('content') },
                        success: function(){
                            table.ajax.reload(null, false);
                            Swal.fire('Terhapus','Surat telah dihapus','success');
                        },
                        error: function(){ Swal.fire('Error','Gagal menghapus','error'); }
                    });
                }
            });
        });
    });
</script>
@endsection
