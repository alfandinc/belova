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
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> <strong>Catatan:</strong> Hanya <span class="text-danger">Nama Obat</span> dan <span class="text-danger">Harga Non-Fornas</span> yang wajib diisi. Field lainnya bersifat opsional.
            </div>
            <form action="{{ route('erm.obat.store') }}" method="POST">
                @csrf
                @if(isset($obat->id))
                    <input type="hidden" name="id" value="{{ $obat->id }}">
                @endif

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="nama">Nama Obat <span class="text-danger">*</span></label>
                        <input type="text" name="nama" id="nama" class="form-control" value="{{ $obat->nama ?? old('nama') }}" required>
                    </div>
                    
                    <div class="form-group col-md-6">
                        <label for="kode_obat">Kode Obat</label>
                        <input type="text" name="kode_obat" id="kode_obat" class="form-control" value="{{ $obat->kode_obat ?? old('kode_obat') }}">
                    </div>

                    <div class="form-group col-md-3">
                        <label for="dosis">Dosis</label>
                        <input type="text" name="dosis" id="dosis" class="form-control" value="{{ $obat->dosis ?? old('dosis') }}">
                    </div>

                    <div class="form-group col-md-3">
                        <label for="satuan">Satuan</label>
                        <select id="satuan" name="satuan" class="form-control select2">
                            <option value="">Pilih Satuan</option>
                            @foreach (['mg','g','ml','tablet','kapsul','sendok','tetes','vial','ampul','patch','suppositoria','puff'] as $satuan)
                                <option value="{{ $satuan }}" {{ ($obat->satuan ?? old('satuan')) == $satuan ? 'selected' : '' }}>{{ ucfirst($satuan) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-md-4">
                        <label for="kategori">Kategori</label>
                        <select id="kategori" name="kategori" class="form-control select2">
                            <option value="">Pilih Kategori</option>
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
                            <!-- Hidden field to ensure the parameter is always submitted -->
                            <input type="hidden" name="status_aktif_submitted" value="1">
                            <input type="checkbox" name="status_aktif" class="custom-control-input" id="status_aktif" value="1"
                                {{ isset($obat) ? ($obat->status_aktif ? 'checked' : '') : 'checked' }}>
                            <label class="custom-control-label" for="status_aktif">Status Aktif</label>
                            <small class="form-text text-muted">Obat dengan status aktif akan muncul pada pencarian di E-Resep. Obat yang tidak aktif tidak akan muncul pada pencarian.</small>
                        </div>
                    </div>

                    <div class="form-group col-md-4">
                        <label for="harga_net">Harga Net (Rp)</label>
                        <input type="number" name="harga_net" id="harga_net" class="form-control" step="0.01" value="{{ $obat->harga_net ?? old('harga_net') }}">
                    </div>

                    <div class="form-group col-md-4">
                        <label for="harga_fornas">Harga Fornas (Rp)</label>
                        <input type="number" name="harga_fornas" id="harga_fornas" class="form-control" step="0.01" value="{{ $obat->harga_fornas ?? old('harga_fornas') }}">
                    </div>

                    <div class="form-group col-md-4">
                        <label for="harga_nonfornas">Harga Non-Fornas (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="harga_nonfornas" id="harga_nonfornas" class="form-control" step="0.01" value="{{ $obat->harga_nonfornas ?? old('harga_nonfornas') }}" required>
                    </div>

                    <div class="form-group col-md-4">
                        <label for="stok">Stok</label>
                        <input type="number" name="stok" id="stok" class="form-control" value="{{ $obat->stok ?? old('stok', 0) }}">
                    </div>

                    <div class="form-group col-12">
                        <label for="zataktif_id">Zat Aktif <small>(bisa lebih dari satu)</small></label>
                        <select name="zataktif_id[]" id="zataktif_id" class="form-control select2" multiple>
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
        
        // Debug status checkbox
        console.log('Initial status_aktif checked:', $('#status_aktif').is(':checked'));
        
        // Monitor changes to the status checkbox
        $('#status_aktif').on('change', function() {
            console.log('Status checkbox changed to:', $(this).is(':checked'));
        });
        
        // Log form data on submit
        $('form').on('submit', function() {
            console.log('Form submitted with status_aktif checked:', $('#status_aktif').is(':checked'));
        });
    });
</script>
@endsection