@extends('layouts.erm.app')

@section('title', 'Premiere Belova - Statistik Kunjungan')

@section('navbar')
    @include('layouts.ceodashboard.navbar')
@endsection

@section('content')
    <div class="container-fluid mt-3">
        <div class="row">
            <div class="col-12">
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

                        <div id="statisticContent"></div>
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

                // build annotations for latest series points so we can style arrows green/red
                var annotationsPoints = [];
                try {
                    var seriesAll = data.series || [];
                    var labelsAll = data.labels || [];
                    var currIdx = seriesAll.length - 1;
                    var prevIdx = currIdx - 1;
                    if (currIdx >= 0) {
                        for (var i = 0; i < labelsAll.length; i++) {
                            var curr = (seriesAll[currIdx].data && typeof seriesAll[currIdx].data[i] !== 'undefined') ? seriesAll[currIdx].data[i] : 0;
                            var prev = (prevIdx >= 0 && seriesAll[prevIdx].data && typeof seriesAll[prevIdx].data[i] !== 'undefined') ? seriesAll[prevIdx].data[i] : null;
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

                            var labelText = String(curr) + (pctText || '');
                            var clr = '#6c757d';
                            if (arrow === '▲') clr = '#28a745';
                            else if (arrow === '▼') clr = '#dc3545';

                            annotationsPoints.push({
                                x: labelsAll[i],
                                y: curr,
                                marker: { size: 0 },
                                label: {
                                    text: labelText + (arrow ? (' ' + arrow) : ''),
                                    borderColor: clr,
                                    // use styled background so text is readable and arrow color is visible
                                    style: { color: '#ffffff', background: clr },
                                    // small offset to position above the point
                                    offsetY: -18
                                }
                            });
                        }
                    }
                } catch(e) { annotationsPoints = []; }

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
                    // use solid fill but per-series opacity to make latest series block the chart
                    fill: { type: 'solid', opacity: seriesOpacities },
                    xaxis: { categories: data.labels || [], labels: { rotate: 0 } },
                    // disable built-in dataLabels for latest series; we'll use annotations for colored labels
                    dataLabels: {
                        enabled: false,
                        formatter: function(val, opts) {
                            try {
                                // only show labels for the latest year series
                                if (opts.seriesIndex !== lastIndex) return '';
                                var idx = opts.dataPointIndex;
                                var series = data.series || [];
                                var curr = series[lastIndex] && series[lastIndex].data ? series[lastIndex].data[idx] : 0;
                                var prev = (lastIndex >= 1 && series[lastIndex-1] && series[lastIndex-1].data) ? (series[lastIndex-1].data[idx] || 0) : null;
                                var arrow = '';
                                if (prev !== null) {
                                    if (curr > prev) { arrow = '▲'; }
                                    else if (curr < prev) { arrow = '▼'; }
                                }
                                var pct = '';
                                if (prev !== null && prev !== 0) {
                                    var change = Math.round(((curr - prev) / prev) * 100);
                                    pct = ' (' + (change > 0 ? '+' : '') + change + '%)';
                                }
                                // return text using SVG <tspan> for colored arrow (ApexCharts renders tspan)
                                if (arrow) {
                                    var clr = (arrow === '▲') ? '#28a745' : '#dc3545';
                                    return String(curr) + ' ' + '<tspan fill="' + clr + '">' + arrow + '</tspan>' + pct;
                                }
                                return String(curr) + pct;
                            } catch(e) { return String(val); }
                        },
                        style: { fontSize: '11px', colors: ['#333333'] },
                        offsetY: -18,
                        background: { enabled: true, foreColor: '#ffffff', padding: 6, borderRadius: 4 }
                    },
                    annotations: { points: annotationsPoints },
                    yaxis: { labels: { formatter: function(v){ return Math.round(v); } }, min: 0 },
                    tooltip: { shared: true, intersect: false, y: { formatter: function(v){ return Math.round(v); } } },
                    legend: { position: 'top' },
                    markers: { size: markerSizes, hover: { size: 6 } }
                };

                var el = document.getElementById('statisticContent');
                if (!el) return;
                el.innerHTML = '<div id="visitationChart"></div>';

                try {
                    if (chart) { try { chart.destroy(); } catch(e){} chart = null; }
                    chart = new ApexCharts(document.querySelector('#visitationChart'), opts);
                    chart.render();
                } catch(e) { console.error(e); }
            }

            function loadData(years) {
                var url = "{{ route('ceo-dashboard.premiere_belova.index') }}";
                $.getJSON(url, { years: years })
                    .done(function(resp){
                        renderChart(resp);
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
