@extends('layouts.bcl.app')

@section('title', 'Daftar Kamar')

@section('bcl-content')
<div class="page-title-box d-flex flex-wrap align-items-center justify-content-between" style="gap: 10px;">
    <div>
        <h4 class="page-title mb-0">Daftar Kamar</h4>
        <span>Klik kamar untuk sewa, bayar, perpanjang, pindah atau keluar.</span>
    </div>
    <div class="bcl-toolbar">
        <button type="button" class="btn btn-primary bcl-btn" id="bt_sewa"><i data-feather="plus"></i> Sewa Baru</button>
        <button type="button" class="btn btn-outline-primary bcl-btn" id="bt_extra"><i data-feather="package"></i> Tambahan Sewa</button>
        <button type="button" class="btn btn-outline-danger bcl-btn" id="bt_unpaid"><i data-feather="credit-card"></i> Belum Lunas <span class="bcl-count" id="unpaid_count"></span></button>
        <button type="button" class="btn btn-outline-info bcl-btn" id="bt_extra_bed" title="Ketersediaan extra bed"><i data-feather="layers"></i> Extra Bed <span class="bcl-count is-info" id="extra_bed_badge">-</span></button>
        <span class="bcl-toolbar__sep"></span>
        <button type="button" class="btn btn-outline-dark bcl-btn" id="bt_room_add" title="Tambah kamar"><i data-feather="plus-square"></i> Kamar</button>
        <button type="button" class="btn btn-outline-dark bcl-btn is-icon" id="bt_room_deleted" title="Kamar dihapus" data-toggle="tooltip"><i data-feather="archive"></i></button>
        <button type="button" class="btn btn-outline-dark bcl-btn is-icon" id="bt_export" title="Export daftar kamar (Excel/CSV)" data-toggle="tooltip"><i data-feather="download"></i></button>
    </div>
</div>

<div class="bcl-kpis" id="kpis">
    <button type="button" class="bcl-kpi" data-filter=""><span class="bcl-kpi__label">Semua kamar</span><span class="bcl-kpi__value" data-stat="all">-</span></button>
    <button type="button" class="bcl-kpi is-good" data-filter="occupied"><span class="bcl-kpi__label"><span class="bcl-kpi__dot bcl-dot-occupied"></span>Terisi</span><span class="bcl-kpi__value" data-stat="occupied">-</span></button>
    <button type="button" class="bcl-kpi is-neutral" data-filter="vacant"><span class="bcl-kpi__label"><span class="bcl-kpi__dot bcl-dot-vacant"></span>Kosong</span><span class="bcl-kpi__value" data-stat="vacant">-</span></button>
    <button type="button" class="bcl-kpi is-info" data-filter="booked"><span class="bcl-kpi__label"><span class="bcl-kpi__dot bcl-dot-booked"></span>Ada booking</span><span class="bcl-kpi__value" data-stat="pending">-</span></button>
    <button type="button" class="bcl-kpi is-bad" data-filter="overdue" title="Masa sewa habis, belum perpanjang / belum dikonfirmasi keluar"><span class="bcl-kpi__label"><span class="bcl-kpi__dot bcl-dot-unpaid"></span>Lewat masa sewa</span><span class="bcl-kpi__value" data-stat="overdue">-</span></button>
    <button type="button" class="bcl-kpi is-warn" data-filter="expiring"><span class="bcl-kpi__label"><span class="bcl-kpi__dot bcl-dot-expiring"></span>Habis &le; 7 hari</span><span class="bcl-kpi__value" data-stat="expiring">-</span></button>
    <button type="button" class="bcl-kpi is-bad" data-filter="unpaid"><span class="bcl-kpi__label"><span class="bcl-kpi__dot bcl-dot-unpaid"></span>Kamar belum lunas</span><span class="bcl-kpi__value" data-stat="unpaid">-</span></button>
