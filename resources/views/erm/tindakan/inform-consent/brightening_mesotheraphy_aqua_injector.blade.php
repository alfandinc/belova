<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT BRIGHTENING MESOTHERAPY WITH AQUA INJECTOR</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Brightening Mesotherapy dengan Aqua Injector</h5>
            </div>
            <div class="card-body">
                <p>Saya, yang bertanda tangan di bawah ini, dengan ini memberikan persetujuan untuk menjalani tindakan <strong>Brightening Mesotherapy dengan Aqua Injector</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong> Tindakan ini menggunakan alat <em>Aqua Injector</em> untuk menyuntikkan serum yang mengandung bahan pencerah, vitamin, dan antioksidan secara merata ke lapisan tengah kulit (dermis). Teknologi ini memungkinkan distribusi bahan aktif secara presisi dan minim rasa sakit, bertujuan untuk mencerahkan kulit dan memperbaiki kondisi kulit secara menyeluruh.</p>

                <p><strong>Manfaat:</strong> Manfaat dari prosedur ini antara lain:</p>
                <ol>
                    <li>Mencerahkan dan meratakan warna kulit wajah.</li>
                    <li>Meningkatkan hidrasi kulit dan elastisitas.</li>
                    <li>Mengurangi tampilan flek hitam dan bekas jerawat.</li>
                    <li>Mempercepat regenerasi sel kulit baru.</li>
                    <li>Memperbaiki tekstur kulit dan membuat kulit tampak lebih sehat dan glowing.</li>
                </ol>

                <p><strong>Risiko dan Komplikasi:</strong> Meskipun tergolong aman dan minim invasif, prosedur ini tetap memiliki risiko sebagai berikut:</p>
                <ol>
                    <li>Kemerahan atau bengkak ringan setelah tindakan (sementara).</li>
                    <li>Memar ringan di beberapa titik suntikan.</li>
                    <li>Reaksi alergi terhadap bahan aktif yang digunakan.</li>
                    <li>Infeksi lokal jika area suntikan tidak dijaga kebersihannya.</li>
                    <li>Nodul kecil yang umumnya akan hilang dalam beberapa hari.</li>
                </ol>

                <p><strong>Alternatif:</strong> Saya memahami bahwa terdapat beberapa alternatif untuk mencapai hasil serupa, seperti mesotherapy manual (jarum), peeling kimia, brightening facial, atau laser. Saya telah berdiskusi dengan dokter atau terapis untuk menentukan pilihan terbaik sesuai kebutuhan kulit saya.</p>

                <p><strong>Persetujuan:</strong> Saya menyatakan telah menerima penjelasan secara lengkap dan jelas mengenai tindakan ini, termasuk manfaat, risiko, dan alternatifnya. Saya diberi kesempatan untuk bertanya dan semua pertanyaan saya telah dijawab dengan memuaskan.</p>

                <p>Saya memahami bahwa hasil dapat berbeda-beda pada setiap individu, dan mungkin memerlukan beberapa sesi untuk mendapatkan hasil optimal. Saya memberikan persetujuan ini secara sadar, sukarela, dan tanpa paksaan dari pihak manapun.</p>

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