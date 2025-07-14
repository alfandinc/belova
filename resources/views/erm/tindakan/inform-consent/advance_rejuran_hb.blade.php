<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT ADVANCE REJURAN HB</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Advance Rejuran HB (Hydro Boost)</h5>
            </div>
            <div class="card-body">
                <p>Saya, yang bertanda tangan di bawah ini, dengan ini memberikan persetujuan untuk menjalani tindakan <strong>Advance Rejuran HB</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong> Rejuran HB (Hydro Boost) adalah prosedur injeksi menggunakan kombinasi <em>polynucleotide (PN)</em> murni dan <em>hyaluronic acid (HA)</em>. Formulasi ini bertujuan untuk memberikan efek ganda berupa peremajaan kulit (regenerasi sel) sekaligus hidrasi intensif. Tindakan ini dilakukan melalui teknik injeksi mikro di area wajah.</p>

                <p><strong>Manfaat:</strong> Tindakan Rejuran HB memberikan manfaat berikut:</p>
                <ol>
                    <li>Meningkatkan hidrasi mendalam pada kulit kering dan dehidrasi.</li>
                    <li>Memperbaiki tekstur dan elastisitas kulit.</li>
                    <li>Memberikan efek glowing dan kenyal pada kulit wajah.</li>
                    <li>Merangsang regenerasi sel kulit dan produksi kolagen.</li>
                    <li>Mengurangi garis halus dan kulit kusam akibat stres atau penuaan.</li>
                </ol>

                <p><strong>Risiko dan Komplikasi:</strong> Risiko yang mungkin terjadi meliputi:</p>
                <ol>
                    <li>Kemerahan atau bengkak di titik suntikan (sementara).</li>
                    <li>Memar ringan di beberapa titik.</li>
                    <li>Nodul kecil yang biasanya hilang dalam beberapa hari.</li>
                    <li>Reaksi alergi terhadap salah satu komponen (jarang terjadi).</li>
                    <li>Infeksi lokal jika perawatan pasca-tindakan tidak sesuai.</li>
                </ol>

                <p><strong>Alternatif:</strong> Alternatif tindakan meliputi PRP, skin booster HA biasa, Rejuran tipe lain (Healer, i, S), dan terapi laser untuk hidrasi kulit. Saya telah berdiskusi dengan dokter dan memilih prosedur ini secara sadar dan sesuai dengan kondisi kulit saya.</p>

                <p><strong>Persetujuan:</strong> Saya menyatakan bahwa saya telah menerima penjelasan menyeluruh mengenai prosedur ini, termasuk manfaat, risiko, dan alternatif yang tersedia. Semua pertanyaan saya telah dijawab dengan memuaskan.</p>

                <p>Saya memahami bahwa hasil prosedur dapat berbeda pada setiap individu dan mungkin membutuhkan beberapa sesi untuk mendapatkan hasil optimal. Saya dengan ini memberikan persetujuan untuk menjalani tindakan Rejuran HB secara sadar dan tanpa paksaan.</p>

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