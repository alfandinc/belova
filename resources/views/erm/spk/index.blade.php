@extends('layouts.erm.app')

@section('title', 'ERM | SPK Tindakan')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection

@section('content')
<style>
    #spkTable td { vertical-align: middle; }
    #spkTable .spk-pasien { font-weight: 600; }
    #spkTable .spk-klinik-logo { height: 24px; max-width: 60px; object-fit: contain; flex: 0 0 auto; }
    .spk-filter .btn { font-weight: 600; }
    #spkPetugasTable td { vertical-align: top; }
    #spkPetugasTable .select2-container { width: 100% !important; }
</style>
<div class="container-fluid">
    <div class="d-flex flex-wrap align-items-center justify-content-between mt-3 mb-3" style="gap:8px">
        <div>
            <h4 class="page-title mb-0">SPK Tindakan</h4>
            <small class="text-muted">Diisi di akhir shift, satu baris per kunjungan: <strong>siapa yang mengerjakan</strong> tiap tindakan. Petugas menjadi dasar rekap jasa medis.</small>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center mb-2" style="gap:8px">
                <div class="input-group input-group-sm" style="width:auto">
                    <div class="input-group-prepend"><span class="input-group-text">Tanggal</span></div>
                    <input type="date" id="spkFrom" class="form-control" value="{{ now()->toDateString() }}">
                    <div class="input-group-prepend input-group-append"><span class="input-group-text">s/d</span></div>
                    <input type="date" id="spkTo" class="form-control" value="{{ now()->toDateString() }}">
                </div>
                <select id="spkKlinik" class="custom-select custom-select-sm" style="width:auto">
                    <option value="">Semua klinik</option>
                    @foreach($kliniks as $k)
                        <option value="{{ $k->id }}">{{ $k->nama }}</option>
                    @endforeach
                </select>
                <div class="input-group input-group-sm" style="max-width:260px">
                    <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search"></i></span></div>
                    <input type="search" id="spkSearch" class="form-control" placeholder="Cari pasien, paket, dokter...">
                </div>
                <div class="btn-group btn-group-sm spk-filter" role="group" id="spkFilter">
                    <button type="button" class="btn btn-outline-primary active" data-petugas="">Semua <span class="badge badge-light" data-count="semua">0</span></button>
                    <button type="button" class="btn btn-outline-warning" data-petugas="belum">Petugas belum diisi <span class="badge badge-light" data-count="belum">0</span></button>
                    <button type="button" class="btn btn-outline-success" data-petugas="lengkap">Sudah diisi <span class="badge badge-light" data-count="lengkap">0</span></button>
                </div>
            </div>

            <table id="spkTable" class="table table-sm table-hover w-100">
                <thead class="thead-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>Pasien</th>
                        <th>Paket Tindakan</th>
                        <th>Dokter</th>
                        <th>Petugas</th>
                        <th>Status Invoice</th>
                        <th class="text-right" style="width:1%">Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<!-- Isi SPK; only the X or Batal close it -->
