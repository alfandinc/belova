@extends('layouts.erm.app')

@section('title', 'ERM | Surat Keterangan')
@section('navbar')
    @include('layouts.erm.navbardetail')
@endsection

@section('content')

@include('erm.partials.modal-alergipasien')
<!-- Modal Surat Istirahat -->
<div class="modal fade" id="modalSurat" tabindex="-1" role="dialog" aria-labelledby="modalSuratLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form id="formSurat">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalSuratLabel">
                        <i class="fas fa-file-medical"></i> Buat Surat Istirahat
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="pasien_id" value="{{ $pasien->id }}">
                    <div class="form-group mb-3">
                        <label class="form-label"><i class="fas fa-user-md"></i> Dokter</label>
                        <select id="dokter_id" name="dokter_id" class="form-control select2" required>
                            @foreach ($dokters as $dokter)
                                <option value="{{ $dokter->id }}"
                                    {{ $dokter->user_id == $dokterUserId ? 'selected' : '' }}>
                                    {{ $dokter->user->name ?? 'Tanpa Nama' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label"><i class="fas fa-calendar-alt"></i> Tanggal Mulai</label>
                                <input type="date" name="tanggal_mulai" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label"><i class="fas fa-calendar-alt"></i> Tanggal Selesai</label>
                                <input type="date" name="tanggal_selesai" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label"><i class="fas fa-calculator"></i> Jumlah Hari</label>
                        <input type="text" name="jumlah_hari" class="form-control" readonly>
                        <small class="form-text text-muted">Akan dihitung otomatis berdasarkan tanggal mulai dan selesai</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Surat Mondok -->
<div class="modal fade" id="modalSuratMondok" tabindex="-1" role="dialog" aria-labelledby="modalSuratMondokLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form id="formSuratMondok">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="modalSuratMondokLabel">
                        <i class="fas fa-hospital"></i> Buat Surat Mondok
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="pasien_id" value="{{ $pasien->id }}">
                    <div class="form-group mb-3">
                        <label class="form-label"><i class="fas fa-user-md"></i> Dokter</label>
                        <select id="dokter_id_mondok" name="dokter_id" class="form-control select2" required>
                            @foreach ($dokters as $dokter)
                                <option value="{{ $dokter->id }}"
                                    {{ $dokter->user_id == $dokterUserId ? 'selected' : '' }}>
                                    {{ $dokter->user->name ?? 'Tanpa Nama' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label"><i class="fas fa-hospital"></i> Tujuan IGD</label>
                        <input type="text" name="tujuan_igd" class="form-control" placeholder="Contoh: IGD RS Premier Bintaro" required>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label"><i class="fas fa-stethoscope"></i> Diagnosa</label>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small class="text-muted">Diagnosa dari asesmen penunjang</small>
                            <button type="button" class="btn btn-sm btn-info" id="btnAutoFill">
                                <i class="fas fa-magic"></i> Auto Fill
                            </button>
                        </div>
                        <textarea name="diagnosa" class="form-control" rows="3" placeholder="Masukkan diagnosa..." required></textarea>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label"><i class="fas fa-prescription"></i> Instruksi Terapi</label>
                        <textarea name="instruksi_terapi" class="form-control" rows="4" placeholder="Masukkan instruksi terapi..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="container-fluid">
    <div class="d-flex  align-items-center mb-0 mt-2">
        <h3 class="mb-0 mr-2">Surat Keterangan</h3>
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
                            <li class="breadcrumb-item active">Surat Keterangan</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->                                                              
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    <!-- end page title end breadcrumb -->    @include('erm.partials.card-identitaspasien')

    <div class="row">
        <!-- Surat Istirahat Card -->
        <div class="col-lg-6 col-12 mb-3">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">Daftar Surat Istirahat</h4>
                        <button class="btn btn-primary" data-toggle="modal" data-target="#modalSurat">
                            <i class="fas fa-plus"></i> Buat Surat
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="suratTable">

                            <thead class="table-dark">
                                <tr>
                                    <th>Dokter</th>
                                    <th>Spesialisasi</th>
                                    <th>Periode</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Surat Mondok Card -->
        <div class="col-lg-6 col-12 mb-3">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">Daftar Surat Mondok</h4>
                        <button class="btn btn-success" data-toggle="modal" data-target="#modalSuratMondok">
                            <i class="fas fa-plus"></i> Buat Surat
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="suratMondokTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>Dokter</th>
                                    <th>Spesialisasi</th>
                                    <th>Tujuan IGD</th>
                                    <th>Tanggal Dibuat</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        
        <!-- Surat Diagnosa Card -->
        <div class="col-lg-12 col-12 mb-3">
            <div class="card">
                <div class="card-header text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Surat Diagnosa</h5>
                    <button class="btn btn-info" data-toggle="modal" data-target="#modalSuratDiagnosa">
                        <i class="fas fa-plus"></i> Buat Surat Diagnosa
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="suratDiagnosaTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Dokter</th>
                                    <th>Spesialisasi</th>
                                    <th>Keterangan</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

<!-- Modal Surat Diagnosa -->
<div class="modal fade" id="modalSuratDiagnosa" tabindex="-1" role="dialog" aria-labelledby="modalSuratDiagnosaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form id="formSuratDiagnosa">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="modalSuratDiagnosaLabel">
                        <i class="fas fa-file-medical-alt"></i> Buat Surat Diagnosa
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="pasien_id" value="{{ $pasien->id }}">
                    <div class="form-group mb-3">
                        <label class="form-label"><i class="fas fa-user-md"></i> Dokter</label>
                        <select id="dokter_id_diagnosa" name="dokter_id" class="form-control select2" required>
                            @foreach ($dokters as $dokter)
                                <option value="{{ $dokter->id }}">{{ $dokter->user->name ?? 'Tanpa Nama' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label"><i class="fas fa-sticky-note"></i> Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="3" placeholder="Masukkan keterangan..." required></textarea>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label"><i class="fas fa-notes-medical"></i> Pilih Kunjungan</label>
                        <select name="visitation_id" class="form-control select2" required id="diagnosaKunjunganSelect">
                            @foreach($visitations as $visit)
                                <option value="{{ $visit->id }}" data-diagnosa="{{
                                    collect([
                                        $visit->asesmenPenunjang->diagnosakerja_1 ?? '',
                                        $visit->asesmenPenunjang->diagnosakerja_2 ?? '',
                                        $visit->asesmenPenunjang->diagnosakerja_3 ?? '',
                                        $visit->asesmenPenunjang->diagnosakerja_4 ?? '',
                                        $visit->asesmenPenunjang->diagnosakerja_5 ?? ''
                                    ])->filter()->implode('|')
                                }}">
                                    {{ $visit->tanggal_visitation }} - {{ $visit->dokter->user->name ?? '-' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label"><i class="fas fa-diagnoses"></i> Diagnosa Kerja</label>
                        <ul id="diagnosaKerjaList" class="list-group"></ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
    </div>

</div><!-- container -->


@endsection
@section('scripts')
<script>
$(document).ready(function() {
    // Initialize Surat Istirahat DataTable with AJAX
    let table = $('#suratTable').DataTable({
        responsive: true,
        autoWidth: false,
        processing: true,
        serverSide: false,
        scrollX: true,
        language: {
            processing: "Memuat data...",
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ data per halaman",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
            infoFiltered: "(disaring dari _MAX_ total data)",
            paginate: {
                first: "Pertama",
                last: "Terakhir",
                next: "Selanjutnya",
                previous: "Sebelumnya"
            },
            emptyTable: "Tidak ada data surat istirahat"
        },
        ajax: {
            url: '{{ route("erm.suratistirahat.index", $pasien->id) }}',
            type: 'GET',
        },
        columns: [
            { 
                data: 'dokter_name', 
                name: 'dokter_name',
                title: 'Dokter'
            },
            { 
                data: 'spesialisasi', 
                name: 'spesialisasi',
                title: 'Spesialisasi'
            },
            { 
                data: 'periode', 
                name: 'periode',
                title: 'Periode',
                className: 'text-center'
            },
            { 
                data: 'aksi', 
                name: 'aksi', 
                orderable: false, 
                searchable: false,
                title: 'Aksi',
                className: 'text-center'
            },
        ],
        columnDefs: [
            {
                targets: [2, 3], // Periode and Aksi columns
                className: 'text-center'
            }
        ]
    });

    // Initialize Surat Mondok DataTable with AJAX
    let tableMondok = $('#suratMondokTable').DataTable({
        responsive: true,
        autoWidth: false,
        processing: true,
        serverSide: false,
        scrollX: true,
        language: {
            processing: "Memuat data...",
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ data per halaman",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
            infoFiltered: "(disaring dari _MAX_ total data)",
            paginate: {
                first: "Pertama",
                last: "Terakhir",
                next: "Selanjutnya",
                previous: "Sebelumnya"
            },
            emptyTable: "Tidak ada data surat mondok"
        },
        ajax: {
            url: '{{ route("erm.suratmondok.data", $pasien->id) }}',
            type: 'GET',
        },
        columns: [
            { 
                data: 'dokter_name', 
                name: 'dokter_name',
                title: 'Dokter'
            },
            { 
                data: 'spesialisasi', 
                name: 'spesialisasi',
                title: 'Spesialisasi'
            },
            { 
                data: 'tujuan_igd_short', 
                name: 'tujuan_igd_short',
                title: 'Tujuan IGD'
            },
            { 
                data: 'tanggal_dibuat', 
                name: 'tanggal_dibuat',
                title: 'Tanggal Dibuat',
                className: 'text-center'
            },
            { 
                data: 'aksi', 
                name: 'aksi', 
                orderable: false, 
                searchable: false,
                title: 'Aksi',
                className: 'text-center'
            },
        ],
        columnDefs: [
            {
                targets: [3, 4], // Tanggal Dibuat and Aksi columns
                className: 'text-center'
            }
        ]
    });


    $('.select2').select2({ width: '100%' });

    // Initialize Surat Diagnosa DataTable with AJAX
    let tableDiagnosa = $('#suratDiagnosaTable').DataTable({
        responsive: true,
        autoWidth: false,
        processing: true,
        serverSide: false,
        scrollX: true,
        language: {
            processing: "Memuat data...",
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ data per halaman",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
            infoFiltered: "(disaring dari _MAX_ total data)",
            paginate: {
                first: "Pertama",
                last: "Terakhir",
                next: "Selanjutnya",
                previous: "Sebelumnya"
            },
            emptyTable: "Tidak ada data surat diagnosa"
        },
        ajax: {
            url: '/erm/riwayatkunjungan/{{ $pasien->id }}/get-data-diagnosis-table',
            type: 'GET',
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', title: 'No', orderable: false, searchable: false },
            { data: 'dokter', name: 'dokter', title: 'Dokter' },
            { data: 'spesialisasi', name: 'spesialisasi', title: 'Spesialisasi' },
            { data: 'keterangan', name: 'keterangan', title: 'Keterangan' },
            { data: 'tanggal', name: 'tanggal', title: 'Tanggal' },
            { data: 'aksi', name: 'aksi', orderable: false, searchable: false, title: 'Aksi', className: 'text-center' },
        ],
        columnDefs: [
            { targets: [0, 5], className: 'text-center' }
        ]
    });

    // Surat Diagnosa Form Submission
    // Diagnosa Kerja dynamic display
    $('#diagnosaKunjunganSelect').on('change', function() {
        var diagnosaStr = $(this).find('option:selected').data('diagnosa');
        var diagnosaArr = diagnosaStr ? diagnosaStr.split('|') : [];
        var html = '';
        if (diagnosaArr.length > 0) {
            diagnosaArr.forEach(function(d, i) {
                html += '<li class="list-group-item">' + (i+1) + '. ' + d + '</li>';
            });
        } else {
            html = '<li class="list-group-item text-muted">Tidak ada diagnosa kerja.</li>';
        }
        $('#diagnosaKerjaList').html(html);
    });
    // Trigger on page load for default selection
    $('#diagnosaKunjunganSelect').trigger('change');

    $('#formSuratDiagnosa').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '/erm/riwayatkunjungan/store-surat-diagnosis',
            method: 'POST',
            data: $(this).serialize(),
            success: function(data) {
                $('#modalSuratDiagnosa').modal('hide');
                $('#formSuratDiagnosa')[0].reset();
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Surat diagnosa berhasil dibuat.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#28a745'
                });
                tableDiagnosa.ajax.reload();
            },
            error: function(err) {
                let errorMessage = 'Terjadi kesalahan saat menyimpan data.';
                if (err.responseJSON && err.responseJSON.errors) {
                    const errors = Object.values(err.responseJSON.errors).flat();
                    errorMessage = errors.join('\n');
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: errorMessage,
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#d33'
                });
            }
        });
    });

    function countDays() {
        let mulai = $('input[name="tanggal_mulai"]').val();
        let selesai = $('input[name="tanggal_selesai"]').val();
        if (mulai && selesai) {
            let start = new Date(mulai);
            let end = new Date(selesai);
            let diff = (end - start) / (1000 * 60 * 60 * 24) + 1;
            $('input[name="jumlah_hari"]').val(diff);
            
            // Check if days exceed 6
            if (diff > 6) {
                // Disable submit button
                $('#formSurat button[type="submit"]').prop('disabled', true);
                $('#formSurat button[type="submit"]').addClass('btn-secondary').removeClass('btn-primary');
                
                Swal.fire({
                    icon: 'warning',
                    title: 'Peringatan!',
                    text: 'Maksimal jumlah hari istirahat adalah 6 hari. Silakan pilih tanggal yang sesuai.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#3085d6'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Reset tanggal selesai
                        $('input[name="tanggal_selesai"]').val('');
                        $('input[name="jumlah_hari"]').val('');
                        // Re-enable submit button
                        $('#formSurat button[type="submit"]').prop('disabled', false);
                        $('#formSurat button[type="submit"]').addClass('btn-primary').removeClass('btn-secondary');
                    }
                });
            } else if (diff > 0) {
                // Re-enable submit button if days are valid
                $('#formSurat button[type="submit"]').prop('disabled', false);
                $('#formSurat button[type="submit"]').addClass('btn-primary').removeClass('btn-secondary');
            }
        }
    }

    $('input[name="tanggal_mulai"], input[name="tanggal_selesai"]').on('change', countDays);

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Surat Istirahat Form Submission
    $('#formSurat').on('submit', function(e) {
        e.preventDefault();
        
        // Check if jumlah hari exceeds 6 before submitting
        let jumlahHari = parseInt($('input[name="jumlah_hari"]').val()) || 0;
        if (jumlahHari > 6 || jumlahHari <= 0) {
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: 'Maksimal jumlah hari istirahat adalah 6 hari dan minimal 1 hari. Silakan sesuaikan tanggal.',
                confirmButtonText: 'OK',
                confirmButtonColor: '#d33'
            });
            return false;
        }
        
        // Additional validation - check if fields are empty
        if (!$('input[name="tanggal_mulai"]').val() || !$('input[name="tanggal_selesai"]').val()) {
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: 'Tanggal mulai dan tanggal selesai harus diisi.',
                confirmButtonText: 'OK',
                confirmButtonColor: '#d33'
            });
            return false;
        }
        
        $.ajax({
            url: '{{ route("erm.suratistirahat.store") }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(data) {
                table.ajax.reload();
                $('#modalSurat').modal('hide');
                $('#formSurat')[0].reset();
                
                // Reset submit button state
                $('#formSurat button[type="submit"]').prop('disabled', false);
                $('#formSurat button[type="submit"]').addClass('btn-primary').removeClass('btn-secondary');
                
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Surat istirahat berhasil dibuat.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#28a745'
                });
            },
            error: function(err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: 'Terjadi kesalahan saat menyimpan data.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#d33'
                });
            }
        });
    });

    // Auto fill button functionality for Surat Mondok
    $('#btnAutoFill').on('click', function() {
        const visitationId = '{{ $visitation->id }}';
        
        $.ajax({
            url: '{{ route("erm.suratmondok.asesmen-data", ":visitation_id") }}'.replace(':visitation_id', visitationId),
            method: 'GET',
            success: function(data) {
                $('#formSuratMondok textarea[name="diagnosa"]').val(data.diagnosa);
                $('#formSuratMondok textarea[name="instruksi_terapi"]').val(data.instruksi_terapi);
                
                if (data.diagnosa || data.instruksi_terapi) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Data berhasil diambil dari asesmen penunjang.',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#28a745'
                    });
                } else {
                    Swal.fire({
                        icon: 'info',
                        title: 'Informasi',
                        text: 'Tidak ada data asesmen penunjang untuk kunjungan ini.',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#17a2b8'
                    });
                }
            },
            error: function(err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: 'Gagal mengambil data asesmen.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#d33'
                });
            }
        });
    });

    // Surat Mondok Form Submission
    $('#formSuratMondok').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: '{{ route("erm.suratmondok.store") }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(data) {
                tableMondok.ajax.reload();
                $('#modalSuratMondok').modal('hide');
                $('#formSuratMondok')[0].reset();
                
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Surat mondok berhasil dibuat.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#28a745'
                });
            },
            error: function(err) {
                let errorMessage = 'Terjadi kesalahan saat menyimpan data.';
                if (err.responseJSON && err.responseJSON.errors) {
                    const errors = Object.values(err.responseJSON.errors).flat();
                    errorMessage = errors.join('\n');
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: errorMessage,
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#d33'
                });
            }
        });
    });

    // Reset button state when modal is opened
    $('#modalSurat').on('show.bs.modal', function() {
        $('#formSurat button[type="submit"]').prop('disabled', false);
        $('#formSurat button[type="submit"]').addClass('btn-primary').removeClass('btn-secondary');
    });

    // Reset form when Surat Mondok modal is opened
    $('#modalSuratMondok').on('show.bs.modal', function() {
        $('#formSuratMondok')[0].reset();
        $('#dokter_id_mondok').trigger('change');
    });

    // Saat tombol modal alergi ditekan
    $('#btnBukaAlergi').on('click', function () {
        $('#modalAlergi').modal('show');
    });

    // Toggle semua bagian tergantung status
    var initialStatusAlergi = $('input[name="statusAlergi"]:checked').val();
    
    if (initialStatusAlergi === 'ada') {
        $('#inputKataKunciWrapper').show();
        $('#selectAlergiWrapper').show();
        $('#selectKandunganWrapper').show();
    } else {
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

