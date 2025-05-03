<!-- Modal Daftar Kunjungan -->
<div class="modal fade" id="modalKunjungan" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form id="form-kunjungan">
      @csrf
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalLabel">Daftarkan Kunjungan Pasien</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true"><i class="la la-times"></i></span>
          </button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="pasien_id" id="modal-pasien-id">
            
            <div class="form-group">
              <label for="nama_pasien">Nama Pasien</label>
              <input type="text" id="modal-nama-pasien" class="form-control" value="{{ $pasienName }}" readonly>
            </div>

            <div class="form-group">
                <label for="dokter_id">Dokter</label>
                <select class="form-control select2" id="dokter_id" name="dokter_id" required>
                    <option value="" selected disabled>Select Dokter</option>
                    @foreach($dokters as $dokter)
                        <option value="{{ $dokter->id }}">
                            {{ $dokter->user->name }} - {{ $dokter->spesialisasi->nama }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
              <label for="tanggal_visitation">Tanggal Kunjungan</label>
              <input type="date" class="form-control" id="tanggal_visitation" name="tanggal_visitation" required>
            </div>

            <div class="form-group">
              <label for="metode_bayar_id">Cara Bayar</label>
              <select class="form-control select2" id="metode_bayar_id" name="metode_bayar_id" required>
                  <option value="" selected disabled>Pilih Metode Bayar</option>
                  @foreach($metodeBayar as $metode)
                      <option value="{{ $metode->id }}">{{ $metode->nama }}</option>
                  @endforeach
              </select>
            </div>

            <div class="form-group">
                <label for="no_antrian">No Antrian</label>
                <input type="text" name="no_antrian" id="modal-no-antrian" class="form-control" readonly>
            </div>

        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </div>
    </form>
  </div>
</div>