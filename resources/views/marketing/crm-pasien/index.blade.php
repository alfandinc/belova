@extends('layouts.marketing.app')

@section('title', 'Pasien & Follow Up')

@section('navbar')
    @include('layouts.marketing.navbar')
@endsection

@push('styles')
<style>
    /* Theme-neutral pieces (the marketing layout defaults to dark mode) */
    .crm-tabs .nav-link { font-weight: 600; padding: .6rem 1.1rem; }
    .crm-tabs .nav-link .badge { margin-left: 6px; }
    .crm-toolbar { display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; }
    .crm-toolbar .crm-field { display: flex; flex-direction: column; min-width: 170px; flex: 0 1 220px; }
    .crm-toolbar .crm-field label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; opacity: .65; margin-bottom: 4px; }
    .crm-toolbar .crm-field-wide { flex-basis: 260px; }
    .crm-toolbar .crm-actions { margin-left: auto; display: flex; gap: 8px; align-items: flex-end; }
    .crm-toolbar .crm-field-chips { flex-basis: 400px; }
    .crm-chips { display: flex; flex-wrap: wrap; gap: 6px; align-items: flex-start; align-content: flex-start; }
    .crm-chips .btn { border-radius: 999px; }
    .crm-chips .badge { font-size: 11px; padding: 4px 8px; }
    .crm-stats-row { row-gap: 10px; }
    .crm-muted { opacity: .6; }
    /* Klinik: its logo (or a colored initial) + short name */
    .crm-klinik { display: inline-flex; align-items: center; gap: 7px; min-width: 0; max-width: 100%; vertical-align: middle; }
    .crm-klinik-logo { width: 22px; height: 22px; flex: 0 0 22px; object-fit: contain; border-radius: 6px; background: #fff; padding: 1px; box-shadow: 0 0 0 1.5px var(--klinik, #94a3b8); }
    .crm-klinik-logo.is-initial { display: inline-flex; align-items: center; justify-content: center; color: #fff; font-size: 11px; font-weight: 800; box-shadow: none; padding: 0; }
    .crm-klinik-logo.is-empty { background: rgba(148, 163, 184, .35); }
    .crm-klinik-name { font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .crm-table .crm-klinik { max-width: 220px; }
    /* Breakdown cards (Per klinik / Per kategori / Top sales): name, number(s), share bar */
    .crm-stat-hint { text-transform: none; letter-spacing: 0; font-weight: 400; margin-left: 4px; }
    .crm-breakdown { list-style: none; margin: 0; padding: 0; }
    .crm-breakdown-row { padding: 5px 6px; margin: 0 -6px; border-radius: 6px; transition: background .15s, opacity .15s; }
    .crm-breakdown-row + .crm-breakdown-row { margin-top: 2px; }
    .crm-breakdown-row.is-link { cursor: pointer; outline: none; }
    .crm-breakdown-row.is-link:hover, .crm-breakdown-row.is-link:focus { background: rgba(148, 163, 184, .12); }
    .crm-breakdown-row.is-active { background: rgba(30, 136, 229, .12); box-shadow: inset 0 0 0 1px rgba(30, 136, 229, .45); }
    .crm-breakdown-row.is-dim { opacity: .45; }
    .crm-breakdown-head { display: flex; align-items: center; gap: 8px; font-size: 13px; }
    .crm-breakdown-name { display: flex; align-items: center; gap: 7px; flex: 1 1 auto; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .crm-breakdown-name > span:last-child { overflow: hidden; text-overflow: ellipsis; }
    .crm-breakdown-num { flex: 0 0 64px; text-align: right; white-space: nowrap; }
    .crm-breakdown-bar { height: 4px; margin-top: 4px; border-radius: 2px; background: rgba(148, 163, 184, .2); overflow: hidden; }
    .crm-breakdown-bar span { display: block; height: 100%; border-radius: 2px; transition: width .3s; }
    .crm-dot { width: 10px; height: 10px; border-radius: 50%; flex: 0 0 10px; }
    .crm-rank { width: 18px; height: 18px; flex: 0 0 18px; border-radius: 50%; background: rgba(148, 163, 184, .2); font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; }
    /* Select2 in the toolbar / form: same height and border as .form-control */
    .crm-toolbar .select2-container .select2-selection--single,
    #followupModal .select2-container .select2-selection--single { height: calc(1.5em + .75rem + 2px); display: flex; align-items: center; }
    .crm-toolbar .select2-container .select2-selection--single .select2-selection__rendered,
    #followupModal .select2-container .select2-selection--single .select2-selection__rendered { line-height: normal; padding-left: 10px; flex: 1; min-width: 0; }
    .crm-toolbar .select2-container .select2-selection--single .select2-selection__arrow,
    #followupModal .select2-container .select2-selection--single .select2-selection__arrow { height: 100%; }
    .crm-toolbar .select2-container .select2-selection--multiple { min-height: calc(1.5em + .75rem + 2px); }
    .crm-toolbar .select2-container .select2-selection--multiple .select2-search__field { margin-top: 7px; }
    .select2-results__option .crm-klinik { max-width: 100%; }
    .theme-dark .crm-toolbar .select2-selection, .theme-dark #followupModal .select2-selection { background: #2c2f40; border-color: #555; }
    .theme-dark .crm-toolbar .select2-selection__rendered, .theme-dark #followupModal .select2-selection__rendered { color: #fff; }
    .theme-dark .select2-dropdown { background: #2c2f40; border-color: #555; color: #fff; }
    /* Kategori toggles: outlined until chosen, filled when chosen (--chip = the kategori colour) */
    .crm-chip-toggle { border: 1px solid var(--chip); color: var(--chip); background: transparent; font-weight: 600; }
    .crm-chip-toggle:hover { color: var(--chip); background: rgba(148, 163, 184, .12); }
    .crm-chip-toggle.active, .crm-chip-toggle.active:hover { background: var(--chip); color: #fff; }
    .crm-paste-zone {
        border: 2px dashed rgba(148, 163, 184, .45); border-radius: 8px; padding: 12px; text-align: center;
        cursor: pointer; min-height: 92px; display: flex; align-items: center; justify-content: center; outline: none;
        transition: border-color .15s, background .15s;
    }
    .crm-paste-zone:hover, .crm-paste-zone:focus, .crm-paste-zone.is-over { border-color: #1e88e5; background: rgba(30, 136, 229, .06); }
    .crm-paste-zone img { max-width: 100%; max-height: 160px; border-radius: 6px; }
    @media (max-width: 575.98px) {
        .crm-toolbar .crm-field, .crm-toolbar .crm-field-chips { flex: 1 1 100%; }
        .crm-toolbar .crm-actions { margin-left: 0; }
    }
    .crm-stat { cursor: default; height: 100%; margin-bottom: 0; border: 1px solid rgba(148, 163, 184, .25); transition: border-color .15s, box-shadow .15s; }
    .crm-stat[data-filter] { cursor: pointer; }
    .crm-stat[data-filter]:hover { border-color: rgba(30, 136, 229, .6); }
    .crm-stat.is-active { border-color: #1e88e5; box-shadow: 0 0 0 2px rgba(30, 136, 229, .25); }
    .crm-stat .card-body { padding: .75rem 1rem; }
    .crm-stat-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; opacity: .65; }
    .crm-stat-value { font-size: 1.6rem; font-weight: 800; line-height: 1.2; }
    .crm-stat-sub { font-size: 12px; opacity: .7; }
    .crm-table td { vertical-align: middle; }
    .crm-table .crm-actions-cell, .crm-table .crm-nowrap { white-space: nowrap; }
    .crm-table .crm-wrap { white-space: normal; min-width: 180px; max-width: 320px; }
    .crm-person-name { font-weight: 600; }
    .crm-muted-id { font-size: 12px; opacity: .6; }
    .crm-wa { white-space: nowrap; }
    .crm-wa i { color: #25d366; }
    .badge-pink { background: #e83e8c; color: #fff; }
    .badge-purple { background: #6f42c1; color: #fff; }
</style>
@endpush

{{-- Pushed before the tab partials so their scripts can use these helpers --}}
@push('scripts')
<script>
window.crmPasien = {
    inits: {},
    // Plain text -> safe HTML
    esc: function (value) {
        return $('<div>').text(value === null || value === undefined ? '' : String(value)).html();
    },
    // Digits only, leading 0 becomes the Indonesian 62
    waNumber: function (phone) {
        var digits = String(phone || '').replace(/\D/g, '');
        return digits.charAt(0) === '0' ? '62' + digits.substring(1) : digits;
    },
    // Phone number as a WhatsApp chat link; `phoneHtml` is the already escaped number from the server
    waLink: function (phoneHtml) {
        var text = $('<div>').html(phoneHtml || '').text();
        var number = window.crmPasien.waNumber(text);
        if (!number) return '<span class="text-muted">-</span>';
        return '<a class="crm-wa" href="https://wa.me/' + number + '" target="_blank" rel="noopener" title="Chat WhatsApp">'
            + '<i class="fab fa-whatsapp mr-1"></i>' + phoneHtml + '</a>';
    },
    // Opens the shared Daftarkan Kunjungan modal for a follow up ({id, pasien_id, pasien_nama, klinik_id, label})
    registerVisit: function (followUp) {
        if (typeof window.openDaftarKunjunganModal !== 'function') {
            Swal.fire('Tidak tersedia', 'Form pendaftaran kunjungan belum siap, muat ulang halaman.', 'error');
            return;
        }
        window.openDaftarKunjunganModal({
            jenis: 'konsultasi',
            pasienId: followUp.pasien_id,
            pasienNama: followUp.pasien_nama,
            klinikId: followUp.klinik_id || '',
            followUpId: followUp.id,
            followUpLabel: followUp.label || ''
        });
    },
    isActive: function (name) {
        return $('#crmPasienTabs a[data-tab="' + name + '"]').hasClass('active');
    },
    // Date range picker with quick ranges; onChange(start, end) gets moment objects
    dateRangePicker: function ($input, start, end, onChange) {
        $input.daterangepicker({
            startDate: start,
            endDate: end,
            opens: 'left',
            autoApply: true,
            locale: {
                format: 'DD/MM/YYYY',
                applyLabel: 'Terapkan',
                cancelLabel: 'Batal',
                customRangeLabel: 'Pilih tanggal',
                daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
                monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
                firstDay: 1
            },
            ranges: {
                'Hari ini': [moment(), moment()],
                'Kemarin': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                '7 hari terakhir': [moment().subtract(6, 'days'), moment()],
                'Bulan ini': [moment().startOf('month'), moment().endOf('month')],
                'Bulan lalu': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        }, onChange);
    }
};
</script>
@endpush

{{--
    Pasien data, follow up and birthday greetings in one page (each was its own page before).
    Each tab's table loads the first time the tab is opened; ?tab= keeps the open tab on reload.
--}}
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row align-items-center">
                    <div class="col">
                        <h4 class="page-title">Pasien & Follow Up</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">CRM</li>
                            <li class="breadcrumb-item active">Pasien & Follow Up</li>
                        </ol>
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-success" id="crmNewFollowUp">
                            <i class="fas fa-plus mr-1"></i>Tambah Follow Up
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs crm-tabs" id="crmPasienTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'pasien' ? 'active' : '' }}" data-toggle="tab" href="#tab-pasien" data-tab="pasien" role="tab">
                <i class="fas fa-users mr-1"></i>Pasien
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'followup' ? 'active' : '' }}" data-toggle="tab" href="#tab-followup" data-tab="followup" role="tab">
                <i class="fas fa-phone-alt mr-1"></i>Follow Up
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'ulang-tahun' ? 'active' : '' }}" data-toggle="tab" href="#tab-ulang-tahun" data-tab="ulang-tahun" role="tab">
                <i class="fas fa-birthday-cake mr-1"></i>Ulang Tahun
                @if($birthdayTodayCount > 0)
                    <span class="badge badge-pill badge-danger" id="crmBirthdayBadge" title="Ulang tahun hari ini yang belum diucapkan">{{ $birthdayTodayCount }}</span>
                @endif
            </a>
        </li>
    </ul>

    <div class="tab-content pt-3">
        <div class="tab-pane fade {{ $activeTab === 'pasien' ? 'show active' : '' }}" id="tab-pasien" role="tabpanel">
            @include('marketing.crm-pasien._pasien')
        </div>
        <div class="tab-pane fade {{ $activeTab === 'followup' ? 'show active' : '' }}" id="tab-followup" role="tabpanel">
            @include('marketing.crm-pasien._followup')
        </div>
        <div class="tab-pane fade {{ $activeTab === 'ulang-tahun' ? 'show active' : '' }}" id="tab-ulang-tahun" role="tabpanel">
            @include('marketing.crm-pasien._ulang_tahun')
        </div>
    </div>
</div>

{{-- Modals sit outside the tab panes: a modal inside a hidden pane cannot open --}}
@stack('crm-modals')

{{-- Shared ERM "Daftarkan Kunjungan" modal (window.openDaftarKunjunganModal); opened from a follow up it sets referral "Follow Up CRM" --}}
@include('erm.rawatjalans.partials.modal-daftar-kunjungan')
@endsection

@push('scripts')
<script>
$(function () {
    var crm = window.crmPasien;
    var tables = {};

    // Builds a tab's table the first time it is shown; later visits only fix the column widths
    function showTab(name) {
        if (!tables[name] && crm.inits[name]) {
            tables[name] = crm.inits[name]();
        } else if (tables[name]) {
            tables[name].columns.adjust();
        }
    }

    // Reloads a tab's table (keeping its page) when that tab has been opened
    crm.reload = function (name) {
        if (tables[name]) tables[name].ajax.reload(null, false);
    };

    $('#crmPasienTabs a[data-toggle="tab"]').on('shown.bs.tab', function () {
        var name = $(this).data('tab');
        showTab(name);
        try {
            var url = new URL(window.location.href);
            url.searchParams.set('tab', name);
            window.history.replaceState(null, '', url.toString());
        } catch (e) {}
    });

    $('#crmNewFollowUp').on('click', function () {
        crm.openFollowUp(null);
    });

    // A visit registered from here marks its follow up booked
    $(document).on('rj:visitation-registered', function () {
        crm.reload('followup');
        crm.reload('pasien');
    });

    showTab(@json($activeTab));
});
</script>
@endpush
