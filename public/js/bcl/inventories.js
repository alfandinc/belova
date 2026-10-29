/*
 * Kos BCL - Inventaris. Server-side table (Yajra); add/edit and maintenance are AJAX dialogs,
 * the table and the counters refresh in place after each save.
 */
(function ($) {
    'use strict';

    var cfg = window.BCL_INV;
    var canSupervise = !!window.BCL_CAN_SUPERVISE;
    var MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    function esc(v) { return $('<div>').text(v == null ? '' : v).html(); }
    function url(name, id) { return cfg.urls[name].replace(':id', encodeURIComponent(id)); }
    function rp(v) { return 'Rp ' + $.number(Math.round(v || 0), 0, ',', '.'); }
    function digits(v) { return parseFloat(String(v == null ? '' : v).replace(/[^0-9]/g, '')) || 0; }
    function fmtDate(v) {
        if (!v) return '-';
        var m = moment(v, 'YYYY-MM-DD');
        return m.isValid() ? m.date() + ' ' + MONTHS[m.month()] + ' ' + m.year() : '-';
    }
    function toast(msg, type) { if (window.bclToast) window.bclToast(msg, type); }
    function ajaxError(xhr, fallback) {
        var json = xhr && xhr.responseJSON;
        if (json && json.errors) return Object.values(json.errors).flat().join(' ');
        return (json && json.message) || fallback || 'Terjadi kesalahan.';
    }

    function duePill(row) {
        if (!row.next_due) return '<span class="text-muted">-</span>';
        var d = row.days_left;
        var label = fmtDate(row.next_due);
        if (row.status === 'overdue') return label + '<div><span class="bcl-pill is-bad">Terlambat ' + Math.abs(d) + ' hari</span></div>';
        if (row.status === 'soon') return label + '<div><span class="bcl-pill is-warn">' + (d === 0 ? 'Hari ini' : d + ' hari lagi') + '</span></div>';
        return label + '<div><span class="bcl-pill">' + d + ' hari lagi</span></div>';
    }

    // ------------------------------------------------------------ table

    var table = $('#tb_inventory').DataTable({
        processing: true,
        serverSide: true,
        deferRender: true,
        pageLength: 25,
        order: [[5, 'asc']],
        ajax: {
            url: cfg.urls.data,
            data: function (d) {
                d.room = $('#flt_room').val();
                d.status = $('#flt_status').val();
            },
        },
        language: {
            search: 'Cari:', lengthMenu: '_MENU_ per halaman', info: '_START_–_END_ dari _TOTAL_ item', infoEmpty: '0 item',
            infoFiltered: '(dari _MAX_)', zeroRecords: 'Tidak ada inventaris yang cocok.', processing: 'Memuat...',
            paginate: { previous: '‹', next: '›' },
        },
        columns: [
            { data: 'inv_number', render: function (v) { return '<a href="#" class="bcl-link js-history" data-inv="' + esc(v) + '">' + esc(v) + '</a>'; } },
            { data: 'name', render: function (v, t, row) { return '<strong>' + esc(v) + '</strong>' + (row.notes && row.notes !== row.type ? '<div class="small text-muted">' + esc(row.notes) + '</div>' : ''); } },
            { data: 'room_name', render: function (v, t, row) { return row.type === 'Public' || !v ? '<span class="bcl-pill is-info">Umum</span>' : esc(v) + (row.floor ? '<div class="small text-muted">Lantai ' + esc(row.floor) + '</div>' : ''); } },
            { data: 'routine', orderable: false, searchable: false, render: function (v) { return v ? esc(v) : '<span class="text-muted">-</span>'; } },
            { data: 'last_maint', searchable: false, render: function (v) { return v ? fmtDate(v) : '<span class="text-muted">Belum pernah</span>'; } },
            { data: 'next_due', searchable: false, render: function (v, t, row) { return duePill(row); } },
            {
                data: 'id', orderable: false, searchable: false, className: 'text-right text-nowrap',
                render: function (id, t, row) {
                    return '<button type="button" class="btn btn-sm btn-primary js-maint" data-inv="' + esc(row.inv_number) + '" title="Catat perawatan"><i class="fas fa-tools"></i> Rawat</button> ' +
                        '<button type="button" class="btn btn-sm btn-secondary js-edit" data-id="' + id + '" title="Edit"><i class="fas fa-pen"></i></button>' +
                        (canSupervise ? ' <button type="button" class="btn btn-sm btn-danger js-delete" data-id="' + id + '" data-label="' + esc(row.inv_number + ' ' + row.name) + '" title="Hapus"><i class="fas fa-trash"></i></button>' : '');
                },
            },
        ],
    });

    function loadStats() {
        $.getJSON(cfg.urls.stats).done(function (s) {
            $('[data-stat]').each(function () {
                var key = $(this).data('stat');
                $(this).text(key === 'month_cost' ? rp(s[key]) : (s[key] || 0));
            });
        });
    }

    function refresh() {
        table.ajax.reload(null, false);
        loadStats();
    }

    $('#flt_room, #flt_status').on('change', function () {
        $('.bcl-kpi[data-status]').removeClass('is-active').filter('[data-status="' + $('#flt_status').val() + '"]').addClass('is-active');
        table.ajax.reload();
    });
    $(document).on('click', '.bcl-kpi[data-status]', function () {
        var status = String($(this).data('status'));
        $('#flt_status').val($('#flt_status').val() === status ? '' : status).trigger('change');
    });

    // ------------------------------------------------------------ add / edit

    function initRoomSelect(max) {
        var $sel = $('#inv_rooms');
        if ($sel.hasClass('select2-hidden-accessible')) $sel.select2('destroy');
        $sel.select2({ width: '100%', dropdownParent: $('#md_inv'), placeholder: 'Pilih kamar', maximumSelectionLength: max || 0, closeOnSelect: max === 1 });
    }

    function setType(type) {
        $('#form_inv input[name="tipe_inv"]').each(function () {
            this.checked = this.value === type;
            $(this).parent().toggleClass('active', this.checked);
        });
        $('#inv_room_group').toggle(type === 'Private/Room');
    }

    function setRoutine(on) {
        $('#inv_routine').prop('checked', on);
        $('#inv_routine_group').toggle(on);
        $('#inv_period').prop('required', on);
    }

    $('#form_inv').on('change', 'input[name="tipe_inv"]', function () { setType(this.value); });
    $('#inv_routine').on('change', function () { setRoutine(this.checked); });

    $('#bt_inv_add').on('click', function () {
        $('#form_inv')[0].reset();
        $('#inv_id').val('');
        $('#inv_title').text('Tambah Inventaris');
        $('#inv_number_group').hide();
        $('#inv_name_group').attr('class', 'form-group col-md-8');
        $('#inv_qty_group').show();
        $('#inv_room_label').html('Kamar <span class="text-danger">*</span> <small class="text-muted">(bisa pilih beberapa: dibuat satu item per kamar)</small>');
        initRoomSelect(0);
        $('#inv_rooms').val(null).trigger('change');
        setType('Private/Room');
        setRoutine(false);
        $('#md_inv').modal('show');
    });

    $(document).on('click', '.js-edit', function () {
        $.getJSON(url('edit', $(this).data('id'))).done(function (inv) {
            $('#form_inv')[0].reset();
            $('#inv_id').val(inv.id);
            $('#inv_title').text('Edit Inventaris ' + inv.inv_number);
            $('#inv_number_group').show();
            $('#inv_name_group').attr('class', 'form-group col-md-8');
            $('#inv_qty_group').hide();
            $('#inv_number').val(inv.inv_number);
            $('#inv_name').val(inv.name);
            $('#inv_notes').val(inv.notes);
            $('#inv_room_label').html('Kamar <span class="text-danger">*</span>');
            initRoomSelect(1);
            $('#inv_rooms').val(inv.assigned_to ? [String(inv.assigned_to)] : null).trigger('change');
            setType(inv.type === 'Public' ? 'Public' : 'Private/Room');
            setRoutine(!!inv.maintanance_period);
            $('#inv_period').val(inv.maintanance_period || '');
            $('#inv_cycle').val(inv.maintanance_cycle || 'Bulan');
            $('#md_inv').modal('show');
        });
    });

    $('#form_inv').on('submit', function (e) {
        e.preventDefault();
        var editing = !!$('#inv_id').val();
        var type = $('#form_inv input[name="tipe_inv"]:checked').val();
        var rooms = $('#inv_rooms').val() || [];
        if (type === 'Private/Room' && !rooms.length) {
            toast('Pilih kamar.', 'error');
            return;
        }
        var data = $(this).serializeArray().filter(function (f) { return f.name !== 'kamar[]'; });
        if (type === 'Private/Room') {
            if (editing) data.push({ name: 'kamar', value: rooms[0] });
            else rooms.forEach(function (r) { data.push({ name: 'kamar[]', value: r }); });
        }
        var $btn = $(this).find('button[type="submit"]').prop('disabled', true);
        $.ajax({ url: editing ? cfg.urls.update : cfg.urls.store, type: 'POST', data: $.param(data), headers: { Accept: 'application/json' } })
            .done(function (res) { $('#md_inv').modal('hide'); toast(res.message); refresh(); })
            .fail(function (xhr) { toast(ajaxError(xhr, 'Gagal menyimpan.'), 'error'); })
            .always(function () { $btn.prop('disabled', false); });
    });

    $(document).on('click', '.js-delete', function () {
        var id = $(this).data('id');
        $.confirm({
            title: 'Hapus ' + $(this).data('label') + '?',
            content: 'Inventaris yang punya riwayat perawatan tidak bisa dihapus.',
            buttons: {
                confirm: {
                    text: 'Hapus', btnClass: 'btn-red', action: function () {
                        $.ajax({ url: url('remove', id), type: 'POST', headers: { Accept: 'application/json' } })
                            .done(function (res) { toast(res.message); refresh(); })
                            .fail(function (xhr) { toast(ajaxError(xhr), 'error'); });
                    },
                },
                cancel: { text: 'Batal' },
            },
        });
    });

    // ------------------------------------------------------------ maintenance

    var current = null;

    function resetMaintForm() {
        $('#form_maint')[0].reset();
        $('#mt_inv').val(current ? current.inv_number : '');
        $('#mt_id').val('');
        $('#mt_date').val(cfg.today);
        $('#mt_form_title').text('Catat perawatan / perbaikan');
        $('#mt_submit').text('Simpan Perawatan');
        $('#mt_cancel_edit').hide();
        $('#mt_proof_group').hide();
        if (window.bclDropzoneReset) window.bclDropzoneReset('#mt_zone');
    }

    function renderMaint(data) {
        current = data;
        $('#mt_title').text('· ' + data.inv_number + ' · ' + data.name);
        $('#mt_info').html(
            '<dt>Lokasi</dt><dd>' + (data.room_name ? esc(data.room_name) : 'Umum') + '</dd>' +
            '<dt>Perawatan rutin</dt><dd>' + esc(data.routine || 'Tidak') + '</dd>' +
            '<dt>Terakhir dirawat</dt><dd>' + (data.last_maint ? fmtDate(data.last_maint) : 'Belum pernah') + '</dd>' +
            (data.next_due ? '<dt>Berikutnya</dt><dd>' + fmtDate(data.next_due) + (data.next_due < cfg.today ? ' <span class="bcl-pill is-bad">terlambat</span>' : '') + '</dd>' : '')
        );
        $('#mt_history').html((data.history || []).length ? data.history.map(function (m) {
            return '<tr><td class="text-nowrap">' + fmtDate(m.tanggal) + '</td><td>' + esc(m.description) + '</td><td>' + esc(m.vendor_name || '-') + '</td>' +
                '<td class="text-right">' + (m.cost > 0 ? rp(m.cost) + (m.metode_bayar ? '<div class="small text-muted">' + esc(m.metode_bayar) + '</div>' : '') : '<span class="text-muted">-</span>') + '</td>' +
                '<td class="text-right text-nowrap"><button type="button" class="btn btn-xs btn-secondary js-maint-edit" data-id="' + m.id + '">Koreksi</button>' +
                (canSupervise ? ' <button type="button" class="btn btn-xs btn-danger js-maint-delete" data-id="' + m.id + '">Hapus</button>' : '') + '</td></tr>';
        }).join('') : '<tr><td colspan="5" class="text-muted">Belum ada riwayat perawatan.</td></tr>');
    }

    // The form is usable right away (inv_number comes from the clicked row); details and history load
    // alongside without touching what the user already typed.
    function openMaint(inv) {
        current = { inv_number: String(inv), history: [] };
        resetMaintForm();
        $('#mt_title').text('· ' + inv);
        $('#mt_history').html('<tr><td colspan="5" class="text-muted">Memuat...</td></tr>');
        $('#mt_info').empty();
        $('#md_maint').modal('show');
        $.getJSON(url('show', inv), { id: inv })
            .done(renderMaint)
            .fail(function (xhr) {
                $('#mt_history').html('<tr><td colspan="5" class="text-danger">Riwayat gagal dimuat. Perawatan tetap bisa dicatat.</td></tr>');
                toast(ajaxError(xhr, 'Gagal memuat detail inventaris.'), 'error');
            });
    }

    $(document).on('click', '.js-maint, .js-history', function (e) {
        e.preventDefault();
        openMaint($(this).data('inv'));
    });

    $('#mt_cost').on('input', function () { $('#mt_proof_group').toggle(digits(this.value) > 0); });
    $('#mt_cancel_edit').on('click', function (e) { e.preventDefault(); resetMaintForm(); });

    $(document).on('click', '.js-maint-edit', function () {
        var m = (current.history || []).find(function (h) { return String(h.id) === String($(this).data('id')); }, this);
        if (!m) return;
        $('#mt_id').val(m.id);
        $('#mt_date').val(String(m.tanggal).slice(0, 10));
        $('#mt_desc').val(m.description);
        $('#mt_cost').val(m.cost ? $.number(m.cost, 0, ',', '.') : '');
        $('#mt_vendor').val(m.vendor_name || '');
        $('#mt_method').val(m.metode_bayar || 'Tunai');
        $('#mt_proof_group').toggle(m.cost > 0);
        $('#mt_form_title').text('Koreksi catatan ' + fmtDate(m.tanggal));
        $('#mt_submit').text('Simpan Koreksi');
        $('#mt_cancel_edit').show();
        $('#mt_desc').trigger('focus');
    });

    $('#form_maint').on('submit', function (e) {
        e.preventDefault();
        var editing = !!$('#mt_id').val();
        if (!$('#mt_inv').val() && current) $('#mt_inv').val(current.inv_number);
        $('#mt_cost').val(digits($('#mt_cost').val()) || '');
        var $btn = $('#mt_submit').prop('disabled', true);
        $.ajax({ url: editing ? cfg.urls.maintUpdate : cfg.urls.maintStore, type: 'POST', data: new FormData(this), processData: false, contentType: false, headers: { Accept: 'application/json' } })
            .done(function (res) {
                toast(res.message);
                refresh();
                resetMaintForm();
                $.getJSON(url('show', current.inv_number), { id: current.inv_number }).done(renderMaint);
            })
            .fail(function (xhr) { toast(ajaxError(xhr, 'Gagal menyimpan perawatan.'), 'error'); })
            .always(function () { $btn.prop('disabled', false); });
    });

    $(document).on('click', '.js-maint-delete', function () {
        var id = $(this).data('id');
        $.confirm({
            title: 'Hapus catatan perawatan?',
            content: 'Pengeluarannya (jika ada biaya) ikut dihapus.',
            buttons: {
                confirm: {
                    text: 'Hapus', btnClass: 'btn-red', action: function () {
                        $.ajax({ url: cfg.urls.maintDelete, type: 'POST', data: { id: id }, headers: { Accept: 'application/json' } })
                            .done(function (res) {
                                toast(res.message);
                                refresh();
                                $.getJSON(url('show', current.inv_number), { id: current.inv_number }).done(renderMaint);
                            })
                            .fail(function (xhr) { toast(ajaxError(xhr), 'error'); });
                    },
                },
                cancel: { text: 'Batal' },
            },
        });
    });

    loadStats();
})(jQuery);
