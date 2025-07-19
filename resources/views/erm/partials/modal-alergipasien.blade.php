<!-- filepath: c:\wamp64\www\belova\resources\views\erm\partials\modal-alergipasien.blade.php -->
{{-- Modals --}}
<div class="modal fade" id="modalAlergi" tabindex="-1" aria-labelledby="modalAlergiLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="formAlergi" method="POST" action="{{ route('erm.alergi.store', $visitation->id) }}">
        @csrf
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalLabel">Riwayat Alergi Pasien</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true"><i class="la la-times"></i></span>
          </button>
        </div>
        <div class="modal-body">
          <div id="alergiAlertContainer"></div>

          {{-- Radio Button --}}
          <div class="form-group">
            <label>Apakah Pasien memiliki riwayat alergi?</label><br>
            <div class="form-check form-check-inline">
              <input checked class="form-check-input" type="radio" name="statusAlergi" id="alergiTidakAda" value="tidak" {{ $alergistatus == 'tidak' ? 'checked' : '' }}>
              <label class="form-check-label" for="alergiTidakAda">Tidak Ada</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="statusAlergi" id="alergiAda" value="ada" {{ $alergistatus == 'ada' ? 'checked' : '' }}>
              <label class="form-check-label" for="alergiAda">Ada</label>
            </div>
          </div>

          {{-- Input Kata Kunci --}}
          <div class="form-group" id="inputKataKunciWrapper" style="display: none;">
            <label for="inputKataKunci">Kata Kunci</label>
            <input value="{{ old('katakunci', $alergikatakunci) }}" type="text" name="katakunci" id="inputKataKunci" class="form-control" placeholder="Masukkan kata kunci...">
          </div>

          {{-- Select2 Kandungan Obat --}}
          <div class="form-group" id="selectKandunganWrapper" style="display: none;">
            <label for="zataktif_id">Pilih Zat Aktif Alergi:</label>
            <select name="zataktif_id[]" class="form-control select2" multiple>
                @foreach ($zatAktif as $zat)
                    <option value="{{ $zat->id }}"
                        @if(in_array($zat->id, $alergiIds)) selected @endif>
                        {{ $zat->nama }}
                    </option>
                @endforeach
            </select>
          </div>

        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Simpan Alergi</button>
        </div>
      </div>
    </form>
  </div>
</div>
{{-- End Modals --}}
@push('scripts')
<script>
// Modal alergi functionality
$(document).ready(function() {
    // Saat tombol modal alergi ditekan
    $('#btnBukaAlergi').on('click', function () {
        $('#modalAlergi').modal('show');
    });

    // Toggle semua bagian tergantung status
    var initialStatusAlergi = $('input[name="statusAlergi"]:checked').val(); // Ambil status yang dipilih awalnya
    
    // Jika status alergi adalah 'ada', tampilkan semua elemen yang terkait
    if (initialStatusAlergi === 'ada') {
        $('#inputKataKunciWrapper').show();
        $('#selectAlergiWrapper').show();
        $('#selectKandunganWrapper').show();
    } else {
        // Jika tidak, sembunyikan elemen-elemen tersebut
        $('#inputKataKunciWrapper').hide();
        $('#selectAlergiWrapper').hide();
        $('#selectKandunganWrapper').hide();
    }
    
    $('input[name="statusAlergi"]').on('change', function () {
        if ($(this).val() === 'ada') {
            $('#inputKataKunciWrapper').show();
            $('#selectAlergiWrapper').show();
            $('#selectKandunganWrapper').show();
        } else {
            $('#inputKataKunciWrapper').hide();
            $('#selectAlergiWrapper').hide();
            $('#selectKandunganWrapper').hide();
            $('#inputKataKunci').val('');
            $('#selectAlergi, #selectKandungan').val(null).trigger('change');
        }
    });

    // Ajax form submission
    $('#formAlergi').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        var submitBtn = $(this).find('button[type="submit"]');
        var originalBtnText = submitBtn.html();
        
        // Change button to loading state
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');
        submitBtn.prop('disabled', true);
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                // Show success message
                $('#alergiAlertContainer').html(
                    '<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                    '<strong>Sukses!</strong> ' + response.message +
                    '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                    '<span aria-hidden="true">&times;</span></button></div>'
                );
                
                // Update the allergy display in the card-identitaspasien
                updateAlergiDisplay(response.data);
                
                // Reset button state
                submitBtn.html(originalBtnText);
                submitBtn.prop('disabled', false);
                
                // Auto-close modal after 2 seconds
                setTimeout(function() {
                    $('#modalAlergi').modal('hide');
                }, 2000);
            },
            error: function(xhr) {
                // Show error message
                var errorMsg = 'Terjadi kesalahan saat menyimpan data.';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    errorMsg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                
                $('#alergiAlertContainer').html(
                    '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                    '<strong>Error!</strong> ' + errorMsg +
                    '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                    '<span aria-hidden="true">&times;</span></button></div>'
                );
                
                // Reset button state
                submitBtn.html(originalBtnText);
                submitBtn.prop('disabled', false);
            }
        });
    });
    
    // Function to update the allergy display in the patient card
    function updateAlergiDisplay(data) {
        // Update kata kunci text
        $('.alergi-label').text('Alergi : ' + (data.kataKunci || '-'));

        // Update allergy badges
        var badgesHtml = '';
        if (data.alergiNames && data.alergiNames.length > 0) {
            // If only katakunci is present and no zat aktif, show 'alergi belum diverifikasi' (yellow)
            if (
                data.alergiNames.length === 1 &&
                data.kataKunci &&
                data.alergiNames[0] === data.kataKunci
            ) {
                badgesHtml += '<span class="badge d-inline-flex align-items-center justify-content-center rounded mr-1" style="height: 25px; padding: 0 10px; color:black; background-color: #ffe066;"><strong>alergi belum diverifikasi</strong></span>';
            } else {
                data.alergiNames.forEach(function(name) {
                    badgesHtml += '<span class="badge d-inline-flex align-items-center justify-content-center rounded mr-1" ' +
                        'style="height: 25px; padding: 0 10px; color:white; background-color: #28a745;">' +
                        '<strong>' + name + '</strong></span>';
                });
            }
        }

        // Add the Edit button to the badges HTML
        badgesHtml += '<button type="button" class="btn btn-sm btn-primary d-flex align-items-center mr-2 mt-2 " ' +
            'style="font-size: 12px;" data-toggle="modal" data-target="#modalAlergi">' +
            '<i class="fas fa-edit mr-1"></i> Edit</button>';

        // Update the badges container
        $('.alergi-badges').html(badgesHtml);
    }

    
});
</script>
@endpush