@extends('layouts.erm.app')

@section('content')
<div class="container">
    <h1>Tambah Master Faktur</h1>
    <form action="{{ route('erm.masterfaktur.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label>Obat</label>
            <select name="obat_id" class="form-control" required>
                <option value="">Pilih Obat</option>
                @foreach($obats as $obat)
                    <option value="{{ $obat->id }}">{{ $obat->nama }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label>Pemasok</label>
            <select name="pemasok_id" class="form-control" required>
                <option value="">Pilih Pemasok</option>
                @foreach($pemasoks as $pemasok)
                    <option value="{{ $pemasok->id }}">{{ $pemasok->nama }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label>Harga</label>
            <input type="number" step="0.01" name="harga" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Qty per Box</label>
            <input type="number" name="qty_per_box" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Diskon</label>
            <input type="number" step="0.01" name="diskon" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Diskon Type</label>
            <select name="diskon_type" class="form-control" required>
                <option value="nominal">Nominal</option>
                <option value="percent">Percent</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="{{ route('erm.masterfaktur.index') }}" class="btn btn-secondary">Kembali</a>
    </form>
</div>
@endsection
