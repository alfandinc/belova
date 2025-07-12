@extends('layouts.marketing.app')

@section('title', 'Manage Tindakan - Marketing')

@section('navbar')
    @include('layouts.marketing.navbar')
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="row">
                    <div class="col">
                        <h4 class="page-title">Manage Tindakan</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('marketing.dashboard') }}">Marketing</a></li>
                            <li class="breadcrumb-item active">Tindakan Management</li>
                        </ol>
                    </div>
                    <div class="col-auto align-self-center">
                        <button type="button" class="btn btn-primary waves-effect waves-light add-tindakan">
                            <i class="fas fa-plus mr-2"></i> Add Tindakan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main content -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Tindakan List</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tindakan-table" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Price</th>
                                    <th>Specialist</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
<!-- Modal for Edit SOP Tindakan -->
<div class="modal fade" id="editSopTindakanModal" tabindex="-1" role="dialog" aria-labelledby="editSopTindakanModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSopTindakanModalLabel">Edit SOP Tindakan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="tindakan_id" value="">
                <div class="form-group">
                    <label>SOP List (Order with Up/Down, remove with X, add with search)</label>
                    <ul id="sopSortableList" class="list-group mb-2">
                        <!-- Assigned SOPs will be loaded here -->
                    </ul>
                    <div class="input-group">
                        <select id="addSopSelect" class="form-control" style="width:100%"></select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveSopTindakanBtn">Save</button>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Modal for Create/Edit Tindakan -->
