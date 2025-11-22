<!-- Modal Rujuk -->
<div class="modal fade" id="modalRujuk" tabindex="-1" role="dialog" aria-labelledby="modalRujuk" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <form id="form-rujuk">
      <div class="modal-content">
        <div class="modal-header bg-info text-white">
          <h5 class="modal-title" id="modalLabelRujuk">Buat Rujuk / Konsultasi</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true"><i class="la la-times"></i></span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="pasien_id" id="rujuk-pasien-id">
          <input type="hidden" id="rujuk-tgllahir" />

          <!-- Top row: Nama Pasien + No RM -->
          <div class="row mb-2">
            <div class="col-md-8">
              <div class="form-group">
                <label>Nama Pasien</label>
                <input type="text" id="rujuk-nama-pasien" class="form-control" value="" readonly>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>No RM</label>
                <input type="text" id="rujuk-no-rm" class="form-control" value="" readonly>
              </div>
            </div>
          </div>
 

          <!-- Next row: Umur + Jenis Permintaan -->
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Umur</label>
                <input type="text" id="rujuk-age" class="form-control" value="" readonly>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Jenis Permintaan</label>
                <select id="jenis_permintaan" name="jenis_permintaan" class="form-control select2" required>
                  <option value="Rujuk">Rujuk</option>
                  <option value="Konsultasi">Konsultasi</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Next row: Dokter Pengirim + Dokter Tujuan -->
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Dokter Pengirim</label>
                <select id="dokter_pengirim_id" name="dokter_pengirim_id" class="form-control select2">
                  <option value="" selected disabled>Pilih Dokter Pengirim</option>
                    @foreach($dokters as $dokter)
                      <option value="{{ $dokter->id }}" @if(isset($visitation) && $visitation->dokter_id == $dokter->id) selected @endif>{{ $dokter->user->name ?? 'Dokter' }}@if($dokter->spesialisasi) ({{ $dokter->spesialisasi->nama }})@endif</option>
                    @endforeach
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Dokter Tujuan</label>
                <select id="dokter_tujuan_id" name="dokter_id" class="form-control select2" required>
                  <option value="" selected disabled>Pilih Dokter Tujuan</option>
                  @foreach($dokters as $dokter)
                    <option value="{{ $dokter->id }}">{{ $dokter->user->name ?? 'Dokter' }}@if($dokter->spesialisasi) ({{ $dokter->spesialisasi->nama }})@endif</option>
                  @endforeach
                </select>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Keterangan</label>
                <textarea name="keterangan" id="keterangan" class="form-control" rows="3"></textarea>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Penunjang (opsional)</label>
                <input type="text" name="penunjang" id="penunjang" class="form-control" placeholder="Mis: Lab, X-Ray, dll">
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Tanggal Kunjungan</label>
                <input type="date" class="form-control" id="rujuk-tanggal_visitation" name="tanggal_visitation" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>No Antrian</label>
                <input type="text" name="no_antrian" id="rujuk-no-antrian" class="form-control" readonly>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="metode_bayar_id_rujuk">Cara Bayar</label>
                <select class="form-control select2" id="metode_bayar_id_rujuk" name="metode_bayar_id" required>
                    <option value="" selected disabled>Pilih Metode Bayar</option>
                    @foreach($metodeBayar as $metode)
                        <option value="{{ $metode->id }}" @if(isset($visitation) && $visitation->metode_bayar_id == $metode->id) selected @endif>{{ $metode->nama }}</option>
                    @endforeach
                </select>
              </div>
            </div>
            <div class="col-md-6">
              {{-- empty column to keep two-input-per-row layout --}}
            </div>
          </div>

          {{-- Klinik is derived from selected dokter, so no klinik select here --}}

        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Simpan Rujuk</button>
        </div>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
