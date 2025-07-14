
<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT PAKET EUROPE BOTOX FULL FACE</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Europe Botox Full Face</h5>
            </div>
            <div class="card-body">
                <p>Saya yang bertanda tangan di bawah ini memberikan persetujuan untuk menjalani <strong>Paket Europe Botox Full Face</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong><br>
                Europe Botox Full Face adalah teknik injeksi botulinum toxin (Botox) yang menggunakan dosis optimal untuk hasil yang lebih tegas namun tetap natural, mencakup area wajah seperti dahi, area glabella (antara alis), sekitar mata (crow’s feet), dan area lainnya yang sesuai indikasi. Teknik ini umum digunakan dalam pendekatan Eropa untuk menghasilkan efek rejuvenasi dan pengencangan yang lebih nyata.</p>

                <p><strong>Manfaat:</strong></p>
                <ol>
                    <li>Mengurangi garis ekspresi dan kerutan pada seluruh wajah.</li>
                    <li>Memberikan hasil wajah yang lebih kencang dan muda.</li>
                    <li>Efek tahan lebih lama dibanding dosis ringan (sekitar 4–6 bulan).</li>
                </ol>

                <p><strong>Risiko dan Efek Samping:</strong></p>
                <ol>
                    <li>Rasa nyeri atau bengkak di area injeksi.</li>
                    <li>Memar ringan atau kemerahan sementara.</li>
                    <li>Kelumpuhan otot sementara yang menyebabkan asimetri atau rasa berat.</li>
                    <li>Kelopak mata turun (ptosis), sakit kepala, atau keluhan lainnya (jarang).</li>
                </ol>

                <p><strong>Alternatif:</strong><br>
                Saya memahami bahwa terdapat alternatif seperti Korean Botox, filler, skinbooster, atau terapi estetika non-invasif lain. Dokter telah menjelaskan dan saya memilih tindakan ini berdasarkan indikasi dan keinginan hasil yang lebih kuat dan tahan lama.</p>

                <p><strong>Persetujuan:</strong><br>
                Saya telah diberi informasi lengkap dan jelas mengenai tindakan ini, termasuk manfaat, risiko, serta alternatifnya. Saya menyadari bahwa hasil setiap individu dapat bervariasi dan efek bersifat sementara. Dengan ini saya menyetujui untuk menjalani tindakan ini secara sadar dan sukarela.</p>

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