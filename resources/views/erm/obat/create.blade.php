@extends('layouts.erm.app')
@section('title', 'ERM | Tambah Obat')

@section('navbar')
    @include('layouts.erm.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="d-flex  align-items-center mb-0 mt-2">
        <h3 class="mb-0 mr-2">Tambah Obat Baru</h3>
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
                            <li class="breadcrumb-item active">Tambah Obat</li>
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

        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="nama">Nama Obat</label>
                <input type="text" name="nama" id="nama" class="form-control" required>
            </div>

            <div class="form-group col-md-3">
                <label for="dosis">Dosis</label>
                <input type="number" name="dosis" id="dosis" class="form-control" required>
            </div>

            <div class="form-group col-md-3">
                <label for="satuan">Satuan</label>
                <select id="satuan" name="satuan" class="form-control select2" required>
                    <option value="" disabled selected>Pilih</option>
                    @foreach (['mg','g','ml','tablet','kapsul','sendok','tetes','vial','ampul','patch','suppositoria','puff'] as $satuan)
                        <option value="{{ $satuan }}">{{ ucfirst($satuan) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group col-md-4">
                <label for="harga_umum">Harga Umum</label>
                <input type="number" name="harga_umum" id="harga_umum" class="form-control" required>
            </div>

            <div class="form-group col-md-4">
                <label for="harga_inhealth">Harga InHealth</label>
                <input type="number" name="harga_inhealth" id="harga_inhealth" class="form-control" required>
            </div>

            <div class="form-group col-md-4">
                <label for="stok">Stok</label>
                <input type="number" name="stok" id="stok" class="form-control" required>
            </div>

            <div class="form-group col-12">
                <label for="zataktif_id">Zat Aktif <small>(bisa lebih dari satu)</small></label>
                <select name="zataktif_id[]" id="zataktif_id" class="form-control select2" multiple required>
                    @foreach ($zatAktif as $zat)
                        <option value="{{ $zat->id }}">{{ $zat->nama }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Uncomment when supplier field is needed
            <div class="form-group col-md-6">
                <label for="supplier_id">Supplier Obat</label>
                <select name="supplier_id" id="supplier_id" class="form-control select2" required>
                    @foreach ($supplier as $s)
                        <option value="{{ $s->id }}">{{ $s->nama }}</option>
                    @endforeach
                </select>
            </div>
            --}}
        </div>

        <div class="mt-3">
            <button type="submit" class="btn btn-success">Simpan</button>
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
