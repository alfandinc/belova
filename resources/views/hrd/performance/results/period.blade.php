@extends('layouts.hrd.app')

@section('content')
<!-- This is the PERIOD results view -->
<!-- Variables available: $period, $averageScores -->
<div class="container">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Evaluation Results</h2>
            <p>Period: {{ $period->name }} ({{ $period->start_date->format('d M Y') }} - {{ $period->end_date->format('d M Y') }})</p>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('hrd.performance.results.index') }}" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Back to Results
            </a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5>Employee Performance Results</h5>
        </div>
        <div class="card-body">
            @if(count($averageScores) === 0)
                <div class="alert alert-info">
                    No completed evaluations found for this period.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Employee Name</th>
                                <th>Position</th>
                                <th>Division</th>
                                <th>Overall Score</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($averageScores as $score)
                                <tr>
                                    <td>{{ $score['employee']->name ?? $score['employee']->nama }}</td>
                                    <td>{{ $score['employee']->position->name ?? 'N/A' }}</td>
                                    <td>{{ $score['employee']->division->name ?? 'N/A' }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-{{ $score['overallAverage'] >= 4 ? 'success' : ($score['overallAverage'] >= 3 ? 'info' : ($score['overallAverage'] >= 2 ? 'warning' : 'danger')) }} badge-pill">
                                            {{ number_format($score['overallAverage'], 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('hrd.performance.results.employee', ['period' => $period, 'employee' => $score['employee']]) }}" class="btn btn-info btn-sm">
                                            <i class="fa fa-eye"></i> View Details
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