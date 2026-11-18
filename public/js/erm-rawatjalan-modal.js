$(document).ready(function(){
    let rjFixedPasienContext = null;
    let rjCalendarPreviewVisible = false;
    let rjSubmitting = false;
    let rjPendingDokterId = null;

    // init select2 inside modal
    $('#modalDaftarKunjunganRawatJalan select.select2:not(#rj_pasien_id)').select2({ width: '100%' });

    // pasien select2 ajax
    $('#rj_pasien_id').select2({
        width: '100%',
        placeholder: 'Cari pasien (nama / RM / identitas)',
        allowClear: true,
        ajax: {
            url: window.ErmRawatJalanConfig.routes["erm.pasiens.select2"],
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

    // ===== Referral kunjungan =====
    // First visit: locked to the patient's source referral. Later visits: staff must pick one.
    const RJ_REFERRAL_LABELS = {
        walk_in: 'Walk-in',
        pasien: 'Pasien',
        dokter: 'Dokter',
        employee: 'Karyawan',
        social_media: 'Social Media',
        marketplace: 'Marketplace',
        event: 'Event',
        website: 'Website',
        partnership: 'B2B Partnership',
        google_maps: 'Google Maps',
        follow_up: 'Follow Up CRM'
    };
    const RJ_REFERRAL_DETAIL_OPTIONS = {
        social_media: [
            { value: 'instagram', label: 'Instagram' },
            { value: 'tiktok', label: 'TikTok' },
            { value: 'facebook', label: 'Facebook' },
            { value: 'threads', label: 'Threads' },
            { value: 'twitter', label: 'Twitter / X' },
            { value: 'whatsapp', label: 'WhatsApp' }
        ],
        marketplace: [
            { value: 'shopee', label: 'Shopee' },
            { value: 'tiktokshop', label: 'TikTok Shop' },
            { value: 'tokopedia', label: 'Tokopedia' },
            { value: 'lazada', label: 'Lazada' }
        ]
    };
    const RJ_REFERRAL_TYPES_WITH_DETAIL = ['social_media', 'marketplace', 'partnership', 'google_maps'];
    let rjReferralContext = null;
    // Follow up the modal was opened from ({ id, label }); becomes the visit referral
    let rjPendingFollowUp = null;

    $('#modalDaftarKunjunganRawatJalan .rj-referral-select2').select2({ width: '100%' });
    $('#rj_referral_target_pasien_id').select2({
        width: '100%',
        placeholder: 'Cari pasien referral',
        allowClear: true,
        ajax: {
            url: window.ErmRawatJalanConfig.routes["erm.pasiens.select2"],
            dataType: 'json',
            delay: 250,
            data: function(params){
                return { q: params.term || '' };
            },
            processResults: function(data){
                return data;
            }
        }
    });

    function currentRjPasienId() {
        return (rjFixedPasienContext && rjFixedPasienContext.id) || $('#rj_pasien_id').val() || '';
    }

    // Repeat visit registered by a dokter: referral is that dokter (set server-side too).
    function isRjReferralLockedToDokter() {
        return !!rjReferralContext
            && !rjReferralContext.is_first_visit
            && !!rjReferralContext.registrar_dokter;
    }

    function isRjReferralEditable() {
        return !isMarketplaceMode()
            && !!currentRjPasienId()
            && !!rjReferralContext
            && !rjReferralContext.is_first_visit
            && !isRjReferralLockedToDokter();
    }

    function rjReferralSourceLabel(source) {
        if (!source) {
            return '-';
        }

        const typeLabel = RJ_REFERRAL_LABELS[source.referral_type] || source.referral_type || 'Walk-in';
        const detail = source.referralable_label || source.referral_detail || '';

        return detail ? typeLabel + ': ' + detail : typeLabel;
    }

    function resetRjReferralInputs() {
        $('#rj_referral_type').val('').trigger('change.select2');
        $('#rj_referral_employee_id, #rj_referral_dokter_id, #rj_referral_event_id').val('').trigger('change.select2');
        $('#rj_referral_target_pasien_id').val(null).trigger('change.select2');
        $('#rj_referral_follow_up_id').empty().trigger('change.select2');
        $('#rj_referral_detail_select').empty();
        $('#rj_referral_detail_input').val('');
    }

    // Options = the patient's open follow ups (+ the one the modal was opened from)
    function fillRjFollowUpOptions(followUps) {
        const $select = $('#rj_referral_follow_up_id').empty();
        const list = (followUps || []).slice();
        if (rjPendingFollowUp && !list.some(function(f) { return String(f.id) === String(rjPendingFollowUp.id); })) {
            list.unshift(rjPendingFollowUp);
        }
        list.forEach(function(f) {
            $select.append(new Option(f.label || ('Follow up #' + f.id), f.id, false, false));
        });
        $select.val(list.length ? String((rjPendingFollowUp || list[0]).id) : null).trigger('change.select2');
        return list;
    }

    function syncRjReferralFields() {
        const editable = isRjReferralEditable();
        const type = $('#rj_referral_type').val() || '';
        const detailOptions = RJ_REFERRAL_DETAIL_OPTIONS[type] || null;
        const needsDetail = RJ_REFERRAL_TYPES_WITH_DETAIL.includes(type);
        const detailSelect = $('#rj_referral_detail_select');

        $('#rj_referral_type').prop('disabled', !editable);

        // Sub-fields only matter while the staff is choosing a referral.
        $('#rj_referral_pasien_wrapper').toggleClass('d-none', !editable || type !== 'pasien');
        $('#rj_referral_employee_wrapper').toggleClass('d-none', !editable || type !== 'employee');
        $('#rj_referral_dokter_wrapper').toggleClass('d-none', !editable || type !== 'dokter');
        $('#rj_referral_event_wrapper').toggleClass('d-none', !editable || type !== 'event');
        $('#rj_referral_follow_up_wrapper').toggleClass('d-none', !editable || type !== 'follow_up');
        $('#rj_referral_detail_wrapper').toggleClass('d-none', !editable || !needsDetail);

        $('#rj_referral_target_pasien_id').prop('disabled', !editable || type !== 'pasien');
        $('#rj_referral_employee_id').prop('disabled', !editable || type !== 'employee');
        $('#rj_referral_dokter_id').prop('disabled', !editable || type !== 'dokter');
        $('#rj_referral_event_id').prop('disabled', !editable || type !== 'event');
        $('#rj_referral_follow_up_id').prop('disabled', !editable || type !== 'follow_up');

        if (detailOptions) {
            const currentValue = detailSelect.val() || '';
            let optionsHtml = '<option value="">Pilih Detail Referral</option>';
            detailOptions.forEach(function(option) {
                optionsHtml += '<option value="' + option.value + '">' + option.label + '</option>';
            });
            detailSelect.html(optionsHtml).val(currentValue);
        } else {
            detailSelect.empty();
        }

        detailSelect.toggleClass('d-none', !detailOptions).prop('disabled', !editable || !detailOptions);
        $('#rj_referral_detail_input')
            .toggleClass('d-none', !needsDetail || !!detailOptions)
            .prop('disabled', !editable || !needsDetail || !!detailOptions);
    }

    function loadRjReferralContext() {
        const pasienId = currentRjPasienId();

        rjReferralContext = null;
        resetRjReferralInputs();
        $('#rj_referral_section').toggle(!isMarketplaceMode());

        if (isMarketplaceMode() || !pasienId) {
            $('#rj_referral_hint').text('Pilih pasien terlebih dahulu.');
            syncRjReferralFields();
            return;
        }

        $('#rj_referral_hint').text('Memuat referral pasien...');
        syncRjReferralFields();

        $.get(window.ErmRawatJalanConfig.routes["erm.visitations.referral-context"], { pasien_id: pasienId })
            .done(function(res) {
                if (currentRjPasienId() !== pasienId) {
                    return;
                }

                rjReferralContext = res || { is_first_visit: false, source: null };
                const sourceLabel = rjReferralSourceLabel(rjReferralContext.source);

                if (rjReferralContext.is_first_visit) {
                    $('#rj_referral_type').val(rjReferralContext.source.referral_type).trigger('change.select2');
                    $('#rj_referral_hint').text('Kunjungan pertama — otomatis mengikuti sumber pasien (' + sourceLabel + ').');
                } else if (isRjReferralLockedToDokter()) {
                    $('#rj_referral_type').val('dokter').trigger('change.select2');
                    $('#rj_referral_hint').text('Didaftarkan oleh dokter — referral otomatis: Dokter (' + rjReferralContext.registrar_dokter.label + '). Sumber awal pasien: ' + sourceLabel + '.');
                } else if (fillRjFollowUpOptions(rjReferralContext.follow_ups).length) {
                    // Booked from a CRM follow up: that follow up is the referral, still changeable
                    $('#rj_referral_type').val('follow_up').trigger('change.select2');
                    $('#rj_referral_hint').text('Referral otomatis dari follow up CRM. Ganti bila pasien datang karena hal lain. Sumber awal pasien: ' + sourceLabel + '.');
                } else {
                    $('#rj_referral_hint').text('Sumber awal pasien: ' + sourceLabel + '. Pilih alasan datang kunjungan ini.');
                }

                syncRjReferralFields();
            })
            .fail(function() {
                if (currentRjPasienId() !== pasienId) {
                    return;
                }

                rjReferralContext = { is_first_visit: false, source: null };
                $('#rj_referral_hint').text('Gagal memuat sumber pasien. Pilih referral kunjungan.');
                syncRjReferralFields();
            });
    }

    function validateRjReferral() {
        if (!isRjReferralEditable()) {
            return null;
        }

        const type = $('#rj_referral_type').val() || '';

        if (!type) return 'Referral kunjungan wajib dipilih.';
        if (type === 'pasien' && !$('#rj_referral_target_pasien_id').val()) return 'Pasien referral wajib dipilih.';
        if (type === 'employee' && !$('#rj_referral_employee_id').val()) return 'Karyawan referral wajib dipilih.';
        if (type === 'dokter' && !$('#rj_referral_dokter_id').val()) return 'Dokter referral wajib dipilih.';
        if (type === 'event' && !$('#rj_referral_event_id').val()) return 'Event referral wajib dipilih.';
        if (type === 'follow_up' && !$('#rj_referral_follow_up_id').val()) return 'Pasien ini tidak punya follow up yang bisa dipakai. Pilih referral lain.';
        if (RJ_REFERRAL_DETAIL_OPTIONS[type] && !$('#rj_referral_detail_select').val()) return 'Detail referral wajib dipilih.';
        if (['partnership', 'google_maps'].includes(type) && !($('#rj_referral_detail_input').val() || '').trim()) return 'Detail referral wajib diisi.';

        return null;
    }

    // ===== Cara bayar: Umum / Asuransi =====
    const RJ_METODE_BAYAR = window.ErmRawatJalanConfig.metodeBayar;

    $('#rj_metode_bayar_id').select2({ width: '100%' });

    function rjMetodeBayarGroup() {
        return $('input[name="metode_bayar_group"]:checked').val() || 'umum';
    }

    function setRjMetodeBayarGroup(group, selectedId) {
        const isAsuransi = group === 'asuransi';
        const options = RJ_METODE_BAYAR.filter(function(m) { return m.is_asuransi === isAsuransi; });
        const $select = $('#rj_metode_bayar_id').empty();

        $('input[name="metode_bayar_group"]').each(function() {
            const selected = this.value === group;
            this.checked = selected;
            $(this).closest('label').toggleClass('active', selected);
        });

        if (options.length !== 1) {
            $select.append(new Option(isAsuransi ? 'Pilih Asuransi' : 'Pilih Metode Bayar', '', true, true));
        }
        options.forEach(function(m) {
            $select.append(new Option(m.nama, m.id, false, false));
        });
        if (selectedId && options.some(function(m) { return String(m.id) === String(selectedId); })) {
            $select.val(String(selectedId));
        } else if (options.length === 1) {
            $select.val(String(options[0].id));
        }
        $select.trigger('change.select2');

        // Umum usually has a single option ("Umum"): no picker needed.
        $('#rj_metode_bayar_wrapper').toggleClass('d-none', !isAsuransi && options.length === 1);
        $('#rj_metode_bayar_label').text(isAsuransi ? 'Penjamin Asuransi' : 'Metode Bayar');
    }

    $(document).on('change', 'input[name="metode_bayar_group"]', function() {
        setRjMetodeBayarGroup(this.value);
    });

    $('#rj_referral_type').on('change', function() {
        $('#rj_referral_employee_id, #rj_referral_dokter_id, #rj_referral_event_id').val('').trigger('change.select2');
        $('#rj_referral_target_pasien_id').val(null).trigger('change.select2');
        $('#rj_referral_detail_select').val('');
        $('#rj_referral_detail_input').val('');
        if ($(this).val() === 'follow_up') {
            fillRjFollowUpOptions(rjReferralContext ? rjReferralContext.follow_ups : []);
        }
        syncRjReferralFields();
    });

    function isMarketplaceMode() {
        return $('#rj_mode').val() === 'marketplace';
    }

    function isEventMode() {
        return $('#rj_mode').val() === 'event';
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
        const hasFixedPasien = !!(rjFixedPasienContext && rjFixedPasienContext.id);
        const existingMarketplacePatient = marketplaceMode && !newMarketplacePatient;
        // A fixed patient (opened from a patient/visit context) stays visible as a locked, read-only field.
        const shouldShowPatientSelector = hasFixedPasien ? true : (!marketplaceMode || existingMarketplacePatient);
        const shouldShowPatientModeGroup = marketplaceMode && !hasFixedPasien;
        const shouldShowMarketplacePatientSection = newMarketplacePatient && !hasFixedPasien;

        $('#rj_marketplace_patient_mode_group').toggle(shouldShowPatientModeGroup);
        // Toggle buttons mirror the hidden select (also when it is set from code)
        $('.rj-mp-mode-btn').each(function() {
            const on = $(this).data('value') === marketplacePatientMode();
            $(this).toggleClass('active', on).attr('aria-checked', on ? 'true' : 'false');
        });
        $('#rj_marketplace_referral_group').toggle(marketplaceMode);
        $('#rj_marketplace_patient_section').toggle(shouldShowMarketplacePatientSection);
        $('#rj_marketplace_pasien_hint').toggleClass('d-none', !marketplaceMode || hasFixedPasien);
        $('#rj_pasien_label').text(marketplaceMode ? 'Pasien Lama' : 'Pasien');
        $('#rj_pasien_id').closest('.form-group').toggle(shouldShowPatientSelector);
        $('#rj_pasien_id').prop('required', hasFixedPasien ? false : shouldShowPatientSelector);
        $('#rj_pasien_id').prop('disabled', hasFixedPasien);
        $('#rj_force_create_duplicate').val('0');

        // Visible inputs use the browser's required check; jenis kelamin + marketplace are hidden selects behind
        // toggle buttons (a hidden required field would silently block the submit), so the submit checks them.
        $('#rj_marketplace_nama, #rj_marketplace_alamat, #rj_marketplace_no_hp')
            .prop('required', shouldShowMarketplacePatientSection);
        // Shares the referral_detail name with the visit referral fields; only submit it in marketplace mode.
        $('#rj_marketplace_referral_detail').prop('disabled', !marketplaceMode);
        $('#rj_marketplace_referral_label').text(shouldShowMarketplacePatientSection ? 'Marketplace' : 'Marketplace (opsional)');
        $('#rj_marketplace_referral_hint').toggle(!shouldShowMarketplacePatientSection);
        // Marketplace visits rarely have a dokter (only when one recommended the purchase)
        $('label[for="rj_dokter_id"]').html(marketplaceMode ? 'Dokter <span class="rj-label-optional">(opsional, jika direkomendasikan dokter)</span>' : 'Dokter');
        rjSyncSelectToggles();
    }

    /** Toggle button groups (.rj-select-toggle[data-select]) mirror a hidden select: one click instead of two. */
    function rjSyncSelectToggles() {
        $('.rj-select-toggle').each(function() {
            const value = String($($(this).data('select')).val() || '');
            $(this).find('[data-value]').each(function() {
                const on = String($(this).data('value')) === value;
                $(this).toggleClass('active', on).attr('aria-checked', on ? 'true' : 'false');
            });
        });
    }

    $(document).on('click', '.rj-select-toggle [data-value]', function() {
        const $select = $($(this).closest('.rj-select-toggle').data('select'));
        $select.val(String($(this).data('value'))).trigger('change');
        rjSyncSelectToggles();
    });

    /** Marketplace pasien baru: the toggle fields the browser cannot check (null = ok). */
    function validateRjMarketplaceNewPatient() {
        if (!isMarketplaceNewPatient() || (rjFixedPasienContext && rjFixedPasienContext.id)) return null;
        if (!$('#rj_marketplace_gender').val()) return 'Jenis kelamin pasien baru wajib dipilih.';
        if (!$('#rj_marketplace_referral_detail').val()) return 'Marketplace pasien baru wajib dipilih.';
        return null;
    }

    function setFixedPasienContext(pasienId, pasienNama) {
        if (!pasienId) {
            rjFixedPasienContext = null;
            $('#rj_pasien_id').prop('disabled', false).val(null).trigger('change');
            return;
        }

        rjFixedPasienContext = {
            id: pasienId.toString(),
            nama: (pasienNama || '').toString()
        };

        const selectedText = rjFixedPasienContext.nama
            ? rjFixedPasienContext.nama + ' (RM: ' + rjFixedPasienContext.id + ')'
            : 'RM: ' + rjFixedPasienContext.id;

        if ($('#rj_pasien_id').find("option[value='" + rjFixedPasienContext.id + "']").length === 0) {
            const option = new Option(selectedText, rjFixedPasienContext.id, true, true);
            $('#rj_pasien_id').append(option);
        }

        $('#rj_pasien_id').val(rjFixedPasienContext.id).trigger('change');
        $('#rj_pasien_id').prop('disabled', true);
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

    function escapeRjCalendarHtml(value) {
        if (value === null || typeof value === 'undefined') {
            return '';
        }

        return $('<div>').text(value).html();
    }

    function renderRjCalendarBreakdownItem(label, value) {
        var parsedValue = parseInt(value || 0, 10);

        if (parsedValue <= 0) {
            return '';
        }

        return '<small>' + escapeRjCalendarHtml(label) + ': ' + escapeRjCalendarHtml(parsedValue) + '</small>';
    }

    function renderRjCalendarPreview(response, selectedDate) {
        var weekdays = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
        var month = response && response.month ? response.month : {};
        var summary = response && response.summary ? response.summary : {};
        var days = Array.isArray(response && response.days) ? response.days : [];
        var startsOn = parseInt(month.starts_on || 1, 10);
        var html = '<div class="rj-visit-modal-calendar-grid">';

        weekdays.forEach(function(label) {
            html += '<div class="rj-visit-modal-calendar-weekday">' + escapeRjCalendarHtml(label) + '</div>';
        });

        for (var blank = 1; blank < startsOn; blank++) {
            html += '<div class="rj-visit-modal-calendar-day is-empty" aria-hidden="true"></div>';
        }

        days.forEach(function(day) {
            var count = parseInt(day.count || 0, 10);
            var breakdown = day && day.breakdown ? day.breakdown : {};
            var classes = ['rj-visit-modal-calendar-day'];
            var breakdownItems = [
                renderRjCalendarBreakdownItem('Konsul', breakdown.konsultasi),
                renderRjCalendarBreakdownItem('Produk', breakdown.produk),
                renderRjCalendarBreakdownItem('Lab', breakdown.lab),
                renderRjCalendarBreakdownItem('Event', breakdown.event),
                renderRjCalendarBreakdownItem('Marketplace', breakdown.marketplace),
                renderRjCalendarBreakdownItem('Reseller', breakdown.reseller),
                renderRjCalendarBreakdownItem('Slimming', breakdown.slimming),
                renderRjCalendarBreakdownItem('Vaksin', breakdown.vaksin),
                renderRjCalendarBreakdownItem('MCU', breakdown.mcu)
            ].join('');

            if (day.is_past) {
                classes.push('is-past');
            }

            if (day.is_today) {
                classes.push('is-today');
            }

            if (count > 0) {
                classes.push('is-busy');
            }

            if (selectedDate && day.date === selectedDate) {
                classes.push('is-selected');
            }

            html += '<div class="' + classes.join(' ') + '">'
                + '<div class="rj-visit-modal-calendar-day-head">'
                + '<div>'
                + '<div class="rj-visit-modal-calendar-day-number">' + escapeRjCalendarHtml(day.day) + '</div>'
                + '<div class="rj-visit-modal-calendar-day-name">' + escapeRjCalendarHtml(day.weekday || '') + '</div>'
                + '</div>'
                + '<div class="rj-visit-modal-calendar-count">' + escapeRjCalendarHtml(count) + '</div>'
                + '</div>'
                + (breakdownItems ? '<div class="rj-visit-modal-calendar-breakdown">' + breakdownItems + '</div>' : '')
                + '</div>';
        });

        html += '</div>';

        $('#rj-calendar-preview-month-label').text(month.label || 'Kalender Antrian');
        $('#rj-calendar-preview-summary').text(
            'Total visit: ' + (summary.total_visits || 0)
            + ' | Hari terisi: ' + (summary.active_days || 0)
            + ' | Puncak harian: ' + (summary.max_visits || 0)
        );
        $('#rj-calendar-preview-content').html(html);
    }

    function setRjCalendarPreviewLoading(message) {
        $('#rj-calendar-preview-month-label').text('Kalender Antrian');
        $('#rj-calendar-preview-summary').text(message || 'Memuat data antrian...');
        $('#rj-calendar-preview-content').html('<div class="rj-visit-modal-calendar-placeholder"><span class="spinner-border spinner-border-sm mr-2"></span>Memuat kalender antrian...</div>');
    }

    function loadRjCalendarPreview() {
        if (!rjCalendarPreviewVisible) {
            return;
        }

        var selectedDate = $('#rj_tanggal_visitation').val() || '';
        var selectedMonth = selectedDate && window.moment
            ? moment(selectedDate).format('YYYY-MM')
            : (window.moment ? moment().format('YYYY-MM') : '');

        setRjCalendarPreviewLoading('Memuat data antrian...');

        $.get(window.ErmRawatJalanConfig.routes["erm.rawatjalans.queueCalendar"], {
            month: selectedMonth,
            dokter_id: rjDokterId(),
            klinik_id: $('#rj_klinik_id').val() || ''
        }).done(function(response) {
            renderRjCalendarPreview(response || {}, selectedDate);
        }).fail(function(xhr) {
            $('#rj-calendar-preview-summary').text('Gagal memuat data antrian.');
            $('#rj-calendar-preview-content').html('<div class="rj-visit-modal-calendar-placeholder text-danger">Tidak dapat memuat kalender antrian.</div>');
            console.error('Failed to load modal queue calendar', xhr);
        });
    }

    function toggleRjCalendarPreview(forceVisible) {
        rjCalendarPreviewVisible = typeof forceVisible === 'boolean' ? forceVisible : !rjCalendarPreviewVisible;
        $('#modalDaftarKunjunganRawatJalan').toggleClass('is-calendar-expanded', rjCalendarPreviewVisible);
        $('#btn-rj-cek-kalender')
            .toggleClass('active', rjCalendarPreviewVisible)
            .text(rjCalendarPreviewVisible ? 'Sembunyikan Kalender' : 'Cek Kalender');

        if (rjCalendarPreviewVisible) {
            loadRjCalendarPreview();
            return;
        }

        $('#rj-calendar-preview-month-label').text('Kalender Antrian');
        $('#rj-calendar-preview-summary').text('Pilih filter lalu cek kalender.');
        $('#rj-calendar-preview-content').html('<div class="rj-visit-modal-calendar-placeholder">Klik Cek Kalender untuk melihat kepadatan antrian pada bulan yang dipilih.</div>');
    }

    function resolveSubmitUrl() {
        const mode = $('#rj_mode').val();
        if (mode === 'produk' || mode === 'reseller') {
            return window.ErmRawatJalanConfig.routes["erm.visitations.produk.store"];
        }
        if (mode === 'lab') {
            return window.ErmRawatJalanConfig.routes["erm.visitations.lab.store"];
        }
        if (mode === 'marketplace') {
            return window.ErmRawatJalanConfig.routes["erm.visitations.marketplace.store"];
        }

        return window.ErmRawatJalanConfig.routes["erm.visitations.store"];
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
        const formData = $('#form-daftar-kunjungan-rawatjalan').serializeArray();

        if (rjFixedPasienContext && rjFixedPasienContext.id && !formData.some(function(field) {
            return field.name === 'pasien_id';
        })) {
            formData.push({
                name: 'pasien_id',
                value: rjFixedPasienContext.id
            });
        }

        // Guard against double submit (double click / Enter while the request is in flight).
        if (rjSubmitting) {
            return;
        }
        rjSubmitting = true;
        const $submitBtn = $('#form-daftar-kunjungan-rawatjalan button[type="submit"]').prop('disabled', true);

        $.ajax({
            url: resolveSubmitUrl(),
            type: 'POST',
            data: $.param(formData)
        }).always(function(){
            rjSubmitting = false;
            $submitBtn.prop('disabled', false);
        }).done(function(res){
            // Lets the host page (rawat jalan, pasien, billing) refresh its own tables.
            $(document).trigger('rj:visitation-registered', [res]);

            $('#modalDaftarKunjunganRawatJalan').modal('hide');
            $('#form-daftar-kunjungan-rawatjalan')[0].reset();
            $('#rj_pasien_id').val(null).trigger('change');
            $('#rj_dokter_id').empty().append('<option value="">Tanpa Dokter</option>').prop('disabled', true).trigger('change.select2');
            rjRenderSpesialisasi();
            $('#rj_no_antrian').val('');
            resetRawatJalanMarketplaceFields();

            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: (res && res.message) ? res.message : 'Kunjungan berhasil disimpan.',
                confirmButtonText: 'OK'
            }).then(function(){
                try {
                    $('#rawatjalan-table').DataTable().ajax.reload(null, false);
                } catch(e) {}
                try {
                    if (typeof updateStats === 'function') updateStats();
                } catch(e) {}
                try {
                    if (window.pasiensTable && typeof window.pasiensTable.ajax === 'object') window.pasiensTable.ajax.reload(null, false);
                } catch(e) {}
                // After the success popup closes (e.g. pasien create page reloads itself).
                $(document).trigger('rj:visitation-success-closed', [res]);
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
        $.get(window.ErmRawatJalanConfig.routes["erm.pasiens.marketplace.check-duplicate"], marketplaceDuplicatePayload())
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

    const RJ_SERVICE_MODES = {
        slimming: { jenis: '7', label: 'Slimming' },
        vaksin: { jenis: '8', label: 'Vaksin' },
        mcu: { jenis: '9', label: 'MCU' }
    };

    /**
     * Switch the layanan (jenis kunjungan). keepInputs = switched from a layanan chip / klinik fallback while the
     * form is being filled: keep the chosen cara bayar and referral (only reload the referral when entering or
     * leaving Marketplace, which has its own referral). Otherwise (opening / closing the modal) reset them.
     */
    function applyMode(mode, keepInputs){
        mode = (mode || 'konsultasi').toString();
        const wasMarketplace = isMarketplaceMode();
        $('#rj_mode').val(mode);

        if (mode === 'produk') {
            $('#modalDaftarKunjunganRawatJalanLabel').text('Daftarkan Kunjungan Beli Produk Pasien');
            $('#rj_jenis_kunjungan').val('2');
            $('#rj_waktu_group').hide();
            $('#rj_no_antrian_group').hide();
            $('#rj_waktu_kunjungan').val('');
            $('#rj_no_antrian').val('');
        } else if (mode === 'reseller') {
            // Same flow as Beli Produk
            $('#modalDaftarKunjunganRawatJalanLabel').text('Daftarkan Kunjungan Reseller');
            $('#rj_jenis_kunjungan').val('6');
            $('#rj_waktu_group').hide();
            $('#rj_no_antrian_group').hide();
            $('#rj_waktu_kunjungan').val('');
            $('#rj_no_antrian').val('');
        } else if (RJ_SERVICE_MODES[mode]) {
            // Slimming / Vaksin / MCU: scheduled like a konsultasi, without a dokter queue number
            $('#modalDaftarKunjunganRawatJalanLabel').text('Daftarkan Kunjungan ' + RJ_SERVICE_MODES[mode].label + ' Pasien');
            $('#rj_jenis_kunjungan').val(RJ_SERVICE_MODES[mode].jenis);
            $('#rj_waktu_group').show();
            $('#rj_no_antrian_group').hide();
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
        } else if (mode === 'event') {
            $('#modalDaftarKunjunganRawatJalanLabel').text('Daftarkan Kunjungan Event Pasien');
            $('#rj_jenis_kunjungan').val('4');
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
        if (!keepInputs || wasMarketplace !== isMarketplaceMode()) {
            loadRjReferralContext();
        }
        if (!keepInputs) {
            setRjMetodeBayarGroup('umum');
        }
        rjSyncChips();
        // Back to Konsultasi with a dokter already chosen: the queue number must be filled again
        if (mode === 'konsultasi') {
            cekAntrianRJ();
        }
    }

    // ---- Klinik + Layanan chips (top of the modal) ----
    // Layanan chips follow the mapping in Admin > Klinik Setting > tab Layanan; the server rejects the rest too.
    function rjLayananOff(klinikId, jenis) {
        const nonaktif = (window.ErmRawatJalanConfig && window.ErmRawatJalanConfig.layananNonaktif) || {};
        return !!klinikId && (nonaktif[klinikId] || []).map(Number).indexOf(Number(jenis)) !== -1;
    }

    function rjSyncChips() {
        const klinikId = $('#rj_klinik_id').val() || '';
        const mode = $('#rj_mode').val() || 'konsultasi';

        $('#rj_klinik_chips .rj-klinik-chip').each(function() {
            const on = String($(this).data('klinik')) === String(klinikId);
            $(this).toggleClass('active', on).attr('aria-checked', on ? 'true' : 'false');
        });
        $('#rj_layanan_group').toggle(!!klinikId); // layanan depend on the klinik: shown once one is chosen
        $('#rj_layanan_chips .rj-layanan-chip').each(function() {
            const $chip = $(this);
            const on = $chip.data('mode') === mode;
            $chip.toggleClass('d-none', rjLayananOff(klinikId, $chip.data('jenis')))
                .toggleClass('active', on).attr('aria-checked', on ? 'true' : 'false');
        });
    }

    /** force = reload the dokter list even when the klinik is unchanged (a pre-filled dokter must be applied). */
    function rjSelectKlinik(klinikId, force) {
        if (force || String($('#rj_klinik_id').val() || '') !== String(klinikId)) {
            $('#rj_klinik_id').val(String(klinikId)).trigger('change');
        }
        // The chosen layanan is not offered here: fall back to Konsultasi (or the first one offered) and say so
        const $current = $('#rj_layanan_chips .rj-layanan-chip[data-mode="' + ($('#rj_mode').val() || 'konsultasi') + '"]');
        const $hint = $('#rj_layanan_hint').addClass('d-none').text('');
        if ($current.length && rjLayananOff(klinikId, $current.data('jenis'))) {
            const $fallback = $('#rj_layanan_chips .rj-layanan-chip').filter(function() {
                return !rjLayananOff(klinikId, $(this).data('jenis'));
            });
            const $pick = $fallback.filter('[data-mode="konsultasi"]').first().length ? $fallback.filter('[data-mode="konsultasi"]').first() : $fallback.first();
            $hint.removeClass('d-none').text('Layanan ' + $.trim($current.text()) + ' tidak tersedia di klinik ini'
                + ($pick.length ? ', diganti ke ' + $.trim($pick.text()) + '.' : '.'));
            if ($pick.length) {
                applyMode($pick.data('mode'), true);
                return;
            }
        }
        rjSyncChips();
    }

    $(document).on('click', '#rj_klinik_chips .rj-klinik-chip', function() {
        rjSelectKlinik($(this).data('klinik'));
    });

    $(document).on('click', '#rj_layanan_chips .rj-layanan-chip', function() {
        const mode = $(this).data('mode');
        $('#rj_layanan_hint').addClass('d-none').text('');
        if (mode !== $('#rj_mode').val()) applyMode(mode, true);
    });

    /** Klinik to start with: the Rawat Jalan / Billing klinik filter when it is set to one klinik. */
    function rjDefaultKlinikId() {
        const filter = $('#filter_klinik').val();
        return filter && $('#rj_klinik_chips .rj-klinik-chip[data-klinik="' + filter + '"]').length ? String(filter) : '';
    }

    function todayYmd() {
        const d = new Date();
        const pad = function(n) { return n < 10 ? '0' + n : String(n); };
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
    }

    /**
     * Single entry point for every "Daftarkan Kunjungan" flow.
     * opts: { jenis, pasienId, pasienNama, klinikId, dokterId, metodeBayarId, followUpId, followUpLabel } (all optional)
     */
    function openRjDaftarKunjungan(opts) {
        opts = opts || {};
        // Set before the patient is applied: its referral context load picks it up
        rjPendingFollowUp = opts.followUpId ? { id: String(opts.followUpId), label: opts.followUpLabel || '' } : null;
        applyMode(opts.jenis || 'konsultasi');
        setFixedPasienContext(opts.pasienId || '', opts.pasienNama || '');
        $('#rj_tanggal_visitation').val(todayYmd());

        // Klinik: given by the caller (e.g. ERM sidebar "Reservasi Kunjungan"), else the page's klinik filter,
        // else none: staff often serve two kliniks, so it is picked with a chip instead of guessed.
        $('#rj_layanan_hint').addClass('d-none').text('');
        const klinikId = (opts.klinikId && $('#rj_klinik_id option[value="' + opts.klinikId + '"]').length)
            ? String(opts.klinikId) : rjDefaultKlinikId();
        if (klinikId) {
            rjPendingDokterId = opts.dokterId ? String(opts.dokterId) : null;
            rjSelectKlinik(klinikId, true);
        } else {
            $('#rj_klinik_id').val('').trigger('change');
            rjSyncChips();
        }

        const metode = RJ_METODE_BAYAR.find(function(m) { return String(m.id) === String(opts.metodeBayarId || ''); });
        if (metode) {
            setRjMetodeBayarGroup(metode.is_asuransi ? 'asuransi' : 'umum', metode.id);
        }

        $('#modalDaftarKunjunganRawatJalan').modal('show');
    }

    window.openDaftarKunjunganModal = openRjDaftarKunjungan;

    // open modal (from dropdown / any trigger with this class)
    $(document).on('click', '.btn-daftarkan-pasien-rawatjalan', function(e){
        e.preventDefault();
        const $btn = $(this);
        openRjDaftarKunjungan({
            jenis: $btn.data('jenis') || 'konsultasi',
            pasienId: $btn.data('id') || '',
            pasienNama: $btn.data('nama') || '',
            klinikId: $btn.data('klinik') || '',
            dokterId: $btn.data('dokter') || '',
            metodeBayarId: $btn.data('metodebayar') || ''
        });
    });

    let rjAntrianSeq = 0;
    function cekAntrianRJ(){
        const seq = ++rjAntrianSeq; // a slower earlier answer (other dokter / date) must not win
        let dokterId = rjDokterId();
        let tanggal = $('#rj_tanggal_visitation').val();
        if (!dokterId || !tanggal) {
            $('#rj_no_antrian').val('');
            return;
        }
        if ($('#rj_mode').val() !== 'konsultasi') return;

        $.get(window.ErmRawatJalanConfig.routes["erm.visitations.cekAntrian"], { dokter_id: dokterId, tanggal: tanggal }, function(res){
            if (seq === rjAntrianSeq) $('#rj_no_antrian').val(res.no_antrian || '');
        }).fail(function(){
            if (seq === rjAntrianSeq) $('#rj_no_antrian').val('');
        });
    }

    // The Dokter select holds one option per dokter × spesialisasi ("dokterId:spesialisasiId");
    // the chosen pair goes to the hidden dokter_id / spesialisasi_id fields.
    function rjDokterId() {
        return String($('#rj_dokter_id option:selected').data('dokter') || '');
    }

    function rjRenderSpesialisasi() {
        const $opt = $('#rj_dokter_id option:selected');
        $('#rj_dokter_value').val($opt.data('dokter') || '');
        $('#rj_spesialisasi_id').val($opt.data('spesialisasi') || '');
    }

    // Group order: the everyday ones first, then the rest A-Z, dokter without spesialisasi last
    const RJ_SPESIALISASI_FIRST = ['umum', 'estetika'];
    function rjGroupRank(nama) {
        const i = RJ_SPESIALISASI_FIRST.indexOf(String(nama || '').toLowerCase());
        return i === -1 ? (nama ? RJ_SPESIALISASI_FIRST.length : 99) : i;
    }

    /** Options grouped by spesialisasi: [{label, items: [{value, dokter, spesialisasi, text}]}] */
    function rjDokterGroups(dokters) {
        const groups = {};
        (dokters || []).forEach(function(dokter) {
            const nama = (dokter.user && dokter.user.name) ? dokter.user.name : 'Unknown Doctor';
            let spesialisasis = Array.isArray(dokter.spesialisasis) ? dokter.spesialisasis : [];
            if (!spesialisasis.length && dokter.spesialisasi) spesialisasis = [dokter.spesialisasi];
            if (!spesialisasis.length) spesialisasis = [{ id: '', nama: '' }];
            spesialisasis.forEach(function(s) {
                const label = s.nama || 'Tanpa Spesialisasi';
                (groups[label] = groups[label] || { label: label, rank: rjGroupRank(s.nama), items: [] }).items.push({
                    value: dokter.id + ':' + (s.id || ''),
                    dokter: dokter.id,
                    spesialisasi: s.id || '',
                    text: nama + (s.nama ? ' — ' + s.nama : '')
                });
            });
        });

        return Object.keys(groups).map(function(k) { return groups[k]; }).sort(function(a, b) {
            return a.rank - b.rank || a.label.localeCompare(b.label);
        });
    }

    // klinik => load doctors (only the answer for the latest klinik click is used)
    let rjDokterLoadSeq = 0;
    $('#rj_klinik_id').on('change', function(){
        let klinikId = $(this).val();
        let dokterSelect = $('#rj_dokter_id');
        const seq = ++rjDokterLoadSeq;

        dokterSelect.empty().append('<option value="">Loading...</option>').prop('disabled', true).trigger('change.select2');
        rjRenderSpesialisasi();
        $('#rj_no_antrian').val('');
        if (!klinikId) {
            dokterSelect.empty().append('<option value="">Tanpa Dokter</option>').prop('disabled', true).trigger('change.select2');
            return;
        }

        $.ajax({
            url: `/get-dokters/${klinikId}`,
            type: 'GET'
        }).done(function(data){
            if (seq !== rjDokterLoadSeq) return;
            dokterSelect.empty().append('<option value="">Tanpa Dokter</option>');
            rjDokterGroups(data).forEach(function(group) {
                const $group = $('<optgroup>').attr('label', group.label);
                group.items.forEach(function(item) {
                    $group.append($('<option>').val(item.value).text(item.text)
                        .attr('data-dokter', item.dokter).attr('data-spesialisasi', item.spesialisasi));
                });
                dokterSelect.append($group);
            });
            dokterSelect.prop('disabled', false).trigger('change.select2');
            rjRenderSpesialisasi();

            // Pre-filled dokter (openRjDaftarKunjungan): the first of its options once the klinik's list is in
            const $pending = rjPendingDokterId
                ? dokterSelect.find('option').filter(function() { return String($(this).data('dokter')) === rjPendingDokterId; }).first()
                : $();
            if ($pending.length) {
                dokterSelect.val($pending.val()).trigger('change');
            }
            rjPendingDokterId = null;
        }).fail(function(xhr, status){
            if (seq !== rjDokterLoadSeq || status === 'abort') return;
            rjPendingDokterId = null;
            dokterSelect.empty().append('<option value="">Tanpa Dokter</option>').prop('disabled', false).trigger('change.select2');
            Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal mengambil data dokter' });
        });
    });

    // dokter/date => cek antrian
    $('#rj_dokter_id').on('change', rjRenderSpesialisasi);

    $('#rj_dokter_id, #rj_tanggal_visitation').on('change', function(){
        cekAntrianRJ();
        loadRjCalendarPreview();
    });

    $('#rj_klinik_id').on('change', function(){
        loadRjCalendarPreview();
    });

    $('#btn-rj-cek-kalender').on('click', function() {
        toggleRjCalendarPreview();
    });

    $(document).on('click', '.rj-mp-mode-btn', function() {
        const value = $(this).data('value');
        if (value !== marketplacePatientMode()) {
            $('#rj_marketplace_patient_mode').val(value).trigger('change');
        }
    });

    $('#rj_marketplace_patient_mode').on('change', function() {
        $('#rj_force_create_duplicate').val('0');
        if (rjFixedPasienContext && rjFixedPasienContext.id) {
            $(this).val('existing');
            syncMarketplaceState();
            return;
        }
        if (marketplacePatientMode() === 'new') {
            $('#rj_pasien_id').val(null).trigger('change');
        }
        syncMarketplaceState();
    });

    $('#rj_pasien_id').on('change', function(){
        syncMarketplaceState();
        loadRjReferralContext();
    });

    // submit
    $('#form-daftar-kunjungan-rawatjalan').on('submit', function(e){
        e.preventDefault();

        if (!$('#rj_klinik_id').val()) {
            Swal.fire({ icon: 'warning', title: 'Klinik', text: 'Pilih klinik tujuan kunjungan terlebih dahulu.', confirmButtonText: 'OK' });
            return;
        }

        if (!$('#rj_metode_bayar_id').val()) {
            const metodeMessage = rjMetodeBayarGroup() === 'asuransi' ? 'Penjamin asuransi wajib dipilih.' : 'Metode bayar wajib dipilih.';
            Swal.fire({ icon: 'warning', title: 'Cara Bayar', text: metodeMessage, confirmButtonText: 'OK' });
            return;
        }

        const referralError = validateRjReferral();
        if (referralError) {
            Swal.fire({ icon: 'warning', title: 'Referral Kunjungan', text: referralError, confirmButtonText: 'OK' });
            return;
        }

        if (!isMarketplaceMode() && currentRjPasienId() && !rjReferralContext) {
            Swal.fire({ icon: 'info', title: 'Mohon tunggu', text: 'Referral pasien masih dimuat.', confirmButtonText: 'OK' });
            return;
        }

        const marketplaceError = validateRjMarketplaceNewPatient();
        if (marketplaceError) {
            Swal.fire({ icon: 'warning', title: 'Pasien Baru Marketplace', text: marketplaceError, confirmButtonText: 'OK' });
            return;
        }

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
        try { $('#rj_dokter_id').empty().append('<option value="">Tanpa Dokter</option>').prop('disabled', true).trigger('change.select2'); rjRenderSpesialisasi(); } catch(e) {}
        $('#rj_no_antrian').val('');
        rjFixedPasienContext = null;
        rjPendingFollowUp = null;
        $('#rj_pasien_id').prop('disabled', false);
        resetRawatJalanMarketplaceFields();
        toggleRjCalendarPreview(false);
        applyMode('konsultasi');
    });

    // Default mode
    applyMode('konsultasi');
});
