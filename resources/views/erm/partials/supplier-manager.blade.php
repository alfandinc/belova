{{--
    Kelola pemasok or principal (SupplierMasterController): add, edit, merge duplicates, delete unused, export.
    Principal is managed on Master Obat, pemasok on Master Pembelian; wired by erm.partials.supplier-manager-js.
    Params: $key (pemasok|principal), $label, $subtitle, $placeholder, $canDelete, $obatHint
--}}
@php($lower = strtolower($label))
<div class="modal fade supplier-manager-modal" id="{{ $key }}ManagerModal" tabindex="-1" role="dialog" aria-labelledby="{{ $key }}ManagerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $key }}ManagerModalLabel">
                    <i class="fas {{ $key === 'pemasok' ? 'fa-truck' : 'fa-industry' }} mr-1"></i> Kelola {{ $label }} <small class="text-muted">({{ $subtitle }})</small>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body supplier-pane">
                {{-- Tambah / Edit --}}
                <form class="supplier-form mb-3 p-2 rounded border" autocomplete="off" novalidate>
                    <input type="hidden" name="id" value="">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small text-muted supplier-form-title">Tambah {{ $lower }} baru</span>
                    </div>
                    <div class="form-row">
                        <div class="col-md-5 mb-2">
                            <input type="text" name="nama" class="form-control form-control-sm" placeholder="Nama {{ $lower }}, mis. {{ $placeholder }}" maxlength="255">
                        </div>
                        <div class="col-md-3 mb-2">
                            <input type="text" name="telepon" class="form-control form-control-sm" placeholder="Telepon" maxlength="255">
                        </div>
                        <div class="col-md-4 mb-2">
                            <input type="email" name="email" class="form-control form-control-sm" placeholder="Email" maxlength="255">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md mb-1 mb-md-0">
                            <input type="text" name="alamat" class="form-control form-control-sm" placeholder="Alamat (opsional)">
                        </div>
                        <div class="col-md-auto text-right">
                            <button type="button" class="btn btn-sm btn-light border supplier-cancel d-none">Batal</button>
                            <button type="submit" class="btn btn-sm btn-primary supplier-submit"><i class="fas fa-plus mr-1"></i> Tambah</button>
                        </div>
                    </div>
                    <small class="form-text supplier-feedback"></small>
                </form>

                {{-- Gabungkan (shown when merging) --}}
                <div class="alert alert-info supplier-merge-panel d-none">
                    <div class="mb-2"><i class="fas fa-code-branch mr-1"></i> Gabungkan <strong class="supplier-merge-source"></strong> ke:</div>
                    <select class="form-control supplier-merge-target"></select>
                    <small class="d-block mt-2">Semua data yang memakai <strong class="supplier-merge-source"></strong> akan dipindah ke {{ $lower }} tujuan, lalu nama ganda ini dihapus.</small>
                    <div class="mt-2 text-right">
                        <button type="button" class="btn btn-sm btn-light border supplier-merge-cancel">Batal</button>
                        <button type="button" class="btn btn-sm btn-primary supplier-merge-save"><i class="fas fa-check mr-1"></i> Gabungkan</button>
                    </div>
                </div>

                {{-- Cari & filter --}}
                <div class="form-row">
                    <div class="col-md-6 mb-2">
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search"></i></span></div>
                            <input type="search" class="form-control supplier-search" placeholder="Cari nama, alamat, telepon, email" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-md-4 col-8 mb-2">
                        <select class="form-control form-control-sm supplier-filter">
                            <option value="">Semua {{ $lower }}</option>
                            <option value="dipakai">Dipakai</option>
                            <option value="tidak_dipakai">Tidak dipakai</option>
                            <option value="ganda">Nama ganda</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-4 mb-2 text-right">
                        <a href="{{ url('/erm/' . $key . '/export-excel') }}" class="btn btn-sm btn-outline-success btn-block" title="Export Excel"><i class="fas fa-file-excel mr-1"></i> Excel</a>
                    </div>
                </div>
                <div class="small text-warning mb-2 d-none supplier-duplicate-hint"></div>

                <table class="table table-sm table-hover w-100 supplier-table">
                    <thead class="thead-light"><tr><th style="width:50px">No</th><th>Nama {{ $label }}</th><th>Dipakai di</th><th class="text-right" style="width:120px">Aksi</th></tr></thead>
                    <tbody></tbody>
                </table>
                <small class="text-muted d-block">
                    {{ $obatHint }} Klik <i class="fas fa-pen"></i> untuk mengubah nama atau kontak.
                    @if($canDelete) Hapus hanya untuk data yang tidak dipakai; nama ganda gunakan <em>Gabungkan</em>. @endif
                </small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
