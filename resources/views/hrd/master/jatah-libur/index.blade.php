@extends('layouts.hrd.app')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('title', 'Master Data Jatah Libur')

@section('content')
<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <h4 class="page-title">Master Data</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">HRD</a></li>
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Master Data</a></li>
                            <li class="breadcrumb-item active">Jatah Libur</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    <!-- end page title end breadcrumb -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <h4 class="card-title mb-0 mr-3">Data Jatah Libur Karyawan</h4>
                        <button type="button" class="btn btn-primary btn-sm" id="btnAddJatahLibur">
                            <i class="fa fa-plus"></i> Tambah Jatah Libur
                        </button>
                        <button type="button" class="btn btn-warning btn-sm ml-2" id="btnResetAnnual">
                            <i class="fa fa-undo"></i> Reset Cuti Tahunan
                        </button>
                    </div>
                    <div>
                        <button type="button" class="btn btn-primary btn-sm" id="btnLeaveCapacity">
                            <i class="fa fa-cog"></i> Pengaturan Kuota Libur Harian
                        </button>
                    </div>
                </div><!--end card-header-->
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="jatahLiburTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>ID Karyawan</th>
                                    <th>Nama Karyawan</th>
                                    <th>Divisi</th>
                                    <th>Cuti Tahunan</th>
                                    <th>Ganti Libur</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be loaded by DataTable -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div> <!-- end col -->
    </div> <!-- end row -->
</div><!-- container -->

<!-- Add/Edit Jatah Libur Modal -->
<div class="modal fade" id="jatahLiburModal" tabindex="-1" role="dialog" aria-labelledby="jatahLiburModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="jatahLiburModalLabel">Tambah Jatah Libur</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="jatahLiburForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="jatah_libur_id" name="jatah_libur_id">
                    
                    <div class="form-group" id="employee_selection_group">
                        <label for="employee_id">Karyawan <span class="text-danger">*</span></label>
                        <select class="form-control" id="employee_id" name="employee_id" required>
                            <option value="">Pilih Karyawan</option>
                            <!-- Options will be loaded via AJAX -->
                        </select>
                        <div class="invalid-feedback" id="employee_id-error"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="jatah_cuti_tahunan">Jatah Cuti Tahunan <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="jatah_cuti_tahunan" name="jatah_cuti_tahunan" min="0" value="0" required>
                        <div class="invalid-feedback" id="jatah_cuti_tahunan-error"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="jatah_ganti_libur">Jatah Ganti Libur <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="jatah_ganti_libur" name="jatah_ganti_libur" min="0" value="0" required>
                        <div class="invalid-feedback" id="jatah_ganti_libur-error"></div>
                        <span class="badge badge-warning d-none mt-1" id="ganti_libur_tanpa_tanggal"></span>
                        <button type="button" class="btn btn-link btn-sm p-0 ml-1 d-none" id="btnLengkapiTanggal">
                            <i class="fas fa-calendar-plus"></i> Lengkapi tanggal
                        </button>
                        <div class="border rounded p-2 mt-2 d-none" id="lengkapiTanggalForm">
                            <small class="text-muted d-block mb-1">
                                Hari Minggu / libur nasional yang dikerjakan untuk saldo ini. Ditambahkan ke jadwal karyawan, saldo tidak berubah.
                                Jangan diisi lewat halaman Jadwal, karena di sana saldo akan bertambah lagi.
                            </small>
                            <div class="form-row">
                                <div class="col-5"><input type="date" class="form-control form-control-sm" id="lengkapi_date"></div>
                                <div class="col-5"><select class="form-control form-control-sm" id="lengkapi_shift"></select></div>
                                <div class="col-2 text-nowrap">
                                    <button type="button" class="btn btn-sm btn-success" id="btnSimpanLengkapi" title="Simpan"><i class="fas fa-check"></i></button>
                                    <button type="button" class="btn btn-sm btn-secondary" id="btnBatalLengkapi" title="Batal"><i class="fas fa-times"></i></button>
                                </div>
                            </div>
                            <div class="small mt-1" id="lengkapi_note"></div>
                        </div>
                        <div class="d-none mt-2" id="gantiLiburTanggalGroup">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="text-muted">Riwayat hari masuk Minggu / libur nasional dan libur penggantinya:</small>
                                <div class="custom-control custom-checkbox small d-none" id="toggleLamaWrap">
                                    <input type="checkbox" class="custom-control-input" id="toggleLama">
                                    <label class="custom-control-label" for="toggleLama">Tampilkan yang lama (<span id="countLama">0</span>)</label>
                                </div>
                            </div>
                            <div class="table-responsive" style="max-height: 320px; overflow-y: auto;">
                                <table class="table table-sm table-bordered small mb-0">
                                    <thead class="thead-light" style="position: sticky; top: 0; z-index: 1;">
                                        <tr>
                                            <th>Hari Masuk</th>
                                            <th>Shift</th>
                                            <th>Libur Pengganti</th>
                                            <th>Status</th>
                                            <th style="width: 1%;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="gantiLiburTanggalList"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-0 d-none" id="hariMasukGroup">
                        <label>Hari Masuk Minggu / Libur Nasional <span class="text-danger">*</span></label>
                        <div id="hariMasukRows"></div>
                        <div class="invalid-feedback d-block" id="hari_masuk-error"></div>
                        <small class="form-text text-muted">Satu tanggal untuk tiap hari jatah yang ditambahkan. Tanggal otomatis ditambahkan ke jadwal karyawan dengan shift yang dipilih.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary" id="saveJatahLibur">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Leave Capacity Modal -->
