<div class="container">
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">
        
        <h4 class="text-center mb-4">INFORMED CONSENT RF SINESON</h4>
        
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
                <h5 class="mb-0">Persetujuan Tindakan Lifting RF Sineson Bipolar</h5>
            </div>
            <div class="card-body">
                <p>Saya, yang bertanda tangan di bawah ini, dengan ini memberikan persetujuan untuk menjalani perawatan RF SINESON di Klinik Pratama Belova Skin & Beauty Center.</p>
                <p>Deskripsi Tindakan: Perawatan RF (Radiofrequency) SINESON adalah prosedur perawatan kulit yang menggunakan gelombang radiofrekuensi untuk merangsang produksi kolagen dan mengencangkan kulit. Tujuan perawatan ini adalah untuk mengurangi kerutan, meremajakan kulit, dan meningkatkan elastisitas kulit.</p>
                <p>Manfaat: Perawatan RF SINESON dapat memberikan manfaat sebagai berikut:</p>
                <ol>
                    <li>Mengurangi kerutan dan garis halus.</li>
                    <li>Meremajakan kulit dan meningkatkan elastisitas.</li>
                    <li>Meratakan tekstur kulit dan mengurangi pori-pori yang membesar.</li>

                </ol>
                <p>Risiko dan Komplikasi: Meskipun perawatan RF SINESON umumnya dianggap aman, tetapi setiap prosedur medis memiliki risiko. Beberapa risiko dan komplikasi yang mungkin terjadi termasuk:</p>
                <ol>
                    <li>Kulit menjadi merah atau teriritasi setelah perawatan.</li>
                    <li>Kemungkinan terjadi pembengkakan atau memar.</li>
                    <li>Perubahan warna kulit atau timbulnya bintik-bintik.</li>
                    <li>Reaksi alergi terhadap gelombang radiofrekuensi.</li>
                </ol>
                <p>Alternatif: Terdapat beberapa alternatif untuk perawatan kulit, seperti krim atau losion topikal, peeling kimia, atau prosedur laser lainnya. Dokter atau terapis telah membahas opsi yang paling sesuai dengan kondisi kulit saya.</p>
                <p>Persetujuan: Saya telah diberikan kesempatan untuk bertanya tentang tindakan ini dan semua pertanyaan saya telah dijawab dengan memuaskan. Saya mengerti bahwa hasil dari perawatan RF SINESON tidak dapat dijamin dan dapat bervariasi dari individu ke individu. Saya juga memahami bahwa saya memiliki hak untuk menolak atau menghentikan tindakan ini kapan saja.</p>
                <p>Penjelasan Risiko dan Manfaat: Saya telah diberikan penjelasan yang memadai tentang risiko, manfaat, dan alternatif dari tindakan ini. Saya mengerti dan setuju dengan penjelasan tersebut.</p>
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
