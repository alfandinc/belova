<div class="container">
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">
        
        <h4 class="text-center mb-4">INFORMED CONSENT LASER LIP CARE</h4>
        
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
                <h5 class="mb-0">Persetujuan Tindakan Laser Lip Care</h5>
            </div>
            <div class="card-body">
                <p>Saya, yang bertanda tangan di bawah ini, dengan ini memberikan persetujuan untuk menjalani perawatan Facial di Klinik Pratama Belova Skin & Beauty Center.</p>
                <p>Deskripsi Tindakan: Prosedur laser bibir menggunakan teknologi laser untuk meningkatkan penampilan bibir, yang dapat termasuk mengurangi pigmentasi, melicinkan tekstur kulit, dan meningkatkan kontur bibir. Teknologi laser bekerja dengan mengirimkan sinar laser yang terfokus ke area target untuk merangsang produksi kolagen dan pembaharuan sel.</p>
                <p>Manfaat Prosedur:</p>
                <ol>
                    <li>Memperbaiki warna dan tekstur bibir</li>
                    <li>Mengurangi tampilan garis-garis halus dan kerutan di sekitar bibir</li>
                    <li>Meningkatkan kejelasan dan definisi kontur bibir</li>
                </ol>
                <p>Risiko dan Kemungkinan Komplikasi:</p>
                <ol>
                    <li>Kemerahan, pembengkakan, dan ketidaknyamanan ringan di area perawatan</li>
                    <li>Perubahan pigmen, baik hipopigmentasi maupun hiperpigmentasi</li>
                    <li>Infeksi, pembentukan parut, dan reaksi alergi terhadap anestesi lokal</li>
                    <li>Kekecewaan terhadap hasil yang tidak memenuhi ekspektasi</li>
                </ol>

                <p>Alternatif Pengobatan:</p>
                <ol>
                    <li>Penggunaan filler bibir</li>
                    <li>Terapi mikro-needling</li>
                    <li>Penggunaan krim topikal atau perawatan dermatologis lainnya</li>
                    <li>Memilih untuk tidak melakukan perawatan apapun</li>
                </ol>
                <p>Persetujuan: Dengan ini mengakui bahwa saya telah menerima dan memahami informasi lengkap mengenai prosedur laser bibir yang dijelaskan di atas. Saya telah diberikan kesempatan untuk bertanya dan mendiskusikan tentang prosedur ini dengan penyedia layanan kesehatan saya.
Saya mengerti bahwa tidak ada jaminan yang diberikan mengenai hasil akhir dari perawatan. Saya juga memahami bahwa setiap prosedur medis memiliki potensi risiko dan efek samping. Saya telah mempertimbangkan alternatif yang tersedia dan dengan ini memberikan persetujuan saya untuk melanjutkan dengan prosedur laser bibir sesuai dengan standar medis yang berlaku.
</p>

                
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

        <div class="text-center">
            <button type="submit" class="btn btn-primary">Simpan Persetujuan</button>
        </div>
    </form>
</div>
