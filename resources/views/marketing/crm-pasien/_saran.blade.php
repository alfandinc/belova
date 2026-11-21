{{-- Tab "Perlu Follow Up": pasien suggested from visit data (App\Services\Marketing\SaranFollowUp); a row leaves once followed up --}}
@php($saranAlasan = \App\Services\Marketing\SaranFollowUp::ALASAN)
<div class="card">
    <div class="card-body">
        <div class="crm-toolbar mb-2">
            <div class="crm-field crm-field-chips">
                <label>Alasan</label>
                <div class="crm-chips" id="saranAlasanChips">
                    <button type="button" class="btn btn-sm btn-primary" data-alasan="">Semua <span class="badge badge-light ml-1" data-count="total">0</span></button>
                    @foreach($saranAlasan as $key => $alasan)
                        <button type="button" class="btn btn-sm btn-outline-primary" data-alasan="{{ $key }}"
                                title="Kunjungan terakhir {{ $alasan['from'] }}–{{ $alasan['to'] }} hari lalu">
                            {{ $alasan['label'] }} <span class="badge badge-light ml-1" data-count="{{ $key }}">0</span>
                        </button>
                    @endforeach
                </div>
            </div>
            <div class="crm-field">
                <label for="saranKlinikFilter">Klinik</label>
                <select id="saranKlinikFilter" class="form-control">
                    <option value="">Semua klinik</option>
                    @foreach($kliniks as $klinik)
                        <option value="{{ $klinik->id }}">{{ $klinik->nama }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <p class="small text-muted mb-3">
            Disusun otomatis dari kunjungan terakhir pasien di tiap klinik yang sudah dibayar:
            <b>konsultasi tanpa perawatan</b> 3–14 hari lalu, <b>beli produk</b> 30–60 hari lalu
            (termasuk resep di Belova Skin), dan <b>lama tidak datang</b> 3–12 bulan.
            Pasien hilang dari daftar setelah dibuatkan follow up di klinik itu, atau saat sudah terdaftar kunjungan baru.
        </p>

        <table id="saranTable" class="table table-hover crm-table w-100">
            <thead>
                <tr>
                    <th>Pasien</th>
                    <th>No HP</th>
                    <th>Klinik</th>
                    <th>Alasan</th>
                    <th>Kunjungan Terakhir</th>
                    <th>Saran Kategori</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
window.crmPasien.inits.saran = function () {
    var crm = window.crmPasien;
    var alasan = '';
    var plain = function (html) { return $('<div>').html(html || '').text(); };

    function params() {
        return { alasan: alasan, klinik_id: $('#saranKlinikFilter').val() };
    }

    var table = $('#saranTable').DataTable({
        processing: true,
        serverSide: true,
        order: [], // server default: konsultasi first, then the most recent visit
        language: { search: 'Search:', searchPlaceholder: 'Nama / No RM', processing: 'Memuat...', emptyTable: 'Tidak ada pasien yang perlu di-follow up', zeroRecords: 'Pasien tidak ditemukan' },
        ajax: {
            url: "{{ route('marketing.followup.saran') }}",
            data: function (d) { $.extend(d, params()); }
        },
        // Server fields are HTML-escaped
        columns: [
            { data: 'pasien_nama', name: 'pasien_nama', render: function (data, type, row) {
                if (type !== 'display') return data;
                return '<div class="crm-person-name">' + (data || '-') + '</div><div class="crm-muted-id">' + row.pasien_id + '</div>';
            } },
            { data: 'no_hp', name: 'p.no_hp', orderable: false, searchable: false, render: function (data, type) {
                return type === 'display' ? crm.waLink(data) : data;
            } },
            { data: 'klinik_nama', name: 'k.nama', orderable: false, searchable: false, render: function (data, type, row) {
                if (type !== 'display') return data;
                return data ? crm.klinikLabel(data, row.klinik_color, plain(row.klinik_logo)) : crm.klinikNone;
            } },
            { data: 'alasan_label', name: 's.alasan', orderable: false, searchable: false, className: 'crm-wrap', render: function (data, type, row) {
                if (type !== 'display') return data;
                return '<span class="badge badge-' + crm.esc(row.alasan_color) + '">' + data + '</span>'
                    + '<div class="small mt-1">' + row.keterangan + '</div>';
            } },
            { data: 'trigger_date', name: 'trigger_date', searchable: false, className: 'text-nowrap' },
            { data: 'kategori', name: 'kategori', orderable: false, searchable: false, render: function (data) {
                if (!data) return '<span class="text-muted">-</span>';
                return '<span class="badge badge-' + (crm.kategoriColors[plain(data)] || 'secondary') + '">' + data + '</span>';
            } },
            { data: null, orderable: false, searchable: false, className: 'crm-actions-cell', render: function () {
                return '<button type="button" class="btn btn-sm btn-success saranFollowUpBtn"><i class="fas fa-phone-alt mr-1"></i>Buat Follow Up</button>';
            } }
        ],
        drawCallback: updateCounts
    });

    // Counts on the alasan chips (klinik filter applied) and the tab badge
    function updateCounts() {
        $.get("{{ route('marketing.followup.saran-count') }}", { klinik_id: $('#saranKlinikFilter').val() }, function (res) {
            $('#saranAlasanChips [data-count="total"]').text(res.total);
            $.each(res.per_alasan || {}, function (key, n) {
                $('#saranAlasanChips [data-count="' + key + '"]').text(n);
            });
            if (!$('#saranKlinikFilter').val()) crm.setSaranBadge(res.total);
        });
    }

    $('#saranAlasanChips').on('click', 'button', function () {
        alasan = String($(this).data('alasan') || '');
        $('#saranAlasanChips button').removeClass('btn-primary').addClass('btn-outline-primary');
        $(this).removeClass('btn-outline-primary').addClass('btn-primary');
        table.ajax.reload();
    });
    $('#saranKlinikFilter').on('change', function () { table.ajax.reload(); });

    // Opens the follow up form filled in: pasien, klinik, kategori and why in Catatan
    $('#saranTable').on('click', '.saranFollowUpBtn', function () {
        var row = table.row($(this).closest('tr')).data();
        if (!row) return;
        crm.openFollowUp({
            id: plain(row.pasien_id),
            nama: plain(row.pasien_nama),
            klinik_id: row.klinik_id,
            kategori: row.kategori ? plain(row.kategori) : null,
            catatan: plain(row.keterangan)
        });
    });

    return table;
};
</script>
@endpush
