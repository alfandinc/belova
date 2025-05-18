<!-- Modal -->
<div class="modal fade" id="modalPermintaanLab" tabindex="-1" role="dialog" aria-labelledby="modalPermintaanLabLabel" aria-hidden="true">
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
              <!-- Kolom 1 -->
              <div class="col-md-3">
                <div class="card mb-3">
                    <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
                        <strong>HEMATOLOGI</strong>
                        <button class="btn btn-sm btn-primary " type="button" data-toggle="collapse" data-target="#hematologiBody" aria-expanded="false" aria-controls="hematologiBody" title="Tampilkan/Sembunyikan">
                        ▼
                        </button>
                    </div>
                    <div class="collapse" id="hematologiBody"> <!-- Default tertutup -->
                        <div class="card-body">
                            <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="hematologi[]" value="CBC+DIFF+LED">
                            <label class="form-check-label">HEMATOLOGI LENGKAP (CBC+DIFF+LED)</label>
                            </div>
                            <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="hematologi[]" value="CBC+RETIKULOSIT">
                            <label class="form-check-label">HEMATOLOGI LENGKAP + RETIKULOSIT</label>
                            </div>
                            <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="hematologi[]" value="LAJU ENDAP DARAH">
                            <label class="form-check-label">LAJU ENDAP DARAH</label>
                            </div>
                            <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="hematologi[]" value="HAPUSAN DARAH TEPI">
                            <label class="form-check-label">HAPUSAN DARAH TEPI</label>
                            </div>
                            <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="hematologi[]" value="GOLONGAN DARAH">
                            <label class="form-check-label">GOLONGAN DARAH (ABO & Rh)</label>
                            </div>
                            <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="hematologi[]" value="PROFIL IRON">
                            <label class="form-check-label">PROFIL IRON (SI+TIBC+FERRITIN)</label>
                            </div>
                            <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="hematologi[]" value="SERUM IRON">
                            <label class="form-check-label">SERUM IRON</label>
                            </div>
                            <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="hematologi[]" value="TIBC">
                            <label class="form-check-label">TIBC</label>
                            </div>
                            <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="hematologi[]" value="TRANSFERIN">
                            <label class="form-check-label">TRANSFERIN</label>
                            </div>
                            <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="hematologi[]" value="FERRITIN">
                            <label class="form-check-label">FERRITIN</label>
                            </div>
                            <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="hematologi[]" value="Hb ELEKTROFORESIS">
                            <label class="form-check-label">Hb ELEKTROFORESIS</label>
                            </div>
                            <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="hematologi[]" value="G6PD">
                            <label class="form-check-label">G6PD</label>
                            </div>
                            <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="hematologi[]" value="COOMBS TEST">
                            <label class="form-check-label">COOMBS TEST</label>
                            </div>
                            <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="hematologi[]" value="Cd4">
                            <label class="form-check-label">Cd4</label>
                            </div>
                            </div>
                        </div>
                </div>
                <div class="card mb-3">
  <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
    <strong>HEMOSTASIS</strong>
    <button class="btn btn-sm btn-primary" type="button" data-toggle="collapse" data-target="#hemostasisBody" aria-expanded="false" aria-controls="hemostasisBody" title="Tampilkan/Sembunyikan">
      ▼
    </button>
  </div>
  <div class="collapse" id="hemostasisBody">
    <div class="card-body">
      <div class="form-check"><input class="form-check-input" type="checkbox" name="hemostasis[]" value="FAAL HEMOSTASIS"><label class="form-check-label">FAAL HEMOSTASIS</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="hemostasis[]" value="(CBC+BT+CT+PPT+APTT)"><label class="form-check-label">(CBC+BT+CT+PPT+APTT)</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="hemostasis[]" value="WAKTU PENDARAHAN (BT)"><label class="form-check-label">WAKTU PENDARAHAN (BT)</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="hemostasis[]" value="WAKTU PEMBEKUAN (CT)"><label class="form-check-label">WAKTU PEMBEKUAN (CT)</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="hemostasis[]" value="PT (INR)"><label class="form-check-label">PT (INR)</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="hemostasis[]" value="APTT"><label class="form-check-label">APTT</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="hemostasis[]" value="FIBRINOGEN"><label class="form-check-label">FIBRINOGEN</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="hemostasis[]" value="TES AGREGASI TROMBOSIT (TAT)"><label class="form-check-label">TES AGREGASI TROMBOSIT (TAT)</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="hemostasis[]" value="D-DIMER"><label class="form-check-label">D-DIMER</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="hemostasis[]" value="VISKOSITAS PLASMA"><label class="form-check-label">VISKOSITAS PLASMA</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="hemostasis[]" value="VISKOSITAS DARAH"><label class="form-check-label">VISKOSITAS DARAH</label></div>
    </div>
  </div>
</div>
<div class="card mb-3">
  <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
    <strong>URINALISA</strong>
    <button class="btn btn-sm btn-primary" type="button" data-toggle="collapse" data-target="#urinalisaBody" aria-expanded="false" aria-controls="urinalisaBody" title="Tampilkan/Sembunyikan">
      ▼
    </button>
  </div>
  <div class="collapse" id="urinalisaBody">
    <div class="card-body">
      <div class="form-check"><input class="form-check-input" type="checkbox" name="urinalisa[]" value="URINE RUTIN"><label class="form-check-label">URINE RUTIN</label></div>
    </div>
  </div>
