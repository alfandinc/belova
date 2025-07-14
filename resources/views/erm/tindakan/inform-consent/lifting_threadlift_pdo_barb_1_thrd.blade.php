<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT LIFTING THREADLIFT PDO BARB – 1 THREAD</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Lifting Threadlift PDO Barb (1 Benang)</h5>
            </div>
            <div class="card-body">
                <p>Saya yang bertanda tangan di bawah ini memberikan persetujuan untuk menjalani tindakan <strong>Threadlift PDO Barb (1 Thread)</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong> Threadlift PDO Barb adalah prosedur penanaman benang berjenis Polydioxanone (PDO) dengan kait (barb/cog) yang berfungsi menarik jaringan kulit untuk memberikan efek lifting langsung. Satu benang akan ditanam sesuai kebutuhan area, dengan teknik steril dan sesuai standar medis.</p>

                <p><strong>Manfaat:</strong></p>
                <ol>
                    <li>Memberikan efek tarik langsung pada kulit yang kendur.</li>
                    <li>Mengangkat pipi, rahang, atau area tertentu tanpa pembedahan.</li>
                    <li>Menstimulasi produksi kolagen di sekitar benang.</li>
                    <li>Peremajaan kulit secara bertahap dan alami.</li>
                    <li>Meningkatkan kontur wajah dan simetri.</li>
                </ol>

                <p><strong>Risiko dan Komplikasi:</strong></p>
                <ol>
                    <li>Nyeri, kemerahan, atau bengkak di area penanaman benang.</li>
                    <li>Memar atau rasa tertarik yang tidak nyaman sementara.</li>
                    <li>Risiko infeksi lokal jika tidak dijaga kebersihannya.</li>
                    <li>Asimetri atau tarik berlebih jika reaksi kulit tidak merata.</li>
                    <li>Benang terasa atau terlihat di bawah kulit selama pemulihan awal.</li>
                </ol>

                <p><strong>Alternatif:</strong> Saya memahami bahwa tersedia metode lain seperti HIFU, RF, filler, atau benang tipe mono. Berdasarkan konsultasi dan kondisi wajah saya, saya memilih tindakan tanam benang barb ini.</p>

                <p><strong>Persetujuan:</strong> Saya menyatakan bahwa saya telah menerima penjelasan yang cukup mengenai tindakan ini, termasuk manfaat, risiko, dan alternatifnya. Saya memahami hasil bersifat individual dan memerlukan masa pemulihan. Saya memberikan persetujuan secara sadar dan tanpa paksaan.</p>

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