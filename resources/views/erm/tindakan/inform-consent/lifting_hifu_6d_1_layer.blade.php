<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT LIFTING HIFU 6D – 1 LAYER</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Lifting HIFU 6D (1 Layer)</h5>
            </div>
            <div class="card-body">
                <p>Saya, yang bertanda tangan di bawah ini, memberikan persetujuan untuk menjalani prosedur <strong>HIFU 6D (1 Layer)</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong> HIFU (High-Intensity Focused Ultrasound) adalah teknologi pengencangan wajah tanpa bedah yang menggunakan gelombang ultrasound berintensitas tinggi dan terfokus untuk menstimulasi produksi kolagen pada lapisan kulit dalam (SMAS layer). Prosedur 1 layer berarti tindakan dilakukan pada satu kedalaman target sesuai kondisi kulit.</p>

                <p><strong>Manfaat:</strong> HIFU 6D 1 Layer dapat memberikan manfaat berikut:</p>
                <ol>
                    <li>Mengencangkan kulit wajah dan leher tanpa operasi.</li>
                    <li>Mengurangi kerutan halus dan garis ekspresi.</li>
                    <li>Memberikan efek lifting secara bertahap dan alami.</li>
                    <li>Menstimulasi produksi kolagen dan elastin dari dalam.</li>
                    <li>Meningkatkan kontur wajah dan tampilan kulit lebih kencang.</li>
                </ol>

                <p><strong>Risiko dan Efek Samping:</strong> Meskipun termasuk prosedur aman dan non-invasif, efek samping yang mungkin terjadi antara lain:</p>
                <ol>
                    <li>Kemerahan atau rasa panas ringan setelah tindakan.</li>
                    <li>Nyeri atau rasa tertarik di bawah kulit (sementara).</li>
                    <li>Memar atau bengkak ringan di area sensitif.</li>
                    <li>Kesemutan atau sensasi geli pada saraf wajah (sementara).</li>
                    <li>Efek tidak merata bila tidak dilakukan sesuai protokol.</li>
                </ol>

                <p><strong>Alternatif:</strong> Saya telah diberi tahu bahwa prosedur ini memiliki alternatif seperti radiofrekuensi, benang tarik, filler, atau facelift. Saya memilih HIFU berdasarkan pertimbangan kondisi dan diskusi dengan dokter/terapis.</p>

                <p><strong>Persetujuan:</strong> Saya memahami bahwa hasil HIFU bersifat bertahap dan bervariasi pada setiap individu. Saya menyatakan bahwa saya telah mendapat penjelasan lengkap mengenai prosedur ini, risiko, manfaat, serta alternatifnya. Saya menyetujui tindakan ini dilakukan secara sadar dan tanpa paksaan.</p>

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