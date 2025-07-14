<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT PAKET ARMPIT GLOW ADVANCE</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Armpit Glow Advance</h5>
            </div>
            <div class="card-body">
                <p>Saya yang bertanda tangan di bawah ini memberikan persetujuan untuk menjalani <strong>Paket Armpit Glow Advance</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong><br>
                Paket ini merupakan perawatan pencerahan area ketiak tingkat lanjut yang menggabungkan beberapa metode, seperti:
                <ul>
                    <li>Peeling kimia dengan bahan aktif pencerah konsentrasi tinggi</li>
                    <li>Mesotherapy brightening (suntikan mikro ke dalam kulit)</li>
                    <li>Teknologi laser/light-based untuk membantu mengurangi pigmentasi dan merangsang regenerasi kulit</li>
                    <li>Masker dan serum khusus brightening untuk area sensitif</li>
                </ul>
                </p>

                <p><strong>Manfaat:</strong></p>
                <ol>
                    <li>Mencerahkan kulit ketiak secara lebih efektif dibanding perawatan dasar.</li>
                    <li>Mengurangi pigmentasi, noda, dan warna gelap yang membandel.</li>
                    <li>Memperbaiki tekstur kulit dan menjaga kelembapan alami area ketiak.</li>
                    <li>Memberikan hasil yang lebih optimal dalam waktu relatif singkat.</li>
                </ol>

                <p><strong>Risiko dan Efek Samping:</strong></p>
                <ol>
                    <li>Kemerahan, rasa panas, atau iritasi di area tindakan (sementara).</li>
                    <li>Pengelupasan kulit ringan hingga sedang.</li>
                    <li>Reaksi alergi terhadap bahan aktif (jarang).</li>
                    <li>Risiko hiperpigmentasi pasca inflamasi jika tidak dijaga dengan baik setelah tindakan.</li>
                </ol>

                <p><strong>Alternatif:</strong><br>
                Saya telah dijelaskan mengenai pilihan perawatan lain seperti laser tunggal, krim topikal, atau perawatan dasar (basic), dan saya secara sadar memilih Paket Armpit Glow Advance sebagai pilihan yang sesuai dengan kebutuhan dan kondisi kulit saya.</p>

                <p><strong>Persetujuan:</strong><br>
                Saya memahami manfaat, risiko, dan kemungkinan efek samping dari tindakan ini. Saya juga memahami bahwa hasil dapat berbeda pada tiap individu, dan tindakan lanjutan mungkin dibutuhkan untuk hasil optimal. Dengan ini saya menyetujui prosedur secara sadar dan sukarela.</p>

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