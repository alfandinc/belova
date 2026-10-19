// E-Radiologi page (same flow and look as E-Lab). Page data comes from window.EradiologiConfig;
// stacked modals and the PDF preview come from erm-pdf-preview.js.
$(document).ready(function () {
    const cfg = window.EradiologiConfig;
    const visitationId = cfg.visitationId;
    // radiologi_test_id => status of what is already saved for this visit
    const existingStatuses = cfg.existingStatuses;
    // Diproses / Selesai is set by the lab; the others only see it
    const canEditStatus = cfg.canEditStatus;

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

    function url(name, id) {
        return cfg.routes[name].replace('__ID__', encodeURIComponent(id));
    }

    function statusBadge(status) {
        const key = STATUS[status] ? status : 'requested';
        return `<span class="elab-status is-${key}${canEditStatus ? '' : ' is-readonly'}">${STATUS[key]}</span>`;
    }

    // Saved as Diproses / Selesai: already being worked on, so it can not be removed here
    function isLocked(testId) {
        const status = existingStatuses[String(testId)];
        return status === 'processing' || status === 'completed';
    }

    function applyLocks() {
        $('.test-checkbox').each(function () {
            const locked = isLocked($(this).data('id'));
            $(this).prop('disabled', locked).closest('label').toggleClass('is-locked', locked)
                .attr('title', locked ? 'Sudah diproses, tidak bisa dihapus' : null);
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
        $('#submitRequests')
            .toggleClass('is-dirty', dirty)
            .html(dirty ? '<i class="fas fa-save mr-1"></i>Simpan Perubahan' : '<i class="fas fa-save mr-1"></i>Simpan Permintaan Radiologi');
        $('#saveHint').html(dirty
            ? '<i class="fas fa-exclamation-circle mr-1 text-warning"></i>Ada perubahan yang belum disimpan.'
            : '<i class="fas fa-info-circle mr-1"></i>Pemeriksaan yang dicentang baru tersimpan setelah klik Simpan.');
    }

    window.addEventListener('beforeunload', function (e) {
        if (!isDirty) return;
        e.preventDefault();
        e.returnValue = '';
    });

    // ---- Permintaan table + estimasi ----
    function renderSelected() {
        const rowCheckStates = {};
        $('.row-checkbox').each(function () {
            rowCheckStates[String($(this).data('test-id'))] = $(this).prop('checked');
        });
        // Keep the (unsaved) statuses of rows that stay in the list
        const statusStates = {};
        $('#selectedTable tbody tr[data-test-id]').each(function () {
            statusStates[String($(this).data('test-id'))] = $(this).find('.status-select').val();
        });

        let html = '';
        let total = 0;
        $('.test-checkbox:checked').each(function (idx) {
            const testId = String($(this).data('id'));
            const price = Number($(this).data('price') || 0);
            const status = statusStates[testId] || existingStatuses[testId] || 'requested';
            total += price;
            const noCell = canEditStatus
                ? `<div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input row-checkbox" id="rowCheck-${testId}" data-test-id="${testId}" ${rowCheckStates[testId] ? 'checked' : ''}>
                        <label class="custom-control-label" for="rowCheck-${testId}">${idx + 1}</label>
                    </div>`
                : `<span class="text-muted font-weight-bold">${idx + 1}</span>`;
            const lock = isLocked(testId) ? '<i class="fas fa-lock elab-lock-icon" title="Sudah diproses, tidak bisa dihapus"></i>' : '';
            html += `<tr data-test-id="${testId}">
                    <td>${noCell}</td>
                    <td>${escapeHtml($(this).data('name'))}${lock}</td>
                    <td class="text-right">Rp ${formatNumber(price)}</td>
                    <td class="text-center">
                        <div class="status-container" data-test-id="${testId}">
                            <div class="status-display">${statusBadge(status)}</div>
                            <select class="form-control form-control-sm status-select d-none" data-test-id="${testId}">
                                ${Object.keys(STATUS).map(function (s) { return `<option value="${s}" ${s === status ? 'selected' : ''}>${STATUS[s]}</option>`; }).join('')}
                            </select>
                        </div>
                    </td>
                </tr>`;
        });

        $('#selectedTable tbody').html(html || '<tr><td colspan="4" class="text-center text-muted py-3">Belum ada pemeriksaan dipilih</td></tr>');
        $('#estimasiTotal, #estimasiBar').text(formatNumber(total));
        updateBulkControls();
    }

    function updateBulkControls() {
        const all = $('.row-checkbox');
        const checked = $('.row-checkbox:checked');
        $('#selectAll').prop('checked', all.length > 0 && checked.length === all.length);
        $('.bulk-status-container').css('display', checked.length ? 'flex' : 'none');
    }

    applyLocks();
    renderSelected();

    $(document).on('change', '.test-checkbox', function () {
        renderSelected();
        setDirty(true);
    });

    // ---- Ulangi permintaan dari kunjungan radiologi terakhir (adds to what is already checked) ----
    $('#repeatPrevious').on('click', function () {
        const previous = cfg.previous;
        if (!previous) return;
        const boxes = (previous.test_ids || []).map(function (id) { return $(`.test-checkbox[data-id="${id}"]`); });
        const found = boxes.filter(function (box) { return box.length; });
        const missing = boxes.length - found.length;
        const names = found.map(function (box) { return box.data('name'); });

        Swal.fire({
            title: 'Ulangi radiologi ' + previous.tanggal + '?',
            html: (names.length ? '<div class="text-left small">' + names.map(escapeHtml).join('<br>') + '</div>' : 'Tidak ada pemeriksaan yang masih tersedia.')
                + (missing ? `<div class="small text-muted mt-2">${missing} pemeriksaan sudah tidak ada di master.</div>` : '')
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
    $('#testSearch').on('input', function () {
        const q = $(this).val().trim().toLowerCase();
        let visible = 0;
        $('.elab-test').each(function () {
            const match = !q || String($(this).data('search')).indexOf(q) !== -1;
            $(this).toggle(match);
            if (match) visible++;
        });
        $('.elab-kategori').each(function () {
            $(this).toggle($(this).find('.elab-test').filter(function () { return $(this).css('display') !== 'none'; }).length > 0);
        });
        $('#searchEmpty').toggle(visible === 0);
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

    $('#selectAll').on('change', function () {
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
    $('#submitRequests').on('click', function () {
        if (saving) return;
        const requestsData = {};
        $('#selectedTable tbody tr[data-test-id]').each(function () {
            requestsData[String($(this).data('test-id'))] = $(this).find('.status-select').val() || 'requested';
        });

        const btn = $(this);
        saving = true;
        btn.prop('disabled', true);
        $.ajax({
            url: cfg.routes.sync,
            method: 'POST',
            data: { visitation_id: visitationId, requests: requestsData, _token: $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                // What the server saved is the new baseline (it keeps tests already being worked on)
                Object.keys(existingStatuses).forEach(function (k) { delete existingStatuses[k]; });
                $.each(response.statuses || {}, function (testId, status) { existingStatuses[String(testId)] = status; });
                $('.test-checkbox').each(function () {
                    $(this).prop('checked', Object.prototype.hasOwnProperty.call(existingStatuses, String($(this).data('id'))));
                });
                applyLocks();
                $('#selectedTable tbody').empty();
                renderSelected();
                setDirty(false);
                Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message, timer: 1500, showConfirmButton: false });
                // The printed form and this visit's riwayat changed
                window.ErmPdfPreview.drop(cfg.routes.print);
                delete historyDetailCache[String(visitationId)];
                if (historyTable) historyTable.ajax.reload(null, false);
            },
            error: function (xhr) {
                const errors = xhr.responseJSON && xhr.responseJSON.errors;
                Swal.fire('Gagal', errors ? Object.values(errors).flat().map(escapeHtml).join('<br>') : escapeHtml((xhr.responseJSON && xhr.responseJSON.message) || 'Permintaan radiologi gagal disimpan.'), 'error');
            },
            complete: function () {
                saving = false;
                btn.prop('disabled', false);
            }
        });
    });

    // Print shows what is saved: unsaved changes go in first
    $('#printPermintaanBtn').on('erm:pdf-preview', function (e) {
        if (!isDirty) return;
        e.preventDefault();
        Swal.fire('Simpan dulu', 'Ada perubahan yang belum disimpan. Klik Simpan supaya ikut tercetak.', 'info');
    });

    // ---- Hasil radiologi modal: the table is made on the first open (no request on page load) ----
    let dokumenTable = null;

    $('#modalHasilRadiologi').on('shown.bs.modal', function () {
        if (dokumenTable) {
            dokumenTable.columns.adjust();
            return;
        }
        dokumenTable = $('#dokumenRadiologiTable').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            lengthChange: false,
            pageLength: 10,
            deferRender: true,
            ajax: cfg.routes.dokumenData,
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'tanggal', name: 'tanggal_pemeriksaan' },
                { data: 'dokter_pengirim', name: 'dokter_pengirim', render: function (data) { return escapeHtml(data); } },
                { data: 'nama_pemeriksaan', name: 'nama_pemeriksaan', render: function (data) { return escapeHtml(data); } },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            order: [[1, 'desc']],
            language: { emptyTable: 'Belum ada hasil radiologi' }
        });
    });

    $('#btnUploadHasil').on('click', function () {
        $('#uploadRadiologiForm')[0].reset();
        $('#uploadRadiologiTanggal').val(todayLocal());
        $('#uploadRadiologiModal').modal('show');
    });

    $('#uploadRadiologiForm').on('submit', function (e) {
        e.preventDefault();
        const btn = $(this).find('[type="submit"]');
        btn.prop('disabled', true);
        $.ajax({
            url: cfg.routes.hasilUpload,
            type: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            success: function (response) {
                $('#uploadRadiologiModal').modal('hide');
                Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message, timer: 1500, showConfirmButton: false });
                if (dokumenTable) dokumenTable.ajax.reload();
            },
            error: function (xhr) {
                const errors = xhr.responseJSON && xhr.responseJSON.errors;
                Swal.fire({ icon: 'error', title: 'Gagal', html: errors ? Object.values(errors).flat().map(escapeHtml).join('<br>') : 'Hasil radiologi gagal diunggah.' });
            },
            complete: function () {
                btn.prop('disabled', false);
            }
        });
    });

    $('#dokumenRadiologiTable').on('click', '.btn-view-radiologi', function () {
        $.get(url('hasilDetail', $(this).data('id'))).done(function (response) {
            if (!response.success) return;
            const hasil = response.data;
            $('#viewRadiologiHasilId').val(hasil.id);
            $('#viewRadiologiNamaPemeriksaan').text(hasil.nama_pemeriksaan || '-');
            $('#viewRadiologiDokter').text(hasil.dokter_pengirim || '-');
            $('#viewRadiologiTanggal').text(hasil.tanggal_pemeriksaan ? new Date(hasil.tanggal_pemeriksaan).toLocaleDateString('id-ID') : '-');
            $('#viewRadiologiDeskripsi').val(hasil.deskripsi || '');

            const preview = $('#viewRadiologiFilePreview').empty();
            const fileUrl = '/storage/' + String(hasil.file_path || '').split('/').map(encodeURIComponent).join('/');
            const ext = String(hasil.file_path || '').split('.').pop().toLowerCase();
            if (['jpg', 'jpeg', 'png'].includes(ext)) {
                preview.append($('<img class="img-fluid" alt="Hasil radiologi">').attr('src', fileUrl));
            } else if (ext === 'pdf') {
                preview.append($('<iframe allowfullscreen>').attr('src', fileUrl).css({ width: '100%', height: '70vh', border: 0 }));
            } else {
                preview.append($('<p>File tidak dapat ditampilkan. </p>').append($('<a target="_blank">Download</a>').attr('href', fileUrl)));
            }
            $('#viewRadiologiModal').modal('show');
        }).fail(function () {
            Swal.fire('Gagal', 'Hasil radiologi gagal dimuat.', 'error');
        });
    });

    // Hapus hasil (the button is only rendered for Admin; the server checks the role again)
    $('#dokumenRadiologiTable').on('click', '.btn-delete-radiologi-hasil', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Hapus hasil radiologi?',
            html: `<b>${escapeHtml($(this).data('nama'))}</b><br><small>Dokumen dan file-nya dihapus permanen.</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#dc2626'
        }).then(function (result) {
            if (!(result.isConfirmed || result.value)) return;
            $.ajax({
                url: url('hasilDetail', id),
                type: 'DELETE',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (response) {
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message, timer: 1500, showConfirmButton: false });
                    if (dokumenTable) dokumenTable.ajax.reload(null, false);
                    const count = $('[data-target="#modalHasilRadiologi"].elab-tool-btn .elab-count');
                    count.text(Math.max(0, (parseInt(count.text(), 10) || 0) - 1));
                },
                error: function (xhr) {
                    Swal.fire('Gagal', (xhr.responseJSON && xhr.responseJSON.message) || 'Hasil radiologi gagal dihapus.', 'error');
                }
            });
        });
    });

    $('#btn-update-radiologi-deskripsi').on('click', function () {
        const hasilId = $('#viewRadiologiHasilId').val();
        if (!hasilId) return;
        $.ajax({
            url: url('hasilDeskripsi', hasilId),
            type: 'PUT',
            data: { deskripsi: $('#viewRadiologiDeskripsi').val(), _token: $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message, timer: 1500, showConfirmButton: false });
                if (dokumenTable) dokumenTable.ajax.reload(null, false);
            },
            error: function (xhr) {
                Swal.fire('Gagal', (xhr.responseJSON && xhr.responseJSON.message) || 'Deskripsi gagal disimpan.', 'error');
            }
        });
    });

    // ---- Riwayat permintaan radiologi modal: visit list on the first open, a visit's tests on its first expand ----
    let historyTable = null;
    const historyDetailCache = {};

    $('#modalRiwayatRadiologi').on('shown.bs.modal', function () {
        if (historyTable) {
            historyTable.columns.adjust();
            return;
        }
        historyTable = $('#historyTable').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            lengthChange: false,
            pageLength: 10,
            deferRender: true,
            ajax: cfg.routes.history,
            columns: [
                { data: null, orderable: false, searchable: false, render: function () { return '<i class="fas fa-chevron-right elab-expand"></i>'; } },
                { data: 'tanggal', name: 'tanggal_visitation', render: function (data, type, row) {
                        return escapeHtml(data) + (String(row.id) === String(visitationId) ? ' <span class="badge badge-light">Kunjungan ini</span>' : '');
                    }
                },
                { data: 'dokter', name: 'dokter', orderable: false, render: function (data) { return escapeHtml(data); } }
            ],
            order: [[1, 'desc']],
            language: { emptyTable: 'Belum ada riwayat permintaan radiologi' }
        });
    });

    function historyDetailHtml(items) {
        if (!items.length) return '<div class="elab-history-detail text-muted">Tidak ada data permintaan radiologi</div>';
        return '<div class="elab-history-detail"><table class="table table-sm"><thead><tr><th>Pemeriksaan</th><th>Kategori</th><th>Harga</th><th>Status</th></tr></thead><tbody>'
            + items.map(function (item) {
                return `<tr><td>${escapeHtml(item.nama_pemeriksaan)}</td><td>${escapeHtml(item.kategori)}</td><td>${escapeHtml(item.harga)}</td><td>${statusBadge(item.status).replace('elab-status', 'elab-status is-readonly')}</td></tr>`;
            }).join('')
            + '</tbody></table></div>';
    }

    $('#historyTable').on('click', 'tbody tr[role="row"]', function () {
        const tr = $(this);
        const row = historyTable.row(tr);
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
        $.get(url('visitDetail', id)).done(function (response) {
            historyDetailCache[id] = (response && response.data) || [];
            if (row.child.isShown()) row.child(historyDetailHtml(historyDetailCache[id])).show();
        }).fail(function () {
            row.child('<div class="elab-history-detail text-danger">Detail gagal dimuat.</div>').show();
        });
    });
});
