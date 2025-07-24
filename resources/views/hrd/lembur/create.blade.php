@extends('layouts.hrd.app')
@section('title', 'Ajukan Lembur')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection
@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Ajukan Lembur</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('hrd.lembur.store') }}">
                        @csrf
                        <div class="form-group">
                            <label for="tanggal">Tanggal</label>
                            <input type="date" class="form-control" name="tanggal" id="tanggal" required>
                        </div>
                        <div class="form-group">
                            <label for="jam_mulai">Jam Mulai</label>
                            <input type="time" class="form-control" name="jam_mulai" id="jam_mulai" required>
                        </div>
                        <div class="form-group">
                            <label for="jam_selesai">Jam Selesai</label>
                            <input type="time" class="form-control" name="jam_selesai" id="jam_selesai" required>
                        </div>
                        <div class="form-group">
                            <label for="alasan">Alasan</label>
                            <textarea class="form-control" name="alasan" id="alasan" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Ajukan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