<div class="modal fade" id="tindakanModal" tabindex="-1" role="dialog" aria-labelledby="tindakanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tindakanModalLabel">Add New Tindakan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>            <form id="tindakanForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="tindakan_id" name="id">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    
                    <div class="form-group">
                        <label for="nama">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nama" name="nama" required>
                        <div class="invalid-feedback" id="nama-error"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="deskripsi">Description</label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"></textarea>
                        <div class="invalid-feedback" id="deskripsi-error"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="harga">Price (Rp) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="harga" name="harga" step="0.01" required>
                        <div class="invalid-feedback" id="harga-error"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="spesialis_id">Specialist <span class="text-danger">*</span></label>
                        <select class="form-control select2" id="spesialis_id" name="spesialis_id" required>
                            <option value="">Select Specialist</option>
                            <!-- Specialist options will be loaded via Ajax -->
                        </select>
                        <div class="invalid-feedback" id="spesialis_id-error"></div>
                    </div>
                    <div class="form-group">
                        <label>SOP List (Order with Up/Down, remove with X, add with text input)</label>
                        <ul id="tindakanSopList" class="list-group mb-2"></ul>
                        <div class="input-group">
                            <input type="text" id="tindakanAddSopText" class="form-control" placeholder="Enter SOP name...">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-success" id="tindakanAddSopBtn">Add</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="saveBtn">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>

        // Add SOP to tindakan list from text input
        $('#tindakanAddSopBtn').click(function() {
            var sopText = $('#tindakanAddSopText').val().trim();
            if (!sopText) return;
            // Prevent duplicate by name (case-insensitive)
            var duplicate = false;
            $('#tindakanSopList li').each(function() {
                if ($(this).find('.sop-name').text().toLowerCase() === sopText.toLowerCase()) {
                    duplicate = true;
                }
            });
            if (duplicate) return;
            var order = $('#tindakanSopList li').length + 1;
            $('#tindakanSopList').append('<li class="list-group-item d-flex align-items-center" data-name="'+sopText.replace(/"/g, '&quot;')+'">'
                +'<span class="sop-order badge badge-secondary mr-2">'+order+'</span>'
                +'<span class="sop-name">'+sopText+'</span>'
                +'<div class="ml-auto btn-group btn-group-sm" role="group">'
                    +'<button type="button" class="btn btn-light tindakan-move-up" title="Up">&#8593;</button>'
                    +'<button type="button" class="btn btn-light tindakan-move-down" title="Down">&#8595;</button>'
                    +'<button type="button" class="btn btn-danger tindakan-remove-sop-btn">&times;</button>'
                +'</div>'
            +'</li>');
            updateTindakanSopOrderNumbers();
            $('#tindakanAddSopText').val('');
        });

        // Add SOP to tindakan list when selected
        $('#tindakanAddSopSelect').on('select2:select', function(e) {
            var sopId = e.params.data.id;
            var sopText = e.params.data.text;
            if (!sopId) return;
            // Prevent duplicate
            if ($('#tindakanSopList li[data-id="'+sopId+'"]')[0]) return;
            var order = $('#tindakanSopList li').length + 1;
            $('#tindakanSopList').append('<li class="list-group-item d-flex align-items-center" data-id="'+sopId+'">'
                +'<span class="sop-order badge badge-secondary mr-2">'+order+'</span>'
                +'<span>'+sopText+'</span>'
                +'<div class="ml-auto btn-group btn-group-sm" role="group">'
                    +'<button type="button" class="btn btn-light tindakan-move-up" title="Up">&#8593;</button>'
                    +'<button type="button" class="btn btn-light tindakan-move-down" title="Down">&#8595;</button>'
                    +'<button type="button" class="btn btn-danger tindakan-remove-sop-btn">&times;</button>'
                +'</div>'
            +'</li>');
            updateTindakanSopOrderNumbers();
            $('#tindakanAddSopSelect').val(null).trigger('change');
        });

        // Move SOP up in tindakan modal
        $(document).on('click', '.tindakan-move-up', function() {
            var li = $(this).closest('li');
            var prev = li.prev('li');
            if (prev.length) {
                prev.before(li);
                updateTindakanSopOrderNumbers();
            }
        });

        // Move SOP down in tindakan modal
        $(document).on('click', '.tindakan-move-down', function() {
            var li = $(this).closest('li');
            var next = li.next('li');
            if (next.length) {
                next.after(li);
                updateTindakanSopOrderNumbers();
            }
        });

        // Remove SOP from tindakan list
        $(document).on('click', '.tindakan-remove-sop-btn', function() {
            $(this).closest('li').remove();
            updateTindakanSopOrderNumbers();
        });

        // Update order numbers for tindakan SOPs
        function updateTindakanSopOrderNumbers() {
            $('#tindakanSopList li').each(function(idx) {
                $(this).find('.sop-order').text(idx+1);
            });
        }
    $(document).ready(function() {
        // Set up CSRF token for all AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        
        // Initialize Select2
        $('.select2').select2({
            width: '100%',
            dropdownParent: $('#tindakanModal')
        });


        // Initialize Select2 for add SOP
        $('#addSopSelect').select2({
            width: '100%',
            placeholder: 'Search SOP...',
            ajax: {
                url: '/marketing/sop/search',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return { q: params.term };
                },
                processResults: function(data) {
                    return {
                        results: data.map(function(sop) {
                            return { id: sop.id, text: sop.nama_sop };
                        })
                    };
                },
                cache: true
            },
            minimumInputLength: 1
        });

        // Load only assigned SOPs for tindakan
        $(document).on('click', '.edit-sop-tindakan', function() {
            var tindakanId = $(this).data('id');
            $('#editSopTindakanModal').modal('show');
            $('#editSopTindakanModal input[name="tindakan_id"]').val(tindakanId);
            $.ajax({
                url: '/marketing/tindakan/' + tindakanId + '/sop',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    var sopList = $('#sopSortableList');
                    sopList.empty();
                    let sopMap = {};
                    data.all_sop.forEach(sop => sopMap[sop.id] = sop);
                    data.selected_sop_ids.forEach(function(sopId, idx, arr) {
                        let sop = sopMap[sopId];
                        if (sop) {
                            sopList.append('<li class="list-group-item d-flex align-items-center" data-id="'+sop.id+'">'
                                +'<span class="sop-order badge badge-secondary mr-2">'+(idx+1)+'</span>'
                                +'<span>'+sop.nama_sop+'</span>'
                                +'<div class="ml-auto btn-group btn-group-sm" role="group">'
                                    +'<button type="button" class="btn btn-light move-up" title="Up">&#8593;</button>'
                                    +'<button type="button" class="btn btn-light move-down" title="Down">&#8595;</button>'
                                    +'<button type="button" class="btn btn-danger remove-sop-btn">&times;</button>'
                                +'</div>'
                            +'</li>');
                        }
                    });
                }
            });
            // Reset select2
            $('#addSopSelect').val(null).trigger('change');
        });

        // Add SOP to list when selected from Select2
        $('#addSopSelect').on('select2:select', function(e) {
            var sopId = e.params.data.id;
            var sopText = e.params.data.text;
            if (!sopId) return;
            // Prevent duplicate
            if ($('#sopSortableList li[data-id="'+sopId+'"]')[0]) return;
            var order = $('#sopSortableList li').length + 1;
            $('#sopSortableList').append('<li class="list-group-item d-flex align-items-center" data-id="'+sopId+'">'
                +'<span class="sop-order badge badge-secondary mr-2">'+order+'</span>'
                +'<span>'+sopText+'</span>'
                +'<div class="ml-auto btn-group btn-group-sm" role="group">'
                    +'<button type="button" class="btn btn-light move-up" title="Up">&#8593;</button>'
                    +'<button type="button" class="btn btn-light move-down" title="Down">&#8595;</button>'
                    +'<button type="button" class="btn btn-danger remove-sop-btn">&times;</button>'
                +'</div>'
            +'</li>');
            updateSopOrderNumbers();
            $('#addSopSelect').val(null).trigger('change');
        });
        // Move SOP up
        $(document).on('click', '.move-up', function() {
            var li = $(this).closest('li');
            var prev = li.prev('li');
            if (prev.length) {
                prev.before(li);
                updateSopOrderNumbers();
            }
        });

        // Move SOP down
        $(document).on('click', '.move-down', function() {
            var li = $(this).closest('li');
            var next = li.next('li');
            if (next.length) {
                next.after(li);
                updateSopOrderNumbers();
            }
        });

        // Update order numbers after any change
        function updateSopOrderNumbers() {
            $('#sopSortableList li').each(function(idx) {
                $(this).find('.sop-order').text(idx+1);
            });
        }

        // Also update order numbers when removing
        $(document).on('click', '.remove-sop-btn', function() {
            $(this).closest('li').remove();
            updateSopOrderNumbers();
        });

        // Remove SOP from list
        $(document).on('click', '.remove-sop-btn', function() {
            $(this).closest('li').remove();
        });

        // Save SOPs for tindakan (with order)
        $('#saveSopTindakanBtn').click(function() {
            var tindakanId = $('#editSopTindakanModal input[name="tindakan_id"]').val();
            var sopIds = [];
            $('#sopSortableList li').each(function() {
                sopIds.push($(this).attr('data-id'));
            });
            $.ajax({
                url: '/marketing/tindakan/' + tindakanId + '/sop',
                type: 'POST',
                data: {
                    sop_ids: sopIds,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    $('#editSopTindakanModal').modal('hide');
                    Swal.fire({ icon: 'success', title: 'Success', text: response.message });
                    table.ajax.reload();
                },
                error: function(xhr) {
                    showError(xhr.responseJSON.message);
                }
            });
        });
        // jQuery UI Sortable CSS (for highlight)
        $('<style>.ui-state-highlight{height:2.5em;line-height:1.2em;background:#f0f0f0;border:1px dashed #ccc;}</style>').appendTo('head');
        
        // Load specialists on page load
        loadSpesialisasi();
        
        // Initialize DataTable
        var table = $('#tindakan-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('marketing.tindakan.data') }}",
            columns: [
                {data: 'id', name: 'id'},
                {data: 'nama', name: 'nama'},
                {data: 'deskripsi', name: 'deskripsi'},
                {
                    data: 'harga', 
                    name: 'harga',
                    render: function(data) {
                        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(data);
                    }
                },
                {data: 'spesialis_nama', name: 'spesialis_nama'},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ]
        });
        
        // Load specialists function
        function loadSpesialisasi() {
            $.ajax({
                url: "{{ route('marketing.spesialisasi.list') }}",
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    $('#spesialis_id').empty();
                    $('#spesialis_id').append('<option value="">Select Specialist</option>');
                    $.each(data, function(key, value) {
                        $('#spesialis_id').append('<option value="' + value.id + '">' + value.nama + '</option>');
                    });
                }
            });
        }
        
        // Open modal to create new tindakan
        $('.add-tindakan').click(function() {
            resetForm();
            $('#tindakanModalLabel').text('Add New Tindakan');
            $('#tindakanSopList').empty(); // Clear SOP list when adding new tindakan
            $('#tindakanModal').modal('show');
        });
        
        // Edit tindakan
        $(document).on('click', '.edit-tindakan', function() {
            resetForm();
            var id = $(this).data('id');
            $('#tindakanModalLabel').text('Edit Tindakan');
            // Clear SOP list first
            $('#tindakanSopList').empty();
            $.ajax({
                url: "/marketing/tindakan/" + id,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    $('#tindakan_id').val(data.id);
                    $('#nama').val(data.nama);
                    $('#deskripsi').val(data.deskripsi);
                    $('#harga').val(data.harga);
                    $('#spesialis_id').val(data.spesialis_id).trigger('change');
                    // Populate SOPs if available (ordered by urutan if present)
                    if (data.sop && Array.isArray(data.sop)) {
                        data.sop.sort(function(a, b) {
                            return (a.urutan || 0) - (b.urutan || 0);
                        });
                        data.sop.forEach(function(sop, idx) {
                            var order = idx + 1;
                            var sopName = sop.nama_sop || sop.nama || sop.name || '';
                            $('#tindakanSopList').append('<li class="list-group-item d-flex align-items-center" data-name="'+sopName.replace(/"/g, '&quot;')+'">'
                                +'<span class="sop-order badge badge-secondary mr-2">'+order+'</span>'
                                +'<span class="sop-name">'+sopName+'</span>'
                                +'<div class="ml-auto btn-group btn-group-sm" role="group">'
                                    +'<button type="button" class="btn btn-light tindakan-move-up" title="Up">&#8593;</button>'
                                    +'<button type="button" class="btn btn-light tindakan-move-down" title="Down">&#8595;</button>'
                                    +'<button type="button" class="btn btn-danger tindakan-remove-sop-btn">&times;</button>'
                                +'</div>'
                            +'</li>');
                        });
                    }
                    $('#tindakanModal').modal('show');
                },
                error: function(xhr) {
                    showError(xhr.responseJSON.message);
                }
            });
        });
        
        // Delete tindakan
        $(document).on('click', '.delete-tindakan', function() {
            var id = $(this).data('id');
            
            Swal.fire({
                title: 'Are you sure?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.value) {                    $.ajax({
                        url: "/marketing/tindakan/" + id,
                        type: 'DELETE',
                        data: {
                            "_token": "{{ csrf_token() }}"
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire(
                                    'Deleted!',
                                    response.message,
                                    'success'
                                );
                                table.ajax.reload();
                            } else {
                                showError(response.message);
                            }
                        },
                        error: function(xhr) {
                            showError(xhr.responseJSON.message);
                        }
                    });
                }
            });
        });
        
        // Form submission
        $('#tindakanForm').submit(function(e) {
            e.preventDefault();
            var formData = $(this).serializeArray();
            // Collect SOP names and their order
            var sopNames = [];
            $('#tindakanSopList li').each(function() {
                sopNames.push($(this).find('.sop-name').text());
            });
            formData.push({name: 'sop_names', value: sopNames});
            var url = "{{ route('marketing.tindakan.store') }}";
            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        $('#tindakanModal').modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.message
                        });
                        table.ajax.reload();
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        var errors = xhr.responseJSON.errors;
                        displayErrors(errors);
                    } else {
                        showError(xhr.responseJSON.message);
                    }
                }
            });
        });
        
        // Display validation errors
        function displayErrors(errors) {
            resetErrors();
            $.each(errors, function(field, messages) {
                var input = $('#' + field);
                input.addClass('is-invalid');
                $('#' + field + '-error').text(messages[0]);
            });
        }
        
        // Reset errors
        function resetErrors() {
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').text('');
        }
        
        // Reset form
        function resetForm() {
            $('#tindakanForm')[0].reset();
            resetErrors();
            $('#tindakan_id').val('');
            $('.select2').val('').trigger('change');
        }
        
        // Show error alert
        function showError(message) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message
            });
        }
    });
</script>
@endsection
