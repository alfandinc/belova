<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT HAIR TREATMENT ROOT CARE</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Hair Treatment Root Care</h5>
            </div>
            <div class="card-body">
                <p>Saya yang bertanda tangan di bawah ini menyatakan telah mendapatkan penjelasan dan memberikan persetujuan untuk menjalani <strong>Hair Treatment Root Care</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong>  
                Hair Treatment Root Care adalah perawatan intensif yang difokuskan pada akar rambut dan kulit kepala dengan tujuan menyeimbangkan kondisi kulit kepala, mengatasi masalah seperti kerontokan, ketombe, minyak berlebih, atau kulit kepala kering, serta menstimulasi folikel rambut untuk pertumbuhan rambut baru. Produk yang digunakan mengandung bahan aktif seperti niacinamide, menthol, vitamin, atau ekstrak alami.</p>

                <p><strong>Manfaat:</strong></p>
                <ol>
                    <li>Mengurangi kerontokan rambut.</li>
                    <li>Menyeimbangkan kondisi kulit kepala (berminyak/kering).</li>
                    <li>Merangsang pertumbuhan rambut baru.</li>
                    <li>Mengurangi ketombe dan rasa gatal pada kulit kepala.</li>
                </ol>

                <p><strong>Risiko dan Efek Samping:</strong></p>
                <ol>
                    <li>Iritasi ringan atau kemerahan pada kulit kepala (sementara).</li>
                    <li>Reaksi alergi terhadap bahan aktif (jarang).</li>
                    <li>Efek tergantung kondisi kulit kepala dan konsistensi perawatan.</li>
                </ol>

                <p><strong>Alternatif:</strong> Saya mengetahui bahwa tersedia alternatif perawatan lain seperti PRP rambut, serum topikal, masker rambut, atau tidak menjalani tindakan apa pun.</p>

                <p><strong>Persetujuan:</strong> Saya menyatakan telah memahami penjelasan mengenai prosedur, manfaat, dan risiko dari tindakan Hair Treatment Root Care ini. Saya menyetujui prosedur ini secara sadar dan tanpa paksaan.</p>

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