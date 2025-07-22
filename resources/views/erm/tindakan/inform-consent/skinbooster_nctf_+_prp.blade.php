<div class="container">
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT SKINBOOSTER NCTF + PRP</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Skinbooster NCTF + PRP</h5>
            </div>
            <div class="card-body">
                <p>Saya yang bertanda tangan di bawah ini, dengan ini menyatakan telah menerima penjelasan mengenai prosedur tindakan Skinbooster NCTF + PRP di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong> Skinbooster NCTF + PRP adalah prosedur estetika yang menggabungkan injeksi bahan aktif (seperti NCTF Filorga) yang mengandung vitamin, asam hialuronat, dan mineral penting, dengan PRP (Platelet Rich Plasma) yang berasal dari darah pasien sendiri. Tujuannya untuk meningkatkan kelembaban, elastisitas, dan kecerahan kulit.</p>

                <p><strong>Manfaat:</strong></p>
                <ol>
                    <li>Meningkatkan hidrasi dan kekenyalan kulit</li>
                    <li>Mencerahkan dan meratakan warna kulit</li>
                    <li>Memperbaiki tekstur kulit dan mengurangi garis halus</li>
                    <li>Stimulasi regenerasi sel kulit dengan faktor pertumbuhan dari PRP</li>
                </ol>

                <p><strong>Risiko dan Komplikasi:</strong> Walaupun prosedur ini umumnya aman, beberapa efek samping dapat terjadi, antara lain:</p>
                <ol>
                    <li>Kemerahan, bengkak, atau memar ringan di area suntikan</li>
                    <li>Reaksi alergi terhadap bahan aktif, meskipun jarang terjadi</li>
                    <li>Infeksi jika prosedur tidak dilakukan secara steril</li>
                    <li>Ketidaknyamanan atau nyeri ringan pada saat atau setelah tindakan</li>
                </ol>

                <p><strong>Persetujuan:</strong> Saya telah diberikan penjelasan yang cukup mengenai prosedur, manfaat, dan risikonya. Saya menyatakan memahami dan menerima bahwa hasil dari tindakan ini dapat bervariasi tergantung kondisi masing-masing individu. Saya juga mengetahui bahwa saya berhak untuk menghentikan atau menolak tindakan ini kapan saja.</p>

                <p><strong>Penjelasan Risiko dan Manfaat:</strong> Saya telah diberi kesempatan untuk bertanya dan semua pertanyaan saya telah dijawab dengan jelas dan memuaskan. Saya memahami dan menyetujui penjelasan yang diberikan oleh tenaga medis.</p>

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
