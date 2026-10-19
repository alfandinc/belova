$(document).ready(function () {
    const cfg = window.ElabConfig;
    const visitationId = cfg.visitationId;
    const pasienId = cfg.pasienId;
    // lab_test_id => status of what is already saved for this visit
    const existingLabTestStatuses = cfg.existingLabTestStatuses;
    // lab_test_id => lab_paket_id of what is already saved (null = standalone)
    const existingLabTestPaket = cfg.existingLabTestPaket;
    const previousLab = cfg.previousLab;
    const hasilLisNames = new Set(cfg.hasilLisNames);
    // Diproses / Selesai is set by the lab; the others only see it
    const canEditStatus = cfg.canEditStatus;
    const labPaketMap = {};
    (cfg.labPakets || []).forEach(function (paket) {
        labPaketMap[String(paket.id)] = paket;
    });

    const STATUS = {
        requested: 'Diminta',
        processing: 'Diproses',
        completed: 'Selesai'
    };

    let isDirty = false;
    let saving = false;

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function formatNumber(value) {
        return (Number(value) || 0).toLocaleString('id-ID');
    }

    function statusBadge(status) {
        const key = STATUS[status] ? status : 'requested';
        return `<span class="elab-status is-${key}${canEditStatus ? '' : ' is-readonly'}">${STATUS[key]}</span>`;
    }

    // Saved as Diproses / Selesai: the lab already works on it, so it can not be removed here
    function isLocked(testId) {
        const status = existingLabTestStatuses[String(testId)];
        return status === 'processing' || status === 'completed';
    }

    function rowIcons(item, status) {
        let icons = '';
        if (hasilLisNames.has(String(item.name || '').trim().toLowerCase())) {
            icons += '<i class="fas fa-check-circle elab-hasil-icon" title="Hasil sudah keluar"></i>';
        }
        if (isLocked(item.testId)) {
            icons += '<i class="fas fa-lock elab-lock-icon" title="Sudah diproses lab, tidak bisa dihapus"></i>';
        }
        return icons;
    }

    // Lock the picker checkbox (the test itself, or its paket) of what the lab already works on
    function applyLocks() {
        $('.lab-test-checkbox, .lab-paket-checkbox').prop('disabled', false).closest('label').removeClass('is-locked').removeAttr('title');
        Object.keys(existingLabTestStatuses).forEach(function (testId) {
            if (!isLocked(testId)) return;
            const paketId = existingLabTestPaket[testId];
            const box = paketId ? $(`.lab-paket-checkbox[data-id="${paketId}"]`) : $(`.lab-test-checkbox[data-id="${testId}"]`);
            box.prop('disabled', true).closest('label').addClass('is-locked').attr('title', 'Sudah diproses lab, tidak bisa dihapus');
        });
    }

    // Today in the browser's own timezone (toISOString is UTC: before 07.00 WIB it gives yesterday)
    function todayLocal() {
        const d = new Date();
        const pad = function (n) { return String(n).padStart(2, '0'); };
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
    }

    // Unsaved changes: highlight the save button and warn before leaving the page
    function setDirty(dirty) {
        isDirty = dirty;
        $('#submitLabRequests')
            .toggleClass('is-dirty', dirty)
            .html(dirty ? '<i class="fas fa-save mr-1"></i>Simpan Perubahan' : '<i class="fas fa-save mr-1"></i>Simpan Permintaan Lab');
        $('#labSaveHint').html(dirty
            ? '<i class="fas fa-exclamation-circle mr-1 text-warning"></i>Ada perubahan yang belum disimpan.'
            : '<i class="fas fa-info-circle mr-1"></i>Pemeriksaan yang dicentang baru tersimpan setelah klik Simpan.');
    }

    window.addEventListener('beforeunload', function (e) {
        if (!isDirty) return;
        e.preventDefault();
        e.returnValue = '';
    });

    // ---- Selection: paket groups + standalone tests ----
    function getSelectedPackageGroups() {
        const groups = [];
        const coveredTestIds = new Set();

        $('.lab-paket-checkbox:checked').each(function () {
            const paket = labPaketMap[String($(this).data('id'))];
            if (!paket) return;

            const tests = [];
            (paket.lab_tests || []).forEach(function (test) {
                const testKey = String(test.id);
                // A test in two chosen paket is requested once, under the first one
                if (coveredTestIds.has(testKey)) return;
                // A test the lab already works on stays where it was saved (its billing must not move)
                if (isLocked(testKey) && String(existingLabTestPaket[testKey] || '') !== String(paket.id)) return;
                coveredTestIds.add(testKey);
                tests.push({
                    testId: Number(test.id),
                    name: test.nama,
                    price: Number(test.harga || 0),
                    labPaketId: Number(paket.id)
                });
            });

            groups.push({ id: Number(paket.id), name: paket.nama, price: Number(paket.harga_paket || 0), tests: tests });
        });

        return { groups: groups, coveredTestIds: coveredTestIds };
    }

    function buildSelectedRequests() {
        const packageData = getSelectedPackageGroups();
        const standaloneItems = [];
        $('.lab-test-checkbox:checked').each(function () {
            const testId = String($(this).data('id'));
            if (packageData.coveredTestIds.has(testId)) return;
            standaloneItems.push({
                testId: Number(testId),
                name: $(this).data('name'),
                price: Number($(this).data('price') || 0),
                labPaketId: null
            });
        });
        return { groups: packageData.groups, standaloneItems: standaloneItems, coveredTestIds: packageData.coveredTestIds };
    }

    // ---- Permintaan table + estimasi ----
    function renderSelected() {
        const tbody = $('#checkedLabTable tbody');
        const selected = buildSelectedRequests();

        // Keep the row checks and the (unsaved) statuses of rows that stay in the list
        const rowCheckStates = {};
        $('.row-checkbox').each(function () {
            rowCheckStates[String($(this).data('test-id'))] = $(this).prop('checked');
        });
        const statusStates = {};
        $('#checkedLabTable tbody tr[data-test-id]').each(function () {
            statusStates[String($(this).data('test-id'))] = $(this).find('.status-select').val();
        });

        // Tests already covered by a chosen paket are marked in the picker
        $('.elab-test').each(function () {
            $(this).toggleClass('in-paket', selected.coveredTestIds.has(String($(this).data('test-id'))));
        });

        const rows = [];
        let html = '';
        let total = 0;
        selected.groups.forEach(function (group) {
            // A paket whose tests are all in another chosen paket adds nothing (and is not saved)
            if (!group.tests.length) return;
            total += group.price;
            html += `<tr class="lab-package-summary"><td colspan="3">Paket: ${escapeHtml(group.name)}</td><td class="text-right">Rp ${formatNumber(group.price)}</td></tr>`;
            group.tests.forEach(function (item) { rows.push($.extend({ inPaket: true }, item)); });
        });
        selected.standaloneItems.forEach(function (item) {
            total += item.price;
            rows.push($.extend({ inPaket: false }, item));
        });

        rows.forEach(function (item, idx) {
            const key = String(item.testId);
            const status = statusStates[key] || existingLabTestStatuses[key] || 'requested';
            const checked = rowCheckStates[key] ? 'checked' : '';
            // Row checks only serve the bulk status change of the lab
            const noCell = canEditStatus
                ? `<div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input row-checkbox" id="rowCheck-${item.testId}" data-test-id="${item.testId}" ${checked}>
                        <label class="custom-control-label" for="rowCheck-${item.testId}">${idx + 1}</label>
                    </div>`
                : `<span class="text-muted font-weight-bold">${idx + 1}</span>`;
            html += `<tr data-test-id="${item.testId}" data-package-id="${item.labPaketId || ''}">
                    <td>${noCell}</td>
                    <td class="${item.inPaket ? 'lab-package-child-name' : ''}">${item.inPaket ? '&rdsh; ' : ''}${escapeHtml(item.name)}${rowIcons(item, status)}</td>
                    <td class="text-right">${item.inPaket ? '<span class="elab-in-paket">Paket</span>' : 'Rp ' + formatNumber(item.price)}</td>
                    <td class="text-center">
                        <div class="status-container" data-test-id="${item.testId}">
                            <div class="status-display">${statusBadge(status)}</div>
                            <select class="form-control form-control-sm status-select d-none" data-test-id="${item.testId}">
                                ${Object.keys(STATUS).map(function (s) { return `<option value="${s}" ${s === status ? 'selected' : ''}>${STATUS[s]}</option>`; }).join('')}
                            </select>
                        </div>
                    </td>
                </tr>`;
        });

        if (!rows.length) {
            html = '<tr><td colspan="4" class="text-center text-muted py-3">Belum ada pemeriksaan dipilih</td></tr>';
        }
        tbody.html(html);
        $('#estimasiHargaTotal, #estimasiHargaBar').text(formatNumber(total));
        updateBulkControls();
    }

    function updateBulkControls() {
        const all = $('.row-checkbox');
        const checked = $('.row-checkbox:checked');
        $('#selectAllLab').prop('checked', all.length > 0 && checked.length === all.length);
        $('.bulk-status-container').css('display', checked.length ? 'flex' : 'none');
    }

    applyLocks();
    renderSelected();

    $(document).on('change', '.lab-test-checkbox, .lab-paket-checkbox', function () {
        renderSelected();
        setDirty(true);
    });

    // ---- Ulangi permintaan dari kunjungan lab terakhir (adds to what is already checked) ----
    $('#repeatPreviousLab').on('click', function () {
        if (!previousLab) return;
        const paketBoxes = (previousLab.paket_ids || []).map(function (id) { return $(`.lab-paket-checkbox[data-id="${id}"]`); });
        const testBoxes = (previousLab.test_ids || []).map(function (id) { return $(`.lab-test-checkbox[data-id="${id}"]`); });
        const found = paketBoxes.concat(testBoxes).filter(function (box) { return box.length; });
        const missing = paketBoxes.length + testBoxes.length - found.length;
        const names = found.map(function (box) {
            return box.hasClass('lab-paket-checkbox')
                ? 'Paket ' + box.closest('.elab-paket').find('.elab-paket-name').text().trim()
                : box.data('name');
        });

        Swal.fire({
            title: 'Ulangi lab ' + previousLab.tanggal + '?',
            html: (names.length ? '<div class="text-left small">' + names.map(escapeHtml).join('<br>') + '</div>' : 'Tidak ada pemeriksaan yang masih tersedia.')
                + (missing ? `<div class="small text-muted mt-2">${missing} pemeriksaan/paket sudah tidak ada di master.</div>` : '')
                + '<div class="small text-muted mt-2">Pilihan yang sudah dicentang tetap dipertahankan.</div>',
            icon: 'question',
            showCancelButton: names.length > 0,
            showConfirmButton: names.length > 0,
            confirmButtonText: 'Ya, centang',
            cancelButtonText: 'Batal'
        }).then(function (result) {
            if (!(result.isConfirmed || result.value)) return;
            found.forEach(function (box) { box.prop('checked', true); });
            renderSelected();
            setDirty(true);
        });
    });

    // ---- Search the picker ----
    $('#labTestSearch').on('input', function () {
        const q = $(this).val().trim().toLowerCase();
        let visible = 0;
        $('.elab-test, .elab-paket').each(function () {
            const match = !q || String($(this).data('search')).indexOf(q) !== -1;
            $(this).toggle(match);
            if (match) visible++;
        });
        $('.elab-kategori').each(function () {
            $(this).toggle($(this).find('.elab-test').filter(function () { return $(this).css('display') !== 'none'; }).length > 0);
        });
        $('#labSearchEmpty').toggle(visible === 0);
    });

    // ---- Status: click the badge to change it ----
    $(document).on('click', '.status-display', function () {
        if (!canEditStatus) return;
        const container = $(this).closest('.status-container');
        $(this).hide();
        container.find('.status-select').removeClass('d-none').trigger('focus');
    });

    $(document).on('blur change', '.status-select', function (e) {
        const container = $(this).closest('.status-container');
        $(this).addClass('d-none');
        container.find('.status-display').html(statusBadge($(this).val())).show();
        if (e.type === 'change') setDirty(true);
    });

    $('#selectAllLab').on('change', function () {
        $('.row-checkbox').prop('checked', this.checked);
        updateBulkControls();
    });

    $(document).on('change', '.row-checkbox', updateBulkControls);

    $('.bulk-status-btn').on('click', function () {
        const newStatus = $(this).data('status');
        $('.row-checkbox:checked').each(function () {
            const container = $(`.status-container[data-test-id="${$(this).data('test-id')}"]`);
            container.find('.status-select').val(newStatus);
            container.find('.status-display').html(statusBadge(newStatus));
        });
        setDirty(true);
    });

    // ---- Save: the whole list of this visit (unchecked ones are removed on the server) ----
    $('#submitLabRequests').on('click', function () {
        if (saving) return;
        const requestsData = {};
        $('#checkedLabTable tbody tr[data-test-id]').each(function () {
            const packageId = $(this).data('package-id') || null;
            requestsData[String($(this).data('test-id'))] = {
                status: $(this).find('.status-select').val() || 'requested',
                lab_paket_id: packageId ? Number(packageId) : null
            };
        });

        const btn = $(this);
        saving = true;
        btn.prop('disabled', true);
        $.ajax({
            url: cfg.routes.bulkUpdate,
            method: 'POST',
            data: { visitation_id: visitationId, requests: requestsData, _token: $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                // What is saved now is the new baseline for the statuses
                Object.keys(existingLabTestStatuses).forEach(function (k) { delete existingLabTestStatuses[k]; });
                Object.keys(existingLabTestPaket).forEach(function (k) { delete existingLabTestPaket[k]; });
                $.each(requestsData, function (testId, r) {
                    existingLabTestStatuses[testId] = r.status;
                    existingLabTestPaket[testId] = r.lab_paket_id;
                });
                applyLocks();
                renderSelected();
                setDirty(false);
                Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message, timer: 1500, showConfirmButton: false });
                // This visit's riwayat changed: refetch it next time it is opened
                delete historyDetailCache[String(visitationId)];
                if (labHistoryTable) labHistoryTable.ajax.reload(null, false);
            },
            error: function (xhr) {
                Swal.fire('Gagal', (xhr.responseJSON && xhr.responseJSON.message) || 'Permintaan lab gagal disimpan.', 'error');
            },
            complete: function () {
                saving = false;
                btn.prop('disabled', false);
            }
        });
    });

    // ---- Hasil Lab modal: the tables are made on the first open (no requests on page load) ----
    let hasilLisTable = null;
    let hasilEksternalTable = null;

    $('#modalHasilLab').on('shown.bs.modal', function () {
        if (!hasilLisTable) {
            initHasilLisTable();
        } else {
            hasilLisTable.columns.adjust();
        }
    });

    $('#hasil-eksternal-tab').on('shown.bs.tab', function () {
        if (!hasilEksternalTable) {
            initHasilEksternalTable();
        } else {
            hasilEksternalTable.columns.adjust();
        }
    });

    function initHasilLisTable() {
    hasilLisTable = $('#hasilLisTable').DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        lengthChange: false,
        pageLength: 10,
        deferRender: true,
        ajax: cfg.routes.hasilLisData,
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'tanggal', name: 'tanggal_visitation' },
            { data: 'pemeriksaan', name: 'pemeriksaan', orderable: false, render: function (data) { return escapeHtml(data); } },
            { data: 'dokter', name: 'dokter', orderable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[1, 'desc']],
        language: { emptyTable: 'Belum ada hasil LIS' }
    });
    }

    $(document).on('click', '.btn-view-hasil-lis', function () {
        const tbody = $('#hasilLisDetailTable tbody');
        tbody.html('<tr><td colspan="6" class="text-center">Memuat data...</td></tr>');
        $('#hasilLisModal').modal('show');

        $.get('/erm/elab/hasil-lis/' + $(this).data('id')).done(function (response) {
            const items = (response && response.data) || [];
            if (!items.length) {
                tbody.html('<tr><td colspan="6" class="text-center">Tidak ada data hasil</td></tr>');
                return;
            }
            // Group by header then sub header (results without a header go under "Lainnya")
            const grouped = {};
            items.forEach(function (item) {
                const header = item.header || 'Lainnya';
                const sub = item.sub_header || '';
                grouped[header] = grouped[header] || {};
                (grouped[header][sub] = grouped[header][sub] || []).push(item);
            });
            let html = '';
            let no = 1;
            $.each(grouped, function (header, subs) {
                html += `<tr><td colspan="6" class="font-weight-bold text-uppercase" style="background: var(--sb-tile, #f1f5f9);">${escapeHtml(header)}</td></tr>`;
                $.each(subs, function (sub, rows) {
                    if (sub) {
                        html += `<tr><td colspan="6" class="font-weight-bold text-success" style="padding-left: 20px;">${escapeHtml(sub)}</td></tr>`;
                    }
                    rows.forEach(function (item) {
                        html += `<tr><td>${no++}</td><td>${escapeHtml(item.nama_test || '-')}</td><td>${escapeHtml(item.hasil || '-')}</td><td>${escapeHtml(item.flag || '-')}</td><td>${escapeHtml(item.nilai_rujukan || '-')}</td><td>${escapeHtml(item.satuan || '-')}</td></tr>`;
                    });
                });
            });
            tbody.html(html);
        }).fail(function () {
            tbody.html('<tr><td colspan="6" class="text-center text-danger">Hasil LIS gagal dimuat.</td></tr>');
        });
    });

    $('#addLisHasilModal').on('hidden.bs.modal', function () {
        $('#addLisHasilForm')[0].reset();
        $('.is-invalid').removeClass('is-invalid');
    });

    // Header follows the kategori of the chosen test (bound once, the modal is reused)
    $('#nama_test').on('change', function () {
        $(this).removeClass('is-invalid');
        const option = $(this).find('option:selected');
        if (!option.val()) return;
        if (option.data('kategori')) {
            $('#header').val(option.data('kategori'));
        } else if (!$('#header').val()) {
            $('#header').val('Lainnya');
        }
    });

    $('#hasilLisTable').on('click', '.btn-add-hasil-lis', function () {
        $('#addLisHasilForm')[0].reset();
        $('.is-invalid').removeClass('is-invalid');
        $('#lis_visitation_id').val($(this).data('id'));

        if ($('#nama_test').hasClass('select2-hidden-accessible')) {
            $('#nama_test').select2('destroy');
        }
        $('#nama_test').val('').select2({
            dropdownParent: $('#addLisHasilModal'),
            placeholder: 'Pilih Test',
            width: '100%',
            tags: true,
            createTag: function (params) {
                return { id: params.term, text: params.term, newTag: true };
            }
        });
        $('#addLisHasilModal').modal('show');
    });

    $('#saveLisHasil').on('click', function () {
        $('.is-invalid').removeClass('is-invalid');
        if (!$('#nama_test').val()) {
            $('#nama_test').addClass('is-invalid');
            $('#nama_test_error').show();
            Swal.fire('Validasi Gagal', 'Nama test wajib diisi.', 'warning');
            return;
        }

        const saveBtn = $(this);
        saveBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...');
        $.ajax({
            url: cfg.routes.hasilLisStore,
            method: 'POST',
            data: $('#addLisHasilForm').serialize(),
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                if (response.success) {
                    $('#addLisHasilModal').modal('hide');
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message || 'Hasil LIS berhasil ditambahkan', timer: 1500, showConfirmButton: false });
                    if (hasilLisTable) hasilLisTable.ajax.reload(null, false);
                    // The PDF of that visit and the lembar monitoring now have one more result
                    window.ErmPdfPreview.drop('/hasil-lis/' + $('#lis_visitation_id').val() + '/pdf');
                    window.ErmPdfPreview.drop('lembar-monitoring');
                } else {
                    Swal.fire('Gagal', response.message || 'Terjadi kesalahan', 'error');
                }
            },
            error: function (xhr) {
                const errors = xhr.responseJSON && xhr.responseJSON.errors;
                Swal.fire('Gagal', errors ? Object.values(errors).flat().join('<br>') : 'Hasil LIS gagal disimpan.', 'error');
            },
            complete: function () {
                saveBtn.prop('disabled', false).html('Simpan');
            }
        });
    });

    // ---- Hasil eksternal ----
    function initHasilEksternalTable() {
    hasilEksternalTable = $('#hasilEksternalTable').DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        lengthChange: false,
        pageLength: 10,
        deferRender: true,
        ajax: cfg.routes.hasilEksternalData,
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'asal_lab', name: 'asal_lab', render: function (data) { return escapeHtml(data); } },
            { data: 'nama_pemeriksaan', name: 'nama_pemeriksaan', render: function (data) { return escapeHtml(data); } },
            { data: 'dokter', name: 'dokter', render: function (data) { return escapeHtml(data); } },
            { data: 'tanggal_pemeriksaan', name: 'tanggal_pemeriksaan' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[4, 'desc']],
        language: { emptyTable: 'Belum ada hasil lab eksternal' }
    });
    }

    $('#hasilEksternalTable').on('click', '.btn-view-hasil-eksternal', function () {
        $.get('/erm/elab/hasil-eksternal/' + $(this).data('id')).done(function (response) {
            const d = response.data || {};
            $('#hasilEksternalAsalLab').text(d.asal_lab || '-');
            $('#hasilEksternalNamaPemeriksaan').text(d.nama_pemeriksaan || '-');
            $('#hasilEksternalTanggalPemeriksaan').text(d.tanggal_pemeriksaan ? new Date(d.tanggal_pemeriksaan).toLocaleDateString('id-ID') : '-');
            $('#hasilEksternalDokter').text(d.dokter || '-');
            $('#hasilEksternalCatatan').text(d.catatan || '-');

            if (response.fileUrl) {
                const ext = response.fileUrl.split('.').pop().toLowerCase();
                const viewer = $('#fileViewerContent').empty();
                if (['jpg', 'jpeg', 'png'].includes(ext)) {
                    viewer.append($('<img>').attr('src', response.fileUrl).css({ display: 'block', margin: 'auto', maxWidth: '100%', border: '1px solid #ddd' }));
                } else if (ext === 'pdf') {
                    viewer.append($('<iframe>').attr({ src: response.fileUrl, frameborder: 0 }).css({ width: '100%', height: '72vh', border: '1px solid #ddd' }));
                } else {
                    viewer.html('<div class="text-danger">File tidak didukung untuk preview.</div>');
                }
                $('#fileViewerContainer').show();
                $('#downloadPdfLink').attr('href', response.fileUrl).show();
            } else {
                $('#fileViewerContent').empty();
                $('#fileViewerContainer').hide();
                $('#downloadPdfLink').hide();
            }
            $('#hasilEksternalModal').modal('show');
        }).fail(function () {
            Swal.fire('Gagal', 'Hasil lab eksternal gagal dimuat.', 'error');
        });
    });

    $('#addEksternalHasilBtn').on('click', function () {
        $('#addEksternalHasilForm')[0].reset();
        $('#eksternalHasilFilePreview').empty();
        $('#tanggal_pemeriksaan').val(todayLocal());
        $('#addEksternalHasilModal').modal('show');
    });

    $('#addEksternalHasilForm input[type="file"]').on('change', function (e) {
        const file = e.target.files[0];
        const preview = $('#eksternalHasilFilePreview').empty();
        if (!file) return;
        if (file.type.startsWith('image/')) {
            preview.append($('<img>').attr('src', URL.createObjectURL(file)).css({ maxWidth: '100%', maxHeight: '300px', margin: '10px auto', display: 'block' }));
        } else if (file.type === 'application/pdf') {
            preview.append($('<iframe>').attr('src', URL.createObjectURL(file)).css({ width: '100%', height: '300px', border: 'none' }));
        } else {
            preview.text('File tidak didukung.');
        }
    });

    $('#saveEksternalHasil').on('click', function () {
        const btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...');
        $.ajax({
            url: cfg.routes.hasilEksternalStore,
            type: 'POST',
            data: new FormData($('#addEksternalHasilForm')[0]),
            processData: false,
            contentType: false,
            success: function () {
                Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Hasil lab eksternal berhasil ditambahkan', timer: 1500, showConfirmButton: false });
                if (hasilEksternalTable) hasilEksternalTable.ajax.reload(null, false);
                $('#addEksternalHasilModal').modal('hide');
            },
            error: function (xhr) {
                let message = 'Hasil lab eksternal gagal disimpan.';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    message = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = escapeHtml(xhr.responseJSON.message);
                }
                Swal.fire({ icon: 'error', title: 'Gagal', html: message });
            },
            complete: function () {
                btn.prop('disabled', false).html('Simpan');
            }
        });
    });

    // ---- Riwayat permintaan lab modal: visit list on the first open, a visit's tests on its first expand ----
    let labHistoryTable = null;
    const historyDetailCache = {};

    $('#modalRiwayatLab').on('shown.bs.modal', function () {
        if (labHistoryTable) {
            labHistoryTable.columns.adjust();
            return;
        }
        labHistoryTable = $('#labHistoryTable').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            lengthChange: false,
            pageLength: 10,
            deferRender: true,
            ajax: '/erm/elab/patient/' + encodeURIComponent(pasienId) + '/history',
            columns: [
                { data: null, orderable: false, searchable: false, render: function () { return '<i class="fas fa-chevron-right elab-expand"></i>'; } },
                { data: 'tanggal', name: 'tanggal_visitation', render: function (data, type, row) {
                        return escapeHtml(data) + (String(row.id) === String(visitationId) ? ' <span class="badge badge-light">Kunjungan ini</span>' : '');
                    }
                },
                { data: 'dokter', name: 'dokter', orderable: false, render: function (data) { return escapeHtml(data); } }
            ],
            order: [[1, 'desc']],
            language: { emptyTable: 'Belum ada riwayat permintaan lab' }
        });
    });

    function historyDetailHtml(items) {
        if (!items.length) return '<div class="elab-history-detail text-muted">Tidak ada data permintaan lab</div>';
        return '<div class="elab-history-detail"><table class="table table-sm"><thead><tr><th>Pemeriksaan</th><th>Kategori</th><th>Harga</th><th>Status</th></tr></thead><tbody>'
            + items.map(function (item) {
                const paket = item.paket_lab ? `<div class="small text-muted">Paket: ${escapeHtml(item.paket_lab)}</div>` : '';
                return `<tr><td>${escapeHtml(item.nama_pemeriksaan)}${paket}</td><td>${escapeHtml(item.kategori)}</td><td>${escapeHtml(item.harga)}</td><td>${item.status_label}</td></tr>`;
            }).join('')
            + '</tbody></table></div>';
    }

    $('#labHistoryTable').on('click', 'tbody tr[role="row"]', function () {
        const tr = $(this);
        const row = labHistoryTable.row(tr);
        if (!row.data()) return;
        if (row.child.isShown()) {
            row.child.hide();
            tr.removeClass('shown');
            return;
        }
        const id = String(row.data().id);
        tr.addClass('shown');
        if (historyDetailCache[id]) {
            row.child(historyDetailHtml(historyDetailCache[id])).show();
            return;
        }
        row.child('<div class="elab-history-detail text-muted">Memuat...</div>').show();
        $.get('/erm/elab/visitation/' + encodeURIComponent(id) + '/detail').done(function (response) {
            historyDetailCache[id] = (response && response.data) || [];
            if (row.child.isShown()) row.child(historyDetailHtml(historyDetailCache[id])).show();
        }).fail(function () {
            row.child('<div class="elab-history-detail text-danger">Detail gagal dimuat.</div>').show();
        });
    });
});
