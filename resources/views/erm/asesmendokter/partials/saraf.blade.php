<div class="row">
    <div class="col-md-12">
        <div class="form-group">
            <label for="keluhan_utama">KELUHAN UTAMA</label>
            <input type="text" class="form-control focus:outline-white focus:border-white" id="keluhan_utama" name="keluhan_utama" value="{{ old('keluhan_utama', $asesmen->keluhan_utama ?? $dataperawat->keluhan_utama ?? '') }}">
        </div>
    </div>
       
</div> 

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="riwayat_penyakit_dahulu">Riwayat Penyakit Dahulu</label>
            <input type="text" class="form-control" id="riwayat_penyakit_dahulu" name="riwayat_penyakit_dahulu" value="{{ old('riwayat_penyakit_dahulu', $asesmen->riwayat_penyakit_dahulu ?? '') }}">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="riwayat_penyakit_sekarang">Riwayat Penyakit Sekarang</label>
            <input type="text" class="form-control" id="riwayat_penyakit_sekarang" name="riwayat_penyakit_sekarang" value="{{ old('riwayat_penyakit_sekarang', $asesmen->riwayat_penyakit_sekarang ?? '') }}">
        </div>
    </div> 
       
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="keadaan_umum">Keadaan Umum</label>
            <input type="text" class="form-control" id="keadaan_umum" name="keadaan_umum" value="{{ old('keadaan_umum', $asesmen->keadaan_umum ?? 'Baik') }}">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="obat_dikonsumsi">Obat yang Dikonsumsi</label>
            <input type="text" class="form-control" id="obat_dikonsumsi" name="obat_dikonsumsi" value="{{ old('obat_dikonsumsi', $asesmen->obat_dikonsumsi ?? '') }}">
        </div>
    </div> 
</div>

<!-- GCS Assessment -->
<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label for="e">E (Eye Opening)</label>
            <select class="form-control" id="e" name="e">
                <option value="">Pilih</option>
                <option selected value="4">Spontan (4)</option>
                <option value="3">Perintah Suara (3)</option>
                <option value="2">Nyeri (2)</option>
                <option value="1">Tidak Ada Respon (1)</option>
            </select>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="v">V (Verbal)</label>
            <select class="form-control" id="v" name="v">
                <option value="">Pilih</option>
                <option selected value="5">Orientasi Baik (5)</option>
                <option value="4">Bingung (4)</option>
                <option value="3">Kata Tidak Tepat (3)</option>
                <option value="2">Kata Tidak Dimengerti (2)</option>
                <option value="1">Tidak Ada Suara (1)</option>
            </select>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="m">M (Motorik)</label>
            <select class="form-control" id="m" name="m">
                <option value="">Pilih</option>
                <option selected value="6">Perintah Tepat (6)</option>
                <option value="5">Lokal Nyeri (5)</option>
                <option value="4">Menarik (4)</option>
                <option value="3">Fleksi Abnormal (3)</option>
                <option value="2">Ekstensi Abnormal (2)</option>
                <option value="1">Tidak Ada Gerakan (1)</option>
            </select>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="hsl">Total GCS</label>
            <input value="15" type="number" id="hsl" name="hsl" class="form-control" readonly>
        </div>
    </div>
</div>

<!-- Vital Signs -->
<div class="row">
    <div class="col-md-3">
        <div class="form-group d-flex align-items-center">
            <label for="td" class="me-2 mb-0 mr-2" style="width: 40px;">TD</label>
            <input type="text" class="form-control" id="td" name="td" value="{{ old('td', $asesmen->td ?? $dataperawat->td ?? '') }}">
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group d-flex align-items-center">
            <label for="n" class="me-2 mb-0 mr-2" style="width: 40px;">N</label>
            <input type="text" class="form-control" id="n" name="n" value="{{ old('n', $asesmen->n ?? $dataperawat->nadi ?? '') }}">
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group d-flex align-items-center">
            <label for="s" class="me-2 mb-0 mr-2" style="width: 40px;">S</label>
            <input type="text" class="form-control" id="s" name="s" value="{{ old('s', $asesmen->s ?? $dataperawat->suhu ?? '') }}">
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group d-flex align-items-center">
            <label for="r" class="me-2 mb-0 mr-2" style="width: 40px;">R</label>
            <input type="text" class="form-control" id="r" name="r" value="{{ old('r', $asesmen->r ?? $dataperawat->rr ?? '') }}">
        </div>
    </div>
</div>

<!-- Pupil and Eye Examination -->
<table class="table table-bordered">
    <tr>
        <td>Pupil</td>
        <td>
            <div class="d-flex align-items-center">
                <span>Diameter: </span>
                <input type="text" class="form-control mx-2" style="width: 50px;" name="diameter_1" value="{{ old('diameter_1', $asesmen->diameter_1 ?? '3') }}">
                <span>/</span>
                <input type="text" class="form-control mx-2" style="width: 50px;" name="diameter_2" value="{{ old('diameter_2', $asesmen->diameter_2 ?? '3') }}">
            </div>
        </td>
        <td>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="isokor" name="isokor" value="1" {{ old('isokor', $asesmen->isokor ?? '') ? 'checked' : '' }}>
                <label class="form-check-label" for="isokor">Isokor</label>
            </div>
        </td>
        <td>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="anisokor" name="anisokor" value="1" {{ old('anisokor', $asesmen->anisokor ?? '') ? 'checked' : '' }}>
                <label class="form-check-label" for="anisokor">Anisokor</label>
            </div>
        </td>
    </tr>
    <tr>
        <td>Reflek Cahaya</td>
        <td colspan="3">
            <div class="row">
                <div class="col-md-6">
                    <input type="text" class="form-control" name="reflek_cahaya1" value="{{ old('reflek_cahaya1', $asesmen->reflek_cahaya1 ?? '') }}" placeholder="+">
                </div>
                <div class="col-md-6">
                    <input type="text" class="form-control" name="reflek_cahaya2" value="{{ old('reflek_cahaya2', $asesmen->reflek_cahaya2 ?? '') }}" placeholder="+">
                </div>
            </div>
        </td>
    </tr>
    <tr>
        <td>Reflek Cornea</td>
        <td colspan="3">
            <div class="row">
                <div class="col-md-6">
                    <input type="text" class="form-control" name="reflek_cornea1" value="{{ old('reflek_cornea1', $asesmen->reflek_cornea1 ?? '') }}" placeholder="+">
                </div>
                <div class="col-md-6">
                    <input type="text" class="form-control" name="reflek_cornea2" value="{{ old('reflek_cornea2', $asesmen->reflek_cornea2 ?? '') }}" placeholder="+">
                </div>
            </div>
        </td>
    </tr>
</table>

<!-- Nervus Cranialis Assessment -->
<div class="form-group">
    <label for="nervus">Nervus Cranialis (I-XII)</label>
    <textarea class="form-control" id="nervus" name="nervus" rows="3">{{ old('nervus', $asesmen->nervus ?? '') }}</textarea>
</div>

<!-- Meningeal Sign Assessment -->
<table class="table table-bordered">
    <tr>
        <td style="width: 20%;">Kaku Kuduk</td>
        <td>
            <input type="text" class="form-control" name="kaku_kuduk" value="{{ old('kaku_kuduk', $asesmen->kaku_kuduk ?? 'dbn') }}">
        </td>
    </tr>
    <tr>
        <td>Meningeal Sign</td>
        <td>
            <input type="text" class="form-control" name="sign" value="{{ old('sign', $asesmen->sign ?? 'dbn') }}">
        </td>
    </tr>
    <tr>
        <td>Brudzinski I-V</td>
        <td>
            <input type="text" class="form-control" name="brudzinki" value="{{ old('brudzinki', $asesmen->brudzinki ?? 'dbn') }}">
        </td>
    </tr>
    <tr>
        <td>Kernig Sign</td>
        <td>
            <input type="text" class="form-control" name="kernig" value="{{ old('kernig', $asesmen->kernig ?? 'dbn') }}">
        </td>
    </tr>
    <tr>
        <td>Doll's eye Phenomena</td>
        <td>
            <input type="text" class="form-control" name="doll" value="{{ old('doll', $asesmen->doll ?? 'dbn') }}">
        </td>
    </tr>
</table>

<!-- Vertebra and Extremitas -->
<div class="form-group">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" id="normal" name="normal" value="1" {{ old('normal', $asesmen->normal ?? '') ? 'checked' : '' }}>
        <label class="form-check-label" for="normal">Normal</label>
    </div>
</div>

<div class="form-group">
    <label for="vertebra">Vertebra</label>
    <input type="text" class="form-control" id="vertebra" name="vertebra" value="{{ old('vertebra', $asesmen->vertebra ?? '') }}">
</div>

<div class="form-group">
    <label for="extremitas">Extremitas</label>
    <input type="text" class="form-control" id="extremitas" name="extremitas" value="{{ old('extremitas', $asesmen->extremitas ?? '') }}">
</div>

<!-- Movement and Strength -->
<div class="form-group">
    <label>Gerak Dan Kekuatan</label>
    <div class="row">
        <div class="col-md-3">
            <input type="text" class="form-control mb-2" name="gerak1" value="{{ old('gerak1', $asesmen->gerak1 ?? '5') }}">
        </div>
        <div class="col-md-3">
            <input type="text" class="form-control mb-2" name="gerak2" value="{{ old('gerak2', $asesmen->gerak2 ?? '5') }}">
        </div>
    </div>
    <div class="row">
        <div class="col-md-3">
            <input type="text" class="form-control" name="gerak3" value="{{ old('gerak3', $asesmen->gerak3 ?? '5') }}">
        </div>
        <div class="col-md-3">
            <input type="text" class="form-control" name="gerak4" value="{{ old('gerak4', $asesmen->gerak4 ?? '5') }}">
        </div>
    </div>
