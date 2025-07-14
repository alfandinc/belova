<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT SLIMMING FAT INJECTION – MESOCOCKTAIL / AMP</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Injeksi Slimming Fat Mesococktail</h5>
            </div>
            <div class="card-body">
                <p>Saya yang bertanda tangan di bawah ini memberikan persetujuan untuk menjalani prosedur <strong>injeksi pelarut lemak (slimming fat injection) menggunakan Mesococktail / ampul</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong> Injeksi pelarut lemak dengan Mesococktail adalah prosedur penyuntikan bahan aktif ke lapisan lemak subkutan yang bertujuan untuk menghancurkan sel-sel lemak lokal. Mesococktail terdiri dari kombinasi zat seperti phosphatidylcholine, deoxycholate, L-carnitine, caffeine, atau vitamin, dan diberikan sesuai dengan evaluasi medis.</p>

                <p><strong>Manfaat:</strong></p>
                <ol>
                    <li>Mengurangi timbunan lemak lokal (dagu, pipi, lengan, perut, paha, dll).</li>
                    <li>Membentuk kontur tubuh dan wajah menjadi lebih proporsional.</li>
                    <li>Alternatif non-bedah untuk perampingan area spesifik.</li>
                    <li>Efek bertahap dan alami jika dilakukan dengan dosis yang tepat.</li>
                </ol>

                <p><strong>Risiko dan Komplikasi:</strong></p>
                <ol>
                    <li>Nyeri, kemerahan, atau bengkak ringan pada area suntikan.</li>
                    <li>Memar atau reaksi inflamasi lokal.</li>
                    <li>Reaksi alergi terhadap salah satu bahan dalam mesococktail.</li>
                    <li>Asimetri atau hasil yang tidak rata (biasanya dapat diperbaiki dengan sesi lanjutan).</li>
                    <li>Efek tidak maksimal jika tidak diikuti pola hidup sehat.</li>
                </ol>

                <p><strong>Alternatif:</strong> Saya telah dijelaskan alternatif lain seperti tindakan HIFU, cryolipolysis (coolsculpting), olahraga dan diet, atau tidak melakukan tindakan sama sekali. Saya memilih injeksi pelarut lemak ini setelah pertimbangan bersama tenaga medis.</p>

                <p><strong>Persetujuan:</strong> Saya menyadari bahwa hasil tidak instan, memerlukan beberapa sesi, dan tergantung kondisi metabolisme tubuh saya. Saya telah mendapatkan penjelasan yang lengkap, memiliki kesempatan untuk bertanya, dan menyetujui prosedur ini secara sadar tanpa paksaan dari pihak mana pun.</p>

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