@extends('layouts.erm.app')

@section('title', 'CEO Dashboard')

@section('navbar')
    @include('layouts.ceodashboard.navbar')
@endsection

@section('content')
    <div class="container-fluid mt-3">
        <div class="row">
            <div class="col-lg-6 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h4 class="card-title">CEO Dashboard</h4>
                        <p class="card-text">Ringkasan eksekutif tersedia di modul ini. Konten dashboard dapat dikembangkan dari halaman ini.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-3">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <h4 class="card-title">Daily Task Report</h4>
                            <p class="card-text">Lihat semua task harian yang sudah dilaporkan manager dalam tampilan DataTable.</p>
                        </div>
                        <div>
                            <a href="{{ route('ceo-dashboard.daily-tasks.index') }}" class="btn btn-primary">Open Daily Task Report</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
