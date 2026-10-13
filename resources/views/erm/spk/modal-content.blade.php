<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            @if(isset($allRiwayat) && $allRiwayat->count() > 1)
            <div class="tindakan-navigation d-flex align-items-center">
                <div class="dropdown mr-2">
                    <button class="btn btn-sm btn-info dropdown-toggle" type="button" id="tindakanDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Tindakan {{ $currentIndex + 1 }} dari {{ $allRiwayat->count() }}
                    </button>
                    <div class="dropdown-menu" aria-labelledby="tindakanDropdown">
                        @foreach($allRiwayat as $index => $item)
                        <a class="dropdown-item load-spk-index" href="#" data-visitation-id="{{ $item->visitation_id }}" data-index="{{ $index }}">
                            {{ $index + 1 }}. {{ $item->tindakan->nama ?? 'N/A' }}
                        </a>
                        @endforeach
                    </div>
                </div>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-secondary load-spk-index {{ $currentIndex <= 0 ? 'disabled' : '' }}" 
                            data-visitation-id="{{ $riwayat->visitation_id }}" 
                            data-index="{{ max(0, $currentIndex - 1) }}">
                        <i class="fa fa-chevron-left"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary load-spk-index {{ $currentIndex >= $allRiwayat->count() - 1 ? 'disabled' : '' }}" 
                            data-visitation-id="{{ $riwayat->visitation_id }}" 
                            data-index="{{ min($allRiwayat->count() - 1, $currentIndex + 1) }}">
                        <i class="fa fa-chevron-right"></i>
                    </button>
                </div>
            </div>
            @endif
        </div>
    </div>

    <form id="spkModalForm">
        @csrf
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Nama Pasien</label>
                    <input type="text" class="form-control" id="spkModalNamaPasien" readonly>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>No RM</label>
                    <input type="text" class="form-control" id="spkModalNoRm" readonly>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Tanggal Tindakan</label>
                    <input type="date" class="form-control" id="spkModalTanggalTindakan" name="tanggal_tindakan">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Nama Tindakan</label>
                    <input type="text" class="form-control" id="spkModalNamaTindakan" readonly>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Dokter Penanggung Jawab</label>
                    <input type="text" class="form-control" id="spkModalDokterPJ" readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Harga</label>
                    <input type="text" class="form-control" id="spkModalHarga" readonly>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Jam Mulai</label>
                    <div class="input-group">
                        <input type="time" class="form-control" id="globalJamMulaiModal" name="waktu_mulai">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-secondary" id="btnNowJamMulaiModal">Now</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Jam Selesai</label>
                    <div class="input-group">
                        <input type="time" class="form-control" id="globalJamSelesaiModal" name="waktu_selesai">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-secondary" id="btnNowJamSelesaiModal">Now</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <input type="hidden" id="spkModalInformConsentId" name="inform_consent_id">
        <input type="hidden" id="spkModalRiwayatTindakanId" name="riwayat_tindakan_id" value="{{ $riwayat->id ?? '' }}">
        
        <!-- Timestamp Information -->
        <div class="row mt-3 border-top pt-3">
            <div class="col-md-6">
                <small class="text-muted">
                    <strong>Dibuat:</strong> 
                    <span id="spkCreatedAt">-</span>
                </small>
            </div>
            <div class="col-md-6">
                <small class="text-muted">
                    <strong>Terakhir Diubah:</strong> 
                    <span id="spkUpdatedAt">-</span>
                </small>
            </div>
        </div>
        
        <div class="d-flex justify-content-between align-items-center mt-3">
            @if(isset($allRiwayat) && $allRiwayat->count() > 1)
            <div>
                <button type="button" class="btn btn-secondary load-spk-index {{ $currentIndex <= 0 ? 'disabled' : '' }}" 
                        data-visitation-id="{{ $riwayat->visitation_id }}" 
                        data-index="{{ max(0, $currentIndex - 1) }}"
                        title="Previous: {{ max(0, $currentIndex - 1) }}">
                    <i class="fa fa-chevron-left"></i> Sebelumnya
                </button>
                <span class="mx-2 text-muted">{{ $currentIndex + 1 }} / {{ $allRiwayat->count() }}</span>
                <button type="button" class="btn btn-secondary load-spk-index {{ $currentIndex >= $allRiwayat->count() - 1 ? 'disabled' : '' }}" 
                        data-visitation-id="{{ $riwayat->visitation_id }}" 
                        data-index="{{ min($allRiwayat->count() - 1, $currentIndex + 1) }}"
                        title="Next: {{ min($allRiwayat->count() - 1, $currentIndex + 1) }}">
                    Selanjutnya <i class="fa fa-chevron-right"></i>
                </button>
            </div>
            @else
            <div></div>
            @endif
            
            <button type="button" class="btn btn-success" id="saveSpkModal">Simpan</button>
        </div>
    </form>
</div>

