@extends('layouts.hrd.app')
@section('title', 'HRD | Pengajuan Ganti Shift')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@php
    $tabs = [];
    if ($hasEmployeeProfile) $tabs['panePersonal'] = ['label' => 'Pengajuan Saya', 'icon' => 'fa-user', 'badge' => $targetPending, 'badgeId' => 'badgeTargetPending'];
    if ($canApproveTeam) $tabs['paneTeam'] = ['label' => 'Persetujuan Tim', 'icon' => 'fa-users', 'badge' => $teamPending, 'badgeId' => 'badgeTeamPending'];
    if ($isHrd) $tabs['paneApproval'] = ['label' => 'Persetujuan HRD', 'icon' => 'fa-user-check', 'badge' => $hrdPending, 'badgeId' => 'badgeHrdPending'];
    $preferredPane = ['personal' => 'panePersonal', 'team' => 'paneTeam', 'approval' => 'paneApproval'][request('view')] ?? null;
@endphp

@section('content')
<div class="container-fluid px-2">
    <div class="row mb-2">
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h3 class="mb-0 font-weight-bold">Pengajuan Ganti Shift</h3>
                <div class="text-muted small">Ganti shift sendiri atau tukar shift dengan rekan kerja</div>
            </div>
            <div class="d-flex align-items-center pengajuan-toolbar">
                <input type="text" id="dateRangeFilter" class="form-control form-control-sm mr-2" placeholder="Filter tanggal" title="Filter tanggal shift" readonly />
                @if($hasEmployeeProfile)
                <button type="button" class="btn btn-sm btn-primary text-nowrap" id="btnCreateGantiShift">
                    <i class="fas fa-plus-circle mr-1"></i>Buat Pengajuan
                </button>
                @endif
            </div>
        </div>
    </div>

    @if($targetPending > 0)
    <div class="alert alert-warning py-2 d-flex align-items-center" id="targetPendingAlert">
        <i class="fas fa-exchange-alt mr-2"></i>
        <div>Ada <strong id="targetPendingCount">{{ $targetPending }}</strong> permintaan tukar shift dari rekan kerja yang menunggu tanggapan Anda (tab Pengajuan Saya).</div>
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
                @foreach($tabs as $paneId => $tab)
                <div class="tab-pane fade" id="{{ $paneId }}" role="tabpanel">
                    <table id="table_{{ $paneId }}" class="table table-bordered table-hover w-100">
                        <thead>
                            <tr>
                                <th>No</th>
                                @if($paneId !== 'panePersonal')<th>Nama Karyawan</th>@endif
                                <th>Tanggal &amp; Shift</th>
                                <th>Jenis</th>
                                <th>Alasan</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                @endforeach
            </div>

            <div class="pengajuan-legend text-muted mt-2">
                <span class="swatch mr-1"></span> Menunggu tanggapan Anda. Selalu tampil paling atas, apa pun filter tanggalnya.
                <span class="d-block mt-1"><i class="fas fa-info-circle mr-1"></i>Alur tukar shift: rekan menyetujui &rarr; atasan langsung &rarr; HRD. Jadwal diperbarui otomatis setelah disetujui HRD.</span>
            </div>
        </div>
    </div>
    @endif
</div>

