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
                        <div class="d-inline-block mr-2">
                            <input type="text" id="filterRange" class="form-control form-control-sm" style="min-width:240px;" placeholder="Pilih rentang tanggal" />
                        </div>
                        <div class="d-inline-block mr-2">
                            <select id="filterStatus" class="form-control form-control-sm" style="min-width:160px;">
                                <option value="">Semua</option>
                                <option value="draft" selected>Draft</option>
                                <option value="done">Done</option>
                            </select>
                        </div>
                        <a href="{{ route('workdoc.memorandum.create') }}" class="btn btn-primary">Buat Memorandum</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="memorandumTable" class="table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Nomor</th>
                                    <th>Detail</th>
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

<!-- Disposisi Modal -->
<div class="modal fade" id="disposisiModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title mb-0">Buat Disposisi</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Tanggal Terima</label>
                    <input type="date" id="dispTanggalTerima" class="form-control" />
                </div>

                <div class="form-group">
                    <label>Disposisi Pimpinan</label>
                    <div id="dispPimpinanOptions" class="row">
                        <div class="col-md-6">
                            <div class="custom-control custom-checkbox mb-1">
                                <input type="checkbox" class="custom-control-input disp-pimpinan" id="dp1" value="Untuk diketahui">
                                <label class="custom-control-label" for="dp1">Untuk diketahui</label>
                            </div>
                            <div class="custom-control custom-checkbox mb-1">
                                <input type="checkbox" class="custom-control-input disp-pimpinan" id="dp2" value="Untuk dipelajari">
                                <label class="custom-control-label" for="dp2">Untuk dipelajari</label>
                            </div>
                            <div class="custom-control custom-checkbox mb-1">
                                <input type="checkbox" class="custom-control-input disp-pimpinan" id="dp3" value="Segera dilaksanakan">
                                <label class="custom-control-label" for="dp3">Segera dilaksanakan</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="custom-control custom-checkbox mb-1">
                                <input type="checkbox" class="custom-control-input disp-pimpinan" id="dp4" value="Untuk ditindaklanjuti">
                                <label class="custom-control-label" for="dp4">Untuk ditindaklanjuti</label>
                            </div>
                            <div class="custom-control custom-checkbox mb-1">
                                <input type="checkbox" class="custom-control-input disp-pimpinan" id="dp5" value="Koordinasi dengan unit terkait">
                                <label class="custom-control-label" for="dp5">Koordinasi dengan unit terkait</label>
                            </div>
                            <div class="custom-control custom-checkbox mb-1">
                                <input type="checkbox" class="custom-control-input disp-pimpinan" id="dp6" value="Diarsipkan">
                                <label class="custom-control-label" for="dp6">Diarsipkan</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Tujuan Disposisi (Divisi)</label>
                    <div id="dispTujuanContainer" class="row"></div>
                </div>

                <div class="form-group">
                    <label>Catatan</label>
                    <textarea id="dispCatatan" class="form-control" rows="3" placeholder="Catatan"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <a id="dispCetakBtn" href="#" target="_blank" class="btn btn-secondary mr-auto" style="display:none;">Cetak Disposisi</a>
                <button type="button" class="btn btn-primary" id="dispSimpanBtn">Simpan</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
    </div>
@endsection

