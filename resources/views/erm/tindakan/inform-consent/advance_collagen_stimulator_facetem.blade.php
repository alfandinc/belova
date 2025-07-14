<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT ADVANCE COLLAGEN STIMULATOR – FACETEM</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Advance Collagen Stimulator – Facetem</h5>
            </div>
            <div class="card-body">
                <p>Saya yang bertanda tangan di bawah ini memberikan persetujuan untuk menjalani tindakan <strong>Advance Collagen Stimulator menggunakan produk Facetem</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong> Facetem merupakan produk injeksi stimulan kolagen berbahan aktif (seperti Poly-L-lactic acid atau sejenisnya) yang disuntikkan ke beberapa titik pada wajah untuk merangsang produksi kolagen alami. Tindakan ini bertujuan memperbaiki tekstur, elastisitas, dan kepadatan kulit secara bertahap tanpa memberikan volume langsung seperti filler.</p>

                <p><strong>Manfaat:</strong></p>
                <ol>
                    <li>Stimulasi produksi kolagen secara alami dan progresif.</li>
                    <li>Mengencangkan dan meremajakan kulit wajah dari dalam.</li>
                    <li>Memperbaiki kontur wajah akibat hilangnya volume karena penuaan.</li>
                    <li>Efek natural tanpa perubahan bentuk wajah secara tiba-tiba.</li>
                    <li>Efek bertahap yang dapat bertahan hingga 12–24 bulan (tergantung kondisi kulit dan gaya hidup).</li>
                </ol>

                <p><strong>Risiko dan Komplikasi:</strong></p>
                <ol>
                    <li>Kemerahan, nyeri, atau bengkak di lokasi suntikan.</li>
                    <li>Memar ringan atau rasa keras sementara di titik injeksi.</li>
                    <li>Granuloma atau nodul jika penyebaran tidak merata atau tidak sesuai panduan pasca tindakan.</li>
                    <li>Infeksi lokal jika tidak dijaga kebersihannya.</li>
                    <li>Hasil tidak instan, memerlukan waktu dan sesi bertahap.</li>
                </ol>

                <p><strong>Alternatif:</strong> Saya telah dijelaskan bahwa terdapat alternatif seperti filler, tanam benang, HIFU, RF, atau skincare topikal untuk peremajaan kulit. Saya memilih tindakan ini atas dasar konsultasi dengan dokter/terapis dan pertimbangan hasil jangka panjang yang alami.</p>

                <p><strong>Persetujuan:</strong> Saya menyatakan telah memahami manfaat, risiko, dan alternatif dari prosedur ini. Saya sadar bahwa hasil akan muncul secara bertahap dan berbeda untuk tiap individu. Dengan ini saya memberikan persetujuan secara sadar dan tanpa paksaan.</p>

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