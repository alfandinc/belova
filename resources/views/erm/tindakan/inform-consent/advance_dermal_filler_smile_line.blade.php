<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT ADVANCE DERMAL FILLER – SMILE LINE</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Dermal Filler – Smile Line (Garis Senyum)</h5>
            </div>
            <div class="card-body">
                <p>Saya yang bertanda tangan di bawah ini memberikan persetujuan untuk menjalani tindakan <strong>injeksi dermal filler di area smile line (nasolabial fold)</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong> Prosedur ini melibatkan penyuntikan dermal filler berbasis <em>Hyaluronic Acid</em> pada area garis senyum (lipatan dari samping hidung ke sudut mulut) untuk mengurangi cekungan atau lipatan akibat penuaan dan penurunan volume wajah. Tujuannya adalah memberikan tampilan wajah yang lebih halus dan muda tanpa mengubah ekspresi alami.</p>

                <p><strong>Manfaat:</strong></p>
                <ol>
                    <li>Mengurangi kedalaman garis senyum (nasolabial fold).</li>
                    <li>Meningkatkan volume dan hidrasi kulit di area tersebut.</li>
                    <li>Memberikan efek peremajaan dan tampilan wajah yang lebih segar.</li>
                    <li>Prosedur cepat dan minim downtime.</li>
                    <li>Hasil instan dengan efek yang bertahan 6–12 bulan (tergantung produk dan metabolisme individu).</li>
                </ol>

                <p><strong>Risiko dan Komplikasi:</strong></p>
                <ol>
                    <li>Kemerahan, nyeri, memar, atau bengkak di area suntikan.</li>
                    <li>Asimetri atau hasil yang tidak merata.</li>
                    <li>Efek sementara seperti benjolan jika penyuntikan tidak merata (biasanya bisa diperbaiki).</li>
                    <li>Risiko oklusi pembuluh darah (jarang tapi serius).</li>
                    <li>Reaksi alergi atau granuloma (sangat jarang).</li>
                </ol>

                <p><strong>Alternatif:</strong> Saya telah dijelaskan tentang tindakan alternatif seperti tanam benang, RF, HIFU, atau penggunaan skincare jangka panjang. Saya memilih dermal filler karena hasilnya cepat dan sesuai dengan tujuan estetik saya.</p>

                <p><strong>Persetujuan:</strong> Saya menyatakan telah menerima penjelasan yang memadai mengenai tindakan ini. Saya memahami bahwa hasil dapat bervariasi dan bersifat sementara. Saya memberikan persetujuan secara sadar, sukarela, dan tanpa tekanan dari pihak manapun.</p>

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