<!-- filepath: c:\wamp64\www\belova\resources\views\erm\obat\create.blade.php -->
@extends('layouts.erm.app')
@section('title', isset($obat->id) ? 'ERM | Edit Obat' : 'ERM | Tambah Obat')

@section('navbar')
    @include('layouts.erm.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center mb-0 mt-2">
        <h3 class="mb-0 mr-2">{{ isset($obat->id) ? 'Edit Obat' : 'Tambah Obat Baru' }}</h3>
    </div>
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                            <li class="breadcrumb-item">Farmasi</li>
                            <li class="breadcrumb-item active">{{ isset($obat->id) ? 'Edit Obat' : 'Tambah Obat' }}</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->                                                              
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->  
    <!-- end page title end breadcrumb -->

    <div class="card">
        <div class="card-body">
            <form action="{{ route('erm.obat.store') }}" method="POST">
                @csrf
                @if(isset($obat->id))
                    <input type="hidden" name="id" value="{{ $obat->id }}">
                @endif

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="nama">Nama Obat</label>
                        <input type="text" name="nama" id="nama" class="form-control" value="{{ $obat->nama ?? old('nama') }}" required>
                    </div>

                    <div class="form-group col-md-3">
                        <label for="dosis">Dosis</label>
                        <input type="text" name="dosis" id="dosis" class="form-control" value="{{ $obat->dosis ?? old('dosis') }}" required>
                    </div>

                    <div class="form-group col-md-3">
                        <label for="satuan">Satuan</label>
                        <select id="satuan" name="satuan" class="form-control select2" required>
                            <option value="" disabled selected>Pilih</option>
                            @foreach (['mg','g','ml','tablet','kapsul','sendok','tetes','vial','ampul','patch','suppositoria','puff'] as $satuan)
                                <option value="{{ $satuan }}" {{ ($obat->satuan ?? old('satuan')) == $satuan ? 'selected' : '' }}>{{ ucfirst($satuan) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-md-4">
                        <label for="kategori">Kategori</label>
                        <select id="kategori" name="kategori" class="form-control select2" required>
                            <option value="" disabled selected>Pilih Kategori</option>
                            @foreach ($kategoris as $kategori)
                                <option value="{{ $kategori }}" {{ ($obat->kategori ?? old('kategori')) == $kategori ? 'selected' : '' }}>{{ $kategori }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-md-4">
                        <label for="metode_bayar_id">Metode Bayar</label>
                        <select id="metode_bayar_id" name="metode_bayar_id" class="form-control select2">
                            <option value="">Pilih Metode Bayar</option>
                            @foreach ($metodeBayars as $metodeBayar)
                                <option value="{{ $metodeBayar->id }}" {{ ($obat->metode_bayar_id ?? old('metode_bayar_id')) == $metodeBayar->id ? 'selected' : '' }}>{{ $metodeBayar->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-md-4 d-flex align-items-end">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" name="status_aktif" class="custom-control-input" id="status_aktif" 
                                {{ isset($obat) ? ($obat->status_aktif ? 'checked' : '') : 'checked' }}>
                            <label class="custom-control-label" for="status_aktif">Status Aktif</label>
                        </div>
                    </div>

                    <div class="form-group col-md-4">
                        <label for="harga_net">Harga Net (Rp)</label>
                        <input type="number" name="harga_net" id="harga_net" class="form-control" step="0.01" value="{{ $obat->harga_net ?? old('harga_net') }}" required>
                    </div>

                    <div class="form-group col-md-4">
                        <label for="harga_fornas">Harga Fornas (Rp)</label>
                        <input type="number" name="harga_fornas" id="harga_fornas" class="form-control" step="0.01" value="{{ $obat->harga_fornas ?? old('harga_fornas') }}">
                    </div>

                    <div class="form-group col-md-4">
                        <label for="harga_nonfornas">Harga Non-Fornas (Rp)</label>
                        <input type="number" name="harga_nonfornas" id="harga_nonfornas" class="form-control" step="0.01" value="{{ $obat->harga_nonfornas ?? old('harga_nonfornas') }}">
                    </div>

                    <div class="form-group col-md-4">
                        <label for="stok">Stok</label>
                        <input type="number" name="stok" id="stok" class="form-control" value="{{ $obat->stok ?? old('stok', 0) }}" required>
                    </div>

                    <div class="form-group col-12">
                        <label for="zataktif_id">Zat Aktif <small>(bisa lebih dari satu)</small></label>
                        <select name="zataktif_id[]" id="zataktif_id" class="form-control select2" multiple required>
                            @foreach ($zatAktif as $zat)
                                <option value="{{ $zat->id }}" {{ isset($obat) && $obat->zatAktifs->contains($zat->id) ? 'selected' : '' }}>{{ $zat->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-success">{{ isset($obat->id) ? 'Update' : 'Simpan' }}</button>
                    <a href="{{ route('erm.obat.index') }}" class="btn btn-secondary">Kembali</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function () {
        $('.select2').select2({
            width: '100%'
        });
    });
</script>
@endsection