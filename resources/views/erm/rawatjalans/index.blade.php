@extends('layouts.erm.app')
@section('title', 'ERM | Rawat Jalan')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection  

@section('content')

<style>
/* Status Pasien Icons in DataTable */
.dataTables_wrapper .status-pasien-icon,
.dataTables_wrapper .status-akses-icon {
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    vertical-align: middle;
    margin-right: 8px;
}

.dataTables_wrapper td {
    vertical-align: middle;
}
</style>

@include('erm.partials.modal-reschedule')
<div class="modal fade" id="modalKonfirmasi" tabindex="-1" role="dialog" aria-labelledby="modalKonfirmasiTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalKonfirmasiTitle">Konfirmasi Kunjungan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="konfirmasi-nama-pasien">Nama Pasien</label>
                    <input type="text" class="form-control" id="konfirmasi-nama-pasien" readonly>
                </div>
                <div class="form-group">
                    <label for="konfirmasi-no-telepon">Nomor Telepon</label>
                    <input type="text" class="form-control" id="konfirmasi-no-telepon">
                </div>
                <div class="form-group">
                    <label for="konfirmasi-pesan">Template Pesan</label>
                    <textarea class="form-control" id="konfirmasi-pesan" rows="5">Halo %PANGGILAN% %NAMA_PASIEN%, 

Kami ingin mengingatkan jadwal kunjungan Anda di Klinik Belova:
Tanggal: %TANGGAL_KUNJUNGAN%
Dokter: %DOKTER%
Nomor Antrian: %NO_ANTRIAN%

Mohon konfirmasi kehadiran Anda. 
Terima kasih.
</textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" id="btn-kirim-wa">Kirim WhatsApp</button>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                            <li class="breadcrumb-item active">Rawat Jalan</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->                                                              
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    <!-- end page title end breadcrumb -->

    <div class="card">
        <div class="card-header bg-primary">
            <h4 class="card-title text-white">Daftar Kunjungan Rawat Jalan</h4>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="filter_tanggal">Periode Tanggal Kunjungan</label>
                    <div class="input-group">
                        <input type="text" id="filter_tanggal" class="form-control" placeholder="Pilih Rentang Tanggal">
                        <div class="input-group-append">
                            <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                        </div>
                    </div>
                </div>
                @if ($role !== 'Dokter')
                <div class="col-md-4">
                    <label for="filter_dokter">Filter Dokter</label>
                    <select id="filter_dokter" class="form-control select2">
                        <option value="">Semua Dokter</option>
                        @foreach($dokters as $dokter)
                            <option value="{{ $dokter->id }}">{{ $dokter->user->name }} - {{ $dokter->spesialisasi->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="filter_klinik">Filter Klinik</label>
                    <select id="filter_klinik" class="form-control select2">
                        <option value="">Semua Klinik</option>
                        @foreach($kliniks as $klinik)
                            <option value="{{ $klinik->id }}">{{ $klinik->nama }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>
            <table class="table table-bordered w-100" id="rawatjalan-table">
                <thead>
                    <tr>
                        <th>
                            @if ($role === 'Dokter')
                                No
                            @else
                                Antrian
                            @endif
                        </th>
                        <th>No RM</th>
                        <th>Nama Pasien</th>
                        <th>Tanggal Kunjungan</th>
                        <th>Waktu Kunjungan</th> <!-- Add header for waktu_kunjungan -->
                        <th>Spesialisasi</th>
                        <th>Dokter</th>
                        <th>Selesai Asesmen</th>
                        <th>Metode Bayar</th>
                        <th>Dokumen</th>
                    </tr>
                </thead>
            </table>
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
    // Initialize daterangepicker
    $('#filter_tanggal').daterangepicker({
        locale: {
            format: 'DD-MM-YYYY',
            separator: ' s/d ',
            applyLabel: 'Pilih',
            cancelLabel: 'Batal',
            fromLabel: 'Dari',
            toLabel: 'Hingga',
            customRangeLabel: 'Kustom',
            weekLabel: 'M',
            daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
            monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
            firstDay: 1
        },
        opens: 'left',
        autoUpdateInput: false
    });
    
    // Set default value to today
    var today = moment().format('DD-MM-YYYY');
    $('#filter_tanggal').val(today + ' s/d ' + today);
    
    // Handle apply event
    $('#filter_tanggal').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('DD-MM-YYYY') + ' s/d ' + picker.endDate.format('DD-MM-YYYY'));
        table.ajax.reload();
    });
    
    // Handle cancel event
    $('#filter_tanggal').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
        table.ajax.reload();
    });

    $.fn.dataTable.ext.order['antrian-number'] = function(settings, col) {
        return this.api().column(col, {order: 'index'}).nodes().map(function(td, i) {
            return parseInt($('span', td).data('order')) || 0;
        });
    };
