<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT PAKET COLLAGEN STIMULATOR WITH SINESON & FACE FIRMING WITH MICROTOX</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Collagen Stimulator & Microtox</h5>
            </div>
            <div class="card-body">
                <p>Saya yang bertanda tangan di bawah ini memberikan persetujuan untuk menjalani <strong>Paket Collagen Stimulator with Sineson & Face Firming with Microtox</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong><br>
                Prosedur ini terdiri dari dua bagian:
                <ul>
                    <li><strong>Collagen Stimulator dengan Sineson:</strong> injeksi bahan aktif yang merangsang produksi kolagen alami untuk meningkatkan elastisitas dan struktur kulit.</li>
                    <li><strong>Microtox:</strong> injeksi botox dosis mikro yang disuntikkan ke lapisan permukaan kulit untuk mengencangkan pori, memperbaiki tekstur, dan memberikan efek lifting tanpa membekukan ekspresi wajah.</li>
                </ul></p>

                <p><strong>Manfaat:</strong></p>
                <ol>
                    <li>Merangsang produksi kolagen dan peremajaan kulit.</li>
                    <li>Mengencangkan kulit wajah secara alami.</li>
                    <li>Menghaluskan garis halus dan memperkecil tampilan pori-pori.</li>
                    <li>Memberikan efek lifting dan kulit tampak lebih segar.</li>
                </ol>

                <p><strong>Risiko dan Efek Samping:</strong></p>
                <ol>
                    <li>Kemerahan, memar, atau bengkak ringan di area suntikan.</li>
                    <li>Reaksi alergi terhadap bahan aktif (jarang).</li>
                    <li>Efek tidak merata atau asimetri (dapat disesuaikan dalam evaluasi lanjutan).</li>
                    <li>Nyeri ringan atau sensasi tidak nyaman saat penyuntikan.</li>
                </ol>

                <p><strong>Alternatif:</strong><br>
                Saya telah diberi penjelasan mengenai alternatif tindakan seperti facial RF, filler, atau skin booster lainnya. Setelah berkonsultasi, saya memilih tindakan ini sebagai solusi yang paling sesuai dengan kondisi kulit saya.</p>

                <p><strong>Persetujuan:</strong><br>
                Saya menyatakan telah memahami seluruh informasi yang disampaikan mengenai prosedur, manfaat, risiko, dan alternatif tindakan ini. Saya menyadari bahwa hasil akan bervariasi pada tiap individu dan memerlukan evaluasi berkala.</p>

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