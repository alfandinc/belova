@extends('layouts.erm.app')
@section('title', 'Obat')
@section('content')
<div class="container">
    <h1>Tambah Obat</h1>

    <form action="{{ route('erm.obat.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label for="nama" class="form-label">Nama Obat</label>
            <input type="text" name="nama" id="nama" class="form-control" required>
        </div>
         <div class="mb-3">
            <label for="dosis" class="form-label">Dosis</label>
            <input type="number" name="dosis" id="stok" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="satuan" class="form-label">Satuan</label>
            <select id="satuan" class="form-control select2" name="satuan">
                <option value="" disabled selected>Pilih Satuan</option>
                <option value="mg">mg</option>
                <option value="g">gram</option>
                <option value="ml">ml</option>
                <option value="tablet">tablet</option>
                <option value="kapsul">kapsul</option>
                <option value="sendok">sendok</option>
                <option value="tetes">tetes</option>
                <option value="vial">vial</option>
                <option value="ampul">ampul</option>
                <option value="patch">patch</option>
                <option value="suppositoria">suppositoria</option>
                <option value="puff">puff</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="harga_umum" class="form-label">Harga Umum</label>
            <input type="number" name="harga_umum" id="harga_umum" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="harga_inhealth" class="form-label">Harga In Health</label>
            <input type="number" name="harga_inhealth" id="harga_inhealth" class="form-control" required>
        </div>
        
        <div class="mb-3">
            <label for="stok" class="form-label">Stok Obat</label>
            <input type="number" name="stok" id="stok" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="zataktif_id" class="form-label">Zat Aktif (Pilih lebih dari satu jika perlu)</label>
            <select name="zataktif_id[]" id="zataktif_id" class="form-control select2" multiple required>
                @foreach ($zatAktif as $zat)
                    <option value="{{ $zat->id }}">{{ $zat->nama }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label for="supplier_id" class="form-label">Supllier Obat</label>
            <select name="supplier_id" id="supllier_id" class="form-control select2" required>
                @foreach ($supplier as $supplier)
                    <option value="{{ $supplier->id }}">{{ $supplier->nama }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-success">Simpan</button>
        <a href="{{ route('erm.obat.index') }}" class="btn btn-secondary">Kembali</a>
    </form>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function () {
        $('.select2').select2();
    });
</script>
@endsection
