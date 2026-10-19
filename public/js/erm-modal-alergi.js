// Modal alergi functionality
$(document).ready(function() {
    // Saat tombol modal alergi ditekan
    $('#btnBukaAlergi').on('click', function () {
        $('#modalAlergi').modal('show');
    });

    // Zat aktif picker: searched on the server, 30 per page
    var $zatAktif = $('#alergiZatAktif');
    if ($.fn.select2 && $zatAktif.length) {
        $zatAktif.select2({
            width: '100%',
            dropdownParent: $('#modalAlergi'),
            placeholder: 'Ketik untuk mencari zat aktif...',
            ajax: {
                url: $zatAktif.data('search-url'),
                dataType: 'json',
                delay: 250,
                cache: true,
                data: function (params) { return { q: params.term || '', page: params.page || 1 }; },
                processResults: function (res) { return { results: res.results || [], pagination: { more: !!res.more } }; }
            }
        });
    }

    // Set the chosen zat aktif from {id, text} items (adds the options the searched picker does not have yet)
    window.alergiSetZatAktif = function (items) {
        var ids = [];
        $zatAktif.find('option').remove();
        (items || []).forEach(function (z) {
            ids.push(String(z.id));
            $zatAktif.append(new Option(z.text, z.id, true, true));
        });
        $zatAktif.val(ids).trigger('change');
    };

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
                window.pcUpdateAlergiDisplay(response.data);
                
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
    
});
