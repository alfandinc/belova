@extends('layouts.erm.app')
@section('title', 'Farmasi | Mutasi Gudang')
@section('navbar')
    @include('layouts.erm.navbar-farmasi')
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
                    <button type="button" class="btn btn-success mr-2" data-toggle="modal" data-target="#modalObatBaru">
                        <i class="fas fa-plus-circle"></i> Mutasi Obat Baru
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
                        <table class="table table-bordered dt-responsive" style="border-collapse: collapse; border-spacing: 0; width: 100%; table-layout: fixed;" id="mutasi-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>No Mutasi</th>
                                    <th style="max-width:420px; white-space:normal;">Obat</th>
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
<div class="modal fade" id="modalMutasi" tabindex="-1" role="dialog" aria-labelledby="modalMutasiLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
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
                            <label>Pilih Obat (Bisa lebih dari 1)</label>
                            <div class="table-responsive">
                                <table class="table table-sm" id="mutasi-items-table">
                                    <thead>
                                        <tr>
                                            <th style="width:40%">Obat</th>
                                            <th style="width:20%">Jumlah</th>
                                            <th style="width:30%">Keterangan</th>
                                            <th style="width:10%"><button type="button" id="add-item" class="btn btn-sm btn-primary">Tambah</button></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- rows added dynamically -->
                                    </tbody>
                                </table>
                            </div>
                            <small class="text-muted">Stok tersedia untuk setiap obat akan dilihat berdasarkan gudang asal.</small>
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

