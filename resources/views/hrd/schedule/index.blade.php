@extends('layouts.hrd.app')
@section('title', 'HRD | Daftar Karyawan')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-2">
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h3 class="mb-0 font-weight-bold">Jadwal Karyawan Mingguan</h3>
                <div class="text-muted small">Kelola jadwal karyawan per minggu: atur shift dan copy jadwal ke Minggu Ini.</div>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-between align-items-center mb-3" id="week-nav">
        <form id="prev-week-form" method="GET" action="{{ route('hrd.schedule.index') }}" class="d-inline">
            <input type="hidden" name="start_date" id="prev-week-date" value="{{ $startOfWeek->copy()->subWeek()->toDateString() }}">
            <button type="submit" class="btn btn-outline-primary">&laquo; Minggu Sebelumnya</button>
        </form>
        <div class="d-flex flex-column align-items-center">
            <span class="font-weight-bold mb-2" id="week-range">{{ $startOfWeek->format('d M Y') }} - {{ $startOfWeek->copy()->addDays(6)->format('d M Y') }}</span>
            <div class="d-flex align-items-center justify-content-center">
                <button id="this-week-btn" type="button" class="btn btn-outline-success btn-sm mr-2">
                    <i data-feather="rotate-ccw" class="icon-xs mr-1"></i>
                    Kembali ke Minggu Ini
                </button>
                <button id="copy-to-this-week-btn" type="button" class="btn btn-outline-primary btn-sm">
                    <i data-feather="copy" class="icon-xs mr-1"></i>
                    Copy ke Minggu Ini
                </button>
            </div>
        </div>
        <form id="next-week-form" method="GET" action="{{ route('hrd.schedule.index') }}" class="d-inline">
            <input type="hidden" name="start_date" id="next-week-date" value="{{ $startOfWeek->copy()->addWeek()->toDateString() }}">
            <button type="submit" class="btn btn-outline-primary">Minggu Berikutnya &raquo;</button>
        </form>
    </div>
    <div id="ajax-loading" style="display:none;text-align:center;">
        <div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>
    </div>
    <div id="alert-wrapper"></div>
    <div id="jadwal-wrapper">
    @include('hrd.schedule._table', ['dates' => $dates, 'employeesByDivision' => $employeesByDivision, 'shifts' => $shifts, 'allShifts' => $allShifts, 'schedules' => $schedules, 'startOfWeek' => $startOfWeek])
    </div>

    <!-- Shift Management Modal -->
    <div class="modal fade" id="shiftModal" tabindex="-1" role="dialog" aria-labelledby="shiftModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="shift-form">
                    <div class="modal-header">
                        <h5 class="modal-title" id="shiftModalLabel">Tambah Shift</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="shift-id">
                        <div class="form-group">
                            <label for="shift-name">Nama Shift</label>
                            <input type="text" class="form-control" id="shift-name" placeholder="Pagi-Service">
                        </div>
                        <div class="form-group">
                            <label for="shift-start">Jam Mulai</label>
                            <input type="time" class="form-control" id="shift-start" step="60" min="00:00" max="23:59">
                        </div>
                        <div class="form-group">
                            <label for="shift-end">Jam Selesai</label>
                            <input type="time" class="form-control" id="shift-end" step="60" min="00:00" max="23:59">
                        </div>
                        <div class="form-group">
                            <label for="shift-color">Warna Shift</label>
                            <input type="color" class="form-control" id="shift-color" value="#007bff">
                            <small class="form-text text-muted">Pilih warna background untuk shift ini.</small>
                        </div>
                        <div class="form-group">
                            <label for="shift-active">Status</label>
                            <select class="form-control" id="shift-active">
                                <option value="1">Aktif</option>
                                <option value="0">Tidak Aktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function showLoading(show) {
    document.getElementById('ajax-loading').style.display = show ? 'block' : 'none';
}
function showAlert(type, message) {
    // SweetAlert2
    let icon = 'info';
    if(type === 'success') icon = 'success';
    else if(type === 'danger' || type === 'error') icon = 'error';
    else if(type === 'warning') icon = 'warning';
    swal.fire({
        title: message,
        icon: icon,
        timer: 2000,
        showConfirmButton: false,
        toast: true,
        position: 'top-end'
    });
}
// State for shift modal
var currentShiftMode = null; // 'add' or 'edit'