var userRole = "{{ $role }}";
    let table = $('#rawatjalan-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        pageLength: 50, // Set default rows per page to 50
        ajax: {
            url: '{{ route("erm.rawatjalans.index") }}',
            data: function(d) {
                var dateRange = $('#filter_tanggal').val().split(' s/d ');
                d.start_date = dateRange[0] ? moment(dateRange[0], 'DD-MM-YYYY').format('YYYY-MM-DD') : '';
                d.end_date = dateRange[1] ? moment(dateRange[1], 'DD-MM-YYYY').format('YYYY-MM-DD') : '';
                d.dokter_id = $('#filter_dokter').val();
                d.klinik_id = $('#filter_klinik').val();
            }
        },
        order: [[3, 'asc'], [0, 'asc']], // Tanggal ASC, Antrian ASC
        columns: [
            { 
                data: 'antrian', 
                name: 'no_antrian', 
                searchable: true, 
                orderable: true,
                render: function(data, type, row, meta) {
                    if (userRole === 'Dokter') {
                        return meta.row + 1;
                    } else {
                        return data;
                    }
                }
            },
            { data: 'no_rm', name: 'no_rm', searchable: true, orderable: false },
            { data: 'nama_pasien', name: 'nama_pasien', searchable: true, orderable: false },
            { data: 'tanggal', name: 'tanggal_visitation', searchable: true },
            { data: 'waktu_kunjungan', name: 'waktu_kunjungan', searchable: false, orderable: false }, // Add waktu_kunjungan column
            { data: 'spesialisasi', name: 'spesialisasi', searchable: false, orderable: false },
            { data: 'dokter_nama', name: 'dokter_nama', searchable: false, orderable: false },
            { data: 'selesai_asesmen', name: 'selesai_asesmen', searchable: false, orderable: false },
            { data: 'metode_bayar', name: 'metode_bayar', searchable: true, orderable: false },
            { data: 'dokumen', name: 'dokumen', searchable: false, orderable: false },
        ],
        columnDefs: [
            { targets: 0, width: "8%" }, // Antrian
            { targets: 6, width: "15%" }, // Dokumen
            { targets: 9, width: "15%" }, // Dokumen
        ],
        createdRow: function(row, data, dataIndex) {
    if (data.status_kunjungan == 2) {
        $(row).css('color', 'orange'); 
    } else if (data.status_kunjungan == 1 && userRole === 'Perawat') {
        $(row).css('color', 'yellow');
    }
    // No color change for status_kunjungan == 1 and userRole === 'Dokter'
}
    });

    $('#filter_dokter, #filter_klinik').on('change', function () {
        table.ajax.reload();
    });

    // ambil no antrian otomatis
    $('#reschedule-dokter-id, #reschedule-tanggal-visitation').on('change', function() {
        let dokterId = $('#reschedule-dokter-id').val();
        let tanggal = $('#reschedule-tanggal-visitation').val();

        if (dokterId && tanggal) {
            $.get('{{ route("erm.rawatjalans.cekAntrian") }}', { dokter_id: dokterId, tanggal: tanggal }, function(res) {
                $('#reschedule-no-antrian').val(res.no_antrian);
            });
        }
    });

    // submit form reschedule
    $('#form-reschedule').submit(function(e) {
        e.preventDefault();

        $.ajax({
            url: '{{ route("erm.rawatjalans.store") }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                $('#modalReschedule').modal('hide');
                $('#rawatjalan-table').DataTable().ajax.reload();
                alert(res.message);
            },
            error: function(xhr) {
                alert('Terjadi kesalahan!');
            }
        });
    });
    $('#btn-kirim-wa').click(function() {
    let phoneNumber = $('#konfirmasi-no-telepon').val().replace(/\D/g, '');
    
    // Convert phone number format if needed (0 → 62)
    if (phoneNumber.startsWith('0')) {
        phoneNumber = '62' + phoneNumber.substring(1);
    }
    // Make sure it starts with 62 if not already
    else if (!phoneNumber.startsWith('62')) {
        phoneNumber = '62' + phoneNumber;
    }
    
    const message = encodeURIComponent($('#konfirmasi-pesan').val());
    
    if (phoneNumber) {
        // Open WhatsApp with the message in a new tab
        window.open(`https://wa.me/${phoneNumber}?text=${message}`, '_blank');
        $('#modalKonfirmasi').modal('hide');
    } else {
        alert('Nomor telepon tidak valid');
    }
});


});

