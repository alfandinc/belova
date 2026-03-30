@extends('layouts.erm.app')

@section('title', 'Premiere Belova - Statistik Kunjungan')

@section('navbar')
    @include('layouts.ceodashboard.navbar')
@endsection

@section('content')
    <div class="container-fluid mt-3">
        <div class="row">
            <div class="col-md-9">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <h4 class="card-title mb-1">Premiere Belova</h4>
                                <p class="text-muted mb-0">Statistik kunjungan untuk Klinik ID = 1 (status_kunjungan = 2).</p>
                            </div>
                            <div class="d-flex align-items-center" style="gap:8px;">
                                <label class="mb-0 small text-muted mr-2">Years:</label>
                                <select id="filter-years" class="form-control form-control-sm">
                                    <option value="2">This year + Last year</option>
                                    <option value="5">Last 5 years</option>
                                    <option value="all">All time</option>
                                </select>
                            </div>
                        </div>

                        <div id="visitationChartArea">
                            <div id="visitationChart"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="mb-3">Patient Summary</h6>
                        <div id="visitationStats">
                            <table class="table table-sm mb-0">
                                <tbody>
                                <tr>
                                    <th>New Patients</th>
                                    <td id="stat-new">-</td>
                                </tr>
                                <tr>
                                    <th>Returning Patients</th>
                                    <td id="stat-returning">-</td>
                                </tr>
                                <tr>
                                    <th>Retention Rate</th>
                                    <td id="stat-retention">-</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="card mt-3">
                    <div class="card-body">
                        <h6 class="mb-3">Jenis Kunjungan</h6>
                        <div id="jenisSummary">
                            <table class="table table-sm mb-0">
                                <tbody>
                                <tr>
                                    <th>Konsultasi</th>
                                    <td id="jenis-konsultasi">-</td>
                                </tr>
                                <tr>
                                    <th>Beli Produk</th>
                                    <td id="jenis-beli">-</td>
                                </tr>
                                <tr>
                                    <th>Lab</th>
                                    <td id="jenis-lab">-</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.3"></script>
    @if(!empty($initial) && is_array($initial))
    <script>
        window.INIT_VISITS = {!! json_encode($initial) !!};
    </script>
    @endif
    <script>
        (function(){
            if (!window.jQuery) return console.error('jQuery is required for this chart to load');
            if (!window.ApexCharts) return console.error('ApexCharts missing');

            var colors = ['#1f77b4','#ff7f0e','#2ca02c','#d62728','#9467bd'];
            var chart = null;

            function renderChart(data) {
                var lastIndex = (data.series || []).length - 1;

                // build annotations for latest series points: top = count+pct, bottom = revenue
                var annotationsPoints = [];
                var seriesAll = data.series || [];
                var labelsAll = data.labels || [];
                var revenuesAll = data.revenues || [];
                var currIdx = seriesAll.length - 1;
                var prevIdx = currIdx - 1;

                if (currIdx >= 0) {
                    for (var i = 0; i < labelsAll.length; i++) {
                        var curr = (seriesAll[currIdx].data && typeof seriesAll[currIdx].data[i] !== 'undefined') ? seriesAll[currIdx].data[i] : 0;
                        var prev = (prevIdx >= 0 && seriesAll[prevIdx].data && typeof seriesAll[prevIdx].data[i] !== 'undefined') ? seriesAll[prevIdx].data[i] : null;
                        var rev = (revenuesAll[currIdx] && typeof revenuesAll[currIdx][i] !== 'undefined') ? revenuesAll[currIdx][i] : 0;

                        var arrow = '';
                        if (prev !== null) {
                            if (curr > prev) arrow = '▲';
                            else if (curr < prev) arrow = '▼';
                        }

                        var pctText = '';
                        if (prev !== null && prev !== 0) {
                            var change = Math.round(((curr - prev) / prev) * 100);
                            pctText = ' (' + (change > 0 ? '+' : '') + change + '%)';
                        }

                        var topText = String(curr) + (pctText || '') + (arrow ? (' ' + arrow) : '');
                        var revText = '';
                        try { revText = 'Rp ' + Number(rev || 0).toLocaleString('id-ID'); } catch (e) { revText = 'Rp ' + (rev || 0); }
                        var clr = '#6c757d';
                        if (arrow === '▲') clr = '#28a745';
                        else if (arrow === '▼') clr = '#dc3545';

                        // top annotation (above point)
                        annotationsPoints.push({
                            x: labelsAll[i],
                            y: curr,
                            marker: { size: 0 },
                            label: { text: topText, borderColor: clr, style: { color: '#ffffff', background: clr, fontSize: '12px' }, offsetY: -22 }
                        });

                        // bottom annotation (below point)
                        annotationsPoints.push({
                            x: labelsAll[i],
                            y: curr,
                            marker: { size: 0 },
                            label: { text: revText, borderColor: '#e9ecef', style: { color: '#000000', background: '#ffffff', fontSize: '11px' }, offsetY: 18 }
                        });
                    }
                }

                // determine colors per-series: latest (this year) = blue, previous (last year) = grey
                var seriesCount = (data.series || []).length;
                var seriesColors = [];
                for (var i = 0; i < seriesCount; i++) {
                    if (i === lastIndex) seriesColors.push('#1f77b4');
                    else if (i === lastIndex - 1) seriesColors.push('#6c757d');
                    else seriesColors.push(colors[i % colors.length]);
                }

                // opacity and stroke per series: make latest series bold/filled, others subtle
                var seriesOpacities = [];
                var strokeWidths = [];
                var markerSizes = [];
                for (var i = 0; i < seriesCount; i++) {
                    if (i === lastIndex) { seriesOpacities.push(0.85); strokeWidths.push(3); markerSizes.push(5); }
                    else if (i === lastIndex - 1) { seriesOpacities.push(0.12); strokeWidths.push(2); markerSizes.push(4); }
                    else { seriesOpacities.push(0.08); strokeWidths.push(2); markerSizes.push(3); }
                }

                var opts = {
                    chart: { type: 'area', height: 420, toolbar: { show: false } },
                    stroke: { curve: 'smooth', width: strokeWidths },
                    series: data.series || [],
                    colors: seriesColors,
                    fill: { type: 'solid', opacity: seriesOpacities },
                    xaxis: { categories: data.labels || [], labels: { rotate: 0 } },
                    dataLabels: { enabled: false },
                    annotations: { points: annotationsPoints },
                    yaxis: { labels: { formatter: function(v){ return Math.round(v); } }, min: 0 },
                    tooltip: { shared: true, intersect: false, y: { formatter: function(v){ return Math.round(v); } } },
                    legend: { position: 'top' },
                    markers: { size: markerSizes, hover: { size: 6 } }
                };

                var chartEl = document.getElementById('visitationChart');
                if (!chartEl) return;

                try {
                    if (chart) { try { chart.destroy(); } catch(e){} chart = null; }
                    chart = new ApexCharts(chartEl, opts);
                    chart.render();
                } catch(e) { console.error(e); }
            }

            function renderStats(stats) {
                try {
                    if (!stats) return;
                    document.getElementById('stat-new').textContent = (typeof stats.new !== 'undefined') ? stats.new : '-';
                    document.getElementById('stat-returning').textContent = (typeof stats.returning !== 'undefined') ? stats.returning : '-';
                    document.getElementById('stat-retention').textContent = (typeof stats.retention_rate !== 'undefined') ? (stats.retention_rate + '%') : '-';
                    // render jenis kunjungan if present
                    if (stats.jenis) {
                        document.getElementById('jenis-konsultasi').textContent = stats.jenis.konsultasi ?? 0;
                        document.getElementById('jenis-beli').textContent = stats.jenis.beli_produk ?? 0;
                        document.getElementById('jenis-lab').textContent = stats.jenis.lab ?? 0;
                    }
                } catch(e) { console.error(e); }
            }

            function loadData(years) {
                var url = "{{ route('ceo-dashboard.premiere_belova.index') }}";
                $.getJSON(url, { years: years })
                    .done(function(resp){
                        renderChart(resp);
                        if (resp.stats) renderStats(resp.stats);
                    })
                    .fail(function(xhr){
                        console.error('Failed to load premiere belova data', xhr);
                    });
            }

            function renderComparisons(data) {
                var container = document.getElementById('monthComparisons');
                if (!container) return;
                container.innerHTML = '';
                var labels = data.labels || [];
                var series = data.series || [];
                if (!series.length) return;

                var currIdx = series.length - 1;
                var prevIdx = series.length >= 2 ? series.length - 2 : null;

                var row = document.createElement('div');
                row.className = 'd-flex flex-wrap';
                for (var i = 0; i < labels.length; i++) {
                    var month = labels[i];
                    var currVal = (series[currIdx].data && series[currIdx].data[i]) ? series[currIdx].data[i] : 0;
                    var prevVal = prevIdx !== null ? ((series[prevIdx].data && series[prevIdx].data[i]) ? series[prevIdx].data[i] : 0) : null;

                    var badge = document.createElement('div');
                    badge.className = 'p-2 mr-2 mb-2';
                    badge.style.minWidth = '110px';

                    var title = document.createElement('div');
                    title.className = 'small text-muted';
                    title.textContent = month;

                    var valueLine = document.createElement('div');
                    valueLine.className = 'font-weight-bold';
                    var arrow = '';
                    var arrowClass = '';
                    if (prevVal !== null) {
                        if (currVal > prevVal) { arrow = ' ▲'; arrowClass = 'text-success'; }
                        else if (currVal < prevVal) { arrow = ' ▼'; arrowClass = 'text-danger'; }
                        else { arrow = ''; }
                    }

                    var valSpan = document.createElement('span');
                    valSpan.textContent = currVal + (arrow ? '' : '');
                    valueLine.appendChild(valSpan);

                    if (arrow) {
                        var arrSpan = document.createElement('span');
                        arrSpan.textContent = arrow;
                        arrSpan.className = 'ml-2 ' + arrowClass;
                        valueLine.appendChild(arrSpan);
                    }

                    badge.appendChild(title);
                    badge.appendChild(valueLine);
                    row.appendChild(badge);
                }

                container.appendChild(row);
            }

            // auto-load default = 2 years (this year + last year)
            $(function(){
                var defaultYears = $('#filter-years').val() || '2';
                loadData(defaultYears);

                $('#filter-years').on('change', function(){
                    var v = $(this).val();
                    loadData(v);
                });
            });
        })();
    </script>
@endsection
