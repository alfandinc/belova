@extends('layouts.hrd.app')
@section('title', 'HRD | Daftar Karyawan')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection
@section('content')
<div class="container-fluid">
    <h4>Jadwal Dokter</h4>
    <div class="row mb-3">
    <div class="col-md-2">
      <button id="printScheduleBtn" class="btn btn-info btn-block">
        <i class="fa fa-print"></i> Print
      </button>
    </div>
    <div class="col-md-3">
      <input type="month" id="monthPicker" class="form-control" value="{{ $month }}">
    </div>
    <div class="col-md-4">
      <select id="clinicFilter" class="form-control">
        <option value="">- Semua Klinik -</option>
        @foreach(\App\Models\ERM\Klinik::all() as $klinik)
          <option value="{{ $klinik->id }}">{{ $klinik->nama }}</option>
        @endforeach
      </select>
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


<!-- Modal Edit Jam Dokter -->
<div class="modal fade" id="editJamModal" tabindex="-1" aria-labelledby="editJamModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editJamModalLabel">Edit Jam Dokter</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="editJamForm">
        <div class="modal-body">
          <input type="hidden" id="editJamId" name="id">
          <input type="hidden" id="editJamDate" name="date">
          <div class="form-group">
            <label>Dokter</label>
            <input type="text" class="form-control" id="editJamDokter" name="dokter" readonly>
          </div>
          <div class="form-group">
            <label>Jam Mulai</label>
            <input type="time" class="form-control" id="editJamMulai" name="jam_mulai" required>
          </div>
          <div class="form-group">
            <label>Jam Selesai</label>
            <input type="time" class="form-control" id="editJamSelesai" name="jam_selesai" required>
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

@endsection
@section('scripts')
<style>
.calendar-table { width: 100%; border-collapse: separate; border-spacing: 0; table-layout: fixed; }
.calendar-table th, .calendar-table td {
  border: 1px solid #e9ecef;
  text-align: left;
  vertical-align: top;
  height: 140px;
  width: 14.28%;
  max-width: 200px;
  min-width: 120px;
  /* background: #f8fafc; */
  padding: 0.5rem 0.5rem 0.2rem 0.5rem;
}
.calendar-table th {
  text-align: center;
  font-weight: 500;
  height: 32px;
  padding: 0.3rem 0.1rem;
  font-size: 1rem;
  /* background: #23263a; */
  /* color: #fff; */
  vertical-align: middle;
}
.calendar-day { cursor: pointer; position: relative; }
.calendar-day-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.3rem;
}
.calendar-day-number {
  font-size: 2.1rem;
  font-weight: 700;
  color: #007bff;
  margin-right: 0.2rem;
}
.atur-icon-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #1976d2;
  border: none;
  padding: 0 14px;
  margin-left: 6px;
  cursor: pointer;
  color: #fff;
  font-size: 1.4em;
  line-height: 1;
  border-radius: 8px;
  min-width: 22px;
  height: 22px;
  padding: 0 6px;
  box-shadow: 0 1px 4px rgba(25,118,210,0.10);
  transition: background 0.2s, box-shadow 0.2s;
}
  
.atur-icon-btn:hover {
  background: #1565c0;
  box-shadow: 0 4px 14px rgba(25,118,210,0.18);
}

.atur-icon-btn:focus {
  outline: none;
}
.doctor-list { margin-top: 0.2rem; }
.doctor-card {
  margin-bottom: 0.5rem;
  box-shadow: 0 2px 6px rgba(0,0,0,0.04);
  border-radius: 0.5rem;
  border: 1px solid #1976d2;
  /* background: #fff; */
  padding: 0.5rem 0.7rem 0.2rem 0.7rem;
  position: relative;
}
.doctor-actions-row {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 6px;
  margin-top: 6px;
  background: none;
  border: none;
  box-shadow: none;
  position: static;
}

