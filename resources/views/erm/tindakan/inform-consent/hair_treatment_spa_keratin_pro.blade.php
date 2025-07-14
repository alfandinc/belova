<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT HAIR TREATMENT SPA KERATIN PRO</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Hair Treatment Spa Keratin Pro</h5>
            </div>
            <div class="card-body">
                <p>Saya yang bertanda tangan di bawah ini memberikan persetujuan untuk menjalani tindakan <strong>Hair Treatment Spa Keratin Pro</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong>  
                Hair Spa Keratin Pro adalah perawatan intensif menggunakan keratin dengan formula profesional yang dirancang untuk memperbaiki dan menutrisi rambut yang sangat rusak akibat styling, pewarnaan, pelurusan, atau paparan sinar matahari secara terus-menerus. Prosedur ini membantu mengisi keratin yang hilang agar rambut kembali sehat dan kuat.</p>

                <p><strong>Manfaat:</strong></p>
                <ol>
                    <li>Memperbaiki dan menguatkan rambut rusak berat dan rapuh.</li>
                    <li>Meningkatkan kelembapan alami rambut dan mengurangi kekeringan.</li>
                    <li>Menjadikan rambut lebih halus, lembut, dan mudah diatur.</li>
                    <li>Melindungi rambut dari kerusakan akibat panas atau kimia.</li>
                </ol>

                <p><strong>Risiko dan Efek Samping:</strong></p>
                <ol>
                    <li>Iritasi ringan pada kulit kepala (jarang terjadi).</li>
                    <li>Rambut terasa berat atau lepek jika tidak dibilas sesuai instruksi.</li>
                    <li>Perlu dilakukan secara berkala untuk mempertahankan hasil optimal.</li>
                </ol>

                <p><strong>Alternatif:</strong> Saya memahami bahwa terdapat pilihan lain seperti Hair Mask, Hair Filler, atau tidak menjalani perawatan apa pun.</p>

                <p><strong>Persetujuan:</strong> Saya telah diberikan informasi dan penjelasan yang memadai mengenai tindakan Hair Treatment Spa Keratin Pro ini. Saya memahami manfaat, risiko, serta alternatifnya dan dengan ini menyatakan persetujuan secara sadar dan tanpa paksaan untuk menjalani prosedur tersebut.</p>

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