@extends('layouts.erm.app')

@section('title', 'ERM | Jasa Medis')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection

@section('content')
<style>
    #jmTable td { vertical-align: middle; }
    #jmTable .jm-nama { font-weight: 600; }
    #jmTable tr.jm-off td { opacity: .6; }
    #jmTable input.jm-input { max-width: 150px; text-align: right; }
    #jmTable input.jm-ket { min-width: 160px; }
    .jm-filter .btn { font-weight: 600; }
</style>
<div class="container-fluid">
    <div class="d-flex flex-wrap align-items-center justify-content-between mt-3 mb-3" style="gap:8px">
        <div>
            <h4 class="page-title mb-0">Jasa Medis</h4>
            <small class="text-muted">Tarif jasa medis per <strong>tindakan</strong>: satu tarif untuk dokter, satu untuk petugas (perawat / beautician). Saat mengubah tarif, pilih tanggal mulai berlaku: SPK sebelum tanggal itu tidak berubah. Bila dikerjakan lebih dari satu orang, tarif dibagi rata.</small>
        </div>
        <div class="d-flex flex-wrap" style="gap:6px">
            <a href="{{ route('erm.jasa-medis.rekap') }}" class="btn btn-light btn-sm"><i class="fas fa-chart-bar"></i> Rekap Jasa Medis</a>
            <button type="button" class="btn btn-light btn-sm" id="btnImport"><i class="fas fa-file-import"></i> Import CSV</button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center mb-2" style="gap:8px">
                <div class="input-group input-group-sm" style="max-width:320px">
                    <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search"></i></span></div>
                    <input type="search" id="jmSearch" class="form-control" placeholder="Cari nama atau kode tindakan...">
                </div>
                <div class="btn-group btn-group-sm jm-filter" role="group" id="jmFilter">
                    <button type="button" class="btn btn-outline-primary active" data-filter="">Semua <span class="badge badge-light" data-count="semua">0</span></button>
                    <button type="button" class="btn btn-outline-warning" data-filter="belum">Belum ada tarif <span class="badge badge-light" data-count="belum">0</span></button>
                    <button type="button" class="btn btn-outline-primary" data-filter="ada">Sudah ada tarif <span class="badge badge-light" data-count="ada">0</span></button>
                </div>
                <div class="custom-control custom-checkbox ml-1">
                    <input type="checkbox" class="custom-control-input" id="jmAktif" checked>
                    <label class="custom-control-label" for="jmAktif">Hanya tindakan aktif</label>
                </div>
                <div class="custom-control custom-checkbox ml-1">
                    <input type="checkbox" class="custom-control-input" id="jmPaket">
                    <label class="custom-control-label" for="jmPaket">Hanya yang ada di paket aktif</label>
                </div>
            </div>

            <table id="jmTable" class="table table-sm table-hover w-100">
                <thead class="thead-light">
                    <tr>
                        <th>Tindakan</th>
                        <th class="text-center">Paket aktif</th>
                        <th>Tarif Dokter</th>
                        <th>Tarif Petugas</th>
                        <th>Keterangan</th>
                        <th>Diubah</th>
                        <th class="text-right" style="width:1%">Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="importModal" tabindex="-1" role="dialog" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form class="modal-content" id="importForm">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">Import Tarif Jasa Medis</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group mb-2">
                    <label for="jmCsv">File CSV</label>
                    <input type="file" id="jmCsv" name="csv" accept=".csv,text/csv" class="form-control" required>
                </div>
                <div class="form-group mb-2">
                    <label for="jmImportBerlaku">Berlaku mulai tanggal</label>
                    <input type="date" id="jmImportBerlaku" name="berlaku_mulai" class="form-control" max="{{ now()->toDateString() }}" value="{{ now()->toDateString() }}" required>
                    <small class="form-text text-muted">SPK dengan tanggal tindakan sejak tanggal ini memakai tarif dari file. SPK sebelumnya tidak berubah.</small>
                </div>
                <small class="text-muted">
                    Kolom: <code>nama atau kode tindakan</code>, <code>tarif dokter</code>, <code>tarif petugas</code>, <code>keterangan</code> (opsional). Header opsional, pemisah koma atau titik koma.
                    Contoh: <code>Facial Acne;50000;20000</code>
                </small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Import</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="riwayatModal" tabindex="-1" role="dialog" aria-labelledby="riwayatModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="riwayatModalLabel">Riwayat Tarif</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <table class="table table-sm table-bordered mb-0" id="riwayatTable">
                    <thead class="thead-light">
                        <tr>
                            <th>Diubah</th>
                            <th>Berlaku mulai</th>
                            <th class="text-right">Tarif Dokter</th>
                            <th class="text-right">Tarif Petugas</th>
                            <th>Keterangan</th>
                            <th>Sumber</th>
                            <th class="text-center">SPK ikut berubah</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function () {
    var state = { filter: '' };

    function esc(v) { return $('<div>').text(v == null ? '' : String(v)).html(); }
    function num(v) { return Number(v || 0); }
    function rupiah(n) { return 'Rp' + Number(n || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 }); }

    // Filters run on the loaded rows (all tindakan come in one request)
    $.fn.dataTable.ext.search.push(function (settings, data, i, row) {
        if (settings.nTable.id !== 'jmTable') return true;
        if ($('#jmAktif').is(':checked') && !row.is_active) return false;
        if ($('#jmPaket').is(':checked') && !row.paket_aktif) return false;
        if (state.filter === 'belum' && row.ada_tarif) return false;
        if (state.filter === 'ada' && !row.ada_tarif) return false;
        return true;
    });

    function inputCell(field, step) {
        return function (d, t, r) {
            if (t !== 'display') return d;
            return '<input type="number" min="0" step="' + step + '" class="form-control form-control-sm jm-input" data-field="' + field + '" value="' + num(d) + '">';
        };
    }

    var table = $('#jmTable').DataTable({
        dom: 'rt<"d-flex flex-wrap justify-content-between align-items-center mt-2"lip>',
        order: [],
        pageLength: 50,
        ajax: { url: '{{ route('erm.jasa-medis.data') }}', dataSrc: function (json) { updateCounts(json.data); return json.data; } },
        language: {
            processing: '<i class="fas fa-spinner fa-spin"></i> Memuat...',
            zeroRecords: 'Tidak ada tindakan yang cocok', emptyTable: 'Belum ada tindakan',
            info: '_START_–_END_ dari _TOTAL_ tindakan', infoEmpty: '0 tindakan', infoFiltered: '',
            lengthMenu: 'Tampil _MENU_', paginate: { previous: '‹', next: '›' }
        },
        columns: [
            {
                data: 'nama', render: function (d, t, r) {
                    if (t !== 'display') return d + ' ' + (r.kode || '');
                    return '<div class="jm-nama">' + esc(d) + '</div>'
                        + '<div class="small text-muted">' + esc(r.kode || '') + (r.is_active ? '' : ' <span class="badge badge-secondary">nonaktif</span>') + '</div>';
                }
            },
            { data: 'paket_aktif', className: 'text-center', render: function (d, t) { return t === 'display' ? (d ? '<span class="badge badge-info">' + d + '</span>' : '<span class="small text-muted">-</span>') : d; } },
            { data: 'tarif_dokter', render: inputCell('tarif_dokter', 1000) },
            { data: 'tarif_employee', render: inputCell('tarif_employee', 1000) },
            {
                data: 'keterangan', render: function (d, t) {
                    return t === 'display' ? '<input type="text" maxlength="255" class="form-control form-control-sm jm-ket" data-field="keterangan" value="' + esc(d || '') + '">' : (d || '');
                }
            },
            { data: 'updated_at', render: function (d, t, r) { return d ? '<div class="small">' + esc(d) + '</div><div class="small text-muted">' + esc(r.updated_by || '') + '</div>' : '<span class="small text-muted">-</span>'; } },
            {
                data: 'id', orderable: false, className: 'text-right text-nowrap',
                render: function (id) {
                    return '<button type="button" class="btn btn-sm btn-outline-success btn-save" data-id="' + id + '" disabled><i class="fas fa-save"></i> Simpan</button>'
                        + ' <button type="button" class="btn btn-sm btn-outline-secondary btn-riwayat" data-id="' + id + '" title="Riwayat perubahan tarif"><i class="fas fa-history"></i> Riwayat</button>';
                }
            }
        ],
        createdRow: function (tr, r) { $(tr).toggleClass('jm-off', !r.is_active); }
    });

    function updateCounts(rows) {
        var aktif = $('#jmAktif').is(':checked'), paket = $('#jmPaket').is(':checked');
        var base = (rows || table.rows().data().toArray()).filter(function (r) { return (!aktif || r.is_active) && (!paket || r.paket_aktif); });
        $('#jmFilter [data-count="semua"]').text(base.length);
        $('#jmFilter [data-count="belum"]').text(base.filter(function (r) { return !r.ada_tarif; }).length);
        $('#jmFilter [data-count="ada"]').text(base.filter(function (r) { return r.ada_tarif; }).length);
    }

    $('#jmFilter').on('click', '.btn', function () {
        state.filter = $(this).data('filter') || '';
        $('#jmFilter .btn').removeClass('active');
        $(this).addClass('active');
        table.draw();
    });
    $('#jmAktif, #jmPaket').on('change', function () { updateCounts(); table.draw(); });
    var searchTimer;
    $('#jmSearch').on('input', function () {
        var v = this.value;
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () { table.search(v).draw(); }, 300);
    });

    // Row edits: Simpan turns on once a value changes; Enter saves
    $('#jmTable').on('input', 'input', function () { $(this).closest('tr').find('.btn-save').prop('disabled', false); });
    $('#jmTable').on('keydown', 'input', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); $(this).closest('tr').find('.btn-save').trigger('click'); }
    });
    $('#jmTable').on('click', '.btn-save', function () {
        var $btn = $(this), $tr = $btn.closest('tr'), row = table.row($tr);
        var payload = {
            tarif_dokter: $tr.find('[data-field="tarif_dokter"]').val() || 0,
            tarif_employee: $tr.find('[data-field="tarif_employee"]').val() || 0,
            keterangan: $tr.find('[data-field="keterangan"]').val()
        };
        var today = moment().format('YYYY-MM-DD');
        // Ask from which tanggal tindakan the new tarif applies (max today)
        Swal.fire({
            title: 'Tarif berlaku mulai',
            html: '<div class="text-left small mb-2">' + esc(row.data().nama) + ': dokter ' + rupiah(payload.tarif_dokter) + ', petugas ' + rupiah(payload.tarif_employee) + '</div>'
                + '<input type="date" id="jmBerlaku" class="form-control" max="' + today + '" value="' + today + '">'
                + '<div class="text-left small text-muted mt-2">SPK dengan tanggal tindakan sejak tanggal ini memakai tarif baru. SPK sebelumnya tidak berubah.</div>',
            showCancelButton: true, confirmButtonText: 'Simpan', cancelButtonText: 'Batal',
            preConfirm: function () {
                var v = $('#jmBerlaku').val();
                if (!v) { Swal.showValidationMessage('Isi tanggal berlaku'); return false; }
                if (v > today) { Swal.showValidationMessage('Tanggal berlaku tidak boleh di masa depan'); return false; }
                return v;
            }
        }).then(function (res) {
            if (!res.isConfirmed) return;
            payload.berlaku_mulai = res.value;
            $btn.prop('disabled', true);
            $.post('{{ url('erm/jasa-medis') }}/' + $btn.data('id'), payload)
                .done(function (out) {
                    // The table shows the tarif in force today (a backdated change may be overridden by a newer one)
                    var d = row.data();
                    if (out.row) {
                        d.tarif_dokter = num(out.row.tarif_dokter);
                        d.tarif_employee = num(out.row.tarif_employee);
                        d.keterangan = out.row.keterangan;
                    }
                    d.ada_tarif = d.tarif_dokter > 0 || d.tarif_employee > 0;
                    d.updated_at = moment().format('DD MMM YYYY');
                    d.updated_by = @json(auth()->user()->name);
                    row.data(d).invalidate();
                    table.draw(false);
                    updateCounts();
                    Swal.fire({ icon: 'success', title: out.message, text: out.spk_diperbarui ? out.spk_diperbarui + ' baris SPK ikut diperbarui.' : '', timer: 2200, showConfirmButton: false });
                })
                .fail(function (xhr) {
                    $btn.prop('disabled', false);
                    Swal.fire({ icon: 'error', title: 'Gagal menyimpan', html: belovaAjaxError(xhr) });
                });
        });
    });

    // ================= Riwayat tarif =================
    function perubahan(lama, baru) {
        if (lama === null || lama === undefined) return rupiah(baru);
        if (Number(lama) === Number(baru)) return '<span class="text-muted">' + rupiah(baru) + '</span>';
        return '<span class="text-muted">' + rupiah(lama) + '</span> → <strong>' + rupiah(baru) + '</strong>';
    }
    var SUMBER = { manual: 'Diubah', import: 'Import CSV', awal: 'Tarif awal' };
    $('#jmTable').on('click', '.btn-riwayat', function () {
        var $btn = $(this).prop('disabled', true);
        $.get('{{ url('erm/jasa-medis') }}/' + $btn.data('id') + '/riwayat')
            .done(function (res) {
                $('#riwayatModalLabel').text('Riwayat Tarif · ' + res.nama);
                $('#riwayatTable tbody').html(res.data.length ? res.data.map(function (r) {
                    return '<tr><td><div>' + esc(r.waktu) + '</div><div class="small text-muted">' + esc(r.user) + '</div></td>'
                        + '<td>' + (r.berlaku_mulai ? moment(r.berlaku_mulai).format('DD MMM YYYY') : '<span class="text-muted">sejak awal</span>') + '</td>'
                        + '<td class="text-right">' + perubahan(r.tarif_dokter_lama, r.tarif_dokter) + '</td>'
                        + '<td class="text-right">' + perubahan(r.tarif_petugas_lama, r.tarif_petugas) + '</td>'
                        + '<td>' + esc(r.keterangan || '') + '</td>'
                        + '<td>' + esc(SUMBER[r.sumber] || r.sumber) + '</td>'
                        + '<td class="text-center">' + r.spk_diperbarui + '</td></tr>';
                }).join('') : '<tr><td colspan="7" class="text-center text-muted">Belum ada perubahan tarif</td></tr>');
                $('#riwayatModal').modal('show');
            })
            .fail(function (xhr) { Swal.fire({ icon: 'error', title: 'Gagal memuat riwayat', html: belovaAjaxError(xhr) }); })
            .always(function () { $btn.prop('disabled', false); });
    });

    // ================= Import CSV =================
    $('#btnImport').on('click', function () { $('#importForm')[0].reset(); $('#importModal').modal('show'); });
    $('#importForm').on('submit', function (e) {
        e.preventDefault();
        var fd = new FormData(this);
        var $btn = $(this).find('[type="submit"]').prop('disabled', true);
        $.ajax({ url: '{{ route('erm.jasa-medis.import') }}', method: 'POST', data: fd, processData: false, contentType: false })
            .done(function (res) {
                $('#importModal').modal('hide');
                table.ajax.reload();
                var html = '<b>' + res.saved + '</b> tarif diubah' + (res.spk_diperbarui ? ', <b>' + res.spk_diperbarui + '</b> baris SPK ikut diperbarui.' : '.');
                if (res.errors.length) html += '<div class="text-left small mt-2" style="max-height:200px;overflow:auto">' + res.errors.map(esc).join('<br>') + '</div>';
                Swal.fire({ icon: res.errors.length ? 'warning' : 'success', title: 'Import selesai', html: html });
            })
            .fail(function (xhr) { Swal.fire({ icon: 'error', title: 'Import gagal', html: belovaAjaxError(xhr) }); })
            .always(function () { $btn.prop('disabled', false); });
    });
});
</script>
@endsection
