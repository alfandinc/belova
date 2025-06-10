@extends('layouts.hrd.app')
@section('title', 'HRD | Evaluasi Kinerja')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection


@section('content')
<div class="container">
    <h2>My Performance Evaluations</h2>
    
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    
    <ul class="nav nav-tabs mb-4" id="evaluationTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link active" id="pending-tab" data-toggle="tab" href="#pending" role="tab">
                Pending Evaluations <span class="badge badge-warning">{{ count($pendingEvaluations) }}</span>
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="completed-tab" data-toggle="tab" href="#completed" role="tab">
                Completed Evaluations <span class="badge badge-success">{{ count($completedEvaluations) }}</span>
            </a>
        </li>
    </ul>
    
    <div class="tab-content" id="evaluationTabContent">
        <div class="tab-pane fade show active" id="pending" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    @if($pendingEvaluations->isEmpty())
                        <p class="text-center">No pending evaluations.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Evaluation Period</th>
                                        <th>Evaluatee</th>
                                        <th>Position</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pendingEvaluations as $evaluation)
                                        <tr>
                                            <td>{{ $evaluation->period->name }}</td>
                                            <td>{{ $evaluation->evaluatee->nama }}</td>
                                            <td>
                                                {{ $evaluation->evaluatee->position->name ?? 'N/A' }}
                                                <span class="text-muted d-block small">
                                                    {{ $evaluation->evaluatee->division instanceof \App\Models\HRD\Division ? $evaluation->evaluatee->division->name : 'N/A' }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ route('hrd.performance.evaluations.fill', $evaluation) }}" class="btn btn-primary btn-sm">
                                                    <i class="fa fa-edit"></i> Fill Evaluation
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="tab-pane fade" id="completed" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    @if($completedEvaluations->isEmpty())
                        <p class="text-center">No completed evaluations.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Evaluation Period</th>
                                        <th>Evaluatee</th>
                                        <th>Position</th>
                                        <th>Completed On</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($completedEvaluations as $evaluation)
                                        <tr>
                                            <td>{{ $evaluation->period->name }}</td>
                                            <td>{{ $evaluation->evaluatee->nama }}</td>
                                            <td>
                                                {{ $evaluation->evaluatee->position->name ?? 'N/A' }}
                                                <span class="text-muted d-block small">
                                                    {{ $evaluation->evaluatee->division instanceof \App\Models\HRD\Division ? $evaluation->evaluatee->division->name : 'N/A' }}
                                                </span>
                                            </td>
                                            <td>{{ $evaluation->completed_at ? $evaluation->completed_at->format('d M Y') : 'N/A' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection