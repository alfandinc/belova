@extends('layouts.hrd.app')

@section('title', 'Admin Dashboard')

@section('navbar')
    @include('layouts.admin.navbar')
@endsection

@section('content')
<div class="container">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h2 class="mb-0">Admin Dashboard</h2>
        <div>
            <a href="{{ route('admin.users.create') ?? url('/admin/users/create') }}" class="btn btn-primary">Add New User</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-primary h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">Users</h5>
                            <h3 class="card-text">{{ $usersCount ?? '--' }}</h3>
                        </div>
                        <div>
                            <i data-feather="users" width="48" height="48"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <a href="{{ route('admin.users.index') ?? url('/admin/users') }}" class="text-white">View all users &raquo;</a>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card text-white bg-success h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">Roles</h5>
                            <h3 class="card-text">{{ $rolesCount ?? 0 }}</h3>
                        </div>
                        <div>
                            <i data-feather="shield" width="48" height="48"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <a href="{{ route('admin.roles.index') ?? url('/admin/roles') }}" class="text-white">Manage roles &raquo;</a>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Activity (last 7 days)</h5>
                    <div id="admin-activity-chart"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Quick actions</h5>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('admin.users.create') ?? url('/admin/users/create') }}" class="btn btn-outline-primary">Create user</a>
                        <a href="{{ route('admin.roles.index') ?? url('/admin/roles') }}" class="btn btn-outline-success">Manage roles</a>
                        <a href="/admin/settings" class="btn btn-outline-secondary">Settings</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function(){
        try {
            // Fetch activity data from server
            fetch("{{ route('admin.activity.data') }}")
                .then(function(resp){ return resp.json(); })
                .then(function(json){
                    var labels = json.labels && json.labels.length ? json.labels : ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
                    var data = json.data || [0,0,0,0,0,0,0];

                    var options = {
                        chart: { type: 'area', height: 240, toolbar: { show: false } },
                        series: [{ name: 'Signups', data: data }],
                        xaxis: { categories: labels },
                        colors: ['#556ee6'],
                        dataLabels: { enabled: false }
                    };

                    var chartEl = document.querySelector('#admin-activity-chart');
                    if (chartEl && typeof ApexCharts !== 'undefined') {
                        var chart = new ApexCharts(chartEl, options);
                        chart.render();
                    }
                })
                .catch(function(err){
                    console.warn('Failed to load activity data', err);
                });
        } catch (e) {
            console.warn('Chart rendering error', e);
        }
    });
</script>
@endsection
