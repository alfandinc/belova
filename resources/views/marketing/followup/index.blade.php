@extends('layouts.marketing.app')

@section('title', 'Follow Up Customer')

@section('navbar')
    @include('layouts.marketing.navbar')
@endsection

@section('content')
<div class="container-fluid mt-4">
    <div class="row mb-4 align-items-stretch" style="gap:0.5rem;">
        <div class="col-md-2">
            <div class="card h-100 shadow-sm border-0" style="background:linear-gradient(135deg,#e3f0ff 0%,#f8f9fa 100%);">
                <div class="card-header text-center py-2" style="background:transparent; border-bottom:none;">
                    <span style="font-weight:700; font-size:1.5em; color:#007bff; letter-spacing:1px;">Follow Up</span>
                </div>
                <div class="card-body d-flex flex-column align-items-center justify-content-center" style="padding:0.7rem 1.7rem;">
                    <div id="followUpCount" style="font-size:3.7em; font-weight:900; color:#007bff; text-shadow:0 2px 8px rgba(0,123,255,0.15); line-height:1;">0</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card h-100 shadow-sm border-0" style="background:linear-gradient(135deg,#f8f9fa 0%,#e3f0ff 100%);">
                <div class="card-header text-center py-2" style="background:transparent; border-bottom:none;">
                    <span style="font-weight:700; font-size:1.5em; color:#6c757d; letter-spacing:1px;">Status Respon</span>
                </div>
                <div class="card-body d-flex flex-column align-items-center justify-content-center" style="padding:0.7rem 1.7rem;">
                    <div class="w-100 mt-1">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span style="font-weight:700; color:#28a745; font-size:1.2em;">Direspon</span>
                            <span>
                                <span id="followUpDirespon" style="font-size:1.8em; font-weight:900; color:#28a745; line-height:1;">0</span>
                                <span id="followUpDiresponPercent" style="font-size:1em; color:#28a745; font-weight:700; margin-left:6px;">(0%)</span>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span style="font-weight:700; color:#dc3545; font-size:1.2em;">Tidak Direspon</span>
                            <span>
                                <span id="followUpTidakDirespon" style="font-size:1.8em; font-weight:900; color:#dc3545; line-height:1;">0</span>
                                <span id="followUpTidakDiresponPercent" style="font-size:1em; color:#dc3545; font-weight:700; margin-left:6px;">(0%)</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card h-100 shadow-sm border-0" style="background:linear-gradient(135deg,#f8f9fa 0%,#e3f0ff 100%);">
                <div class="card-header text-center py-2" style="background:transparent; border-bottom:none;">
                    <span style="font-weight:700; font-size:1.5em; color:#6c757d; letter-spacing:1px;">Status Booking</span>
                </div>
                <div class="card-body d-flex flex-column align-items-center justify-content-center" style="padding:0.7rem 1.7rem;">
                    <div class="w-100 mt-1">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span style="font-weight:700; color:#dc3545; font-size:1.2em;">Batal</span>
                            <span id="bookingBatal" style="font-size:1.8em; font-weight:900; color:#dc3545; line-height:1;">0</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span style="font-weight:700; color:#ffc107; font-size:1.2em;">Menunggu</span>
                            <span id="bookingMenunggu" style="font-size:1.8em; font-weight:900; color:#ffc107; line-height:1;">0</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span style="font-weight:700; color:#28a745; font-size:1.2em;">Sukses</span>
                            <span id="bookingSukses" style="font-size:1.8em; font-weight:900; color:#28a745; line-height:1;">0</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card h-100 shadow-sm border-0" style="background:linear-gradient(135deg,#e3f0ff 0%,#f8f9fa 100%);">
                <div class="card-header text-center py-2" style="background:transparent; border-bottom:none;">
                    <span style="font-weight:700; font-size:1.5em; color:#6c757d; letter-spacing:1px;">Kategori Jumlah</span>
                </div>
                <div class="card-body d-flex flex-column align-items-center justify-content-center" style="padding:0.7rem 1.7rem;">
                    <div class="w-100 mt-1">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span style="font-weight:700; color:#007bff; font-size:1.1em;">Produk</span>
                            <span id="kategoriProduk" style="font-size:1.5em; font-weight:900; color:#007bff; line-height:1;">0</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span style="font-weight:700; color:#e83e8c; font-size:1.1em;">Perawatan</span>
                            <span id="kategoriPerawatan" style="font-size:1.5em; font-weight:900; color:#e83e8c; line-height:1;">0</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span style="font-weight:700; color:#ffc107; font-size:1.1em;">Reseller</span>
                            <span id="kategoriReseller" style="font-size:1.5em; font-weight:900; color:#ffc107; line-height:1;">0</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span style="font-weight:700; color:#6f42c1; font-size:1.1em;">Slimming</span>
                            <span id="kategoriSlimming" style="font-size:1.5em; font-weight:900; color:#6f42c1; line-height:1;">0</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 shadow-sm border-0" style="background:linear-gradient(135deg,#f8f9fa 0%,#e3f0ff 100%);">
                <div class="card-header text-center py-2" style="background:transparent; border-bottom:none;">
                    <span style="font-weight:700; font-size:1.5em; color:#6c757d; letter-spacing:1px;">Top Sales</span>
                </div>
                <div class="card-body d-flex flex-column align-items-center justify-content-center" style="padding:0.7rem 1.7rem; width:100%;">
                    <ul id="topSalesList" class="list-unstyled w-100 mb-0" style="font-size:1.1em;">
                        <!-- Top sales will be injected here -->
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Follow Up Customer</h4>
            <button class="btn btn-success" id="addFollowUpBtn">Tambah Follow Up</button>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3" style="max-width:300px;">
                    <label for="dateRange" class="form-label">Filter Tanggal:</label>
                    <input type="text" id="dateRange" class="form-control" autocomplete="off" placeholder="Pilih rentang tanggal">
                </div>
                <div class="col-md-3" style="max-width:300px;">
                    <label for="kategoriFilter" class="form-label">Filter Kategori:</label>
                    <select id="kategoriFilter" class="form-control" multiple="multiple" style="width:100%"></select>
                </div>
                <div class="col-md-3" style="max-width:200px;">
                    <label for="statusResponFilter" class="form-label">Filter Status Respon:</label>
                    <select id="statusResponFilter" class="form-control" style="width:100%">
                        <option value="" selected>Semua</option>
                        <option value="Direspon">Direspon</option>
                        <option value="Tidak Direspon">Tidak Direspon</option>
                    </select>
                </div>
                <div class="col-md-3" style="max-width:200px;">
                    <label for="statusBookingFilter" class="form-label">Filter Status Booking:</label>
                    <select id="statusBookingFilter" class="form-control" style="width:100%">
                        <option value="" selected>Semua</option>
                        <option value="Sukses">Sukses</option>
                        <option value="Menunggu">Menunggu</option>
                        <option value="Batal">Batal</option>
                    </select>
                </div>
            </div>
            <table class="table table-bordered" id="followupTable">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Pasien</th>
                        <th>Kategori</th>
                        <th>Sales</th>
                        <th>Status Respon</th>
                        <th>Rencana Tindak Lanjut</th>
                        <th>Status Booking</th>
                        <th>Catatan</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="followupModal" tabindex="-1" role="dialog" aria-labelledby="followupModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <form id="followupForm">

        <div class="modal-header">
          <h5 class="modal-title" id="followupModalLabel">Tambah Follow Up</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body" style="max-height:70vh; overflow-y:auto;">
          <input type="hidden" id="followup_id" name="followup_id">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="pasienSelect">Pasien</label>
                <select id="pasienSelect" name="pasien_id" class="form-control" style="width:100%"></select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="kategori">Kategori</label>
                <select class="form-control" name="kategori[]" id="kategori" multiple="multiple" style="width:100%">
                  <option value="Produk">Produk</option>
                  <option value="Perawatan">Perawatan</option>
                  <option value="Reseller">Reseller</option>
                  <option value="Slimming">Slimming</option>
                </select>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="salesSelect">Sales</label>
                <select id="salesSelect" name="sales_id" class="form-control" style="width:100%"></select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="status_respon">Status Respon</label>
                <select class="form-control" name="status_respon" id="status_respon" required>
                  <option value="">Pilih Status</option>
                  <option value="Direspon">Direspon</option>
                  <option value="Tidak Direspon">Tidak Direspon</option>
                </select>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="bukti_respon">Bukti Respon (Screenshot)</label>
                <input type="file" class="form-control" name="bukti_respon" id="bukti_respon" accept="image/*">
                <div id="buktiPreview" class="mt-2"></div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="rencana_tindak_lanjut">Rencana Tindak Lanjut</label>
                <textarea class="form-control" name="rencana_tindak_lanjut" id="rencana_tindak_lanjut"></textarea>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="status_booking">Status Booking</label>
                <select class="form-control" name="status_booking" id="status_booking">
                  <option value="">Pilih Status</option>
                  <option value="Sukses">Sukses</option>
                  <option value="Menunggu" selected>Menunggu</option>
                  <option value="Batal">Batal</option>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="catatan">Catatan</label>
                <textarea class="form-control" name="catatan" id="catatan"></textarea>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
// Ensure select2 CSS is loaded
if (!$('link[href*="select2.min.css"]').length) {
    $('head').append('<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />');
}

$(document).ready(function() {
    updateFollowUpCount();
    // Initialize date range picker
    $('#dateRange').daterangepicker({
        autoUpdateInput: false,
        locale: {
            cancelLabel: 'Clear',
            format: 'YYYY-MM-DD'
        }
    });
    $('#dateRange').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
        table.ajax.reload();
        updateFollowUpCount();
    });
    $('#dateRange').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
        table.ajax.reload();
        updateFollowUpCount();
    });
    $('#kategoriFilter, #statusResponFilter, #statusBookingFilter').on('change', function() {
        updateFollowUpCount();
    });

    // Function to update today's follow up count
    function updateFollowUpCount() {
        // This function fetches and updates the stat cards based on the current date range and filters
        var dr = $('#dateRange').val();
        var params = {};
        if(dr) {
            var dates = dr.split(' - ');
            params.start_date = dates[0];
            params.end_date = dates[1];
        }
        $.get("{{ route('marketing.followup.count-today') }}", params, function(res) {
            $('#followUpCount').text(res.count);
            $('#followUpDirespon').text(res.direspon);
            $('#followUpTidakDirespon').text(res.tidak_direspon);
            $('#followUpDiresponPercent').text('(' + res.percent_direspon + '%)');
            $('#followUpTidakDiresponPercent').text('(' + res.percent_tidak_direspon + '%)');
            $('#bookingBatal').text(res.batal ?? 0);
            $('#bookingMenunggu').text(res.menunggu ?? 0);
            $('#bookingSukses').text(res.sukses ?? 0);
            $('#kategoriProduk').text(res.kategori_produk ?? 0);
            $('#kategoriPerawatan').text(res.kategori_perawatan ?? 0);
            $('#kategoriReseller').text(res.kategori_reseller ?? 0);
            $('#kategoriSlimming').text(res.kategori_slimming ?? 0);
            // Render Top Sales
            var $list = $('#topSalesList');
            $list.empty();
            if(res.top_sales && res.top_sales.length > 0) {
                res.top_sales.forEach(function(sale, idx) {
                    $list.append('<li class="d-flex justify-content-between align-items-center mb-1"><span style="font-weight:600; color:#343a40;">' + (idx+1) + '. ' + sale.nama + '</span><span style="font-weight:700; color:#fd7e14; font-size:1.2em;">' + sale.jumlah + '</span></li>');
                });
            } else {
                $list.append('<li class="text-muted">Tidak ada data</li>');
            }
        });
    }
    // Set dateRange to today by default
    var today = moment().format('YYYY-MM-DD');
    $('#dateRange').val(today + ' - ' + today);

    var table = $('#followupTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('marketing.followup.index') }}",
            data: function(d) {
                var dr = $('#dateRange').val();
                if(dr) {
                    var dates = dr.split(' - ');
                    d.start_date = dates[0];
                    d.end_date = dates[1];
                } else {
                    // If no date selected, default to today
                    d.start_date = today;
                    d.end_date = today;
                }
                var kategori = $('#kategoriFilter').val();
                if(kategori && kategori.length > 0) {
                    d.kategori = kategori;
                }
                var statusRespon = $('#statusResponFilter').val();
                if(statusRespon) {
                    d.status_respon = statusRespon;
                }
                var statusBooking = $('#statusBookingFilter').val();
                if(statusBooking) {
                    d.status_booking = statusBooking;
                }
            }
        },
        columns: [
            { data: null, name: 'no', orderable: false, searchable: false },
            { data: 'pasien_nama', name: 'pasien_nama' },
            // Render kategori as colored badges
            { 
                data: 'kategori', 
                name: 'kategori', 
                render: function(data) {
                    var colors = {
                        'Produk': 'primary', // blue
                        'Perawatan': 'pink', // pink
                        'Reseller': 'warning', // yellow
                        'Slimming': 'purple' // purple
                    };
                    var html = '';
                    if(Array.isArray(data)) {
                        data.forEach(function(item) {
                            var color = colors[item] || 'secondary';
                            html += '<span class="badge badge-' + color + ' mx-1">' + item + '</span>';
                        });
                    } else {
                        html = '<span class="badge badge-secondary mx-1">' + data + '</span>';
                    }
                    return html || '-';
                }
            },
            { data: 'sales_nama', name: 'sales_nama' },
            {
                data: 'status_respon',
                name: 'status_respon',
                render: function(data) {
                    var color = data === 'Direspon' ? 'success' : (data === 'Tidak Direspon' ? 'danger' : 'secondary');
                    return '<span class="badge badge-' + color + '">' + data + '</span>';
                }
            },
            { data: 'rencana_tindak_lanjut', name: 'rencana_tindak_lanjut' },
            {
                data: 'status_booking',
                name: 'status_booking',
                render: function(data) {
                    var color = data === 'Sukses' ? 'success' : (data === 'Menunggu' ? 'warning' : (data === 'Batal' ? 'danger' : 'secondary'));
                    return '<span class="badge badge-' + color + '">' + data + '</span>';
                }
            },
            { data: 'catatan', name: 'catatan' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        order: [[2, 'desc']],
        drawCallback: function(settings) {
            var api = this.api();
            api.column(0, {search:'applied', order:'applied'}).nodes().each(function(cell, i) {
                cell.innerHTML = api.page.info().start + i + 1;
            });
            updateFollowUpCount();
        }
    });

    // Kategori options for filter
    var kategoriOptions = [
        {id: 'Produk', text: 'Produk'},
        {id: 'Perawatan', text: 'Perawatan'},
        {id: 'Reseller', text: 'Reseller'},
        {id: 'Slimming', text: 'Slimming'}
    ];
    $('#kategoriFilter').empty();
    kategoriOptions.forEach(function(opt) {
        $('#kategoriFilter').append('<option value="'+opt.id+'">'+opt.text+'</option>');
    });
    $('#kategoriFilter').select2({
        placeholder: 'Pilih Kategori',
        allowClear: true,
        width: 'resolve'
    });
    $('#kategoriFilter').select2({
        placeholder: 'Pilih Kategori',
        allowClear: true,
        width: 'resolve'
    });
    $('#statusResponFilter').select2({
        minimumResultsForSearch: -1,
        width: 'resolve',
        dropdownAutoWidth: true
    });
    $('#statusBookingFilter').select2({
        minimumResultsForSearch: -1,
        width: 'resolve',
        dropdownAutoWidth: true
    });
    // Reset to 'Semua' when cleared
    $('#statusResponFilter').on('select2:clear', function() {
        $(this).val('').trigger('change');
    });
    $('#statusBookingFilter').on('select2:clear', function() {
        $(this).val('').trigger('change');
    });
    $('#kategoriFilter, #statusResponFilter, #statusBookingFilter').on('change', function() {
        table.ajax.reload();
    });

    // Select2 for pasien
    $('#pasienSelect').select2({
        placeholder: 'Cari Pasien',
        ajax: {
            url: "{{ route('marketing.followup.pasien-search') }}",
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return { search: params.term };
            },
            processResults: function(data) {
                return {
                    results: $.map(data, function(item) {
                        return { id: item.id, text: item.nama };
                    })
                };
            },
            cache: true
        },
        minimumInputLength: 2
    });

    // Select2 for kategori multi-select
    $('#kategori').select2({
        placeholder: 'Pilih Kategori',
        allowClear: true,
        width: 'resolve'
    });

    // Select2 for sales
    $('#salesSelect').select2({
        placeholder: 'Pilih Sales',
        ajax: {
            url: '/api/hrd/employees', // You may need to create this API endpoint
            dataType: 'json',
            delay: 250,
            data: function(params) {
                // EmployeeController.searchForSelect2 expects the query param 'q'
                return { q: params.term };
            },
            processResults: function(data) {
                return {
                    results: $.map(data, function(item) {
                        return { id: item.id, text: item.nama };
                    })
                };
            },
            cache: true
        },
        minimumInputLength: 2
    });

    $('#addFollowUpBtn').click(function() {
        $('#followupForm')[0].reset();
        $('#followup_id').val('');
        $('#pasienSelect').val(null).trigger('change');
        $('#salesSelect').val(null).trigger('change');
        $('#buktiPreview').html('');
        $('#followupModalLabel').text('Tambah Follow Up');
        $('#followupModal').modal('show');
    });

    // Edit
    $('#followupTable').on('click', '.editBtn', function() {
        var id = $(this).data('id');
        $.get('/marketing/followup/' + id, function(data) {
            $('#followupForm')[0].reset();
            $('#followup_id').val(data.id);
            if(data.pasien_id && data.pasien) {
                var option = new Option(data.pasien.nama, data.pasien_id, true, true);
                $('#pasienSelect').append(option).trigger('change');
            } else {
                $('#pasienSelect').val(null).trigger('change');
            }
            if(data.sales_id && data.sales) {
                var option = new Option(data.sales.nama, data.sales_id, true, true);
                $('#salesSelect').append(option).trigger('change');
            } else {
                $('#salesSelect').val(null).trigger('change');
            }
            $('#kategori').val(data.kategori);
            if(Array.isArray(data.kategori)) {
                $('#kategori').val(data.kategori).trigger('change');
            } else if(data.kategori) {
                try {
                    var arr = JSON.parse(data.kategori);
                    $('#kategori').val(arr).trigger('change');
                } catch(e) {
                    $('#kategori').val([data.kategori]).trigger('change');
                }
            } else {
                $('#kategori').val(null).trigger('change');
            }
            $('#status_respon').val(data.status_respon);
            $('#rencana_tindak_lanjut').val(data.rencana_tindak_lanjut);
            $('#status_booking').val(data.status_booking);
            $('#catatan').val(data.catatan);
            if(data.bukti_respon) {
                $('#buktiPreview').html('<img src="'+data.bukti_respon+'" style="max-width:120px;max-height:120px;">');
            } else {
                $('#buktiPreview').html('');
            }
            $('#followupModalLabel').text('Edit Follow Up');
            $('#followupModal').modal('show');
        });
    });

    // Delete
    $('#followupTable').on('click', '.deleteBtn', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Yakin hapus?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus!'
        }).then((result) => {
            if(result.value) {
                $.ajax({
                    url: '/marketing/followup/' + id,
                    type: 'DELETE',
                    success: function(res) {
                        table.ajax.reload();
                        Swal.fire('Sukses', res.message, 'success');
                    },
                    error: function(xhr) {
                        Swal.fire('Error', 'Gagal menghapus', 'error');
                    }
                });
            }
        });
    });

    // Form submit handler for AJAX
    $('#followupForm').submit(function(e) {
        e.preventDefault();
        var id = $('#followup_id').val();
        var url = id ? '/marketing/followup/' + id : '/marketing/followup';
        var formData = new FormData(this);
        if (id) formData.append('_method', 'PUT');
        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                $('#followupModal').modal('hide');
                table.ajax.reload();
                Swal.fire('Sukses', res.message, 'success');
            },
            error: function(xhr) {
                let msg = 'Terjadi kesalahan';
                if(xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).join('<br>');
                }
                Swal.fire('Error', msg, 'error');
            }
        });
    });
});
</script>
@endpush
