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
                var opts = {
                    chart: { type: 'area', height: 420, toolbar: { show: false } },
                    stroke: { curve: 'smooth', width: 2 },
                    series: data.series || [],
                    colors: colors.slice(0, (data.series || []).length),
                    fill: {
                        type: 'gradient',
                        gradient: { shade: 'light', type: 'vertical', shadeIntensity: 1, opacityFrom: 0.55, opacityTo: 0.08, stops: [0,50,100] }
                    },
                    xaxis: { categories: data.labels || [], labels: { rotate: 0 } },
                    dataLabels: { enabled: false },
                    yaxis: { labels: { formatter: function(v){ return Math.round(v); } }, min: 0 },
                    tooltip: { shared: true, intersect: false, y: { formatter: function(v){ return Math.round(v); } } },
                    legend: { position: 'top' },
                    markers: { size: 4 }
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
