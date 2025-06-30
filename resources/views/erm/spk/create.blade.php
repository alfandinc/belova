@extends('layouts.erm.app')

@section('title', 'ERM | Input SPK')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center mb-0 mt-2">
        <h3 class="mb-0 mr-2">SPK & CUCI TANGAN</h3>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box"></div>
        </div>
    </div>
    <form id="spkForm">
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
                        <th style="width: 5%">NO</th>
                        <th style="width: 15%">TINDAKAN</th>
                        <th style="width: 12%">PJ</th>
                        <th style="width: 6%">SBK</th>
                        <th style="width: 6%">SBA</th>
                        <th style="width: 6%">SDC</th>
                        <th style="width: 6%">SDK</th>
                        <th style="width: 6%">SDL</th>
                        <th style="width: 8%">MULAI</th>
                        <th style="width: 8%">SELESAI</th>
                        <th style="width: 22%">NOTES</th>
                    </tr>
                </thead>
                <tbody id="spkTableBody">
                    <!-- Will be populated dynamically -->
                </tbody>
            </table>
        </div>
        <input type="hidden" id="spkInformConsentId" name="inform_consent_id">
        <input type="hidden" id="spkRiwayatTindakanId" name="riwayat_tindakan_id" value="{{ $riwayat->id ?? '' }}">
        <button type="button" class="btn btn-success mt-3" id="saveSpk">Simpan</button>
    </form>
</div>
@endsection
@section('scripts')
<script>
$(document).ready(function() {
    var informConsentId = '{{ $informConsentId ?? '' }}';
    if (informConsentId) {
        $.ajax({
            url: '/erm/tindakan/spk/' + informConsentId,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    $('#spkNamaPasien').val(data.inform_consent.visitation.pasien.nama);
                    $('#spkNoRm').val(data.inform_consent.visitation.pasien.id);
                    $('#spkNamaTindakan').val(data.inform_consent.tindakan.nama);
                    $('#spkDokterPJ').val(data.inform_consent.visitation.dokter.user.name);
                    $('#spkHarga').val(data.inform_consent.tindakan.harga);
                    let tanggalTindakan = data.spk && data.spk.tanggal_tindakan ? data.spk.tanggal_tindakan : '';
                    if (tanggalTindakan) {
                        const date = new Date(tanggalTindakan);
                        tanggalTindakan = date.toISOString().split('T')[0];
                    }
                    $('#spkTanggalTindakan').val(tanggalTindakan || new Date().toISOString().split('T')[0]);
                    let tableHtml = '';
                    data.sop_list.forEach(function(sop, index) {
                        var existingDetail = data.spk ? data.spk.details.find(function(d) { return d.sop_id == sop.id; }) : null;
                        var waktuMulai = existingDetail && existingDetail.waktu_mulai ? existingDetail.waktu_mulai.substring(0,5) : '';
                        var waktuSelesai = existingDetail && existingDetail.waktu_selesai ? existingDetail.waktu_selesai.substring(0,5) : '';
                        tableHtml += `<tr>
                            <td>${index + 1}</td>
                            <td>${sop.nama_sop}</td>
                            <td>
                                <select class="form-control select2-spk" name="details[${index}][penanggung_jawab]" data-sop-id="${sop.id}" required>
                                    <option value="">Pilih PJ</option>
                                </select>
                            </td>
                            <td><input type="checkbox" name="details[${index}][sbk]" ${existingDetail && existingDetail.sbk ? 'checked' : ''}></td>
                            <td><input type="checkbox" name="details[${index}][sba]" ${existingDetail && existingDetail.sba ? 'checked' : ''}></td>
                            <td><input type="checkbox" name="details[${index}][sdc]" ${existingDetail && existingDetail.sdc ? 'checked' : ''}></td>
                            <td><input type="checkbox" name="details[${index}][sdk]" ${existingDetail && existingDetail.sdk ? 'checked' : ''}></td>
                            <td><input type="checkbox" name="details[${index}][sdl]" ${existingDetail && existingDetail.sdl ? 'checked' : ''}></td>
                            <td><input type="time" class="form-control" name="details[${index}][waktu_mulai]" value="${waktuMulai}"></td>
                            <td><input type="time" class="form-control" name="details[${index}][waktu_selesai]" value="${waktuSelesai}"></td>
                            <td><textarea class="form-control" name="details[${index}][notes]" rows="2" placeholder="Catatan...">${existingDetail && existingDetail.notes ? existingDetail.notes : ''}</textarea></td>
                            <input type="hidden" name="details[${index}][sop_id]" value="${sop.id}">
                        </tr>`;
                    });
                    $('#spkTableBody').html(tableHtml);
                    $('.select2-spk').each(function() {
                        var select = $(this);
                        var sopId = select.data('sop-id');
                        var existingDetail = data.spk ? data.spk.details.find(function(d) { return d.sop_id == sopId; }) : null;
                        data.users.forEach(function(user) {
                            var selected = existingDetail && existingDetail.penanggung_jawab === user.name ? 'selected' : '';
                            select.append(`<option value="${user.name}" ${selected}>${user.name}</option>`);
                        });
                        select.select2({ width: '100%' });
                    });
                }
            },
            error: function(xhr) {
                Swal.fire('Error', 'Failed to load SPK data', 'error');
            }
        });
    }
    // Save SPK
    $('#saveSpk').click(function() {
        const spkData = {
            inform_consent_id: $('#spkInformConsentId').val(),
            riwayat_tindakan_id: $('#spkRiwayatTindakanId').val(), // Always send riwayat_tindakan_id
            tanggal_tindakan: $('#spkTanggalTindakan').val(),
            details: []
        };
        $('#spkTableBody tr').each(function(index) {
            const row = $(this);
            const detail = {
                sop_id: row.find('input[name*="[sop_id]"]').val(),
                penanggung_jawab: row.find('select[name*="[penanggung_jawab]"]').val(),
                sbk: row.find('input[name*="[sbk]"]').is(':checked'),
                sba: row.find('input[name*="[sba]"]').is(':checked'),
                sdc: row.find('input[name*="[sdc]"]').is(':checked'),
                sdk: row.find('input[name*="[sdk]"]').is(':checked'),
                sdl: row.find('input[name*="[sdl]"]').is(':checked'),
                waktu_mulai: row.find('input[name*="[waktu_mulai]"]').val(),
                waktu_selesai: row.find('input[name*="[waktu_selesai]"]').val(),
                notes: row.find('textarea[name*="[notes]"]').val()
            };
            if (detail.penanggung_jawab) {
                spkData.details.push(detail);
            }
        });
        if (spkData.details.length === 0) {
            Swal.fire('Error', 'Harap pilih minimal satu penanggung jawab', 'error');
            return;
        }
        Swal.fire({
            title: 'Menyimpan...',
            text: 'Please wait while saving SPK data',
            icon: 'info',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
        });
        $.ajax({
            url: '/erm/tindakan/spk/save',
            method: 'POST',
            data: spkData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire('Success', response.message, 'success');
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function(xhr) {
                let errorMessage = 'Failed to save SPK data';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseText) {
                    errorMessage = xhr.responseText;
                }
                Swal.fire('Error', errorMessage, 'error');
            }
        });
    });
});
</script>
@endsection
