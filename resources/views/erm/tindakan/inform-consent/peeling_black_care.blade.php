<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT PEELING BACK CARE</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Peeling Back Care</h5>
            </div>
            <div class="card-body">
                <p>Saya yang bertanda tangan di bawah ini memberikan persetujuan untuk menjalani tindakan <strong>Peeling Back Care</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong>  
                Peeling Back Care adalah prosedur yang dilakukan pada area punggung dengan menggunakan bahan kimia eksfoliasi untuk mengangkat sel-sel kulit mati, membantu merawat jerawat punggung, dan memperbaiki tampilan kulit agar tampak lebih cerah dan bersih.</p>

                <p><strong>Manfaat:</strong></p>
                <ol>
                    <li>Mengangkat sel kulit mati dan membersihkan pori-pori punggung.</li>
                    <li>Membantu mengurangi jerawat dan komedo di punggung.</li>
                    <li>Mencerahkan warna kulit yang kusam di area punggung.</li>
                    <li>Menjadikan kulit terasa lebih halus dan bersih.</li>
                </ol>

                <p><strong>Risiko dan Efek Samping:</strong></p>
                <ol>
                    <li>Rasa perih atau terbakar ringan selama atau setelah prosedur.</li>
                    <li>Kemerahan atau pengelupasan kulit sementara.</li>
                    <li>Reaksi alergi terhadap bahan peeling (jarang).</li>
                    <li>Hiperpigmentasi atau hipopigmentasi bila tidak menggunakan pelindung matahari dengan benar setelah tindakan.</li>
                </ol>

                <p><strong>Alternatif:</strong> Perawatan alternatif meliputi back facial, scrub punggung, terapi LED, atau perawatan topikal dengan bahan aktif yang diresepkan.</p>

                <p><strong>Persetujuan:</strong> Saya menyatakan telah menerima penjelasan secara menyeluruh mengenai prosedur ini, termasuk manfaat, risiko, dan alternatif tindakan. Saya mengerti hasil dapat bervariasi tergantung kondisi kulit dan menyetujui untuk menjalani tindakan ini secara sadar dan sukarela.</p>

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