</div>
<div class="card mb-3">
  <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
    <strong>FAESES</strong>
    <button class="btn btn-sm btn-primary" type="button" data-toggle="collapse" data-target="#faesesBody" aria-expanded="false" aria-controls="faesesBody" title="Tampilkan/Sembunyikan">
      ▼
    </button>
  </div>
  <div class="collapse" id="faesesBody">
    <div class="card-body">
      <div class="form-check"><input class="form-check-input" type="checkbox" name="faeses[]" value="FAESES RUTIN"><label class="form-check-label">FAESES RUTIN</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="faeses[]" value="DARAH SAMAR (FOBT)"><label class="form-check-label">DARAH SAMAR (FOBT)</label></div>
    </div>
  </div>
</div>
<div class="card mb-3">
  <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
    <strong>FAAL HATI</strong>
    <button class="btn btn-sm btn-primary" type="button" data-toggle="collapse" data-target="#faalHatiBody" aria-expanded="false" aria-controls="faalHatiBody" title="Tampilkan/Sembunyikan">
      ▼
    </button>
  </div>
  <div class="collapse" id="faalHatiBody">
    <div class="card-body">
      <div class="form-check"><input class="form-check-input" type="checkbox" name="faal_hati[]" value="SGOT"><label class="form-check-label">SGOT</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="faal_hati[]" value="SGPT"><label class="form-check-label">SGPT</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="faal_hati[]" value="GAMMA GT"><label class="form-check-label">GAMMA GT</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="faal_hati[]" value="FOSFATASE ALKALI (ALP)"><label class="form-check-label">FOSFATASE ALKALI (ALP)</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="faal_hati[]" value="BILIRUBIN"><label class="form-check-label">BILIRUBIN</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="faal_hati[]" value="GLOBULIN"><label class="form-check-label">GLOBULIN</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="faal_hati[]" value="TOTAL PROTEIN"><label class="form-check-label">TOTAL PROTEIN</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="faal_hati[]" value="SERUM PROTEIN ELP (SPE)"><label class="form-check-label">SERUM PROTEIN ELP (SPE)</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="faal_hati[]" value="ALBUMIN"><label class="form-check-label">ALBUMIN</label></div>
    </div>
  </div>
</div>
<div class="card mb-3">
  <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
    <strong>PENCERNAAN</strong>
    <button class="btn btn-sm btn-primary" type="button" data-toggle="collapse" data-target="#pencernaanBody" aria-expanded="false" aria-controls="pencernaanBody" title="Tampilkan/Sembunyikan">
      ▼
    </button>
  </div>
  <div class="collapse" id="pencernaanBody">
    <div class="card-body">
      <div class="form-check"><input class="form-check-input" type="checkbox" name="pencernaan[]" value="AMILASE"><label class="form-check-label">AMILASE</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="pencernaan[]" value="LIPASE"><label class="form-check-label">LIPASE</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="pencernaan[]" value="FECAL CALPROTECTIN"><label class="form-check-label">FECAL CALPROTECTIN</label></div>
    </div>
  </div>
</div>
<!-- MOLEKULER -->
<div class="card mb-3">
  <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
    <strong>MOLEKULER</strong>
    <button class="btn btn-sm btn-primary" type="button" data-toggle="collapse" data-target="#molekulerBody" aria-expanded="false" aria-controls="molekulerBody">▼</button>
  </div>
  <div class="collapse" id="molekulerBody">
    <div class="card-body">
      <div class="form-check"><input class="form-check-input" type="checkbox" name="molekuler[]" value="HBV DNA KUANTITATIF"><label class="form-check-label">HBV DNA KUANTITATIF</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="molekuler[]" value="HCV RNA KUANTITATIF"><label class="form-check-label">HCV RNA KUANTITATIF</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="molekuler[]" value="HPV DNA GENOTYPING"><label class="form-check-label">HPV DNA GENOTYPING</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="molekuler[]" value="PCR SARS CoV-2"><label class="form-check-label">PCR SARS CoV-2</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="molekuler[]" value="NIPT (NON INVASIVE PRENATAL TESTING)"><label class="form-check-label">NIPT (NON INVASIVE PRENATAL TESTING)</label></div>
      {{-- <div class="form-check"><input class="form-check-input" type="checkbox" name="molekuler[]" value="PREMIERE BELOVA SportGen"><label class="form-check-label">PREMIERE BELOVA SportGen</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="molekuler[]" value="PREMIERE BELOVA NutriGen"><label class="form-check-label">PREMIERE BELOVA NutriGen</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="molekuler[]" value="PREMIERE BELOVA WellnessGen"><label class="form-check-label">PREMIERE BELOVA WellnessGen</label></div> --}}
    </div>
  </div>