<script>
// Wrap everything in IIFE to prevent redeclaration errors
(function() {
    'use strict';

    // Modal-specific JavaScript
    const loadSpkModalData = () => {
        return new Promise((resolve, reject) => {
            const riwayatTindakanId = $('#spkModalRiwayatTindakanId').val();
            if (riwayatTindakanId) {
                // Show loading state in form fields
                $('#spkModalNamaPasien').val('Loading...');
                $('#spkModalNoRm').val('Loading...');
                $('#spkModalNamaTindakan').val('Loading...');
                $('#spkModalDokterPJ').val('Loading...');
                $('#spkModalHarga').val('Loading...');
                
                fetch(`/erm/tindakan/spk/by-riwayat/${riwayatTindakanId}`)
                    .then(res => res.json())
                    .then(response => {
                        if (response.success) {
                            const data = response.data;
                            $('#spkModalNamaPasien').val(data.pasien_nama || '');
                            $('#spkModalNoRm').val(data.pasien_id || '');
                            $('#spkModalNamaTindakan').val(data.tindakan_nama || '');
                            $('#spkModalDokterPJ').val(data.dokter_nama || '');
                            $('#spkModalHarga').val(data.harga || '');
                            
                            let tanggalTindakan = data.spk?.tanggal_tindakan || '';
                            if (tanggalTindakan) {
                                tanggalTindakan = new Date(tanggalTindakan).toISOString().split('T')[0];
                            }
                            $('#spkModalTanggalTindakan').val(tanggalTindakan || new Date().toISOString().split('T')[0]);
                            
                            // Jam mulai/selesai are stored on the SPK itself
                            $('#globalJamMulaiModal').val(data.spk && data.spk.waktu_mulai ? data.spk.waktu_mulai.substring(0,5) : '');
                            $('#globalJamSelesaiModal').val(data.spk && data.spk.waktu_selesai ? data.spk.waktu_selesai.substring(0,5) : '');
                            
                            // Update timestamp information
                            if (data.spk) {
                                const createdAt = data.spk.created_at ? 
                                    new Date(data.spk.created_at).toLocaleString('id-ID', {
                                        year: 'numeric',
                                        month: 'short',
                                        day: '2-digit',
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    }) : '-';
                                
                                const updatedAt = data.spk.updated_at ? 
                                    new Date(data.spk.updated_at).toLocaleString('id-ID', {
                                        year: 'numeric',
                                        month: 'short',
                                        day: '2-digit',
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    }) : '-';
                                
                                $('#spkCreatedAt').text(createdAt);
                                $('#spkUpdatedAt').text(updatedAt);
                            } else {
                                $('#spkCreatedAt').text('-');
                                $('#spkUpdatedAt').text('-');
                            }
                            
                            resolve(data);
                        } else {
                            reject(new Error('Failed to load SPK data'));
                        }
                    })
                    .catch((error) => {
                        console.error('Error loading SPK data:', error);
                        $('#spkModalNamaPasien').val('');
                        $('#spkModalNoRm').val('');
                        $('#spkModalNamaTindakan').val('');
                        $('#spkModalDokterPJ').val('');
                        $('#spkModalHarga').val('');
                        
                        // Clear timestamps on error
                        $('#spkCreatedAt').text('-');
                        $('#spkUpdatedAt').text('-');
                        
                        Swal.fire('Error', 'Failed to load SPK data', 'error');
                        reject(error);
                    });
            } else {
                // Clear data if no riwayat ID
                $('#spkModalNamaPasien').val('');
                $('#spkModalNoRm').val('');
                $('#spkModalNamaTindakan').val('');
                $('#spkModalDokterPJ').val('');
                $('#spkModalHarga').val('');
                $('#spkModalTanggalTindakan').val(new Date().toISOString().split('T')[0]);
                $('#globalJamMulaiModal').val('');
                $('#globalJamSelesaiModal').val('');
                
                // Clear timestamps
                $('#spkCreatedAt').text('-');
                $('#spkUpdatedAt').text('-');
                
                resolve(null);
            }
        });
    };

    // Initialize modal data on load
    $(document).ready(function() {
        // Load data immediately when modal content is ready
        loadSpkModalData();
    });

    // Handle navigation between tindakan - using unique namespace to prevent conflicts
    $(document).off('click.spkNavigation', '.load-spk-index').on('click.spkNavigation', '.load-spk-index', function(e) {
        e.preventDefault();
        
        if ($(this).hasClass('disabled')) {
            return;
        }
        
        const visitationId = $(this).data('visitation-id');
        const newIndex = $(this).data('index');
        
        if (!visitationId && visitationId !== 0) {
            alert('Error: No visitation ID found');
            return;
        }
        
        if (newIndex === undefined || newIndex === null) {
            alert('Error: No index found');
            return;
        }
        
        // Prevent multiple clicks during navigation
        if ($('.load-spk-index').prop('disabled')) {
            return;
        }
        
        // Add loading overlay
        const $modalBody = $('#spkModalBody');
        
        // Remove any existing loading overlays first
        $('.loading-overlay').remove();
        
        // Create new loading overlay
        const loadingOverlay = $(`
            <div class="loading-overlay" style="
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(255, 255, 255, 0.9);
                z-index: 1050;
                display: flex;
                align-items: center;
                justify-content: center;
            ">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <div class="mt-2">Memuat tindakan ${newIndex + 1}...</div>
                </div>
            </div>
        `);
        
        // Add overlay to modal body
        $modalBody.css('position', 'relative').append(loadingOverlay);
        
        // Disable navigation buttons during loading
        $('.load-spk-index').prop('disabled', true);
        
        // Load new content via AJAX with timeout
        window.spkNavigationTimeout = setTimeout(function() {
            Swal.fire('Timeout', 'Permintaan memuat data terlalu lama. Silakan coba lagi.', 'warning');
            $('.loading-overlay').remove();
            $('.load-spk-index').prop('disabled', false);
        }, 10000); // 10 second timeout
        
        $.get('/erm/spk/modal', {
            visitation_id: visitationId,
            index: newIndex
        })
        .done(function(response) {
            clearTimeout(window.spkNavigationTimeout);
            
            // Use a smoother transition approach
            $modalBody.animate({opacity: 0.3}, 150, function() {
                $('#spkModalBody').html(response);
                
                $modalBody.animate({opacity: 1}, 150, function() {
                    // Clean up
                    $('.loading-overlay').remove();
                    $('.load-spk-index').prop('disabled', false);
                });
            });
        })
        .fail(function(xhr, status, error) {
            clearTimeout(window.spkNavigationTimeout);
            console.error('Failed to load modal content:', error);
            
            // Remove loading overlay
            $('.loading-overlay').fadeOut(200, function() {
                $(this).remove();
            });
            $('.load-spk-index').prop('disabled', false);
            
            // Show error message
            Swal.fire('Error', 'Gagal memuat data SPK. Silakan coba lagi.', 'error');
        });
    });

    // Make functions available globally for external calls
    window.loadSpkModalData = loadSpkModalData;

})(); // End of IIFE

