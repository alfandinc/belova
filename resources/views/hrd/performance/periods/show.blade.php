@extends('layouts.hrd.app')
@section('title', 'HRD | Tambah Karyawan')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Period: {{ $period->name }}</h2>
            <p>
                <span class="badge badge-{{ $period->status == 'pending' ? 'warning' : ($period->status == 'active' ? 'primary' : 'success') }}">
                    {{ ucfirst($period->status) }}
                </span>
                <span class="ml-3">{{ $period->start_date->format('d M Y') }} - {{ $period->end_date->format('d M Y') }}</span>
            </p>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('hrd.performance.periods.index') }}" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Back to Periods
            </a>
            
            @if($period->status == 'pending')
                <form action="{{ route('hrd.performance.periods.initiate', $period) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success" onclick="return confirm('Are you sure you want to initiate this evaluation period?')">
                        <i class="fa fa-play"></i> Initiate Evaluations
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Progress Overview</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Evaluations:</span>
                        <strong>{{ count($evaluations) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Completed:</span>
                        <strong>{{ $completedCount }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Pending:</span>
                        <strong>{{ $pendingCount }}</strong>
                    </div>
                    <div class="progress mt-3">
                        @php
                            $progressPercent = count($evaluations) > 0 ? round(($completedCount / count($evaluations)) * 100) : 0;
                        @endphp
                        <div class="progress-bar" role="progressbar" style="width: {{ $progressPercent }}%" aria-valuenow="{{ $progressPercent }}" aria-valuemin="0" aria-valuemax="100">
                            {{ $progressPercent }}%
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Actions</h5>
                </div>
                <div class="card-body">
                    @if($period->status == 'completed')
                        <a href="{{ route('hrd.performance.results.period', $period) }}" class="btn btn-info btn-block mb-2">
                            <i class="fa fa-chart-bar"></i> View Results
                        </a>
                    @endif
                    
                    @if($period->status == 'active')
                        <button class="btn btn-warning btn-block mb-2" onclick="alert('Send reminder emails to employees with pending evaluations')">
                            <i class="fa fa-envelope"></i> Send Reminders
                        </button>
                        
                        <form action="{{ route('hrd.performance.periods.update', $period) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="name" value="{{ $period->name }}">
                            <input type="hidden" name="start_date" value="{{ $period->start_date->format('Y-m-d') }}">
                            <input type="hidden" name="end_date" value="{{ $period->end_date->format('Y-m-d') }}">
                            <input type="hidden" name="status" value="completed">
                            <button type="submit" class="btn btn-success btn-block" onclick="return confirm('Are you sure you want to mark this period as completed?')">
                                <i class="fa fa-check-circle"></i> Mark as Completed
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5>Evaluation Assignments</h5>
        </div>
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Evaluator</th>
                        <th>Evaluatee</th>
                        <th>Type</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($evaluations as $eval)
                    <tr>
                        <td>{{ $eval->evaluator->nama }}</td>
                        <td>{{ $eval->evaluatee->nama }}</td>
                        <td>
                            @php
                                $evaluatorDivision = $eval->evaluator->division->name ?? 'Unknown';
                                $evaluateeDivision = $eval->evaluatee->division->name ?? 'Unknown';
                                $isEvaluatorManager = $eval->evaluator->isManager();
                                $isEvaluateeManager = $eval->evaluatee->isManager();
                                $isEvaluatorHRD = strpos(strtolower($evaluatorDivision), 'hrd') !== false;
                                $isEvaluateeHRD = strpos(strtolower($evaluateeDivision), 'hrd') !== false;
                                
                                if ($isEvaluatorHRD && $isEvaluateeManager) {
                                    echo "HRD to Manager";
                                } elseif ($isEvaluatorManager && !$isEvaluateeManager && !$isEvaluateeHRD) {
                                    echo "Manager to Employee";
                                } elseif (!$isEvaluatorManager && !$isEvaluatorHRD && $isEvaluateeManager) {
                                    echo "Employee to Manager";
                                } elseif ($isEvaluatorManager && $isEvaluateeHRD) {
                                    echo "Manager to HRD";
                                } else {
                                    echo "Other";
                                }
                            @endphp
                        </td>
                        <td>
                            <span class="badge badge-{{ $eval->status == 'pending' ? 'warning' : 'success' }}">
                                {{ ucfirst($eval->status) }}
                            </span>
                            @if($eval->completed_at)
                                <small class="d-block text-muted">{{ $eval->completed_at->format('d M Y') }}</small>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection