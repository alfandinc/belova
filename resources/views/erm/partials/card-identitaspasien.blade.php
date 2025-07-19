<div class="card">
        <div class="card-body">  
                  
            <div class="row mt-0">
                <!-- Kolom Nama -->
                <div class="col-md-3 ">
                    <div class="row mb-0 mt-0">
                        <div class="col-12 d-flex align-items-center">
                            
                            <h3><strong>{{ ucfirst($visitation->pasien->nama ?? '-') }}</strong></h3>
                            @if($visitation->pasien->gender == 'Laki-laki')
                                <span class="d-inline-flex align-items-center justify-content-center ml-2"
                                    style="width: 25px; height: 25px; background-color: #0d6efd; border-radius: 4px;">
                                    <i class="fas fa-mars text-white" style="font-size: 20px;"></i>
                                </span>
                            @elseif($visitation->pasien->gender == 'Perempuan')
                                <span class="d-inline-flex align-items-center justify-content-center ml-2"
                                    style="width: 25px; height: 25px; background-color: hotpink; border-radius: 4px;">
                                    <i class="fas fa-venus text-white" style="font-size: 20px;"></i>
                                </span>
                            @endif
                            
                            {{-- Status Pasien Icon --}}
                            @php
                                $statusConfig = [
                                    'VIP' => ['color' => '#FFD700', 'icon' => 'fas fa-crown', 'title' => 'VIP Member'],
                                    'Familia' => ['color' => '#32CD32', 'icon' => 'fas fa-users', 'title' => 'Familia Member'],
                                    'Black Card' => ['color' => '#2F2F2F', 'icon' => 'fas fa-credit-card', 'title' => 'Black Card Member'],
                                    'Regular' => ['color' => '#6C757D', 'icon' => 'fas fa-user', 'title' => 'Regular Member']
                                ];
                                $status = $visitation->pasien->status_pasien ?? 'Regular';
                                $config = $statusConfig[$status] ?? $statusConfig['Regular'];
                            @endphp
                            
                            <span class="d-inline-flex align-items-center justify-content-center ml-2 status-pasien-icon" 
                                  style="width: 25px; height: 25px; background-color: {{ $config['color'] }}; border-radius: 4px; cursor: pointer;"
                                  title="Edit Status Pasien"
                                  data-toggle="modal" 
                                  data-target="#modalStatusCombined"
                                  data-pasien-id="{{ $visitation->pasien->id }}"
                                  data-current-status-pasien="{{ $status }}"
                                  data-current-status-akses="{{ $visitation->pasien->status_akses ?? 'normal' }}">
                                <i class="{{ $config['icon'] }} text-white" style="font-size: 14px;"></i>
                            </span>
                            
                            {{-- Status Akses Icon (only show for "akses cepat") --}}
                            @if(($visitation->pasien->status_akses ?? 'normal') == 'akses cepat')
                                <span class="d-inline-flex align-items-center justify-content-center ml-2 status-akses-icon" 
                                      style="width: 25px; height: 25px; background-color: #007BFF; border-radius: 4px; cursor: pointer;"
                                      title="Edit Status Pasien"
                                      data-toggle="modal" 
                                      data-target="#modalStatusCombined"
                                      data-pasien-id="{{ $visitation->pasien->id }}"
                                      data-current-status-pasien="{{ $status }}"
                                      data-current-status-akses="akses cepat">
                                    <i class="fas fa-wheelchair text-white" style="font-size: 14px;"></i>
                                </span>
                            @endif
                            
                            {{-- Edit button for both statuses --}}
                            <button type="button" class="btn btn-sm btn-link p-0 ml-2 edit-combined-status-btn" 
                                  data-toggle="modal" 
                                  data-target="#modalStatusCombined"
                                  data-pasien-id="{{ $visitation->pasien->id }}"
                                  data-current-status-pasien="{{ $status }}"
                                  data-current-status-akses="{{ $visitation->pasien->status_akses ?? 'normal' }}"
                                  title="Edit Status Pasien">
                              <i class="fas fa-edit text-primary"></i>
                            </button>
                             
                        </div>     
                    </div> 
                    <div class="row mt-0 mb-2">
                        <div class="col-12 d-flex align-items-center">
                            
                            <h5 class="mt-0 mb-0">NO. RM #{{ $visitation->pasien->id ?? '-' }}</h5>
                              
                        </div>
                    </div>
                    <div class="row mb-1 align-items-center">
    <div class="col-12 text-end">
        
        Kunjungan Terakhir: {{ $lastVisitDate }}
    </div>
