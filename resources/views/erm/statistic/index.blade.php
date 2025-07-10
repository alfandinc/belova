@extends('layouts.erm.app')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection

@section('title', 'Statistik Farmasi')

@section('styles')
<style>
    .analytics-data-box {
        transition: all 0.3s ease;
    }
    .analytics-data-box:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 16px rgba(0,0,0,0.15);
    }
    .btn-group .btn {
        flex: 1;
    }
    .ml-2 {
        margin-left: 0.5rem;
    }
    @media (max-width: 768px) {
        .analytics-data-box h1 {
            font-size: 2.5rem !important;
        }
        .analytics-data-box span {
            font-size: 1.2rem !important;
        }
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <h4 class="page-title">Statistik Farmasi</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/erm">Dashboard</a></li>
                            <li class="breadcrumb-item active">Statistik Farmasi</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    
    <div class="row">
        <div class="col-lg-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Filter Statistik</h4>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label>Filter Periode:</label>
                            <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                                <label class="btn btn-primary active">
                                    <input type="radio" name="period" id="daily" value="daily" checked> Harian
                                </label>
                                <label class="btn btn-primary">
                                    <input type="radio" name="period" id="weekly" value="weekly"> Mingguan
                                </label>
                                <label class="btn btn-primary">
                                    <input type="radio" name="period" id="monthly" value="monthly"> Bulanan
                                </label>
                                <label class="btn btn-primary">
                                    <input type="radio" name="period" id="yearly" value="yearly"> Tahunan
                                </label>
                                <label class="btn btn-primary">
                                    <input type="radio" name="period" id="all" value="all"> Semua
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="filter_klinik">Filter Klinik:</label>
                                <select id="filter_klinik" class="form-control select2">
                                    <option value="">Semua Klinik</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="filter_dokter">Filter Dokter:</label>
                                <select id="filter_dokter" class="form-control select2">
                                    <option value="">Semua Dokter</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body text-center p-0">
                    <div class="analytics-data-box bg-success" style="padding: 1.5rem 1rem; border-radius: 4px; position: relative;">
                        <h5 class="text-white mb-3 font-weight-bold">RESEP TERLAYANI</h5>
                        <div class="d-flex justify-content-center align-items-baseline">
                            <h1 class="text-white font-weight-bold" id="total-terlayani" style="font-size: 3.5rem; margin-bottom: 0.5rem;">0</h1>
                            <span class="text-white-50 ml-2" id="percentage-terlayani" style="font-size: 1.5rem;">(0%)</span>
                        </div>
                        <div class="icon-box" style="position: absolute; top: 15px; right: 15px;">
                            <i data-feather="check-circle" class="align-self-center text-white-50" style="width: 30px; height: 30px;"></i>
                        </div>
                    </div>
                </div><!--end card-body-->
            </div><!--end card-->
        </div><!--end col-->

        <div class="col-lg-6">
            <div class="card">
                <div class="card-body text-center p-0">
                    <div class="analytics-data-box bg-danger" style="padding: 1.5rem 1rem; border-radius: 4px; position: relative;">
                        <h5 class="text-white mb-3 font-weight-bold">RESEP TIDAK TERLAYANI</h5>
                        <div class="d-flex justify-content-center align-items-baseline">
                            <h1 class="text-white font-weight-bold" id="total-tidak-terlayani" style="font-size: 3.5rem; margin-bottom: 0.5rem;">0</h1>
                            <span class="text-white-50 ml-2" id="percentage-tidak-terlayani" style="font-size: 1.5rem;">(0%)</span>
                        </div>
                        <div class="icon-box" style="position: absolute; top: 15px; right: 15px;">
                            <i data-feather="x-circle" class="align-self-center text-white-50" style="width: 30px; height: 30px;"></i>
                        </div>
                    </div>
                </div><!--end card-body-->
            </div><!--end card-->
        </div><!--end col-->
    </div><!--end row-->

    <div class="row">
        <div class="col-lg-12 text-center mt-3">
            <p class="text-muted">Total Resep: <span class="font-weight-bold" id="total-resep">0</span></p>
        </div>
    </div><!--end row-->
    
    <div class="row mt-4">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Statistik Obat Racikan dan Non-Racikan yang Terlayani</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Periode</th>
                                    <th>Obat Non-Racikan</th>
                                    <th>Racikan (Paket)</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody id="racikan-period-tbody">
                                <tr>
                                    <td colspan="4" class="text-center">Memuat data...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">
                            <i>* Obat Non-Racikan: Jumlah item obat individu yang tidak termasuk dalam racikan</i><br>
                            <i>* Racikan (Paket): Jumlah paket racikan (setiap paket racikan dihitung sebagai 1 item meskipun berisi beberapa obat)</i>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div><!--end row-->
    
</div><!-- container -->
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Initialize select2
        $('.select2').select2({
            width: '100%'
        });
        
        // Populate dropdown options for klinik and dokter
        @if(isset($kliniks))
        @foreach($kliniks as $klinik)
            $('#filter_klinik').append(new Option('{{ $klinik->nama }}', '{{ $klinik->id }}'));
        @endforeach
        @endif
        
        @if(isset($dokters))
        @foreach($dokters as $dokter)
            $('#filter_dokter').append(new Option('{{ $dokter->user->name ?? "-" }}', '{{ $dokter->id }}'));
        @endforeach
        @endif
        
        // Function to load statistics data
        function loadStatisticsData() {
            const period = $('input[name="period"]:checked').val();
            const klinikId = $('#filter_klinik').val();
            const dokterId = $('#filter_dokter').val();
            
            $.ajax({
                url: '/erm/statistic/data',
                method: 'GET',
                data: { 
                    period: period,
                    klinik_id: klinikId,
                    dokter_id: dokterId
                },
                success: function(response) {
                    // Calculate totals
                    const totalTerlayani = response.terlayani.reduce((a, b) => a + b, 0);
                    const totalTidakTerlayani = response.tidak_terlayani.reduce((a, b) => a + b, 0);
                    const totalResep = totalTerlayani + totalTidakTerlayani;
                    
                    // Update big number displays and percentages
                    $('#total-terlayani').text(totalTerlayani);
                    $('#total-tidak-terlayani').text(totalTidakTerlayani);
                    $('#total-resep').text(totalResep);
                    
                    if (totalResep > 0) {
                        const percentageTerlayani = ((totalTerlayani / totalResep) * 100).toFixed(0);
                        const percentageTidakTerlayani = ((totalTidakTerlayani / totalResep) * 100).toFixed(0);
                        
                        $('#percentage-terlayani').text('(' + percentageTerlayani + '%)');
                        $('#percentage-tidak-terlayani').text('(' + percentageTidakTerlayani + '%)');
                    } else {
                        $('#percentage-terlayani').text('(0%)');
                        $('#percentage-tidak-terlayani').text('(0%)');
                    }
                    
                    // Update racikan statistics by period
                    const labels = response.labels;
                    const racikanByPeriod = response.racikanByPeriod;
                    const nonRacikanByPeriod = response.nonRacikanByPeriod;
                    
                    // Clear the table
                    $('#racikan-period-tbody').empty();
                    
                    // Create table rows for each period
                    if (labels && labels.length > 0) {
                        for (let i = 0; i < labels.length; i++) {
                            const nonRacikan = nonRacikanByPeriod[i] || 0;
                            const racikan = racikanByPeriod[i] || 0;
                            const total = nonRacikan + racikan;
                            
                            const row = `
                                <tr>
                                    <td>${labels[i]}</td>
                                    <td>${nonRacikan}</td>
                                    <td>${racikan}</td>
                                    <td>${total}</td>
                                </tr>
                            `;
                            
                            $('#racikan-period-tbody').append(row);
                        }
                        
                        // Add a total row
                        const totalNonRacikan = nonRacikanByPeriod.reduce((a, b) => a + b, 0);
                        const totalRacikan = racikanByPeriod.reduce((a, b) => a + b, 0);
                        const grandTotal = totalNonRacikan + totalRacikan;
                        
                        const totalRow = `
                            <tr class="table-primary">
                                <td><strong>Total</strong></td>
                                <td><strong>${totalNonRacikan}</strong></td>
                                <td><strong>${totalRacikan}</strong></td>
                                <td><strong>${grandTotal}</strong></td>
                            </tr>
                        `;
                        
                        $('#racikan-period-tbody').append(totalRow);
                    } else {
                        $('#racikan-period-tbody').html('<tr><td colspan="4" class="text-center">Tidak ada data</td></tr>');
                    }
                },
                error: function(error) {
                    console.error('Error loading statistics data:', error);
                }
            });
        }
        
        // Load initial data (daily by default)
        loadStatisticsData();
        
        // Handle period change
        $('input[name="period"]').change(function() {
            loadStatisticsData();
        });
        
        // Handle filter changes
        $('#filter_klinik, #filter_dokter').change(function() {
            loadStatisticsData();
        });
        
        // Initialize Feather icons
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    });
</script>
@endsection
