<!-- Modal -->
<div class="modal fade" id="modalPermintaanRadiologi" tabindex="-1" role="dialog" aria-labelledby="modalPermintaanRadiologiLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document"> <!-- Extra large modal -->
    <div class="modal-content">
      <form action="" method="POST">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="modalLabel">Form Permintaan Lab</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true"><i class="la la-times"></i></span>
          </button>
        </div>

        <div class="modal-body">
          <div class="container-fluid">
            <div class="row">
              <div class="col md-3">
                <!-- PEMERIKSAAN RONTGEN -->
<div class="card mb-3">
  <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
    <strong>PEMERIKSAAN RONTGEN</strong>
    <button class="btn btn-sm btn-primary" type="button" data-toggle="collapse" data-target="#rontgenBody" aria-expanded="false" aria-controls="rontgenBody">▼</button>
  </div>
  <div class="collapse" id="rontgenBody">
    <div class="card-body">
      <strong>KEPALA/SKULL</strong>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="rontgen[]" value="WATER'S"><label class="form-check-label">WATER'S</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="rontgen[]" value="SKULL AP"><label class="form-check-label">SKULL AP</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="rontgen[]" value="SKULL LAT"><label class="form-check-label">SKULL LAT</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="rontgen[]" value="SINUS PARANASALIS"><label class="form-check-label">SINUS PARANASALIS</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="rontgen[]" value="SKULL LAT ADENOID"><label class="form-check-label">SKULL LAT ADENOID</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="rontgen[]" value="CALD WELL"><label class="form-check-label">CALD WELL</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="rontgen[]" value="MASTOID SCHULLER DEX"><label class="form-check-label">MASTOID SCHULLER DEX</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="rontgen[]" value="MASTOID SCHULLER SIN"><label class="form-check-label">MASTOID SCHULLER SIN</label></div>

      <strong class="mt-3">SPINE</strong>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="rontgen[]" value="CERVICAL AP"><label class="form-check-label">CERVICAL AP</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="rontgen[]" value="CERVICAL LAT"><label class="form-check-label">CERVICAL LAT</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="rontgen[]" value="CERVICAL OBL DEX"><label class="form-check-label">CERVICAL OBL DEX</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="rontgen[]" value="CERVICAL OBL SIN"><label class="form-check-label">CERVICAL OBL SIN</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="rontgen[]" value="THORACALIS AP"><label class="form-check-label">THORACALIS AP</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="rontgen[]" value="THORACALIS LAT"><label class="form-check-label">THORACALIS LAT</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="rontgen[]" value="LUMBO-SACRALIS AP"><label class="form-check-label">LUMBO-SACRALIS AP</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="rontgen[]" value="LUMBO-SACRALIS LAT"><label class="form-check-label">LUMBO-SACRALIS LAT</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="rontgen[]" value="THORACO-LUMBALIS AP"><label class="form-check-label">THORACO-LUMBALIS AP</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="rontgen[]" value="THORACO-LUMBALIS LAT"><label class="form-check-label">THORACO-LUMBALIS LAT</label></div>

      <strong class="mt-3">SPINE LAIN</strong>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="rontgen[]" value="ANTEBR DEX AP/LAT"><label class="form-check-label">ANTEBR DEX AP/LAT</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="rontgen[]" value="ANTEBR SIN AP/LAT"><label class="form-check-label">ANTEBR SIN AP/LAT</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="rontgen[]" value="MANUS DEX"><label class="form-check-label">MANUS DEX</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="rontgen[]" value="MANUS SIN"><label class="form-check-label">MANUS SIN</label></div>

      <strong class="mt-3">EKSTRIMITAS ATAS</strong>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="rontgen[]" value="GENU DEX AP/LAT"><label class="form-check-label">GENU DEX AP/LAT</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="rontgen[]" value="GENU SIN AP/LAT"><label class="form-check-label">GENU SIN AP/LAT</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="rontgen[]" value="THORAX PA"><label class="form-check-label">THORAX PA</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="rontgen[]" value="BOF/KUB"><label class="form-check-label">BOF/KUB**</label></div>

      <strong class="mt-3">KONTRAS</strong>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="rontgen[]" value="IVP"><label class="form-check-label">IVP**</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="rontgen[]" value="H.S.G"><label class="form-check-label">H.S.G**</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="rontgen[]" value="OSEPHAGOGRAM"><label class="form-check-label">OSEPHAGOGRAM**</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="rontgen[]" value="CHOLANGIOGRAPHY"><label class="form-check-label">CHOLANGIOGRAPHY**</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="rontgen[]" value="APPENDICOGRAM"><label class="form-check-label">APPENDICOGRAM**</label></div>
    </div>
  </div>
