<div class="card" style="border: 2.5px solid #0d6efd; border-radius: 18px; box-shadow: 0 8px 40px 0 rgba(13,110,253,0.22); margin-bottom: 60px; padding: 6px;">
        <div class="card-body">  

            <div class="row mb-2">
                <div class="col-12">
                    <div style="white-space:nowrap; display:flex; align-items:center; gap:6px;">
                        <h3 class="mb-0" style="margin:0; line-height:1; display:inline-block;"><strong>{{ ucfirst($visitation->pasien->nama ?? '-') }}</strong></h3>
                        @if($visitation->pasien->gender == 'Laki-laki')
                            <span class="d-inline-flex align-items-center justify-content-center"
                                style="width: 24px; height: 24px; background-color: #0d6efd; border-radius: 6px; flex:0 0 24px; display:inline-flex; align-items:center; justify-content:center;">
                                <i class="fas fa-mars text-white" style="font-size: 12px; line-height:1;"></i>
                            </span>
                        @elseif($visitation->pasien->gender == 'Perempuan')
                            <span class="d-inline-flex align-items-center justify-content-center"
                                style="width: 24px; height: 24px; background-color: hotpink; border-radius: 6px; flex:0 0 24px; display:inline-flex; align-items:center; justify-content:center;">
                                <i class="fas fa-venus text-white" style="font-size: 12px; line-height:1;"></i>
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="row mt-0">
                <!-- Kolom Nama -->
                <div class="col-md-3 ">
                    <div class="row mt-0 mb-0">
                        <div class="col-12 d-flex align-items-center">
                            
                        </div>     
                    </div> 
                    <div class="row mt-0 mb-2">
                        <div class="col-12 d-flex align-items-center">
                            
                            <h5 class="mt-0 mb-0">NO. RM #{{ $visitation->pasien->id ?? '-' }}</h5>
                              
                        </div>
                    </div>
                    {{-- Status badges under NO. RM --}}
                    <div class="row mb-2">
                        <div class="col-12">
                            @php
                                $statusConfig = [
                                    'VIP' => ['color' => '#FFD700', 'icon' => 'fas fa-crown', 'title' => 'VIP Member'],
                                    'Familia' => ['color' => '#32CD32', 'icon' => 'fas fa-users', 'title' => 'Familia Member'],
                                    'Black Card' => ['color' => '#2F2F2F', 'icon' => 'fas fa-credit-card', 'title' => 'Black Card Member'],
                                    'Regular' => ['color' => '#6C757D', 'icon' => 'fas fa-user', 'title' => 'Regular Member']
                                ];
                                $status = $visitation->pasien->status_pasien ?? 'Regular';
                                $config = $statusConfig[$status] ?? $statusConfig['Regular'];
                                $akses = $visitation->pasien->status_akses ?? 'normal';
                            @endphp

                            <div class="d-flex align-items-center" style="gap:6px;flex-wrap:wrap;">
                                <span class="badge d-inline-flex align-items-center" style="background: {{ $config['color'] }}; color: #fff; padding:4px 8px; border-radius:10px; font-size:13px;">
                                    <i class="{{ $config['icon'] }}" style="margin-right:8px;font-size:12px;"></i>
                                    <strong style="font-weight:600;">{{ $status }}</strong>
                                </span>

                                @if($akses == 'akses cepat')
                                    <span class="badge d-inline-flex align-items-center" style="background:#007BFF; color:#fff; padding:4px 8px; border-radius:10px; font-size:13px;">
                                        <i class="fas fa-wheelchair" style="margin-right:8px;font-size:12px;"></i>
                                        <strong style="font-weight:600;">Akses Cepat</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="row mb-1 mt-3 align-items-center">
    <div class="col-12 text-start">
        @php
            $lv = null; $lv_raw = $lastVisitDate ?? null;
            $ll = null; $ll_raw = $lastLabVisitDate ?? null;
            try {
                if(isset($lastVisitDate) && $lastVisitDate) {
                    if($lastVisitDate instanceof \Carbon\Carbon || $lastVisitDate instanceof \DateTime) {
                        $lv = $lastVisitDate instanceof \Carbon\Carbon ? $lastVisitDate : \Carbon\Carbon::instance($lastVisitDate);
                    } else {
                        $lv = \Carbon\Carbon::parse($lastVisitDate);
                    }
                }
            } catch (\Exception $e) {
                $lv = null;
            }
            try {
                if(isset($lastLabVisitDate) && $lastLabVisitDate) {
                    if($lastLabVisitDate instanceof \Carbon\Carbon || $lastLabVisitDate instanceof \DateTime) {
                        $ll = $lastLabVisitDate instanceof \Carbon\Carbon ? $lastLabVisitDate : \Carbon\Carbon::instance($lastLabVisitDate);
                    } else {
                        $ll = \Carbon\Carbon::parse($lastLabVisitDate);
                    }
                }
            } catch (\Exception $e) {
                $ll = null;
            }
        @endphp
        <div class="d-inline-flex flex-column align-items-start" style="gap:6px;">
            <span class="badge d-inline-flex align-items-center" style="background:#fff;border:1px solid #0d6efd;color:#0d6efd;padding:3px 6px;border-radius:14px;font-weight:600;font-size:11px;min-width:100px;justify-content:flex-start;">
                <i class="fas fa-history" aria-hidden="true" style="font-size:11px;margin-right:6px;color:#0d6efd;opacity:0.85;"></i>
                <i class="fas fa-calendar-check" aria-hidden="true" style="font-size:12px;margin-right:6px;"></i>
                <span title="{{ $lv ? $lv->translatedFormat('d F Y') : ($lv_raw ?? '-') }}">{{ $lv ? $lv->diffForHumans() : ($lv_raw ?? '-') }}</span>
            </span>

            <span id="lastLabSpan" class="badge d-inline-flex align-items-center" style="background:#fff;border:1px solid #198754;color:#198754;padding:3px 6px;border-radius:14px;font-weight:600;font-size:11px;cursor:pointer;min-width:100px;justify-content:flex-start;" data-toggle="modal" data-target="#modalLastLabPermintaan" title="{{ $ll ? $ll->translatedFormat('d F Y') : ($ll_raw ?? '-') }}">
                <i class="fas fa-history" aria-hidden="true" style="font-size:11px;margin-right:6px;color:#198754;opacity:0.85;"></i>
                <i class="fas fa-vials" aria-hidden="true" style="font-size:12px;margin-right:6px;"></i>
                <span>{{ $ll ? $ll->diffForHumans() : ($ll_raw ?? '-') }}</span>
            </span>
        </div>
    </div>