// Cleanup function to prevent event handler accumulation
function cleanupSpkModal() {
    // Remove any existing overlays
    $('.loading-overlay').remove();
    
    // Remove namespaced event handlers
    $(document).off('click.spkNavigation');
    $(document).off('click.spkSave');
    $(document).off('click.spkTimeButtons');
    
    // Clear any pending timeouts
    if (window.spkNavigationTimeout) {
        clearTimeout(window.spkNavigationTimeout);
    }
    
    // Re-enable any disabled buttons
    $('#saveSpkModal').prop('disabled', false);
    $('.load-spk-index').prop('disabled', false);
}

// Cleanup when modal is hidden
$(document).on('hidden.bs.modal', '#spkModal', function() {
    cleanupSpkModal();
});

// Save SPK - using namespaced event to prevent duplicates
$(document).off('click.spkSave', '#saveSpkModal').on('click.spkSave', '#saveSpkModal', function() {
    // Prevent multiple clicks during save
    if ($(this).prop('disabled')) {
        return;
    }
    
    // Disable save button to prevent multiple submissions
    $(this).prop('disabled', true);
    
    
    const form = $('#spkModalForm')[0];
    const formData = new FormData(form);
    
    Swal.fire({
        title: 'Menyimpan...',
        text: 'Please wait while saving SPK data',
        icon: 'info',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
    });
    
    fetch('/erm/tindakan/spk/save', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    })
    .then(res => res.json())
    .then(response => {
        // Re-enable save button
        $('#saveSpkModal').prop('disabled', false);
        
        if (response.success) {
            Swal.fire('Berhasil!', response.message, 'success').then(() => {
                // Reload the main table
                if (typeof table !== 'undefined') {
                    table.ajax.reload();
                }
                // Close modal
                $('#spkModal').modal('hide');
            });
        } else {
            Swal.fire('Error', response.message || 'Failed to save SPK data', 'error');
        }
    })
    .catch(error => {
        // Re-enable save button
        $('#saveSpkModal').prop('disabled', false);
        
        Swal.fire('Error', 'Failed to save SPK data', 'error');
    });
});


// Handle time buttons - using namespaced events
$(document).off('click.spkTimeButtons', '#btnNowJamMulaiModal').on('click.spkTimeButtons', '#btnNowJamMulaiModal', function() {
    const now = new Date();
    const hh = String(now.getHours()).padStart(2, '0');
    const mm = String(now.getMinutes()).padStart(2, '0');
    $('#globalJamMulaiModal').val(`${hh}:${mm}`);
});

$(document).off('click.spkTimeButtons', '#btnNowJamSelesaiModal').on('click.spkTimeButtons', '#btnNowJamSelesaiModal', function() {
    const now = new Date();
    const hh = String(now.getHours()).padStart(2, '0');
    const mm = String(now.getMinutes()).padStart(2, '0');
    $('#globalJamSelesaiModal').val(`${hh}:${mm}`);
});
</script>
