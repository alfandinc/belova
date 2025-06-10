@extends('layouts.hrd.app')

@section('content')
<div class="container">
    <h2>Performance Evaluation Results</h2>
    
    <div class="card">
        <div class="card-body">
            @if($periods->isEmpty())
                <div class="alert alert-info">
                    No completed evaluation periods found. Once periods are marked as completed, their results will appear here.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Period Name</th>
                                <th>Date Range</th>
                                <th>Evaluations</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($periods as $period)
                                <tr>
                                    <td>{{ $period->name }}</td>
                                    <td>{{ $period->start_date->format('d M Y') }} - {{ $period->end_date->format('d M Y') }}</td>
                                    <td>
                                        @php
                                            $totalEvals = $period->evaluations->count();
                                            $completedEvals = $period->evaluations->where('status', 'completed')->count();
                                            $completionRate = $totalEvals > 0 ? round(($completedEvals / $totalEvals) * 100) : 0;
                                        @endphp
                                        {{ $completedEvals }} / {{ $totalEvals }} ({{ $completionRate }}% completed)
                                    </td>
                                    <td>
                                        <a href="{{ route('hrd.performance.results.period', $period) }}" class="btn btn-info btn-sm">
                                            <i class="fa fa-chart-bar"></i> View Results
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
@endsection