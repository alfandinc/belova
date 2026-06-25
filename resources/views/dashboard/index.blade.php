@extends('layouts.erm.app')

@section('title', 'Dashboard')

@section('navbar')
    @include('dashboard.navbar')
@endsection

@section('content')
    @php
        $dashboardGreetingHour = now()->hour;
        $dashboardGreeting = $dashboardGreetingHour < 12
            ? 'Good Morning'
            : ($dashboardGreetingHour < 17 ? 'Good Afternoon' : 'Good Evening');
        $dashboardDisplayName = auth()->user()->name ?? 'User';
        $dashboardSubtitle = $employeePosition
            ? 'Custom dashboard for ' . $employeePosition->name . ' - Ringkasan widget Belova Corp'
            : 'Custom dashboard - Ringkasan widget Belova Corp';
        $dashboardFormattedRange = \Carbon\Carbon::parse($dashboardFilter['start_date'])->format('d M Y') . ' - ' . \Carbon\Carbon::parse($dashboardFilter['end_date'])->format('d M Y');
    @endphp

    <style>
        .dashboard-page-shell {
            padding: 8px 0 24px;
        }

        .dashboard-hero {
            background: #ffffff;
            border: 1px solid #ebf0f6;
            border-radius: 18px;
            box-shadow: 0 10px 28px rgba(31, 45, 61, 0.06);
            padding: 18px 22px;
            margin-bottom: 20px;
        }

        .dashboard-hero-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 22px;
            flex-wrap: wrap;
        }

        .dashboard-hero-copy {
            min-width: 0;
            flex: 1 1 420px;
        }

        .dashboard-hero-title {
            margin: 0;
            font-size: 1.35rem;
            line-height: 1.25;
            font-weight: 700;
            color: #233041;
        }

        .dashboard-hero-subtitle {
            margin: 4px 0 0;
            color: #7f8b9c;
            font-size: 0.9rem;
        }

        .dashboard-filter-panel {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            flex-wrap: wrap;
            flex: 0 1 auto;
        }

        .dashboard-filter-group {
            min-width: 320px;
        }

        .dashboard-filter-label {
            display: block;
            margin-bottom: 5px;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #9aa6b6;
        }

        .dashboard-filter-input-wrap {
            position: relative;
            display: block;
        }

        .dashboard-filter-input-wrap .feather {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #7a8798;
            width: 16px;
            height: 16px;
            pointer-events: none;
            stroke-width: 2;
            z-index: 2;
        }

        #dashboard-daterange {
            display: block;
            min-width: 320px;
            width: 100%;
            height: 40px;
            border-radius: 12px;
            border: 1px solid #d9e2ee;
            padding: 9px 12px 9px 38px;
            font-weight: 600;
            color: #334155;
            background: #ffffff;
            box-shadow: none;
            line-height: 20px;
        }

        .dashboard-filter-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            align-self: flex-end;
        }

        .dashboard-filter-actions .btn {
            height: 40px;
            border-radius: 12px;
            padding: 0 16px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .dashboard-filter-actions .btn-primary {
            box-shadow: none;
        }

        .dashboard-hero-wave {
            font-size: 1.2rem;
            vertical-align: middle;
        }

        @media (max-width: 991.98px) {
            .dashboard-hero-row {
                align-items: flex-start;
            }

            .dashboard-filter-panel {
                justify-content: flex-start;
                width: 100%;
            }

            .dashboard-filter-group,
            #dashboard-daterange {
                width: 100%;
            }

            .dashboard-filter-actions {
                width: 100%;
            }

            .dashboard-filter-actions .btn {
                flex: 1 1 0;
            }
        }
    </style>

    <div class="container-fluid mt-4">
        <div class="dashboard-page-shell">
            <div class="dashboard-hero">
                <div class="dashboard-hero-row">
                    <div class="dashboard-hero-copy">
                        <h1 class="dashboard-hero-title">{{ $dashboardGreeting }}, {{ $dashboardDisplayName }} <span class="dashboard-hero-wave">👋</span></h1>
                        <p class="dashboard-hero-subtitle">{{ $dashboardSubtitle }}</p>
                    </div>

                    @if ($employeePosition)
                        <div class="dashboard-filter-panel">
                            <div class="dashboard-filter-group">
                                <label class="dashboard-filter-label">Date Range</label>
                                <div class="dashboard-filter-input-wrap">
                                    <i data-feather="calendar"></i>
                                    <input type="text" id="dashboard-daterange" class="form-control" autocomplete="off" value="{{ $dashboardFormattedRange }}">
                                </div>
                            </div>

                            <div class="dashboard-filter-actions">
                                <button type="button" id="dashboard-filter-apply" class="btn btn-primary">Apply Filter</button>
                                <button type="button" id="dashboard-filter-reset" class="btn btn-outline-secondary">Reset</button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div id="dashboard-widgets-container">
                @include('dashboard.partials.widgets_grid', ['dashboardWidgets' => $dashboardWidgets, 'employeePosition' => $employeePosition, 'dashboardFilter' => $dashboardFilter])
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function () {
            var initialStart = moment(@json($dashboardFilter['start_date']), 'YYYY-MM-DD');
            var initialEnd = moment(@json($dashboardFilter['end_date']), 'YYYY-MM-DD');

            function formatDisplayRange(startDate, endDate) {
                return startDate.format('DD MMM YYYY') + ' - ' + endDate.format('DD MMM YYYY');
            }

            function updateDashboardFilterDisplay(startDate, endDate) {
                var formattedRange = formatDisplayRange(startDate, endDate);

                $('#dashboard-daterange').val(formattedRange);
            }

            function showDashboardAvailabilityAlert(hasEmployeePosition, hasWidgets) {
                if (!hasEmployeePosition || !hasWidgets) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Dashboard Belum Tersedia',
                        text: 'Posisi anda belum memiliki dashboard.',
                        confirmButtonText: 'OK'
                    });
                }
            }

            function loadDashboardWidgets(startDate, endDate) {
                $.ajax({
                    url: '{{ route('dashboard.index') }}',
                    type: 'GET',
                    data: {
                        start_date: startDate.format('YYYY-MM-DD'),
                        end_date: endDate.format('YYYY-MM-DD')
                    },
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function (response) {
                        $('#dashboard-widgets-container').html(response.html || '');
                        updateDashboardFilterDisplay(startDate, endDate);
                        showDashboardAvailabilityAlert(response.hasEmployeePosition, response.hasWidgets);
                    },
                    error: function () {
                        Swal.fire({
                            icon: 'error',
                            title: 'Filter gagal dimuat',
                            text: 'Dashboard tidak bisa diperbarui. Silakan coba lagi.',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            }

            if ($.fn.daterangepicker) {
                $('#dashboard-daterange').daterangepicker({
                    autoUpdateInput: false,
                    autoApply: false,
                    startDate: initialStart,
                    endDate: initialEnd,
                    opens: 'left',
                    locale: {
                        format: 'DD MMM YYYY',
                        cancelLabel: 'Clear'
                    },
                    ranges: {
                        'Today': [moment(), moment()],
                        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                        'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                        'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                        'This Month': [moment().startOf('month'), moment().endOf('month')],
                        'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                        'This Year': [moment().startOf('year'), moment().endOf('year')],
                        'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')]
                    }
                });

                $('#dashboard-daterange').on('apply.daterangepicker', function (ev, picker) {
                    updateDashboardFilterDisplay(picker.startDate, picker.endDate);
                });

                $('#dashboard-daterange').on('cancel.daterangepicker', function () {
                    updateDashboardFilterDisplay(initialStart, initialEnd);
                });
            }

            $('#dashboard-filter-apply').on('click', function () {
                var picker = $('#dashboard-daterange').data('daterangepicker');
                if (!picker) {
                    return;
                }

                loadDashboardWidgets(picker.startDate, picker.endDate);
            });

            $('#dashboard-filter-reset').on('click', function () {
                var start = moment().startOf('month');
                var end = moment();
                var picker = $('#dashboard-daterange').data('daterangepicker');

                if (picker) {
                    picker.setStartDate(start);
                    picker.setEndDate(end);
                }

                loadDashboardWidgets(start, end);
            });

            updateDashboardFilterDisplay(initialStart, initialEnd);

            if (window.feather) {
                window.feather.replace();
            }

            showDashboardAvailabilityAlert(@json((bool) $employeePosition), @json($dashboardWidgets->isNotEmpty()));
        });
    </script>
@endsection