</div>
<div class="bcl-kpis is-money">
    <div class="bcl-kpi is-static"><span class="bcl-kpi__label">Okupansi</span><span class="bcl-kpi__value" id="sum_occupancy">-</span></div>
    <div class="bcl-kpi is-static is-good"><span class="bcl-kpi__label">Kas masuk <span id="sum_month"></span></span><span class="bcl-kpi__value" id="sum_income">-</span></div>
    <div class="bcl-kpi is-static is-bad"><span class="bcl-kpi__label">Kas keluar</span><span class="bcl-kpi__value" id="sum_expense">-</span></div>
    <button type="button" class="bcl-kpi is-bad" id="kpi_receivable" title="Lihat tagihan belum lunas"><span class="bcl-kpi__label">Total piutang <span class="bcl-kpi__link">lihat &rarr;</span></span><span class="bcl-kpi__value" id="sum_receivable">-</span></button>
</div>
<div class="bcl-filterbar">
    <div class="bcl-search">
        <i data-feather="search"></i>
        <input type="search" class="form-control form-control-sm" id="room_search" placeholder="Cari kamar, penyewa, kategori..." autocomplete="off">
    </div>
    <span class="bcl-filter-note" id="filter_note"></span>
</div>

<div id="rooms_sections">
    <div class="bcl-rooms">
        @for($i = 0; $i < 8; $i++)<div class="bcl-skeleton"></div>@endfor
    </div>
</div>

{{-- ============================================================ room panel --}}
<div class="modal fade bcl-drawer" id="md_room" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="bcl-drawer__head">
                <div>
                    <h5 class="bcl-drawer__title" id="room_title">-</h5>
                    <div class="bcl-drawer__sub" id="room_sub"></div>
                </div>
                <div class="d-flex align-items-center" style="gap: 6px;">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="room_edit_btn" title="Edit kamar"><i data-feather="edit-2" style="width:14px;height:14px"></i></button>
                    <button type="button" class="close ml-1" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
                </div>
            </div>
            <div class="bcl-drawer__body" id="room_body"></div>
        </div>
    </div>
</div>

