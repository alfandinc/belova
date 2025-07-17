@extends('layouts.hrd.app')
@section('title', 'HRD | Performance Evaluation Periods')
@section('navbar')
    @include('layouts.hrd.navbar')
@endsection

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<div class="container">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Performance Evaluation Periods</h2>
        </div>
        <div class="col-md-4 text-right">
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#createPeriodModal">
                <i class="fa fa-plus"></i> Create New Period
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered table-striped" id="periodsTable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Period Modal -->
<div class="modal fade" id="createPeriodModal" tabindex="-1" role="dialog" aria-labelledby="createPeriodModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createPeriodModalLabel">Create New Evaluation Period</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="createPeriodForm">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="name">Period Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" required>
                    </div>
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" required>
                    </div>
                    <div class="form-group">
                        <label for="mode">Evaluation Mode</label>
                        <select class="form-control" id="mode" name="mode" required>
                            <option value="360">360</option>
                            <option value="satu arah">Satu Arah</option>
                        </select>
                    </div>
                    <input type="hidden" name="status" value="pending">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Create Period</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Period Modal -->
<div class="modal fade" id="editPeriodModal" tabindex="-1" role="dialog" aria-labelledby="editPeriodModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPeriodModalLabel">Edit Evaluation Period</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editPeriodForm">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_period_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_name">Period Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_start_date">Start Date</label>
                        <input type="date" class="form-control" id="edit_start_date" name="start_date" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_end_date">End Date</label>
                        <input type="date" class="form-control" id="edit_end_date" name="end_date" required>
                    </div>
                    <input type="hidden" id="edit_status" name="status" value="pending">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Period</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Initialize DataTable
        var periodsTable = $('#periodsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('hrd.performance.periods.index') }}",
                dataSrc: function(json) {
                    return json.data;
                }
            },
            columns: [
                { data: 0, name: 'name' },
                { data: 1, name: 'start_date' },
                { data: 2, name: 'end_date' },
                { 
                    data: 3, 
                    name: 'status',
                    render: function(data, type, row) {
                        return type === 'display' ? data : '';
                    } 
                },
                { 
                    data: 4, 
                    name: 'actions', 
                    orderable: false, 
                    searchable: false,
                    render: function(data, type, row) {
                        return type === 'display' ? data : '';
                    }
                }
            ],
            order: [[0, 'asc']],
            responsive: false, // Disable responsive mode to prevent hiding columns
            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "Showing 0 to 0 of 0 entries",
                infoFiltered: "(filtered from _MAX_ total entries)",
                emptyTable: "No evaluation periods found."
            },
            columnDefs: [
                { className: "text-center", targets: [3] },
                { className: "text-nowrap", targets: [4] }
            ]
        });
        
        // Initialize event handlers
        

        // Handle create form submission
        $('#createPeriodForm').on('submit', function(e) {
            e.preventDefault();
            
            $.ajax({
                url: "{{ route('hrd.performance.periods.store') }}",
                method: "POST",
                data: $(this).serialize(),
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    // Reset the form first
                    $('#createPeriodForm')[0].reset();
                    
                    // Close the modal
                    $('#createPeriodModal').modal('hide');
                    
                    // Show success message with SweetAlert
                    Swal.fire({
                        title: 'Success!',
                        text: 'Evaluation period created successfully',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        // Refresh DataTable
                        refreshPeriodsTable();
                    });
                },
                error: function(xhr) {
                    // Handle errors - display validation errors or other issues
                    let errors = xhr.responseJSON.errors;
                    let errorMessage = 'Something went wrong!';
                    
                    if (errors) {
                        errorMessage = Object.values(errors).flat().join('<br>');
                    }
                    
                    Swal.fire({
                        title: 'Error!',
                        html: errorMessage,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            });
        });
        
        // Handle edit form submission
        $('#editPeriodForm').on('submit', function(e) {
            e.preventDefault();
            
            const periodId = $('#edit_period_id').val();
            
            $.ajax({
                url: `/hrd/performance/periods/${periodId}`,
                method: "PUT",
                data: $(this).serialize(),
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    // Reset the form
                    $('#editPeriodForm')[0].reset();
                    
                    // Close the modal
                    $('#editPeriodModal').modal('hide');
                    
                    // Show success message
                    Swal.fire({
                        title: 'Success!',
                        text: 'Evaluation period updated successfully',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        // Refresh DataTable
                        refreshPeriodsTable();
                    });
                },
                error: function(xhr) {
                    // Handle errors
                    let errors = xhr.responseJSON.errors;
                    let errorMessage = 'Something went wrong!';
                    
                    if (errors) {
                        errorMessage = Object.values(errors).flat().join('<br>');
                    }
                    
                    Swal.fire({
                        title: 'Error!',
                        html: errorMessage,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            });
        });
        
        // Delete handler is now in reattachEventHandlers function
        
        // Function to refresh the periods table
        function refreshPeriodsTable() {
            $('#periodsTable').DataTable().ajax.reload(null, false);
        }
        
        // Edit period button handler (using event delegation for dynamically added elements)
        $('#periodsTable').on('click', '.edit-period', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            const start = $(this).data('start');
            const end = $(this).data('end');
            const status = $(this).data('status');
            
            // Store period ID
            $('#edit_period_id').val(id);
            
            // Fill form fields
            $('#edit_name').val(name);
            $('#edit_start_date').val(start);
            $('#edit_end_date').val(end);
        });
        
        // Delete button handler
        $('#periodsTable').on('click', '.btn-delete', function() {
            const periodId = $(this).data('id');
            
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.value) {
                    $.ajax({
                        url: `/hrd/performance/periods/${periodId}`,
                        method: 'DELETE',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            Swal.fire(
                                'Deleted!',
                                'Evaluation period has been deleted.',
                                'success'
                            ).then(() => {
                                refreshPeriodsTable();
                            });
                        },
                        error: function(xhr) {
                            Swal.fire(
                                'Error!',
                                xhr.responseJSON.message || 'Something went wrong!',
                                'error'
                            );
                        }
                    });
                }
            });
        });
        
        // Initiate button handler
        $('#periodsTable').on('click', '.btn-initiate', function() {
            const periodId = $(this).data('id');
            
            Swal.fire({
                title: 'Are you sure?',
                text: "This will initiate the evaluation period and create all necessary evaluations.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, initiate it!'
            }).then((result) => {
                if (result.value) {
                    // Show loading state
                    Swal.fire({
                        title: 'Processing...',
                        html: 'Please wait while we set up all evaluations.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Send AJAX request to initiate period
                    $.ajax({
                        url: `/hrd/performance/periods/${periodId}/initiate`,
                        method: 'POST',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            Swal.fire({
                                title: 'Success!',
                                text: 'Evaluation period initiated successfully.',
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                refreshPeriodsTable();
                            });
                        },
                        error: function(xhr) {
                            let errorMessage = 'Something went wrong!';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }
                            
                            Swal.fire({
                                title: 'Error!',
                                text: errorMessage,
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    });
                }
            });
        });
    });
</script>
@endsection