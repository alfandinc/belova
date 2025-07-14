<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT BODY SPA 60 MENIT</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Body SPA 60 Menit</h5>
            </div>
            <div class="card-body">
                <p>Saya yang bertanda tangan di bawah ini memberikan persetujuan untuk menjalani tindakan <strong>Body SPA selama 60 menit</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong>  
                Body SPA adalah prosedur perawatan tubuh menyeluruh selama kurang lebih 60 menit yang mencakup pemijatan, pengangkatan sel kulit mati (eksfoliasi), pelembapan kulit, serta teknik relaksasi dengan tujuan meningkatkan kesehatan kulit, peredaran darah, dan ketenangan pikiran.</p>

                <p><strong>Manfaat:</strong></p>
                <ol>
                    <li>Mengurangi stres dan memberikan relaksasi menyeluruh.</li>
                    <li>Melancarkan peredaran darah dan mengurangi ketegangan otot.</li>
                    <li>Mengangkat sel kulit mati dan membuat kulit terasa lebih halus dan lembut.</li>
                    <li>Meningkatkan kelembapan dan elastisitas kulit.</li>
                </ol>

                <p><strong>Risiko dan Efek Samping:</strong></p>
                <ol>
                    <li>Kemerahan atau iritasi ringan pada kulit setelah eksfoliasi.</li>
                    <li>Reaksi alergi terhadap minyak atau produk spa tertentu.</li>
                    <li>Nyeri otot ringan setelah pemijatan pada area tertentu.</li>
                    <li>Tidak dianjurkan untuk pasien dengan kondisi medis tertentu (seperti hipertensi berat, luka terbuka, atau infeksi kulit).</li>
                </ol>

                <p><strong>Alternatif:</strong> Perawatan alternatif seperti pijat relaksasi biasa, body mask, atau body scrub tersedia dan dapat disesuaikan dengan kebutuhan serta kondisi pasien.</p>

                <p><strong>Persetujuan:</strong> Saya telah menerima informasi yang memadai mengenai tindakan ini, termasuk manfaat, risiko, dan alternatifnya. Saya memahami bahwa tindakan ini bersifat non-medis dan bertujuan untuk relaksasi serta perawatan kulit. Saya menyetujui prosedur ini dilakukan secara sadar dan sukarela.</p>

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