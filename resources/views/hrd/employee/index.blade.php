@extends('layouts.hrd.app')
@section('title', 'HRD | Daftar Karyawan')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="card shadow">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h3 class="card-title m-0 font-weight-bold text-primary">Daftar Karyawan</h3>
            <a href="{{ route('hrd.employee.create') }}" class="btn btn-primary">
                <i class="fas fa-plus mr-1"></i> Tambah Karyawan
            </a>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="filter-division">Filter Divisi:</label>
                    <select id="filter-division" class="form-control select2">
                        <option value="all">-- Semua Divisi --</option>
                        @foreach($divisions as $division)
                            <option value="{{ $division->id }}">{{ $division->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="filter-perusahaan">Filter Perusahaan:</label>
                    <select id="filter-perusahaan" class="form-control select2">
                        <option value="all">-- Semua Perusahaan --</option>
                        <option value="Klinik Utama Premiere Belova">Klinik Utama Premiere Belova</option>
                        <option value="Klinik Pratama Belova">Klinik Pratama Belova</option>
                        <option value="Belova Center Living">Belova Center Living</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="hide-inactive" checked>
                        <label class="form-check-label" for="hide-inactive">Sembunyikan Tidak Aktif</label>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table id="employees-table" class="table table-bordered table-hover table-striped">
                    <thead class="thead-light">
                        <tr>
                            <th width="8%">NIK</th>
                            <th width="8%">No Induk</th>
                            <th width="20%">Nama</th>
                            {{-- <th width="12%">Kategori Pegawai</th> --}}
                            <th width="15%">Posisi</th>
                            <th width="15%">Divisi</th>
                            <th width="10%">Status</th>
                            <th width="15%">Sisa Kontrak</th>
                            <th width="17%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- DataTables will populate this -->
                    </tbody>
                </table>
            </div>
            <!-- DataTables will handle pagination and info display automatically -->
        </div>
    </div>
</div>

<!-- Employee Detail Modal -->
<div class="modal fade" id="employeeDetailModal" tabindex="-1" role="dialog" aria-labelledby="employeeDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="employeeDetailModalLabel"><i class="fas fa-user mr-2"></i>Detail Karyawan</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <div class="text-center mb-3">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
                <div id="employeeDetailContent" style="display: none;">
                    <div class="row">
                        <div class="col-md-3 text-center mb-4">
                            <div id="employee-photo-container">
                                <!-- Photo will be loaded here -->
                            </div>
                            <h5 class="mt-3" id="employee-name">-</h5>
                            <p class="text-muted mb-0" id="employee-position">-</p>
                            <p class="text-muted" id="employee-division">-</p>
                        </div>
                        <div class="col-md-9">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <h5 class="border-bottom pb-2">Data Pribadi</h5>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <strong><i class="fas fa-id-card mr-1"></i>NIK:</strong>
                                    <p class="text-muted" id="employee-nik">-</p>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <strong><i class="fas fa-fingerprint mr-1"></i>No Induk:</strong>
                                    <p class="text-muted" id="employee-no_induk">-</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong><i class="fas fa-users mr-1"></i>Kategori Pegawai:</strong>
                                    <p class="text-muted" id="employee-kategori_pegawai">-</p>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <strong><i class="fas fa-map-marker-alt mr-1"></i>Tempat, Tanggal Lahir:</strong>
                                    <p class="text-muted" id="employee-ttl">-</p>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <strong><i class="fas fa-phone mr-1"></i>No HP:</strong>
                                    <p class="text-muted" id="employee-no_hp">-</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong><i class="fas fa-tint mr-1"></i>Gol. Darah:</strong>
                                    <p class="text-muted" id="employee-gol_darah">-</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong><i class="fas fa-user-shield mr-1"></i>No Darurat:</strong>
                                    <p class="text-muted" id="employee-no_darurat">-</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong><i class="fas fa-envelope mr-1"></i>Email:</strong>
                                    <p class="text-muted" id="employee-email">-</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong><i class="fab fa-instagram mr-1"></i>Instagram:</strong>
                                    <p class="text-muted" id="employee-instagram">-</p>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <strong><i class="fas fa-home mr-1"></i>Alamat:</strong>
                                    <p class="text-muted" id="employee-alamat">-</p>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <strong><i class="fas fa-graduation-cap mr-1"></i>Pendidikan:</strong>
                                    <p class="text-muted" id="employee-pendidikan">-</p>
                                </div>
                                
                                <div class="col-12 mt-3 mb-3">
                                    <h5 class="border-bottom pb-2">Data Kepegawaian</h5>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <strong><i class="fas fa-calendar-alt mr-1"></i>Tanggal Masuk:</strong>
                                    <p class="text-muted" id="employee-tanggal_masuk">-</p>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <strong><i class="fas fa-user-check mr-1"></i>Status:</strong>
                                    <p id="employee-status">-</p>
                                </div>
                                
                                <div class="col-md-6 mb-3 kontrak-info" style="display: none;">
                                    <strong><i class="fas fa-calendar-times mr-1"></i>Kontrak Berakhir:</strong>
                                    <p class="text-muted" id="employee-kontrak_berakhir">-</p>
                                </div>
                                
                                <div class="col-12 mt-3 mb-3">
                                    <h5 class="border-bottom pb-2">Dokumen</h5>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <strong><i class="fas fa-file-alt mr-1"></i>CV:</strong>
                                    <p id="employee-doc_cv">Tidak ada dokumen</p>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <strong><i class="fas fa-id-card-alt mr-1"></i>KTP:</strong>
                                    <p id="employee-doc_ktp">Tidak ada dokumen</p>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <strong><i class="fas fa-file-contract mr-1"></i>Kontrak:</strong>
                                    <p id="employee-doc_kontrak">Tidak ada dokumen</p>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <strong><i class="fas fa-file-invoice mr-1"></i>Dokumen Pendukung:</strong>
                                    <p id="employee-doc_pendukung">Tidak ada dokumen</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" class="btn btn-primary" id="edit-employee-btn">
                    <i class="fas fa-edit mr-1"></i>Edit
                </a>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    #employees-table th, #employees-table td {
        vertical-align: middle;
    }
    
    .table-action-btn {
        margin: 0 3px;
    }
    
    /* Status badge styles that match the enum values in the database */
    .badge-tetap {
        background-color: #28a745;  /* success green */
    }
    
    .badge-kontrak {
        background-color: #ffc107;  /* warning yellow */
    }
    
    .badge-tidak-aktif {
        background-color: #dc3545;  /* danger red */
    }
    
    /* Sisa Kontrak badge styles */
    .badge-info {
        background-color: #17a2b8;
        color: #fff;
    }
    
    .badge-warning {
        color: #212529;
    }
    
    @media (max-width: 767px) {
        #employees-table {
            min-width: 800px;
        }
        
        .card-header {
            flex-direction: column;
            align-items: start !important;
        }
        
        .card-header .btn {
            margin-top: 10px;
            align-self: flex-start;
        }
    }