{{-- ============================================================ sewa --}}
<div class="modal fade" id="md_sewa" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h6 class="modal-title m-0" id="sewa_title">Sewa Kamar</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <form action="{{ route('bcl.rooms.sewa') }}" method="POST" enctype="multipart/form-data" id="form_sewa">
                @csrf
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Penyewa</label>
                            <select class="select2" name="renter" id="sewa_renter" required style="width:100%" data-placeholder="Pilih penyewa"></select>
                            <small><a href="#" class="bcl-link" id="btn_new_renter">+ Penyewa baru</a></small>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Kamar</label>
                            <select class="select2" id="kamar" name="kamar" required style="width:100%" data-placeholder="Pilih kamar"></select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Paket</label>
                            <select class="select2" id="pricelist" name="pricelist" required style="width:100%" data-placeholder="Pilih kamar dulu"></select>
                        </div>
                    </div>
                    {{-- Penyewa baru: fields have no name, they are sent separately via AJAX --}}
                    <div class="bcl-inline-panel" id="new_renter_panel" style="display:none;">
                        <div class="bcl-inline-panel__title">
                            <span>Penyewa baru</span>
                            <a href="#" class="bcl-link small" id="btn_new_renter_cancel">Batal</a>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4"><label>Nama <span class="text-danger">*</span></label><input type="text" class="form-control" id="nr_nama" autocomplete="off"></div>
                            <div class="form-group col-md-4"><label>No. HP / WA <span class="text-danger">*</span></label><input type="tel" class="form-control" id="nr_phone" autocomplete="off"></div>
                            <div class="form-group col-md-4"><label>No. HP Darurat</label><input type="tel" class="form-control" id="nr_phone2" autocomplete="off"></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-2">
                                <label>Identitas</label>
                                <select class="form-control" id="nr_identitas"><option value="KTP">KTP</option><option value="SIM">SIM</option><option value="Paspor">Paspor</option></select>
                            </div>
                            <div class="form-group col-md-4"><label>No. Identitas</label><input type="text" class="form-control" id="nr_no_identitas" autocomplete="off"></div>
                            <div class="form-group col-md-6"><label>Alamat</label><input type="text" class="form-control" id="nr_alamat" autocomplete="off"></div>
                        </div>
                        <div class="form-group">
                            <label>Foto Identitas <small class="text-muted">(opsional, bisa ditempel)</small></label>
                            <div class="bcl-dropzone" id="nr_ktp_zone"><input type="file" id="nr_ktp" accept="image/*"></div>
                        </div>
                        <div class="d-flex align-items-center justify-content-between mb-2" style="gap: 8px;">
                            <small class="text-muted">Foto diri &amp; data lain bisa dilengkapi nanti di Daftar Penyewa.</small>
                            <button type="button" class="btn btn-primary btn-sm" id="btn_new_renter_save">Simpan Penyewa</button>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Tanggal Masuk</label>
                            <input type="date" id="tgl_masuk" required name="tgl_masuk" class="form-control">
                            <small class="text-muted" id="sewa_period_hint"></small>
                        </div>
                        <div class="form-group col-md-8">
                            <label>Catatan</label>
                            <input type="text" id="catatan" name="catatan" class="form-control" autocomplete="off">
                        </div>
                    </div>
                    <hr class="my-2">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Tanggal Bayar</label>
                            <input type="date" id="tgl_bayar" required name="tgl_bayar" class="form-control">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Dibayar Tunai/Transfer (Rp)</label>
                            <input type="text" inputmode="numeric" id="nominal" required name="nominal" class="form-control js-money" autocomplete="off">
                            <div class="form-check mt-1" id="overpay_to_deposit_wrap" style="display:none;">
                                <input class="form-check-input" type="checkbox" value="1" id="overpay_to_deposit" name="overpay_to_deposit">
                                <label class="form-check-label" for="overpay_to_deposit">Simpan kelebihan ke deposit</label>
                            </div>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Deposit Penyewa <span class="text-muted" id="renter_deposit_display">Rp 0</span></label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="use_deposit" name="use_deposit" disabled>
                                <label class="form-check-label" for="use_deposit">Pakai deposit</label>
                                <button class="btn btn-link btn-sm p-0 ml-2" type="button" id="btn_topup_deposit">Top-up</button>
                            </div>
                            <input type="text" inputmode="numeric" id="deposit_amount" name="deposit_amount" class="form-control form-control-sm mt-1 js-money" value="0" style="display:none;">
                        </div>
                    </div>
                    <div class="bcl-hint mb-2" id="sewa_payment_hint"></div>
                    <div class="form-group mb-0">
                        <label>Bukti Transfer <span class="text-danger" id="bukti_transfer_required">*</span></label>
                        <div class="bcl-dropzone" data-default id="bukti_zone"><input type="file" name="bukti_transfer" accept="image/*"></div>
                        <small class="text-muted">Wajib jika ada pembayaran tunai/transfer. Screenshot bisa langsung ditempel dengan Ctrl+V.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan Sewa</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ============================================================ tambahan sewa --}}
<div class="modal fade" id="md_extra" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h6 class="modal-title m-0">Tambahan Sewa</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <form action="{{ route('bcl.extrarent.store') }}" method="POST" id="form_extra">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Untuk Penyewa</label>
                        <select class="select2" name="trans_id" id="trans_id" required style="width:100%" data-placeholder="Pilih kamar/penyewa"></select>
                    </div>
                    <div class="form-group">
                        <label>Item</label>
                        <select class="select2" name="pricelist" id="pricelist_extra" required style="width:100%" data-placeholder="Pilih item"></select>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-4">
                            <label>Mulai</label>
                            <input type="date" id="tgl_sewa" required name="tgl_sewa" class="form-control">
                        </div>
                        <div class="form-group col-4">
                            <label>Jumlah</label>
                            <input type="number" min="1" id="jml_item" required name="jml_item" class="form-control" value="1">
                        </div>
                        <div class="form-group col-4">
                            <label>Lama <span class="text-muted" id="extra_unit"></span></label>
                            <input type="number" min="1" id="lama_sewa" required name="lama_sewa" class="form-control" value="1">
                        </div>
                    </div>
                    <div class="bcl-hint" id="extra_hint"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ============================================================ terima pembayaran --}}
