<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT LASER FRECKLES REMOVAL</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Laser Freckles Removal</h5>
            </div>
            <div class="card-body">
                <p>Saya, yang bertanda tangan di bawah ini, dengan ini memberikan persetujuan untuk menjalani tindakan <strong>Laser Freckles Removal</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong> Laser freckles removal adalah prosedur menggunakan sinar laser untuk menghancurkan melanin di area yang mengalami hiperpigmentasi seperti freckles (bintik cokelat). Prosedur ini bersifat non-invasif dan bertujuan untuk meratakan warna kulit dan mengurangi tampilan flek.</p>

                <p><strong>Manfaat:</strong> Manfaat dari prosedur ini antara lain:</p>
                <ol>
                    <li>Mengurangi atau menghilangkan freckles dan bintik hitam.</li>
                    <li>Meratakan warna kulit wajah.</li>
                    <li>Meningkatkan penampilan kulit secara keseluruhan.</li>
                    <li>Memberikan efek kulit lebih cerah dan bersih.</li>
                </ol>

                <p><strong>Risiko dan Komplikasi:</strong> Seperti tindakan medis lain, prosedur ini memiliki beberapa risiko:</p>
                <ol>
                    <li>Kemerahan dan pembengkakan ringan setelah tindakan.</li>
                    <li>Rasa terbakar atau perih sementara.</li>
                    <li>Penggelapan sementara pada area freckles sebelum mengelupas.</li>
                    <li>Hiperpigmentasi atau hipopigmentasi (jarang terjadi).</li>
                    <li>Risiko infeksi jika tidak dilakukan perawatan pasca-tindakan dengan benar.</li>
                </ol>

                <p><strong>Alternatif:</strong> Alternatif perawatan freckles termasuk krim pemutih, peeling kimia, atau IPL (Intense Pulsed Light). Saya telah berdiskusi dengan dokter/terapis tentang pilihan terbaik yang sesuai dengan kondisi kulit saya.</p>

                <p><strong>Persetujuan:</strong> Saya menyatakan telah menerima penjelasan yang jelas mengenai tindakan ini, termasuk manfaat, risiko, dan alternatifnya. Saya diberikan kesempatan untuk bertanya dan memahami semua informasi yang disampaikan.</p>

                <p>Saya menyadari bahwa hasil dari tindakan ini dapat berbeda pada setiap individu. Dengan ini saya memberikan persetujuan secara sukarela dan berhak menghentikan tindakan kapan saja jika merasa tidak nyaman.</p>

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