<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT HAIR TREATMENT CREAMBATH DIAMOND</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Hair Treatment Creambath Diamond</h5>
            </div>
            <div class="card-body">
                <p>Saya yang bertanda tangan di bawah ini memberikan persetujuan untuk menjalani tindakan <strong>Hair Treatment Creambath Diamond</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong>  
                Creambath Diamond adalah perawatan rambut menggunakan krim khusus yang mengandung nutrisi aktif dan bahan premium untuk memperkuat akar rambut, meningkatkan kelembapan, dan memberikan kilau sehat. Perawatan ini juga dilengkapi dengan pijatan kulit kepala untuk meningkatkan sirkulasi darah dan relaksasi.</p>

                <p><strong>Manfaat:</strong></p>
                <ol>
                    <li>Memberikan nutrisi pada rambut dan kulit kepala.</li>
                    <li>Membantu mengurangi rambut kusam dan kering.</li>
                    <li>Menjadikan rambut lebih halus, lembut, dan berkilau.</li>
                    <li>Memberikan efek relaksasi melalui pijatan lembut kulit kepala.</li>
                </ol>

                <p><strong>Risiko dan Efek Samping:</strong></p>
                <ol>
                    <li>Reaksi alergi ringan terhadap bahan aktif pada krim (jarang terjadi).</li>
                    <li>Kulit kepala berminyak atau rambut lepek jika tidak dibilas dengan benar.</li>
                    <li>Efek bersifat sementara dan memerlukan perawatan rutin untuk hasil optimal.</li>
                </ol>

                <p><strong>Alternatif:</strong> Saya memahami bahwa tersedia pilihan perawatan lain seperti hair mask, spa keratin, atau tidak menjalani tindakan ini.</p>

                <p><strong>Persetujuan:</strong> Saya telah menerima penjelasan yang cukup mengenai tindakan ini, termasuk manfaat, risiko, dan alternatifnya. Saya menyatakan bahwa saya memberikan persetujuan untuk menjalani prosedur ini secara sadar tanpa paksaan dari pihak mana pun.</p>

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