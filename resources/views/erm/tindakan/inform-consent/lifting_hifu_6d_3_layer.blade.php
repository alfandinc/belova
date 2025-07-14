<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT LIFTING HIFU 6D – 3 LAYER</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Lifting HIFU 6D (3 Layer)</h5>
            </div>
            <div class="card-body">
                <p>Saya, yang bertanda tangan di bawah ini, memberikan persetujuan untuk menjalani tindakan <strong>Lifting HIFU 6D 3 Layer</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong> HIFU (High-Intensity Focused Ultrasound) adalah teknologi yang digunakan untuk mengencangkan kulit tanpa operasi. Prosedur ini bekerja dengan memanaskan jaringan pada tiga kedalaman berbeda (epidermis, dermis, dan SMAS layer) guna menstimulasi produksi kolagen dan elastin. HIFU 6D 3 layer dilakukan bertahap sesuai standar untuk hasil lifting maksimal.</p>

                <p><strong>Manfaat:</strong> Tindakan ini dapat memberikan manfaat sebagai berikut:</p>
                <ol>
                    <li>Efek lifting dan pengencangan kulit yang lebih menyeluruh.</li>
                    <li>Perbaikan kontur wajah dan rahang (V-Shape).</li>
                    <li>Mengurangi kerutan halus hingga sedang.</li>
                    <li>Menstimulasi produksi kolagen di semua lapisan target.</li>
                    <li>Hasil bertahap dengan tampilan lebih muda dan segar.</li>
                </ol>

                <p><strong>Risiko dan Komplikasi:</strong> Meskipun aman, risiko yang mungkin terjadi meliputi:</p>
                <ol>
                    <li>Kemerahan, rasa panas, atau sensasi tertarik pasca tindakan.</li>
                    <li>Nyeri ringan pada area sensitif selama atau setelah prosedur.</li>
                    <li>Kekakuan otot wajah sementara.</li>
                    <li>Memar ringan atau pembengkakan.</li>
                    <li>Efek asimetris sementara bila kulit belum beradaptasi.</li>
                </ol>

                <p><strong>Alternatif:</strong> Saya mengetahui bahwa terdapat alternatif lain seperti tindakan radiofrekuensi, tanam benang, filler, ataupun facelift bedah. Setelah berkonsultasi, saya memilih prosedur HIFU 6D 3 layer sebagai solusi non-invasif yang sesuai kondisi kulit saya.</p>

                <p><strong>Persetujuan:</strong> Saya telah mendapatkan penjelasan tentang prosedur ini, manfaat, risiko, serta alternatifnya. Saya menyadari bahwa hasil dapat bervariasi antar individu dan membutuhkan waktu beberapa minggu untuk hasil maksimal. Saya menyetujui tindakan ini dilakukan secara sadar, sukarela, dan tanpa paksaan.</p>

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