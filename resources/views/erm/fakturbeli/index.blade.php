@extends('layouts.erm.app')
@section('title', 'Farmasi | Faktur Pembelian')
@section('navbar')
    @include('layouts.erm.navbar-farmasi')
@endsection
@section('content')
<div class="container-fluid">
    <style>
        .fb-tabs .nav-link { padding: .35rem .8rem; font-size: .875rem; }
        #fakturbeli-table td { vertical-align: top; }
        #fakturbeli-table tr.fb-perlu td:first-child { box-shadow: inset 3px 0 0 #17a2b8; }
        #fakturbeli-table tr.fb-diminta td:first-child { box-shadow: inset 3px 0 0 #ffc107; }
        #fakturbeli-table .badge { font-size: .72rem; }
        .fb-approve-table td, .fb-approve-table th { font-size: .8rem; padding: .3rem .4rem; }
    </style>

    <div class="d-flex flex-wrap align-items-center justify-content-between mt-3 mb-3">
        <div>
            <h4 class="page-title mb-0">Faktur Pembelian</h4>
            <small class="text-muted">Faktur dari permintaan yang disetujui muncul berstatus <strong>Diminta</strong>. Isi saat barang datang, lalu approve agar stok masuk.</small>
        </div>
        <div class="mt-2 mt-md-0">
            <button class="btn btn-outline-success" data-toggle="modal" data-target="#modalExportItems"><i class="fa fa-download"></i> Export Item</button>
            <button type="button" class="btn btn-primary ml-1" id="btnFakturBaru" title="Faktur tanpa permintaan (mis. pembelian langsung)"><i class="fas fa-plus"></i> Faktur Baru</button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <ul class="nav nav-pills fb-tabs mb-3" id="status-tabs">
                <li class="nav-item"><a class="nav-link active" href="#" data-status="">Semua <span class="badge badge-light ml-1" data-count="all">0</span></a></li>
                <li class="nav-item"><a class="nav-link" href="#" data-status="diminta" title="Menunggu barang datang">Diminta <span class="badge badge-warning ml-1" data-count="diminta">0</span></a></li>
                <li class="nav-item"><a class="nav-link" href="#" data-status="diterima" title="Faktur sudah diisi, menunggu approval">Perlu approval <span class="badge badge-info ml-1" data-count="diterima">0</span></a></li>
                <li class="nav-item"><a class="nav-link" href="#" data-status="diapprove">Disetujui <span class="badge badge-light ml-1" data-count="diapprove">0</span></a></li>
                <li class="nav-item"><a class="nav-link" href="#" data-status="diretur">Diretur <span class="badge badge-light ml-1" data-count="diretur">0</span></a></li>
            </ul>

            <div class="form-row align-items-end mb-2">
                <div class="col-md-3 mb-2">
                    <label class="small text-muted mb-1" for="tanggalTerimaRange">Tanggal (terima / minta)</label>
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend"><span class="input-group-text"><i class="fa fa-calendar"></i></span></div>
                        <input type="text" id="tanggalTerimaRange" class="form-control" autocomplete="off" placeholder="Semua tanggal">
                    </div>
                </div>
                <div class="col-md-5 mb-2">
                    <label class="small text-muted mb-1" for="searchFaktur">Cari</label>
                    <input type="search" id="searchFaktur" class="form-control form-control-sm" placeholder="No faktur, no permintaan, pemasok, atau nama obat">
                </div>
                <div class="col-md-2 mb-2">
                    <div class="custom-control custom-switch mb-1">
                        <input type="checkbox" class="custom-control-input" id="jatuhTempoFilter">
                        <label class="custom-control-label small" for="jatuhTempoFilter" title="Faktur disetujui yang jatuh tempo dalam 7 hari atau sudah lewat">Jatuh tempo ≤ 7 hari</label>
                    </div>
                </div>
                <div class="col-md-2 mb-2">
                    <button class="btn btn-light btn-sm btn-block" id="resetFilter"><i class="fas fa-undo"></i> Reset</button>
                </div>
            </div>

            <table class="table table-bordered table-sm w-100" id="fakturbeli-table">
                <thead class="thead-light">
                    <tr>
                        <th>Faktur</th>
                        <th>Obat</th>
                        <th>Tanggal</th>
                        <th class="text-right">Total</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <!-- Modal Export Items -->
    <div class="modal fade" id="modalExportItems" tabindex="-1" role="dialog" aria-labelledby="modalExportItemsLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalExportItemsLabel">Export Item Faktur (disetujui)</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <form id="formExportItems" method="GET" action="{{ route('erm.fakturbeli.items.export') }}">
                        <div class="form-group">
                            <label for="exportTanggalTerimaRange">Tanggal Terima</label>
                            <input type="text" id="exportTanggalTerimaRange" name="tanggal_terima_range" class="form-control" placeholder="Pilih rentang tanggal" autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label for="exportPrincipalId">Principal</label>
                            <select id="exportPrincipalId" name="principal_id" class="form-control">
                                <option value="">Semua Principal</option>
                                @foreach(($principals ?? collect()) as $principal)
                                    <option value="{{ $principal->id }}">{{ $principal->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-0">
                            <label for="exportType">Jenis Export</label>
                            <select id="exportType" name="export_type" class="form-control">
                                <option value="detail">Detail Item Faktur</option>
                                <option value="principal_summary">Rekap Principal per Obat</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-success" id="btnExportItems"><i class="fa fa-download"></i> Download</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalRetur" tabindex="-1" role="dialog" aria-labelledby="modalReturLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalReturLabel">Ajukan Retur ke Supplier</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body" id="modalReturBody"></div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="modalDetailRetur" tabindex="-1" role="dialog" aria-labelledby="modalDetailReturLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalDetailReturLabel">Detail Retur</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body" id="modalDetailReturBody"></div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
@include('erm.fakturretur._actions-js')
@include('erm.fakturbeli._form-modal')
<script>
$(function() {
    var CSRF = '{{ csrf_token() }}';
    var BASE = '{{ url('/erm/fakturpembelian') }}';
    var currentStatus = '';

    @if(session('error'))
        Swal.fire({ icon: 'warning', title: 'Perhatian', text: @json(session('error')) });
    @endif

    function esc(value) {
        return String(value === undefined || value === null ? '' : value).replace(/[&<>"']/g, function(c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    function rupiah(v) { return 'Rp ' + Number(v || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 }); }
    function tgl(v) { return v ? moment(v).format('DD/MM/YYYY') : '-'; }
    function xhrMessage(xhr, fallback) {
        var json = xhr && xhr.responseJSON;
        if (json && json.errors) return Object.values(json.errors).map(function(m) { return Array.isArray(m) ? m.join('\n') : m; }).join('\n');
        return (json && json.message) || fallback;
    }

    var STATUS_BADGE = { diminta: 'badge-warning', diterima: 'badge-info', diapprove: 'badge-success', diretur_sebagian: 'badge-danger', diretur: 'badge-danger' };

    // Values from the server arrive HTML-escaped (Yajra)
    var fakturTable = $('#fakturbeli-table').DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        dom: 'rt<"d-flex flex-wrap justify-content-between align-items-center mt-2"lip>',
        pageLength: 25,
        ajax: {
            url: '{{ route('erm.fakturbeli.index') }}',
            data: function(d) {
                d.status = currentStatus;
                d.tanggal_terima_range = $('#tanggalTerimaRange').val();
                d.q = $('#searchFaktur').val();
                d.jatuh_tempo = $('#jatuhTempoFilter').is(':checked') ? 1 : 0;
            }
        },
        order: [[2, 'desc']],
        createdRow: function(row, data) {
            if (data.status === 'diterima') $(row).addClass('fb-perlu');
            if (data.status === 'diminta') $(row).addClass('fb-diminta');
        },
        columns: [
            {
                data: 'no_faktur', name: 'no_faktur', orderable: false,
                render: function(data, type, row) {
                    var html = '<div class="font-weight-bold">' + (data || '<span class="text-muted">belum ada nomor</span>') + '</div>'
                        + '<div class="small">' + row.pemasok + '</div>';
                    if (row.no_permintaan) html += '<div class="small text-muted">Permintaan ' + row.no_permintaan + '</div>';
                    if (row.replaced_faktur_no) html += '<div class="small"><span class="badge badge-primary">Pengganti</span> asal ' + row.replaced_faktur_no + '</div>';
                    if (row.replacement_invoice_nos && row.replacement_invoice_nos.length) html += '<div class="small text-success">Pengganti: ' + row.replacement_invoice_nos.join(', ') + '</div>';
                    if (row.bukti) html += '<div class="small"><a href="/storage/' + row.bukti + '" target="_blank"><i class="fas fa-paperclip"></i> bukti</a></div>';
                    return html;
                }
            },
            {
                data: 'nama_obat', orderable: false,
                render: function(data, type, row) {
                    return '<div>' + data + '</div><small class="text-muted">' + row.jumlah_item + ' obat</small>';
                }
            },
            {
                data: 'requested_date', name: 'requested_date',
                render: function(data, type, row) {
                    var lines = [];
                    if (row.requested_date) lines.push('<span class="text-muted">Diminta</span> ' + tgl(row.requested_date));
                    if (row.received_date) lines.push('<span class="text-muted">Diterima</span> ' + tgl(row.received_date));
                    if (row.due_date) {
                        var hari = row.jatuh_tempo_hari;
                        var cls = hari === null ? '' : (hari < 0 ? 'text-danger font-weight-bold' : (hari <= 7 ? 'text-warning font-weight-bold' : ''));
                        var note = hari === null ? '' : (hari < 0 ? ' (lewat ' + Math.abs(hari) + ' hari)' : (hari === 0 ? ' (hari ini)' : (hari <= 7 ? ' (' + hari + ' hari lagi)' : '')));
                        lines.push('<span class="text-muted">Jatuh tempo</span> <span class="' + cls + '">' + tgl(row.due_date) + note + '</span>');
                    }
                    return '<div class="small text-nowrap">' + (lines.join('<br>') || '-') + '</div>';
                }
            },
            {
                data: 'total', name: 'total', className: 'text-right text-nowrap',
                render: function(data) { return data ? rupiah(data) : '<span class="text-muted">-</span>'; }
            },
            {
                data: 'status', name: 'status',
                render: function(data, type, row) {
                    var html = '<span class="badge ' + (STATUS_BADGE[data] || 'badge-secondary') + '">' + row.status_label + '</span>';
                    if (data === 'diapprove' && row.approved_by_user_name) html += '<div class="small text-muted">oleh ' + row.approved_by_user_name + (row.approved_at_label ? '<br>' + row.approved_at_label : '') + '</div>';
                    if (row.latest_retur) html += '<div class="small"><a href="#" class="btn-detail-retur-faktur" data-id="' + row.latest_retur.id + '"><i class="fas fa-undo"></i> ' + row.latest_retur.no_retur + '</a> <span class="text-muted">(' + row.latest_retur.status + ')</span></div>';
                    if (row.actions.pengganti) html += '<div class="small text-danger">Menunggu faktur pengganti</div>';
                    return html;
                }
            },
            {
                data: 'actions', orderable: false, className: 'text-right text-nowrap',
                render: function(a, type, row) {
                    var main = '';
                    if (a.input) main = '<button type="button" class="btn btn-sm btn-warning btn-form-faktur" data-id="' + row.id + '"><i class="fas fa-keyboard"></i> Input Faktur</button>';
                    else if (a.approve) main = '<button class="btn btn-sm btn-success btn-approve-faktur" data-id="' + row.id + '"><i class="fas fa-clipboard-check"></i> Tinjau & Approve</button>';
                    else if (a.print) main = '<a href="' + BASE + '/' + row.id + '/print" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fa fa-print"></i> Cetak</a>';

                    var more = [];
                    if (a.edit) more.push('<button type="button" class="btn btn-sm btn-outline-primary btn-form-faktur" data-id="' + row.id + '" title="Edit faktur"><i class="fas fa-pen"></i></button>');
                    if (a.retur) more.push('<button type="button" class="btn btn-sm btn-outline-warning btn-create-retur-faktur" data-id="' + row.id + '" title="Ajukan retur"><i class="fas fa-undo"></i> Retur</button>');
                    if (a.pengganti) more.push('<button type="button" class="btn btn-sm btn-outline-info btn-faktur-pengganti" data-retur="' + a.pengganti + '" title="Buat faktur pengganti"><i class="fas fa-file-medical"></i> Pengganti</button>');
                    if (a.delete) more.push('<button type="button" class="btn btn-sm btn-outline-danger btn-delete-faktur" data-id="' + row.id + '" data-nomor="' + (row.no_faktur || ('#' + row.id)) + '" title="Hapus"><i class="fas fa-trash"></i></button>');

                    var html = main + (more.length ? ' ' + more.join(' ') : '');
                    return html || '<span class="text-muted">-</span>';
                }
            }
        ],
        language: {
            processing: '<i class="fas fa-spinner fa-spin"></i> Memuat...',
            zeroRecords: 'Tidak ada faktur yang cocok',
            emptyTable: 'Belum ada faktur',
            info: '_START_–_END_ dari _TOTAL_ faktur',
            infoEmpty: '0 faktur',
            lengthMenu: 'Tampil _MENU_',
            paginate: { previous: '‹', next: '›' }
        },
        drawCallback: function() {
            var counts = (this.api().ajax.json() || {}).counts || {};
            $('#status-tabs [data-count]').each(function() { $(this).text(counts[$(this).data('count')] || 0); });
        }
    });

    $('#status-tabs').on('click', '.nav-link', function(e) {
        e.preventDefault();
        $('#status-tabs .nav-link').removeClass('active');
        $(this).addClass('active');
        currentStatus = $(this).data('status') || '';
        fakturTable.draw();
    });

    $('#tanggalTerimaRange').daterangepicker({ autoUpdateInput: false, locale: { cancelLabel: 'Hapus', applyLabel: 'Terapkan', format: 'YYYY-MM-DD' } });
    $('#tanggalTerimaRange').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
        fakturTable.draw();
    }).on('cancel.daterangepicker', function() {
        $(this).val('');
        fakturTable.draw();
    });
    $('#jatuhTempoFilter').on('change', function() { fakturTable.draw(); });

    var searchTimer;
    $('#searchFaktur').on('input', function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() { fakturTable.draw(); }, 400);
    });

    $('#resetFilter').on('click', function() {
        $('#tanggalTerimaRange, #searchFaktur').val('');
        $('#jatuhTempoFilter').prop('checked', false);
        $('#status-tabs .nav-link').removeClass('active').first().addClass('active');
        currentStatus = '';
        fakturTable.draw();
    });

    // Export
    $('#exportTanggalTerimaRange').daterangepicker({ autoUpdateInput: false, locale: { cancelLabel: 'Hapus', applyLabel: 'Terapkan', format: 'YYYY-MM-DD' } });
    $('#exportTanggalTerimaRange').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
    }).on('cancel.daterangepicker', function() { $(this).val(''); });
    $('#btnExportItems').on('click', function() {
        if (!$('#exportTanggalTerimaRange').val().trim()) {
            Swal.fire({ icon: 'warning', title: 'Pilih tanggal', text: 'Pilih rentang tanggal terima terlebih dahulu.' });
            return;
        }
        $('#formExportItems').submit();
        $('#modalExportItems').modal('hide');
    });

    // Approve: show what will happen (qty, batch, ED, price vs Master Pembelian, HPP) before confirming
    $('#fakturbeli-table').on('click', '.btn-approve-faktur', function() {
        openApprove($(this).data('id'));
    });

    function openApprove(id) {
        $.get(BASE + '/' + id + '/approve-preview').done(function(p) {
            var rows = p.items.map(function(it) {
                var selisih = it.selisih_persen === null ? '<span class="text-muted">-</span>'
                    : '<span class="' + (it.selisih_besar ? 'text-danger font-weight-bold' : '') + '">' + (it.selisih_persen > 0 ? '+' : '') + it.selisih_persen + '%</span>';
                // Several batches of one obat: show the total next to each batch
                var qty = it.qty
                    + (it.batch_grup > 1 ? ' <small class="text-muted">(total ' + it.qty_grup + ')</small>' : '')
                    + (!it.bonus && it.diminta > it.qty_grup ? ' <small class="text-muted">/ ' + it.diminta + '</small>' : '');
                return '<tr>'
                    + '<td class="text-left">' + esc(it.obat) + (it.bonus ? ' <span class="badge badge-success">Bonus</span>' : '') + '</td>'
                    + '<td>' + qty + '</td>'
                    + '<td class="text-left">' + (it.batch ? esc(it.batch) : '<span class="text-danger">-</span>') + '<br><small>' + (it.expiration_date || '<span class="text-danger">ED -</span>') + '</small></td>'
                    + '<td>' + (it.bonus ? '<span class="text-muted">gratis</span>' : rupiah(it.harga) + '<br><small>' + selisih + '</small>') + '</td>'
                    + '<td>' + (it.bonus ? '<span class="text-muted">tidak berubah</span>' : rupiah(it.hpp_lama) + ' → <strong>' + rupiah(it.hpp_baru) + '</strong>') + '</td>'
                    + '</tr>';
            }).join('');
            var warnings = '';
            if (p.problems.length) {
                warnings += '<div class="alert alert-danger small text-left mb-2">' + p.problems.map(esc).join('<br>') + '<br>Lengkapi lewat <strong>Edit faktur</strong> sebelum approve.</div>';
            }
            if (p.items.some(function(it) { return it.selisih_besar; })) {
                warnings += '<div class="alert alert-warning small text-left mb-2">Ada harga yang berbeda lebih dari ' + p.toleransi_persen + '% dari Master Pembelian. Pastikan sesuai faktur.</div>';
            }
            if (p.sisa > 0) {
                warnings += '<div class="small text-muted text-left mb-2">' + p.sisa + ' obat belum diterima penuh; sisanya otomatis dibuat sebagai faktur lanjutan (status diminta).</div>';
            }
            var html = '<div class="text-left small mb-2">' + esc(p.pemasok) + ' · total <strong>' + rupiah(p.total) + '</strong></div>'
                + warnings
                + '<div class="table-responsive"><table class="table table-bordered fb-approve-table mb-0"><thead class="thead-light"><tr>'
                + '<th class="text-left">Obat</th><th>Qty</th><th class="text-left">Batch / ED</th><th>Harga<br><small>vs master</small></th><th>HPP</th>'
                + '</tr></thead><tbody>' + rows + '</tbody></table></div>'
                + '<div class="small text-muted mt-2 text-left">Stok masuk ke gudang per batch dan harga menjadi harga Master Pembelian berikutnya.</div>';

            Swal.fire({
                title: 'Approve faktur ' + esc(p.no_faktur || '') + '?',
                html: html,
                width: 820,
                showCancelButton: true,
                confirmButtonText: 'Ya, approve & masukkan stok',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#28a745',
                reverseButtons: true,
                onOpen: function() { if (p.problems.length) Swal.getConfirmButton().disabled = true; }
            }).then(function(result) {
                if (!result.value) return;
                $.post(BASE + '/' + id + '/approve', { _token: CSRF }).done(function(res) {
                    fakturTable.draw(false);
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message });
                }).fail(function(xhr) {
                    Swal.fire({ icon: 'error', title: 'Gagal approve', text: xhrMessage(xhr, 'Faktur gagal di-approve.') });
                });
            });
        }).fail(function(xhr) {
            Swal.fire({ icon: 'error', title: 'Gagal', text: xhrMessage(xhr, 'Data faktur tidak dapat dimuat.') });
        });
    }

    // ---------- Faktur form (modal) ----------
    $(document).on('click', '.btn-form-faktur', function(e) {
        e.preventDefault();
        FakturForm.open({ id: $(this).data('id') });
    });
    $(document).on('click', '.btn-faktur-pengganti', function(e) {
        e.preventDefault();
        $('#modalDetailRetur').modal('hide');
        FakturForm.open({ pengganti: $(this).data('retur') });
    });
    $('#btnFakturBaru').on('click', function() { FakturForm.open({}); });

    // Saved: offer to review and approve right away (stok enters on approval)
    FakturForm.onSaved(function(res) {
        fakturTable.draw(false);
        Swal.fire({
            icon: 'success',
            title: 'Faktur tersimpan',
            text: res.message,
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-clipboard-check"></i> Tinjau & approve sekarang',
            cancelButtonText: 'Nanti',
            reverseButtons: true
        }).then(function(result) {
            if (result.value && res.id) openApprove(res.id);
        });
    });

    // Old links (/create, /edit, retur "faktur pengganti") land here with the modal to open
    (function() {
        var params = new URLSearchParams(window.location.search);
        if (params.get('input')) FakturForm.open({ id: params.get('input') });
        else if (params.get('pengganti')) FakturForm.open({ pengganti: params.get('pengganti') });
        else if (params.get('baru')) FakturForm.open({});
        if (params.toString()) window.history.replaceState({}, '', window.location.pathname);
    })();

    $('#fakturbeli-table').on('click', '.btn-delete-faktur', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        Swal.fire({
            icon: 'warning',
            title: 'Hapus faktur ' + esc($(this).data('nomor')) + '?',
            text: 'Faktur dan itemnya dihapus permanen. Stok tidak terpengaruh karena faktur ini belum di-approve.',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#d33',
            reverseButtons: true,
            focusCancel: true
        }).then(function(result) {
            if (!result.value) return;
            $.ajax({ url: BASE + '/' + id, type: 'DELETE', data: { _token: CSRF } }).done(function(res) {
                fakturTable.draw(false);
                Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 2000, showConfirmButton: false });
            }).fail(function(xhr) {
                Swal.fire({ icon: 'error', title: 'Gagal', text: xhrMessage(xhr, 'Faktur gagal dihapus.') });
            });
        });
    });

    // Retur ke supplier from a faktur
    $('#fakturbeli-table').on('click', '.btn-create-retur-faktur', function(e) {
        e.preventDefault();
        $.get('{{ route('erm.fakturretur.create') }}', { fakturbeli_id: $(this).data('id') }).done(function(res) {
            $('#modalReturBody').html(res);
            $('#modalRetur').modal('show');
        }).fail(function(xhr) {
            Swal.fire({ icon: 'error', title: 'Gagal', text: xhrMessage(xhr, 'Form retur tidak dapat dimuat.') });
        });
    });

    $('#fakturbeli-table').on('click', '.btn-detail-retur-faktur', function(e) {
        e.preventDefault();
        $.get('{{ url('/erm/fakturretur') }}/' + $(this).data('id')).done(function(res) {
            $('#modalDetailReturBody').html(res);
            $('#modalDetailRetur').modal('show');
        }).fail(function(xhr) {
            Swal.fire({ icon: 'error', title: 'Gagal', text: xhrMessage(xhr, 'Detail retur tidak dapat dimuat.') });
        });
    });

    ReturActions.init(function() { fakturTable.draw(false); });
});
</script>
@endpush
