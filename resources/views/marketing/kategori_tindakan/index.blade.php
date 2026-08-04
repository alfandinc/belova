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
                    <tr>
                        <th>Nama</th>
                        <th style="width:140px">Jumlah Tindakan</th>
                        <th>ICD10 Terhubung</th>
                        <th style="width:120px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($kategoris as $k)
                        <tr data-id="{{ $k->id }}">
                            <td>{{ $k->nama }}</td>
                            <td>{{ $k->kode_tindakans_count }}</td>
                            <td>
                                @if($k->icd10s->isEmpty())
                                    <span class="text-muted">-</span>
                                @else
                                    @foreach($k->icd10s as $icd10)
                                        <div>
                                            <strong>{{ $icd10->code }}</strong>
                                            <span class="text-muted">{{ $icd10->description }}</span>
                                        </div>
                                    @endforeach
                                @endif
                            </td>
                            <td><button class="btn btn-sm btn-danger btn-delete-kategori" data-id="{{ $k->id }}">Hapus</button></td>
                        </tr>
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
                                        <label for="csvFileKategori">Pilih file CSV</label>
                                        <input type="file" id="csvFileKategori" name="csv" accept=".csv,text/csv" class="form-control-file" required />
                                        <small class="form-text text-muted">Kolom yang dipakai: Nama Tindakan, Kategori, Kode Diagnosa ICD.10, Diagnosa ICD.10.</small>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-primary">Preview Import</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="importPreviewKategoriModal" tabindex="-1" role="dialog" aria-labelledby="importPreviewKategoriModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="importPreviewKategoriModalLabel">Preview Import Kategori Tindakan</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" id="importPreviewToken">
                                <div class="row mb-3" id="importPreviewSummary"></div>
                                <div class="table-responsive" style="max-height: 420px; overflow:auto;">
                                    <table class="table table-bordered table-sm" id="importPreviewTable">
                                        <thead>
                                            <tr>
                                                <th>Row</th>
                                                <th>Nama Tindakan</th>
                                                <th>Kategori</th>
                                                <th>Kode ICD10</th>
                                                <th>Diagnosa ICD10</th>
                                                <th>Kategori</th>
                                                <th>Link Tindakan</th>
                                                <th>ICD10</th>
                                                <th>Link ICD10</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                <button type="button" class="btn btn-primary" id="btnApplyImportKategori">Submit Import</button>
                            </div>
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
    var kategoriStoreUrl = '{{ route('marketing.kategori.store') }}';
    var kategoriPreviewImportUrl = '{{ route('marketing.kategori.import.preview') }}';
    var kategoriApplyImportUrl = '{{ route('marketing.kategori.import.apply') }}';
    var kategoriDestroyUrlTemplate = '{{ route('marketing.kategori.destroy', ['id' => '__ID__']) }}';

    $('#btnAddKategori').on('click', function(){
        $('#kategoriForm')[0].reset();
        $('#kategoriModal').modal('show');
    });
    $('#kategoriForm').on('submit', function(e){
        e.preventDefault();
        $.post(kategoriStoreUrl, $(this).serialize(), function(res){
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
        Swal.fire({title: 'Membuat preview...', allowOutsideClick: false, didOpen: ()=>{Swal.showLoading();}});
        $.ajax({
            url: kategoriPreviewImportUrl,
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: function(res) {
                $('#importCsvKategoriModal').modal('hide');
                renderImportPreview(res);
                Swal.close();
                $('#importPreviewKategoriModal').modal('show');
            },
            error: function(xhr) {
                var msg = 'Terjadi kesalahan saat import';
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                Swal.fire('Gagal', msg, 'error');
            }
        });
    });

    $('#btnApplyImportKategori').on('click', function() {
        var token = $('#importPreviewToken').val();
        if (!token) {
            Swal.fire('Gagal', 'Preview import tidak ditemukan.', 'error');
            return;
        }

        Swal.fire({title: 'Mengimpor...', allowOutsideClick: false, didOpen: ()=>{Swal.showLoading();}});
        $.post(kategoriApplyImportUrl, {
            _token: '{{ csrf_token() }}',
            token: token
        }, function(res) {
            $('#importPreviewKategoriModal').modal('hide');
            Swal.fire('Berhasil', buildImportResultMessage(res.summary), 'success').then(function() {
                window.location.reload();
            });
        }).fail(function(xhr) {
            Swal.fire('Gagal', xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Terjadi kesalahan', 'error');
        });
    });

    $(document).on('click', '.btn-delete-kategori', function(){
        var id = $(this).data('id');
        if (!confirm('Hapus kategori?')) return;
        $.ajax({url:kategoriDestroyUrlTemplate.replace('__ID__', id), type:'DELETE', data:{_token:'{{ csrf_token() }}'}, success:function(){
            $('#kategoriTable tbody tr[data-id="'+id+'"]').remove();
        }}).fail(function(){ Swal.fire('Gagal','Tidak dapat menghapus','error'); });
    });
});

function renderImportPreview(res) {
    $('#importPreviewToken').val(res.token || '');

    var summary = res.summary || {};
    var summaryHtml = '';
    summaryHtml += summaryCard('Rows', summary.rows_total || 0, 'info');
    summaryHtml += summaryCard('Kategori Baru', summary.categories_to_create || 0, 'primary');
    summaryHtml += summaryCard('Link Kode Tindakan', summary.kode_tindakan_to_link || 0, 'success');
    summaryHtml += summaryCard('Kode Tindakan Tidak Ditemukan', summary.kode_tindakan_missing || 0, 'warning');
    summaryHtml += summaryCard('ICD10 Baru', summary.icd10_to_create || 0, 'secondary');
    summaryHtml += summaryCard('Link ICD10', summary.icd10_to_link || 0, 'dark');
    $('#importPreviewSummary').html(summaryHtml);

    var rowsHtml = '';
    (res.rows || []).forEach(function(row) {
        rowsHtml += '<tr>'
            + '<td>' + escapeHtml(row.row) + '</td>'
            + '<td>' + escapeHtml(row.nama_tindakan) + '</td>'
            + '<td>' + escapeHtml(row.kategori) + '</td>'
            + '<td>' + escapeHtml(row.icd_code || '-') + '</td>'
            + '<td>' + escapeHtml(row.icd_description || '-') + '</td>'
            + '<td>' + renderStatusBadge(row.kategori_action) + '</td>'
            + '<td>' + renderStatusBadge(row.kode_tindakan_action) + '</td>'
            + '<td>' + renderStatusBadge(row.icd_action) + '</td>'
            + '<td>' + renderStatusBadge(row.icd_link_action) + '</td>'
            + '</tr>';
    });
    $('#importPreviewTable tbody').html(rowsHtml || '<tr><td colspan="9" class="text-center">Tidak ada data valid untuk diimport.</td></tr>');
}

function summaryCard(label, value, theme) {
    return '<div class="col-md-2 col-sm-4 col-6 mb-2">'
        + '<div class="card border-' + theme + ' h-100 mb-0">'
        + '<div class="card-body p-2 text-center">'
        + '<div class="small text-muted">' + escapeHtml(label) + '</div>'
        + '<div class="h4 mb-0">' + escapeHtml(value) + '</div>'
        + '</div></div></div>';
}

function renderStatusBadge(status) {
    var map = {
        existing: 'badge badge-success',
        create: 'badge badge-primary',
        attach: 'badge badge-info',
        exists: 'badge badge-success',
        missing: 'badge badge-warning',
        none: 'badge badge-secondary'
    };
    var labelMap = {
        existing: 'Existing',
        create: 'Create',
        attach: 'Attach',
        exists: 'Exists',
        missing: 'Missing',
        none: 'None'
    };
    return '<span class="' + (map[status] || 'badge badge-light') + '">' + escapeHtml(labelMap[status] || status) + '</span>';
}

function buildImportResultMessage(summary) {
    summary = summary || {};
    return 'Kategori baru: ' + (summary.categories_created || 0)
        + ', link kode tindakan: ' + (summary.kode_tindakan_linked || 0)
        + ', ICD10 baru: ' + (summary.icd10_created || 0)
        + ', link ICD10: ' + (summary.icd10_linked || 0)
        + ', kode tindakan tidak ditemukan: ' + (summary.kode_tindakan_missing || 0) + '.';
}

function escapeHtml(unsafe) { return String(unsafe).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'); }
</script>
@endpush
