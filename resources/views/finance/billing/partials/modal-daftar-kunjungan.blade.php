<!-- Modal: Daftarkan Kunjungan (Billing Index) -->
<div class="modal fade" id="modalDaftarKunjunganBillingIndex" tabindex="-1" role="dialog" aria-labelledby="modalDaftarKunjunganBillingIndexLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="form-daftar-kunjungan-billing-index">
            @csrf
            <input type="hidden" name="jenis_kunjungan" id="fb_jenis_kunjungan" value="1">
            <input type="hidden" id="fb_mode" value="konsultasi">
            <input type="hidden" name="force_create_duplicate" id="fb_force_create_duplicate" value="0">

            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="modalDaftarKunjunganBillingIndexLabel">Daftarkan Kunjungan Pasien</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="la la-times"></i></span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="form-group" id="fb_marketplace_patient_mode_group" style="display:none;">
                        <label>Tipe Pasien Marketplace</label>
                        <select id="fb_marketplace_patient_mode" class="form-control">
                            <option value="existing">Pasien Lama</option>
                            <option value="new">Pasien Baru</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label id="fb_pasien_label">Pasien</label>
                        <select id="fb_pasien_id" name="pasien_id" class="form-control" required></select>
                        <small class="form-text text-muted d-none" id="fb_marketplace_pasien_hint">Pilih pasien lama jika sudah ada. Kosongkan pasien untuk membuat pasien marketplace baru.</small>
                    </div>

                    <div class="form-group" id="fb_marketplace_referral_group" style="display:none;">
                        <label>Referral Marketplace</label>
                        <select class="form-control" id="fb_marketplace_referral_detail" name="referral_detail">
                            <option value="">Pilih Marketplace</option>
                            <option value="shopee">Shopee</option>
                            <option value="tiktokshop">Tiktokshop</option>
                            <option value="tokopedia">Tokopedia</option>
                            <option value="lazada">Lazada</option>
                        </select>
                        <small class="form-text text-muted">Untuk pasien lama, isi jika referral marketplace pasien belum ada.</small>
                    </div>

                    <div id="fb_marketplace_patient_section" style="display:none;">
                        <div class="border rounded p-3 mb-3 bg-light">
                            <div class="form-group mb-2">
                                <label>Nama Pasien Baru</label>
                                <input type="text" class="form-control" id="fb_marketplace_nama" name="nama" maxlength="255">
                            </div>

                            <div class="form-group mb-2">
                                <label>Jenis Kelamin</label>
                                <select class="form-control" id="fb_marketplace_gender" name="gender">
                                    <option value="">Pilih Jenis Kelamin</option>
                                    <option value="Laki-laki">Laki-laki</option>
                                    <option value="Perempuan">Perempuan</option>
                                </select>
                            </div>

                            <div class="form-group mb-2">
                                <label>Alamat</label>
                                <textarea class="form-control" id="fb_marketplace_alamat" name="alamat" rows="2"></textarea>
                            </div>

                            <div class="form-group mb-2">
                                <label>No HP</label>
                                <input type="text" class="form-control" id="fb_marketplace_no_hp" name="no_hp" maxlength="20">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Klinik</label>
                        <select id="fb_klinik_id" name="klinik_id" class="form-control select2" required>
                            <option value="" disabled selected>Pilih Klinik</option>
                            @foreach(($kliniks ?? []) as $klinik)
                                <option value="{{ $klinik->id }}">{{ $klinik->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Dokter</label>
                        <select id="fb_dokter_id" name="dokter_id" class="form-control select2" disabled>
                            <option value="">Tanpa Dokter</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Tanggal Kunjungan</label>
                        <input type="date" class="form-control" id="fb_tanggal_visitation" name="tanggal_visitation" required>
                    </div>

                    <div class="form-group" id="fb_waktu_group">
                        <label>Waktu Kunjungan (Opsional)</label>
                        <input type="time" class="form-control" id="fb_waktu_kunjungan" name="waktu_kunjungan">
                    </div>

                    <div class="form-group">
                        <label for="fb_metode_bayar_id">Cara Bayar</label>
                        <select class="form-control select2" id="fb_metode_bayar_id" name="metode_bayar_id" required>
                            <option value="" selected disabled>Pilih Metode Bayar</option>
                            @foreach(($metodeBayar ?? []) as $metode)
                                <option value="{{ $metode->id }}">{{ $metode->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" id="fb_no_antrian_group">
                        <label>No Antrian</label>
                        <input type="text" name="no_antrian" id="fb_no_antrian" class="form-control" readonly>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    window.financeBillingIndexModals = window.financeBillingIndexModals || {};
    if (window.financeBillingIndexModals.__daftarKunjunganMarketplaceReady) {
        return;
    }

    window.financeBillingIndexModals.__daftarKunjunganMarketplaceReady = true;

    function isMarketplaceMode() {
        return $('#fb_mode').val() === 'marketplace';
    }

    function marketplacePatientMode() {
        return $('#fb_marketplace_patient_mode').val() || 'existing';
    }

    function isMarketplaceNewPatient() {
        return isMarketplaceMode() && marketplacePatientMode() === 'new';
    }

    function syncMarketplaceState() {
        const marketplaceMode = isMarketplaceMode();
        const newMarketplacePatient = isMarketplaceNewPatient();
        const existingMarketplacePatient = marketplaceMode && !newMarketplacePatient;

        $('#fb_marketplace_patient_mode_group').toggle(marketplaceMode);
        $('#fb_marketplace_referral_group').toggle(marketplaceMode);
        $('#fb_marketplace_patient_section').toggle(newMarketplacePatient);
        $('#fb_marketplace_pasien_hint').toggleClass('d-none', !marketplaceMode);
        $('#fb_pasien_label').text(marketplaceMode ? 'Pasien Lama' : 'Pasien');
        $('#fb_pasien_id').closest('.form-group').toggle(!marketplaceMode || existingMarketplacePatient);
        $('#fb_pasien_id').prop('required', !marketplaceMode || existingMarketplacePatient);
        $('#fb_force_create_duplicate').val('0');

        $('#fb_marketplace_nama, #fb_marketplace_gender, #fb_marketplace_alamat, #fb_marketplace_no_hp')
            .prop('required', newMarketplacePatient);
        $('#fb_marketplace_referral_detail').prop('required', newMarketplacePatient);
    }

    function resetMarketplaceFields() {
        $('#fb_force_create_duplicate').val('0');
        $('#fb_marketplace_patient_mode').val('existing');
        $('#fb_marketplace_nama').val('');
        $('#fb_marketplace_gender').val('');
        $('#fb_marketplace_alamat').val('');
        $('#fb_marketplace_no_hp').val('');
        $('#fb_marketplace_referral_detail').val('');
    }

    function applyMode(mode) {
        mode = (mode || 'konsultasi').toString();
        $('#fb_mode').val(mode);

        if (mode === 'produk') {
            $('#modalDaftarKunjunganBillingIndexLabel').text('Daftarkan Kunjungan Beli Produk Pasien');
            $('#fb_jenis_kunjungan').val('2');
            $('#fb_waktu_group').hide();
            $('#fb_no_antrian_group').hide();
            $('#fb_waktu_kunjungan').val('');
            $('#fb_no_antrian').val('');
        } else if (mode === 'lab') {
            $('#modalDaftarKunjunganBillingIndexLabel').text('Daftarkan Kunjungan Laboratorium Pasien');
            $('#fb_jenis_kunjungan').val('3');
            $('#fb_waktu_group').hide();
            $('#fb_no_antrian_group').hide();
            $('#fb_waktu_kunjungan').val('');
            $('#fb_no_antrian').val('');
        } else if (mode === 'marketplace') {
            $('#modalDaftarKunjunganBillingIndexLabel').text('Daftarkan Kunjungan Marketplace Pasien');
            $('#fb_jenis_kunjungan').val('5');
            $('#fb_waktu_group').hide();
            $('#fb_no_antrian_group').hide();
            $('#fb_waktu_kunjungan').val('');
            $('#fb_no_antrian').val('');
        } else {
            $('#modalDaftarKunjunganBillingIndexLabel').text('Daftarkan Kunjungan Pasien');
            $('#fb_jenis_kunjungan').val('1');
            $('#fb_waktu_group').show();
            $('#fb_no_antrian_group').show();
        }

        syncMarketplaceState();
    }

    function cekAntrianBilling() {
        let dokterId = $('#fb_dokter_id').val();
        let tanggal = $('#fb_tanggal_visitation').val();
        if (!dokterId || !tanggal) {
            $('#fb_no_antrian').val('');
            return;
        }
        if ($('#fb_mode').val() !== 'konsultasi') return;

        $.get("{{ route('erm.visitations.cekAntrian') }}", { dokter_id: dokterId, tanggal: tanggal }, function(res) {
            $('#fb_no_antrian').val(res.no_antrian || '');
        }).fail(function() {
            $('#fb_no_antrian').val('');
        });
    }

    function resolveSubmitUrl() {
        const mode = $('#fb_mode').val();
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

    function duplicatePayload() {
        return {
            nama: ($('#fb_marketplace_nama').val() || '').trim(),
            referral_detail: ($('#fb_marketplace_referral_detail').val() || '').trim()
        };
    }

    function duplicateHtml(pasien) {
        pasien = pasien || {};
        return [
            '<div>Sudah ada pasien marketplace dengan nama dan referral yang sama.</div>',
            '<div class="mt-2 text-left">',
            '<div><strong>No RM:</strong> ' + $('<div>').text(pasien.id || '-').html() + '</div>',
            '<div><strong>Nama:</strong> ' + $('<div>').text(pasien.nama || '-').html() + '</div>',
            '<div><strong>No HP:</strong> ' + $('<div>').text(pasien.no_hp || '-').html() + '</div>',
            '<div><strong>Referral:</strong> ' + $('<div>').text(pasien.referral_detail || '-').html() + '</div>',
            '</div>',
            '<div class="mt-3">Lanjutkan membuat pasien baru?</div>'
        ].join('');
    }

    function showDuplicateWarning(onConfirm, pasien) {
        Swal.fire({
            icon: 'warning',
            title: 'Data Mirip Ditemukan',
            html: duplicateHtml(pasien),
            showCancelButton: true,
            confirmButtonText: 'Lanjut Buat Pasien Baru',
            cancelButtonText: 'Batal'
        }).then(function(result) {
            if (result.isConfirmed) {
                $('#fb_force_create_duplicate').val('1');
                onConfirm();
            }
        });
    }

    function submitBillingForm() {
        $.ajax({
            url: resolveSubmitUrl(),
            type: 'POST',
            data: $('#form-daftar-kunjungan-billing-index').serialize()
        }).done(function(res) {
            $('#modalDaftarKunjunganBillingIndex').modal('hide');
            $('#form-daftar-kunjungan-billing-index')[0].reset();
            $('#fb_pasien_id').val(null).trigger('change');
            $('#fb_dokter_id').empty().append('<option value="">Tanpa Dokter</option>').prop('disabled', true).trigger('change.select2');
            $('#fb_no_antrian').val('');
            resetMarketplaceFields();

            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: (res && res.message) ? res.message : 'Kunjungan berhasil disimpan.',
                confirmButtonText: 'OK'
            }).then(function() {
                try {
                    if (typeof reloadBillingTables === 'function') reloadBillingTables(true);
                } catch (e) {}
                try {
                    if (typeof fetchTabCounts === 'function') fetchTabCounts();
                } catch (e) {}
            });
        }).fail(function(xhr) {
            if (xhr && xhr.status === 409 && xhr.responseJSON && xhr.responseJSON.duplicate) {
                showDuplicateWarning(submitBillingForm, xhr.responseJSON.pasien || {});
                return;
            }

            let msg = 'Terjadi kesalahan. Pastikan semua data valid.';
            if (xhr && xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }

            Swal.fire({ icon: 'error', title: 'Gagal', text: msg, confirmButtonText: 'OK' });
        });
    }

    function checkDuplicateThenSubmit() {
        $.get("{{ route('erm.pasiens.marketplace.check-duplicate') }}", duplicatePayload())
            .done(function(res) {
                if (res && res.exists) {
                    showDuplicateWarning(submitBillingForm, res.pasien || {});
                    return;
                }

                submitBillingForm();
            })
            .fail(function() {
                submitBillingForm();
            });
    }

    function initSelect2() {
        $('#modalDaftarKunjunganBillingIndex select.select2:not(#fb_pasien_id)').select2({ width: '100%' });

        if ($('#fb_pasien_id').hasClass('select2-hidden-accessible')) {
            return;
        }

        $('#fb_pasien_id').select2({
            width: '100%',
            placeholder: 'Cari pasien (nama / RM / identitas)',
            allowClear: true,
            ajax: {
                url: "{{ route('erm.pasiens.select2') }}",
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return { q: params.term || '' };
                },
                processResults: function(data) {
                    return data;
                },
                cache: true
            }
        });
    }

    function bindEvents() {
        $(document).off('change.billingDaftarKunjungan', '#fb_klinik_id').on('change.billingDaftarKunjungan', '#fb_klinik_id', function() {
            let klinikId = $(this).val();
            let dokterSelect = $('#fb_dokter_id');

            dokterSelect.empty().append('<option value="">Loading...</option>').prop('disabled', true);
            if (!klinikId) {
                dokterSelect.empty().append('<option value="">Tanpa Dokter</option>').prop('disabled', true).trigger('change.select2');
                $('#fb_no_antrian').val('');
                return;
            }

            $.ajax({
                url: `/get-dokters/${klinikId}`,
                type: 'GET'
            }).done(function(data) {
                dokterSelect.empty().append('<option value="">Tanpa Dokter</option>');
                if (data && data.length) {
                    $.each(data, function(_, dokter) {
                        let dokterName = (dokter.user && dokter.user.name) ? dokter.user.name : 'Unknown Doctor';
                        let spesialis = (dokter.spesialisasi && dokter.spesialisasi.nama) ? ` (${dokter.spesialisasi.nama})` : '';
                        dokterSelect.append(`<option value="${dokter.id}">${dokterName}${spesialis}</option>`);
                    });
                }
                dokterSelect.prop('disabled', false).trigger('change.select2');
            }).fail(function() {
                dokterSelect.empty().append('<option value="">Tanpa Dokter</option>').prop('disabled', false).trigger('change.select2');
                Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal mengambil data dokter' });
            });
        });

        $(document).off('change.billingDaftarKunjunganQueue', '#fb_dokter_id, #fb_tanggal_visitation').on('change.billingDaftarKunjunganQueue', '#fb_dokter_id, #fb_tanggal_visitation', function() {
            cekAntrianBilling();
        });

        $(document).off('change.billingDaftarKunjunganPasien', '#fb_pasien_id').on('change.billingDaftarKunjunganPasien', '#fb_pasien_id', function() {
            syncMarketplaceState();
        });

        $(document).off('change.billingDaftarKunjunganPatientMode', '#fb_marketplace_patient_mode').on('change.billingDaftarKunjunganPatientMode', '#fb_marketplace_patient_mode', function() {
            $('#fb_force_create_duplicate').val('0');
            if (marketplacePatientMode() === 'new') {
                $('#fb_pasien_id').val(null).trigger('change');
            }
            syncMarketplaceState();
        });

        $(document).off('submit.billingDaftarKunjungan', '#form-daftar-kunjungan-billing-index').on('submit.billingDaftarKunjungan', '#form-daftar-kunjungan-billing-index', function(e) {
            e.preventDefault();

            if (isMarketplaceNewPatient() && $('#fb_force_create_duplicate').val() !== '1') {
                checkDuplicateThenSubmit();
                return;
            }

            submitBillingForm();
        });

        $(document).off('hidden.bs.modal.billingDaftarKunjungan', '#modalDaftarKunjunganBillingIndex').on('hidden.bs.modal.billingDaftarKunjungan', '#modalDaftarKunjunganBillingIndex', function() {
            try { $('#form-daftar-kunjungan-billing-index')[0].reset(); } catch (e) {}
            try { $('#fb_pasien_id').val(null).trigger('change'); } catch (e) {}
            try { $('#fb_dokter_id').empty().append('<option value="">Tanpa Dokter</option>').prop('disabled', true).trigger('change.select2'); } catch (e) {}
            $('#fb_no_antrian').val('');
            resetMarketplaceFields();
            applyMode('konsultasi');
        });
    }

    window.financeBillingIndexModals.openDaftarKunjunganModal = function(mode) {
        initSelect2();
        bindEvents();
        applyMode(mode || 'konsultasi');

        try {
            if (window.moment) {
                $('#fb_tanggal_visitation').val(moment().format('YYYY-MM-DD'));
            }
        } catch (e) {}

        $('#modalDaftarKunjunganBillingIndex').modal('show');
    };

    initSelect2();
    bindEvents();
    applyMode('konsultasi');
})();
</script>
