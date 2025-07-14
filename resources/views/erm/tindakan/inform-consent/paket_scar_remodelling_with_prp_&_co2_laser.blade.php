<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT PAKET SCAR REMODELLING WITH PRP & CO2 LASER</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Paket Scar Remodelling with PRP & CO2 Laser</h5>
            </div>
            <div class="card-body">
                <p>Saya yang bertanda tangan di bawah ini memberikan persetujuan untuk menjalani <strong>Paket Scar Remodelling with PRP & CO2 Laser</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong><br>
                Paket ini merupakan kombinasi antara terapi <strong>CO2 fractional laser</strong> yang berfungsi untuk mengikis jaringan parut dan merangsang regenerasi kulit, serta <strong>injeksi PRP (Platelet Rich Plasma)</strong> yang menggunakan plasma darah sendiri untuk mempercepat proses penyembuhan dan peremajaan kulit.</p>

                <p><strong>Manfaat:</strong></p>
                <ol>
                    <li>Meningkatkan tekstur dan elastisitas kulit yang memiliki bekas luka.</li>
                    <li>Mendorong regenerasi jaringan baru melalui stimulasi kolagen.</li>
                    <li>Memudarkan bekas luka jerawat (acne scar) secara bertahap.</li>
                    <li>Mempercepat penyembuhan pasca laser melalui manfaat PRP.</li>
                </ol>

                <p><strong>Risiko dan Efek Samping:</strong></p>
                <ol>
                    <li>Kemerahan, bengkak, atau rasa perih setelah tindakan laser.</li>
                    <li>Pengelupasan kulit beberapa hari setelah prosedur.</li>
                    <li>Memar atau bengkak pada area injeksi PRP.</li>
                    <li>Risiko infeksi jika tidak dilakukan perawatan pasca tindakan yang tepat.</li>
                </ol>

                <p><strong>Alternatif:</strong><br>
                Saya telah diberitahu bahwa terdapat prosedur alternatif seperti perawatan scar konvensional, subsisi, skinbooster, atau filler tergantung pada kebutuhan dan tingkat keparahan scar saya.</p>

                <p><strong>Persetujuan:</strong><br>
                Saya menyatakan bahwa saya telah menerima informasi secara lengkap dan jelas tentang prosedur ini, termasuk manfaat, risiko, dan alternatifnya. Saya memahami bahwa hasil dapat bervariasi antar individu dan mungkin memerlukan beberapa kali sesi untuk hasil optimal. Dengan ini saya memberikan persetujuan secara sadar dan sukarela untuk menjalani tindakan ini.</p>

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