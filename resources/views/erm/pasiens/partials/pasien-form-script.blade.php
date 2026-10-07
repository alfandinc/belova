<script>
// ---------- Pasien baru / edit pasien (modal) ----------
// window.openPasienFormModal(null) = pasien baru, window.openPasienFormModal(id) = edit pasien.
$(function () {
    const $modal = $('#pasienFormModal');
    const URLS = {
        show: "{{ route('erm.pasien.show', '') }}/",
        select2: "{{ route('erm.pasiens.select2') }}",
        checkDuplicate: "{{ route('erm.pasiens.check-duplicate-name-birthdate') }}",
        checkIdentity: "{{ route('erm.pasiens.check-identity-number') }}"
    };

    const identityDocumentLabels = { ktp: 'NIK', sim: 'Nomor SIM', paspor: 'Nomor Paspor', kia: 'Nomor KIA' };
    const identityDocumentHints = {
        ktp: 'KTP wajib 16 digit angka.',
        sim: 'Masukkan nomor SIM sesuai dokumen.',
        paspor: 'Masukkan nomor paspor sesuai dokumen.',
        kia: 'Masukkan nomor KIA sesuai dokumen anak.'
    };
    const referralDetailOptionMap = {
        social_media: [
            { value: 'instagram', label: 'Instagram' },
            { value: 'tiktok', label: 'Tiktok' },
            { value: 'facebook', label: 'Facebook' },
            { value: 'threads', label: 'Threads' },
            { value: 'twitter', label: 'Twitter' },
            { value: 'whatsapp', label: 'Whatsapp' }
        ],
        marketplace: [
            { value: 'shopee', label: 'Shopee' },
            { value: 'tiktokshop', label: 'Tiktokshop' },
            { value: 'tokopedia', label: 'Tokopedia' },
            { value: 'lazada', label: 'Lazada' }
        ]
    };

    // Reset on every open
    let isEditing = false;
    let duplicateCheckState = {};
    let identityNumberCheckState = {};
    let duplicateModalAllowClose = false;

    function resetState() {
        duplicateCheckState = { signature: null, response: null, acknowledgedSignature: null, promptedSignature: null, pendingSubmit: false };
        identityNumberCheckState = { signature: null, exists: false };
        duplicateModalAllowClose = false;
    }

    function escapeAttr(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#039;')
            .replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function clearInvalid($el) {
        $el.removeClass('is-invalid');
        $el.next('.select2-container').find('.select2-selection').removeClass('is-invalid');
    }

    function reloadPasienList() {
        if (window.pasiensTable && window.pasiensTable.ajax) window.pasiensTable.ajax.reload(null, false);
        if (typeof window.refreshPasienStats === 'function') window.refreshPasienStats();
    }

    // ---------- duplicate name + birth date ----------
    function getDuplicateSignature() {
        const nama = ($('#nama').val() || '').trim().toUpperCase();
        const tanggalLahir = ($('#tanggal_lahir').val() || '').trim();
        return nama && tanggalLahir ? nama + '|' + tanggalLahir : '';
    }

    function setDuplicateAcknowledged(isAcknowledged) {
        $('#duplicate_name_birthdate_acknowledged').val(isAcknowledged ? '1' : '0');
        duplicateCheckState.acknowledgedSignature = isAcknowledged ? getDuplicateSignature() : null;
    }

    function invalidateDuplicateCheckIfNeeded() {
        const signature = getDuplicateSignature();
        if (duplicateCheckState.signature && duplicateCheckState.signature !== signature) {
            duplicateCheckState.signature = null;
            duplicateCheckState.response = null;
            duplicateCheckState.promptedSignature = null;
            duplicateCheckState.pendingSubmit = false;
            setDuplicateAcknowledged(false);
        }
        if (!signature) {
            duplicateCheckState.promptedSignature = null;
            setDuplicateAcknowledged(false);
        }
    }

    // Shared "Daftarkan Kunjungan" dropdown (erm.partials.daftar-kunjungan-dropdown) for one patient.
    function daftarKunjunganDropdownHtml(pasienId, pasienNama) {
        return ($('#tpl-daftar-kunjungan-dropdown').html() || '')
            .split('__PASIEN_ID__').join(escapeAttr(pasienId))
            .split('__PASIEN_NAMA__').join(escapeAttr(pasienNama));
    }

    function renderDuplicatePatients(patients) {
        const rows = (patients || []).map(function (patient) {
            const text = function (v) { return $('<div>').text(v || '-').html(); };
            return '<tr>' +
                '<td>' + text(patient.id) + '</td>' +
                '<td>' + text(patient.nama) + '</td>' +
                '<td>' + text(patient.tanggal_lahir) + '</td>' +
                '<td>' + text(patient.alamat) + '</td>' +
                '<td class="text-center">' + daftarKunjunganDropdownHtml(patient.id, patient.nama) + '</td>' +
                '</tr>';
        }).join('');
        $('#duplicate-pasien-list').html(rows || '<tr><td colspan="5" class="text-center text-muted">Tidak ada data pasien.</td></tr>');
    }

    function openDuplicatePatientsModal(response) {
        const total = response?.count || 0;
        $('#duplicate-pasien-summary').text('Terdapat ' + total + ' pasien dengan kombinasi nama dan tanggal lahir yang sama. Silakan cek sebelum menyimpan pasien baru.');
        renderDuplicatePatients(response?.patients || []);
        duplicateModalAllowClose = false;
        $('#duplicatePasienModal').modal('show');
    }

    function promptDuplicatePatients(response) {
        const currentSignature = getDuplicateSignature();
        if (!currentSignature || duplicateCheckState.acknowledgedSignature === currentSignature || duplicateCheckState.promptedSignature === currentSignature || Swal.isVisible()) {
            return;
        }
        duplicateCheckState.promptedSignature = currentSignature;

        Swal.fire({
            icon: 'warning',
            title: 'Pasien Serupa Ditemukan',
            html: 'Terdapat <strong>' + (response.count || 0) + '</strong> pasien dengan kombinasi nama dan tanggal lahir yang sama di sistem.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showCancelButton: false,
            confirmButtonText: 'Check'
        }).then(function (result) {
            if (result.value) {
                openDuplicatePatientsModal(response);
                return;
            }
            duplicateCheckState.promptedSignature = null;
        });
    }

    function checkDuplicateNameBirthdate(options = {}) {
        const currentSignature = getDuplicateSignature();
        if (!currentSignature) {
            return $.Deferred().resolve({ exists: false, count: 0, patients: [] }).promise();
        }
        if (!options.force && duplicateCheckState.signature === currentSignature && duplicateCheckState.response) {
            return $.Deferred().resolve(duplicateCheckState.response).promise();
        }

        return $.ajax({
            url: URLS.checkDuplicate,
            type: 'GET',
            dataType: 'json',
            data: {
                nama: ($('#nama').val() || '').trim(),
                tanggal_lahir: $('#tanggal_lahir').val(),
                pasien_id: $('#pasien_form_pasien_id').val() || ''
            }
        }).done(function (response) {
            duplicateCheckState.signature = currentSignature;
            duplicateCheckState.response = response;
            if (!response.exists) {
                duplicateCheckState.promptedSignature = null;
                setDuplicateAcknowledged(false);
                return;
            }
            if (duplicateCheckState.acknowledgedSignature !== currentSignature) {
                setDuplicateAcknowledged(false);
                promptDuplicatePatients(response);
            }
        });
    }

    // ---------- submit ----------
    function submitPasienForm() {
        const form = $('#pasien-form');

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: new FormData(form[0]),
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function (response) {
                duplicateCheckState.pendingSubmit = false;
                reloadPasienList();

                if (isEditing) {
                    $modal.modal('hide');
                    Swal.fire({ icon: 'success', title: 'Berhasil!', text: response.message || 'Data pasien berhasil diperbarui.', timer: 1800, showConfirmButton: false });
                    return;
                }

                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Data berhasil disimpan.',
                    confirmButtonText: 'OK'
                }).then(function () {
                    return Swal.fire({
                        title: 'Buka kunjungan?',
                        text: 'Apakah Anda ingin membuka form kunjungan?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Ya',
                        cancelButtonText: 'Tidak'
                    });
                }).then(function (result) {
                    $modal.modal('hide');
                    if (result && result.value && typeof window.openDaftarKunjunganModal === 'function') {
                        // Open the shared Daftarkan Kunjungan modal (konsultasi) once the form modal is gone
                        $modal.one('hidden.bs.modal', function () {
                            window.openDaftarKunjunganModal({ jenis: 'konsultasi', pasienId: response.pasien.id, pasienNama: response.pasien.nama });
                        });
                    }
                });
            },
            error: function (xhr) {
                const errors = xhr.responseJSON?.errors;
                let errorMsg = xhr.responseJSON?.message || 'Terjadi kesalahan saat mengirim data.';

                if (xhr.status === 422 && xhr.responseJSON?.duplicate_name_birthdate) {
                    duplicateCheckState.pendingSubmit = true;
                    duplicateCheckState.response = {
                        exists: true,
                        count: xhr.responseJSON.count || 0,
                        patients: xhr.responseJSON.patients || []
                    };
                    duplicateCheckState.signature = getDuplicateSignature();
                    promptDuplicatePatients(duplicateCheckState.response);
                    return;
                }

                duplicateCheckState.pendingSubmit = false;
                if (errors) {
                    errorMsg = Object.values(errors).map(function (err) { return '• ' + err; }).join('<br>');
                }
                Swal.fire({ title: 'Gagal!', html: errorMsg, icon: 'error', confirmButtonText: 'OK' });
            }
        });
    }

    // ---------- field helpers ----------
    function syncReferralDetailInput(referralType) {
        const detailInput = $('#referral_detail');
        const detailSelect = $('#referral_detail_select');
        const detailSelectContainer = detailSelect.next('.select2-container');
        const selectedOptions = referralDetailOptionMap[referralType] || null;
        const currentValue = (detailInput.val() || '').trim().toLowerCase();

        if (!selectedOptions) {
            detailSelect.addClass('d-none').prop('disabled', true).empty().append('<option value="">Pilih Detail Referral</option>').trigger('change.select2');
            detailSelectContainer.addClass('d-none');
            detailInput.removeClass('d-none');
            return;
        }

        let optionsHtml = '<option value="">Pilih Detail Referral</option>';
        selectedOptions.forEach(function (option) {
            optionsHtml += '<option value="' + option.value + '">' + option.label + '</option>';
        });
        detailSelect.html(optionsHtml).prop('disabled', false).removeClass('d-none');
        detailSelectContainer.removeClass('d-none');
        detailInput.addClass('d-none');
        if (currentValue) {
            detailSelect.val(currentValue);
        }
        detailSelect.trigger('change.select2');
    }

    function syncIdentityInput() {
        const documentType = $('#identity_document').val() || 'ktp';
        const identityInput = $('#identity_number');

        $('#identity_number_label').text(identityDocumentLabels[documentType] || 'Nomor Identitas');
        $('#identity_number_help').text(identityDocumentHints[documentType] || 'Masukkan nomor identitas sesuai dokumen.');

        if (documentType === 'ktp') {
            identityInput.attr({ maxlength: 16, inputmode: 'numeric', placeholder: '16 digit nomor KTP' });
            identityInput.val((identityInput.val() || '').replace(/\D/g, '').slice(0, 16));
            return;
        }
        identityInput.attr({ maxlength: 50, inputmode: 'text', placeholder: 'Masukkan nomor dokumen' });
    }

    function setIdentityNumberValidationState(isValid, message, isWarning = false) {
        const identityInput = $('#identity_number');
        const validationHint = $('#identity_number_validation');

        identityInput[0].setCustomValidity(isValid ? '' : (message || 'Nomor identitas tidak valid.'));
        identityInput.toggleClass('is-invalid', !isValid);
        validationHint.removeClass('d-none text-danger text-success text-warning');
        if (!message) {
            validationHint.addClass('d-none').text('');
            return;
        }
        validationHint.addClass(isValid ? 'text-success' : (isWarning ? 'text-warning' : 'text-danger')).text(message);
    }

    function resetIdentityNumberValidationState() {
        identityNumberCheckState.signature = null;
        identityNumberCheckState.exists = false;
        setIdentityNumberValidationState(true, '');
    }

    function validateIdentityNumberImmediately(options = {}) {
        const documentType = $('#identity_document').val() || 'ktp';
        const identityNumber = ($('#identity_number').val() || '').trim();
        const pasienId = $('#pasien_form_pasien_id').val() || '';
        const signature = documentType + '|' + identityNumber + '|' + pasienId;

        if (!identityNumber) {
            resetIdentityNumberValidationState();
            return $.Deferred().resolve({ valid: true, exists: false }).promise();
        }
        if (documentType === 'ktp' && !/^\d{16}$/.test(identityNumber)) {
            identityNumberCheckState.signature = signature;
            identityNumberCheckState.exists = false;
            setIdentityNumberValidationState(false, 'Nomor identitas untuk KTP harus 16 digit angka.');
            return $.Deferred().resolve({ valid: false, exists: false }).promise();
        }
        if (!options.force && identityNumberCheckState.signature === signature) {
            return $.Deferred().resolve({ valid: !identityNumberCheckState.exists, exists: identityNumberCheckState.exists }).promise();
        }

        setIdentityNumberValidationState(true, 'Sedang mengecek nomor identitas...', true);

        return $.ajax({
            url: URLS.checkIdentity,
            type: 'GET',
            dataType: 'json',
            data: { identity_document: documentType, identity_number: identityNumber, pasien_id: pasienId }
        }).done(function (response) {
            identityNumberCheckState.signature = signature;
            identityNumberCheckState.exists = !!response.exists;
            if (response.valid && !response.exists) {
                setIdentityNumberValidationState(true, 'Nomor identitas tersedia.');
                return;
            }
            const duplicateMessage = response?.pasien
                ? 'Nomor identitas sudah dipakai ' + response.pasien.nama + ' (RM: ' + response.pasien.id + ').'
                : (response.message || 'Nomor identitas sudah digunakan pasien lain.');
            setIdentityNumberValidationState(false, duplicateMessage);
        }).fail(function (xhr) {
            identityNumberCheckState.signature = null;
            identityNumberCheckState.exists = false;
            setIdentityNumberValidationState(false, xhr.responseJSON?.message || 'Gagal memvalidasi nomor identitas. Coba lagi.', true);
        });
    }

    function syncReferralFields() {
        const referralType = $('#referral_type').val() || '';
        const isPasien = referralType === 'pasien';
        const isEmployee = referralType === 'employee';
        const isDokter = referralType === 'dokter';
        const isEvent = referralType === 'event';
        const needsDetail = ['social_media', 'marketplace', 'partnership', 'google_maps'].includes(referralType);

        $('#referral_pasien_wrapper').toggleClass('d-none', !isPasien);
        $('#referral_employee_wrapper').toggleClass('d-none', !isEmployee);
        $('#referral_dokter_wrapper').toggleClass('d-none', !isDokter);
        $('#referral_event_wrapper').toggleClass('d-none', !isEvent);
        $('#referral_detail_wrapper').toggleClass('d-none', !needsDetail);

        $('#referral_target_pasien_id').prop({ required: isPasien, disabled: !isPasien });
        $('#referral_employee_id').prop({ required: isEmployee, disabled: !isEmployee });
        $('#referral_dokter_id').prop({ required: isDokter, disabled: !isDokter });
        $('#referral_event_id').prop({ required: isEvent, disabled: !isEvent });
        $('#referral_detail, #referral_detail_select').prop({ required: needsDetail, disabled: !needsDetail });

        syncReferralDetailInput(referralType);

        if (!isPasien) $('#referral_target_pasien_id').val(null).trigger('change');
        if (!isEmployee) $('#referral_employee_id').val('').trigger('change');
        if (!isDokter) $('#referral_dokter_id').val('').trigger('change');
        if (!isEvent) $('#referral_event_id').val('').trigger('change');
        if (!needsDetail) {
            $('#referral_detail').val('');
            $('#referral_detail_select').val('').trigger('change');
        }
    }

    function syncEmployeePatientField() {
        const isEmployeePatient = $('#is_employee_patient').val() === '1';
        $('#employee_patient_wrapper').toggleClass('d-none', !isEmployeePatient);
        $('#employee_id').prop({ required: isEmployeePatient, disabled: !isEmployeePatient });
        if (!isEmployeePatient) {
            $('#employee_id').val('').trigger('change');
        }
    }

    function normalizePhoneNumber(value) {
        const digits = (value || '').replace(/\D/g, '');
        if (!digits) return '';
        if (digits.startsWith('62')) return digits.slice(2, 15);
        if (digits.startsWith('0')) return digits.slice(1, 14);
        return digits.slice(0, 13);
    }

    function validatePhonePrefix(selector, isRequired) {
        const input = $(selector);
        const normalizedValue = normalizePhoneNumber(input.val() || '');
        input.val(normalizedValue);

        if (!normalizedValue) {
            input[0].setCustomValidity(isRequired ? 'Nomor telepon wajib diisi.' : '');
            input.toggleClass('is-invalid', !!isRequired);
            return;
        }
        if (normalizedValue.startsWith('0')) {
            input[0].setCustomValidity('Isi nomor telepon tanpa 0 di depan.');
            input.addClass('is-invalid');
            return;
        }
        input[0].setCustomValidity('');
        input.removeClass('is-invalid');
    }

    // ---------- wilayah (province > regency > district > village) ----------
    // Like Rawat Jalan: the desa can be searched straight away (all of Indonesia, or within the chosen
    // kecamatan) and picking it fills in kecamatan, kabupaten and provinsi.
    const AREA_PLACEHOLDERS = { regency: 'Pilih Kabupaten', district: 'Pilih Kecamatan', village: 'Pilih Desa' };

    // Clear these levels; the desa stays enabled so it can always be searched directly
    function resetArea(ids) {
        ids.forEach(function (id) {
            $('#' + id).html('<option value="">' + AREA_PLACEHOLDERS[id] + '</option>')
                .prop('disabled', id !== 'village')
                .trigger('change.select2');
        });
    }

    // Fill a regency/district select from a /get-* endpoint; selectedId is picked without firing the cascade
    function loadAreaOptions(id, url, selectedId) {
        const $select = $('#' + id);
        $select.html('<option value="">Loading...</option>').prop('disabled', true).trigger('change.select2');
        return $.get(url).done(function (data) {
            let options = '<option value="">' + AREA_PLACEHOLDERS[id] + '</option>';
            (data || []).forEach(function (item) {
                options += '<option value="' + item.id + '">' + escapeAttr(item.name) + '</option>';
            });
            $select.html(options).prop('disabled', false);
            if (selectedId) $select.val(String(selectedId));
            $select.trigger('change.select2');
        }).fail(function () {
            $select.html('<option value="">' + AREA_PLACEHOLDERS[id] + '</option>').prop('disabled', false).trigger('change.select2');
        });
    }

    // Select the provinsi > kabupaten > kecamatan a desa belongs to
    function setAreaParents(village) {
        const district = village && village.district;
        const regency = district && district.regency;
        const province = regency && regency.province;
        if (!province) return $.Deferred().resolve().promise();

        $('#province').val(String(province.id)).trigger('change.select2');
        return loadAreaOptions('regency', '/get-regencies/' + province.id, regency.id).then(function () {
            return loadAreaOptions('district', '/get-districts/' + regency.id, district.id);
        });
    }

    function initVillageSelect() {
        $('#village').select2({
            width: '100%',
            dropdownParent: $modal,
            placeholder: 'Cari desa/kelurahan...',
            allowClear: true,
            minimumInputLength: 0,
            language: {
                noResults: function () {
                    return $('#district').val() ? 'Desa tidak ditemukan' : 'Ketik minimal 3 huruf nama desa';
                },
                searching: function () { return 'Mencari...'; }
            },
            ajax: {
                url: '/search-villages',
                dataType: 'json',
                delay: 300,
                data: function (params) {
                    return { q: params.term || '', district_id: $('#district').val() || '' };
                },
                processResults: function (data) { return { results: (data && data.results) || [] }; }
            },
            templateSelection: function (item) {
                return item.village ? item.village.name : item.text;
            }
        });
    }

    // ---------- build the form ----------
    function initForm() {
        $('#pasienFormBody').html($('#tpl-pasien-form').html());

        $('#pasien-form').steps({
            headerTag: 'h3',
            bodyTag: 'fieldset',
            transitionEffect: 'slide',
            labels: { finish: isEditing ? 'Update' : 'Simpan', next: 'Lanjut', previous: 'Kembali' },
            onInit: function () {
                $('#pasien-form').addClass('wizard-initialized');
            },
            onStepChanging: function (event, currentIndex, newIndex) {
                if (newIndex < currentIndex) return true; // going back never needs validation
                let isValid = true;
                $('#pasien-form .body:eq(' + currentIndex + ')').find('input, select, textarea').each(function () {
                    const ok = this.checkValidity();
                    $(this).toggleClass('is-invalid', !ok);
                    if ($(this).hasClass('select2-hidden-accessible')) {
                        $(this).next('.select2-container').find('.select2-selection').toggleClass('is-invalid', !ok);
                    }
                    isValid = isValid && ok;
                });
                return isValid;
            },
            onFinished: function () {
                $('#pasien-form').trigger('submit');
            }
        });

        $('#pasien-form select.select2').not('#village').select2({ width: '100%', dropdownParent: $modal });
        initVillageSelect();
        $('#referral_target_pasien_id').select2({
            width: '100%',
            dropdownParent: $modal,
            placeholder: 'Cari pasien referral',
            allowClear: true,
            ajax: {
                url: URLS.select2,
                dataType: 'json',
                delay: 250,
                data: function (params) { return { q: params.term || '' }; },
                processResults: function (data) { return data; }
            }
        });

        resetArea(['regency', 'district', 'village']);
        syncIdentityInput();
        syncReferralFields();
        syncEmployeePatientField();
    }

    // Fill the form with a patient from erm.pasien.show
    function fillForm(p) {
        const raw = function (v) { return v == null || v === 'NULL' ? '' : String(v); };

        $('#pasien_form_pasien_id').val(p.id);
        $('#identity_document').val(p.identity_document || 'ktp').trigger('change.select2');
        $('#identity_number').val(raw(p.identity_number));
        syncIdentityInput();
        $('#nama').val(raw(p.nama));
        $('#tanggal_lahir').val(raw(p.tanggal_lahir).slice(0, 10));
        $('#notes').val(raw(p.notes));
        ['gender', 'agama', 'marital_status', 'pendidikan', 'pekerjaan', 'gol_darah'].forEach(function (field) {
            $('#' + field).val(raw(p[field]) || null).trigger('change.select2');
        });

        $('#is_employee_patient').val(p.employee_id ? '1' : '0').trigger('change.select2');
        syncEmployeePatientField();
        if (p.employee_id) $('#employee_id').val(String(p.employee_id)).trigger('change.select2');

        $('#alamat').val(raw(p.alamat));
        $('#no_hp').val(raw(p.no_hp).replace(/^(62|0)/, ''));
        $('#no_hp2').val(raw(p.no_hp2).replace(/^(62|0)/, ''));
        $('#email').val(raw(p.email));
        $('#instagram').val(raw(p.instagram));

        // Referral: set the type first (it clears the other referral fields), then its detail
        const referralType = p.referral_type || 'walk_in';
        $('#referral_detail').val(raw(p.referral_detail));
        $('#referral_type').val(referralType).trigger('change.select2');
        syncReferralFields();
        if (referralType === 'pasien' && p.referralable_id) {
            const label = p.referralable && p.referralable.nama ? p.referralable.nama + ' (RM: ' + p.referralable_id + ')' : 'RM: ' + p.referralable_id;
            $('#referral_target_pasien_id').append(new Option(label, p.referralable_id, true, true)).trigger('change.select2');
        } else if (referralType === 'employee' && p.referralable_id) {
            $('#referral_employee_id').val(String(p.referralable_id)).trigger('change.select2');
        } else if (referralType === 'dokter' && p.referralable_id) {
            $('#referral_dokter_id').val(String(p.referralable_id)).trigger('change.select2');
        } else if (referralType === 'event' && p.referralable_id) {
            $('#referral_event_id').val(String(p.referralable_id)).trigger('change.select2');
        }
        if (referralDetailOptionMap[referralType]) {
            $('#referral_detail_select').val(raw(p.referral_detail).toLowerCase()).trigger('change.select2');
        }

        // Wilayah: the stored desa, then its kecamatan > kabupaten > provinsi
        if (p.village && p.village.district) {
            $('#village').append(new Option(p.village.name, p.village.id, true, true)).trigger('change.select2');
            setAreaParents(p.village);
        }
    }

    function openPasienFormModal(pasienId) {
        resetState();
        isEditing = !!pasienId;
        $('#pasienFormModalLabel').text(isEditing ? 'Edit Data Pasien' : 'Data Pasien Baru');

        if (!isEditing) {
            initForm();
            $modal.modal('show');
            return;
        }

        $('#pasienFormBody').html('<div class="text-center text-muted py-5"><i class="fas fa-spinner fa-spin mr-1"></i> Memuat data pasien...</div>');
        $modal.modal('show');
        $.get(URLS.show + encodeURIComponent(pasienId))
            .done(function (pasien) {
                initForm();
                fillForm(pasien);
                $('#pasienFormModalLabel').text('Edit Data Pasien — ' + (pasien.nama || '') + ' (RM ' + pasien.id + ')');
            })
            .fail(function () {
                $modal.modal('hide');
                Swal.fire({ icon: 'error', title: 'Gagal', text: 'Terjadi kesalahan saat mengambil data pasien.' });
            });
    }

    window.openPasienFormModal = openPasienFormModal;

    // ---------- events (bound once, delegated to the form inside the modal) ----------
    $modal.on('input', '#nama', function () {
        this.value = this.value.toUpperCase();
        invalidateDuplicateCheckIfNeeded();
    });
    $modal.on('blur', '#nama', function () {
        invalidateDuplicateCheckIfNeeded();
        checkDuplicateNameBirthdate({ force: true });
    });
    $modal.on('input', '#alamat', function () {
        this.value = this.value.toUpperCase();
    });

    $modal.on('change', '#identity_document', function () {
        clearInvalid($('#identity_number'));
        clearInvalid($(this));
        resetIdentityNumberValidationState();
        syncIdentityInput();
        if (($('#identity_number').val() || '').trim()) {
            validateIdentityNumberImmediately({ force: true });
        }
    });
    $modal.on('input', '#identity_number', function () {
        this.value = ($('#identity_document').val() || 'ktp') === 'ktp'
            ? this.value.replace(/\D/g, '').slice(0, 16)
            : this.value.toUpperCase().slice(0, 50);
        resetIdentityNumberValidationState();
    });
    $modal.on('blur', '#identity_number', function () {
        validateIdentityNumberImmediately({ force: true });
    });

    $modal.on('change', '#referral_type', function () {
        clearInvalid($('#referral_target_pasien_id, #referral_employee_id, #referral_dokter_id, #referral_event_id, #referral_detail'));
        clearInvalid($(this));
        syncReferralFields();
    });
    $modal.on('change', '#referral_target_pasien_id, #referral_employee_id, #referral_dokter_id, #referral_event_id, #employee_id', function () {
        clearInvalid($(this));
    });
    $modal.on('change', '#is_employee_patient', function () {
        clearInvalid($('#employee_id'));
        clearInvalid($(this));
        syncEmployeePatientField();
    });
    $modal.on('change', '#referral_detail_select', function () {
        $('#referral_detail').val($(this).val() || '');
        clearInvalid($(this));
    });

    $modal.on('input', '#no_hp, #no_hp2', function () {
        this.value = normalizePhoneNumber(this.value);
        $(this).removeClass('is-invalid');
        this.setCustomValidity('');
    });
    $modal.on('blur', '#no_hp', function () { validatePhonePrefix('#no_hp', true); });
    $modal.on('blur', '#no_hp2', function () { validatePhonePrefix('#no_hp2', false); });

    $modal.on('change', '#tanggal_lahir', function () {
        const selectedDate = new Date(this.value);
        const today = new Date();
        selectedDate.setHours(0, 0, 0, 0);
        today.setHours(0, 0, 0, 0);

        if (selectedDate > today) {
            const input = this;
            Swal.fire({ icon: 'error', title: 'Tanggal tidak valid', text: 'Tanggal lahir tidak boleh lebih dari hari ini!' })
                .then(function () { $(input).val('').focus(); });
            invalidateDuplicateCheckIfNeeded();
            return;
        }
        invalidateDuplicateCheckIfNeeded();
        checkDuplicateNameBirthdate({ force: true });
    });

    $modal.on('blur', '#email', function () {
        const email = $(this).val();
        if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            const input = this;
            Swal.fire({ icon: 'error', title: 'Email tidak valid', text: 'Harap masukkan alamat email yang benar!' })
                .then(function () { $(input).val('').focus(); });
        }
    });

    $modal.on('change', '#province', function () {
        resetArea(['regency', 'district', 'village']);
        if ($(this).val()) loadAreaOptions('regency', '/get-regencies/' + $(this).val());
    });
    $modal.on('change', '#regency', function () {
        resetArea(['district', 'village']);
        if ($(this).val()) loadAreaOptions('district', '/get-districts/' + $(this).val());
    });
    // A new kecamatan narrows the desa search, so clear the desa
    $modal.on('change', '#district', function () {
        resetArea(['village']);
    });
    // Picking a desa directly fills in its kecamatan, kabupaten and provinsi
    $modal.on('select2:select', '#village', function (e) {
        const village = e.params && e.params.data && e.params.data.village;
        clearInvalid($(this));
        if (!village || !village.district) return;
        if (String($('#district').val() || '') === String(village.district.id)) return;
        setAreaParents(village).then(function () {
            clearInvalid($('#province, #regency, #district'));
        });
    });

    $modal.on('submit', '#pasien-form', function (e) {
        e.preventDefault();

        const currentSignature = getDuplicateSignature();
        duplicateCheckState.pendingSubmit = true;

        if (currentSignature && duplicateCheckState.acknowledgedSignature !== currentSignature) {
            checkDuplicateNameBirthdate({ force: true }).done(function (response) {
                if (response.exists && duplicateCheckState.acknowledgedSignature !== currentSignature) {
                    promptDuplicatePatients(response);
                    return;
                }
                submitPasienForm();
            }).fail(function () {
                duplicateCheckState.pendingSubmit = false;
            });
            return;
        }
        submitPasienForm();
    });

    // Empty the form when closed so a stale copy never lingers
    $modal.on('hidden.bs.modal', function () {
        $('#pasienFormBody').empty();
    });

    // ---------- duplicate list modal (opens on top of the form) ----------
    const $duplicateModal = $('#duplicatePasienModal');

    $('#confirm-new-patient-btn').on('click', function () {
        setDuplicateAcknowledged(true);
        duplicateModalAllowClose = true;
        $duplicateModal.modal('hide');
        if (duplicateCheckState.pendingSubmit) {
            submitPasienForm();
        }
    });

    // Picking an existing patient to register: leave the new-patient form
    $(document).on('click', '#duplicate-pasien-list .btn-daftarkan-pasien-rawatjalan', function () {
        duplicateCheckState.pendingSubmit = false;
        duplicateModalAllowClose = true;
        $duplicateModal.modal('hide');
        $modal.modal('hide');
    });

    $duplicateModal.on('shown.bs.modal', function () {
        $('.modal-backdrop').last().css('z-index', 1055);
    });

    $duplicateModal.on('hide.bs.modal', function (event) {
        if (!duplicateModalAllowClose) {
            event.preventDefault();
        }
    });

    $duplicateModal.on('hidden.bs.modal', function () {
        duplicateModalAllowClose = false;
        // Another modal (the form, or Daftarkan Kunjungan) may still be open; keep body scroll locked for it
        if ($('.modal.show').length) {
            $('body').addClass('modal-open');
        }
        if (duplicateCheckState.acknowledgedSignature !== getDuplicateSignature()) {
            duplicateCheckState.promptedSignature = null;
        }
    });
});
</script>
