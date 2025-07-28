<div class="container">
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT INJEKSI EXOSOME SKINBOOSTER</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Injeksi Exosome Skinbooster</h5>
            </div>
            <div class="card-body">
                <p>Saya yang bertanda tangan di bawah ini telah diberikan penjelasan mengenai prosedur tindakan Injeksi Exosome Skinbooster dan dengan ini menyatakan persetujuan untuk menjalani prosedur tersebut di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong> Injeksi Exosome Skinbooster adalah prosedur estetika yang menggunakan eksosom (exosome), yaitu partikel nano alami yang mengandung protein, faktor pertumbuhan, dan molekul bioaktif dari sel punca, yang disuntikkan ke dalam kulit untuk merangsang regenerasi sel, memperbaiki jaringan, serta meningkatkan kualitas kulit secara keseluruhan.</p>

                <p><strong>Manfaat:</strong></p>
                <ol>
                    <li>Merangsang regenerasi sel kulit dan produksi kolagen</li>
                    <li>Memperbaiki tekstur kulit, mengurangi garis halus dan kerutan</li>
                    <li>Meningkatkan elastisitas dan kecerahan kulit</li>
                    <li>Membantu peremajaan kulit secara menyeluruh</li>
                </ol>

                <p><strong>Risiko dan Komplikasi:</strong> Tindakan ini umumnya aman, namun seperti semua prosedur estetika, memiliki risiko, antara lain:</p>
                <ol>
                    <li>Kemerahan, bengkak, atau memar di area suntikan</li>
                    <li>Reaksi alergi terhadap bahan aktif (walau jarang terjadi)</li>
                    <li>Infeksi jika tidak dilakukan secara steril</li>
                    <li>Hasil tidak langsung terlihat dan bervariasi antar individu</li>
                </ol>

                <p><strong>Persetujuan:</strong> Saya telah menerima penjelasan yang memadai mengenai prosedur, termasuk manfaat, risiko, serta alternatif tindakan. Saya menyadari bahwa hasil tindakan dapat berbeda pada setiap individu dan tidak dapat dijamin. Saya menyatakan bahwa saya bersedia menjalani prosedur ini secara sukarela dan dapat menghentikan kapan saja jika diinginkan.</p>

                <p><strong>Penjelasan Risiko dan Manfaat:</strong> Saya telah diberikan waktu dan kesempatan untuk bertanya, dan semua pertanyaan saya telah dijawab dengan memuaskan oleh tenaga medis.</p>

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
