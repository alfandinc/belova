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
                            <div class="row">
                                <!-- Wilayah filter removed -->
                                <div class="col-6">
                                    <select id="last-visit-filter" class="form-control" style="min-width:180px;">
                                        <option value="all">Semua Kunjungan Terakhir</option>
                                        <option value="gt1w">Lebih dari 1 Minggu</option>
                                        <option value="gt1m">Lebih dari 1 Bulan</option>
                                        <option value="gt3m">Lebih dari 3 Bulan</option>
                                        <option value="gt6m">Lebih dari 6 Bulan</option>
                                        <option value="gt1y">Lebih dari 1 Tahun</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <select id="last-visit-klinik-filter" class="form-control" style="min-width:180px;">
                                        <option value="all">Semua Klinik Terakhir</option>
                                        @foreach(\App\Models\ERM\Klinik::all() as $klinik)
                                            <option value="{{ $klinik->id }}">{{ $klinik->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
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
                                    <th>Tanggal Lahir</th>
                                    <th>No HP</th>
                                    <th>Kunjungan Terakhir</th>
                                    <th>Gender</th>
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
                    d.last_visit = $('#last-visit-filter').val();
                    d.last_visit_klinik = $('#last-visit-klinik-filter').val();
                }
            },
            columns: [
                { data: 'id', name: 'id' },
                { data: 'nama', name: 'nama' },
                { data: 'tanggal_lahir', name: 'tanggal_lahir' },
                { data: 'no_hp', name: 'no_hp' },
                { data: 'kunjungan_terakhir', name: 'kunjungan_terakhir' },
                { data: 'gender_text', name: 'gender_text' }
            ]
        });
        
        // Reload table when any filter changes
        $('#last-visit-filter, #last-visit-klinik-filter').change(function() {
            table.draw();
        });
    });
</script>
@endpush
