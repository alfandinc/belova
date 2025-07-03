<div class="container">
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">
        
        <h4 class="text-center mb-4">INFORMED CONSENT BRIGHTENING INJECTION AURA</h4>
        
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
                <h5 class="mb-0">Persetujuan Tindakan Brightening Injection Aura</h5>
            </div>
            <div class="card-body">
                <p>Saya, yang bertanda tangan di bawah ini, dengan ini memberikan persetujuan untuk menjalani perawatan Inject Aura di Klinik Pratama Belova Skin & Beauty Center.</p>
                <p>Deskripsi Tindakan: Injeksi Aura adalah prosedur non-bedah yang bertujuan untuk mencerahkan kulit, meratakan warna kulit, dan meningkatkan kesehatan kulit secara keseluruhan. Bahan yang disuntikkan dapat berupa vitamin, antioksidan, atau zat lain yang dirancang untuk perawatan estetika kulit. Prosedur ini dilakukan oleh tenaga medis yang kompeten.</p>
                <p>Manfaat :</p>
                <ol>
                    <li>Membantu meningkatkan kecerahan kulit.</li>
                    <li>Memperbaiki tekstur dan elastisitas kulit.</li>
                    <li>Mengurangi tanda-tanda penuaan dini atau kelelahan kulit.</li>
                </ol>
                <p>Risiko dan Komplikasi:</p>
                <ol>
                    <li>Efek lokal ringan: Kemerahan, pembengkakan, memar, atau nyeri pada area injeksi.</li>
                    <li>Reaksi alergi terhadap bahan yang disuntikkan.</li>
                    <li>Infeksi (meskipun jarang terjadi, dapat terjadi pada area injeksi).</li>
                    <li>Hasil yang tidak sesuai harapan, seperti perubahan warna kulit sementara.</li>
                </ol>
                <p>Alternatif :</p>
                <ol>
                    <li>Perawatan topikal (krim mata atau serum).</li>
                    <li>Terapi laser atau radiofrekuensi untuk peremajaan kulit.</li>
                    <li>Tidak menjalani perawatan dengan memahami risiko kondisi tetap tidak berubah.</li>
                </ol>
                <p>Persetujuan: Pasien harus menyatakan bahwa mereka memahami informasi yang diberikan tentang prosedur, termasuk manfaat, risiko, alternatif, dan kemungkinan hasil. Pasien setuju untuk menjalani prosedur tersebut dengan kesadaran penuh bahwa hasilnya bisa bervariasi.</p>
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
/* Custom styles for the form */
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
