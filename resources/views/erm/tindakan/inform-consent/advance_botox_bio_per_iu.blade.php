<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT ADVANCE BOTOX – BIO PER IU</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Injeksi Botox (Bio Brand)</h5>
            </div>
            <div class="card-body">
                <p>Saya yang bertanda tangan di bawah ini memberikan persetujuan untuk menjalani tindakan <strong>injeksi botulinum toxin (Bio-brand)</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong> Botox adalah zat yang berasal dari toksin <em>Clostridium botulinum</em> yang disuntikkan dalam dosis kecil untuk mengendurkan otot-otot tertentu. Tindakan ini digunakan untuk mengurangi kerutan dinamis (misalnya di dahi, glabella, mata), membentuk kontur wajah (jaw slimming), atau mengatasi keringat berlebih (hiperhidrosis). Produk yang digunakan dalam prosedur ini adalah merek Bio, dihitung berdasarkan International Unit (IU).</p>

                <p><strong>Manfaat:</strong></p>
                <ol>
                    <li>Mengurangi kerutan ekspresi pada wajah.</li>
                    <li>Membuat wajah tampak lebih segar dan muda.</li>
                    <li>Meniruskan wajah (jaw slimming) bila disuntikkan ke otot masseter.</li>
                    <li>Mengontrol hiperhidrosis (keringat berlebih) pada ketiak atau telapak.</li>
                    <li>Efek bertahan 3–6 bulan tergantung dosis dan area.</li>
                </ol>

                <p><strong>Risiko dan Komplikasi:</strong></p>
                <ol>
                    <li>Kemerahan, nyeri ringan, atau memar di area suntikan.</li>
                    <li>Hasil sementara asimetris (dapat disesuaikan pada touch-up).</li>
                    <li>Efek samping ringan seperti sakit kepala, rasa berat di area wajah, atau turunnya kelopak mata (ptosis – jarang).</li>
                    <li>Tidak ada efek jika tubuh membentuk antibodi terhadap toksin (jarang).</li>
                    <li>Reaksi alergi (sangat jarang).</li>
                </ol>

                <p><strong>Alternatif:</strong> Saya telah diberi informasi mengenai alternatif lain seperti perawatan topikal (krim antiaging), filler, tanam benang, atau tidak melakukan tindakan sama sekali. Saya memilih Botox berdasarkan tujuan dan indikasi estetika pribadi.</p>

                <p><strong>Persetujuan:</strong> Saya telah menerima penjelasan yang cukup mengenai manfaat, risiko, dan alternatif dari tindakan ini. Saya menyadari bahwa hasil bersifat sementara dan dapat bervariasi antara individu. Saya menyetujui prosedur ini secara sadar, sukarela, dan tanpa tekanan.</p>

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
