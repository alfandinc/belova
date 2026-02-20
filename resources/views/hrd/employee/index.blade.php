@extends('layouts.hrd.app')
@section('title', 'HRD | Daftar Karyawan')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-2">
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h3 class="mb-0 font-weight-bold">Data Karyawan</h3>
                <div class="text-muted small">Kelola data karyawan, filter berdasarkan divisi, perusahaan, dan status aktif.</div>
                <!-- Stats summary for employees (simple inline) -->
                <div id="employee-stats" class="mt-3">
                    <div id="employee-stats-items" class="d-flex flex-wrap align-items-center" style="gap:12px;">
                        <div class="mr-4 mb-1"><span class="badge badge-warning">Kontrak</span><span class="stat-colon" style="font-weight:400;margin:0 10px;color:inherit;">:</span><strong id="stat-kontrak">0</strong></div>
                        <div class="mr-4 mb-1"><span class="badge badge-success">Tetap</span><span class="stat-colon" style="font-weight:400;margin:0 10px;color:inherit;">:</span><strong id="stat-tetap">0</strong></div>
                        <div class="mr-4 mb-1"><span class="badge badge-info">Freelance</span><span class="stat-colon" style="font-weight:400;margin:0 10px;color:inherit;">:</span><strong id="stat-freelance">0</strong></div>
                        <div class="mr-4 mb-1"><span class="badge badge-secondary">Rata-rata Usia</span><span class="stat-colon" style="font-weight:400;margin:0 10px;color:inherit;">:</span><strong id="stat-usia">-</strong></div>
                        <div class="mr-4 mb-1"><span class="badge badge-primary">Laki-laki</span><span class="stat-colon" style="font-weight:400;margin:0 10px;color:inherit;">:</span><strong id="stat-male">0</strong></div>
                        <div class="mr-4 mb-1"><span class="badge" style="background:#e83e8c;color:#fff;">Perempuan</span><span class="stat-colon" style="font-weight:400;margin:0 10px;color:inherit;">:</span><strong id="stat-female">0</strong></div>
                    </div>
                </div>
            </div>
            <div class="mt-2 mt-sm-0">
                <a href="{{ route('hrd.employee.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus mr-1"></i> Tambah Karyawan
                </a>
            </div>
        </div>
    </div>

    <!-- Filter toolbar (will be moved next to DataTables search box) -->
    <div id="employeeToolbarHolder" class="d-none">
        <select id="filter-division" class="form-control form-control-sm mr-2" style="width: 200px; max-width: 100%;">
            <option value="all">-- Semua Divisi --</option>
            @foreach($divisions as $division)
                <option value="{{ $division->id }}">{{ $division->name }}</option>
            @endforeach
        </select>
        <select id="filter-perusahaan" class="form-control form-control-sm mr-2" style="width: 220px; max-width: 100%;">
            <option value="all">-- Semua Perusahaan --</option>
            <option value="Klinik Utama Premiere Belova">Klinik Utama Premiere Belova</option>
            <option value="Klinik Pratama Belova">Klinik Pratama Belova</option>
            <option value="Belova Center Living">Belova Center Living</option>
        </select>
        <select id="filter-status" class="form-control form-control-sm mr-2" style="width: 200px; max-width: 100%;">
            <option value="active" selected>Hanya Karyawan Aktif</option>
            <option value="all">Semua Status</option>
            <option value="inactive">Hanya Tidak Aktif</option>
        </select>
    </div>

    <div class="card shadow">
        <div class="card-body">
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
        // Show all entries by default and keep a length menu including 'Semua'
        pageLength: -1,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Semua']],
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
                var statusVal = $('#filter-status').val();
                d.status_filter = statusVal;
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
            {data: 'nik', name: 'nik', defaultContent: '-', visible: false},
            {data: 'no_induk', name: 'hrd_employee.no_induk', defaultContent: '-', orderable: true},
            {
                data: 'nama',
                name: 'nama',
                defaultContent: '-',
                render: function(data, type, row) {
                    var nama = data || '-';
                    var nik = row.nik || '-';
                    return '<div class="font-weight-bold"><a href="#" class="show-employee text-dark" data-id="' + row.id + '">' + nama + '</a></div>' +
                           '<div class="text-muted small">NIK: ' + nik + '</div>';
                }
            },
            {
                data: 'position.name',
                name: 'position.name',
                defaultContent: '-',
                render: function(data, type, row) {
                    var positionName = data || '-';
                    var divisionName = (row.division && row.division.name) ? row.division.name : '-';

                    var dropdown = '' +
                        '<div class="dropdown ml-2 position-dropdown-wrapper">' +
                            '<button class="btn btn-sm btn-light p-1 position-change-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-id="' + row.id + '" data-division-id="' + (row.division && row.division.id ? row.division.id : '') + '" title="Ubah posisi">' +
                                '<i class="fas fa-ellipsis-v"></i>' +
                            '</button>' +
                            '<div class="dropdown-menu dropdown-menu-right p-0" id="position-menu-' + row.id + '">' +
                                '<div class="px-3 py-2 text-muted small">Memuat...</div>' +
                            '</div>' +
                        '</div>';

                    return '<div class="d-flex justify-content-between align-items-center">' +
                                '<div>' + positionName + '<div class="text-muted small">' + divisionName + '</div></div>' +
                                dropdown +
                           '</div>';
                }
            },
            {data: 'division.name', name: 'division.name', defaultContent: '-', visible: false},
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

                    var badgeHtml = '<span class="badge badge-pill badge-' + statusColor + '">' + 
                                    (status.charAt(0).toUpperCase() + status.slice(1)) + '</span>';

                    var kontrakInfoHtml = '';

                    if (status === 'kontrak' && row.kontrak_berakhir) {
                        var today = new Date();
                        var endDate = new Date(row.kontrak_berakhir);

                        today.setHours(0, 0, 0, 0);
                        endDate.setHours(0, 0, 0, 0);

                        var diffTime = endDate - today;
                        var diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

                        var formattedEndDate = endDate.toLocaleDateString('id-ID', {
                            day: '2-digit',
                            month: 'short',
                            year: 'numeric'
                        });

                        var formattedDuration = '';
                        var textColor = '';

                        if (diffDays < 0) {
                            textColor = 'text-danger';
                            formattedDuration = 'Kontrak Berakhir';
                        } else if (diffDays === 0) {
                            textColor = 'text-warning';
                            formattedDuration = 'Berakhir Hari Ini';
                        } else {
                            var years = 0;
                            var months = 0;
                            var days = diffDays;

                            if (days >= 365) {
                                years = Math.floor(days / 365);
                                days = days % 365;
                            }

                            if (days >= 30) {
                                months = Math.floor(days / 30);
                                days = days % 30;
                            }

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

                        kontrakInfoHtml = '<div class="small ' + textColor + '" data-toggle="tooltip" title="Berakhir pada: ' + 
                                           formattedEndDate + '">' + formattedDuration + '</div>';
                    }

                    // Build a right-aligned three-dot dropdown for status actions
                    var dropdownHtml = '\n' +
                        '<div class="dropdown ml-2">' +
                            '<button class="btn btn-sm btn-light p-1 change-status-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Ubah status">' +
                                '<i class="fas fa-ellipsis-v"></i>' +
                            '</button>' +
                            '<div class="dropdown-menu dropdown-menu-right">' +
                                '<a class="dropdown-item change-status" href="#" data-id="' + row.id + '" data-status="tetap">Tetap</a>' +
                                '<a class="dropdown-item change-status" href="#" data-id="' + row.id + '" data-status="kontrak">Kontrak</a>' +
                                '<a class="dropdown-item change-status" href="#" data-id="' + row.id + '" data-status="tidak aktif">Tidak Aktif</a>' +
                                '<a class="dropdown-item change-status" href="#" data-id="' + row.id + '" data-status="freelance">Freelance</a>' +
                                (row.status === 'kontrak' ? '<div class="dropdown-divider"></div><a class="dropdown-item" href="{{ url('/hrd/employee') }}/' + row.id + '/contracts">Manage Kontrak</a>' : '') +
                            '</div>' +
                        '</div>';

                    return '<div class="d-flex justify-content-between align-items-center">' +
                                '<div>' + badgeHtml + kontrakInfoHtml + '</div>' +
                                dropdownHtml +
                           '</div>';
                }
            },
            {
                data: 'kontrak_berakhir', 
                name: 'kontrak_berakhir',
                defaultContent: '-',
                orderable: true,
                type: 'date',
                visible: false
            },
            {
                data: 'action', 
                name: 'action', 
                orderable: false, 
                searchable: false,
                render: function(data, type, row) {
                    var html = '';
                    html += '<div class="btn-group">';
                    html += '<a href="{{ url('/hrd/employee') }}/' + row.id + '/edit" class="btn btn-sm btn-primary table-action-btn" title="Edit Data">Edit Data</a>';
                    html += '</div>';
                    return html;
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
            // Update stats summary based on currently shown rows (applied filters)
            updateEmployeeStats();
        }
    });

    // Compute and render employee stats based on DataTable rows (respecting current filters/search)
    function parseDateSafe(val) {
        if (!val) return null;
        try {
            var d = new Date(val);
            if (isNaN(d.getTime())) return null;
            return d;
        } catch (e) {
            return null;
        }
    }

    function getGenderFromRow(row) {
        // Prefer single-letter codes 'L'/'P' (case-insensitive) since DB stores that
        var g = row.jenis_kelamin || row.gender || row.jk || row.sex || row.kelamin || row.gender_id || null;
        if (!g) return null;
        g = String(g).trim();
        if (g.length === 1) {
            var ch = g.toLowerCase();
            if (ch === 'l') return 'male';
            if (ch === 'p') return 'female';
        }
        // Fallback to more verbose values if not a single-letter code
        var gl = g.toLowerCase();
        if (gl === 'male' || gl === 'laki' || gl === 'laki-laki' || gl === 'laki laki' || gl === 'man') return 'male';
        if (gl === 'female' || gl === 'perempuan' || gl === 'wanita' || gl === 'woman') return 'female';
        return null;
    }

    function updateEmployeeStats() {
        var rows = table.rows({ search: 'applied' }).data().toArray();
        var kontrak = 0, tetap = 0, freelance = 0;
        var male = 0, female = 0;
        var ageSum = 0, ageCount = 0;

        rows.forEach(function(r) {
            var status = (r.status || '').toString().toLowerCase();
            if (status === 'kontrak') kontrak++;
            else if (status === 'tetap') tetap++;
            else if (status === 'freelance') freelance++;

            var g = getGenderFromRow(r);
            if (g === 'male') male++;
            if (g === 'female') female++;

            var dob = r.tanggal_lahir || r.tgl_lahir || r.birth_date || r.birthdate || null;
            var d = parseDateSafe(dob);
            if (d) {
                var today = new Date();
                var age = today.getFullYear() - d.getFullYear();
                var m = today.getMonth() - d.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < d.getDate())) {
                    age--;
                }
                if (!isNaN(age) && age >= 0) {
                    ageSum += age;
                    ageCount++;
                }
            }
        });

        var avgAge = ageCount > 0 ? (ageSum / ageCount) : null;

        $('#stat-kontrak').text(kontrak);
        $('#stat-tetap').text(tetap);
        $('#stat-freelance').text(freelance);
        $('#stat-male').text(male);
        $('#stat-female').text(female);
        $('#stat-usia').text(avgAge ? Math.round(avgAge * 10) / 10 + ' thn' : '-');
    }

    // Move custom filters next to DataTables search box
    var $toolbarContent = $('#employeeToolbarHolder').children().detach();
    var $wrapper = $('#employees-table_wrapper');
    var $filter = $wrapper.find('.dataTables_filter');

    // Align filters and search box together on the right side
    $filter.addClass('d-flex align-items-center justify-content-end flex-wrap');

    var $leftArea = $('<div class="d-flex align-items-center flex-wrap mb-2 mb-sm-0" id="employeeToolbar"></div>');
    $leftArea.append($toolbarContent);
    $filter.prepend($leftArea);

    // Style the built-in search label to sit on the right
    $filter.find('label').addClass('mb-0 ml-sm-3');

    $('#employeeToolbarHolder').remove();

    // Reload table when any filter changes
    $('#filter-division, #filter-perusahaan, #filter-status').on('change', function() {
        table.ajax.reload();
    });

    // DataTables built-in search and pagination are now used
    
    // Handle status change from the three-dot dropdown in the Status column
    $('#employees-table').on('click', '.change-status', function(e) {
        e.preventDefault();
        var employeeId = $(this).data('id');
        var newStatus = $(this).data('status');

        Swal.fire({
            title: 'Ubah status?',
            text: 'Set status karyawan menjadi: ' + newStatus,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, ubah',
            cancelButtonText: 'Batal'
        }).then(function(result) {
            if (result.value) {
                $.ajax({
                    url: '/hrd/employee/' + employeeId,
                    type: 'POST',
                    data: {
                        _method: 'PUT',
                        status: newStatus,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response && response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: response.message || 'Status diperbarui',
                                timer: 1500,
                                timerProgressBar: true
                            });
                            table.ajax.reload(null, false);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: (response && response.message) ? response.message : 'Gagal mengubah status'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Gagal mengubah status. Coba lagi.'
                        });
                    }
                });
            }
        });
    });

    // Cache for positions list
    var positionsCache = null;

    // Populate position dropdown when toggle is clicked
    $(document).on('click', '.position-change-toggle', function(e) {
        var $btn = $(this);
        var employeeId = $btn.data('id');
        var empDivId = $btn.data('division-id');
        var $menu = $('#position-menu-' + employeeId);

        // try to find the row's division name from DataTable data
        var empDivName = '';
        try {
            var rowDataArr = table.rows().data().toArray();
            for (var i = 0; i < rowDataArr.length; i++) {
                if (rowDataArr[i].id == employeeId) {
                    if (rowDataArr[i].division && rowDataArr[i].division.name) empDivName = rowDataArr[i].division.name;
                    break;
                }
            }
        } catch (err) {
            empDivName = '';
        }

        // If already populated, do nothing
        if ($menu.data('populated')) {
            return;
        }

        // Fetch positions list (DataTables endpoint returns {data: [...]})
        var loadPositions = function(callback) {
            if (positionsCache) {
                return callback(positionsCache);
            }
            $.ajax({
                url: '/hrd/master/position/data',
                type: 'GET',
                success: function(resp) {
                    // DataTables returns an object with `data` array
                    var list = resp && resp.data ? resp.data : [];
                    positionsCache = list;
                    callback(list);
                },
                error: function() {
                    $menu.html('<div class="px-3 py-2 text-danger small">Gagal memuat posisi</div>');
                }
            });
        };

        $menu.html('<div class="px-3 py-2 small text-muted">Memuat...</div>');

        loadPositions(function(list) {
            if (!list || list.length === 0) {
                $menu.html('<div class="px-3 py-2 small text-muted">Tidak ada posisi</div>');
                $menu.data('populated', true);
                return;
            }

            // Filter positions by the employee's division if available
            var filtered = list.filter(function(p) {
                if ((!empDivId || empDivId === '') && (!empDivName || empDivName === '')) return true;
                if (empDivId && empDivId !== '') {
                    if (p.division && p.division.id && String(p.division.id) == String(empDivId)) return true;
                    if (p.division_id && String(p.division_id) == String(empDivId)) return true;
                }
                if (empDivName && empDivName !== '') {
                    if (p.division_name && String(p.division_name) == String(empDivName)) return true;
                    if (p.division && p.division.name && String(p.division.name) == String(empDivName)) return true;
                }
                return false;
            });

            if (!filtered || filtered.length === 0) {
                $menu.html('<div class="px-3 py-2 small text-muted">Tidak ada posisi untuk divisi ini</div>');
                $menu.data('populated', true);
                return;
            }

            var html = '';
            filtered.forEach(function(p) {
                var divName = (p.division_name) ? p.division_name : (p.division && p.division.name ? p.division.name : '');
                html += '<a href="#" class="dropdown-item change-position" data-employee-id="' + employeeId + '" data-position-id="' + p.id + '">';
                html += '<div class="d-flex flex-column"><span>' + (p.name || '-') + '</span>';
                if (divName) html += '<small class="text-muted">' + divName + '</small>';
                html += '</div></a>';
            });

            $menu.html(html);
            $menu.data('populated', true);
        });
    });

    // Handle selecting a new position from the dropdown
    $(document).on('click', '.change-position', function(e) {
        e.preventDefault();
        var employeeId = $(this).data('employee-id');
        var positionId = $(this).data('position-id');

        Swal.fire({
            title: 'Ubah posisi?',
            text: 'Set posisi karyawan menjadi pilihan ini.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, ubah',
            cancelButtonText: 'Batal'
        }).then(function(result) {
            if (result.value) {
                $.ajax({
                    url: '/hrd/employee/' + employeeId,
                    type: 'POST',
                    data: {
                        _method: 'PUT',
                        position_id: positionId,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response && response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: response.message || 'Posisi diperbarui',
                                timer: 1200,
                                timerProgressBar: true
                            });
                            table.ajax.reload(null, false);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: (response && response.message) ? response.message : 'Gagal mengubah posisi'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Gagal mengubah posisi. Coba lagi.'
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
    $('#employees-table').on('click', '.show-employee', function(e) {
        e.preventDefault();
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