@extends('layouts.hrd.app')
@section('title', 'HRD | Pengajuan Lembur')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
<div class="container-fluid px-2">
    <div class="row mb-2">
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h3 class="mb-0 font-weight-bold">Pengajuan Lembur</h3>
                <div class="text-muted small">Kelola pengajuan lembur karyawan</div>
            </div>
            <div class="d-flex align-items-center pengajuan-toolbar">
                <input type="text" id="dateRangeFilter" class="form-control form-control-sm mr-2" placeholder="Filter tanggal" title="Filter tanggal lembur" readonly />
                @if($hasEmployeeProfile)
                <button type="button" class="btn btn-sm btn-primary text-nowrap" id="btnCreateLembur">
                    <i class="fas fa-plus-circle mr-1"></i>Ajukan Lembur
                </button>
                @endif
            </div>
        </div>
    </div>

    @if($pendingCount > 0)
    <div class="alert alert-warning py-2 d-flex align-items-center" id="pendingAlert">
        <i class="fas fa-bell mr-2"></i>
        <div>
            Ada <strong id="pendingCount">{{ $pendingCount }}</strong> pengajuan lembur yang menunggu persetujuan Anda.
            Pengajuan tersebut ditandai kuning dan selalu tampil paling atas, apa pun filter tanggalnya.
        </div>
    </div>
    @endif

    <div class="card">
        <div class="card-body p-2">
            <table class="table table-bordered table-hover w-100" id="tableLembur">
                <thead>
                    <tr>
                        <th>No</th>
                        @if($showEmployeeColumn)
                        <th>Nama Pegawai</th>
                        @endif
                        <th>Tanggal &amp; Jam</th>
                        <th>Alasan</th>
                        <th>Catatan</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
            </table>
            @if($showEmployeeColumn)
            <div class="pengajuan-legend text-muted mt-2"><span class="swatch mr-1"></span> Menunggu persetujuan Anda</div>
            @endif
        </div>
    </div>
</div>

@if($hasEmployeeProfile)
<!-- Modal Create Lembur -->
<div class="modal fade" id="modalCreateLembur" tabindex="-1" role="dialog" aria-labelledby="modalCreateLemburLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCreateLemburLabel">Ajukan Lembur</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formCreateLembur" novalidate>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="tanggal">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="tanggal" id="tanggal" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-6">
                            <label for="jam_mulai">Jam Mulai <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" name="jam_mulai" id="jam_mulai" required>
                        </div>
                        <div class="form-group col-6">
                            <label for="jam_selesai">Jam Selesai <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" name="jam_selesai" id="jam_selesai" required>
                        </div>
                    </div>
                    <div class="alert alert-light border py-2 small mb-3" id="lemburDurasi">
                        <i class="fas fa-info-circle mr-1"></i>Jika jam selesai lebih awal dari jam mulai, lembur dianggap selesai keesokan harinya.
                    </div>
                    <div class="form-group mb-0">
                        <label for="alasan">Alasan <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="alasan" id="alasan" rows="3" maxlength="1000" required placeholder="Contoh: Menyelesaikan stok opname akhir bulan"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmitLembur">Ajukan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Modal Detail Lembur -->
<div class="modal fade" id="modalDetailLembur" tabindex="-1" role="dialog" aria-labelledby="modalDetailLemburLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDetailLemburLabel">Detail Pengajuan Lembur</h5>
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

@include('hrd.pengajuan._approval_modal', ['modalId' => 'modalApprovalManagerLembur', 'title' => 'Persetujuan Manager', 'commentName' => 'komentar_manager', 'adjust' => 'time'])
@include('hrd.pengajuan._approval_modal', ['modalId' => 'modalApprovalHRDLembur', 'title' => 'Persetujuan HRD', 'commentName' => 'komentar_hrd', 'adjust' => 'time'])
@endsection

