                        <style>
    /* Custom styles for the slider */
    input[type="range"] {
        -webkit-appearance: none;
        width: 100%;
        height: 8px;
        border-radius: 5px;
        background: #ddd;
        outline: none;
        transition: background 0.3s;
    }

    input[type="range"]::-webkit-slider-thumb {
        -webkit-appearance: none;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #fff;
        cursor: pointer;
        box-shadow: 0 0 2px rgba(0, 0, 0, 0.5);
    }

    input[type="range"].red {
        background: red;
    }

    input[type="range"].yellow {
        background: yellow;
    }

    input[type="range"].green {
        background: green;
    }
</style>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="keluhan_utama">KELUHAN UTAMA</label>
                                    <textarea class="form-control focus:outline-white focus:border-white" id="keluhan_utama" name="keluhan_utama" rows="3">{{ old('keluhan_utama', $asesmen->keluhan_utama ?? $dataperawat->keluhan_utama ?? '') }}</textarea>
                                </div>
                            </div>
                        </div>
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
                        
                        
                        <div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="kebiasaan_makan">Kebiasaan Makan</label>
            <select class="form-control select2" id="kebiasaan_makan" name="kebiasaan_makan[]" multiple>
                
                <option value="Berlemak">Berlemak</option>
<option value="Tepung-tepungan">Tepung-tepungan</option>
<option value="Sayur">Sayur</option>
<option value="Protein seimbang">Protein seimbang</option>
<option value="Tanpa sayur">Tanpa sayur</option>
<option value="Tanpa daging">Tanpa daging</option>
<option value="Makan sehat">Makan sehat</option>
<option value="Tidak teratur">Tidak teratur</option>
<option value="Protein">Protein</option>
<option value="Makanan Manis">Makanan Manis</option>
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="kebiasaan_minum">Kebiasaan Minum</label>
            <select class="form-control select2" id="kebiasaan_minum" name="kebiasaan_minum[]" multiple>
                <option value="8 gelas / hari">8 gelas / hari</option>
<option value="< 8 gelas / hari">&lt; 8 gelas / hari</option>
<option value="Suka minum manis">Suka minum manis</option>

            </select>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="pola_tidur">Pola Tidur</label>
            <div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="pola_tidur" id="pola_tidur_teratur" value="Teratur" {{ old('pola_tidur', $asesmen->pola_tidur ?? '') == 'Teratur' ? 'checked' : '' }}>
                    <label class="form-check-label" for="pola_tidur_teratur">Teratur</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="pola_tidur" id="pola_tidur_begadang" value="Begadang" {{ old('pola_tidur', $asesmen->pola_tidur ?? '') == 'Begadang' ? 'checked' : '' }}>
                    <label class="form-check-label" for="pola_tidur_begadang">Begadang</label>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="kontrasepsi">Kontrasepsi</label>
            <select class="form-control select2" id="kontrasepsi" name="kontrasepsi">
                <option selected value="Tidak pakai">Tidak pakai</option>
                <option value="Hormonal">Hormonal</option>
<option value="Non hormonal">Non hormonal</option>
<option value="Hamil">Hamil</option>
<option value="Belum menikah">Belum menikah</option>
<option value="Menyusui">Menyusui</option>

<option value="Program hamil">Program hamil</option>
<option value="Sedang hamil">Sedang hamil</option>

            </select>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="riwayat_perawatan">Riwayat Perawatan</label>
            <input type="text" class="form-control" id="riwayat_perawatan" name="riwayat_perawatan" value="{{ old('riwayat_perawatan', $asesmen->riwayat_perawatan ?? '') }}">
        </div>
    </div>
    <div class="col-md-6">
                                <div class="form-group">
                                    <label for="obat_dikonsumsi">Obat yang Dikonsumsi</label>
                                    <input type="text" class="form-control" id="obat_dikonsumsi" name="obat_dikonsumsi" value="{{ old('obat_dikonsumsi', $asesmen->obat_dikonsumsi ?? '') }}">
                                </div>
                            </div>
</div>
<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="jenis_kulit">Jenis Kulit</label>
            <select class="form-control select2" id="jenis_kulit" name="jenis_kulit">
                <option value="Normal">Normal</option>
                <option value="Kering">Kering</option>
                <option value="Berminyak">Berminyak</option>
                <option value="Sensitif">Sensitif</option>
                <option value="Kombinasi">Kombinasi</option>
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="kelembaban">Kelembaban Kulit</label>
            <input type="range" class="form-range w-100" id="kelembaban" name="kelembaban" min="0" max="100" step="50" value="{{ old('kelembaban', $asesmen->kelembaban ?? 100) }}">
            <div class="d-flex justify-content-between align-items-center mt-2">
                <span class="text-muted">Kurang</span>
                <span class="text-muted">Cukup</span>
                <span class="text-muted">Baik</span>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="area_kerutan">Area Kerutan</label>
            <select class="form-control select2" id="area_kerutan" name="area_kerutan[]" multiple>
               <option value="Dahi">Dahi</option>
