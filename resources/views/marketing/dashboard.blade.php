@extends('layouts.marketing.app')
@section('title', 'Marketing Dashboard | Belova')
@section('navbar')
    @include('layouts.marketing.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <h4 class="page-title">Marketing Dashboard</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item active">Overview of clinic performance and analytics</li>
                        </ol>
                    </div>
                    <div class="col-auto align-self-center">
                        <div class="dropdown">
                            <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i data-feather="calendar"></i> Today
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="#">Today</a>
                                <a class="dropdown-item" href="#">This Week</a>
                                <a class="dropdown-item" href="#">This Month</a>
                                <a class="dropdown-item" href="#">This Year</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Key Performance Indicators -->
    <div class="row">
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h4 class="text-primary mb-1">{{ number_format($stats['patients']['total']) }}</h4>
                            <p class="text-muted font-14 mb-0">Total Patients</p>
                            <small class="text-success">
                                <i data-feather="trending-up" class="align-self-center icon-xs me-1"></i>
                                +{{ $stats['patients']['new_this_month'] }} this month
                            </small>
                        </div>
                        <div class="col-4 align-self-center">
                            <div class="icon-lg bg-primary-lighten rounded-circle d-flex align-items-center justify-content-center">
                                <i data-feather="users" class="icon-sm text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h4 class="text-success mb-1">{{ 'Rp ' . number_format($stats['revenue']['this_month'], 0, ',', '.') }}</h4>
                            <p class="text-muted font-14 mb-0">Monthly Revenue</p>
                            <small class="text-success">
                                <i data-feather="dollar-sign" class="align-self-center icon-xs me-1"></i>
                                Rp {{ number_format($stats['revenue']['today'], 0, ',', '.') }} today
                            </small>
                        </div>
                        <div class="col-4 align-self-center">
                            <div class="icon-lg bg-success-lighten rounded-circle d-flex align-items-center justify-content-center">
                                <i data-feather="dollar-sign" class="icon-sm text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h4 class="text-warning mb-1">{{ number_format($stats['visits']['this_month']) }}</h4>
                            <p class="text-muted font-14 mb-0">Monthly Visits</p>
                            <small class="text-warning">
                                <i data-feather="calendar" class="align-self-center icon-xs me-1"></i>
                                {{ $stats['visits']['today'] }} today
                            </small>
                        </div>
                        <div class="col-4 align-self-center">
                            <div class="icon-lg bg-warning-lighten rounded-circle d-flex align-items-center justify-content-center">
                                <i data-feather="activity" class="icon-sm text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h4 class="text-info mb-1">{{ 'Rp ' . number_format($stats['revenue']['average_per_visit'], 0, ',', '.') }}</h4>
                            <p class="text-muted font-14 mb-0">Avg Revenue/Visit</p>
                            <small class="text-info">
                                <i data-feather="trending-up" class="align-self-center icon-xs me-1"></i>
                                {{ $stats['treatments']['most_popular'] }}
                            </small>
                        </div>
                        <div class="col-4 align-self-center">
                            <div class="icon-lg bg-info-lighten rounded-circle d-flex align-items-center justify-content-center">
                                <i data-feather="bar-chart-2" class="icon-sm text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions and Navigation -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Quick Analytics Access</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="/marketing/revenue" class="text-decoration-none">
                                <div class="analytics-card bg-gradient-primary text-white p-4 rounded">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">Revenue Analytics</h6>
                                            <p class="mb-0 opacity-75">Track revenue trends, payment methods, and doctor performance</p>
                                        </div>
                                        <div class="ms-3">
                                            <i data-feather="dollar-sign" class="icon-lg"></i>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="/marketing/patients" class="text-decoration-none">
                                <div class="analytics-card bg-gradient-success text-white p-4 rounded">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">Patient Analytics</h6>
                                            <p class="mb-0 opacity-75">Demographics, loyalty analysis, and geographic distribution</p>
                                        </div>
                                        <div class="ms-3">
                                            <i data-feather="users" class="icon-lg"></i>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="/marketing/services" class="text-decoration-none">
                                <div class="analytics-card bg-gradient-warning text-white p-4 rounded">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">Service Analytics</h6>
                                            <p class="mb-0 opacity-75">Treatment popularity, efficiency, and satisfaction trends</p>
                                        </div>
                                        <div class="ms-3">
                                            <i data-feather="activity" class="icon-lg"></i>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="/marketing/products" class="text-decoration-none">
                                <div class="analytics-card bg-gradient-info text-white p-4 rounded">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">Product Analytics</h6>
                                            <p class="mb-0 opacity-75">Best sellers, inventory turnover, and profitability</p>
                                        </div>
                                        <div class="ms-3">
                                            <i data-feather="package" class="icon-lg"></i>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity Summary -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Performance Overview</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3 col-6">
                            <div class="mb-4">
                                <h4 class="text-primary">{{ number_format($stats['patients']['active_this_year']) }}</h4>
                                <p class="text-muted mb-0">Active Patients This Year</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="mb-4">
                                <h4 class="text-success">{{ 'Rp ' . number_format($stats['revenue']['this_year'], 0, ',', '.') }}</h4>
                                <p class="text-muted mb-0">Total Revenue This Year</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="mb-4">
                                <h4 class="text-warning">{{ number_format($stats['visits']['this_year']) }}</h4>
                                <p class="text-muted mb-0">Total Visits This Year</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="mb-4">
                                <h4 class="text-info">{{ number_format($stats['treatments']['total_performed']) }}</h4>
                                <p class="text-muted mb-0">Treatments Performed</p>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="text-center">
                        <h6 class="mb-3">Marketing Tools</h6>
                        <div class="d-flex justify-content-center flex-wrap gap-2">
                            <a href="/marketing/followup" class="btn btn-outline-primary btn-sm">
                                <i data-feather="check-square" class="icon-xs me-1"></i> Follow Up
                            </a>
                            <a href="/marketing/content-plan" class="btn btn-outline-success btn-sm">
                                <i data-feather="calendar" class="icon-xs me-1"></i> Content Plan
                            </a>
                            <a href="/marketing/catatan-keluhan" class="btn btn-outline-warning btn-sm">
                                <i data-feather="alert-circle" class="icon-xs me-1"></i> Customer Complaints
                            </a>
                            <a href="/marketing/pasien-data" class="btn btn-outline-info btn-sm">
                                <i data-feather="database" class="icon-xs me-1"></i> Patient Data
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Clinic Status</h5>
                </div>
                <div class="card-body">
                    @if($clinics->count() > 0)
                        @foreach($clinics as $clinic)
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">{{ $clinic->nama }}</h6>
                                <p class="text-muted mb-0 small">{{ $clinic->alamat ?? 'No address' }}</p>
                            </div>
                            <div>
                                <span class="badge bg-success">Active</span>
                            </div>
                        </div>
                        @if(!$loop->last)<hr class="my-2">@endif
                        @endforeach
                    @else
                        <div class="text-center text-muted">
                            <i data-feather="building" class="icon-lg mb-3"></i>
                            <p>No clinics found</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.analytics-card {
    transition: transform 0.2s ease-in-out;
    border: none;
}

.analytics-card:hover {
    transform: translateY(-2px);
}

.bg-gradient-primary {
    background: linear-gradient(45deg, #4e73df, #6c5ce7);
}

.bg-gradient-success {
    background: linear-gradient(45deg, #1cc88a, #00b894);
}

.bg-gradient-warning {
    background: linear-gradient(45deg, #f6c23e, #fdcb6e);
}

.bg-gradient-info {
    background: linear-gradient(45deg, #36b9cc, #74b9ff);
}

.icon-lg {
    width: 3rem;
    height: 3rem;
}

.icon-sm {
    width: 1.5rem;
    height: 1.5rem;
}

.icon-xs {
    width: 1rem;
    height: 1rem;
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize feather icons
    feather.replace();
    
    // Add any dashboard-specific JavaScript here
    console.log('Marketing Dashboard loaded successfully');
});
</script>
@endpush
