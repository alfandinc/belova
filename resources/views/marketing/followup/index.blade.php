@extends('layouts.marketing.app')

@section('title', 'Follow Up Customer')

@section('navbar')
    @include('layouts.marketing.navbar')
@endsection

@section('content')
<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Follow Up Customer</h4>
            <button class="btn btn-success" id="addFollowUpBtn">Tambah Follow Up</button>
        </div>
        <div class="card-body">
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
$(function() {
    var table = $('#followupTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('marketing.followup.index') }}",
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
        }
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
