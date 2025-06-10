@extends('layouts.hrd.app')
@section('title', 'HRD | Tambah Karyawan')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Create Performance Evaluation Period</h2>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('hrd.performance.periods.index') }}" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Back to Periods
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('hrd.performance.periods.store') }}">
                @csrf

                <div class="form-group row">
                    <label for="name" class="col-md-4 col-form-label text-md-right">Period Name</label>
                    <div class="col-md-6">
                        <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>

                <div class="form-group row">
                    <label for="start_date" class="col-md-4 col-form-label text-md-right">Start Date</label>
                    <div class="col-md-6">
                        <input type="date" id="start_date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date') }}" required>
                        @error('start_date')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>

                <div class="form-group row">
                    <label for="end_date" class="col-md-4 col-form-label text-md-right">End Date</label>
                    <div class="col-md-6">
                        <input type="date" id="end_date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date') }}" required>
                        @error('end_date')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>

                <div class="form-group row">
                    <label for="status" class="col-md-4 col-form-label text-md-right">Status</label>
                    <div class="col-md-6">
                        <select id="status" name="status" class="form-control @error('status') is-invalid @enderror" required>
                            <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        </select>
                        @error('status')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>

                <div class="form-group row mb-0">
                    <div class="col-md-6 offset-md-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> Create Period
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection