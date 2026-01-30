@extends('layouts.erm.app')

@section('title', 'Events Dashboard')

@section('navbar')
    @include('layouts.erm.navbar-ngaji')
@endsection

@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h3>Events Dashboard</h3>
            <p class="text-muted">Daftar event yang tersedia. Klik untuk membuka masing-masing event.</p>
        </div>
    </div>

    <div class="row mt-3">
        @foreach($events as $event)
        <div class="col-md-4 mb-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">{{ $event['title'] }}</h5>
                    <p class="card-text">{{ $event['description'] }}</p>
                    <a href="{{ $event['url'] }}" class="btn btn-primary">Open</a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
