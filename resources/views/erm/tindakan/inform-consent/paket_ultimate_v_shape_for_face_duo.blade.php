<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT PAKET ULTIMATE V SHAPE FOR FACE DUO</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Paket Ultimate V Shape</h5>
            </div>
            <div class="card-body">
                <p>Saya yang bertanda tangan di bawah ini memberikan persetujuan untuk menjalani <strong>Paket Ultimate V Shape for Face Duo</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong><br>
                Tindakan ini merupakan kombinasi dua prosedur yang ditujukan untuk membentuk wajah dengan kontur berbentuk V yang ideal, meliputi:
                <ul>
                    <li><strong>Injeksi pengencangan atau pelangsing wajah:</strong> Menggunakan agen seperti botox dosis mikro atau mesococktail slimming untuk mengecilkan bagian bawah wajah.</li>
                    <li><strong>Teknologi pengencangan (misalnya RF atau HIFU):</strong> Untuk mengencangkan kulit dan menonjolkan garis rahang.</li>
                </ul></p>

                <p><strong>Manfaat:</strong></p>
                <ol>
                    <li>Meniruskan area rahang dan dagu untuk efek wajah berbentuk V.</li>
                    <li>Mengencangkan kulit secara non-invasif.</li>
                    <li>Memberikan tampilan wajah yang lebih simetris dan proporsional.</li>
                    <li>Meningkatkan kepercayaan diri dengan bentuk wajah yang ideal.</li>
                </ol>

                <p><strong>Risiko dan Efek Samping:</strong></p>
                <ol>
                    <li>Kemerahan, bengkak, atau memar ringan pada area suntikan (sementara).</li>
                    <li>Nyeri ringan atau rasa tidak nyaman saat atau setelah prosedur.</li>
                    <li>Asimetri wajah jika terjadi perbedaan respons di kedua sisi wajah (umumnya dapat dikoreksi).</li>
                    <li>Reaksi alergi terhadap bahan yang digunakan (jarang).</li>
                </ol>

                <p><strong>Alternatif:</strong><br>
                Saya telah diberi penjelasan mengenai pilihan lain seperti filler, threadlift, atau tindakan pelangsing wajah lainnya, dan telah memilih prosedur ini sebagai yang paling sesuai dengan kondisi saya saat ini.</p>

                <p><strong>Persetujuan:</strong><br>
                Saya memahami manfaat, risiko, dan alternatif tindakan ini. Saya menyadari bahwa hasil mungkin tidak langsung terlihat dan memerlukan waktu serta sesi lanjutan. Saya menyetujui prosedur ini secara sadar dan sukarela.</p>

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