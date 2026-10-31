@extends('layouts.hrd.app')
@section('title', 'HRD | Pengajuan Tidak Masuk (Sakit/Izin)')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@php
    $tabs = [];
    if ($hasEmployeeProfile) $tabs['panePersonal'] = ['label' => 'Pengajuan Saya', 'icon' => 'fa-user', 'badge' => 0, 'badgeId' => null];
    if ($canApproveTeam) $tabs['paneTeam'] = ['label' => 'Persetujuan Tim', 'icon' => 'fa-users', 'badge' => $teamPending, 'badgeId' => 'badgeTeamPending'];
    if ($isHrd) $tabs['paneApproval'] = ['label' => 'Persetujuan HRD', 'icon' => 'fa-user-check', 'badge' => $hrdPending, 'badgeId' => 'badgeHrdPending'];
    $preferredPane = ['personal' => 'panePersonal', 'team' => 'paneTeam', 'approval' => 'paneApproval'][request('view')] ?? null;
@endphp

@section('content')
<div class="container-fluid px-2">
    <div class="row mb-2">
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h3 class="mb-0 font-weight-bold">Pengajuan Tidak Masuk</h3>
                <div class="text-muted small">Kelola pengajuan sakit dan izin karyawan</div>
            </div>
            <div class="d-flex align-items-center pengajuan-toolbar">
                <input type="text" id="dateRangeFilter" class="form-control form-control-sm mr-2" placeholder="Filter tanggal" title="Filter tanggal tidak masuk" readonly />
                @if($hasEmployeeProfile)
                <button type="button" class="btn btn-sm btn-primary text-nowrap" id="btnCreateTidakMasuk">
                    <i class="fas fa-plus-circle mr-1"></i>Ajukan Tidak Masuk
                </button>
                @endif
            </div>
        </div>
    </div>

    @if(empty($tabs))
        <div class="alert alert-info">Akun Anda belum terhubung dengan data karyawan, sehingga belum ada pengajuan yang dapat ditampilkan.</div>
    @else
    <div class="card">
        <div class="card-body p-2">
            <ul class="nav nav-tabs pengajuan-tabs mb-2 {{ count($tabs) < 2 ? 'd-none' : '' }}" role="tablist">
                @foreach($tabs as $paneId => $tab)
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#{{ $paneId }}" role="tab">
                        <i class="fas {{ $tab['icon'] }} mr-1"></i>{{ $tab['label'] }}
                        @if($tab['badge'] > 0)<span class="badge badge-pill badge-danger ml-1" id="{{ $tab['badgeId'] }}">{{ $tab['badge'] }}</span>@endif
                    </a>
                </li>
                @endforeach
            </ul>

            <div class="tab-content">
                @if($hasEmployeeProfile)
                <div class="tab-pane fade" id="panePersonal" role="tabpanel">
                    <table id="tableTidakMasukPersonal" class="table table-bordered table-hover w-100">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Alasan</th>
                                <th>Catatan</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                @endif

                @if($canApproveTeam)
                <div class="tab-pane fade" id="paneTeam" role="tabpanel">
                    <table id="tableTidakMasukTeam" class="table table-bordered table-hover w-100">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Karyawan</th>
                                <th>Tanggal</th>
                                <th>Alasan</th>
                                <th>Catatan</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                @endif

                @if($isHrd)
                <div class="tab-pane fade" id="paneApproval" role="tabpanel">
                    <table id="tableTidakMasukApproval" class="table table-bordered table-hover w-100">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Karyawan</th>
                                <th>Tanggal</th>
                                <th>Alasan</th>
                                <th>Catatan</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                @endif
            </div>

            @if($canApproveTeam || $isHrd)
            <div class="pengajuan-legend text-muted mt-2">
                <span class="swatch mr-1"></span> Menunggu persetujuan Anda. Selalu tampil paling atas, apa pun filter tanggalnya.
            </div>
            @endif
        </div>
    </div>
    @endif
</div>

@if($hasEmployeeProfile)
<!-- Modal Create Tidak Masuk -->
<div class="modal fade" id="modalCreateTidakMasuk" tabindex="-1" role="dialog" aria-labelledby="modalCreateTidakMasukLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCreateTidakMasukLabel">Ajukan Tidak Masuk (Sakit/Izin)</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formCreateTidakMasuk" enctype="multipart/form-data" novalidate>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="jenis">Jenis <span class="text-danger">*</span></label>
                        <select name="jenis" id="jenis" class="form-control" required>
                            <option value="">Pilih Jenis</option>
                            <option value="sakit">Sakit</option>
                            <option value="izin">Izin</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-6">
                            <label for="tanggal_mulai">Tanggal Mulai <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_mulai" id="tanggal_mulai" class="form-control" required>
                        </div>
                        <div class="form-group col-6">
                            <label for="tanggal_selesai">Tanggal Selesai <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_selesai" id="tanggal_selesai" class="form-control" required>
                        </div>
                    </div>
                    <div id="tidakMasukInfo" class="mb-3"></div>
                    <div class="form-group">
                        <label for="alasan">Alasan <span class="text-danger">*</span></label>
                        <textarea name="alasan" id="alasan" class="form-control" rows="3" maxlength="1000" required></textarea>
                    </div>
                    <div class="form-group mb-0">
                        <label for="bukti">Bukti <span class="text-muted small" id="buktiHint">(opsional)</span></label>
                        <input type="file" name="bukti" id="bukti" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                        <small class="form-text text-muted">Surat dokter / dokumen pendukung. JPG, PNG, atau PDF, maksimal 2MB.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmitTidakMasuk">Ajukan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Modal Detail Tidak Masuk -->
<div class="modal fade" id="modalDetailTidakMasuk" tabindex="-1" role="dialog" aria-labelledby="modalDetailTidakMasukLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDetailTidakMasukLabel">Detail Pengajuan Tidak Masuk</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@include('hrd.pengajuan._approval_modal', ['modalId' => 'modalApprovalManagerTidakMasuk', 'title' => 'Persetujuan Manager', 'commentName' => 'komentar_manager', 'adjust' => 'date'])
@include('hrd.pengajuan._approval_modal', ['modalId' => 'modalApprovalHRDTidakMasuk', 'title' => 'Persetujuan HRD', 'commentName' => 'komentar_hrd', 'adjust' => 'date'])
@endsection

