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
        <h3 class="mb-0 font-weight-bold">Jadwal Dokter Mingguan</h3>
        <div class="text-muted small">Kelola jadwal dokter per minggu: atur shift dan cetak jadwal.</div>
      </div>
      <div class="d-flex align-items-center mt-2">
        <button id="printScheduleBtn" class="btn btn-info mr-2">
          <i class="fa fa-print"></i> Print
        </button>
        <input type="month" id="monthPicker" class="form-control mr-2" value="{{ $month }}" style="width:200px;">
        <select id="clinicFilter" class="form-control" style="width:220px;">
          <option value="">- Semua Klinik -</option>
          @foreach(\App\Models\ERM\Klinik::all() as $klinik)
            <option value="{{ $klinik->id }}">{{ $klinik->nama }}</option>
          @endforeach
        </select>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-lg-3 mb-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="font-weight-bold">Daftar Dokter</div>
            <button class="btn btn-sm btn-primary" id="addShiftBtn">Tambah Shift</button>
          </div>
          <div class="table-responsive" style="max-height: calc(100vh - 260px); overflow: auto;">
            <table class="table table-sm table-hover mb-0" id="availableDoctorsTable">
              <thead>
                <tr>
                  <th>Dokter</th>
                  <th class="text-muted">Shift</th>
                </tr>
              </thead>
              <tbody>
                @php
                  $doctorRows = collect($shifts ?? [])->unique('dokter_id');
                @endphp
                @foreach($doctorRows as $shift)
                  @php
                    $dokterId = $shift->dokter_id;
                    $dokterName = $shift->dokter->user->name ?? $dokterId;
                    $clinicId = $shift->dokter->klinik_id ?? '';
                    $jamMulai = $shift->jam_mulai ?? '';
                    $jamSelesai = $shift->jam_selesai ?? '';
                  @endphp
                    <tr class="doctor-draggable"
                      draggable="true"
                      data-shift-id="{{ $shift->id ?? '' }}"
                      data-dokter-id="{{ $dokterId }}"
                      data-dokter-name="{{ $dokterName }}"
                      data-clinic-id="{{ $clinicId }}"
                      data-jam-mulai="{{ $jamMulai }}"
                      data-jam-selesai="{{ $jamSelesai }}">
                    <td class="align-middle"><span class="doctor-name-sidebar">{{ $dokterName }}</span></td>
                    <td class="align-middle text-muted small">{{ $jamMulai }}{{ ($jamMulai || $jamSelesai) ? ' - ' : '' }}{{ $jamSelesai }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          
          <!-- Add Shift Modal -->
          <div class="modal fade" id="addShiftModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title">Tambah Shift Dokter</h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
                <form id="addShiftForm">
                  <input type="hidden" name="shift_id" id="addShiftId" value="">
                  <div class="modal-body">
                    @php $allDokters = \App\Models\ERM\Dokter::with('user')->get(); @endphp
                    <div class="form-group">
                      <label>Dokter</label>
                      <select name="dokter_id" id="addDokterId" class="form-control" required>
                        <option value="">-- Pilih Dokter --</option>
                        @foreach($allDokters as $d)
                          <option value="{{ $d->id }}">{{ $d->user->name ?? $d->id }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="form-group">
                      <label>Jam Mulai</label>
                      <input type="time" name="jam_mulai" class="form-control" required>
                    </div>
                    <div class="form-group">
                      <label>Jam Selesai</label>
                      <input type="time" name="jam_selesai" class="form-control" required>
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
          <div class="text-muted small mt-2">Drag & drop dokter ke tanggal pada kalender.</div>
        </div>
      </div>
    </div>
    <div class="col-lg-9">
      <div id="calendarContainer"></div>
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
  font-size: 1.6rem;
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
.doctor-name-sidebar {
  display: inline-block;
  max-width: 220px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.doctor-card {
  margin-bottom: 0.5rem;
  box-shadow: 0 2px 6px rgba(0,0,0,0.04);
  border-radius: 0.5rem;
  border: 1px solid #1976d2;
  /* background: #fff; */
  padding: 0.5rem 0.7rem 0.2rem 0.7rem;
  position: relative;
}
.schedule-draggable {
  cursor: pointer;
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

}

/* top-right delete button on schedule card */
.doctor-delete-topright {
  position: absolute;
  top: 6px;
  right: 6px;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: #e74c3c;
  color: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: none;
  box-shadow: none;
  cursor: pointer;
  font-size: 12px;
  line-height: 1;
}

.doctor-delete-topright:hover { filter: brightness(0.95); }
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
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
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
  window.__isDraggingSchedule = false;

  $('#printScheduleBtn').on('click', function() {
    var month = $('#monthPicker').val();
    var clinicId = $('#clinicFilter').val();
    var url = `/hrd/dokter-schedule/print?month=${month}&clinic_id=${clinicId}`;
    window.open(url, '_blank');
  });

  window.filterDoctorListByClinic = function() {
    var clinicId = ($('#clinicFilter').val() || '').toString();
    $('#availableDoctorsTable tbody tr').each(function() {
      var rowClinic = ($(this).attr('data-clinic-id') || '').toString();
      if (!clinicId) {
        $(this).show();
      } else {
        $(this).toggle(rowClinic === clinicId);
      }
    });
  };

  window.bindDoctorDragEvents = function() {
    document.querySelectorAll('.doctor-draggable').forEach(function(row) {
      row.addEventListener('dragstart', function(e) {
        var payload = {
          dokter_id: row.getAttribute('data-dokter-id'),
          dokter_name: row.getAttribute('data-dokter-name'),
          jam_mulai: row.getAttribute('data-jam-mulai') || '',
          jam_selesai: row.getAttribute('data-jam-selesai') || ''
        };
        e.dataTransfer.setData('text/plain', JSON.stringify(payload));
      });
    });
  };

  window.bindCalendarDropTargets = function() {
    // Make existing schedules draggable (move between days)
    document.querySelectorAll('#calendarContainer .schedule-draggable').forEach(function(card) {
      card.addEventListener('dragstart', function(e) {
        window.__isDraggingSchedule = true;
        var payload = {
          schedule_id: card.getAttribute('data-schedule-id'),
          dokter_id: card.getAttribute('data-dokter-id') || '',
          dokter_name: card.getAttribute('data-dokter-name') || '',
          jam_mulai: card.getAttribute('data-jam-mulai') || '',
          jam_selesai: card.getAttribute('data-jam-selesai') || '',
          source_date: card.getAttribute('data-date') || ''
        };
        e.dataTransfer.setData('text/plain', JSON.stringify(payload));
      });

      card.addEventListener('dragend', function() {
        setTimeout(function(){ window.__isDraggingSchedule = false; }, 150);
      });
    });

    document.querySelectorAll('#calendarContainer .calendar-day').forEach(function(cell) {
      cell.addEventListener('dragover', function(e) {
        e.preventDefault();
      });
      cell.addEventListener('drop', function(e) {
        e.preventDefault();
        var dateStr = cell.getAttribute('data-date');
        if (!dateStr) return;

        var raw = e.dataTransfer.getData('text/plain');
        if (!raw) return;

        var data;
        try {
          data = JSON.parse(raw);
        } catch (err) {
          return;
        }

        // If dropping an existing schedule card: move it to this date (keep its times)
        if (data && data.schedule_id) {
          $.ajax({
            url: "{{ route('hrd.dokter-schedule.move', ['id' => '__ID__']) }}".replace('__ID__', data.schedule_id),
            method: 'POST',
            data: {
              _token: '{{ csrf_token() }}',
              target_date: dateStr
            },
            success: function() {
              renderCalendar($('#monthPicker').val());
            },
            error: function() {
              Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: 'Gagal memindahkan jadwal dokter!'
              });
            }
          });
          return;
        }

        var dokterId = data.dokter_id;
        var dokterName = data.dokter_name;
        var defaultMulai = data.jam_mulai || '';
        var defaultSelesai = data.jam_selesai || '';
        if (!dokterId) return;

        Swal.fire({
          title: 'Atur jam dokter',
          html:
            '<div class="text-left mb-2"><b>' + (dokterName || 'Dokter') + '</b><div class="text-muted small">' + dateStr + '</div></div>' +
            '<div class="form-group text-left">' +
              '<label class="mb-1">Jam Mulai</label>' +
              '<input type="time" id="swalJamMulai" class="form-control" value="' + defaultMulai + '">' +
            '</div>' +
            '<div class="form-group text-left">' +
              '<label class="mb-1">Jam Selesai</label>' +
              '<input type="time" id="swalJamSelesai" class="form-control" value="' + defaultSelesai + '">' +
            '</div>',
          showCancelButton: true,
          confirmButtonText: 'Simpan',
          cancelButtonText: 'Batal',
          focusConfirm: false,
          preConfirm: function() {
            var jamMulai = document.getElementById('swalJamMulai').value;
            var jamSelesai = document.getElementById('swalJamSelesai').value;
            if (!jamMulai || !jamSelesai) {
              Swal.showValidationMessage('Jam mulai dan jam selesai wajib diisi');
              return false;
            }
            return { jam_mulai: jamMulai, jam_selesai: jamSelesai };
          }
        }).then(function(result) {
          if (!result.value) return;

          $.ajax({
            url: "{{ route('hrd.dokter-schedule.store_single') }}",
            method: 'POST',
            data: {
              _token: '{{ csrf_token() }}',
              date: dateStr,
              dokter_id: dokterId,
              jam_mulai: result.value.jam_mulai,
              jam_selesai: result.value.jam_selesai
            },
            success: function() {
              renderCalendar($('#monthPicker').val());
              Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Jadwal dokter berhasil disimpan!',
                timer: 1200,
                showConfirmButton: false
              });
            },
            error: function() {
              Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: 'Gagal menyimpan jadwal dokter!'
              });
            }
          });
        });
      });
    });
  };

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
  
  // Open Add Shift modal
  $('#addShiftBtn').on('click', function() {
    // prepare modal for creating new shift
    $('#addShiftId').val('');
    $('#addDokterId').val('');
    $('#addShiftForm input[name="jam_mulai"]').val('');
    $('#addShiftForm input[name="jam_selesai"]').val('');
    $('#addShiftModal .modal-title').text('Tambah Shift Dokter');
    $('#addShiftModal button[type="submit"]').text('Simpan');
    $('#addShiftModal').modal('show');
  });

  // Click doctor row to edit shift
  $('#availableDoctorsTable').on('click', '.doctor-draggable', function(e) {
    // ignore if clicking interactive elements inside the row
    if ($(e.target).is('a,button,input,select')) return;
    var row = $(this);
    var shiftId = row.attr('data-shift-id') || '';
    var dokterId = row.attr('data-dokter-id') || '';
    var jamMulai = row.attr('data-jam-mulai') || '';
    var jamSelesai = row.attr('data-jam-selesai') || '';
    $('#addShiftId').val(shiftId);
    $('#addDokterId').val(dokterId);
    $('#addShiftForm input[name="jam_mulai"]').val(jamMulai);
    $('#addShiftForm input[name="jam_selesai"]').val(jamSelesai);
    $('#addShiftModal .modal-title').text(shiftId ? 'Edit Shift Dokter' : 'Tambah Shift Dokter');
    $('#addShiftModal button[type="submit"]').text(shiftId ? 'Simpan Perubahan' : 'Simpan');
    $('#addShiftModal').modal('show');
  });

  // Submit Add Shift form
  $('#addShiftForm').on('submit', function(e) {
    e.preventDefault();
    var data = $(this).serialize();
    var shiftIdVal = $('#addShiftId').val() || '';
    var url = shiftIdVal ? '{{ url("hrd/dokter-shifts/update/") }}' + '/' + shiftIdVal : '{{ route("hrd.dokter-shifts.store") }}';
    $.ajax({
      url: url,
      method: 'POST',
      data: data + '&_token={{ csrf_token() }}',
      success: function(resp) {
        $('#addShiftModal').modal('hide');
        Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Shift dokter berhasil ditambahkan', timer: 900, showConfirmButton: false });
        // Insert or update the doctor row in the available doctors table without full reload
        try {
          var s = resp.shift || {};
          var dokter = s.dokter || {};
          var dokterId = s.dokter_id || (dokter.id || '');
          var dokterName = (dokter.user && dokter.user.name) ? dokter.user.name : (dokter.name || dokterId);
          var jamMulai = s.jam_mulai || '';
          var jamSelesai = s.jam_selesai || '';
          var rowSelector = '#availableDoctorsTable tbody tr[data-dokter-id="' + dokterId + '"]';
          var rowHtml = '<tr class="doctor-draggable" draggable="true" data-shift-id="' + (s.id || '') + '" data-dokter-id="' + dokterId + '" data-dokter-name="' + dokterName + '" data-clinic-id="" data-jam-mulai="' + jamMulai + '" data-jam-selesai="' + jamSelesai + '">'
            + '<td class="align-middle"><span class="doctor-name-sidebar">' + dokterName + '</span></td>'
            + '<td class="align-middle text-muted small">' + jamMulai + (jamMulai || jamSelesai ? ' - ' : '') + jamSelesai + '</td>'
            + '</tr>';
          if ($(rowSelector).length) {
            $(rowSelector).replaceWith(rowHtml);
          } else {
            $('#availableDoctorsTable tbody').prepend(rowHtml);
          }
          if (window.filterDoctorListByClinic) window.filterDoctorListByClinic();
          if (window.bindDoctorDragEvents) window.bindDoctorDragEvents();
        } catch (err) {
          // fallback: reload if anything goes wrong
          setTimeout(function(){ location.reload(); }, 700);
        }
      },
      error: function(xhr) {
        Swal.fire({ icon: 'error', title: 'Gagal', text: 'Gagal menambahkan shift dokter' });
      }
    });
  });
  renderCalendar($('#monthPicker').val());
  if (window.filterDoctorListByClinic) window.filterDoctorListByClinic();
  if (window.bindDoctorDragEvents) window.bindDoctorDragEvents();

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

  // Click schedule card to open edit modal
  $(document).on('click', '.schedule-draggable', function(e) {
    if ($(e.target).closest('.doctor-action-btn, .edit-jam-btn, .delete-jadwal-btn').length) return;
    if (window.__isDraggingSchedule) return;

    var card = $(this);
    var id = card.attr('data-schedule-id');
    if (!id) return;

    $('#editJamId').val(id);
    $('#editJamDate').val(card.attr('data-date') || '');
    $('#editJamDokter').val(card.attr('data-dokter-name') || '');
    $('#editJamMulai').val(card.attr('data-jam-mulai') || '');
    $('#editJamSelesai').val(card.attr('data-jam-selesai') || '');
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

// return a consistent vibrant color per dokter id
function colorForDokter(dokterId) {
  const palette = [
    '#ffffff',
    '#FFD54F',
    '#FF8A65',
    '#FF7043',
    '#BA68C8',
    '#64B5F6',
    '#4DB6AC',
    '#FFB74D',
    '#E57373'
  ];
  let id = parseInt(dokterId);
  if (isNaN(id) || id < 1) return palette[0];
  return palette[(id % (palette.length - 1)) + 1];
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
        return `<div class='card doctor-card shadow-sm mb-2 schedule-draggable'
          style='background-color: ${colorForDokter(j.dokter_id)}; border-color: ${colorForDokter(j.dokter_id)}; color: #222; background-image: none;'
            draggable='true'
            data-schedule-id='${j.id}'
            data-dokter-id='${j.dokter_id || ''}'
            data-dokter-name='${nama}'
            data-jam-mulai='${jamMulai}'
            data-jam-selesai='${jamSelesai}'
            data-date='${j.date}'>
          <button class='doctor-delete-topright delete-jadwal-btn' data-id='${j.id}' title='Hapus Jadwal' aria-label='Hapus Jadwal' type='button'>
            &times;
          </button>
          <div class='card-body p-2' style='background: transparent;'>
            <span class='doctor-name'>${nama}</span>
            <span class='doctor-time'>${jamMulai} - ${jamSelesai}</span>
          </div>
        </div>`;
      }).join('');
      calendar += `<td class='calendar-day' data-date='${dateStr}'>
        <div class='calendar-day-header'>
          <span class='calendar-day-number'>${d}</span>
        </div>
        <div class='doctor-list'>${dokterList}</div>
      </td>`;
      dayCell++;
      if(dayCell % 7 === 0 && d !== days) calendar += '</tr><tr>';
    }
    while(dayCell % 7 !== 0) { calendar += '<td></td>'; dayCell++; }
    calendar += '</tr></tbody></table>';
    $('#calendarContainer').html(calendar);
    if (window.bindCalendarDropTargets) window.bindCalendarDropTargets();
    // Tooltip full name for clamped dokter names
    $('#calendarContainer .doctor-name').each(function(){
      this.title = $(this).text();
    });
  });
}
$('#monthPicker').on('change', function() {
  renderCalendar(this.value);
});
$(document).ready(function() {
  renderCalendar($('#monthPicker').val());
  $('#clinicFilter').on('change', function() {
    renderCalendar($('#monthPicker').val());
    if (window.filterDoctorListByClinic) window.filterDoctorListByClinic();
  });
});
</script>
@endsection
