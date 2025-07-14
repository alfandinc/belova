<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT PAKET COLLAGEN STIMULATOR WITH HIFU & FACE FIRMING WITH MICROTOX</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Collagen Stimulator, HIFU, & Microtox</h5>
            </div>
            <div class="card-body">
                <p>Saya yang bertanda tangan di bawah ini memberikan persetujuan untuk menjalani <strong>Paket Collagen Stimulator with HIFU & Face Firming with Microtox</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong><br>
                Prosedur ini terdiri dari tiga komponen:
                <ul>
                    <li><strong>Collagen Stimulator:</strong> Injeksi bahan aktif (misalnya Sineson atau agen sejenis) yang berfungsi untuk merangsang produksi kolagen alami dan memperbaiki struktur kulit dari dalam.</li>
                    <li><strong>HIFU (High Intensity Focused Ultrasound):</strong> Teknologi ultrasound intensitas tinggi yang ditujukan ke lapisan SMAS (Superficial Musculo-Aponeurotic System) untuk memberikan efek lifting dan tightening.</li>
                    <li><strong>Microtox:</strong> Injeksi Botox mikro ke lapisan permukaan kulit untuk mengurangi tampilan pori-pori, menghaluskan kulit, dan memberi efek glow serta kencang alami tanpa membekukan ekspresi.</li>
                </ul></p>

                <p><strong>Manfaat:</strong></p>
                <ol>
                    <li>Stimulasi produksi kolagen untuk kulit lebih kenyal dan elastis.</li>
                    <li>Mengencangkan kontur wajah dan memperbaiki garis rahang.</li>
                    <li>Menghaluskan tekstur kulit dan mengurangi pori-pori.</li>
                    <li>Memberikan efek lifting alami tanpa operasi.</li>
                </ol>

                <p><strong>Risiko dan Efek Samping:</strong></p>
                <ol>
                    <li>Kemerahan, pembengkakan, atau memar ringan pasca injeksi (sementara).</li>
                    <li>Nyeri ringan selama atau setelah prosedur HIFU atau penyuntikan.</li>
                    <li>Efek tidak simetris atau hasil tidak merata (akan dievaluasi pasca tindakan).</li>
                    <li>Reaksi alergi terhadap bahan aktif (jarang).</li>
                    <li>Efek seperti kesemutan atau ketegangan sementara akibat HIFU (umumnya pulih spontan).</li>
                </ol>

                <p><strong>Alternatif:</strong><br>
                Saya telah diberi informasi mengenai pilihan alternatif seperti treatment RF, filler, skinbooster, atau tindakan lain non-invasif, dan telah memilih prosedur ini secara sadar setelah berdiskusi dengan dokter/terapis.</p>

                <p><strong>Persetujuan:</strong><br>
                Saya telah membaca dan memahami informasi yang diberikan terkait tindakan ini. Saya menyadari bahwa hasil bervariasi antar individu dan dapat membutuhkan beberapa sesi untuk hasil maksimal. Saya menyatakan setuju untuk menjalani prosedur ini secara sukarela.</p>

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