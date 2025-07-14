<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT LASER KOREAN</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Laser Korean</h5>
            </div>
            <div class="card-body">
                <p>Saya, yang bertanda tangan di bawah ini, dengan ini memberikan persetujuan untuk menjalani tindakan <strong>Laser Korean</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong> Laser Korean adalah prosedur non-invasif menggunakan teknologi laser berbasis karbon dan sinar laser Q-switched untuk membantu mencerahkan kulit, mengecilkan pori, mengurangi minyak berlebih, serta memperbaiki tekstur kulit secara keseluruhan.</p>

                <p><strong>Manfaat:</strong> Prosedur ini memiliki berbagai manfaat, antara lain:</p>
                <ol>
                    <li>Mencerahkan kulit secara merata.</li>
                    <li>Mengecilkan pori-pori yang membesar.</li>
                    <li>Mengurangi minyak berlebih pada wajah.</li>
                    <li>Meningkatkan elastisitas dan tekstur kulit.</li>
                    <li>Mengurangi tampilan noda hitam dan pigmentasi ringan.</li>
                </ol>

                <p><strong>Risiko dan Komplikasi:</strong> Tindakan ini tergolong aman, namun dapat menimbulkan efek samping seperti:</p>
                <ol>
                    <li>Kemerahan ringan atau rasa hangat setelah prosedur.</li>
                    <li>Iritasi atau kekeringan pada kulit (sementara).</li>
                    <li>Hiperpigmentasi atau hipopigmentasi (jarang).</li>
                    <li>Reaksi alergi terhadap karbon lotion (jika digunakan).</li>
                    <li>Risiko infeksi bila tidak dilakukan perawatan pasca tindakan yang sesuai.</li>
                </ol>

                <p><strong>Alternatif:</strong> Alternatif dari tindakan ini termasuk facial biasa, chemical peeling, atau perawatan pencerah berbasis krim/topikal. Saya telah berkonsultasi dengan dokter atau terapis mengenai opsi terbaik sesuai kondisi kulit saya.</p>

                <p><strong>Persetujuan:</strong> Saya telah menerima penjelasan secara lengkap dan jelas mengenai tindakan ini, termasuk manfaat, risiko, dan alternatif yang tersedia. Saya juga telah diberi kesempatan untuk mengajukan pertanyaan dan memahami bahwa hasil tindakan dapat berbeda untuk setiap individu.</p>

                <p>Saya menyatakan bahwa saya memberikan persetujuan ini secara sukarela dan dapat menghentikan tindakan kapan saja bila merasa tidak nyaman.</p>

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