</div>
                </div>
                <!-- Kolom Kiri -->
                <div class="col-md-3 mt-2">
                    <div class="mb-2">
                        <div class="d-flex align-items-center">
                            <span class="d-inline-flex align-items-center justify-content-center mr-2" 
                                style="background-color:grey; width:24px; height:24px; border-radius:6px; flex:0 0 24px;">
                                <i style="color:white;font-size:12px;line-height:1;" class="fas fa-id-card" title="NIK"></i>
                            </span>
                            <div class="flex-grow-1">
                                <strong>{{ $visitation->pasien->nik ?? '-' }}</strong>
                            </div>
                        </div>
                    </div>

                    @php
                        $tanggalLahir = null;
                        $isBirthday = false;
                        try {
                            if(!empty($visitation->pasien->tanggal_lahir)) {
                                $tanggalLahir = \Carbon\Carbon::parse($visitation->pasien->tanggal_lahir);
                                $isBirthday = $tanggalLahir->isBirthday();
                            }
                        } catch (\Exception $e) {
                            $tanggalLahir = null;
                        }
                    @endphp
                    <div class="mb-2">
                        <div class="d-flex align-items-center">
                            <span class="d-inline-flex align-items-center justify-content-center mr-2" 
                                style="background-color:grey; width:24px; height:24px; border-radius:6px; flex:0 0 24px;">
                                <i style="color:white;font-size:12px;line-height:1;" class="fas fa-birthday-cake" title="tanggal_lahir"></i>
                            </span>
                            <div class="flex-grow-1">
                                <strong style="{{ $isBirthday ? 'color: red;' : '' }}">
                                    {{ $tanggalLahir ? $tanggalLahir->translatedFormat('d F Y') : ($visitation->pasien->tanggal_lahir ?? '-') }}
                                    @if ($isBirthday)
                                        🎉
                                    @endif
                                </strong>
                            </div>
                        </div>
                    </div>

                    <div class="mb-2">
                        <div class="d-flex align-items-center">
                            <span class="d-inline-flex align-items-center justify-content-center mr-2" 
                                style="background-color:grey; width:24px; height:24px; border-radius:6px; flex:0 0 24px;">
                                <i style="color:white;font-size:12px;line-height:1;" class="fas fa-calendar-alt" title="usia"></i>
                            </span>
                            <div class="flex-grow-1">
                                <strong>{{ $usia }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Kolom Kanan -->
                <div class="col-md-3 mt-2">
                    <div class="mb-2">
                        <div class="d-flex align-items-center">
                            <span class="d-inline-flex align-items-center justify-content-center mr-2" 
                                style="background-color:grey; width:28px; height:28px; border-radius:6px; flex:0 0 28px;">
                                <i style="color:white;font-size:14px;" class="fas fa-phone" title="no_hp"></i>
                            </span>
                            <div class="flex-grow-1">
                                <strong>{{ ucfirst($visitation->pasien->no_hp ?? '-') }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="mb-2">
                        <div class="d-flex align-items-center">
                            <span class="d-inline-flex align-items-center justify-content-center mr-2" 
                                style="background-color:grey; width:24px; height:24px; border-radius:6px; flex:0 0 24px;">
                                <i style="color:white;font-size:12px;line-height:1;" class="fab fa-instagram" title="Instagram"></i>
                            </span>
                            <div class="flex-grow-1">
                                @if($visitation->pasien->instagram)
                                    <a href="https://instagram.com/{{ ltrim($visitation->pasien->instagram, '@') }}" target="_blank" class="text-decoration-none">
                                        <strong>{{ ucfirst($visitation->pasien->instagram) }}</strong>
                                    </a>
                                @else
                                    <strong>-</strong>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mb-2">
                        <div class="d-flex align-items-start">
                            <span class="d-inline-flex align-items-center justify-content-center mr-2 mt-1" 
                                style="background-color:grey; width:24px; height:24px; border-radius:6px; flex:0 0 24px;">
                                <i style="color:white;font-size:12px;line-height:1;" class="fas fa-home" title="alamat"></i>
                            </span>
                            @php
                                // Build combined address parts safely (alamat, desa, kecamatan, kabupaten, provinsi)
                                $addressParts = [];
                                if(!empty($visitation->pasien->alamat)) {
                                    $addressParts[] = $visitation->pasien->alamat;
                                }
                                if(isset($visitation->pasien->village) && !empty($visitation->pasien->village->name)) {
                                    $addressParts[] = $visitation->pasien->village->name;
                                }
                                if(isset($visitation->pasien->village->district) && !empty($visitation->pasien->village->district->name)) {
                                    $addressParts[] = $visitation->pasien->village->district->name;
                                }
                                if(isset($visitation->pasien->village->district->regency) && !empty($visitation->pasien->village->district->regency->name)) {
                                    $addressParts[] = $visitation->pasien->village->district->regency->name;
                                }
                                if(isset($visitation->pasien->village->district->regency->province) && !empty($visitation->pasien->village->district->regency->province->name)) {
                                    $addressParts[] = $visitation->pasien->village->district->regency->province->name;
                                }
                                $fullAddress = count($addressParts) ? ucfirst(implode(', ', $addressParts)) : '-';
                            @endphp
                            <div class="flex-grow-1" style="white-space:normal; word-break:break-word; overflow-wrap:anywhere;">
                                <strong>{{ $fullAddress }}</strong>
                            </div>
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

        <!-- Modal for Last Lab Permintaan -->
        <div class="modal fade" id="modalLastLabPermintaan" tabindex="-1" role="dialog" aria-labelledby="modalLastLabPermintaanLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalLastLabPermintaanLabel">Permintaan Lab pada Kunjungan Terkait</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        @if(isset($lastLabPermintaanList) && count($lastLabPermintaanList) > 0)
                            <ul class="list-group">
                                @foreach($lastLabPermintaanList as $permintaan)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        {{ $permintaan->labTest->nama ?? '-' }}
                                        <span class="badge badge-primary badge-pill">{{ $permintaan->status }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="text-center">Tidak ada permintaan lab pada kunjungan ini.</div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
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