@section('scripts')
@include('hrd.pengajuan._scripts')
<script>
$(function () {
    var P = window.Pengajuan;
    var baseUrl = "{{ url('hrd/lembur') }}";

    var range = P.initDateRange($('#dateRangeFilter'), "{{ $defaultDateStart }}", "{{ $defaultDateEnd }}", function () {
        tableLembur.ajax.reload();
    });

    var tableLembur = P.dataTable('#tableLembur', "{{ route('hrd.lembur.index') }}", range, [
        {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
        @if($showEmployeeColumn)
        {data: 'employee_nama', name: 'employee_nama', orderable: false},
        @endif
        {data: 'tanggal', name: 'tanggal', orderable: false, searchable: false},
        {data: 'alasan', name: 'alasan', orderable: false},
        {data: 'catatan', name: 'catatan', orderable: false, searchable: false},
        {data: 'status_pengajuan', name: 'status_pengajuan', orderable: false, searchable: false},
        {data: 'action', name: 'action', orderable: false, searchable: false}
    ]);

    // ===================== Detail & approval =====================
    P.bindDetail('.btn-detail-lembur', function (id) { return baseUrl + '/' + id; }, '#modalDetailLembur');

    function onApproved() {
        P.decrementBadge('#pendingCount');
        if (!$('#pendingCount').length) $('#pendingAlert').remove();
        tableLembur.ajax.reload(null, false);
    }

    P.bindApproval({
        modal: '#modalApprovalManagerLembur',
        trigger: '.btn-approve-manager-lembur',
        method: 'POST',
        url: function (id) { return baseUrl + '/' + id + '/persetujuan-manager'; },
        onSuccess: onApproved,
        onStale: function () { tableLembur.ajax.reload(null, false); }
    });

    P.bindApproval({
        modal: '#modalApprovalHRDLembur',
        trigger: '.btn-approve-hrd-lembur',
        method: 'POST',
        url: function (id) { return baseUrl + '/' + id + '/persetujuan-hrd'; },
        onSuccess: onApproved,
        onStale: function () { tableLembur.ajax.reload(null, false); }
    });

    // ===================== Create =====================
    var $form = $('#formCreateLembur');
    if (!$form.length) return;

    var $tanggal = $('#tanggal'), $mulai = $('#jam_mulai'), $selesai = $('#jam_selesai'), $alasan = $('#alasan');
    var durasiDefault = $('#lemburDurasi').html();

    function formatDuration(minutes) {
        var h = Math.floor(minutes / 60), m = minutes % 60, out = [];
        if (h) out.push(h + ' jam');
        if (m) out.push(m + ' menit');
        return out.join(' ') || '0 menit';
    }

    // Validates the time fields; returns true when they are acceptable
    function validateTimes() {
        var ok = true, mulai = $mulai.val(), selesai = $selesai.val();
        P.clearFieldError($mulai);
        P.clearFieldError($selesai);

        if (mulai && selesai && mulai === selesai) {
            P.setFieldError($selesai, 'Jam selesai tidak boleh sama dengan jam mulai.');
            ok = false;
        }

        var $info = $('#lemburDurasi');
        if (mulai && selesai && mulai !== selesai) {
            var start = moment(mulai, 'HH:mm'), end = moment(selesai, 'HH:mm');
            var overnight = !end.isAfter(start);
            if (overnight) end.add(1, 'day');
            var html = '<i class="fas fa-clock mr-1"></i>Durasi lembur: <strong>' + formatDuration(end.diff(start, 'minutes')) + '</strong>';
            if (overnight) html += ' <span class="badge badge-warning ml-1">selesai keesokan hari</span>';
            $info.html(html).toggleClass('alert-warning', overnight).toggleClass('alert-light', !overnight);
        } else {
            $info.html(durasiDefault).removeClass('alert-warning').addClass('alert-light');
        }
        return ok;
    }

    $tanggal.add($mulai).add($selesai).on('change input', validateTimes);
    // Time fields are re-validated by validateTimes; clear "required" errors on the others as the user types
    $tanggal.add($alasan).on('input change', function () {
        if ($(this).val()) P.clearFieldError($(this));
    });

    $('#btnCreateLembur').on('click', function () {
        $form[0].reset();
        P.clearFieldErrors($form);
        $tanggal.val(moment().format('YYYY-MM-DD'));
        validateTimes();
        $('#modalCreateLembur').modal('show');
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        P.clearFieldErrors($form);

        var ok = validateTimes();
        $.each([$tanggal, $mulai, $selesai, $alasan], function (_, $i) {
            if (!$.trim($i.val())) { P.setFieldError($i, 'Wajib diisi.'); ok = false; }
        });
        if (!ok) {
            $form.find('.is-invalid').first().trigger('focus');
            return;
        }

        var $btn = $('#btnSubmitLembur');
        P.setBusy($btn, true);
        $.ajax({ url: "{{ route('hrd.lembur.store') }}", method: 'POST', data: $form.serialize() })
            .done(function (res) {
                $('#modalCreateLembur').modal('hide');
                P.toast((res && res.message) || 'Pengajuan lembur berhasil diajukan');
                tableLembur.ajax.reload();
            })
            .fail(function (xhr) {
                if (!P.applyServerErrors($form, xhr)) P.showError(xhr, 'Gagal mengajukan lembur');
            })
            .always(function () { P.setBusy($btn, false); });
    });
});
</script>
@endsection