<div class="modal fade" id="md_pay" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h6 class="modal-title m-0">Terima Pembayaran</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <form method="POST" action="{{ route('bcl.income.store') }}" id="form_pay">
                @csrf
                <input type="hidden" id="pay_section" name="section">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Tagihan</label>
                        <select class="select2" name="transaksi" id="pay_doc" required style="width:100%" data-placeholder="Pilih tagihan"></select>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-6">
                            <label>Tanggal</label>
                            <input type="date" name="tgl_transaksi" id="pay_date" required class="form-control">
                        </div>
                        <div class="form-group col-6">
                            <label>Tunai/Transfer (Rp)</label>
                            <input type="text" inputmode="numeric" name="nominal" id="pay_nominal" required class="form-control js-money" autocomplete="off">
                        </div>
                    </div>
                    <div class="form-check mb-2" id="pay_deposit_row" style="display:none;">
                        <input class="form-check-input" type="checkbox" value="1" id="pay_use_deposit" name="use_deposit">
                        <label class="form-check-label" for="pay_use_deposit">Pakai deposit (saldo <span id="pay_deposit_balance"></span>)</label>
                        <input type="hidden" name="deposit_amount" id="pay_deposit_amount" value="0">
                    </div>
                    <div class="form-group">
                        <label>Keterangan</label>
                        <input type="text" name="keterangan" id="pay_note" class="form-control" autocomplete="off">
                    </div>
                    <div class="bcl-hint" id="pay_hint"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan Pembayaran</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ============================================================ daftar belum lunas --}}
<div class="modal fade" id="md_unpaid" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h6 class="modal-title m-0">Tagihan Belum Lunas</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead><tr><th>Tanggal</th><th>Nomor</th><th>Penyewa / Keterangan</th><th class="text-right">Total</th><th class="text-right">Kurang</th><th></th></tr></thead>
                        <tbody id="unpaid_body"><tr><td colspan="6" class="text-center text-muted py-3">Memuat...</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================ extra bed --}}
<div class="modal fade" id="md_extra_bed" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h6 class="modal-title m-0">Extra Bed</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-lg-4 mb-3">
                        <div class="bcl-section__title">Unit saat ini</div>
                        <ul class="bcl-list" id="extra_bed_assets"><li class="muted">Memuat...</li></ul>
                    </div>
                    <div class="col-lg-8">
                        <div class="bcl-section__title">Transaksi extra bed</div>
                        <div class="table-responsive" style="max-height: 420px;">
                            <table class="table table-sm mb-0">
                                <thead><tr><th>Kode</th><th>Item</th><th>Unit</th><th>Kamar / Penyewa</th><th>Periode</th><th>Status</th></tr></thead>
                                <tbody id="extra_bed_trx"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================ tambah / edit kamar --}}
<div class="modal fade" id="md_room_form" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h6 class="modal-title m-0" id="room_form_title">Tambah Kamar</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <form method="POST" id="form_room" data-store="{{ route('bcl.rooms.store') }}" data-update="{{ route('bcl.rooms.update') }}">
                @csrf
                <input type="hidden" name="id" id="room_form_id">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-6">
                            <label>No/Nama Kamar</label>
                            <input type="text" name="no_kamar" id="room_form_name" required class="form-control" autocomplete="off">
                        </div>
                        <div class="form-group col-6">
                            <label>Lantai</label>
                            <input type="number" min="1" max="10" name="floor" id="room_form_floor" required class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Kategori</label>
                        <select class="select2" name="kategori" id="room_form_category" required style="width:100%"></select>
                    </div>
                    <div class="form-group mb-0">
                        <label>Catatan</label>
                        <input type="text" name="catatan" id="room_form_notes" class="form-control" autocomplete="off">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger btn-sm mr-auto" id="room_delete_btn" style="display:none;">Hapus Kamar</button>
                    <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="md_deleted" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h6 class="modal-title m-0">Kamar Dihapus</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body"><ul class="bcl-list" id="deleted_rooms"><li class="muted">Memuat...</li></ul></div>
        </div>
    </div>
