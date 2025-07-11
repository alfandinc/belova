@extends('layouts.hrd.app')

@section('content')
<!-- This is the EMPLOYEE results view -->
<!-- Variables available: $period, $employee, $categoryResults, $overallAverage -->
<div class="container">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Evaluation Results for {{ $employee->name ?? $employee->nama }}</h2>
            <p>Period: {{ $period->name }} ({{ $period->start_date->format('d M Y') }} - {{ $period->end_date->format('d M Y') }})</p>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('hrd.performance.results.period', $period) }}" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Back to Period Results
            </a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5>Overall Results</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3 class="mb-0">{{ number_format($overallAverage, 2) }} / 5.00</h3>
                            <p class="text-muted">Overall Average Score</p>
                            <div class="progress mt-3">
                                <div class="progress-bar bg-{{ $overallAverage >= 4 ? 'success' : ($overallAverage >= 3 ? 'info' : ($overallAverage >= 2 ? 'warning' : 'danger')) }}" 
                                     role="progressbar" 
                                     style="width: {{ ($overallAverage / 5) * 100 }}%" 
                                     aria-valuenow="{{ $overallAverage }}" 
                                     aria-valuemin="0" 
                                     aria-valuemax="5"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5>Category Averages</h5>
                            <ul class="list-group list-group-flush">
                                @foreach($categoryResults as $category)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    {{ $category['name'] }}
                                    <span class="badge badge-{{ $category['average'] >= 4 ? 'success' : ($category['average'] >= 3 ? 'info' : ($category['average'] >= 2 ? 'warning' : 'danger')) }} badge-pill">
                                        {{ number_format($category['average'], 2) }}
                                    </span>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @foreach($categoryResults as $category)
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ $category['name'] }}</h5>
                <span class="badge badge-{{ $category['average'] >= 4 ? 'success' : ($category['average'] >= 3 ? 'info' : ($category['average'] >= 2 ? 'warning' : 'danger')) }} badge-pill">
                    {{ number_format($category['average'], 2) }}
                </span>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Question</th>
                            <th width="100">Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($category['questions'] as $question)
                            <tr>
                                <td>
                                    {{ $question['question'] }}
                                    @if(!empty($question['comments']))
                                        <div class="mt-2">
                                            <strong>Comments:</strong>
                                            <ul class="pl-3 mb-0">
                                                @foreach($question['comments'] as $comment)
                                                    <li><small>{{ $comment }}</small></li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-{{ $question['average_score'] >= 4 ? 'success' : ($question['average_score'] >= 3 ? 'info' : ($question['average_score'] >= 2 ? 'warning' : 'danger')) }} badge-pill">
                                        {{ number_format($question['average_score'], 1) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</div>
@endsection