// Helper to resolve shift color from management table by shift ID
function getShiftColorById(shiftId) {
    if (!shiftId) return null;
    var btn = document.querySelector('#shift-table .shift-edit-btn[data-shift-id="' + shiftId + '"]');
    return btn ? btn.getAttribute('data-shift-color') : null;
}

// Determine appropriate text color (black/white) based on background brightness
function getContrastTextColor(hexColor) {
    if (!hexColor) return '#000000';
    var c = hexColor.trim();
    if (c[0] === '#') c = c.slice(1);
    if (c.length === 3) {
        c = c[0] + c[0] + c[1] + c[1] + c[2] + c[2];
    }
    if (c.length !== 6) return '#000000';
    var r = parseInt(c.substr(0, 2), 16);
    var g = parseInt(c.substr(2, 2), 16);
    var b = parseInt(c.substr(4, 2), 16);
    if (isNaN(r) || isNaN(g) || isNaN(b)) return '#000000';
    var brightness = (r * 299 + g * 587 + b * 114) / 1000;
    return brightness > 150 ? '#000000' : '#ffffff';
}

function updateShiftColor(select) {
    var cell = select.closest('td');
    if (!cell) return;

    // Reset classes and inline styles
    cell.className = 'shift-cell';
    select.className = 'form-control shift-select';
    select.style.removeProperty('background-color');
    select.style.removeProperty('color');

    var selected = select.options[select.selectedIndex];
    if (!selected) return;

    var shiftId = select.value;
    var shiftColor = selected.getAttribute('data-shift-color') || getShiftColorById(shiftId);
    var shiftName = selected.getAttribute('data-shift-name');

    if (shiftColor) {
        // Apply dynamic color from database, use !important to beat theme CSS
        select.style.setProperty('background-color', shiftColor, 'important');
        select.style.setProperty('color', getContrastTextColor(shiftColor), 'important');
    } else if (shiftName) {
        // Fallback to legacy class-based coloring
        cell.classList.add('shift-' + shiftName);
        select.classList.add('shift-' + shiftName);
    }
}
function reapplyShiftColors() {
    document.querySelectorAll('.shift-select').forEach(function(sel){
        // Attach unified handler for color + auto-save
        sel.addEventListener('change', function(){ handleShiftChange(sel); });
        updateShiftColor(sel);
    });
}
function handleShiftChange(select) {
    updateShiftColor(select);
    autoSaveSchedule(select);
}

function autoSaveSchedule(select) {
    var employeeId = select.getAttribute('data-employee-id');
    var date = select.getAttribute('data-date');
    if (!employeeId || !date) return;

    var cell = select.closest('td');
    if (!cell) return;

    // Collect both selects in this cell to send full state (max 2 shifts)
    var selects = cell.querySelectorAll('.shift-select');
    var shiftIds = [];
    selects.forEach(function(s) {
        if (s.value) {
            shiftIds.push(s.value);
        }
    });

    var payload = {
        schedule: {}
    };
    payload.schedule[employeeId] = {};
    payload.schedule[employeeId][date] = shiftIds;

    showLoading(true);
    fetch("{{ route('hrd.schedule.store') }}", {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(payload)
    })
    .then(function(res){ return res.json(); })
    .then(function(data){
        if (data && data.success) {
            // Reload current week view so delete buttons and state reflect saved schedules
            var weekStart = document.getElementById('week-start').value;
            fetch("{{ route('hrd.schedule.index') }}?start_date=" + weekStart, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(res){ return res.text(); })
            .then(function(html){
                document.getElementById('jadwal-wrapper').innerHTML = html;
                reapplyShiftColors();
                updateWeekNav();
                attachNavEvents();
                initShiftDataTable();
                showLoading(false);
                showAlert('success', 'Jadwal otomatis disimpan');
            });
        } else {
            showAlert('danger', 'Gagal menyimpan jadwal');
            showLoading(false);
        }
    })
    .catch(function(){
        showAlert('danger', 'Gagal menyimpan jadwal');
        showLoading(false);
    });
}
function updateWeekNav() {
    var weekStartEl = document.getElementById('week-start');
    var weekEndEl = document.getElementById('week-end');
    if (!weekStartEl || !weekEndEl) return;
    var weekStart = weekStartEl.value;
    var weekEnd = weekEndEl.value;
    var options = { day: '2-digit', month: 'short', year: 'numeric' };
    document.getElementById('week-range').textContent =
        new Date(weekStart).toLocaleDateString('id-ID', options) + ' - ' +
        new Date(weekEnd).toLocaleDateString('id-ID', options);
    // Update hidden input prev/next week agar navigasi bisa ke minggu berapapun
    var prevWeekDate = new Date(weekStart);
    prevWeekDate.setDate(prevWeekDate.getDate() - 7);
    document.getElementById('prev-week-date').value = prevWeekDate.toISOString().slice(0,10);
    var nextWeekDate = new Date(weekStart);
    nextWeekDate.setDate(nextWeekDate.getDate() + 7);
    document.getElementById('next-week-date').value = nextWeekDate.toISOString().slice(0,10);
}

