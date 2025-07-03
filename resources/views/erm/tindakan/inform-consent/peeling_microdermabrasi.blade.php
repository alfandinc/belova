<div class="container">
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">
        
        <h4 class="text-center mb-4">INFORMED CONSENT PEELING MICRODERMABRASI</h4>
        
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
                <h5 class="mb-0">Persetujuan Tindakan Peeling Microdermabrasi</h5>
            </div>
            <div class="card-body">
                <p>Saya, yang bertanda tangan di bawah ini, dengan ini menyetujui untuk menjalani perawatan Microdermabrasi di Klinik Pratama Belova Skin & Beauty Center.</p>
                <p>Saya mengerti bahwa Microdermabrasi adalah prosedur perawatan yang melibatkan pengelupasan lapisan kulit terluar (kulit mati) menggunakan alat khusus, yang dapat menjadikan kulit yang lebih halus dan meningkatkan kolagenisasi. Tujuan dari perawatan ini adalah untuk memperbaiki penampilan kulit di wajah, leher, atau tangan.</p>
                <p>Saya mengerti bahwa manfaat Microdermabrasi diantaranya termasuk :</p>
                <ol>
                    <li>Kulit lebih halus dan kencang</li>
                    <li>Mengurangi munculnya garis-garis halus dan kerutan</li>
                    <li>Memperbaiki tekstur dan warna tidak merata pada kulit</li>
                </ol>
                <p>Saya juga memahami bahwa ada risiko dan efek samping tertentu yang terkait dengan Microdermabrasi, yang mungkin termasuk tetapi tidak terbatas diantaranya :</p>
                <ol>
                    <li>Kemerahan, bengkak, dan iritasi pada kulit</li>
                    <li>Perubahan warna kulit, termasuk penggelapan atau pencerahan kulit</li>
                    <li>Terbentuk jaringan parut</li>
                    <li>Reaksi alergi terhadap larutan kimia</li>
                </ol>
                <p>Saya mengerti bahwa hasil Microdermabrasi tidak dijamin dan dapat bervariasi pada tiap orang. Saya telah diberitahu tentang pengobatan alternatif dan memahami bahwa saya memiliki hak untuk menolak atau menghentikan pengobatan setiap saat.</p>
                <p>Saya memiliki kesempatan untuk mengajukan pertanyaan tentang perawatan Microdermabrasi, dan semua pertanyaan saya telah dijawab dengan memuaskan. Saya memahami risiko, manfaat, dan alternatif yang terkait dengan perawatan ini, dan saya setuju untuk menjalani prosedur.</p>
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
