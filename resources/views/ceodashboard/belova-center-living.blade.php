@extends('layouts.erm.app')

@section('title', 'Belova Center Living - CEO Dashboard')

@section('navbar')
    @include('layouts.ceodashboard.navbar')
@endsection

@section('content')
    <div class="container-fluid mt-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between flex-wrap mb-3" style="gap:12px;">
                    <div>
                        <h4 class="card-title mb-1">Belova Center Living</h4>
                        <p class="text-muted mb-0">Statistik operasional BCL untuk rooms, renter, revenue, dan inventaris.</p>
                    </div>
                    <div class="d-flex flex-wrap align-items-end justify-content-end" style="gap:8px;">
                        <form method="GET" action="{{ route('ceo-dashboard.bcl.index') }}" class="d-flex align-items-end" style="gap:8px;">
                            <div>
                                <label class="mb-1 small text-muted d-block">Year</label>
                                <select name="year" class="form-control form-control-sm" onchange="this.form.submit()">
                                    @for($year = $currentYear; $year >= max(2020, $currentYear - 5); $year--)
                                        <option value="{{ $year }}" {{ $selectedYear === $year ? 'selected' : '' }}>{{ $year }}</option>
                                    @endfor
                                </select>
                            </div>
                        </form>
                        @include('ceodashboard.partials.back-to-main-menu')
                    </div>
                </div>

                <ul class="nav nav-tabs mb-3" id="bclTabs" role="tablist">
                    <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#bcl-overview" role="tab">Overview</a></li>
                    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#bcl-revenue" role="tab">Revenue</a></li>
                    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#bcl-rooms" role="tab">Rooms</a></li>
                    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#bcl-renters" role="tab">Renters</a></li>
                    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#bcl-inventory" role="tab">Inventory</a></li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="bcl-overview" role="tabpanel">
                        <div class="row mb-3">
                            <div class="col-md-3 col-sm-6 mb-2"><div class="border rounded p-3 h-100"><div class="small text-muted">Total Rooms</div><div class="h4 mb-0">{{ number_format($overview['room_total']) }}</div></div></div>
                            <div class="col-md-3 col-sm-6 mb-2"><div class="border rounded p-3 h-100"><div class="small text-muted">Occupied Rooms</div><div class="h4 mb-0">{{ number_format($overview['occupied_rooms']) }}</div><div class="small text-muted mt-1">{{ number_format($overview['occupancy_rate'], 1) }}% occupancy</div></div></div>
                            <div class="col-md-3 col-sm-6 mb-2"><div class="border rounded p-3 h-100"><div class="small text-muted">Active Renters</div><div class="h4 mb-0">{{ number_format($overview['active_renters']) }}</div><div class="small text-muted mt-1">Total renter: {{ number_format($overview['total_renters']) }}</div></div></div>
                            <div class="col-md-3 col-sm-6 mb-2"><div class="border rounded p-3 h-100"><div class="small text-muted">Net Revenue {{ $selectedYear }}</div><div class="h4 mb-0">Rp {{ number_format($overview['net_revenue'], 0, ',', '.') }}</div></div></div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-lg-8 mb-3">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="mb-3">Revenue Trend {{ $selectedYear }}</h6>
                                    <div id="bclRevenueChart"></div>
                                </div>
                            </div>
                            <div class="col-lg-4 mb-3">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="mb-3">Operational Snapshot</h6>
                                    <table class="table table-sm mb-0">
                                        <tbody>
                                            <tr><th>Unpaid Transactions</th><td class="text-end">{{ number_format($overview['unpaid_count']) }}</td></tr>
                                            <tr><th>Outstanding</th><td class="text-end">Rp {{ number_format($overview['unpaid_total'], 0, ',', '.') }}</td></tr>
                                            <tr><th>Total Inventories</th><td class="text-end">{{ number_format($overview['inventories_total']) }}</td></tr>
                                            <tr><th>Assigned</th><td class="text-end">{{ number_format($overview['inventories_assigned']) }}</td></tr>
                                            <tr><th>Unassigned</th><td class="text-end">{{ number_format($overview['inventories_unassigned']) }}</td></tr>
                                            <tr><th>Maintenance Due</th><td class="text-end">{{ number_format($overview['maintenance_due_count']) }}</td></tr>
                                            <tr><th>Deposit Balance</th><td class="text-end">Rp {{ number_format($overview['total_deposit_balance'], 0, ',', '.') }}</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-6 mb-3">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="mb-3">Top Revenue Rooms</h6>
                                    <div id="bclTopRoomChart"></div>
                                </div>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="mb-3">Upcoming Checkout (30 Days)</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0">
                                            <thead><tr><th>Renter</th><th>Room</th><th>Checkout</th><th class="text-end">Days Left</th></tr></thead>
                                            <tbody>
                                                @forelse($upcomingCheckouts as $item)
                                                    <tr>
                                                        <td>{{ $item['renter_name'] }}</td>
                                                        <td>{{ $item['room_name'] }}</td>
                                                        <td>{{ \Illuminate\Support\Carbon::parse($item['checkout_date'])->format('d M Y') }}</td>
                                                        <td class="text-end">{{ $item['days_left'] }}</td>
                                                    </tr>
                                                @empty
                                                    <tr><td colspan="4" class="text-muted">Tidak ada data</td></tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="bcl-revenue" role="tabpanel">
                        <div class="row mb-3">
                            <div class="col-md-4 mb-2"><div class="border rounded p-3 h-100"><div class="small text-muted">Total Income</div><div class="h4 mb-0">Rp {{ number_format($overview['total_income'], 0, ',', '.') }}</div></div></div>
                            <div class="col-md-4 mb-2"><div class="border rounded p-3 h-100"><div class="small text-muted">Total Expense</div><div class="h4 mb-0">Rp {{ number_format($overview['total_expense'], 0, ',', '.') }}</div></div></div>
                            <div class="col-md-4 mb-2"><div class="border rounded p-3 h-100"><div class="small text-muted">Net Revenue</div><div class="h4 mb-0">Rp {{ number_format($overview['net_revenue'], 0, ',', '.') }}</div></div></div>
                        </div>
                        <div class="border rounded p-3 mb-3">
                            <h6 class="mb-3">Income vs Expense</h6>
                            <div id="bclRevenueChartSecondary"></div>
                        </div>
                        <div class="border rounded p-3">
                            <h6 class="mb-3">Outstanding Transactions</h6>
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead><tr><th>Date</th><th>Doc ID</th><th>Renter</th><th class="text-end">Outstanding</th></tr></thead>
                                    <tbody>
                                        @forelse($unpaidSummary['items'] as $item)
                                            <tr>
                                                <td>{{ $item['tanggal'] ? \Illuminate\Support\Carbon::parse($item['tanggal'])->format('d M Y') : '-' }}</td>
                                                <td>{{ $item['doc_id'] }}</td>
                                                <td>{{ $item['renter_name'] }}</td>
                                                <td class="text-end">Rp {{ number_format($item['kurang'], 0, ',', '.') }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="text-muted">Tidak ada transaksi outstanding</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="bcl-rooms" role="tabpanel">
                        <div class="row mb-3">
                            <div class="col-md-4 mb-2"><div class="border rounded p-3 h-100"><div class="small text-muted">Vacant Rooms</div><div class="h4 mb-0">{{ number_format($overview['vacant_rooms']) }}</div></div></div>
                            <div class="col-md-4 mb-2"><div class="border rounded p-3 h-100"><div class="small text-muted">Occupancy Rate</div><div class="h4 mb-0">{{ number_format($overview['occupancy_rate'], 1) }}%</div></div></div>
                            <div class="col-md-4 mb-2"><div class="border rounded p-3 h-100"><div class="small text-muted">Revenue Rooms Tracked</div><div class="h4 mb-0">{{ number_format(count($roomRevenueRows)) }}</div></div></div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6 mb-3"><div class="border rounded p-3 h-100"><h6 class="mb-3">Room Revenue Ranking</h6><div id="bclTopRoomChartSecondary"></div></div></div>
                            <div class="col-lg-6 mb-3"><div class="border rounded p-3 h-100"><h6 class="mb-3">Room Category Mix</h6><div id="bclRoomCategoryChart"></div></div></div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6 mb-3">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="mb-3">Occupancy by Floor</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0">
                                            <thead><tr><th>Floor</th><th class="text-end">Total</th><th class="text-end">Occupied</th><th class="text-end">Vacant</th></tr></thead>
                                            <tbody>
                                                @foreach($roomFloorStats as $floor)
                                                    <tr>
                                                        <td>{{ $floor['floor'] }}</td>
                                                        <td class="text-end">{{ $floor['total'] }}</td>
                                                        <td class="text-end">{{ $floor['occupied'] }}</td>
                                                        <td class="text-end">{{ $floor['vacant'] }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="mb-3">Top Revenue Room Table</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0">
                                            <thead><tr><th>Room</th><th class="text-end">Revenue</th></tr></thead>
                                            <tbody>
                                                @forelse($roomRevenueRows as $row)
                                                    <tr><td>{{ $row['room_name'] }}</td><td class="text-end">Rp {{ number_format($row['total_value'], 0, ',', '.') }}</td></tr>
                                                @empty
                                                    <tr><td colspan="2" class="text-muted">Tidak ada data</td></tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="bcl-renters" role="tabpanel">
                        <div class="row mb-3">
                            <div class="col-md-4 mb-2"><div class="border rounded p-3 h-100"><div class="small text-muted">Total Renters</div><div class="h4 mb-0">{{ number_format($overview['total_renters']) }}</div></div></div>
                            <div class="col-md-4 mb-2"><div class="border rounded p-3 h-100"><div class="small text-muted">Active Renters</div><div class="h4 mb-0">{{ number_format($overview['active_renters']) }}</div></div></div>
                            <div class="col-md-4 mb-2"><div class="border rounded p-3 h-100"><div class="small text-muted">Total Deposit Balance</div><div class="h4 mb-0">Rp {{ number_format($overview['total_deposit_balance'], 0, ',', '.') }}</div></div></div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6 mb-3"><div class="border rounded p-3 h-100"><h6 class="mb-3">Move-ins by Month</h6><div id="bclMoveInChart"></div></div></div>
                            <div class="col-lg-6 mb-3"><div class="border rounded p-3 h-100"><h6 class="mb-3">Upcoming Checkout</h6><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Renter</th><th>Room</th><th>Checkout</th></tr></thead><tbody>@forelse($upcomingCheckouts as $item)<tr><td>{{ $item['renter_name'] }}</td><td>{{ $item['room_name'] }}</td><td>{{ \Illuminate\Support\Carbon::parse($item['checkout_date'])->format('d M Y') }}</td></tr>@empty<tr><td colspan="3" class="text-muted">Tidak ada data</td></tr>@endforelse</tbody></table></div></div></div>
                        </div>
                        <div class="border rounded p-3">
                            <h6 class="mb-3">Longest Stay Renters</h6>
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead><tr><th>Renter</th><th class="text-end">Total Days</th></tr></thead>
                                    <tbody>
                                        @forelse($longestRenters as $item)
                                            <tr><td>{{ $item['renter_name'] }}</td><td class="text-end">{{ number_format($item['total_days']) }}</td></tr>
                                        @empty
                                            <tr><td colspan="2" class="text-muted">Tidak ada data</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="bcl-inventory" role="tabpanel">
                        <div class="row mb-3">
                            <div class="col-md-3 col-sm-6 mb-2"><div class="border rounded p-3 h-100"><div class="small text-muted">Total Inventory</div><div class="h4 mb-0">{{ number_format($overview['inventories_total']) }}</div></div></div>
                            <div class="col-md-3 col-sm-6 mb-2"><div class="border rounded p-3 h-100"><div class="small text-muted">Assigned</div><div class="h4 mb-0">{{ number_format($overview['inventories_assigned']) }}</div></div></div>
                            <div class="col-md-3 col-sm-6 mb-2"><div class="border rounded p-3 h-100"><div class="small text-muted">Unassigned</div><div class="h4 mb-0">{{ number_format($overview['inventories_unassigned']) }}</div></div></div>
                            <div class="col-md-3 col-sm-6 mb-2"><div class="border rounded p-3 h-100"><div class="small text-muted">Maintenance Due</div><div class="h4 mb-0">{{ number_format($overview['maintenance_due_count']) }}</div></div></div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6 mb-3"><div class="border rounded p-3 h-100"><h6 class="mb-3">Inventory by Room</h6><div id="bclInventoryRoomChart"></div></div></div>
                            <div class="col-lg-6 mb-3"><div class="border rounded p-3 h-100"><h6 class="mb-3">Maintenance Due Soon</h6><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Inventory</th><th>Room</th><th>Next</th><th class="text-end">Days</th></tr></thead><tbody>@forelse($maintenanceDueItems as $item)<tr><td>{{ $item['name'] }}</td><td>{{ $item['room_name'] }}</td><td>{{ \Illuminate\Support\Carbon::parse($item['next_maintenance'])->format('d M Y') }}</td><td class="text-end">{{ $item['days_left'] }}</td></tr>@empty<tr><td colspan="4" class="text-muted">Tidak ada jadwal maintenance dekat</td></tr>@endforelse</tbody></table></div></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.3"></script>
    <script>
        (function () {
            if (!window.ApexCharts) return;

            var currency = function (value) {
                return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
            };

            var revenueData = @json($revenueChart);
            var topRooms = @json($roomRevenueRows);
            var roomCategories = @json($roomCategoryStats);
            var moveIns = @json($renterMoveInChart);
            var inventoryByRoom = @json($inventoryByRoom);

            function renderChart(selector, options) {
                var el = document.querySelector(selector);
                if (!el) return;
                var chart = new ApexCharts(el, options);
                chart.render();
            }

            var revenueOptions = {
                chart: { type: 'line', height: 340, toolbar: { show: false } },
                series: [
                    { name: 'Income', data: revenueData.income || [] },
                    { name: 'Expense', data: revenueData.expense || [] },
                    { name: 'Net', data: revenueData.net || [] }
                ],
                colors: ['#198754', '#dc3545', '#0d6efd'],
                stroke: { curve: 'smooth', width: 3 },
                dataLabels: { enabled: false },
                xaxis: { categories: revenueData.labels || [] },
                yaxis: { labels: { formatter: function (value) { return currency(value); } } },
                tooltip: { y: { formatter: function (value) { return currency(value); } } }
            };

            renderChart('#bclRevenueChart', revenueOptions);
            renderChart('#bclRevenueChartSecondary', revenueOptions);

            var topRoomOptions = {
                chart: { type: 'bar', height: 340, toolbar: { show: false } },
                series: [{ name: 'Revenue', data: topRooms.map(function (item) { return item.total_value || 0; }) }],
                colors: ['#6f42c1'],
                plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '65%' } },
                dataLabels: { enabled: false },
                xaxis: { categories: topRooms.map(function (item) { return item.room_name || '-'; }), labels: { formatter: function (value) { return currency(value); } } },
                tooltip: { y: { formatter: function (value) { return currency(value); } } }
            };
            renderChart('#bclTopRoomChart', topRoomOptions);
            renderChart('#bclTopRoomChartSecondary', topRoomOptions);

            renderChart('#bclRoomCategoryChart', {
                chart: { type: 'donut', height: 340 },
                series: roomCategories.map(function (item) { return item.count || 0; }),
                labels: roomCategories.map(function (item) { return item.name || '-'; }),
                legend: { position: 'bottom' }
            });

            renderChart('#bclMoveInChart', {
                chart: { type: 'bar', height: 320, toolbar: { show: false } },
                series: [{ name: 'Move-ins', data: moveIns.counts || [] }],
                colors: ['#fd7e14'],
                plotOptions: { bar: { borderRadius: 4, columnWidth: '50%' } },
                dataLabels: { enabled: false },
                xaxis: { categories: moveIns.labels || [] }
            });

            renderChart('#bclInventoryRoomChart', {
                chart: { type: 'bar', height: 340, toolbar: { show: false } },
                series: [{ name: 'Inventory', data: inventoryByRoom.map(function (item) { return item.count || 0; }) }],
                colors: ['#20c997'],
                plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '65%' } },
                dataLabels: { enabled: false },
                xaxis: { categories: inventoryByRoom.map(function (item) { return item.name || '-'; }) }
            });
        })();
    </script>
@endsection