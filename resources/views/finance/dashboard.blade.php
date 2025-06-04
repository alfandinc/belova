@extends('layouts.hrd.app')
@section('title', 'Dashboard | HRD Belova')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection  
@section('content')
<div class="container">
    <h2>Welcome to HRD Dashboard</h2>

    {{-- Navigation Bar --}}
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">HRD System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">

                    {{-- If user is Dokter (can access all menus) --}}
                    @if(auth()->user()->hasRole('dokter'))
                        <li class="nav-item"><a class="nav-link" href="#">Patient Management</a></li>
                        <li class="nav-item"><a class="nav-link" href="#">Medical Records</a></li>
                        <li class="nav-item"><a class="nav-link" href="#">Appointments</a></li>

                    {{-- If user is Perawat (can access only 2 menus) --}}
                    @elseif(auth()->user()->hasRole('perawat'))
                        <li class="nav-item"><a class="nav-link" href="#">Patient Management</a></li>
                        <li class="nav-item"><a class="nav-link" href="#">Medical Records</a></li>

                    {{-- If user is Pendaftaran (can access only 1 menu) --}}
                    @elseif(auth()->user()->hasRole('pendaftaran'))
                        <li class="nav-item"><a class="nav-link" href="#">Appointments</a></li>
                    @endif

                </ul>
            </div>
        </div>
    </nav>

    {{-- Dashboard Content --}}
    <div class="mt-4">
        <p>Hello, {{ auth()->user()->name }}! You are logged in as <strong>{{ auth()->user()->getRoleNames()->first() }}</strong>.</p>
    </div>

    {{-- Logout Button --}}
    <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-danger">Logout</button>
    </form>
</div>
@endsection