@if($hasEmployeeProfile)
<!-- Modal Create -->
<div class="modal fade" id="modalCreateGantiShift" tabindex="-1" role="dialog" aria-labelledby="modalCreateGantiShiftLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCreateGantiShiftLabel">Pengajuan Ganti Shift</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <form id="formCreateGantiShift" novalidate>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="tanggal_shift">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="tanggal_shift" name="tanggal_shift" required>
                    </div>

                    <div id="shiftStep" class="d-none">
                        <div class="form-group">
                            <label class="d-block">Jadwal Anda di tanggal ini</label>
                            <div id="currentShiftInfo" class="small"></div>
                            <select class="form-control d-none mt-1" id="shift_lama_id" name="shift_lama_id"></select>
                        </div>

                        <div class="form-group">
                            <label class="d-block">Jenis Pengajuan <span class="text-danger">*</span></label>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" id="jenisGanti" name="jenis" value="ganti" class="custom-control-input" checked>
                                <label class="custom-control-label" for="jenisGanti">Ganti shift saya</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" id="jenisTukar" name="jenis" value="tukar" class="custom-control-input">
                                <label class="custom-control-label" for="jenisTukar">Tukar dengan rekan</label>
                            </div>
                            <input type="hidden" name="is_tukar_shift" id="is_tukar_shift" value="0">
                            <small class="form-text text-muted" id="jenisHint"></small>
                        </div>

                        <div class="form-group">
                            <label for="shift_baru_id" id="shiftBaruLabel">Shift Baru <span class="text-danger">*</span></label>
                            <select class="form-control" id="shift_baru_id" name="shift_baru_id" required></select>
                        </div>

                        <div class="form-group d-none" id="targetGroup">
                            <label for="target_employee_id">Rekan yang Ditukar <span class="text-danger">*</span></label>
                            <select class="form-control" id="target_employee_id" name="target_employee_id"></select>
                            <small class="form-text text-muted">Rekan yang terjadwal pada shift tersebut di tanggal ini. Rekan akan mendapat shift Anda.</small>
                        </div>

                        <div class="form-group mb-0">
                            <label for="alasan">Alasan <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="alasan" name="alasan" rows="3" maxlength="1000" required placeholder="Jelaskan alasan Anda meminta ganti shift"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmitGantiShift" disabled>Ajukan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Modal Detail -->
<div class="modal fade" id="modalDetailGantiShift" tabindex="-1" role="dialog" aria-labelledby="modalDetailGantiShiftLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDetailGantiShiftLabel">Detail Pengajuan Ganti Shift</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@include('hrd.pengajuan._approval_modal', ['modalId' => 'modalTargetApproval', 'title' => 'Tanggapi Permintaan Tukar Shift', 'commentName' => 'notes',
    'extra' => '<div class="small text-muted"><i class="fas fa-info-circle mr-1"></i>Jika Anda setuju, shift Anda dan rekan akan ditukar setelah disetujui atasan dan HRD.</div>'])
@include('hrd.pengajuan._approval_modal', ['modalId' => 'modalApprovalManagerGantiShift', 'title' => 'Persetujuan Atasan', 'commentName' => 'komentar_manager'])
@include('hrd.pengajuan._approval_modal', ['modalId' => 'modalApprovalHRDGantiShift', 'title' => 'Persetujuan HRD', 'commentName' => 'komentar_hrd',
    'extra' => '<div class="small text-muted"><i class="fas fa-info-circle mr-1"></i>Jika disetujui, jadwal karyawan langsung diperbarui.</div>'])
@endsection

