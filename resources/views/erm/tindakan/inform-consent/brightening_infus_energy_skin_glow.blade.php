<div class="container"> 
    <form id="informConsentForm" method="POST" action="{{ route('erm.tindakan.inform-consent.save') }}">
        @csrf
        <input type="hidden" name="visitation_id" value="{{ $visitation->id }}">
        <input type="hidden" name="tindakan_id" value="{{ $tindakan->id }}">
        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <h4 class="text-center mb-4">INFORMED CONSENT BRIGHTENING INFUS ENERGY SKIN GLOW</h4>

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
                <h5 class="mb-0">Persetujuan Tindakan Brightening Infus Energy Skin Glow</h5>
            </div>
            <div class="card-body">
                <p>Saya, yang bertanda tangan di bawah ini, dengan ini memberikan persetujuan untuk menjalani tindakan <strong>Brightening Infus Energy Skin Glow</strong> di Klinik Pratama Belova Skin & Beauty Center.</p>

                <p><strong>Deskripsi Tindakan:</strong> Brightening Infus Energy Skin Glow adalah prosedur pemberian infus yang mengandung kombinasi vitamin C, glutation, antioksidan, dan nutrisi lainnya melalui pembuluh darah vena. Tujuannya adalah untuk meningkatkan kecerahan kulit, energi tubuh, dan memperbaiki tampilan kulit secara menyeluruh dari dalam.</p>

                <p><strong>Manfaat:</strong> Beberapa manfaat dari tindakan ini antara lain:</p>
                <ol>
                    <li>Mencerahkan kulit secara merata dan memberikan efek glowing.</li>
                    <li>Menangkal radikal bebas dan memperlambat tanda-tanda penuaan.</li>
                    <li>Meningkatkan daya tahan tubuh dan energi harian.</li>
                    <li>Membantu detoksifikasi tubuh secara alami.</li>
                    <li>Meningkatkan hidrasi kulit dan memperbaiki tekstur kulit kusam.</li>
                </ol>

                <p><strong>Risiko dan Komplikasi:</strong> Risiko yang mungkin terjadi termasuk:</p>
                <ol>
                    <li>Reaksi alergi terhadap salah satu komponen infus.</li>
                    <li>Nyeri, bengkak, atau kemerahan di tempat suntikan.</li>
                    <li>Pusing atau mual ringan setelah prosedur.</li>
                    <li>Efek samping ringan seperti sakit kepala atau kelelahan.</li>
                    <li>Risiko infeksi jika prosedur tidak dilakukan secara steril (telah dicegah dengan SOP klinik).</li>
                </ol>

                <p><strong>Alternatif:</strong> Tersedia metode lain seperti konsumsi oral suplemen vitamin, skincare topikal, atau terapi antioksidan lainnya. Namun, efektivitas infus umumnya lebih cepat karena diberikan langsung ke aliran darah.</p>

                <p><strong>Persetujuan:</strong> Saya menyatakan telah menerima penjelasan mengenai prosedur ini, manfaat, potensi efek samping, dan alternatifnya. Saya telah diberi kesempatan untuk bertanya dan semua pertanyaan saya telah dijawab dengan memuaskan.</p>

                <p>Saya memahami bahwa hasil dapat bervariasi dan tidak bersifat permanen. Saya menyetujui prosedur ini secara sadar, sukarela, dan tanpa paksaan dari pihak manapun.</p>

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
                        <div class="signature-container t
