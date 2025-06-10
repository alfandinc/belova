@extends('layouts.hrd.app')
@section('title', 'HRD | Divisi Saya')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
<div class="container">
    <div class="row">
        <!-- Division Info -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Informasi Divisi</h5>
                </div>
                <div class="card-body text-center">
                    <h3>{{ $division->name }}</h3>
                    <p class="text-muted">{{ $division->description ?? 'Tidak ada deskripsi' }}</p>
                    
                    <div class="mt-4">
                        <h5>Jumlah Anggota Tim</h5>
                        <h2 class="text-primary">{{ $division->employees->count() }}</h2>
                    </div>
                    
                    <div class="mt-3">
                        <a href="{{ route('hrd.division.team') }}" class="btn btn-primary">Lihat Tim Saya</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Division Stats -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Statistik Tim</h5>
                </div>
                <div class="card-body">
                    <!-- Status Stats -->
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h5>Karyawan Tetap</h5>
                                    <h3>{{ $division->employees->where('status', 'tetap')->count() }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h5>Karyawan Kontrak</h5>
                                    <h3>{{ $division->employees->where('status', 'kontrak')->count() }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <h5>Tidak Aktif</h5>
                                    <h3>{{ $division->employees->where('status', 'tidak aktif')->count() }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Contract expirations in next 30 days -->
                    <div class="card mt-4">
                        <div class="card-header bg-warning text-white">
                            <h5 class="mb-0">Kontrak Berakhir Dalam 30 Hari</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group">
                                @php
                                    $expiringSoon = $division->employees
                                        ->where('status', 'kontrak')
                                        ->filter(function($employee) {
                                            return $employee->kontrak_berakhir && 
                                                $employee->kontrak_berakhir->diffInDays(now()) <= 30;
                                        });
                                @endphp
                                
                                @forelse($expiringSoon as $employee)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        {{ $employee->nama }}
                                        <span class="badge badge-warning">Berakhir: {{ $employee->kontrak_berakhir->format('d-m-Y') }}</span>
                                    </li>
                                @empty
                                    <li class="list-group-item text-center text-muted">Tidak ada kontrak yang berakhir dalam 30 hari</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection