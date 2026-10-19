// Riwayat Kunjungan page. Page data comes from window.RiwayatKunjunganConfig;
// the PDF preview (surat diagnosis) comes from erm-pdf-preview.js.
$(document).ready(function () {
    const cfg = window.RiwayatKunjunganConfig;
    const csrf = function () { return $('meta[name="csrf-token"]').attr('content'); };
    let jenis = 'klinis';

    function url(name, id) {
        return cfg.routes[name].replace('__ID__', encodeURIComponent(id));
    }

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    let bayar = '';
    const DEFAULT_STATUS = '2';

    const riwayatTable = $('#riwayat-table').DataTable({
        processing: true,
        serverSide: true,
        deferRender: true,
        searching: false,
        pageLength: 10,
        ajax: {
            url: cfg.routes.data,
            data: function (d) {
                d.status_kunjungan = $('#filter_status_kunjungan').val();
                d.jenis_kunjungan = jenis;
                d.date_from = $('#filter_date_from').val();
                d.date_to = $('#filter_date_to').val();
                d.bayar = bayar;
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'kunjungan', name: 'tanggal_visitation' },
            { data: 'dokter', name: 'dokter', orderable: false, searchable: false },
            { data: 'ringkasan', name: 'ringkasan', orderable: false, searchable: false },
            { data: 'tagihan', name: 'tagihan', orderable: false, searchable: false },
            { data: 'aksi', name: 'aksi', orderable: false, searchable: false }
        ],
        order: [[1, 'desc']],
        language: { emptyTable: 'Belum ada kunjungan untuk filter ini' }
    });

    // Chip counts + how many of the patient's visits the filters hide
    $('#riwayat-table').on('xhr.dt', function (e, settings, json) {
        if (!json) return;
        $.each(json.jenis_counts || {}, function (key, count) {
            $(`.rk-chip-count[data-count-for="${key}"]`).text(count);
        });
        const hidden = (json.total_all || 0) - (json.recordsFiltered || 0);
        $('#rkHiddenNote')
            .html(hidden > 0 ? `${hidden} kunjungan lain tersembunyi oleh filter &middot; <a id="rkShowAll">Tampilkan semua</a>` : '')
            .toggle(hidden > 0);
    });

    function reload() {
        riwayatTable.ajax.reload();
    }

    function setJenis(key) {
        jenis = key;
        $('.rk-chip[data-jenis]').removeClass('active').filter(`[data-jenis="${key}"]`).addClass('active');
    }

    function setBayar(value) {
        bayar = value;
        $('#filterBayar').toggleClass('active', bayar === 'belum');
    }

    $('.rk-chip[data-jenis]').on('click', function () {
        setJenis(String($(this).data('jenis') || ''));
        reload();
    });

    $('#filter_status_kunjungan, #filter_date_from, #filter_date_to').on('change', reload);

    $('#filterBayar').on('click', function () {
        setBayar(bayar === 'belum' ? '' : 'belum');
        reload();
    });

    // "Belum dibayar Rp…" on the stat tile: every unpaid visit, whatever its jenis / status
    $('.rk-due-filter').on('click', function () {
        setBayar('belum');
        setJenis('');
        $('#filter_status_kunjungan').val('');
        reload();
    });

    // Reset to the page's defaults (klinis + sudah diperiksa)
    $('#btnResetFilter').on('click', function () {
        setBayar('');
        setJenis('klinis');
        $('#filter_status_kunjungan').val(DEFAULT_STATUS);
        $('#filter_date_from, #filter_date_to').val('');
        reload();
    });

    // "Tampilkan semua": no filter at all
    $(document).on('click', '#rkShowAll', function () {
        setBayar('');
        setJenis('');
        $('#filter_status_kunjungan').val('');
        $('#filter_date_from, #filter_date_to').val('');
        reload();
    });

    // ---- Surat diagnosis ----
    $(document).on('click', '.diagnosis-btn', function () {
        const visitationId = $(this).data('id');
        $('#visitation_id').val(visitationId);
        $.getJSON(url('diagnosisData', visitationId)).done(function (response) {
            const pasien = response.pasien || {};
            $('#pasien_nama').text(pasien.nama || '-');
            $('#pasien_rm').text(pasien.id || '-');
            $('#pasien_lahir').text(pasien.tanggal_lahir ? moment(pasien.tanggal_lahir).format('DD-MM-YYYY') : '-');
            $('#pasien_gender').text(pasien.gender || '-');
            $('#pasien_alamat').text(pasien.alamat || '-');

            const diagnoses = response.diagnoses ? Object.values(response.diagnoses) : [];
            $('#diagnosis_list').html(diagnoses.length
                ? '<ul class="list-group">' + diagnoses.map(function (d) { return '<li class="list-group-item">' + escapeHtml(d) + '</li>'; }).join('') + '</ul>'
                : '<p class="text-muted mb-0">Tidak ada diagnosis yang ditemukan.</p>');
            $('#keterangan').val(response.suratDiagnosa ? response.suratDiagnosa.keterangan : '');
            $('#diagnosisModal').modal('show');
        }).fail(function () {
            Swal.fire('Gagal', 'Data surat diagnosis gagal dimuat.', 'error');
        });
    });

    function saveDiagnosis() {
        return $.ajax({
            url: cfg.routes.diagnosisStore,
            type: 'POST',
            dataType: 'json',
            data: { _token: csrf(), visitation_id: $('#visitation_id').val(), keterangan: $('#keterangan').val() }
        });
    }

    function saveFailed(xhr) {
        Swal.fire('Gagal', (xhr.responseJSON && xhr.responseJSON.message) || 'Surat diagnosis gagal disimpan.', 'error');
    }

    $('#saveDiagnosis').on('click', function () {
        saveDiagnosis().done(function () {
            Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Surat diagnosis berhasil disimpan.', timer: 1500, showConfirmButton: false });
        }).fail(saveFailed);
    });

    // Cetak: save first so the keterangan is in the PDF, then preview it on the page
    function printDiagnosis(routeName, title) {
        const visitationId = $('#visitation_id').val();
        saveDiagnosis().done(function () {
            const pdfUrl = url(routeName, visitationId);
            window.ErmPdfPreview.drop(pdfUrl);
            window.ErmPdfPreview.open(pdfUrl, title);
        }).fail(saveFailed);
    }

    $('#printDiagnosis').on('click', function () { printDiagnosis('diagnosisPrint', 'Surat Keterangan Diagnosis'); });
    $('#printDiagnosisEn').on('click', function () { printDiagnosis('diagnosisPrintEn', 'Medical Diagnosis Certificate'); });
});
