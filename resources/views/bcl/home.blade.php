@extends('layouts.bcl.app')
@section('title', 'BCL | Home')
@section('navbar')
    @include('layouts.bcl.navbar')
@endsection


@section('content')
<?php
function convert($sum)
{
    $years = floor($sum / 365);
    $months = floor(($sum - ($years * 365)) / 30.5);
    $days = round($sum - ($years * 365) - ($months * 30.5));
    // echo "Days received: " . $sum . " days <br />";
    if ($years == 0) $years = "";
    else $years = $years . " Tahun, ";
    if ($months == 0) $months = "";
    else $months = $months . " Bulan, ";
    if ($days == 0) $days = "0 Hari";
    else $days = $days . " Hari";
    return $years . $months  . $days;
}
?>
<!-- Page-Title -->
<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
            <div class="row">
                <div class="col">
                    <h4 class="page-title">Dashboard</h4>
                    <span>{{config('app.name')}}</span>
                </div><!--end col-->
                <div class="col-auto align-self-center">
                    <a href="#" class="btn btn-sm btn-outline-primary" id="dashboard-filter-range">
                        <span class="day-name" id="dashboard-filter-label">Periode:</span>&nbsp;
                        <span id="dashboard-filter-text">{{ data_get($response, 'filter.label', now()->format('Y')) }}</span>
                        <i data-feather="calendar" class="align-self-center icon-xs ml-1"></i>
                    </a>
                </div><!--end col-->
            </div><!--end row-->
        </div>
        <div class="row">
            <div class="col-lg-9">
                <div class="row justify-content-center">
                    <div class="col-md-6 col-lg-4">
                        <div class="card report-card">
                            <a href="{{route('bcl.rooms')}}">
                                <div class="card-body">
                                    <div class="row d-flex justify-content-center">
                                        <div class="col">
                                            <p class="text-dark mb-0 font-weight-semibold">Kamar Terisi</p>
                                            <h3 class="m-0">{{$response->rooms->used.' dari '.$response->rooms->total}}</h3>
                                            <p class="mb-0 text-truncate text-muted"> Kamar Terisi</p>
                                        </div>
                                        <div class="col-auto align-self-center">
                                            <div class="report-main-icon bg-light-alt">
                                                <i data-feather="users" class="align-self-center text-muted icon-sm"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div><!--end card-body-->
                            </a>
                        </div><!--end card-->
                    </div> <!--end col-->
                    <div class="col-md-6 col-lg-4">
                        <div class="card report-card">
                            <a href="{{route('bcl.income.index')}}">
                                <div class="card-body">
                                    <div class="row d-flex justify-content-center">
                                        <div class="col">
                                            <p class="text-dark mb-0 font-weight-semibold">Total Revenue</p>
                                            <h3 class="m-0" id="total-revenue-value">Rp {{ number_format(data_get($response, 'total_revenue', 0), 0, ',', '.') }}</h3>
                                            <p class="mb-0 text-truncate text-muted" id="total-revenue-subtitle">Periode {{ data_get($response, 'filter.label', now()->format('Y')) }}</p>
                                        </div>
                                        <div class="col-auto align-self-center">
                                            <div class="report-main-icon bg-light-alt">
                                                <i data-feather="dollar-sign" class="align-self-center text-muted icon-sm"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div><!--end card-body-->
                            </a>
                        </div><!--end card-->
                    </div> <!--end col-->
                    <div class="col-md-6 col-lg-4">
                        <div class="card report-card">
                            <a href="{{route('bcl.inventories.index')}}">
                                <div class="card-body">
                                    <div class="row d-flex justify-content-center">
                                        <div class="col">
                                            <p class="text-dark mb-0 font-weight-semibold">Perawaran Inventaris</p>
                                            <h3 class="m-0">{{$response->needed_maintanance}}</h3>
                                            <p class="mb-0 text-truncate text-muted">Dibutuhkan Perawatan segera</p>
                                        </div>
                                        <div class="col-auto align-self-center">
                                            <div class="report-main-icon bg-light-alt">
                                                <i data-feather="activity" class="align-self-center text-muted icon-sm"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div><!--end card-body-->
                            </a>
                        </div><!--end card-->
                    </div> <!--end col-->
                </div><!--end row-->
                <div class="card">

                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h4 class="card-title">Room Stat. by Revenue <span id="room-stat-period">{{ data_get($response, 'filter.label', now()->format('Y')) }}</span> (Top 10)</h4>
                            </div><!--end col-->
                        </div> <!--end row-->
                    </div><!--end card-header-->
                    <div class="card-body">
                        <div id="barchart" class="apex-charts ml-n4"></div>
                    </div><!--end card-body-->

                </div><!--end card-->
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h4 class="card-title">Revenue Paket Sewa <span id="package-period-title">{{ data_get($response, 'filter.label', now()->format('Y')) }}</span></h4>
                            </div>
                            <div class="col-auto text-right">
                                <span class="badge badge-soft-primary" id="package-total-transactions">{{ number_format(data_get($response, 'period_stats.total_transactions', 0)) }} transaksi</span>
                                <span class="badge badge-soft-success" id="package-total-revenue">Rp {{ number_format(data_get($response, 'period_stats.total_revenue', 0), 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>Paket Sewa</th>
                                        <th class="text-right">Jumlah Sewa</th>
                                        <th class="text-right">Total Revenue</th>
                                    </tr>
                                </thead>
                                <tbody id="package-table-body">
                                    @forelse(data_get($response, 'period_stats.items', []) as $item)
                                        <tr>
                                            <td>{{ $item->label }}</td>
                                            <td class="text-right">{{ number_format($item->total_transactions) }}</td>
                                            <td class="text-right">Rp {{ number_format($item->total_revenue, 0, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">Belum ada transaksi sewa untuk tahun ini.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div><!--end col-->
            <div class="col-lg-3">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h4 class="card-title">Revenue Paket Sewa</h4>
                            </div><!--end col-->
                            <div class="col-auto">
                                <span class="text-muted small" id="package-side-period">{{ data_get($response, 'filter.label', now()->format('Y')) }}</span>
                            </div><!--end col-->
                        </div> <!--end row-->
                    </div><!--end card-header-->
                    <div class="card-body">
                        <div class="text-center">
                            <div id="ana_device" class="apex-charts"></div>
                            <h6 class="bg-light-alt py-3 px-2 mb-0">
                                <i data-feather="calendar" class="align-self-center icon-xs mr-1"></i>
                                Breakdown Paket Sewa
                            </h6>
                        </div>
                        <div class="table-responsive mt-2">
                            <table class="table border-dashed mb-0">
                                <thead>
                                    <tr>
                                        <th>Paket</th>
                                        <th class="text-right">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody id="package-side-table-body">
                                    @forelse(collect(data_get($response, 'period_stats.items', []))->take(5) as $item)
                                        <tr>
                                            <td>{{ $item->label }}</td>
                                            <td class="text-right">{{ number_format($item->total_transactions) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-center text-muted">Tidak ada data.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table><!--end /table-->
                        </div><!--end /div-->
                    </div><!--end card-body-->
                </div><!--end card-->
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Top 5 Penyewa Terlama</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table border-dashed mb-0">
                                <thead>
                                    <tr>
                                        <th>Nama</th>
                                        <th class="text-right">Total Lama Sewa</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($response->ranking_penyewa->original->take(5) as $data)
                                    <tr>
                                        <td class="text-truncated">{{ data_get($data, 'renter.nama', '-') }}</td>
                                        <td class="text-right">{{ convert(data_get($data, 'total_lama_sewa', 0)) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div> <!--end col-->
        </div><!--end row-->

        <!--end page-title-box-->
    </div><!--end col-->
</div><!--end row-->
@section('pagescript')
<script>
    var dashboardDataUrl = @json(route('bcl.dashboard.data'));
    var dashboardStartDate = @json(data_get($response, 'filter.start_date', now()->startOfYear()->format('Y-m-d')));
    var dashboardEndDate = @json(data_get($response, 'filter.end_date', now()->endOfYear()->format('Y-m-d')));
    var rooms = {!! json_encode(data_get($response, 'room_stat.room_name', [])) !!};
    var total_value = {!! json_encode(data_get($response, 'room_stat.total_value', [])) !!};
    var period_labels = {!! json_encode(data_get($response, 'period_stats.labels', [])) !!};
    var period_counts = {!! json_encode(data_get($response, 'period_stats.counts', [])) !!};
    var period_revenues = {!! json_encode(data_get($response, 'period_stats.revenues', [])) !!};
</script>
<script src="{{ asset('dastone/plugins/apex-charts/apexcharts.min.js') }}"></script>
<script>
    (function () {
        var currencyFormatter = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0
        });
        var numberFormatter = new Intl.NumberFormat('id-ID');
        var roomChart = null;
        var packageChart = null;

        function escapeHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function setFilterText(label) {
            $('#dashboard-filter-text').text(label || '-');
            $('#room-stat-period').text(label || '-');
            $('#package-period-title').text(label || '-');
            $('#package-side-period').text(label || '-');
            $('#total-revenue-subtitle').text('Periode ' + (label || '-'));
        }

        function renderPackageTable(items) {
            var rows = [];
            if (!items.length) {
                rows.push('<tr><td colspan="3" class="text-center text-muted">Belum ada transaksi sewa untuk periode ini.</td></tr>');
            } else {
                items.forEach(function (item) {
                    rows.push(
                        '<tr>' +
                            '<td>' + escapeHtml(item.label) + '</td>' +
                            '<td class="text-right">' + numberFormatter.format(item.total_transactions || 0) + '</td>' +
                            '<td class="text-right">' + currencyFormatter.format(item.total_revenue || 0) + '</td>' +
                        '</tr>'
                    );
                });
            }

            $('#package-table-body').html(rows.join(''));
        }

        function renderSidePackageTable(items) {
            var rows = [];
            var topItems = (items || []).slice(0, 5);

            if (!topItems.length) {
                rows.push('<tr><td colspan="2" class="text-center text-muted">Tidak ada data.</td></tr>');
            } else {
                topItems.forEach(function (item) {
                    rows.push(
                        '<tr>' +
                            '<td>' + escapeHtml(item.label) + '</td>' +
                            '<td class="text-right">' + numberFormatter.format(item.total_transactions || 0) + '</td>' +
                        '</tr>'
                    );
                });
            }

            $('#package-side-table-body').html(rows.join(''));
        }

        function updateDashboardView(payload) {
            var filter = payload.filter || {};
            var roomStat = payload.room_stat || { room_name: [], total_value: [] };
            var periodStats = payload.period_stats || { labels: [], counts: [], revenues: [], items: [], total_transactions: 0, total_revenue: 0 };

            rooms = roomStat.room_name || [];
            total_value = roomStat.total_value || [];
            period_labels = periodStats.labels || [];
            period_counts = periodStats.counts || [];
            period_revenues = periodStats.revenues || [];

            setFilterText(filter.label || '-');
            $('#total-revenue-value').text(currencyFormatter.format(payload.total_revenue || 0));
            $('#package-total-transactions').text(numberFormatter.format(periodStats.total_transactions || 0) + ' transaksi');
            $('#package-total-revenue').text(currencyFormatter.format(periodStats.total_revenue || 0));

            renderPackageTable(periodStats.items || []);
            renderSidePackageTable(periodStats.items || []);

            if (roomChart) {
                roomChart.updateOptions({
                    xaxis: { categories: rooms }
                }, false, false);
                roomChart.updateSeries([{ name: 'Revenue', data: total_value }], true);
            }

            if (packageChart) {
                packageChart.updateOptions({ labels: period_labels }, false, false);
                packageChart.updateSeries(period_revenues, true);
            }
        }

        function fetchDashboardData(startDate, endDate) {
            $('#dashboard-filter-range').addClass('disabled');

            $.ajax({
                url: dashboardDataUrl,
                method: 'GET',
                dataType: 'json',
                data: {
                    start_date: startDate,
                    end_date: endDate
                }
            }).done(function (response) {
                dashboardStartDate = startDate;
                dashboardEndDate = endDate;
                updateDashboardView(response || {});
            }).fail(function () {
                $.toast({
                    text: 'Gagal memuat data dashboard.',
                    heading: 'Result',
                    position: 'top-center',
                    hideAfter: 5000,
                    icon: 'error'
                });
            }).always(function () {
                $('#dashboard-filter-range').removeClass('disabled');
            });
        }

        var roomChartEl = document.querySelector('#barchart');
        if (roomChartEl) {
            roomChart = new ApexCharts(roomChartEl, {
                series: [{
                    name: 'Revenue',
                    data: total_value
                }],
                chart: {
                    height: 355,
                    type: 'bar',
                    toolbar: {
                        show: false
                    }
                },
                colors: ['#2a76f4'],
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        columnWidth: '45%',
                        distributed: false
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: function (value) {
                        return currencyFormatter.format(value || 0);
                    },
                    offsetY: -20,
                    style: {
                        fontSize: '11px',
                        colors: ['#304758']
                    }
                },
                xaxis: {
                    categories: rooms,
                    position: 'bottom',
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    }
                },
                yaxis: {
                    labels: {
                        formatter: function (value) {
                            return currencyFormatter.format(value || 0);
                        }
                    }
                },
                grid: {
                    strokeDashArray: 3
                },
                tooltip: {
                    y: {
                        formatter: function (value) {
                            return currencyFormatter.format(value || 0);
                        }
                    }
                },
                noData: {
                    text: 'Belum ada data revenue kamar'
                }
            });
            roomChart.render();
        }

        var packageChartEl = document.querySelector('#ana_device');
        if (packageChartEl) {
            packageChart = new ApexCharts(packageChartEl, {
                series: period_revenues,
                chart: {
                    height: 290,
                    type: 'donut'
                },
                labels: period_labels,
                colors: ['#2a76f4', '#1ccab8', '#fdb5c8', '#ffb822', '#5b6be8', '#6d81f5', '#ff8acc'],
                plotOptions: {
                    pie: {
                        donut: {
                            size: '78%'
                        }
                    }
                },
                dataLabels: {
                    enabled: false
                },
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    y: {
                        formatter: function (value, opts) {
                            var count = period_counts[opts.seriesIndex] || 0;
                            return currencyFormatter.format(value || 0) + ' (' + count + ' transaksi)';
                        }
                    }
                },
                noData: {
                    text: 'Belum ada data paket sewa'
                }
            });
            packageChart.render();
        }

        if ($.fn.daterangepicker && typeof moment !== 'undefined') {
            var start = moment(dashboardStartDate, 'YYYY-MM-DD');
            var end = moment(dashboardEndDate, 'YYYY-MM-DD');

            $('#dashboard-filter-range').daterangepicker({
                startDate: start,
                endDate: end,
                autoUpdateInput: false,
                linkedCalendars: false,
                opens: 'left',
                locale: {
                    format: 'YYYY-MM-DD',
                    cancelLabel: 'Reset'
                },
                ranges: {
                    'Tahun Ini': [moment().startOf('year'), moment().endOf('year')],
                    'Bulan Ini': [moment().startOf('month'), moment().endOf('month')],
                    '30 Hari Terakhir': [moment().subtract(29, 'days'), moment()],
                    '3 Bulan Terakhir': [moment().subtract(2, 'months').startOf('month'), moment().endOf('month')]
                }
            });

            $('#dashboard-filter-range').on('apply.daterangepicker', function (ev, picker) {
                fetchDashboardData(
                    picker.startDate.format('YYYY-MM-DD'),
                    picker.endDate.format('YYYY-MM-DD')
                );
            });

            $('#dashboard-filter-range').on('cancel.daterangepicker', function () {
                fetchDashboardData(
                    moment().startOf('year').format('YYYY-MM-DD'),
                    moment().endOf('year').format('YYYY-MM-DD')
                );
            });
        }

        updateDashboardView({
            total_revenue: {{ json_encode(data_get($response, 'total_revenue', 0)) }},
            filter: {
                label: @json(data_get($response, 'filter.label', now()->format('Y')))
            },
            room_stat: {
                room_name: rooms,
                total_value: total_value
            },
            period_stats: {
                labels: period_labels,
                counts: period_counts,
                revenues: period_revenues,
                items: {!! json_encode(data_get($response, 'period_stats.items', [])) !!},
                total_transactions: {{ json_encode(data_get($response, 'period_stats.total_transactions', 0)) }},
                total_revenue: {{ json_encode(data_get($response, 'period_stats.total_revenue', 0)) }}
            }
        });
    })();
</script>

@endsection
@endsection