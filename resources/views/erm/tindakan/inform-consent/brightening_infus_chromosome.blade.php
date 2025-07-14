<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT BRIGHTENING INFUS CHROMOSOME</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Brightening Infus Chromosome</h5>
            </div>
            <div class="card-body">
                <p>Saya, yang bertanda tangan di bawah ini, dengan ini memberikan persetujuan untuk menjalani tindakan <strong>Brightening Infus Chromosome</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong> Brightening Infus Chromosome merupakan prosedur pemberian infus intravena yang mengandung kombinasi zat aktif seperti <em>DNA repair enzyme, peptide complex, glutathione, vitamin C,</em> dan antioksidan tinggi. Tindakan ini dirancang untuk membantu proses regenerasi kulit dari dalam, memperbaiki kerusakan sel, serta memberikan efek pencerahan secara menyeluruh.</p>

                <p><strong>Manfaat:</strong> Infus Chromosome dapat memberikan manfaat antara lain:</p>
                <ol>
                    <li>Mencerahkan kulit secara merata dan meningkatkan skin tone.</li>
                    <li>Meningkatkan proses perbaikan sel kulit akibat paparan radikal bebas dan sinar UV.</li>
                    <li>Meningkatkan kelembapan, elastisitas, dan tekstur kulit.</li>
                    <li>Mempercepat pemulihan kulit pasca tindakan estetika lainnya.</li>
                    <li>Memberikan efek glowing dan sehat dari dalam.</li>
                </ol>

                <p><strong>Risiko dan Komplikasi:</strong> Seperti halnya terapi infus lainnya, tindakan ini dapat menimbulkan risiko berikut:</p>
                <ol>
                    <li>Kemerahan, nyeri ringan, atau memar di tempat penyuntikan infus.</li>
                    <li>Reaksi alergi terhadap salah satu kandungan infus (meskipun jarang).</li>
                    <li>Rasa pusing, mual, atau tidak nyaman selama atau setelah infus.</li>
                    <li>Infeksi lokal apabila prosedur tidak dilakukan secara steril (telah dicegah melalui SOP klinik).</li>
                    <li>Efek samping individual yang mungkin berbeda pada setiap pasien.</li>
                </ol>

                <p><strong>Alternatif:</strong> Saya memahami bahwa tersedia prosedur alternatif lain seperti infus whitening biasa, injeksi lokal, suplemen oral, atau perawatan topikal. Setelah konsultasi, saya memilih prosedur ini sesuai saran dokter/terapis.</p>

                <p><strong>Persetujuan:</strong> Saya telah mendapatkan penjelasan yang memadai mengenai tindakan ini, termasuk manfaat, potensi risiko, dan alternatifnya. Semua pertanyaan saya telah dijawab dengan memuaskan dan saya menyetujui tindakan ini dilakukan secara sadar dan sukarela.</p>

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