</div>
<div class="card mb-3">
  <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
    <strong>SITOLOGI & ANALISIS LAIN</strong>
    <button class="btn btn-sm btn-primary" type="button" data-toggle="collapse" data-target="#sitologiBody" aria-expanded="false" aria-controls="sitologiBody">▼</button>
  </div>
  <div class="collapse" id="sitologiBody">
    <div class="card-body">
      <div class="form-check"><input class="form-check-input" type="checkbox" name="sitologi[]" value="PAP SMEAR LIQUID BASE"><label class="form-check-label">PAP SMEAR LIQUID BASE</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="sitologi[]" value="FNAB"><label class="form-check-label">FNAB</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="sitologi[]" value="SITOLOGI"><label class="form-check-label">SITOLOGI</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="sitologi[]" value="ANALISA SPERMA**"><label class="form-check-label">ANALISA SPERMA**</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="sitologi[]" value="FRAGMENTASI DNA SPERMA**"><label class="form-check-label">FRAGMENTASI DNA SPERMA**</label></div>
    </div>
  </div>
</div>
<!-- PEMERIKSAAN RONTGEN -->
{{-- <div class="card mb-3">
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
</div> --}}





              </div>
              <!-- Kolom 2 -->
              <div class="col-md-3">
                <div class="card mb-3">
                    <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
                        <strong>FAAL GINJAL</strong>
                        <button class="btn btn-sm btn-primary" type="button" data-toggle="collapse" data-target="#faalGinjalBody" aria-expanded="false" aria-controls="faalGinjalBody" title="Tampilkan/Sembunyikan">
                        ▼
                        </button>
                    </div>
                    <div class="collapse" id="faalGinjalBody">
                        <div class="card-body">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="faal_ginjal[]" value="UREUM (BUN)">
                            <label class="form-check-label">UREUM (BUN)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="faal_ginjal[]" value="KREATININ (eGFR)">
                            <label class="form-check-label">KREATININ (eGFR)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="faal_ginjal[]" value="CYSTATIN C">
                            <label class="form-check-label">CYSTATIN C</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="faal_ginjal[]" value="MIKROALBUMIN URINE (ACR)">
                            <label class="form-check-label">MIKROALBUMIN URINE (ACR)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="faal_ginjal[]" value="ASAM URAT">
                            <label class="form-check-label">ASAM URAT*</label>
                        </div>
                        </div>
                    </div>
                </div>
                <div class="card mb-3">
  <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
    <strong>LEMAK</strong>
    <button class="btn btn-sm btn-primary" type="button" data-toggle="collapse" data-target="#lemakBody" aria-expanded="false" aria-controls="lemakBody" title="Tampilkan/Sembunyikan">
      ▼
    </button>
  </div>
  <div class="collapse" id="lemakBody">
    <div class="card-body">
      <div class="form-check"><input class="form-check-input" type="checkbox" name="lemak[]" value="PROFIL LEMAK*"><label class="form-check-label">PROFIL LEMAK*</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="lemak[]" value="KOLESTEROL TOTAL"><label class="form-check-label">KOLESTEROL TOTAL</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="lemak[]" value="TRIGLISERIDA*"><label class="form-check-label">TRIGLISERIDA*</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="lemak[]" value="HDL KOLESTEROL"><label class="form-check-label">HDL KOLESTEROL</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="lemak[]" value="LDL KOLESTEROL"><label class="form-check-label">LDL KOLESTEROL</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="lemak[]" value="SMALLDENSE LDL*"><label class="form-check-label">SMALLDENSE LDL*</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="lemak[]" value="Lp(A)*"><label class="form-check-label">Lp(A)*</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="lemak[]" value="APO AI*"><label class="form-check-label">APO AI*</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="lemak[]" value="APO B*"><label class="form-check-label">APO B*</label></div>
    </div>
  </div>
</div>
<div class="card mb-3">
  <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
    <strong>DIABETES MELLITUS</strong>
    <button class="btn btn-sm btn-primary" type="button" data-toggle="collapse" data-target="#dmBody" aria-expanded="false" aria-controls="dmBody" title="Tampilkan/Sembunyikan">
      ▼
    </button>
  </div>
  <div class="collapse" id="dmBody">
    <div class="card-body">
      <div class="form-check"><input class="form-check-input" type="checkbox" name="dm[]" value="GLUKOSA PUASA*"><label class="form-check-label">GLUKOSA PUASA*</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="dm[]" value="GLUKOSA 2 JAM PP"><label class="form-check-label">GLUKOSA 2 JAM PP</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="dm[]" value="GLUKOSA SEWAKTU"><label class="form-check-label">GLUKOSA SEWAKTU</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="dm[]" value="TTGO*"><label class="form-check-label">TTGO*</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="dm[]" value="HbA1c"><label class="form-check-label">HbA1c</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="dm[]" value="INSULIN*"><label class="form-check-label">INSULIN*</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="dm[]" value="C-PEPTIDE*"><label class="form-check-label">C-PEPTIDE*</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="dm[]" value="HOMA IR*"><label class="form-check-label">HOMA IR*</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="dm[]" value="HOMA B*"><label class="form-check-label">HOMA B*</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="dm[]" value="GLYCATED ALBUMIN"><label class="form-check-label">GLYCATED ALBUMIN</label></div>
    </div>
  </div>
