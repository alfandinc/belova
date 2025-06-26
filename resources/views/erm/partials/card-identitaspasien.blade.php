
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
                                  title="{{ $config['title'] }}"
                                  data-toggle="modal" 
                                  data-target="#modalStatusPasien"
                                  data-pasien-id="{{ $visitation->pasien->id }}"
                                  data-current-status="{{ $status }}">
                                <i class="{{ $config['icon'] }} text-white" style="font-size: 14px;"></i>
                            </span>
                             
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
        @foreach($alergiNames as $alergiName)
            <span class="badge badge-warning d-inline-flex align-items-center justify-content-center rounded mr-1" 
                style="height: 25px; padding: 0 10px; color:black;">
                <strong>{{ $alergiName }}</strong>
            </span>
        @endforeach
        <button type="button" class="btn btn-sm btn-primary d-flex align-items-center mr-2 mt-2 " style="font-size: 12px;" data-toggle="modal" data-target="#modalAlergi">
            <i class="fas fa-edit mr-1"></i> Edit
        </button>
    </div>
</div>
            </div>
        </div>
    </div>

<!-- Modal Edit Status Pasien -->
<div class="modal fade" id="modalStatusPasien" tabindex="-1" role="dialog" aria-labelledby="modalStatusPasienLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalStatusPasienLabel">Edit Status Pasien</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="statusPasienForm">
                    <div class="form-group">
                        <label for="status_pasien">Status Pasien</label>
                        <select class="form-control" id="status_pasien" name="status_pasien" required>
                            <option value="Regular">Regular</option>
                            <option value="VIP">VIP</option>
                            <option value="Familia">Familia</option>
                            <option value="Black Card">Black Card</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="saveStatusPasien">Simpan</button>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
$(document).ready(function() {
    // Open modal and set current status
    $('#modalStatusPasien').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var pasienId = button.data('pasien-id');
        var currentStatus = button.data('current-status');
        
        $('#status_pasien').val(currentStatus);
        $('#modalStatusPasien').data('pasien-id', pasienId);
    });
    
    // Save status pasien
    $('#saveStatusPasien').on('click', function() {
        var pasienId = $('#modalStatusPasien').data('pasien-id');
        var newStatus = $('#status_pasien').val();
        
        $.ajax({
            url: '/erm/pasiens/' + pasienId + '/update-status',
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                status_pasien: newStatus
            },
            success: function(response) {
                if(response.success) {
                    // Update the icon
                    var statusConfig = {
                        'VIP': {color: '#FFD700', icon: 'fas fa-crown', title: 'VIP Member'},
                        'Familia': {color: '#32CD32', icon: 'fas fa-users', title: 'Familia Member'},
                        'Black Card': {color: '#2F2F2F', icon: 'fas fa-credit-card', title: 'Black Card Member'},
                        'Regular': {color: '#6C757D', icon: 'fas fa-user', title: 'Regular Member'}
                    };
                    
                    var config = statusConfig[newStatus] || statusConfig['Regular'];
                    var $icon = $('.status-pasien-icon');
                    
                    $icon.css('background-color', config.color);
                    $icon.attr('title', config.title);
                    $icon.attr('data-current-status', newStatus);
                    $icon.find('i').attr('class', config.icon + ' text-white');
                    
                    $('#modalStatusPasien').modal('hide');
                    
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Status pasien berhasil diperbarui.',
                        timer: 2000,
                        showConfirmButton: false
                    });
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
    