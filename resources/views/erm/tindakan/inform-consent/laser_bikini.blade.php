<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT LASER BIKINI</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Laser Bikini</h5>
            </div>
            <div class="card-body">
                <p>Saya, yang bertanda tangan di bawah ini, dengan ini memberikan persetujuan untuk menjalani tindakan <strong>Laser Bikini</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong> Laser Bikini adalah prosedur estetika yang menggunakan teknologi laser untuk menghilangkan rambut secara semi permanen pada area sekitar bikini. Prosedur ini bekerja dengan menargetkan pigmen rambut menggunakan sinar laser sehingga merusak folikel rambut dan menghambat pertumbuhannya.</p>

                <p><strong>Manfaat:</strong> Tindakan ini memberikan manfaat sebagai berikut:</p>
                <ol>
                    <li>Mengurangi pertumbuhan rambut di area bikini.</li>
                    <li>Membuat kulit terasa lebih halus dan bersih.</li>
                    <li>Mengurangi risiko iritasi akibat bercukur atau waxing.</li>
                    <li>Meningkatkan kenyamanan dan kebersihan pribadi.</li>
                </ol>

                <p><strong>Risiko dan Komplikasi:</strong> Meskipun prosedur ini umumnya aman, beberapa risiko atau efek samping yang mungkin terjadi meliputi:</p>
                <ol>
                    <li>Kemerahan dan bengkak di area yang dirawat.</li>
                    <li>Iritasi kulit atau sensasi terbakar ringan.</li>
                    <li>Perubahan warna kulit sementara (hiperpigmentasi atau hipopigmentasi).</li>
                    <li>Luka kecil atau lepuhan (jarang terjadi).</li>
                    <li>Risiko infeksi bila tidak dirawat dengan baik setelah tindakan.</li>
                </ol>

                <p><strong>Alternatif:</strong> Tindakan ini bukan satu-satunya metode penghilangan rambut. Alternatif lain termasuk mencukur, waxing, krim perontok rambut, dan metode elektrolisis. Saya telah berdiskusi dengan dokter/terapis mengenai pilihan yang paling sesuai.</p>

                <p><strong>Persetujuan:</strong> Saya telah menerima penjelasan yang cukup mengenai prosedur ini, termasuk manfaat, risiko, dan alternatifnya. Saya telah diberikan kesempatan untuk bertanya, dan seluruh pertanyaan saya telah dijawab dengan memuaskan. Saya menyadari bahwa hasil tindakan dapat bervariasi antar individu.</p>

                <p>Saya menyatakan bahwa saya memberikan persetujuan secara sukarela, dan saya berhak menghentikan tindakan ini kapan saja.</p>

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