// Batalkan Kunjungan
function batalkanKunjungan(visitationId, btn) {
    Swal.fire({
        title: 'Batalkan Kunjungan?',
        text: 'Status kunjungan akan diubah menjadi dibatalkan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Batalkan',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.value) {
            $.ajax({
                url: '/erm/rawatjalans/batalkan',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    visitation_id: visitationId
                },
                success: function(res) {
                    // Remove row from datatable
                    $('#rawatjalan-table').DataTable().ajax.reload();
                    Swal.fire('Dibatalkan!', 'Kunjungan berhasil dibatalkan.', 'success');
                },
                error: function() {
                    Swal.fire('Gagal', 'Terjadi kesalahan.', 'error');
                }
            });
        }
    });
}

// Edit Antrian
function editAntrian(visitationId, currentAntrian, currentWaktuKunjungan = null) {
    Swal.fire({
        title: 'Edit Nomor Antrian & Waktu Kunjungan',
        html: `<input id="swal-input1" class="swal2-input" type="number" min="1" value="${currentAntrian}" placeholder="Nomor Antrian">
               <input id="swal-input2" class="swal2-input" type="time" value="${currentWaktuKunjungan ? currentWaktuKunjungan : ''}" placeholder="Waktu Kunjungan (opsional)">`,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Simpan',
        cancelButtonText: 'Batal',
        preConfirm: () => {
            const noAntrian = document.getElementById('swal-input1').value;
            const waktuKunjungan = document.getElementById('swal-input2').value;
            if (!noAntrian || noAntrian < 1) {
                Swal.showValidationMessage('Nomor antrian tidak valid');
                return false;
            }
            return { noAntrian, waktuKunjungan };
        }
    }).then((result) => {
        if (result.value) {
            $.ajax({
                url: '/erm/rawatjalans/edit-antrian',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    visitation_id: visitationId,
                    no_antrian: result.value.noAntrian,
                    waktu_kunjungan: result.value.waktuKunjungan
                },
                success: function(res) {
                    $('#rawatjalan-table').DataTable().ajax.reload();
                    Swal.fire('Berhasil', 'Nomor antrian & waktu kunjungan berhasil diubah.', 'success');
                },
                error: function() {
                    Swal.fire('Gagal', 'Terjadi kesalahan.', 'error');
                }
            });
        }
    });
}

function openRescheduleModal(visitationId, namaPasien, pasienId) {
    $('#modalReschedule').modal('show');
    $('#reschedule-visitation-id').val(visitationId);
    $('#reschedule-pasien-id').val(pasienId);
    $('#reschedule-nama-pasien').val(namaPasien);
}

function openKonfirmasiModal(namaPasien, telepon, dokterNama, tanggalKunjungan, noAntrian, gender, tanggalLahir) {
    $('#konfirmasi-nama-pasien').val(namaPasien);
    $('#konfirmasi-no-telepon').val(telepon);
    
    // Calculate age based on tanggal_lahir
    let age = 0;
    if (tanggalLahir) {
        const birthDate = new Date(tanggalLahir);
        const today = new Date();
        age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
    }
    
    // Determine the appropriate honorific based on gender and age
    let honorific = '';
    if (age < 17) {
        honorific = 'Adik';
    } else if (age < 30) {
        honorific = 'Kakak';
    } else if (gender === 'Laki-laki' || gender === 'L' || gender === 'M') {
        honorific = 'Bapak';
    } else if (gender === 'Perempuan' || gender === 'P' || gender === 'F') {
        honorific = 'Ibu';
    } else {
        honorific = 'Bapak/Ibu'; // Default if gender is unknown
    }
    
    // Format template message with patient data
    const templateMessage = $('#konfirmasi-pesan').val()
        .replace('%NAMA_PASIEN%', namaPasien)
        .replace('%PANGGILAN%', honorific)
        .replace('%TANGGAL_KUNJUNGAN%', tanggalKunjungan)
        .replace('%DOKTER%', dokterNama)
        .replace('%NO_ANTRIAN%', noAntrian);
    
    $('#konfirmasi-pesan').val(templateMessage);
    $('#modalKonfirmasi').modal('show');
}
</script>


@endsection
