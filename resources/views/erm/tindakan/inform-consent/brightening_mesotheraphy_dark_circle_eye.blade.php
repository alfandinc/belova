<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT BRIGHTENING MESOTHERAPY DARK CIRCLE EYE</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Brightening Mesotherapy Area Bawah Mata</h5>
            </div>
            <div class="card-body">
                <p>Saya, yang bertanda tangan di bawah ini, dengan ini memberikan persetujuan untuk menjalani tindakan <strong>Brightening Mesotherapy Dark Circle Eye</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong> Brightening Mesotherapy untuk area bawah mata adalah prosedur yang bertujuan untuk mengurangi lingkaran hitam (dark circle), mata panda, dan kelelahan di sekitar mata. Tindakan ini menggunakan jarum halus atau alat injeksi otomatis untuk menyuntikkan serum khusus yang mengandung vitamin, peptida, dan bahan pencerah ke area bawah mata.</p>

                <p><strong>Manfaat:</strong> Tindakan ini memberikan berbagai manfaat, antara lain:</p>
                <ol>
                    <li>Mencerahkan area bawah mata yang gelap.</li>
                    <li>Mengurangi tampilan mata panda dan kelelahan.</li>
                    <li>Memperbaiki sirkulasi mikro di sekitar mata.</li>
                    <li>Meningkatkan hidrasi dan elastisitas kulit di bawah mata.</li>
                    <li>Membuat tampilan mata lebih segar dan cerah.</li>
                </ol>

                <p><strong>Risiko dan Komplikasi:</strong> Karena area bawah mata sangat sensitif, terdapat beberapa risiko:</p>
                <ol>
                    <li>Kemerahan atau pembengkakan sementara di area suntikan.</li>
                    <li>Memar kecil (bruising) yang akan menghilang dalam beberapa hari.</li>
                    <li>Rasa nyeri ringan saat penyuntikan.</li>
                    <li>Reaksi alergi terhadap bahan yang disuntikkan (jarang).</li>
                    <li>Nodul kecil yang biasanya akan hilang dengan sendirinya.</li>
                </ol>

                <p><strong>Alternatif:</strong> Alternatif tindakan ini meliputi krim topikal khusus area mata, penggunaan filler, PRP (Platelet Rich Plasma), atau perawatan laser tertentu. Saya telah berdiskusi dengan dokter/terapis untuk memilih opsi terbaik sesuai kondisi saya.</p>

                <p><strong>Persetujuan:</strong> Saya telah menerima penjelasan secara rinci dan jelas mengenai prosedur ini, manfaat, risiko, dan alternatif yang tersedia. Saya diberi kesempatan untuk bertanya dan mendapatkan jawaban yang memuaskan.</p>

                <p>Saya memahami bahwa hasil yang diharapkan mungkin memerlukan beberapa sesi dan bisa bervariasi antar individu. Dengan ini saya menyatakan menyetujui untuk menjalani tindakan tersebut secara sadar dan tanpa paksaan.</p>

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
