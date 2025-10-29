@extends('layouts.erm.app')
@section('title', 'ERM | Daftar Obat')
@section('navbar')
    @include('layouts.erm.navbar-farmasi')
@endsection  

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<div class="container-fluid">
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
                                                        <input type="number" class="form-control" id="hpp" name="hpp" min="0">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="hpp_jual">HPP Jual</label>
                                                        <input type="number" class="form-control" id="hpp_jual" name="hpp_jual" min="0">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="harga_net">Harga Net</label>
                                                        <input type="number" class="form-control" id="harga_net" name="harga_net" min="0" step="any">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="harga_nonfornas">Harga Non-Fornas</label>
                                                        <input type="number" class="form-control" id="harga_nonfornas" name="harga_nonfornas" min="0" step="any">
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
    <a href="{{ route('erm.obat.export-excel', request()->all()) }}" class="btn btn-success mb-3" target="_blank">
        <i class="fas fa-file-excel"></i> Export Excel
    </a>

    <div class="row mb-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Filter</h5>
                    <div class="row">
                        <div class="col-md-4">
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
                        <div class="col-md-4">
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
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="filter_status">Status</label>
                                <select id="filter_status" class="form-control select2">
                                    <option value="">Semua Status</option>
                                    <option value="1">Aktif</option>
                                    <option value="0">Tidak Aktif</option>
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
                        <th class="text-right">HPP Jual</th>
                        <th class="text-right">Harga Net</th>
                        <th class="text-right">Harga Non-Fornas</th>
                        <th>Metode Bayar</th>
                        <th>Kategori</th>
                        <th>Zat Aktif</th>
                        <th>Status</th>
                        <th>Aksi</th>
                           <th>Farmasi</th>
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
    /* Right align for price and stock columns */
    #obat-table td:nth-child(3), 
    #obat-table td:nth-child(6), 
    #obat-table td:nth-child(8) {
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
            ajax: {
                url: "{{ route('erm.obat.index') }}",
                data: function(d) {
                    d.kategori = $('#filter_kategori').val();
                    d.metode_bayar_id = $('#filter_metode_bayar').val();
                    
                    // Always send the status_aktif parameter
                    // Even when it's empty, to ensure the controller gets it
                    d.status_aktif = $('#filter_status').val();
                    
                    console.log('Sending filters:', {
                        kategori: d.kategori,
                        metode_bayar_id: d.metode_bayar_id,
                        status_aktif: d.status_aktif
                    });
                }
            },
            columns: [
                { data: 'kode_obat', name: 'kode_obat' },
                { data: 'nama', name: 'nama' },
                { 
                    data: 'hpp',
                    name: 'hpp',
                    className: 'text-right',
                    render: function(data) {
                        return data ? 'Rp ' + data.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".") : '-';
                    }
                },
                {
                    data: 'hpp_jual',
                    name: 'hpp_jual',
                    className: 'text-right',
                    render: function(data) {
                        return data ? 'Rp ' + data.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".") : '-';
                    }
                },
                {
                    data: 'harga_net',
                    name: 'harga_net',
                    className: 'text-right',
                    render: function(data) {
                        return data ? 'Rp ' + data.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".") : '-';
                    }
                },
                { 
                    data: 'harga_nonfornas', 
                    name: 'harga_nonfornas',
                    className: 'text-right',
                    render: function(data) {
                        return data ? 'Rp ' + data.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".") : '-';
                    }
                },
                { data: 'metode_bayar', name: 'metode_bayar' },
                { data: 'kategori', name: 'kategori' },
                { data: 'zat_aktif', name: 'zat_aktif', width: '180px' },
                { 
                    data: 'status_aktif', 
                    name: 'status_aktif',
                    render: function(data) {
                        if (data === 1) {
                            return '<span class="status-badge status-active">Aktif</span>';
                        } else {
                            return '<span class="status-badge status-inactive">Tidak Aktif</span>';
                        }
                    }
                },
                { data: 'action', name: 'action', orderable: false, searchable: false }
                ,
                { 
                    data: null, 
                    orderable: false, 
                    searchable: false,
                    render: function (data, type, row) {
                        // Single relations button that opens the modal showing both Pemasok and Principal
                        return '<button class="btn btn-sm btn-info btn-relations" data-id="'+row.id+'">Relasi</button>';
                    }
                }
            ]
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
        $('#filter_kategori, #filter_metode_bayar, #filter_status').on('change', function() {
            var statusFilter = $('#filter_status').val();
            console.log('Status filter changed to:', statusFilter);
            
            // Add special handling for the "All" option
            if (statusFilter === '') {
                console.log('All statuses selected');
            }
            
            table.ajax.reload();
        });
        
        // Add reload button functionality
        $('#reload-table').on('click', function() {
            console.log('Manually reloading table...');
            $('#filter_status').val('').trigger('change.select2');
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
@endsection
