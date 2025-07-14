<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT PAKET PREGNANCY GLOW THERAPY</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Pregnancy Glow Therapy</h5>
            </div>
            <div class="card-body">
                <p>Saya yang bertanda tangan di bawah ini memberikan persetujuan untuk menjalani <strong>Paket Pregnancy Glow Therapy</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong>  
                Pregnancy Glow Therapy adalah serangkaian perawatan relaksasi dan perawatan kulit wajah/tubuh yang aman bagi ibu hamil. Seluruh produk dan teknik yang digunakan telah disesuaikan agar tidak membahayakan kondisi kehamilan, dengan fokus utama pada kelembapan, relaksasi, dan peremajaan kulit selama kehamilan.</p>

                <p><strong>Manfaat:</strong></p>
                <ol>
                    <li>Meningkatkan kelembapan dan elastisitas kulit.</li>
                    <li>Membantu mengurangi stres dan memberikan efek relaksasi.</li>
                    <li>Mencerahkan dan menyegarkan tampilan kulit wajah atau tubuh.</li>
                    <li>Meningkatkan rasa percaya diri selama masa kehamilan.</li>
                </ol>

                <p><strong>Risiko dan Efek Samping:</strong></p>
                <ol>
                    <li>Reaksi alergi ringan terhadap produk yang digunakan.</li>
                    <li>Rasa tidak nyaman selama perawatan jika posisi tubuh tidak sesuai.</li>
                    <li>Kemungkinan mual atau pusing akibat aroma/aromaterapi (bila digunakan).</li>
                </ol>

                <p><strong>Alternatif:</strong> Saya menyadari bahwa saya dapat memilih untuk tidak melakukan perawatan selama kehamilan, atau melakukan perawatan mandiri di rumah dengan produk khusus untuk ibu hamil.</p>

                <p><strong>Persetujuan:</strong> Saya telah menerima penjelasan tentang manfaat, risiko, dan langkah-langkah tindakan ini, serta menyatakan bahwa saya saat ini dalam kondisi kehamilan yang sehat. Saya memahami bahwa hasil dapat bervariasi dan menyetujui untuk menjalani prosedur ini secara sadar dan sukarela.</p>

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