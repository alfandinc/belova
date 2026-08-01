@extends('layouts.marketing.app')

@section('title', 'Master Kategori Tindakan')

@section('navbar')
    @include('layouts.marketing.navbar')
@endsection

@section('content')
<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Master Kategori Tindakan</h4>
            <div>
                <button class="btn btn-secondary mr-2" id="btnImportCsvKategori"><i class="mdi mdi-file-import"></i> Import CSV</button>
                <button class="btn btn-primary" id="btnAddKategori">Tambah Kategori</button>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-bordered" id="kategoriTable">
                <thead>
                    <tr><th>Nama</th><th style="width:120px">Aksi</th></tr>
                </thead>
                <tbody>
                    @foreach($kategoris as $k)
                        <tr data-id="{{ $k->id }}"><td>{{ $k->nama }}</td><td><button class="btn btn-sm btn-danger btn-delete-kategori" data-id="{{ $k->id }}">Hapus</button></td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

                <!-- Import CSV Modal -->
                <div class="modal fade" id="importCsvKategoriModal" tabindex="-1" role="dialog" aria-labelledby="importCsvKategoriModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <form id="importCsvKategoriForm" enctype="multipart/form-data">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="importCsvKategoriModalLabel">Import Kategori Tindakan dari CSV</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label for="csvFileKategori">Pilih file CSV (kolom: nama)</label>
                                        <input type="file" id="csvFileKategori" name="csv" accept=".csv,text/csv" class="form-control-file" required />
                                        <small class="form-text text-muted">File should contain one column with the category name. Header optional.</small>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-primary">Import</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

<!-- Modal -->
<div class="modal fade" id="kategoriModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="kategoriForm">
        <div class="modal-header"><h5 class="modal-title">Tambah Kategori</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
        <div class="modal-body">
          <div class="form-group">
            <label for="kategoriNama">Nama Kategori</label>
            <input type="text" id="kategoriNama" name="nama" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
$(function(){
    $('#btnAddKategori').on('click', function(){
        $('#kategoriForm')[0].reset();
        $('#kategoriModal').modal('show');
    });
    $('#kategoriForm').on('submit', function(e){
        e.preventDefault();
        $.post('/marketing/kategori-tindakan', $(this).serialize(), function(res){
            if (res.success) {
                // append to table
                var k = res.data;
                $('#kategoriTable tbody').append('<tr data-id="'+k.id+'"><td>'+escapeHtml(k.nama)+'</td><td><button class="btn btn-sm btn-danger btn-delete-kategori" data-id="'+k.id+'">Hapus</button></td></tr>');
                $('#kategoriModal').modal('hide');
            }
        }).fail(function(xhr){
            Swal.fire('Gagal', xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Terjadi kesalahan', 'error');
        });
    });

    // Import CSV modal open
    $('#btnImportCsvKategori').on('click', function() {
        $('#importCsvKategoriForm')[0].reset();
        $('#importCsvKategoriModal').modal('show');
    });

    // Handle CSV import form submit
    $('#importCsvKategoriForm').on('submit', function(e) {
        e.preventDefault();
        var fileInput = $('#csvFileKategori')[0];
        if (!fileInput.files || !fileInput.files.length) {
            Swal.fire('Pilih file', 'Silakan pilih file CSV terlebih dahulu.', 'warning');
            return;
        }
        var fd = new FormData();
        fd.append('csv', fileInput.files[0]);
        fd.append('_token', '{{ csrf_token() }}');
        Swal.fire({title: 'Mengimpor...', allowOutsideClick: false, didOpen: ()=>{Swal.showLoading();}});
        $.ajax({
            url: '/erm/marketing/kategori-tindakan/import',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: function(res) {
                $('#importCsvKategoriModal').modal('hide');
                // append created items
                if (res.created_items && res.created_items.length) {
                    res.created_items.forEach(function(k) {
                        $('#kategoriTable tbody').append('<tr data-id="'+k.id+'"><td>'+escapeHtml(k.nama)+'</td><td><button class="btn btn-sm btn-danger btn-delete-kategori" data-id="'+k.id+'">Hapus</button></td></tr>');
                    });
                }
                var html = '<div style="max-height:320px; overflow:auto; text-align:left;">';
                html += '<p><strong>Dibuat:</strong> ' + (res.created||0) + '</p>';
                html += '<p><strong>Dilewati:</strong> ' + (res.skipped||0) + '</p>';
                if (res.errors && res.errors.length) {
                    html += '<hr><p><strong>Errors:</strong></p><ul>';
                    res.errors.forEach(function(err){ html += '<li>'+escapeHtml(err)+'</li>'; });
                    html += '</ul>';
                }
                html += '</div>';
                Swal.fire({title: 'Import Selesai', html: html, width: 700});
            },
            error: function(xhr) {
                var msg = 'Terjadi kesalahan saat import';
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                Swal.fire('Gagal', msg, 'error');
            }
        });
    });

    $(document).on('click', '.btn-delete-kategori', function(){
        var id = $(this).data('id');
        if (!confirm('Hapus kategori?')) return;
        $.ajax({url:'/marketing/kategori-tindakan/'+id, type:'DELETE', data:{_token:'{{ csrf_token() }}'}, success:function(){
            $('#kategoriTable tbody tr[data-id="'+id+'"]').remove();
        }}).fail(function(){ Swal.fire('Gagal','Tidak dapat menghapus','error'); });
    });
});

function escapeHtml(unsafe) { return String(unsafe).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'); }
</script>
@endpush
