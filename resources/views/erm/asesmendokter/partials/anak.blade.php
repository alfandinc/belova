

<div class="row">
    <div class="col-md-12">
        <div class="form-group">
            <label>Anamnesis :</label>
            <div class="d-flex">
                <div class="form-check mr-3">
                    <input class="form-check-input" type="checkbox" id="autoanamnesis" name="autoanamnesis" value="1" {{ old('autoanamnesis', $asesmen->autoanamnesis ?? '') ? 'checked' : '' }}>
                    <label class="form-check-label" for="autoanamnesis">Autoanamnesis</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="alloanamnesis" name="alloanamnesis" value="1" {{ old('alloanamnesis', $asesmen->alloanamnesis ?? '') ? 'checked' : '' }}>
                    <label class="form-check-label" for="alloanamnesis">Alloanamnesis</label>
                </div>
                <div class="ml-3">
                    <label>dengan :</label>
                    <input type="text" class="form-control d-inline-block ml-2" id="allo_dengan" name="allo_dengan" style="width: 200px;" value="{{ old('allo_dengan', $asesmen->allo_dengan ?? '') }}">
                </div>
                <div class="ml-3">
                    <label>Hubungan dengan pasien :</label>
                    <input type="text" class="form-control d-inline-block ml-2" id="anamnesis2" name="anamnesis2" style="width: 200px;" value="{{ old('anamnesis2', $asesmen->anamnesis2 ?? '') }}">
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="form-group">
            <label for="keluhan_utama">Keluhan Utama</label>
            <input type="text" class="form-control" id="keluhan_utama" name="keluhan_utama" value="{{ old('keluhan_utama', $asesmen->keluhan_utama ?? $dataperawat->keluhan_utama ?? '') }}">
        </div>
    </div>
    
</div>

<hr>

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
            <label for="hasil_allo">Hasil Alloanamnesis</label>
            <input type="text" class="form-control" id="hasil_allo" name="hasil_allo" value="{{ old('hasil_allo', $asesmen->hasil_allo ?? '') }}">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="riwayat_penyakit_keluarga">Riwayat Penyakit Keluarga & Pohon Keluarga</label>
            <input type="text" class="form-control" id="riwayat_penyakit_keluarga" name="riwayat_penyakit_keluarga" value="{{ old('riwayat_penyakit_keluarga', $asesmen->riwayat_penyakit_keluarga ?? '') }}">
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="riwayat_makanan">Riwayat Makanan</label>
            <input type="text" class="form-control" id="riwayat_makanan" name="riwayat_makanan" value="{{ old('riwayat_makanan', $asesmen->riwayat_makanan ?? '') }}">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="riwayat_tumbang">Riwayat Pertumbuhan dan Perkembangan</label>
            <input type="text" class="form-control" id="riwayat_tumbang" name="riwayat_tumbang" value="{{ old('riwayat_tumbang', $asesmen->riwayat_tumbang ?? '') }}">
        </div>
    </div>
</div>

<div class="row">
    
    <div class="col-md-6">
        <div class="form-group">
            <label for="riwayat_kehamilan">Riwayat Kehamilan</label>
            <input type="text" class="form-control" id="riwayat_kehamilan" name="riwayat_kehamilan" value="{{ old('riwayat_kehamilan', $asesmen->riwayat_kehamilan ?? '') }}">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="riwayat_persalinan">Riwayat Persalinan</label>
            <input type="text" class="form-control" id="riwayat_persalinan" name="riwayat_persalinan" value="{{ old('riwayat_persalinan', $asesmen->riwayat_persalinan ?? '') }}">
        </div>
    
</div>
</div>
<hr>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Imunisasi Dasar</label>
            <div class="d-flex">
                <div class="form-check mr-3">
                    <input class="form-check-input" type="checkbox" id="imunisasi_dasar_lengkap" name="imunisasi_dasar" value="Lengkap" {{ old('imunisasi_dasar', $asesmen->imunisasi_dasar ?? '') == 'Lengkap' ? 'checked' : '' }}>
                    <label class="form-check-label" for="imunisasi_dasar_lengkap">Lengkap</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="imunisasi_dasar_tidak" name="imunisasi_dasar" value="Tidak Lengkap" {{ old('imunisasi_dasar', $asesmen->imunisasi_dasar ?? '') == 'Tidak Lengkap' ? 'checked' : '' }}>
                    <label class="form-check-label" for="imunisasi_dasar_tidak">Tidak Lengkap</label>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="imunisasi_dasar_ket">Keterangan</label>
            <input type="text" class="form-control" id="imunisasi_dasar_ket" name="imunisasi_dasar_ket" value="{{ old('imunisasi_dasar_ket', $asesmen->imunisasi_dasar_ket ?? '') }}">
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Imunisasi Lanjutan</label>
            <div class="d-flex">
                <div class="form-check mr-3">
                    <input class="form-check-input" type="checkbox" id="imunisasi_lanjut_lengkap" name="imunisasi_lanjut" value="Lengkap" {{ old('imunisasi_lanjut', $asesmen->imunisasi_lanjut ?? '') == 'Lengkap' ? 'checked' : '' }}>
                    <label class="form-check-label" for="imunisasi_lanjut_lengkap">Lengkap</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="imunisasi_lanjut_tidak" name="imunisasi_lanjut" value="Tidak Lengkap" {{ old('imunisasi_lanjut', $asesmen->imunisasi_lanjut ?? '') == 'Tidak Lengkap' ? 'checked' : '' }}>
                    <label class="form-check-label" for="imunisasi_lanjut_tidak">Tidak Lengkap</label>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="imunisasi_lanjut_ket">Keterangan</label>
            <input type="text" class="form-control" id="imunisasi_lanjut_ket" name="imunisasi_lanjut_ket" value="{{ old('imunisasi_lanjut_ket', $asesmen->imunisasi_lanjut_ket ?? '') }}">
        </div>
    </div>
</div>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="keadaan_umum">Keadaan Umum</label>
                                    <input type="text" class="form-control" id="keadaan_umum" name="keadaan_umum" value="{{ old('keadaan_umum', $asesmen->keadaan_umum ?? 'Baik') }}">
                                </div>
                            </div>

                        </div>
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
                    <hr>