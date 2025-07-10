@extends('layouts.erm.app')

@section('title', 'ERM | Input SPK')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="d-flex align-items-center mb-0 mt-2">
                <h3 class="mb-0 mr-2">SPK & CUCI TANGAN</h3>
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
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Harga</label>
                            <input type="text" class="form-control" id="spkHarga" readonly>
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
                                <th style="width: 18%">JAM MULAI/SELESAI</th>
                                <th style="width: 30%">NOTES</th>
                            </tr>
                        </thead>
                        <tbody id="spkTableBody">
                            <!-- Will be populated dynamically -->
                        </tbody>
                    </table>
                </div>
                <input type="hidden" id="spkInformConsentId" name="inform_consent_id">
                <input type="hidden" id="spkRiwayatTindakanId" name="riwayat_tindakan_id" value="{{ $riwayat->id ?? '' }}">
                <div class="d-flex justify-content-end">
                    <button type="button" class="btn btn-success" id="saveSpk">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@section('scripts')
<script>
const renderSpkTable = (sopList, spk, users) =>
    sopList.map((sop, index) => {
        const existingDetail = spk?.details?.find(d => d.sop_id == sop.id);
        const waktuMulai = existingDetail?.waktu_mulai?.substring(0,5) || '';
        const waktuSelesai = existingDetail?.waktu_selesai?.substring(0,5) || '';
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
                <input type="time" class="form-control mr-1 spk-mulai" name="details[${index}][waktu_mulai]" value="${waktuMulai}">
                <button type="button" class="btn btn-sm btn-secondary set-time-btn" data-target="mulai">Now</button>
            </td>
            <td class="d-flex align-items-center">
                <input type="time" class="form-control mr-1 spk-selesai" name="details[${index}][waktu_selesai]" value="${waktuSelesai}">
                <button type="button" class="btn btn-sm btn-secondary set-time-btn" data-target="selesai">Now</button>
            </td>
            <td>
                <textarea class="form-control" name="details[${index}][notes]" rows="2" placeholder="Catatan...">${existingDetail?.notes || ''}</textarea>
                <button type="button" class="btn btn-sm btn-primary mt-2 check-all-btn" data-checked="0">Check All</button>
            </td>
            <input type="hidden" name="details[${index}][sop_id]" value="${sop.id}">
        </tr>`;
    }).join('');

const populateSelect2 = (users, spk) => {
    document.querySelectorAll('.select2-spk').forEach(select => {
        const sopId = select.dataset.sopId;
        const existingDetail = spk?.details?.find(d => d.sop_id == sopId);
        users.forEach(user => {
            const option = document.createElement('option');
            option.value = user.name;
            option.textContent = user.name;
            if (existingDetail?.penanggung_jawab === user.name) option.selected = true;
            select.appendChild(option);
        });
        $(select).select2({ width: '100%' });
    });
};

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
                    document.getElementById('spkTableBody').innerHTML = renderSpkTable(data.sop_list, data.spk, data.users);
                    populateSelect2(data.users, data.spk);
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
                Swal.fire('Success', response.message, 'success');
            } else {
                Swal.fire('Error', response.message || 'Failed to save SPK data', 'error');
            }
        })
        .catch(error => {
            Swal.fire('Error', 'Failed to save SPK data', 'error');
        });
    });
    document.getElementById('spkTableBody').addEventListener('click', function(e) {
        if (e.target.classList.contains('check-all-btn')) {
            const row = e.target.closest('tr');
            const checkboxes = row.querySelectorAll('input[type="checkbox"]');
            const isChecked = e.target.getAttribute('data-checked') === '1';
            checkboxes.forEach(cb => cb.checked = !isChecked);
            e.target.setAttribute('data-checked', isChecked ? '0' : '1');
            e.target.textContent = isChecked ? 'Check All' : 'Uncheck All';
        }
        if (e.target.classList.contains('set-time-btn')) {
            const row = e.target.closest('tr');
            const now = new Date();
            const hh = String(now.getHours()).padStart(2, '0');
            const mm = String(now.getMinutes()).padStart(2, '0');
            const currentTime = `${hh}:${mm}`;
            if (e.target.dataset.target === 'mulai') {
                row.querySelector('input.spk-mulai').value = currentTime;
            } else if (e.target.dataset.target === 'selesai') {
                row.querySelector('input.spk-selesai').value = currentTime;
            }
        }
    });
});
</script>
@endsection