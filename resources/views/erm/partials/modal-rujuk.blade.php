<!-- Modal Rujuk -->
<div class="modal fade" id="modalRujuk" tabindex="-1" role="dialog" aria-labelledby="modalRujuk" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <form id="form-rujuk">
      @csrf
      <div class="modal-content">
        <div class="modal-header bg-info text-white">
          <h5 class="modal-title" id="modalLabelRujuk">Buat Rujuk / Konsultasi</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true"><i class="la la-times"></i></span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="pasien_id" id="rujuk-pasien-id">
          <input type="hidden" id="rujuk-tgllahir" />

          <!-- Top row: Nama Pasien + No RM -->
          <div class="row mb-2">
            <div class="col-md-8">
              <div class="form-group">
                <label>Nama Pasien</label>
                <input type="text" id="rujuk-nama-pasien" class="form-control" value="" readonly>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>No RM</label>
                <input type="text" id="rujuk-no-rm" class="form-control" value="" readonly>
              </div>
            </div>
          </div>
 

          <!-- Next row: Umur + Jenis Permintaan -->
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Umur</label>
                <input type="text" id="rujuk-age" class="form-control" value="" readonly>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Jenis Permintaan</label>
                <select id="jenis_permintaan" name="jenis_permintaan" class="form-control select2" required>
                  <option value="Rujuk">Rujuk</option>
                  <option value="Konsultasi">Konsultasi</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Next row: Dokter Pengirim + Dokter Tujuan -->
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Dokter Pengirim</label>
                <select id="dokter_pengirim_id" name="dokter_pengirim_id" class="form-control select2">
                  <option value="" selected disabled>Pilih Dokter Pengirim</option>
                    @foreach($dokters as $dokter)
                      <option value="{{ $dokter->id }}" @if(isset($visitation) && $visitation->dokter_id == $dokter->id) selected @endif>{{ $dokter->user->name ?? 'Dokter' }}@if($dokter->spesialisasi) ({{ $dokter->spesialisasi->nama }})@endif</option>
                    @endforeach
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Dokter Tujuan</label>
                <select id="dokter_tujuan_id" name="dokter_id" class="form-control select2" required>
                  <option value="" selected disabled>Pilih Dokter Tujuan</option>
                  @foreach($dokters as $dokter)
                    <option value="{{ $dokter->id }}">{{ $dokter->user->name ?? 'Dokter' }}@if($dokter->spesialisasi) ({{ $dokter->spesialisasi->nama }})@endif</option>
                  @endforeach
                </select>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Keterangan</label>
                <textarea name="keterangan" id="keterangan" class="form-control" rows="3"></textarea>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Penunjang (opsional)</label>
                <input type="text" name="penunjang" id="penunjang" class="form-control" placeholder="Mis: Lab, X-Ray, dll">
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Tanggal Kunjungan</label>
                <input type="date" class="form-control" id="rujuk-tanggal_visitation" name="tanggal_visitation" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>No Antrian</label>
                <input type="text" name="no_antrian" id="rujuk-no-antrian" class="form-control" readonly>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="metode_bayar_id_rujuk">Cara Bayar</label>
                <select class="form-control select2" id="metode_bayar_id_rujuk" name="metode_bayar_id" required>
                    <option value="" selected disabled>Pilih Metode Bayar</option>
                    @foreach($metodeBayar as $metode)
                        <option value="{{ $metode->id }}" @if(isset($visitation) && $visitation->metode_bayar_id == $metode->id) selected @endif>{{ $metode->nama }}</option>
                    @endforeach
                </select>
              </div>
            </div>
            <div class="col-md-6">
              {{-- empty column to keep two-input-per-row layout --}}
            </div>
          </div>

          {{-- Klinik is derived from selected dokter, so no klinik select here --}}

        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Simpan Rujuk</button>
        </div>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>window.ErmRujukConfig = {!! json_encode(['routes' => ['erm.rujuk.store' => route('erm.rujuk.store'), 'erm.visitations.cekAntrian' => route('erm.visitations.cekAntrian')]], 15) !!};</script>
<script src="{{ asset('js/erm-modal-rujuk.js') }}?v={{ filemtime(public_path('js/erm-modal-rujuk.js')) }}"></script>
@endpush
