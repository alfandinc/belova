@extends('layouts.erm.app')
@section('title', 'Finance | Billing')
@section('navbar')
    @include('layouts.erm.navbar')
@endsection
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right text-right" style="float: right;">
                    <button type="button" class="btn btn-warning mr-2" data-toggle="modal" data-target="#modalMigrateStok">
                        <i class="fas fa-database"></i> Migrasi Stok Obat
                    </button>
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalMutasi">
                        <i class="fas fa-plus"></i> Buat Permintaan
                    </button>
                </div>
                <h4 class="page-title">Mutasi Gudang</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <select class="form-control" id="filter_gudang">
                                <option value="">Semua Gudang</option>
                                @foreach($gudangs as $gudang)
                                    <option value="{{ $gudang->id }}">{{ $gudang->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-control" id="filter_status">
                                <option value="">Semua Status</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Disetujui</option>
                                <option value="rejected">Ditolak</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;" id="mutasi-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>No Mutasi</th>
                                    <th>Obat</th>
                                    <th>Jumlah</th>
                                    <th>Dari Gudang</th>
                                    <th>Ke Gudang</th>
                                    <th>Diminta Oleh</th>
                                    <th>Disetujui Oleh</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal Mutasi -->
<div class="modal fade" id="modalMutasi" tabindex="-1" role="dialog" aria-labelledby="modalMutasiLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalMutasiLabel">Buat Mutasi Gudang</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="form-mutasi" action="{{ route('erm.mutasi-gudang.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="gudang_asal_id">Gudang Asal <span class="text-danger">*</span></label>
                                <select name="gudang_asal_id" id="gudang_asal_id" class="form-control" required>
                                    <option value="">Pilih Gudang Asal</option>
                                    @foreach($gudangs as $gudang)
                                        <option value="{{ $gudang->id }}">{{ $gudang->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="gudang_tujuan_id">Gudang Tujuan <span class="text-danger">*</span></label>
                                <select name="gudang_tujuan_id" id="gudang_tujuan_id" class="form-control" required>
                                    <option value="">Pilih Gudang Tujuan</option>
                                    @foreach($gudangs as $gudang)
                                        <option value="{{ $gudang->id }}">{{ $gudang->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Pilih Obat <span class="text-danger">*</span></label>
                                <select name="obat_id" id="obat_id" class="form-control select2-modal" required>
                                    <option value="">Pilih Obat</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="jumlah">Jumlah <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="jumlah" id="jumlah" required min="1">
                                <small class="text-muted">Stok tersedia: <span id="stok-tersedia">0</span></small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="keterangan">Keterangan</label>
                                <textarea name="keterangan" id="keterangan" rows="3" class="form-control"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detail -->
<div class="modal fade" id="modalDetail" tabindex="-1" role="dialog" aria-labelledby="modalDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDetailLabel">Detail Mutasi Gudang</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="modalDetailContent">
                <!-- Content will be loaded dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                <div id="approval-buttons" style="display: none;">
                    <button type="button" class="btn btn-success btn-approve">
                        <i class="fas fa-check"></i> Setujui
                    </button>
                    <button type="button" class="btn btn-danger btn-reject">
                        <i class="fas fa-times"></i> Tolak
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Migrate Stok -->
<div class="modal fade" id="modalMigrateStok" tabindex="-1" role="dialog" aria-labelledby="modalMigrateStokLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalMigrateStokLabel">Migrasi Stok dari Field Obat ke Gudang</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    Fitur ini akan menambahkan stok dari field <strong>stok</strong> di tabel obat ke gudang yang dipilih dengan:
                    <ul class="mt-2 mb-0">
                        <li>Batch: MIGRATE-YYYYMMDD-ObatID</li>
                        <li>Expiration Date: 3 bulan dari sekarang</li>
                        <li><strong>Field stok obat TIDAK akan direset</strong> (untuk keamanan)</li>
                        <li>Anda bisa cleanup manual nanti setelah yakin migrasi berhasil</li>
                    </ul>
                </div>
                
                <div id="migration-preview" class="mb-3" style="display: none;">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6>Preview Migrasi:</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Total Obat:</strong> <span id="preview-total-obat">0</span>
                                </div>
                                <div class="col-md-6">
                                    <strong>Total Stok:</strong> <span id="preview-total-stok">0</span>
                                </div>
                            </div>
                            <div class="mt-2">
                                <strong>Sample Obat (10 pertama):</strong>
                                <div id="preview-obat-list"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <form id="form-migrate-stok">
                    <div class="form-group">
                        <label for="migrate_gudang_id">Pilih Gudang Tujuan <span class="text-danger">*</span></label>
                        <select name="gudang_id" id="migrate_gudang_id" class="form-control" required>
                            <option value="">Pilih Gudang</option>
                            @foreach($gudangs as $gudang)
                                <option value="{{ $gudang->id }}">{{ $gudang->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="text-center">
                        <button type="button" class="btn btn-info" id="btn-preview">
                            <i class="fas fa-eye"></i> Preview Data
                        </button>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="btn-cleanup" style="display: none;">
                    <i class="fas fa-trash"></i> Cleanup Field Stok
                </button>
                <button type="button" class="btn btn-warning" id="btn-migrate" disabled>
                    <i class="fas fa-database"></i> Migrate Stok
                </button>
            </div>
        </div>
    </div>
</div>
@endsection



@push('scripts')
<script>
$(document).ready(function() {
    var table = $('#mutasi-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('erm.mutasi-gudang.data') }}",
            data: function(d) {
                d.gudang_id = $('#filter_gudang').val();
                d.status = $('#filter_status').val();
            }
        },
        pageLength: 25,
        responsive: true,
        drawCallback: function(settings) {
            $('[data-toggle="tooltip"]').tooltip();
            feather.replace();
        },
        columns: [
            { data: 'tanggal', name: 'created_at' },
            { data: 'nomor_mutasi', name: 'nomor_mutasi' },
            { data: 'nama_obat', name: 'obat.nama' },
            { data: 'jumlah', name: 'jumlah' },
            { data: 'gudang_asal', name: 'gudangAsal.nama' },
            { data: 'gudang_tujuan', name: 'gudangTujuan.nama' },
            { data: 'requested_by', name: 'requestedBy.name' },
            { data: 'approved_by', name: 'approvedBy.name' },
            { data: 'status_label', name: 'status' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']]
    });

    $('#filter_gudang, #filter_status').change(function() {
        table.draw();
    });

    // Inisialisasi select2 hanya di document ready
    $('#gudang_asal_id, #gudang_tujuan_id').select2({
        dropdownParent: $('#modalMutasi'),
        width: '100%'
    });
    $('.select2-modal').select2({
        dropdownParent: $('#modalMutasi'),
        width: '100%',
        ajax: {
            url: "{{ route('erm.mutasi-gudang.obat') }}",
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term,
                    gudang_id: $('#gudang_asal_id').val()
                };
            },
            processResults: function(data, params) {
                return {
                    results: data.results
                };
            },
            cache: true
        },
        placeholder: 'Cari obat...',
        minimumInputLength: 1,
        templateResult: formatObat,
        templateSelection: formatObatSelection
    });

    function formatObat(obat) {
        if (obat.loading) return obat.text;
        var markup = "<div class='select2-result-repository clearfix'>" +
            "<div class='select2-result-repository__title'>" + obat.nama + "</div>" +
            "<div class='select2-result-repository__description'>Stok: " + obat.stok + " | Batch: " + (obat.batch || '-') + " | Exp: " + (obat.expiration_date || '-') + "</div>" +
            "</div>";
        return $(markup);
    }

    function formatObatSelection(obat) {
        return obat.nama || obat.text;
    }

    // Fungsi untuk update stok tersedia
    function updateStokTersedia() {
        var gudangId = $('#gudang_asal_id').val();
        var obatId = $('#obat_id').val();
        if (!gudangId || !obatId) {
            $('#stok-tersedia').text('0');
            return;
        }
        $.get("{{ url('erm/mutasi-gudang/obat') }}", { gudang_id: gudangId, q: '' }, function(res) {
            var stok = 0;
            if (res.results && res.results.length > 0) {
                var found = res.results.find(function(item) { return item.id == obatId; });
                if (found) stok = found.stok;
            }
            $('#stok-tersedia').text(stok);
        });
    }

    // Update stok tersedia saat gudang asal berubah
    $('#gudang_asal_id').on('change', function() {
        // Reset select2 obat
        $('#obat_id').val('').trigger('change');
        updateStokTersedia();
    });

    // Update stok tersedia saat obat berubah
    $('#obat_id').on('change', function() {
        updateStokTersedia();
    });

    // Update stok tersedia when obat is selected
    $('#obat_id').on('select2:select', function(e) {
        updateStokTersedia();
    });

    // Handle detail button
    $(document).on('click', '.btn-detail', function() {
        var id = $(this).data('id');
        $.get("{{ url('erm/mutasi-gudang') }}/" + id, function(response) {
            $('#modalDetailContent').html(response.html);
            if(response.status === 'pending' && response.can_approve) {
                $('#approval-buttons').show();
                $('.btn-approve, .btn-reject').data('id', id);
            } else {
                $('#approval-buttons').hide();
            }
            $('#modalDetail').modal('show');
        });
    });

    // Handler tombol approve (delegasi event)
    $(document).on('click', '.btn-approve', function() {
        var id = $(this).data('id');
        if (confirm('Apakah Anda yakin ingin menyetujui mutasi ini?')) {
            $.ajax({
                url: "{{ url('erm/mutasi-gudang') }}/" + id + "/approve",
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    if (response.success) {
                        alert('Mutasi berhasil disetujui');
                        $('#modalDetail').modal('hide');
                        table.draw();
                    } else {
                        alert(response.message);
                    }
                },
                error: function(xhr) {
                    alert('Terjadi kesalahan: ' + xhr.responseJSON.message);
                }
            });
        }
    });

    // Handler tombol reject (delegasi event)
    $(document).on('click', '.btn-reject', function() {
        var id = $(this).data('id');
        if (confirm('Apakah Anda yakin ingin menolak mutasi ini?')) {
            $.ajax({
                url: "{{ url('erm/mutasi-gudang') }}/" + id + "/reject",
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    if (response.success) {
                        alert('Mutasi berhasil ditolak');
                        $('#modalDetail').modal('hide');
                        table.draw();
                    } else {
                        alert(response.message);
                    }
                },
                error: function(xhr) {
                    alert('Terjadi kesalahan: ' + xhr.responseJSON.message);
                }
            });
        }
    });

    // Form mutasi submit via AJAX
    $('#form-mutasi').submit(function(e) {
        e.preventDefault();
        var form = $(this);
        var gudangAsal = $('#gudang_asal_id').val();
        var gudangTujuan = $('#gudang_tujuan_id').val();
        var obat = $('#obat_id').val();
        var jumlah = $('#jumlah').val();
        var stokTersedia = parseInt($('#stok-tersedia').text());

        if (!gudangAsal || !gudangTujuan || !obat || !jumlah) {
            alert('Semua field yang bertanda * harus diisi');
            return false;
        }
        if (gudangAsal === gudangTujuan) {
            alert('Gudang tujuan tidak boleh sama dengan gudang asal');
            return false;
        }
        if (parseInt(jumlah) > stokTersedia) {
            alert('Jumlah mutasi tidak boleh melebihi stok tersedia');
            return false;
        }

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    $('#modalMutasi').modal('hide');
                    table.draw();
                    form[0].reset();
                    $('#stok-tersedia').text('0');
                    $('#obat_id').val(null).trigger('change');
                } else {
                    alert(response.message);
                }
            },
            error: function(xhr) {
                var msg = 'Terjadi kesalahan';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                alert(msg);
            }
        });
    });

    // ========== MIGRATION STOK FUNCTIONALITY ==========
    
    // Preview migration data
    $('#btn-preview').click(function() {
        $.ajax({
            url: "{{ route('erm.mutasi-gudang.migration-preview') }}",
            type: 'GET',
            beforeSend: function() {
                $('#btn-preview').prop('disabled', true).text('Loading...');
            },
            success: function(response) {
                if (response.success) {
                    $('#preview-total-obat').text(response.data.total_obat);
                    $('#preview-total-stok').text(response.data.total_stok);
                    
                    var obatListHtml = '<ul class="list-unstyled small mt-1">';
                    response.data.obat_list.forEach(function(obat) {
                        obatListHtml += '<li>' + obat.nama + ' - Stok: ' + obat.stok + ' ' + (obat.satuan || '') + '</li>';
                    });
                    obatListHtml += '</ul>';
                    
                    $('#preview-obat-list').html(obatListHtml);
                    $('#migration-preview').show();
                    
                    if (response.data.total_obat > 0) {
                        $('#btn-migrate').prop('disabled', false);
                        $('#btn-cleanup').show();
                    } else {
                        $('#btn-migrate').prop('disabled', true);
                        $('#btn-cleanup').hide();
                        alert('Tidak ada obat dengan stok > 0 di field stok untuk dimigrasi');
                    }
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr) {
                alert('Terjadi kesalahan: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
            },
            complete: function() {
                $('#btn-preview').prop('disabled', false).html('<i class="fas fa-eye"></i> Preview Data');
            }
        });
    });
    
    // Migrate stok
    $('#btn-migrate').click(function() {
        var gudangId = $('#migrate_gudang_id').val();
        
        if (!gudangId) {
            alert('Pilih gudang tujuan terlebih dahulu');
            return;
        }
        
        if (!confirm('Apakah Anda yakin ingin memindahkan semua stok dari field obat ke gudang yang dipilih?\n\nProses ini akan:\n1. Menambahkan stok ke gudang yang dipilih\n2. TIDAK mereset field stok obat (untuk keamanan)\n3. Membuat batch dengan nama MIGRATE-YYYYMMDD-ObatID\n4. Set expiration date 3 bulan dari sekarang\n\nAnda bisa cleanup field stok nanti secara manual jika diperlukan.')) {
            return;
        }
        
        $.ajax({
            url: "{{ route('erm.mutasi-gudang.migrate-stok') }}",
            type: 'POST',
            data: {
                gudang_id: gudangId,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            beforeSend: function() {
                $('#btn-migrate').prop('disabled', true).text('Processing...');
            },
            success: function(response) {
                if (response.success) {
                    alert('Berhasil! ' + response.message);
                    $('#modalMigrateStok').modal('hide');
                    
                    // Reset form and preview
                    $('#form-migrate-stok')[0].reset();
                    $('#migration-preview').hide();
                    $('#btn-migrate').prop('disabled', true);
                    
                    // Refresh table if needed
                    table.draw();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr) {
                alert('Terjadi kesalahan: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
            },
            complete: function() {
                $('#btn-migrate').prop('disabled', false).html('<i class="fas fa-database"></i> Migrate Stok');
            }
        });
    });
    
    // Cleanup field stok
    $('#btn-cleanup').click(function() {
        if (!confirm('Apakah Anda yakin ingin menghapus semua nilai di field stok obat?\n\nHANYA lakukan ini setelah Anda yakin migrasi stok berhasil!\n\nProses ini akan:\n1. Mereset field stok menjadi 0 untuk obat yang sudah ada stok di gudang\n2. TIDAK dapat di-undo\n\nPastikan backup database sudah dilakukan!')) {
            return;
        }
        
        $.ajax({
            url: "{{ route('erm.mutasi-gudang.cleanup-field-stok') }}",
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            beforeSend: function() {
                $('#btn-cleanup').prop('disabled', true).text('Processing...');
            },
            success: function(response) {
                if (response.success) {
                    alert('Berhasil! ' + response.message);
                    $('#modalMigrateStok').modal('hide');
                    
                    // Reset form and preview
                    $('#form-migrate-stok')[0].reset();
                    $('#migration-preview').hide();
                    $('#btn-migrate').prop('disabled', true);
                    $('#btn-cleanup').hide();
                    
                    // Refresh table if needed
                    table.draw();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr) {
                alert('Terjadi kesalahan: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
            },
            complete: function() {
                $('#btn-cleanup').prop('disabled', false).html('<i class="fas fa-trash"></i> Cleanup Field Stok');
            }
        });
    });
    
    // Reset modal when closed
    $('#modalMigrateStok').on('hidden.bs.modal', function() {
        $('#form-migrate-stok')[0].reset();
        $('#migration-preview').hide();
        $('#btn-migrate').prop('disabled', true);
        $('#btn-cleanup').hide();
    });
});
</script>
@endpush

{{-- @push('styles')
<style>
.select2-container { width: 100% !important; }
.select2-selection { width: 100% !important; }
.select2-selection__rendered { width: 100% !important; }
.select2-dropdown { z-index: 9999; }
</style>
@endpush --}}