</style>
@endpush

@section('scripts')
<script>
$(function() {
    // Initialize select2 for division and perusahaan filter
    $('#filter-division').select2({
        width: '100%',
        placeholder: '-- Semua Divisi --',
        allowClear: true
    });
    $('#filter-perusahaan').select2({
        width: '100%',
        placeholder: '-- Semua Perusahaan --',
        allowClear: true
    });
    // Initialize tooltips
    $('body').tooltip({
        selector: '[data-toggle="tooltip"]',
        container: 'body'
    });
    
    var table = $('#employees-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        dom: '<"top"fl>rt<"bottom"ip><"clear">',
        order: [[6, 'asc']], // Order by the 7th column (Sisa Kontrak) in ascending order
        language: {
            processing: '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span>',
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ entri",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
            infoEmpty: "Menampilkan 0 sampai 0 dari 0 entri",
            infoFiltered: "(disaring dari _MAX_ entri keseluruhan)",
            paginate: {
                previous: '<i class="fas fa-chevron-left"></i>',
                next: '<i class="fas fa-chevron-right"></i>'
            },
            emptyTable: 'Tidak ada data yang tersedia'
        },
        ajax: {
            url: "{{ route('hrd.employee.index') }}",
            data: function(d) {
                // (debug logging removed)
                var divVal = $('#filter-division').val();
                d.division_id = (divVal === 'all' ? '' : divVal);
                var perVal = $('#filter-perusahaan').val();
                d.perusahaan = (perVal === 'all' ? '' : perVal);
                    var hideVal = $('#hide-inactive').is(':checked') ? 1 : '';
                    d.hide_inactive = hideVal;
            },
            error: function (xhr, error, thrown) {
                console.error('DataTables error:', error, thrown);
                console.log('Response:', xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Gagal memuat data. Silakan coba lagi.'
                });
            }
        },
        columns: [
            {data: 'nik', name: 'nik', defaultContent: '-'},
            {data: 'no_induk', name: 'hrd_employee.no_induk', defaultContent: '-', orderable: true},
            {data: 'nama', name: 'nama', defaultContent: '-'},
            {data: 'position.name', name: 'position.name', defaultContent: '-'},
            {data: 'division.name', name: 'division.name', defaultContent: '-'},
            {
                data: 'status', 
                name: 'status', 
                defaultContent: '-',
                searchable: false,
                render: function(data, type, row) {
                    var statusColors = {
                        'tetap': 'success',
                        'kontrak': 'warning',
                        'tidak aktif': 'danger'
                    };
                    
                    var status = data || 'tidak aktif';
                    var statusColor = statusColors[status] || 'secondary';
                    
                    return '<span class="badge badge-pill badge-' + statusColor + '">' + 
                           (status.charAt(0).toUpperCase() + status.slice(1)) + '</span>';
                }
            },
            {
                data: 'kontrak_berakhir', 
                name: 'kontrak_berakhir',
                defaultContent: '-',
                orderable: true,
                type: 'date',
                render: function(data, type, row) {
                    // For employees with status other than 'kontrak', just show a dash
                    if (row.status !== 'kontrak' || !row.kontrak_berakhir) {
                        return '-';
                    }
                    
                    // Calculate days remaining in contract
                    var today = new Date();
                    var endDate = new Date(row.kontrak_berakhir);
                    
                    // Reset time part for accurate day calculation
                    today.setHours(0, 0, 0, 0);
                    endDate.setHours(0, 0, 0, 0);
                    
                    var diffTime = endDate - today;
                    var diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                    
                    // Format the end date for tooltip display
                    var formattedEndDate = endDate.toLocaleDateString('id-ID', {
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric'
                    });
                    
                    // Calculate years, months, and days
                    var formattedDuration = '';
                    var textColor = '';
                    
                    if (diffDays < 0) {
                        textColor = 'text-danger';
                        formattedDuration = 'Kontrak Berakhir';
                    } else if (diffDays === 0) {
                        textColor = 'text-warning';
                        formattedDuration = 'Berakhir Hari Ini';
                    } else {
                        // Calculate years, months, and days more accurately
                        var years = 0;
                        var months = 0;
                        var days = diffDays;
                        
                        // Calculate years
                        if (days >= 365) {
                            years = Math.floor(days / 365);
                            days = days % 365;
                        }
                        
                        // Calculate months
                        if (days >= 30) {
                            months = Math.floor(days / 30);
                            days = days % 30;
                        }
                        
                        // Format the duration string
                        var parts = [];
                        
                        if (years > 0) {
                            parts.push(years + ' thn');
                        }
                        
                        if (months > 0) {
                            parts.push(months + ' bln');
                        }
                        
                        if (days > 0 || parts.length === 0) {
                            parts.push(days + ' hari');
                        }
                        
                        formattedDuration = parts.join(' ');
                        textColor = diffDays <= 30 ? 'text-warning' : '';
                    }
                    
                    return '<div class="' + textColor + '" data-toggle="tooltip" title="Berakhir pada: ' + 
                           formattedEndDate + '">' + formattedDuration + '</div>';
                }
            },
            {
                data: 'action', 
                name: 'action', 
                orderable: false, 
                searchable: false,
                render: function(data, type, row) {
                    return `
                        <div class="btn-group">                            
                            <button type="button" class="btn btn-sm btn-info show-employee table-action-btn" data-id="${row.id}" title="View">
                                <i class="fas fa-eye"></i>
                            </button>
                            <a href="{{ url('/hrd/employee') }}/${row.id}/edit" class="btn btn-sm btn-primary table-action-btn" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="{{ url('/hrd/employee') }}/${row.id}/contracts" class="btn btn-sm btn-info table-action-btn" title="Kontrak">
                                <i class="fas fa-file-contract"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-danger delete-employee table-action-btn" data-id="${row.id}" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        // Ensure DataTables sends explicit DB column names for server-side ordering
        columnDefs: [
            { targets: 0, name: 'hrd_employee.nik' },
            { targets: 1, name: 'hrd_employee.no_induk' },
            { targets: 2, name: 'hrd_employee.nama' },
            { targets: 6, name: 'hrd_employee.kontrak_berakhir' }
        ],
        drawCallback: function() {
            // Reinitialize tooltips after each table draw
            $('[data-toggle="tooltip"]').tooltip();
        }
    });
    // Reload table when hide-inactive checkbox changes
    $('#hide-inactive').on('change', function() {
        table.ajax.reload();
    });
    // Filter by division and perusahaan
    $('#filter-division, #filter-perusahaan').on('change', function() {
        table.ajax.reload();
    });
    
    // DataTables built-in search and pagination are now used
    
    // Handle delete button with SweetAlert
    $('#employees-table').on('click', '.delete-employee', function() {
        var employeeId = $(this).data('id');
        
        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: "Data karyawan akan dihapus!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.value) {
                $.ajax({
                    url: '/hrd/employee/' + employeeId,
                    type: 'DELETE',
                    data: {
                        "_token": "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Terhapus!',
                                text: response.message,
                                timer: 2000,
                                timerProgressBar: true
                            });
                            table.ajax.reload();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: response.message
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Terjadi kesalahan saat menghapus data'
                        });
                    }
                });
            }
        });
    });
    
    // Show success message if exists
    @if(session('success'))
    Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: '{{ session('success') }}',
        timer: 3000,
        timerProgressBar: true
    });
    @endif
    
    // Handle employee detail modal
    $('#employees-table').on('click', '.show-employee', function() {
        var employeeId = $(this).data('id');
        
        // Show loading spinner
        $('#employeeDetailContent').hide();
        $('.spinner-border').show();
        $('#employeeDetailModal').modal('show');
        
        // Set edit button URL
        $('#edit-employee-btn').attr('href', '{{ url("/hrd/employee") }}/' + employeeId + '/edit');
        
        // Fetch employee data via AJAX
        $.ajax({
            url: '/hrd/employee/' + employeeId + '/get-details',
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    var employee = response.data;
                    
                    // Set employee basic info
                    $('#employee-name').text(employee.nama || '-');
                    $('#employee-position').text((employee.position && employee.position.name) ? employee.position.name : '-');
                    $('#employee-division').text((employee.division && employee.division.name) ? employee.division.name : '-');
                    $('#employee-nik').text(employee.nik || '-');
                    $('#employee-no_induk').text(employee.no_induk || '-');
                    
                    // Format birthdate
                    var ttl = employee.tempat_lahir || '-';
                    if (employee.tanggal_lahir) {
                        var birthDate = new Date(employee.tanggal_lahir);
                        var formattedBirthDate = new Intl.DateTimeFormat('id-ID', {
                            day: '2-digit', 
                            month: 'short', 
                            year: 'numeric'
                        }).format(birthDate);
                        ttl += ', ' + formattedBirthDate;
                    }
                    $('#employee-ttl').text(ttl);
                    
                    $('#employee-no_hp').text(employee.no_hp || '-');
                    $('#employee-gol_darah').text(employee.gol_darah || '-');
                    $('#employee-no_darurat').text(employee.no_darurat || '-');
                    $('#employee-email').text(employee.email || '-');
                    // Show Instagram as a list if array or JSON string
                    var instaHtml = '-';
                    if (employee.instagram) {
                        var instagrams = employee.instagram;
                        if (typeof instagrams === 'string') {
                            try {
                                instagrams = JSON.parse(instagrams);
                            } catch (e) {
                                instagrams = [instagrams];
                            }
                        }
                        if (Array.isArray(instagrams)) {
                            instagrams = instagrams.filter(function(i) { return i && i !== 'null'; });
                            if (instagrams.length > 0) {
                                instaHtml = '<ul class="mb-0">';
                                instagrams.forEach(function(i) {
                                    instaHtml += '<li>@' + i + '</li>';
                                });
                                instaHtml += '</ul>';
                            }
                        } else if (instagrams) {
                            instaHtml = '@' + instagrams;
                        }
                    }
                    $('#employee-instagram').html(instaHtml);
                    $('#employee-alamat').text(employee.alamat || '-');
                    $('#employee-pendidikan').text(employee.pendidikan || '-');
                    
                    // Format join date
                    if (employee.tanggal_masuk) {
                        var joinDate = new Date(employee.tanggal_masuk);
                        var formattedJoinDate = new Intl.DateTimeFormat('id-ID', {
                            day: '2-digit', 
                            month: 'short', 
                            year: 'numeric'
                        }).format(joinDate);
                        $('#employee-tanggal_masuk').text(formattedJoinDate);
                    } else {
                        $('#employee-tanggal_masuk').text('-');
                    }
                    
                    // Set status with badge
                    var statusColors = {
                        'tetap': 'success',
                        'kontrak': 'warning',
                        'tidak aktif': 'danger'
                    };
                    var status = employee.status || 'tidak aktif';
                    var badgeHtml = `<span class="badge badge-pill badge-${statusColors[status] || 'secondary'}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`;
                    $('#employee-status').html(badgeHtml);
                    
                    // Show contract end date if status is contract
                    if (status === 'kontrak' && employee.kontrak_berakhir) {
                        $('.kontrak-info').show();
                        var contractEndDate = new Date(employee.kontrak_berakhir);
                        var formattedContractEndDate = new Intl.DateTimeFormat('id-ID', {
                            day: '2-digit', 
                            month: 'short', 
                            year: 'numeric'
                        }).format(contractEndDate);
                        $('#employee-kontrak_berakhir').text(formattedContractEndDate);
                    } else {
                        $('.kontrak-info').hide();
                    }
                    
                    // Set photo
                    if (employee.photo) {
                        $('#employee-photo-container').html(`<img src="{{ asset('storage') }}/${employee.photo}" alt="${employee.nama}" class="img-thumbnail rounded-circle" style="width: 180px; height: 180px; object-fit: cover;">`);
                    } else {
                        $('#employee-photo-container').html(`<div class="bg-light rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 180px; height: 180px;"><i class="fas fa-user-tie fa-6x text-secondary"></i></div>`);
                    }
                    
                    // Set documents
                    var documentTypes = ['cv', 'ktp', 'kontrak', 'pendukung'];
                    var docNames = {
                        'cv': 'CV',
                        'ktp': 'KTP',
                        'kontrak': 'Kontrak',
                        'pendukung': 'Dokumen Pendukung'
                    };
                    
                    documentTypes.forEach(function(docType) {
                        if (employee['doc_' + docType]) {
                            // Create the full URL to the document
                            let storageUrl = "{{ asset('storage') }}/";
                            let documentPath = employee['doc_' + docType];
                            
                            $(`#employee-doc_${docType}`).html(`<a href="${storageUrl}${documentPath}" target="_blank" class="btn btn-sm btn-info"><i class="fas fa-download mr-1"></i>Download ${docNames[docType]}</a>`);
                        } else {
                            $(`#employee-doc_${docType}`).text('Tidak ada dokumen');
                        }
                    });
                    
                    // Hide spinner and show content
                    $('.spinner-border').hide();
                    $('#employeeDetailContent').show();
                } else {
                    $('#employeeDetailModal').modal('hide');
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to load employee details'
                    });
                }
            },
            error: function() {
                $('#employeeDetailModal').modal('hide');
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load employee details'
                });
            }
        });
    });
});
</script>
@endsection