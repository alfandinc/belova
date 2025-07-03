@extends('layouts.marketing.app')

@section('title', 'Pasien Data - Marketing')

@section('navbar')
    @include('layouts.marketing.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <h4 class="page-title">Pasien Data</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('marketing.dashboard') }}">Marketing</a></li>
                            <li class="breadcrumb-item active">Pasien Data</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    <!-- end page title end breadcrumb -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-md-8">
                            <h4 class="card-title">Daftar Pasien</h4>
                        </div>
                        <div class="col-md-4">
                            <select id="area-filter" class="form-control">
                                <option value="all">Semua Wilayah</option>
                                <option value="Laweyan">Laweyan</option>
                                <option value="Banjarsari">Banjarsari</option>
                                <option value="Serengan">Serengan</option>
                                <option value="Pasar Kliwon">Pasar Kliwon</option>
                                <option value="Jebres">Jebres</option>
                                <option value="Sukoharjo">Sukoharjo</option>
                                <option value="Wonogiri">Wonogiri</option>
                                <option value="Karanganyar">Karanganyar</option>
                            </select>
                        </div>
                    </div>
                </div><!--end card-header-->
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="pasien-table" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama</th>
                                    <th>Umur</th>
                                    <th>Gender</th>
                                    <th>Wilayah</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div> <!-- end col -->
    </div> <!-- end row -->
</div><!-- container -->
@endsection

@push('scripts')
<script>
    $(function() {
        // Initialize DataTable
        var table = $('#pasien-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('marketing.pasien-data') }}",
                data: function (d) {
                    d.area = $('#area-filter').val();
                }
            },
            columns: [
                { data: 'id', name: 'id' },
                { data: 'nama', name: 'nama' },
                { data: 'umur', name: 'umur' },
                { data: 'gender_text', name: 'gender_text' },
                { data: 'area', name: 'area' }
            ]
        });
        
        // Reload table when area filter changes
        $('#area-filter').change(function() {
            table.draw();
        });
    });
</script>
@endpush
