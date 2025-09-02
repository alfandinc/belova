@extends('layouts.erm.app')

@section('title', 'Lakukan Stok Opname')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection

@section('content')
<!-- Modal Ubah Status -->
                    <div class="modal fade" id="changeStatusModal" tabindex="-1" role="dialog" aria-labelledby="changeStatusModalLabel" aria-hidden="true">
                      <div class="modal-dialog" role="document">
                        <div class="modal-content">
                          <form id="changeStatusForm" method="POST" action="{{ route('erm.stokopname.updateStatus', $stokOpname->id) }}">
                            @csrf
                            <div class="modal-header">
                              <h5 class="modal-title" id="changeStatusModalLabel">Ubah Status Stok Opname</h5>
                              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                              </button>
                            </div>
                            <div class="modal-body">
                              <div class="form-group">
                                <label for="status">Status Baru</label>
                                <select class="form-control" name="status" id="status" required>
                                  <option value="draft" {{ $stokOpname->status == 'draft' ? 'selected' : '' }}>Draft</option>
                                  <option value="proses" {{ $stokOpname->status == 'proses' ? 'selected' : '' }}>Proses</option>
                                  <option value="selesai" {{ $stokOpname->status == 'selesai' ? 'selected' : '' }}>Selesai</option>
                                </select>
                              </div>
                            </div>
                            <div class="modal-footer">
                              <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                              <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Lakukan Stok Opname</h4>
        <a href="{{ route('erm.stokopname.index') }}" class="btn btn-secondary mt-2"><i class="fa fa-arrow-left"></i> Kembali</a>
    </div>
    <div class="row">
        <div class="col-md-7">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>INFORMASI STOK OPNAME</strong>
                    <div class="d-inline-flex align-items-center" style="background: #17a2b8; padding: 0.12rem 0.7rem 0.12rem 0.7rem; border-radius: 4px;">
                        <span id="status-text" style="color: #fff; font-weight: 500; font-size: 0.92rem; letter-spacing: 0.2px; margin-right: 0.35rem;">{{ strtoupper($stokOpname->status) }}</span>
                        <button type="button" class="btn btn-link p-0 m-0" data-toggle="modal" data-target="#changeStatusModal" title="Ubah Status" style="color: #fff; font-size: 1rem; border-radius: 3px;">
                            <i class="fa fa-edit"></i>
                        </button>
                        
                    </div>
                    
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-5 font-weight-bold">Tanggal Opname</div>
                        <div class="col-7">
                            @php
                                $bulanIndo = [
                                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
                                    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                                ];
                                $tgl = \Carbon\Carbon::parse($stokOpname->tanggal_opname);
                                $tglText = $tgl->day . ' ' . ($bulanIndo[$tgl->month] ?? $tgl->month) . ' ' . $tgl->year;
                            @endphp
                            {{ $tglText }}
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 font-weight-bold">Gudang</div>
                        <div class="col-7">{{ $stokOpname->gudang->nama ?? '-' }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 font-weight-bold">Periode</div>
                        <div class="col-7">
                            @php
                                $bulanIndo = [
                                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
                                    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                                ];
                                $periodeText = strtoupper(($bulanIndo[$stokOpname->periode_bulan] ?? $stokOpname->periode_bulan) . ' ' . $stokOpname->periode_tahun);
                            @endphp
                            <strong>{{ $periodeText }}</strong>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 font-weight-bold">Catatan</div>
                        <div class="col-7">{{ $stokOpname->notes }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="card mb-3">
                <div class="card-header"><strong>AKSI</strong></div>
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <a href="{{ route('erm.stokopname.downloadExcel', $stokOpname->id) }}" class="btn btn-warning mr-2"><i class="fa fa-download"></i> Download</a>
                        <form action="{{ route('erm.stokopname.uploadExcel', $stokOpname->id) }}" method="POST" enctype="multipart/form-data" role="form" class="d-flex align-items-center">
                            @csrf
                            <div class="input-group">
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="file" name="file" accept=".xlsx,.xls" required>
                                    <label class="custom-file-label" for="file">Pilih file...</label>
                                </div>
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-primary"><i class="fa fa-upload"></i> Upload Data</button>
                                    <button type="button" class="btn btn-success ml-2" id="saveStokFisikBtn"><i class="fa fa-save"></i> Submit Stok</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    @if(session('success'))
                        <div class="alert alert-success mt-3">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger mt-3">{{ session('error') }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
    <div class="mt-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0">Hasil Stok Opname</h5>
            <button id="syncTotalsBtn" class="btn btn-outline-primary btn-sm"><i class="fa fa-sync"></i> Sync Total Nilai Stok</button>
        </div>
        <table class="table table-bordered table-striped" id="stokOpnameItemsTable">
            <thead>
                <tr>
                    <th>Obat ID</th>
                    <th>Nama Obat</th>
                    <th>Stok Sistem</th>
                    <th>Stok Fisik</th>
                    <th>Selisih</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
        <div class="row mt-3">
            <div class="col-md-6">
                <div class="alert alert-info" id="totalStokSistemBox">
                    <strong>Total Nilai Stok Sistem (HPP Jual x Stok Sistem):</strong><br>
                    Rp <span id="totalStokSistemText">{{ number_format($totalStokSistem, 0, ',', '.') }}</span>
                </div>
            </div>
            <div class="col-md-6">
                <div class="alert alert-success" id="totalStokFisikBox">
                    <strong>Total Nilai Stok Fisik (HPP Jual x Stok Fisik):</strong><br>
                    Rp <span id="totalStokFisikText">{{ number_format($totalStokFisik, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>
@push('scripts')
<script>
$(function () {
    $('#syncTotalsBtn').click(function() {
        var btn = $(this);
        btn.prop('disabled', true);
        btn.html('<i class="fa fa-sync fa-spin"></i> Syncing...');
        $.get("{{ route('erm.stokopname.syncTotals', $stokOpname->id) }}", function(res) {
            $('#totalStokSistemText').text(res.totalStokSistem.toLocaleString('id-ID'));
            $('#totalStokFisikText').text(res.totalStokFisik.toLocaleString('id-ID'));
        }).always(function() {
            btn.prop('disabled', false);
            btn.html('<i class="fa fa-sync"></i> Sync Total Nilai Stok');
        });
    });
    var table = $('#stokOpnameItemsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('erm.stokopname.itemsData', $stokOpname->id) }}",
        columns: [
            {data: 'obat_id', name: 'obat_id'},
            {data: 'nama_obat', name: 'nama_obat'},
            {data: 'stok_sistem', name: 'stok_sistem'},
                {
                    data: 'stok_fisik',
                    name: 'stok_fisik',
                    render: function(data, type, row) {
                        return `<input type="number" class="form-control form-control-sm stok-fisik-input" data-id="${row.id}" value="${data}" style="width:90px;">`;
                    }
                },
            {
                data: 'selisih',
                name: 'selisih',
                render: function(data, type, row) {
                    if (data != 0) {
                        return data + ' <i class="fa fa-exclamation-triangle text-warning blink-warning" title="Ada selisih"></i>';
                    } else {
                        return data + ' <i class="fa fa-check text-success" title="Sesuai"></i>';
                    }
                }
            },
            {
                data: 'notes',
                name: 'notes',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    return `<input type="text" class="form-control form-control-sm notes-input" data-id="${row.id}" value="${data ?? ''}" placeholder="Catatan...">`;
                }
            },
        ]
    });

        // Inline update stok fisik
        $('#stokOpnameItemsTable').on('change', '.stok-fisik-input', function() {
            var itemId = $(this).data('id');
            var stokFisik = $(this).val();
            var input = $(this);
            input.prop('disabled', true);
            $.ajax({
                url: '/erm/stokopname-item/' + itemId + '/update-stok-fisik',
                method: 'POST',
                data: {
                    stok_fisik: stokFisik,
                    _token: '{{ csrf_token() }}'
                },
                success: function(res) {
                    input.removeClass('is-invalid').addClass('is-valid');
                    setTimeout(() => input.removeClass('is-valid'), 1000);
                    // Update selisih cell in the same row
                    var rowIdx = table.row(input.closest('tr')).index();
                    var selisihCell = $(table.cell(rowIdx, 4).node());
                    var icon = res.selisih != 0 ? '<i class="fa fa-exclamation-triangle text-warning blink-warning" title="Ada selisih"></i>' : '<i class="fa fa-check text-success" title="Sesuai"></i>';
                    selisihCell.html(res.selisih + ' ' + icon);
                },
                error: function() {
                    input.addClass('is-invalid');
                },
                complete: function() {
                    input.prop('disabled', false);
                }
            });
        });
    // Inline update notes
    $('#stokOpnameItemsTable').on('change', '.notes-input', function() {
        var itemId = $(this).data('id');
        var notes = $(this).val();
        var input = $(this);
        Swal.fire({
            title: 'Menyimpan catatan...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });
        $.ajax({
            url: '/erm/stokopname/item/' + itemId + '/update-notes',
            method: 'POST',
            data: {
                notes: notes,
                _token: '{{ csrf_token() }}'
            },
            success: function(res) {
                input.removeClass('is-invalid').addClass('is-valid');
                setTimeout(() => input.removeClass('is-valid'), 1000);
                Swal.fire({
                    icon: 'success',
                    title: 'Catatan disimpan',
                    timer: 1000,
                    showConfirmButton: false
                });
            },
            error: function() {
                input.addClass('is-invalid');
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal menyimpan catatan',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        });
    });

    // Show selected file name in upload
    $('#file').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName);
    });

    // Save stok fisik to stok obat
    $('#saveStokFisikBtn').click(function() {
        Swal.fire({
            title: 'Yakin ingin mengganti stok obat sesuai stok fisik hasil opname?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Simpan!',
            cancelButtonText: 'Batal',
        }).then((result) => {
            if (result.value) {
                var btn = $('#saveStokFisikBtn');
                btn.prop('disabled', true);
                Swal.fire({
                    title: 'Menyimpan stok fisik ke stok obat...',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });
                $.post("{{ route('erm.stokopname.saveStokFisik', $stokOpname->id) }}", {
                    _token: '{{ csrf_token() }}'
                })
                .done(function(res) {
                    Swal.fire({
                        icon: 'success',
                        title: res.message || 'Stok obat berhasil diperbarui!',
                        timer: 1800,
                        showConfirmButton: false
                    });
                    table.ajax.reload();
                })
                .fail(function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal menyimpan stok fisik ke stok obat!',
                        timer: 1800,
                        showConfirmButton: false
                    });
                })
                .always(function() {
                    btn.prop('disabled', false);
                });
            }
        });
    });

    // AJAX for change status
    $('#changeStatusForm').submit(function(e) {
        e.preventDefault();
        var form = $(this);
        var url = form.attr('action');
        var data = form.serialize();
        var btn = form.find('button[type="submit"]');
        btn.prop('disabled', true);
        Swal.fire({
            title: 'Menyimpan perubahan status...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });
        $.post(url, data)
            .done(function(res) {
                $('#changeStatusModal').modal('hide');
                if(res.status) {
                    $('#status-text').text(res.status.toUpperCase());
                }
                Swal.fire({
                    icon: 'success',
                    title: 'Status berhasil diubah!',
                    timer: 1500,
                    showConfirmButton: false
                });
            })
            .fail(function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal mengubah status!',
                    timer: 1500,
                    showConfirmButton: false
                });
            })
            .always(function() {
                btn.prop('disabled', false);
            });
    });
});
</script>
@endpush
<style>
@keyframes blink {
  0%, 100% { opacity: 1; }
  50% { opacity: 0; }
}
.blink-warning {
  animation: blink 1s linear infinite;
}
</style>
</div>
@endsection
