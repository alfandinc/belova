@extends('layouts.erm.app')

@section('title', 'Dashboard')

@section('navbar')
    @include('dashboard.navbar')
@endsection

@section('content')
    @php
        $dashboardHour = now()->hour;
        $dashboardGreeting = $dashboardHour < 11 ? 'Selamat pagi' : ($dashboardHour < 15 ? 'Selamat siang' : ($dashboardHour < 18 ? 'Selamat sore' : 'Selamat malam'));
        $dashboardDisplayName = auth()->user()->name ?? 'User';
        // Only employees with a position get a chip
        $dashboardPosition = $employeePosition->name ?? null;
        $dashboardFormattedRange = \Carbon\Carbon::parse($dashboardFilter['start_date'])->format('d M Y') . ' - ' . \Carbon\Carbon::parse($dashboardFilter['end_date'])->format('d M Y');
    @endphp

    <style>
        .dsh {
            --dsh-ink: #1e293b;
            --dsh-ink-2: #475569;
            --dsh-muted: #64748b;
            --dsh-faint: #94a3b8;
            --dsh-line: #e2e8f0;
            --dsh-line-soft: #eef2f7;
            --dsh-surface: #ffffff;
            --dsh-surface-2: #f8fafc;
            --dsh-accent: #2563eb;
            --dsh-accent-soft: #eff6ff;
            --dsh-danger: #dc2626;
            --dsh-radius: 14px;
            --dsh-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 4px 16px rgba(15, 23, 42, 0.05);
            color: var(--dsh-ink);
            padding-top: 20px;
            padding-bottom: 32px;
        }

        .dsh-card {
            background: var(--dsh-surface);
            border: 1px solid var(--dsh-line);
            border-radius: var(--dsh-radius);
            box-shadow: var(--dsh-shadow);
        }

        /* Header: hero card tinted with the clinic accent (--hero-* vars come from ClinicTheme on the element) */
        .dsh-hero {
            --hero-accent-text: var(--hero-accent-text-light);
            --hero-tint: rgba(var(--hero-accent-rgb), 0.09);
            position: relative;
            overflow: hidden;
            margin-bottom: 20px;
            padding: 14px 18px;
            border: 1px solid rgba(var(--hero-accent-rgb), 0.18);
            border-radius: 14px;
            background:
                radial-gradient(520px 260px at 100% 0%, rgba(var(--hero-accent-rgb), 0.16), transparent 70%),
                linear-gradient(135deg, var(--hero-tint), transparent 60%),
                var(--dsh-surface);
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 12px 32px -18px rgba(var(--hero-accent-rgb), 0.45);
        }

        html.theme-dark .dsh-hero {
            --hero-accent-text: var(--hero-accent-text-dark);
            --hero-tint: rgba(var(--hero-accent-rgb), 0.16);
            border-color: rgba(var(--hero-accent-rgb), 0.3);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }

        /* Decorative rings in the corner */
        .dsh-hero-deco {
            position: absolute;
            top: -90px;
            right: -60px;
            width: 280px;
            height: 280px;
            border-radius: 50%;
            border: 36px solid rgba(var(--hero-accent-rgb), 0.06);
            pointer-events: none;
        }

        .dsh-hero-deco::after {
            content: '';
            position: absolute;
            inset: 40px;
            border-radius: 50%;
            border: 1px dashed rgba(var(--hero-accent-rgb), 0.25);
        }

        .dsh-hero-top {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .dsh-hero-heading {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            min-width: 0;
        }

        .dsh-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
            line-height: 1.25;
            color: var(--dsh-ink);
            letter-spacing: -0.01em;
        }

        .dsh-title-name {
            background: linear-gradient(90deg, var(--hero-accent-text), var(--hero-accent));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .dsh-chip {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 10px;
            border-radius: 999px;
            background: rgba(var(--hero-accent-rgb), 0.12);
            color: var(--hero-accent-text);
            font-size: 0.75rem;
            font-weight: 600;
        }

        .dsh-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .dsh-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            height: 38px;
            padding: 0 14px;
            border-radius: 10px;
            border: 1px solid var(--dsh-line);
            background: var(--dsh-surface);
            color: var(--dsh-ink-2);
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            white-space: nowrap;
            transition: border-color 0.15s ease, background 0.15s ease, color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
        }

        .dsh-btn:hover,
        .dsh-btn:focus {
            border-color: var(--dsh-faint);
            background: var(--dsh-surface-2);
            color: var(--dsh-ink);
            text-decoration: none;
        }

        .dsh-btn-primary {
            background: linear-gradient(135deg, var(--hero-accent), var(--hero-accent-deep));
            border-color: transparent;
            color: #fff;
            box-shadow: 0 8px 18px -10px rgba(var(--hero-accent-rgb), 0.9);
        }

        .dsh-btn-primary:hover,
        .dsh-btn-primary:focus {
            background: linear-gradient(135deg, var(--hero-accent), var(--hero-accent-deep));
            border-color: transparent;
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 10px 22px -10px rgba(var(--hero-accent-rgb), 1);
        }

        .dsh-btn .feather {
            width: 16px;
            height: 16px;
        }

        .dsh-btn-count {
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            border-radius: 999px;
            background: var(--dsh-danger);
            color: #fff;
            font-size: 0.7rem;
            line-height: 20px;
            text-align: center;
        }

        /* Akses cepat: the role's most used pages */
        .dsh-quick {
            position: relative;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px dashed rgba(var(--hero-accent-rgb), 0.25);
        }

        .dsh-quick-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
            gap: 8px;
        }

        .dsh-quick-item {
            position: relative;
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
            padding: 7px 10px;
            border-radius: 10px;
            border: 1px solid var(--dsh-line);
            background: var(--dsh-surface);
            color: var(--dsh-ink);
            text-decoration: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
        }

        .dsh-quick-item:hover,
        .dsh-quick-item:focus {
            border-color: rgba(var(--hero-accent-rgb), 0.45);
            box-shadow: 0 8px 20px -14px rgba(var(--hero-accent-rgb), 0.8);
            transform: translateY(-2px);
            color: var(--dsh-ink);
            text-decoration: none;
        }

        .dsh-quick-icon {
            flex-shrink: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: rgba(var(--hero-accent-rgb), 0.12);
            color: var(--hero-accent-text);
            transition: background 0.15s ease, color 0.15s ease;
        }

        .dsh-quick-item:hover .dsh-quick-icon,
        .dsh-quick-item:focus .dsh-quick-icon {
            background: linear-gradient(135deg, var(--hero-accent), var(--hero-accent-deep));
            color: #fff;
        }

        .dsh-quick-icon .feather {
            width: 18px;
            height: 18px;
        }

        .dsh-quick-text {
            flex: 1 1 auto;
            min-width: 0;
        }

        .dsh-quick-title,
        .dsh-quick-hint {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dsh-quick-title {
            font-size: 0.875rem;
            font-weight: 600;
        }

        .dsh-quick-hint {
            font-size: 0.75rem;
            color: var(--dsh-muted);
        }

        .dsh-quick-arrow {
            flex-shrink: 0;
            width: 16px;
            height: 16px;
            color: var(--dsh-faint);
            transition: transform 0.15s ease, color 0.15s ease;
        }

        .dsh-quick-item:hover .dsh-quick-arrow {
            transform: translateX(2px);
            color: var(--hero-accent-text);
        }

        @media (max-width: 575.98px) {
            .dsh-hero {
                padding: 12px 14px;
            }

            .dsh-title {
                font-size: 1.1rem;
            }

            .dsh-quick-grid {
                grid-template-columns: 1fr 1fr;
            }

            .dsh-quick-hint,
            .dsh-quick-arrow {
                display: none;
            }
        }

        .dsh-empty {
            text-align: center;
            padding: 28px 16px;
            color: var(--dsh-muted);
        }

        .dsh-empty-icon {
            width: 44px;
            height: 44px;
            margin: 0 auto 10px;
            border-radius: 12px;
            background: var(--dsh-surface-2);
            border: 1px solid var(--dsh-line);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dsh-faint);
        }

        .dsh-empty-icon .feather {
            width: 20px;
            height: 20px;
        }

        .dsh-empty-title {
            font-weight: 600;
            color: var(--dsh-ink-2);
            margin-bottom: 2px;
        }

        .dsh-empty-text {
            font-size: 0.85rem;
            margin: 0;
        }

        /* Widget section */
        .dsh-section-head {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin: 8px 0 14px;
            padding-top: 20px;
            border-top: 1px solid var(--dsh-line);
        }

        .dsh-section-title {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--dsh-ink);
        }

        .dsh-section-sub {
            font-size: 0.8rem;
            color: var(--dsh-muted);
        }

        .dsh-filter {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .dsh-filter-input {
            position: relative;
        }

        .dsh-filter-input .feather {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            color: var(--dsh-muted);
            pointer-events: none;
            z-index: 2;
        }

        #dashboard-daterange {
            min-width: 260px;
            height: 38px;
            border-radius: 10px;
            border: 1px solid var(--dsh-line);
            padding: 8px 12px 8px 36px;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--dsh-ink-2);
            background: var(--dsh-surface);
            box-shadow: none;
        }

        .dsh-widgets-empty {
            border: 1px dashed var(--dsh-faint);
            border-radius: var(--dsh-radius);
            background: var(--dsh-surface-2);
            padding: 36px 16px;
        }

        @media (max-width: 575.98px) {
            .dsh-actions,
            .dsh-actions .dsh-btn,
            .dsh-filter,
            .dsh-filter-input,
            #dashboard-daterange {
                width: 100%;
            }

            .dsh-actions .dsh-btn {
                justify-content: center;
            }

            .dsh-filter .dsh-btn {
                flex: 1 1 0;
                justify-content: center;
            }
        }
    </style>
    @include('dashboard.partials.widget_styles')

    <div class="container-fluid dsh">
        {{-- Pages that redirect home (e.g. HRD self-service without employee data) explain why here --}}
        @foreach (['error' => 'danger', 'warning' => 'warning', 'success' => 'success'] as $flashKey => $flashClass)
            @if (session($flashKey))
                <div class="alert alert-{{ $flashClass }} alert-dismissible fade show" role="alert">
                    {{ session($flashKey) }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
            @endif
        @endforeach

        {{-- Header --}}
        <section class="dsh-hero" style="{{ \App\Support\ClinicTheme::cssVars('hero') }}">
            <div class="dsh-hero-deco" aria-hidden="true"></div>
            <div class="dsh-hero-top">
                <div class="dsh-hero-heading">
                    <h1 class="dsh-title">{{ $dashboardGreeting }}, <span class="dsh-title-name">{{ $dashboardDisplayName }}</span></h1>
                    @if ($dashboardPosition)
                        <span class="dsh-chip">{{ $dashboardPosition }}</span>
                    @endif
                </div>
                <div class="dsh-actions">
                    <a href="#" class="dsh-btn js-open-jadwal">
                        <i data-feather="calendar"></i> Jadwal Saya
                        @if ($jadwalUnread > 0)
                            <span class="dsh-btn-count jv-unread" title="Jadwal Anda diubah">{{ $jadwalUnread }}</span>
                        @endif
                    </a>
                    <a href="{{ route('daily-journal.index') }}" class="dsh-btn dsh-btn-primary js-open-journal">
                        <i data-feather="plus"></i> Daily Journal
                    </a>
                </div>
            </div>

            {{-- Shortcuts to the pages the user's roles open most (App\Support\Dashboard\QuickLinks) --}}
            @if (!empty($quickLinks))
                <div class="dsh-quick">
                    <div class="dsh-quick-grid">
                        @foreach ($quickLinks as $quickLink)
                            <a href="{{ $quickLink['url'] }}" class="dsh-quick-item">
                                <span class="dsh-quick-icon"><i data-feather="{{ $quickLink['icon'] }}"></i></span>
                                <span class="dsh-quick-text">
                                    <span class="dsh-quick-title">{{ $quickLink['label'] }}</span>
                                    <span class="dsh-quick-hint">{{ $quickLink['hint'] }}</span>
                                </span>
                                @if ($quickLink['count'] > 0)
                                    <span class="dsh-btn-count" title="Perlu ditindaklanjuti">{{ $quickLink['count'] }}</span>
                                @else
                                    <i data-feather="chevron-right" class="dsh-quick-arrow"></i>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>

        {{-- Widgets per role permission (config/dashboard_widgets.php, Admin Panel > Widget Dashboard) --}}
        <div class="dsh-section-head">
            <div>
                <h2 class="dsh-section-title">Widget</h2>
                <div class="dsh-section-sub">
                    {{ $dashboardWidgets->contains('uses_period', true) ? 'Sesuai akses role Anda. Pilihan periode berlaku untuk widget yang menampilkan rentang tanggal.' : 'Sesuai akses role Anda.' }}
                </div>
            </div>
            @if ($dashboardWidgets->contains('uses_period', true))
                <div class="dsh-filter">
                    <div class="dsh-filter-input">
                        <i data-feather="calendar"></i>
                        <input type="text" id="dashboard-daterange" class="form-control" autocomplete="off" value="{{ $dashboardFormattedRange }}" aria-label="Periode widget">
                    </div>
                    <button type="button" id="dashboard-filter-reset" class="dsh-btn">Reset</button>
                </div>
            @endif
        </div>

        <div id="dashboard-widgets-container">
            @include('dashboard.partials.widgets_grid', ['dashboardWidgets' => $dashboardWidgets, 'employeePosition' => $employeePosition, 'dashboardFilter' => $dashboardFilter])
        </div>
    </div>

    {{-- Sumber Omset > "Lihat semua": all sold items per category, loaded from dashboard.omset-items --}}
    <div class="modal fade" id="dashboard-items-modal" tabindex="-1" role="dialog" aria-labelledby="dashboard-items-title" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
            <div class="modal-content dsh dw-modal">
                <div class="modal-header">
                    <h5 class="modal-title dw-title" id="dashboard-items-title">Penjualan per item</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <nav class="dw-tabs" role="tablist">
                        @foreach (\App\Support\Dashboard\Revenue::CATEGORIES as $categoryKey => $categoryLabel)
                            <a href="#" data-items-tab="{{ $categoryKey }}" role="tab">{{ $categoryLabel }}</a>
                        @endforeach
                    </nav>
                    <div class="dw-search">
                        <i data-feather="search"></i>
                        <input type="search" class="js-items-search" placeholder="Cari item…" aria-label="Cari item">
                    </div>
                    <div class="js-items-body"></div>
                </div>
            </div>
        </div>
    </div>
@endsection


@section('scripts')
    <script>
        $(function () {
            var container = $('#dashboard-widgets-container');
            var initialStart = moment(@json($dashboardFilter['start_date']), 'YYYY-MM-DD');
            var initialEnd = moment(@json($dashboardFilter['end_date']), 'YYYY-MM-DD');
            var charts = [];

            function rupiah(value) {
                return 'Rp ' + Math.round(value || 0).toLocaleString('id-ID');
            }

            function rupiahShort(value) {
                var abs = Math.abs(value || 0);
                if (abs >= 1e9) return 'Rp ' + (value / 1e9).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' M';
                if (abs >= 1e6) return 'Rp ' + (value / 1e6).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' jt';
                if (abs >= 1e3) return 'Rp ' + Math.round(value / 1e3).toLocaleString('id-ID') + ' rb';
                return rupiah(value);
            }

            /*
             * Charts from data-dash-chart JSON: { type: area|line|bar, format: rupiah|number, height,
             * categories: [...], series: [{ name, values, color: '--token', dashed }] }.
             * One y-axis, 2px lines, recessive grid, shared crosshair tooltip with exact values,
             * legend only when there is more than one series.
             */
            // scope: the widget slot(s) whose charts to (re)draw; charts in other widgets are left alone
            function initCharts(scope) {
                charts = charts.filter(function (entry) {
                    if ($.contains(scope[0], entry.el) || !document.body.contains(entry.el)) {
                        entry.chart.destroy();
                        return false;
                    }
                    return true;
                });
                if (!window.ApexCharts) {
                    return;
                }

                // Colors come from the theme tokens, so light and dark each get their own steps
                var css = getComputedStyle(container.closest('.dsh')[0] || document.body);
                var token = function (name, fallback) { return (css.getPropertyValue(name) || '').trim() || fallback; };
                var ink = token('--dsh-muted', '#64748b');

                scope.find('[data-dash-chart]').each(function () {
                    var cfg = $(this).data('dash-chart');
                    var money = cfg.format === 'rupiah';
                    var type = cfg.type || 'area';
                    var series = cfg.series || [{ name: cfg.name, values: cfg.values }];
                    var number = function (v) { return Math.round(v || 0).toLocaleString('id-ID'); };
                    var chart = new ApexCharts(this, {
                        chart: { type: type, height: cfg.height || 210, fontFamily: 'inherit', toolbar: { show: false }, zoom: { enabled: false }, animations: { enabled: false }, background: 'transparent' },
                        series: series.map(function (s) { return { name: s.name, data: s.values }; }),
                        colors: series.map(function (s, i) { return token(s.color || '--dw-series-' + (i + 1), '#2a78d6'); }),
                        stroke: type === 'bar' ? { width: 0 } : { width: 2, curve: 'straight', dashArray: series.map(function (s) { return s.dashed ? 5 : 0; }) },
                        fill: type === 'area'
                            ? { type: 'gradient', gradient: { shadeIntensity: 0, opacityFrom: series.map(function (s) { return s.dashed ? 0 : 0.18; }), opacityTo: 0.02 } }
                            : { opacity: 1 },
                        plotOptions: { bar: { columnWidth: '58%', borderRadius: 4, borderRadiusApplication: 'end' } },
                        dataLabels: { enabled: false },
                        markers: { size: 0, hover: { size: 5 } },
                        legend: { show: series.length > 1, position: 'top', horizontalAlign: 'left', fontSize: '12px', labels: { colors: ink }, markers: { width: 10, height: 10, radius: 3 } },
                        grid: { borderColor: token('--dw-grid', '#eef0f3'), xaxis: { lines: { show: false } }, padding: { left: 8, right: 8 } },
                        xaxis: {
                            categories: cfg.categories,
                            tickAmount: type === 'bar' ? undefined : Math.min(7, cfg.categories.length),
                            labels: { rotate: 0, hideOverlappingLabels: true, style: { colors: ink, fontSize: '11px' } },
                            axisBorder: { color: token('--dsh-line', '#e2e8f0') },
                            axisTicks: { show: false },
                            tooltip: { enabled: false },
                            crosshairs: { stroke: { color: token('--dsh-faint', '#94a3b8'), width: 1, dashArray: 0 } }
                        },
                        yaxis: { min: 0, forceNiceScale: true, labels: { formatter: money ? rupiahShort : number, style: { colors: ink, fontSize: '11px' } } },
                        tooltip: { theme: document.documentElement.classList.contains('theme-dark') ? 'dark' : 'light', shared: true, intersect: false, y: { formatter: money ? rupiah : number } }
                    });
                    chart.render();
                    charts.push({ el: this, chart: chart });
                });
            }

            // Buku Menu: three server-side tables behind tabs, one search box for whichever tab is open
            function initBukuMenu(scope) {
                if (!$.fn.DataTable) {
                    return;
                }

                // A failed load (e.g. expired session) shows a message in the table, not a browser alert()
                $.fn.dataTable.ext.errMode = 'none';

                scope.find('[data-dash-buku-menu]').each(function () {
                    var box = $(this);
                    var tables = {};
                    var current = 'obat';
                    var timer = null;
                    var base = {
                        processing: true, serverSide: true, dom: 'rtip', pageLength: 6, lengthChange: false, autoWidth: false,
                        order: [[0, 'asc']],
                        language: {
                            processing: 'Memuat…', zeroRecords: 'Tidak ditemukan', infoEmpty: 'Tidak ada data', infoFiltered: '',
                            info: '_START_–_END_ dari _TOTAL_', paginate: { previous: '‹', next: '›' }
                        }
                    };
                    var columns = {
                        obat: [
                            { data: 'nama', name: 'nama' },
                            { data: 'satuan', name: 'satuan' },
                            { data: 'total_stok', name: 'total_stok', searchable: false, className: 'dw-num' },
                            { data: 'harga_nonfornas', name: 'harga_nonfornas', searchable: false, className: 'dw-num' }
                        ],
                        tindakan: [
                            { data: 'nama', name: 'nama' },
                            { data: 'jenis_harga', name: 'jenis_harga', orderable: false, searchable: false },
                            { data: 'list_harga', name: 'list_harga', orderable: false, searchable: false }
                        ],
                        lab: [
                            { data: 'nama', name: 'nama' },
                            { data: 'kategori', name: 'kategori', orderable: false, searchable: false },
                            { data: 'harga', name: 'harga', orderable: false, searchable: false }
                        ]
                    };

                    function table(key) {
                        if (!tables[key]) {
                            var el = box.find('[data-bm-table="' + key + '"]');
                            el.on('error.dt', function () {
                                el.find('tbody').html('<tr><td colspan="' + columns[key].length + '" class="text-center text-muted">Data gagal dimuat. Muat ulang halaman.</td></tr>');
                            });
                            tables[key] = el.DataTable($.extend({}, base, {
                                ajax: box.data(key + '-url'),
                                columns: columns[key]
                            }));
                        }
                        return tables[key];
                    }

                    table('obat');

                    box.on('click', '[data-bm-tab]', function (e) {
                        e.preventDefault();
                        current = $(this).data('bm-tab');
                        box.find('[data-bm-tab]').removeClass('active');
                        $(this).addClass('active');
                        box.find('[data-bm-pane]').attr('hidden', true);
                        box.find('[data-bm-pane="' + current + '"]').attr('hidden', false);
                        table(current).search(box.find('.js-bm-search').val()).draw();
                    });

                    box.on('input', '.js-bm-search', function () {
                        var value = this.value;
                        clearTimeout(timer);
                        timer = setTimeout(function () { table(current).search(value).draw(); }, 300);
                    });
                });
            }

            function initWidgets(scope) {
                initCharts(scope);
                initBukuMenu(scope);
                if (window.feather) {
                    window.feather.replace();
                }
            }

            // Widget-level options (clinic select, Saya/Tim switch, ...) travel with every reload of that widget
            function collectWidgetFilters(scope) {
                var params = {};
                scope.find('[data-dashboard-filter-input]').each(function () {
                    var input = $(this);
                    var name = input.attr('name');
                    if (!name || input.is(':disabled') || (input.is(':radio, :checkbox') && !input.is(':checked'))) {
                        return;
                    }
                    params[name] = input.val();
                });
                return params;
            }

            function activeRange() {
                var picker = $('#dashboard-daterange').data('daterangepicker');
                return picker
                    ? { start: picker.startDate.clone(), end: picker.endDate.clone() }
                    : { start: initialStart.clone(), end: initialEnd.clone() };
            }

            function showRange(start, end) {
                $('#dashboard-daterange').val(start.format('DD MMM YYYY') + ' - ' + end.format('DD MMM YYYY'));
            }

            /*
             * Each widget loads on its own (dashboard.widget), a few at a time, so the page shows at once
             * and a slow widget only delays itself. A newer request for a slot supersedes an older one.
             */
            var MAX_PARALLEL = 4;
            var queue = [];
            var running = 0;

            function loadWidget(slot) {
                var range = activeRange();
                var ticket = (slot.data('ticket') || 0) + 1;
                slot.data('ticket', ticket).addClass('is-loading').attr('aria-busy', 'true');

                return $.ajax({
                    url: slot.data('widget-url'),
                    type: 'GET',
                    data: $.extend(collectWidgetFilters(slot), {
                        start_date: range.start.format('YYYY-MM-DD'),
                        end_date: range.end.format('YYYY-MM-DD')
                    }),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                }).done(function (response) {
                    if (slot.data('ticket') !== ticket) {
                        return;
                    }
                    slot.html(response.html || '');
                    initWidgets(slot);
                }).fail(function (xhr) {
                    if (slot.data('ticket') !== ticket || xhr.statusText === 'abort') {
                        return;
                    }
                    // Session gone: the whole page has to go back to login
                    if (xhr.status === 401 || xhr.status === 419) {
                        window.location.reload();
                        return;
                    }
                    slot.find('.dw-body').html(
                        '<div class="dsh-empty"><div class="dsh-empty-title">Widget gagal dimuat</div>' +
                        '<p class="dsh-empty-text"><a href="#" class="js-widget-retry">Coba lagi</a></p></div>'
                    );
                }).always(function () {
                    if (slot.data('ticket') === ticket) {
                        slot.removeClass('is-loading').removeAttr('aria-busy');
                    }
                });
            }

            function pump() {
                while (running < MAX_PARALLEL && queue.length) {
                    running++;
                    loadWidget(queue.shift()).always(function () {
                        running--;
                        pump();
                    });
                }
            }

            function queueWidgets(slots) {
                slots.each(function () {
                    var slot = $(this);
                    if (!slot.data('widget-url')) {
                        return;
                    }
                    queue = queue.filter(function (queued) { return queued[0] !== slot[0]; });
                    queue.push(slot);
                });
                pump();
            }

            // The date range only matters to widgets that use the period
            function reloadPeriodWidgets() {
                queueWidgets(container.find('[data-widget-slot][data-uses-period="1"]'));
            }

            if ($.fn.daterangepicker && $('#dashboard-daterange').length) {
                $('#dashboard-daterange').daterangepicker({
                    autoUpdateInput: false,
                    startDate: initialStart,
                    endDate: initialEnd,
                    opens: 'left',
                    locale: { format: 'DD MMM YYYY', applyLabel: 'Pilih', cancelLabel: 'Batal', customRangeLabel: 'Pilih tanggal' },
                    ranges: {
                        'Hari ini': [moment(), moment()],
                        'Kemarin': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                        '7 hari terakhir': [moment().subtract(6, 'days'), moment()],
                        '30 hari terakhir': [moment().subtract(29, 'days'), moment()],
                        'Bulan ini': [moment().startOf('month'), moment()],
                        'Bulan lalu': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                        'Tahun ini': [moment().startOf('year'), moment()]
                    }
                });

                // Picking a range applies it right away
                $('#dashboard-daterange').on('apply.daterangepicker', function (ev, picker) {
                    showRange(picker.startDate, picker.endDate);
                    reloadPeriodWidgets();
                });
            }

            $('#dashboard-filter-reset').on('click', function () {
                var picker = $('#dashboard-daterange').data('daterangepicker');
                if (picker) {
                    picker.setStartDate(moment().startOf('month'));
                    picker.setEndDate(moment());
                }
                var range = activeRange();
                showRange(range.start, range.end);
                reloadPeriodWidgets();
            });

            // A widget's own option (clinic, Saya/Tim, ...) reloads just that widget
            $(document).on('change', '#dashboard-widgets-container [data-dashboard-filter-input]', function () {
                queueWidgets($(this).closest('[data-widget-slot]'));
            });

            $(document).on('click', '#dashboard-widgets-container .js-widget-retry', function (e) {
                e.preventDefault();
                queueWidgets($(this).closest('[data-widget-slot]'));
            });

            // The theme toggle flips html.theme-dark: redraw charts with the other theme's colors
            if (window.MutationObserver) {
                var isDarkTheme = document.documentElement.classList.contains('theme-dark');
                new MutationObserver(function () {
                    var nowDark = document.documentElement.classList.contains('theme-dark');
                    if (nowDark !== isDarkTheme) {
                        isDarkTheme = nowDark;
                        initCharts(container);
                    }
                }).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
            }

            // Sumber Omset > "Lihat semua": every item of a category for the widget's period, with search and sorting
            var itemsModal = $('#dashboard-items-modal');
            var itemsUrl = null;
            var itemsRequest = null;

            function loadItems(category) {
                itemsModal.find('[data-items-tab]').removeClass('active').filter('[data-items-tab="' + category + '"]').addClass('active');
                var body = itemsModal.find('.js-items-body').html('<div class="dw-note mt-0">Memuat…</div>');
                if (itemsRequest) {
                    itemsRequest.abort();
                }
                itemsRequest = $.get(itemsUrl, { category: category }).done(function (response) {
                    body.html(response.html || '');
                    body.find('[data-dash-items-table] thead th').each(function (i) {
                        $(this).attr('data-sort-col', i);
                    });
                    filterItems();
                    if (window.feather) {
                        window.feather.replace();
                    }
                }).fail(function (xhr) {
                    if (xhr.statusText !== 'abort') {
                        body.html('<div class="dw-note mt-0">Data gagal dimuat.</div>');
                    }
                });
            }

            function filterItems() {
                var term = (itemsModal.find('.js-items-search').val() || '').toLowerCase();
                itemsModal.find('[data-dash-items-table] tbody tr').each(function () {
                    $(this).toggle($(this).children().eq(1).text().toLowerCase().indexOf(term) !== -1);
                });
            }

            $(document).on('click', '#dashboard-widgets-container [data-dash-items]', function () {
                itemsUrl = $(this).data('dash-items');
                itemsModal.find('.js-items-search').val('');
                loadItems($(this).data('category'));
                itemsModal.modal('show');
            });

            itemsModal.on('click', '[data-items-tab]', function (e) {
                e.preventDefault();
                loadItems($(this).data('items-tab'));
            });

            itemsModal.on('input', '.js-items-search', filterItems);

            // Text columns sort A–Z, number columns (data-order) largest first; clicking again reverses
            itemsModal.on('click', '[data-sort-col]', function () {
                var th = $(this);
                var col = +th.data('sort-col');
                var desc = th.hasClass('is-sorted') ? !th.data('desc') : col > 1;
                var tbody = th.closest('table').find('tbody');
                var key = function (row) {
                    var cell = $(row).children().eq(col);
                    var order = cell.attr('data-order');
                    return order !== undefined ? parseFloat(order) : cell.text().trim().toLowerCase();
                };
                var rows = tbody.children('tr').get().sort(function (a, b) {
                    var x = key(a), y = key(b);
                    return (x < y ? -1 : x > y ? 1 : 0) * (desc ? -1 : 1);
                });
                th.siblings().removeClass('is-sorted');
                th.addClass('is-sorted').data('desc', desc);
                tbody.append(rows);
            });

            showRange(initialStart, initialEnd);
            initWidgets(container);
            queueWidgets(container.find('[data-widget-slot]'));
        });
    </script>
@endsection