</div>
<div class="card mb-3">
  <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
    <strong>ELEKTROLIT</strong>
    <button class="btn btn-sm btn-primary" type="button" data-toggle="collapse" data-target="#elektrolitBody" aria-expanded="false" aria-controls="elektrolitBody" title="Tampilkan/Sembunyikan">
      ▼
    </button>
  </div>
  <div class="collapse" id="elektrolitBody">
    <div class="card-body">
      <div class="form-check"><input class="form-check-input" type="checkbox" name="elektrolit[]" value="NATRIUM"><label class="form-check-label">NATRIUM</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="elektrolit[]" value="KALIUM"><label class="form-check-label">KALIUM</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="elektrolit[]" value="KLORIDA"><label class="form-check-label">KLORIDA</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="elektrolit[]" value="KALSIUM TOTAL"><label class="form-check-label">KALSIUM TOTAL</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="elektrolit[]" value="KALSIUM ION"><label class="form-check-label">KALSIUM ION</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="elektrolit[]" value="FOSFOR"><label class="form-check-label">FOSFOR</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="elektrolit[]" value="MAGNESIUM"><label class="form-check-label">MAGNESIUM</label></div>
    </div>
  </div>
</div>
<div class="card mb-3">
  <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
    <strong>HEPATITIS</strong>
    <button class="btn btn-sm btn-primary" type="button" data-toggle="collapse" data-target="#hepatitisBody" aria-expanded="false" aria-controls="hepatitisBody" title="Tampilkan/Sembunyikan">
      ▼
    </button>
  </div>
  <div class="collapse" id="hepatitisBody">
    <div class="card-body">
      <div class="form-check"><input class="form-check-input" type="checkbox" name="hepatitis[]" value="HBsAg"><label class="form-check-label">HBsAg</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="hepatitis[]" value="HBsAg KUANTITATIF"><label class="form-check-label">HBsAg KUANTITATIF</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="hepatitis[]" value="ANTI HBs"><label class="form-check-label">ANTI HBs</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="hepatitis[]" value="ANTI HBs TITER"><label class="form-check-label">ANTI HBs TITER</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="hepatitis[]" value="ANTI Hbc"><label class="form-check-label">ANTI Hbc</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="hepatitis[]" value="IgM ANTI Hbc"><label class="form-check-label">IgM ANTI Hbc</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="hepatitis[]" value="HBeAg"><label class="form-check-label">HBeAg</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="hepatitis[]" value="ANTI Hbe"><label class="form-check-label">ANTI Hbe</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="hepatitis[]" value="ANTI HAV TOTAL"><label class="form-check-label">ANTI HAV TOTAL</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="hepatitis[]" value="IgM ANTI HAV"><label class="form-check-label">IgM ANTI HAV</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="hepatitis[]" value="ANTI HCV"><label class="form-check-label">ANTI HCV</label></div>
    </div>
  </div>
</div>
<div class="card mb-3">
  <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
    <strong>BONE MAKER</strong>
    <button class="btn btn-sm btn-primary" type="button" data-toggle="collapse" data-target="#boneBody" aria-expanded="false" aria-controls="boneBody" title="Tampilkan/Sembunyikan">
      ▼
    </button>
  </div>
  <div class="collapse" id="boneBody">
    <div class="card-body">
      <div class="form-check"><input class="form-check-input" type="checkbox" name="bone[]" value="BETA CROSSLAPS (BETA CTX)"><label class="form-check-label">BETA CROSSLAPS (BETA CTX)</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="bone[]" value="N-MID OSTEOCALCIN"><label class="form-check-label">N-MID OSTEOCALCIN</label></div>
    </div>
  </div>
</div>
<div class="card mb-3">
  <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
    <strong>PREPARAT</strong>
    <button class="btn btn-sm btn-primary" type="button" data-toggle="collapse" data-target="#preparatBody" aria-expanded="false" aria-controls="preparatBody">▼</button>
  </div>
  <div class="collapse" id="preparatBody">
    <div class="card-body">
      <div class="form-check"><input class="form-check-input" type="checkbox" name="preparat[]" value="SAMPEL : ............"><label class="form-check-label">SAMPEL : ............</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="preparat[]" value="GRAM"><label class="form-check-label">GRAM</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="preparat[]" value="JAMUR / SPORA"><label class="form-check-label">JAMUR / SPORA</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="preparat[]" value="TRICHOMONAS"><label class="form-check-label">TRICHOMONAS</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="preparat[]" value="GONORRHEA"><label class="form-check-label">GONORRHEA</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="preparat[]" value="DIPTHERIA"><label class="form-check-label">DIPTHERIA</label></div>
    </div>
  </div>
</div>
<div class="card mb-3">
  <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
    <strong>KULTUR & SENSITIVITAS</strong>
    <button class="btn btn-sm btn-primary" type="button" data-toggle="collapse" data-target="#kulturBody" aria-expanded="false" aria-controls="kulturBody">▼</button>
  </div>
  <div class="collapse" id="kulturBody">
    <div class="card-body">
      <div class="form-check"><input class="form-check-input" type="checkbox" name="kultur[]" value="KULTUR URINE"><label class="form-check-label">KULTUR URINE</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="kultur[]" value="KULTUR : ............"><label class="form-check-label">KULTUR : ............</label></div>
    </div>
  </div>
