@extends('layouts.bcl.app')

@section('title', 'Inventaris')

@section('bcl-content')
<div class="page-title-box d-flex flex-wrap align-items-center justify-content-between" style="gap: 10px;">
    <div>
        <h4 class="page-title mb-0">Inventaris</h4>
        <span>Klik <strong>Rawat</strong> untuk mencatat perawatan/perbaikan, nomor inventaris untuk riwayatnya.</span>
    </div>
    <div class="bcl-toolbar">
        <button type="button" class="btn btn-primary bcl-btn" id="bt_inv_add"><i data-feather="plus"></i> Tambah Inventaris</button>
        <button type="button" class="btn btn-secondary bcl-btn" data-toggle="modal" data-target="#md_export_inv"><i data-feather="printer"></i> Export PDF</button>
    </div>
</div>

<div class="bcl-kpis" id="inv_kpis">
    <button type="button" class="bcl-kpi" data-status=""><span class="bcl-kpi__label">Semua inventaris</span><span class="bcl-kpi__value" data-stat="total">-</span></button>
    <button type="button" class="bcl-kpi is-bad" data-status="overdue"><span class="bcl-kpi__label"><span class="bcl-kpi__dot bcl-dot-unpaid"></span>Terlambat dirawat</span><span class="bcl-kpi__value" data-stat="overdue">-</span></button>
    <button type="button" class="bcl-kpi is-warn" data-status="soon"><span class="bcl-kpi__label"><span class="bcl-kpi__dot bcl-dot-expiring"></span>Jatuh tempo &le; 7 hari</span><span class="bcl-kpi__value" data-stat="soon">-</span></button>
    <div class="bcl-kpi is-static is-info"><span class="bcl-kpi__label">Dengan perawatan rutin</span><span class="bcl-kpi__value" data-stat="routine">-</span></div>
    <div class="bcl-kpi is-static"><span class="bcl-kpi__label">Biaya perawatan bulan ini</span><span class="bcl-kpi__value is-sm" data-stat="month_cost">-</span></div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-end mb-2" style="gap: 10px;">
            <div style="min-width: 220px;">
                <label class="mb-1">Lokasi</label>
                <select id="flt_room" class="form-control form-control-sm select2" data-placeholder="Semua lokasi">
                    <option value="">Semua lokasi</option>
                    <option value="umum">Umum (bukan kamar)</option>
                    @foreach($rooms as $r)
                    <option value="{{ $r->id }}">{{ $r->room_name }}{{ $r->floor ? ' · Lantai ' . $r->floor : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div style="min-width: 200px;">
                <label class="mb-1">Status perawatan</label>
                <select id="flt_status" class="form-control form-control-sm">
                    <option value="">Semua</option>
                    <option value="overdue">Terlambat dirawat</option>
                    <option value="soon">Jatuh tempo &le; 7 hari</option>
                    <option value="ok">Terjadwal</option>
                    <option value="none">Tanpa perawatan rutin</option>
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 w-100" id="tb_inventory">
                <thead>
                    <tr>
                        <th>No. Inv</th>
                        <th>Nama</th>
                        <th>Lokasi</th>
                        <th>Perawatan rutin</th>
                        <th>Terakhir dirawat</th>
                        <th>Berikutnya</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

{{-- ============================================================ tambah / edit --}}
<div class="modal fade" id="md_inv" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h6 class="modal-title m-0" id="inv_title">Tambah Inventaris</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <form id="form_inv">
                <input type="hidden" name="id" id="inv_id">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-md-4" id="inv_number_group" style="display:none;">
                            <label>Nomor Inventaris</label>
                            <input type="text" name="inv_number" id="inv_number" class="form-control">
                            <small class="text-muted">Mengubah nomor ikut memindahkan riwayat perawatannya.</small>
                        </div>
                        <div class="form-group col-md-8" id="inv_name_group">
                            <label>Nama Barang <span class="text-danger">*</span></label>
                            <input type="text" name="nama" id="inv_name" class="form-control" required autocomplete="off" placeholder="mis. AC split 1 PK, Kasur, Lemari">
                        </div>
                        <div class="form-group col-md-4" id="inv_qty_group">
                            <label>Jumlah per lokasi</label>
                            <input type="number" min="1" max="50" name="jumlah" id="inv_qty" class="form-control" value="1">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="d-block">Lokasi <span class="text-danger">*</span></label>
                        <div class="btn-group btn-group-toggle" data-toggle="buttons">
                            <label class="btn btn-outline-primary btn-sm active"><input type="radio" name="tipe_inv" value="Private/Room" checked> Di kamar</label>
                            <label class="btn btn-outline-primary btn-sm"><input type="radio" name="tipe_inv" value="Public"> Umum (fasilitas bersama)</label>
                        </div>
                    </div>
                    <div class="form-group" id="inv_room_group">
                        <label id="inv_room_label">Kamar <span class="text-danger">*</span> <small class="text-muted">(bisa pilih beberapa: dibuat satu item per kamar)</small></label>
                        <select name="kamar[]" id="inv_rooms" class="form-control select2" multiple style="width: 100%" data-placeholder="Pilih kamar">
                            @foreach($rooms as $r)
                            <option value="{{ $r->id }}">{{ $r->room_name }}{{ $r->floor ? ' · Lantai ' . $r->floor : '' }}{{ $r->category_name ? ' (' . $r->category_name . ')' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="custom-control custom-switch mb-2">
                        <input type="checkbox" class="custom-control-input" id="inv_routine" name="perawatan_rutin" value="1">
                        <label class="custom-control-label" for="inv_routine">Perlu perawatan rutin</label>
                    </div>
                    <div class="form-row" id="inv_routine_group" style="display:none;">
                        <div class="form-group col-4 col-md-2"><label>Setiap</label><input type="number" min="1" max="120" name="waktu_perawatan" id="inv_period" class="form-control"></div>
                        <div class="form-group col-8 col-md-3"><label>&nbsp;</label>
                            <select name="cycle_perawatan" id="inv_cycle" class="form-control"><option value="Minggu">Minggu</option><option value="Bulan" selected>Bulan</option><option value="Tahun">Tahun</option></select>
                        </div>
                        <div class="form-group col-md-7"><small class="text-muted d-block mt-md-4">Jadwal dihitung dari perawatan terakhir (atau dari tanggal didaftarkan jika belum pernah dirawat).</small></div>
                    </div>
                    <div class="form-group mb-0">
                        <label>Keterangan</label>
                        <input type="text" name="keterangan" id="inv_notes" class="form-control" autocomplete="off" placeholder="merek, ukuran, kondisi, dll.">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ============================================================ rawat + riwayat --}}
<div class="modal fade" id="md_maint" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h6 class="modal-title m-0">Perawatan <span id="mt_title" class="font-weight-normal"></span></h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <dl class="bcl-kv mb-3" id="mt_info"></dl>
                <form id="form_maint" class="bcl-inline-panel">
                    <input type="hidden" name="inv_number" id="mt_inv">
                    <input type="hidden" name="id" id="mt_id">
                    <div class="bcl-inline-panel__title"><span id="mt_form_title">Catat perawatan / perbaikan</span><a href="#" class="bcl-link small" id="mt_cancel_edit" style="display:none;">Batal koreksi</a></div>
                    <div class="form-row">
                        <div class="form-group col-md-3"><label>Tanggal</label><input type="date" name="tanggal" id="mt_date" class="form-control" required></div>
                        <div class="form-group col-md-9"><label>Yang dikerjakan <span class="text-danger">*</span></label><input type="text" name="catatan" id="mt_desc" class="form-control" required autocomplete="off" placeholder="mis. cuci AC, ganti freon, servis pompa"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-3"><label>Biaya (Rp)</label><input type="text" inputmode="numeric" name="nominal" id="mt_cost" class="form-control js-money" placeholder="0"></div>
                        <div class="form-group col-md-3"><label>Dibayar dengan</label><select name="metode_bayar" id="mt_method" class="form-control">@foreach(\App\Services\BCL\RentalService::METHODS as $m)<option value="{{ $m }}">{{ $m }}</option>@endforeach</select></div>
                        <div class="form-group col-md-6"><label>Vendor / teknisi</label><input type="text" name="vendor_name" id="mt_vendor" class="form-control" autocomplete="off"></div>
                    </div>
                    <div class="form-group" id="mt_proof_group" style="display:none;">
                        <label>Nota / bukti bayar <small class="text-muted">(opsional, bisa Ctrl+V)</small></label>
                        <div class="bcl-dropzone" data-default id="mt_zone"><input type="file" name="bukti" accept="image/*"></div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="text-muted">Biaya &gt; 0 otomatis tercatat di Pengeluaran (perawatan inventaris).</small>
                        <button type="submit" class="btn btn-primary btn-sm" id="mt_submit">Simpan Perawatan</button>
                    </div>
                </form>
                <div class="bcl-section__title mt-3">Riwayat perawatan</div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Tanggal</th><th>Yang dikerjakan</th><th>Vendor</th><th class="text-right">Biaya</th><th class="text-right">Aksi</th></tr></thead>
                        <tbody id="mt_history"><tr><td colspan="5" class="text-muted">Memuat...</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================ export --}}
