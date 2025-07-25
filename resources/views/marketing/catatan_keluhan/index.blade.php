@extends('layouts.marketing.app')

@section('title', 'Catatan Keluhan Customer')

@section('navbar')
    @include('layouts.marketing.navbar')
@endsection

@section('content')
<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Catatan Keluhan Customer</h4>
            <button class="btn btn-success" id="addKeluhanBtn">Tambah Catatan</button>
        </div>
        <div class="card-body">
    <div class="row mb-3">
        <div class="col-md-3 mb-2">
            <label for="dateRange">Filter Tanggal Publish</label>
            <input type="text" id="dateRange" class="form-control" placeholder="Filter Tanggal Kunjungan" autocomplete="off" readonly>
        </div>
        <div class="col-md-3 mb-2">
            <label for="filterPerusahaan">Filter Brand</label>
            <select id="filterPerusahaan" class="form-control filter-select" style="width:100%">
                <option value="">Semua Perusahaan</option>
                <option value="Klinik Utama Premire Belova">Klinik Utama Premire Belova</option>
                <option value="Klinik Pratama Belova">Klinik Pratama Belova</option>
                <option value="Belova Center Living">Belova Center Living</option>
            </select>
        </div>
        <div class="col-md-2 mb-2">
            <label for="filterUnit">Filter Unit</label>
            <select id="filterUnit" class="form-control filter-select" style="width:100%">
                <option value="">Semua Unit</option>
                <option value="Human Resource">Human Resource</option>
                    <option value="Administrasi dan Urusan Bisnis">Administrasi dan Urusan Bisnis</option>
                    <option value="Pemasaran dan Hubungan Digital">Pemasaran dan Hubungan Digital</option>
                    <option value="Operasianal dan Pengelolaan Fasilitas">Operasianal dan Pengelolaan Fasilitas</option>
                    <option value="Kefarmasian dan Asuransi">Kefarmasian dan Asuransi</option>
            </select>
        </div>
        <div class="col-md-2 mb-2">
            <label for="filterKategori">Filter Kategori</label>
            <select id="filterKategori" class="form-control filter-select" style="width:100%">
                <option value="">Semua Kategori</option>
                <option value="Pelayanan">Pelayanan</option>
                    <option value="Treatment">Treatment</option>
                    <option value="Produk">Produk</option>
                    <option value="Obat dan Resep">Obat dan Resep</option>
                    <option value="Pembayaran">Pembayaran</option>
                    <option value="Fasilitas">Fasilitas</option>
            </select>
        </div>
        <div class="col-md-2 mb-2">
            <label for="filterStatus">Filter Status</label>
            <select id="filterStatus" class="form-control filter-select" style="width:100%">
                <option value="">Semua Status</option>
                <option value="Diproses">Diproses</option>
                <option value="Selesai">Selesai</option>
                <option value="Ditolak">Ditolak</option>
            </select>
        </div>
    </div>
            <table class="table table-bordered" id="catatanTable">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Perusahaan</th>
                        <th>Pasien</th>
                        <th>Tanggal Kunjungan</th>
                        <th>Unit</th>
                        <th>Kategori</th>
                        <th>Keluhan</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="keluhanModal" tabindex="-1" role="dialog" aria-labelledby="keluhanModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <form id="keluhanForm">
        <div class="modal-header">
          <h5 class="modal-title" id="keluhanModalLabel">Tambah Catatan Keluhan</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body" style="max-height:70vh; overflow-y:auto;">
          <input type="hidden" id="catatan_id" name="catatan_id">
          <div class="container-fluid">
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Perusahaan</label>
                  <select name="perusahaan" id="perusahaanSelect" class="form-control" required style="width:100%">
                    <option value="">Pilih Perusahaan</option>
                    <option value="Klinik Utama Premire Belova">Klinik Utama Premire Belova</option>
                    <option value="Klinik Pratama Belova">Klinik Pratama Belova</option>
                    <option value="Belova Center Living">Belova Center Living</option>
                  </select>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>Pasien</label>
                  <select name="pasien_id" id="pasienSelect" class="form-control" required style="width:100%">
                    <option value="">Pilih Pasien</option>
                  </select>
                </div>
              </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>No RM</label>
                        <input type="text" id="noRmField" class="form-control" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>No HP</label>
                        <input type="text" id="noHpField" class="form-control" readonly>
                    </div>
                </div>

            </div>
            <div class="row">
              
              <div class="col-md-6">
                <div class="form-group">
                  <label>Tanggal Kunjungan</label>
                  <input type="date" name="visit_date" class="form-control" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>Unit</label>
                  <select name="unit" id="unitSelect" class="form-control" required style="width:100%">
                    <option value="">Pilih Unit</option>
                    <option value="Human Resource">Human Resource</option>
                    <option value="Administrasi dan Urusan Bisnis">Administrasi dan Urusan Bisnis</option>
                    <option value="Pemasaran dan Hubungan Digital">Pemasaran dan Hubungan Digital</option>
                    <option value="Operasianal dan Pengelolaan Fasilitas">Operasianal dan Pengelolaan Fasilitas</option>
                    <option value="Kefarmasian dan Asuransi">Kefarmasian dan Asuransi</option>
                  </select>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Kategori</label>
                  <select name="kategori" id="kategoriSelect" class="form-control" required style="width:100%">
                    <option value="">Pilih Kategori</option>
                    <option value="Pelayanan">Pelayanan</option>
                    <option value="Treatment">Treatment</option>
                    <option value="Produk">Produk</option>
                    <option value="Obat dan Resep">Obat dan Resep</option>
                    <option value="Pembayaran">Pembayaran</option>
                    <option value="Fasilitas">Fasilitas</option>
                  </select>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>Status</label>
                  <select name="status" id="statusSelect" class="form-control" required style="width:100%">
                    <option value="Diproses" selected>Diproses</option>
                    <option value="Selesai">Selesai</option>
                    <option value="Ditolak">Ditolak</option>
                  </select>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Keluhan</label>
                  <textarea name="keluhan" class="form-control" rows="5" required></textarea>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>Bukti (Upload File/Foto)</label>
                  <input type="file" name="bukti" id="buktiInput" class="form-control" accept="image/*,application/pdf">
                  <div id="buktiPreview" class="mt-2"></div>
                </div>
              </div>
    
            </div>
            
            <div class="row">
              <div class="col-md-12">
                <div class="form-group">
                  <label>Penyelesaian</label>
                  <textarea name="penyelesaian" class="form-control"></textarea>
                </div>
              </div>
    
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Deadline Perbaikan</label>
                  <input type="date" name="deadline_perbaikan" class="form-control">
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>Rencana Perbaikan</label>
                  <textarea name="rencana_perbaikan" class="form-control"></textarea>
                </div>
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

    // Date Range Picker
    $('#dateRange').daterangepicker({
        autoUpdateInput: false,
        locale: {
            cancelLabel: 'Clear',
            format: 'YYYY-MM-DD',
            applyLabel: 'Terapkan',
            cancelLabel: 'Bersihkan',
            fromLabel: 'Dari',
            toLabel: 'Sampai',
            customRangeLabel: 'Custom',
            daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
            monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
            firstDay: 1
        }
    });

    $('#dateRange').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' s/d ' + picker.endDate.format('YYYY-MM-DD'));
        table.ajax.reload();
    });
    $('#dateRange').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
        table.ajax.reload();
    });

    var table = $('#catatanTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('marketing.catatan-keluhan.index') }}',
            data: function(d) {
                var dr = $('#dateRange').val();
                if(dr) {
                    var parts = dr.split(' s/d ');
                    d.start_date = parts[0];
                    d.end_date = parts[1];
                }
                var perusahaan = $('#filterPerusahaan').val();
                if(perusahaan) {
                    d.perusahaan = perusahaan;
                }
                var unit = $('#filterUnit').val();
                if(unit) {
                    d.unit = unit;
                }
                var kategori = $('#filterKategori').val();
                if(kategori) {
                    d.kategori = kategori;
                }
                var status = $('#filterStatus').val();
                if(status) {
                    d.status = status;
                }
            }
        },
        columns: [
            { data: null, name: 'no', orderable: false, searchable: false },
            { data: 'perusahaan', name: 'perusahaan' },
            { data: 'pasien_nama', name: 'pasien_nama' },
            { data: 'visit_date', name: 'visit_date' },
            { data: 'unit', name: 'unit' },
            { data: 'kategori', name: 'kategori' },
            { data: 'keluhan', name: 'keluhan' },
            { data: 'status', name: 'status' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        order: [[3, 'desc']],
        drawCallback: function(settings) {
            var api = this.api();
            api.column(0, {search:'applied', order:'applied'}).nodes().each(function(cell, i) {
                cell.innerHTML = api.page.info().start + i + 1;
            });
        }
    });

    // Init select2 for all filter selects
    $('.filter-select').select2({
        placeholder: function(){
            return $(this).find('option:first').text();
        },
        width: 'resolve'
    });

    // Filter events
    $('#filterPerusahaan, #filterUnit, #filterKategori, #filterStatus').on('change', function() {
        table.ajax.reload();
    });

    // Select2 for perusahaan
    $('#perusahaanSelect').select2({
        placeholder: 'Pilih Perusahaan',
    });

    // Select2 for kategori
    $('#kategoriSelect').select2({
        placeholder: 'Pilih Kategori',
    });

    // Select2 for status
    $('#statusSelect').select2({
        placeholder: 'Pilih Status',
    });

    // Select2 for unit
    $('#unitSelect').select2({
        placeholder: 'Pilih Unit',
    });

    // Select2 for pasien
    $('#pasienSelect').select2({
        placeholder: 'Cari Pasien',
        ajax: {
            url: '/marketing/catatan-keluhan-pasien-search',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    search: params.term,
                    page: params.page || 1
                };
            },
            processResults: function(data, params) {
                params.page = params.page || 1;
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

    $('#addKeluhanBtn').click(function() {
        $('#keluhanForm')[0].reset();
        $('#catatan_id').val('');
        $('#keluhanModalLabel').text('Tambah Catatan Keluhan');
        $('#pasienSelect').val(null).trigger('change');
        $('#perusahaanSelect').val(null).trigger('change');
        $('#unitSelect').val(null).trigger('change');
        $('#kategoriSelect').val(null).trigger('change');
        $('#statusSelect').val('Diproses').trigger('change');
        $('#noRmField').val('');
        $('#noHpField').val('');
        $('#noRmField').closest('.form-group').hide();
        $('#noHpField').closest('.form-group').hide();
        $('#keluhanModal').modal('show');
    });

    // Create or Update
    $('#keluhanForm').submit(function(e) {
        e.preventDefault();
        var id = $('#catatan_id').val();
        var url = id ? '/marketing/catatan-keluhan/' + id : '/marketing/catatan-keluhan';
        var method = id ? 'PUT' : 'POST';
        var formData = new FormData(this);
        if (id) formData.append('_method', 'PUT');
        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                $('#keluhanModal').modal('hide');
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

    // Edit
    $('#catatanTable').on('click', '.editBtn', function() {
        var id = $(this).data('id');
        $.get('/marketing/catatan-keluhan/' + id, function(data) {
            $('#keluhanForm')[0].reset();
            $('#catatan_id').val(data.id);
            // Set perusahaan select2 value
            if(data.perusahaan) {
                if($('#perusahaanSelect option[value="'+data.perusahaan.replace(/"/g,'\\"')+'"], #perusahaanSelect option').filter(function(){return $(this).val() === data.perusahaan;}).length === 0) {
                    var perusahaanOption = new Option(data.perusahaan, data.perusahaan, true, true);
                    $('#perusahaanSelect').append(perusahaanOption);
                }
                $('#perusahaanSelect').val(data.perusahaan).trigger('change');
            } else {
                $('#perusahaanSelect').val(null).trigger('change');
            }
            // Set unit select2 value
            if(data.unit) {
                if($('#unitSelect option[value="'+data.unit.replace(/"/g,'\\"')+'"], #unitSelect option').filter(function(){return $(this).val() === data.unit;}).length === 0) {
                    var unitOption = new Option(data.unit, data.unit, true, true);
                    $('#unitSelect').append(unitOption);
                }
                $('#unitSelect').val(data.unit).trigger('change');
            } else {
                $('#unitSelect').val(null).trigger('change');
            }
            // Set kategori select2 value
            if(data.kategori) {
                if($('#kategoriSelect option[value="'+data.kategori.replace(/"/g,'\\"')+'"], #kategoriSelect option').filter(function(){return $(this).val() === data.kategori;}).length === 0) {
                    var kategoriOption = new Option(data.kategori, data.kategori, true, true);
                    $('#kategoriSelect').append(kategoriOption);
                }
                $('#kategoriSelect').val(data.kategori).trigger('change');
            } else {
                $('#kategoriSelect').val(null).trigger('change');
            }
            // Set status select2 value
            if(data.status) {
                if($('#statusSelect option[value="'+data.status.replace(/"/g,'\\"')+'"], #statusSelect option').filter(function(){return $(this).val() === data.status;}).length === 0) {
                    var statusOption = new Option(data.status, data.status, true, true);
                    $('#statusSelect').append(statusOption);
                }
                $('#statusSelect').val(data.status).trigger('change');
            } else {
                $('#statusSelect').val('Diproses').trigger('change');
            }
            // Set pasien select2 value dynamically
            if(data.pasien_id && data.pasien) {
                if($('#pasienSelect option[value="'+data.pasien_id+'"], #pasienSelect option').filter(function(){return $(this).val() == data.pasien_id;}).length === 0) {
                    var option = new Option(data.pasien.nama, data.pasien_id, true, true);
                    $('#pasienSelect').append(option);
                }
                $('#pasienSelect').val(data.pasien_id).trigger('change');
                // Set No RM and No HP fields
                $('#noRmField').val(data.pasien.id || '');
                $('#noHpField').val(data.pasien.no_hp || '');
                $('#noRmField').closest('.form-group').show();
                $('#noHpField').closest('.form-group').show();
            } else {
                $('#pasienSelect').val(null).trigger('change');
                $('#noRmField').val('');
                $('#noHpField').val('');
                $('#noRmField').closest('.form-group').hide();
                $('#noHpField').closest('.form-group').hide();
            }
            // Show bukti preview
            if(data.bukti) {
                var ext = data.bukti.split('.').pop().toLowerCase();
                var html = '';
                if(['jpg','jpeg','png','gif','bmp','webp'].includes(ext)) {
                    html = '<a href="'+data.bukti+'" target="_blank"><img src="'+data.bukti+'" alt="Bukti" style="max-width:120px;max-height:120px;"></a>';
                } else if(['pdf'].includes(ext)) {
                    html = '<a href="'+data.bukti+'" target="_blank">Lihat Bukti (PDF)</a>';
                } else {
                    html = '<a href="'+data.bukti+'" target="_blank">Lihat Bukti</a>';
                }
                $('#buktiPreview').html(html);
            } else {
                $('#buktiPreview').html('');
            }
            $('[name=visit_date]').val(data.visit_date);
            $('[name=keluhan]').val(data.keluhan);
            $('[name=penyelesaian]').val(data.penyelesaian);
            $('[name=rencana_perbaikan]').val(data.rencana_perbaikan);
            $('[name=deadline_perbaikan]').val(data.deadline_perbaikan);
            $('#keluhanModalLabel').text('Edit Catatan Keluhan');
            $('#keluhanModal').modal('show');
        });
    });

    // Delete
    $('#catatanTable').on('click', '.deleteBtn', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Yakin hapus?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus!'
        }).then((result) => {
            if(result.isConfirmed) {
                $.ajax({
                    url: '/marketing/catatan-keluhan/' + id,
                    type: 'DELETE',
                    data: {_token: $('meta[name="csrf-token"]').attr('content')},
                    success: function(res) {
                        table.ajax.reload();
                        Swal.fire('Sukses', res.message, 'success');
                    },
                    error: function() {
                        Swal.fire('Error', 'Gagal menghapus data', 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush
