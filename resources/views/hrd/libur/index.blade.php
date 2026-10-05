@extends('layouts.hrd.app')
@section('title', 'HRD | Pengajuan Cuti/Libur')
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
                <h3 class="mb-0 font-weight-bold">Pengajuan Cuti/Libur</h3>
                <div class="text-muted small">Kelola pengajuan cuti dan ganti libur karyawan</div>
            </div>
            <div class="d-flex align-items-center pengajuan-toolbar">
                <input type="text" id="dateRangeFilter" class="form-control form-control-sm mr-2" placeholder="Filter tanggal" title="Filter tanggal libur" readonly />
                @if($hasEmployeeProfile)
                <button type="button" class="btn btn-sm btn-primary text-nowrap" id="btnCreateLibur">
                    <i class="fas fa-plus-circle mr-1"></i>Buat Pengajuan
                </button>
                @endif
            </div>
        </div>
    </div>

    @if($saldo)
    <div class="row mb-1">
        @foreach($saldo as $jenis => $s)
        <div class="col-sm-6 col-lg-3 mb-2">
            <div class="card mb-0 border-left-0">
                <div class="card-body py-2 px-3">
                    <div class="text-muted small">{{ $s['label'] }}</div>
                    <div class="h4 mb-0 font-weight-bold"><span data-saldo="{{ $jenis }}.tersedia">{{ $s['tersedia'] }}</span> <small class="text-muted">hari tersedia</small></div>
                    <div class="small text-muted" data-saldo-note="{{ $jenis }}">
                        Saldo {{ $s['saldo'] }} hari
                        @if($s['pending'] > 0) &middot; {{ $s['pending'] }} hari menunggu persetujuan @endif
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

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
                    <table id="tableLiburKaryawan" class="table table-bordered table-hover w-100">
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
                    <table id="tableLiburManager" class="table table-bordered table-hover w-100">
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
                    <table id="tableLiburHRD" class="table table-bordered table-hover w-100">
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
<!-- Modal Create Pengajuan -->
<div class="modal fade" id="modalCreateLibur" tabindex="-1" role="dialog" aria-labelledby="modalCreateLiburLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCreateLiburLabel">Pengajuan Cuti/Libur</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formCreateLibur" novalidate>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="jenis_libur">Jenis <span class="text-danger">*</span></label>
                        <select class="form-control" name="jenis_libur" id="jenis_libur" required>
                            <option value="">Pilih Jenis</option>
                            <option value="cuti_tahunan">Cuti Tahunan</option>
                            <option value="ganti_libur">Ganti Libur</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-6">
                            <label for="tanggal_mulai">Tanggal Mulai <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="tanggal_mulai" id="tanggal_mulai" required>
                        </div>
                        <div class="form-group col-6">
                            <label for="tanggal_selesai">Tanggal Selesai <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="tanggal_selesai" id="tanggal_selesai" required>
                        </div>
                    </div>

                    <div class="form-group d-none" id="hariMasukGroup">
                        <label>Hari Minggu / libur nasional yang Anda masuk (pengganti) <span class="text-danger">*</span></label>
                        <div id="hariMasukList" class="border rounded p-2" style="max-height: 180px; overflow-y: auto;"></div>
                        <small class="form-text text-muted" id="hariMasukHint">Pilih satu hari untuk setiap hari libur yang diajukan. Hanya hari Minggu dan libur nasional di jadwal Anda yang sudah lewat.</small>
                    </div>

                    <div id="liburInfo" class="mb-3"></div>

                    <div class="form-group mb-0">
                        <label for="alasan">Alasan <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="alasan" id="alasan" rows="3" maxlength="1000" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmitLibur">Ajukan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Modal Detail Pengajuan -->
<div class="modal fade" id="modalDetailLibur" tabindex="-1" role="dialog" aria-labelledby="modalDetailLiburLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDetailLiburLabel">Detail Pengajuan Cuti/Libur</h5>
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

@include('hrd.pengajuan._approval_modal', ['modalId' => 'modalApprovalManager', 'title' => 'Persetujuan Manager', 'commentName' => 'komentar_manager', 'adjust' => 'date'])
@include('hrd.pengajuan._approval_modal', ['modalId' => 'modalApprovalHRD', 'title' => 'Persetujuan HRD', 'commentName' => 'komentar_hrd', 'adjust' => 'date',
    'extra' => '<div class="small text-muted"><i class="fas fa-info-circle mr-1"></i>Jika disetujui, saldo karyawan otomatis dikurangi sesuai jumlah hari yang disetujui.</div>'])
@endsection

@section('scripts')
@include('hrd.pengajuan._scripts')
<script>
$(function () {
    var P = window.Pengajuan;
    var baseUrl = "{{ url('hrd/libur') }}";
    var indexUrl = "{{ route('hrd.libur.index') }}";
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

    if ($('#tableLiburKaryawan').length) {
        tables.push(P.dataTable('#tableLiburKaryawan', indexUrl + '?view=personal', range, [noCol].concat(baseColumns)));
    }
    if ($('#tableLiburManager').length) {
        tables.push(P.dataTable('#tableLiburManager', indexUrl + '?view=team', range, [noCol, nameCol].concat(baseColumns)));
    }
    if ($('#tableLiburHRD').length) {
        tables.push(P.dataTable('#tableLiburHRD', indexUrl + '?view=approval', range, [noCol, nameCol].concat(baseColumns)));
    }

    P.initTabs('hrd.libur.tab', @json($preferredPane));

    // ===================== Detail & approval =====================
    P.bindDetail('.btn-detail', function (id) { return baseUrl + '/' + id; }, '#modalDetailLibur');

    P.bindApproval({
        modal: '#modalApprovalManager',
        trigger: '.btn-approve-manager',
        method: 'PUT',
        url: function (id) { return baseUrl + '/' + id + '/manager'; },
        onSuccess: function () { P.decrementBadge('#badgeTeamPending'); P.reloadTables(tables); },
        onStale: function () { P.reloadTables(tables); }
    });

    P.bindApproval({
        modal: '#modalApprovalHRD',
        trigger: '.btn-approve-hrd',
        method: 'PUT',
        url: function (id) { return baseUrl + '/' + id + '/hrd'; },
        onSuccess: function () { P.decrementBadge('#badgeHrdPending'); P.reloadTables(tables); },
        onStale: function () { P.reloadTables(tables); }
    });

    // ===================== Create =====================
    var $form = $('#formCreateLibur');
    if (!$form.length) return;

    var TODAY = "{{ now()->toDateString() }}";
    var saldo = @json($saldo);
    var checkCapacityUrl = "{{ route('hrd.libur.check_capacity') }}";
    var $jenis = $('#jenis_libur'), $mulai = $('#tanggal_mulai'), $selesai = $('#tanggal_selesai'), $alasan = $('#alasan');
    var $submit = $('#btnSubmitLibur');
    var capacity = { key: null, blocked: [], max: null };

    function fmtDate(d) { return moment(d, 'YYYY-MM-DD').locale('id').format('D MMM YYYY'); }

    // ---- Ganti libur: which worked Sunday(s) this day off replaces ----
    var hariMasukUrl = "{{ route('hrd.libur.hari_masuk_tersedia') }}";
    var $hariMasukGroup = $('#hariMasukGroup'), $hariMasukList = $('#hariMasukList');
    var hariMasuk = null; // null = not loaded, false = failed, [] = loaded

    function isGanti() { return $jenis.val() === 'ganti_libur'; }
    function selectedHariMasuk() { return $hariMasukList.find('input:checked').length; }

    function renderHariMasuk() {
        if (hariMasuk === null) {
            $hariMasukList.html('<span class="text-muted small"><i class="fa fa-spinner fa-spin mr-1"></i>Memuat jadwal...</span>');
        } else if (hariMasuk === false) {
            $hariMasukList.html('<span class="text-danger small">Gagal memuat jadwal. Tutup dan buka kembali formulir.</span>');
        } else if (!hariMasuk.length) {
            $hariMasukList.html('<span class="text-muted small">Belum ada hari Minggu atau libur nasional di jadwal Anda (yang sudah lewat) yang belum dipakai untuk ganti libur.</span>');
        } else {
            $hariMasukList.html($.map(hariMasuk, function (h) {
                var id = 'hm_' + h.date;
                return '<div class="custom-control custom-checkbox">'
                    + '<input type="checkbox" class="custom-control-input" name="tanggal_masuk_pengganti[]" value="' + h.date + '" id="' + id + '">'
                    + '<label class="custom-control-label" for="' + id + '">' + P.escapeHtml(h.label)
                    + (h.libur ? ' <span class="badge badge-danger">' + P.escapeHtml(h.libur) + '</span>' : '')
                    + (h.shift ? ' <small class="text-muted">(' + P.escapeHtml(h.shift) + ')</small>' : '') + '</label></div>';
            }).join(''));
        }
    }

    function loadHariMasuk() {
        hariMasuk = null;
        renderHariMasuk();
        $.getJSON(hariMasukUrl)
            .done(function (res) { hariMasuk = (res && res.data) || []; })
            .fail(function () { hariMasuk = false; })
            .always(function () { renderHariMasuk(); evaluate(); });
    }

    $hariMasukList.on('change', 'input', function () { evaluate(); });

    function renderSaldoCards() {
        if (!saldo) return;
        $.each(saldo, function (jenis, s) {
            $('[data-saldo="' + jenis + '.tersedia"]').text(s.tersedia);
            $('[data-saldo-note="' + jenis + '"]').text('Saldo ' + s.saldo + ' hari' + (s.pending > 0 ? ' · ' + s.pending + ' hari menunggu persetujuan' : ''));
        });
        $jenis.find('option[value]').each(function () {
            var s = saldo[this.value];
            if (s) $(this).text(s.label + ' (tersedia ' + s.tersedia + ' hari)');
        });
    }

    // Returns a description of anything blocking submission ('' when OK) and renders the info box
    function evaluate() {
        var s = $mulai.val(), e = $selesai.val(), jenis = $jenis.val();
        var days = P.dayCount(s, e), html = '', problem = '';

        if (!days) {
            html = '<div class="alert alert-light border small mb-0"><i class="fas fa-info-circle mr-1"></i>'
                + 'Jumlah hari dihitung inklusif. Libur 1 hari: isi tanggal mulai dan selesai sama.</div>';
        } else {
            var lines = ['Anda mengajukan <strong>' + days + ' hari</strong> (' + fmtDate(s) + (s !== e ? ' - ' + fmtDate(e) : '') + ').'];
            var cls = 'alert-info';
            if (jenis && saldo && saldo[jenis]) {
                var sisa = saldo[jenis].tersedia - days;
                if (sisa < 0) {
                    cls = 'alert-danger';
                    problem = 'Saldo ' + saldo[jenis].label + ' tidak mencukupi (tersedia ' + saldo[jenis].tersedia + ' hari).';
                    lines.push('<i class="fas fa-exclamation-triangle mr-1"></i>' + problem);
                } else {
                    lines.push('Sisa saldo ' + saldo[jenis].label + ' setelah pengajuan ini: <strong>' + sisa + ' hari</strong>.');
                }
            }
            if (isGanti()) {
                var picked = selectedHariMasuk(), hmMsg = '';
                if (hariMasuk === null) hmMsg = 'Menunggu jadwal dimuat.';
                else if (hariMasuk === false || !hariMasuk.length) hmMsg = 'Tidak ada hari Minggu / libur nasional yang bisa dipakai sebagai pengganti.';
                else if (picked !== days) hmMsg = 'Pilih ' + days + ' hari pengganti (dipilih ' + picked + ').';
                if (hmMsg) {
                    if (cls !== 'alert-danger') cls = 'alert-warning';
                    problem = problem || hmMsg;
                    lines.push('<i class="fas fa-calendar-check mr-1"></i>' + hmMsg);
                }
            }
            if (capacity.key === s + '|' + e && capacity.blocked.length) {
                cls = 'alert-danger';
                var capMsg = 'Kuota libur penuh (maks. ' + capacity.max + ' orang/hari) pada: ' + $.map(capacity.blocked, fmtDate).join(', ') + '.';
                problem = problem || capMsg;
                lines.push('<i class="fas fa-ban mr-1"></i>' + capMsg);
            }
            html = '<div class="alert ' + cls + ' small mb-0">' + lines.join('<br>') + '</div>';
        }

        $('#liburInfo').html(html);
        $submit.prop('disabled', !!problem);
        return problem;
    }

    function checkCapacity() {
        var s = $mulai.val(), e = $selesai.val(), key = s + '|' + e;
        if (!s || !e || e < s || capacity.key === key) return $.Deferred().resolve().promise();
        return $.getJSON(checkCapacityUrl, { start: s, end: e })
            .done(function (r) {
                if ($mulai.val() + '|' + $selesai.val() !== key) return; // dates changed meanwhile
                capacity = { key: key, blocked: (r && r.blockedDates) || [], max: r && r.capacity };
                evaluate();
            });
            // On failure the server still validates capacity when submitting
    }

    $mulai.on('change', function () {
        var s = $mulai.val();
        if (s && s < TODAY) {
            P.setFieldError($mulai, 'Tanggal mulai tidak boleh sebelum hari ini.');
        } else {
            P.clearFieldError($mulai);
        }
        $selesai.attr('min', s || TODAY);
        // Keep the end date valid automatically instead of rejecting it
        if (s && (!$selesai.val() || $selesai.val() < s)) $selesai.val(s);
        P.clearFieldError($selesai);
        evaluate();
        checkCapacity();
    });

    $selesai.on('change', function () {
        var s = $mulai.val(), e = $selesai.val();
        if (s && e && e < s) {
            P.setFieldError($selesai, 'Tanggal selesai tidak boleh sebelum tanggal mulai.');
        } else {
            P.clearFieldError($selesai);
        }
        evaluate();
        checkCapacity();
    });

    $jenis.on('change', function () {
        P.clearFieldError($jenis);
        $hariMasukGroup.toggleClass('d-none', !isGanti());
        if (isGanti()) {
            if (hariMasuk === null || hariMasuk === false) loadHariMasuk();
        } else {
            $hariMasukList.find('input').prop('checked', false); // not sent for cuti tahunan
        }
        evaluate();
    });
    $alasan.on('input', function () { P.clearFieldError($alasan); });

    $('#btnCreateLibur').on('click', function () {
        $form[0].reset();
        P.clearFieldErrors($form);
        capacity = { key: null, blocked: [], max: null };
        // Reload each time: Sundays used by other requests change
        hariMasuk = null;
        $hariMasukList.empty();
        $hariMasukGroup.addClass('d-none');
        $mulai.attr('min', TODAY);
        $selesai.attr('min', TODAY);
        renderSaldoCards();
        evaluate();
        $('#modalCreateLibur').modal('show');
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        P.clearFieldErrors($form);

        var ok = true;
        $.each([$jenis, $mulai, $selesai, $alasan], function (_, $i) {
            if (!$.trim($i.val())) { P.setFieldError($i, 'Wajib diisi.'); ok = false; }
        });
        if ($mulai.val() && $mulai.val() < TODAY) { P.setFieldError($mulai, 'Tanggal mulai tidak boleh sebelum hari ini.'); ok = false; }
        if ($mulai.val() && $selesai.val() && $selesai.val() < $mulai.val()) { P.setFieldError($selesai, 'Tanggal selesai tidak boleh sebelum tanggal mulai.'); ok = false; }
        if (!ok) { $form.find('.is-invalid').first().trigger('focus'); return; }

        P.setBusy($submit, true);
        checkCapacity().always(function () {
            if (evaluate()) { P.setBusy($submit, false); $submit.prop('disabled', true); return; }
            $submit.prop('disabled', true); // evaluate() re-enables it; keep it locked while saving

            $.ajax({ url: "{{ route('hrd.libur.store') }}", type: 'POST', data: $form.serialize() })
                .done(function (res) {
                    $('#modalCreateLibur').modal('hide');
                    if (res && res.saldo) { saldo = res.saldo; renderSaldoCards(); }
                    Swal.fire({ icon: 'success', title: 'Berhasil!', text: (res && res.message) || 'Pengajuan libur berhasil diajukan.' });
                    P.reloadTables(tables);
                })
                .fail(function (xhr) {
                    if (!P.applyServerErrors($form, xhr)) P.showError(xhr, 'Gagal mengajukan libur');
                    capacity.key = null; // force a fresh capacity check next time
                })
                .always(function () { P.setBusy($submit, false); });
        });
    });

    renderSaldoCards();
});
</script>
@endsection