</div>
{{-- <div class="card mb-3">
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
</div> --}}



              </div>
              <!-- Kolom 3 -->
              <div class="col-md-3">
                <div class="card mb-3">
                <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
                    <strong>JANTUNG</strong>
                    <button class="btn btn-sm btn-primary" type="button" data-toggle="collapse" data-target="#jantungBody" aria-expanded="false" aria-controls="jantungBody" title="Tampilkan/Sembunyikan">
                    ▼
                    </button>
                </div>
                <div class="collapse" id="jantungBody">
                    <div class="card-body">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="jantung[]" value="CK">
                        <label class="form-check-label">CK</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="jantung[]" value="CKMB MASS">
                        <label class="form-check-label">CKMB MASS</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="jantung[]" value="hs-CRP (ss-CRP)">
                        <label class="form-check-label">hs-CRP (ss-CRP)</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="jantung[]" value="hs-TROPONIN I">
                        <label class="form-check-label">hs-TROPONIN I</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="jantung[]" value="HOMOCYSTEINE">
                        <label class="form-check-label">HOMOCYSTEINE</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="jantung[]" value="NT-proBNP">
                        <label class="form-check-label">NT-proBNP</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="jantung[]" value="LDH">
                        <label class="form-check-label">LDH</label>
                    </div>
                    </div>
                </div>
                </div>
                <div class="card mb-3">
  <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
    <strong>FUNGSI TIROID</strong>
    <button class="btn btn-sm btn-primary" type="button" data-toggle="collapse" data-target="#tiroidBody" aria-expanded="false" aria-controls="tiroidBody" title="Tampilkan/Sembunyikan">
      ▼
    </button>
  </div>
  <div class="collapse" id="tiroidBody">
    <div class="card-body">
      <div class="form-check"><input class="form-check-input" type="checkbox" name="tiroid[]" value="T3"><label class="form-check-label">T3</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="tiroid[]" value="T4"><label class="form-check-label">T4</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="tiroid[]" value="TSH-S"><label class="form-check-label">TSH-S</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="tiroid[]" value="FREE T3"><label class="form-check-label">FREE T3</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="tiroid[]" value="FREE T4"><label class="form-check-label">FREE T4</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="tiroid[]" value="TIROGLOBULIN"><label class="form-check-label">TIROGLOBULIN</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="tiroid[]" value="ANTI TIROGLOBULIN"><label class="form-check-label">ANTI TIROGLOBULIN</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="tiroid[]" value="TRAb (TSH Receptor Anti)"><label class="form-check-label">TRAb (TSH Receptor Anti)</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="tiroid[]" value="ANTI TPO"><label class="form-check-label">ANTI TPO</label></div>
    </div>
  </div>
</div>
<div class="card mb-3">
  <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
    <strong>TORCH</strong>
    <button class="btn btn-sm btn-primary" type="button" data-toggle="collapse" data-target="#torchBody" aria-expanded="false" aria-controls="torchBody" title="Tampilkan/Sembunyikan">
      ▼
    </button>
  </div>
  <div class="collapse" id="torchBody">
    <div class="card-body">
      <div class="form-check"><input class="form-check-input" type="checkbox" name="torch[]" value="IgM ANTI TOXOPLASMA"><label class="form-check-label">IgM ANTI TOXOPLASMA</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="torch[]" value="IgG ANTI TOXOPLASMA"><label class="form-check-label">IgG ANTI TOXOPLASMA</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="torch[]" value="IgG ANTI TOXOPLASMA AVIDITY"><label class="form-check-label">IgG ANTI TOXOPLASMA AVIDITY</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="torch[]" value="IgM ANTI RUBELLA"><label class="form-check-label">IgM ANTI RUBELLA</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="torch[]" value="IgG ANTI RUBELLA"><label class="form-check-label">IgG ANTI RUBELLA</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="torch[]" value="IgM ANTI CMV"><label class="form-check-label">IgM ANTI CMV</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="torch[]" value="IgG ANTI CMV"><label class="form-check-label">IgG ANTI CMV</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="torch[]" value="IgG ANTI CMV AVIDITY"><label class="form-check-label">IgG ANTI CMV AVIDITY</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="torch[]" value="IgM ANTI HSV 1/2"><label class="form-check-label">IgM ANTI HSV 1/2</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="torch[]" value="IgG ANTI HSV 1"><label class="form-check-label">IgG ANTI HSV 1</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="torch[]" value="IgG ANTI HSV 2"><label class="form-check-label">IgG ANTI HSV 2</label></div>
    </div>
  </div>
