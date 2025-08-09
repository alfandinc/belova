@extends('layouts.erm.app')

@section('content')
<div class="container">
    <h1>Edit Master Faktur</h1>
    <form action="{{ route('erm.masterfaktur.update', $masterFaktur->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label>Obat</label>
            <select name="obat_id" class="form-control" required>
                <option value="">Pilih Obat</option>
                @foreach($obats as $obat)
                    <option value="{{ $obat->id }}" @if($masterFaktur->obat_id == $obat->id) selected @endif>{{ $obat->nama }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label>Pemasok</label>
            <select name="pemasok_id" class="form-control" required>
                <option value="">Pilih Pemasok</option>
                @foreach($pemasoks as $pemasok)
                    <option value="{{ $pemasok->id }}" @if($masterFaktur->pemasok_id == $pemasok->id) selected @endif>{{ $pemasok->nama }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label>Harga</label>
            <input type="number" step="0.01" name="harga" class="form-control" value="{{ $masterFaktur->harga }}" required>
        </div>
        <div class="mb-3">
            <label>Qty per Box</label>
            <input type="number" name="qty_per_box" class="form-control" value="{{ $masterFaktur->qty_per_box }}" required>
        </div>
        <div class="mb-3">
            <label>Diskon</label>
            <input type="number" step="0.01" name="diskon" class="form-control" value="{{ $masterFaktur->diskon }}" required>
        </div>
        <div class="mb-3">
            <label>Diskon Type</label>
            <select name="diskon_type" class="form-control" required>
                <option value="nominal" @if($masterFaktur->diskon_type == 'nominal') selected @endif>Nominal</option>
                <option value="percent" @if($masterFaktur->diskon_type == 'percent') selected @endif>Percent</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Update</button>
        <a href="{{ route('erm.masterfaktur.index') }}" class="btn btn-secondary">Kembali</a>
    </form>
</div>
@endsection
