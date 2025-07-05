@extends('layouts.erm.app')

@section('title', 'Error')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title text-danger"><i class="fas fa-exclamation-triangle mr-2"></i>Terjadi Kesalahan</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-danger">
                        {{ $message }}
                    </div>
                    
                    @if(app()->environment('local') && isset($exception))
                    <div class="mt-4">
                        <h5>Detail Error (hanya terlihat di mode development):</h5>
                        <pre class="bg-light p-3">{{ $exception }}</pre>
                    </div>
                    @endif
                    
                    <div class="mt-4">
                        <a href="{{ url()->previous() }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i> Kembali
                        </a>
                        <a href="{{ route('erm.dashboard') }}" class="btn btn-primary">
                            <i class="fas fa-home mr-1"></i> Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
