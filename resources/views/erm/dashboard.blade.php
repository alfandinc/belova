
@extends('layouts.erm.app')
@section('title')
    @php
        $klinikId = auth()->user()->dokter->klinik_id ?? null; // Assuming 'dokter' is the relationship
        echo $klinikId == 1 ? ' ERM Premiere Belova' : ($klinikId == 2 ? ' ERM Belova Skin' : 'ERM Belova');
    @endphp
@endsection 
@section('navbar')
    @include('layouts.erm.navbar')
@endsection        
@section('content')
<!-- Modal for visitation details -->
<div class="modal fade" id="visitationDetailModal" tabindex="-1" role="dialog" aria-labelledby="visitationDetailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="visitationDetailModalLabel">Detail Kunjungan</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <table class="table table-bordered" id="visitation-detail-table">
          <thead>
            <tr>
              <th>Tanggal Visit</th>
              <th>Nama Pasien</th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </div>
</div>

            <!-- Page Content-->           
            <div class="container-fluid">
                <!-- Page-Title -->
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-title-box">
                            <div class="row">
                                <div class="col">
                                    <h4>Selamat Datang di ERM <strong>{{ auth()->user()->getRoleNames()->first() }}</strong>, {{ auth()->user()->name }}!</h4>
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                                        <li class="breadcrumb-item active">Dashboard</li>
                                    </ol>
                                </div><!--end col-->
                            </div><!--end row-->                                                              
                        </div><!--end page-title-box-->
                    </div><!--end col-->
                </div><!--end row-->
                <!-- end page title end breadcrumb -->

                <!-- Filters -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <label for="dokter-select">Dokter</label>
                        <select id="dokter-select" class="form-control select2">
                            <option value="">Semua Dokter</option>
                            <option value="all">Tampilkan Semua Data</option>
                            @php
                                $dokters = \App\Models\ERM\Dokter::all();
                            @endphp
                            @php
                                $userDokterId = auth()->user()->dokter->id ?? null;
                            @endphp
                            @foreach($dokters as $dokter)
                                <option value="{{ $dokter->id }}" {{ $userDokterId == $dokter->id ? 'selected' : '' }}>{{ $dokter->user->name ?? '-' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="date-range">Tanggal</label>
                        <input type="text" id="date-range" class="form-control" placeholder="Pilih rentang tanggal">
                    </div>
                </div>
                
                <!-- Visitation Count Boxes -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="card text-center visitation-box" data-status="all">
                            <div class="card-header bg-primary text-white" style="font-weight:bold;font-size:1.2em;">Total Visitation</div>
                            <div class="card-body">
                                <h2 id="visitation-count" style="font-weight:bold;">0</h2>
                            </div>
                        </div>
                        <!-- Pasien Kunjungan Terbanyak box below Total Visitation -->
                        <div class="card text-center mt-3">
                            <div class="card-header" style="font-weight:bold;font-size:1.2em;">Pasien Kunjungan Terbanyak</div>
                            <div class="card-body">
                                <div id="most-visit-pasien" style="font-size:1.2em;line-height:2em;text-align:left;white-space:pre-line">-</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center visitation-box" data-status="2">
                            <div class="card-header bg-success text-white" style="font-weight:bold;font-size:1.2em;">Dilayani</div>
                            <div class="card-body">
                                <h2 id="dilayani-count" style="font-weight:bold;">0</h2>
                            </div>
                        </div>
                        <!-- Most Spender Pasien box below Dilayani -->
                        <div class="card text-center mt-3">
                            <div class="card-header" style="font-weight:bold;font-size:1.1em;">Most Spender Pasien</div>
                            <div class="card-body">
                                <div id="most-spender-pasien" style="font-size:1.1em;line-height:2em;text-align:left;white-space:pre-line">-</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center visitation-box" data-status="1">
                            <div class="card-header bg-warning text-dark" style="font-weight:bold;font-size:1.2em;">Belum Dilayani</div>
                            <div class="card-body">
                                <h2 id="belum-count" style="font-weight:bold;">0</h2>
                            </div>
                        </div>
                        <!-- Jenis Kunjungan box below Belum Dilayani -->
                        <div class="card text-center mt-3">
                            <div class="card-header" style="font-weight:bold;font-size:1.2em;">Jenis Kunjungan</div>
                            <div class="card-body" style="text-align:left;">
                                <div id="jenis-kunjungan-list" style="font-size:1.1em;line-height:2em;">-</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center visitation-box" data-status="0">
                            <div class="card-header bg-danger text-white" style="font-weight:bold;font-size:1.2em;">Tidak Datang</div>
                            <div class="card-body">
                                <h2 id="tidakdatang-count" style="font-weight:bold;">0</h2>
                            </div>
                        </div>
                        <!-- Metode Bayar box below Tidak Datang -->
                        <div class="card text-center mt-3">
                            <div class="card-header" style="font-weight:bold;font-size:1.1em;">Metode Bayar</div>
                            <div class="card-body">
                                <div id="metode-bayar-list" style="font-size:1.1em;line-height:2em;text-align:left;">-</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center visitation-box" data-status="7">
                            <div class="card-header bg-danger text-white" style="font-weight:bold;font-size:1.2em;">Dibatalkan</div>
                            <div class="card-body">
                                <h2 id="dibatalkan-count" style="font-weight:bold;">0</h2>
                            </div>
                        </div>
                        <!-- Pasien Baru box below Dibatalkan -->
                        <div class="card text-center mt-3">
                            <div class="card-header" style="font-weight:bold;font-size:1.1em;">Pasien Baru</div>
                            <div class="card-body">
                                <h2 id="pasien-baru-count" style="font-weight:bold;">0</h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Monthly Visit Bar Chart -->
                <div class="row mb-4">
                    <div class="col-6">
                        <div class="card mt-4">
                            <div class="card-header bg-primary text-white" style="font-weight:bold;font-size:1.2em;">Grafik Kunjungan per Bulan ({{ date('Y') }})</div>
                            <div class="card-body">
                                <div id="monthly-visit-chart"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div><!-- container -->

@endsection


@push('scripts')
<script>
$(document).ready(function() {
    // Initialize Select2 for dokter dropdown
    $('#dokter-select').select2({
        placeholder: 'Pilih Dokter',
        allowClear: true
    });

    // Initialize daterangepicker for date filter with default date today
    var today = moment().format('YYYY-MM-DD');
    $('#date-range').daterangepicker({
        autoUpdateInput: true,
        startDate: today,
        endDate: today,
        locale: {
            cancelLabel: 'Clear',
            format: 'YYYY-MM-DD'
        }
    });

    // Set initial value to today
    $('#date-range').val(today + ' - ' + today);

    $('#date-range').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
        fetchVisitationCount();
    });
    $('#date-range').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
        fetchVisitationCount();
    });

    $('#dokter-select').on('change', function() {
        fetchVisitationCount();
    });

    var monthlyVisitChart;
    function renderMonthlyVisitChart(data) {
        var options = {
            chart: {
                type: 'bar',
                height: 350
            },
            series: [{
                name: 'Jumlah Kunjungan',
                data: data
            }],
            xaxis: {
                categories: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des']
            },
            colors: ['#007bff'],
            dataLabels: {
                enabled: true
            }
        };
        if (monthlyVisitChart) {
            monthlyVisitChart.updateOptions(options);
        } else {
            monthlyVisitChart = new ApexCharts(document.querySelector('#monthly-visit-chart'), options);
            monthlyVisitChart.render();
        }
    }

    function fetchVisitationCount() {
        var dokterId = $('#dokter-select').val();
        if (dokterId === 'all' || dokterId === '') {
            dokterId = '';
        }
        var dateRange = $('#date-range').val();
        var startDate = '';
        var endDate = '';
        if (dateRange) {
            var dates = dateRange.split(' - ');
            startDate = dates[0];
            endDate = dates[1] ? dates[1] : dates[0];
        }
        $.ajax({
            url: '{{ route('erm.dashboard.visitation-count') }}',
            data: {
                dokter_id: dokterId,
                start_date: startDate,
                end_date: endDate
            },
            success: function(res) {
                $('#visitation-count').text(res.count);
                $('#dilayani-count').text(res.dilayani ?? 0);
                $('#belum-count').text(res.belum ?? 0);
                $('#tidakdatang-count').text(res.tidakdatang ?? 0);
                $('#dibatalkan-count').text(res.dibatalkan ?? 0);
                $('#pasien-baru-count').text(res.pasien_baru_count ?? 0);
                if (Array.isArray(res.most_visit_pasien) && res.most_visit_pasien.length > 0) {
                    let html = '';
                    res.most_visit_pasien.forEach(function(n, i) {
                        // Split name and count
                        let match = n.match(/^(.*) \((\d+)\)$/);
                        if (match) {
                            html += '<div style="display:flex;justify-content:space-between;"><span>' + (i+1) + '. ' + match[1] + '</span><span style="font-weight:bold;text-align:right;min-width:40px;">' + match[2] + '</span></div>';
                        } else {
                            html += (i+1) + '. ' + n + '<br>';
                        }
                    });
                    $('#most-visit-pasien').html(html);
                } else {
                    $('#most-visit-pasien').text('-');
                }
                // Most Spender Pasien
                if (Array.isArray(res.most_spender_pasien) && res.most_spender_pasien.length > 0) {
                    let spenderHtml = '';
                    res.most_spender_pasien.forEach(function(n, i) {
                        // Split name and money
                        let match = n.match(/^(.*) \(Rp (.*)\)$/);
                        if (match) {
                            spenderHtml += '<div style="display:flex;justify-content:space-between;"><span>' + (i+1) + '. ' + match[1] + '</span><span style="font-weight:bold;text-align:right;min-width:100px;">Rp ' + match[2] + '</span></div>';
                        } else {
                            spenderHtml += (i+1) + '. ' + n + '<br>';
                        }
                    });
                    $('#most-spender-pasien').html(spenderHtml);
                } else {
                    $('#most-spender-pasien').text('-');
                }
                // Jenis Kunjungan
                if (res.jenis_kunjungan) {
                    let jkHtml = '';
                    jkHtml += '<div><span style="color:#28a745;font-weight:bold;">1. Konsultasi Dokter:</span> <span style="float:right;font-weight:bold;">' + (res.jenis_kunjungan[1] ?? 0) + '</span></div>';
                    jkHtml += '<div><span style="color:#ffc107;font-weight:bold;">2. Beli Produk:</span> <span style="float:right;font-weight:bold;">' + (res.jenis_kunjungan[2] ?? 0) + '</span></div>';
                    jkHtml += '<div><span style="color:#007bff;font-weight:bold;">3. Laboratorium:</span> <span style="float:right;font-weight:bold;">' + (res.jenis_kunjungan[3] ?? 0) + '</span></div>';
                    $('#jenis-kunjungan-list').html(jkHtml);
                } else {
                    $('#jenis-kunjungan-list').text('-');
                }
                // Metode Bayar
                if (res.metode_bayar_counts) {
                    let mbHtml = '';
                    mbHtml += '<div><span style="color:#ffc107;font-weight:bold;">1. Umum:</span> <span style="float:right;font-weight:bold;">' + (res.metode_bayar_counts[1] ?? 0) + '</span></div>';
                    mbHtml += '<div><span style="color:#007bff;font-weight:bold;">2. InHealth:</span> <span style="float:right;font-weight:bold;">' + (res.metode_bayar_counts[2] ?? 0) + '</span></div>';
                    $('#metode-bayar-list').html(mbHtml);
                } else {
                    $('#metode-bayar-list').text('-');
                }
                // ...existing code...
                if (Array.isArray(res.monthly_visit_counts)) {
                    renderMonthlyVisitChart(res.monthly_visit_counts);
                }
            }
        });
    }

    // Set Select2 to selected dokter on page load
    var userDokterId = '{{ auth()->user()->dokter->id ?? '' }}';
    if (userDokterId) {
        $('#dokter-select').val(userDokterId).trigger('change');
    }
    // Click event for visitation boxes
    $('.visitation-box').on('click', function() {
        var status = $(this).data('status');
        var dokterId = $('#dokter-select').val();
        if (dokterId === 'all' || dokterId === '') {
            dokterId = '';
        }
        var dateRange = $('#date-range').val();
        var startDate = '';
        var endDate = '';
        if (dateRange) {
            var dates = dateRange.split(' - ');
            startDate = dates[0];
            endDate = dates[1] ? dates[1] : dates[0];
        }
        // Destroy previous DataTable instance if exists
        if ($.fn.DataTable.isDataTable('#visitation-detail-table')) {
            $('#visitation-detail-table').DataTable().destroy();
            $('#visitation-detail-table').empty();
            $('#visitation-detail-table').append('<thead><tr><th>Tanggal Visit</th><th>Nama Pasien</th></tr></thead>');
        }
        // Initialize DataTable with server-side processing
        $('#visitation-detail-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route('erm.dashboard.visitation-detail') }}',
                data: {
                    dokter_id: dokterId,
                    start_date: startDate,
                    end_date: endDate,
                    status: status
                }
            },
            columns: [
                { data: 'tanggal_visitation', name: 'tanggal_visitation' },
                { data: 'nama_pasien', name: 'nama_pasien' }
            ],
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            searching: true,
            ordering: false
        });
        $('#visitationDetailModal').modal('show');
    });

    // Initial load
    fetchVisitationCount();
});
</script>
@endpush
