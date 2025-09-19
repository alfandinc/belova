@extends('layouts.marketing.app')

@section('title', 'Manage Tindakan - Marketing')



@section('navbar')
    @include('layouts.marketing.navbar')
@endsection

@section('content')
<style>
@keyframes blink {
    0%   { opacity: 1; }
    25%  { opacity: 0.1; }
    50%  { opacity: 1; }
    75%  { opacity: 0.1; }
    100% { opacity: 1; }
}

.blink-icon {
    animation: blink 1s linear infinite;
    transition: opacity 0.2s;
}
</style>
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
                <div class="card-header d-flex align-items-center">
                    <h4 class="card-title mb-0">Tindakan List</h4>
                    <div class="ml-auto d-flex align-items-center">
                        <label for="filter_spesialis" class="mr-2 mb-0">Filter Specialist</label>
                        <select id="filter_spesialis" class="form-control" style="min-width:220px">
                            <option value="">All Specialists</option>
                        </select>
                    </div>
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
    <!-- Modal for Galeri Before After -->
<div class="modal fade" id="galeriBeforeAfterModal" tabindex="-1" role="dialog" aria-labelledby="galeriBeforeAfterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Galeri Before After</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="galeriBeforeAfterContent">
            // Clear kode tindakan table first
            $('#kodeTindakanTable tbody').empty();
            
                <!-- Content will be loaded via JS -->
                <style>
                #galeriBeforeAfterContent {
                    max-height: 60vh;
                    overflow-y: auto;
                }
                </style>
                <style>
                #galeriBeforeAfterContent {
                    max-height: 60vh;
                    overflow-y: auto;
                }
                .gallery-img {
                    max-width: 100%;
                    max-height: 300px;
                    border-radius: 8px;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                }
                .download-btn {
                    margin-top: 16px;
                    display: inline-block;
                }
                </style>
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

