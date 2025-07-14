<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT ADDS ON – BOTOX MICRO</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Tambahan – Botox Micro</h5>
            </div>
            <div class="card-body">
                <p>Saya yang bertanda tangan di bawah ini memberikan persetujuan untuk menjalani tindakan tambahan berupa <strong>Botox Micro</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong> Botox Micro adalah prosedur injeksi botulinum toxin dosis rendah yang disuntikkan secara dangkal ke lapisan atas kulit (intradermal). Berbeda dari Botox biasa yang menargetkan otot, Botox Micro bertujuan memperbaiki kualitas permukaan kulit dengan menyasar kelenjar sebasea dan keringat, serta garis halus.</p>

                <p><strong>Manfaat:</strong></p>
                <ol>
                    <li>Mengurangi produksi minyak berlebih pada wajah.</li>
                    <li>Mengecilkan tampilan pori-pori.</li>
                    <li>Menjadikan kulit tampak lebih halus dan glowing.</li>
                    <li>Mengurangi garis halus di area wajah tertentu.</li>
                    <li>Efek menyegarkan dan memperbaiki tekstur kulit.</li>
                </ol>

                <p><strong>Risiko dan Komplikasi:</strong></p>
                <ol>
                    <li>Kemerahan dan bengkak ringan pada area suntikan (sementara).</li>
                    <li>Memar kecil atau sensasi tertarik pada kulit beberapa hari setelah tindakan.</li>
                    <li>Reaksi alergi terhadap bahan (jarang).</li>
                    <li>Efek terlalu kering pada area berminyak (bila overdosis – sangat jarang).</li>
                </ol>

                <p><strong>Alternatif:</strong> Saya memahami bahwa ada alternatif lain seperti perawatan skincare, laser resurfacing, atau tidak melakukan tindakan tambahan ini. Saya memilih untuk menerima prosedur Botox Micro sebagai tindakan penunjang dari perawatan utama saya.</p>

                <p><strong>Persetujuan:</strong> Saya telah dijelaskan mengenai manfaat, risiko, dan alternatif tindakan ini. Saya menyadari bahwa efek Botox Micro bersifat sementara (2–4 bulan) dan dapat bervariasi antara individu. Saya memberikan persetujuan secara sadar, tanpa tekanan, dan atas kemauan sendiri.</p>

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