</div>

{{-- ============================================================ wifi --}}
<div class="modal fade" id="md_wifi" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h6 class="modal-title m-0">WiFi <span id="wifi_room_name"></span></h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <form id="form_wifi">
                <input type="hidden" id="wifi_record_id">
                <input type="hidden" id="wifi_room_id">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-6"><label>SSID</label><input type="text" class="form-control" id="wifi_ssid" required></div>
                        <div class="form-group col-6"><label>Password</label><input type="text" class="form-control" id="wifi_password"></div>
                    </div>
                    <div class="form-group"><label>Catatan</label><input type="text" class="form-control" id="wifi_notes"></div>
                    <div class="form-check"><input type="checkbox" class="form-check-input" id="wifi_active" checked><label class="form-check-label" for="wifi_active">Aktif</label></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan WiFi</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ============================================================ detail penyewa --}}
<div class="modal fade" id="md_renter" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h6 class="modal-title m-0" id="renter_title">Penyewa</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body" id="renter_body"></div>
        </div>
    </div>
</div>

{{-- ============================================================ top-up deposit --}}
<div class="modal fade" id="md_topup_deposit" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h6 class="modal-title m-0">Top-up Deposit</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <form action="{{ route('bcl.deposit.topup') }}" method="POST">
                @csrf
                <input type="hidden" name="renter" id="topup_renter_id">
                <div class="modal-body">
                    <div class="form-group"><label>Jumlah (Rp)</label><input type="text" inputmode="numeric" name="amount" class="form-control js-money" required></div>
                    <div class="form-group"><label>Tanggal</label><input type="date" name="tgl_transaksi" value="{{ date('Y-m-d') }}" class="form-control" required></div>
                    <div class="form-group mb-0"><label>Catatan</label><input type="text" name="note" class="form-control"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">Top-up</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('bcl.partials.refund_modal')
@include('bcl.partials.change_room_modal')
@endsection

@section('pagescript')
<script>
    window.BCL_ROOMS = {
        initial: @json($initial),
        today: @json(now()->format('Y-m-d')),
        urls: {
            data: @json(route('bcl.rooms.data')),
            formData: @json(route('bcl.rooms.form_data')),
            unpaid: @json(route('bcl.rooms.unpaid_data')),
            extraBed: @json(route('bcl.rooms.extra_bed_data')),
            deleted: @json(route('bcl.rooms.deleted_data')),
            edit: @json(route('bcl.rooms.edit', ':id')),
            remove: @json(route('bcl.rooms.delete', ':id')),
            wifi: @json(route('bcl.rooms.wifi', ':id')),
            wifiStore: @json(route('bcl.roomwifi.store')),
            wifiUpdate: @json(route('bcl.roomwifi.update', ':id')),
            history: @json(route('bcl.rooms.history', ':id')),
            pricelist: @json(route('bcl.pricelist.get_pl_room', ':id')),
            renter: @json(route('bcl.renter.detail', ':id')),
            renterQuickStore: @json(route('bcl.renter.quick_store')),
            print: @json(route('bcl.transaksi.cetak', ':id')),
            checkout: @json(route('bcl.transaksi.checkout')),
            availability: @json(route('bcl.extrarent.availability')),
        },
    };
</script>
<script src="{{ asset('js/bcl/rooms.js') }}?v={{ filemtime(public_path('js/bcl/rooms.js')) }}"></script>
@include('bcl.partials.change_room_script')
@include('bcl.partials.refund_script')
@endsection