<!-- Modal for Create/Edit Tindakan -->
<!-- Make this modal non-dismissible by clicking backdrop or pressing Escape; only the top-right X can close it -->
<div class="modal fade" id="tindakanModal" tabindex="-1" role="dialog" aria-labelledby="tindakanModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tindakanModalLabel">Add New Tindakan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>            <form id="tindakanForm">
                @csrf
                <div class="modal-body" style="max-height:70vh; overflow-y:auto;">
                    <input type="hidden" id="tindakan_id" name="id">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="nama">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama" name="nama" required>
                            <div class="invalid-feedback" id="nama-error"></div>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="spesialis_id">Specialist <span class="text-danger">*</span></label>
                            <select class="form-control select2" id="spesialis_id" name="spesialis_id" required>
                                <option value="">Select Specialist</option>
                                <!-- Specialist options will be loaded via Ajax -->
                            </select>
                            <div class="invalid-feedback" id="spesialis_id-error"></div>
                        </div>
                    </div>
                    
                    {{-- <div class="form-group">
                        <label for="deskripsi">Description</label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"></textarea>
                        <div class="invalid-feedback" id="deskripsi-error"></div>
                    </div> --}}
                    
                    <div class="form-group">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="harga">Harga <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="harga" name="harga" step="0.01" required>
                            <div class="invalid-feedback" id="harga-error"></div>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="harga_diskon">Harga Diskon</label>
                            <input type="number" class="form-control" id="harga_diskon" name="harga_diskon" step="0.01">
                            <div class="invalid-feedback" id="harga_diskon-error"></div>
                            <div class="form-group mt-2">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="diskon_active" name="diskon_active" value="1">
                                    <label class="form-check-label" for="diskon_active">Diskon Active</label>
                                </div>
                                <div class="invalid-feedback" id="diskon_active-error"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Kode Tindakan</label>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="kodeTindakanTable">
                                <thead>
                                    <tr>
                                        <th>Kode Tindakan</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                            <button type="button" class="btn btn-sm btn-success" id="addKodeTindakanRow">Add Kode Tindakan</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="obat_ids">Bundled Obat/BHP</label>
                        <select class="form-control select2" id="obat_ids" name="obat_ids[]" multiple></select>
                        <div class="invalid-feedback" id="obat_ids-error"></div>
                    </div>
                    {{-- <div class="form-group">
                        <label>SOP List (Order with Up/Down, remove with X, add with text input)</label>
                        <ul id="tindakanSopList" class="list-group mb-2"></ul>
                        <div class="input-group">
                            <input type="text" id="tindakanAddSopText" class="form-control" placeholder="Enter SOP name...">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-success" id="tindakanAddSopBtn">Add</button>
                            </div>
                        </div>
                    </div> --}}
                </div>
                <div class="modal-footer">
                    <!-- Footer Close intentionally does not dismiss the modal: only the X in the header will close -->
                    <button type="button" class="btn btn-secondary" id="tindakanModalFooterClose">Close</button>
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
        // Add row to kode tindakan table
        $('#addKodeTindakanRow').click(function() {
            var rowIdx = $('#kodeTindakanTable tbody tr').length;
            var row = `<tr>
                <td>
                    <select class="form-control kode-tindakan-search" style="width:100%"></select>
                    <input type="hidden" class="kode-tindakan-id" name="kode_tindakan_ids[]" />
                </td>
                <td class="kode-tindakan-obat-cell"><span class="text-muted">No obat connected</span></td>
                <td><button type="button" class="btn btn-danger btn-sm remove-kode-tindakan-row">Remove</button></td>
            </tr>`;
            var $row = $(row);
            $('#kodeTindakanTable tbody').append($row);
            // Initialize Select2 for the new row
            $row.find('.kode-tindakan-search').select2({
                width: '100%',
                placeholder: 'Search kode tindakan...',
                minimumInputLength: 2,
                ajax: {
                    url: '/marketing/kodetindakan/search',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return { q: params.term };
                    },
                    processResults: function(data) {
                        return {
                            results: data.results.map(function(kode) {
                                return { id: kode.id, text: kode.text };
                            })
                        };
                    },
                    cache: true
                },
                dropdownParent: $('#tindakanModal')
            }).on('select2:select', function(e) {
                var kodeId = e.params.data.id;
                var kodeText = e.params.data.text;
                $(this).closest('td').find('.kode-tindakan-id').val(kodeId);
                var $obatCell = $(this).closest('tr').find('.kode-tindakan-obat-cell');
                // Fetch connected obats for selected kode tindakan
                $.ajax({
                    url: '/marketing/kodetindakan/' + kodeId + '/obats',
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        if (data && data.length > 0) {
                            var obatsHtml = '<ul class="list-group">';
                            data.forEach(function(obat) {
                                obatsHtml += `<li class="list-group-item p-1">${obat.nama} <span class="badge badge-info ml-1">${obat.qty || ''} ${obat.satuan_dosis || ''}</span> <span class="text-muted ml-1">${obat.dosis ? 'Dosis: '+obat.dosis : ''}</span></li>`;
                            });
                            obatsHtml += '</ul>';
                            $obatCell.html(obatsHtml);
                        } else {
                            $obatCell.html('<span class="text-muted">No obat connected</span>');
                        }
                    },
                    error: function() {
                        $obatCell.html('<span class="text-danger">Failed to load obat</span>');
                    }
                });
            });
        });
        // });

        // Remove row from kode tindakan table
        $(document).on('click', '.remove-kode-tindakan-row', function() {
            $(this).closest('tr').remove();
        });

        // Autocomplete for kode tindakan search
        $(document).on('input', '.kode-tindakan-search', function() {
            var $input = $(this);
            var term = $input.val();
            if (term.length < 2) return;
            $.ajax({
                url: '/marketing/kodetindakan/search',
                data: { q: term },
                success: function(data) {
                    var results = data.results || [];
                    var $list = $('<ul class="list-group position-absolute w-100" style="z-index:9999;"></ul>');
                    results.forEach(function(item) {
                        $list.append('<li class="list-group-item list-group-item-action kode-tindakan-autocomplete" data-id="'+item.id+'" data-text="'+item.text+'">'+item.text+'</li>');
                    });
                    $input.nextAll('.autocomplete-list').remove();
                    $input.after($list.addClass('autocomplete-list'));
                }
            });
        });

        // Select kode tindakan from autocomplete
        $(document).on('click', '.kode-tindakan-autocomplete', function() {
            var $li = $(this);
            var $input = $li.closest('td').find('.kode-tindakan-search');
            var $hidden = $li.closest('td').find('.kode-tindakan-id');
            $input.val($li.data('text'));
            $hidden.val($li.data('id'));
            $input.nextAll('.autocomplete-list').remove();
        });

        // Hide autocomplete when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).hasClass('kode-tindakan-search')) {
                $('.autocomplete-list').remove();
            }
        });
        // Set up CSRF token for all AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        
        // Initialize Select2 for Specialist (no AJAX, no minimumInputLength)
        $('#spesialis_id').select2({
            width: '100%',
            dropdownParent: $('#tindakanModal')
        });

        // Initialize Select2 for bundled obat (with AJAX and minimumInputLength)
        $('#obat_ids').select2({
            width: '100%',
            placeholder: 'Select Obat...',
            minimumInputLength: 2,
            ajax: {
                url: "{{ route('obat.search') }}",
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return { q: params.term };
                },
                processResults: function(data) {
                    return {
                        results: data.results.map(function(obat) {
                            return { id: obat.id, text: obat.nama };
                        })
                    };
                },
                cache: true
            },
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
                    table.ajax.reload(null, false); // don't reset paging
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
            ajax: {
                url: "{{ route('marketing.tindakan.data') }}",
                data: function(d) {
                    d.spesialis_id = $('#filter_spesialis').val();
                }
            },
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
                    // Populate both modal spesialis select and the filter select
                    $('#spesialis_id').empty();
                    $('#spesialis_id').append('<option value="">Select Specialist</option>');
                    $('#filter_spesialis').empty();
                    $('#filter_spesialis').append('<option value="">All Specialists</option>');
                    $.each(data, function(key, value) {
                        $('#spesialis_id').append('<option value="' + value.id + '">' + value.nama + '</option>');
                        $('#filter_spesialis').append('<option value="' + value.id + '">' + value.nama + '</option>');
                    });
                }
            });
        }

        // Reload DataTable when specialist filter changes
        $(document).on('change', '#filter_spesialis', function() {
            table.ajax.reload();
        });
        
        // Open modal to create new tindakan
        $('.add-tindakan').click(function() {
            resetForm();
            $('#tindakanModalLabel').text('Add New Tindakan');
            $('#tindakanSopList').empty(); // Clear SOP list when adding new tindakan
            $('#kodeTindakanTable tbody').empty(); // Clear kode tindakan table rows
            $('#tindakanModal').modal('show');
        });

        // Footer Close: ask confirmation before hiding since modal is non-dismissible
        $(document).on('click', '#tindakanModalFooterClose', function() {
            Swal.fire({
                title: 'Close form?',
                text: 'Any unsaved changes will be lost. Are you sure you want to close?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, close',
                cancelButtonText: 'Cancel'
            }).then(function(result) {
                if (result.value) {
                    $('#tindakanModal').modal('hide');
                }
            });
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
                       $('#harga_diskon').val(data.harga_diskon || '');
                       if (data.diskon_active && (data.diskon_active == 1 || data.diskon_active === true)) {
                           $('#diskon_active').prop('checked', true);
                       } else {
                           $('#diskon_active').prop('checked', false);
                       }
                    $('#spesialis_id').val(data.spesialis_id).trigger('change');
                    // Populate bundled obat
                    if (data.obat_ids && Array.isArray(data.obat_ids)) {
                        // Clear any existing options first
                        $('#obat_ids').empty();
                        // If backend returned full obat objects, use their names to create options
                        if (data.obats && Array.isArray(data.obats) && data.obats.length) {
                            data.obats.forEach(function(obat) {
                                var label = obat.nama || obat.name || ('Obat ' + obat.id);
                                var option = new Option(label, obat.id, true, true);
                                $('#obat_ids').append(option);
                            });
                            // Notify Select2 of change
                            $('#obat_ids').trigger('change');
                        } else {
                            // Fallback: set by ids only (may not show labels until user searches)
                            $('#obat_ids').val(data.obat_ids).trigger('change');
                        }
                    }
                    // Populate kode tindakan table rows using kode_tindakans array
                    $('#kodeTindakanTable tbody').empty();
                    if (data.kode_tindakans && Array.isArray(data.kode_tindakans)) {
                        data.kode_tindakans.forEach(function(kode) {
                            var obatsHtml = '';
                            if (kode.obats && kode.obats.length > 0) {
                                obatsHtml = '<ul class="list-group">';
                                kode.obats.forEach(function(obat) {
                                    obatsHtml += `<li class="list-group-item p-1">${obat.nama} <span class="badge badge-info ml-1">${obat.qty || ''} ${obat.satuan_dosis || ''}</span> <span class="text-muted ml-1">${obat.dosis ? 'Dosis: '+obat.dosis : ''}</span></li>`;
                                });
                                obatsHtml += '</ul>';
                            } else {
                                obatsHtml = '<span class="text-muted">No obat connected</span>';
                            }
                            var row = `<tr>
                                <td>
                                    <select class="form-control kode-tindakan-search" style="width:100%"></select>
                                    <input type="hidden" class="kode-tindakan-id" name="kode_tindakan_ids[]" value="${kode.id}" />
                                </td>
                                <td>${obatsHtml}</td>
                                <td><button type="button" class="btn btn-danger btn-sm remove-kode-tindakan-row">Remove</button></td>
                            </tr>`;
                            var $row = $(row);
                            $('#kodeTindakanTable tbody').append($row);
                            // Initialize Select2 for the row, set value
                            $row.find('.kode-tindakan-search').select2({
                                width: '100%',
                                placeholder: 'Search kode tindakan...',
                                minimumInputLength: 2,
                                ajax: {
                                    url: '/marketing/kodetindakan/search',
                                    dataType: 'json',
                                    delay: 250,
                                    data: function(params) {
                                        return { q: params.term };
                                    },
                                    processResults: function(data) {
                                        return {
                                            results: data.results.map(function(kode) {
                                                return { id: kode.id, text: kode.text };
                                            })
                                        };
                                    },
                                    cache: true
                                },
                                dropdownParent: $('#tindakanModal')
                            });
                            // Set initial value (id & text)
                            var option = new Option(kode.text, kode.id, true, true);
                            $row.find('.kode-tindakan-search').append(option).trigger('change');
                        });
                    }
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
                                table.ajax.reload(null, false); // don't reset paging
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
            // Collect bundled obat
            var obatIds = $('#obat_ids').val();
            if (Array.isArray(obatIds)) {
                obatIds.forEach(function(id) {
                    formData.push({name: 'obat_ids[]', value: id});
                });
            }
                // Collect kode tindakan from table rows
                $('#kodeTindakanTable tbody tr').each(function() {
                    var kodeId = $(this).find('.kode-tindakan-id').val();
                    if (kodeId) {
                        formData.push({name: 'kode_tindakan_ids[]', value: kodeId});
                    }
                });
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
                        table.ajax.reload(null, false); // don't reset paging
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
            // Ensure obat Select2 is fully cleared (remove any appended option elements)
            $('#obat_ids').empty().val(null).trigger('change');
                $('#kode_tindakan_ids').val('').trigger('change');
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
    
    // Galeri Before After button click handler
    $(document).on('click', '.galeri-before-after', function() {
        var tindakanId = $(this).data('id');
        $.ajax({
            url: '/marketing/tindakan/' + tindakanId + '/galeri-before-after',
            type: 'GET',
            success: function(data) {
                var html = '';
                if (!data || data.length === 0) {
                    html = '<p>No gallery found for this tindakan.</p>';
                } else {
                    data.forEach(function(item) {
                        html += '<div class="row mb-3">';
                        html += '<div class="col-md-4"><strong>Pasien:</strong> ' + item.pasien_nama + '</div>';
                        html += '<div class="col-md-4"><strong>Tanggal Visit:</strong> ' + item.tanggal_visit + '</div>';
                        html += '<div class="col-md-4"><strong>Dokter:</strong> ' + item.dokter_nama + '</div>';
                        if (typeof item.allow_post !== "undefined") {
                            html += '<div class="col-md-12 mt-1">';
                            if (item.allow_post) {
                                html += '<span class="badge badge-success"><i class="fas fa-check"></i> Diizinkan Posting ke Sosmed</span>';
                            } else {
                                html += '<span class="badge badge-secondary"><i class="fas fa-ban"></i> Tidak Diizinkan Posting</span>';
                            }
                            html += '</div>';
                        }
                        html += '</div>';
                        html += '<div class="row mb-4">';
                        html += '<div class="col-md-6 text-center">';
                        if (item.before_image) {
                            var beforeFileName = (item.nama_tindakan ? item.nama_tindakan.replace(/\s+/g, '_') : 'Tindakan') + '_' + (item.pasien_nama ? item.pasien_nama.replace(/\s+/g, '_') : 'Pasien') + '_' + (item.tanggal_visit ? item.tanggal_visit : '') + '_before';
                            html += '<img src="' + item.before_image + '" class="img-fluid gallery-img" alt="Before">';
                            html += '<a href="' + item.before_image + '" download="' + beforeFileName + '" class="btn btn-outline-primary btn-sm download-btn mt-3"><i class="fas fa-download"></i> Download Before</a>';
                        } else {
                            html += '<span class="text-muted">No Before Image</span>';
                        }
                        html += '</div>';
                        html += '<div class="col-md-6 text-center">';
                        if (item.after_image) {
                            var afterFileName = (item.nama_tindakan ? item.nama_tindakan.replace(/\s+/g, '_') : 'Tindakan') + '_' + (item.pasien_nama ? item.pasien_nama.replace(/\s+/g, '_') : 'Pasien') + '_' + (item.tanggal_visit ? item.tanggal_visit : '') + '_after';
                            html += '<img src="' + item.after_image + '" class="img-fluid gallery-img" alt="After">';
                            html += '<a href="' + item.after_image + '" download="' + afterFileName + '" class="btn btn-outline-success btn-sm download-btn mt-3"><i class="fas fa-download"></i> Download After</a>';
                        } else {
                            html += '<span class="text-muted">No After Image</span>';
                        }
                        html += '</div>';
                        html += '</div>';
                    });
                }
                $('#galeriBeforeAfterContent').html(html);
                $('#galeriBeforeAfterModal').modal('show');
            },
            error: function() {
                $('#galeriBeforeAfterContent').html('<p class="text-danger">Failed to load gallery data.</p>');
                $('#galeriBeforeAfterModal').modal('show');
            }
        });
    });
</script>
@endsection