</div>

<!-- Physiological Reflexes -->
<div class="form-group">
    <label>Reflek Fisiologis</label>
    <div class="row">
        <div class="col-md-3">
            <input type="text" class="form-control mb-2" name="reflek_fisio1" value="{{ old('reflek_fisio1', $asesmen->reflek_fisio1 ?? '+2') }}">
        </div>
        <div class="col-md-3">
            <input type="text" class="form-control mb-2" name="reflek_fisio2" value="{{ old('reflek_fisio2', $asesmen->reflek_fisio2 ?? '+2') }}">
        </div>
    </div>
    <div class="row">
        <div class="col-md-3">
            <input type="text" class="form-control" name="reflek_fisio3" value="{{ old('reflek_fisio3', $asesmen->reflek_fisio3 ?? '+2') }}">
        </div>
        <div class="col-md-3">
            <input type="text" class="form-control" name="reflek_fisio4" value="{{ old('reflek_fisio4', $asesmen->reflek_fisio4 ?? '+2') }}">
        </div>
    </div>
</div>

<!-- Pathological Reflexes -->
<div class="form-group">
    <label>Reflek Patologis</label>
    <div class="row">
        <div class="col-md-3">
            <input type="text" class="form-control mb-2" name="reflek_pato1" value="{{ old('reflek_pato1', $asesmen->reflek_pato1 ?? '-') }}">
        </div>
        <div class="col-md-3">
            <input type="text" class="form-control mb-2" name="reflek_pato2" value="{{ old('reflek_pato2', $asesmen->reflek_pato2 ?? '-') }}">
        </div>
    </div>
    <div class="row">
        <div class="col-md-3">
            <input type="text" class="form-control" name="reflek_pato3" value="{{ old('reflek_pato3', $asesmen->reflek_pato3 ?? '-') }}">
        </div>
        <div class="col-md-3">
            <input type="text" class="form-control" name="reflek_pato4" value="{{ old('reflek_pato4', $asesmen->reflek_pato4 ?? '-') }}">
        </div>
    </div>
</div>

<!-- Additional fields -->
<div class="form-group">
    <label for="add_tambahan">Tambahan</label>
    <textarea class="form-control" id="add_tambahan" name="add_tambahan" rows="3">{{ old('add_tambahan', $asesmen->add_tambahan ?? '') }}</textarea>
</div>

<div class="form-group">
    <label for="clonus">Clonus</label>
    <input type="text" class="form-control" id="clonus" name="clonus" value="{{ old('clonus', $asesmen->clonus ?? '') }}">
</div>

<div class="form-group">
    <label for="sensibilitas">Sensibilitas</label>
    <input type="text" class="form-control" id="sensibilitas" name="sensibilitas" value="{{ old('sensibilitas', $asesmen->sensibilitas ?? '') }}">
</div>

<!-- Keep your existing tables for physical examination -->
<table class="table table-bordered" style="color: white">
    <tbody>
        <tr>
            <td>1.</td>
            <td>Kepala</td>
            <td>:</td>
            <td><input type="text" class="form-control" name="kepala" value="{{ old('kepala', $asesmen->kepala ?? 'dbn') }}"></td>
        </tr>
        <tr>
            <td>2.</td>
            <td>Leher</td>
            <td>:</td>
            <td><input type="text" class="form-control" name="leher" value="{{ old('leher', $asesmen->leher ?? 'dbn') }}"></td>
        </tr>
        <tr>
            <td>3.</td>
            <td><em>Thorax</em></td>
            <td>:</td>
            <td><input type="text" class="form-control" name="thorax" value="{{ old('thorax', $asesmen->thorax ?? 'dbn') }}"></td>
        </tr>
        <tr>
            <td>4.</td>
            <td><em>Abdomen</em></td>
            <td>:</td>
            <td><input type="text" class="form-control" name="abdomen" value="{{ old('abdomen', $asesmen->abdomen ?? 'dbn') }}"></td>
        </tr>
        <tr>
            <td>5.</td>
            <td><em>Genitalia</em></td>
            <td>:</td>
            <td><input type="text" class="form-control" name="genitalia" value="{{ old('genitalia', $asesmen->genitalia ?? 'dbn') }}"></td>
        </tr>
        <tr>
            <td>6.</td>
            <td><em>Extremitas</em></td>
            <td>:</td>
            <td></td>
        </tr>
        <tr>
            <td></td>
            <td>- <em>Extremitas Atas</em></td>
            <td>:</td>
            <td><input type="text" class="form-control" name="ext_atas" value="{{ old('ext_atas', $asesmen->ext_atas ?? 'dbn') }}"></td>
        </tr>
        <tr>
            <td></td>
            <td>- <em>Extremitas Bawah</em></td>
            <td>:</td>
            <td><input type="text" class="form-control" name="ext_bawah" value="{{ old('ext_bawah', $asesmen->ext_bawah ?? 'dbn') }}"></td>
        </tr>
    </tbody>
</table>

