<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT SLIMMING TREATMENT AKUPUNKTUR</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Slimming Treatment Akupunktur</h5>
            </div>
            <div class="card-body">
                <p>Saya yang bertanda tangan di bawah ini memberikan persetujuan untuk menjalani tindakan <strong>Slimming Treatment Akupunktur</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong>  
                Akupunktur pelangsingan adalah metode pengobatan tradisional Tiongkok yang menggunakan jarum halus yang ditusukkan pada titik-titik tertentu di tubuh untuk membantu menyeimbangkan metabolisme, mengurangi nafsu makan, serta mendukung pembakaran lemak dan pengurangan berat badan secara bertahap.</p>

                <p><strong>Manfaat:</strong></p>
                <ol>
                    <li>Menstimulasi metabolisme tubuh.</li>
                    <li>Membantu mengontrol nafsu makan dan keinginan makan berlebih.</li>
                    <li>Meningkatkan pencernaan dan sistem eliminasi.</li>
                    <li>Membantu mengurangi berat badan secara alami jika dilakukan secara konsisten.</li>
                </ol>

                <p><strong>Risiko dan Komplikasi:</strong></p>
                <ol>
                    <li>Nyeri ringan atau rasa geli saat jarum dimasukkan.</li>
                    <li>Kemungkinan memar atau perdarahan ringan di area tusukan.</li>
                    <li>Infeksi lokal jika tidak dilakukan dengan prosedur steril (telah dicegah dengan SOP klinik).</li>
                    <li>Pusing atau lelah setelah tindakan (jarang dan bersifat sementara).</li>
                </ol>

                <p><strong>Alternatif:</strong> Saya telah dijelaskan bahwa alternatif lain meliputi program diet nutrisi, olahraga, atau metode pelangsingan lain seperti mesoterapi atau infus pelangsing.</p>

                <p><strong>Persetujuan:</strong> Saya menyatakan bahwa saya telah diberikan informasi secara lengkap mengenai manfaat, risiko, dan alternatif tindakan ini. Saya memahami bahwa hasil dari akupunktur bersifat individual dan tidak dapat dijamin. Saya menyetujui untuk menjalani tindakan ini secara sadar dan sukarela.</p>

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