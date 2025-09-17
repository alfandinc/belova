<div class="container">
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">
        
        <h4 class="text-center mb-4">INFORMED CONSENT VAKSINASI HEPATITIS B</h4>
        
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
                <h5 class="mb-0">Informasi Tindakan Medis</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th width="5%">No.</th>
                            <th width="30%">JENIS INFORMASI</th>
                            <th>ISI INFORMASI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>Penjelasan Umum</td>
                            <td>
                                • Vaksinasi Hepatitis B bertujuan untuk memberikan perlindungan terhadap infeksi 
                                virus Hepatitis B yang dapat menyebabkan kerusakan hati (hepatitis kronis, sirosis, 
                                maupun kanker hati). <br>
                                • Vaksin Hepatitis B diberikan melalui suntikan intramuskular sesuai jadwal yang 
                                ditentukan. <br>
                                • Sebelum vaksinasi, pasien dianjurkan melakukan pemeriksaan laboratorium HBsAg 
                                dan Anti-HBs untuk mengetahui status kekebalan atau kemungkinan adanya infeksi 
                                Hepatitis B. <br>
                                • Pemeriksaan laboratorium ini bukan syarat mutlak untuk dilakukan vaksinasi. Namun 
                                pasien dianjurkan untuk berkonsultasi dengan dokter guna menentukan pilihan 
                                terbaik sebelum vaksinasi, sedangkan keputusan akhir tetap berada pada pasien.
                            </td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>Manfaat Vaksinasi </td>
                            <td>
                                • Mencegah penularan infeksi virus Hepatitis B. <br>
                                • Memberikan kekebalan jangka panjang (dapat bertahan hingga >10 tahun). <br>
                                • Mengurangi risiko komplikasi serius akibat Hepatitis B. <br>
                            </td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td>Tata Cara Pemberian Vaksinasi</td>
                            <td>
                                • Vaksin Hepatitis B diberikan melalui suntikan intramuskular (disuntikkan ke dalam 
                                otot), umumnya di lengan atas (deltoid) untuk dewasa atau paha (anterolateral) untuk 
                                bayi/anak kecil. <br>
                                • Dosis vaksinasi mengikuti jadwal yang berlaku, antara lain: <br>
                                • Dosis primer: 3 kali suntikan pada bulan ke-0, ke-1, dan ke-6. <br>
                                • Pada kondisi tertentu, dokter dapat menyesuaikan jadwal sesuai usia, status 
                                kekebalan, atau indikasi medis pasien. <br>
                                • Pasien dianjurkan untuk tetap berada di klinik minimal 30 menit setelah vaksinasi 
                                guna pemantauan kemungkinan reaksi alergi atau efek samping segera. <br>
                                • Setelah vaksinasi, pasien akan mendapatkan catatan vaksinasi (buku/ kartu vaksin) 
                                sebagai bukti dan panduan untuk jadwal dosis berikutnya. <br>
                                • Untuk memperoleh hasil optimal, pasien disarankan untuk melengkapi seluruh 
                                rangkaian vaksinasi sesuai jadwal yang telah ditentukan dokter. <br>
                            </td>
                        </tr>
                        <tr>
                            <td>4</td>
                            <td>Risiko dan Efek Samping </td>
                            <td>
                                Vaksinasi Hepatitis B umumnya aman, namun dapat menimbulkan efek samping ringan, 
                                seperti: <br>
                                • Nyeri, bengkak, atau kemerahan di tempat suntikan. <br>
                                • Demam ringan, sakit kepala, atau rasa lelah. <br>
                                • Reaksi alergi berat (sangat jarang) 
                            </td>
                        </tr>
                        <tr>
                            <td>5</td>
                            <td>Pernyataan Persetujuan </td>
                            <td>
                                • Saya menyatakan telah menerima penjelasan yang lengkap, jelas, dan dapat saya 
                                pahami mengenai tujuan, manfaat, prosedur, risiko, serta alternatif vaksinasi Hepatitis 
                                B. <br>
                                • Saya telah diberi kesempatan untuk mengajukan pertanyaan, dan telah memperoleh 
                                jawaban yang memuaskan dari dokter/petugas medis. <br>
                                • Saya memahami manfaat, risiko, serta konsekuensi dari pilihan untuk melakukan atau 
                                tidak melakukan pemeriksaan laboratorium sebelum vaksinasi. <br>
                                • Dengan kesadaran penuh dan tanpa paksaan dari pihak manapun, saya menyatakan 
                                menyetujui untuk dilakukan tindakan vaksinasi Hepatitis B di klinik. 
                            </td>
                        </tr>
                        
                    </tbody>
                </table>

                <div class="mt-4">
                    <p>Saya yang bertanda tangan dibawah ini menyetujui untuk dilakukan tindakan Vaksinasi Hepatitis B dengan ketentuan:</p>
                    <ol>
                        <li>Saya telah mendapatkan penjelasan mengenai vaksin yang akan diberikan, termasuk manfaat, cara kerja, serta efek samping yang mungkin terjadi. </li>
                        <li>Saya memahami bahwa vaksinasi bertujuan untuk membantu meningkatkan kekebalan tubuh dan mencegah penyakit tertentu. </li>
                        <li>Saya mengetahui bahwa meskipun jarang terjadi, vaksinasi dapat menimbulkan efek samping ringan hingga sedang, seperti nyeri di tempat suntikan, demam ringan, atau reaksi alergi. </li>
                        <li>Saya menyatakan dengan sukarela dan penuh kesadaran untuk menjalani vaksinasi. </li>
                        <li>Saya telah diberi kesempatan untuk bertanya dan mendapatkan jawaban yang memuaskan atas pertanyaan saya terkait vaksinasi ini.</li>
                        <li>Saya menyetujui tindakan vaksinasi yang akan dilakukan oleh tenaga medis di klinik ini.</li>
                    </ol>
                </div>
                
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