<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT WOMAN INJEKSI KAMASUTRA</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Woman Injeksi Kamasutra</h5>
            </div>
            <div class="card-body">
                <p>Saya yang bertanda tangan di bawah ini menyatakan telah mendapatkan penjelasan lengkap dan menyeluruh serta memberikan persetujuan untuk menjalani <strong>prosedur Woman Injeksi Kamasutra</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong>  
                Injeksi Kamasutra merupakan prosedur penyuntikan bahan tertentu (biasanya asam hialuronat, PRP, vitamin, atau bahan rejuvenasi lainnya) ke area genital wanita yang bertujuan untuk:</p>
                <ul>
                    <li>Meningkatkan elastisitas dan kelembapan area intim.</li>
                    <li>Membantu mengurangi kekeringan dan ketidaknyamanan.</li>
                    <li>Menunjang kepercayaan diri dan kenyamanan dalam hubungan seksual.</li>
                    <li>Memperbaiki sensitivitas area tertentu secara bertahap.</li>
                </ul>

                <p><strong>Manfaat:</strong></p>
                <ol>
                    <li>Meningkatkan kenyamanan dan kelembapan organ intim.</li>
                    <li>Membantu rejuvenasi jaringan area genital.</li>
                    <li>Mendukung kualitas hubungan seksual (secara psikologis dan fisiologis).</li>
                    <li>Efek dapat dirasakan bertahap sesuai respons tubuh masing-masing individu.</li>
                </ol>

                <p><strong>Risiko dan Komplikasi:</strong></p>
                <ol>
                    <li>Kemerahan atau bengkak ringan di area suntikan (sementara).</li>
                    <li>Rasa nyeri lokal sesaat setelah tindakan.</li>
                    <li>Infeksi atau iritasi (jarang, jika tidak dilakukan dengan prosedur steril).</li>
                    <li>Reaksi alergi terhadap bahan yang digunakan (sangat jarang).</li>
                    <li>Efek yang berbeda-beda antar individu, tergantung kondisi anatomi dan hormonal.</li>
                </ol>

                <p><strong>Alternatif:</strong> Saya memahami bahwa terdapat alternatif lain seperti penggunaan krim, terapi hormonal, PRP vaginal, atau tidak menjalani tindakan sama sekali.</p>

                <p><strong>Persetujuan:</strong> Saya telah mendapatkan penjelasan lengkap dan memadai, telah bertanya, dan memahami seluruh aspek tindakan ini. Saya menyadari bahwa hasil dari prosedur tidak permanen dan dapat berbeda pada setiap orang. Saya menyatakan setuju untuk menjalani tindakan ini dengan sadar dan tanpa tekanan.</p>

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