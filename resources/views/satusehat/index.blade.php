@extends('layouts.erm.app')

@section('navbar')
    @include('layouts.satusehat.navbar')
@endsection

@section('title', 'SatuSehat - Klinik Configs')

@section('content')
    <div class="container-fluid">
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">SatuSehat Dashboard</h4>
                        <p class="card-text">Dashboard awal untuk integrasi SatuSehat. Gunakan menu di sebelah kiri untuk navigasi.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
