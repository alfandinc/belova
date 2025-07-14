<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT PAKET COLLAGEN STIMULATOR WITH FACETEM</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Collagen Stimulator & Facetem</h5>
            </div>
            <div class="card-body">
                <p>Saya yang bertanda tangan di bawah ini memberikan persetujuan untuk menjalani <strong>Paket Collagen Stimulator with Facetem</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong><br>
                Tindakan ini menggabungkan dua prosedur:
                <ul>
                    <li><strong>Collagen Stimulator:</strong> Injeksi bahan aktif (misalnya polinukleotida/PDRN atau sejenis) yang merangsang produksi kolagen dalam kulit untuk memperbaiki tekstur, elastisitas, dan hidrasi kulit secara alami.</li>
                    <li><strong>Facetem:</strong> Alat teknologi Radiofrequency (RF) untuk pengencangan kulit secara non-invasif melalui pemanasan lapisan dalam kulit, merangsang pembentukan kolagen baru, dan memperbaiki kontur wajah.</li>
                </ul></p>

                <p><strong>Manfaat:</strong></p>
                <ol>
                    <li>Meningkatkan elastisitas dan kekencangan kulit.</li>
                    <li>Memperbaiki tekstur kulit dan mengurangi garis halus.</li>
                    <li>Memberikan efek lifting wajah secara alami tanpa operasi.</li>
                    <li>Merangsang produksi kolagen baru secara berkelanjutan.</li>
                </ol>

                <p><strong>Risiko dan Efek Samping:</strong></p>
                <ol>
                    <li>Kemerahan ringan, bengkak, atau memar pada area tindakan (sementara).</li>
                    <li>Rasa hangat atau kesemutan pada saat tindakan Facetem.</li>
                    <li>Risiko infeksi ringan pada area suntikan jika tidak dijaga kebersihannya.</li>
                    <li>Reaksi alergi terhadap bahan stimulan kolagen (sangat jarang).</li>
                </ol>

                <p><strong>Alternatif:</strong><br>
                Saya telah mendapatkan penjelasan bahwa terdapat alternatif lain seperti perawatan HIFU, skin booster, atau tindakan facial rejuvenation non-invasif lainnya, dan telah memilih paket ini setelah berkonsultasi.</p>

                <p><strong>Persetujuan:</strong><br>
                Saya telah membaca dan memahami seluruh penjelasan yang diberikan, termasuk manfaat, risiko, dan alternatif tindakan. Saya menyadari bahwa hasil akan bervariasi pada setiap individu dan dapat memerlukan perawatan berkelanjutan untuk hasil optimal.</p>

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