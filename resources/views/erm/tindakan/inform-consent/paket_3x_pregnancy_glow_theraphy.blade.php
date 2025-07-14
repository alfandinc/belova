<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT PAKET 3x PREGNANCY GLOW THERAPY</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Paket 3x Pregnancy Glow Therapy</h5>
            </div>
            <div class="card-body">
                <p>Saya yang bertanda tangan di bawah ini memberikan persetujuan untuk menjalani <strong>Paket 3x Pregnancy Glow Therapy</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong>  
                Paket ini terdiri dari tiga sesi perawatan wajah dan/atau tubuh yang dirancang khusus untuk ibu hamil, menggunakan produk yang aman dan bebas bahan aktif berisiko. Terapi dilakukan dengan pendekatan lembut untuk memberikan kenyamanan, kelembapan, dan membantu meningkatkan penampilan kulit selama masa kehamilan.</p>

                <p><strong>Manfaat:</strong></p>
                <ol>
                    <li>Menjaga kelembapan kulit secara berkelanjutan selama kehamilan.</li>
                    <li>Meningkatkan relaksasi dan mengurangi stres melalui sesi perawatan berkala.</li>
                    <li>Membantu mengatasi perubahan kulit akibat hormonal selama kehamilan.</li>
                    <li>Mencerahkan kulit dan meningkatkan kepercayaan diri ibu hamil.</li>
                </ol>

                <p><strong>Risiko dan Efek Samping:</strong></p>
                <ol>
                    <li>Reaksi kulit ringan terhadap produk meskipun bersifat non-irritant.</li>
                    <li>Mual ringan jika sensitif terhadap aroma terapi tertentu.</li>
                    <li>Ketidaknyamanan posisi perawatan jika tidak disesuaikan dengan usia kehamilan.</li>
                </ol>

                <p><strong>Alternatif:</strong> Saya memahami bahwa saya dapat memilih untuk melakukan perawatan wajah di rumah atau menunda perawatan hingga setelah masa kehamilan.</p>

                <p><strong>Persetujuan:</strong> Saya telah mendapatkan penjelasan lengkap tentang tindakan ini, termasuk manfaat, risiko, dan alternatifnya. Saya menyatakan memahami bahwa hasil perawatan bersifat individual dan menyetujui untuk menjalani rangkaian tindakan ini secara sukarela dan sadar.</p>

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