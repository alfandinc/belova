@extends('layouts.laporan.app')

@section('title', 'Rekap Kehadiran Karyawan')
@section('navbar')
    @include('layouts.laporan.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Rekap Kehadiran Karyawan</h4>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <a href="{{ route('laporan.hrd.rekap-kehadiran.excel') }}" class="btn btn-success mr-2">
                            <i class="fa fa-file-excel-o"></i> Download Excel
                        </a>
                        <a href="{{ route('laporan.hrd.rekap-kehadiran.pdf') }}" class="btn btn-danger">
                            <i class="fa fa-file-pdf-o"></i> Download PDF
                        </a>
                    </div>
                    <table id="rekapKehadiranTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>No Induk</th>
                                <th>Nama</th>
                                <th>Sakit</th>
                                <th>Izin</th>
                                <th>Cuti</th>
                                <th>Sisa Cuti</th>
                                <th>Jumlah Hari Masuk</th>
                                <th>On Time</th>
                                <th>Overtime (menit)</th>
                                <th>Terlambat</th>
                                <th>Menit Terlambat</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data as $row)
                                <tr>
                                    <td>{{ $row['no_induk'] }}</td>
                                    <td>{{ $row['nama'] }}</td>
                                    <td>{{ $row['sakit'] }}</td>
                                    <td>{{ $row['izin'] }}</td>
                                    <td>{{ $row['cuti'] }}</td>
                                    <td>{{ $row['sisa_cuti'] }}</td>
                                    <td>{{ $row['jumlah_hari_masuk'] }}</td>
                                    <td>{{ $row['on_time'] }}</td>
                                    <td>{{ $row['overtime'] }}</td>
                                    <td>{{ $row['terlambat'] }}</td>
                                    <td>{{ $row['menit_terlambat'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#rekapKehadiranTable').DataTable({
        responsive: true
    });
});
</script>
@endpush
