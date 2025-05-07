<div class="card">
        <div class="card-body">  
                  
            <div class="row mt-0">
                <!-- Kolom Nama -->
                <div class="col-md-3 ">
                    <div class="row mb-0 mt-0">
                        <div class="col-12 d-flex align-items-center">
                            
                            <h3><strong>{{ ucfirst($visitation->pasien->nama ?? '-') }}</strong></h3>
                             
                        </div>     
                    </div> 
                    <div class="row mt-0 mb-4">
                        <div class="col-12 d-flex align-items-center">
                            
                            <h5 class="mt-0 mb-0">NO. RM #{{ $visitation->pasien->id ?? '-' }}</h5>
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
                                <i style="color:white" class="fas fa-home" title="alamat"></i>
                            </span>
                            <strong>{{ ucfirst($visitation->pasien->alamat ?? '-') }}</strong>
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
                </div> 
                <!-- Kolom alergi -->
                <div class="col-md-3 mt-2">
                    <div class="text-end">
                        <span class="d-inline-flex align-items-center justify-content-center rounded mr-2" 
                            style="background-color:red; width: 25px; height: 25px;">
                            <i style="color:white" class="fas fa-capsules" title="no_hp"></i>
                        </span>
                        <strong>Riwayat Alergi :</strong>
                    </div>

                    <div class="text-end mt-2">
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