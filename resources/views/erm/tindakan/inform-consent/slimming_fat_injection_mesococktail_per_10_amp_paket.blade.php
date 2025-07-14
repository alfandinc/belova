<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT SLIMMING FAT INJECTION – PAKET 10 AMP MESOCOCKTAIL</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Paket Injeksi Slimming Fat Mesococktail (10 Ampul)</h5>
            </div>
            <div class="card-body">
                <p>Saya yang bertanda tangan di bawah ini memberikan persetujuan untuk menjalani <strong>paket tindakan injeksi pelarut lemak (Slimming Fat Injection) menggunakan 10 ampul Mesococktail</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong> Prosedur ini melibatkan penyuntikan Mesococktail — campuran zat aktif seperti phosphatidylcholine, deoxycholate, L-carnitine, dan vitamin — ke dalam lapisan lemak subkutan secara bertahap dalam beberapa sesi. Tujuannya adalah mengurangi lemak lokal dan memperbaiki kontur wajah atau tubuh. Paket ini terdiri dari 10 ampul yang akan digunakan sesuai protokol medis yang disesuaikan per pasien.</p>

                <p><strong>Manfaat:</strong></p>
                <ol>
                    <li>Menghilangkan lemak lokal secara bertahap tanpa pembedahan.</li>
                    <li>Membentuk wajah dan tubuh lebih proporsional.</li>
                    <li>Efek bertahap dan dapat dievaluasi setiap sesi.</li>
                    <li>Prosedur minim nyeri dan cepat.</li>
                </ol>

                <p><strong>Risiko dan Komplikasi:</strong></p>
                <ol>
                    <li>Kemerahan, bengkak, atau nyeri ringan pada area suntikan.</li>
                    <li>Memar atau pengerasan lokal sementara.</li>
                    <li>Asimetri hasil jika tidak ditangani dengan protokol lanjutan.</li>
                    <li>Reaksi alergi terhadap salah satu bahan (jarang).</li>
                    <li>Hasil tidak maksimal jika tidak disertai gaya hidup sehat.</li>
                </ol>

                <p><strong>Alternatif:</strong> Saya memahami bahwa terdapat metode alternatif seperti cryolipolysis, HIFU, tanam benang lemak, atau tidak menjalani tindakan sama sekali. Saya telah berkonsultasi dan memilih untuk menjalani paket injeksi ini.</p>

                <p><strong>Persetujuan:</strong> Saya menyadari bahwa hasil tindakan ini tidak instan dan membutuhkan evaluasi berkala. Saya telah mendapatkan penjelasan mengenai manfaat, risiko, dan prosedur pelaksanaannya. Saya menyatakan setuju untuk menjalani prosedur ini secara sadar dan tanpa tekanan dari pihak mana pun.</p>

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