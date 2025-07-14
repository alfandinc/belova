<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT LIFTING THREADLIFT SILHOUETTE SOFT BIDIRECTIONAL – 1 THREAD</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Lifting Threadlift Silhouette Soft Bidirectional (1 Benang)</h5>
            </div>
            <div class="card-body">
                <p>Saya yang bertanda tangan di bawah ini menyatakan setuju untuk menjalani tindakan <strong>Threadlift Silhouette Soft Bidirectional (1 Thread)</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong> Silhouette Soft adalah benang bioabsorbable berbahan <em>Polylactic Acid (PLA)</em> dengan cone dua arah (bidirectional cone) yang memberikan efek lifting langsung dan kolagenesis jangka panjang. Tindakan ini menggunakan satu benang sesuai indikasi area wajah tertentu (seperti pipi, rahang, atau alis).</p>

                <p><strong>Manfaat:</strong></p>
                <ol>
                    <li>Efek tarik langsung untuk mengangkat kulit wajah yang kendur.</li>
                    <li>Stimulasi produksi kolagen selama beberapa bulan setelah tindakan.</li>
                    <li>Perbaikan kontur wajah secara natural dan progresif.</li>
                    <li>Prosedur minim invasif tanpa bedah.</li>
                    <li>Efek awet hingga 12–18 bulan (tergantung kondisi kulit dan gaya hidup).</li>
                </ol>

                <p><strong>Risiko dan Komplikasi:</strong></p>
                <ol>
                    <li>Kemerahan, bengkak, atau memar ringan di area tindakan.</li>
                    <li>Asimetri atau rasa tidak nyaman pada sisi penanaman.</li>
                    <li>Risiko infeksi lokal atau granuloma jika perawatan pascatindakan tidak sesuai.</li>
                    <li>Benang atau cone terasa selama masa adaptasi awal.</li>
                    <li>Reaksi inflamasi ringan atau gatal karena proses stimulasi kolagen.</li>
                </ol>

                <p><strong>Alternatif:</strong> Saya telah diberi penjelasan tentang metode alternatif seperti tanam benang PDO Barb, HIFU, RF, filler, atau facelift. Berdasarkan konsultasi dengan dokter/terapis, saya memilih Silhouette Soft sebagai tindakan yang sesuai dengan kebutuhan estetika saya.</p>

                <p><strong>Persetujuan:</strong> Saya menyatakan telah memahami seluruh penjelasan terkait prosedur, manfaat, risiko, dan alternatifnya. Saya menyadari bahwa hasil bervariasi antar individu dan dapat berubah tergantung respons tubuh. Saya memberikan persetujuan secara sadar dan tanpa tekanan.</p>

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