.doctor-action-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 15x;
  height: 15px;
  border-radius: 50%;
  border: none;
  /* background: #f5f6fa; */
  color: #6c757d;
  margin-left: 6px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.07);
  transition: background 0.2s, color 0.2s;
  padding: 0;
}
.doctor-action-btn.edit {
  color: #1976d2;
}
.doctor-action-btn.delete {
  color: #e53935;
}
.doctor-action-btn:hover {
  /* background: #e3e7ed; */
}
.doctor-action-btn.delete:hover {
  /* background: #ffeaea; */
  color: #b71c1c;
}
.doctor-name {
  font-weight: 600;
  font-size: 1.05em;
  /* color: #343a40; */
  margin-bottom: 2px;
  display: block;
}
.doctor-time {
  font-size: 0.95em;
  color: #6c757d;
  display: block;
}
</style>
<!-- Select2 CDN -->
{{-- <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script> --}}
<script>
$(document).ready(function() {
  $('#printScheduleBtn').on('click', function() {
    var month = $('#monthPicker').val();
    var clinicId = $('#clinicFilter').val();
    var url = `/hrd/dokter-schedule/print?month=${month}&clinic_id=${clinicId}`;
    window.open(url, '_blank');
  });
  // Delete jadwal (delegated event handler)
  $(document).on('click', '.delete-jadwal-btn', function(e) {
    e.stopPropagation();
    var id = $(this).data('id');
    Swal.fire({
      title: 'Hapus Jadwal?',
      text: 'Yakin ingin menghapus jadwal dokter ini?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Ya, hapus!',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if (result.value) {
        $.ajax({
          url: '/hrd/dokter-schedule/delete/' + id,
          method: 'POST',
          data: {_token: '{{ csrf_token() }}'},
          success: function() {
            renderCalendar($('#monthPicker').val());
            Swal.fire({
              icon: 'success',
              title: 'Berhasil',
              text: 'Jadwal dokter berhasil dihapus!',
              timer: 1500,
              showConfirmButton: false
            });
          },
          error: function(xhr) {
            Swal.fire({
              icon: 'error',
              title: 'Gagal',
              text: 'Gagal menghapus jadwal dokter!',
            });
          }
        });
      }
    });
  });
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

  // Edit jam modal
  $(document).on('click', '.edit-jam-btn', function(e) {
    e.stopPropagation();
    var id = $(this).data('id');
    var date = $(this).data('date');
    var dokter = $(this).data('dokter');
    var jamMulai = $(this).data('jam_mulai');
    var jamSelesai = $(this).data('jam_selesai');
    $('#editJamId').val(id);
    $('#editJamDate').val(date);
    $('#editJamDokter').val(dokter);
    $('#editJamMulai').val(jamMulai);
    $('#editJamSelesai').val(jamSelesai);
    $('#editJamModal').modal('show');
  });

  $('#editJamForm').on('submit', function(e) {
    e.preventDefault();
    var id = $('#editJamId').val();
    $.ajax({
      url: '/hrd/dokter-schedule/update-jam/' + id,
      method: 'POST',
      data: $(this).serialize(),
      headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
      success: function() {
        $('#editJamModal').modal('hide');
        renderCalendar($('#monthPicker').val());
        Swal.fire({
          icon: 'success',
          title: 'Berhasil',
          text: 'Jam dokter berhasil diupdate!',
          timer: 1500,
          showConfirmButton: false
        });
      },
      error: function(xhr) {
        Swal.fire({
          icon: 'error',
          title: 'Gagal',
          text: 'Gagal mengupdate jam dokter!',
        });
      }
    });
  });
});

function getDaysInMonth(year, month) {
  return new Date(year, month, 0).getDate();
}

function renderCalendar(month) {
  let [y, m] = month.split('-');
  y = parseInt(y); m = parseInt(m);
  let firstDay = new Date(y, m-1, 1).getDay();
  let days = getDaysInMonth(y, m);
  var clinicId = $('#clinicFilter').val();
  $.get("{{ route('hrd.dokter-schedule.get') }}", {month: month, clinic_id: clinicId}, function(data) {
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
        return `<div class='card doctor-card shadow-sm mb-2'>
          <div class='card-body p-2'>
            <span class='doctor-name'>${nama}</span>
            <span class='doctor-time'>${jamMulai} - ${jamSelesai}</span>
            <div class='doctor-actions-row'>
              <button class='doctor-action-btn edit edit-jam-btn' data-id='${j.id}' data-date='${j.date}' data-dokter='${nama}' data-jam_mulai='${jamMulai}' data-jam_selesai='${jamSelesai}' title='Edit Jam'>
                <svg xmlns='http://www.w3.org/2000/svg' width='20' height='20' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' viewBox='0 0 24 24'><path d='M12 20h9'/><path d='M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z'/></svg>
              </button>
              <button class='doctor-action-btn delete delete-jadwal-btn' data-id='${j.id}' title='Hapus Jadwal'>
                <svg xmlns='http://www.w3.org/2000/svg' width='20' height='20' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' viewBox='0 0 24 24'><polyline points='3 6 5 6 21 6'/><path d='M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m5 6v6m4-6v6'/><path d='M10 11V17M14 11V17'/><path d='M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2'/></svg>
              </button>
            </div>
          </div>
        </div>`;
      }).join('');
      calendar += `<td class='calendar-day' data-date='${dateStr}'>
        <div class='calendar-day-header'>
          <span class='calendar-day-number'>${d}</span>
          ${jadwal.length === 0 ? `<button class='atur-icon-btn' title='Tambah Jadwal' onclick='showModal("${dateStr}")'>
            <svg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none'>
              <rect x='4' y='9' width='16' height='6' rx='5' fill='none'/>
              <path d='M12 8v8M8 12h8' stroke='white' stroke-width='2.8' stroke-linecap='round'/>
            </svg>
          </button>` : ''}
        </div>
        <div class='doctor-list'>${dokterList}</div>
      </td>`;
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
      Swal.fire({
        icon: 'success',
        title: 'Berhasil',
        text: 'Jadwal dokter berhasil disimpan!',
        timer: 1500,
        showConfirmButton: false
      });
    },
    error: function(xhr) {
      Swal.fire({
        icon: 'error',
        title: 'Gagal',
        text: 'Gagal menyimpan jadwal dokter!',
      });
    }
  });
});
$(document).ready(function() {
  renderCalendar($('#monthPicker').val());
  $('#clinicFilter').on('change', function() {
    renderCalendar($('#monthPicker').val());
  });
});
</script>
@endsection
