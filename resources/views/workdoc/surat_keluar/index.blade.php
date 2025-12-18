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
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="card-title mb-0">Surat Keluar</h3>
                        <button class="btn btn-primary" id="btnNew">Buat Surat</button>
                    </div>
                    <table class="table table-bordered" id="suratTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>No Surat</th>
                                <th>Instansi / Jenis</th>
                                <th>Diajukan / Dibuat</th>
                                <th>Tgl Dibuat</th>
                                   <th>Status</th>
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
                { data: null, orderable: false, searchable: false, render: function(data, type, row, meta){ return meta.row + meta.settings._iDisplayStart + 1; } },
                { data: 'no_surat', name: 'no_surat' },
                    { data: null, name: 'instansi_jenis', orderable:false, searchable:false, render: function(data, type, row){
                        var inst = row.instansi? row.instansi : '';
                        var jenis = row.jenis_surat? row.jenis_surat : '';
                        // color mapping
                        var color = '';
                        if (inst === 'Belova Skincare') color = '#e83e8c'; // pink
                        else if (inst === 'Premiere Belova') color = '#007bff'; // blue
                        else if (inst === 'BCL') color = '#fd7e14'; // orange

                        var badgeHtml = '';
                        if (inst) {
                            badgeHtml = '<div style="margin-top:6px;"><span class="badge" style="background:'+color+';color:#fff;padding:0.35em 0.6em;border-radius:0.25rem;">'+inst+'</span></div>';
                        }

                        var jenisHtml = jenis ? '<div>'+jenis+'</div>' : '';
                        return '<div>'+jenisHtml+badgeHtml+'</div>';
                    } },
                
                { data: 'person_info', name: 'person_info', orderable:false, searchable:false },
                { data: 'tgl_dibuat', name: 'tgl_dibuat', render: function(data, type, row){
                    if (!data) return '';
                    // expected format: 'YYYY-MM-DD' or 'YYYY-MM-DD HH:MM:SS'
                    var datePart = data.split(' ')[0];
                    var parts = datePart.split('-');
                    if (parts.length < 3) return data;
                    var year = parts[0];
                    var month = parseInt(parts[1], 10);
                    var day = parseInt(parts[2], 10);
                    var months = ['januari','februari','maret','april','mei','juni','juli','agustus','september','oktober','november','desember'];
                    var monthName = months[(month-1)] || '';
                    return day + ' ' + monthName + ' ' + year;
                } },
                    { data: 'status', name: 'status', render: function(data, type, row){
                        var s = (row.status || '').toString().toLowerCase();
                        var cls = 'badge-secondary';
                        if (s === 'draft') cls = 'badge-primary';
                        else if (s === 'diajukan') cls = 'badge-warning';
                        else if (s === 'disetujui') cls = 'badge-success';
                        else if (s === 'revisi') cls = 'badge-danger';
                        var label = row.status || '';
                        return '<span class="badge '+cls+'">'+label+'</span>';
                    } },
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

        // handle Ajukan button (submit) — only visible to creator for draft/revisi
        $(document).on('click', '.btn-ajukan', function(){
            var id = $(this).data('id');
            Swal.fire({
                title: 'Ajukan surat?',
                text: 'Status akan berubah menjadi diajukan.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, ajukan'
            }).then((result) => {
                if (!result.value) return;
                $.ajax({
                    url: '/workdoc/surat-keluar/'+id+'/ajukan',
                    method: 'POST',
                    data: { _token: $('meta[name="csrf-token"]').attr('content') },
                    success: function(){
                        table.ajax.reload(null, false);
                        Swal.fire('Diajukan','Surat berhasil diajukan','success');
                    },
                    error: function(xhr){
                        Swal.fire('Error','Gagal mengajukan','error');
                    }
                });
            });
        });

        // handle Approve button — only shown to user in diajukan_for
        $(document).on('click', '.btn-approve', function(){
            var id = $(this).data('id');
            Swal.fire({
                title: 'Setujui surat?',
                text: 'Status akan berubah menjadi disetujui.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, setujui'
            }).then((result) => {
                if (!result.value) return;
                $.ajax({
                    url: '/workdoc/surat-keluar/'+id+'/approve',
                    method: 'POST',
                    data: { _token: $('meta[name="csrf-token"]').attr('content') },
                    success: function(){
                        table.ajax.reload(null, false);
                        Swal.fire('Disetujui','Surat berhasil disetujui','success');
                    },
                    error: function(xhr){
                        Swal.fire('Error','Gagal menyetujui','error');
                    }
                });
            });
        });

        // handle Revisi button — only shown to user in diajukan_for
        $(document).on('click', '.btn-revisi', function(){
            var id = $(this).data('id');
            Swal.fire({
                title: 'Minta revisi?',
                text: 'Status akan berubah menjadi revisi.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, minta revisi'
            }).then((result) => {
                if (!result.value) return;
                $.ajax({
                    url: '/workdoc/surat-keluar/'+id+'/revisi',
                    method: 'POST',
                    data: { _token: $('meta[name="csrf-token"]').attr('content') },
                    success: function(){
                        table.ajax.reload(null, false);
                        Swal.fire('Revisi','Permintaan revisi terkirim','success');
                    },
                    error: function(xhr){
                        Swal.fire('Error','Gagal membuat revisi','error');
                    }
                });
            });
        });
    });
</script>
@endsection