</div>
                </div>
                <!-- Kolom Kiri -->
                <div class="col-md-3 mt-2">
                    <div class="row mb-1 align-items-center">
                        <div class="col-12 text-end">
                            <span class="d-inline-flex align-items-center justify-content-center rounded mr-2" 
                                style="background-color:grey; width: 25px; height: 25px;">
                                <i style="color:white" class="fas fa-id-card" title="NIK"></i>
                            </span>
                            <strong>{{ $visitation->pasien->nik ?? '-' }}</strong>
                        </div>
                    </div>

                    @php
                        $tanggalLahir = \Carbon\Carbon::parse($visitation->pasien->tanggal_lahir);
                        $isBirthday = $tanggalLahir->isBirthday();
                    @endphp
                    <div class="row mb-1 align-items-center">
                        <div class="col-12 text-end">
                            <span class="d-inline-flex align-items-center justify-content-center rounded mr-2" 
                                style="background-color:grey; width: 25px; height: 25px;">
                                <i style="color:white" class="fas fa-birthday-cake" title="tanggal_lahir"></i>
                            </span>
                            <strong style="{{ $isBirthday ? 'color: red;' : '' }}">
                                {{ $visitation->pasien->tanggal_lahir 
                                    ? $tanggalLahir->translatedFormat('d F Y') 
                                    : '-' }}
                                @if ($isBirthday)
                                    🎉
                                @endif
                            </strong>
                        </div>
                    </div>
                    <div class="row mb-1 align-items-center">
                        <div class="col-12 text-end">
                            <span class="d-inline-flex align-items-center justify-content-center rounded mr-2" 
                                style="background-color:grey; width: 25px; height: 25px;">
                                <i style="color:white" class="fas fa-calendar-alt" title="NIK"></i>
                            </span>
                            <strong>{{ $usia }}</strong>
                        </div>
                    </div>
                </div>
                <!-- Kolom Kanan -->
                <div class="col-md-3 mt-2">
                    <div class="row mb-1 align-items-center">
                        <div class="col-12 text-end">
                            <span class="d-inline-flex align-items-center justify-content-center rounded mr-2" 
                                style="background-color:grey; width: 25px; height: 25px;">
                                <i style="color:white" class="fas fa-phone" title="no_hp"></i>
                            </span>
                            <strong>{{ ucfirst($visitation->pasien->no_hp ?? '-') }}</strong>
                        </div>
                    </div>
                    <div class="row mb-1 align-items-center">
                        <div class="col-12 text-end">
                            <span class="d-inline-flex align-items-center justify-content-center rounded mr-2" 
                                style="background-color:grey; width: 25px; height: 25px;">
                                <i style="color:white" class="fab fa-instagram" title="Instagram"></i>
                            </span>
                            @if($visitation->pasien->instagram)
                                <a href="https://instagram.com/{{ ltrim($visitation->pasien->instagram, '@') }}" target="_blank" class="text-decoration-none">
                                    <strong>{{ ucfirst($visitation->pasien->instagram) }}</strong>
                                </a>
                            @else
                                <strong>-</strong>
                            @endif
                        </div>
                    </div>
                    <div class="row mb-1 align-items-center">
                        <div class="col-12 text-end">
                            <span class="d-inline-flex align-items-center justify-content-center rounded mr-2" 
                                style="background-color:grey; width: 25px; height: 25px;">
                                <i style="color:white" class="fas fa-home" title="alamat"></i>
                            </span>
                            <strong>{{ ucfirst($visitation->pasien->alamat ?? '-') }}</strong>
                        </div>
                    </div>
                    
                </div> 
                <!-- Kolom alergi -->
                <!-- Kolom alergi -->
<div class="col-md-3 mt-2">
    <div class="text-end">
        <span class="d-inline-flex align-items-center justify-content-center rounded mr-2" 
            style="background-color:red; width: 25px; height: 25px;">
            <i style="color:white" class="fas fa-capsules" title="no_hp"></i>
        </span>
        <strong class="alergi-label">Alergi : {{ $alergikatakunci ?? '-'}}</strong>
    </div>

    <div class="text-end mt-2 alergi-badges">
        @if(isset($alergiList) && count($alergiList) === 1 && empty($alergiList[0]->zataktif_id))
            <span class="badge d-inline-flex align-items-center justify-content-center rounded mr-1"
                style="height: 25px; padding: 0 10px; color:black; background-color: #ffe066;">
                <strong>alergi belum diverifikasi</strong>
            </span>
        @elseif(isset($alergiNames) && count($alergiNames) > 0)
            @foreach($alergiNames as $alergiName)
                <span class="badge d-inline-flex align-items-center justify-content-center rounded mr-1"
                    style="height: 25px; padding: 0 10px; color:white; background-color: #28a745;">
                    <strong>{{ $alergiName }}</strong>
                </span>
            @endforeach
        @endif
        <button type="button" class="btn btn-sm btn-primary d-flex align-items-center mr-2 mt-2 " style="font-size: 12px;" data-toggle="modal" data-target="#modalAlergi">
            <i class="fas fa-edit mr-1"></i> Edit
        </button>
    </div>
</div>
            </div>
        </div>
    </div>

<!-- Modal Edit Status Pasien & Akses (Combined) -->
<div class="modal fade" id="modalStatusCombined" tabindex="-1" role="dialog" aria-labelledby="modalStatusCombinedLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalStatusCombinedLabel">Edit Status Pasien</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="statusCombinedForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="combined_status_pasien">Status Pasien</label>
                                <select class="form-control" id="combined_status_pasien" name="status_pasien" required>
                                    <option value="Regular">Regular</option>
                                    <option value="VIP">VIP</option>
                                    <option value="Familia">Familia</option>
                                    <option value="Black Card">Black Card</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="combined_status_akses">Status Akses</label>
                                <select class="form-control" id="combined_status_akses" name="status_akses" required>
                                    <option value="normal">Normal</option>
                                    <option value="akses cepat">Akses Cepat</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="saveStatusCombined">Simpan</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
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
</script>
@endpush
