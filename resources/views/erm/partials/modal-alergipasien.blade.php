{{-- Modals --}}
<div class="modal fade" id="modalAlergi" tabindex="-1" aria-labelledby="modalAlergiLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="formAlergi" method="POST" action="{{ route('erm.alergi.store', $visitation->id) }}">

        @csrf
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalLabel">Riwayat Alergi Pasien</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true"><i class="la la-times"></i></span>
          </button>
        </div>
        <div class="modal-body">

          {{-- Radio Button --}}
          <div class="form-group">
            <label>Apakah Pasien memiliki riwayat alergi?</label><br>
            <div class="form-check form-check-inline">
              <input checked class="form-check-input" type="radio" name="statusAlergi" id="alergiTidakAda" value="tidak" {{ $alergistatus == 'tidak' ? 'checked' : '' }}>
              <label class="form-check-label" for="alergiTidakAda">Tidak Ada</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="statusAlergi" id="alergiAda" value="ada" {{ $alergistatus == 'ada' ? 'checked' : '' }}>
              <label class="form-check-label" for="alergiAda">Ada</label>
            </div>
          </div>

          {{-- Input Kata Kunci --}}
          <div class="form-group" id="inputKataKunciWrapper" style="display: none;">
            <label for="inputKataKunci">Kata Kunci</label>
            <input value="{{ old('katakunci', $alergikatakunci) }}" type="text" name="katakunci" id="inputKataKunci" class="form-control" placeholder="Masukkan kata kunci...">
          </div>

          {{-- Select2 Kandungan Obat --}}
          <div class="form-group" id="selectKandunganWrapper" style="display: none;">
            <label for="zataktif_id">Pilih Zat Aktif Alergi:</label>
            <select name="zataktif_id[]" class="form-control select2" multiple>
                @foreach ($zatAktif as $zat)
                    <option value="{{ $zat->id }}"
                        @if(in_array($zat->id, $alergiIds)) selected @endif>
                        {{ $zat->nama }}
                    </option>
                @endforeach
            </select>
          </div>

        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Simpan Alergi</button>
        </div>
      </div>
    </form>
  </div>
</div>
{{-- End Modals --}}