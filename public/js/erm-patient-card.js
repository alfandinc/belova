// Allergy pane: same rules as the Blade above. Also used by erm.partials.modal-alergipasien after saving.
window.pcUpdateAlergiDisplay = function(data) {
    var keyword = $.trim(data.kataKunci || '');
    var names = data.alergiNames || [];
    // Only a kata kunci and no zat aktif: 'belum diverifikasi' (yellow)
    var unverified = names.length === 1 && keyword && names[0] === data.kataKunci;
    var chips = unverified ? [] : names.filter(function(name) { return !!name; });
    var hasAlergi = unverified || chips.length > 0 || !!keyword;

    var badgesHtml = unverified
        ? '<span class="pc-chip pc-alergi-unverified"><i class="fas fa-exclamation-triangle"></i>Belum diverifikasi</span>'
        : chips.map(function(name) { return '<span class="pc-chip pc-alergi-item">' + $('<div>').text(name).html() + '</span>'; }).join('');
    $('.alergi-badges').html(badgesHtml);

    // Kata kunci: hidden when it only repeats a chip, a small note when it adds something
    var keywordInChips = !!keyword && chips.some(function(name) { return String(name).trim().toLowerCase() === keyword.toLowerCase(); });
    $('.alergi-label')
        .text(hasAlergi ? keyword : 'Tidak ada alergi tercatat')
        .removeClass('is-empty is-note d-none')
        .addClass(!hasAlergi ? 'is-empty' : ((keywordInChips || !keyword) ? 'd-none' : (chips.length ? 'is-note' : '')));

    // The card turns red while the patient has allergies
    $('.pc-alergi').toggleClass('has-alergi', hasAlergi);
};

// Refresh the allergy pane every 10 s, so an allergy entered on another screen (perawat / dokter / farmasi) shows up here
(function() {
    var url = window.ErmPatientCardConfig.alergiCurrentUrl;
    var lastSignature = null;
    var busy = false;

    function syncAlergiForm(data) {
        // Keep the Edit modal's form current, so saving it doesn't put back stale allergies
        var $form = $('#formAlergi');
        if (!$form.length) return;
        var status = data.status === 'ada' ? 'ada' : 'tidak';
        $form.find('input[name="statusAlergi"][value="' + status + '"]').prop('checked', true).trigger('change');
        $form.find('#inputKataKunci').val(data.kataKunci || '');
        if (window.alergiSetZatAktif && data.alergiZat) {
            window.alergiSetZatAktif(data.alergiZat);
        } else {
            $form.find('select[name="zataktif_id[]"]').val((data.alergiIds || []).map(String)).trigger('change');
        }
    }

    function poll() {
        // Not while the tab is in the background, a request is running, or the allergy form is being edited
        if (busy || document.hidden || $('#modalAlergi').hasClass('show')) return;
        busy = true;
        $.ajax({ url: url, dataType: 'json', cache: false })
            .done(function(data) {
                var signature = JSON.stringify(data);
                if (signature !== lastSignature) {
                    window.pcUpdateAlergiDisplay(data);
                    syncAlergiForm(data);
                }
                lastSignature = signature;
            })
            .always(function() { busy = false; });
    }

    $(function() {
        if (!$('.pc-alergi').length) return;
        poll();
        setInterval(poll, 10000);
    });
})();

$(document).ready(function() {
    // Open combined modal and set current statuses
    $('#modalStatusCombined').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var pasienId = button.data('pasien-id');
        var currentStatusPasien = button.data('current-status-pasien');
        var currentStatusAkses = button.data('current-status-akses');
        
        $('#combined_status_pasien').val(currentStatusPasien);
        $('#combined_status_akses').val(currentStatusAkses);
        $('#modalStatusCombined').data('pasien-id', pasienId);
    });
    
    // Save combined status
    $('#saveStatusCombined').on('click', function() {
        var pasienId = $('#modalStatusCombined').data('pasien-id');
        var newStatusPasien = $('#combined_status_pasien').val();
        var newStatusAkses = $('#combined_status_akses').val();
        
        // Update both statuses at once with the combined endpoint
        $.ajax({
            url: '/erm/pasiens/' + pasienId + '/update-status-combined',
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                status_pasien: newStatusPasien,
                status_akses: newStatusAkses
            },
            success: function(response) {
                if(response.success) {
                    $('#modalStatusCombined').modal('hide');
                    
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Status pasien berhasil diperbarui.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    // Reload the page to update all UI elements
                    location.reload();
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Gagal memperbarui status pasien.',
                });
            }
        });
    });
});
