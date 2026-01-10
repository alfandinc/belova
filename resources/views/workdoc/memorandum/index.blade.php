@extends('layouts.workdoc.app')

@section('title','Workdoc - Memorandum')

@section('navbar')
    @include('layouts.workdoc.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Memorandum</h4>
                    <div>
                        <a href="{{ route('workdoc.memorandum.create') }}" class="btn btn-primary">Buat Memorandum</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="memorandumTable" class="table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Nomor</th>
                                    <th>Dari/Kepada</th>
                                    <th>Perihal</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dokumen Modal -->
<div class="modal fade" id="dokumenModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title mb-0">Dokumen Pendukung</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="dokumenPreview" class="mb-3" style="display:none;">
                    <iframe id="dokumenIframe" src="" style="width:100%;height:480px;border:1px solid #e9ecef;border-radius:4px;"></iframe>
                </div>
                <div class="form-group">
                    <label for="dokumenFile">Pilih file (PDF/IMG, maks 10MB)</label>
                    <input type="file" id="dokumenFile" class="form-control-file" accept="application/pdf,image/*">
                </div>
            </div>
            <div class="modal-footer">
                <a id="dokumenOpenLink" href="#" target="_blank" class="btn btn-success mr-auto" style="display:none;">Buka di Tab Baru</a>
                <button type="button" class="btn btn-primary" id="dokumenUploadBtn">Unggah</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
    </div>
@endsection

@push('scripts')
<script>
$(function(){
        let currentMemoId = null;
    const table = $('#memorandumTable').DataTable({
        ajax: {
            url: '{{ route('workdoc.memorandum.data') }}',
            dataSrc: 'data'
        },
        columns: [
            {data: null, render: function(row){
                const tgl = row.tanggal || '-';
                const user = row.user || '';
                const maker = user ? '<div class="text-muted small">'+user+'</div>' : '';
                return '<div>'+tgl+'</div>'+maker;
            }},
            {data: null, render: function(row){
                const nomor = row.nomor_memo || '-';
                const shortName = (row.klinik_short || '').toString();
                const klinik = shortName || row.klinik || '';
                let style = '';
                if (shortName.toLowerCase() === 'premiere') {
                    style = 'background-color:#007bff;color:#fff;';
                } else if (shortName.toLowerCase() === 'belovaskin') {
                    style = 'background-color:#e83e8c;color:#fff;';
                } else {
                    style = 'background-color:#6c757d;color:#fff;';
                }
                const badges = (klinik)
                    ? '<div style="display:flex;align-items:center;gap:4px;margin-top:4px;">'
                        + (klinik ? '<span class="badge" style="'+style+'">'+klinik+'</span>' : '')
                    + '</div>'
                    : '';
                return '<div>'+nomor+'</div>'+badges;
            }},
            {data: null, render: function(row){
                const divisi = (row.division || '-').toString();
                const kepada = (row.kepada || '-').toString();
                return '<div><div><strong>Dari:</strong> '+divisi+'</div><div><strong>Kepada:</strong> '+kepada+'</div></div>';
            }},
            {data: 'perihal'},
            {data: 'status', render: function(data){
                const raw = (data || '').toString();
                const label = raw.charAt(0).toUpperCase() + raw.slice(1);
                const map = { draft: 'secondary', published: 'success', archived: 'dark' };
                const cls = map[raw] || 'secondary';
                return '<span class="badge badge-'+cls+'">'+label+'</span>';
            }},
            {data: null, render: function(row){
                const editUrl = '{{ route('workdoc.memorandum.edit', ['memorandum' => 'MEMO_ID']) }}'.replace('MEMO_ID', row.id);
                const pdfUrl = '{{ route('workdoc.memorandum.print_pdf', ['memorandum' => 'MEMO_ID']) }}'.replace('MEMO_ID', row.id);
                return '<div class="btn-group btn-group-sm" role="group">'
                        + '<a class="btn btn-info" href="'+editUrl+'">Edit</a>'
                        + '<a class="btn btn-secondary" target="_blank" href="'+pdfUrl+'">PDF</a>'
                        + '<button class="btn btn-warning docModal" data-id="'+row.id+'">Dokumen</button>'
                        + '<button class="btn btn-danger deleteMemo" data-id="'+row.id+'">Delete</button>'
                    + '</div>';
            }}
        ]
    });

    $('#memorandumTable').on('click', '.deleteMemo', function(){
        const id = $(this).data('id');
        Swal.fire({
            title: 'Hapus memorandum?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal'
        }).then(function(result){
            if(result.isConfirmed){
                $.post('{{ url('/workdoc/memorandums') }}/'+id, { _method: 'DELETE', _token: '{{ csrf_token() }}' })
                    .done(function(resp){
                        Swal.fire({icon:'success', title:'Terhapus', text: resp.message || 'Memorandum dihapus'});
                        table.ajax.reload(null,false);
                    })
                    .fail(function(){
                        Swal.fire({icon:'error', title:'Error', text:'Gagal menghapus'});
                    });
            }
        });
    });

    function openDokumenModal(row){
        currentMemoId = row.id;
        const hasDoc = !!row.dokumen_path;
        const viewUrl = '{{ route('workdoc.memorandum.dokumen.view', ['memorandum' => 'MEMO_ID']) }}'.replace('MEMO_ID', row.id);
        $('#dokumenFile').val('');
        if(hasDoc){
            $('#dokumenIframe').attr('src', viewUrl + '?t=' + Date.now());
            $('#dokumenPreview').show();
            $('#dokumenOpenLink').attr('href', viewUrl).show();
            $('#dokumenUploadBtn').text('Ganti Dokumen');
        } else {
            $('#dokumenIframe').attr('src', '');
            $('#dokumenPreview').hide();
            $('#dokumenOpenLink').hide();
            $('#dokumenUploadBtn').text('Unggah');
        }
        $('#dokumenModal').modal('show');
    }

    $('#memorandumTable').on('click', '.docModal', function(){
        const rowData = table.row($(this).closest('tr')).data();
        openDokumenModal(rowData);
    });

    $('#dokumenUploadBtn').on('click', function(){
        if(!currentMemoId){ return; }
        const fileEl = document.getElementById('dokumenFile');
        if(!fileEl || !fileEl.files || !fileEl.files.length){
            Swal.fire({icon:'warning', title:'Pilih File', text:'Silakan pilih file (PDF/IMG) terlebih dahulu.'});
            return;
        }
        const formData = new FormData();
        formData.append('dokumen', fileEl.files[0]);
        formData.append('_token', '{{ csrf_token() }}');
        $.ajax({
            url: '{{ url('/workdoc/memorandums') }}/'+currentMemoId+'/dokumen',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false
        }).done(function(resp){
            Swal.fire({icon:'success', title:'Sukses', text: resp.message || 'Dokumen disimpan'});
            const viewUrl = '{{ route('workdoc.memorandum.dokumen.view', ['memorandum' => 'MEMO_ID']) }}'.replace('MEMO_ID', currentMemoId);
            $('#dokumenIframe').attr('src', viewUrl + '?t=' + Date.now());
            $('#dokumenPreview').show();
            $('#dokumenOpenLink').attr('href', viewUrl).show();
            $('#dokumenUploadBtn').text('Ganti Dokumen');
            $('#dokumenFile').val('');
            table.ajax.reload(null,false);
        }).fail(function(xhr){
            let msg = 'Gagal mengunggah dokumen';
            if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
            Swal.fire({icon:'error', title:'Error', text: msg});
        });
    });
});
</script>
@endpush