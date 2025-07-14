<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT LIFTING THREADLIFT FACELIFTING – 2 THREADS</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Lifting Threadlift Facelifting (2 Benang)</h5>
            </div>
            <div class="card-body">
                <p>Saya yang bertanda tangan di bawah ini menyatakan setuju untuk menjalani tindakan <strong>Threadlift Facelifting menggunakan 2 benang</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong> Tindakan ini merupakan prosedur tanam benang tarik (lifting thread) di area wajah untuk meningkatkan kekencangan kulit secara langsung serta menstimulasi produksi kolagen. Jenis benang yang digunakan bersifat bioabsorbable dan aman diserap tubuh dalam beberapa bulan. Dua benang akan ditanam secara simetris di area wajah sesuai indikasi estetika.</p>

                <p><strong>Manfaat:</strong></p>
                <ol>
                    <li>Mengangkat jaringan kulit wajah yang mulai kendur.</li>
                    <li>Mengencangkan kontur wajah dengan hasil yang natural.</li>
                    <li>Stimulasi kolagen jangka panjang.</li>
                    <li>Memberikan efek awet muda tanpa operasi.</li>
                    <li>Waktu pemulihan lebih singkat dibanding tindakan bedah.</li>
                </ol>

                <p><strong>Risiko dan Komplikasi:</strong></p>
                <ol>
                    <li>Nyeri ringan, bengkak, atau memar sementara di lokasi tanam benang.</li>
                    <li>Rasa tertarik atau tidak nyaman selama proses adaptasi kulit.</li>
                    <li>Risiko infeksi lokal jika tidak dijaga kebersihannya.</li>
                    <li>Benang terasa atau terlihat sementara di bawah kulit.</li>
                    <li>Asimetri ringan bila penyembuhan tidak merata.</li>
                </ol>

                <p><strong>Alternatif:</strong> Saya telah diberi penjelasan tentang tindakan lain seperti tanam benang jenis berbeda (Mono, Barb, Cog, Silhouette), HIFU, RF, filler, atau facelift. Berdasarkan konsultasi, saya memilih tindakan ini sesuai kebutuhan estetika saya.</p>

                <p><strong>Persetujuan:</strong> Saya menyatakan telah memahami dengan jelas manfaat, risiko, dan alternatif dari prosedur ini. Saya menyadari bahwa hasil dapat bervariasi antar individu. Dengan ini saya menyetujui tindakan dilakukan secara sadar dan sukarela tanpa paksaan.</p>

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