/*
 * Kos BCL - Daftar Kamar. The first payload is embedded in the page (window.BCL_ROOMS.initial);
 * form data, unpaid bills and extra beds are fetched only when a dialog needs them.
 */
(function ($) {
    'use strict';

    var cfg = window.BCL_ROOMS;
    var state = {
        data: cfg.initial,
        filter: '',
        query: '',
        openRoomId: null,
        formData: null,
        unpaid: null,
    };

    // ------------------------------------------------------------ helpers

    function esc(value) {
        return $('<div>').text(value == null ? '' : value).html();
    }

    function url(name, id) {
        return cfg.urls[name].replace(':id', encodeURIComponent(id));
    }

    function rp(value) {
        return 'Rp ' + $.number(Math.round(value || 0), 0, ',', '.');
    }

    function digits(value) {
        return parseFloat(String(value == null ? '' : value).replace(/[^0-9]/g, '')) || 0;
    }

    var MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    function fmtDate(value) {
        if (!value) return '-';
        var m = moment(value, 'YYYY-MM-DD');
        return m.isValid() ? m.date() + ' ' + MONTHS[m.month()] + ' ' + m.year() : '-';
    }

    function icon(name) {
        return '<i data-feather="' + name + '"></i>';
    }

    function feather() {
        if (window.feather) window.feather.replace();
    }

    function toast(message, type) {
        if (window.bclToast) window.bclToast(message, type);
    }

    function ajaxError(xhr, fallback) {
        var json = xhr && xhr.responseJSON;
        if (json && json.errors) return Object.values(json.errors).flat().join(' ');
        return (json && json.message) || fallback || 'Terjadi kesalahan.';
    }

    function roomById(id) {
        return (state.data.rooms || []).find(function (room) { return String(room.id) === String(id); });
    }

    function setMoney($input, value) {
        $input.val(value ? $.number(Math.round(value), 0, ',', '.') : (value === 0 ? '0' : ''));
    }

    function unitsToMoment(unit) {
        return { hari: 'days', minggu: 'weeks', bulan: 'months', tahun: 'years' }[String(unit || '').toLowerCase()] || 'days';
    }

    // Live thousand separators on money inputs; digits only on submit.
    $(document).on('input', '.js-money', function () {
        var raw = digits(this.value);
        this.value = raw ? $.number(raw, 0, ',', '.') : '';
    });
    $(document).on('submit', 'form', function () {
        $(this).find('.js-money').each(function () { this.value = digits(this.value); });
    });

    // ------------------------------------------------------------ data loading

    function reloadData() {
        return $.getJSON(cfg.urls.data).done(function (payload) {
            state.data = payload;
            state.unpaid = null;
            render();
            if (state.openRoomId) renderRoomPanel(state.openRoomId);
        });
    }

    function loadFormData() {
        if (!state.formData) {
            state.formData = $.getJSON(cfg.urls.formData).fail(function () {
                state.formData = null;
                toast('Gagal memuat data form.', 'error');
            });
        }
        return state.formData;
    }

    function loadUnpaid() {
        if (!state.unpaid) {
            state.unpaid = $.getJSON(cfg.urls.unpaid).fail(function () { state.unpaid = null; });
        }
        return state.unpaid;
    }

    // ------------------------------------------------------------ main render

    function matchesFilter(room) {
        switch (state.filter) {
            case 'occupied': return room.is_occupied;
            case 'vacant': return room.status === 'vacant';
            case 'booked': return room.status === 'booked';
            case 'expiring': return room.is_expiring && !room.is_extended;
            case 'unpaid': return room.kurang > 0;
            case 'overdue': return room.is_overdue;
            default: return true;
        }
    }

    function matchesQuery(room) {
        if (!state.query) return true;
        var haystack = [room.room_name, room.nama, room.category_name, room.notes, room.trans_id]
            .concat((room.bookings || []).map(function (b) { return b.renter; }))
            .join(' ').toLowerCase();
        return state.query.split(/\s+/).every(function (word) { return haystack.indexOf(word) !== -1; });
    }

    function roomCard(room) {
        var classes = ['bcl-room', 'is-' + room.status];
        var who;
        var meta = '';
        var flags = [];

        if (room.is_overdue) {
            who = '<div class="bcl-room__who" title="' + esc(room.nama) + '">' + esc(room.nama || '-') + '</div>';
            meta = room.days_over > 0 ? 'Habis ' + fmtDate(room.tgl_selesai) + ' · lewat ' + room.days_over + ' hari' : 'Habis hari ini (' + fmtDate(room.tgl_selesai) + ')';
            flags.push('<span class="bcl-pill is-bad">' + icon('alert-triangle') + 'Belum perpanjang</span>');
            if (room.kurang > 0) flags.push('<span class="bcl-pill is-bad">Kurang ' + rp(room.kurang) + '</span>');
        } else if (room.is_occupied) {
            if (room.is_expiring && !room.is_extended) classes.push('is-expiring');
            who = '<div class="bcl-room__who" title="' + esc(room.nama) + '">' + esc(room.nama || '-') + '</div>';
            var left = room.days_left;
            meta = 's/d ' + fmtDate(room.tgl_selesai) + (left !== null ? ' · ' + (left <= 0 ? 'hari ini' : left + ' hari lagi') : '');
            if (room.is_extended) flags.push('<span class="bcl-pill is-good">' + icon('repeat') + 'Diperpanjang</span>');
            else if (room.is_expiring) flags.push('<span class="bcl-pill is-warn">' + icon('clock') + 'Segera habis</span>');
            if (room.kurang > 0) flags.push('<span class="bcl-pill is-bad">Kurang ' + rp(room.kurang) + '</span>');
        } else if (room.status === 'booked') {
            who = '<div class="bcl-room__who is-empty">Kosong</div>';
            meta = 'Booking: ' + esc(room.next_booking_renter || '-') + ' · ' + fmtDate(room.next_booking_start);
        } else {
            who = '<div class="bcl-room__who is-empty">Kosong</div>';
            meta = 'Siap disewakan';
        }
        if (room.booking_count > 0 && room.is_occupied) flags.push('<span class="bcl-pill is-info">' + room.booking_count + ' booking</span>');
        if (room.extra_bed_count > 0) flags.push('<span class="bcl-pill" title="' + esc((room.extra_bed_asset_codes || []).join(', ')) + '">' + icon('layers') + room.extra_bed_count + ' EB</span>');

        return '<button type="button" class="' + classes.join(' ') + '" data-room="' + room.id + '">' +
            '<div class="bcl-room__top"><span class="bcl-room__name">' + esc(room.room_name) + '</span>' + (room.category_name ? '<span class="bcl-room__cat" title="' + esc(room.category_name) + '">' + esc(room.category_name) + '</span>' : '') + '</div>' +
            who +
            '<div class="bcl-room__meta">' + meta + '</div>' +
            (flags.length ? '<div class="bcl-room__flags">' + flags.join('') + '</div>' : '') +
            '</button>';
    }

    function renderRooms() {
        var rooms = (state.data.rooms || []).filter(function (room) { return matchesFilter(room) && matchesQuery(room); });
        var floors = {};
        rooms.forEach(function (room) {
            var key = room.floor == null ? 'Tanpa lantai' : 'Lantai ' + room.floor;
            (floors[key] = floors[key] || []).push(room);
        });

        var keys = Object.keys(floors).sort(function (a, b) { return a.localeCompare(b, undefined, { numeric: true }); });
        var html = keys.map(function (key) {
            var list = floors[key].sort(function (a, b) { return String(a.room_name).localeCompare(String(b.room_name), undefined, { numeric: true }); });
            return '<section class="bcl-floor"><div class="bcl-floor__head">' + esc(key) + ' · ' + list.length + ' kamar</div>' +
                '<div class="bcl-rooms">' + list.map(roomCard).join('') + '</div></section>';
        }).join('');

        $('#rooms_sections').html(html || '<div class="bcl-empty">Tidak ada kamar yang cocok dengan filter/pencarian.</div>');
        var total = (state.data.rooms || []).length;
        $('#filter_note').text(state.filter || state.query ? 'Menampilkan ' + rooms.length + ' dari ' + total + ' kamar' : '');
        feather();
    }

    function render() {
        var stats = state.data.stats || {};
        var summary = state.data.summary || {};

        $('[data-stat]').each(function () { $(this).text(stats[$(this).data('stat')] || 0); });
        $('.bcl-kpi[data-filter]').each(function () { $(this).toggleClass('is-active', String($(this).data('filter')) === state.filter); });
        $('#sum_occupancy').text((summary.occupancy || 0) + '%');
        $('#sum_month').text(summary.month ? '· ' + summary.month : '');
        $('#sum_income').text(rp(summary.income));
        $('#sum_expense').text(rp(summary.expense));
        $('#sum_receivable').text(rp(summary.receivable));
        $('#unpaid_count').text(summary.unpaid_count || '').attr('data-count', summary.unpaid_count || 0);
        var renewalCount = (stats.expiring || 0) + (stats.overdue || 0);
        $('#renewal_count').text(renewalCount || '').attr('data-count', renewalCount);
        $('#extra_bed_badge').text((summary.extra_bed_available || 0) + '/' + (summary.extra_bed_total || 0)).attr('title', (summary.extra_bed_available || 0) + ' dari ' + (summary.extra_bed_total || 0) + ' unit tersedia');
        renderRooms();
    }

    $(document).on('click', '.bcl-kpi[data-filter]', function () {
        var filter = String($(this).data('filter'));
        state.filter = state.filter === filter ? '' : filter;
        render();
    });
    $('#kpi_receivable, #bt_unpaid').on('click', openUnpaidList);

    var searchTimer;
    $('#room_search').on('input', function () {
        var value = this.value;
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            state.query = value.trim().toLowerCase();
            renderRooms();
        }, 120);
    });
    $(document).on('keydown', function (e) {
        if (e.key === '/' && !$(e.target).is('input, textarea, select')) {
            e.preventDefault();
            $('#room_search').focus();
        }
    });

    // ------------------------------------------------------------ room panel

    function action(name, iconName, label, hint, extraClass, attrs) {
        return '<button type="button" class="bcl-action ' + (extraClass || '') + '" data-act="' + name + '"' + (attrs || '') + '>' + icon(iconName) +
            '<span>' + label + (hint ? '<small>' + hint + '</small>' : '') + '</span></button>';
    }

    function renderRoomPanel(id) {
        var room = roomById(id);
        if (!room) {
            $('#md_room').modal('hide');
            return;
        }
        var statusPill = room.is_overdue
            ? '<span class="bcl-pill is-bad">Dihuni · lewat masa sewa</span>'
            : room.is_occupied
            ? '<span class="bcl-pill is-good">Terisi</span>'
            : (room.status === 'booked' ? '<span class="bcl-pill is-info">Kosong · ada booking</span>' : '<span class="bcl-pill">Kosong</span>');
        $('#room_title').html(esc(room.room_name) + ' ' + statusPill);
        $('#room_sub').text([room.category_name, room.floor ? 'Lantai ' + room.floor : null].filter(Boolean).join(' · '));

        var html = '';
        if (room.is_occupied) {
            var wa = room.phone ? String(room.phone).replace(/\D/g, '').replace(/^0/, '62') : '';
            var leftPill = room.is_overdue
                ? '<span class="bcl-pill is-bad">Habis ' + room.days_over + ' hari lalu, belum perpanjang</span>'
                : room.is_extended
                ? '<span class="bcl-pill is-good">Sudah diperpanjang</span>'
                : (room.is_expiring ? '<span class="bcl-pill is-warn">' + (room.days_left <= 0 ? 'Habis hari ini' : room.days_left + ' hari lagi') + '</span>' : '<span class="bcl-pill">' + room.days_left + ' hari lagi</span>');

            html += '<div class="bcl-section"><div class="bcl-section__title">Penyewa</div><dl class="bcl-kv">' +
                '<dt>Nama</dt><dd><a class="bcl-link" data-act="renter">' + esc(room.nama) + '</a></dd>' +
                '<dt>Telepon</dt><dd>' + (room.phone ? esc(room.phone) + ' · <a href="https://wa.me/' + wa + '" target="_blank" rel="noopener" class="bcl-link">WhatsApp</a>' : '-') + '</dd>' +
                '<dt>Paket</dt><dd>' + esc((room.lama_sewa || '') + ' ' + (room.jangka_sewa || '')) + (room.identity === 'Pindah Kamar' ? ' <span class="bcl-pill is-accent">pindahan</span>' : '') + '</dd>' +
                '<dt>Periode</dt><dd>' + fmtDate(room.tgl_mulai) + ' – ' + fmtDate(room.tgl_selesai) + '<div class="mt-1">' + leftPill + '</div></dd>' +
                (room.deposit > 0 ? '<dt>Deposit</dt><dd>' + rp(room.deposit) + '</dd>' : '') +
                (room.catatan ? '<dt>Catatan</dt><dd>' + esc(room.catatan) + '</dd>' : '') +
                '</dl></div>';

            html += '<div class="bcl-section"><div class="bcl-section__title">Pembayaran <span class="text-muted font-weight-normal">' + esc(room.trans_id) + '</span></div>' +
                '<div class="bcl-money"><div><span>Tagihan</span><strong>' + rp(room.harga) + '</strong></div>' +
                '<div><span>Dibayar</span><strong>' + rp(room.paid) + '</strong></div>' +
                '<div class="' + (room.kurang > 0 ? 'is-bad' : '') + '"><span>Kurang</span><strong>' + (room.kurang > 0 ? rp(room.kurang) : 'Lunas') + '</strong></div></div></div>';

            var acts = '';
            if (room.is_overdue) {
                html += '<div class="bcl-section"><div class="bcl-hint is-warn">Masa sewa sudah habis tetapi penghuni belum perpanjang atau dikonfirmasi keluar. Kamar tidak bisa disewakan ke orang lain sampai salah satu dipilih.</div></div>';
                acts += action('extend', 'repeat', 'Perpanjang', 'Mulai ' + fmtDate(room.tgl_selesai) + ', boleh bayar nanti', 'is-primary is-wide');
                if (room.kurang > 0) acts += action('pay', 'credit-card', 'Terima Pembayaran', 'Sisa ' + rp(room.kurang));
                acts += action('process_checkout', 'log-out', 'Sudah Keluar', 'Tagih hari lewat, checklist, jaminan', 'is-danger');
                acts += action('guarantee', 'shield', 'Uang Jaminan', room.jaminan > 0 ? 'Saldo ' + rp(room.jaminan) : 'Belum ada');
                acts += '<a class="bcl-action" href="' + url('print', room.trans_id) + '" target="_blank" rel="noopener">' + icon('printer') + '<span>Cetak Nota</span></a>';
                html += '<div class="bcl-section"><div class="bcl-section__title">Aksi</div><div class="bcl-actions">' + acts + '</div></div>';
                acts = null;
            }
            if (acts !== null && room.kurang > 0) acts += action('pay', 'credit-card', 'Terima Pembayaran', 'Sisa ' + rp(room.kurang), 'is-primary is-wide');
            if (acts !== null) {
            if (!room.is_extended) acts += action('extend', 'repeat', 'Perpanjang', 'Mulai ' + fmtDate(room.tgl_selesai), room.is_expiring && !(room.kurang > 0) ? 'is-primary' : '');
            acts += action('extra', 'package', 'Tambahan Sewa', 'Extra bed, dll.');
            acts += action('move', 'log-in', 'Pindah Kamar', 'Upgrade / downgrade');
            if (!room.has_checkin) acts += action('checkin', 'clipboard', 'Check-in Kamar', 'Belum dicatat');
            acts += action('guarantee', 'shield', 'Uang Jaminan', room.jaminan > 0 ? 'Saldo ' + rp(room.jaminan) : 'Belum ada');
            acts += action('process_checkout', 'log-out', 'Proses Keluar', 'Checklist, jaminan, refund', 'is-danger');
            acts += '<a class="bcl-action" href="' + url('print', room.trans_id) + '" target="_blank" rel="noopener">' + icon('printer') + '<span>Cetak Nota</span></a>';
            html += '<div class="bcl-section"><div class="bcl-section__title">Aksi</div><div class="bcl-actions">' + acts + '</div></div>';
            }
        } else {
            html += '<div class="bcl-section"><div class="bcl-actions">' +
                action('rent', 'key', 'Sewakan Kamar Ini', room.status === 'booked' ? 'Sebelum ' + fmtDate(room.next_booking_start) : 'Pilih penyewa & paket', 'is-primary is-wide') +
                '</div></div>';
        }

        if ((room.bookings || []).length) {
            html += '<div class="bcl-section"><div class="bcl-section__title">Antrian booking</div><ul class="bcl-list">' +
                room.bookings.map(function (b) {
                    return '<li><span>' + esc(b.renter || '-') + '<div class="muted">' + fmtDate(b.start) + ' – ' + fmtDate(b.end) + '</div></span>' +
                        '<span class="text-right"><a class="bcl-link" href="' + url('print', b.trans_id) + '" target="_blank">' + esc(b.trans_id) + '</a>' +
                        (cfg.canSupervise ? '<br><a class="bcl-link text-danger js-cancel-booking" data-trans="' + esc(b.trans_id) + '" data-label="' + esc((b.renter || '') + ' ' + fmtDate(b.start)) + '">Batalkan</a>' : '') + '</span></li>';
                }).join('') + '</ul></div>';
        }

        var side = '';
        if (room.extra_bed_count > 0) {
            side += '<div class="bcl-section"><div class="bcl-section__title">Extra bed</div><div class="bcl-hint">' + room.extra_bed_count + ' unit: ' + esc((room.extra_bed_asset_codes || []).join(', ') || '-') + '</div></div>';
        }

        side += '<div class="bcl-section"><div class="bcl-section__title">Harga paket ' + esc(room.category_name || '') + '</div><ul class="bcl-list" id="room_prices"><li class="muted">Memuat...</li></ul></div>';
        side += '<div class="bcl-section"><div class="bcl-section__title">WiFi <a class="bcl-link" data-act="wifi">Ubah</a></div><div class="bcl-hint" id="room_wifi">Memuat...</div></div>';
        if (room.notes) side += '<div class="bcl-section"><div class="bcl-section__title">Catatan kamar</div><div class="bcl-hint">' + esc(room.notes) + '</div></div>';
        side += '<div class="bcl-section"><div class="bcl-section__title">Riwayat transaksi <a class="bcl-link" data-act="history">Tampilkan</a></div><div id="room_history"></div></div>';

        $('#room_body').html('<div class="bcl-room-grid"><div class="bcl-room-main">' + html + '</div><div class="bcl-room-side">' + side + '</div></div>');
        feather();

        pricelistOf(room.room_category).done(function (list) {
            $('#room_prices').html((list || []).map(function (pl) {
                var bonus = parseInt(pl.bonus_waktu, 10) > 0 ? ' <span class="muted">+ bonus ' + pl.bonus_waktu + ' ' + esc(pl.bonus_sewa || '') + '</span>' : '';
                return '<li><span>' + esc(pl.jangka_waktu + ' ' + pl.jangka_sewa) + bonus + '</span><strong>' + rp(pl.price) + '</strong></li>';
            }).join('') || '<li class="muted">Belum ada harga untuk kategori ini.</li>');
        });
        $.getJSON(url('wifi', room.id)).done(function (res) {
            var wifi = res.wifi;
            $('#room_wifi').html(wifi ? 'SSID <strong>' + esc(wifi.ssid) + '</strong> · Password <strong>' + esc(wifi.password || '-') + '</strong>' : 'Belum ada WiFi aktif.');
        });
    }

    // Packages per room category, fetched once per page visit (room panel + Sewa form share it).
    var pricelistCache = {};
    function pricelistOf(categoryId) {
        if (!pricelistCache[categoryId]) {
            pricelistCache[categoryId] = $.getJSON(url('pricelist', categoryId)).fail(function () { delete pricelistCache[categoryId]; });
        }
        return pricelistCache[categoryId];
    }

    function openRoom(id) {
        state.openRoomId = id;
        renderRoomPanel(id);
        $('#md_room').modal('show');
    }

    $(document).on('click', '.bcl-room', function () { openRoom($(this).data('room')); });
    $('#md_room').on('hidden.bs.modal', function () { state.openRoomId = null; });

    $('#md_room').on('click', '[data-act]', function (e) {
        var room = roomById(state.openRoomId);
        if (!room) return;
        var act = $(this).data('act');
        if (act === 'print') return;
        e.preventDefault();

        switch (act) {
            case 'rent': openSewa({ roomId: room.id, title: 'Sewa Kamar ' + room.room_name }); break;
            case 'extend': openSewa({ roomId: room.id, renterId: room.id_renter, startDate: room.tgl_selesai, title: 'Perpanjang Sewa ' + room.room_name, note: 'Perpanjangan ' + room.trans_id }); break;
            case 'pay': openPay(room.trans_id); break;
            case 'extra': openExtra(room.trans_id); break;
            case 'move': $('#md_room').modal('hide'); window.bclOpenChangeRoom(room.trans_id); break;
            case 'checkout': $('#md_room').modal('hide'); window.bclOpenRefund(room.trans_id, 'Kamar ' + room.room_name); break;
            case 'process_checkout': openCheckout(room); break;
            case 'checkin': openCheckin(room); break;
            case 'guarantee': openGuarantee(room.id_renter, room.nama, room.jaminan); break;
            case 'renter': openRenter(room.id_renter); break;
            case 'wifi': openWifi(room); break;
            case 'history': loadHistory(room); break;
        }
    });
    $('#room_edit_btn').on('click', function () { openRoomForm(state.openRoomId); });

    function loadHistory(room) {
        $('#room_history').html('<div class="bcl-hint">Memuat...</div>');
        $.getJSON(url('history', room.id)).done(function (res) {
            var rows = (res.transactions || []).map(function (t) {
                var total = (t.harga || 0) + (t.extra_total || 0);
                var lunas = (t.paid_total || 0) >= total - 0.5;
                return '<li><span><a class="bcl-link" href="' + url('print', t.trans_id) + '" target="_blank">' + esc(t.trans_id) + '</a> · ' + esc(t.renter_name || '-') +
                    '<div class="muted">' + fmtDate(t.tgl_mulai) + ' – ' + fmtDate(t.tgl_selesai) + (t.bukti_transfer_url ? ' · <a href="' + esc(t.bukti_transfer_url) + '" target="_blank">bukti</a>' : '') + '</div></span>' +
                    '<span class="text-right">' + rp(total) + '<div>' + (lunas ? '<span class="bcl-pill is-good">Lunas</span>' : '<span class="bcl-pill is-bad">Kurang ' + rp(total - t.paid_total) + '</span>') + '</div></span></li>';
            });
            $('#room_history').html(rows.length ? '<ul class="bcl-list">' + rows.join('') + '</ul>' : '<div class="bcl-hint">Belum ada riwayat.</div>');
        });
    }

    // ------------------------------------------------------------ sewa / perpanjang

    function fillSelect($select, items, placeholder) {
        $select.html('<option value="">' + esc(placeholder || '') + '</option>' + items.join('')).trigger('change.select2');
    }

    function prepareSewaOptions(form) {
        fillSelect($('#sewa_renter'), form.renters.map(function (r) {
            return '<option value="' + r.id + '" data-deposit="' + (r.deposit_balance || 0) + '">' + esc(r.nama) + '</option>';
        }));
        fillSelect($('#kamar'), form.base_rooms.map(function (r) {
            var label = r.room_name + ' · ' + (r.category_name || '-') + (r.renter ? ' (terisi: ' + r.renter.nama + ')' : '');
            return '<option value="' + r.id + '" data-category="' + r.room_category + '">' + esc(label) + '</option>';
        }));
    }

    function loadSewaPricelist(categoryId, preferLama, preferJangka) {
        $('#pricelist').html('<option value="">Memuat...</option>').trigger('change.select2');
        updateSewaHint();
        if (!categoryId) return;
        pricelistOf(categoryId).done(function (list) {
            var options = (list || []).map(function (pl) {
                var bonus = parseInt(pl.bonus_waktu, 10) > 0 ? ' + bonus ' + pl.bonus_waktu + ' ' + (pl.bonus_sewa || '') : '';
                return '<option value="' + pl.id + '" data-harga="' + pl.price + '" data-lama="' + pl.jangka_waktu + '" data-jangka="' + esc(pl.jangka_sewa) + '" data-bonus="' + (pl.bonus_waktu || 0) + '" data-bonus-jangka="' + esc(pl.bonus_sewa || '') + '">' +
                    esc(pl.jangka_waktu + ' ' + pl.jangka_sewa + bonus + ' · ' + rp(pl.price)) + '</option>';
            });
            options.push('<option value="custom">Durasi custom (atur tanggal &amp; harga sendiri)…</option>');
            fillSelect($('#pricelist'), options, list && list.length ? 'Pilih paket' : 'Belum ada paket, pakai durasi custom');
            if (preferLama) {
                var $match = $('#pricelist option').filter(function () {
                    return String($(this).data('lama')) === String(preferLama) && String($(this).data('jangka')).toLowerCase() === String(preferJangka || '').toLowerCase();
                }).first();
                if ($match.length) {
                    $('#pricelist').val($match.val()).trigger('change');
                } else if (String(preferJangka || '').toLowerCase() === 'hari') {
                    // Renewing a custom stay: same number of days again.
                    $('#pricelist').val('custom').trigger('change');
                    $('#sewa_custom_days').val(preferLama).trigger('input');
                }
            }
        });
    }

    function openSewa(opts) {
        opts = opts || {};
        $('#md_room').modal('hide');
        state.formData = null; // renters may have been added in another tab
        loadFormData().done(function (form) {
            prepareSewaOptions(form);
            var $form = $('#form_sewa');
            $form[0].reset();
            resetNewRenter();
            if (window.bclDropzoneReset) window.bclDropzoneReset('#bukti_zone');
            $('#sewa_custom').hide().data({ suggested: 0, edited: false }).find('input').val('');
            $('#sewa_custom_breakdown').text('Isi tanggal selesai atau jumlah hari.');
            $('#sewa_title').text(opts.title || 'Sewa Kamar');
            $('#sewa_renter').val(opts.renterId || '').trigger('change');
            $('#kamar').val(opts.roomId || '').trigger('change.select2');
            $('#tgl_masuk').val(opts.startDate || cfg.today);
            $('#tgl_bayar').val(cfg.today);
            $('#catatan').val(opts.note || '');
            $('#nominal').data('price', 0).val('');
            var room = roomById(opts.roomId);
            loadSewaPricelist($('#kamar option:selected').data('category'), opts.renterId && room ? room.lama_sewa : null, room ? room.jangka_sewa : null);
            $('#md_sewa').modal('show');
        });
    }

    // Price to pay = package price minus the special discount.
    function isCustom() {
        return $('#pricelist').val() === 'custom';
    }

    function sewaNetPrice() {
        if (isCustom()) return digits($('#sewa_custom_price').val());
        var list = parseFloat($('#pricelist option:selected').data('harga')) || 0;
        return Math.max(list - digits($('#sewa_diskon').val()), 0);
    }

    function updateSewaHint() {
        var $pl = $('#pricelist option:selected');
        var custom = isCustom();
        var suggested = parseFloat($('#sewa_custom').data('suggested')) || 0;
        var listPrice = custom ? suggested : (parseFloat($pl.data('harga')) || 0);
        var price = sewaNetPrice();
        // Custom stays: a price below the suggestion counts as a discount and needs a reason.
        var discount = custom ? Math.max(suggested - price, 0) : digits($('#sewa_diskon').val());
        $('#sewa_custom').toggle(custom);
        $('#sewa_diskon_col').toggle(!custom);
        $('#sewa_custom_end, #sewa_custom_price').prop('required', custom);
        $('#sewa_diskon_reason').toggle(discount > 0);
        $('#sewa_alasan_diskon').prop('required', discount > 0);
        $('#sewa_price_hint').text(listPrice ? (discount > 0 ? 'Harga paket ' + rp(listPrice) + ' → ' + rp(price) + (discount > listPrice ? ' (diskon melebihi harga!)' : '') : 'Harga paket ' + rp(listPrice)) : '');
        var cash = digits($('#nominal').val());
        var deposit = $('#use_deposit').is(':checked') ? digits($('#deposit_amount').val()) : 0;
        var paid = cash + deposit;
        var $hint = $('#sewa_payment_hint').removeClass('is-good is-warn is-info');

        var start = $('#tgl_masuk').val();
        if (custom) {
            $('#sewa_period_hint').text($('#sewa_custom_end').val() ? 'Selesai ' + fmtDate($('#sewa_custom_end').val()) : '');
        } else if (start && $pl.val()) {
            var end = moment(start).add(parseInt($pl.data('lama'), 10) || 0, unitsToMoment($pl.data('jangka')));
            if (parseInt($pl.data('bonus'), 10) > 0) end.add(parseInt($pl.data('bonus'), 10), unitsToMoment($pl.data('bonus-jangka')));
            $('#sewa_period_hint').text('Selesai ' + fmtDate(end.format('YYYY-MM-DD')));
        } else {
            $('#sewa_period_hint').text('');
        }

        $('#overpay_to_deposit_wrap').toggle(price > 0 && paid > price);
        if (!(price > 0 && paid > price)) $('#overpay_to_deposit').prop('checked', false);
        var method = $('#sewa_method').val() || 'Tunai';
        $('#bukti_transfer_required').toggle((cash + digits($('#sewa_jaminan').val())) > 0 && method !== 'Tunai');

        if (!price) {
            $hint.text('');
        } else if (paid < price) {
            $hint.addClass('is-warn').text('Dibayar ' + rp(paid) + ' dari ' + rp(price) + '. Sisa ' + rp(price - paid) + ' tercatat sebagai Belum Lunas (DP).');
        } else if (paid > price) {
            $hint.addClass('is-info').text('Kelebihan ' + rp(paid - price) + '. Centang "Simpan kelebihan ke deposit" agar bisa disimpan.');
        } else {
            $hint.addClass('is-good').text('Lunas ' + rp(price) + '.');
        }
    }

    $('#kamar').on('change', function () {
        if ($(this).data('select2')) loadSewaPricelist($(this).find(':selected').data('category'));
    });
    $('#pricelist').on('change', function () {
        $('#sewa_diskon, #sewa_alasan_diskon').val('');
        if (isCustom()) {
            $('#sewa_custom').show();
            refreshCustomSuggestion();
        }
        var price = sewaNetPrice();
        $('#nominal').data('price', price);
        setMoney($('#nominal'), price);
        $('#use_deposit').prop('checked', false);
        $('#deposit_amount').hide().val(0);
        updateSewaHint();
    });
    $('#sewa_renter').on('change', function () {
        var deposit = parseFloat($(this).find(':selected').data('deposit')) || 0;
        $('#renter_deposit_display').text(rp(deposit));
        $('#use_deposit').prop({ checked: false, disabled: deposit <= 0 });
        $('#deposit_amount').hide().val(0);
        $('#topup_renter_id').val($(this).val());
        updateSewaHint();
    });
    $('#use_deposit').on('change', function () {
        var deposit = parseFloat($('#sewa_renter option:selected').data('deposit')) || 0;
        var price = sewaNetPrice();
        var use = Math.min(deposit, price || deposit);
        $('#deposit_amount').toggle(this.checked);
        setMoney($('#deposit_amount'), this.checked ? use : 0);
        if (price) setMoney($('#nominal'), this.checked ? Math.max(price - use, 0) : price);
        updateSewaHint();
    });
    $('#nominal, #deposit_amount, #tgl_masuk').on('input change', updateSewaHint);
    $('#btn_topup_deposit').on('click', function () {
        if (!$('#sewa_renter').val()) {
            toast('Pilih penyewa dulu.', 'error');
            return;
        }
        $('#topup_renter_id').val($('#sewa_renter').val());
        $('#md_topup_deposit').modal('show');
    });
    $('#bt_sewa').on('click', function () { openSewa(); });

    // ------------------------------------------------------------ durasi custom

    var suggestTimer = null;

    function refreshCustomSuggestion() {
        clearTimeout(suggestTimer);
        var start = $('#tgl_masuk').val();
        var end = $('#sewa_custom_end').val();
        var room = $('#kamar').val();
        if (!isCustom() || !start || !end || !room || end <= start) {
            $('#sewa_custom').data('suggested', 0);
            $('#sewa_custom_breakdown').text(end && start && end <= start ? 'Tanggal selesai harus setelah tanggal masuk.' : 'Isi tanggal selesai atau jumlah hari.');
            updateSewaHint();
            return;
        }
        suggestTimer = setTimeout(function () {
            $.getJSON(cfg.urls.priceSuggestion, { room: room, start: start, end: end }).done(function (res) {
                var $box = $('#sewa_custom');
                $box.data('suggested', res.total || 0);
                $('#sewa_custom_breakdown').html('<strong>' + res.days + ' hari</strong> · harga saran <strong>' + rp(res.total) + '</strong>' +
                    ((res.parts || []).length ? ' = ' + res.parts.map(function (p) { return esc(p.label) + ' (' + rp(p.amount) + ')'; }).join(' + ') : '') +
                    (res.total ? '' : ' · Daftar Harga kategori ini kosong, isi harga manual.'));
                if (!$box.data('edited')) setMoney($('#sewa_custom_price'), res.total || '');
                applyCustomPrice();
            });
        }, 200);
    }

    function applyCustomPrice() {
        var price = sewaNetPrice();
        $('#nominal').data('price', price);
        setMoney($('#nominal'), price);
        $('#use_deposit').prop('checked', false);
        $('#deposit_amount').hide().val(0);
        updateSewaHint();
    }

    $('#sewa_custom_end').on('change', function () {
        var start = $('#tgl_masuk').val();
        $('#sewa_custom_days').val(start && this.value ? Math.max(moment(this.value).diff(moment(start), 'days'), 0) || '' : '');
        refreshCustomSuggestion();
    });
    $('#sewa_custom_days').on('input', function () {
        var start = $('#tgl_masuk').val();
        var days = parseInt(this.value, 10) || 0;
        $('#sewa_custom_end').val(start && days > 0 ? moment(start).add(days, 'days').format('YYYY-MM-DD') : '');
        refreshCustomSuggestion();
    });
    $('#tgl_masuk').on('change', function () {
        if (isCustom() && $('#sewa_custom_days').val()) $('#sewa_custom_days').trigger('input');
    });
    $('#sewa_custom_price').on('input', function () {
        $('#sewa_custom').data('edited', true);
        applyCustomPrice();
    });
    $('#sewa_custom_use').on('click', function (e) {
        e.preventDefault();
        $('#sewa_custom').data('edited', false);
        setMoney($('#sewa_custom_price'), $('#sewa_custom').data('suggested') || '');
        applyCustomPrice();
    });
    // ------------------------------------------------------------ penyewa baru (inline in the Sewa form)

    function resetNewRenter() {
        $('#new_renter_panel').hide().find('input').val('');
        $('#nr_identitas').val('KTP');
        if (window.bclDropzoneReset) window.bclDropzoneReset('#nr_ktp_zone');
    }

    $('#btn_new_renter').on('click', function (e) {
        e.preventDefault();
        $('#new_renter_panel').slideDown(120, function () { $('#nr_nama').trigger('focus'); });
    });
    $('#btn_new_renter_cancel').on('click', function (e) {
        e.preventDefault();
        resetNewRenter();
    });
    // Enter inside the panel saves the renter instead of submitting the whole rent form.
    $('#new_renter_panel').on('keydown', 'input', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $('#btn_new_renter_save').trigger('click');
        }
    });

    $('#btn_new_renter_save').on('click', function () {
        var nama = $.trim($('#nr_nama').val());
        var phone = $.trim($('#nr_phone').val());
        if (!nama || !phone) {
            toast('Nama dan No. HP wajib diisi.', 'error');
            $(!nama ? '#nr_nama' : '#nr_phone').trigger('focus');
            return;
        }
        var data = new FormData();
        data.append('nama', nama);
        data.append('phone', phone);
        data.append('phone2', $('#nr_phone2').val());
        data.append('identitas', $('#nr_identitas').val());
        data.append('nomor_identitas', $('#nr_no_identitas').val());
        data.append('alamat', $('#nr_alamat').val());
        var ktp = $('#nr_ktp')[0].files[0];
        if (ktp) data.append('img_identitas', ktp);

        var $btn = $(this).prop('disabled', true).text('Menyimpan...');
        $.ajax({ url: cfg.urls.renterQuickStore, type: 'POST', data: data, processData: false, contentType: false, headers: { Accept: 'application/json' } })
            .done(function (res) {
                var r = res.renter;
                $('#sewa_renter').append('<option value="' + r.id + '" data-deposit="0">' + esc(r.nama) + '</option>').val(r.id).trigger('change');
                state.formData = null;
                resetNewRenter();
                toast(res.message);
            })
            .fail(function (xhr) { toast(ajaxError(xhr, 'Gagal menyimpan penyewa.'), 'error'); })
            .always(function () { $btn.prop('disabled', false).text('Simpan Penyewa'); });
    });

    // ------------------------------------------------------------ tambahan sewa

    function openExtra(transId) {
        $('#md_room').modal('hide');
        loadFormData().done(function (form) {
            fillSelect($('#trans_id'), form.base_rooms.filter(function (r) { return r.renter; }).map(function (r) {
                return '<option value="' + esc(r.renter.trans_id) + '">' + esc(r.room_name + ' · ' + r.renter.nama) + '</option>';
            }), 'Pilih kamar/penyewa');
            fillSelect($('#pricelist_extra'), ['<option value="custom">+ Tagihan lain (isi nama & nominal sendiri)</option>'].concat(form.extra_pricelist.map(function (item) {
                return '<option value="' + item.id + '" data-unit="' + esc(item.jangka_sewa) + '" data-tracked="' + (item.tracked_inventory ? 1 : 0) + '" data-harga="' + item.harga + '">' +
                    esc(item.nama + ' · ' + rp(item.harga) + '/' + (item.jangka_sewa || '-')) + '</option>';
            })), 'Pilih item');
            $('#extra_custom').hide().find('input').val('');
            $('#trans_id').val(transId || '').trigger('change');
            $('#tgl_sewa').val(cfg.today);
            $('#jml_item, #lama_sewa').val(1);
            $('#extra_unit').text('');
            $('#extra_hint').text('').removeClass('is-good is-warn');
            $('#md_extra').modal('show');
        });
    }

    function updateExtraHint() {
        var $item = $('#pricelist_extra option:selected');
        var qty = parseInt($('#jml_item').val(), 10) || 0;
        var lama = parseInt($('#lama_sewa').val(), 10) || 0;
        $('#extra_unit').text($item.data('unit') ? '(' + $item.data('unit') + ')' : '');
        if (!$item.val()) {
            $('#extra_hint').text('');
            return;
        }
        var custom = $item.val() === 'custom';
        $('#extra_custom').toggle(custom);
        $('#extra_custom_name, #extra_custom_price').prop('required', custom);
        $('#lama_sewa').closest('.form-group').toggle(!custom);
        if (custom) {
            $('#lama_sewa').val(1);
            $('#extra_unit').text('');
            $('#extra_hint').removeClass('is-good is-warn').text('Total ' + rp(digits($('#extra_custom_price').val()) * qty) + ' (tercatat Belum Lunas sampai dibayar).');
            return;
        }
        var total = (parseFloat($item.data('harga')) || 0) * qty * lama;
        var $hint = $('#extra_hint').removeClass('is-good is-warn').text('Total ' + rp(total) + ' (tercatat Belum Lunas sampai dibayar).');
        if (String($item.data('tracked')) === '1' && $('#tgl_sewa').val() && lama) {
            $.getJSON(cfg.urls.availability, { pricelist: $item.val(), tgl_sewa: $('#tgl_sewa').val(), lama_sewa: lama }).done(function (res) {
                var ok = (res.available_count || 0) >= qty;
                $hint.addClass(ok ? 'is-good' : 'is-warn').text('Total ' + rp(total) + '. Extra bed tersedia ' + (res.available_count || 0) + ' dari ' + (res.total_count || 0) + ' unit' +
                    ((res.available_asset_codes || []).length ? ' (' + res.available_asset_codes.join(', ') + ')' : '') + '.');
            });
        }
    }
    $('#pricelist_extra, #jml_item, #lama_sewa, #tgl_sewa, #extra_custom_price').on('change input', updateExtraHint);
    $('#bt_extra').on('click', function () { openExtra(''); });

    // ------------------------------------------------------------ pembayaran

    function openPay(docId) {
        $('#md_room').modal('hide');
        loadUnpaid().done(function (res) {
            var items = res.items || [];
            fillSelect($('#pay_doc'), items.map(function (item) {
                return '<option value="' + esc(item.transaksi) + '" data-section="' + esc(item.section) + '" data-kurang="' + item.kurang + '" data-deposit="' + (item.deposit || 0) + '">' +
                    esc(item.nomor + ' · ' + (item.renter_name || '-') + ' · ' + item.tipe + ' · kurang ' + rp(item.kurang)) + '</option>';
            }), 'Pilih tagihan');
            $('#pay_doc').val(docId || '').trigger('change');
            $('#pay_date').val(cfg.today);
            $('#pay_note').val('');
            $('#pay_method').val('Tunai');
            if (window.bclDropzoneReset) window.bclDropzoneReset('#pay_zone');
            $('#md_unpaid').modal('hide');
            $('#md_pay').modal('show');
        });
    }

    function updatePayHint() {
        var $opt = $('#pay_doc option:selected');
        var kurang = parseFloat($opt.data('kurang')) || 0;
        var deposit = $('#pay_use_deposit').is(':checked') ? Math.min(parseFloat($opt.data('deposit')) || 0, kurang) : 0;
        var cash = digits($('#pay_nominal').val());
        $('#pay_deposit_amount').val(deposit);
        var rest = kurang - cash - deposit;
        var $hint = $('#pay_hint').removeClass('is-good is-warn');
        if (!$opt.val()) return $hint.text('');
        if (rest > 0.5) $hint.addClass('is-warn').text('Sisa tagihan setelah pembayaran: ' + rp(rest));
        else if (rest < -0.5) $hint.addClass('is-warn').text('Melebihi tagihan ' + rp(-rest) + '. Kurangi nominal.');
        else $hint.addClass('is-good').text('Tagihan lunas.');
    }

    $('#pay_doc').on('change', function () {
        var $opt = $(this).find(':selected');
        var kurang = parseFloat($opt.data('kurang')) || 0;
        var deposit = parseFloat($opt.data('deposit')) || 0;
        $('#pay_section').val($opt.data('section') || '');
        setMoney($('#pay_nominal'), kurang || '');
        $('#pay_use_deposit').prop('checked', false);
        $('#pay_deposit_row').toggle(deposit > 0);
        $('#pay_deposit_balance').text(rp(deposit));
        updatePayHint();
    });
    $('#pay_use_deposit').on('change', function () {
        var $opt = $('#pay_doc option:selected');
        var kurang = parseFloat($opt.data('kurang')) || 0;
        var use = this.checked ? Math.min(parseFloat($opt.data('deposit')) || 0, kurang) : 0;
        setMoney($('#pay_nominal'), Math.max(kurang - use, 0));
        updatePayHint();
    });
    $('#pay_nominal').on('input', updatePayHint);

    $('#form_pay').on('submit', function (e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]').prop('disabled', true);
        $form.find('.js-money').each(function () { this.value = digits(this.value); });
        $.ajax({ url: $form.attr('action'), type: 'POST', data: new FormData(this), processData: false, contentType: false, headers: { Accept: 'application/json' } })
            .done(function (res) {
                $('#md_pay').modal('hide');
                if (window.bclDropzoneReset) window.bclDropzoneReset('#pay_zone');
                toast(res.message || 'Pembayaran tersimpan.');
                reloadData();
            })
            .fail(function (xhr) { toast(ajaxError(xhr, 'Gagal menyimpan pembayaran.'), 'error'); })
            .always(function () { $btn.prop('disabled', false); });
    });

    function openUnpaidList() {
        $('#unpaid_body').html('<tr><td colspan="6" class="text-center text-muted py-3">Memuat...</td></tr>');
        $('#md_unpaid').modal('show');
        loadUnpaid().done(function (res) {
            var items = res.items || [];
            $('#unpaid_body').html(items.length ? items.map(function (item) {
                return '<tr><td class="text-nowrap">' + fmtDate(item.tanggal) + '</td><td>' + esc(item.nomor) + '<div class="small text-muted">' + esc(item.tipe) + '</div></td>' +
                    '<td>' + esc(item.renter_name || '-') + '<div class="small text-muted">' + esc(item.catatan) + '</div></td>' +
                    '<td class="text-right">' + rp(item.jumlah) + '</td><td class="text-right text-danger font-weight-bold">' + rp(item.kurang) + '</td>' +
                    '<td class="text-right"><button type="button" class="btn btn-sm btn-primary js-pay" data-doc="' + esc(item.transaksi) + '">Terima</button></td></tr>';
            }).join('') : '<tr><td colspan="6" class="text-center text-muted py-3">Tidak ada tagihan belum lunas.</td></tr>');
        });
    }
    $(document).on('click', '.js-pay', function () { openPay($(this).data('doc')); });

    // ------------------------------------------------------------ extra bed

    $('#bt_extra_bed').on('click', function () {
        $('#md_extra_bed').modal('show');
        $.getJSON(cfg.urls.extraBed).done(function (res) {
            $('#extra_bed_assets').html((res.assets || []).map(function (a) {
                return '<li><span><strong>' + esc(a.asset_code) + '</strong><div class="muted">' + esc(a.room_name ? a.room_name + ' · ' + (a.renter_name || '') : '-') + '</div></span>' +
                    '<span class="bcl-pill ' + (a.status === 'Dipakai' ? 'is-warn' : 'is-good') + '">' + esc(a.status) + '</span></li>';
            }).join('') || '<li class="muted">Belum ada unit.</li>');
            $('#extra_bed_trx').html((res.transactions || []).map(function (t) {
                var tone = t.status === 'Aktif' ? 'is-warn' : (t.status === 'Akan Datang' ? 'is-info' : '');
                return '<tr><td>' + esc(t.kode) + '</td><td>' + esc(t.nama + ' x' + t.qty) + '</td><td>' + esc((t.asset_codes || []).join(', ') || '-') + '</td>' +
                    '<td>' + esc((t.room_name || '-') + ' · ' + (t.renter_name || '-')) + '</td><td class="text-nowrap">' + fmtDate(t.tgl_mulai) + ' – ' + fmtDate(t.tgl_selesai) + '</td>' +
                    '<td><span class="bcl-pill ' + tone + '">' + esc(t.status) + '</span>' + (t.kurang > 0 ? ' <span class="bcl-pill is-bad">Kurang ' + rp(t.kurang) + '</span>' : '') + '</td></tr>';
            }).join('') || '<tr><td colspan="6" class="text-muted">Belum ada transaksi.</td></tr>');
        });
    });

    // ------------------------------------------------------------ tambah / edit / hapus kamar

    function openRoomForm(id) {
        loadFormData().done(function (form) {
            fillSelect($('#room_form_category'), form.categories.map(function (c) { return '<option value="' + c.id + '">' + esc(c.name) + '</option>'; }), 'Pilih kategori');
            var room = roomById(id);
            $('#form_room')[0].reset();
            $('#room_form_title').text(room ? 'Edit Kamar ' + room.room_name : 'Tambah Kamar');
            $('#room_form_id').val(room ? room.id : '');
            $('#room_form_name').val(room ? room.room_name : '');
            $('#room_form_floor').val(room ? room.floor : '');
            $('#room_form_notes').val(room ? room.notes : '');
            $('#room_form_category').val(room ? room.room_category : '').trigger('change');
            $('#room_delete_btn').toggle(!!room);
            $('#md_room').modal('hide');
            $('#md_room_form').modal('show');
        });
    }
    $('#bt_room_add').on('click', function () { openRoomForm(null); });

    $('#form_room').on('submit', function (e) {
        e.preventDefault();
        var $form = $(this);
        var isEdit = !!$('#room_form_id').val();
        var $btn = $form.find('button[type="submit"]').prop('disabled', true);
        $.ajax({ url: $form.data(isEdit ? 'update' : 'store'), type: 'POST', data: $form.serialize(), headers: { Accept: 'application/json' } })
            .done(function (res) {
                $('#md_room_form').modal('hide');
                state.formData = null;
                toast(res.message || 'Data kamar tersimpan.');
                reloadData();
            })
            .fail(function (xhr) { toast(ajaxError(xhr, 'Gagal menyimpan kamar.'), 'error'); })
            .always(function () { $btn.prop('disabled', false); });
    });

    $('#room_delete_btn').on('click', function () {
        var id = $('#room_form_id').val();
        $.confirm({
            title: 'Hapus kamar ini?',
            content: 'Kamar bisa dikembalikan dari menu "Kamar dihapus".',
            buttons: {
                confirm: {
                    text: 'Hapus', btnClass: 'btn-red', action: function () {
                        $.ajax({ url: url('remove', id), type: 'POST', headers: { Accept: 'application/json' } })
                            .done(function (res) {
                                $('#md_room_form').modal('hide');
                                state.formData = null;
                                toast(res.message || 'Kamar dihapus.');
                                reloadData();
                            })
                            .fail(function (xhr) { toast(ajaxError(xhr, 'Gagal menghapus kamar.'), 'error'); });
                    },
                },
                cancel: { text: 'Batal' },
            },
        });
    });

    $('#bt_room_deleted').on('click', function () {
        $('#deleted_rooms').html('<li class="muted">Memuat...</li>');
        $('#md_deleted').modal('show');
        $.getJSON(cfg.urls.deleted).done(function (res) {
            $('#deleted_rooms').html((res.deleted || []).map(function (room) {
                return '<li><span>' + esc(room.room_name) + '<div class="muted">Dihapus ' + esc(room.deleted_at || '-') + '</div></span>' +
                    '<button type="button" class="btn btn-sm btn-outline-success js-restore" data-url="' + esc(room.restore_url) + '">Aktifkan</button></li>';
            }).join('') || '<li class="muted">Tidak ada kamar yang dihapus.</li>');
        });
    });
    $(document).on('click', '.js-restore', function () {
        var $btn = $(this).prop('disabled', true);
        $.ajax({ url: $btn.data('url'), type: 'POST', headers: { Accept: 'application/json' } })
            .done(function (res) {
                $btn.closest('li').remove();
                state.formData = null;
                toast(res.message || 'Kamar diaktifkan.');
                reloadData();
            })
            .fail(function (xhr) { $btn.prop('disabled', false); toast(ajaxError(xhr), 'error'); });
    });

    // ------------------------------------------------------------ wifi

    function openWifi(room) {
        $('#form_wifi')[0].reset();
        $('#wifi_room_name').text(room.room_name);
        $('#wifi_room_id').val(room.id);
        $('#wifi_record_id').val('');
        $('#md_wifi').modal('show');
        $.getJSON(url('wifi', room.id)).done(function (res) {
            if (!res.wifi) return;
            $('#wifi_record_id').val(res.wifi.id);
            $('#wifi_ssid').val(res.wifi.ssid);
            $('#wifi_password').val(res.wifi.password);
            $('#wifi_notes').val(res.wifi.notes);
            $('#wifi_active').prop('checked', !!res.wifi.active);
        });
    }

    $('#form_wifi').on('submit', function (e) {
        e.preventDefault();
        var id = $('#wifi_record_id').val();
        var payload = { room_id: $('#wifi_room_id').val(), ssid: $('#wifi_ssid').val(), password: $('#wifi_password').val(), notes: $('#wifi_notes').val() };
        if ($('#wifi_active').is(':checked')) payload.active = 1;
        $.post(id ? url('wifiUpdate', id) : cfg.urls.wifiStore, payload)
            .done(function () {
                $('#md_wifi').modal('hide');
                toast('WiFi tersimpan.');
                if (state.openRoomId) renderRoomPanel(state.openRoomId);
            })
            .fail(function (xhr) { toast(ajaxError(xhr, 'Gagal menyimpan WiFi.'), 'error'); });
    });

    // ------------------------------------------------------------ detail penyewa

    function openRenter(id) {
        if (!id) return;
        $('#renter_body').html('<div class="bcl-hint">Memuat...</div>');
        $('#md_renter').modal('show');
        $.getJSON(url('renter', id)).done(function (res) {
            var r = res.renter || {};
            $('#renter_title').text(r.nama || 'Penyewa');
            var docs = (res.documents || []).filter(function (d) { return d.url; }).map(function (d) {
                return '<a class="bcl-pill" href="' + esc(d.url) + '" target="_blank" rel="noopener">' + esc(d.type) + '</a>';
            }).join(' ');
            var ledger = (res.deposit_ledger || []).map(function (row) {
                return '<li><span>' + esc(row.keterangan) + '<div class="muted">' + fmtDate(row.tanggal) + '</div></span><span class="text-right">' +
                    (row.masuk ? '<span class="text-success">+' + rp(row.masuk) + '</span>' : '<span class="text-danger">-' + rp(row.keluar) + '</span>') +
                    '<div class="muted">saldo ' + rp(row.saldo) + '</div></span></li>';
            }).join('');
            var trx = (res.transactions || []).map(function (t) {
                return '<li><span><a class="bcl-link" href="' + url('print', t.trans_id) + '" target="_blank">' + esc(t.trans_id) + '</a> · Kamar ' + esc(t.room_name || '-') +
                    '<div class="muted">' + fmtDate(t.tgl_mulai) + ' – ' + fmtDate(t.tgl_selesai) + '</div></span><span class="text-right">' + rp(t.harga + t.extra_total) +
                    '<div>' + (t.remaining_total > 0 ? '<span class="bcl-pill is-bad">Kurang ' + rp(t.remaining_total) + '</span>' : '<span class="bcl-pill is-good">Lunas</span>') + '</div></span></li>';
            }).join('');

            $('#renter_body').html(
                '<div class="row"><div class="col-md-6">' +
                '<div class="bcl-section"><div class="bcl-section__title">Identitas</div><dl class="bcl-kv">' +
                '<dt>Telepon</dt><dd>' + esc([r.phone, r.phone2].filter(Boolean).join(' / ') || '-') + '</dd>' +
                '<dt>Alamat</dt><dd>' + esc(r.alamat || '-') + '</dd>' +
                '<dt>Lahir</dt><dd>' + esc(r.birthday || '-') + '</dd>' +
                '<dt>Identitas</dt><dd>' + esc((r.identitas || '') + ' ' + (r.no_identitas || '')) + '</dd>' +
                '<dt>Kendaraan</dt><dd>' + esc([r.kendaraan, r.nopol].filter(Boolean).join(' · ') || '-') + '</dd>' +
                '<dt>Kamar aktif</dt><dd>' + esc(r.current_room_name ? r.current_room_name + ' s/d ' + fmtDate(r.current_room_end) : '-') + '</dd>' +
                '<dt>Dokumen</dt><dd>' + (docs || '-') + '</dd></dl></div>' +
                '<div class="bcl-section"><div class="bcl-section__title">Deposit · ' + rp(r.deposit_balance) + '</div><ul class="bcl-list">' + (ledger || '<li class="muted">Belum ada mutasi.</li>') + '</ul></div>' +
                '</div><div class="col-md-6"><div class="bcl-section"><div class="bcl-section__title">Riwayat sewa</div><ul class="bcl-list">' + (trx || '<li class="muted">Belum ada transaksi.</li>') + '</ul></div></div></div>'
            );
        });
    }

    // ------------------------------------------------------------ export

    $('#bt_export').on('click', function () {
        var header = ['Kamar', 'Lantai', 'Kategori', 'Status', 'Penyewa', 'Mulai', 'Selesai', 'Paket', 'Tagihan', 'Kurang', 'Catatan'];
        var rows = (state.data.rooms || []).map(function (r) {
            return [r.room_name, r.floor, r.category_name, r.is_occupied ? 'Terisi' : (r.status === 'booked' ? 'Booking' : 'Kosong'), r.nama, r.tgl_mulai, r.tgl_selesai,
                r.lama_sewa ? r.lama_sewa + ' ' + r.jangka_sewa : '', r.harga || '', r.kurang || '', r.notes];
        });
        var csv = [header].concat(rows).map(function (row) {
            return row.map(function (cell) { return '"' + String(cell == null ? '' : cell).replace(/"/g, '""') + '"'; }).join(';');
        }).join('\r\n');
        var blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8' });
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'Daftar Kamar ' + cfg.today + '.csv';
        link.click();
        setTimeout(function () { URL.revokeObjectURL(link.href); }, 1000);
    });

    // ------------------------------------------------------------ operational dialogs

    cfg.canSupervise = !!window.BCL_CAN_SUPERVISE;
    $('.js-methods').html((window.BCL_METHODS || ['Tunai']).map(function (m) { return '<option value="' + esc(m) + '">' + esc(m) + '</option>'; }).join(''));
    $('#sewa_method, #sewa_jaminan').on('change input', updateSewaHint);
    $('#sewa_diskon').on('input', function () {
        var price = sewaNetPrice();
        $('#nominal').data('price', price);
        setMoney($('#nominal'), price);
        $('#use_deposit').prop('checked', false);
        $('#deposit_amount').hide().val(0);
        updateSewaHint();
    });

    function postForm(form, urlValue) {
        var $form = $(form);
        $form.find('.js-money').each(function () { this.value = digits(this.value); });
        var $btn = $form.find('button[type="submit"]').prop('disabled', true);
        return $.ajax({ url: urlValue, type: 'POST', data: new FormData(form), processData: false, contentType: false, headers: { Accept: 'application/json' } })
            .fail(function (xhr) { toast(ajaxError(xhr), 'error'); })
            .always(function () { $btn.prop('disabled', false); });
    }

    function renderChecklist($box, inventory, previous) {
        if (!inventory.length) {
            $box.html('<div class="bcl-hint">Belum ada inventaris untuk kamar ini (atur di menu Inventaris). Catatan &amp; foto tetap bisa diisi.</div>');
            return;
        }
        var before = {};
        (previous || []).forEach(function (i) { before[i.inv_number] = i; });
        $box.html('<ul class="bcl-list">' + inventory.map(function (inv) {
            var prev = before[inv.inv_number];
            return '<li class="js-check-row" data-inv="' + esc(inv.inv_number) + '" data-name="' + esc(inv.name) + '">' +
                '<span>' + esc(inv.name) + (prev ? '<div class="muted">Saat masuk: ' + esc(prev.kondisi) + (prev.catatan ? ' (' + esc(prev.catatan) + ')' : '') + '</div>' : '') + '</span>' +
                '<span class="d-flex" style="gap: 6px;"><select class="form-control form-control-sm js-kondisi" style="width: 100px;"><option>Baik</option><option>Rusak</option><option>Hilang</option></select>' +
                '<input type="text" class="form-control form-control-sm js-kondisi-note" placeholder="catatan" style="width: 160px;"></span></li>';
        }).join('') + '</ul>');
    }

    function checklistJson($box) {
        return JSON.stringify($box.find('.js-check-row').map(function () {
            return { inv_number: $(this).data('inv'), name: $(this).data('name'), kondisi: $(this).find('.js-kondisi').val(), catatan: $(this).find('.js-kondisi-note').val() };
        }).get());
    }

    // Proses keluar ---------------------------------------------------------
    var checkoutRoom = null;

    function refreshCheckoutSections() {
        if (!checkoutRoom) return;
        var date = moment($('#co_date').val(), 'YYYY-MM-DD');
        var end = moment(checkoutRoom.tgl_selesai, 'YYYY-MM-DD');
        var diff = date.isValid() ? date.diff(end, 'days') : 0;
        $('#co_early').toggle(diff < 0);
        $('#co_overstay').toggle(diff > 0);
        $('#co_days').text(Math.max(diff, 0));
        $('#co_total').text(rp(Math.max(diff, 0) * digits($('#co_rate').val())));
        var $hint = $('#co_hint').removeClass('is-warn is-info is-good');
        if (diff < 0) $hint.addClass('is-info').text('Keluar ' + Math.abs(diff) + ' hari sebelum masa sewa habis. Sewa diakhiri pada tanggal ini.');
        else if (diff > 0) $hint.addClass('is-warn').text('Tinggal ' + diff + ' hari melewati masa sewa (habis ' + fmtDate(checkoutRoom.tgl_selesai) + ').');
        else $hint.addClass('is-good').text('Keluar tepat saat masa sewa habis.');
    }

    function openCheckout(room) {
        checkoutRoom = room;
        $('#md_room').modal('hide');
        var form = $('#form_checkout')[0];
        form.reset();
        if (window.bclDropzoneReset) window.bclDropzoneReset('#md_checkout');
        $('#co_trans_id').val(room.trans_id);
        $('#co_title').text('· ' + room.room_name + ' · ' + (room.nama || ''));
        $('#co_period').text('Masa sewa ' + fmtDate(room.tgl_mulai) + ' – ' + fmtDate(room.tgl_selesai));
        $('#co_date').val(cfg.today);
        setMoney($('#co_rate'), room.daily_rate || 0);
        $('#co_items').html('<div class="bcl-hint">Memuat inventaris...</div>');
        $('#co_guarantee').hide();
        $('#form_checkout .js-supervisor').toggle(cfg.canSupervise);
        refreshCheckoutSections();
        $('#md_checkout').modal('show');

        $.getJSON(url('inspections', room.trans_id)).done(function (res) {
            var checkin = (res.inspections || []).filter(function (i) { return i.type === 'checkin'; }).pop();
            renderChecklist($('#co_items'), res.inventory || [], checkin ? checkin.items : []);
            if (res.jaminan > 0) {
                $('#co_guarantee').show();
                $('#co_guarantee_balance').text(rp(res.jaminan));
                setMoney($('#co_return'), res.jaminan);
                $('#co_deduct').val(0);
            }
        });
    }

    $('#co_date, #co_rate').on('change input', refreshCheckoutSections);
    $('#co_deduct').on('input', function () {
        var balance = digits($('#co_guarantee_balance').text());
        setMoney($('#co_return'), Math.max(balance - digits(this.value), 0));
    });
    $('#form_checkout').on('submit', function (e) {
        e.preventDefault();
        $('#co_items_json').val(checklistJson($('#co_items')));
        postForm(this, cfg.urls.checkout).done(function (res) {
            $('#md_checkout').modal('hide');
            toast(res.message);
            reloadData();
        });
    });

    // Check-in ----------------------------------------------------------------
    function openCheckin(room) {
        $('#md_room').modal('hide');
        $('#form_checkin')[0].reset();
        if (window.bclDropzoneReset) window.bclDropzoneReset('#md_checkin');
        $('#ci_trans_id').val(room.trans_id);
        $('#ci_title').text('· ' + room.room_name + ' · ' + (room.nama || ''));
        $('#ci_date').val(room.tgl_mulai && room.tgl_mulai <= cfg.today ? room.tgl_mulai : cfg.today);
        $('#ci_items').html('<div class="bcl-hint">Memuat inventaris...</div>');
        $('#md_checkin').modal('show');
        $.getJSON(url('inspections', room.trans_id)).done(function (res) { renderChecklist($('#ci_items'), res.inventory || [], []); });
    }
    $('#form_checkin').on('submit', function (e) {
        e.preventDefault();
        $('#ci_items_json').val(checklistJson($('#ci_items')));
        postForm(this, cfg.urls.inspection).done(function (res) {
            $('#md_checkin').modal('hide');
            toast(res.message);
            reloadData();
        });
    });

    // Uang jaminan ------------------------------------------------------------
    function openGuarantee(renterId, name, balance) {
        $('#md_room').modal('hide');
        $('#form_guarantee')[0].reset();
        if (window.bclDropzoneReset) window.bclDropzoneReset('#md_guarantee');
        $('#gu_renter').val(renterId);
        $('#gu_title').text('· ' + (name || ''));
        $('#gu_balance').text(rp(balance));
        $('#gu_mode').val(balance > 0 ? 'kembalikan' : 'terima').trigger('change');
        $('#md_guarantee').modal('show');
    }
    $('#gu_mode').on('change', function () { $('#gu_proof').toggle(this.value === 'terima'); });
    $('#form_guarantee').on('submit', function (e) {
        e.preventDefault();
        postForm(this, cfg.urls.guarantee).done(function (res) {
            $('#md_guarantee').modal('hide');
            toast(res.message);
            reloadData();
        });
    });

    // Batal booking -----------------------------------------------------------
    $(document).on('click', '.js-cancel-booking', function (e) {
        e.preventDefault();
        var transId = $(this).data('trans');
        $('#md_room').modal('hide');
        $('#form_cancel_booking')[0].reset();
        $('#cb_trans_id').val(transId);
        $('#cb_title').text('· ' + $(this).data('label'));
        $('#cb_info').text('Memuat pembayaran...');
        $('#md_cancel_booking').modal('show');
        $.getJSON(url('show', transId), { id: transId }).done(function (trx) {
            var paid = (trx.jurnal || []).reduce(function (t, l) { return t + (parseFloat(l.kredit) || 0); }, 0);
            $('#cb_info').text('Booking ' + trx.trans_id + ' · ' + fmtDate(trx.tgl_mulai) + ' – ' + fmtDate(trx.tgl_selesai) + '. Sudah dibayar ' + rp(paid) + '.');
            setMoney($('#cb_refund'), paid);
        });
    });
    $('#form_cancel_booking').on('submit', function (e) {
        e.preventDefault();
        postForm(this, cfg.urls.cancelBooking).done(function (res) {
            $('#md_cancel_booking').modal('hide');
            toast(res.message);
            reloadData();
        });
    });

    // Perpanjangan massal ----------------------------------------------------
    function loadRenewals() {
        $('#rn_body').html('<tr><td colspan="6" class="text-center text-muted py-3">Memuat...</td></tr>');
        $('#rn_all').prop('checked', false);
        $.getJSON(cfg.urls.renewals, { days: $('#rn_days').val() }).done(function (res) {
            var items = res.items || [];
            $('#rn_body').html(items.length ? items.map(function (item) {
                var options = item.options.map(function (o) {
                    return '<option value="' + o.id + '"' + (o.same ? ' selected' : '') + '>' + esc(o.label + ' · ' + rp(o.price)) + '</option>';
                }).join('');
                var due = item.days_left < 0 ? '<span class="bcl-pill is-bad">lewat ' + Math.abs(item.days_left) + ' hari</span>' : (item.days_left === 0 ? '<span class="bcl-pill is-bad">hari ini</span>' : '<span class="bcl-pill is-warn">' + item.days_left + ' hari lagi</span>');
                return '<tr data-trans="' + esc(item.trans_id) + '"><td><input type="checkbox" class="js-rn-check"' + (options ? '' : ' disabled') + '></td>' +
                    '<td><strong>' + esc(item.room) + '</strong><div class="small text-muted">' + esc(item.category || '') + '</div></td>' +
                    '<td>' + esc(item.renter || '-') + (item.phone ? '<div class="small"><a href="https://wa.me/' + String(item.phone).replace(/\D/g, '').replace(/^0/, '62') + '" target="_blank">WhatsApp</a></div>' : '') + '</td>' +
                    '<td class="text-nowrap">' + fmtDate(item.tgl_selesai) + '<div>' + due + '</div></td>' +
                    '<td>' + (item.kurang > 0 ? '<span class="text-danger font-weight-bold">' + rp(item.kurang) + '</span>' : '-') + '</td>' +
                    '<td>' + (options ? '<select class="form-control form-control-sm js-rn-pl">' + options + '</select>' : '<span class="text-muted">Belum ada harga</span>') + '</td></tr>';
            }).join('') : '<tr><td colspan="6" class="text-center text-muted py-3">Tidak ada sewa yang perlu diperpanjang.</td></tr>');
        });
    }
    $('#bt_renewals').on('click', function () {
        $('#md_renewals').modal('show');
        loadRenewals();
    });
    $('#rn_days').on('change', loadRenewals);
    $('#rn_all').on('change', function () { $('#rn_body .js-rn-check:not(:disabled)').prop('checked', this.checked); });
    $('#rn_submit').on('click', function () {
        var items = $('#rn_body tr').filter(function () { return $(this).find('.js-rn-check').is(':checked'); }).map(function () {
            return { trans_id: $(this).data('trans'), pricelist: $(this).find('.js-rn-pl').val() };
        }).get();
        if (!items.length) {
            toast('Pilih kamar yang akan diperpanjang.', 'error');
            return;
        }
        var $btn = $(this).prop('disabled', true);
        $.ajax({ url: cfg.urls.bulkRenew, type: 'POST', data: { items: items }, headers: { Accept: 'application/json' } })
            .done(function (res) { toast(res.message); reloadData(); loadRenewals(); })
            .fail(function (xhr) { toast(ajaxError(xhr), 'error'); })
            .always(function () { $btn.prop('disabled', false); });
    });

    // ------------------------------------------------------------ start

    render();
    if ($.fn.tooltip) $('.bcl-toolbar [data-toggle="tooltip"]').tooltip();

    // Deep link from Daftar Penyewa: /bcl/rooms?sewa={renterId} opens the rent form for that renter.
    var params = new URLSearchParams(window.location.search);
    if (params.get('sewa')) {
        $(function () {
            openSewa({ renterId: params.get('sewa'), title: 'Sewa Kamar' });
            window.history.replaceState(null, '', window.location.pathname);
        });
    }
})(jQuery);
