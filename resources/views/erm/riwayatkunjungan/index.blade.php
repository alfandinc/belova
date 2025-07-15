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
    $('#riwayat-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('erm.riwayatkunjungan.index', $pasien) }}',
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