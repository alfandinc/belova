@php
    $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
@endphp
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-center bg-light">
            <div class="card-body">
                <h6 class="card-title">Most Frequent Patient</h6>
                <form id="most-frequent-form" class="mb-2" onsubmit="return false;">
                    <div class="form-row mb-2">
                        <div class="col">
                            <select name="month" id="most-frequent-month" class="form-control form-control-sm">
                                @foreach($monthNames as $idx => $name)
                                    <option value="{{ $idx+1 }}" {{ $selectedMonth == $idx+1 ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col">
                            <select name="year" id="most-frequent-year" class="form-control form-control-sm">
                                @foreach($yearRange as $year)
                                    <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </form>
                <h3 class="font-weight-bold" id="most-frequent-pasien">
                    {{ $mostFrequentPatient ?? '-' }}
                    @if(isset($mostFrequentPatientCount) && $mostFrequentPatientCount)
                        <span class="text-muted" style="font-size: 1rem;">({{ $mostFrequentPatientCount }} visits)</span>
                    @endif
                </h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center bg-light">
            <div class="card-body">
                <h6 class="card-title">Busiest Month</h6>
                <h3 class="font-weight-bold">
                    {{ $busiestMonth ? $monthNames[$busiestMonth-1] : '-' }}
                </h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center bg-light">
            <div class="card-body">
                <h6 class="card-title">Total Visit</h6>
                <h3 class="font-weight-bold">{{ $totalVisit }}</h3>
            </div>
        </div>
    </div>
</div>
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h6 class="card-title mb-0">Visits by Payment Method</h6></div>
            <div class="card-body">
                <div id="visitsByPaymentChart" style="height: 250px;"></div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h6 class="card-title mb-0">Visits per Day (This Month)</h6></div>
            <div class="card-body">
                <div id="visitsByDayChart" style="height: 250px;"></div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Pie/Donut Chart for Payment Method
    var paymentOptions = {
        chart: { type: 'donut', height: 250 },
        labels: Object.keys(@json($visitsByPayment)),
        series: Object.values(@json($visitsByPayment)),
        legend: { position: 'bottom' }
    };
    new ApexCharts(document.querySelector('#visitsByPaymentChart'), paymentOptions).render();

    // Bar Chart for Visits by Day
    var dayOptions = {
        chart: { type: 'bar', height: 250 },
        series: [{ name: 'Visits', data: @json($visitsByDay) }],
        xaxis: { categories: Array.from({length: @json(count($visitsByDay))}, (_, i) => i + 1) },
        legend: { show: false }
    };
    new ApexCharts(document.querySelector('#visitsByDayChart'), dayOptions).render();

    // AJAX for most frequent patient
    function updateMostFrequentPatient() {
        var month = document.getElementById('most-frequent-month').value;
        var year = document.getElementById('most-frequent-year').value;
        fetch(`/erm/dashboard/most-frequent-patient?month=${month}&year=${year}`)
            .then(response => response.json())
            .then(data => {
                let html = data.name;
                if (data.count && data.name !== '-') {
                    html += ` <span class="text-muted" style="font-size: 1rem;">(${data.count} visits)</span>`;
                }
                document.getElementById('most-frequent-pasien').innerHTML = html;
            });
    }
    document.getElementById('most-frequent-month').addEventListener('change', updateMostFrequentPatient);
    document.getElementById('most-frequent-year').addEventListener('change', updateMostFrequentPatient);
});
</script>
@endpush
