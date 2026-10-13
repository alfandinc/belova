@extends('layouts.erm.app')

@section('title', 'ERM | Input SPK')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-0 mt-2">
                <h3 class="mb-0">SPK</h3>
                
                @if(isset($allRiwayat) && $allRiwayat->count() > 1)
                <div class="tindakan-navigation d-flex align-items-center">
                    <div class="dropdown mr-2">
                        <button class="btn btn-sm btn-info dropdown-toggle" type="button" id="tindakanDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            Tindakan {{ $currentIndex + 1 }} dari {{ $allRiwayat->count() }}
                        </button>
                        <div class="dropdown-menu" aria-labelledby="tindakanDropdown">
                            @foreach($allRiwayat as $index => $item)
                                <a class="dropdown-item {{ $index == $currentIndex ? 'active' : '' }}" 
                                   href="{{ route('erm.spk.create', ['visitation_id' => $riwayat->visitation_id, 'index' => $index]) }}">
                                   {{ $index + 1 }}. {{ $item->tindakan->nama ?? 'Tindakan' }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                    <div class="btn-group">
                        <a href="{{ route('erm.spk.create', ['visitation_id' => $riwayat->visitation_id, 'index' => max(0, $currentIndex - 1)]) }}" 
                           class="btn btn-sm btn-outline-secondary {{ $currentIndex <= 0 ? 'disabled' : '' }}">
                            <i class="fas fa-chevron-left"></i> Prev
                        </a>
                        <a href="{{ route('erm.spk.create', ['visitation_id' => $riwayat->visitation_id, 'index' => min($allRiwayat->count() - 1, $currentIndex + 1)]) }}" 
                           class="btn btn-sm btn-outline-secondary {{ $currentIndex >= $allRiwayat->count() - 1 ? 'disabled' : '' }}">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                </div>
                @endif
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <div class="page-title-box"></div>
                </div>
            </div>
            <form id="spkForm">
                @csrf
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Nama Pasien</label>
                            <input type="text" class="form-control" id="spkNamaPasien" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>No RM</label>
                            <input type="text" class="form-control" id="spkNoRm" readonly>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Tanggal Tindakan</label>
                            <input type="date" class="form-control" id="spkTanggalTindakan" name="tanggal_tindakan">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Nama Tindakan</label>
                            <input type="text" class="form-control" id="spkNamaTindakan" readonly>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Dokter Penanggung Jawab</label>
                            <input type="text" class="form-control" id="spkDokterPJ" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Harga</label>
                            <input type="text" class="form-control" id="spkHarga" readonly>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Jam Mulai</label>
                            <div class="input-group">
                                <input type="time" class="form-control" id="globalJamMulai" name="waktu_mulai">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-secondary" id="btnNowJamMulai">Now</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Jam Selesai</label>
                            <div class="input-group">
                                <input type="time" class="form-control" id="globalJamSelesai" name="waktu_selesai">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-secondary" id="btnNowJamSelesai">Now</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <input type="hidden" id="spkInformConsentId" name="inform_consent_id">
                <input type="hidden" id="spkRiwayatTindakanId" name="riwayat_tindakan_id" value="{{ $riwayat->id ?? '' }}">
                
                <div class="d-flex justify-content-between align-items-center">
                    @if(isset($allRiwayat) && $allRiwayat->count() > 1)
                    <div>
                        <a href="{{ route('erm.spk.create', ['visitation_id' => $riwayat->visitation_id, 'index' => max(0, $currentIndex - 1)]) }}" 
                           class="btn btn-outline-primary {{ $currentIndex <= 0 ? 'disabled' : '' }}">
                            <i class="fas fa-chevron-left"></i> Tindakan Sebelumnya
                        </a>
                        <a href="{{ route('erm.spk.create', ['visitation_id' => $riwayat->visitation_id, 'index' => min($allRiwayat->count() - 1, $currentIndex + 1)]) }}" 
                           class="btn btn-outline-primary {{ $currentIndex >= $allRiwayat->count() - 1 ? 'disabled' : '' }}">
                            Tindakan Berikutnya <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                    @else
                    <div></div>
                    @endif
                    
                    <button type="button" class="btn btn-success" id="saveSpk">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const riwayatTindakanId = document.getElementById('spkRiwayatTindakanId')?.value;
    if (riwayatTindakanId) {
        fetch(`/erm/tindakan/spk/by-riwayat/${riwayatTindakanId}`)
            .then(res => res.json())
            .then(response => {
                if (response.success) {
                    const data = response.data;
                    document.getElementById('spkNamaPasien').value = data.pasien_nama;
                    document.getElementById('spkNoRm').value = data.pasien_id;
                    document.getElementById('spkNamaTindakan').value = data.tindakan_nama;
                    document.getElementById('spkDokterPJ').value = data.dokter_nama;
                    document.getElementById('spkHarga').value = data.harga;
                    let tanggalTindakan = data.spk?.tanggal_tindakan || '';
                    if (tanggalTindakan) {
                        tanggalTindakan = new Date(tanggalTindakan).toISOString().split('T')[0];
                    }
                    document.getElementById('spkTanggalTindakan').value = tanggalTindakan || new Date().toISOString().split('T')[0];
                    // Jam mulai/selesai are stored on the SPK itself
                    if (data.spk && data.spk.waktu_mulai) {
                        document.getElementById('globalJamMulai').value = data.spk.waktu_mulai.substring(0,5);
                    }
                    if (data.spk && data.spk.waktu_selesai) {
                        document.getElementById('globalJamSelesai').value = data.spk.waktu_selesai.substring(0,5);
                    }
                }
            })
            .catch(() => Swal.fire('Error', 'Failed to load SPK data', 'error'));
    }
    document.getElementById('saveSpk').addEventListener('click', () => {
        const form = document.getElementById('spkForm');
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
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(res => res.json())
        .then(response => {
            if (response.success) {
                // Get the current URL parameters
                const urlParams = new URLSearchParams(window.location.search);
                const visitationId = urlParams.get('visitation_id');
                const currentIndex = parseInt(urlParams.get('index') || '0');
                
                // Check if we have more tindakan to navigate to
                @if(isset($allRiwayat) && $allRiwayat->count() > 1)
                const totalTindakan = {{ $allRiwayat->count() }};
                if (visitationId && currentIndex < totalTindakan - 1) {
                    // Show success and offer to navigate to next tindakan
                    Swal.fire({
                        title: 'Berhasil!',
                        text: response.message + '. Lanjutkan ke tindakan berikutnya?',
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, lanjutkan',
                        cancelButtonText: 'Tidak, tetap di halaman ini'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Navigate to next tindakan
                            window.location.href = `{{ route('erm.spk.create') }}?visitation_id=${visitationId}&index=${currentIndex + 1}`;
                        }
                    });
                } else {
                    Swal.fire('Berhasil!', response.message, 'success');
                }
                @else
                Swal.fire('Berhasil!', response.message, 'success');
                @endif
            } else {
                Swal.fire('Error', response.message || 'Failed to save SPK data', 'error');
            }
        })
        .catch(error => {
            Swal.fire('Error', 'Failed to save SPK data', 'error');
        });
    });
    document.getElementById('btnNowJamMulai').addEventListener('click', function() {
        const now = new Date();
        const hh = String(now.getHours()).padStart(2, '0');
        const mm = String(now.getMinutes()).padStart(2, '0');
        document.getElementById('globalJamMulai').value = `${hh}:${mm}`;
    });
    document.getElementById('btnNowJamSelesai').addEventListener('click', function() {
        const now = new Date();
        const hh = String(now.getHours()).padStart(2, '0');
        const mm = String(now.getMinutes()).padStart(2, '0');
        document.getElementById('globalJamSelesai').value = `${hh}:${mm}`;
    });
});
</script>
@endsection