</div>
<div class="card mb-3">
  <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
    <strong>ALERGI</strong>
    <button class="btn btn-sm btn-primary" type="button" data-toggle="collapse" data-target="#alergiBody" aria-expanded="false" aria-controls="alergiBody" title="Tampilkan/Sembunyikan">
      ▼
    </button>
  </div>
  <div class="collapse" id="alergiBody">
    <div class="card-body">
      <div class="form-check"><input class="form-check-input" type="checkbox" name="alergi[]" value="EOSINOFIL ABSOLUT"><label class="form-check-label">EOSINOFIL ABSOLUT</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="alergi[]" value="IgE TOTAL"><label class="form-check-label">IgE TOTAL</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="alergi[]" value="IgE ATOPY 55 TEST"><label class="form-check-label">IgE ATOPY 55 TEST</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="alergi[]" value="IgE ATOPY 96 TEST"><label class="form-check-label">IgE ATOPY 96 TEST</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="alergi[]" value="IgE MAKANAN ASIA"><label class="form-check-label">IgE MAKANAN ASIA</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="alergi[]" value="IgG FOOD SENSITIVITY PROFILE"><label class="form-check-label">IgG FOOD SENSITIVITY PROFILE</label></div>
    </div>
  </div>
</div>
<div class="card mb-3">
  <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
    <strong>AUTOIMUN</strong>
    <button class="btn btn-sm btn-primary" type="button" data-toggle="collapse" data-target="#autoimunBody" aria-expanded="false" aria-controls="autoimunBody" title="Tampilkan/Sembunyikan">
      ▼
    </button>
  </div>
  <div class="collapse" id="autoimunBody">
    <div class="card-body">
      <div class="form-check"><input class="form-check-input" type="checkbox" name="autoimun[]" value="RHEUMATOID FACTOR"><label class="form-check-label">RHEUMATOID FACTOR</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="autoimun[]" value="ASTO"><label class="form-check-label">ASTO</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="autoimun[]" value="ANA IF"><label class="form-check-label">ANA IF</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="autoimun[]" value="ANTI dsDNA"><label class="form-check-label">ANTI dsDNA</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="autoimun[]" value="IgM ANTI ACA"><label class="form-check-label">IgM ANTI ACA</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="autoimun[]" value="IgG ANTI ACA"><label class="form-check-label">IgG ANTI ACA</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="autoimun[]" value="C3 KOMPLEMEN"><label class="form-check-label">C3 KOMPLEMEN</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="autoimun[]" value="C4 KOMPLEMEN"><label class="form-check-label">C4 KOMPLEMEN</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="autoimun[]" value="SEL LE"><label class="form-check-label">SEL LE</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="autoimun[]" value="CRP KUANTITATIF"><label class="form-check-label">CRP KUANTITATIF</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="autoimun[]" value="ANA PROFILE"><label class="form-check-label">ANA PROFILE</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="autoimun[]" value="IgG ANTI CCP"><label class="form-check-label">IgG ANTI CCP</label></div>
    </div>
  </div>
</div>
<div class="card mb-3">
  <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
    <strong>SCREENING NARKOBA</strong>
    <button class="btn btn-sm btn-primary" type="button" data-toggle="collapse" data-target="#narkobaBody" aria-expanded="false" aria-controls="narkobaBody">▼</button>
  </div>
  <div class="collapse" id="narkobaBody">
    <div class="card-body">
      <div class="form-check"><input class="form-check-input" type="checkbox" name="narkoba[]" value="ALCOHOL SALIVA"><label class="form-check-label">ALCOHOL SALIVA</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="narkoba[]" value="AMPHETAMIN"><label class="form-check-label">AMPHETAMIN</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="narkoba[]" value="METHAMPHETAMIN"><label class="form-check-label">METHAMPHETAMIN</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="narkoba[]" value="COCCAIN"><label class="form-check-label">COCCAIN</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="narkoba[]" value="CANABINOID / THC"><label class="form-check-label">CANABINOID / THC</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="narkoba[]" value="MORFIN / OPIEATE"><label class="form-check-label">MORFIN / OPIEATE</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="narkoba[]" value="BENZODIAZEPIN"><label class="form-check-label">BENZODIAZEPIN</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="narkoba[]" value="BARBITURAT"><label class="form-check-label">BARBITURAT</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="narkoba[]" value="CARISOPRODOL"><label class="form-check-label">CARISOPRODOL</label></div>
    </div>
  </div>
</div>
<div class="card mb-3">
  <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
    <strong>VITAMIN & MIKRO NUTRISI</strong>
    <button class="btn btn-sm btn-primary" type="button" data-toggle="collapse" data-target="#vitaminBody" aria-expanded="false" aria-controls="vitaminBody">▼</button>
  </div>
  <div class="collapse" id="vitaminBody">
    <div class="card-body">
      <div class="form-check"><input class="form-check-input" type="checkbox" name="vitamin[]" value="ASAM FOLAT"><label class="form-check-label">ASAM FOLAT</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="vitamin[]" value="VITAMIN B12"><label class="form-check-label">VITAMIN B12</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="vitamin[]" value="VITAMIN D 25-OH"><label class="form-check-label">VITAMIN D 25-OH</label></div>
    </div>
  </div>
