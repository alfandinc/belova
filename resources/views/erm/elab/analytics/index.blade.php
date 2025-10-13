@extends('layouts.erm.app')
@section('title', 'ERM | E-Lab Analytics')
@section('navbar')
    @include('layouts.erm.navbar-lab')
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">E-Lab Analytics</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12 mb-3">
            <div class="d-flex align-items-center analytics-controls">
                <label class="me-2 mb-0 small">Dari</label>
                <input type="date" id="analytics-start" class="form-control form-control-sm me-2" style="width:130px;" />
                <label class="me-2 mb-0 small">Sampai</label>
                <input type="date" id="analytics-end" class="form-control form-control-sm me-2" style="width:130px;" />
                <button id="analytics-refresh" class="btn btn-sm btn-primary">Refresh</button>
                <button id="analytics-reset" class="btn btn-sm btn-outline-secondary ms-2">Reset</button>
            </div>
        </div>

        <div class="col-12 col-lg-6 mb-3">
            <div class="card">
                <div class="card-body py-2">
                    <h6 class="card-title mb-2">Kunjungan / Hari</h6>
                    <div class="chart-wrapper"><canvas id="chart-visits-per-day" class="chart-canvas"></canvas></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6 mb-3">
            <div class="card">
                <div class="card-body py-2">
                    <h6 class="card-title mb-2">Pemeriksaan / Kategori</h6>
                    <div class="chart-wrapper"><canvas id="chart-tests-per-category" class="chart-canvas"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-12 col-lg-4 mb-3">
            <div class="card">
                <div class="card-body py-2">
                    <h6 class="card-title mb-2">Pasien Baru / Returning</h6>
                    <div class="chart-wrapper"><canvas id="chart-patients-type" class="chart-canvas"></canvas></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4 mb-3">
            <div class="card">
                <div class="card-body py-2">
                    <h6 class="card-title mb-2">Status Pembayaran</h6>
                    <div class="chart-wrapper"><canvas id="chart-payment-status" class="chart-canvas"></canvas></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4 mb-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-2">Ringkasan Totals</h6>
                    <div id="totals-summary" class="small">
                        <div>Total Kunjungan: <span id="total-visits">-</span></div>
                        <div>Total Pemeriksaan: <span id="total-tests">-</span></div>
                        <div>Total Nominal: <span id="total-nominal-analytics">-</span></div>
                        <div>Total Sudah Dibayar: <span id="total-paid-analytics">-</span></div>
                        <div>Total Belum Dibayar: <span id="total-unpaid-analytics">-</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-12 col-lg-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-2">Top 10 Pemeriksaan</h6>
                    <canvas id="chart-top-tests" style="height:260px"></canvas>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6 mb-3">
            <div class="row">
                <div class="col-12 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title mb-2">Top 10 Pasien (by Visits)</h6>
                            <div class="table-responsive">
                                <table class="table table-sm" id="top-patients-table">
                                    <thead><tr><th>#</th><th>Nama Pasien</th><th>Visits</th></tr></thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title mb-2">Top 10 Pasien (by Spending)</h6>
                            <canvas id="chart-top-spenders" style="height:220px"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    // styles (inlined for this view) — slightly larger than previous compact
    const style = document.createElement('style');
    style.innerHTML = `
        .card { margin-bottom:10px; }
        .card .card-body.py-2 { padding-top:10px; padding-bottom:10px; }
        .card-title { font-size:1rem; }
        .chart-wrapper { position:relative; height:200px; }
        canvas.chart-canvas { width:100% !important; height:200px !important; }
        /* Ensure top charts have a fixed height to prevent vertical stretching */
        #chart-top-tests { width:100% !important; height:260px !important; display:block; }
        #chart-top-spenders { width:100% !important; height:220px !important; display:block; }
        .analytics-controls input { height:34px; }
    `;
    document.head.appendChild(style);
    // small helper to escape HTML when inserting text into the DOM
    function escapeHtml(string){
        if (string === null || string === undefined) return '';
        return String(string).replace(/[&<>"'/`=]/g, function (s) {
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','/':'&#x2F;','`':'&#x60;','=':'&#x3D;'})[s];
        });
    }
    // default date range: last 30 days
    const endDefault = new Date();
    const startDefault = new Date(); startDefault.setDate(endDefault.getDate() - 29);
    function toYMD(d){ return d.toISOString().slice(0,10); }
    document.getElementById('analytics-start').value = toYMD(startDefault);
    document.getElementById('analytics-end').value = toYMD(endDefault);
    // Placeholder charts with empty data. We'll fetch real endpoints later.
    const visitsCtx = document.getElementById('chart-visits-per-day').getContext('2d');
    const compactOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: { bodyFont: { size: 11 }, titleFont: { size: 12 } }
        },
        scales: {
            x: { ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 6, font: { size: 10 } } },
            y: { ticks: { font: { size: 10 } }, beginAtZero: true }
        },
        elements: { point: { radius: 1 }, line: { tension: 0.2 } }
    };

    const visitsChart = new Chart(visitsCtx, {
        type: 'line',
        data: { labels: [], datasets: [{ label: 'Kunjungan', data: [], borderColor: '#4e73df', backgroundColor: 'rgba(78,115,223,0.05)' }] },
        options: compactOptions
    });

    const testsCtx = document.getElementById('chart-tests-per-category').getContext('2d');
    const testsChart = new Chart(testsCtx, {
        type: 'doughnut',
        data: { labels: [], datasets: [{ data: [], backgroundColor: ['#1cc88a','#36b9cc','#f6c23e','#e74a3b','#4e73df','#858796'] }] },
        options: Object.assign({}, compactOptions, { plugins: { legend: { display: true, position: 'bottom', labels: { boxWidth:10, font: { size: 11 } } } } })
    });

    const patientsCtx = document.getElementById('chart-patients-type').getContext('2d');
    const patientsChart = new Chart(patientsCtx, {
        type: 'pie',
        data: { labels: ['New','Returning'], datasets: [{ data: [0,0], backgroundColor: ['#4e73df','#858796'] }] },
        options: Object.assign({}, compactOptions, { plugins: { legend: { display: false } } })
    });

    const paymentCtx = document.getElementById('chart-payment-status').getContext('2d');
    const paymentChart = new Chart(paymentCtx, {
        type: 'bar',
        data: { labels: ['Sudah Dibayar','Belum Dibayar'], datasets: [{ data: [0,0], backgroundColor: ['#1cc88a','#e74a3b'] }] },
        options: Object.assign({}, compactOptions, { plugins: { legend: { display: false } } })
    });

    // Top tests chart (horizontal bar)
    const topTestsCtx = document.getElementById('chart-top-tests').getContext('2d');
    const topTestsChart = new Chart(topTestsCtx, {
        type: 'bar',
        data: { labels: [], datasets: [{ label: 'Jumlah', data: [], backgroundColor: '#36b9cc', barThickness: 18, maxBarThickness: 24 }] },
        options: Object.assign({}, compactOptions, {
            indexAxis: 'y',
            maintainAspectRatio: false,
            scales: { x: { ticks: { font: { size: 11 } } }, y: { ticks: { font: { size: 11 } } } },
            plugins: { legend: { display: false } }
        })
    });

    // Top spenders chart
    const topSpendersCtx = document.getElementById('chart-top-spenders').getContext('2d');
    const topSpendersChart = new Chart(topSpendersCtx, {
        type: 'bar',
        data: { labels: [], datasets: [{ label: 'Nominal (Rp)', data: [], backgroundColor: '#1cc88a', barThickness: 18, maxBarThickness: 24 }] },
        options: Object.assign({}, compactOptions, {
            indexAxis: 'y',
            maintainAspectRatio: false,
            scales: { x: { ticks: { font: { size: 11 }, callback: v => v } }, y: { ticks: { font: { size: 11 } } } },
            plugins: { legend: { display: false } }
        })
    });

    // New fetch functions for top lists
    function fetchTopTests(start,end){ return fetch(`/erm/elab/analytics/top-tests?start=${start}&end=${end}`).then(r=>r.json()); }
    function fetchTopPatientsVisits(start,end){ return fetch(`/erm/elab/analytics/top-patients-visits?start=${start}&end=${end}`).then(r=>r.json()); }
    function fetchTopPatientsSpending(start,end){ return fetch(`/erm/elab/analytics/top-patients-spending?start=${start}&end=${end}`).then(r=>r.json()); }
    function fetchTotalsSummary(start,end){ return fetch(`/erm/elab/analytics/totals-summary?start=${start}&end=${end}`).then(r=>r.json()); }

    // Fetch functions
    function fetchVisits(start,end){
        return fetch(`/erm/elab/analytics/visits-per-day?start=${start}&end=${end}`).then(r=>r.json());
    }
    function fetchTests(start,end){
        return fetch(`/erm/elab/analytics/tests-per-category?start=${start}&end=${end}`).then(r=>r.json());
    }
    function fetchPatients(start,end){
        return fetch(`/erm/elab/analytics/patients-type?start=${start}&end=${end}`).then(r=>r.json());
    }
    function fetchPayment(start,end){
        return fetch(`/erm/elab/analytics/payment-status?start=${start}&end=${end}`).then(r=>r.json());
    }

    async function refreshAll(){
        const start = document.getElementById('analytics-start').value;
        const end = document.getElementById('analytics-end').value;
        try{
            const [visits, tests, patients, payment, topTests, topPatientsVisits, topSpenders, totals] = await Promise.all([
                fetchVisits(start,end), fetchTests(start,end), fetchPatients(start,end), fetchPayment(start,end),
                fetchTopTests(start,end), fetchTopPatientsVisits(start,end), fetchTopPatientsSpending(start,end), fetchTotalsSummary(start,end)
            ]);

            // visits
            visitsChart.data.labels = visits.labels || [];
            visitsChart.data.datasets[0].data = visits.data || [];
            visitsChart.update();

            // tests
            testsChart.data.labels = tests.labels || [];
            testsChart.data.datasets[0].data = tests.data || [];
            testsChart.update();

            // patients
            patientsChart.data.labels = patients.labels || ['New','Returning'];
            patientsChart.data.datasets[0].data = patients.data || [0,0];
            patientsChart.update();

            // payment
            paymentChart.data.labels = payment.labels || ['Sudah Dibayar','Belum Dibayar'];
            paymentChart.data.datasets[0].data = payment.data || [0,0];
            paymentChart.update();

                // top tests
                topTestsChart.data.labels = topTests.labels || [];
                topTestsChart.data.datasets[0].data = topTests.data || [];
                topTestsChart.update();

                // top patients table
                const tbody = document.querySelector('#top-patients-table tbody');
                tbody.innerHTML = '';
                if (topPatientsVisits.labels && topPatientsVisits.labels.length){
                    topPatientsVisits.labels.forEach((name, idx) => {
                        const visitsCount = topPatientsVisits.data[idx] || 0;
                        const tr = document.createElement('tr');
                        tr.innerHTML = `<td>${idx+1}</td><td>${escapeHtml(name)}</td><td>${visitsCount}</td>`;
                        tbody.appendChild(tr);
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="3">No data</td></tr>';
                }

                // top spenders
                topSpendersChart.data.labels = topSpenders.labels || [];
                topSpendersChart.data.datasets[0].data = topSpenders.data || [];
                topSpendersChart.update();

                // totals
                if (totals) {
                    document.getElementById('total-visits').textContent = totals.total_visits ?? '-';
                    document.getElementById('total-tests').textContent = totals.total_tests ?? '-';
                    document.getElementById('total-nominal-analytics').textContent = (totals.total_nominal ?? 0).toLocaleString('id-ID');
                    document.getElementById('total-paid-analytics').textContent = (totals.total_paid ?? 0).toLocaleString('id-ID');
                    document.getElementById('total-unpaid-analytics').textContent = (totals.total_unpaid ?? 0).toLocaleString('id-ID');
                }

        }catch(err){ console.error('Failed to fetch analytics', err); }
    }

    document.getElementById('analytics-refresh').addEventListener('click', refreshAll);

    // initial load
    refreshAll();
});
</script>
@endsection
