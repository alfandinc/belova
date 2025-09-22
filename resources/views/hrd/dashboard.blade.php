@extends('layouts.hrd.app')
@section('title', 'Dashboard | HRD Belova')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection  
@section('content')
<div class="container">
    <h2 class="my-4">HRD Dashboard</h2>

    <div class="row">
        {{-- Summary cards --}}
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-primary h-100">
                <div class="card-body">
                    <h5 class="card-title">Employees</h5>
                    <p class="card-text display-6">{{ $counts['employees'] ?? 0 }}</p>
                </div>
                <div class="card-footer">
                    <small>Total active employees</small>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card text-white bg-warning h-100">
                <div class="card-body">
                    <h5 class="card-title">Pending Leaves</h5>
                    <p class="card-text display-6">{{ $counts['pending_leaves'] ?? 0 }}</p>
                </div>
                <div class="card-footer">
                    <small>Awaiting approval</small>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card text-white bg-success h-100">
                <div class="card-body">
                    <h5 class="card-title">Overtime Requests</h5>
                    <p class="card-text display-6">{{ $counts['pending_overtime'] ?? 0 }}</p>
                </div>
                <div class="card-footer">
                    <small>Pending overtime</small>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card text-white bg-info h-100">
                <div class="card-body">
                    <h5 class="card-title">Shift Changes</h5>
                    <p class="card-text display-6">{{ $counts['pending_shifts'] ?? 0 }}</p>
                </div>
                <div class="card-footer">
                    <small>Pending shift swaps</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Left column: pending approvals table --}}
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header">Pending Approvals</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Type</th>
                                    <th>Employee</th>
                                    <th>Date(s)</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pendingRows as $i => $r)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>{{ class_basename($r) }}</td>
                                        <td>
                                            @php
                                                $emp = $r->employee ?? null;
                                                $empName = 'Unknown';
                                                if ($emp) {
                                                    if (!empty($emp->nama)) {
                                                        $empName = $emp->nama;
                                                    } elseif (isset($emp->user) && !empty($emp->user->name)) {
                                                        $empName = $emp->user->name;
                                                    }
                                                }
                                            @endphp
                                            {{ $empName }}
                                        </td>
                                        <td>
                                            @if(isset($r->tanggal))
                                                {{ optional($r->tanggal)->format('Y-m-d') }}
                                            @elseif(isset($r->tanggal_mulai))
                                                {{ optional($r->tanggal_mulai)->format('Y-m-d') }} - {{ optional($r->tanggal_selesai)->format('Y-m-d') }}
                                            @elseif(isset($r->tanggal_shift))
                                                {{ optional($r->tanggal_shift)->format('Y-m-d') }}
                                            @endif
                                        </td>
                                        <td>
                                            @if(property_exists($r, 'status_manager') && $r->status_manager == 'pending')
                                                <span class="badge bg-warning text-dark">Manager: pending</span>
                                            @endif
                                            @if(property_exists($r, 'status_hrd') && $r->status_hrd == 'pending')
                                                <span class="badge bg-info">HRD: pending</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center">No pending approvals</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right column: birthdays & quick chart --}}
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">Upcoming Birthdays</div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        @forelse($birthdays as $emp)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ $emp->nama }}</strong><br>
                                    <small class="text-muted">{{ $emp->upcoming_date->format('M d') }} (in {{ (int)$emp->upcoming_days }} days)</small>
                                </div>
                                <div>
                                    <span class="badge bg-secondary">{{ optional($emp->tanggal_lahir)->format('Y') }}</span>
                                </div>
                            </li>
                        @empty
                            <li class="list-group-item">No upcoming birthdays</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Monthly Summary</div>
                <div class="card-body">
                    <canvas id="hrdSummaryChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- small greeting and logout --}}
    <div class="mt-4 d-flex justify-content-between align-items-center">
        <div>
            <p class="mb-0">Hello, {{ auth()->user()->name }}! You are logged in as <strong>{{ auth()->user()->getRoleNames()->first() }}</strong>.</p>
        </div>
        <div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-danger">Logout</button>
            </form>
        </div>
    </div>

    </div> {{-- .container end --}}

@endsection {{-- content end --}}

@section('scripts')
    {{-- Chart.js - include from CDN if not already available in layout --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var ctx = document.getElementById('hrdSummaryChart');
            if (!ctx) return;
            var chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Employees','Pending Leaves','Overtime','Shift Changes'],
                    datasets: [{
                        label: 'Count',
                        backgroundColor: ['#0d6efd','#ffc107','#198754','#0dcaf0'],
                        data: [
                            {{ $counts['employees'] ?? 0 }},
                            {{ $counts['pending_leaves'] ?? 0 }},
                            {{ $counts['pending_overtime'] ?? 0 }},
                            {{ $counts['pending_shifts'] ?? 0 }}
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        });
    </script>
@endsection
