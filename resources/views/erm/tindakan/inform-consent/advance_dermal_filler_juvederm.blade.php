<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT ADVANCE DERMAL FILLER – JUVEDERM</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Advance Dermal Filler – Juvederm</h5>
            </div>
            <div class="card-body">
                <p>Saya yang bertanda tangan di bawah ini menyatakan persetujuan untuk menjalani tindakan <strong>Dermal Filler menggunakan produk Juvederm</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong> Juvederm adalah dermal filler berbasis <em>Hyaluronic Acid (HA)</em> yang digunakan untuk menambah volume, mengisi garis halus/kerutan, dan memperbaiki kontur wajah. Produk ini disuntikkan ke area wajah tertentu seperti pipi, dagu, hidung, bibir, atau area bawah mata sesuai indikasi.</p>

                <p><strong>Manfaat:</strong></p>
                <ol>
                    <li>Menambah volume pada area wajah yang mengalami penurunan akibat penuaan.</li>
                    <li>Mengisi garis halus, kerutan, atau cekungan pada wajah.</li>
                    <li>Mengoreksi kontur wajah seperti dagu, hidung, atau pipi.</li>
                    <li>Memberikan efek peremajaan wajah secara langsung dan alami.</li>
                    <li>Produk aman, biodegradable, dan terserap oleh tubuh seiring waktu.</li>
                </ol>

                <p><strong>Risiko dan Komplikasi:</strong></p>
                <ol>
                    <li>Nyeri, kemerahan, atau bengkak pada area suntikan.</li>
                    <li>Memar ringan yang akan hilang dalam beberapa hari.</li>
                    <li>Asimetri atau hasil tidak merata jika distribusi filler tidak seimbang.</li>
                    <li>Risiko penyumbatan pembuluh darah jika penyuntikan tidak sesuai teknik.</li>
                    <li>Reaksi alergi atau pembentukan granuloma (sangat jarang).</li>
                </ol>

                <p><strong>Alternatif:</strong> Saya telah mendapatkan penjelasan mengenai alternatif seperti filler merek lain (Restylane, Belotero), tanam benang, atau tindakan bedah. Saya memilih Juvederm setelah berkonsultasi dan memahami keunggulannya dalam hasil yang natural dan teknologi Vycross/Ultra yang dimiliki produk ini.</p>

                <p><strong>Persetujuan:</strong> Saya menyatakan telah memahami dengan jelas manfaat, risiko, serta alternatif tindakan. Saya menyadari bahwa hasil bisa berbeda antar individu dan bersifat sementara (6–18 bulan tergantung area dan jenis produk). Saya memberikan persetujuan dengan sadar, sukarela, dan tanpa paksaan.</p>

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