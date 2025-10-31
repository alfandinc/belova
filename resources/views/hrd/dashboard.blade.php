@extends('layouts.hrd.app')
@section('title', 'Dashboard | HRD Belova')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection  
@section('content')
@hasanyrole('Hrd|Admin')
<div class="container hrd-dashboard">
    <style>
        /* Scoped dashboard tweaks */
        .hrd-dashboard .card { border-radius:8px; }
    .hrd-dashboard .card .display-6 { font-size:1.6rem; }
    .hrd-dashboard .card .small { font-size:0.95rem; }
    .hrd-dashboard .card-header { font-size:1rem }
    .hrd-dashboard .list-group-item { font-size:0.95rem }
        .hrd-dashboard .list-group-item { padding:0.75rem 1rem; }
        .hrd-dashboard .avatar { width:48px;height:48px;font-size:1.05rem }
    .hrd-dashboard .font-weight-bold { font-size:1rem }
        .hrd-dashboard .card-header { font-weight:600 }
        .hrd-dashboard .card-body .text-muted { opacity:0.85 }
        .hrd-dashboard .page-note { color: #9aa3b2 }
        /* Smaller/normal text specifically for the Pending Approvals card */
        .hrd-dashboard .pending-approvals .list-group-item { font-size:0.92rem; padding:0.6rem 1rem; }
        .hrd-dashboard .pending-approvals .font-weight-bold { font-size:0.95rem; }
        .hrd-dashboard .pending-approvals .small { font-size:0.85rem; }
        .hrd-dashboard .pending-approvals .mr-3 { font-size:0.9rem; }
    /* Birthday "today" styling */
    .hrd-dashboard .birthday-today-name { color: #dc3545; font-weight:700; display:inline-flex; align-items:center; gap:0.45rem; }
    /* make icon clearly visible by placing it inside a small circular background */
    .hrd-dashboard .birthday-icon { width:22px; height:22px; display:inline-flex; align-items:center; justify-content:center; border-radius:50%; background:#dc3545; color:#fff; border:1px solid rgba(0,0,0,0.05); box-shadow:0 1px 2px rgba(0,0,0,0.06); margin-left:6px; }
    .hrd-dashboard .birthday-icon svg { width:14px; height:14px; display:block; fill:currentColor; stroke:currentColor; }
    </style>
    <h2 class="my-4">HRD Dashboard</h2>

    <div class="row">
        {{-- Summary cards (modern) --}}
        <div class="col-md-3 mb-3">
            <div class="card h-100 shadow-sm">
                <div class="card-body d-flex align-items-center">
                    <div class="mr-3">
                        <!-- user icon -->
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 12c2.761 0 5-2.239 5-5s-2.239-5-5-5-5 2.239-5 5 2.239 5 5 5z" stroke="#0d6efd" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M19 21v-1c0-2.761-4-4-7-4s-7 1.239-7 4v1" stroke="#0d6efd" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <div>
                        <div class="text-muted small">Employees</div>
                        <div class="font-weight-bold display-6">{{ $counts['employees'] ?? 0 }}</div>
                        <div class="text-muted small">Total active employees</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card h-100 shadow-sm">
            <div class="card-body d-flex align-items-center">
                    <div class="mr-3">
                        <!-- leave icon -->
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21 8V7a2 2 0 0 0-2-2h-3" stroke="#ffc107" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 12h18" stroke="#ffc107" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M5 21h14" stroke="#ffc107" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <div>
                        <div class="text-muted small">Pending Leaves</div>
                        <div class="font-weight-bold display-6">{{ $counts['pending_leaves'] ?? 0 }}</div>
                        <div class="text-muted small">Awaiting approval</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card h-100 shadow-sm">
            <div class="card-body d-flex align-items-center">
                    <div class="mr-3">
                        <!-- clock icon -->
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="9" stroke="#198754" stroke-width="1.5"/><path d="M12 7v6l4 2" stroke="#198754" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <div>
                        <div class="text-muted small">Overtime Requests</div>
                        <div class="font-weight-bold display-6">{{ $counts['pending_overtime'] ?? 0 }}</div>
                        <div class="text-muted small">Pending overtime</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card h-100 shadow-sm">
            <div class="card-body d-flex align-items-center">
                    <div class="mr-3">
                        <!-- swap icon -->
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16 3h5v5" stroke="#0dcaf0" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 3l-6 6" stroke="#0dcaf0" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M8 21H3v-5" stroke="#0dcaf0" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 21l6-6" stroke="#0dcaf0" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <div>
                        <div class="text-muted small">Shift Changes</div>
                        <div class="font-weight-bold display-6">{{ $counts['pending_shifts'] ?? 0 }}</div>
                        <div class="text-muted small">Pending shift swaps</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Left column: pending approvals table --}}
        <div class="col-lg-8">
            <div class="card mb-3 pending-approvals">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>Pending Approvals</div>
                    <a href="{{ route('hrd.dashboard') }}" class="small">View all</a>
                </div>
                <div class="card-body">
                    @if(collect($pendingRows)->isEmpty())
                        <div class="text-center py-4">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none"><path d="M12 2v6" stroke="#adb5bd" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M20 12h-6" stroke="#adb5bd" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 12h6" stroke="#adb5bd" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <div class="mt-3 text-muted">No pending approvals at the moment</div>
                        </div>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach($pendingRows as $r)
                                @php
                                    $emp = $r->employee ?? null;
                                    $empName = $emp->nama ?? ($emp->user->name ?? 'Unknown');
                                    $dateLabel = isset($r->tanggal) ? optional($r->tanggal)->format('Y-m-d') : (isset($r->tanggal_mulai) ? optional($r->tanggal_mulai)->format('Y-m-d') . ' - ' . optional($r->tanggal_selesai)->format('Y-m-d') : (isset($r->tanggal_shift) ? optional($r->tanggal_shift)->format('Y-m-d') : '-'));
                                @endphp
                                <li class="list-group-item d-flex align-items-center">
                                    <div class="mr-3" style="width:44px;height:44px;display:flex;align-items:center;justify-content:center">{{ strtoupper(substr($empName,0,1)) }}</div>
                                    <div class="flex-fill">
                                        <div class="font-weight-bold">{{ class_basename($r) }} — {{ $empName }}</div>
                                        <div class="small text-muted">{{ $dateLabel }}</div>
                                    </div>
                                    <div>
                                        @if(property_exists($r, 'status_manager') && $r->status_manager == 'pending')
                                            <span class="badge badge-warning text-dark">M</span>
                                        @endif
                                        @if(property_exists($r, 'status_hrd') && $r->status_hrd == 'pending')
                                            <span class="badge badge-info">H</span>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right column: birthdays & quick chart --}}
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>Upcoming Birthdays</div>
                    <!-- Button trigger modal -->
                    <button type="button" class="btn btn-sm btn-link p-0 small" data-toggle="modal" data-target="#allBirthdaysModal">
                        View all
                    </button>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        @forelse($birthdays as $emp)
                            <li class="list-group-item">
                                <div>
                                    <div class="font-weight-bold @if((int)$emp->upcoming_days === 0) birthday-today-name @endif">
                                        {{ $emp->nama }}
                                        @if((int)$emp->upcoming_days === 0)
                                            <span class="birthday-icon" title="Birthday today" aria-hidden="true">
                                                <!-- small cake + confetti SVG (compact) -->
                                                <svg width="18" height="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" focusable="false" aria-hidden="true">
                                                    <!-- Simple cake silhouette: two-tier cake + candle, uses currentColor to appear white on red background -->
                                                    <!-- top candle -->
                                                    <rect x="11" y="2.5" width="2" height="4" rx="0.5" fill="currentColor" />
                                                    <!-- top tier -->
                                                    <rect x="6" y="8" width="12" height="3.5" rx="0.8" fill="currentColor" />
                                                    <!-- bottom tier -->
                                                    <rect x="4" y="12.2" width="16" height="6" rx="1" fill="currentColor" />
                                                </svg>
                                            </span>
                                        @endif
                                    </div>
                                    <div class="small text-muted">{{ $emp->upcoming_date->format('M d') }} — in {{ (int)$emp->upcoming_days }} days · {{ optional($emp->tanggal_lahir)->format('Y') }}</div>
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

        <!-- All birthdays modal -->
        <div class="modal fade" id="allBirthdaysModal" tabindex="-1" role="dialog" aria-labelledby="allBirthdaysModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="allBirthdaysModalLabel">All Employee Birthdays <small class="text-muted">({{ $allBirthdays->count() }})</small></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        @if($allBirthdays->isEmpty())
                            <div class="alert alert-info mb-0">No employee birthdays found.</div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Birth Date</th>
                                            <th>Next Birthday</th>
                                            <th>In (days)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($allBirthdays as $e)
                                            <tr>
                                                <td>{{ $e->nama }}</td>
                                                <td>{{ optional($e->tanggal_lahir)->format('Y-m-d') }}</td>
                                                <td>{{ $e->upcoming_date->format('M d') }}</td>
                                                <td>{{ $e->upcoming_days }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
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

@else
    <div class="container mt-4">
        <div class="alert alert-info">Welcome to E-Hrd Page</div>
    </div>
@endhasanyrole

@endsection {{-- content end --}}

@section('scripts')
    @hasanyrole('Hrd|Admin')
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
    @endhasanyrole
@endsection
