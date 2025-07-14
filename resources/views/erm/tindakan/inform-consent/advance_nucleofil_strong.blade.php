<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT ADVANCE NUCLEOFILL STRONG</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Advance Nucleofill Strong</h5>
            </div>
            <div class="card-body">
                <p>Saya, yang bertanda tangan di bawah ini, dengan ini memberikan persetujuan untuk menjalani tindakan <strong>Advance Nucleofill Strong</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong> Advance Nucleofill Strong adalah prosedur injeksi menggunakan bahan polinukleotida berkonsentrasi tinggi (PN) yang berfungsi sebagai biostimulator kulit. Produk ini bekerja pada lapisan dalam kulit untuk meningkatkan elastisitas, hidrasi, dan regenerasi jaringan, serta cocok untuk kulit dengan tanda penuaan sedang hingga berat.</p>

                <p><strong>Manfaat:</strong> Beberapa manfaat dari tindakan ini antara lain:</p>
                <ol>
                    <li>Memperbaiki struktur dan elastisitas kulit secara mendalam.</li>
                    <li>Mengurangi tampilan kerutan dan garis halus.</li>
                    <li>Meningkatkan hidrasi dan kekenyalan kulit.</li>
                    <li>Menstimulasi regenerasi sel dan produksi kolagen.</li>
                    <li>Menjadikan kulit tampak lebih sehat, padat, dan bercahaya.</li>
                </ol>

                <p><strong>Risiko dan Komplikasi:</strong> Seperti tindakan injeksi lainnya, prosedur ini memiliki beberapa risiko yang mungkin terjadi, seperti:</p>
                <ol>
                    <li>Kemerahan, bengkak, atau memar di area injeksi.</li>
                    <li>Nyeri ringan atau rasa tidak nyaman sementara.</li>
                    <li>Nodul kecil di bawah kulit (biasanya akan merata dalam beberapa hari).</li>
                    <li>Reaksi alergi terhadap bahan aktif (jarang).</li>
                    <li>Infeksi lokal jika prosedur pasca-perawatan tidak dipatuhi.</li>
                </ol>

                <p><strong>Alternatif:</strong> Saya memahami bahwa terdapat pilihan lain untuk peremajaan kulit, seperti filler, PRP, mesotherapy, atau treatment laser. Saya telah mendiskusikan dengan dokter dan memilih tindakan ini berdasarkan kondisi dan kebutuhan kulit saya.</p>

                <p><strong>Persetujuan:</strong> Saya telah mendapatkan penjelasan lengkap mengenai manfaat, risiko, dan alternatif tindakan ini. Saya telah diberi kesempatan untuk bertanya dan semua pertanyaan saya telah dijawab secara memuaskan. Saya juga memahami bahwa hasil tindakan dapat berbeda antar individu, dan hasil terbaik mungkin memerlukan beberapa sesi.</p>

                <p>Dengan ini saya menyatakan memberikan persetujuan secara sadar dan sukarela untuk menjalani tindakan Advance Nucleofill Strong.</p>

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
