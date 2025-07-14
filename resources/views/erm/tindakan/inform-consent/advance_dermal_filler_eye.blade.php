<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT ADVANCE DERMAL FILLER – UNDER EYE AREA</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Dermal Filler Area Bawah Mata (Tear Trough)</h5>
            </div>
            <div class="card-body">
                <p>Saya yang bertanda tangan di bawah ini memberikan persetujuan untuk menjalani tindakan <strong>injeksi dermal filler di area bawah mata</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong> Prosedur ini menggunakan dermal filler berbasis <em>Hyaluronic Acid</em> yang disuntikkan ke area tear trough (cekungan bawah mata) untuk mengurangi tampilan lingkaran hitam, cekungan, dan kelelahan pada mata. Tindakan ini dilakukan dengan teknik injeksi mikro yang hati-hati menggunakan jarum atau kanula khusus.</p>

                <p><strong>Manfaat:</strong></p>
                <ol>
                    <li>Mengurangi tampilan kantung mata dan lingkaran hitam.</li>
                    <li>Memperbaiki volume area bawah mata yang cekung.</li>
                    <li>Memberikan tampilan mata yang lebih segar dan muda.</li>
                    <li>Efek instan dan pemulihan cepat.</li>
                    <li>Tanpa pembedahan atau tindakan invasif.</li>
                </ol>

                <p><strong>Risiko dan Komplikasi:</strong></p>
                <ol>
                    <li>Kemerahan, bengkak, atau memar di area suntikan.</li>
                    <li>Asimetri atau hasil yang kurang merata.</li>
                    <li>Efek Tyndall (warna kebiruan) jika penyuntikan terlalu dangkal.</li>
                    <li>Risiko penyumbatan pembuluh darah (sangat jarang, tapi serius).</li>
                    <li>Granuloma atau benjolan kecil jika terjadi reaksi terhadap filler.</li>
                </ol>

                <p><strong>Alternatif:</strong> Saya telah dijelaskan tentang alternatif seperti penggunaan krim mata, treatment laser, PRP, atau tidak melakukan tindakan sama sekali. Saya memilih tindakan ini berdasarkan saran dokter dan tujuan estetika saya.</p>

                <p><strong>Persetujuan:</strong> Saya menyatakan telah menerima penjelasan yang cukup mengenai tindakan ini, termasuk manfaat, risiko, dan alternatifnya. Saya sadar bahwa hasil dapat berbeda pada setiap individu dan bersifat sementara (sekitar 6–12 bulan). Saya memberikan persetujuan ini dengan sadar, sukarela, dan tanpa paksaan dari pihak manapun.</p>

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