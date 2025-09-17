@php
    $currentSpk = $spkTindakans->first();
@endphp

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0">Informasi Pasien</h6>
                @if($spkTindakans->count() > 1)
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="prevSpk" onclick="navigateSpk('prev')">
                        <i class="mdi mdi-chevron-left"></i> Sebelumnya
                    </button>
                    <span class="btn btn-sm btn-light" id="spkNavInfo">
                        <span id="currentSpkIndex">1</span> dari {{ $spkTindakans->count() }}
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="nextSpk" onclick="navigateSpk('next')">
                        Selanjutnya <i class="mdi mdi-chevron-right"></i>
                    </button>
                </div>
                @endif
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="info-group">
                            <label class="info-label">RM:</label>
                            <span class="info-value" id="patientRm">{{ $currentSpk->riwayatTindakan->visitation->pasien_id ?? '-' }}</span>
                        </div>
                        <div class="info-group">
                            <label class="info-label">Nama:</label>
                            <span class="info-value" id="patientName">{{ $currentSpk->riwayatTindakan->visitation->pasien->nama ?? '-' }}</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-group">
                            <label class="info-label">Dokter:</label>
                            <span class="info-value" id="doctorName">{{ $currentSpk->riwayatTindakan->visitation->dokter->user->name ?? '-' }}</span>
                        </div>
                        <div class="info-group">
                            <label class="info-label">Tindakan:</label>
                            <span class="info-value" id="tindakanName">{{ $currentSpk->riwayatTindakan->tindakan->nama ?? '-' }}</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-group">
                            <label class="info-label">Tanggal:</label>
                            <span class="info-value" id="tindakanDate">{{ $currentSpk->tanggal_tindakan ? $currentSpk->tanggal_tindakan->format('d/m/Y') : '-' }}</span>
                        </div>
                        <div class="info-group">
                            <label class="info-label">Status SPK:</label>
                            <select class="form-control form-control-sm status-select" id="spkStatus" onchange="updateSpkStatusFromDropdown(getCurrentSpkId(), this.value)">
                                <option value="pending" {{ $currentSpk->status === 'pending' ? 'selected' : '' }}>
                                    🟡 Pending
                                </option>
                                <option value="in_progress" {{ $currentSpk->status === 'in_progress' ? 'selected' : '' }}>
                                    🔵 In Progress
                                </option>
                                <option value="completed" {{ $currentSpk->status === 'completed' ? 'selected' : '' }}>
                                    🟢 Completed
                                </option>
                                <option value="cancelled" {{ $currentSpk->status === 'cancelled' ? 'selected' : '' }}>
                                    🔴 Cancelled
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Time Fields Row -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="time-group">
                            <label class="time-label"><strong>Waktu Mulai:</strong></label>
                            <div class="input-group input-group-sm">
                                <input type="time" class="form-control" id="waktuMulai" 
                                       value="{{ $currentSpk->waktu_mulai ? $currentSpk->waktu_mulai->format('H:i') : '' }}">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="setCurrentTime('waktuMulai')">
                                        <i class="mdi mdi-clock"></i> Now
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="time-group">
                            <label class="time-label"><strong>Waktu Selesai:</strong></label>
                            <div class="input-group input-group-sm">
                                <input type="time" class="form-control" id="waktuSelesai" 
                                       value="{{ $currentSpk->waktu_selesai ? $currentSpk->waktu_selesai->format('H:i') : '' }}">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="setCurrentTime('waktuSelesai')">
                                        <i class="mdi mdi-clock"></i> Now
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@foreach($spkTindakans as $spkIndex => $spkTindakan)
<div class="row spk-content" data-spk-id="{{ $spkTindakan->id }}" style="{{ $spkIndex > 0 ? 'display: none;' : '' }}">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">Detail Kode Tindakan</h6>
            </div>
            <div class="card-body">
                <form class="spk-form" data-spk-id="{{ $spkTindakan->id }}" action="{{ route('erm.spktindakan.items.update', $spkTindakan->id) }}" method="POST">
                    @csrf
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="bg-light">
                                <tr>
                                    <th width="20%">Kode Tindakan</th>
                                    <th width="15%">Penanggung Jawab</th>
                                    <th width="10%">SBK</th>
                                    <th width="10%">SBA</th>
                                    <th width="10%">SDC</th>
                                    <th width="10%">SDK</th>
                                    <th width="10%">SDL</th>
                                    <th width="15%">Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($spkTindakan->items as $index => $item)
                                <tr>
                                    <td>
                                        <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                                        <strong>{{ $item->kodeTindakan->nama ?? '-' }}</strong><br>
                                        <small class="text-muted">{{ $item->kodeTindakan->kode ?? '-' }}</small>
                                    </td>
                                    <td>
                                        <select class="form-control form-control-sm" name="items[{{ $index }}][penanggung_jawab]">
                                            <option value="">Pilih Staff</option>
                                            @foreach($users as $user)
                                                <option value="{{ $user->name }}" {{ $item->penanggung_jawab === $user->name ? 'selected' : '' }}>
                                                    {{ $user->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" 
                                                   class="custom-control-input" 
                                                   id="sbk_{{ $item->id }}"
                                                   name="items[{{ $index }}][sbk]" 
                                                   value="1"
                                                   {{ $item->sbk ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="sbk_{{ $item->id }}"></label>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" 
                                                   class="custom-control-input" 
                                                   id="sba_{{ $item->id }}"
                                                   name="items[{{ $index }}][sba]" 
                                                   value="1"
                                                   {{ $item->sba ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="sba_{{ $item->id }}"></label>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" 
                                                   class="custom-control-input" 
                                                   id="sdc_{{ $item->id }}"
                                                   name="items[{{ $index }}][sdc]" 
                                                   value="1"
                                                   {{ $item->sdc ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="sdc_{{ $item->id }}"></label>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" 
                                                   class="custom-control-input" 
                                                   id="sdk_{{ $item->id }}"
                                                   name="items[{{ $index }}][sdk]" 
                                                   value="1"
                                                   {{ $item->sdk ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="sdk_{{ $item->id }}"></label>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" 
                                                   class="custom-control-input" 
                                                   id="sdl_{{ $item->id }}"
                                                   name="items[{{ $index }}][sdl]" 
                                                   value="1"
                                                   {{ $item->sdl ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="sdl_{{ $item->id }}"></label>
                                        </div>
                                    </td>
                                    <td>
                                        <textarea class="form-control form-control-sm" 
                                                  name="items[{{ $index }}][notes]" 
                                                  rows="2" 
                                                  placeholder="Catatan">{{ $item->notes }}</textarea>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    @if($spkTindakan->items->isEmpty())
                        <div class="text-center py-4">
                            <i class="mdi mdi-information-outline h2 text-muted"></i>
                            <p class="text-muted">Tidak ada item SPK untuk tindakan ini</p>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>
</div>
@endforeach
<style>
/* Improved modal layout styles */
.info-group {
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
}

.info-label {
    font-weight: 600;
    color: #495057;
    min-width: 70px;
    display: inline-block;
    margin-right: 8px;
    margin-bottom: 0;
}

.info-value {
    flex: 1;
    color: #212529;
    font-weight: 500;
}

.status-select {
    max-width: 160px;
}

.time-group {
    margin-bottom: 0;
}

.time-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
    display: block;
}

.nav-tabs .nav-item {
    margin-bottom: 0;
}

.nav-tabs .nav-link {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
}

.nav-tabs .nav-link.active {
    background-color: #007bff;
    color: white;
    border-color: #007bff;
}

.table-responsive {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
}

.modal-header {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}

.modal-footer {
    background-color: #f8f9fa;
    border-top: 1px solid #dee2e6;
}

.card {
    box-shadow: none;
    border: 1px solid #dee2e6;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    font-weight: 600;
}

/* Existing styles */
.btn-group button.active {
    box-shadow: inset 0 3px 5px rgba(0,0,0,.125);
}

.table th {
    font-size: 12px;
    font-weight: 600;
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}

.table td {
    font-size: 12px;
    vertical-align: middle;
}



#spkStatus {
    border: 2px solid #e3e6f0;
    border-radius: 6px;
    font-weight: 500;
}

#spkStatus:focus {
    border-color: #4e73df;
    box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
}

.btn-group .btn {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

/* Form control consistency */
.form-control-sm {
    height: calc(1.5em + 0.5rem + 2px);
    font-size: 0.875rem;
}

/* Navigation buttons */
.btn-navigation {
    min-width: 100px;
}

/* Table columns */
.checkbox-column {
    width: 60px;
    text-align: center;
}

.notes-column {
    min-width: 150px;
}

/* Responsive design */
@media (max-width: 768px) {
    .info-group {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .info-label {
        min-width: auto;
        margin-bottom: 0.25rem;
    }
    
    .status-select {
        max-width: 100%;
    }
}
</style>

<script>
// Store SPK data for navigation
window.spkData = @json($spkTindakans->values());
window.currentSpkIndex = 0;

// Initialize time fields on page load
$(document).ready(function() {
    console.log('SPK Data loaded:', window.spkData);
    if (window.spkData && window.spkData.length > 0) {
        updateSpkDisplay(window.currentSpkIndex);
    }
});

function getCurrentSpkId() {
    return window.spkData[window.currentSpkIndex].id;
}

function navigateSpk(direction) {
    if (direction === 'next' && window.currentSpkIndex < window.spkData.length - 1) {
        window.currentSpkIndex++;
    } else if (direction === 'prev' && window.currentSpkIndex > 0) {
        window.currentSpkIndex--;
    }
    
    updateSpkDisplay();
}

function updateSpkDisplay() {
    const currentSpk = window.spkData[window.currentSpkIndex];
    
    // Hide all SPK content
    $('.spk-content').hide();
    
    // Show current SPK content
    $(`.spk-content[data-spk-id="${currentSpk.id}"]`).show();
    
    // Update patient info
    $('#patientRm').text(currentSpk.riwayat_tindakan.visitation.pasien_id || '-');
    $('#patientName').text(currentSpk.riwayat_tindakan.visitation.pasien.nama || '-');
    $('#doctorName').text(currentSpk.riwayat_tindakan.visitation.dokter.user.name || '-');
    $('#tindakanName').text(currentSpk.riwayat_tindakan.tindakan.nama || '-');
    $('#tindakanDate').text(currentSpk.tanggal_tindakan ? new Date(currentSpk.tanggal_tindakan).toLocaleDateString('id-ID') : '-');
    
    // Update time fields (time only format HH:mm)
    let waktuMulai = '';
    let waktuSelesai = '';
    
    console.log('Time data from server:', {
        waktu_mulai: currentSpk.waktu_mulai,
        waktu_selesai: currentSpk.waktu_selesai
    });
    
    // Parse time strings directly (they're already in HH:mm format)
    if (currentSpk.waktu_mulai) {
        const timeStr = currentSpk.waktu_mulai.toString();
        // Check if it's already in HH:mm format
        if (/^\d{1,2}:\d{2}$/.test(timeStr)) {
            waktuMulai = timeStr.padStart(5, '0'); // Ensure HH:mm format
        } else {
            // Try to parse as date and extract time
            const mulaiDate = new Date(currentSpk.waktu_mulai);
            console.log('Parsed mulai date:', mulaiDate);
            if (!isNaN(mulaiDate.getTime())) {
                waktuMulai = String(mulaiDate.getHours()).padStart(2, '0') + ':' + 
                            String(mulaiDate.getMinutes()).padStart(2, '0');
            }
        }
    }
    
    if (currentSpk.waktu_selesai) {
        const timeStr = currentSpk.waktu_selesai.toString();
        // Check if it's already in HH:mm format
        if (/^\d{1,2}:\d{2}$/.test(timeStr)) {
            waktuSelesai = timeStr.padStart(5, '0'); // Ensure HH:mm format
        } else {
            // Try to parse as date and extract time
            const selesaiDate = new Date(currentSpk.waktu_selesai);
            console.log('Parsed selesai date:', selesaiDate);
            if (!isNaN(selesaiDate.getTime())) {
                waktuSelesai = String(selesaiDate.getHours()).padStart(2, '0') + ':' + 
                              String(selesaiDate.getMinutes()).padStart(2, '0');
            }
        }
    }
    
    console.log('Setting time values:', { waktuMulai, waktuSelesai });
    $('#waktuMulai').val(waktuMulai);
    $('#waktuSelesai').val(waktuSelesai);
    
    // Update status dropdown
    $('#spkStatus').val(currentSpk.status);
    
    // Update navigation
    $('#currentSpkIndex').text(window.currentSpkIndex + 1);
    $('#prevSpk').prop('disabled', window.currentSpkIndex === 0);
    $('#nextSpk').prop('disabled', window.currentSpkIndex === window.spkData.length - 1);
    
    // No Select2 for penanggung_jawab - using native select element
}
</script>


<script>
function updateSpkStatusFromDropdown(spkId, status) {
    Swal.fire({
        title: 'Konfirmasi',
        text: `Apakah Anda yakin ingin mengubah status SPK menjadi "${status.replace('_', ' ').toUpperCase()}"?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, Ubah!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (!result.isConfirmed) {
            // Reset dropdown to previous value if user cancels
            location.reload();
            return;
        }
        
        $.ajax({
            url: `{{ url('/erm/spktindakan') }}/${spkId}/status`,
            method: 'POST',
            data: {
                status: status,
                _token: '{{ csrf_token() }}'
            }
        })
        .done(function(response) {
            if (response.success) {
                Swal.fire({
                    title: 'Berhasil!',
                    text: response.message,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
                // Update the parent table if needed
                if (typeof window.parent !== 'undefined' && window.parent.$('#spk-table').length) {
                    window.parent.$('#spk-table').DataTable().ajax.reload();
                }
            } else {
                Swal.fire({
                    title: 'Error!',
                    text: 'Gagal mengubah status',
                    icon: 'error'
                });
                location.reload();
            }
        })
        .fail(function() {
            Swal.fire({
                title: 'Error!',
                text: 'Terjadi kesalahan saat mengubah status',
                icon: 'error'
            });
            location.reload();
        });
    });
}

// Function to set current time to time input (HH:mm format)
function setCurrentTime(inputId) {
    const now = new Date();
    // Format: HH:mm (time only)
    const formattedTime = String(now.getHours()).padStart(2, '0') + ':' + 
                         String(now.getMinutes()).padStart(2, '0');
    
    document.getElementById(inputId).value = formattedTime;
}
</script>