// Inisialisasi DataTable untuk manajemen shift
function initShiftDataTable() {
    if (typeof $ === 'undefined' || !$.fn || !$.fn.DataTable) {
        return; // jQuery/DataTables tidak tersedia
    }
    var table = $('#shift-table');
    if (!table.length) return;

    if ($.fn.DataTable.isDataTable('#shift-table')) {
        table.DataTable().destroy();
    }

    var dataTable = table.DataTable({
        paging: false,
        searching: true,
        info: false,
        ordering: true,
        order: [[0, 'asc']],
        initComplete: function () {
            var api = this.api();
            // Sembunyikan filter bawaan DataTables
            $(api.table().container()).find('.dataTables_filter').hide();

            var statusFilter = $('#shift-status-filter');
            // Default: hanya tampilkan Aktif
            statusFilter.val('active');
            api.column(3).search('^\\s*Aktif\\s*$', true, false).draw();

            statusFilter.off('change').on('change', function () {
                var val = $(this).val();
                if (val === 'active') {
                    api.column(3).search('^\\s*Aktif\\s*$', true, false).draw();
                } else if (val === 'inactive') {
                    api.column(3).search('^\\s*Tidak\\s+Aktif\\s*$', true, false).draw();
                } else {
                    api.column(3).search('', false, false).draw();
                }
            });
        }
    });
}

