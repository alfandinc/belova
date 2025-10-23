@extends('layouts.erm.app')
@section('title', 'ERM | Master Faktur Pembelian')
@section('navbar')
    @include('layouts.erm.navbar-farmasi')
@endsection  
@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
        <!-- Title and Button Row -->
    <div class="row mt-3 align-items-center">
        <div class="col-md-6">
            <h2 class="mb-0">Master Pembelian</h2>
        </div>
        <div class="col-md-6 text-right">
            <button id="addMasterFakturBtn" class="btn btn-primary">Tambah Master Faktur</button>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">ERM</a></li>
                            <li class="breadcrumb-item">Farmasi</li>
                            <li class="breadcrumb-item active">Master Pembelian</li>
                        </ol>
                    </div><!--end col-->
                </div><!--end row-->
                                                                              
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div><!--end row-->
    <!-- end page title end breadcrumb -->
        <div class="mb-3">
            <div class="form-inline">
                <label for="filterObat" class="mr-2">Nama Obat:</label>
                <select id="filterObat" class="form-control select2-ajax" style="width:220px;"></select>
                <label for="filterPemasok" class="ml-4 mr-2">Nama Pemasok:</label>
                <select id="filterPemasok" class="form-control select2-ajax" style="width:220px;"></select>
                <label for="filterPrincipal" class="ml-4 mr-2">Principal:</label>
                <select id="filterPrincipal" class="form-control select2-ajax" style="width:220px;"></select>
                <button class="btn btn-secondary btn-sm ml-2" id="resetMasterFakturFilter">Reset</button>
            </div>
        </div>
    <table class="table table-bordered" id="master-faktur-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Obat</th>
                <th>Pemasok</th>
                <th>Principal</th>
                <th>Harga</th>
                <th>Qty/Box</th>
                <th>Diskon</th>
                <th>Action</th>
            </tr>
        </thead>
    </table>
    <!-- Inline Modal for Create/Edit (Bootstrap 4 compatible) -->
    <div class="modal fade" id="masterFakturModal" tabindex="-1" role="dialog" aria-labelledby="masterFakturModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="masterFakturModalLabel">Tambah Master Faktur</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <form id="masterFakturForm">
                @csrf
                <input type="hidden" name="id" id="mf_id">
                <div class="mb-3">
                    <label>Obat</label>
                    <select name="obat_id" class="form-control select2-ajax" id="mf_obat_id" required style="width:100%"></select>
                </div>
                <div class="mb-3">
                    <label>Pemasok</label>
                    <div class="input-group">
                        <select name="pemasok_id" class="form-control select2-ajax" id="mf_pemasok_id" required style="width:100%"></select>
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="button" id="addPemasokBtn" title="Tambah Pemasok">+</button>
                        </div>
                    </div>
                    <div class="mt-2" id="mf_pemasok_add_box" style="display:none;">
                        <div class="input-group">
                            <input type="text" id="mf_pemasok_nama_new" class="form-control" placeholder="Nama pemasok">
                            <div class="input-group-append">
                                <button class="btn btn-success" type="button" id="savePemasokInline">Simpan</button>
                                <button class="btn btn-secondary" type="button" id="cancelPemasokInline">Batal</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label>Principal</label>
                    <div class="input-group">
                        <select name="principal_id" class="form-control select2-ajax" id="mf_principal_id" style="width:100%"></select>
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="button" id="addPrincipalBtn" title="Tambah Principal">+</button>
                        </div>
                    </div>
                    <div class="mt-2" id="mf_principal_add_box" style="display:none;">
                        <div class="input-group">
                            <input type="text" id="mf_principal_nama_new" class="form-control" placeholder="Nama principal">
                            <div class="input-group-append">
                                <button class="btn btn-success" type="button" id="savePrincipalInline">Simpan</button>
                                <button class="btn btn-secondary" type="button" id="cancelPrincipalInline">Batal</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label>Harga</label>
                    <input type="number" step="0.01" name="harga" class="form-control" id="mf_harga" required>
                </div>
                <div class="mb-3">
                    <label>Qty per Box</label>
                    <input type="number" name="qty_per_box" class="form-control" id="mf_qty_per_box" required>
                </div>
                <div class="mb-3">
                <div class="mb-3">
                    <label>Diskon &amp; Tipe Diskon</label>
                    <div class="input-group">
                        <input type="number" step="0.01" name="diskon" class="form-control" id="mf_diskon" required placeholder="Diskon">
                        <select name="diskon_type" class="form-control" id="mf_diskon_type" required style="max-width: 120px;">
                            <option value="nominal">Nominal</option>
                            <option value="percent" selected>%</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </form>
          </div>
        </div>
      </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Delete handler with Swal
    $('#master-faktur-table').on('click', '.deleteMasterFaktur', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Konfirmasi',
            text: 'Yakin ingin menghapus data ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.value) {
                $.ajax({
                    url: '/erm/masterfaktur/' + id,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        if(res.success) {
                            Swal.fire('Berhasil!', 'Data berhasil dihapus!', 'success');
                            table.ajax.reload();
                        } else {
                            Swal.fire('Gagal!', 'Gagal menghapus data!', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Gagal!', 'Gagal menghapus data!', 'error');
                    }
                });
            }
        });
    });
    var table = $('#master-faktur-table').DataTable({
        processing: true,
        serverSide: true,
            ajax: {
                url: '{{ route('erm.masterfaktur.data') }}',
                data: function(d) {
                    d.obat_id = $('#filterObat').val();
                    d.pemasok_id = $('#filterPemasok').val();
                    d.principal_id = $('#filterPrincipal').val();
                }
            },
        columns: [
            { data: 'id', name: 'id' },
            { data: 'obat', name: 'obat' },
            { data: 'pemasok', name: 'pemasok' },
            { data: 'principal', name: 'principal' },
            { data: 'harga', name: 'harga' },
            { data: 'qty_per_box', name: 'qty_per_box' },
            {
                data: null,
                name: 'diskon',
                render: function(data, type, row) {
                    if (row.diskon_type === 'percent') {
                        return row.diskon + ' %';
                    } else {
                        return 'Rp ' + parseFloat(row.diskon).toLocaleString('id-ID', {minimumFractionDigits: 0});
                    }
                }
            },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ]
    });

    // Open modal for create
    $('#addMasterFakturBtn').on('click', function(e) {
        e.preventDefault();
        $('#masterFakturModalLabel').text('Tambah Master Faktur');
        $('#masterFakturForm')[0].reset();
        $('#mf_id').val('');
        $('#masterFakturModal').modal('show');
    });

    // Initialize select2 AJAX for Obat
    $('#mf_obat_id').select2({
        placeholder: 'Pilih Obat',
        minimumInputLength: 2,
        ajax: {
            url: '/erm/ajax/obat',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return { q: params.term };
            },
            processResults: function(data) {
                return { results: data };
            },
            cache: true
        },
        allowClear: true
    });
        // Filter select2 for Obat
        $('#filterObat').select2({
            placeholder: 'Cari Obat',
            minimumInputLength: 2,
            ajax: {
                url: '/erm/ajax/obat',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return { q: params.term };
                },
                processResults: function(data) {
                    return { results: data };
                },
                cache: true
            },
            allowClear: true
        });
        // Filter select2 for Pemasok
        $('#filterPemasok').select2({
            placeholder: 'Cari Pemasok',
            minimumInputLength: 2,
            ajax: {
                url: '/erm/ajax/pemasok',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return { q: params.term };
                },
                processResults: function(data) {
                    return { results: data };
                },
                cache: true
            },
            allowClear: true
        });

        // Filter select2 for Principal
        $('#filterPrincipal').select2({
            placeholder: 'Cari Principal',
            minimumInputLength: 2,
            ajax: {
                url: '/erm/ajax/principal',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return { q: params.term };
                },
                processResults: function(data) {
                    return { results: data };
                },
                cache: true
            },
            allowClear: true
        });

        // Reload table on filter change
        $('#filterObat, #filterPemasok').on('change', function() {
            table.ajax.reload();
        });
        // Reset filters
        $('#resetMasterFakturFilter').on('click', function() {
            $('#filterObat').val(null).trigger('change');
            $('#filterPemasok').val(null).trigger('change');
            table.ajax.reload();
        });
    // Initialize select2 AJAX for Pemasok
    $('#mf_pemasok_id').select2({
        placeholder: 'Pilih Pemasok',
        minimumInputLength: 2,
        ajax: {
            url: '/erm/ajax/pemasok',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return { q: params.term };
            },
            processResults: function(data) {
                return { results: data };
            },
            cache: true
        },
        allowClear: true
    });

    // Initialize select2 AJAX for Principal
    $('#mf_principal_id').select2({
        placeholder: 'Pilih Principal',
        minimumInputLength: 2,
        ajax: {
            url: '/erm/ajax/principal',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return { q: params.term };
            },
            processResults: function(data) {
                return { results: data };
            },
            cache: true
        },
        allowClear: true
    });

    // Open modal for edit
    $('#master-faktur-table').on('click', '.btn-edit-mf', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        $.get('/erm/masterfaktur/' + id, function(data) {
            $('#masterFakturModalLabel').text('Edit Master Faktur');
            $('#mf_id').val(data.id);
            // Set Obat
            var obatOption = new Option(data.obat_nama, data.obat_id, true, true);
            $('#mf_obat_id').append(obatOption).trigger('change');
            // Set Pemasok
            var pemasokOption = new Option(data.pemasok_nama, data.pemasok_id, true, true);
            $('#mf_pemasok_id').append(pemasokOption).trigger('change');
            // Set Principal (if provided by API)
            if (data.principal_id) {
                var principalOption = new Option(data.principal_nama || data.principal_name || 'Principal', data.principal_id, true, true);
                $('#mf_principal_id').append(principalOption).trigger('change');
            } else {
                $('#mf_principal_id').val(null).trigger('change');
            }
            $('#mf_harga').val(data.harga);
            $('#mf_qty_per_box').val(data.qty_per_box);
            $('#mf_diskon').val(data.diskon);
            $('#mf_diskon_type').val(data.diskon_type);
            $('#masterFakturModal').modal('show');
        });
    });

    // Handle form submit for both create and edit
    $('#masterFakturForm').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var id = $('#mf_id').val();
        var url = id ? '/erm/masterfaktur/' + id : '/erm/masterfaktur';
        var method = id ? 'PUT' : 'POST';
        $.ajax({
            url: url,
            type: method,
            data: form.serialize(),
            success: function(res) {
                $('#masterFakturModal').modal('hide');
                table.ajax.reload();
                form[0].reset();
                Swal.fire('Berhasil!', 'Data berhasil disimpan!', 'success');
            },
            error: function(xhr) {
                let msg = 'Gagal menyimpan data!';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                Swal.fire('Gagal!', msg, 'error');
            }
        });
    });
    // Toggle inline add form for Pemasok
    $('#addPemasokBtn').on('click', function() {
        $('#mf_pemasok_add_box').show();
        $('#mf_pemasok_nama_new').focus();
    });
    $('#cancelPemasokInline').on('click', function() {
        $('#mf_pemasok_add_box').hide();
        $('#mf_pemasok_nama_new').val('');
    });
    $('#savePemasokInline').on('click', function() {
        var nama = $('#mf_pemasok_nama_new').val();
        if (!nama || nama.trim() === '') {
            Swal.fire('Error', 'Masukkan nama pemasok', 'warning');
            return;
        }
        $.ajax({
            url: '/erm/pemasok',
            type: 'POST',
            data: {
                nama: nama,
                _token: '{{ csrf_token() }}'
            },
            success: function(res) {
                var id = res.id || (res.data && res.data.id) || null;
                if (id) {
                    var newOption = new Option(nama, id, true, true);
                    $('#mf_pemasok_id').append(newOption).trigger('change');
                    $('#mf_pemasok_add_box').hide();
                    $('#mf_pemasok_nama_new').val('');
                    Swal.fire('Sukses', 'Pemasok ditambahkan', 'success');
                } else {
                    // fallback: search via select2 ajax and select the first matching
                    $('#mf_pemasok_id').val(null).trigger('change');
                    $('#mf_pemasok_add_box').hide();
                    $('#mf_pemasok_nama_new').val('');
                    Swal.fire('Sukses', 'Pemasok mungkin ditambahkan. Coba cari di select.', 'success');
                }
            },
            error: function(xhr) {
                let msg = 'Gagal menambahkan pemasok';
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                Swal.fire('Gagal', msg, 'error');
            }
        });
    });

    // Toggle inline add form for Principal
    $('#addPrincipalBtn').on('click', function() {
        $('#mf_principal_add_box').show();
        $('#mf_principal_nama_new').focus();
    });
    $('#cancelPrincipalInline').on('click', function() {
        $('#mf_principal_add_box').hide();
        $('#mf_principal_nama_new').val('');
    });
    $('#savePrincipalInline').on('click', function() {
        var nama = $('#mf_principal_nama_new').val();
        if (!nama || nama.trim() === '') {
            Swal.fire('Error', 'Masukkan nama principal', 'warning');
            return;
        }
        $.ajax({
            url: '/erm/principal',
            type: 'POST',
            data: {
                nama: nama,
                _token: '{{ csrf_token() }}'
            },
            success: function(res) {
                var id = res.id || (res.data && res.data.id) || null;
                if (id) {
                    var newOption = new Option(nama, id, true, true);
                    $('#mf_principal_id').append(newOption).trigger('change');
                    $('#mf_principal_add_box').hide();
                    $('#mf_principal_nama_new').val('');
                    Swal.fire('Sukses', 'Principal ditambahkan', 'success');
                } else {
                    $('#mf_principal_add_box').hide();
                    $('#mf_principal_nama_new').val('');
                    Swal.fire('Sukses', 'Principal mungkin ditambahkan. Coba cari di select.', 'success');
                }
            },
            error: function(xhr) {
                let msg = 'Gagal menambahkan principal';
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                Swal.fire('Gagal', msg, 'error');
            }
        });
    });
});
</script>
@endsection
