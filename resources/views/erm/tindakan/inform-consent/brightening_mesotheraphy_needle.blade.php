<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT BRIGHTENING MESOTHERAPY WITH NEEDLE</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Brightening Mesotherapy dengan Jarum</h5>
            </div>
            <div class="card-body">
                <p>Saya, yang bertanda tangan di bawah ini, dengan ini memberikan persetujuan untuk menjalani tindakan <strong>Brightening Mesotherapy with Needle</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong> Brightening Mesotherapy adalah prosedur kecantikan yang dilakukan dengan cara menyuntikkan campuran vitamin, antioksidan, dan bahan pencerah langsung ke lapisan dermis kulit menggunakan jarum kecil. Tujuannya adalah untuk membantu mencerahkan kulit, meratakan warna kulit, dan memperbaiki kondisi kulit secara keseluruhan.</p>

                <p><strong>Manfaat:</strong> Beberapa manfaat dari prosedur ini antara lain:</p>
                <ol>
                    <li>Mencerahkan dan meratakan warna kulit wajah.</li>
                    <li>Meningkatkan hidrasi dan nutrisi kulit.</li>
                    <li>Mengurangi noda hitam atau bekas jerawat.</li>
                    <li>Meningkatkan elastisitas dan tekstur kulit.</li>
                    <li>Merangsang regenerasi sel kulit baru.</li>
                </ol>

                <p><strong>Risiko dan Komplikasi:</strong> Prosedur ini relatif aman, namun tetap memiliki kemungkinan risiko, seperti:</p>
                <ol>
                    <li>Kemerahan, bengkak, atau memar ringan pada area suntikan.</li>
                    <li>Nyeri ringan selama atau setelah prosedur.</li>
                    <li>Infeksi di area suntikan (jarang, jika tidak steril).</li>
                    <li>Reaksi alergi terhadap bahan yang disuntikkan.</li>
                    <li>Nodul kecil di bawah kulit (biasanya sementara).</li>
                </ol>

                <p><strong>Alternatif:</strong> Alternatif dari tindakan ini termasuk brightening facial, penggunaan skincare topikal, chemical peeling, atau laser brightening. Saya telah mendiskusikan pilihan terbaik dengan dokter atau terapis berdasarkan kondisi kulit saya.</p>

                <p><strong>Persetujuan:</strong> Saya menyatakan bahwa saya telah menerima penjelasan yang lengkap dan jelas mengenai tindakan ini, termasuk manfaat, risiko, dan alternatif yang tersedia. Saya diberi kesempatan untuk bertanya dan telah mendapatkan jawaban yang memuaskan.</p>

                <p>Saya memahami bahwa hasil dapat bervariasi pada setiap individu dan bahwa hasil optimal memerlukan perawatan berulang. Saya menyatakan setuju dan memberikan persetujuan secara sadar dan sukarela.</p>

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