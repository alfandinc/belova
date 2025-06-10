@extends('layouts.hrd.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Create New Question</h2>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('hrd.performance.questions.index') }}" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Back to Questions
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('hrd.performance.questions.store') }}">
                @csrf

                <div class="form-group row">
                    <label for="question_text" class="col-md-4 col-form-label text-md-right">Question Text</label>
                    <div class="col-md-6">
                        <textarea id="question_text" name="question_text" class="form-control @error('question_text') is-invalid @enderror" rows="3" required>{{ old('question_text') }}</textarea>
                        @error('question_text')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>

                <div class="form-group row">
                    <label for="category_id" class="col-md-4 col-form-label text-md-right">Category</label>
                    <div class="col-md-6">
                        <select id="category_id" name="category_id" class="form-control @error('category_id') is-invalid @enderror" required>
                            <option value="">Select Category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>

                <div class="form-group row">
                    <label for="evaluation_type" class="col-md-4 col-form-label text-md-right">Evaluation Type</label>
                    <div class="col-md-6">
                        <select id="evaluation_type" name="evaluation_type" class="form-control @error('evaluation_type') is-invalid @enderror" required>
                            <option value="">Select Type</option>
                            @foreach($evaluationTypes as $value => $label)
                                <option value="{{ $value }}" {{ old('evaluation_type') == $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('evaluation_type')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                        <small class="form-text text-muted">
                            This determines which evaluation form this question will appear on.
                        </small>
                    </div>
                </div>

                <div class="form-group row">
                    <div class="col-md-6 offset-md-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Active
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-group row mb-0">
                    <div class="col-md-6 offset-md-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> Create Question
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection