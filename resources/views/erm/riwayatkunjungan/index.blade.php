@extends('layouts.erm.app')

@section('title', 'ERM | Riwayat Kunjungan')
@section('navbar')
    @include('layouts.erm.navbardetail')
@endsection

@section('content')

@include('erm.partials.modal-alergipasien')
@include('erm.partials.modal-suratdiagnosa')

<div class="container-fluid">
    <div class="d-flex  align-items-center mb-0 mt-2">
        <h3 class="mb-0 mr-2">Riwayat Kunjungan</h3>
    </div>
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                            <li class="breadcrumb-item">Rawat Jalan</li>
                            <li class="breadcrumb-item active">E-Lab</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->                                                              
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->  
    <!-- end page title end breadcrumb -->
    @include('erm.partials.card-identitaspasien')

    <div class="card">
        <div class="card-body">
            <div class="row mb-3 align-items-end">
                <div class="col-md-3">
                    <div class="card bg-primary mb-0">
                        <div class="card-body p-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="text-dark">
                                    <div class="font-weight-bold text-dark">Total Kunjungan (Selesai)</div>
                                    <div class="h4 mb-0 text-dark">{{ number_format($stats['total_visits'] ?? 0) }}</div>
                                </div>
                                <div><i class="fas fa-calendar-check fa-2x text-dark"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success mb-0">
                        <div class="card-body p-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="text-dark">
                                    <div class="font-weight-bold text-dark">Total Pengeluaran</div>
                                    <div class="h4 mb-0 text-dark">Rp {{ number_format($stats['total_spend'] ?? 0, 0, ',', '.') }}</div>
                                </div>
                                <div><i class="fas fa-wallet fa-2x text-dark"></i></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="row">
                        <div class="col-md-5">
                            <label for="filter_status_kunjungan">Status Kunjungan</label>
                            <select id="filter_status_kunjungan" class="form-control select2">
                                <option value="">Semua</option>
                                <option value="0">Tidak datang</option>
                                <option value="1">Belum diperiksa</option>
                                <option value="2">Sudah diperiksa</option>
                                <option value="7">Dibatalkan</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label for="filter_jenis_kunjungan">Jenis Kunjungan</label>
                            <select id="filter_jenis_kunjungan" class="form-control select2">
                                <option value="">Semua</option>
                                <option value="1">Konsultasi Dokter</option>
                                <option value="2">Beli Produk</option>
                                <option value="3">Laboratorium</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button id="btnResetFilter" class="btn btn-secondary w-100">Reset</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Riwayat Hasil Laboratorium -->
            <div>
                <div class="table-responsive">
                    <table class="table table-bordered" id="riwayat-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal Kunjungan</th>
                                <th>Spesialisasi</th>
                                <th>Dokter</th>
                                <th>Status Pasien</th>                
                                {{-- <th>Tanggal Booking</th> --}}
                                <th>Dokumen</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<script>
$(document).ready(function () {
    $('.select2').select2({ width: '100%' });

    // Set default filters: status_kunjungan = 2, jenis_kunjungan = 1
    $('#filter_status_kunjungan').val('2').trigger('change');
    $('#filter_jenis_kunjungan').val('1').trigger('change');

    var riwayatTable = $('#riwayat-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('erm.riwayatkunjungan.index', $pasien) }}',
            data: function(d) {
                d.status_kunjungan = $('#filter_status_kunjungan').val();
                d.jenis_kunjungan = $('#filter_jenis_kunjungan').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', title: 'No', orderable: false, searchable: false },
            
            { data: 'tanggal_visitation', name: 'tanggal_visitation' },
            { data: 'spesialisasi', name: 'spesialisasi', orderable: false }, 
            { data: 'dokter', name: 'dokter', orderable: false }, 
            { data: 'metode', name: 'metodeBayar.nama' },
            { data: 'status_dokumen', name: 'status_dokumen' },
            // { data: 'created_at', name: 'created_at' },
            { data: 'aksi', name: 'aksi', orderable: false, searchable: false }
        ]
    });

    // Auto-apply filters when selection changes
    $('#filter_status_kunjungan, #filter_jenis_kunjungan').on('change', function() {
        riwayatTable.ajax.reload();
    });

    // Reset filters
    $('#btnResetFilter').on('click', function() {
        $('#filter_status_kunjungan').val('').trigger('change');
        $('#filter_jenis_kunjungan').val('').trigger('change');
        // table will reload because change event triggers reload
    });

    // Surat Diagnosis button click
    $(document).on('click', '.diagnosis-btn', function() {
        const visitationId = $(this).data('id');
        $('#visitation_id').val(visitationId);
        
        // Get data from server
        $.ajax({
            url: '/erm/riwayatkunjungan/' + visitationId + '/get-data-diagnosis',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                // Set pasien data
                $('#pasien_nama').text(response.pasien.nama);
                $('#pasien_rm').text(response.pasien.id);
                $('#pasien_lahir').text(moment(response.pasien.tanggal_lahir).format('DD-MM-YYYY'));
                $('#pasien_gender').text(response.pasien.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan');
                $('#pasien_alamat').text(response.pasien.alamat);
                
                // Set diagnoses
                let diagnosisHtml = '';
                if (response.diagnoses.length > 0) {
                    diagnosisHtml += '<ul class="list-group">';
                    response.diagnoses.forEach(function(diagnosis, index) {
                        diagnosisHtml += '<li class="list-group-item">' + diagnosis + '</li>';
                    });
                    diagnosisHtml += '</ul>';
                } else {
                    diagnosisHtml = '<p class="text-muted">Tidak ada diagnosis yang ditemukan.</p>';
                }
                $('#diagnosis_list').html(diagnosisHtml);
                
                // Set keterangan if exists
                if (response.suratDiagnosa) {
                    $('#keterangan').val(response.suratDiagnosa.keterangan);
                } else {
                    $('#keterangan').val('');
                }
                
                // Show modal
                $('#diagnosisModal').modal('show');
            },
            error: function(error) {
                console.error('Error fetching data:', error);
                alert('Terjadi kesalahan saat mengambil data. Silakan coba lagi.');
            }
        });
    });
    
    // Save Surat Diagnosis
    $('#saveDiagnosis').on('click', function() {
        const visitationId = $('#visitation_id').val();
        const keterangan = $('#keterangan').val();
        
        $.ajax({
            url: '/erm/riwayatkunjungan/store-surat-diagnosis',
            type: 'POST',
            dataType: 'json',
            data: {
                _token: '{{ csrf_token() }}',
                visitation_id: visitationId,
                keterangan: keterangan
            },
            success: function(response) {
                if (response.success) {
                    alert('Surat diagnosis berhasil disimpan.');
                } else {
                    alert('Terjadi kesalahan. Silakan coba lagi.');
                }
            },
            error: function(error) {
                console.error('Error saving data:', error);
                alert('Terjadi kesalahan saat menyimpan data. Silakan coba lagi.');
            }
        });
    });
    
    // Print Surat Diagnosis
    $('#printDiagnosis').on('click', function() {
        const visitationId = $('#visitation_id').val();
        
        // First save the form data
        const keterangan = $('#keterangan').val();
        
        $.ajax({
            url: '/erm/riwayatkunjungan/store-surat-diagnosis',
            type: 'POST',
            dataType: 'json',
            data: {
                _token: '{{ csrf_token() }}',
                visitation_id: visitationId,
                keterangan: keterangan
            },
            success: function(response) {
                if (response.success) {
                    // If save is successful, open the PDF in a new window
                    window.open('/erm/riwayatkunjungan/' + visitationId + '/print-surat-diagnosis', '_blank');
                } else {
                    alert('Terjadi kesalahan saat menyimpan. Silakan coba lagi.');
                }
            },
            error: function(error) {
                console.error('Error saving data:', error);
                alert('Terjadi kesalahan saat menyimpan data. Silakan coba lagi.');
            }
        });
    });

    // Saat tombol modal alergi ditekan
    $('#btnBukaAlergi').on('click', function () {
        $('#modalAlergi').modal('show');
    });

    // Dokumen button: open different pages based on status_dokumen
    // - if status_dokumen === 'asesmen' -> open Asesmen Dokter create page
    // - if status_dokumen === 'cppt' -> open CPPT create page
    $(document).on('click', '.dokumen-btn', function(e) {
        e.preventDefault();

        // get the DataTable row data for this button's row
        var $tr = $(this).closest('tr');
        var rowData = riwayatTable.row($tr).data();

        // fallback: try to read id from data attribute on the button
        var visitationId = (rowData && (rowData.id || rowData.visitation_id)) ? (rowData.id || rowData.visitation_id) : $(this).data('id');
        var statusDokumen = rowData && rowData.status_dokumen ? rowData.status_dokumen : $(this).data('status');

        if (!visitationId) {
            console.error('Visitation id not found for dokumen button.');
            return;
        }

        // normalize status to lowercase string when present
        if (statusDokumen) {
            statusDokumen = String(statusDokumen).toLowerCase();
        }

        if (statusDokumen === 'asesmen') {
            // Open Asesmen Dokter create route
            window.open('/asesmendokter/' + visitationId + '/create', '_blank');
        } else if (statusDokumen === 'cppt') {
            // Open CPPT create route
            window.open('/cppt/' + visitationId + '/create', '_blank');
        } else {
            // Fallback: open resume medis if available
            window.open('/resume-medis/' + visitationId, '_blank');
        }
    });

    // Toggle semua bagian tergantung status
        var initialStatusAlergi = $('input[name="statusAlergi"]:checked').val(); // Ambil status yang dipilih awalnya
    
    // Jika status alergi adalah 'ada', tampilkan semua elemen yang terkait
    if (initialStatusAlergi === 'ada') {
        $('#inputKataKunciWrapper').show();
        $('#selectAlergiWrapper').show();
        $('#selectKandunganWrapper').show();
    } else {
        // Jika tidak, sembunyikan elemen-elemen tersebut
        $('#inputKataKunciWrapper').hide();
        $('#selectAlergiWrapper').hide();
        $('#selectKandunganWrapper').hide();
    }
    $('input[name="statusAlergi"]').on('change', function () {
        if ($(this).val() === 'ada') {
            $('#inputKataKunciWrapper').show();
            $('#selectAlergiWrapper').show();
            $('#selectKandunganWrapper').show();
        } else {
            $('#inputKataKunciWrapper').hide();
            $('#selectAlergiWrapper').hide();
            $('#selectKandunganWrapper').hide();
            $('#inputKataKunci').val('');
            $('#selectAlergi, #selectKandungan').val(null).trigger('change');
        }
    });
});
</script>
@endsection