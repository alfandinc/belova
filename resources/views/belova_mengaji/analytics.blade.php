@extends('layouts.erm.app')
@section('title', 'Analytics Mengaji')
@section('navbar')
    @include('layouts.erm.navbar-ngaji')
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Analytics Mengaji</h4>
                    <form class="form-inline">
                        <label class="mr-2">From</label>
                        <input type="date" name="from" value="{{ $from }}" class="form-control mr-2">
                        <label class="mr-2">To</label>
                        <input type="date" name="to" value="{{ $to }}" class="form-control mr-2">
                        <button class="btn btn-primary" type="submit">Apply</button>
                    </form>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6 col-md-2 mb-2">
                            <div class="card p-3">
                                <div class="text-muted">Average Total</div>
                                <h4 class="mb-0" id="avg_total">{{ $avg_total ? number_format($avg_total, (floor($avg_total)==$avg_total?0:2)) : '-' }}</h4>
                            </div>
                        </div>
                        <div class="col-6 col-md-2 mb-2">
                            <div class="card p-3">
                                <div class="text-muted">Avg Makhroj</div>
                                <h4 class="mb-0" id="avg_makhroj">{{ $avg_makhroj ? number_format($avg_makhroj,1) : '-' }}</h4>
                            </div>
                        </div>
                        <div class="col-6 col-md-2 mb-2">
                            <div class="card p-3">
                                <div class="text-muted">Avg Tajwid</div>
                                <h4 class="mb-0" id="avg_tajwid">{{ $avg_tajwid ? number_format($avg_tajwid,1) : '-' }}</h4>
                            </div>
                        </div>
                        <div class="col-6 col-md-2 mb-2">
                            <div class="card p-3">
                                <div class="text-muted">Avg Panjang / Pendek</div>
                                <h4 class="mb-0" id="avg_panjang">{{ $avg_panjang ? number_format($avg_panjang,1) : '-' }}</h4>
                            </div>
                        </div>
                        <div class="col-6 col-md-2 mb-2">
                            <div class="card p-3">
                                <div class="text-muted">Avg Kelancaran</div>
                                <h4 class="mb-0" id="avg_kelancaran">{{ $avg_kelancaran ? number_format($avg_kelancaran,1) : '-' }}</h4>
                            </div>
                        </div>
                        <div class="col-12 col-md-2 mb-2">
                            <div class="card p-3 text-center">
                                <div class="text-muted">Present / Absent</div>
                                <h5 class="mb-0" id="present_absent">{{ $present_employees }} / {{ $absent_employees }}</h5>
                            </div>
                        </div>
                    </div>

                    <script>
                        (function(){
                            // Debounce helper
                            function debounce(fn, delay){
                                var t;
                                return function(){
                                    var args = arguments;
                                    clearTimeout(t);
                                    t = setTimeout(function(){ fn.apply(null, args); }, delay);
                                };
                            }

                            function formatNumberSmart(n){
                                if (n === null || n === undefined) return '-';
                                if (Number.isInteger(n)) return n.toString();
                                return parseFloat(n).toFixed(2).replace(/\.00$/,'');
                            }

                            function renderTables(data){
                                var topActiveTbody = document.querySelector('#top_active_table tbody');
                                var perAvgTbody = document.querySelector('#per_avg_table tbody');
                                topActiveTbody.innerHTML = '';
                                perAvgTbody.innerHTML = '';
                                data.top_active.forEach(function(r, i){
                                    var tr = document.createElement('tr');
                                    tr.innerHTML = '<td>'+(i+1)+'</td><td>'+(r.nama || ('ID '+r.employee_id))+'</td><td>'+r.cnt+'</td>';
                                    topActiveTbody.appendChild(tr);
                                });
                                data.per_employee_avg.forEach(function(r, i){
                                    var tr = document.createElement('tr');
                                    tr.innerHTML = '<td>'+(i+1)+'</td><td>'+(r.nama || ('ID '+r.employee_id))+'</td><td>'+formatNumberSmart(r.avg_total)+'</td><td>'+r.cnt+'</td>';
                                    perAvgTbody.appendChild(tr);
                                });
                            }

                            function fetchAnalytics(){
                                var from = document.querySelector('input[name="from"]').value;
                                var to = document.querySelector('input[name="to"]').value;
                                var url = "{{ route('belova.mengaji.analytics.data') }}" + '?from=' + encodeURIComponent(from) + '&to=' + encodeURIComponent(to);
                                fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                                .then(function(resp){ return resp.json(); })
                                .then(function(json){
                                    document.getElementById('avg_total').textContent = formatNumberSmart(json.avg_total);
                                    document.getElementById('avg_makhroj').textContent = json.avg_makhroj ? parseFloat(json.avg_makhroj).toFixed(1) : '-';
                                    document.getElementById('avg_tajwid').textContent = json.avg_tajwid ? parseFloat(json.avg_tajwid).toFixed(1) : '-';
                                    document.getElementById('avg_panjang').textContent = json.avg_panjang ? parseFloat(json.avg_panjang).toFixed(1) : '-';
                                    document.getElementById('avg_kelancaran').textContent = json.avg_kelancaran ? parseFloat(json.avg_kelancaran).toFixed(1) : '-';
                                    document.getElementById('present_absent').textContent = json.present_employees + ' / ' + json.absent_employees;
                                    renderTables(json);
                                }).catch(function(err){
                                    console.error('Failed to load analytics', err);
                                });
                            }

                            var debouncedFetch = debounce(fetchAnalytics, 400);

                            // fetch on input change without needing to click apply
                            document.querySelector('input[name="from"]').addEventListener('change', debouncedFetch);
                            document.querySelector('input[name="to"]').addEventListener('change', debouncedFetch);

                            // intercept form submit to do AJAX instead
                            var form = document.querySelector('.card-header form');
                            form.addEventListener('submit', function(e){ e.preventDefault(); fetchAnalytics(); });

                            // initial fetch on load
                            document.addEventListener('DOMContentLoaded', function(){ fetchAnalytics(); });
                        })();
                    </script>

                    <hr>
                    <h5>Top Active Employees (most records)</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Employee</th>
                                    <th>Records</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($top_active as $i => $r)
                                <tr>
                                    <td>{{ $i+1 }}</td>
                                    <td>{{ optional($r->employee)->nama ?? 'ID '.$r->employee_id }}</td>
                                    <td>{{ $r->cnt }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <hr>
                    <h5>Top by Average Total</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Employee</th>
                                    <th>Avg Total</th>
                                    <th>Records</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($per_employee_avg as $i => $r)
                                <tr>
                                    <td>{{ $i+1 }}</td>
                                    <td>{{ optional($r->employee)->nama ?? 'ID '.$r->employee_id }}</td>
                                    <td>{{ number_format($r->avg_total, (floor($r->avg_total)==$r->avg_total?0:2)) }}</td>
                                    <td>{{ $r->cnt }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