$(document).ready(function () {
    $('#modalRujuk .select2').select2({ width: '100%' });

  // Open rujuk modal
  $(document).on('click', '.btn-rujuk', function () {
    let pasienId = $(this).data('id');
    let namaPasien = $(this).data('nama');
    let norm = $(this).data('norm');
    let tglLahir = $(this).data('tgllahir');
    let dokterId = $(this).data('dokter');
    let metodeBayarId = $(this).data('metodebayar');

    $('#rujuk-pasien-id').val(pasienId);
    $('#rujuk-nama-pasien').val(namaPasien);
    $('#rujuk-no-rm').val(norm || '');
    $('#rujuk-tgllahir').val(tglLahir || '');
    $('#modalRujuk').data('tgllahir', tglLahir || '');

    // If button provides current visitation dokter, preselect dokter_pengirim
    if (dokterId) {
      $('#dokter_pengirim_id').val(dokterId).trigger('change');
    } else {
      $('#dokter_pengirim_id').trigger('change');
    }

    if (metodeBayarId) {
      $('#metode_bayar_id_rujuk').val(metodeBayarId).trigger('change');
    } else {
      $('#metode_bayar_id_rujuk').trigger('change');
    }

  // Compute and display age (use visit date if set, otherwise today)
  computeAndSetAge(tglLahir, $('#rujuk-tanggal_visitation').val());

    // Trigger antrian check after preselects
    setTimeout(function() {
      cekAntrianRujuk();
    }, 250);

    $('#modalRujuk').modal('show');
  });

    // Submit rujuk form
    $('#form-rujuk').submit(function (e) {
        e.preventDefault();

        let formData = $(this).serialize();

        $.ajax({
            url: "{{ route('erm.rujuk.store') }}",
            type: "POST",
            data: formData,
            success: function (res) {
                $('#modalRujuk').modal('hide');
                $('#form-rujuk')[0].reset();
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: res.message,
                    confirmButtonText: 'OK'
                }).then(() => {
                    location.reload();
                });
            },
            error: function (xhr) {
              // Prefer to show detailed validation messages when available
              let msg = 'Terjadi kesalahan. Pastikan semua data valid.';
              try {
                if (xhr.status === 422 && xhr.responseJSON) {
                  // Laravel validation responses include `errors` object
                  if (xhr.responseJSON.errors) {
                    const errs = xhr.responseJSON.errors;
                    // flatten errors into one message string
                    msg = Object.values(errs).map(e => e.join(' ')).join('\n');
                  } else if (xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                  }
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                  msg = xhr.responseJSON.message;
                } else if (xhr.responseText) {
                  // fallback to raw response text for unexpected errors
                  msg = xhr.responseText.substring(0, 1000);
                }
              } catch (err) {
                // ignore parsing errors and use generic message
              }

              Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: msg,
                confirmButtonText: 'OK'
              });
            }
        });
    });
    
    // Check queue number for rujuk modal
    function cekAntrianRujuk() {
      let dokterId = $('#dokter_tujuan_id').val();
      let tanggal = $('#rujuk-tanggal_visitation').val();

      if (dokterId && tanggal) {
        $.ajax({
          url: "{{ route('erm.visitations.cekAntrian') }}",
          type: 'GET',
          data: {
            dokter_id: dokterId,
            tanggal: tanggal
          },
          success: function(response) {
            $('#rujuk-no-antrian').val(response.no_antrian);
          },
          error: function(xhr) {
            $('#rujuk-no-antrian').val('Error');
          }
        });
      } else {
        $('#rujuk-no-antrian').val('');
      }
    }

    // Run check when dokter tujuan or date changes
    $('#dokter_tujuan_id, #rujuk-tanggal_visitation').on('change', function() {
      cekAntrianRujuk();
      // recompute age when date changes using stored birthdate on modal
      computeAndSetAge($('#modalRujuk').data('tgllahir'), $('#rujuk-tanggal_visitation').val());
    });

    function computeAndSetAge(birthDateStr, refDateStr) {
      if (!birthDateStr) {
        $('#rujuk-age').val('');
        return;
      }
      let birth = new Date(birthDateStr);
      let ref = refDateStr ? new Date(refDateStr) : new Date();
      if (isNaN(birth.getTime())) { $('#rujuk-age').val(''); return; }

      let years = ref.getFullYear() - birth.getFullYear();
      let months = ref.getMonth() - birth.getMonth();
      let days = ref.getDate() - birth.getDate();
      if (days < 0) {
        months -= 1;
        // get days in previous month
        let prevMonth = new Date(ref.getFullYear(), ref.getMonth(), 0);
        days += prevMonth.getDate();
      }
      if (months < 0) {
        years -= 1;
        months += 12;
      }
      let parts = [];
      if (years > 0) parts.push(years + ' th');
      if (months > 0) parts.push(months + ' bln');
      if (days > 0) parts.push(days + ' hr');
      if (parts.length === 0) parts.push('0 hr');
      $('#rujuk-age').val(parts.join(' '));
    }
});
</script>
@endpush
