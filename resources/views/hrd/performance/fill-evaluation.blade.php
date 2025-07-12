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

    <form id="evaluationForm" method="POST" action="{{ route('hrd.performance.evaluations.submit', $evaluation) }}">
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
                                    <th width="20%">Response</th>
                                    <th width="20%">Comment (Optional)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($category['questions'] as $question)
                                    <tr>
                                        <td>{{ $question->question_text }}</td>
                                        <td>
                                            <div class="form-group mb-0">
                                                @if($question->question_type == 'score')
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
                                                @else
                                                    <textarea name="text_answers[{{ $question->id }}]" class="form-control @error('text_answers.'.$question->id) is-invalid @enderror" rows="3" required placeholder="Enter your answer"></textarea>
                                                    @error('text_answers.'.$question->id)
                                                        <span class="invalid-feedback" role="alert">
                                                            <strong>{{ $message }}</strong>
                                                        </span>
                                                    @enderror
                                                @endif
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
            <button type="button" id="submitEvaluation" class="btn btn-primary btn-lg">
                <i class="fa fa-paper-plane"></i> Submit Evaluation
            </button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Handle form submission with AJAX
        $('#submitEvaluation').click(function() {
            // Show confirmation SweetAlert
            Swal.fire({
                title: 'Submit Evaluation?',
                text: 'You cannot change it afterwards.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, submit it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.value) {
                    // Show loading state
                    Swal.fire({
                        title: 'Submitting...',
                        text: 'Please wait while your evaluation is being submitted.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Submit the form via AJAX
                    $.ajax({
                        url: $('#evaluationForm').attr('action'),
                        type: 'POST',
                        data: $('#evaluationForm').serialize(),
                        success: function(response) {
                            Swal.fire({
                                title: 'Success!',
                                text: 'Your evaluation has been submitted successfully.',
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then((result) => {
                                window.location.href = "{{ route('hrd.performance.my-evaluations') }}";
                            });
                        },
                        error: function(xhr) {
                            let errorMessage = 'An error occurred. Please try again.';
                            
                            if (xhr.responseJSON && xhr.responseJSON.errors) {
                                const errors = xhr.responseJSON.errors;
                                errorMessage = Object.values(errors).flat().join('<br>');
                            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }
                            
                            Swal.fire({
                                title: 'Error!',
                                html: errorMessage,
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    });
                }
            });
        });
    });
</script>
@endsection