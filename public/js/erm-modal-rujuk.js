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
            url: window.ErmRujukConfig.routes["erm.rujuk.store"],
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
          url: window.ErmRujukConfig.routes["erm.visitations.cekAntrian"],
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
