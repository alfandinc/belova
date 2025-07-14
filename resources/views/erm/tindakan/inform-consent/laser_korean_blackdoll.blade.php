<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT LASER KOREAN BLACKDOLL</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Laser Korean Blackdoll</h5>
            </div>
            <div class="card-body">
                <p>Saya, yang bertanda tangan di bawah ini, dengan ini memberikan persetujuan untuk menjalani tindakan <strong>Laser Korean Blackdoll</strong> (Carbon Laser Peel) di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong> Laser Korean Blackdoll adalah prosedur perawatan wajah non-invasif yang menggunakan masker karbon (blackdoll) yang diaplikasikan ke kulit, kemudian disinari dengan laser Q-switched. Tujuannya adalah untuk membersihkan pori-pori, mencerahkan wajah, dan meremajakan kulit dengan efek pengelupasan ringan dan stimulasi kolagen.</p>

                <p><strong>Manfaat:</strong> Beberapa manfaat dari tindakan ini adalah:</p>
                <ol>
                    <li>Membersihkan pori-pori secara mendalam dan mengangkat sel kulit mati.</li>
                    <li>Mengurangi produksi minyak berlebih pada wajah.</li>
                    <li>Mencerahkan kulit dan menyamarkan noda hitam atau bekas jerawat.</li>
                    <li>Meratakan warna kulit dan meningkatkan elastisitas kulit.</li>
                    <li>Mengurangi tampilan komedo dan jerawat ringan.</li>
                </ol>

                <p><strong>Risiko dan Komplikasi:</strong> Prosedur ini umumnya aman, namun tetap memiliki kemungkinan risiko, seperti:</p>
                <ol>
                    <li>Kemerahan ringan atau rasa hangat sementara di kulit.</li>
                    <li>Iritasi atau kekeringan kulit pasca tindakan.</li>
                    <li>Reaksi alergi terhadap karbon lotion atau gel pendingin.</li>
                    <li>Perubahan warna kulit (jarang terjadi).</li>
                    <li>Risiko infeksi jika kulit tidak dijaga kebersihannya setelah tindakan.</li>
                </ol>

                <p><strong>Alternatif:</strong> Alternatif tindakan ini termasuk facial biasa, chemical peeling, atau prosedur laser lain sesuai kebutuhan kulit. Saya telah berkonsultasi dan menerima informasi mengenai alternatif yang sesuai dari dokter/terapis.</p>

                <p><strong>Persetujuan:</strong> Saya menyatakan bahwa saya telah menerima penjelasan secara lengkap dan jelas mengenai prosedur ini, termasuk manfaat, risiko, dan alternatif yang tersedia. Saya juga diberikan kesempatan untuk bertanya dan semua pertanyaan saya telah dijawab dengan memuaskan.</p>

                <p>Saya memahami bahwa hasil perawatan dapat bervariasi antara individu, dan saya memberikan persetujuan ini secara sukarela. Saya juga memahami bahwa saya memiliki hak untuk menolak atau menghentikan tindakan kapan saja.</p>

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