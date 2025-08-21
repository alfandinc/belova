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
                            {{ $index + 1 }}. {{ $item->tindakan->nama ?? ($item->paketTindakan->nama ?? 'N/A') }}
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
                        <input type="time" class="form-control" id="globalJamMulaiModal">
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
                        <input type="time" class="form-control" id="globalJamSelesaiModal">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-secondary" id="btnNowJamSelesaiModal">Now</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="table-responsive mt-4">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th style="width: 2%">NO</th>
                        <th style="width: 10%">TINDAKAN</th>
                        <th style="width: 12%">PJ</th>
                        <th style="width: 4%">SBK</th>
                        <th style="width: 4%">SBA</th>
                        <th style="width: 4%">SDC</th>
                        <th style="width: 4%">SDK</th>
                        <th style="width: 4%">SDL</th>
                        <th style="width: 30%">NOTES</th>
                    </tr>
                </thead>
                <tbody id="spkModalTableBody">
                </tbody>
            </table>
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
    const renderSpkModalTable = (sopList, spk, users) =>
        sopList.map((sop, index) => {
            const existingDetail = spk?.details?.find(d => d.sop_id == sop.id);
            return `<tr>
                <td>${index + 1}</td>
                <td>${sop.nama_sop}</td>
                <td>
                    <select class="form-control select2-spk" name="details[${index}][penanggung_jawab]" data-sop-id="${sop.id}" required>
                        <option value="">Pilih PJ</option>
                    </select>
                </td>
                <td><input type="checkbox" name="details[${index}][sbk]" ${existingDetail?.sbk ? 'checked' : ''}></td>
                <td><input type="checkbox" name="details[${index}][sba]" ${existingDetail?.sba ? 'checked' : ''}></td>
                <td><input type="checkbox" name="details[${index}][sdc]" ${existingDetail?.sdc ? 'checked' : ''}></td>
                <td><input type="checkbox" name="details[${index}][sdk]" ${existingDetail?.sdk ? 'checked' : ''}></td>
                <td><input type="checkbox" name="details[${index}][sdl]" ${existingDetail?.sdl ? 'checked' : ''}></td>
                <td class="d-flex align-items-center">
                    <textarea class="form-control" name="details[${index}][notes]" rows="2" placeholder="Catatan...">${existingDetail?.notes || ''}</textarea>
                    <button type="button" class="btn btn-sm btn-primary mt-2 check-all-btn" data-checked="0">Check All</button>
                </td>
                <input type="hidden" class="spk-mulai" name="details[${index}][waktu_mulai]" value="${existingDetail?.waktu_mulai?.substring(0,5) || ''}">
                <input type="hidden" class="spk-selesai" name="details[${index}][waktu_selesai]" value="${existingDetail?.waktu_selesai?.substring(0,5) || ''}">
                <input type="hidden" name="details[${index}][sop_id]" value="${sop.id}">
            </tr>`;
        }).join('');

    const populateModalSelect2 = (users, spk) => {
        $('#spkModal .select2-spk').each(function() {
            const sopId = $(this).data('sop-id');
            const existingDetail = spk?.details?.find(d => d.sop_id == sopId);
            
            // Destroy existing select2 if it exists
            if ($(this).hasClass('select2-hidden-accessible')) {
                $(this).select2('destroy');
            }
            
            // Clear existing options except placeholder
            $(this).find('option:not(:first)').remove();
            
            users.forEach(user => {
                const option = $('<option></option>');
                option.val(user.name);
                option.text(user.name);
                if (existingDetail?.penanggung_jawab === user.name) option.prop('selected', true);
                $(this).append(option);
            });
            
            // Re-initialize select2
            $(this).select2({
                width: '100%',
                dropdownParent: $('#spkModal')
            });
        });
    };

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
                $('#spkModalTableBody').html('<tr><td colspan="9" class="text-center"><i class="fa fa-spinner fa-spin"></i> Memuat data SPK...</td></tr>');
                
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
                            
                            if (data.sop_list && data.sop_list.length > 0) {
                                $('#spkModalTableBody').html(renderSpkModalTable(data.sop_list, data.spk, data.users));
                                populateModalSelect2(data.users, data.spk);
                            } else {
                                $('#spkModalTableBody').html('<tr><td colspan="9" class="text-center text-muted">Tidak ada SOP untuk tindakan ini</td></tr>');
                            }
                            
                            // Set global times from first detail if available
                            if (data.spk && data.spk.details && data.spk.details.length > 0) {
                                const firstDetail = data.spk.details[0];
                                if (firstDetail.waktu_mulai) {
                                    $('#globalJamMulaiModal').val(firstDetail.waktu_mulai.substring(0,5));
                                }
                                if (firstDetail.waktu_selesai) {
                                    $('#globalJamSelesaiModal').val(firstDetail.waktu_selesai.substring(0,5));
                                }
                            }
                            
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
                            $('#spkModalTableBody').html('<tr><td colspan="9" class="text-center text-danger">Gagal memuat data SPK</td></tr>');
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
                        $('#spkModalTableBody').html('<tr><td colspan="9" class="text-center text-danger">Error memuat data SPK</td></tr>');
                        
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
                $('#spkModalTableBody').html('<tr><td colspan="9" class="text-center text-muted">Pilih tindakan untuk memuat SPK</td></tr>');
                
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
        
        // Destroy any existing select2 instances
        $('#spkModal .select2-spk').each(function() {
            if ($(this).hasClass('select2-hidden-accessible')) {
                $(this).select2('destroy');
            }
        });
        
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
    
    // Destroy select2 instances
    $('#spkModal .select2-spk').each(function() {
        if ($(this).hasClass('select2-hidden-accessible')) {
            $(this).select2('destroy');
        }
    });
    
    // Remove namespaced event handlers
    $(document).off('click.spkNavigation');
    $(document).off('click.spkSave');
    $(document).off('click.spkCheckAll');
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
    
    // Set all waktu_mulai and waktu_selesai per row from global input
    const jamMulai = $('#globalJamMulaiModal').val();
    const jamSelesai = $('#globalJamSelesaiModal').val();
    $('#spkModal input.spk-mulai').each(function() {
        $(this).val(jamMulai);
    });
    $('#spkModal input.spk-selesai').each(function() {
        $(this).val(jamSelesai);
    });
    
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

// Handle check all buttons - using namespaced event
$(document).off('click.spkCheckAll', '.check-all-btn').on('click.spkCheckAll', '.check-all-btn', function() {
    const row = $(this).closest('tr');
    const checkboxes = row.find('input[type="checkbox"]');
    const isChecked = $(this).attr('data-checked') === '1';
    
    checkboxes.prop('checked', !isChecked);
    $(this).attr('data-checked', isChecked ? '0' : '1');
    $(this).text(isChecked ? 'Check All' : 'Uncheck All');
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
