<!-- filepath: c:\wamp64\www\belova\resources\views\erm\partials\modal-alergipasien.blade.php -->
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
          <div id="alergiAlertContainer"></div>

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
            {{-- Only the patient's own zat aktif are rendered; the rest is searched (no .select2 class: a page's global init must not take it) --}}
            <select name="zataktif_id[]" id="alergiZatAktif" class="form-control" multiple data-search-url="{{ route('erm.alergi.zataktif.search') }}">
                @foreach ($zatAktif as $zat)
                    <option value="{{ $zat->id }}" selected>{{ $zat->nama }}</option>
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
@push('scripts')
<script src="{{ asset('js/erm-modal-alergi.js') }}?v={{ filemtime(public_path('js/erm-modal-alergi.js')) }}"></script>
@endpush