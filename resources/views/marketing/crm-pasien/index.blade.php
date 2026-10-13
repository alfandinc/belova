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
    .crm-top-sales li { display: flex; justify-content: space-between; gap: 8px; }
    .crm-top-sales-name { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; min-width: 0; }
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
    // Opens the shared Daftarkan Kunjungan modal for a follow up ({id, pasien_id, pasien_nama, label})
    registerVisit: function (followUp) {
        if (typeof window.openDaftarKunjunganModal !== 'function') {
            Swal.fire('Tidak tersedia', 'Form pendaftaran kunjungan belum siap, muat ulang halaman.', 'error');
            return;
        }
        window.openDaftarKunjunganModal({
            jenis: 'konsultasi',
            pasienId: followUp.pasien_id,
            pasienNama: followUp.pasien_nama,
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