</div>
<div class="card mb-3">
  <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
    <strong>IMMUNE BOOSTER</strong>
    <button class="btn btn-sm btn-primary" type="button" data-toggle="collapse" data-target="#boosterBody" aria-expanded="false" aria-controls="boosterBody">▼</button>
  </div>
  <div class="collapse" id="boosterBody">
    <div class="card-body">
      <div class="form-check"><input class="form-check-input" type="checkbox" name="booster[]" value="VITAMIN B"><label class="form-check-label">VITAMIN B</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="booster[]" value="VITAMIN C"><label class="form-check-label">VITAMIN C</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="booster[]" value="MULTIVITAMIN"><label class="form-check-label">MULTIVITAMIN</label></div>
    </div>
  </div>
</div>
{{-- <div class="card mb-3">
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
</div> --}}
              </div>
              <!-- Kolom 4 -->
              <div class="col-md-3">
                <div class="card mb-3">
  <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
    <strong>IMUNOLOGI LAIN</strong>
    <button class="btn btn-sm btn-primary" type="button" data-toggle="collapse" data-target="#imunologiLainBody" aria-expanded="false" aria-controls="imunologiLainBody" title="Tampilkan/Sembunyikan">
      ▼
    </button>
  </div>
  <div class="collapse" id="imunologiLainBody">
    <div class="card-body">
      <div class="form-check"><input class="form-check-input" type="checkbox" name="imunologi[]" value="INTERLEUKIN-6"><label class="form-check-label">INTERLEUKIN-6</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="imunologi[]" value="BETA 2 MICROGLOBULIN"><label class="form-check-label">BETA 2 MICROGLOBULIN</label></div>
    </div>
  </div>
</div>
<div class="card mb-3">
  <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
    <strong>TUMOR MARKER</strong>
    <button class="btn btn-sm btn-primary" type="button" data-toggle="collapse" data-target="#tumorMarkerBody" aria-expanded="false" aria-controls="tumorMarkerBody" title="Tampilkan/Sembunyikan">
      ▼
    </button>
  </div>
  <div class="collapse" id="tumorMarkerBody">
    <div class="card-body">
      <div class="form-check"><input class="form-check-input" type="checkbox" name="tumor[]" value="AFP"><label class="form-check-label">AFP</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="tumor[]" value="CEA"><label class="form-check-label">CEA</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="tumor[]" value="PSA TOTAL"><label class="form-check-label">PSA TOTAL</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="tumor[]" value="FREE PSA"><label class="form-check-label">FREE PSA</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="tumor[]" value="NSE"><label class="form-check-label">NSE</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="tumor[]" value="SCC"><label class="form-check-label">SCC</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="tumor[]" value="CA 15-3"><label class="form-check-label">CA 15-3</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="tumor[]" value="CA 19-9"><label class="form-check-label">CA 19-9</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="tumor[]" value="CA 125"><label class="form-check-label">CA 125</label></div>
    </div>
  </div>
</div>
<div class="card mb-3">
  <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
    <strong>REPRODUKSI</strong>
    <button class="btn btn-sm btn-primary" type="button" data-toggle="collapse" data-target="#reproduksiBody" aria-expanded="false" aria-controls="reproduksiBody" title="Tampilkan/Sembunyikan">
      ▼
    </button>
  </div>
  <div class="collapse" id="reproduksiBody">
    <div class="card-body">
      <div class="form-check"><input class="form-check-input" type="checkbox" name="reproduksi[]" value="LH"><label class="form-check-label">LH</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="reproduksi[]" value="FSH"><label class="form-check-label">FSH</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="reproduksi[]" value="PROLAKTIN"><label class="form-check-label">PROLAKTIN</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="reproduksi[]" value="TESTOSTERON"><label class="form-check-label">TESTOSTERON</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="reproduksi[]" value="ESTRADIOL"><label class="form-check-label">ESTRADIOL</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="reproduksi[]" value="PROGESTERON"><label class="form-check-label">PROGESTERON</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="reproduksi[]" value="ANTI MULLERIAN HORMON (AMH)"><label class="form-check-label">ANTI MULLERIAN HORMON (AMH)</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="reproduksi[]" value="HCG URINE (TEST KEHAMILAN)"><label class="form-check-label">HCG URINE (TEST KEHAMILAN)</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="reproduksi[]" value="BETA HCG KUANTITATIF"><label class="form-check-label">BETA HCG KUANTITATIF</label></div>
    </div>
  </div>
</div>
<div class="card mb-3">
  <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
    <strong>HORMON LAIN</strong>
    <button class="btn btn-sm btn-primary" type="button" data-toggle="collapse" data-target="#hormonLainBody" aria-expanded="false" aria-controls="hormonLainBody" title="Tampilkan/Sembunyikan">▼</button>
  </div>
  <div class="collapse" id="hormonLainBody">
    <div class="card-body">
      <div class="form-check"><input class="form-check-input" type="checkbox" name="hormon_lain[]" value="CORTISOL"><label class="form-check-label">CORTISOL</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="hormon_lain[]" value="GROWTH HORMON BASAL"><label class="form-check-label">GROWTH HORMON BASAL</label></div>
    </div>
  </div>