<div class="modal fade" id="spkModal" tabindex="-1" role="dialog" aria-labelledby="spkModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <form class="modal-content" id="spkForm" novalidate>
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="spkModalLabel">SPK Tindakan</h5>
                    <div class="small text-muted" id="spkInfo"></div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="spkTanggal">Tanggal Tindakan <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="spkTanggal" name="tanggal_tindakan" required>
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center justify-content-between mb-2" style="gap:8px">
                    <h6 class="mb-0">Petugas per tindakan <span class="text-danger">*</span></h6>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="spkCopyFirst" title="Salin petugas tindakan pertama ke semua tindakan"><i class="fas fa-copy"></i> Salin baris pertama ke semua</button>
                </div>
                <table class="table table-sm table-bordered mb-1" id="spkPetugasTable">
                    <thead class="thead-light">
                        <tr>
                            <th style="width:28%">Tindakan</th>
                            <th style="width:36%">Dokter</th>
                            <th style="width:36%">Petugas <small class="text-muted">(perawat / beautician)</small></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <small class="text-muted">Setiap tindakan wajib punya minimal satu yang mengerjakan: dokter, petugas, atau keduanya.</small>
            </div>
            <div class="modal-footer">
                <small class="text-muted mr-auto" id="spkUpdated"></small>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success" id="spkSave"><i class="fas fa-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function () {
    var DOKTERS = @json($dokters);
    var EMPLOYEES = @json($employees);
    var state = { petugas: '', visitId: null };

    function esc(v) { return $('<div>').text(v == null ? '' : String(v)).html(); }
    function tgl(d) { return d ? moment(d).format('DD MMM YYYY') : '-'; }
    function invoiceBadge(status, label) {
        var cls = { paid: 'success', partial: 'warning', issued: 'secondary' }[status] || 'light';
        return '<span class="badge badge-' + cls + '">' + esc(label) + '</span>';
    }

    var table = $('#spkTable').DataTable({
        dom: 'rt<"d-flex flex-wrap justify-content-between align-items-center mt-2"lip>',
        order: [],
        pageLength: 25,
        ajax: {
            url: '{{ route('erm.spk-tindakan.data') }}',
            data: function (d) {
                d.from = $('#spkFrom').val();
                d.to = $('#spkTo').val();
                d.klinik_id = $('#spkKlinik').val();
                d.petugas = state.petugas;
            },
            dataSrc: function (json) {
                $.each(json.counts || {}, function (k, v) { $('#spkFilter [data-count="' + k + '"]').text(v); });
                return json.data;
            },
            error: function (xhr) { Swal.fire({ icon: 'error', title: 'Gagal memuat', html: belovaAjaxError(xhr) }); }
        },
        language: {
            processing: '<i class="fas fa-spinner fa-spin"></i> Memuat...',
            zeroRecords: 'Tidak ada tindakan yang cocok', emptyTable: 'Tidak ada tindakan pada tanggal ini',
            info: '_START_–_END_ dari _TOTAL_ tindakan', infoEmpty: '0 tindakan', infoFiltered: '',
            lengthMenu: 'Tampil _MENU_', paginate: { previous: '‹', next: '›' }
        },
        columns: [
            {
                data: 'tanggal', render: function (d, t, r) {
                    if (t !== 'display') return d;
                    // Klinik as its logo (name in the tooltip); the name only when the klinik has no logo
                    var klinik = r.klinik_logo_url
                        ? '<img src="' + esc(r.klinik_logo_url) + '" alt="' + esc(r.klinik) + '" title="' + esc(r.klinik) + '" class="spk-klinik-logo mr-2">'
                        : (r.klinik ? '<span class="small text-muted mr-2" title="' + esc(r.klinik) + '">' + esc(r.klinik) + '</span>' : '');
                    return '<div class="d-flex align-items-center">' + klinik + '<span class="text-nowrap">' + tgl(d) + '</span></div>';
                }
            },
            { data: 'pasien_nama', render: function (d, t, r) { return '<div class="spk-pasien">' + esc(d) + '</div><div class="small text-muted">RM ' + esc(r.pasien_id) + '</div>'; } },
            {
                data: 'paket', render: function (d, t, r) {
                    if (t !== 'display') return d.join(' ');
                    return '<ul class="mb-0 pl-3">' + d.map(function (p) { return '<li>' + esc(p) + '</li>'; }).join('') + '</ul>';
                }
            },
            { data: 'dokter', render: function (d) { return esc(d || '-'); } },
            {
                data: 'terisi', render: function (d, t, r) {
                    return '<span class="badge badge-' + (r.petugas_lengkap ? 'success' : (d > 0 ? 'warning' : 'light')) + '">' + d + ' / ' + r.isi + ' tindakan</span>';
                }
            },
            { data: 'invoice', render: function (d, t, r) { return t === 'display' ? invoiceBadge(r.invoice_status, d) : d; } },
            {
                data: 'visitation_id', orderable: false, className: 'text-right text-nowrap',
                render: function (id) { return '<button type="button" class="btn btn-sm btn-primary btn-isi" data-id="' + esc(id) + '"><i class="fas fa-user-md"></i> Isi SPK</button>'; }
            }
        ]
    });

    function reload() { table.ajax.reload(); }
    $('#spkFrom, #spkTo, #spkKlinik').on('change', reload);
    $('#spkFilter').on('click', '.btn', function () {
        state.petugas = $(this).data('petugas') || '';
        $('#spkFilter .btn').removeClass('active');
        $(this).addClass('active');
        reload();
    });
    var searchTimer;
    $('#spkSearch').on('input', function () {
        var v = this.value;
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () { table.search(v).draw(); }, 300);
    });

    // ================= Isi SPK =================
    function optionsHtml(list, selected) {
        var ids = (selected || []).map(function (p) { return String(p.id); });
        var html = list.map(function (o) { return '<option value="' + o.id + '"' + (ids.indexOf(String(o.id)) >= 0 ? ' selected' : '') + '>' + esc(o.nama) + '</option>'; }).join('');
        // Petugas already saved but no longer in the list (nonaktif) stay selectable
        (selected || []).forEach(function (p) {
            if (!list.some(function (o) { return String(o.id) === String(p.id); })) html += '<option value="' + p.id + '" selected>' + esc(p.nama) + ' (nonaktif)</option>';
        });
        return html;
    }

    // One form per visit: a header row per paket, then a row per tindakan of that paket
    function renderForm(f) {
        $('#spkModalLabel').text('SPK · ' + f.pasien_nama);
        $('#spkInfo').html('RM ' + esc(f.pasien_id) + (f.klinik ? ' · ' + esc(f.klinik) : '')
            + (f.dokter_pj ? ' · Dokter PJ: ' + esc(f.dokter_pj.nama) : '') + ' · Invoice: ' + esc(f.invoice));
        $('#spkTanggal').val(f.tanggal_tindakan || moment().format('YYYY-MM-DD'));
        $('#spkUpdated').text(f.updated_at ? 'Terakhir diubah ' + f.updated_at : '');

        var $body = $('#spkPetugasTable tbody').empty();
        var rowCount = 0;
        f.pakets.forEach(function (p) {
            $body.append('<tr class="table-active"><td colspan="3" class="font-weight-bold"><i class="fas fa-layer-group mr-1"></i>' + esc(p.paket) + '</td></tr>');
            if (!p.tindakans.length) {
                $body.append('<tr><td colspan="3" class="text-center text-warning">Paket ini belum berisi tindakan, petugas tidak bisa diisi.</td></tr>');
            }
            p.tindakans.forEach(function (k) {
                // New SPK: the dokter penanggung jawab is the default dokter of every tindakan
                var dokters = (!p.has_petugas && !k.dokter.length && f.dokter_pj) ? [f.dokter_pj] : k.dokter;
                var $tr = $('<tr>').attr('data-riwayat', p.riwayat_id).attr('data-kode', k.id);
                $tr.append('<td class="pl-4"><div class="font-weight-bold spk-nama">' + esc(k.nama) + '</div></td>');
                $tr.append('<td><select multiple data-tipe="dokter">' + optionsHtml(DOKTERS, dokters) + '</select></td>');
                $tr.append('<td><select multiple data-tipe="employee">' + optionsHtml(EMPLOYEES, k.employee) + '</select></td>');
                $body.append($tr);
                $tr.find('select').each(function () {
                    $(this).select2({ dropdownParent: $('#spkModal'), width: '100%', placeholder: $(this).data('tipe') === 'dokter' ? 'Pilih dokter' : 'Pilih petugas' });
                });
                rowCount++;
            });
        });
        $('#spkCopyFirst').toggle(rowCount > 1);
        $('#spkSave').prop('disabled', !rowCount);
    }

    $('#spkTable').on('click', '.btn-isi', function () {
        var $btn = $(this).prop('disabled', true);
        // attr, not data(): visit ids are 21-digit strings that data() would turn into an imprecise number
        state.visitId = $btn.attr('data-id');
        $.get('{{ url('erm/spk-tindakan') }}/' + state.visitId)
            .done(function (f) { renderForm(f); $('#spkModal').modal('show'); })
            .fail(function (xhr) { Swal.fire({ icon: 'error', title: 'Gagal memuat SPK', html: belovaAjaxError(xhr) }); })
            .always(function () { $btn.prop('disabled', false); });
    });

    $('#spkCopyFirst').on('click', function () {
        var $rows = $('#spkPetugasTable tbody tr[data-kode]');
        var $first = $rows.first();
        $rows.slice(1).each(function () {
            var $tr = $(this);
            ['dokter', 'employee'].forEach(function (tipe) {
                var $src = $first.find('select[data-tipe="' + tipe + '"]');
                var $dst = $tr.find('select[data-tipe="' + tipe + '"]');
                // Copy options that only exist in the first row (nonaktif petugas) before selecting them
                $src.find('option:selected').each(function () {
                    if (!$dst.find('option[value="' + this.value + '"]').length) $dst.append($(this).clone());
                });
                $dst.val($src.val()).trigger('change');
            });
        });
    });

    $('#spkForm').on('submit', function (e) {
        e.preventDefault();
        var payload = { tanggal_tindakan: $('#spkTanggal').val(), petugas: {} };
        var kosong = [];
        // petugas[riwayat][kode tindakan] = { dokter: [], employee: [] }
        $('#spkPetugasTable tbody tr[data-kode]').each(function () {
            var $tr = $(this);
            var p = {
                dokter: $tr.find('select[data-tipe="dokter"]').val() || [],
                employee: $tr.find('select[data-tipe="employee"]').val() || []
            };
            if (!p.dokter.length && !p.employee.length) kosong.push($tr.find('.spk-nama').text());
            var riwayat = $tr.attr('data-riwayat');
            payload.petugas[riwayat] = payload.petugas[riwayat] || {};
            payload.petugas[riwayat][$tr.attr('data-kode')] = p;
        });
        if (kosong.length) {
            Swal.fire({ icon: 'warning', title: 'Petugas belum lengkap', html: 'Isi petugas untuk: <b>' + kosong.map(esc).join(', ') + '</b>' });
            return;
        }
        var $save = $('#spkSave').prop('disabled', true);
        $.ajax({ url: '{{ url('erm/spk-tindakan') }}/' + state.visitId, method: 'POST', data: payload })
            .done(function (res) {
                $('#spkModal').modal('hide');
                Swal.fire({ icon: 'success', title: res.message, timer: 1400, showConfirmButton: false });
                table.ajax.reload(null, false);
            })
            .fail(function (xhr) { Swal.fire({ icon: 'error', title: 'Gagal menyimpan', html: belovaAjaxError(xhr) }); })
            .always(function () { $save.prop('disabled', false); });
    });

    $('#spkModal').on('hidden.bs.modal', function () {
        $('#spkPetugasTable select').each(function () { if ($(this).data('select2')) $(this).select2('destroy'); });
    });
});
</script>
@endsection
