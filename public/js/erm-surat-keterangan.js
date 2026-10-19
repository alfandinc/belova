// Surat Keterangan page (resources/views/erm/suratistirahat/index.blade.php): Surat Istirahat, Surat Mondok and
// Surat Diagnosa tables + their forms. Page data in window.SuratKeteranganConfig; PDFs
// are printed straight away (public/js/erm-pdf-preview.js, .btn-pdf-print links).
$(function () {
    const cfg = window.SuratKeteranganConfig;
    const visitsById = {};
    cfg.visits.forEach(function (v) { visitsById[v.id] = v; });

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    function esc(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }
    function errorMessage(xhr, fallback) {
        const json = xhr && xhr.responseJSON;
        if (json && json.errors) return Object.values(json.errors).flat().join('\n');
        return (json && json.message) || fallback;
    }
    function showError(xhr, fallback) {
        Swal.fire({ icon: 'error', title: 'Gagal', text: errorMessage(xhr, fallback) });
    }
    // Saved: say so and offer to print it straight away
    function savedWithPrint(message, printUrl, title) {
        Swal.fire({
            icon: 'success', title: 'Tersimpan', text: message,
            showCancelButton: !!printUrl, confirmButtonText: printUrl ? '<i class="fas fa-print mr-1"></i>Cetak sekarang' : 'OK',
            cancelButtonText: 'Nanti', reverseButtons: true
        }).then(function (r) {
            if (r.value && printUrl) window.ErmPdfPreview.printPdf(printUrl, title);
        });
    }

    const tableLanguage = {
        processing: 'Memuat data...', zeroRecords: 'Tidak ada data', infoEmpty: '', info: '_START_–_END_ dari _TOTAL_',
        paginate: { next: '›', previous: '‹' }
    };
    function surTable(selector, url, columns, empty) {
        return $(selector).DataTable({
            processing: true, serverSide: false, autoWidth: false, searching: false, lengthChange: false,
            pageLength: 5, ordering: false, dom: 'rtip',
            ajax: { url: url, type: 'GET' },
            language: $.extend({}, tableLanguage, { emptyTable: empty }),
            columns: columns,
            // Paging only when there is more than one page
            drawCallback: function () {
                const api = this.api();
                $(api.table().container()).find('.dataTables_paginate, .dataTables_info').toggle(api.page.info().pages > 1);
            }
        });
    }

    const tableIstirahat = surTable('#suratTable', cfg.routes.istirahatData, [
        { data: 'dokter_name' },
        { data: 'periode', className: 'text-nowrap' },
        { data: 'tanggal_dibuat', className: 'text-nowrap' },
        { data: 'aksi', className: 'text-right' }
    ], 'Belum ada surat istirahat');

    const tableMondok = surTable('#suratMondokTable', cfg.routes.mondokData, [
        { data: 'dokter_name' },
        { data: 'tujuan_igd_short' },
        { data: 'tanggal_dibuat', className: 'text-nowrap' },
        { data: 'aksi', className: 'text-right' }
    ], 'Belum ada surat mondok');

    const tableDiagnosa = surTable('#suratDiagnosaTable', cfg.routes.diagnosaData, [
        { data: 'DT_RowIndex' },
        { data: 'dokter', render: function (d, t, row) {
            return esc(d) + (row.spesialisasi && row.spesialisasi !== '-' ? '<div class="small text-muted">' + esc(row.spesialisasi) + '</div>' : '');
        } },
        { data: 'keterangan' },
        { data: 'tanggal', className: 'text-nowrap' },
        { data: 'aksi', className: 'text-right' }
    ], 'Belum ada surat diagnosa');

    // Selects in the modals
    $('.surat-select').each(function () {
        $(this).select2({ width: '100%', dropdownParent: $(this).closest('.modal') });
    });
    // Visit choices, newest first
    $('.surat-visit-select').each(function () {
        const $sel = $(this);
        cfg.visits.forEach(function (v) { $sel.append(new Option(v.label, v.id)); });
    });

    // Delete (Admin or the surat's doctor; the button is only there when allowed)
    $(document).on('click', '.btn-hapus-surat', function () {
        const url = $(this).data('url');
        const title = $(this).data('title');
        Swal.fire({
            icon: 'warning', title: 'Hapus ' + title + '?', text: 'Surat yang dihapus tidak bisa dikembalikan.',
            showCancelButton: true, confirmButtonText: 'Ya, hapus', cancelButtonText: 'Batal', confirmButtonColor: '#dc3545', reverseButtons: true
        }).then(function (r) {
            if (!r.value) return;
            $.ajax({ url: url, type: 'DELETE' })
                .done(function (res) {
                    window.ErmPdfPreview.drop(url);
                    tableIstirahat.ajax.reload(null, false);
                    tableMondok.ajax.reload(null, false);
                    Swal.fire({ icon: 'success', title: 'Dihapus', text: res.message, timer: 1500, showConfirmButton: false });
                })
                .fail(function (xhr) { showError(xhr, 'Surat gagal dihapus.'); });
        });
    });

    // ================= Surat Istirahat =================
    function isoDate(d) {
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }
    function parseDate(value) {
        const p = (value || '').split('-');
        return p.length === 3 ? new Date(+p[0], +p[1] - 1, +p[2]) : null;
    }
    // Number of days and whether it is allowed; shown under the dates, Simpan only when valid
    function updateIstirahat() {
        const start = parseDate($('#tanggal_mulai').val());
        const end = parseDate($('#tanggal_selesai').val());
        let text = '';
        let ok = false;
        if (start && end) {
            const days = Math.round((end - start) / 86400000) + 1;
            if (days < 1) text = 'Tanggal selesai tidak boleh sebelum tanggal mulai.';
            else if (days > cfg.maxHari) text = days + ' hari: maksimal istirahat ' + cfg.maxHari + ' hari.';
            else { text = 'Istirahat ' + days + ' hari'; ok = true; }
            $('.surat-quick-btn').removeClass('active').filter('[data-hari="' + days + '"]').addClass('active');
        }
        $('#istirahatInfo').text(text).toggleClass('is-invalid', !!text && !ok);
        $('#btnSimpanIstirahat').prop('disabled', !ok);
    }
    $('#tanggal_mulai, #tanggal_selesai').on('change input', updateIstirahat);
    $('.surat-quick-btn').on('click', function () {
        const start = parseDate($('#tanggal_mulai').val()) || parseDate(cfg.today);
        const end = new Date(start.getFullYear(), start.getMonth(), start.getDate() + Number($(this).data('hari')) - 1);
        $('#tanggal_mulai').val(isoDate(start));
        $('#tanggal_selesai').val(isoDate(end));
        updateIstirahat();
    });

    $('#btnBuatIstirahat').on('click', function () {
        $('#tanggal_mulai').val(cfg.today);
        $('#tanggal_selesai').val('');
        updateIstirahat();
        $('#modalSurat').modal('show');
    });

    $('#formSurat').on('submit', function (e) {
        e.preventDefault();
        const $btn = $('#btnSimpanIstirahat').prop('disabled', true);
        $.post(cfg.routes.istirahatStore, $(this).serialize())
            .done(function (res) {
                $('#modalSurat').modal('hide');
                tableIstirahat.ajax.reload();
                savedWithPrint(res.message, res.print_url, 'Surat Istirahat');
            })
            .fail(function (xhr) { showError(xhr, 'Surat istirahat gagal disimpan.'); })
            .always(function () { updateIstirahat(); });
    });

    // ================= Surat Mondok =================
    // A visit sets the surat date and its doctor
    $('#visitation_id_mondok').on('change', function () {
        const visit = visitsById[$(this).val()];
        if (!visit) return;
        if (visit.tanggal) $('#mondokTanggal').val(visit.tanggal);
        if (visit.dokter_id) $('#dokter_id_mondok').val(String(visit.dokter_id)).trigger('change.select2');
    });

    $('#btnBuatMondok').on('click', function () {
        $('#formSuratMondok')[0].reset();
        $('#visitation_id_mondok').val(String(cfg.visitationId)).trigger('change');
        $('#modalSuratMondok').modal('show');
    });

    $('#btnAutoFill').on('click', function () {
        const visitId = $('#visitation_id_mondok').val();
        if (!visitId) return;
        const $btn = $(this).prop('disabled', true);
        $.get(cfg.routes.asesmenData.replace('__ID__', visitId))
            .done(function (data) {
                if (!data.diagnosa && !data.instruksi_terapi) {
                    Swal.fire({ icon: 'info', title: 'Tidak ada data', text: 'Kunjungan ini belum punya diagnosa / instruksi di asesmen.' });
                    return;
                }
                if (data.diagnosa) $('#mondokDiagnosa').val(data.diagnosa);
                if (data.instruksi_terapi) $('#mondokInstruksi').val(data.instruksi_terapi);
            })
            .fail(function (xhr) { showError(xhr, 'Data asesmen gagal diambil.'); })
            .always(function () { $btn.prop('disabled', false); });
    });

    $('#formSuratMondok').on('submit', function (e) {
        e.preventDefault();
        const $btn = $('#btnSimpanMondok').prop('disabled', true);
        $.post(cfg.routes.mondokStore, $(this).serialize())
            .done(function (res) {
                $('#modalSuratMondok').modal('hide');
                tableMondok.ajax.reload();
                savedWithPrint(res.message, res.print_url, 'Surat Mondok');
            })
            .fail(function (xhr) { showError(xhr, 'Surat mondok gagal disimpan.'); })
            .always(function () { $btn.prop('disabled', false); });
    });

    // ================= Surat Diagnosa =================
    // The visit's diagnosa kerja, and its current keterangan when it already has a surat
    $('#diagnosaKunjunganSelect').on('change', function () {
        const visit = visitsById[$(this).val()] || {};
        const list = visit.diagnosa || [];
        $('#diagnosaKerjaList').html(list.length
            ? list.map(function (d) { return '<span class="elab-diagnosa-chip">' + esc(d) + '</span>'; }).join('')
            : '<span class="elab-diagnosa-empty">Kunjungan ini belum punya diagnosa kerja.</span>');
        $('#diagnosaExists').toggleClass('d-none', !visit.has_surat_diagnosa);
        $('#diagnosaKeterangan').val(visit.keterangan || '').trigger('input');
    });
    $('#diagnosaKeterangan').on('input', function () {
        $('#diagnosaKeteranganCount').text(this.value.length + '/191');
    });
    function openDiagnosa(visitId) {
        $('#diagnosaKunjunganSelect').val(String(visitId)).trigger('change');
        $('#modalSuratDiagnosa').modal('show');
    }
    $('#btnBuatDiagnosa').on('click', function () { openDiagnosa(cfg.visitationId); });
    $(document).on('click', '.btn-ubah-diagnosa', function () { openDiagnosa($(this).data('visit')); });

    $('#formSuratDiagnosa').on('submit', function (e) {
        e.preventDefault();
        const visitId = $('#diagnosaKunjunganSelect').val();
        const keterangan = $('#diagnosaKeterangan').val();
        const $btn = $('#btnSimpanDiagnosa').prop('disabled', true);
        $.post(cfg.routes.diagnosaStore, $(this).serialize())
            .done(function (res) {
                const visit = visitsById[visitId];
                if (visit) { visit.has_surat_diagnosa = true; visit.keterangan = keterangan; }
                // Both language versions show the keterangan: make them again
                window.ErmPdfPreview.drop('/riwayatkunjungan/' + visitId + '/print-surat-diagnosis');
                $('#modalSuratDiagnosa').modal('hide');
                tableDiagnosa.ajax.reload(null, false);
                savedWithPrint(res.message, cfg.routes.diagnosaPrint.replace('__ID__', visitId), 'Surat Keterangan Diagnosis');
            })
            .fail(function (xhr) { showError(xhr, 'Surat diagnosa gagal disimpan.'); })
            .always(function () { $btn.prop('disabled', false); });
    });
});
