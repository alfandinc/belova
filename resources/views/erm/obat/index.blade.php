@extends('layouts.erm.app')
@section('title', 'ERM | Daftar Obat')
@section('navbar')
    @include('layouts.erm.navbar-farmasi')
@endsection  

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<div class="container-fluid">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <!-- Modal Tambah/Edit Obat -->
        <div class="modal fade" id="obatModal" tabindex="-1" role="dialog" aria-labelledby="obatModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <form id="formObat">
                        <div class="modal-header">
                            <h5 class="modal-title" id="obatModalLabel">Tambah/Edit Obat</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                                            <input type="hidden" id="obat_id" name="obat_id">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="kode_obat">Kode Obat</label>
                                                        <input type="text" class="form-control" id="kode_obat" name="kode_obat">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="nama">Nama Obat</label>
                                                        <input type="text" class="form-control" id="nama" name="nama" required>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="hpp">HPP</label>
                                                        <input type="text" class="form-control numeric-input" id="hpp" name="hpp" inputmode="decimal" aria-label="HPP">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="hpp_jual">HPP Jual</label>
                                                        <input type="text" class="form-control numeric-input" id="hpp_jual" name="hpp_jual" inputmode="decimal" aria-label="HPP Jual">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="harga_net">Harga Net</label>
                                                        <input type="text" class="form-control numeric-input" id="harga_net" name="harga_net" inputmode="decimal" aria-label="Harga Net">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="harga_nonfornas">Harga Non-Fornas</label>
                                                        <input type="text" class="form-control numeric-input" id="harga_nonfornas" name="harga_nonfornas" inputmode="decimal" aria-label="Harga Non-Fornas">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="metode_bayar_id">Metode Bayar</label>
                                                        <select class="form-control select2" id="metode_bayar_id" name="metode_bayar_id">
                                                            <option value="">Pilih Metode Bayar</option>
                                                            @foreach($metodeBayars as $metodeBayar)
                                                                <option value="{{ $metodeBayar->id }}">{{ $metodeBayar->nama }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="kategori">Kategori</label>
                                                        <select class="form-control select2" id="kategori" name="kategori">
                                                            <option value="">Pilih Kategori</option>
                                                            <option value="Produk">Produk</option>
                                                            <option value="Obat">Obat</option>
                                                            <option value="Racikan">Racikan</option>
                                                            <option value="Bhp">Bhp</option>
                                                            <option value="Bhp Alat">Bhp Alat</option>
                                                            <option value="Lainnya">Lainnya</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label for="zat_aktif_id">Zat Aktif</label>
                                                <select class="form-control select2" id="zat_aktif_id" name="zataktif_id[]" multiple>
                                                    @php $zatAktifList = \App\Models\ERM\ZatAktif::all(); @endphp
                                                    @foreach($zatAktifList as $zat)
                                                        <option value="{{ $zat->id }}">{{ $zat->nama }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="dosis">Dosis</label>
                                                        <input type="text" class="form-control" id="dosis" name="dosis">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="satuan">Satuan</label>
                                                        <select class="form-control select2" id="satuan" name="satuan">
                                                            <option value="">Pilih Satuan</option>
                                                            <option value="Mg">Mg</option>
                                                            <option value="Ml">Ml</option>
                                                            <option value="Gram">Gram</option>
                                                            <option value="Tablet">Tablet</option>
                                                            <option value="Kapsul">Kapsul</option>
                                                            <option value="Botol">Botol</option>
                                                            <option value="Strip">Strip</option>
                                                            <option value="Tube">Tube</option>
                                                            <option value="Ampul">Ampul</option>
                                                            <option value="Sachet">Sachet</option>
                                                            <option value="Vial">Vial</option>
                                                            <option value="Pcs">Pcs</option>
                                                            <option value="Pack">Pack</option>
                                                            <option value="IU">IU</option>
                                                            <option value="Softbag">Softbag</option>

                                                            <option value="Lainnya">Lainnya</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label for="status_aktif">Status</label>
                                                <select class="form-control" id="status_aktif" name="status_aktif">
                                                    <option value="1">Aktif</option>
                                                    <option value="0">Tidak Aktif</option>
                                                </select>
                                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary" id="btnSimpanObat">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <div class="d-flex align-items-center mb-0 mt-2">
        <h3 class="mb-0 mr-2">Daftar Obat Farmasi</h3>
    </div>
    
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                            <li class="breadcrumb-item">Farmasi</li>
                            <li class="breadcrumb-item active">Stok Obat</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->                                                              
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->  

    <button type="button" class="btn btn-primary mb-3 btn-tambah-obat">+ Tambah Obat</button>
    <a href="{{ route('erm.obat.export-excel', request()->all()) }}" class="btn btn-success mb-3" id="btnExportExcel" target="_blank">
        <i class="fas fa-file-excel"></i> Export Excel
    </a>
    <button type="button" class="btn btn-secondary mb-3 ml-2" data-toggle="modal" data-target="#importCsvModal">
        <i class="fas fa-file-upload"></i> Import CSV
    </button>

    <!-- Modal: Pilih Kolom Export -->
    <div class="modal fade" id="exportColumnsModal" tabindex="-1" role="dialog" aria-labelledby="exportColumnsModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exportColumnsModalLabel">Pilih Kolom untuk Export</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="exportColumnsForm">
                    <div class="modal-body">
                        <div class="form-group mb-2">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="selectAllColumns" checked>
                                <label class="custom-control-label" for="selectAllColumns">Pilih semua</label>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <!-- Keep values in the exact order desired for export -->
                                <div class="custom-control custom-checkbox">
                                    <input class="custom-control-input column-choice" type="checkbox" id="col-id" value="id" checked>
                                    <label class="custom-control-label" for="col-id">ID</label>
                                </div>
                                <div class="custom-control custom-checkbox">
                                    <input class="custom-control-input column-choice" type="checkbox" id="col-kode" value="kode_obat" checked>
                                    <label class="custom-control-label" for="col-kode">Kode Obat</label>
                                </div>
                                <div class="custom-control custom-checkbox">
                                    <input class="custom-control-input column-choice" type="checkbox" id="col-nama" value="nama" checked>
                                    <label class="custom-control-label" for="col-nama">Nama</label>
                                </div>
                                <div class="custom-control custom-checkbox">
                                    <input class="custom-control-input column-choice" type="checkbox" id="col-hpp" value="hpp" checked>
                                    <label class="custom-control-label" for="col-hpp">HPP</label>
                                </div>
                                <div class="custom-control custom-checkbox">
                                    <input class="custom-control-input column-choice" type="checkbox" id="col-hpp-jual" value="hpp_jual" checked>
                                    <label class="custom-control-label" for="col-hpp-jual">HPP Jual</label>
                                </div>
                                <div class="custom-control custom-checkbox">
                                    <input class="custom-control-input column-choice" type="checkbox" id="col-harga-nonfornas" value="harga_nonfornas" checked>
                                    <label class="custom-control-label" for="col-harga-nonfornas">Harga Non-Fornas</label>
                                </div>
                                <div class="custom-control custom-checkbox">
                                    <input class="custom-control-input column-choice" type="checkbox" id="col-metode-bayar" value="metode_bayar" checked>
                                    <label class="custom-control-label" for="col-metode-bayar">Metode Bayar</label>
                                </div>
                                <div class="custom-control custom-checkbox">
                                    <input class="custom-control-input column-choice" type="checkbox" id="col-kategori" value="kategori" checked>
                                    <label class="custom-control-label" for="col-kategori">Kategori</label>
                                </div>
                                <div class="custom-control custom-checkbox">
                                    <input class="custom-control-input column-choice" type="checkbox" id="col-dosis" value="dosis" checked>
                                    <label class="custom-control-label" for="col-dosis">Dosis</label>
                                </div>
                                <div class="custom-control custom-checkbox">
                                    <input class="custom-control-input column-choice" type="checkbox" id="col-satuan" value="satuan" checked>
                                    <label class="custom-control-label" for="col-satuan">Satuan</label>
                                </div>
                                <div class="custom-control custom-checkbox">
                                    <input class="custom-control-input column-choice" type="checkbox" id="col-is-generik" value="is_generik" checked>
                                    <label class="custom-control-label" for="col-is-generik">Generik</label>
                                </div>
                            </div>
                        </div>
                        <small class="text-muted d-block mt-2">Hanya obat aktif yang akan diexport.</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success" id="confirmExportBtn"><i class="fas fa-file-excel"></i> Export</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Filter</h5>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="filter_kategori">Kategori</label>
                                <select id="filter_kategori" class="form-control select2">
                                    <option value="">Semua Kategori</option>
                                    @foreach($kategoris as $kategori)
                                        <option value="{{ $kategori }}">{{ $kategori }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="filter_metode_bayar">Metode Bayar</label>
                                <select id="filter_metode_bayar" class="form-control select2">
                                    <option value="">Semua Metode Bayar</option>
                                    @foreach($metodeBayars as $metodeBayar)
                                        <option value="{{ $metodeBayar->id }}">{{ $metodeBayar->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="filter_status">Status</label>
                                <select id="filter_status" class="form-control select2">
                                    <option value="">Semua Status</option>
                                    <option value="1">Aktif</option>
                                    <option value="0">Tidak Aktif</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="filter_paten">Jenis Obat</label>
                                <select id="filter_paten" class="form-control select2">
                                    <option value="">Semua Jenis</option>
                                    <option value="1">Obat Paten (punya zat aktif)</option>
                                    <option value="0">Obat Tidak Paten (tanpa zat aktif)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-12">
                            <button id="reload-table" class="btn btn-info btn-sm">
                                <i class="fas fa-sync"></i> Reload
                            </button>
                        </div>
                    </div>
                </div>
            </div>

                        <!-- Modal: Import CSV -->
                        <div class="modal fade" id="importCsvModal" tabindex="-1" role="dialog" aria-labelledby="importCsvModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-xl" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="importCsvModalLabel">Import CSV - Update Obat by ID</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <form id="importCsvForm" enctype="multipart/form-data" onsubmit="return false;">
                                        @csrf
                                        <div class="modal-body">
                                            <p class="small text-muted">File must be CSV with header containing at least <strong>ID</strong>. Other columns supported: <strong>Nama</strong>, <strong>Dosis</strong>, <strong>Satuan</strong>.</p>
                                            <div class="form-group">
                                                <label for="csv_file">Pilih file CSV</label>
                                                <input type="file" name="csv_file" id="csv_file" accept=".csv,text/csv" class="form-control-file" required>
                                            </div>
                                            <div id="importPreviewArea" style="display:none; max-height:60vh; overflow:auto; margin-top:12px;"></div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                            <button type="button" id="btnPreviewCsv" class="btn btn-info" onclick="(window.previewCsvAction||function(){})()">Preview</button>
                                            <button type="button" id="btnConfirmImport" class="btn btn-primary" onclick="(window.confirmImportAction||function(){})()" disabled>Import</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
        </div>
    </div>
     
    <div class="card">
        <div class="card-body">
            <table id="obat-table" class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Kode Obat</th>
                        <th>Nama Obat</th>
                        <th class="text-right">HPP</th>
                        <!-- HPP Jual column removed -->
                        <!-- Harga Net column removed -->
                        <th class="text-right">Harga Jual</th>
                                <th>Zat Aktif</th>
                                <th>Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
    
@endsection

@section('scripts')
<style>
    .badge-zat-aktif {
        background-color: #007bff !important;
        color: #fff !important;
        font-weight: bold;
        font-size: 0.95em;
        border-radius: 6px;
        padding: 4px 10px;
        margin: 2px 2px;
        display: inline-block;
    }
    /* Match Bootstrap .badge sizing for consistent appearance */
    .badge-pink {
        background-color: #ff69b4 !important;
        color: #fff !important;
        font-weight: 400;
        display: inline-block;
        padding: .25em .4em;
        font-size: 75%;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: .25rem;
    }
    .badge-yellow {
        background-color: #ffc107 !important;
        color: #212529 !important;
        font-weight: 700;
        display: inline-block;
        padding: .25em .4em;
        font-size: 75%;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: .25rem;
    }
    /* Right align for price columns (HPP and Harga Jual) */
    #obat-table td:nth-child(3),
    #obat-table td:nth-child(4) {
        text-align: right;
    }
    
    /* Style for inactive medications */
    tr.inactive-medication {
        background-color: #ffe0e0 !important;
    }
    
    .status-badge {
        font-weight: bold;
        padding: 4px 8px;
        border-radius: 4px;
    }
    
    .status-active {
        background-color: #d4edda;
        color: #155724;
    }
    
    .status-inactive {
        background-color: #f8d7da;
        color: #721c24;
    }

    .blinking-warning {
        animation: blink-warning 1s linear infinite;
    }
    @keyframes blink-warning {
        0%, 100% { opacity: 1; }
        50% { opacity: 0; }
    }

    /* Wider import CSV modal and wrapping table cells */
    #importCsvModal .modal-dialog {
        max-width: 1200px;
    }
    #importCsvModal .table td, #importCsvModal .table th {
        white-space: normal;
    }
</style>
<script>
    // Setup CSRF token untuk semua AJAX request
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    
    // Declare table variable globally
    let table;
    
    // Buka modal tambah obat
    $(document).on('click', '.btn-tambah-obat', function() {
        $('#formObat')[0].reset();
        $('#obat_id').val('');
        $('#obatModalLabel').text('Tambah Obat');
        $('#zat_aktif_id').val(null).trigger('change');
        $('#metode_bayar_id').val('').trigger('change');
        $('#kategori').val('').trigger('change');
        $('#status_aktif').val('1'); // Set default to Aktif
        $('#obatModal').modal('show');
    });

    // Buka modal edit obat
    $(document).on('click', '.btn-edit-obat', function() {
        var id = $(this).data('id');
        console.log('Edit button clicked for ID:', id);
        $.ajax({
            url: '/erm/obat/' + id + '/edit',
            type: 'GET',
            success: function(data) {
                console.log('Edit data received:', data);
                $('#formObat')[0].reset();
                $('#obat_id').val(data.id);
                $('#kode_obat').val(data.kode_obat);
                $('#nama').val(data.nama);
                $('#hpp').val(data.hpp);
                $('#hpp_jual').val(data.hpp_jual);
                $('#harga_net').val(data.harga_net);
                $('#harga_nonfornas').val(data.harga_nonfornas);
                $('#metode_bayar_id').val(data.metode_bayar_id).trigger('change');
                $('#kategori').val(data.kategori).trigger('change');
                $('#zat_aktif_id').val(data.zataktif_id).trigger('change');
                $('#dosis').val(data.dosis);
                $('#satuan').val(data.satuan).trigger('change');
                $('#status_aktif').val(data.status_aktif);
                $('#obatModalLabel').text('Edit Obat');
                $('#obatModal').modal('show');
            },
            error: function(xhr) {
                console.log('Edit AJAX error:', xhr);
                alert('Gagal mengambil data obat: ' + xhr.responseText);
            }
        });
    });

    $(document).ready(function () {
        // Initialize select2
        $('.select2').select2({
            width: '100%'
        });
        // Zat aktif minimal input 2 karakter untuk pencarian
        $('#zat_aktif_id').select2({
            width: '100%',
            minimumInputLength: 2,
            placeholder: 'Cari zat aktif...'
        });
        
        // Set filter_status to 'Aktif' by default
        $('#filter_status').val('1').trigger('change.select2');

        // Initialize DataTable
        table = $('#obat-table').DataTable({
            processing: true,
            serverSide: true,
            createdRow: function(row, data, dataIndex) {
                // Mark rows with inactive status
                if (data.status_aktif == 0 || data.status_aktif === '0' || data.status_aktif === false) {
                    $(row).addClass('inactive-medication');
                } else {
                    $(row).removeClass('inactive-medication');
                }
            },
            ajax: {
                url: "{{ route('erm.obat.index') }}",
                data: function(d) {
                    d.kategori = $('#filter_kategori').val();
                    d.metode_bayar_id = $('#filter_metode_bayar').val();
                    
                    // Always send the status_aktif parameter
                    // Even when it's empty, to ensure the controller gets it
                    d.status_aktif = $('#filter_status').val();
                    // Send has_zat_aktif for Paten/Tidak Paten filter (1/0/empty)
                    d.has_zat_aktif = $('#filter_paten').val();
                    
                    console.log('Sending filters:', {
                        kategori: d.kategori,
                        metode_bayar_id: d.metode_bayar_id,
                        status_aktif: d.status_aktif,
                        has_zat_aktif: d.has_zat_aktif
                    });
                }
            },
            columns: [
                {
                    data: 'kode_obat',
                    name: 'kode_obat',
                    render: function(data, type, row) {
                        var kategori = (row.kategori || '').toLowerCase();
                        var cls = 'badge badge-secondary';
                        if (kategori === 'obat') {
                            cls = 'badge badge-primary';
                        } else if (kategori === 'produk') {
                            cls = 'badge badge-pink';
                        } else if (kategori === 'bahan baku' || kategori === 'bahan_baku' || kategori === 'bahanbaku') {
                            cls = 'badge badge-yellow';
                        }
                        var kodeHtml = data ? data : '-';
                        var badgeHtml = '<span class="' + cls + '" style="margin-top:6px; display:inline-block;">' + (row.kategori || '-') + '</span>';
                        return '<div>' + kodeHtml + '<br/>' + badgeHtml + '</div>';
                    }
                },
                {
                    data: 'nama',
                    name: 'nama',
                    render: function(data, type, row) {
                        var metode = row.metode_bayar || '';
                        var metodeLower = (metode + '').toLowerCase();
                        var badgeClass = 'badge badge-primary';
                        if (metodeLower === 'umum') {
                            badgeClass = 'badge badge-success';
                        }
                        var nameHtml = data ? data : '-';
                        var badgeHtml = '<span class="' + badgeClass + '" style="margin-top:6px; display:inline-block;">' + (metode || '-') + '</span>';
                        var patenBadge;
                        if (row.has_zat_aktif) {
                            patenBadge = '<span class="badge badge-info" style="margin-top:6px; margin-left:6px; display:inline-block;">Obat Paten</span>';
                        } else {
                            patenBadge = '<span class="badge badge-secondary" style="margin-top:6px; margin-left:6px; display:inline-block;">Obat Tidak Paten</span>';
                        }
                        return '<div>' + nameHtml + '<br/>' + badgeHtml + patenBadge + '</div>';
                    }
                },
                { 
                    data: 'hpp',
                    name: 'hpp',
                    className: 'text-right',
                    render: function(data) {
                        return data ? 'Rp ' + data.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".") : '-';
                    }
                },
                // HPP Jual column removed
                // Harga Net column removed
                { 
                    data: 'harga_nonfornas', 
                    name: 'harga_nonfornas',
                    className: 'text-right',
                    render: function(data) {
                        return data ? 'Rp ' + data.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".") : '-';
                    }
                },
                { data: 'zat_aktif', name: 'zat_aktif', width: '180px' },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        var actionHtml = data || '';
                        // Ensure the action buttons and the Relasi button are grouped
                        var relasiBtn = '<button class="btn btn-sm btn-info btn-relations" data-id="'+row.id+'">Farmasi</button>';
                        return '<div class="btn-group" role="group">' + actionHtml + relasiBtn + '</div>';
                    }
                }
            ]
        });

        // Export Excel: open modal instead of direct download
        $(document).on('click', '#btnExportExcel', function(e){
            e.preventDefault();
            $('#exportColumnsModal').modal('show');
        });

        // Select All toggle
        $('#selectAllColumns').on('change', function(){
            const checked = $(this).is(':checked');
            $('.column-choice').prop('checked', checked);
        });

        // Keep Select All in sync when individual boxes change
        $(document).on('change', '.column-choice', function(){
            const all = $('.column-choice').length;
            const checked = $('.column-choice:checked').length;
            $('#selectAllColumns').prop('checked', all === checked);
        });

        // Confirm export -> build URL with selected columns and current filters
        $('#exportColumnsForm').on('submit', function(e){
            e.preventDefault();
            const selected = $('.column-choice:checked').map(function(){ return $(this).val(); }).get();
            if (!selected.length) {
                alert('Pilih minimal satu kolom untuk diexport.');
                return;
            }
            const params = new URLSearchParams();
            // Maintain current filters
            const kategori = $('#filter_kategori').val();
            const metode = $('#filter_metode_bayar').val();
            if (kategori) params.append('kategori', kategori);
            if (metode) params.append('metode_bayar_id', metode);
            // Always export active only (handled by backend); no need to pass status
            selected.forEach(c => params.append('columns[]', c));
            const url = '{{ route('erm.obat.export-excel') }}' + (params.toString() ? ('?' + params.toString()) : '');
            window.open(url, '_blank');
            $('#exportColumnsModal').modal('hide');
        });

        // Append modal for relations (pemasok & principal)
        if ($('#obatRelationsModal').length === 0) {
            $('body').append('\
            <div class="modal fade" id="obatRelationsModal" tabindex="-1" role="dialog" aria-hidden="true">\
              <div class="modal-dialog modal-lg" role="document">\
                <div class="modal-content">\
                  <div class="modal-header">\
                    <h5 class="modal-title">Relasi Obat</h5>\
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">\
                      <span aria-hidden="true">&times;</span>\
                    </button>\
                  </div>\
                                    <div class="modal-body">\
                                        <h6>Pemasok</h6>\
                                        <div class="table-responsive mb-3">\
                                            <table class="table table-sm table-bordered" id="relationsPemasokTable">\
                                                <thead><tr><th>No</th><th>Nama Pemasok</th><th>Jumlah Faktur</th></tr></thead>\
                                                <tbody></tbody>\
                                            </table>\
                                        </div>\
                                        <h6>Principal</h6>\
                                        <div class="table-responsive">\
                                            <table class="table table-sm table-bordered" id="relationsPrincipalTable">\
                                                <thead><tr><th>No</th><th>Nama Principal</th><th>Jumlah Faktur</th></tr></thead>\
                                                <tbody></tbody>\
                                            </table>\
                                        </div>\
                                    </div>\
                  <div class="modal-footer">\
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>\
                  </div>\
                </div>\
              </div>\
            </div>');
        }

        // Click handler for relations buttons (both columns use same handler)
        $(document).on('click', '.btn-relations', function() {
            var id = $(this).data('id');
            $('#relationsPemasokList').empty();
            $('#relationsPrincipalList').empty();
            $.get('/erm/obat/' + id + '/relations', function(res) {
                    // Populate pemasok table
                    var $pmTbody = $('#relationsPemasokTable tbody');
                    $pmTbody.empty();
                    if (res.pemasoks && res.pemasoks.length) {
                        res.pemasoks.forEach(function(pm, idx) {
                            var jumlah = pm.jumlah_faktur || pm.jumlah || 0;
                            $pmTbody.append('<tr><td>' + (idx+1) + '</td><td>' + (pm.nama || '-') + '</td><td>' + jumlah + '</td></tr>');
                        });
                    } else {
                        $pmTbody.append('<tr><td colspan="3" class="text-center text-muted">(Tidak ada pemasok)</td></tr>');
                    }

                    // Populate principals table
                    var $prTbody = $('#relationsPrincipalTable tbody');
                    $prTbody.empty();
                    if (res.principals && res.principals.length) {
                        res.principals.forEach(function(pr, idx) {
                            var jumlah = pr.jumlah_faktur || pr.jumlah || 0;
                            $prTbody.append('<tr><td>' + (idx+1) + '</td><td>' + (pr.nama || '-') + '</td><td>' + jumlah + '</td></tr>');
                        });
                    } else {
                        $prTbody.append('<tr><td colspan="3" class="text-center text-muted">(Tidak ada principal)</td></tr>');
                    }
                $('#obatRelationsModal').modal('show');
            }).fail(function() {
                alert('Gagal mengambil relasi untuk obat ini');
            });
        });

        // Submit form obat via AJAX (moved inside document ready)
        $('#formObat').on('submit', function(e) {
            e.preventDefault();
            var id = $('#obat_id').val();
            var url = id ? '/erm/obat/' + id : '/erm/obat';
            var method = id ? 'PUT' : 'POST';

            // Robust normalization for localized decimal inputs before serializing the form.
            // Handles formats like "3.241,20" (dot thousands, comma decimals) and
            // "3241.20" (dot decimal). Leaves already-correct dot-decimal values intact.
            var decimalIds = ['#hpp', '#hpp_jual', '#harga_net', '#harga_nonfornas'];
            decimalIds.forEach(function(sel){
                var $el = $(sel);
                if ($el.length) {
                    var v = ($el.val() || '').toString().trim();
                    if (v.length) {
                        var normalized;
                        var hasComma = v.indexOf(',') !== -1;
                        var hasDot = v.indexOf('.') !== -1;
                        if (hasComma && hasDot) {
                            // Assume format like 1.234,56 -> remove dots (thousands) and convert comma to dot
                            normalized = v.replace(/\./g, '').replace(/,/g, '.');
                        } else if (hasComma && !hasDot) {
                            // e.g. 1234,56 -> convert comma to dot
                            normalized = v.replace(/,/g, '.');
                        } else {
                            // e.g. 1234.56 or plain integer -> keep dots as decimal separator
                            normalized = v;
                        }
                        // Remove any non-digit except dot and minus
                        normalized = normalized.replace(/[^0-9.\-]/g, '');
                        $el.val(normalized);
                    }
                }
            });

            var formData = $(this).serializeArray();
            // Ambil value status_aktif langsung dari select
            var statusAktifVal = $('#status_aktif').val();
            console.log('Status aktif value from form:', statusAktifVal);
            formData = formData.filter(function(item){ return item.name !== 'status_aktif'; });
            formData.push({name: 'status_aktif', value: statusAktifVal});

            console.log('Submitting form with data:', formData);

            $.ajax({
                url: url,
                type: method,
                data: $.param(formData),
                success: function(response) {
                    console.log('AJAX success:', response);
                    $('#obatModal').modal('hide');
                    table.ajax.reload();
                    if(typeof Swal !== 'undefined'){
                        Swal.fire({
                            title: 'Berhasil!',
                            text: response.message || 'Data obat berhasil disimpan!',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        alert(response.message || 'Data obat berhasil disimpan!');
                    }
                },
                error: function(xhr) {
                    console.log('AJAX error:', xhr);
                    let msg = xhr.responseJSON?.message || xhr.responseText || 'Gagal menyimpan data.';
                    if(typeof Swal !== 'undefined'){
                        Swal.fire({
                            title: 'Gagal!',
                            text: msg,
                            icon: 'error',
                            timer: 2500,
                            showConfirmButton: true
                        });
                    } else {
                        alert('Gagal menyimpan data: ' + msg);
                    }
                }
            });
        });

        // Apply filter when select changes (no button needed)
        $('#filter_kategori, #filter_metode_bayar, #filter_status, #filter_paten').on('change', function() {
            var statusFilter = $('#filter_status').val();
            console.log('Status filter changed to:', statusFilter);
            
            // Add special handling for the "All" option
            if (statusFilter === '') {
                console.log('All statuses selected');
            }
            console.log('Jenis Obat (has_zat_aktif):', $('#filter_paten').val());
            
            table.ajax.reload();
        });
        
        // Add reload button functionality
        $('#reload-table').on('click', function() {
            console.log('Manually reloading table...');
            $('#filter_status').val('').trigger('change.select2');
            $('#filter_paten').val('').trigger('change.select2');
            table.ajax.reload();
        });

        // Handle delete button clicks
        $(document).on('click', '.delete-btn', function() {
            if (confirm('Apakah Anda yakin ingin menghapus obat ini?')) {
                let id = $(this).data('id');
                
                $.ajax({
                    url: '/erm/obat/' + id,
                    type: 'DELETE',
                    data: {
                        '_token': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if(typeof Swal !== 'undefined'){
                            Swal.fire({
                                title: 'Berhasil!',
                                text: 'Obat berhasil dihapus',
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        } else {
                            alert('Obat berhasil dihapus');
                        }
                        table.ajax.reload();
                    },
                    error: function(xhr) {
                        if(typeof Swal !== 'undefined'){
                            Swal.fire({
                                title: 'Gagal!',
                                text: 'Terjadi kesalahan: ' + xhr.responseText,
                                icon: 'error',
                                showConfirmButton: true
                            });
                        } else {
                            alert('Terjadi kesalahan: ' + xhr.responseText);
                        }
                    }
                });
            }
        });
    });
</script>

<script>
    // Global fallback functions for CSV preview/import (exposed so onclick attributes work)
    window.previewCsvAction = window.previewCsvAction || function(){
        try {
            console.log('global previewCsvAction called');
            var el = document.getElementById('csv_file');
            if(!el) { alert('File input tidak ditemukan'); return; }
            var file = el.files[0];
            if(!file) { alert('Pilih file CSV terlebih dahulu'); return; }
            var fd = new FormData(); fd.append('csv_file', file);
            $.ajax({
                url: '{{ route('erm.obat.import_csv_preview') }}',
                method: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                beforeSend: function(){ showGlobalLoading('Menganalisis file...'); $('#btnPreviewCsv').prop('disabled', true).text('Menganalisis...'); },
                success: function(res){ console.log('preview result', res); try{ if(typeof window.renderImportPreview === 'function'){ window.renderImportPreview(res); } else { $('#importPreviewArea').html(JSON.stringify(res)).show(); $('#btnConfirmImport').prop('disabled', true); } } catch(e){ console.error(e); $('#importPreviewArea').html('Error rendering preview'); }
                },
                error: function(xhr){ alert('Gagal menganalisis file: ' + (xhr.responseText || xhr.statusText)); },
                complete: function(){ hideGlobalLoading(); $('#btnPreviewCsv').prop('disabled', false).text('Preview'); }
            });
        } catch (e) { console.error(e); alert('Terjadi kesalahan: ' + e.message); }
    };

    window.confirmImportAction = window.confirmImportAction || function(){
        try {
            if(!confirm('Yakin menerapkan perubahan yang terlihat pada preview?')) return;
            var el = document.getElementById('csv_file');
            if(!el) { alert('File input tidak ditemukan'); return; }
            var file = el.files[0];
            if(!file) { alert('File tidak ditemukan'); return; }
            var fd = new FormData(); fd.append('csv_file', file);
            $.ajax({
                url: '{{ route('erm.obat.import_csv') }}',
                method: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                beforeSend: function(){ showGlobalLoading('Mengimpor data...'); $('#btnConfirmImport').prop('disabled', true).text('Mengimport...'); },
                success: function(){ location.reload(); },
                error: function(xhr){ alert('Import gagal: ' + (xhr.responseText || xhr.statusText)); },
                complete: function(){ hideGlobalLoading(); $('#btnConfirmImport').prop('disabled', false).text('Import'); }
            });
        } catch (e) { console.error(e); alert('Terjadi kesalahan: ' + e.message); }
    };

    // Render preview table nicely with highlights
    window.renderImportPreview = window.renderImportPreview || function(res){
        try{
            var rows = res.rows || [];
            if(!rows.length){
                $('#importPreviewArea').html('<div class="alert alert-warning">Tidak ada baris yang valid dalam file.</div>').show();
                $('#btnConfirmImport').prop('disabled', true);
                return;
            }
            var html = '<div class="mb-2"><strong>Preview ('+rows.length+' baris)</strong></div>';
            html += '<div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>ID</th><th>Ditemukan</th><th>Nama (Lama)</th><th>Nama (Baru)</th><th>Dosis (Lama)</th><th>Dosis (Baru)</th><th>Satuan (Lama)</th><th>Satuan (Baru)</th></tr></thead><tbody>';
            rows.forEach(function(r){
                var rowClass = r.found ? '' : 'table-secondary';
                html += '<tr class="'+rowClass+'">';
                html += '<td>'+ (r.id || '') +'</td>';
                html += '<td>'+ (r.found ? 'Ya' : 'Tidak') +'</td>';
                var fields = ['nama','dosis','satuan'];
                fields.forEach(function(f){
                    var existing = (r.existing && r.existing[f]) ? r.existing[f] : '';
                    var ne = (r.new && r.new[f]) ? r.new[f] : '';
                    var changed = r.found && ne !== null && ne.toString().trim() !== '' && ne.toString() !== (existing===null?''+existing:existing.toString());
                    var newHtml = changed ? '<span class="badge badge-warning">'+escapeHtml(ne)+'</span>' : escapeHtml(ne);
                    html += '<td>'+ escapeHtml(existing) +'</td><td>'+ newHtml +'</td>';
                });
                html += '</tr>';
            });
            html += '</tbody></table></div>';
            $('#importPreviewArea').html(html).show();
            var anyApply = rows.some(function(r){ return r.found && r.changes; });
            $('#btnConfirmImport').prop('disabled', !anyApply);
        }catch(e){ console.error(e); $('#importPreviewArea').html('<div class="alert alert-danger">Gagal menampilkan preview.</div>'); }
    };

    function escapeHtml(unsafe) {
        if (unsafe === null || unsafe === undefined) return '';
        return String(unsafe).replace(/[&<>"'`=\/]/g, function (s) {
            return ({
                '&': '&amp;','<': '&lt;','>': '&gt;','"': '&quot;',"'": '&#39;','/': '&#x2F;','`': '&#x60;','=': '&#x3D;'
            })[s];
        });
    }

    // Global loading overlay controls
    function ensureGlobalLoading() {
        if ($('#globalLoading').length) return;
        $('body').append('\n            <div id="globalLoading" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.45);z-index:20500;align-items:center;justify-content:center;">\n+                <div style="text-align:center;color:#fff;max-width:90%;">\n+                    <div class="spinner-border text-light" role="status" style="width:3rem;height:3rem;"></div>\n+                    <div id="globalLoadingText" style="margin-top:12px;font-size:1.05rem;"></div>\n+                </div>\n+            </div>\n        ');
    }

    function showGlobalLoading(msg){ ensureGlobalLoading(); $('#globalLoadingText').text(msg||'Processing...'); $('#globalLoading').fadeIn(150); }
    function hideGlobalLoading(){ $('#globalLoading').fadeOut(100); }
</script>

@endsection
