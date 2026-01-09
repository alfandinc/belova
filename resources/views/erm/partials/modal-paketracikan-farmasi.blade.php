<!-- Modal Paket Racikan (Farmasi) -->
<div class="modal fade" id="paketRacikanModalFarmasi" tabindex="-1" role="dialog" aria-labelledby="paketRacikanModalFarmasiLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paketRacikanModalFarmasiLabel">Paket Racikan Template (Farmasi)</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-7">
                        <h6><strong>Daftar Paket Racikan</strong></h6>
                        <div class="mb-2">
                            <input type="text" class="form-control form-control-sm" id="searchPaketRacikanFarmasi" placeholder="Cari paket racikan...">
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered" id="paketRacikanTableFarmasi">
                                <thead class="thead-light">
                                    <tr>
                                        <th width="8%">No</th>
                                        <th width="60%">Nama Paket</th>
                                        <th width="32%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="paketRacikanTableBodyFarmasi">
                                    <tr>
                                        <td colspan="3" class="text-center">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <h6><strong>Buat Paket Racikan Baru</strong></h6>
                        <form id="formPaketRacikanFarmasi">
                            <input type="hidden" name="id" id="paketIdFarmasi" value="">
                            <div class="form-group">
                                <label>Nama Paket</label>
                                <input type="text" class="form-control" name="nama_paket" required>
                            </div>
                            <div class="form-group">
                                <label>Wadah</label>
                                <select class="form-control select2-wadah-paket" name="wadah_id">
                                    <option value="">Pilih Wadah</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Bungkus Default</label>
                                <input type="number" class="form-control" name="bungkus_default" value="10" min="1" required>
                            </div>
                            <div class="form-group">
                                <label>Aturan Pakai Default</label>
                                <select class="form-control select2-aturan-pakai-farmasi" name="aturan_pakai_default" style="width:100%">
                                    <option value="">Pilih Aturan Pakai</option>
                                </select>
                            </div>
                            
                            <h6><strong>Obat dalam Paket</strong></h6>
                            <div id="obatPaketContainerFarmasi">
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
                            <button type="button" class="btn btn-sm btn-secondary" id="tambahObatPaketFarmasi">+ Tambah Obat</button>
                              <div class="mt-3">
                                <button type="submit" class="btn btn-primary">Simpan Paket</button>
                                <button type="button" class="btn btn-secondary" id="resetFormPaketBtnFarmasi">Reset</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Paket Racikan (Farmasi) -->
<div class="modal fade" id="detailPaketModalFarmasi" tabindex="-1" role="dialog" aria-labelledby="detailPaketModalFarmasiLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailPaketModalFarmasiLabel">Detail Paket Racikan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="detailPaketContentFarmasi">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Gunakan Paket Racikan (Farmasi) -->
<div class="modal fade" id="gunakanPaketModalFarmasi" tabindex="-1" role="dialog" aria-labelledby="gunakanPaketModalFarmasiLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="gunakanPaketModalFarmasiLabel">Gunakan Paket Racikan (Farmasi)</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label><strong>Nama Paket:</strong></label>
                    <p id="paketNamaDisplayFarmasi" class="mb-2"></p>
                </div>
                <div class="form-group">
                    <label for="paketBungkusFarmasi">Jumlah Bungkus <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="paketBungkusFarmasi" min="1" required>
                </div>
                <div class="form-group">
                    <label for="paketAturanPakaiFarmasi">Aturan Pakai <span class="text-danger">*</span></label>
                    <select class="form-control select2-aturan-pakai-farmasi-modal" id="paketAturanPakaiFarmasi" required style="width:100%">
                        <option value="">Pilih Aturan Pakai</option>
                    </select>
                </div>
                <input type="hidden" id="selectedPaketIdFarmasi">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="konfirmasiGunakanPaketFarmasi">OK</button>
            </div>
        </div>
    </div>
</div>
