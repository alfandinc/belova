<!-- Modal Paket Racikan -->
<div class="modal fade" id="paketRacikanModal" tabindex="-1" role="dialog" aria-labelledby="paketRacikanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paketRacikanModalLabel">Paket Racikan Template</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-8">
                        <h6><strong>Daftar Paket Racikan</strong></h6>                        <div class="table-responsive">
                            <table class="table table-sm table-bordered" id="paketRacikanTable">
                                <thead class="thead-light">
                                    <tr>
                                        <th width="8%">No</th>
                                        <th width="40%">Nama Paket</th>
                                        <th width="22%">Wadah</th>
                                        <th width="30%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="paketRacikanTableBody">
                                    <tr>
                                        <td colspan="4" class="text-center">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <h6><strong>Buat Paket Racikan Baru</strong></h6>
                        <form id="formPaketRacikan">
                            <div class="form-group">
                                <label>Nama Paket</label>
                                <input type="text" class="form-control" name="nama_paket" required>
                            </div>
                            <div class="form-group">
                                <label>Deskripsi</label>
                                <textarea class="form-control" name="deskripsi" rows="2"></textarea>
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
                                <input type="text" class="form-control" name="aturan_pakai_default" placeholder="3 x 1 hari">
                            </div>
                            
                            <h6><strong>Obat dalam Paket</strong></h6>
                            <div id="obatPaketContainer">
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
                            <button type="button" class="btn btn-sm btn-secondary" id="tambahObatPaket">+ Tambah Obat</button>
                              <div class="mt-3">
                                <button type="submit" class="btn btn-primary">Simpan Paket</button>
                                <button type="button" class="btn btn-secondary" id="resetFormPaketBtn">Reset</button>
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

<!-- Modal Detail Paket Racikan -->
<div class="modal fade" id="detailPaketModal" tabindex="-1" role="dialog" aria-labelledby="detailPaketModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailPaketModalLabel">Detail Paket Racikan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="detailPaketContent">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
