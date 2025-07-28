<div class="container">
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT INJEKSI NEAUVIA SKINBOOSTER</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Injeksi Neauvia Skinbooster</h5>
            </div>
            <div class="card-body">
                <p>Saya yang bertanda tangan di bawah ini menyatakan bahwa saya telah menerima penjelasan lengkap mengenai tindakan Injeksi Neauvia Skinbooster yang akan dilakukan di Klinik Pratama Belova Skin & Beauty Center, dan saya menyetujui untuk menjalani prosedur tersebut.</p>

                <p><strong>Deskripsi Tindakan:</strong> Injeksi Neauvia Skinbooster adalah prosedur penyuntikan produk berbasis asam hialuronat dan bahan aktif lainnya ke dalam lapisan kulit, yang bertujuan untuk meningkatkan hidrasi, elastisitas, dan kualitas kulit secara keseluruhan. Neauvia merupakan produk premium dari Eropa yang diformulasikan untuk memberikan efek regeneratif dan anti-aging dengan keamanan tinggi.</p>

                <p><strong>Manfaat:</strong></p>
                <ol>
                    <li>Menambah kelembapan dan elastisitas kulit</li>
                    <li>Meningkatkan tekstur dan kecerahan kulit</li>
                    <li>Mengurangi garis halus dan tanda penuaan dini</li>
                    <li>Stimulasi regenerasi kolagen dan peremajaan kulit</li>
                </ol>

                <p><strong>Risiko dan Komplikasi:</strong> Meskipun tergolong aman, tindakan ini tetap memiliki potensi risiko seperti:</p>
                <ol>
                    <li>Kemerahan, bengkak, atau memar di lokasi suntikan</li>
                    <li>Nyeri atau rasa tidak nyaman setelah penyuntikan</li>
                    <li>Infeksi bila tidak dilakukan secara steril</li>
                    <li>Reaksi alergi terhadap bahan aktif (sangat jarang)</li>
                </ol>

                <p><strong>Persetujuan:</strong> Saya telah menerima penjelasan lengkap mengenai manfaat, risiko, dan alternatif dari tindakan ini. Saya sadar bahwa hasil dapat bervariasi antar individu dan tidak dijamin. Saya telah diberi kesempatan untuk bertanya dan semua pertanyaan saya telah dijawab dengan jelas dan memuaskan.</p>

                <p><strong>Penjelasan Risiko dan Manfaat:</strong> Dengan ini saya menyatakan bahwa saya memahami dan menyetujui tindakan Injeksi Neauvia Skinbooster sesuai penjelasan yang diberikan oleh tenaga medis.</p>

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
