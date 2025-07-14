<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT PAKET HYPERHIDROSIS TREATMENT</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Hyperhidrosis Treatment</h5>
            </div>
            <div class="card-body">
                <p>Saya yang bertanda tangan di bawah ini memberikan persetujuan untuk menjalani <strong>Hyperhidrosis Treatment</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong><br>
                Hyperhidrosis Treatment adalah tindakan medis untuk mengurangi produksi keringat berlebih (hiperhidrosis) pada area tertentu seperti ketiak, telapak tangan, atau kaki. Metode yang digunakan biasanya berupa injeksi toksin botulinum tipe A (Botox) untuk memblokir sementara kerja saraf yang merangsang kelenjar keringat.</p>

                <p><strong>Manfaat:</strong></p>
                <ol>
                    <li>Mengurangi keringat berlebih secara signifikan pada area yang ditangani.</li>
                    <li>Memperbaiki kualitas hidup, kenyamanan sosial, dan percaya diri.</li>
                    <li>Mengurangi risiko infeksi kulit akibat kelembapan berlebih.</li>
                </ol>

                <p><strong>Risiko dan Efek Samping:</strong></p>
                <ol>
                    <li>Nyeri ringan, kemerahan, atau bengkak di area suntikan.</li>
                    <li>Kekakuan otot sementara (tergantung lokasi injeksi).</li>
                    <li>Sakit kepala atau rasa tidak nyaman (biasanya ringan dan sementara).</li>
                    <li>Reaksi alergi terhadap toksin (jarang terjadi).</li>
                </ol>

                <p><strong>Alternatif:</strong><br>
                Saya telah dijelaskan bahwa alternatif lain untuk menangani hiperhidrosis meliputi penggunaan antiperspiran medis, terapi iontophoresis, atau prosedur bedah. Saya memilih tindakan ini setelah mendapatkan penjelasan dari dokter.</p>

                <p><strong>Persetujuan:</strong><br>
                Saya menyatakan telah mendapatkan penjelasan yang lengkap dan dapat dimengerti mengenai prosedur, manfaat, risiko, serta alternatif tindakan ini. Saya juga memahami bahwa efeknya bersifat sementara dan pengulangan tindakan mungkin diperlukan setiap 4–6 bulan. Dengan ini saya menyetujui tindakan ini secara sadar dan sukarela.</p>

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