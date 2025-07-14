<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT ADVANCE SUBCISION</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Advance Subcision</h5>
            </div>
            <div class="card-body">
                <p>Saya yang bertanda tangan di bawah ini menyatakan bahwa saya telah menerima penjelasan dan memberikan persetujuan untuk menjalani tindakan <strong>Subcision</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong> Subcision adalah prosedur minimal invasif untuk memperbaiki bekas jerawat yang dalam dan berbentuk cekungan (rolling scars) dengan cara memutus jaringan parut di bawah permukaan kulit menggunakan jarum khusus. Proses ini merangsang pembentukan kolagen baru dan mendorong permukaan kulit untuk menjadi lebih rata.</p>

                <p><strong>Manfaat:</strong></p>
                <ol>
                    <li>Mengurangi kedalaman bekas jerawat tipe rolling scars.</li>
                    <li>Meningkatkan tekstur dan tampilan kulit secara keseluruhan.</li>
                    <li>Merangsang produksi kolagen secara alami.</li>
                    <li>Bisa dikombinasikan dengan tindakan lain seperti PRP, filler, atau laser untuk hasil maksimal.</li>
                </ol>

                <p><strong>Risiko dan Komplikasi:</strong></p>
                <ol>
                    <li>Memar dan bengkak di area tindakan (umum dan bersifat sementara).</li>
                    <li>Nyeri ringan setelah tindakan.</li>
                    <li>Infeksi atau peradangan (jarang).</li>
                    <li>Asimetri atau ketidaksempurnaan hasil (bisa diperbaiki pada sesi berikutnya).</li>
                    <li>Pembentukan jaringan parut baru (sangat jarang).</li>
                </ol>

                <p><strong>Alternatif:</strong> Saya telah dijelaskan mengenai alternatif lain seperti laser resurfacing, microneedling, dermal filler, atau tidak melakukan tindakan. Saya memilih Subcision karena sesuai dengan kondisi dan tujuan perawatan saya.</p>

                <p><strong>Persetujuan:</strong> Saya menyadari bahwa hasil dari tindakan ini memerlukan waktu, bisa memerlukan beberapa sesi, dan dapat bervariasi pada tiap individu. Saya telah diberikan kesempatan untuk bertanya, dan semua pertanyaan saya telah dijawab dengan memuaskan. Dengan ini saya memberikan persetujuan secara sadar, tanpa tekanan, dan dengan pemahaman penuh atas manfaat dan risiko prosedur ini.</p>

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