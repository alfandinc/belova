<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT SLIMMING FACE V-LINE</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Slimming Face V-Line</h5>
            </div>
            <div class="card-body">
                <p>Saya yang bertanda tangan di bawah ini memberikan persetujuan untuk menjalani prosedur <strong>Slimming Face V-Line</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong> Prosedur Slimming Face V-Line adalah tindakan estetika yang bertujuan untuk membentuk wajah menjadi lebih tirus dan simetris menyerupai bentuk huruf V. Tindakan ini dapat menggunakan teknik injeksi pelarut lemak (lipolytic), injeksi botox pada otot masseter, atau kombinasi keduanya, tergantung kondisi anatomi wajah pasien.</p>

                <p><strong>Manfaat:</strong></p>
                <ol>
                    <li>Membantu meniruskan bagian rahang dan pipi yang tampak besar atau bulat.</li>
                    <li>Menjadikan bentuk wajah lebih simetris dan proporsional.</li>
                    <li>Meningkatkan kepercayaan diri pasien terhadap penampilan wajah.</li>
                    <li>Prosedur minim nyeri dan minim downtime.</li>
                </ol>

                <p><strong>Risiko dan Komplikasi:</strong></p>
                <ol>
                    <li>Kemerahan, bengkak ringan, atau memar di area suntikan.</li>
                    <li>Asimetri wajah sementara.</li>
                    <li>Nyeri ringan atau rasa berat pada area otot (misalnya pada rahang setelah botox masseter).</li>
                    <li>Hasil kurang maksimal jika struktur wajah tidak sesuai dengan prosedur.</li>
                    <li>Reaksi alergi terhadap zat injeksi (sangat jarang).</li>
                </ol>

                <p><strong>Alternatif:</strong> Saya telah dijelaskan alternatif lain seperti tanam benang, filler kontur wajah, HIFU, atau tidak melakukan tindakan sama sekali. Saya memahami manfaat, indikasi, dan batasan dari masing-masing pilihan.</p>

                <p><strong>Persetujuan:</strong> Saya telah menerima penjelasan secara lisan dan tertulis tentang tindakan Slimming Face V-Line termasuk manfaat, risiko, dan alternatif. Saya menyadari bahwa hasil dapat bervariasi pada tiap individu dan efeknya bersifat sementara. Dengan ini saya menyetujui untuk menjalani tindakan tersebut secara sadar dan tanpa tekanan.</p>

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