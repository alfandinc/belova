<!-- Modal: Daftarkan Kunjungan (Billing Index) -->
<div class="modal fade" id="modalDaftarKunjunganBillingIndex" tabindex="-1" role="dialog" aria-labelledby="modalDaftarKunjunganBillingIndexLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="form-daftar-kunjungan-billing-index">
            @csrf
            <input type="hidden" name="jenis_kunjungan" id="fb_jenis_kunjungan" value="1">
            <input type="hidden" id="fb_mode" value="konsultasi">

            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="modalDaftarKunjunganBillingIndexLabel">Daftarkan Kunjungan Pasien</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="la la-times"></i></span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label>Pasien</label>
                        <select id="fb_pasien_id" name="pasien_id" class="form-control" required></select>
                    </div>

                    <div class="form-group">
                        <label>Klinik</label>
                        <select id="fb_klinik_id" name="klinik_id" class="form-control select2" required>
                            <option value="" disabled selected>Pilih Klinik</option>
                            @foreach(($kliniks ?? []) as $klinik)
                                <option value="{{ $klinik->id }}">{{ $klinik->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Dokter</label>
                        <select id="fb_dokter_id" name="dokter_id" class="form-control select2" required disabled>
                            <option value="">Pilih Dokter</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Tanggal Kunjungan</label>
                        <input type="date" class="form-control" id="fb_tanggal_visitation" name="tanggal_visitation" required>
                    </div>

                    <div class="form-group" id="fb_waktu_group">
                        <label>Waktu Kunjungan (Opsional)</label>
                        <input type="time" class="form-control" id="fb_waktu_kunjungan" name="waktu_kunjungan">
                    </div>

                    <div class="form-group">
                        <label for="fb_metode_bayar_id">Cara Bayar</label>
                        <select class="form-control select2" id="fb_metode_bayar_id" name="metode_bayar_id" required>
                            <option value="" selected disabled>Pilih Metode Bayar</option>
                            @foreach(($metodeBayar ?? []) as $metode)
                                <option value="{{ $metode->id }}">{{ $metode->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" id="fb_no_antrian_group">
                        <label>No Antrian</label>
                        <input type="text" name="no_antrian" id="fb_no_antrian" class="form-control" readonly>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>