<div class="modal fade" id="leaveCapacityModal" tabindex="-1" role="dialog" aria-labelledby="leaveCapacityModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="leaveCapacityModalLabel">Pengaturan Kuota Libur Harian</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="leaveCapacityForm">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="leave_capacity">Maksimum Karyawan Libur per Hari</label>
                        <input type="number" class="form-control" id="leave_capacity" name="capacity" min="1" value="2" required>
                        <small class="form-text text-muted">Jika mencapai angka ini pada suatu tanggal, karyawan lain tidak dapat memilih tanggal tersebut.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary" id="saveLeaveCapacity">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    </div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Initialize DataTable
        var table = $('#jatahLiburTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('hrd.master.jatah-libur.data') }}",
            columns: [
                {data: 'id', name: 'id'},
                {data: 'employee_number', name: 'employee_number'},
                {data: 'employee_name', name: 'employee_name'},
                {data: 'division', name: 'division'},
                {data: 'jatah_cuti_tahunan', name: 'jatah_cuti_tahunan'},
                {data: 'jatah_ganti_libur', name: 'jatah_ganti_libur'},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ]
        });

        // Open Leave Capacity modal
        $('#btnLeaveCapacity').on('click', function(){
            $('#leaveCapacityForm')[0].reset();
            $('#saveLeaveCapacity').prop('disabled', false).text('Simpan');
            $.ajax({
                url: "{{ route('hrd.master.jatah-libur.leave_capacity.get') }}",
                method: 'GET',
                success: function(res){
                    if (res && res.success) {
                        $('#leave_capacity').val(res.capacity || 2);
                    }
                    $('#leaveCapacityModal').modal('show');
                },
                error: function(){
                    $('#leave_capacity').val(2);
                    $('#leaveCapacityModal').modal('show');
                }
            });
        });

        // Save Leave Capacity
        $('#leaveCapacityForm').on('submit', function(e){
            e.preventDefault();
            var formData = $(this).serialize();
            $.ajax({
                url: "{{ route('hrd.master.jatah-libur.leave_capacity.update') }}",
                method: 'POST',
                data: formData,
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                beforeSend: function(){
                    $('#saveLeaveCapacity').prop('disabled', true).text('Menyimpan...');
                },
                success: function(res){
                    Swal.fire({
                        icon: 'success',
                        title: 'Tersimpan',
                        text: res.message || 'Kuota libur telah diperbarui'
                    });
                    $('#leaveCapacityModal').modal('hide');
                },
                error: function(xhr){
                    var msg = 'Gagal menyimpan kuota';
                    if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    Swal.fire({ icon: 'error', title: 'Error', text: msg });
                },
                complete: function(){
                    $('#saveLeaveCapacity').prop('disabled', false).text('Simpan');
                }
            });
        });

        // ===================== Hari masuk (Minggu / libur nasional) =====================
        var TODAY = "{{ now()->toDateString() }}";
        var liburNasional = @json($liburNasional);
        var shiftOptions = '<option value="">Pilih Shift</option>' + $.map(@json($shifts), function (s) {
            return '<option value="' + s.id + '">' + $('<div>').text(s.name + ' (' + String(s.start_time).substr(0, 5) + '–' + String(s.end_time).substr(0, 5) + ')').html() + '</option>';
        }).join('');

        function renumberHariMasuk() {
            $('#hariMasukRows .hari-masuk-row').each(function (i) {
                $(this).find('.hm-date').attr('name', 'hari_masuk[' + i + '][date]');
                $(this).find('.hm-shift').attr('name', 'hari_masuk[' + i + '][shift_id]');
            });
        }

        var gantiLiburAwal = 0; // saved value; each day added above it needs a worked date

        // Every Sunday / holiday worked paired with the ganti libur that used it (newest first)
        var GANTI_LIBUR_STATUS = {
            tersedia: '<span class="badge badge-success">Tersedia</span>',
            terjadwal: '<span class="badge badge-secondary">Terjadwal</span>',
            diajukan: '<span class="badge badge-info">Sedang diajukan</span>',
            dipakai: '<span class="badge badge-dark">Sudah dipakai</span>',
            lama: '<span class="badge badge-light" title="Dipakai sebelum ganti libur dicatat per tanggal">Terpakai (lama)</span>'
        };

        var gantiLiburRows = [];
        var esc = function (t) { return $('<div>').text(t || '').html(); };

        function renderGantiLiburTanggal(rows) {
            gantiLiburRows = rows = rows || [];
            var showLama = $('#toggleLama').is(':checked');
            var lama = rows.filter(function (r) { return r.status === 'lama'; }).length;
            $('#gantiLiburTanggalList').html($.map(rows, function (r, i) {
                // Only the pairing of a ganti libur is edited here; worked days come from the schedule
                var edit = r.pengajuan_id
                    ? '<button type="button" class="btn btn-xs btn-outline-primary py-0 px-1 btn-edit-hari-masuk" data-i="' + i + '" title="Ubah hari masuk pengganti"><i class="fas fa-pen"></i></button>'
                    : '';
                return '<tr data-i="' + i + '" class="' + (r.status === 'lama' ? 'row-lama text-muted' + (showLama ? '' : ' d-none') : '') + '">'
                    + '<td class="hm-cell">' + (r.masuk ? esc(r.masuk) + '<div class="text-muted">' + esc(r.keterangan) + '</div>' : '<span class="text-muted">— (saldo lama)</span>') + '</td>'
                    + '<td>' + (esc(r.shift) || '-') + '</td>'
                    + '<td>' + (r.libur ? esc(r.libur) : '<span class="text-muted">-</span>') + '</td>'
                    + '<td>' + (GANTI_LIBUR_STATUS[r.status] || '')
                    + (r.terbalik ? ' <span class="badge badge-warning" title="Hari masuk tidak sebelum tanggal libur. Ubah ke hari masuk sebelum liburnya.">Masuk setelah libur</span>' : '') + '</td>'
                    + '<td class="text-nowrap act-cell">' + edit + '</td></tr>';
            }).join(''));
            $('#countLama').text(lama);
            $('#toggleLamaWrap').toggleClass('d-none', lama === 0);
            $('#gantiLiburTanggalGroup').toggleClass('d-none', rows.length === 0);
        }

        function setTanpaTanggal(n) {
            $('#ganti_libur_tanpa_tanggal').toggleClass('d-none', !(n > 0))
                .text(n > 0 ? n + ' hari belum punya tanggal masuk' : '');
            // Only an existing jatah can get dates for its undated balance
            $('#btnLengkapiTanggal').toggleClass('d-none', !(n > 0 && $('#jatah_libur_id').val()));
            $('#lengkapiTanggalForm').addClass('d-none');
        }

        $('#btnLengkapiTanggal').on('click', function () {
            $('#lengkapi_date').attr('max', TODAY).val('');
            $('#lengkapi_shift').html(shiftOptions);
            $('#lengkapi_note').attr('class', 'small mt-1').text('');
            $('#lengkapiTanggalForm').removeClass('d-none');
        });

        $('#btnBatalLengkapi').on('click', function () {
            $('#lengkapiTanggalForm').addClass('d-none');
        });

        $('#lengkapi_date').on('change', function () {
            var d = this.value, $note = $('#lengkapi_note');
            if (!d) { $note.text(''); return; }
            if (d > TODAY) {
                $note.attr('class', 'small mt-1 text-danger').text('Tanggal tidak boleh setelah hari ini.');
            } else if (liburNasional[d]) {
                $note.attr('class', 'small mt-1 text-success').text('Libur nasional: ' + liburNasional[d]);
            } else if (new Date(d + 'T00:00:00').getDay() === 0) {
                $note.attr('class', 'small mt-1 text-success').text('Hari Minggu');
            } else {
                $note.attr('class', 'small mt-1 text-danger').text('Bukan hari Minggu / libur nasional.');
            }
        });

        $('#btnSimpanLengkapi').on('click', function () {
            var $btn = $(this), $note = $('#lengkapi_note');
            $btn.prop('disabled', true);
            $.ajax({
                url: "{{ route('hrd.master.jatah-libur.hari-masuk.lengkapi', ':id') }}".replace(':id', $('#jatah_libur_id').val()),
                method: 'POST',
                data: { hari_masuk: [{ date: $('#lengkapi_date').val(), shift_id: $('#lengkapi_shift').val() }] },
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (res) {
                    renderGantiLiburTanggal(res.ganti_libur_tanggal);
                    setTanpaTanggal(res.ganti_libur_tanpa_tanggal);
                    table.ajax.reload(null, false);
                },
                error: function (xhr) {
                    var errors = (xhr.responseJSON && xhr.responseJSON.errors) || {};
                    var first = Object.keys(errors).map(function (k) { return errors[k][0]; })[0];
                    $note.attr('class', 'small mt-1 text-danger').text(first || (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menyimpan');
                },
                complete: function () { $btn.prop('disabled', false); }
            });
        });

        $('#toggleLama').on('change', function () {
            $('#gantiLiburTanggalList .row-lama').toggleClass('d-none', !this.checked);
        });

        // Free worked days (not used by any ganti libur) a pairing can move to; 'lama' ones included to record history.
        // Masuk dulu baru libur: only days already worked and before the libur's first day.
        function hariMasukOptions(selected, liburMulai) {
            var statusText = { tersedia: 'Tersedia', terjadwal: 'Terjadwal', lama: 'Terpakai (lama)' };
            return '<option value="">Pilih hari masuk</option>' + $.map(gantiLiburRows, function (r) {
                var free = r.date && !r.pengajuan_id && r.date <= TODAY && r.date < liburMulai;
                if (!free && r.date !== selected) return null;
                return '<option value="' + r.date + '"' + (r.date === selected ? ' selected' : '') + '>'
                    + esc(r.masuk + (r.keterangan ? ' – ' + r.keterangan : '') + (free ? ' [' + statusText[r.status] + ']' : ' [saat ini]'))
                    + '</option>';
            }).join('');
        }

        $('#gantiLiburTanggalList').on('click', '.btn-edit-hari-masuk', function () {
            renderGantiLiburTanggal(gantiLiburRows); // one row in edit mode at a time
            var i = $(this).data('i'), r = gantiLiburRows[i];
            var $tr = $('#gantiLiburTanggalList tr[data-i="' + i + '"]');
            var n = r.date ? 1 : r.jumlah_hari; // a ganti libur without dates must name all its days
            var options = hariMasukOptions(r.date, r.libur_mulai), selects = '';
            for (var k = 0; k < n; k++) {
                selects += '<select class="form-control form-control-sm hm-baru mb-1">' + options + '</select>';
            }
            // Placeholder (+ the current day) only: nothing else can be chosen
            if ($('<select>' + options + '</select>').find('option').length <= (r.date ? 2 : 1)) {
                selects += '<div class="text-muted">Tidak ada hari masuk lain sebelum tanggal libur ini.</div>';
            }
            $tr.find('.hm-cell').html(selects + '<div class="text-danger hm-edit-error"></div>');
            $tr.find('.act-cell').html(
                '<button type="button" class="btn btn-xs btn-success py-0 px-1 btn-save-hari-masuk" data-i="' + i + '" title="Simpan"><i class="fas fa-check"></i></button> '
                + '<button type="button" class="btn btn-xs btn-secondary py-0 px-1 btn-cancel-hari-masuk" title="Batal"><i class="fas fa-times"></i></button>'
            );
        });

        $('#gantiLiburTanggalList').on('click', '.btn-cancel-hari-masuk', function () {
            renderGantiLiburTanggal(gantiLiburRows);
        });

        $('#gantiLiburTanggalList').on('click', '.btn-save-hari-masuk', function () {
            var $btn = $(this), r = gantiLiburRows[$btn.data('i')], $tr = $btn.closest('tr');
            var baru = $tr.find('.hm-baru').map(function () { return this.value; }).get();
            var $err = $tr.find('.hm-edit-error');
            if (baru.indexOf('') !== -1) { $err.text('Pilih hari masuk.'); return; }
            if (r.date && baru[0] === r.date) { renderGantiLiburTanggal(gantiLiburRows); return; }

            $btn.prop('disabled', true);
            $.ajax({
                url: "{{ route('hrd.master.jatah-libur.hari-masuk.update', ':id') }}".replace(':id', $('#jatah_libur_id').val()),
                method: 'PUT',
                data: { pengajuan_id: r.pengajuan_id, lama: r.date, baru: baru },
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (res) {
                    renderGantiLiburTanggal(res.ganti_libur_tanggal);
                    setTanpaTanggal(res.ganti_libur_tanpa_tanggal);
                    table.ajax.reload(null, false);
                },
                error: function (xhr) {
                    $btn.prop('disabled', false);
                    var errors = (xhr.responseJSON && xhr.responseJSON.errors) || {};
                    var first = Object.keys(errors).map(function (k) { return errors[k][0]; })[0];
                    $err.text(first || (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menyimpan');
                }
            });
        });

        function resetHariMasuk(saldo, tanpaTanggal, tanggal) {
            $('#toggleLama').prop('checked', false);
            renderGantiLiburTanggal(tanggal);
            gantiLiburAwal = parseInt(saldo, 10) || 0;
            $('#jatah_ganti_libur').val(gantiLiburAwal);
            $('#hariMasukRows').empty();
            $('#hari_masuk-error').text('');
            setTanpaTanggal(tanpaTanggal);
            syncHariMasukRows();
        }

        // One date + shift row per day added; rows already filled in are kept
        function syncHariMasukRows() {
            var need = Math.max(0, (parseInt($('#jatah_ganti_libur').val(), 10) || 0) - gantiLiburAwal);
            var $rows = $('#hariMasukRows .hari-masuk-row');
            $rows.slice(need).remove();
            for (var i = $rows.length; i < need; i++) {
                $('#hariMasukRows').append(
                    '<div class="form-row hari-masuk-row mb-2">'
                    + '<div class="col-6"><input type="date" class="form-control form-control-sm hm-date" max="' + TODAY + '" required></div>'
                    + '<div class="col-6"><select class="form-control form-control-sm hm-shift" required>' + shiftOptions + '</select></div>'
                    + '<div class="col-12 small hm-note"></div></div>'
                );
            }
            $('#hariMasukGroup').toggleClass('d-none', need === 0);
            renumberHariMasuk();
        }

        $('#jatah_ganti_libur').on('input change', syncHariMasukRows);

        $('#hariMasukRows').on('change', '.hm-date', function () {
            var d = this.value, $note = $(this).closest('.hari-masuk-row').find('.hm-note');
            if (!d) { $note.text(''); return; }
            if (d > TODAY) {
                $note.attr('class', 'col-12 small hm-note text-danger').text('Tanggal tidak boleh setelah hari ini.');
            } else if (liburNasional[d]) {
                $note.attr('class', 'col-12 small hm-note text-success').text('Libur nasional: ' + liburNasional[d]);
            } else if (new Date(d + 'T00:00:00').getDay() === 0) {
                $note.attr('class', 'col-12 small hm-note text-success').text('Hari Minggu');
            } else {
                $note.attr('class', 'col-12 small hm-note text-danger').text('Bukan hari Minggu / libur nasional.');
            }
        });

        // Initialize select2 for employee dropdown
        $('#employee_id').select2({
            dropdownParent: $('#jatahLiburModal'),
            placeholder: "Pilih Karyawan",
            width: '100%'
        });

        // Open modal for adding new jatah libur
        $('#btnAddJatahLibur').on('click', function() {
            $('#jatahLiburModalLabel').text('Tambah Jatah Libur');
            $('#jatahLiburForm')[0].reset();
            $('#jatah_libur_id').val('');
            
            // Show employee selection and ensure required attribute is set
            $('#employee_selection_group').show();
            $('#employee_id').attr('required', 'required');
            
            // Remove any hidden employee_id field if it exists
            $('#hidden_employee_id').remove();
            
            // Load employees without jatah libur
            loadEmployeesWithoutJatahLibur();
            resetHariMasuk(0, 0);

            $('.invalid-feedback').text('');
            $('#jatahLiburModal').modal('show');
        });

        // Load employees without jatah libur
        function loadEmployeesWithoutJatahLibur() {
            $.ajax({
                url: "{{ route('hrd.master.jatah-libur.employees-without-jatah-libur') }}",
                method: 'GET',
                dataType: 'json',
                beforeSend: function() {
                    $('#employee_id').empty().append('<option value="">Loading...</option>');
                },
                error: function(xhr, status, error) {
                    console.error('Error loading employees:', error);
                    console.error('Status:', status);
                    console.error('Response:', xhr.responseText);
                    $('#employee_id').empty().append('<option value="">Error loading data</option>');
                    var errorMsg = 'Failed to load employee data: ' + error;
                    if (xhr.responseText) {
                        try {
                            var jsonResponse = JSON.parse(xhr.responseText);
                            if (jsonResponse.error) {
                                errorMsg += '<br>Details: ' + jsonResponse.error;
                            }
                        } catch (e) {
                            errorMsg += '<br>Response: ' + xhr.responseText.substring(0, 100);
                        }
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        html: errorMsg
                    });
                },
                success: function(response) {
                    console.log('Employees received:', response);
                    $('#employee_id').empty().append('<option value="">Pilih Karyawan</option>');
                    if (response.error) {
                        console.error('Server returned an error:', response.error);
                        $('#employee_id').append('<option value="">Error: ' + response.error + '</option>');
                        return;
                    }
                    if (!Array.isArray(response)) {
                        console.error('Expected array but got:', typeof response);
                        $('#employee_id').append('<option value="">Invalid response format</option>');
                        return;
                    }
                    $.each(response, function(index, employee) {
                        var employeeNumber = employee.employee_number || 'No ID';
                        var employeeName = employee.name || 'Unnamed';
                        $('#employee_id').append('<option value="' + employee.id + '">' + employeeNumber + ' - ' + employeeName + '</option>');
                    });
                    if (response.length === 0) {
                        $('#employee_id').append('<option value="">Semua karyawan sudah memiliki jatah libur</option>');
                    }
                }
            });
        }

        // Reset annual leave to 12 for employees with masa jabatan >= 1 year
        $('#btnResetAnnual').on('click', function() {
            Swal.fire({
                title: 'Reset Jatah Cuti Tahunan',
                text: 'Set semua jatah cuti tahunan menjadi 12 untuk karyawan masa jabatan >= 1 tahun. Lanjutkan?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Reset',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.value) {
                    $.ajax({
                        url: "{{ route('hrd.master.jatah-libur.reset_annual') }}",
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        beforeSend: function() {
                            Swal.showLoading();
                        },
                        success: function(res) {
                            Swal.close();
                            if (res.success) {
                                Swal.fire('Sukses', 'Diperbarui: ' + res.updated + ', Baru: ' + res.created + ', Total terproses: ' + res.total_employees, 'success');
                                table.ajax.reload();
                            } else {
                                Swal.fire('Gagal', res.error || 'Terjadi kesalahan', 'error');
                            }
                        },
                        error: function(xhr) {
                            Swal.close();
                            var msg = 'Server error';
                            if (xhr.responseJSON && xhr.responseJSON.error) msg = xhr.responseJSON.error;
                            Swal.fire('Error', msg, 'error');
                        }
                    });
                }
            });
        });
        // Handle form submission
        $('#jatahLiburForm').on('submit', function(e) {
            e.preventDefault();
            var id = $('#jatah_libur_id').val();
            var url = id ? "{{ route('hrd.master.jatah-libur.update', ':id') }}".replace(':id', id) : "{{ route('hrd.master.jatah-libur.store') }}";
            var method = id ? 'PUT' : 'POST';

            var formData = $(this).serialize();
            formData += '&_token=' + $('meta[name="csrf-token"]').attr('content');
            
            $.ajax({
                url: url,
                method: method,
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                beforeSend: function() {
                    // Clear previous validation errors
                    $('.invalid-feedback').text('');
                    $('.is-invalid').removeClass('is-invalid');
                    $('#saveJatahLibur').attr('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...');
                },
                success: function(response) {
                    Swal.fire({
                        title: 'Sukses!',
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    });
                    $('#jatahLiburModal').modal('hide');
                    table.ajax.reload();
                },
                error: function(xhr) {
                    $('#saveJatahLibur').attr('disabled', false).html('Simpan');
                    if (xhr.status === 422) {
                        var errors = xhr.responseJSON.errors;
                        var hariMasukErrors = [];
                        $.each(errors, function(key, value) {
                            if (key.indexOf('hari_masuk') === 0) {
                                if ($.inArray(value[0], hariMasukErrors) === -1) hariMasukErrors.push(value[0]);
                                return;
                            }
                            $('#' + key).addClass('is-invalid');
                            $('#' + key + '-error').text(value[0]);
                        });
                        $('#hari_masuk-error').text(hariMasukErrors.join(' '));
                    } else if (xhr.status === 500) {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Terjadi kesalahan pada server',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                },
                complete: function() {
                    $('#saveJatahLibur').attr('disabled', false).html('Simpan');
                }
            });
        });

        // Edit Jatah Libur
        $(document).on('click', '.edit-jatah-libur', function() {
            var id = $(this).data('id');
            $('.invalid-feedback').text('');
            $('.is-invalid').removeClass('is-invalid');
            
            $.ajax({
                url: "{{ route('hrd.master.jatah-libur.show', ':id') }}".replace(':id', id),
                method: 'GET',
                success: function(response) {
                    $('#jatahLiburModalLabel').text('Edit Jatah Libur');
                    $('#jatah_libur_id').val(response.id);
                    $('#jatah_cuti_tahunan').val(response.jatah_cuti_tahunan);
                    resetHariMasuk(response.jatah_ganti_libur, response.ganti_libur_tanpa_tanggal, response.ganti_libur_tanggal);
                    
                    // Hide employee selection when editing and remove required attribute
                    $('#employee_selection_group').hide();
                    $('#employee_id').removeAttr('required');
                    
                    // Add hidden input for employee_id to ensure it's submitted with the form
                    if (!$('#hidden_employee_id').length) {
                        $('<input>').attr({
                            type: 'hidden',
                            id: 'hidden_employee_id',
                            name: 'employee_id',
                            value: response.employee_id
                        }).appendTo('#jatahLiburForm');
                    } else {
                        $('#hidden_employee_id').val(response.employee_id);
                    }
                    
                    $('#jatahLiburModal').modal('show');
                }
            });
        });
    });
</script>
@endsection