@push('scripts')
<script>
$(function(){
    // Default to current month
    let start = moment().startOf('month');
    let end = moment().endOf('month');

    function updateRangeLabel(s, e){
        $('#filterRange').val(s.format('DD MMM YYYY') + ' - ' + e.format('DD MMM YYYY'));
    }
    $('#filterRange').daterangepicker({
        startDate: start,
        endDate: end,
        autoUpdateInput: false,
        locale: {
            format: 'DD/MM/YYYY',
            applyLabel: 'Pilih',
            cancelLabel: 'Batal'
        }
    }, function(s, e){
        start = s;
        end = e;
        updateRangeLabel(start, end);
        table.ajax.reload(null, false);
    });
    updateRangeLabel(start, end);

        let currentMemoId = null;
        let statusFilter = 'draft';
    const table = $('#memorandumTable').DataTable({
        ajax: {
            url: '{{ route('workdoc.memorandum.data') }}',
            dataSrc: 'data',
            data: function(d){
                d.start_date = start.format('YYYY-MM-DD');
                d.end_date = end.format('YYYY-MM-DD');
                    // Map 'done' to backend 'published'
                    d.status = (statusFilter === 'done') ? 'published' : statusFilter;
            }
        },
        order: [],
        columns: [
            {data: null, render: function(row){
                const nomor = row.nomor_memo || '-';
                const shortName = (row.klinik_short || '').toString();
                const klinik = shortName || row.klinik || '';
                const user = row.user || '';
                const maker = user ? '<div class="text-muted small">'+user+'</div>' : '';
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
                return '<div><strong>'+nomor+'</strong></div>'+maker+badges;
            }},
            {data: null, render: function(row){
                const tgl = row.tanggal || '-';
                const divisi = (row.division || '-').toString();
                const kepada = (row.kepada || '-').toString();
                const dk = '<div class="mt-1"><div><strong>Dari:</strong> '+divisi+'</div><div><strong>Kepada:</strong> '+kepada+'</div></div>';
                return '<div><strong>'+tgl+'</strong></div>'+dk;
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
                        + (({{ Auth::id() ?? 'null' }} === row.user_id) ? '<a class="btn btn-info" href="'+editUrl+'">Edit</a>' : '')
                        + '<a class="btn btn-secondary" target="_blank" href="'+pdfUrl+'">PDF</a>'
                        + '<button class="btn btn-warning docModal" data-id="'+row.id+'">Dokumen</button>'
                        + '<button class="btn btn-primary disposisiBtn" data-id="'+row.id+'">Disposisi</button>'
                        + (({{ Auth::id() ?? 'null' }} === row.user_id) ? '<button class="btn btn-danger deleteMemo" data-id="'+row.id+'">Delete</button>' : '')
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

    // Disposisi modal logic
    let currentDispMemoId = null;
    let currentDisposisiId = null;
    let dispDivisionsLoaded = false;
    function renderDivisionCheckboxes(list){
        const container = $('#dispTujuanContainer');
        container.empty();
        const half = Math.ceil(list.length / 2);
        const cols = [list.slice(0, half), list.slice(half)];
        cols.forEach(function(items){
            const col = $('<div class="col-md-6"></div>');
            items.forEach(function(d){
                const id = 'div-'+d.id;
                const cb = '<div class="custom-control custom-checkbox mb-1">'
                    + '<input type="checkbox" class="custom-control-input disp-divisi" id="'+id+'" value="'+d.id+'">'
                    + '<label class="custom-control-label" for="'+id+'">'+(d.name || ('Divisi '+d.id))+'</label>'
                    + '</div>';
                col.append(cb);
            });
            container.append(col);
        });
    }

    function ensureDivisions(){
        if(dispDivisionsLoaded){
            var d = $.Deferred();
            d.resolve();
            return d.promise();
        }
        return $.get('{{ route('workdoc.disposisi.divisions') }}')
            .done(function(resp){
                renderDivisionCheckboxes(resp.data || []);
                dispDivisionsLoaded = true;
            });
    }

    $('#memorandumTable').on('click', '.disposisiBtn', function(){
        currentDispMemoId = $(this).data('id');
        // reset fields
        $('#dispTanggalTerima').val(moment().format('YYYY-MM-DD'));
        $('.disp-pimpinan').prop('checked', false);
        $('#dispCatatan').val('');
        currentDisposisiId = null;
        $('#dispSimpanBtn').text('Simpan');
        // load divisions then fetch existing data and show
        ensureDivisions().always(function(){
            $('.disp-divisi').prop('checked', false);
            $.get('{{ url('/workdoc/disposisi/memorandums') }}/'+currentDispMemoId+'/latest')
                .done(function(resp){
                    if(resp && resp.data){
                        const d = resp.data;
                        currentDisposisiId = d.id;
                        $('#dispSimpanBtn').text('Perbarui');
                        $('#dispCetakBtn').attr('href', '{{ url('/workdoc/disposisi') }}/'+currentDisposisiId+'/print-pdf').show();
                        if(d.tanggal_terima){
                            $('#dispTanggalTerima').val(moment(d.tanggal_terima).format('YYYY-MM-DD'));
                        }
                        try {
                            (d.disposisi_pimpinan || []).forEach(function(v){
                                $(".disp-pimpinan[value='"+v+"']").prop('checked', true);
                            });
                        } catch(e) {}
                        try {
                            (d.tujuan_disposisi || []).forEach(function(id){
                                $(".disp-divisi[value='"+id+"']").prop('checked', true);
                            });
                        } catch(e) {}
                        $('#dispCatatan').val(d.catatan || '');
                    } else {
                        $('#dispCetakBtn').hide().attr('href', '#');
                    }
                })
                .always(function(){
                    $('#disposisiModal').modal('show');
                });
        });
    });

    $('#dispSimpanBtn').on('click', function(){
        if(!currentDispMemoId){ return; }
        const tanggal = $('#dispTanggalTerima').val();
        const pimpinan = $('.disp-pimpinan:checked').map(function(){ return this.value; }).get();
        const tujuan = $('.disp-divisi:checked').map(function(){ return parseInt(this.value,10); }).get();
        const catatan = $('#dispCatatan').val();
        if(currentDisposisiId){
            $.ajax({
                url: '{{ url('/workdoc/disposisi') }}/'+currentDisposisiId,
                method: 'PUT',
                data: {
                    tanggal_terima: tanggal,
                    disposisi_pimpinan: pimpinan,
                    tujuan_disposisi: tujuan,
                    catatan: catatan,
                    _token: '{{ csrf_token() }}'
                }
            }).done(function(resp){
                Swal.fire({icon:'success', title:'Sukses', text: resp.message || 'Disposisi diperbarui'});
                // ensure Cetak link exists after update
                currentDisposisiId = resp.data && resp.data.id ? resp.data.id : currentDisposisiId;
                if(currentDisposisiId){
                    $('#dispCetakBtn').attr('href', '{{ url('/workdoc/disposisi') }}/'+currentDisposisiId+'/print-pdf').show();
                }
                $('#disposisiModal').modal('hide');
            }).fail(function(xhr){
                let msg = 'Gagal menyimpan disposisi';
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                Swal.fire({icon:'error', title:'Error', text: msg});
            });
        } else {
            $.ajax({
                url: '{{ route('workdoc.disposisi.store') }}',
                method: 'POST',
                data: {
                    memorandum_id: currentDispMemoId,
                    tanggal_terima: tanggal,
                    disposisi_pimpinan: pimpinan,
                    tujuan_disposisi: tujuan,
                    catatan: catatan,
                    _token: '{{ csrf_token() }}'
                }
            }).done(function(resp){
                Swal.fire({icon:'success', title:'Sukses', text: resp.message || 'Disposisi dibuat'});
                // set Cetak link to newly created disposisi
                currentDisposisiId = resp.data && resp.data.id ? resp.data.id : null;
                if(currentDisposisiId){
                    $('#dispCetakBtn').attr('href', '{{ url('/workdoc/disposisi') }}/'+currentDisposisiId+'/print-pdf').show();
                }
                $('#disposisiModal').modal('hide');
            }).fail(function(xhr){
                let msg = 'Gagal menyimpan disposisi';
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                Swal.fire({icon:'error', title:'Error', text: msg});
            });
        }
    });

    // Status filter change
    $('#filterStatus').on('change', function(){
        statusFilter = this.value || '';
        table.ajax.reload(null, false);
    });
});
</script>
@endpush