</div>
              </div>
              <div class="col md-3">
                <div class="card mb-3">
  <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
    <strong>ULTRASONOGRAPHY</strong>
    <button class="btn btn-sm btn-primary" type="button" data-toggle="collapse" data-target="#usgBody" aria-expanded="false" aria-controls="usgBody">▼</button>
  </div>
  <div class="collapse" id="usgBody">
    <div class="card-body">
      <div class="form-check"><input class="form-check-input" type="checkbox" name="usg[]" value="USG UPPER ABD"><label class="form-check-label">USG UPPER ABD**</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="usg[]" value="USG LOWER ABD"><label class="form-check-label">USG LOWER ABD**</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="usg[]" value="USG UP+LOW ABD"><label class="form-check-label">USG UP+LOW ABD**</label></div>
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="usg[]" value="USG CUSTOM">
        <label class="form-check-label">USG..................................**</label>
      </div>
    </div>
  </div>
</div>
              </div>
              <div class="col md-3">
                <div class="card mb-3">
  <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
    <strong>BMD*</strong>
    <button class="btn btn-sm btn-primary" type="button" data-toggle="collapse" data-target="#bmdBody" aria-expanded="false" aria-controls="bmdBody">▼</button>
  </div>
  <div class="collapse" id="bmdBody">
    <div class="card-body">
      <div class="form-check"><input class="form-check-input" type="checkbox" name="bmd[]" value="AP LIMBAL"><label class="form-check-label">AP LIMBAL</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="bmd[]" value="COXAE HIP DEX/SIN"><label class="form-check-label">COXAE HIP DEX/SIN</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="bmd[]" value="FORE ARM DEX/SIN"><label class="form-check-label">FORE ARM DEX/SIN</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="bmd[]" value="LUMBAL/COXAE DEX/SIN"><label class="form-check-label">LUMBAL/COXAE DEX/SIN</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="bmd[]" value="WHOLE BODY"><label class="form-check-label">WHOLE BODY</label></div>
    </div>
  </div>
</div>

              </div>
              <div class="col md-3">
                <div class="card mb-3">
  <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
    <strong>MAMMOGRAPHY*</strong>
    <button class="btn btn-sm btn-primary" type="button" data-toggle="collapse" data-target="#mammographyBody" aria-expanded="false" aria-controls="mammographyBody">▼</button>
  </div>
  <div class="collapse" id="mammographyBody">
    <div class="card-body">
      <div class="form-check"><input class="form-check-input" type="checkbox" name="mammography[]" value="MAMMOGRAPHY DEX/SIN"><label class="form-check-label">MAMMOGRAPHY DEX/SIN</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="mammography[]" value="MAMMOGRAPHY DEX"><label class="form-check-label">MAMMOGRAPHY DEX</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="mammography[]" value="MAMMOGRAPHY SIN"><label class="form-check-label">MAMMOGRAPHY SIN</label></div>
    </div>
  </div>
</div>

              </div>

            </div>
          </div> <!-- end container-fluid -->
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
          <button type="submit" class="btn btn-primary">Simpan Permintaan</button>
        </div>
      </form>
    </div>
  </div>
</div>