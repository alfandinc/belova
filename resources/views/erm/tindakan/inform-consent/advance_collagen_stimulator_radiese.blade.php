<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT ADVANCE COLLAGEN STIMULATOR – RADIESSE</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Advance Collagen Stimulator – Radiesse</h5>
            </div>
            <div class="card-body">
                <p>Saya yang bertanda tangan di bawah ini memberikan persetujuan untuk menjalani tindakan <strong>Advance Collagen Stimulator dengan produk Radiesse</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong> Radiesse adalah bahan injeksi berbasis <em>Calcium Hydroxylapatite (CaHA)</em> yang digunakan sebagai stimulan kolagen dan filler dermal. Saat disuntikkan, ia memberikan efek pengisian (volumizing) langsung dan merangsang produksi kolagen jangka panjang. Radiesse cocok digunakan pada area wajah seperti pipi, rahang, atau tangan untuk memperbaiki kontur dan tekstur kulit.</p>

                <p><strong>Manfaat:</strong></p>
                <ol>
                    <li>Efek pengisian (filler) langsung pada area wajah yang kehilangan volume.</li>
                    <li>Stimulasi produksi kolagen alami untuk hasil jangka panjang.</li>
                    <li>Perbaikan elastisitas dan kekencangan kulit wajah.</li>
                    <li>Memberikan hasil yang terlihat alami dan tahan lama (hingga 18 bulan atau lebih).</li>
                    <li>Dapat digunakan untuk facial contouring tanpa pembedahan.</li>
                </ol>

                <p><strong>Risiko dan Komplikasi:</strong></p>
                <ol>
                    <li>Kemerahan, nyeri, atau memar di lokasi suntikan.</li>
                    <li>Bengkak ringan setelah tindakan, biasanya sembuh dalam beberapa hari.</li>
                    <li>Risiko granuloma atau pembentukan nodul jika tidak sesuai indikasi atau teknik penyuntikan.</li>
                    <li>Infeksi atau reaksi alergi (sangat jarang).</li>
                    <li>Risiko asimetri atau efek berlebih jika respons tidak merata.</li>
                </ol>

                <p><strong>Alternatif:</strong> Saya telah dijelaskan mengenai opsi lain seperti filler asam hialuronat, tanam benang, atau stimulan kolagen lain seperti Sculptra atau Facetem. Saya memilih Radiesse berdasarkan konsultasi medis dengan dokter dan indikasi yang sesuai dengan kondisi wajah saya.</p>

                <p><strong>Persetujuan:</strong> Saya telah memahami penjelasan tentang manfaat, risiko, dan alternatif dari tindakan ini. Saya juga sadar bahwa hasil bervariasi antar individu. Dengan ini saya memberikan persetujuan secara sadar dan sukarela tanpa tekanan dari pihak manapun.</p>

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