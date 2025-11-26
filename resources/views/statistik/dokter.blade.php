@extends('layouts.erm.app')

@section('title', 'Statistik Dokter')

@section('navbar')
    @include('layouts.pusatstatistik.navbar')
@endsection

@section('content')
    <style>
        /* Page-scoped card outline for Statistik Dokter (thicker/ticker outline) */
        .statistik-blue .card {
            border: 2px solid #4f7df0 !important;
            box-shadow: 0 4px 12px rgba(79,125,240,0.08) !important;
            transition: box-shadow .15s ease, transform .15s ease;
        }
        .statistik-blue .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(79,125,240,0.12) !important;
        }
        .statistik-blue .card .card-body {
            /* keep existing spacing */
        }
        .statistik-blue .card .card-title {
            color: #2c2f45;
        }
    </style>
    <div class="container-fluid mt-3 statistik-blue">
        <div class="mb-3 d-flex justify-content-between align-items-center">
            <div style="flex:1; margin-right:12px;">
                <label for="dokterSelect" class="form-label">Pilih Dokter</label>
                <select id="dokterSelect" class="form-control" aria-label="Pilih Dokter">
                    <option value="0">-- Pilih Dokter --</option>
                    @foreach($dokterList as $d)
                        <option value="{{ $d->id }}" {{ ($dokter && $dokter->id == $d->id) ? 'selected' : '' }}>{{ $d->user->name ?? ('Dokter ' . $d->id) }} @if($d->spesialisasi) - {{ $d->spesialisasi->nama }} @endif</option>
                    @endforeach
                </select>
            </div>
            <div style="width:320px; text-align:right;">
                <label for="globalRangePicker" class="form-label" style="visibility:hidden; display:block;">Range</label>
                <div class="d-flex justify-content-end">
                    <input type="text" id="globalRangePicker" class="form-control form-control-sm" style="width:220px;" />
                    <button id="globalAllTimeBtn" type="button" class="btn btn-sm btn-link ms-2">All time</button>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-4 mb-3">
                <div class="card shadow-sm h-100" style="border-radius:12px; overflow:hidden;">
                    <div style="position:relative;">
                        @if($dokter && $dokter->photo)
                            <img id="dokterPhoto" src="{{ asset('storage/' . ltrim($dokter->photo, '/')) }}" alt="foto dokter" class="card-img-top" style="height:300px; object-fit:cover; display:block;">
                        @else
                            <img id="dokterPhoto" src="{{ asset('img/avatar.png') }}" alt="avatar" class="card-img-top" style="height:300px; object-fit:cover; display:block; background:#f7f7f7;">
                        @endif
                        
                    </div>

                    <div id="dokterNameBar" style="background:#4f7df0; padding:14px 18px; color:#fff; font-weight:700; font-size:1.25rem;">
                        {{ $dokter->user->name ?? '-' }}
                    </div>

                    <div class="card-body">
                        @if($dokter)
                            <ul id="dokterMeta" class="list-unstyled mb-0">
                                <li><span class="text-muted">NIK:</span> <strong>{{ $dokter->nik ?? '-' }}</strong></li>
                                <li class="mt-2"><span class="text-muted">SIP:</span> <strong>{{ $dokter->sip ?? '-' }}</strong></li>
                                <li class="mt-2"><span class="text-muted">STR:</span> <strong>{{ $dokter->str ?? '-' }}</strong></li>
                                <li class="mt-2"><span class="text-muted">Klinik:</span> <strong>{{ $dokter->klinik->nama ?? '-' }}</strong></li>
                                <li class="mt-2"><span class="text-muted">No HP:</span> <strong>{{ $dokter->no_hp ?? '-' }}</strong></li>
                            </ul>
                        @else
                            <div class="text-muted">Tidak ada data dokter.</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-8 mb-3">
                <div class="card shadow-sm h-100" style="border-radius:10px;">
                    <div class="card-body">
                        <h3 id="dokterHeading" class="mb-2" style="font-weight:700; color:#2c2f45;"></h3>
                        <p class="text-muted">Detail statistik dan grafik akan muncul di area ini.</p>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div id="statsHeader" style="font-weight:700; color:#2c2f45; font-size:1.05rem;">Visitation</div>
                            <div class="text-muted" style="font-size:0.9rem;">Filter: use the global range next to Dokter selector</div>
                        </div>

                        <div id="statisticContent" style="min-height:260px;">
                            <!-- Placeholder for charts / stats. Implement AJAX-loaded charts here. -->
                        </div>
                        <div id="breakdownContainer" class="mt-4">
                            <h5 class="mb-2">Breakdown Kunjungan</h5>
                            <div id="breakdownSummary" class="mb-2 text-muted">Periode: Semua waktu</div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered" id="breakdownTable">
                                            <thead>
                                                <tr>
                                                    <th style="width:70%">Jenis Kunjungan</th>
                                                    <th class="text-end" style="width:30%">Jumlah</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td><strong>Total</strong></td>
                                                    <td class="text-end"><span id="totalVisits">-</span></td>
                                                </tr>
                                                <tr id="kunjungan1_row">
                                                    <td><span id="kunjungan1_caret" style="display:inline-block; width:18px;">▸</span> Konsultasi</td>
                                                    <td class="text-end"><span id="kunjungan1">-</span></td>
                                                </tr>
                                                <tr class="konsultasi-detail d-none">
                                                    <td style="padding-left:18px;">Konsultasi (Tanpa Lab)</td>
                                                    <td class="text-end"><span id="kunjungan1_nolab">-</span></td>
                                                </tr>
                                                <tr class="konsultasi-detail d-none">
                                                    <td style="padding-left:18px;">Konsultasi dengan Lab</td>
                                                    <td class="text-end"><span id="kunjungan1_withlab">-</span></td>
                                                </tr>
                                                <tr>
                                                    <td>Beli Produk</td>
                                                    <td class="text-end"><span id="kunjungan2">-</span></td>
                                                </tr>
                                                <tr>
                                                    <td>Lab</td>
                                                    <td class="text-end"><span id="kunjungan3">-</span></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered" id="retentionTable">
                                            <thead>
                                                <tr>
                                                    <th>Keterangan</th>
                                                    <th class="text-end">Jumlah</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td><strong>Total Pasien (periode)</strong></td>
                                                    <td class="text-end"><span id="ret_total">-</span></td>
                                                </tr>
                                                <tr>
                                                    <td>Pasien Baru</td>
                                                    <td class="text-end"><span id="ret_new">-</span></td>
                                                </tr>
                                                <tr>
                                                    <td>Pasien Kembali</td>
                                                    <td class="text-end"><span id="ret_returning">-</span></td>
                                                </tr>
                                                <tr>
                                                    <td>Retention Rate</td>
                                                    <td class="text-end"><span id="ret_rate">-</span></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
                                                                        <div class="row mt-3">
                                                                            <div class="col-md-6 mb-3">
                                                                                <div class="card shadow-sm h-100" style="border-radius:10px;">
                                                                                    <div class="card-body">
                                                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                                                            <div>
                                                                                                <h5 class="card-title">Tindakan</h5>
                                                                                                <p class="text-muted mb-0">Top tindakan untuk dokter (periode saat ini).</p>
                                                                                            </div>
                                                                                            <!-- Filters removed: using global range picker -->
                                                                                        </div>
                                                                                        <div class="table-responsive">
                                                                                            <table class="table table-sm table-striped" id="tindakanTable">
                                                                                                <thead>
                                                                                                    <tr>
                                                                                                        <th style="width:6%">#</th>
                                                                                                        <th>Tindakan</th>
                                                                                                        <th style="width:18%">Kunjungan</th>
                                                                                                    </tr>
                                                                                                </thead>
                                                                                                <tbody>
                                                                                                    <tr><td colspan="3">Memuat...</td></tr>
                                                                                                </tbody>
                                                                                            </table>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-md-6 mb-3">
                                                                                <div class="card shadow-sm h-100" style="border-radius:10px;">
                                                                                    <div class="card-body">
                                                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                                                            <div>
                                                                                                <h5 class="card-title">Obat</h5>
                                                                                                <p class="text-muted mb-0">Obat yang diresepkan oleh dokter (jumlah per obat).</p>
                                                                                            </div>
                                                                                            <!-- Filters removed: using global range picker -->
                                                                                        </div>
                                                                                        <div class="table-responsive">
                                                                                            <table class="table table-sm table-striped" id="obatTable">
                                                                                                <thead>
                                                                                                    <tr>
                                                                                                        <th style="width:6%">#</th>
                                                                                                        <th>Obat</th>
                                                                                                        <th style="width:18%">Jumlah</th>
                                                                                                    </tr>
                                                                                                </thead>
                                                                                                <tbody>
                                                                                                    <tr><td colspan="3">Memuat...</td></tr>
                                                                                                </tbody>
                                                                                            </table>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
        <div class="row mt-3">
            <div class="col-12">
                <div class="card shadow-sm" style="border-radius:10px;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h4 class="mb-0" style="font-weight:700; color:#2c2f45;">Statistik Pasien</h4>
                            <div class="text-muted" style="font-size:0.9rem;">Periode dikendalikan oleh picker global di samping pemilih dokter.</div>
                        </div>
                        <div id="patientSummary" class="mb-2 text-muted">Periode: Semua waktu</div>
                        <div class="row">
                            <!-- Total Pasien card removed as requested -->
                            <div class="col-md-4 mb-3">
                                <div class="card p-2 h-100">
                                    <div class="text-muted">Gender</div>
                                    <div id="genderChart" style="height:360px;"></div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card p-2 h-100">
                                    <div class="text-muted">Kelompok Usia</div>
                                    <div id="ageChart" style="height:360px;"></div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card p-2 h-100">
                                    <div class="text-muted">Top Pasien (by visits)</div>
                                    <div class="table-responsive mt-2">
                                        <table class="table table-sm" id="topPatientsTable">
                                            <thead>
                                                <tr>
                                                    <th style="width:5%">No</th>
                                                    <th>Nama Pasien</th>
                                                    <th style="width:20%" class="text-end clickable" data-sort="spend">Spend <span id="sortIndicatorSpend"></span></th>
                                                    <th style="width:20%" class="text-end clickable" data-sort="visits">Kunjungan <span id="sortIndicatorVisits"></span></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr><td colspan="4" class="text-muted text-center">Memuat...</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Textual status list removed as requested -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.3"></script>
    @if(!empty($initialVisits) && is_array($initialVisits))
    <script>
        // Embed initial visits computed server-side for immediate render
        window.INIT_VISITS = {!! json_encode($initialVisits) !!};
    </script>
    @endif
    <script>
        // Tiny wrapper to render visitation counts with ApexCharts.
        var _visitationChart = null;
        var _genderChart = null;
        var _ageChart = null;
        function renderVisitationChart(labels, seriesData) {
            if (!window.ApexCharts) {
                console.error('ApexCharts is not available. Ensure the script is included in the page.');
                return;
            }

                // convert labels: support both 'YYYY-MM' (month) and 'YYYY-MM-DD' (day)
                var categories = labels.map(function(l){
                    try {
                        if (/^\d{4}-\d{2}-\d{2}$/.test(l)) {
                            return (typeof moment !== 'undefined') ? moment(l).format('DD MMM') : l;
                        } else if (/^\d{4}-\d{2}$/.test(l)) {
                            return (typeof moment !== 'undefined') ? moment(l + '-01').format('MMM YYYY') : l;
                        } else {
                            return l;
                        }
                    } catch(e){ return l; }
                });

                // Normalize seriesData: support legacy array of numbers or new [{name,data},...]
                var series = [];
                if (!seriesData) {
                    series = [];
                } else if (Array.isArray(seriesData) && seriesData.length && typeof seriesData[0] === 'number') {
                    series = [{ name: 'Kunjungan', data: seriesData }];
                } else if (Array.isArray(seriesData)) {
                    series = seriesData;
                }

                var colorMap = {
                    'Total': '#1f77b4',
                    'Kunjungan': '#1f77b4',
                    'Konsultasi': '#ff7f0e',
                    'Konsultasi (Tanpa Lab)': '#2ca02c',
                    'Konsultasi (Dengan Lab)': '#d62728',
                    'Beli Produk': '#9467bd',
                    'Lab': '#8c564b'
                };

                var colors = series.map(function(s){ return colorMap[s.name] || '#333333'; });

                var options = {
                    chart: { type: 'line', height: 360, toolbar: { show: false } },
                    stroke: { curve: 'smooth', width: 2 },
                    series: series,
                    colors: colors,
                    xaxis: { categories: categories, labels: { rotate: -45 } },
                    dataLabels: { enabled: false },
                    yaxis: { labels: { formatter: function(v){ return Math.round(v); } } },
                    tooltip: { shared: true, intersect: false }
                };

                var el = document.getElementById('statisticContent');
                if (!el) return;
                el.innerHTML = '<div id="visitationChart"></div>';

                if (_visitationChart) {
                    try { _visitationChart.destroy(); } catch(e){}
                    _visitationChart = null;
                }

                try {
                    _visitationChart = new ApexCharts(document.querySelector('#visitationChart'), options);
                    _visitationChart.render();
                } catch(err) { console.error('Error creating ApexCharts instance', err); }
        }

        // Render gender donut chart. Expects object like { male: n, female: n, other: n }
        function renderGenderChart(genderObj) {
            if (!window.ApexCharts) return;
            var male = (genderObj && genderObj.male) ? genderObj.male : 0;
            var female = (genderObj && genderObj.female) ? genderObj.female : 0;
            var other = (genderObj && genderObj.other) ? genderObj.other : 0;
            var series = [male, female, other];
            var labels = ['Laki-laki','Perempuan','Other'];

            var opts = {
                chart: { type: 'pie', height: 340, toolbar: { show: false } },
                series: series,
                labels: labels,
                colors: ['#4f7df0', '#f06f6f', '#9aa0ff'],
                legend: { show: true, position: 'bottom', horizontalAlign: 'center' },
                dataLabels: { enabled: true, formatter: function(val){ return val.toFixed(1) + '%'; } },
                responsive: [{ breakpoint: 768, options: { chart: { height: 260 }, legend: { show: true, position: 'bottom' } } }, { breakpoint: 480, options: { chart: { height: 220 }, legend: { show: false } } }]
            };

            var el = document.querySelector('#genderChart');
            if (!el) return;
            if (_genderChart) { try { _genderChart.destroy(); } catch(e){} _genderChart = null; }
            try { _genderChart = new ApexCharts(el, opts); _genderChart.render(); } catch(e){ console.error('gender chart', e); }
        }

        // Render age buckets bar chart. Expects buckets object with keys '0-17','18-30','31-45','46-60','61+'
        // Render age buckets as donut chart. Expects buckets object with keys '0-17','18-30','31-45','46-60','61+'
        function renderAgeChart(buckets) {
            if (!window.ApexCharts) return;
            var cats = ['0-17','18-30','31-45','46-60','61+'];
            var data = cats.map(function(k){ return (buckets && buckets[k]) ? buckets[k] : 0; });

            var opts = {
                chart: { type: 'pie', height: 340, toolbar: { show: false } },
                series: data,
                labels: cats,
                colors: ['#4f7df0','#6fcf97','#f6c85f','#f6a6a6','#9aa0ff'],
                legend: { show: true, position: 'bottom', horizontalAlign: 'center' },
                dataLabels: { enabled: true, formatter: function(val){ return val.toFixed(1) + '%'; } },
                responsive: [{ breakpoint: 768, options: { chart: { height: 260 }, legend: { show: true } } }, { breakpoint: 480, options: { chart: { height: 220 }, legend: { show: false } } }]
            };

            var el = document.querySelector('#ageChart');
            if (!el) return;
            if (_ageChart) { try { _ageChart.destroy(); } catch(e){} _ageChart = null; }
            try { _ageChart = new ApexCharts(el, opts); _ageChart.render(); } catch(e){ console.error('age chart', e); }
        }

        // Sorting state for top patients
        var _topPatientsSort = { field: 'visits', dir: 'desc' };

        // Fetch top patients (by visit count or spend) and render table
        function fetchTopPatients(dokterId, start, end, sortField, sortDir) {
            if (!dokterId) return;
            sortField = sortField || _topPatientsSort.field;
            sortDir = sortDir || _topPatientsSort.dir;
            var qsParts = [];
            if (start && end) { qsParts.push('start=' + encodeURIComponent(start)); qsParts.push('end=' + encodeURIComponent(end)); }
            else if (!start && !end) { qsParts.push('all=1'); }
            qsParts.push('sort=' + encodeURIComponent(sortField));
            qsParts.push('dir=' + encodeURIComponent(sortDir));
            var qs = qsParts.length ? ('?' + qsParts.join('&')) : '';
            fetch('/statistik/dokter/' + dokterId + '/top-patients' + qs)
                .then(function(res){ if (!res.ok) throw res; return res.json(); })
                .then(function(payload){
                    if (!payload || !payload.ok) return;
                    // update stored sort
                    _topPatientsSort.field = sortField; _topPatientsSort.dir = sortDir;
                    updateSortIndicators();
                    renderTopPatients(payload.tops || []);
                }).catch(function(e){
                    console.error('Failed to load top patients', e);
                });
        }

        function updateSortIndicators() {
            var sSpend = document.getElementById('sortIndicatorSpend');
            var sVisits = document.getElementById('sortIndicatorVisits');
            if (sSpend) sSpend.textContent = '';
            if (sVisits) sVisits.textContent = '';
            if (_topPatientsSort.field === 'spend') {
                if (sSpend) sSpend.textContent = (_topPatientsSort.dir === 'asc') ? '▲' : '▼';
            } else {
                if (sVisits) sVisits.textContent = (_topPatientsSort.dir === 'asc') ? '▲' : '▼';
            }
        }

        function renderTopPatients(list) {
            var tb = document.querySelector('#topPatientsTable tbody');
            if (!tb) return;
            tb.innerHTML = '';
            if (!list || !Array.isArray(list) || list.length === 0) {
                tb.innerHTML = '<tr><td colspan="3" class="text-muted text-center">Tidak ada data</td></tr>';
                return;
            }
                var nf = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
            list.forEach(function(r, idx){
                var tr = document.createElement('tr');
                var spendFormatted = (typeof r.spend === 'number') ? nf.format(r.spend) : (r.spend ? nf.format(Number(r.spend)) : nf.format(0));
                tr.innerHTML = '<td>' + (idx+1) + '</td><td>' + (r.name || ('Pasien ' + r.pasien_id)) + '</td><td class="text-end">' + spendFormatted + '</td><td class="text-end">' + (r.visits || 0) + '</td>';
                tb.appendChild(tr);
            });
        }

        (function(){
            var sel = document.getElementById('dokterSelect');
            if (!sel) return;

            function updateDoctorCard(data) {
                // image
                var img = document.getElementById('dokterPhoto');
                if (img) img.src = data.photo || img.src;
                // name bar
                var nameBar = document.getElementById('dokterNameBar');
                if (nameBar) nameBar.textContent = data.name || '-';
                // list items
                var list = document.getElementById('dokterMeta');
                if (list) {
                    list.innerHTML = '';
                    var items = [
                        ['NIK', data.nik],
                        ['SIP', data.sip],
                        ['STR', data.str],
                        ['Klinik', data.klinik],
                        ['No HP', data.no_hp]
                    ];
                    items.forEach(function(it){
                        var li = document.createElement('li');
                        li.innerHTML = '<span class="text-muted">' + it[0] + ':</span> <strong>' + (it[1] || '-') + '</strong>';
                        li.className = 'mt-2';
                        list.appendChild(li);
                    });
                }
                // specialization badge removed — nothing to update here
            }

            sel.addEventListener('change', function(){
                var id = this.value;
                if (!id || id === '0') {
                    // reset to first or show placeholder (we'll reload initial page state)
                    // simply load index (first dokter) via AJAX: use index route's first dokter id if available
                    var firstOption = sel.querySelector('option:not([value="0"])');
                    if (firstOption) {
                        sel.value = firstOption.value;
                        id = firstOption.value;
                    } else {
                        return;
                    }
                }

                fetch('/statistik/dokter/' + id + '/data')
                    .then(function(res){
                        if(!res.ok) throw res;
                        return res.json();
                    })
                    .then(function(payload){
                        if (payload && payload.ok && payload.data) {
                            updateDoctorCard(payload.data);
                            // update right panel heading (use element ID)
                            var heading = document.getElementById('dokterHeading');
                            if (heading) {
                                // specialization removed from heading
                                heading.innerHTML = '';
                            }
                            // Clear / placeholder statistic content for now
                            var statEl = document.getElementById('statisticContent');
                            if (statEl) statEl.innerHTML = '<div class="text-muted">Memuat statistik untuk ' + (payload.data.name || 'dokter') + '...</div>';
                            // fetch visitation stats and render chart (respect selected date range)
                            fetchAndRenderStats(id, selectedStart, selectedEnd);
                            // fetch breakdown (summary numbers) with range
                            fetchBreakdown(id, selectedStart, selectedEnd);
                            // fetch patient stats using patient-specific range
                            fetchPatientStats(id, selectedPatientStart, selectedPatientEnd);
                            // fetch obat stats (independent) so obat table updates when doctor changes
                            try { fetchObatStats(id, selectedObatStart, selectedObatEnd); } catch(e){ console.error('fetchObatStats error', e); }
                        }
                    })
                    .catch(function(err){
                        console.error('Failed to load dokter data', err);
                    });
            });

            // Date range state (visitation)
            var selectedStart = null;
            var selectedEnd = null;
            // Date range state (patient stats - independent)
            var selectedPatientStart = null;
            var selectedPatientEnd = null;
            // Date range state (tindakan - independent)
            var selectedTindakanStart = null;
            var selectedTindakanEnd = null;
            // Date range state (obat - independent)
            var selectedObatStart = null;
            var selectedObatEnd = null;

            // Initialize daterangepicker (requires moment & daterangepicker plugin loaded in layout)
            function initRangePickerImpl(){
                var start, end;
                if (typeof moment !== 'undefined' && typeof moment().endOf === 'function') {
                    // Default to current month
                    start = moment().startOf('month');
                    end = moment().endOf('month');
                    selectedStart = start.format('YYYY-MM-DD');
                    selectedEnd = end.format('YYYY-MM-DD');
                } else {
                    // fallback to first/last day of current month if moment not available
                    var now = new Date();
                    var s = new Date(now.getFullYear(), now.getMonth(), 1);
                    var e = new Date(now.getFullYear(), now.getMonth() + 1, 0); // last day of current month
                    start = s;
                    end = e;
                    // format YYYY-MM-DD
                    function fmt(d){ return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0'); }
                    selectedStart = fmt(start);
                    selectedEnd = fmt(end);
                }

                if (window.jQuery && jQuery.fn.daterangepicker && typeof moment !== 'undefined') {
                    // Initialize a single global picker with presets
                    $('#globalRangePicker').daterangepicker({
                        startDate: start,
                        endDate: end,
                        locale: { format: 'YYYY-MM-DD' },
                        opens: 'left',
                        ranges: {
                            'Today': [moment(), moment()],
                            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                            'This Week': [moment().startOf('week'), moment().endOf('week')],
                            'This Month': [moment().startOf('month'), moment().endOf('month')],
                            '3 Months': [moment().subtract(3, 'months').startOf('month'), moment().endOf('month')],
                            '6 Months': [moment().subtract(6, 'months').startOf('month'), moment().endOf('month')],
                            'This Year': [moment().startOf('year'), moment().endOf('year')],
                            'All time': [moment('1970-01-01'), moment()]
                        }
                    });

                    // Per-section pickers removed; global picker controls all sections.

                    $('#globalRangePicker').on('apply.daterangepicker', function(ev, picker){
                        var chosen = picker.chosenLabel || null;
                        if (chosen === 'All time') {
                            selectedStart = null; selectedEnd = null;
                            $('#globalRangePicker').val('All time');
                        } else {
                            selectedStart = picker.startDate.format('YYYY-MM-DD');
                            selectedEnd = picker.endDate.format('YYYY-MM-DD');
                            $('#globalRangePicker').val(selectedStart + ' - ' + selectedEnd);
                        }

                        // propagate to per-section state variables (they will be read by their fetchers)
                        selectedPatientStart = selectedStart; selectedPatientEnd = selectedEnd;
                        selectedTindakanStart = selectedStart; selectedTindakanEnd = selectedEnd;
                        selectedObatStart = selectedStart; selectedObatEnd = selectedEnd;

                        // trigger reloads for all stats
                        var curId = sel.value;
                        if (curId && curId !== '0') {
                            fetchAndRenderStats(curId, selectedStart, selectedEnd);
                            fetchBreakdown(curId, selectedStart, selectedEnd);
                            fetchPatientStats(curId, selectedPatientStart, selectedPatientEnd);
                            fetchTindakanStats(curId, selectedTindakanStart, selectedTindakanEnd);
                            fetchObatStats(curId, selectedObatStart, selectedObatEnd);
                        }
                    });

                    // global all-time button
                    var gAllBtn = document.getElementById('globalAllTimeBtn');
                    if (gAllBtn) {
                        gAllBtn.addEventListener('click', function(){
                            selectedStart = null; selectedEnd = null;
                            $('#globalRangePicker').val('All time');
                            selectedPatientStart = selectedPatientEnd = null;
                            selectedTindakanStart = selectedTindakanEnd = null;
                            selectedObatStart = selectedObatEnd = null;
                            var curId = sel.value;
                            if (curId && curId !== '0') {
                                fetchAndRenderStats(curId, null, null);
                                fetchBreakdown(curId, null, null);
                                fetchPatientStats(curId, null, null);
                                fetchTindakanStats(curId, null, null);
                                fetchObatStats(curId, null, null);
                            }
                        });
                    }

                    // per-section All-time buttons removed; global All-time button is authoritative
                } else {
                    // fallback: no daterangepicker available — set textual global value
                    var gInp = document.getElementById('globalRangePicker'); if (gInp) gInp.value = selectedStart + ' - ' + selectedEnd;
                    var gAllBtn = document.getElementById('globalAllTimeBtn'); if (gAllBtn) gAllBtn.addEventListener('click', function(){
                        selectedStart = null; selectedEnd = null; if (gInp) gInp.value = 'All time';
                        var curId = sel.value; if (curId && curId !== '0') { fetchAndRenderStats(curId, null, null); fetchBreakdown(curId, null, null); fetchPatientStats(curId, null, null); fetchTindakanStats(curId, null, null); fetchObatStats(curId, null, null); }
                    });
                }

                // initial load after datepicker setup
                (function loadInitial(){
                    var initId = sel.value;
                    var initData = window.INIT_VISITS || null;
                    if (initData && Array.isArray(initData.labels) && Array.isArray(initData.series)) {
                        renderVisitationChart(initData.labels, initData.series);
                    }
                    if (initId && initId !== '0') {
                        // use visitation selectedStart/End by default for visitation
                        fetchAndRenderStats(initId, selectedStart, selectedEnd);
                        fetchBreakdown(initId, selectedStart, selectedEnd);
                        // use patient-specific dates for patient stats (default to visitation range if unset)
                        if (!selectedPatientStart && !selectedPatientEnd) { selectedPatientStart = selectedStart; selectedPatientEnd = selectedEnd; }
                        fetchPatientStats(initId, selectedPatientStart, selectedPatientEnd);
                        // use tindakan-specific dates for tindakan stats (default to visitation range if unset)
                        if (!selectedTindakanStart && !selectedTindakanEnd) { selectedTindakanStart = selectedStart; selectedTindakanEnd = selectedEnd; }
                        fetchTindakanStats(initId, selectedTindakanStart, selectedTindakanEnd);
                        // use obat-specific dates for obat stats (default to visitation range if unset)
                        if (!selectedObatStart && !selectedObatEnd) { selectedObatStart = selectedStart; selectedObatEnd = selectedEnd; }
                        fetchObatStats(initId, selectedObatStart, selectedObatEnd);
                    }
                })();
            }

            // Wait for moment/daterangepicker to be available (they are loaded in layout). Poll briefly.
            (function waitForDepsAndInit(){
                var tries = 0;
                var iv = setInterval(function(){
                    tries++;
                    if ((typeof moment !== 'undefined') || tries > 50) {
                        clearInterval(iv);
                        try { initRangePickerImpl(); } catch(e){ console.error('Failed to init range picker', e); }
                    }
                }, 100);
            })();

            // Attach click handlers for sorting top patients table (delegated)
            document.addEventListener('click', function(ev){
                var t = ev.target;
                // find ancestor th with data-sort
                while (t && t !== document) {
                    if (t.tagName && t.tagName.toLowerCase() === 'th' && t.dataset && t.dataset.sort) break;
                    t = t.parentNode;
                }
                if (!t || t === document) return;
                var sortField = t.dataset.sort;
                if (!sortField) return;
                var cur = _topPatientsSort.field;
                var dir = 'desc';
                if (sortField === cur) {
                    // toggle direction
                    dir = (_topPatientsSort.dir === 'desc') ? 'asc' : 'desc';
                }
                var curId = sel.value;
                var start = selectedPatientStart; var end = selectedPatientEnd;
                fetchTopPatients(curId, start, end, sortField, dir);
            });

            // Helper: fetch stats with optional start/end
            function fetchAndRenderStats(dokterId, start, end) {
                if (!dokterId) return;
                var qs = '';
                if (start && end) qs = '?start=' + encodeURIComponent(start) + '&end=' + encodeURIComponent(end);
                else if (!start && !end) qs = '?all=1';
                fetch('/statistik/dokter/' + dokterId + '/visitation-stats' + qs)
                    .then(function(res){ if(!res.ok) throw res; return res.json(); })
                    .then(function(sdata){
                        if (!sdata || !sdata.ok) return;
                        renderVisitationChart(sdata.labels, sdata.series);
                    }).catch(function(e){
                        console.error('Failed to load visitation stats', e);
                    });
            }
            // Fetch and populate visitation breakdown summary numbers (with optional start/end)
            function fetchBreakdown(dokterId, start, end) {
                if (!dokterId) return;
                var qs = '';
                if (start && end) qs = '?start=' + encodeURIComponent(start) + '&end=' + encodeURIComponent(end);
                else if (!start && !end) qs = '?all=1';
                fetch('/statistik/dokter/' + dokterId + '/visitation-breakdown' + qs)
                    .then(function(res){ if(!res.ok) throw res; return res.json(); })
                    .then(function(payload){
                        if (!payload || !payload.ok) return;
                        populateBreakdownSummary(payload.breakdown || {}, payload.total || 0, start, end);
                        // also refresh retention summary, tindakan and obat for same period
                        try { fetchRetentionStats(dokterId, start, end); fetchTindakanStats(dokterId, start, end); fetchObatStats(dokterId, start, end); } catch(e){ console.error('fetchRetentionStats error', e); }
                    }).catch(function(e){
                        console.error('Failed to load visitation breakdown', e);
                    });
            }

            // Fetch and populate patient-level statistics (total patients, gender, age buckets, status)
            function fetchPatientStats(dokterId, start, end) {
                if (!dokterId) return;
                var qs = '';
                if (start && end) qs = '?start=' + encodeURIComponent(start) + '&end=' + encodeURIComponent(end);
                else if (!start && !end) qs = '?all=1';
                fetch('/statistik/dokter/' + dokterId + '/patient-stats' + qs)
                    .then(function(res){ if(!res.ok) throw res; return res.json(); })
                    .then(function(pdata){
                        if (!pdata || !pdata.ok) return;
                            populatePatientStats(pdata, start, end);
                            // also refresh top patients table for the same date range
                            try { fetchTopPatients(dokterId, start, end); } catch(e){ console.error('fetchTopPatients error', e); }
                    }).catch(function(e){
                        console.error('Failed to load patient stats', e);
                    });
            }

            function populatePatientStats(data, start, end) {
                // Render only the three pie charts (Gender, Age buckets, Status)
                try { renderGenderChart(data.gender || { male:0, female:0, other:0 }); } catch (e) { console.error('renderGenderChart error', e); }
                try { renderAgeChart((data.age && data.age.buckets) ? data.age.buckets : {}); } catch (e) { console.error('renderAgeChart error', e); }

                // Update patient summary period text only
                var summaryEl = document.getElementById('patientSummary');
                if (summaryEl) {
                    if (start && end) {
                        try { summaryEl.textContent = 'Periode: ' + moment(start).format('DD MMM YYYY') + ' — ' + moment(end).format('DD MMM YYYY'); }
                        catch (e) { summaryEl.textContent = 'Periode: ' + start + ' - ' + end; }
                    } else {
                        summaryEl.textContent = 'Periode: Semua waktu';
                    }
                }
            }

            function populateBreakdownSummary(breakdown, total, start, end) {
                document.getElementById('totalVisits').textContent = total || 0;
                document.getElementById('kunjungan1').textContent = (breakdown[1] || 0);
                // detailed konsultasi counts
                var kNo = (typeof breakdown.konsultasi_no_lab !== 'undefined') ? breakdown.konsultasi_no_lab : 0;
                var kWith = (typeof breakdown.konsultasi_with_lab !== 'undefined') ? breakdown.konsultasi_with_lab : 0;
                var elNo = document.getElementById('kunjungan1_nolab'); if (elNo) elNo.textContent = kNo;
                var elWith = document.getElementById('kunjungan1_withlab'); if (elWith) elWith.textContent = kWith;
                document.getElementById('kunjungan2').textContent = (breakdown[2] || 0);
                document.getElementById('kunjungan3').textContent = (breakdown[3] || 0);
                var summaryEl = document.getElementById('breakdownSummary');
                if (summaryEl) {
                    if (start && end) {
                        try {
                            summaryEl.textContent = 'Periode: ' + moment(start).format('DD MMM YYYY') + ' — ' + moment(end).format('DD MMM YYYY');
                        } catch (e) { summaryEl.textContent = 'Periode: ' + start + ' - ' + end; }
                    } else {
                        summaryEl.textContent = 'Periode: Semua waktu';
                    }
                }
            }

            // Fetch retention/new vs returning stats for dokter and period
            function fetchRetentionStats(dokterId, start, end) {
                if (!dokterId) return;
                var qs = '';
                if (start && end) qs = '?start=' + encodeURIComponent(start) + '&end=' + encodeURIComponent(end);
                else if (!start && !end) qs = '?all=1';
                fetch('/statistik/dokter/' + dokterId + '/retention-stats' + qs)
                    .then(function(res){ if(!res.ok) throw res; return res.json(); })
                    .then(function(payload){
                        if (!payload || !payload.ok) return;
                        populateRetentionSummary(payload, start, end);
                    }).catch(function(e){
                        console.error('Failed to load retention stats', e);
                    });
            }

            function populateRetentionSummary(payload, start, end) {
                var total = (payload && payload.total) ? payload.total : 0;
                var nw = (payload && payload.new) ? payload.new : 0;
                var ret = (payload && payload.returning) ? payload.returning : 0;
                var rate = (payload && typeof payload.retention_rate !== 'undefined') ? payload.retention_rate : null;
                document.getElementById('ret_total').textContent = total;
                document.getElementById('ret_new').textContent = nw;
                document.getElementById('ret_returning').textContent = ret;
                document.getElementById('ret_rate').textContent = (rate !== null) ? (rate + ' %') : '-';
                var summaryEl = document.getElementById('breakdownSummary');
                if (summaryEl) {
                    if (start && end) {
                        try { summaryEl.textContent = 'Periode: ' + moment(start).format('DD MMM YYYY') + ' — ' + moment(end).format('DD MMM YYYY'); }
                        catch (e) { summaryEl.textContent = 'Periode: ' + start + ' - ' + end; }
                    } else {
                        summaryEl.textContent = 'Periode: Semua waktu';
                    }
                }
            }

            // Fetch tindakan stats (top tindakan) for dokter and period
            function fetchTindakanStats(dokterId, start, end) {
                if (!dokterId) return;
                var qsParts = [];
                if (start && end) { qsParts.push('start=' + encodeURIComponent(start)); qsParts.push('end=' + encodeURIComponent(end)); }
                else if (!start && !end) { qsParts.push('all=1'); }
                var qs = qsParts.length ? ('?' + qsParts.join('&')) : '';
                fetch('/statistik/dokter/' + dokterId + '/tindakan-stats' + qs)
                    .then(function(res){ if(!res.ok) throw res; return res.json(); })
                    .then(function(payload){
                        if (!payload || !payload.ok) {
                            renderTindakanTable([]);
                            return;
                        }
                        renderTindakanTable(payload.tops || []);
                    }).catch(function(err){
                        console.error('fetchTindakanStats error', err);
                        renderTindakanTable([]);
                    });
            }

            function renderTindakanTable(list) {
                var tb = document.querySelector('#tindakanTable tbody');
                if (!tb) return;
                tb.innerHTML = '';
                if (!list || list.length === 0) {
                    var tr = document.createElement('tr');
                    var td = document.createElement('td'); td.setAttribute('colspan', '3'); td.className = 'text-muted text-center'; td.textContent = 'Tidak ada data.'; tr.appendChild(td); tb.appendChild(tr); return;
                }
                list.forEach(function(r, idx){
                    var tr = document.createElement('tr');
                    var td1 = document.createElement('td'); td1.textContent = (idx+1);
                    var td2 = document.createElement('td'); td2.textContent = r.name || ('Tindakan ' + r.tindakan_id);
                    var td3 = document.createElement('td'); td3.className = 'text-end'; td3.textContent = r.count || 0;
                    tr.appendChild(td1); tr.appendChild(td2); tr.appendChild(td3);
                    tb.appendChild(tr);
                });
            }

            // Fetch obat stats (top obat by jumlah) for dokter and period
            function fetchObatStats(dokterId, start, end) {
                if (!dokterId) return;
                var qsParts = [];
                if (start && end) { qsParts.push('start=' + encodeURIComponent(start)); qsParts.push('end=' + encodeURIComponent(end)); }
                else if (!start && !end) { qsParts.push('all=1'); }
                var qs = qsParts.length ? ('?' + qsParts.join('&')) : '';
                fetch('/statistik/dokter/' + dokterId + '/obat-stats' + qs)
                    .then(function(res){ if(!res.ok) throw res; return res.json(); })
                    .then(function(payload){
                        if (!payload || !payload.ok) { renderObatTable([]); return; }
                        renderObatTable(payload.tops || []);
                    }).catch(function(err){
                        console.error('fetchObatStats error', err);
                        renderObatTable([]);
                    });
            }

            function renderObatTable(list) {
                var tb = document.querySelector('#obatTable tbody');
                if (!tb) return;
                tb.innerHTML = '';
                if (!list || list.length === 0) {
                    var tr = document.createElement('tr');
                    var td = document.createElement('td'); td.setAttribute('colspan', '3'); td.className = 'text-muted text-center'; td.textContent = 'Tidak ada data.'; tr.appendChild(td); tb.appendChild(tr); return;
                }
                list.forEach(function(r, idx){
                    var tr = document.createElement('tr');
                    var td1 = document.createElement('td'); td1.textContent = (idx+1);
                    var td2 = document.createElement('td'); td2.textContent = r.name || ('Obat ' + r.obat_id);
                    var td3 = document.createElement('td'); td3.className = 'text-end'; td3.textContent = r.jumlah || 0;
                    tr.appendChild(td1); tr.appendChild(td2); tr.appendChild(td3);
                    tb.appendChild(tr);
                });
            }

            // Toggle konsulatasi detail rows (collapsed by default)
            (function(){
                var konsRow = document.getElementById('kunjungan1_row');
                if (!konsRow) return;
                konsRow.style.cursor = 'pointer';
                konsRow.addEventListener('click', function(){
                    var details = document.querySelectorAll('.konsultasi-detail');
                    details.forEach(function(el){ el.classList.toggle('d-none'); });
                    var caret = document.getElementById('kunjungan1_caret');
                    if (caret) caret.textContent = (caret.textContent === '▾') ? '▸' : '▾';
                });
            })();
        })();
    </script>
    
@endsection
