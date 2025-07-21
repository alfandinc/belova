<div class="container">
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT LASER PICO BRIGHTENING FACE</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Laser Pico Brightening Face</h5>
            </div>
            <div class="card-body">
                <p>Saya, yang bertanda tangan di bawah ini, dengan ini memberikan persetujuan untuk menjalani perawatan Laser Pico Brightening Face di Klinik Pratama Belova Skin & Beauty Center.</p>
                <p><strong>Deskripsi Tindakan:</strong> Prosedur ini menggunakan teknologi laser pico untuk mencerahkan dan meratakan warna kulit wajah, mengurangi noda hitam, pigmentasi, dan memperbaiki tekstur kulit. Teknologi laser bekerja dengan mengirimkan pulsa energi tinggi dalam waktu sangat singkat untuk menargetkan melanin tanpa merusak jaringan di sekitarnya.</p>
                
                <p><strong>Manfaat Prosedur:</strong></p>
                <ol>
                    <li>Mencerahkan kulit wajah secara keseluruhan</li>
                    <li>Mengurangi flek hitam, melasma, dan pigmentasi</li>
                    <li>Meratakan warna kulit dan memperbaiki tekstur wajah</li>
                    <li>Menstimulasi regenerasi kolagen untuk kulit lebih sehat</li>
                </ol>

                <p><strong>Risiko dan Kemungkinan Komplikasi:</strong></p>
                <ol>
                    <li>Kemerahan, rasa perih, atau sensasi terbakar setelah tindakan</li>
                    <li>Pembengkakan ringan di area wajah yang dirawat</li>
                    <li>Perubahan warna kulit sementara (hipo/hiperpigmentasi)</li>
                    <li>Risiko infeksi atau reaksi alergi terhadap bahan topical/anestesi (jika digunakan)</li>
                    <li>Kekecewaan terhadap hasil yang tidak sesuai ekspektasi</li>
                </ol>

                <p><strong>Alternatif Pengobatan:</strong></p>
                <ol>
                    <li>Perawatan chemical peeling atau mikrodermabrasi</li>
                    <li>Terapis skincare topikal (krim pencerah, serum)</li>
                    <li>Terapi IPL atau laser jenis lain</li>
                    <li>Memilih untuk tidak menjalani tindakan apapun</li>
                </ol>

                <p><strong>Persetujuan:</strong> Saya menyatakan telah menerima dan memahami informasi lengkap mengenai prosedur Laser Pico Brightening Face sebagaimana dijelaskan di atas. Saya telah diberi kesempatan untuk bertanya dan mendiskusikan seluruh aspek tindakan ini dengan dokter atau tenaga medis terkait.  
                Saya memahami bahwa hasil dapat bervariasi pada setiap individu dan tidak ada jaminan hasil. Saya menyadari adanya risiko dan komplikasi yang mungkin timbul, dan dengan ini saya menyatakan memberikan persetujuan untuk menjalani prosedur ini sesuai dengan standar medis yang berlaku.</p>

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
