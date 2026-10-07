{{--
    JS for erm.partials.supplier-manager (one modal per pemasok / principal). Usage:
        SupplierManager.create({ key: 'principal', label: 'Principal', url: '/erm/principal', canDelete: true,
                                 onChanged: fn, onShowObat: fn(row), showObatTitle: '...' }).open();
--}}
<script>
window.SupplierManager = window.SupplierManager || (function ($) {
    'use strict';

    // Labels for the usage counts returned by SupplierMasterController ("N obat" is shown separately)
    const USAGE_LABELS = {
        faktur: 'faktur beli', master_faktur: 'master faktur', faktur_item: 'item faktur',
        permintaan: 'item permintaan', retur: 'retur'
    };

    function escapeHtml(value) {
        if (value === null || value === undefined) return '';
        return String(value).replace(/[&<>"'`=\/]/g, function (s) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;', '/': '&#x2F;', '`': '&#x60;', '=': '&#x3D;' }[s];
        });
    }
    function formatNumber(value) { return (parseFloat(value) || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 }); }
    function notify(icon, title, html) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: icon, title: title, html: html || '', timer: icon === 'success' ? 1800 : undefined, showConfirmButton: icon !== 'success' });
        } else {
            alert(title + (html ? '\n' + $('<div>').html(html).text() : ''));
        }
    }
    function confirmDialog(title, text) {
        if (typeof Swal !== 'undefined') {
            return Swal.fire({ icon: 'warning', title: title, text: text, showCancelButton: true, confirmButtonText: 'Ya', cancelButtonText: 'Batal', confirmButtonColor: '#dc3545' })
                .then(function (r) { return !!(r.value || r.isConfirmed); });
        }
        return Promise.resolve(confirm(title + (text ? '\n' + text : '')));
    }
    function ajaxErrorHtml(xhr, fallback) {
        const json = xhr.responseJSON;
        if (json && json.errors) return Object.keys(json.errors).map(function (k) { return escapeHtml(json.errors[k].join(', ')); }).join('<br>');
        if (json && json.message) return escapeHtml(json.message);
        return fallback;
    }
    function busy($btn, label) { $btn.data('label', $btn.data('label') || $btn.html()).prop('disabled', true).text(label || 'Memproses...'); }
    function idle($btn) { $btn.prop('disabled', false).html($btn.data('label')); }

    function create(opt) {
        const key = opt.key;
        const label = opt.label;
        const url = opt.url;
        const lower = label.toLowerCase();
        const $modal = $('#' + key + 'ManagerModal');
        const $pane = $modal.find('.supplier-pane');
        const $form = $pane.find('.supplier-form');
        const $tbl = $pane.find('.supplier-table');
        const onChanged = opt.onChanged || function () {};
        let dt = null;
        let mergeSourceId = null;

        function renderNama(data, type, row) {
            if (type !== 'display') return data;
            const contact = [row.telepon, row.email, row.alamat].filter(function (v) { return v && String(v).trim(); }).map(escapeHtml);
            return '<div class="font-weight-bold">' + (data ? escapeHtml(data) : '<em class="text-danger">(kosong)</em>') + '</div>' +
                (contact.length ? '<div class="small text-muted">' + contact.join(' · ') + '</div>'
                    : '<div class="small text-muted font-italic">Kontak belum diisi</div>');
        }

        function renderUsage(data, type, row) {
            const u = row.usage || {};
            if (type !== 'display') return u.obat || 0;
            const parts = [];
            if (u.obat > 0) {
                parts.push(opt.onShowObat
                    ? '<a href="#" class="supplier-show-obat" title="' + escapeHtml(opt.showObatTitle || 'Lihat di tabel') + '">' + formatNumber(u.obat) + ' obat</a>'
                    : formatNumber(u.obat) + ' obat');
            }
            const others = Object.keys(USAGE_LABELS).filter(function (k) { return u[k] > 0; })
                .map(function (k) { return formatNumber(u[k]) + ' ' + USAGE_LABELS[k]; });
            if (others.length) parts.push('<span class="small text-muted">' + others.join(', ') + '</span>');
            return parts.length ? parts.join('<br>') : '<span class="text-muted">Tidak dipakai</span>';
        }

        function renderActions(data, type, row) {
            let html = '<div class="btn-group btn-group-sm">' +
                '<button type="button" class="btn btn-outline-primary supplier-edit" title="Ubah nama / kontak"><i class="fas fa-pen"></i></button>';
            if (opt.canDelete) {
                html += '<button type="button" class="btn btn-outline-secondary supplier-merge" title="Gabungkan ke ' + lower + ' lain (nama ganda)"><i class="fas fa-code-branch"></i></button>';
                html += '<button type="button" class="btn btn-outline-danger supplier-delete" ' +
                    (row.used ? 'disabled title="Masih dipakai, tidak bisa dihapus"' : 'title="Hapus"') + '><i class="fas fa-trash"></i></button>';
            }
            return html + '</div>';
        }

        function initTable() {
            dt = $tbl.DataTable({
                processing: true,
                serverSide: true,
                pageLength: 10,
                lengthChange: false,
                autoWidth: false,
                order: [[1, 'asc']],
                dom: "rt<'row align-items-center mt-2'<'col-sm-6 small'i><'col-sm-6'p>>",
                ajax: {
                    url: url,
                    data: function (d) { d.pemakaian = $pane.find('.supplier-filter').val() || ''; d.with_summary = 1; },
                    dataSrc: function (res) {
                        const dup = res.duplicate_count || 0;
                        $pane.find('.supplier-duplicate-hint').toggleClass('d-none', dup === 0)
                            .html('<i class="fas fa-exclamation-triangle mr-1"></i>' + dup + ' ' + lower + ' punya nama ganda atau kosong. ' +
                                '<a href="#" class="supplier-show-duplicates">Tampilkan</a>');
                        return res.data || [];
                    },
                    error: function (xhr) { notify('error', 'Gagal memuat ' + lower, ajaxErrorHtml(xhr, '')); }
                },
                columns: [
                    { data: null, orderable: false, render: function (d, t, r, meta) { return meta.settings._iDisplayStart + meta.row + 1; } },
                    { data: 'nama', name: 'nama', render: renderNama },
                    { data: null, orderable: false, searchable: false, render: renderUsage },
                    { data: null, orderable: false, searchable: false, className: 'text-right text-nowrap', render: renderActions }
                ],
                language: {
                    info: '_START_–_END_ dari _TOTAL_ ' + lower,
                    infoEmpty: 'Tidak ada data',
                    infoFiltered: '',
                    zeroRecords: 'Tidak ada ' + lower + ' yang cocok.',
                    emptyTable: 'Belum ada ' + lower + '.',
                    paginate: { previous: '&lsaquo;', next: '&rsaquo;' }
                }
            });
        }

        function reload(resetPaging) {
            if (dt) dt.ajax.reload(null, resetPaging === true);
        }

        function afterChange() {
            reload(false);
            onChanged();
        }

        // ----- tambah / edit form -----
        function field(name) { return $form.find('[name="' + name + '"]'); }

        function resetForm() {
            $form[0].reset();
            field('id').val('');
            $form.removeClass('border-primary bg-light').find('.is-invalid').removeClass('is-invalid');
            $form.find('.supplier-form-title').text('Tambah ' + lower + ' baru');
            $form.find('.supplier-submit').html('<i class="fas fa-plus mr-1"></i> Tambah');
            $form.find('.supplier-cancel').addClass('d-none');
            $form.find('.supplier-feedback').text('').removeClass('text-danger text-success');
        }

        function startEdit(row) {
            resetForm();
            hideMerge();
            field('id').val(row.id);
            ['nama', 'telepon', 'email', 'alamat'].forEach(function (n) { field(n).val(row[n] || ''); });
            $form.addClass('border-primary bg-light');
            $form.find('.supplier-form-title').html('Edit ' + lower + ': <strong>' + escapeHtml(row.nama || '') + '</strong>');
            $form.find('.supplier-submit').html('<i class="fas fa-save mr-1"></i> Simpan');
            $form.find('.supplier-cancel').removeClass('d-none');
            $modal.find('.modal-body').scrollTop(0);
            field('nama').trigger('focus').trigger('select');
        }

        function submit(e) {
            e.preventDefault();
            const id = field('id').val();
            const nama = (field('nama').val() || '').trim();
            const $fb = $form.find('.supplier-feedback').removeClass('text-danger text-success').text('');
            $form.find('.is-invalid').removeClass('is-invalid');
            if (!nama) { field('nama').addClass('is-invalid').trigger('focus'); return; }

            const data = { nama: nama };
            ['telepon', 'email', 'alamat'].forEach(function (n) { data[n] = (field(n).val() || '').trim(); });
            const $btn = $form.find('.supplier-submit').prop('disabled', true);
            $.ajax({ url: id ? url + '/' + id : url, type: id ? 'PUT' : 'POST', data: data })
                .done(function (res) {
                    resetForm();
                    if (id) {
                        notify('success', res.message || label + ' diperbarui');
                        afterChange();
                        return;
                    }
                    $form.find('.supplier-feedback').addClass('text-success').text(res.message || label + ' ditambahkan.');
                    // Show the new row
                    $pane.find('.supplier-search').val((res.data && res.data.nama) || nama);
                    dt.search($pane.find('.supplier-search').val());
                    reload(true);
                    field('nama').trigger('focus');
                })
                .fail(function (xhr) {
                    const errors = (xhr.responseJSON && xhr.responseJSON.errors) || {};
                    Object.keys(errors).forEach(function (n) { field(n).addClass('is-invalid'); });
                    $fb.addClass('text-danger').html(ajaxErrorHtml(xhr, 'Gagal menyimpan.'));
                })
                .always(function () { $btn.prop('disabled', false); });
        }

        // ----- gabungkan -----
        function showMerge(row) {
            resetForm();
            mergeSourceId = row.id;
            $pane.find('.supplier-merge-source').text(row.nama || '(kosong)');
            $pane.find('.supplier-merge-target').empty().trigger('change');
            $pane.find('.supplier-merge-panel').removeClass('d-none');
            $modal.find('.modal-body').scrollTop(0);
            $pane.find('.supplier-merge-target').select2('open');
        }

        function hideMerge() {
            mergeSourceId = null;
            $pane.find('.supplier-merge-panel').addClass('d-none');
        }

        function saveMerge() {
            const $target = $pane.find('.supplier-merge-target');
            const targetId = $target.val();
            if (!mergeSourceId || !targetId) { notify('warning', 'Pilih ' + lower + ' tujuan.'); return; }
            const sourceName = $pane.find('.supplier-merge-source').first().text();
            const targetName = $target.find('option:selected').text();
            confirmDialog('Gabungkan ' + lower + '?', '"' + sourceName + '" akan digabung ke "' + targetName + '". Tidak bisa dibatalkan.').then(function (ok) {
                if (!ok) return;
                const $btn = $pane.find('.supplier-merge-save');
                busy($btn, 'Menggabungkan...');
                $.post(url + '/' + mergeSourceId + '/merge', { target_id: targetId })
                    .done(function (res) { hideMerge(); notify('success', res.message || 'Digabungkan'); afterChange(); })
                    .fail(function (xhr) { notify('error', 'Gagal menggabungkan', ajaxErrorHtml(xhr, '')); })
                    .always(function () { idle($btn); });
            });
        }

        function remove(row) {
            confirmDialog('Hapus ' + lower + '?', row.nama || '(kosong)').then(function (ok) {
                if (!ok) return;
                $.ajax({ url: url + '/' + row.id, type: 'DELETE' })
                    .done(function (res) { notify('success', res.message || 'Dihapus'); afterChange(); })
                    .fail(function (xhr) { notify('error', 'Tidak bisa menghapus', ajaxErrorHtml(xhr, '')); });
            });
        }

        function rowOf(el) { return dt.row($(el).closest('tr')).data(); }

        // ----- wiring -----
        $pane.find('.supplier-merge-target').select2({
            width: '100%',
            dropdownParent: $modal,
            placeholder: 'Cari ' + lower + ' tujuan...',
            minimumInputLength: 1,
            ajax: {
                url: url,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    const page = params.page || 1;
                    return { 'search[value]': params.term || '', start: (page - 1) * 20, length: 20, 'order[0][column]': 1, 'order[0][dir]': 'asc' };
                },
                processResults: function (res, params) {
                    const page = params.page || 1;
                    return {
                        results: (res.data || []).filter(function (r) { return r.id !== mergeSourceId; })
                            .map(function (r) { return { id: r.id, text: r.nama }; }),
                        pagination: { more: page * 20 < (res.recordsFiltered || 0) }
                    };
                }
            }
        });

        $form.on('submit', submit);
        $form.find('.supplier-cancel').on('click', resetForm);
        $form.on('input', '.is-invalid', function () { $(this).removeClass('is-invalid'); });

        let searchTimer = null;
        $pane.find('.supplier-search').on('input search', function () {
            clearTimeout(searchTimer);
            const value = $(this).val();
            searchTimer = setTimeout(function () { dt.search(value.trim()); reload(true); }, 350);
        });
        $pane.find('.supplier-filter').on('change', function () { reload(true); });
        $pane.on('click', '.supplier-show-duplicates', function (e) {
            e.preventDefault();
            $pane.find('.supplier-filter').val('ganda');
            reload(true);
        });

        $tbl.on('click', '.supplier-edit', function () { startEdit(rowOf(this)); });
        $tbl.on('click', '.supplier-merge', function () { showMerge(rowOf(this)); });
        $tbl.on('click', '.supplier-delete', function () { remove(rowOf(this)); });
        // "12 obat": close the modal and let the page show them
        $tbl.on('click', '.supplier-show-obat', function (e) {
            e.preventDefault();
            const row = rowOf(this);
            $modal.modal('hide');
            if (opt.onShowObat) opt.onShowObat(row);
        });
        $pane.find('.supplier-merge-cancel').on('click', hideMerge);
        $pane.find('.supplier-merge-save').on('click', saveMerge);

        // Table loads on first open (DataTables needs the modal visible to size its columns)
        $modal.on('shown.bs.modal', function () { if (!dt) initTable(); else { dt.columns.adjust(); reload(false); } });
        $modal.on('hidden.bs.modal', function () { hideMerge(); resetForm(); });

        return {
            open: function () { $modal.modal('show'); }
        };
    }

    return { create: create };
})(jQuery);
</script>
