@extends('layouts.hrd.app')
@section('content')
<div class="container-fluid">
    <h4>Jadwal Dokter</h4>
    <div class="row mb-3">
        <div class="col-md-3">
            <input type="month" id="monthPicker" class="form-control" value="{{ $month }}">
        </div>
    </div>
    <div id="calendarContainer"></div>
</div>

<!-- Modal Pilih Dokter -->
<div class="modal fade" id="dokterModal" tabindex="-1" aria-labelledby="dokterModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="dokterModalLabel">Pilih Dokter</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="jadwalForm">
          <input type="hidden" id="jadwalDate" name="date">
          <div class="form-group">
            <label>Dokter</label>
            <select multiple class="form-control" id="dokterSelect" name="dokter_ids[]" style="width:100%">
              @foreach($shifts as $shift)
                <option value="{{ $shift->dokter_id }}" data-jam_mulai="{{ $shift->jam_mulai }}" data-jam_selesai="{{ $shift->jam_selesai }}">
                  {{ $shift->dokter->user->name ?? $shift->dokter_id }} ({{ $shift->jam_mulai }} - {{ $shift->jam_selesai }})
                </option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            <label>Jam Mulai</label>
            <input type="time" class="form-control" name="jam_mulai" id="jamMulai" readonly required>
          </div>
          <div class="form-group">
            <label>Jam Selesai</label>
            <input type="time" class="form-control" name="jam_selesai" id="jamSelesai" readonly required>
          </div>
          <button type="submit" class="btn btn-primary">Simpan</button>
        </form>
      </div>
    </div>
  </div>
</div>

@endsection
@section('scripts')
<style>
.calendar-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
.calendar-table th, .calendar-table td {
  border: 1px solid #ddd;
  text-align: center;
  vertical-align: top;
  height: 120px;
  width: 14.28%; /* 100% / 7 hari */
  max-width: 180px;
  min-width: 120px;
  overflow-wrap: break-word;
}
.calendar-table th { background: #f8f9fa; }
.calendar-day { cursor: pointer; }
.doctor-list { font-size: 0.95em; margin-top: 5px; }
</style>
<!-- Select2 CDN -->
{{-- <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script> --}}
<script>
$(document).ready(function() {
  $('#dokterSelect').select2({
    dropdownParent: $('#dokterModal'),
    width: '100%',
    placeholder: 'Pilih dokter...'
  });
  // Ambil shift dokter saat select berubah
  $('#dokterSelect').on('change', function() {
    var selected = $(this).find('option:selected').first();
    var jamMulai = selected.data('jam_mulai') || '';
    var jamSelesai = selected.data('jam_selesai') || '';
    $('#jamMulai').val(jamMulai);
    $('#jamSelesai').val(jamSelesai);
  });
  renderCalendar($('#monthPicker').val());
});
function getDaysInMonth(year, month) {
  return new Date(year, month, 0).getDate();
}
function renderCalendar(month) {
  let [y, m] = month.split('-');
  y = parseInt(y); m = parseInt(m);
  let firstDay = new Date(y, m-1, 1).getDay();
  let days = getDaysInMonth(y, m);
  $.get("{{ route('hrd.dokter-schedule.get') }}", {month: month}, function(data) {
    let calendar = '<table class="calendar-table"><thead><tr>';
    let daysOfWeek = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    for(let d=0; d<7; d++) calendar += `<th>${daysOfWeek[d]}</th>`;
    calendar += '</tr></thead><tbody><tr>';
    let dayCell = 0;
    for(let i=0; i<firstDay; i++) { calendar += '<td></td>'; dayCell++; }
    for(let d=1; d<=days; d++) {
      let dateStr = month+'-'+(d<10?'0'+d:d);
      let jadwal = data.filter(j => j.date === dateStr);
      let dokterList = jadwal.map(j => {
        let nama = j.dokter ? j.dokter.user?.name : '-';
        let jamMulai = j.jam_mulai ? j.jam_mulai : '';
        let jamSelesai = j.jam_selesai ? j.jam_selesai : '';
        return `${nama} <span style='font-size:0.9em;color:#888;'>(${jamMulai} - ${jamSelesai})</span>`;
      }).join('<br>');
      calendar += `<td class='calendar-day' data-date='${dateStr}'><strong>${d}</strong><div class='doctor-list'>${dokterList}</div><button class='btn btn-xs btn-info mt-2' onclick='showModal("${dateStr}")'>Atur</button></td>`;
      dayCell++;
      if(dayCell % 7 === 0 && d !== days) calendar += '</tr><tr>';
    }
    while(dayCell % 7 !== 0) { calendar += '<td></td>'; dayCell++; }
    calendar += '</tr></tbody></table>';
    $('#calendarContainer').html(calendar);
  });
}
function showModal(date) {
  $('#jadwalDate').val(date);
  $('#dokterModal').modal('show');
}
$('#monthPicker').on('change', function() {
  renderCalendar(this.value);
});
$('#jadwalForm').on('submit', function(e) {
  e.preventDefault();
  $.ajax({
    url: "{{ route('hrd.dokter-schedule.store') }}",
    method: 'POST',
    data: $(this).serialize(),
    headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
    success: function() {
      $('#dokterModal').modal('hide');
      renderCalendar($('#monthPicker').val());
    }
  });
});
$(document).ready(function() {
  renderCalendar($('#monthPicker').val());
});
</script>
@endsection