<!-- Modal Obat Baru -->
<div class="modal fade" id="modalObatBaru" tabindex="-1" role="dialog" aria-labelledby="modalObatBaruLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalObatBaruLabel">Mutasi Obat Baru</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    Fitur ini memungkinkan Anda menambahkan stok gudang untuk obat yang sudah ada di master data tapi belum ada stok di gudang manapun.
                </div>
                
                <!-- Bulk Mode Toggle -->
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="bulk_mode">
                        <label class="custom-control-label" for="bulk_mode">
                            <strong>Mode Bulk - Tambahkan SEMUA obat sekaligus</strong>
                        </label>
                    </div>
                    <small class="text-muted">Centang untuk menambahkan semua obat yang belum ada stoknya ke gudang tujuan sekaligus</small>
                </div>
                
                <!-- Bulk Preview -->
                <div id="bulk-preview" class="mb-3" style="display: none;">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6>Preview Bulk Mode:</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Total Obat:</strong> <span id="bulk-total-obat">0</span>
                                </div>
                                <div class="col-md-6">
                                    <strong>Default Stok per Obat:</strong> <span id="bulk-default-stok">0</span>
                                </div>
                            </div>
                            <div class="mt-2">
                                <strong>Sample Obat (10 pertama):</strong>
                                <div id="bulk-obat-list"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <form id="form-obat-baru">
                    <!-- Individual Mode Fields -->
                    <div id="individual-mode-fields">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="obat_baru_id">Pilih Obat <span class="text-danger">*</span></label>
                                    <select name="obat_id" id="obat_baru_id" class="form-control" required>
                                        <option value="">Pilih Obat</option>
                                    </select>
                                    <small class="text-muted">Hanya menampilkan obat aktif yang belum memiliki stok di gudang manapun</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Common Fields (for both modes) -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="gudang_baru_id">Gudang Tujuan <span class="text-danger">*</span></label>
                                <select name="gudang_id" id="gudang_baru_id" class="form-control" required>
                                    <option value="">Pilih Gudang</option>
                                    @foreach($gudangs as $gudang)
                                        <option value="{{ $gudang->id }}">{{ $gudang->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="jumlah_baru">
                                    <span id="label-jumlah">Jumlah Stok</span> <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control" name="jumlah" id="jumlah_baru" required min="1" step="0.01" value="1">
                                <small class="text-muted">
                                    <span id="help-jumlah">Jumlah stok untuk obat yang dipilih</span>
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Bulk Mode Fields -->
                    <div id="bulk-mode-fields" style="display: none;">
                        <div class="text-center mb-3">
                            <button type="button" class="btn btn-info" id="btn-preview-bulk">
                                <i class="fas fa-eye"></i> Preview Semua Obat
                            </button>
                        </div>
                    </div>
                    
                    <!-- Optional Fields (for both modes) -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="batch_baru">Batch</label>
                                <input type="text" class="form-control" name="batch" id="batch_baru" placeholder="Auto-generate jika kosong">
                                <small class="text-muted">Format: INITIAL-YYYYMMDD-ObatID (jika individual) atau BULK-YYYYMMDD (jika bulk)</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="expiration_date_baru">Tanggal Kadaluarsa</label>
                                <input type="date" class="form-control" name="expiration_date" id="expiration_date_baru">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="rak_baru">Rak/Lokasi</label>
                                <input type="text" class="form-control" name="rak" id="rak_baru" placeholder="Opsional">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="keterangan_baru">Keterangan</label>
                                <textarea name="keterangan" id="keterangan_baru" rows="3" class="form-control" placeholder="Opsional"></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-success" id="btn-simpan-obat-baru">
                    <i class="fas fa-save"></i> <span id="btn-text-simpan">Simpan</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection



@push('scripts')
<script>
$(document).ready(function() {
    // Flag for current user's Admin role (used to hide action column client-side)
    var isAdmin = @json(optional(Auth::user())->hasRole('Admin'));
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
            { data: 'nama_obat', name: 'items' },
            { data: 'gudang_asal', name: 'gudangAsal.nama' },
            { data: 'gudang_tujuan', name: 'gudangTujuan.nama' },
            { data: 'requested_by', name: 'requestedBy.name' },
            { data: 'approved_by', name: 'approvedBy.name' },
            { data: 'status_label', name: 'status' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']]
    });

    // If current user is Admin, hide the action column (last column)
    if (isAdmin) {
        // action column is the last column (index starting at 0)
        var actionIdx = table.columns().count() - 1;
        table.column(actionIdx).visible(false);
    }

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

    // --- Dynamic items management for multi-obat mutasi ---
    function initItemSelect2($select) {
        $select.select2({
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
                    return { results: data.results };
                },
                cache: true
            },
            placeholder: 'Cari obat...',
            minimumInputLength: 1,
            templateResult: formatObat,
            templateSelection: formatObatSelection
        });
    }

    function addItemRow(data) {
        var $tbody = $('#mutasi-items-table tbody');
        var rowId = Date.now() + Math.floor(Math.random() * 1000);
        var $tr = $('<tr data-row-id="' + rowId + '"></tr>');
        var obatSelect = '<select name="items['+rowId+'][obat_id]" class="form-control item-obat" required><option value="">Pilih Obat</option></select>';
        var jumlahInput = '<input type="number" min="1" name="items['+rowId+'][jumlah]" class="form-control item-jumlah" required value="1">';
        var ketInput = '<input type="text" name="items['+rowId+'][keterangan]" class="form-control item-keterangan">';
        var removeBtn = '<button type="button" class="btn btn-sm btn-danger btn-remove-item">Hapus</button>';

        $tr.append('<td>'+obatSelect+'</td>');
        $tr.append('<td>'+jumlahInput+'</td>');
        $tr.append('<td>'+ketInput+'</td>');
        $tr.append('<td>'+removeBtn+'</td>');
        $tbody.append($tr);

        var $newSelect = $tr.find('.item-obat');
        initItemSelect2($newSelect);

        if (data) {
            // set selection if provided
            var option = new Option(data.nama, data.id, true, true);
            $newSelect.append(option).trigger('change');
            $tr.find('.item-jumlah').val(data.jumlah || 1);
            $tr.find('.item-keterangan').val(data.keterangan || '');
        }
    }

    // Add initial row when modal opens
    $('#modalMutasi').on('shown.bs.modal', function() {
        // clear rows first
        $('#mutasi-items-table tbody').empty();
        // add one row by default
        addItemRow();
    });

    // Add item button
    $(document).on('click', '#add-item', function() {
        addItemRow();
    });

    // Remove item
    $(document).on('click', '.btn-remove-item', function() {
        $(this).closest('tr').remove();
    });

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

    // Form mutasi submit via AJAX (collect items table)
    $('#form-mutasi').submit(function(e) {
        e.preventDefault();
        var form = $(this);
        var gudangAsal = $('#gudang_asal_id').val();
        var gudangTujuan = $('#gudang_tujuan_id').val();

        if (!gudangAsal || !gudangTujuan) {
            alert('Semua field yang bertanda * harus diisi');
            return false;
        }
        if (gudangAsal === gudangTujuan) {
            alert('Gudang tujuan tidak boleh sama dengan gudang asal');
            return false;
        }

        // Collect items
        var items = [];
        var valid = true;
        $('#mutasi-items-table tbody tr').each(function() {
            var obatId = $(this).find('.item-obat').val();
            var jumlah = $(this).find('.item-jumlah').val();
            var keterangan = $(this).find('.item-keterangan').val();
            if (!obatId || !jumlah || parseInt(jumlah) <= 0) {
                valid = false;
                return false; // break
            }
            items.push({ obat_id: obatId, jumlah: parseInt(jumlah), keterangan: keterangan });
        });

        if (!valid || items.length === 0) {
            alert('Mohon lengkapi setidaknya satu item obat dengan jumlah valid');
            return false;
        }

        // Optional: Client-side stock check (best-effort) - fetch stock list for gudang
        // We'll send request directly and rely on server-side checks as authoritative

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                gudang_asal_id: gudangAsal,
                gudang_tujuan_id: gudangTujuan,
                items: items
            },
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    $('#modalMutasi').modal('hide');
                    table.draw();
                    form[0].reset();
                    $('#mutasi-items-table tbody').empty();
                } else {
                    alert(response.message);
                }
            },
            error: function(xhr) {
                var msg = 'Terjadi kesalahan';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    var errors = [];
                    for (var field in xhr.responseJSON.errors) {
                        errors = errors.concat(xhr.responseJSON.errors[field]);
                    }
                    msg = errors.join('\n');
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
                    
                    // Tampilkan info breakdown obat dengan dan tanpa stok
                    var infoHtml = '<div class="alert alert-info mt-2"><small>';
                    infoHtml += '<strong>Breakdown:</strong><br>';
                    infoHtml += '• Obat dengan stok > 0: ' + response.data.obat_with_stock_count + '<br>';
                    infoHtml += '• Obat dengan stok 0/null: ' + response.data.obat_without_stock_count + '<br>';
                    infoHtml += '<em>' + response.data.message + '</em>';
                    infoHtml += '</small></div>';
                    
                    var obatListHtml = '<ul class="list-unstyled small mt-1">';
                    response.data.obat_list_preview.forEach(function(obat) {
                        var stokDisplay = obat.stok || '0';
                        obatListHtml += '<li>' + obat.nama + ' - Stok: ' + stokDisplay + ' ' + (obat.satuan || '') + '</li>';
                    });
                    if (response.data.total_obat > 10) {
                        obatListHtml += '<li><em>... dan ' + (response.data.total_obat - 10) + ' obat lainnya</em></li>';
                    }
                    obatListHtml += '</ul>';
                    
                    $('#preview-obat-list').html(infoHtml + obatListHtml);
                    $('#migration-preview').show();
                    
                    // Selalu enable migrate button karena sekarang migrasi semua obat
                    $('#btn-migrate').prop('disabled', false);
                    $('#btn-cleanup').show();
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
    
    // ========== OBAT BARU FUNCTIONALITY ==========
    
    // Initialize select2 for obat baru modal
    $('#obat_baru_id').select2({
        dropdownParent: $('#modalObatBaru'),
        width: '100%',
        ajax: {
            url: "{{ route('erm.mutasi-gudang.obat-without-stock') }}",
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term
                };
            },
            processResults: function(data, params) {
                return {
                    results: data.results
                };
            },
            cache: true
        },
        placeholder: 'Cari obat yang belum ada stok...',
        minimumInputLength: 1,
        templateResult: function(obat) {
            if (obat.loading) return obat.text;
            return $('<div>' + obat.nama + '</div>');
        },
        templateSelection: function(obat) {
            return obat.nama || obat.text;
        }
    });
    
    // Initialize select2 for gudang in obat baru modal
    $('#gudang_baru_id').select2({
        dropdownParent: $('#modalObatBaru'),
        width: '100%'
    });
    
    // Handle bulk mode toggle
    $('#bulk_mode').change(function() {
        var isBulkMode = $(this).is(':checked');
        
        if (isBulkMode) {
            // Show bulk fields, hide individual fields
            $('#individual-mode-fields').hide();
            $('#bulk-mode-fields').show();
            $('#label-jumlah').text('Jumlah Stok per Obat');
            $('#help-jumlah').text('Jumlah stok yang akan ditambahkan ke setiap obat');
            $('#btn-text-simpan').text('Simpan Semua Obat');
            
            // Make obat selection not required in bulk mode
            $('#obat_baru_id').removeAttr('required');
        } else {
            // Show individual fields, hide bulk fields  
            $('#individual-mode-fields').show();
            $('#bulk-mode-fields').hide();
            $('#bulk-preview').hide();
            $('#label-jumlah').text('Jumlah Stok');
            $('#help-jumlah').text('Jumlah stok untuk obat yang dipilih');
            $('#btn-text-simpan').text('Simpan');
            
            // Make obat selection required in individual mode
            $('#obat_baru_id').attr('required', 'required');
        }
    });
    
    // Handle bulk preview
    $('#btn-preview-bulk').click(function() {
        $.ajax({
            url: "{{ route('erm.mutasi-gudang.bulk-obat-preview') }}",
            type: 'GET',
            beforeSend: function() {
                $('#btn-preview-bulk').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Loading...');
            },
            success: function(response) {
                if (response.success) {
                    $('#bulk-total-obat').text(response.data.total_obat);
                    $('#bulk-default-stok').text($('#jumlah_baru').val() || '1');
                    
                    // Show preview list
                    var previewHtml = '<div class="list-group list-group-flush">';
                    response.data.obat_list_preview.forEach(function(obat, index) {
                        previewHtml += '<div class="list-group-item py-1 px-2 small">' + 
                            (index + 1) + '. ' + obat.nama + 
                            (obat.satuan ? ' (' + obat.satuan + ')' : '') +
                            (obat.kode_obat ? ' - ' + obat.kode_obat : '') + 
                            '</div>';
                    });
                    if (response.data.total_obat > 10) {
                        previewHtml += '<div class="list-group-item py-1 px-2 small text-muted">... dan ' + 
                            (response.data.total_obat - 10) + ' obat lainnya</div>';
                    }
                    previewHtml += '</div>';
                    $('#bulk-obat-list').html(previewHtml);
                    
                    $('#bulk-preview').show();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr) {
                alert('Terjadi kesalahan: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
            },
            complete: function() {
                $('#btn-preview-bulk').prop('disabled', false).html('<i class="fas fa-eye"></i> Preview Semua Obat');
            }
        });
    });
    
    // Handle save obat baru (both individual and bulk mode)
    $('#btn-simpan-obat-baru').click(function() {
        var isBulkMode = $('#bulk_mode').is(':checked');
        var gudangId = $('#gudang_baru_id').val();
        var jumlah = $('#jumlah_baru').val();
        var batch = $('#batch_baru').val();
        var expirationDate = $('#expiration_date_baru').val();
        var rak = $('#rak_baru').val();
        var keterangan = $('#keterangan_baru').val();

        // Common validation
        if (!gudangId || !jumlah) {
            alert('Mohon lengkapi gudang tujuan dan jumlah stok');
            return;
        }

        if (parseFloat(jumlah) <= 0) {
            alert('Jumlah stok harus lebih dari 0');
            return;
        }

        if (isBulkMode) {
            // Bulk mode - process all obat
            if (!confirm('Apakah Anda yakin ingin menambahkan stok untuk SEMUA obat yang belum memiliki stok?\n\nProses ini akan menambahkan ' + jumlah + ' stok per obat ke gudang yang dipilih.')) {
                return;
            }
            
            $.ajax({
                url: "{{ route('erm.mutasi-gudang.store-bulk-obat-baru') }}",
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    gudang_id: gudangId,
                    jumlah: jumlah,
                    batch: batch,
                    expiration_date: expirationDate,
                    rak: rak,
                    keterangan: keterangan
                },
                beforeSend: function() {
                    $('#btn-simpan-obat-baru').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Memproses...');
                },
                success: function(response) {
                    if (response.success) {
                        var message = response.message;
                        if (response.details) {
                            message += '\n\nDetail:\n';
                            message += '- Total diproses: ' + response.details.total_processed + '\n';
                            message += '- Berhasil: ' + response.details.success_count + '\n';
                            message += '- Error: ' + response.details.error_count;
                        }
                        alert('Berhasil!\n\n' + message);
                        $('#modalObatBaru').modal('hide');
                        
                        // Reset form
                        $('#form-obat-baru')[0].reset();
                        $('#bulk_mode').prop('checked', false).trigger('change');
                        $('#gudang_baru_id').val('').trigger('change');
                        
                        // Refresh table
                        table.draw();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr) {
                    var errorMsg = 'Terjadi kesalahan';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg += ': ' + xhr.responseJSON.message;
                    } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                        var errors = [];
                        for (var field in xhr.responseJSON.errors) {
                            errors = errors.concat(xhr.responseJSON.errors[field]);
                        }
                        errorMsg += ':\n- ' + errors.join('\n- ');
                    }
                    alert(errorMsg);
                },
                complete: function() {
                    $('#btn-simpan-obat-baru').prop('disabled', false).html('<i class="fas fa-save"></i> Simpan Semua Obat');
                }
            });
            
        } else {
            // Individual mode - process single obat
            var obatId = $('#obat_baru_id').val();
            
            if (!obatId) {
                alert('Mohon pilih obat terlebih dahulu');
                return;
            }

            $.ajax({
                url: "{{ route('erm.mutasi-gudang.store-obat-baru') }}",
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    obat_id: obatId,
                    gudang_id: gudangId,
                    jumlah: jumlah,
                    batch: batch,
                    expiration_date: expirationDate,
                    rak: rak,
                    keterangan: keterangan
                },
                beforeSend: function() {
                    $('#btn-simpan-obat-baru').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');
                },
                success: function(response) {
                    if (response.success) {
                        alert('Berhasil! ' + response.message);
                        $('#modalObatBaru').modal('hide');
                        
                        // Reset form
                        $('#form-obat-baru')[0].reset();
                        $('#obat_baru_id').val('').trigger('change');
                        $('#gudang_baru_id').val('').trigger('change');
                        
                        // Refresh table
                        table.draw();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr) {
                    var errorMsg = 'Terjadi kesalahan';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg += ': ' + xhr.responseJSON.message;
                    } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                        var errors = [];
                        for (var field in xhr.responseJSON.errors) {
                            errors = errors.concat(xhr.responseJSON.errors[field]);
                        }
                        errorMsg += ':\n- ' + errors.join('\n- ');
                    }
                    alert(errorMsg);
                },
                complete: function() {
                    $('#btn-simpan-obat-baru').prop('disabled', false).html('<i class="fas fa-save"></i> Simpan');
                }
            });
        }
    });
    
    // Reset obat baru modal when closed
    $('#modalObatBaru').on('hidden.bs.modal', function() {
        $('#form-obat-baru')[0].reset();
        $('#obat_baru_id').val('').trigger('change');
        $('#gudang_baru_id').val('').trigger('change');
        $('#bulk_mode').prop('checked', false).trigger('change');
        $('#bulk-preview').hide();
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

@push('styles')
<style>
/* Allow table cells to wrap long content so table doesn't expand beyond viewport */
.table td, .table th {
    white-space: normal !important;
    word-break: break-word !important;
}
/* Keep headers readable (allow header wrapping but keep them compact) */
.table thead th {
    white-space: nowrap !important;
}
.select2-container { width: 100% !important; }
.select2-selection { width: 100% !important; }
.select2-selection__rendered { width: 100% !important; }
.select2-dropdown { z-index: 9999; }
</style>
@endpush
