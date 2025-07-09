@extends('layouts.inventory.app')
@section('title', 'Pembelian Barang')
@section('navbar')
    @include('layouts.inventory.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <h4 class="page-title">Pembelian Barang</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Inventory</a></li>
                            <li class="breadcrumb-item active">Pembelian Barang</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Data Pembelian Barang</h4>
                    <button type="button" class="btn btn-primary" id="createNewPembelian">Add New Pembelian</button>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-lg-4">
                            <label for="filterTanggal">Filter Tanggal Pembelian</label>
                            <input type="text" id="filterTanggal" class="form-control" placeholder="Pilih rentang tanggal" autocomplete="off">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered dt-responsive nowrap data-table" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                            <thead>
                                <tr>
                                    <th width="5%">No</th>
                                    <th>No Faktur</th>
                                    <th>Barang</th>
                                    <th>Gedung</th>
                                    <th>Jumlah</th>
                                    <th>Harga Satuan</th>
                                    <th>Total Harga</th>
                                    <th>Tanggal</th>
                                    <th width="15%">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
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
                <form id="pembelianForm" name="pembelianForm" class="form-horizontal">
                    <input type="hidden" name="pembelian_id" id="pembelian_id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="no_faktur" class="form-label">No Faktur</label>
                                <input type="text" class="form-control" id="no_faktur" name="no_faktur" placeholder="Enter no faktur" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="tanggal_pembelian" class="form-label">Tanggal Pembelian</label>
                                <input type="date" class="form-control" id="tanggal_pembelian" name="tanggal_pembelian" value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="barang_id" class="form-label">Barang</label>
                                <select class="form-control select2" id="barang_id" name="barang_id" required>
                                    <option value="">Select Barang</option>
                                    @foreach($barangs as $barang)
                                        <option value="{{ $barang->id }}">{{ $barang->name }} ({{ $barang->kode }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="gedung_id" class="form-label">Gedung</label>
                                <select class="form-control select2" id="gedung_id" name="gedung_id" required>
                                    <option value="">Select Gedung</option>
                                    @foreach($gedungs as $gedung)
                                        <option value="{{ $gedung->id }}">{{ $gedung->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="jumlah" class="form-label">Jumlah</label>
                                <input type="number" class="form-control" id="jumlah" name="jumlah" min="1" value="1" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="harga_satuan" class="form-label">Harga Satuan</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="harga_satuan" name="harga_satuan" min="0" step="0.01" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label for="dibeli_dari" class="form-label">Dibeli Dari</label>
                        <input type="text" class="form-control" id="dibeli_dari" name="dibeli_dari" placeholder="Enter supplier name" required>
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
@endsection

@section('scripts')
<script type="text/javascript">
    $(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Initialize daterangepicker
        $('#filterTanggal').daterangepicker({
            autoUpdateInput: false,
            locale: {
                cancelLabel: 'Clear',
                format: 'DD-MM-YYYY'
            }
        });
        $('#filterTanggal').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('DD-MM-YYYY') + ' - ' + picker.endDate.format('DD-MM-YYYY'));
            table.ajax.reload();
        });
        $('#filterTanggal').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
            table.ajax.reload();
        });
        var table = $('.data-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('pembelian.index') }}",
                data: function (d) {
                    d.tanggal_range = $('#filterTanggal').val();
                }
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex'},
                {data: 'no_faktur', name: 'no_faktur'},
                {data: 'barang', name: 'barang'},
                {data: 'gedung', name: 'gedung'},
                {data: 'jumlah', name: 'jumlah'},
                {data: 'harga_satuan', name: 'harga_satuan', render: function(data) {
                    return 'Rp ' + parseFloat(data).toLocaleString('id-ID');
                }},
                {data: 'total_harga', name: 'total_harga'},
                {data: 'tanggal', name: 'tanggal'},
                {data: 'action', name: 'action', orderable: false, searchable: false},
            ]
        });

        // Initialize select2
        $('.select2').select2({
            dropdownParent: $('#ajaxModel'),
            width: '100%'
        });

        $('#createNewPembelian').click(function() {
            $('#saveBtn').text('Save');
            $('#pembelian_id').val('');
            $('#pembelianForm').trigger("reset");
            $('#modelHeading').html("Create New Pembelian");
            $('#ajaxModel').modal('show');
            // Reset select2
            $('#barang_id').val('').trigger('change');
            $('#gedung_id').val('').trigger('change');
        });

        $('body').on('click', '.edit', function() {
            var pembelian_id = $(this).data('id');
            $.get("{{ route('pembelian.index') }}" + '/' + pembelian_id + '/edit', function(data) {
                $('#modelHeading').html("Edit Pembelian Barang");
                $('#saveBtn').text('Update');
                $('#ajaxModel').modal('show');
                $('#pembelian_id').val(data.id);
                $('#no_faktur').val(data.no_faktur);
                $('#tanggal_pembelian').val(data.tanggal_pembelian);
                $('#jumlah').val(data.jumlah);
                $('#harga_satuan').val(data.harga_satuan);
                $('#dibeli_dari').val(data.dibeli_dari);
                $('#barang_id').val(data.barang_id).trigger('change');
                $('#gedung_id').val(data.gedung_id).trigger('change');
            });
        });

        $('#saveBtn').click(function(e) {
            e.preventDefault();
            $(this).html('Saving..');

            $.ajax({
                data: $('#pembelianForm').serialize(),
                url: "{{ route('pembelian.store') }}",
                type: "POST",
                dataType: 'json',
                success: function(data) {
                    $('#pembelianForm').trigger("reset");
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

        $('body').on('click', '.delete', function() {
            var pembelian_id = $(this).data("id");
            
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this! This will also reduce the stock.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "DELETE",
                        url: "{{ route('pembelian.store') }}" + '/' + pembelian_id,
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
                                'Failed to delete the pembelian.',
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
