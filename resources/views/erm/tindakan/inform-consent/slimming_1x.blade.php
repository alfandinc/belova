<div class="container">  
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT SLIMMING 1X</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Slimming 1x</h5>
            </div>
            <div class="card-body">
                <p>Saya yang bertanda tangan di bawah ini memberikan persetujuan untuk menjalani tindakan <strong>Slimming 1x</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong>  
                Slimming 1x adalah prosedur perawatan tubuh untuk membantu mengurangi lemak dan bentuk tubuh secara lokal menggunakan metode non-invasif, yang dapat meliputi pijat khusus, teknologi ultrasonik, atau terapi lain sesuai protokol klinik.</p>

                <p><strong>Manfaat:</strong></p>
                <ol>
                    <li>Membantu mengurangi lemak lokal pada area yang ditargetkan.</li>
                    <li>Memperbaiki bentuk dan kontur tubuh.</li>
                    <li>Meningkatkan sirkulasi darah dan metabolisme lokal.</li>
                    <li>Memberikan efek kulit lebih kencang dan elastis.</li>
                </ol>

                <p><strong>Risiko dan Efek Samping:</strong></p>
                <ol>
                    <li>Rasa hangat atau kemerahan sementara pada area yang dirawat.</li>
                    <li>Kemerahan ringan atau memar (jarang).</li>
                    <li>Hasil dapat bervariasi tergantung kondisi tubuh individu.</li>
                    <li>Risiko iritasi kulit ringan bila kulit sensitif.</li>
                </ol>

                <p><strong>Alternatif:</strong> Saya memahami bahwa terdapat alternatif lain seperti diet, olahraga, atau perawatan medis lainnya untuk membantu mengurangi lemak dan membentuk tubuh.</p>

                <p><strong>Persetujuan:</strong> Saya telah mendapatkan penjelasan mengenai tindakan ini, termasuk manfaat, risiko, dan alternatifnya. Saya menyetujui untuk menjalani Slimming 1x secara sukarela dan sadar sepenuhnya.</p>

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
