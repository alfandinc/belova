<!-- Modal Reschedule -->
<div class="modal fade" id="modalReschedule" tabindex="-1" role="dialog" aria-labelledby="modalLabelReschedule" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form id="form-reschedule">
      @csrf
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalLabelReschedule">Jadwal Ulang Kunjungan</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true"><i class="la la-times"></i></span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="pasien_id" id="reschedule-pasien-id">
          <input type="hidden" name="visitation_id" id="reschedule-visitation-id">

          <div class="form-group">
            <label>Nama Pasien</label>
            <input type="text" id="reschedule-nama-pasien" class="form-control" readonly>
          </div>
          
          <div class="form-group">
            <label>Dokter</label>
            <select id="reschedule-dokter-id" name="dokter_id" class="form-control select2" required>
              <option value="" disabled selected>Pilih Dokter</option>
              @foreach($dokters as $dokter)
                  <option value="{{ $dokter->id }}">{{ $dokter->user->name }} - {{ $dokter->spesialisasi->nama }}</option>
              @endforeach
            </select>
          </div>


          <div class="form-group">
            <label>Tanggal Kunjungan</label>
            <input type="date" class="form-control" id="reschedule-tanggal-visitation" name="tanggal_visitation" required>
          </div>

          <div class="form-group">
            <label>No Antrian</label>
            <input type="text" name="no_antrian" id="reschedule-no-antrian" class="form-control" readonly>
          </div>

        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Simpan Jadwal Ulang</button>
        </div>
      </div>
    </form>
  </div>
</div>