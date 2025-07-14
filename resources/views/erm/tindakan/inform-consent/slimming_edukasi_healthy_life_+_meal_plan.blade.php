<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT SLIMMING EDUKASI HEALTHY LIFE + MEAL PLAN</h4>

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
                <h5 class="mb-0">Persetujuan Slimming Edukasi Healthy Life + Meal Plan</h5>
            </div>
            <div class="card-body">
                <p>Saya yang bertanda tangan di bawah ini memberikan persetujuan untuk mengikuti program <strong>Slimming Edukasi Healthy Life + Meal Plan</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Program:</strong>  
                Program ini merupakan layanan konsultasi dan edukasi gaya hidup sehat yang mencakup penyesuaian aktivitas harian, kebiasaan makan, serta pemberian <em>meal plan</em> sesuai kebutuhan pasien. Tujuannya adalah membantu menurunkan berat badan secara bertahap dan menjaga kesehatan tubuh secara menyeluruh.</p>

                <p><strong>Manfaat:</strong></p>
                <ol>
                    <li>Meningkatkan pemahaman pasien terhadap pola makan dan gaya hidup sehat.</li>
                    <li>Membantu menurunkan berat badan secara alami dan aman.</li>
                    <li>Meningkatkan kebiasaan makan dan aktivitas fisik sehari-hari.</li>
                    <li>Mengurangi risiko penyakit terkait obesitas (seperti hipertensi, diabetes, dan gangguan metabolik).</li>
                </ol>

                <p><strong>Risiko dan Catatan:</strong></p>
                <ol>
                    <li>Penurunan berat badan tidak selalu cepat dan memerlukan konsistensi.</li>
                    <li>Hasil dapat berbeda tergantung kondisi metabolisme dan kepatuhan pasien.</li>
                    <li>Efek seperti lemas, pusing, atau gangguan mood bisa terjadi di awal penyesuaian pola makan.</li>
                    <li>Program ini tidak menggantikan konsultasi dengan dokter spesialis gizi atau penyakit dalam jika dibutuhkan.</li>
                </ol>

                <p><strong>Alternatif:</strong> Pasien dapat memilih untuk menjalani program diet mandiri, berkonsultasi dengan ahli gizi lain, atau mengikuti program olahraga rutin tanpa meal plan dari klinik.</p>

                <p><strong>Persetujuan:</strong> Saya menyatakan telah menerima penjelasan tentang tujuan, manfaat, serta risiko dari program ini. Saya menyetujui untuk mengikuti program edukasi dan meal plan sesuai arahan tim medis/terapis secara sadar dan sukarela.</p>

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