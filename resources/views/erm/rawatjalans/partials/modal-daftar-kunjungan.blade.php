<!-- Modal: Daftarkan Pasien (Rawat Jalan) -->
<div class="modal fade" id="modalDaftarKunjunganRawatJalan" tabindex="-1" role="dialog" aria-labelledby="modalDaftarKunjunganRawatJalanLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="form-daftar-kunjungan-rawatjalan">
            @csrf
            <input type="hidden" name="jenis_kunjungan" id="rj_jenis_kunjungan" value="1">
            <input type="hidden" id="rj_mode" value="konsultasi">
            <input type="hidden" name="force_create_duplicate" id="rj_force_create_duplicate" value="0">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="modalDaftarKunjunganRawatJalanLabel">Daftarkan Kunjungan Pasien</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="la la-times"></i></span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group" id="rj_marketplace_patient_mode_group" style="display:none;">
                        <label>Tipe Pasien Marketplace</label>
                        <select id="rj_marketplace_patient_mode" class="form-control">
                            <option value="existing">Pasien Lama</option>
                            <option value="new">Pasien Baru</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label id="rj_pasien_label">Pasien</label>
                        <select id="rj_pasien_id" name="pasien_id" class="form-control select2" required></select>
                        <small class="form-text text-muted d-none" id="rj_marketplace_pasien_hint">Pilih pasien lama jika sudah ada. Kosongkan pasien untuk membuat pasien marketplace baru.</small>
                    </div>

                    <div class="form-group" id="rj_marketplace_referral_group" style="display:none;">
                        <label>Referral Marketplace</label>
                        <select class="form-control" id="rj_marketplace_referral_detail" name="referral_detail">
                            <option value="">Pilih Marketplace</option>
                            <option value="shopee">Shopee</option>
                            <option value="tiktokshop">Tiktokshop</option>
                            <option value="tokopedia">Tokopedia</option>
                            <option value="lazada">Lazada</option>
                        </select>
                        <small class="form-text text-muted">Untuk pasien lama, isi jika referral marketplace pasien belum ada.</small>
                    </div>

                    <div id="rj_marketplace_patient_section" style="display:none;">
                        <div class="border rounded p-3 mb-3 bg-light">
                            <div class="form-group mb-2">
                                <label>Nama Pasien Baru</label>
                                <input type="text" class="form-control" id="rj_marketplace_nama" name="nama" maxlength="255">
                            </div>

                            <div class="form-group mb-2">
                                <label>Jenis Kelamin</label>
                                <select class="form-control" id="rj_marketplace_gender" name="gender">
                                    <option value="">Pilih Jenis Kelamin</option>
                                    <option value="Laki-laki">Laki-laki</option>
                                    <option value="Perempuan">Perempuan</option>
                                </select>
                            </div>

                            <div class="form-group mb-2">
                                <label>Alamat</label>
                                <textarea class="form-control" id="rj_marketplace_alamat" name="alamat" rows="2"></textarea>
                            </div>

                            <div class="form-group mb-2">
                                <label>No HP</label>
                                <input type="text" class="form-control" id="rj_marketplace_no_hp" name="no_hp" maxlength="20">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Klinik</label>
                        <select id="rj_klinik_id" name="klinik_id" class="form-control select2" required>
                            <option value="" disabled selected>Pilih Klinik</option>
                            @foreach($kliniks as $klinik)
                                <option value="{{ $klinik->id }}">{{ $klinik->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Dokter</label>
                        <select id="rj_dokter_id" name="dokter_id" class="form-control select2" disabled>
                            <option value="">Tanpa Dokter</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Tanggal Kunjungan</label>
                        <input type="date" class="form-control" id="rj_tanggal_visitation" name="tanggal_visitation" required>
                    </div>

                    <div class="form-group" id="rj_waktu_group">
                        <label>Waktu Kunjungan (Opsional)</label>
                        <input type="time" class="form-control" id="rj_waktu_kunjungan" name="waktu_kunjungan">
                    </div>

                    <div class="form-group">
                        <label for="rj_metode_bayar_id">Cara Bayar</label>
                        <select class="form-control select2" id="rj_metode_bayar_id" name="metode_bayar_id" required>
                            <option value="" selected disabled>Pilih Metode Bayar</option>
                            @foreach($metodeBayar as $metode)
                                <option value="{{ $metode->id }}">{{ $metode->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" id="rj_no_antrian_group">
                        <label>No Antrian</label>
                        <input type="text" name="no_antrian" id="rj_no_antrian" class="form-control" readonly>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function(){
    // init select2 inside modal
    $('#modalDaftarKunjunganRawatJalan select.select2:not(#rj_pasien_id)').select2({ width: '100%' });

    // pasien select2 ajax
    $('#rj_pasien_id').select2({
        width: '100%',
        placeholder: 'Cari pasien (nama / RM / identitas)',
        allowClear: true,
        ajax: {
            url: "{{ route('erm.pasiens.select2') }}",
            dataType: 'json',
            delay: 250,
            data: function(params){
                return { q: params.term || '' };
            },
            processResults: function(data){
                return data;
            },
            cache: true
        }
    });

    function isMarketplaceMode() {
        return $('#rj_mode').val() === 'marketplace';
    }

    function marketplacePatientMode() {
        return $('#rj_marketplace_patient_mode').val() || 'existing';
    }

    function isMarketplaceNewPatient() {
        return isMarketplaceMode() && marketplacePatientMode() === 'new';
    }

    function syncMarketplaceState() {
        const marketplaceMode = isMarketplaceMode();
        const newMarketplacePatient = isMarketplaceNewPatient();
        const existingMarketplacePatient = marketplaceMode && !newMarketplacePatient;

        $('#rj_marketplace_patient_mode_group').toggle(marketplaceMode);
        $('#rj_marketplace_referral_group').toggle(marketplaceMode);
        $('#rj_marketplace_patient_section').toggle(newMarketplacePatient);
        $('#rj_marketplace_pasien_hint').toggleClass('d-none', !marketplaceMode);
        $('#rj_pasien_label').text(marketplaceMode ? 'Pasien Lama' : 'Pasien');
        $('#rj_pasien_id').closest('.form-group').toggle(!marketplaceMode || existingMarketplacePatient);
        $('#rj_pasien_id').prop('required', !marketplaceMode || existingMarketplacePatient);
        $('#rj_force_create_duplicate').val('0');

        $('#rj_marketplace_nama, #rj_marketplace_gender, #rj_marketplace_alamat, #rj_marketplace_no_hp')
            .prop('required', newMarketplacePatient);
        $('#rj_marketplace_referral_detail').prop('required', newMarketplacePatient);
    }

    function marketplaceDuplicatePayload() {
        return {
            nama: ($('#rj_marketplace_nama').val() || '').trim(),
            referral_detail: ($('#rj_marketplace_referral_detail').val() || '').trim()
        };
    }

    function marketplaceDuplicateHtml(pasien) {
        pasien = pasien || {};
        const lines = [
            '<div>Sudah ada pasien marketplace dengan nama dan referral yang sama.</div>',
            '<div class="mt-2 text-left">',
            '<div><strong>No RM:</strong> ' + $('<div>').text(pasien.id || '-').html() + '</div>',
            '<div><strong>Nama:</strong> ' + $('<div>').text(pasien.nama || '-').html() + '</div>',
            '<div><strong>No HP:</strong> ' + $('<div>').text(pasien.no_hp || '-').html() + '</div>',
            '<div><strong>Referral:</strong> ' + $('<div>').text(pasien.referral_detail || '-').html() + '</div>',
            '</div>',
            '<div class="mt-3">Lanjutkan membuat pasien baru?</div>'
        ];

        return lines.join('');
    }

    function resetRawatJalanMarketplaceFields() {
        $('#rj_force_create_duplicate').val('0');
        $('#rj_marketplace_patient_mode').val('existing');
        $('#rj_marketplace_nama').val('');
        $('#rj_marketplace_gender').val('');
        $('#rj_marketplace_alamat').val('');
        $('#rj_marketplace_no_hp').val('');
        $('#rj_marketplace_referral_detail').val('');
    }

    function resolveSubmitUrl() {
        const mode = $('#rj_mode').val();
        if (mode === 'produk') {
            return "{{ route('erm.visitations.produk.store') }}";
        }
        if (mode === 'lab') {
            return "{{ route('erm.visitations.lab.store') }}";
        }
        if (mode === 'marketplace') {
            return "{{ route('erm.visitations.marketplace.store') }}";
        }

        return "{{ route('erm.visitations.store') }}";
    }

    function showMarketplaceDuplicateWarning(onConfirm, pasien) {
        Swal.fire({
            icon: 'warning',
            title: 'Data Mirip Ditemukan',
            html: marketplaceDuplicateHtml(pasien),
            showCancelButton: true,
            confirmButtonText: 'Lanjut Buat Pasien Baru',
            cancelButtonText: 'Batal'
        }).then(function(result) {
            if (result.isConfirmed) {
                $('#rj_force_create_duplicate').val('1');
                onConfirm();
            }
        });
    }

    function submitRawatJalanForm() {
        $.ajax({
            url: resolveSubmitUrl(),
            type: 'POST',
            data: $('#form-daftar-kunjungan-rawatjalan').serialize()
        }).done(function(res){
            $('#modalDaftarKunjunganRawatJalan').modal('hide');
            $('#form-daftar-kunjungan-rawatjalan')[0].reset();
            $('#rj_pasien_id').val(null).trigger('change');
            $('#rj_dokter_id').empty().append('<option value="">Tanpa Dokter</option>').prop('disabled', true).trigger('change.select2');
            $('#rj_no_antrian').val('');
            resetRawatJalanMarketplaceFields();

            var htmlParts = ['<div>' + $('<div>').text((res && res.message) ? res.message : 'Kunjungan berhasil disimpan.').html() + '</div>'];

            if (res && res.whatsapp) {
                var wa = res.whatsapp;
                var waMessage = $('<div>').text(wa.message || '').html();
                var statusClass = wa.queued ? 'text-success' : 'text-warning';
                htmlParts.push('<div class="mt-2 ' + statusClass + '"><strong>WhatsApp:</strong> ' + waMessage + '</div>');

                if (wa.schedule_at) {
                    htmlParts.push('<div class="mt-1 text-muted"><small>Jadwal kirim: ' + $('<div>').text(wa.schedule_at).html() + '</small></div>');
                }

                if (wa.client_id) {
                    htmlParts.push('<div class="mt-1 text-muted"><small>Session: ' + $('<div>').text(wa.client_id).html() + '</small></div>');
                }

                if (wa.session_note) {
                    htmlParts.push('<div class="mt-1 text-muted"><small>Status bot: ' + $('<div>').text(wa.session_note).html() + '</small></div>');
                }
            }

            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                html: htmlParts.join(''),
                confirmButtonText: 'OK'
            }).then(function(){
                try {
                    $('#rawatjalan-table').DataTable().ajax.reload(null, false);
                } catch(e) {}
                try {
                    if (typeof updateStats === 'function') updateStats();
                } catch(e) {}
            });
        }).fail(function(xhr){
            if (xhr && xhr.status === 409 && xhr.responseJSON && xhr.responseJSON.duplicate) {
                showMarketplaceDuplicateWarning(submitRawatJalanForm, xhr.responseJSON.pasien || {});
                return;
            }

            let msg = 'Terjadi kesalahan. Pastikan semua data valid.';
            if (xhr && xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }
            Swal.fire({ icon: 'error', title: 'Gagal', text: msg, confirmButtonText: 'OK' });
        });
    }

    function checkMarketplaceDuplicateThenSubmit() {
        $.get("{{ route('erm.pasiens.marketplace.check-duplicate') }}", marketplaceDuplicatePayload())
            .done(function(res) {
                if (res && res.exists) {
                    showMarketplaceDuplicateWarning(submitRawatJalanForm, res.pasien || {});
                    return;
                }

                submitRawatJalanForm();
            })
            .fail(function() {
                submitRawatJalanForm();
            });
    }

    function applyMode(mode){
        mode = (mode || 'konsultasi').toString();
        $('#rj_mode').val(mode);

        if (mode === 'produk') {
            $('#modalDaftarKunjunganRawatJalanLabel').text('Daftarkan Kunjungan Beli Produk Pasien');
            $('#rj_jenis_kunjungan').val('2');
            $('#rj_waktu_group').hide();
            $('#rj_no_antrian_group').hide();
            $('#rj_waktu_kunjungan').val('');
            $('#rj_no_antrian').val('');
        } else if (mode === 'lab') {
            $('#modalDaftarKunjunganRawatJalanLabel').text('Daftarkan Kunjungan Laboratorium Pasien');
            $('#rj_jenis_kunjungan').val('3');
            $('#rj_waktu_group').hide();
            $('#rj_no_antrian_group').hide();
            $('#rj_waktu_kunjungan').val('');
            $('#rj_no_antrian').val('');
        } else if (mode === 'marketplace') {
            $('#modalDaftarKunjunganRawatJalanLabel').text('Daftarkan Kunjungan Marketplace Pasien');
            $('#rj_jenis_kunjungan').val('5');
            $('#rj_waktu_group').hide();
            $('#rj_no_antrian_group').hide();
            $('#rj_waktu_kunjungan').val('');
            $('#rj_no_antrian').val('');
        } else {
            $('#modalDaftarKunjunganRawatJalanLabel').text('Daftarkan Kunjungan Pasien');
            $('#rj_jenis_kunjungan').val('1');
            $('#rj_waktu_group').show();
            $('#rj_no_antrian_group').show();
        }

        syncMarketplaceState();
    }

    // open modal (from dropdown)
    $(document).on('click', '.btn-daftarkan-pasien-rawatjalan', function(e){
        e.preventDefault();
        const mode = $(this).data('jenis') || 'konsultasi';
        applyMode(mode);
        // default tanggal = today
        try {
            if (window.moment) {
                $('#rj_tanggal_visitation').val(moment().format('YYYY-MM-DD'));
            }
        } catch(e) {}

        $('#modalDaftarKunjunganRawatJalan').modal('show');
    });

    function cekAntrianRJ(){
        let dokterId = $('#rj_dokter_id').val();
        let tanggal = $('#rj_tanggal_visitation').val();
        if (!dokterId || !tanggal) {
            $('#rj_no_antrian').val('');
            return;
        }
        if ($('#rj_mode').val() !== 'konsultasi') return;

        $.get("{{ route('erm.visitations.cekAntrian') }}", { dokter_id: dokterId, tanggal: tanggal }, function(res){
            $('#rj_no_antrian').val(res.no_antrian || '');
        }).fail(function(){
            $('#rj_no_antrian').val('');
        });
    }

    // klinik => load doctors
    $('#rj_klinik_id').on('change', function(){
        let klinikId = $(this).val();
        let dokterSelect = $('#rj_dokter_id');

        dokterSelect.empty().append('<option value="">Loading...</option>').prop('disabled', true);
        if (!klinikId) {
            dokterSelect.empty().append('<option value="">Tanpa Dokter</option>').prop('disabled', true).trigger('change.select2');
            $('#rj_no_antrian').val('');
            return;
        }

        $.ajax({
            url: `/get-dokters/${klinikId}`,
            type: 'GET'
        }).done(function(data){
            dokterSelect.empty().append('<option value="">Tanpa Dokter</option>');
            if (data && data.length) {
                $.each(data, function(_, dokter){
                    let dokterName = (dokter.user && dokter.user.name) ? dokter.user.name : 'Unknown Doctor';
                    let spesialis = (dokter.spesialisasi && dokter.spesialisasi.nama) ? ` (${dokter.spesialisasi.nama})` : '';
                    dokterSelect.append(`<option value="${dokter.id}">${dokterName}${spesialis}</option>`);
                });
            }
            dokterSelect.prop('disabled', false).trigger('change.select2');
        }).fail(function(){
            dokterSelect.empty().append('<option value="">Tanpa Dokter</option>').prop('disabled', false).trigger('change.select2');
            Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal mengambil data dokter' });
        });
    });

    // dokter/date => cek antrian
    $('#rj_dokter_id, #rj_tanggal_visitation').on('change', function(){
        cekAntrianRJ();
    });

    $('#rj_marketplace_patient_mode').on('change', function() {
        $('#rj_force_create_duplicate').val('0');
        if (marketplacePatientMode() === 'new') {
            $('#rj_pasien_id').val(null).trigger('change');
        }
        syncMarketplaceState();
    });

    $('#rj_pasien_id').on('change', function(){
        syncMarketplaceState();
    });

    // submit
    $('#form-daftar-kunjungan-rawatjalan').on('submit', function(e){
        e.preventDefault();

        if (isMarketplaceNewPatient() && $('#rj_force_create_duplicate').val() !== '1') {
            checkMarketplaceDuplicateThenSubmit();
            return;
        }

        submitRawatJalanForm();
    });

    // cleanup on close
    $('#modalDaftarKunjunganRawatJalan').on('hidden.bs.modal', function(){
        try { $('#form-daftar-kunjungan-rawatjalan')[0].reset(); } catch(e) {}
        try { $('#rj_pasien_id').val(null).trigger('change'); } catch(e) {}
        try { $('#rj_dokter_id').empty().append('<option value="">Tanpa Dokter</option>').prop('disabled', true).trigger('change.select2'); } catch(e) {}
        $('#rj_no_antrian').val('');
        resetRawatJalanMarketplaceFields();
        applyMode('konsultasi');
    });

    // Default mode
    applyMode('konsultasi');
});
</script>
@endpush
