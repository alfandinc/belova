{{--
    Paket racikan modals for an e-resep page: list + create/edit form, detail, and "gunakan" confirmation.
    $sfx: '' on the dokter page, 'Farmasi' on the farmasi page (the pages' JS uses the suffixed ids).
    $withDeskripsi: the dokter form has a deskripsi field, the farmasi form does not.
--}}
@php
    $sfx = $sfx ?? '';
    $withDeskripsi = $withDeskripsi ?? false;
@endphp
@include('erm.partials.paket-racikan-theme')

<!-- Modal Paket Racikan -->
<div class="modal fade" id="paketRacikanModal{{ $sfx }}" tabindex="-1" role="dialog" aria-labelledby="paketRacikanModal{{ $sfx }}Label" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content pr-modal">
            <div class="modal-header">
                <h5 class="modal-title" id="paketRacikanModal{{ $sfx }}Label">
                    <span class="pr-title-ico"><i class="fas fa-mortar-pestle"></i></span>
                    <span>Paket Racikan<small>Pilih paket lalu klik Gunakan. Klik nama paket untuk mengubahnya.</small></span>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-lg-7">
                        <div class="pr-card">
                            <div class="pr-card-head">
                                <h6 class="pr-card-title">Daftar Paket</h6>
                                <div class="pr-search" style="flex: 0 1 260px;">
                                    <i class="fas fa-search"></i>
                                    <input type="text" class="form-control form-control-sm" id="searchPaketRacikan{{ $sfx }}" placeholder="Cari nama paket / obat..." aria-label="Cari paket racikan">
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm pr-table" id="paketRacikanTable{{ $sfx }}">
                                    <thead>
                                        <tr>
                                            <th width="7%">No</th>
                                            <th width="31%">Nama Paket</th>
                                            <th width="40%">Obat (Dosis)</th>
                                            <th width="22%" class="text-right">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="paketRacikanTableBody{{ $sfx }}">
                                        <tr>
                                            <td colspan="4" class="text-center">Memuat...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="pr-card">
                            <div class="pr-card-head">
                                {{-- text set by setPaketFormMode(): "Buat Paket Racikan Baru" / "Edit Paket: …" --}}
                                <h6 class="pr-card-title paket-form-title"><strong>Buat Paket Racikan Baru</strong></h6>
                            </div>
                            <div class="pr-card-body">
                                <form id="formPaketRacikan{{ $sfx }}">
                                    <input type="hidden" name="id" id="paketId{{ $sfx }}" value="">
                                    <div class="form-group">
                                        <label>Nama Paket</label>
                                        <input type="text" class="form-control" name="nama_paket" required>
                                    </div>
                                    @if ($withDeskripsi)
                                        <div class="form-group">
                                            <label>Deskripsi</label>
                                            <textarea class="form-control" name="deskripsi" rows="2"></textarea>
                                        </div>
                                    @endif
                                    <div class="form-row">
                                        <div class="form-group col-7">
                                            <label>Wadah</label>
                                            <select class="form-control select2-wadah-paket" name="wadah_id">
                                                <option value="">Pilih Wadah</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-5">
                                            <label>Bungkus Default</label>
                                            <input type="number" class="form-control" name="bungkus_default" value="10" min="1" required>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Aturan Pakai Default</label>
                                        <input type="text" class="form-control aturan_pakai" name="aturan_pakai_default" placeholder="Ketik aturan pakai">
                                    </div>

                                    <div class="pr-obat-head">
                                        <span>Obat dalam Paket</span>
                                        <button type="button" class="btn btn-sm pr-btn-soft" id="tambahObatPaket{{ $sfx }}"><i class="fas fa-plus mr-1"></i>Tambah Obat</button>
                                    </div>
                                    <div id="obatPaketContainer{{ $sfx }}">
                                        <div class="obat-paket-item mb-2">
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <select class="form-control select2-obat-paket" name="obats[0][obat_id]" required>
                                                        <option value="">Pilih Obat</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="text" class="form-control" name="obats[0][dosis]" placeholder="Dosis" required>
                                                </div>
                                                <div class="col-md-1">
                                                    <button type="button" class="btn btn-danger btn-sm remove-obat-paket" style="display:none;">×</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="pr-form-actions">
                                        <button type="submit" class="btn pr-btn-primary">Simpan Paket</button>
                                        <button type="button" class="btn pr-btn-ghost" id="resetFormPaketBtn{{ $sfx }}">Reset</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn pr-btn-ghost" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Paket Racikan -->
<div class="modal fade" id="detailPaketModal{{ $sfx }}" tabindex="-1" role="dialog" aria-labelledby="detailPaketModal{{ $sfx }}Label" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content pr-modal">
            <div class="modal-header">
                <h5 class="modal-title" id="detailPaketModal{{ $sfx }}Label">
                    <span class="pr-title-ico"><i class="fas fa-mortar-pestle"></i></span>Detail Paket Racikan
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body" id="detailPaketContent{{ $sfx }}">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn pr-btn-ghost" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Gunakan Paket Racikan -->
<div class="modal fade" id="gunakanPaketModal{{ $sfx }}" tabindex="-1" role="dialog" aria-labelledby="gunakanPaketModal{{ $sfx }}Label" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content pr-modal">
            <div class="modal-header">
                <h5 class="modal-title" id="gunakanPaketModal{{ $sfx }}Label">
                    <span class="pr-title-ico"><i class="fas fa-mortar-pestle"></i></span>Gunakan Paket Racikan
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="pr-chosen">
                    <div>
                        <small>Paket dipilih</small>
                        <p id="paketNamaDisplay{{ $sfx }}"></p>
                    </div>
                </div>
                <div class="form-group">
                    <label for="paketBungkus{{ $sfx }}">Jumlah Bungkus <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="paketBungkus{{ $sfx }}" min="1" required>
                </div>
                <div class="form-group">
                    <label for="paketAturanPakai{{ $sfx }}">Aturan Pakai <span class="text-danger">*</span></label>
                    <input type="text" class="form-control aturan_pakai" id="paketAturanPakai{{ $sfx }}" placeholder="Ketik aturan pakai" required>
                </div>
                <input type="hidden" id="selectedPaketId{{ $sfx }}">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn pr-btn-ghost" data-dismiss="modal">Batal</button>
                <button type="button" class="btn pr-btn-primary" id="konfirmasiGunakanPaket{{ $sfx }}" style="flex: 0 0 auto;">Gunakan Paket</button>
            </div>
        </div>
    </div>
</div>
