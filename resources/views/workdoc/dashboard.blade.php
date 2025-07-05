@extends('layouts.workdoc.app')
@section('title', 'Dashboard | Workdoc Belova')
@section('navbar')
    @include('layouts.workdoc.navbar')
@endsection  
@section('content')
<div class="container">
    <h2>Welcome to Workdoc Dashboard</h2>


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
