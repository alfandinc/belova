@extends('layouts.erm.app')
@section('title', 'Tambah Dokter')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-primary">
            <h4 class="card-title text-white">Tambah Dokter</h4>
        </div>
        <div class="card-body">
            <form action="{{ route('erm.dokters.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="user_id">Pilih User</label>
                    <select name="user_id" class="form-control" required>
                        <option value="">-- Pilih User --</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="sip">Nomor SIP</label>
                    <input type="text" name="sip" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="spesialisasi_id">Spesialisasi</label>
                    <select name="spesialisasi_id" class="form-control" required>
                        <option value="">-- Pilih Spesialisasi --</option>
                        @foreach($spesialisasis as $s)
                            <option value="{{ $s->id }}">{{ $s->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn btn-success">Simpan</button>
            </form>
        </div>
    </div>
</div>
@endsection
