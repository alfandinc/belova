                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="keluhan_utama">KELUHAN UTAMA</label>
                                    <input type="text" class="form-control focus:outline-white focus:border-white" id="keluhan_utama" name="keluhan_utama" value="{{ old('keluhan_utama', $asesmen->keluhan_utama ?? $dataperawat->keluhan_utama ?? '') }}">
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
                                    <label for="riwayat_penyakit_dahulu">Riwayat Penyakit Dahulu</label>
                                    <input type="text" class="form-control" id="riwayat_penyakit_dahulu" name="riwayat_penyakit_dahulu" value="{{ old('riwayat_penyakit_dahulu', $asesmen->riwayat_penyakit_dahulu ?? '') }}">
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
                                    <td><input type="text" class="form-control" name="thorax" value="{{ old('leher', $asesmen->leher ?? 'dbn') }}"></td>
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
                       <div class="form-group">
                        <label class="form-label">Status Lokalis</label>
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
                                <textarea class="form-control" rows="4" placeholder="Tulis status lokalis di sini..."></textarea>
                            </div>

                            <!-- Hidden field for image -->
                            <input type="hidden" name="status_lokalis_image" id="status_lokalis_image">
                        </div>