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
        sel.removeEventListener('change', function(){ updateShiftColor(sel); });
        sel.addEventListener('change', function(){ updateShiftColor(sel); });
        updateShiftColor(sel);
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
</script>
@endpush
