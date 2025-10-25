<div class="container">
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">
        
        <h4 class="text-center mb-4">INFORMED CONSENT INJEKSI INTRAARTIKULAR</h4>
        
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4"><strong>Nama Pasien:</strong> {{ $pasien->nama }}</div>
                    <div class="col-md-4"><strong>No. RM:</strong> {{ $pasien->id }}</div>
                    <div class="col-md-4"><strong>Tanggal:</strong> {{ \Carbon\Carbon::parse($visitation->tanggal_visitation)->locale('id')->format('j F Y') }}</div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Informasi Tindakan Medis</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th width="5%">No.</th>
                            <th width="30%">JENIS INFORMASI</th>
                            <th>ISI INFORMASI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>Diagnosis (diagnosis kerja & diagnosis banding)</td>
                            <td>Abses intraartikular / masalah sendi terkait</td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>Dasar diagnosis</td>
                            <td>
                                1. Anamnesis<br>
                                2. Pemeriksaan fisik<br>
                                3. Pemeriksaan penunjang
                            </td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td>Tindakan Kedokteran</td>
                            <td>Injeksi intraartikular</td>
                        </tr>
                        <tr>
                            <td>4</td>
                            <td>Indikasi Tindakan</td>
                            <td>
                                1. Nyeri sendi berlebihan<br>
                                2. Kambuh berkepanjangan
                            </td>
                        </tr>
                        <tr>
                            <td>5</td>
                            <td>Tata Cara</td>
                            <td>
                                1. Anestesi Lokal jika diperlukan<br>
                                2. Injeksi intraartikular sesuai teknik aseptik
                            </td>
                        </tr>
                        <tr>
                            <td>6</td>
                            <td>Tujuan</td>
                            <td>
                                1. Mengurangi / menghilangkan nyeri<br>
                                2. Menekan peradangan<br>
                                3. Memperbaiki fungsi sendi
                            </td>
                        </tr>
                        <tr>
                            <td>7</td>
                            <td>Risiko</td>
                            <td>Nyeri, perdarahan, reaksi lokal</td>
                        </tr>
                        <tr>
                            <td>8</td>
                            <td>Komplikasi</td>
                            <td>
                                1. Peradangan<br>
                                2. Infeksi
                            </td>
                        </tr>
                        <tr>
                            <td>9</td>
                            <td>Prognosis</td>
                            <td>Ditentukan oleh kondisi penyerta dan respons pengobatan</td>
                        </tr>
                        <tr>
                            <td>10</td>
                            <td>Alternatif</td>
                            <td>Fisioterapi, terapi konservatif lainnya</td>
                        </tr>
                        <tr>
                            <td>11</td>
                            <td>Lain - lain</td>
                            <td>-</td>
                        </tr>
                    </tbody>
                </table>

                <div class="mt-4">
                    <p>Saya yang bertanda tangan dibawah ini menyetujui untuk dilakukan tindakan Injeksi Intraartikular dengan ketentuan:</p>
                    <ol>
                        <li>Saya telah mendapat penjelasan mengenai prosedur tindakan</li>
                        <li>Saya mengerti tentang manfaat dan risiko yang mungkin timbul</li>
                        <li>Saya setuju untuk dilakukan tindakan yang diperlukan</li>
                    </ol>
                </div>
                
                <div class="form-group mt-4">
                    <label for="notes">Catatan Tambahan:</label>
                    <textarea class="form-control" name="notes" id="notes" rows="3"></textarea>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Tanda Tangan</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 border-right">
                        <h6 class="text-center">Pasien/Wali</h6>
                        <div class="signature-container text-center">
                            <div class="signature-pad-container" style="border: 1px solid #ccc; background-color: #fff; margin: 0 auto; width: 350px; height: 150px;">
                                <canvas id="signatureCanvas" style="width: 100%; height: 100%;"></canvas>
                            </div>
                            <input type="hidden" name="signature" id="signatureData">
                            <button type="button" class="btn btn-sm btn-outline-danger mt-2" id="clearSignature">Clear</button>
                        </div>
                        <div class="form-group mt-3">
                            <label for="nama_pasien">Nama Pasien/Wali:</label>
                            <input type="text" class="form-control" id="nama_pasien" name="nama_pasien" value="{{ $pasien->nama }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-center">Saksi</h6>
                        <div class="signature-container text-center">
                            <div class="signature-pad-container" style="border: 1px solid #ccc; background-color: #fff; margin: 0 auto; width: 350px; height: 150px;">
                                <canvas id="witnessSignatureCanvas" style="width: 100%; height: 100%;"></canvas>
                            </div>
                            <input type="hidden" name="witness_signature" id="witnessSignatureData">
                            <button type="button" class="btn btn-sm btn-outline-danger mt-2" id="clearWitnessSignature">Clear</button>
                        </div>
                        <div class="form-group mt-3">
                            <label for="nama_saksi">Nama Saksi:</label>
                            <input type="text" class="form-control" id="nama_saksi" name="nama_saksi">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.card {
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
.border-right {
    border-right: 1px solid #dee2e6;
}
@media (max-width: 768px) {
    .border-right {
        border-right: none;
        border-bottom: 1px solid #dee2e6;
        padding-bottom: 20px;
        margin-bottom: 20px;
    }
}
</style>
