<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT LASER ARMPIT</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Laser Armpit</h5>
            </div>
            <div class="card-body">
                <p>Saya, yang bertanda tangan di bawah ini, dengan ini memberikan persetujuan untuk menjalani tindakan <strong>Laser Armpit</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong> Laser Armpit adalah prosedur menggunakan teknologi sinar laser untuk menghilangkan rambut pada area ketiak. Laser bekerja dengan menargetkan pigmen di folikel rambut sehingga menghambat pertumbuhan rambut secara bertahap dan semi permanen.</p>

                <p><strong>Manfaat:</strong> Prosedur ini memberikan berbagai manfaat, antara lain:</p>
                <ol>
                    <li>Mengurangi pertumbuhan rambut pada ketiak secara signifikan.</li>
                    <li>Menjadikan area ketiak lebih bersih dan halus.</li>
                    <li>Mengurangi risiko iritasi akibat mencukur atau waxing.</li>
                    <li>Meningkatkan kenyamanan dan kebersihan pribadi.</li>
                </ol>

                <p><strong>Risiko dan Komplikasi:</strong> Prosedur ini tergolong aman, tetapi tetap memiliki potensi risiko, seperti:</p>
                <ol>
                    <li>Kemerahan atau rasa perih setelah tindakan.</li>
                    <li>Perubahan warna kulit (hiperpigmentasi/hipopigmentasi sementara).</li>
                    <li>Rasa tidak nyaman atau sedikit panas di area tindakan.</li>
                    <li>Iritasi kulit atau luka ringan (jarang).</li>
                    <li>Reaksi alergi terhadap gel pendingin atau alat laser tertentu.</li>
                </ol>

                <p><strong>Alternatif:</strong> Alternatif tindakan ini termasuk mencukur, waxing, sugaring, dan penggunaan krim perontok rambut. Saya telah berdiskusi dengan dokter atau terapis mengenai pilihan yang paling sesuai.</p>

                <p><strong>Persetujuan:</strong> Saya menyatakan telah menerima penjelasan lengkap mengenai prosedur, manfaat, risiko, dan alternatif tindakan ini. Saya juga telah diberikan kesempatan untuk bertanya dan semua pertanyaan saya telah dijawab dengan memuaskan.</p>

                <p>Saya memahami bahwa hasil dari tindakan ini dapat berbeda-beda, dan saya memberikan persetujuan ini secara sadar dan sukarela. Saya juga mengetahui bahwa saya berhak untuk menghentikan prosedur kapan saja jika merasa tidak nyaman.</p>

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