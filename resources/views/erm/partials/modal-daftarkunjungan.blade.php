<!-- Modal Daftar Kunjungan -->
<div class="modal fade" id="modalKunjungan" tabindex="-1" role="dialog" aria-labelledby="modalKunjungan" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form id="form-kunjungan">
      @csrf
      <div class="modal-content">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title" id="modalLabel">Daftarkan Kunjungan Pasien</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true"><i class="la la-times"></i></span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="pasien_id" id="modal-pasien-id">

          <div class="form-group">
            <label>Nama Pasien</label>
            <input type="text" id="modal-nama-pasien" class="form-control" value="" readonly>
          </div>
            <!-- Add this new form group for klinik selection -->
            <div class="form-group">
                <label>Klinik</label>
                <select id="klinik_id" name="klinik_id" class="form-control select2" required>
                <option value="" disabled selected>Pilih Klinik</option>
                @foreach($kliniks as $klinik)
                    <option value="{{ $klinik->id }}">{{ $klinik->nama }}</option>
                @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Dokter</label>
                <select id="dokter_id" name="dokter_id" class="form-control select2" required disabled>
                <option value="">Pilih Dokter</option>
                </select>
            </div>

          <div class="form-group">
            <label>Tanggal Kunjungan</label>
            <input type="date" class="form-control" id="tanggal_visitation" name="tanggal_visitation" required>
          </div>
          <div class="form-group">
            <label>Waktu Kunjungan (Opsional)</label>
            <input type="time" class="form-control" id="waktu_kunjungan" name="waktu_kunjungan">
          </div>

          <div class="form-group">
              <label for="metode_bayar_id">Cara Bayar</label>
              <select class="form-control select2" id="metode_bayar_id" name="metode_bayar_id" required>
                  <option value="" selected disabled>Pilih Metode Bayar</option>
                  @foreach($metodeBayar as $metode)
                      <option value="{{ $metode->id }}">{{ $metode->nama }}</option>
                  @endforeach
              </select>
            </div>

          <div class="form-group">
            <label>No Antrian</label>
            <input type="text" name="no_antrian" id="modal-no-antrian" class="form-control" readonly>
          </div>

        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
$(document).ready(function () {
    // Initialize Select2 for this modal
    $('#modalKunjungan .select2').select2({ width: '100%' });

    // Handler for daftar visitation button
    $(document).on('click', '.btn-daftar-visitation', function () {
        let pasienId = $(this).data('id');
        let namaPasien = $(this).data('nama');
        let klinikId = $(this).data('klinik');
        let dokterId = $(this).data('dokter');
        let metodeBayarId = $(this).data('metodebayar');

        $('#modal-pasien-id').val(pasienId);
        $('#modal-nama-pasien').val(namaPasien);
        
        // Auto-select Klinik if provided
        if (klinikId) {
            $('#klinik_id').val(klinikId).trigger('change');
            // Wait for dokter select to be populated via AJAX
            setTimeout(function() {
                if (dokterId) {
                    $('#dokter_id').val(dokterId).trigger('change');
                }
            }, 600); // adjust delay if needed
        }
        // Auto-select Cara Bayar if provided
        if (metodeBayarId) {
            $('#metode_bayar_id').val(metodeBayarId).trigger('change');
        }

        $('#modalKunjungan').modal('show');
    });

    // Submit form kunjungan
    $('#form-kunjungan').submit(function (e) {
        e.preventDefault();

        let formData = $(this).serialize();

        $.ajax({
            url: "{{ route('erm.visitations.store') }}",
            type: "POST",
            data: formData,
            success: function (res) {
                $('#modalKunjungan').modal('hide');
                $('#form-kunjungan')[0].reset();
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
                let msg = 'Terjadi kesalahan. Pastikan semua data valid.';
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
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

    // Cek No Antrian otomatis
    function cekAntrian() {
        let dokterId = $('#dokter_id').val();
        let tanggal = $('#tanggal_visitation').val();
        

        if (dokterId && tanggal) {
            console.log('dokter_id:', dokterId, 'tanggal:', tanggal);
            $.ajax({
                url: "{{ route('erm.visitations.cekAntrian') }}",
                type: 'GET',
                data: {
                    dokter_id: dokterId,
                    tanggal: tanggal
                },
                success: function(response) {
                    console.log('Response:', response);
                    $('#modal-no-antrian').val(response.no_antrian);
                },
                error: function(xhr) {
                    console.log(xhr.responseText);
                    $('#modal-no-antrian').val('Error');
                }
            });
        }
    }

    // Jalankan cekAntrian saat dokter atau tanggal berubah
    $('#dokter_id, #tanggal_visitation').on('change', function () {
        cekAntrian();
    });

    $('#klinik_id').on('change', function() {
        let klinikId = $(this).val();
        let dokterSelect = $('#dokter_id');
        
        console.log("Selected klinik_id:", klinikId);
        
        // Reset doctor dropdown
        dokterSelect.empty().append('<option value="">Loading...</option>').prop('disabled', true);
        
        if (klinikId) {
            // Fetch doctors for selected clinic
            $.ajax({
                url: `/get-dokters/${klinikId}`,
                type: 'GET',
                success: function(data) {
                    console.log("API response:", data);
                    dokterSelect.empty().append('<option value="">Pilih Dokter</option>');
                    
                    // Check if we got data
                    if (data && data.length > 0) {
                        // Add options for each doctor with error handling
                        $.each(data, function(index, dokter) {
                            console.log("Processing doctor:", dokter);
                            let dokterName = 'Unknown Doctor';
                            if (dokter.user && dokter.user.name) {
                                dokterName = dokter.user.name;
                            }
                            
                            let spesialis = '';
                            if (dokter.spesialisasi && dokter.spesialisasi.nama) {
                                spesialis = ` (${dokter.spesialisasi.nama})`;
                            }
                            
                            dokterSelect.append(`<option value="${dokter.id}">${dokterName}${spesialis}</option>`);
                        });
                    } else {
                        // No doctors found for this clinic
                        dokterSelect.append('<option value="" disabled>Tidak ada dokter di klinik ini</option>');
                    }
                    
                    // Enable the doctor select
                    dokterSelect.prop('disabled', false).trigger('change.select2');
                },
                error: function(xhr) {
                    console.error("Error loading doctors:", xhr.responseText);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Gagal mengambil data dokter'
                    });
                    
                    dokterSelect.empty().append('<option value="">Pilih Dokter</option>');
                    dokterSelect.prop('disabled', true).trigger('change.select2');
                }
            });
        } else {
            // If no clinic selected, reset and disable doctor dropdown
            dokterSelect.empty().append('<option value="">Pilih Dokter</option>');
            dokterSelect.prop('disabled', true).trigger('change.select2');
        }
    });

    // Reset form fields when modal is closed
    $('#modalKunjungan').on('hidden.bs.modal', function() {
        $('#form-kunjungan')[0].reset();
        $('#dokter_id').empty().append('<option value="">Pilih Dokter</option>').prop('disabled', true).trigger('change.select2');
    });
    
    // After selecting dokter and date, check for queue number
    $('#dokter_id, #tanggal_visitation').on('change', function() {
        cekAntrian();
    });
});
</script>
@endpush