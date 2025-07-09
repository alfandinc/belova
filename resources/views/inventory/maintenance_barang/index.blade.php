@extends('layouts.inventory.app')
@section('title', 'Maintenance Barang')
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
                        <h4 class="page-title">Maintenance Barang</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Inventory</a></li>
                            <li class="breadcrumb-item active">Maintenance Barang</li>
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
                    <h4 class="card-title">Data Maintenance Barang</h4>
                    <button type="button" class="btn btn-primary" id="createNewMaintenance">Add New Maintenance</button>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-lg-4">
                            <label for="filterTanggal">Filter Tanggal Maintenance</label>
                            <input type="text" id="filterTanggal" class="form-control" placeholder="Pilih rentang tanggal" autocomplete="off">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered dt-responsive nowrap data-table" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                            <thead>
                                <tr>
                                    <th width="5%">No</th>
                                    <th>Barang</th>
                                    <th>Tanggal Maintenance</th>
                                    <th>Biaya Maintenance</th>
                                    <th>Nama Vendor</th>
                                    <th>No Faktur</th>
                                    <th>Tanggal Next Maintenance</th>
                                    <th>Status</th>
                                    <th>Keterangan</th>
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
                <form id="maintenanceForm" name="maintenanceForm" class="form-horizontal">
                    <input type="hidden" name="maintenance_id" id="maintenance_id">
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
                                <label for="tanggal_maintenance" class="form-label">Tanggal Maintenance</label>
                                <input type="date" class="form-control" id="tanggal_maintenance" name="tanggal_maintenance" value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="biaya_maintenance" class="form-label">Biaya Maintenance</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="biaya_maintenance" name="biaya_maintenance" min="0" step="0.01" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="nama_vendor" class="form-label">Nama Vendor</label>
                                <input type="text" class="form-control" id="nama_vendor" name="nama_vendor" placeholder="Enter vendor name">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="no_faktur" class="form-label">No Faktur</label>
                                <input type="text" class="form-control" id="no_faktur" name="no_faktur" placeholder="Enter invoice number">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="tanggal_next_maintenance" class="form-label">Tanggal Next Maintenance</label>
                                <input type="date" class="form-control" id="tanggal_next_maintenance" name="tanggal_next_maintenance">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="">-- Pilih Status --</option>
                                    <option value="Selesai">Selesai</option>
                                    <option value="Proses">Proses</option>
                                    <option value="Batal">Batal</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label for="keterangan" class="form-label">Keterangan</label>
                        <textarea class="form-control" id="keterangan" name="keterangan" rows="3" placeholder="Enter additional notes"></textarea>
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
                url: "{{ route('maintenance.index') }}",
                data: function (d) {
                    d.tanggal_range = $('#filterTanggal').val();
                }
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex'},
                {data: 'barang', name: 'barang'},
                {data: 'tanggal_maintenance', name: 'tanggal_maintenance'},
                {data: 'biaya_maintenance', name: 'biaya_maintenance'},
                {data: 'nama_vendor', name: 'nama_vendor'},
                {data: 'no_faktur', name: 'no_faktur'},
                {data: 'tanggal_next_maintenance', name: 'tanggal_next_maintenance'},
                {data: 'status', name: 'status'},
                {data: 'keterangan', name: 'keterangan'},
                {data: 'action', name: 'action', orderable: false, searchable: false},
            ],
            createdRow: function(row, data, dataIndex) {
                if (data.status === 'Batal') {
                    $(row).css('background-color', '#f8d7da'); // red
                } else if (data.status === 'Selesai') {
                    $(row).css('background-color', '#d4edda'); // green
                } else if (data.status === 'Proses') {
                    $(row).css('background-color', '#fff3cd'); // yellow
                }
            }
        });

        // Initialize select2
        $('.select2').select2({
            dropdownParent: $('#ajaxModel'),
            width: '100%'
        });

        $('#createNewMaintenance').click(function() {
            $('#saveBtn').text('Save');
            $('#maintenance_id').val('');
            $('#maintenanceForm').trigger("reset");
            $('#modelHeading').html("Create New Maintenance");
            $('#ajaxModel').modal('show');
            // Reset select2
            $('#barang_id').val('').trigger('change');
        });

        $('body').on('click', '.edit', function() {
            var maintenance_id = $(this).data('id');
            $.get("{{ route('maintenance.index') }}" + '/' + maintenance_id + '/edit', function(data) {
                $('#modelHeading').html("Edit Maintenance Barang");
                $('#saveBtn').text('Update');
                $('#ajaxModel').modal('show');
                $('#maintenance_id').val(data.id);
                $('#tanggal_maintenance').val(data.tanggal_maintenance);
                $('#status').val(data.status);
                $('#biaya_maintenance').val(data.biaya_maintenance);
                $('#keterangan').val(data.keterangan);
                $('#nama_vendor').val(data.nama_vendor);
                $('#no_faktur').val(data.no_faktur);
                $('#tanggal_next_maintenance').val(data.tanggal_next_maintenance);
                $('#barang_id').val(data.barang_id).trigger('change');
            });
        });

        $('#saveBtn').click(function(e) {
            e.preventDefault();
            $(this).html('Saving..');

            $.ajax({
                data: $('#maintenanceForm').serialize(),
                url: "{{ route('maintenance.store') }}",
                type: "POST",
                dataType: 'json',
                success: function(data) {
                    $('#maintenanceForm').trigger("reset");
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
            var maintenance_id = $(this).data("id");
            
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
                        url: "{{ route('maintenance.store') }}" + '/' + maintenance_id,
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
                                'Failed to delete the maintenance record.',
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