@section('scripts')
@include('hrd.pengajuan._scripts')
<script>
$(function () {
    var P = window.Pengajuan;
    var baseUrl = "{{ url('hrd/tidakmasuk') }}";
    var indexUrl = "{{ route('hrd.tidakmasuk.index') }}";
    var tables = [];

    var range = P.initDateRange($('#dateRangeFilter'), "{{ $defaultDateStart }}", "{{ $defaultDateEnd }}", function () {
        P.reloadTables(tables);
    });

    var baseColumns = [
        {data: 'tanggal_range', name: 'tanggal_range', orderable: false, searchable: false},
        {data: 'alasan', name: 'alasan', orderable: false},
        {data: 'catatan', name: 'catatan', orderable: false, searchable: false},
        {data: 'status_pengajuan', name: 'status_pengajuan', orderable: false, searchable: false},
        {data: 'action', name: 'action', orderable: false, searchable: false}
    ];
    var noCol = {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false};
    var nameCol = {data: 'employee_nama', name: 'employee_nama', orderable: false};

    if ($('#tableTidakMasukPersonal').length) {
        tables.push(P.dataTable('#tableTidakMasukPersonal', indexUrl + '?view=personal', range, [noCol].concat(baseColumns)));
    }
    if ($('#tableTidakMasukTeam').length) {
        tables.push(P.dataTable('#tableTidakMasukTeam', indexUrl + '?view=team', range, [noCol, nameCol].concat(baseColumns)));
    }
    if ($('#tableTidakMasukApproval').length) {
        tables.push(P.dataTable('#tableTidakMasukApproval', indexUrl + '?view=approval', range, [noCol, nameCol].concat(baseColumns)));
    }

    P.initTabs('hrd.tidakmasuk.tab', @json($preferredPane));

    // ===================== Detail & approval =====================
    P.bindDetail('.btn-detail', function (id) { return baseUrl + '/' + id; }, '#modalDetailTidakMasuk');

    P.bindApproval({
        modal: '#modalApprovalManagerTidakMasuk',
        trigger: '.btn-approve-manager',
        method: 'PUT',
        url: function (id) { return baseUrl + '/' + id + '/manager'; },
        onSuccess: function () { P.decrementBadge('#badgeTeamPending'); P.reloadTables(tables); },
        onStale: function () { P.reloadTables(tables); }
    });

    P.bindApproval({
        modal: '#modalApprovalHRDTidakMasuk',
        trigger: '.btn-approve-hrd',
        method: 'PUT',
        url: function (id) { return baseUrl + '/' + id + '/hrd'; },
        onSuccess: function () { P.decrementBadge('#badgeHrdPending'); P.reloadTables(tables); },
        onStale: function () { P.reloadTables(tables); }
    });

    // ===================== Create =====================
    var $form = $('#formCreateTidakMasuk');
    if (!$form.length) return;

    var MAX_BUKTI = 2 * 1024 * 1024;
    var $jenis = $('#jenis'), $mulai = $('#tanggal_mulai'), $selesai = $('#tanggal_selesai'), $alasan = $('#alasan'), $bukti = $('#bukti');

    function fmtDate(d) { return moment(d, 'YYYY-MM-DD').locale('id').format('D MMM YYYY'); }

    function renderInfo() {
        var s = $mulai.val(), e = $selesai.val(), days = P.dayCount(s, e);
        var html = days
            ? '<div class="alert alert-info small mb-0">Anda mengajukan tidak masuk <strong>' + days + ' hari</strong> ('
                + fmtDate(s) + (s !== e ? ' - ' + fmtDate(e) : '') + ').</div>'
            : '<div class="alert alert-light border small mb-0"><i class="fas fa-info-circle mr-1"></i>'
                + 'Jumlah hari dihitung inklusif (termasuk tanggal mulai dan selesai).</div>';
        $('#tidakMasukInfo').html(html);
    }

    function validateBukti() {
        P.clearFieldError($bukti);
        var file = $bukti[0].files && $bukti[0].files[0];
        if (!file) return true;
        var ext = (file.name.split('.').pop() || '').toLowerCase();
        if ($.inArray(ext, ['jpg', 'jpeg', 'png', 'pdf']) === -1) {
            P.setFieldError($bukti, 'Format file harus JPG, PNG, atau PDF.');
            return false;
        }
        if (file.size > MAX_BUKTI) {
            P.setFieldError($bukti, 'Ukuran file ' + (file.size / 1024 / 1024).toFixed(1) + 'MB, melebihi batas 2MB.');
            return false;
        }
        return true;
    }

    $mulai.on('change', function () {
        var s = $mulai.val();
        P.clearFieldError($mulai);
        $selesai.attr('min', s || null);
        // Keep the end date valid automatically instead of rejecting it
        if (s && (!$selesai.val() || $selesai.val() < s)) $selesai.val(s);
        P.clearFieldError($selesai);
        renderInfo();
    });

    $selesai.on('change', function () {
        var s = $mulai.val(), e = $selesai.val();
        if (s && e && e < s) P.setFieldError($selesai, 'Tanggal selesai tidak boleh sebelum tanggal mulai.');
        else P.clearFieldError($selesai);
        renderInfo();
    });

    $jenis.on('change', function () {
        P.clearFieldError($jenis);
        $('#buktiHint').text($jenis.val() === 'sakit' ? '(disarankan: surat dokter)' : '(opsional)');
    });
    $alasan.on('input', function () { P.clearFieldError($alasan); });
    $bukti.on('change', validateBukti);

    $('#btnCreateTidakMasuk').on('click', function () {
        $form[0].reset();
        P.clearFieldErrors($form);
        $selesai.removeAttr('min');
        $('#buktiHint').text('(opsional)');
        renderInfo();
        $('#modalCreateTidakMasuk').modal('show');
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        P.clearFieldErrors($form);

        var ok = true;
        $.each([$jenis, $mulai, $selesai, $alasan], function (_, $i) {
            if (!$.trim($i.val())) { P.setFieldError($i, 'Wajib diisi.'); ok = false; }
        });
        if ($mulai.val() && $selesai.val() && $selesai.val() < $mulai.val()) {
            P.setFieldError($selesai, 'Tanggal selesai tidak boleh sebelum tanggal mulai.');
            ok = false;
        }
        if (!validateBukti()) ok = false;
        if (!ok) { $form.find('.is-invalid').first().trigger('focus'); return; }

        var $btn = $('#btnSubmitTidakMasuk');
        P.setBusy($btn, true);
        $.ajax({
            url: "{{ route('hrd.tidakmasuk.store') }}",
            type: 'POST',
            data: new FormData($form[0]),
            processData: false,
            contentType: false
        })
            .done(function (res) {
                $('#modalCreateTidakMasuk').modal('hide');
                Swal.fire({ icon: 'success', title: 'Berhasil!', text: (res && res.message) || 'Pengajuan tidak masuk berhasil diajukan.' });
                P.reloadTables(tables);
            })
            .fail(function (xhr) {
                if (!P.applyServerErrors($form, xhr)) P.showError(xhr, 'Gagal mengajukan');
            })
            .always(function () { P.setBusy($btn, false); });
    });
});
</script>
@endsection