// Make navigation event binding reusable so it can be called
// after AJAX reloads as well as on initial page load.
function attachNavEvents() {
    // Detach event listener lama dengan cloneNode agar event lama benar-benar hilang
    var prevForm = document.getElementById('prev-week-form');
    var nextForm = document.getElementById('next-week-form');
    var thisWeekBtn = document.getElementById('this-week-btn');
    var copyToThisWeekBtn = document.getElementById('copy-to-this-week-btn');
    if (!prevForm || !nextForm || !thisWeekBtn || !copyToThisWeekBtn) {
        return;
    }
    var prevFormClone = prevForm.cloneNode(true);
    var nextFormClone = nextForm.cloneNode(true);
    var thisWeekBtnClone = thisWeekBtn.cloneNode(true);
    var copyToThisWeekBtnClone = copyToThisWeekBtn.cloneNode(true);
    prevForm.parentNode.replaceChild(prevFormClone, prevForm);
    nextForm.parentNode.replaceChild(nextFormClone, nextForm);
    thisWeekBtn.parentNode.replaceChild(thisWeekBtnClone, thisWeekBtn);
    copyToThisWeekBtn.parentNode.replaceChild(copyToThisWeekBtnClone, copyToThisWeekBtn);

    prevFormClone.addEventListener('submit', function(e){
        e.preventDefault();
        var form = this;
        var startDate = document.getElementById('prev-week-date').value;
        showLoading(true);
        fetch(form.action + '?start_date=' + startDate, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.text())
        .then(html => {
            document.getElementById('jadwal-wrapper').innerHTML = html;
            reapplyShiftColors();
            updateWeekNav();
            attachNavEvents();
            initShiftDataTable();
            showLoading(false);
        });
    });
    nextFormClone.addEventListener('submit', function(e){
        e.preventDefault();
        var form = this;
        var startDate = document.getElementById('next-week-date').value;
        showLoading(true);
        fetch(form.action + '?start_date=' + startDate, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.text())
        .then(html => {
            document.getElementById('jadwal-wrapper').innerHTML = html;
            reapplyShiftColors();
            updateWeekNav();
            attachNavEvents();
            initShiftDataTable();
            showLoading(false);
        });
    });
    thisWeekBtnClone.addEventListener('click', function(){
        var now = new Date();
        var nowDay = now.getDay();
        var mondayDiff = (nowDay === 0 ? -6 : 1) - nowDay;
        var monday = new Date(now);
        monday.setDate(now.getDate() + mondayDiff);
        var mondayStr = monday.toISOString().slice(0,10);
        var currentWeekStart = document.getElementById('week-start').value;
        if (currentWeekStart === mondayStr) {
            showAlert('info', 'Sudah berada di minggu ini!');
            return;
        }
        showLoading(true);
        fetch('{{ route('hrd.schedule.index') }}?start_date=' + mondayStr, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.text())
        .then(html => {
            document.getElementById('jadwal-wrapper').innerHTML = html;
            reapplyShiftColors();
            updateWeekNav();
            attachNavEvents();
            initShiftDataTable();
            showLoading(false);
        });
    });

    // Copy schedules FROM currently opened week TO today's week
    copyToThisWeekBtnClone.addEventListener('click', function(){
        var weekStartEl = document.getElementById('week-start');
        if (!weekStartEl) {
            showAlert('danger', 'Tidak dapat menemukan minggu saat ini');
            return;
        }
        var sourceStart = weekStartEl.value;

        // Calculate monday of today's week (local)
        var now = new Date();
        var day = now.getDay(); // 0..6 (Sun..Sat)
        var diffToMonday = (day === 0 ? -6 : 1) - day;
        var monday = new Date(now);
        monday.setDate(now.getDate() + diffToMonday);
        var targetStart = monday.toISOString().slice(0,10);

        if (sourceStart === targetStart) {
            showAlert('info', 'Anda sedang membuka Minggu Ini');
            return;
        }

        swal.fire({
            title: 'Copy jadwal ke Minggu Ini?',
            text: 'Jadwal yang sudah ada di Minggu Ini tidak akan ditimpa.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Copy',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then(function(result){
            if (!result.value) return;

            showLoading(true);
            fetch("{{ route('hrd.schedule.copy_week') }}", {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    target_start_date: targetStart,
                    source_start_date: sourceStart,
                    overwrite: false
                })
            })
            .then(function(res){ return res.json(); })
            .then(function(data){
                if (data && data.success) {
                    showAlert('success', (data.message || 'Berhasil') + (data.inserted ? (' (+' + data.inserted + ' shift)') : ''));
                    // Navigate/reload to today's week so the user can see the result
                    fetch("{{ route('hrd.schedule.index') }}?start_date=" + targetStart, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(function(res){ return res.text(); })
                    .then(function(html){
                        document.getElementById('jadwal-wrapper').innerHTML = html;
                        reapplyShiftColors();
                        updateWeekNav();
                        attachNavEvents();
                        initShiftDataTable();
                        showLoading(false);
                    });
                } else {
                    showAlert('danger', (data && data.message) ? data.message : 'Gagal copy jadwal');
                    showLoading(false);
                }
            })
            .catch(function(){
                showAlert('danger', 'Gagal copy jadwal');
                showLoading(false);
            });
        });
    });
}

document.addEventListener('DOMContentLoaded', function() {
    reapplyShiftColors();
    // Handle delete schedule button click
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-schedule-btn') || (e.target.closest && e.target.closest('.delete-schedule-btn'))) {
            var btn = e.target.classList.contains('delete-schedule-btn') ? e.target : e.target.closest('.delete-schedule-btn');
            var employeeId = btn.getAttribute('data-employee-id');
            var date = btn.getAttribute('data-date');
                var scheduleId = btn.getAttribute('data-schedule-id');
            if (!employeeId || !date) return;
            swal.fire({
                title: 'Hapus jadwal karyawan?',
                    text: 'Jadwal untuk shift ini akan dihapus. Lanjutkan?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then(function(result) {
                if (result.value) {
                    showLoading(true);
                    fetch("{{ route('hrd.schedule.delete') }}", {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content')
                        },
                                body: JSON.stringify({ employee_id: employeeId, date: date, schedule_id: scheduleId })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if(data.success){
                            showAlert('success', 'Jadwal berhasil dihapus!');
                            // Reload the schedule table via AJAX
                            var weekStart = document.getElementById('week-start').value;
                            showLoading(true);
                            fetch("{{ route('hrd.schedule.index') }}?start_date=" + weekStart, {
                                headers: { 'X-Requested-With': 'XMLHttpRequest' }
                            })
                            .then(res => res.text())
                            .then(html => {
                                document.getElementById('jadwal-wrapper').innerHTML = html;
                                reapplyShiftColors();
                                updateWeekNav();
                                attachNavEvents();
                                initShiftDataTable();
                                showLoading(false);
                            });
                        }else{
                            showAlert('danger', 'Gagal menghapus jadwal!');
                            showLoading(false);
                        }
                    })
                    .catch(() => {
                        showAlert('danger', 'Gagal menghapus jadwal!');
                        showLoading(false);
                    });
                }
            });
        }

        // Opsi: Double Shift dari menu dalam sel (hanya untuk hari tersebut)
        if (e.target.classList.contains('option-double-shift') || (e.target.closest && e.target.closest('.option-double-shift'))) {
            e.preventDefault();
            var opt = e.target.classList.contains('option-double-shift') ? e.target : e.target.closest('.option-double-shift');
            var cell = opt.closest('td');
            if (!cell) return;

            var secondRow = cell.querySelector('.second-shift-row');
            if (!secondRow) return;

            var isHidden = secondRow.classList.contains('d-none');
            if (isHidden) {
                secondRow.classList.remove('d-none');
                secondRow.classList.add('d-flex');
            } else {
                secondRow.classList.remove('d-flex');
                secondRow.classList.add('d-none');
            }
            return;
        }

        // Shift management: add new shift
        if (e.target.id === 'btn-add-shift' || (e.target.closest && e.target.closest('#btn-add-shift'))) {
            e.preventDefault();
            openShiftForm('add');
        }

        // Shift management: edit existing shift
        if (e.target.classList.contains('shift-edit-btn') || (e.target.closest && e.target.closest('.shift-edit-btn'))) {
            e.preventDefault();
            var btnEdit = e.target.classList.contains('shift-edit-btn') ? e.target : e.target.closest('.shift-edit-btn');
            openShiftForm('edit', {
                id: btnEdit.getAttribute('data-shift-id'),
                name: btnEdit.getAttribute('data-shift-name'),
                start: btnEdit.getAttribute('data-shift-start'),
                end: btnEdit.getAttribute('data-shift-end'),
                active: btnEdit.getAttribute('data-shift-active') || '1',
                color: btnEdit.getAttribute('data-shift-color') || '#007bff'
            });
        }

        // Shift management: delete existing shift
        if (e.target.classList.contains('shift-delete-btn') || (e.target.closest && e.target.closest('.shift-delete-btn'))) {
            e.preventDefault();
            var btnDel = e.target.classList.contains('shift-delete-btn') ? e.target : e.target.closest('.shift-delete-btn');
            var shiftId = btnDel.getAttribute('data-shift-id');
            if (!shiftId) return;

            swal.fire({
                title: 'Hapus shift ini?',
                text: 'Semua jadwal yang menggunakan shift ini juga akan terhapus. Lanjutkan?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then(function(result){
                if (!result.value) return;
                showLoading(true);
                fetch("{{ route('hrd.master.shift.destroy', ['shift' => '__ID__']) }}".replace('__ID__', shiftId), {
                    method: 'DELETE',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content')
                    }
                })
                .then(function(res){ return res.json(); })
                .then(function(data){
                    if (data && data.success) {
                        showAlert('success', 'Shift berhasil dihapus');
                        // Reload current week view to refresh legend & selects
                        var weekStart = document.getElementById('week-start').value;
                        fetch("{{ route('hrd.schedule.index') }}?start_date=" + weekStart, {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        })
                        .then(function(res){ return res.text(); })
                        .then(function(html){
                            document.getElementById('jadwal-wrapper').innerHTML = html;
                            reapplyShiftColors();
                            updateWeekNav();
                            attachNavEvents();
                            initShiftDataTable();
                            showLoading(false);
                        });
                    } else {
                        showAlert('danger', 'Gagal menghapus shift');
                        showLoading(false);
                    }
                })
                .catch(function(){
                    showAlert('danger', 'Gagal menghapus shift');
                    showLoading(false);
                });
            });
            return;
        }
    });
    attachNavEvents();
    initShiftDataTable();
    // Legacy manual submit (kept for safety, though jadwal sekarang auto-save)
    document.addEventListener('submit', function(e){
        if(e.target && e.target.id === 'jadwal-form'){
            e.preventDefault();
            var form = e.target;
            showLoading(true);
            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if(data.success){
                    showAlert('success', 'Jadwal berhasil disimpan!');
                }else{
                    showAlert('danger', 'Gagal menyimpan jadwal!');
                }
                showLoading(false);
            });
        }
    });
});
// Open Bootstrap modal for shift add/edit
function openShiftForm(mode, shift) {
    currentShiftMode = mode;
    shift = shift || {};

    var title = mode === 'edit' ? 'Edit Shift' : 'Tambah Shift';
    var name = shift.name || '';
    var start = shift.start || '';
    var end = shift.end || '';
    var active = (typeof shift.active !== 'undefined') ? String(shift.active) : '1';
    var color = shift.color || '#007bff';

    var modal = $('#shiftModal');
    modal.find('#shiftModalLabel').text(title);
    modal.find('#shift-id').val(shift.id || '');
    modal.find('#shift-name').val(name);
    modal.find('#shift-start').val(start);
    modal.find('#shift-end').val(end);
    modal.find('#shift-active').val(active);
    modal.find('#shift-color').val(color);

    modal.modal('show');
}

