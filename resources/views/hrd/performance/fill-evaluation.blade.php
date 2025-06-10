@extends('layouts.hrd.app')
@section('title', 'HRD | Tambah Karyawan')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Performance Evaluation</h2>
            <h5>
                Evaluating: {{ $evaluation->evaluatee->nama }} 
                <span class="text-muted">({{ $evaluation->evaluatee->position->name ?? 'N/A' }})</span>
            </h5>
            <p>Period: {{ $evaluation->period->name }}</p>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('hrd.performance.my-evaluations') }}" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Back to Evaluations
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('hrd.performance.evaluations.submit', $evaluation) }}">
        @csrf
        
        @foreach($categories as $category)
            <div class="card mb-4">
                <div class="card-header">
                    <h5>{{ $category['name'] }}</h5>
                    @if($category['description'])
                        <p class="text-muted mb-0">{{ $category['description'] }}</p>
                    @endif
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th width="60%">Question</th>
                                    <th width="20%">Score (1-5)</th>
                                    <th width="20%">Comment (Optional)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($category['questions'] as $question)
                                    <tr>
                                        <td>{{ $question->question_text }}</td>
                                        <td>
                                            <div class="form-group mb-0">
                                                <select name="scores[{{ $question->id }}]" class="form-control @error('scores.'.$question->id) is-invalid @enderror" required>
                                                    <option value="">Select score</option>
                                                    <option value="1">1 - Poor</option>
                                                    <option value="2">2 - Below Average</option>
                                                    <option value="3">3 - Average</option>
                                                    <option value="4">4 - Good</option>
                                                    <option value="5">5 - Excellent</option>
                                                </select>
                                                @error('scores.'.$question->id)
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </td>
                                        <td>
                                            <div class="form-group mb-0">
                                                <textarea name="comments[{{ $question->id }}]" class="form-control" rows="2"></textarea>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
        
        <div class="text-center mb-4">
            <button type="submit" class="btn btn-primary btn-lg" onclick="return confirm('Are you sure you want to submit this evaluation? You cannot change it afterwards.')">
                <i class="fa fa-paper-plane"></i> Submit Evaluation
            </button>
        </div>
    </form>
</div>
@endsection