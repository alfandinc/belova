@extends('layouts.inventory.app')
@section('title', 'Barang Management')
@section('navbar')
    @include('layouts.inventory.navbar')
@endsection

@section('content')

<style>
    .dataTables_wrapper td {
        vertical-align: middle;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <h4 class="page-title">Barang Management</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Inventory</a></li>
                            <li class="breadcrumb-item active">Barang</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-3">
            <label for="filterGedung">Filter by Gedung</label>
            <select id="filterGedung" class="form-control select2">
                <option value="">All Gedung</option>
                @foreach($gedungs as $gedung)
                    <option value="{{ $gedung->id }}">{{ $gedung->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label for="filterRuangan">Filter by Ruangan</label>
            <select id="filterRuangan" class="form-control select2" disabled>
                <option value="">All Ruangan</option>
            </select>
        </div>
        <div class="col-md-3">
            <label for="filterTipeBarang">Filter by Tipe Barang</label>
            <select id="filterTipeBarang" class="form-control select2">
                <option value="">All Tipe Barang</option>
                @foreach($tipeBarangs as $tipeBarang)
                    <option value="{{ $tipeBarang->id }}">{{ $tipeBarang->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label for="filterAlat">Alat Tindakan</label>
            <select id="filterAlat" class="form-control">
                <option value="">Semua</option>
                <option value="1">Ya, alat tindakan</option>
                <option value="0">Belum / bukan</option>
            </select>
        </div>
    </div>
    <div class="row mb-2">
        <div class="col-12">
            <div class="mb-2">
                <strong>Keterangan Warna:</strong>
                <span style="display:inline-block;width:20px;height:20px;background:#f8d7da;border:1px solid #ccc;margin-right:5px;vertical-align:middle;"></span>
                <span style="margin-right:15px;vertical-align:middle;">Stok 0</span>
                <span style="display:inline-block;width:20px;height:20px;background:#fff3cd;border:1px solid #ccc;margin-right:5px;vertical-align:middle;"></span>
                <span style="vertical-align:middle;">Barang Under Maintenance</span>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header bg-primary">
            <h4 class="card-title text-white">Data Barang</h4>
            <button type="button" class="btn btn-light float-right" id="createNewBarang">Add New Barang</button>
        </div>
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center mb-2" style="gap:6px">
                <small class="text-muted"><strong>Alat Tindakan</strong> = alat yang biayanya ikut dihitung ke harga paket tindakan (laser, HIFU, RF, USG, dental unit, ...). Centang barang di tabel lalu:</small>
                <span class="small text-muted ml-auto" id="alatSelectedInfo"></span>
                <button type="button" class="btn btn-sm btn-info" id="alatTandai" disabled><i class="fas fa-check"></i> Tandai Alat Tindakan</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="alatLepas" disabled><i class="fas fa-times"></i> Bukan Alat Tindakan</button>
            </div>
            <table class="table table-bordered w-100 data-table">
                <thead>
                    <tr>
                        <th style="width:24px"><input type="checkbox" id="alatCheckAll" title="Pilih semua di halaman ini"></th>
                        <th width="5%">No</th>
                        <th>Kode</th>
                        <th>Name</th>
                        <th>Tipe Barang</th>
                        <th>Ruangan</th>
                        <th>Stok</th>
                        <th>Satuan</th>
                        <th>Alat Tindakan</th>
                        <th width="15%">Action</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="ajaxModel" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modelHeading"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="barangForm" name="barangForm" class="form-horizontal">
                    <input type="hidden" name="barang_id" id="barang_id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="kode" class="form-label">Kode Barang</label>
                                <input type="text" class="form-control" id="kode" name="kode" placeholder="Enter kode barang" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="name" name="name" placeholder="Enter barang name" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="tipe_barang_id" class="form-label">Tipe Barang</label>
                                <select class="form-control select2" id="tipe_barang_id" name="tipe_barang_id" required>
                                    <option value="">Select Tipe Barang</option>
                                    @foreach($tipeBarangs as $tipeBarang)
                                        <option value="{{ $tipeBarang->id }}">{{ $tipeBarang->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="ruangan_id" class="form-label">Ruangan</label>
                                <select class="form-control select2" id="ruangan_id" name="ruangan_id" required>
                                    <option value="">Select Ruangan</option>
                                    @foreach($ruangans as $ruangan)
                                        <option value="{{ $ruangan->id }}">{{ $ruangan->name }} ({{ $ruangan->gedung ? $ruangan->gedung->name : 'N/A' }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="satuan" class="form-label">Satuan</label>
                                <input type="text" class="form-control" id="satuan" name="satuan" placeholder="Enter satuan (e.g. pcs, unit)" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="stok" class="form-label">Stok</label>
                                <input type="number" class="form-control" id="stok" name="stok" placeholder="Enter initial stock" min="0" value="0" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="merk" class="form-label">Merk</label>
                                <input type="text" class="form-control" id="merk" name="merk" placeholder="Enter merk/brand">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="depreciation_rate" class="form-label">Depreciation Rate (%)</label>
                                <input type="number" class="form-control" id="depreciation_rate" name="depreciation_rate" placeholder="Enter depreciation rate" min="0" max="100" step="0.01">
                            </div>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label for="spec" class="form-label">Specification</label>
                        <textarea class="form-control" id="spec" name="spec" rows="3" placeholder="Enter specification"></textarea>
                    </div>
                    <div class="border rounded p-2 mb-3" style="background:#f6fbfd">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="is_alat_tindakan" name="is_alat_tindakan" value="1">
                            <label class="custom-control-label font-weight-bold" for="is_alat_tindakan">Alat Tindakan</label>
                            <small class="text-muted d-block">Centang kalau biaya alat ini ikut dihitung ke harga paket tindakan.</small>
                        </div>
                        <div id="alatFields" class="mt-2" style="display:none">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <label for="umur_pakai_tahun" class="form-label mb-0">Umur pakai (tahun)</label>
                                        <input type="number" class="form-control" id="umur_pakai_tahun" name="umur_pakai_tahun" min="1" max="50" step="1" placeholder="mis. 5">
                                        <small class="text-muted">Perkiraan sampai alat perlu diganti. Laser/HIFU/RF ± 5, USG 5–7, dental unit 7–10.</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <label for="target_pakai_per_bulan" class="form-label mb-0">Target pemakaian (kali / bulan / unit)</label>
                                        <input type="number" class="form-control" id="target_pakai_per_bulan" name="target_pakai_per_bulan" min="1" step="1" placeholder="mis. 100">
                                        <small class="text-muted">Berapa kali alat <em>seharusnya</em> dipakai dalam bulan yang normal.</small>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-0">
                                        <label for="harga_perolehan" class="form-label mb-0">Harga alat per unit (Rp) <span class="text-muted">— opsional</span></label>
                                        <input type="number" class="form-control" id="harga_perolehan" name="harga_perolehan" min="0" step="1" placeholder="kosong = pembelian terakhir">
                                        <small class="text-muted">Harga beli + ongkir + pasang + pelatihan. Kosongkan untuk memakai harga dari menu Pembelian. Alat lama tanpa nota: isi harga alat sejenis sekarang.</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-0">
                                        <label for="tanggal_perolehan" class="form-label mb-0">Tanggal mulai dipakai <span class="text-muted">— opsional</span></label>
                                        <input type="date" class="form-control" id="tanggal_perolehan" name="tanggal_perolehan" max="{{ date('Y-m-d') }}">
                                        <small class="text-muted">Untuk menghitung balik modal. Kosong = tanggal pembelian terakhir.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group text-right">
                        <button type="submit" class="btn btn-primary" id="saveBtn">Save</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Stock Modal -->
<div class="modal fade" id="stokModal" tabindex="-1" role="dialog" aria-labelledby="stokModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="stokModalLabel">Update Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="stokForm" name="stokForm" class="form-horizontal">
                    <input type="hidden" name="barang_id" id="stok_barang_id">
                    <div class="form-group mb-3">
                        <label for="jumlah" class="form-label">Jumlah</label>
                        <input type="number" class="form-control" id="jumlah" name="jumlah" placeholder="Enter stock quantity" min="0" required>
                    </div>
                    <div class="form-group text-end">
                        <button type="submit" class="btn btn-primary" id="updateStokBtn">Update</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .stok-zero-row {
        background-color: #f8d7da !important;
    }
    .maintenance-row {
        background-color: #fff3cd !important;
    }
</style>
@endsection

@section('scripts')
<script type="text/javascript">
    $(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Initialize select2 for filters (outside modal)
        $('#filterGedung').select2({
            width: '100%',
            dropdownParent: $(document.body)
        });
        $('#filterRuangan').select2({
            width: '100%',
            dropdownParent: $(document.body)
        });
        $('#filterTipeBarang').select2({
            width: '100%',
            dropdownParent: $(document.body)
        });

        // Handle Gedung filter change
        $('#filterGedung').on('change', function() {
            var gedungId = $(this).val();
            $('#filterRuangan').prop('disabled', true).html('<option value="">All Ruangan</option>');
            if (gedungId) {
                // Fetch ruangan by gedung
                $.get('/inventory/ruangan/by-gedung/' + gedungId, function(data) {
                    var options = '<option value="">All Ruangan</option>';
                    if (data && data.length > 0) {
                        $.each(data, function(i, ruangan) {
                            options += '<option value="' + ruangan.id + '">' + ruangan.name + '</option>';
                        });
                        $('#filterRuangan').html(options).prop('disabled', false);
                    } else {
                        $('#filterRuangan').html('<option value="">No Ruangan</option>').prop('disabled', true);
                    }
                });
            } else {
                $('#filterRuangan').prop('disabled', true).html('<option value="">All Ruangan</option>');
            }
            table.ajax.reload();
        });

        // Handle Ruangan filter change
        $('#filterRuangan').on('change', function() {
            table.ajax.reload();
        });

        // Handle Tipe Barang filter change
        $('#filterTipeBarang').on('change', function() {
            table.ajax.reload();
        });

        var table = $('.data-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('barang.index') }}",
                data: function (d) {
                    d.gedung_id = $('#filterGedung').val();
                    d.ruangan_id = $('#filterRuangan').val();
                    d.tipe_barang_id = $('#filterTipeBarang').val();
                    d.alat_tindakan = $('#filterAlat').val();
                }
            },
            columns: [
                {data: 'check', name: 'check', orderable: false, searchable: false},
                {data: 'DT_RowIndex', name: 'DT_RowIndex'},
                {data: 'kode', name: 'kode'},
                {data: 'name', name: 'name'},
                {data: 'tipe_barang', name: 'tipe_barang'},
                {data: 'ruangan', name: 'ruangan'},
                {data: 'stok', name: 'stok'},
                {data: 'satuan', name: 'satuan'},
                {data: 'alat_tindakan', name: 'alat_tindakan', orderable: false, searchable: false},
                {data: 'action', name: 'action', orderable: false, searchable: false},
                {data: 'under_maintenance', name: 'under_maintenance', visible: false, searchable: false},
            ],
            createdRow: function(row, data, dataIndex) {
                var stok = data.stok;
                if (typeof stok === 'string') stok = parseInt(stok);
                if (stok === 0) {
                    $(row).addClass('stok-zero-row');
                } else if (data.under_maintenance && data.under_maintenance === 'Proses') {
                    $(row).addClass('maintenance-row');
                }
            },
            drawCallback: function () { $('#alatCheckAll').prop('checked', false); }
        });

        // ===== Alat tindakan: filter, bulk mark, form fields =====
        var alatSelected = {};
        function refreshAlatSelected() {
            var n = Object.keys(alatSelected).length;
            $('#alatSelectedInfo').text(n ? n + ' barang dipilih' : '');
            $('#alatTandai, #alatLepas').prop('disabled', !n);
        }
        $('#filterAlat').on('change', function () { table.ajax.reload(); });
        $('.data-table').on('change', '.alat-check', function () {
            if (this.checked) alatSelected[this.value] = true; else delete alatSelected[this.value];
            refreshAlatSelected();
        });
        $('.data-table').on('draw.dt', function () {
            $('.data-table .alat-check').each(function () { this.checked = !!alatSelected[this.value]; });
        });
        $('#alatCheckAll').on('change', function () {
            var on = this.checked;
            $('.data-table .alat-check').each(function () { if (this.checked !== on) $(this).prop('checked', on).trigger('change'); });
        });
        function tandaiAlat(value) {
            var ids = Object.keys(alatSelected).map(Number);
            if (!ids.length) return;
            $.ajax({
                url: "{{ route('inventory.barang.alat-massal') }}", type: 'POST', contentType: 'application/json',
                headers: { Accept: 'application/json' }, data: JSON.stringify({ ids: ids, value: value })
            }).done(function (res) {
                alatSelected = {};
                refreshAlatSelected();
                table.draw(false);
                Swal.fire({ icon: 'success', title: 'Selesai', text: res.message });
            }).fail(function (xhr) {
                Swal.fire({ icon: 'error', title: 'Gagal', text: (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menyimpan.' });
            });
        }
        $('#alatTandai').on('click', function () { tandaiAlat(true); });
        $('#alatLepas').on('click', function () { tandaiAlat(false); });
        $('#is_alat_tindakan').on('change', function () { $('#alatFields').toggle(this.checked); });

        // Initialize select2 for modal (inside modal only)
        $('#tipe_barang_id').select2({
            dropdownParent: $('#ajaxModel'),
            width: '100%'
        });
        $('#ruangan_id').select2({
            dropdownParent: $('#ajaxModel'),
            width: '100%'
        });

        $('#createNewBarang').click(function() {
            $('#saveBtn').text('Save');
            $('#barang_id').val('');
            $('#barangForm').trigger("reset");
            $('#modelHeading').html("Create New Barang");
            $('#ajaxModel').modal('show');
            // Reset select2
            $('#tipe_barang_id').val('').trigger('change');
            $('#ruangan_id').val('').trigger('change');
            $('#is_alat_tindakan').prop('checked', false).trigger('change');
        });

        $('body').on('click', '.edit', function() {
            var barang_id = $(this).data('id');
            $.get("{{ route('barang.index') }}" + '/' + barang_id + '/edit', function(data) {
                $('#modelHeading').html("Edit Barang");
                $('#saveBtn').text('Update');
                $('#ajaxModel').modal('show');
                $('#barang_id').val(data.id);
                $('#name').val(data.name);
                $('#kode').val(data.kode);
                $('#satuan').val(data.satuan);
                $('#merk').val(data.merk);
                $('#spec').val(data.spec);
                $('#depreciation_rate').val(data.depreciation_rate);
                $('#tipe_barang_id').val(data.tipe_barang_id).trigger('change');
                $('#ruangan_id').val(data.ruangan_id).trigger('change');
                $('#stok').val(data.stok_barang ? data.stok_barang.jumlah : 0);
                $('#is_alat_tindakan').prop('checked', !!data.is_alat_tindakan).trigger('change');
                $('#umur_pakai_tahun').val(data.umur_pakai_tahun || '');
                $('#target_pakai_per_bulan').val(data.target_pakai_per_bulan || '');
                $('#harga_perolehan').val(data.harga_perolehan > 0 ? Math.round(data.harga_perolehan) : '');
                $('#tanggal_perolehan').val(data.tanggal_perolehan ? String(data.tanggal_perolehan).substring(0, 10) : '');
            });
        });

        $('body').on('click', '.editStok', function() {
            var barang_id = $(this).data('id');
            $.get("{{ route('barang.index') }}" + '/' + barang_id + '/edit', function(data) {
                $('#stok_barang_id').val(data.id);
                $('#jumlah').val(data.stok_barang ? data.stok_barang.jumlah : 0);
                $('#stokModal').modal('show');
            });
        });

        $('#saveBtn').click(function(e) {
            e.preventDefault();
            $(this).html('Saving..');

            $.ajax({
                data: $('#barangForm').serialize(),
                url: "{{ route('barang.store') }}",
                type: "POST",
                dataType: 'json',
                success: function(data) {
                    $('#barangForm').trigger("reset");
                    $('#ajaxModel').modal('hide');
                    table.draw();
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: data.message,
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message,
                        });
                    }
                },
                error: function(data) {
                    console.log('Error:', data);
                    $('#saveBtn').html('Save');
                    
                    // Show validation errors
                    var errors = data.responseJSON.errors;
                    if (errors) {
                        var errorMessage = '';
                        $.each(errors, function(key, value) {
                            errorMessage += value + '<br>';
                        });
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Validation Error',
                            html: errorMessage,
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred while saving the data.',
                        });
                    }
                }
            });
        });

        $('#updateStokBtn').click(function(e) {
            e.preventDefault();
            $(this).html('Updating..');

            $.ajax({
                data: {
                    'barang_id': $('#stok_barang_id').val(),
                    'jumlah': $('#jumlah').val()
                },
                url: "{{ route('inventory.barang.update-stok') }}",
                type: "POST",
                dataType: 'json',
                success: function(data) {
                    $('#stokForm').trigger("reset");
                    $('#stokModal').modal('hide');
                    table.draw();
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: data.message,
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message,
                        });
                    }
                },
                error: function(data) {
                    console.log('Error:', data);
                    $('#updateStokBtn').html('Update');
                    
                    // Show validation errors
                    var errors = data.responseJSON.errors;
                    if (errors) {
                        var errorMessage = '';
                        $.each(errors, function(key, value) {
                            errorMessage += value + '<br>';
                        });
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Validation Error',
                            html: errorMessage,
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred while updating the stock.',
                        });
                    }
                }
            });
        });

        $('body').on('click', '.delete', function() {
            var barang_id = $(this).data("id");
            
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "DELETE",
                        url: "{{ route('barang.store') }}" + '/' + barang_id,
                        success: function(data) {
                            table.draw();
                            if (data.success) {
                                Swal.fire(
                                    'Deleted!',
                                    data.message,
                                    'success'
                                );
                            } else {
                                Swal.fire(
                                    'Error!',
                                    data.message,
                                    'error'
                                );
                            }
                        },
                        error: function(data) {
                            console.log('Error:', data);
                            Swal.fire(
                                'Error!',
                                'Failed to delete the barang.',
                                'error'
                            );
                        }
                    });
                }
            });
        });
    });
</script>
@endsection