@section('scripts')
@include('hrd.pengajuan._scripts')
<script>
$(function () {
    var P = window.Pengajuan;
    var baseUrl = "{{ url('hrd/gantishift') }}";
    var indexUrl = "{{ route('hrd.gantishift.index') }}";
    var tables = [];

    var range = P.initDateRange($('#dateRangeFilter'), "{{ $defaultDateStart }}", "{{ $defaultDateEnd }}", function () {
        P.reloadTables(tables);
    });

    var cols = [
        {data: 'tanggal', name: 'tanggal', orderable: false, searchable: false},
        {data: 'jenis', name: 'jenis', orderable: false, searchable: false},
        {data: 'alasan', name: 'alasan', orderable: false},
        {data: 'status_pengajuan', name: 'status_pengajuan', orderable: false, searchable: false},
        {data: 'action', name: 'action', orderable: false, searchable: false}
    ];
    var noCol = {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false};
    var nameCol = {data: 'employee_nama', name: 'employee_nama', orderable: false};

    if ($('#table_panePersonal').length) tables.push(P.dataTable('#table_panePersonal', indexUrl + '?view=personal', range, [noCol].concat(cols)));
    if ($('#table_paneTeam').length) tables.push(P.dataTable('#table_paneTeam', indexUrl + '?view=team', range, [noCol, nameCol].concat(cols)));
    if ($('#table_paneApproval').length) tables.push(P.dataTable('#table_paneApproval', indexUrl + '?view=approval', range, [noCol, nameCol].concat(cols)));

    P.initTabs('hrd.gantishift.tab', @json($preferredPane));

    // ===================== Detail & approvals =====================
    P.bindDetail('.btn-detail', function (id) { return baseUrl + '/' + id; }, '#modalDetailGantiShift');

    function refreshAll() { P.reloadTables(tables); }

    P.bindApproval({
        modal: '#modalTargetApproval',
        trigger: '.btn-target-approve',
        method: 'PUT',
        url: function (id) { return baseUrl + '/' + id + '/target-approval'; },
        onSuccess: function () {
            P.decrementBadge('#badgeTargetPending');
            P.decrementBadge('#targetPendingCount');
            if (!$('#targetPendingCount').length) $('#targetPendingAlert').remove();
            refreshAll();
        },
        onStale: refreshAll
    });

    P.bindApproval({
        modal: '#modalApprovalManagerGantiShift',
        trigger: '.btn-approve-manager',
        method: 'PUT',
        url: function (id) { return baseUrl + '/' + id + '/manager'; },
        onSuccess: function () { P.decrementBadge('#badgeTeamPending'); refreshAll(); },
        onStale: refreshAll
    });

    P.bindApproval({
        modal: '#modalApprovalHRDGantiShift',
        trigger: '.btn-approve-hrd',
        method: 'PUT',
        url: function (id) { return baseUrl + '/' + id + '/hrd'; },
        onSuccess: function () { P.decrementBadge('#badgeHrdPending'); refreshAll(); },
        onStale: refreshAll
    });

    // ===================== Create =====================
    var $form = $('#formCreateGantiShift');
    if (!$form.length) return;

    var $tanggal = $('#tanggal_shift'), $shiftLama = $('#shift_lama_id'), $shiftBaru = $('#shift_baru_id');
    var $target = $('#target_employee_id'), $alasan = $('#alasan'), $submit = $('#btnSubmitGantiShift');
    var state = { shifts: [], current: [], loadingFor: null };

    function isTukar() { return $('input[name="jenis"]:checked').val() === 'tukar'; }

    function option(value, text, disabled) {
        return $('<option>').val(value).text(text).prop('disabled', !!disabled);
    }

    // Shift options: everything except the shift(s) already on the schedule
    function fillShiftBaru() {
        var currentIds = $.map(state.current, function (s) { return s.id; });
        $shiftBaru.empty().append(option('', 'Pilih shift'));
        $.each(state.shifts, function (_, s) {
            if ($.inArray(s.id, currentIds) === -1) $shiftBaru.append(option(s.id, s.label));
        });
    }

    function renderCurrent() {
        var cur = state.current;
        $shiftLama.empty().addClass('d-none');
        if (!cur.length) {
            $('#currentShiftInfo').html('<span class="badge badge-light border">Libur / tidak ada shift</span>');
        } else if (cur.length === 1) {
            $('#currentShiftInfo').html('<span class="badge badge-info">' + P.escapeHtml(cur[0].label) + '</span>');
            $shiftLama.append(option(cur[0].id, cur[0].label));
        } else {
            $('#currentShiftInfo').html('<span class="text-muted">Anda punya 2 shift. Pilih shift yang ingin diganti:</span>');
            $shiftLama.append(option('', 'Pilih shift yang diganti'));
            $.each(cur, function (_, s) { $shiftLama.append(option(s.id, s.label)); });
            $shiftLama.removeClass('d-none');
        }

        // Tukar needs an own shift to give away
        $('#jenisTukar').prop('disabled', !cur.length);
        if (!cur.length && isTukar()) $('#jenisGanti').prop('checked', true);
        applyJenis();
    }

    function applyJenis() {
        var tukar = isTukar();
        $('#is_tukar_shift').val(tukar ? 1 : 0);
        $('#targetGroup').toggleClass('d-none', !tukar);
        $('#shiftBaruLabel').html((tukar ? 'Shift rekan yang ingin Anda ambil' : 'Shift Baru') + ' <span class="text-danger">*</span>');
        $('#jenisHint').text(tukar
            ? 'Anda mengambil shift rekan, dan rekan mendapat shift Anda. Rekan harus menyetujui lebih dulu.'
            : (state.current.length ? 'Shift Anda diganti dengan shift baru.' : 'Anda tidak punya shift di tanggal ini; shift baru akan ditambahkan ke jadwal Anda.'));
        P.clearFieldError($target);
        if (tukar) loadTargets(); else $target.empty();
    }

    function loadTargets() {
        var date = $tanggal.val(), shiftId = $shiftBaru.val();
        $target.empty();
        if (!date || !shiftId) {
            $target.append(option('', 'Pilih shift rekan terlebih dahulu', true));
            return;
        }
        $target.append(option('', 'Memuat...', true));
        $.getJSON("{{ route('hrd.gantishift.same-shift-employees') }}", { date: date, shift_id: shiftId })
            .done(function (res) {
                if ($tanggal.val() !== date || $shiftBaru.val() !== shiftId) return; // changed meanwhile
                var list = (res && res.employees) || [];
                $target.empty();
                if (!list.length) {
                    $target.append(option('', 'Tidak ada rekan yang terjadwal pada shift ini', true));
                    return;
                }
                $target.append(option('', 'Pilih rekan'));
                $.each(list, function (_, e) { $target.append(option(e.id, e.name + (e.position ? ' (' + e.position + ')' : ''))); });
            })
            .fail(function (xhr) { $target.empty().append(option('', 'Gagal memuat rekan', true)); P.showError(xhr); });
    }

    $tanggal.on('change', function () {
        var date = $tanggal.val();
        P.clearFieldError($tanggal);
        $('#shiftStep').addClass('d-none');
        $submit.prop('disabled', true);
        if (!date) return;

        state.loadingFor = date;
        $.getJSON("{{ route('hrd.gantishift.available-shifts') }}", { date: date })
            .done(function (res) {
                if (state.loadingFor !== date) return;
                state.shifts = (res && res.shifts) || [];
                state.current = (res && res.current_shifts) || [];
                fillShiftBaru();
                renderCurrent();
                $('#shiftStep').removeClass('d-none');
                $submit.prop('disabled', false);
            })
            .fail(function (xhr) { P.showError(xhr, 'Gagal memuat jadwal'); });
    });

    $('input[name="jenis"]').on('change', applyJenis);
    $shiftBaru.on('change', function () { P.clearFieldError($shiftBaru); if (isTukar()) loadTargets(); });
    $shiftLama.add($target).on('change', function () { P.clearFieldError($(this)); });
    $alasan.on('input', function () { P.clearFieldError($alasan); });

    $('#btnCreateGantiShift').on('click', function () {
        $form[0].reset();
        P.clearFieldErrors($form);
        state = { shifts: [], current: [], loadingFor: null };
        $('#shiftStep').addClass('d-none');
        $('#jenisTukar').prop('disabled', false);
        $submit.prop('disabled', true);
        $('#modalCreateGantiShift').modal('show');
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        P.clearFieldErrors($form);

        var ok = true;
        var required = [$tanggal, $shiftBaru, $alasan];
        if (!$shiftLama.hasClass('d-none')) required.push($shiftLama);
        if (isTukar()) required.push($target);
        $.each(required, function (_, $i) {
            if (!$.trim($i.val())) { P.setFieldError($i, 'Wajib diisi.'); ok = false; }
        });
        if (!ok) { $form.find('.is-invalid').first().trigger('focus'); return; }

        P.setBusy($submit, true);
        $.ajax({ url: "{{ route('hrd.gantishift.store') }}", type: 'POST', data: $form.serialize() })
            .done(function (res) {
                $('#modalCreateGantiShift').modal('hide');
                Swal.fire({ icon: 'success', title: 'Berhasil!', text: (res && res.message) || 'Pengajuan berhasil diajukan.' });
                refreshAll();
            })
            .fail(function (xhr) {
                if (!P.applyServerErrors($form, xhr)) P.showError(xhr, 'Gagal mengajukan');
            })
            .always(function () { P.setBusy($submit, false); });
    });
});
</script>
@endsection
