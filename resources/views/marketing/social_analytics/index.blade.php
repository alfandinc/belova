@extends('layouts.marketing.app')

@section('title', 'Social Media Analytics')

@section('navbar')
    @include('layouts.marketing.navbar')
@endsection

@section('content')
<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Social Media Analytics</h4>
            <div class="d-flex align-items-center">
                <div class="mr-2">
                    <select id="sa_brand" class="form-control select2" multiple style="min-width:180px" placeholder="Brand"></select>
                </div>
                <div class="mr-2">
                    <select id="sa_platform" class="form-control select2" multiple style="min-width:160px" placeholder="Platform"></select>
                </div>
                <div class="mr-2">
                    <select id="sa_jenis" class="form-control select2" multiple style="min-width:160px" placeholder="Jenis Konten"></select>
                </div>
                <div class="mr-2">
                    <input type="text" id="sa_date_range" class="form-control" style="min-width:220px" placeholder="Tanggal (range)">
                </div>
                <div>
                    <button class="btn btn-outline-secondary" id="sa_refresh">Refresh</button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-3" id="sa_summary">
                <!-- summary cards injected by JS -->
            </div>

            <div class="row mb-4">
                <div class="col-lg-12 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h6>Monthly Trends</h6>
                            <div style="height:260px;">
                                <canvas id="saMonthlyChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <canvas id="saInteractionsChart" height="180"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <h6>Top Content Plans</h6>
                            <ul id="sa_top_plans" class="list-group list-group-flush">
                                <!-- injected -->
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <table class="table table-bordered table-hover" id="sa_plans_table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Judul</th>
                                <th>Brand</th>
                                <th>Platform</th>
                                <th>Publish</th>
                                <th>Interactions</th>
                                <th>Impr.</th>
                                <th>Reach</th>
                                <th>Avg ERI</th>
                                <th>Avg ERR</th>
                                <th>Reports</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@include('marketing.content_plan.partials.content_report_modal')

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(function(){
    var plansData = [];

    var filtersPopulated = false;
    function getFilterParams() {
        var params = {};
        var brands = $('#sa_brand').val();
        if (brands && brands.length) params.brand = brands;
        var platforms = $('#sa_platform').val();
        if (platforms && platforms.length) params.platform = platforms;
        var jenis = $('#sa_jenis').val();
        if (jenis && jenis.length) params.jenis_konten = jenis;
        var range = $('#sa_date_range').val();
        if (range && range.indexOf(' - ') !== -1) {
            var parts = range.split(' - ');
            params.date_start = parts[0];
            params.date_end = parts[1];
        }
        return params;
    }

    function fetchData() {
        var params = getFilterParams();
        return $.getJSON('{{ route('marketing.social-analytics.data') }}', params).done(function(res){
            // support both legacy array response and new { data, totals } response
            var totals = null;
            if (res && res.data) {
                plansData = res.data;
                totals = res.totals || null;
            } else {
                plansData = res;
            }

            renderSummary(plansData, totals);
            renderTable(plansData);
            renderTopPlans(plansData);
            renderChart(plansData);
            // render monthly chart if backend provided it
            if (res && res.by_month) {
                renderMonthlyChart(res.by_month);
            } else {
                renderMonthlyChart(null);
            }
            if (!filtersPopulated) {
                populateFilterOptions(plansData);
                filtersPopulated = true;
            }
        });
    }
    function renderSummary(data, totals) {
        var totalPlans = data.length;
        var totalReports = data.reduce((s,p)=> s + (p.reports_count||0), 0);
        var totalInteractions = data.reduce((s,p)=> s + (p.total_interactions||0), 0);
        var avgEri = data.length ? (data.reduce((s,p)=> s + Number(p.avg_eri||0), 0) / data.length) : 0;
        var avgErr = data.length ? (data.reduce((s,p)=> s + Number(p.avg_err||0), 0) / data.length) : 0;

        // if backend provided totals, use them for specific metrics; otherwise derive from plans
        var totalLikes = totals ? (totals.likes || 0) : data.reduce((s,p)=> s + (p.total_likes||0), 0);
        var totalComments = totals ? (totals.comments || 0) : data.reduce((s,p)=> s + (p.total_comments||0), 0);
        var totalShares = totals ? (totals.shares || 0) : data.reduce((s,p)=> s + (p.total_shares||0), 0);
        var totalSaves = totals ? (totals.saves || 0) : data.reduce((s,p)=> s + (p.total_saves||0), 0);
        var totalImpressions = totals ? (totals.impressions || 0) : data.reduce((s,p)=> s + (p.total_impressions||0), 0);
        var totalReach = totals ? (totals.reach || 0) : data.reduce((s,p)=> s + (p.total_reach||0), 0);
        var totalAdReach = totals ? (totals.ad_reach || 0) : 0; // placeholder if backend doesn't have ad reach

        var html = `
            <div class="col-md-2">
                <div class="card p-2">
                    <div class="card-body p-2 text-center">
                        <div class="text-muted small">Content Plans</div>
                        <div class="h4">${totalPlans}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card p-2">
                    <div class="card-body p-2 text-center">
                        <div class="text-muted small">Total Reports</div>
                        <div class="h4">${totalReports}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card p-2">
                    <div class="card-body p-2 text-center">
                        <div class="text-muted small">Total Interactions</div>
                        <div class="h4">${totalInteractions.toLocaleString()}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-2">
                    <div class="card-body p-2 text-center">
                        <div class="text-muted small">Avg ERI / ERR</div>
                        <div class="h5">${Number(avgEri).toFixed(2)}% / ${Number(avgErr).toFixed(2)}%</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-2">
                    <div class="card-body p-2 text-center">
                        <div class="text-muted small">Total Likes</div>
                        <div class="h4">${Number(totalLikes).toLocaleString()}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mt-2">
                <div class="card p-2">
                    <div class="card-body p-2 text-center">
                        <div class="text-muted small">Comments</div>
                        <div class="h5">${Number(totalComments).toLocaleString()}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mt-2">
                <div class="card p-2">
                    <div class="card-body p-2 text-center">
                        <div class="text-muted small">Shares</div>
                        <div class="h5">${Number(totalShares).toLocaleString()}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mt-2">
                <div class="card p-2">
                    <div class="card-body p-2 text-center">
                        <div class="text-muted small">Saves</div>
                        <div class="h5">${Number(totalSaves).toLocaleString()}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mt-2">
                <div class="card p-2">
                    <div class="card-body p-2 text-center">
                        <div class="text-muted small">Impressions</div>
                        <div class="h5">${Number(totalImpressions).toLocaleString()}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mt-2">
                <div class="card p-2">
                    <div class="card-body p-2 text-center">
                        <div class="text-muted small">Ad Reach</div>
                        <div class="h5">${Number(totalAdReach).toLocaleString()}</div>
                    </div>
                </div>
            </div>
        `;
        $('#sa_summary').html(html);
    }

    var interactionsChart = null;
    function renderChart(data) {
        var labels = data.map(p => p.judul);
        var interactions = data.map(p => p.total_interactions || 0);

        var ctx = document.getElementById('saInteractionsChart').getContext('2d');
        if (interactionsChart) interactionsChart.destroy();
        interactionsChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Total Interactions',
                    data: interactions,
                    backgroundColor: 'rgba(54,162,235,0.6)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }

    var monthlyChart = null;
    function renderMonthlyChart(byMonth) {
        var $card = $('#saMonthlyChart').closest('.card');
        var $canvas = $('#saMonthlyChart');
        // cleanup previous chart
        if (!byMonth || !Array.isArray(byMonth.months) || byMonth.months.length === 0) {
            if (monthlyChart) { monthlyChart.destroy(); monthlyChart = null; }
            $card.show();
            // show a small placeholder instead of an empty chart
            $canvas.hide();
            if ($card.find('.sa-monthly-placeholder').length === 0) {
                $card.find('.card-body').append('<div class="sa-monthly-placeholder text-center text-muted mt-4">No monthly data for the selected filters.</div>');
            }
            return;
        }
        // remove placeholder and show canvas
        $card.find('.sa-monthly-placeholder').remove();
        $canvas.show();

        var labels = byMonth.months.map(m => moment(m + '-01').format('MMM YYYY'));
        var datasets = [];
        function ds(label, data, color, axis) {
            return {
                label: label,
                data: data,
                backgroundColor: color,
                borderColor: color,
                fill: false,
                tension: 0.2,
                pointRadius: 3,
                pointHoverRadius: 5,
                yAxisID: axis || 'y',
                type: 'line'
            };
        }

        // Render everything as lines; Reach/Impressions use the secondary axis to keep scales readable
        datasets.push(ds('Likes', byMonth.likes, 'rgba(75,192,192,0.9)', 'y'));
        datasets.push(ds('Comments', byMonth.comments, 'rgba(153,102,255,0.9)', 'y'));
        datasets.push(ds('Shares', byMonth.shares, 'rgba(255,159,64,0.9)', 'y'));
        datasets.push(ds('Saves', byMonth.saves, 'rgba(54,162,235,0.9)', 'y'));
        datasets.push(ds('Reach', byMonth.reach, 'rgba(255,99,132,0.9)', 'yReach'));
        datasets.push(ds('Impressions', byMonth.impressions, 'rgba(201,203,207,0.9)', 'yReach'));

        var ctx = document.getElementById('saMonthlyChart').getContext('2d');
        if (monthlyChart) monthlyChart.destroy();
        monthlyChart = new Chart(ctx, {
            type: 'line',
            data: { labels: labels, datasets: datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    y: {
                        beginAtZero: true,
                        position: 'left',
                        ticks: {
                            callback: function(value) { return value >= 1000 ? value.toLocaleString() : value; }
                        }
                    },
                    yReach: {
                        beginAtZero: true,
                        position: 'right',
                        ticks: {
                            callback: function(value) { return value >= 1000 ? value.toLocaleString() : value; }
                        },
                        grid: { drawOnChartArea: false }
                    }
                }
            }
        });
    }

    function renderTopPlans(data) {
        var top = data.slice().sort((a,b)=> (b.total_interactions||0) - (a.total_interactions||0)).slice(0,5);
        var $ul = $('#sa_top_plans').empty();
        top.forEach(function(p){
            var li = `<li class="list-group-item d-flex justify-content-between align-items-center">${p.judul}<span class="badge badge-primary badge-pill">${(p.total_interactions||0).toLocaleString()}</span></li>`;
            $ul.append(li);
        });
    }

    function renderTable(data) {
        var $tb = $('#sa_plans_table tbody').empty();
        data.forEach(function(p){
            var platforms = Array.isArray(p.platform) ? p.platform.join(', ') : (p.platform||'');
            var brand = Array.isArray(p.brand) ? p.brand.join(', ') : (p.brand||'');
            var publik = p.tanggal_publish ? moment(p.tanggal_publish).format('D MMM YYYY') : '';
            var row = `<tr data-id="${p.id}">
                <td>${p.id}</td>
                <td>${p.judul}</td>
                <td>${brand}</td>
                <td>${platforms}</td>
                <td>${publik}</td>
                <td>${(p.total_interactions||0).toLocaleString()}</td>
                <td>${(p.total_impressions||0).toLocaleString()}</td>
                <td>${(p.total_reach||0).toLocaleString()}</td>
                <td>${Number(p.avg_eri||0).toFixed(2)}%</td>
                <td>${Number(p.avg_err||0).toFixed(2)}%</td>
                <td>${p.reports_count||0}</td>
            </tr>`;
            $tb.append(row);
        });
    }

    function populateFilterOptions(data) {
        // derive unique brands/platforms/jenis from dataset
        var brands = {};
        var platforms = {};
        var jenis = {};
        data.forEach(function(p){
            if (p.brand) {
                if (Array.isArray(p.brand)) p.brand.forEach(b => brands[b] = true);
                else brands[p.brand] = true;
            }
            if (p.platform) {
                if (Array.isArray(p.platform)) p.platform.forEach(pt => platforms[pt] = true);
                else platforms[p.platform] = true;
            }
            if (p.jenis_konten) {
                if (Array.isArray(p.jenis_konten)) p.jenis_konten.forEach(j => jenis[j] = true);
                else jenis[p.jenis_konten] = true;
            }
        });

        // fill selects
        var $sb = $('#sa_brand'); $sb.empty();
        Object.keys(brands).sort().forEach(function(b){ $sb.append(new Option(b,b,false,false)); });
        var $sp = $('#sa_platform'); $sp.empty();
        Object.keys(platforms).sort().forEach(function(p){ $sp.append(new Option(p,p,false,false)); });
        var $sj = $('#sa_jenis'); $sj.empty();
        Object.keys(jenis).sort().forEach(function(j){ $sj.append(new Option(j,j,false,false)); });

        $sb.trigger('change'); $sp.trigger('change'); $sj.trigger('change');
    }

    // init selects and date range picker
    $('.select2').select2({ width: 'resolve', placeholder: 'Pilih' , allowClear: true, dropdownParent: $(document.body)});
    $('#sa_date_range').daterangepicker({
        autoUpdateInput: false,
        locale: { cancelLabel: 'Clear', format: 'YYYY-MM-DD' }
    });
    $('#sa_date_range').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
    });
    $('#sa_date_range').on('cancel.daterangepicker', function() { $(this).val(''); });

    // refresh when filters change
    $('#sa_brand, #sa_platform, #sa_jenis').on('change', function(){ fetchData(); });
    $('#sa_date_range').on('apply.daterangepicker cancel.daterangepicker', function(){ fetchData(); });
    $('#sa_refresh').on('click', function(){ fetchData(); });

    // initial load
    fetchData();
});
</script>
@endpush