<option value="Sudut mata">Sudut mata</option>
<option value="Hidung">Hidung</option>
<option value="Sudut bibir">Sudut bibir</option>
<option value="Minimal">Minimal</option>
<option value="Merata">Merata</option>
<option value="Pipi">Pipi</option>

            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="kekenyalan">Kekenyalan Kulit</label>
            <input type="range" class="form-range w-100" id="kekenyalan" name="kekenyalan" min="0" max="100" step="50" value="{{ old('kekenyalan', $asesmen->kekenyalan ?? 50) }}">
            <div class="d-flex justify-content-between align-items-center mt-2">
                <span class="text-muted">Kendor</span>
                <span class="text-muted">Normal</span>
                <span class="text-muted">Kuat</span>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="kelainan_kulit">Kelainan Kulit</label>
            <select class="form-control select2" id="kelainan_kulit" name="kelainan_kulit[]" multiple>
                <option value="Pori besar">Pori besar</option>
<option value="Hiperpigmentasi">Hiperpigmentasi</option>
<option value="Hipopigmentasi">Hipopigmentasi</option>
<option value="Acne milia">Acne milia</option>
<option value="Acne kistik">Acne kistik</option>
<option value="Acne nodul">Acne nodul</option>
<option value="Naevus pigmentosus">Naevus pigmentosus</option>
<option value="Scar hipertrofi">Scar hipertrofi</option>
<option value="Keloid">Keloid</option>
<option value="Striae">Striae</option>
<option value="Tidak ada">Tidak ada</option>
<option value="Acne komedonal">Acne komedonal</option>
<option value="Early Aging">Early Aging</option>
<option value="Scar">Scar</option>
<option value="PIE/PIH">PIE/PIH</option>

            </select>
        </div>
    </div>
</div>

                        
                       <div class="form-group">
                        <label class="form-label">Tanda Penuaaan</label>
                            <!-- Gambar (Canvas + Img) centered -->
                            <div class="col-12 mb-2 d-flex justify-content-center">
                                <div>
                                    @php
                                        $lokalisPath = old('status_lokalis', $asesmen->status_lokalis ?? null);
                                    @endphp

                                    <canvas id="drawingCanvas" class="img-fluid rounded border"></canvas>
                                </div>
                            </div>

                            <!-- Tombol centered -->
                            <div class="col-12 mb-3 d-flex justify-content-center">
                                <button type="button" class="btn btn-secondary mr-2" id="resetButton">Reset</button>
                                <button type="button" class="btn btn-primary" id="addButton">Add</button>
                            </div>

                            <!-- Textarea -->
                            <div class="col-12 mb-3">
                                <textarea class="form-control" rows="4" placeholder="Tuliskan deskripsi tanda..."></textarea>
                            </div>

                            <!-- Hidden field for image -->
                            <input type="hidden" name="status_lokalis_image" id="status_lokalis_image">
                        </div>

                       <script>
    document.addEventListener('DOMContentLoaded', function () {
        const kelembabanSlider = document.getElementById('kelembaban');
        const kelembabanDescription = document.getElementById('kelembaban-description');
        const kekenyalanSlider = document.getElementById('kekenyalan');
        const kekenyalanDescription = document.getElementById('kekenyalan-description');

        // Function to update kelembaban description and color
        kelembabanSlider.addEventListener('input', function () {
            const value = kelembabanSlider.value;
            if (value == 0) {
                kelembabanSlider.style.background = 'red';
            } else if (value == 50) {
                kelembabanSlider.style.background = 'yellow';
            } else if (value == 100) {
                kelembabanSlider.style.background = 'green';
            }
        });

        // Function to update kekenyalan description and color
        kekenyalanSlider.addEventListener('input', function () {
            const value = kekenyalanSlider.value;
            if (value == 0) {
                kekenyalanSlider.style.background = 'red';
            } else if (value == 50) {
                kekenyalanSlider.style.background = 'yellow';
            } else if (value == 100) {
                kekenyalanSlider.style.background = 'green';
            }
        });

        // Initialize descriptions and colors on page load
        kelembabanSlider.dispatchEvent(new Event('input'));
        kekenyalanSlider.dispatchEvent(new Event('input'));
    });
</script>