</div>
<div class="card mb-3">
  <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
    <strong>INFEKSI LAIN</strong>
    <button class="btn btn-sm btn-primary" type="button" data-toggle="collapse" data-target="#infeksiLainBody" aria-expanded="false" aria-controls="infeksiLainBody" title="Tampilkan/Sembunyikan">▼</button>
  </div>
  <div class="collapse" id="infeksiLainBody">
    <div class="card-body">
      <div class="form-check"><input class="form-check-input" type="checkbox" name="infeksi_lain[]" value="Antigen SARS Cov-2"><label class="form-check-label">Antigen SARS Cov-2</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="infeksi_lain[]" value="PROCALCITONIN"><label class="form-check-label">PROCALCITONIN</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="infeksi_lain[]" value="IgM ANTI CHLAMYDIA"><label class="form-check-label">IgM ANTI CHLAMYDIA</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="infeksi_lain[]" value="IgG ANTI CHLAMYDIA"><label class="form-check-label">IgG ANTI CHLAMYDIA</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="infeksi_lain[]" value="TPHA"><label class="form-check-label">TPHA</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="infeksi_lain[]" value="VDRL"><label class="form-check-label">VDRL</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="infeksi_lain[]" value="ANTI HIV (Ag/Ab)"><label class="form-check-label">ANTI HIV (Ag/Ab)</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="infeksi_lain[]" value="HIV 3 Metode"><label class="form-check-label">HIV 3 Metode</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="infeksi_lain[]" value="PAKET MALARIA"><label class="form-check-label">PAKET MALARIA</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="infeksi_lain[]" value="WIDAL"><label class="form-check-label">WIDAL</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="infeksi_lain[]" value="IgM ANTI SALMONELLA TYPHI"><label class="form-check-label">IgM ANTI SALMONELLA TYPHI</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="infeksi_lain[]" value="TRIPLE DENGUE"><label class="form-check-label">TRIPLE DENGUE</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="infeksi_lain[]" value="DENGUE Ns1 Ag"><label class="form-check-label">DENGUE Ns1 Ag</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="infeksi_lain[]" value="IgG & IgM ANTI DENGUE"><label class="form-check-label">IgG & IgM ANTI DENGUE</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="infeksi_lain[]" value="IgM ANTI H.PYLORI"><label class="form-check-label">IgM ANTI H.PYLORI</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="infeksi_lain[]" value="IgG ANTI H.PYLORI"><label class="form-check-label">IgG ANTI H.PYLORI</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="infeksi_lain[]" value="IgM ANTI CHIKUNGUNYA"><label class="form-check-label">IgM ANTI CHIKUNGUNYA</label></div>
    </div>
  </div>
</div>
<div class="card mb-3">
  <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
    <strong>DETEKSI MTB</strong>
    <button class="btn btn-sm btn-primary" type="button" data-toggle="collapse" data-target="#deteksiMtbBody" aria-expanded="false" aria-controls="deteksiMtbBody" title="Tampilkan/Sembunyikan">▼</button>
  </div>
  <div class="collapse" id="deteksiMtbBody">
    <div class="card-body">
      <div class="form-check"><input class="form-check-input" type="checkbox" name="deteksi_mtb[]" value="PREPARAT BTA"><label class="form-check-label">PREPARAT BTA</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="deteksi_mtb[]" value="KULTUR BTA"><label class="form-check-label">KULTUR BTA</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="deteksi_mtb[]" value="IGRA"><label class="form-check-label">IGRA</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="deteksi_mtb[]" value="ADENOSIN DEAMINASE (ADA)"><label class="form-check-label">ADENOSIN DEAMINASE (ADA)</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="deteksi_mtb[]" value="MANTOUX TEST"><label class="form-check-label">MANTOUX TEST</label></div>
    </div>
  </div>
</div>
<div class="card mb-3">
  <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
    <strong>ELEKTROMEDIS</strong>
    <button class="btn btn-sm btn-primary" type="button" data-toggle="collapse" data-target="#elektromedisBody" aria-expanded="false" aria-controls="elektromedisBody" title="Tampilkan/Sembunyikan">▼</button>
  </div>
  <div class="collapse" id="elektromedisBody">
    <div class="card-body">
      <strong>JANTUNG</strong>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="elektromedis[]" value="ECG"><label class="form-check-label">ECG</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="elektromedis[]" value="TREADMILL"><label class="form-check-label">TREADMILL</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="elektromedis[]" value="ECHOCARDIOGRAPHY"><label class="form-check-label">ECHOCARDIOGRAPHY</label></div>

      <strong class="mt-2 d-block">SARAF</strong>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="elektromedis[]" value="EEG**"><label class="form-check-label">EEG**</label></div>

      <strong class="mt-2 d-block">PARU</strong>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="elektromedis[]" value="AUTOSPIROMETRY"><label class="form-check-label">AUTOSPIROMETRY</label></div>

      <strong class="mt-2 d-block">PENDENGARAN</strong>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="elektromedis[]" value="AUDIOMETRY"><label class="form-check-label">AUDIOMETRY</label></div>
    </div>
  </div>
</div>
{{-- <div class="card mb-3">
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
</div> --}}





              </div>
            </div> <!-- end row -->
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