@extends('layouts.hrd.app')
@section('title', 'HRD | Daftar Karyawan')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <h4 class="mb-3">Jadwal Karyawan Mingguan</h4>
    <div class="d-flex justify-content-between align-items-center mb-3" id="week-nav">
        <form id="prev-week-form" method="GET" action="{{ route('hrd.schedule.index') }}" class="d-inline">
            <input type="hidden" name="start_date" id="prev-week-date" value="{{ $startOfWeek->copy()->subWeek()->toDateString() }}">
            <button type="submit" class="btn btn-outline-primary">&laquo; Minggu Sebelumnya</button>
        </form>
        <div class="d-flex flex-column align-items-center">
            <span class="font-weight-bold mb-2" id="week-range">{{ $startOfWeek->format('d M Y') }} - {{ $startOfWeek->copy()->addDays(6)->format('d M Y') }}</span>
            <button id="this-week-btn" type="button" class="btn btn-outline-success btn-sm">Kembali ke Minggu Ini</button>
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
    @include('hrd.schedule._table', compact('dates','employeesByDivision','shifts','schedules','startOfWeek'))
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
function updateShiftColor(select) {
    var cell = select.closest('td');
    cell.className = 'shift-cell';
    select.className = 'form-control shift-select';
    var selected = select.options[select.selectedIndex];
    var shiftName = selected.getAttribute('data-shift-name');
    if(shiftName) {
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
            showAlert('success', 'Jadwal otomatis disimpan');
        } else {
            showAlert('danger', 'Gagal menyimpan jadwal');
        }
        showLoading(false);
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
                end: btnEdit.getAttribute('data-shift-end')
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
        }
    });
    function attachNavEvents() {
        // Detach event listener lama dengan cloneNode agar event lama benar-benar hilang
        var prevForm = document.getElementById('prev-week-form');
        var nextForm = document.getElementById('next-week-form');
        var thisWeekBtn = document.getElementById('this-week-btn');
        var prevFormClone = prevForm.cloneNode(true);
        var nextFormClone = nextForm.cloneNode(true);
        var thisWeekBtnClone = thisWeekBtn.cloneNode(true);
        prevForm.parentNode.replaceChild(prevFormClone, prevForm);
        nextForm.parentNode.replaceChild(nextFormClone, nextForm);
        thisWeekBtn.parentNode.replaceChild(thisWeekBtnClone, thisWeekBtn);

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
                showLoading(false);
            });
        });
    }
    attachNavEvents();
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

function openShiftForm(mode, shift) {
    shift = shift || {};
    var title = mode === 'edit' ? 'Edit Shift' : 'Tambah Shift';
    var name = shift.name || '';
    var start = shift.start || '';
    var end = shift.end || '';

    swal.fire({
        title: title,
        html:
            '<div class="text-left">' +
                '<label>Nama Shift</label>' +
                '<input id="swal-shift-name" class="swal2-input" placeholder="Pagi-Service" value="' + name + '">' +
                '<label>Jam Mulai (HH:MM)</label>' +
                '<input id="swal-shift-start" class="swal2-input" placeholder="08:00" value="' + start + '">' +
                '<label>Jam Selesai (HH:MM)</label>' +
                '<input id="swal-shift-end" class="swal2-input" placeholder="17:00" value="' + end + '">' +
            '</div>',
        focusConfirm: false,
        showCancelButton: true,
        preConfirm: function () {
            var nameVal = document.getElementById('swal-shift-name').value.trim();
            var startVal = document.getElementById('swal-shift-start').value.trim();
            var endVal = document.getElementById('swal-shift-end').value.trim();
            if (!nameVal || !startVal || !endVal) {
                swal.showValidationMessage('Semua field wajib diisi');
                return false;
            }
            return { name: nameVal, start_time: startVal, end_time: endVal };
        }
    }).then(function(result){
        if (!result.value) return;

        var url, method;
        if (mode === 'edit' && shift.id) {
            url = "{{ route('hrd.master.shift.update', ['shift' => '__ID__']) }}".replace('__ID__', shift.id);
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
                'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content')
            },
            body: JSON.stringify(result.value)
        })
        .then(function(res){ return res.json(); })
        .then(function(data){
            if (data && data.success) {
                showAlert('success', 'Shift berhasil disimpan');
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
}
</script>
@endpush