<div class="modal fade" id="md_export_inv" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h6 class="modal-title m-0">Export Inventaris ke PDF</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <form action="{{ route('bcl.inventories.export.pdf') }}" method="POST" target="_blank">
                @csrf
                <div class="modal-body">
                    <label>Kamar <small class="text-muted">(kosongkan untuk semua kamar + inventaris umum)</small></label>
                    <select name="rooms[]" class="form-control select2" multiple style="width:100%" data-placeholder="Semua kamar">
                        @foreach($rooms as $r)
                        <option value="{{ $r->id }}">{{ $r->room_name }}{{ $r->floor ? ' · Lantai ' . $r->floor : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary btn-sm">Buka PDF</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('pagescript')
<script>
    window.BCL_INV = {
        today: @json(now()->format('Y-m-d')),
        urls: {
            data: @json(route('bcl.inventories.data')),
            stats: @json(route('bcl.inventories.stats')),
            store: @json(route('bcl.inventories.store')),
            update: @json(route('bcl.inventories.update')),
            edit: @json(route('bcl.inventories.edit', ':id')),
            remove: @json(route('bcl.inventories.delete', ':id')),
            show: @json(route('bcl.inventories.show', ':id')),
            maintStore: @json(route('bcl.inventories.maintenance.store')),
            maintUpdate: @json(route('bcl.inventories.maintenance.update')),
            maintDelete: @json(route('bcl.inventories.maintenance.delete')),
        },
    };
</script>
<script src="{{ asset('js/bcl/inventories.js') }}?v={{ filemtime(public_path('js/bcl/inventories.js')) }}"></script>
@endsection
