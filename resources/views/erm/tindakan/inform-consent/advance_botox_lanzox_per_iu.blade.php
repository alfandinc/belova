<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT ADVANCE BOTOX – LANZOX PER IU</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Injeksi Botox – Lanzox</h5>
            </div>
            <div class="card-body">
                <p>Saya yang bertanda tangan di bawah ini memberikan persetujuan untuk menjalani tindakan <strong>injeksi botulinum toxin merek Lanzox</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong> Botox (botulinum toxin type A) adalah zat yang digunakan untuk mengendurkan otot tertentu pada wajah atau tubuh. Merek yang digunakan adalah <strong>Lanzox</strong>, dihitung berdasarkan satuan internasional (IU). Tindakan ini bertujuan untuk memperbaiki penampilan estetika seperti mengurangi kerutan ekspresi, melangsingkan otot rahang (masseter), atau mengatasi kondisi seperti keringat berlebih (hiperhidrosis).</p>

                <p><strong>Manfaat:</strong></p>
                <ol>
                    <li>Mengurangi kerutan ekspresi di dahi, mata (crow’s feet), dan glabella (antara alis).</li>
                    <li>Menghaluskan tampilan wajah yang tegang atau lelah.</li>
                    <li>Membentuk wajah lebih simetris (misalnya jaw slimming).</li>
                    <li>Efek cepat, hasil alami jika dilakukan dengan teknik dan dosis yang tepat.</li>
                    <li>Bisa digunakan untuk indikasi non-estetik seperti migrain, bruxism, atau keringat berlebih.</li>
                </ol>

                <p><strong>Risiko dan Komplikasi:</strong></p>
                <ol>
                    <li>Nyeri, kemerahan, atau memar di titik suntikan.</li>
                    <li>Efek sementara seperti kelopak mata turun (ptosis) atau asimetri ekspresi wajah.</li>
                    <li>Reaksi alergi atau sensitivitas (jarang terjadi).</li>
                    <li>Resistensi atau tidak adanya efek jika antibodi terbentuk.</li>
                    <li>Efek melemahkan otot yang tidak diinginkan jika penyuntikan tidak tepat.</li>
                </ol>

                <p><strong>Alternatif:</strong> Saya telah mendapatkan penjelasan mengenai pilihan alternatif seperti botox merek lain (Bio, Nabota, Xeomin), filler, tanam benang, RF/HIFU, atau tidak melakukan tindakan. Saya memilih merek Lanzox berdasarkan kebutuhan dan pertimbangan biaya serta efektivitasnya.</p>

                <p><strong>Persetujuan:</strong> Saya telah menerima penjelasan yang jelas dan lengkap dari tenaga medis mengenai prosedur ini. Saya menyadari bahwa hasil dapat bervariasi dan bersifat sementara (sekitar 3–6 bulan). Dengan ini saya menyatakan setuju untuk menjalani tindakan tanpa paksaan dan secara sadar.</p>

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