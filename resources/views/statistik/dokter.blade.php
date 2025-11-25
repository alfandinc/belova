@extends('layouts.erm.app')

@section('title', 'Statistik Dokter')

@section('navbar')
    @include('layouts.pusatstatistik.navbar')
@endsection

@section('content')
    <div class="container-fluid mt-3">
        <div class="mb-3">
            <label for="dokterSelect" class="form-label">Pilih Dokter</label>
            <select id="dokterSelect" class="form-control" aria-label="Pilih Dokter">
                <option value="0">-- Pilih Dokter --</option>
                @foreach($dokterList as $d)
                    <option value="{{ $d->id }}" {{ ($dokter && $dokter->id == $d->id) ? 'selected' : '' }}>{{ $d->user->name ?? ('Dokter ' . $d->id) }} @if($d->spesialisasi) - {{ $d->spesialisasi->nama }} @endif</option>
                @endforeach
            </select>
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
                            <div class="d-flex align-items-center">
                                <input type="text" id="rangePicker" class="form-control form-control-sm" style="width:220px" />
                                <button id="allTimeBtn" type="button" class="btn btn-sm btn-link ms-2">All time</button>
                            </div>
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
                                                <tr>
                                                    <td>Konsultasi</td>
                                                    <td class="text-end"><span id="kunjungan1">-</span></td>
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
            <div class="col-12">
                <div class="card shadow-sm" style="border-radius:10px;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h4 class="mb-0" style="font-weight:700; color:#2c2f45;">Statistik Pasien</h4>
                            <div class="d-flex align-items-center">
                                <input type="text" id="patientRangePicker" class="form-control form-control-sm" style="width:200px" />
                                <button id="patientAllTimeBtn" type="button" class="btn btn-sm btn-link ms-2">All time</button>
                            </div>
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
                        // daily label
                        return (typeof moment !== 'undefined') ? moment(l).format('DD MMM') : l;
                    } else if (/^\d{4}-\d{2}$/.test(l)) {
                        // monthly label
                        return (typeof moment !== 'undefined') ? moment(l + '-01').format('MMM YYYY') : l;
                    } else {
                        return l;
                    }
                } catch(e){ return l; }
            });

            var options = {
                chart: { type: 'area', height: 260, toolbar: { show: false } },
                series: [{ name: 'Visitation', data: seriesData }],
                xaxis: { categories: categories, labels: { rotate: -45 } },
                stroke: { curve: 'smooth' },
                dataLabels: { enabled: false },
                colors: ['#4f7df0'],
                yaxis: { labels: { formatter: function(v){ return Math.round(v); } } },
                tooltip: { y: { formatter: function(v){ return v + ' visits'; } } }
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
            } catch(err) {
                console.error('Error creating ApexCharts instance', err);
            }
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

            // Initialize daterangepicker (requires moment & daterangepicker plugin loaded in layout)
            function initRangePickerImpl(){
                var start, end;
                if (typeof moment !== 'undefined' && typeof moment().endOfMonth === 'function') {
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
                    $('#rangePicker').daterangepicker({
                        startDate: start,
                        endDate: end,
                        locale: { format: 'YYYY-MM-DD' },
                        opens: 'left'
                    });

                    $('#rangePicker').on('apply.daterangepicker', function(ev, picker){
                        selectedStart = picker.startDate.format('YYYY-MM-DD');
                        selectedEnd = picker.endDate.format('YYYY-MM-DD');
                        // reload visitation stats for current dokter
                        var curId = sel.value;
                        if (curId && curId !== '0') {
                            fetchAndRenderStats(curId, selectedStart, selectedEnd);
                            fetchBreakdown(curId, selectedStart, selectedEnd);
                            // keep patient range independent; do not overwrite patient selection here
                        }
                    });

                    // initialize patient range picker (independent)
                    $('#patientRangePicker').daterangepicker({
                        startDate: start,
                        endDate: end,
                        locale: { format: 'YYYY-MM-DD' },
                        opens: 'left'
                    });

                    $('#patientRangePicker').on('apply.daterangepicker', function(ev, picker){
                        selectedPatientStart = picker.startDate.format('YYYY-MM-DD');
                        selectedPatientEnd = picker.endDate.format('YYYY-MM-DD');
                        var curId = sel.value;
                        if (curId && curId !== '0') {
                            fetchPatientStats(curId, selectedPatientStart, selectedPatientEnd);
                        }
                    });
                } else {
                    // fallback: set input value to current-month range
                    var inp = document.getElementById('rangePicker');
                    if (inp) {
                        if (typeof moment !== 'undefined') inp.value = start.format('YYYY-MM-DD') + ' - ' + end.format('YYYY-MM-DD');
                        else inp.value = selectedStart + ' - ' + selectedEnd;
                    }
                    var pinp = document.getElementById('patientRangePicker');
                    if (pinp) {
                        if (typeof moment !== 'undefined') pinp.value = start.format('YYYY-MM-DD') + ' - ' + end.format('YYYY-MM-DD');
                        else pinp.value = selectedStart + ' - ' + selectedEnd;
                        selectedPatientStart = selectedStart; selectedPatientEnd = selectedEnd;
                    }
                }

                var allBtn = document.getElementById('allTimeBtn');
                if (allBtn) {
                    allBtn.addEventListener('click', function(){
                        selectedStart = null; selectedEnd = null;
                        // clear daterangepicker display if present
                        if (window.jQuery && jQuery.fn.daterangepicker && typeof moment !== 'undefined') {
                            $('#rangePicker').data('daterangepicker').setStartDate(moment().startOf('month'));
                            $('#rangePicker').data('daterangepicker').setEndDate(moment().endOf('month'));
                            $('#rangePicker').val('All time');
                        } else {
                            var inp = document.getElementById('rangePicker'); if (inp) inp.value = 'All time';
                        }
                        var curId = sel.value;
                        if (curId && curId !== '0') {
                            fetchAndRenderStats(curId, null, null);
                            fetchBreakdown(curId, null, null);
                            // keep patient range independent
                        }
                    });
                }

                // patient all-time button (independent)
                var pAllBtn = document.getElementById('patientAllTimeBtn');
                if (pAllBtn) {
                    pAllBtn.addEventListener('click', function(){
                        selectedPatientStart = null; selectedPatientEnd = null;
                        if (window.jQuery && jQuery.fn.daterangepicker && typeof moment !== 'undefined') {
                            $('#patientRangePicker').data('daterangepicker').setStartDate(moment().startOf('month'));
                            $('#patientRangePicker').data('daterangepicker').setEndDate(moment().endOf('month'));
                            $('#patientRangePicker').val('All time');
                        } else {
                            var pinp = document.getElementById('patientRangePicker'); if (pinp) pinp.value = 'All time';
                        }
                        var curId = sel.value;
                        if (curId && curId !== '0') {
                            fetchPatientStats(curId, null, null);
                        }
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
                        // also refresh retention summary for same period
                        try { fetchRetentionStats(dokterId, start, end); } catch(e){ console.error('fetchRetentionStats error', e); }
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
        })();
    </script>
    
@endsection