// Handle shift form submit via AJAX
document.addEventListener('DOMContentLoaded', function(){
    var shiftForm = document.getElementById('shift-form');
    if (!shiftForm) return;

    shiftForm.addEventListener('submit', function(e){
        e.preventDefault();
        var id = document.getElementById('shift-id').value;
        var nameVal = document.getElementById('shift-name').value.trim();
        var startVal = document.getElementById('shift-start').value.trim();
        var endVal = document.getElementById('shift-end').value.trim();
        var activeVal = document.getElementById('shift-active').value;
        var colorVal = document.getElementById('shift-color').value;

        if (!nameVal || !startVal || !endVal) {
            showAlert('danger', 'Semua field shift wajib diisi');
            return;
        }

        var url, method;
        if (currentShiftMode === 'edit' && id) {
            url = "{{ route('hrd.master.shift.update', ['shift' => '__ID__']) }}".replace('__ID__', id);
            method = 'PUT';
        } else {
            url = "{{ route('hrd.master.shift.store') }}";
            method = 'POST';
        }

        showLoading(true);
        fetch(url, {
            method: method,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                name: nameVal,
                start_time: startVal,
                end_time: endVal,
                active: activeVal,
                color: colorVal
            })
        })
        .then(function(res){ return res.json(); })
        .then(function(data){
            if (data && data.success) {
                showAlert('success', 'Shift berhasil disimpan');
                $('#shiftModal').modal('hide');
                // Reload current week view to refresh legend & selects
                var weekStart = document.getElementById('week-start').value;
                fetch("{{ route('hrd.schedule.index') }}?start_date=" + weekStart, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function(res){ return res.text(); })
                .then(function(html){
                    document.getElementById('jadwal-wrapper').innerHTML = html;
                    reapplyShiftColors();
                    updateWeekNav();
                    attachNavEvents();
                    initShiftDataTable();
                    showLoading(false);
                });
            } else {
                showAlert('danger', 'Gagal menyimpan shift');
                showLoading(false);
            }
        })
        .catch(function(){
            showAlert('danger', 'Gagal menyimpan shift');
            showLoading(false);
        });
    });
});
</script>
@endpush
