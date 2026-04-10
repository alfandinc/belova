<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rules Penyewa - {{ $renter->nama }}</title>
    <style>
        @page {
            size: A4;
            margin: 12mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #dfe3e7;
            color: #111;
            font-family: "Times New Roman", serif;
        }

        .preview-shell {
            min-height: 100vh;
            padding: 18px 0;
        }

        .paper {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: #fff;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            position: relative;
            overflow: hidden;
        }

        .paper::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image: url("{{ asset('img/header_bcl.png') }}");
            background-repeat: no-repeat;
            background-position: center top;
            background-size: 100% 100%;
            pointer-events: none;
            z-index: 0;
        }

        .paper + .paper {
            margin-top: 18px;
        }

        .paper-top-line {
            height: 4px;
            background: #f01f8f;
            width: 100%;
        }

        .paper-inner {
            padding: 56mm 16mm 16mm;
            font-size: 13px;
            line-height: 1.35;
            position: relative;
            z-index: 1;
        }

        .doc-title {
            text-align: center;
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 10px;
            letter-spacing: 0.3px;
        }

        .intro {
            margin-bottom: 8px;
        }

        table.identity {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        table.identity td {
            padding: 2px 4px;
            vertical-align: top;
        }

        table.identity td.label {
            width: 145px;
            font-weight: 700;
        }

        table.identity td.separator {
            width: 10px;
            text-align: center;
        }

        .section {
            margin-top: 10px;
        }

        .section-title {
            font-weight: 700;
            margin-bottom: 4px;
        }

        ol.main-list {
            margin: 0;
            padding-left: 18px;
        }

        ol.main-list > li {
            margin-bottom: 10px;
        }

        ol.alpha-list {
            margin: 4px 0 0;
            padding-left: 18px;
            list-style-type: lower-alpha;
        }

        ul.dot-list {
            margin: 2px 0 0;
            padding-left: 18px;
            list-style-type: circle;
        }

        ul.dot-list.tight {
            margin-top: 0;
        }

        .subhead {
            font-weight: 700;
            margin: 10px 0 4px;
        }

        .sign-block {
            margin-top: 26px;
            display: flex;
            justify-content: space-between;
        }

        .sign-box {
            width: 43%;
            text-align: center;
            padding: 6px;
        }

        .sign-space {
            height: 78px;
            margin-top: 6px;
            margin-bottom: 6px;
        }

        .date-line {
            margin-top: 18px;
            text-align: right;
        }

        .page-break {
            page-break-before: always;
        }

        .toolbar-note {
            text-align: center;
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #5f6b77;
            margin-bottom: 10px;
        }

        @media print {
            body {
                background: #fff;
            }

            .preview-shell {
                padding: 0;
                min-height: auto;
            }

            .toolbar-note {
                display: none;
            }

            .paper {
                width: auto;
                min-height: auto;
                box-shadow: none;
                margin: 0;
            }

            .paper + .paper {
                margin-top: 0;
            }
        }
    </style>
</head>
<body>
    @php
        use Carbon\Carbon;
        use Illuminate\Support\Facades\Auth;

        $birthday = $renter->birthday ? Carbon::parse($renter->birthday) : null;
        $moveInDate = !empty(optional($roomTransaction)->tgl_mulai) ? Carbon::parse($roomTransaction->tgl_mulai) : null;
        $contractEndDate = !empty(optional($roomTransaction)->tgl_selesai) ? Carbon::parse($roomTransaction->tgl_selesai) : null;
        $roomName = optional($roomTransaction)->room_name ?: optional(optional($roomTransaction)->room)->room_name ?: '-';
        $formattedVehicle = trim(($renter->kendaraan ?: '-') . (($renter->nopol ?? '') ? ' / No. Polisi : ' . $renter->nopol : ''));
        $documentDate = Carbon::today();
        $adminName = optional(Auth::user())->name ?: 'ADMIN';
    @endphp
    <div class="preview-shell">
        <div class="toolbar-note">Preview dokumen A4. Gunakan print browser untuk simpan sebagai PDF.</div>
        <div class="paper">
            <div class="paper-top-line"></div>
            <div class="paper-inner">
                <div class="doc-title">PERATURAN DAN DATA PENGHUNI</div>
                <div class="intro"><strong>Dengan ini, Saya :</strong></div>

                <table class="identity">
                    <tr>
                        <td class="label">Nama</td>
                        <td class="separator">:</td>
                        <td>{{ $renter->nama ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Alamat</td>
                        <td class="separator">:</td>
                        <td>{{ $renter->alamat ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label">NIK/Passport</td>
                        <td class="separator">:</td>
                        <td>{{ $renter->no_identitas ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Tempat Lahir</td>
                        <td class="separator">:</td>
                        <td>-</td>
                    </tr>
                    <tr>
                        <td class="label">Tanggal Lahir</td>
                        <td class="separator">:</td>
                        <td>{{ $birthday ? $birthday->translatedFormat('d F Y') : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label">No Hp/Wa</td>
                        <td class="separator">:</td>
                        <td>1. {{ $renter->phone ?? '-' }} &nbsp;&nbsp;&nbsp; 2. Emergency : {{ $renter->phone2 ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Masuk Kos</td>
                        <td class="separator">:</td>
                        <td>
                            {{ $moveInDate ? $moveInDate->translatedFormat('d F Y') : '-' }}
                            @if($roomName !== '-')
                                / {{ $roomName }}
                            @endif
                            @if($contractEndDate)
                                <span>(Habis kontrak: {{ $contractEndDate->translatedFormat('d F Y') }})</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="label">Kendaraan Yang Dibawa</td>
                        <td class="separator">:</td>
                        <td>{{ $formattedVehicle }}</td>
                    </tr>
                    <tr>
                        <td class="label">Riwayat Penyakit</td>
                        <td class="separator">:</td>
                        <td>-</td>
                    </tr>
                    <tr>
                        <td class="label">Status/Pekerjaan</td>
                        <td class="separator">:</td>
                        <td>-</td>
                    </tr>
                </table>

                <ol class="main-list">
                    <li>
                        <div class="section-title">Ketentuan Umum :</div>
                        <ol class="alpha-list">
                            <li>Penghuni kost diharapkan untuk menjaga ketertiban dan kenyamanan bersama.</li>
                            <li>Penghuni wajib mematuhi peraturan yang telah ditetapkan oleh pengelola kost.</li>
                            <li>Semua penghuni harus melapor kepada pengelola jika terjadi perubahan alamat atau informasi kontak.</li>
                            <li>Jam berkunjung tamu maksimal jam 12.00 WIB.</li>
                            <li>Apabila ada kerabat yang terpaksa bermalam, wajib melapor kepada penjaga atau admin kost BCL dengan biaya Rp 50.000 per malam termasuk extrabed.</li>
                            <li>Wajib mendaftarkan maksimal 1 mobil dan atau 1 motor bagi setiap penyewa.</li>
                        </ol>
                    </li>
                    <li>
                        <div class="section-title">Kost Belova Center Living menyediakan :</div>
                        <ol class="alpha-list">
                            <li>Kamar kost dengan fasilitas standar (tempat tidur, meja, kursi, lemari pakaian).</li>
                            <li>Akses internet Wi-Fi.</li>
                            <li>Dapur bersama (dispenser, kulkas, kompor, dan alat dapur lainnya) yang dapat digunakan oleh penghuni.</li>
                            <li>Fasilitas mesin cuci dan jemur pakaian tersedia di rooftop lantai 4.</li>
                            <li>Area parkir kendaraan khusus penghuni. Hanya kendaraan yang terdaftar yang boleh diparkir.</li>
                        </ol>
                    </li>
                    <li>
                        <div class="section-title">Tatalaksana Pembayaran :</div>
                        <ol class="alpha-list">
                            <li>Deposit (DP) dan pembayaran bulanan tidak dapat dikembalikan (non-refundable).</li>
                            <li>Pembayaran kost dibayar dimuka dan refund berlaku untuk penghuni kost dengan masa sewa minimal 3 bulan.</li>
                            <li>Refund akan diproses sesuai dengan perhitungan di atas setelah dilakukan pemeriksaan oleh pihak pengelola kost.</li>
                        </ol>
                    </li>
                </ol>
            </div>
        </div>

        <div class="paper page-break">
            <div class="paper-top-line"></div>
            <div class="paper-inner">
                <ol class="main-list" start="4">
                    <li>
                        <div class="section-title">Kebersihan :</div>
                        <ol class="alpha-list">
                            <li>Setiap penghuni wajib menjaga kebersihan kamar kost dan fasilitas bersama.</li>
                            <li>Pembersihan kamar dilakukan minimal sekali seminggu, sedangkan fasilitas umum dibersihkan secara bergiliran oleh penghuni kost.</li>
                            <li>Penghuni diwajibkan untuk membuang sampah pada tempatnya.</li>
                        </ol>
                    </li>
                    <li>
                        <div class="section-title">Larangan :</div>
                        <ol class="alpha-list">
                            <li>Dilarang merokok di dalam kamar atau di area yang tidak diperuntukkan.</li>
                            <li>Dilarang membawa atau mengonsumsi barang-barang terlarang seperti narkoba dan minuman keras.</li>
                            <li>Dilarang membuat kegaduhan seperti menyalakan TV atau musik keras-keras dan membuat keributan yang dapat mengganggu ketenangan penghuni kost lain maupun tetangga.</li>
                            <li>Dilarang membawa tamu lawan jenis ke kamar kost kecuali mahram-nya.</li>
                            <li>Dilarang membawa hewan peliharaan.</li>
                            <li>Dilarang membuang pembalut, tissue, kertas, plastik, dan sejenisnya ke lubang WC. Buanglah sampah pada tempat yang telah disediakan.</li>
                        </ol>
                    </li>
                    <li>
                        <div class="section-title">Kos BCL TIDAK bertanggungjawab atas kehilangan dan kerusakan barang milik penyewa :</div>
                        <ol class="alpha-list">
                            <li>Penghuni kost diharapkan untuk menjaga barang-barang pribadi dengan baik.</li>
                            <li>Pengelola tidak bertanggung jawab atas kehilangan, kerusakan, atau pencurian barang-barang pribadi penghuni.</li>
                        </ol>
                    </li>
                    <li>
                        <div class="section-title">Pemeliharaan dan perbaikan property :</div>
                        <ol class="alpha-list">
                            <li>Penghuni wajib melaporkan setiap kerusakan fasilitas kost kepada pengelola.</li>
                            <li>Perbaikan fasilitas akan dilakukan oleh pengelola kost dalam waktu yang wajar.</li>
                            <li>Jika kerusakan seperti sobek, terkena noda permanen, atau kerusakan berat lainnya disebabkan oleh kelalaian penghuni, maka penghuni bertanggung jawab untuk biaya perbaikan hingga ganti rugi sesuai ketentuan.</li>
                        </ol>
                    </li>
                </ol>

                <div class="date-line">Surakarta, {{ $documentDate->translatedFormat('d F Y') }}</div>

                <div class="sign-block">
                    <div class="sign-box">
                        <div><strong>ADMIN</strong></div>
                        <div class="sign-space"></div>
                        <div>{{ $adminName }}</div>
                    </div>
                    <div class="sign-box">
                        <div><strong>PENYEWA</strong></div>
                        <div class="sign-space"></div>
                        <div>{{ $renter->nama ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="paper page-break">
            <div class="paper-top-line"></div>
            <div class="paper-inner">
                <div class="doc-title" style="font-size:18px;">Daftar Harga Ganti Rugi / Denda untuk Kost Belova Center Living</div>

                <div class="subhead">Barang di Kamar:</div>
                <ol class="main-list">
                    <li>
                        <div class="section-title">Kasur (Single/Queen)</div>
                        <ol class="alpha-list">
                            <li>Spring Bed 100 x 200 = Rp1.200.000</li>
                            <li>Spring Bed 120 x 200 = Rp1.500.000</li>
                            <li>Kasur Busa 100x200 = Rp. 600.000</li>
                        </ol>
                    </li>
                    <li>
                        <div class="section-title">Sprei + Sarung Bantal (Set)</div>
                        <ul class="dot-list tight">
                            <li>Denda: Rp150.000 per set</li>
                        </ul>
                    </li>
                    <li>
                        <div class="section-title">Bantal</div>
                        <ul class="dot-list tight">
                            <li>Denda: Rp100.000 per buah</li>
                        </ul>
                    </li>
                    <li>
                        <div class="section-title">Guling</div>
                        <ul class="dot-list tight">
                            <li>Denda: Rp100.000 per buah</li>
                        </ul>
                    </li>
                    <li>
                        <div class="section-title">Lemari Pakaian</div>
                        <ul class="dot-list tight">
                            <li>Denda: Rp1.500.000 (kerusakan berat)</li>
                        </ul>
                    </li>
                    <li>
                        <div class="section-title">Meja & Kursi Belajar</div>
                        <ul class="dot-list">
                            <li>Denda:</li>
                        </ul>
                        <ul class="dot-list" style="padding-left: 36px;">
                            <li>Meja: Rp300.000 - Rp500.000 (kerusakan berat)</li>
                            <li>Kursi: Rp200.000</li>
                        </ul>
                    </li>
                    <li>
                        <div class="section-title">Tirai/Jendela</div>
                        <ul class="dot-list tight">
                            <li>Denda: Rp250.000 - Rp500.000 (kerusakan atau hilang)</li>
                        </ul>
                    </li>
                    <li>
                        <div class="section-title">Lampu Kamar/Meja</div>
                        <ul class="dot-list tight">
                            <li>Denda: Rp100.000 per lampu</li>
                        </ul>
                    </li>
                </ol>

                <div class="subhead">Barang di Dapur Bersama (jika tersedia):</div>
                <ol class="main-list">
                    <li>
                        <div class="section-title">Piring</div>
                        <ul class="dot-list tight">
                            <li>Denda: Rp20.000 per piring (pecah atau hilang)</li>
                        </ul>
                    </li>
                    <li>
                        <div class="section-title">Mangkok</div>
                        <ul class="dot-list tight">
                            <li>Denda: Rp20.000 per piring (pecah atau hilang)</li>
                        </ul>
                    </li>
                    <li>
                        <div class="section-title">Cangkir</div>
                        <ul class="dot-list tight">
                            <li>Denda: Rp15.000 per cangkir</li>
                        </ul>
                    </li>
                    <li>
                        <div class="section-title">Sendok & Garpu</div>
                        <ul class="dot-list tight">
                            <li>Denda: Rp5.000 per buah</li>
                        </ul>
                    </li>
                    <li>
                        <div class="section-title">Wajan/Panci</div>
                        <ul class="dot-list tight">
                            <li>Denda: Rp100.000 - Rp200.000 (tergantung ukuran)</li>
                        </ul>
                    </li>
                </ol>
            </div>
        </div>

        <div class="paper page-break">
            <div class="paper-top-line"></div>
            <div class="paper-inner">
                <div class="subhead">Barang di Kamar Mandi:</div>
                <ol class="main-list">
                    <li>
                        <div class="section-title">Shower</div>
                        <ul class="dot-list tight">
                            <li>Denda: Rp300.000 - Rp500.000 (kerusakan berat)</li>
                        </ul>
                    </li>
                    <li>
                        <div class="section-title">Kloset Duduk</div>
                        <ul class="dot-list tight">
                            <li>Denda: Rp800.000 - Rp1.500.000 (kerusakan berat)</li>
                        </ul>
                    </li>
                    <li>
                        <div class="section-title">Water Heater</div>
                        <ul class="dot-list tight">
                            <li>Denda: Rp1.500.000 (kerusakan berat)</li>
                        </ul>
                    </li>
                    <li>
                        <div class="section-title">Rak Sabun</div>
                        <ul class="dot-list tight">
                            <li>Denda: Rp50.000</li>
                        </ul>
                    </li>
                </ol>

                <div class="subhead">Barang Elektronik :</div>
                <ol class="main-list">
                    <li>
                        <div class="section-title">AC</div>
                        <ul class="dot-list tight">
                            <li>Denda: Rp3.000.000 - Rp5.000.000 (kerusakan berat, bergantung pada tipe)</li>
                        </ul>
                    </li>
                    <li>
                        <div class="section-title">Televisi</div>
                        <ul class="dot-list tight">
                            <li>Denda: Rp2.000.000 - Rp3.500.000 (tergantung ukuran dan tipe)</li>
                        </ul>
                    </li>
                    <li>
                        <div class="section-title">Kipas Angin</div>
                        <ul class="dot-list tight">
                            <li>Denda: Rp200.000 - Rp500.000 (kerusakan berat)</li>
                        </ul>
                    </li>
                    <li>
                        <div class="section-title">Kulkas Umum</div>
                        <ul class="dot-list tight">
                            <li>Denda: Rp10.000.000 - Rp12.000.000 (kerusakan berat)</li>
                        </ul>
                    </li>
                    <li>
                        <div class="section-title">Dispenser Air</div>
                        <ul class="dot-list tight">
                            <li>Denda: Rp1.000.000 - Rp1.500.000 (kerusakan berat)</li>
                        </ul>
                    </li>
                </ol>

                <hr style="margin: 18px 0; border: 0; border-top: 1px solid #8c8c8c;">

                <div class="subhead">Ketentuan Denda Tambahan:</div>
                <ul class="dot-list">
                    <li><strong>Keterlambatan Pembayaran Sewa:</strong>
                        <ul class="dot-list tight" style="padding-left: 18px;">
                            <li>Rp50.000 per hari setelah tanggal jatuh tempo.</li>
                        </ul>
                    </li>
                    <li><strong>Perubahan Tata Ruang (tanpa izin):</strong>
                        <ul class="dot-list tight" style="padding-left: 18px;">
                            <li>Denda: Rp250.000 (termasuk pemasangan paku di dinding)</li>
                        </ul>
                    </li>
                    <li><strong>Noda Permanen pada Dinding:</strong>
                        <ul class="dot-list tight" style="padding-left: 18px;">
                            <li>Denda: Rp300.000 - Rp500.000 (tergantung luas noda)</li>
                        </ul>
                    </li>
                    <li><strong>Kehilangan Kunci Kamar:</strong>
                        <ul class="dot-list tight" style="padding-left: 18px;">
                            <li>Denda: Rp100.000 per kunci (termasuk penggantian kunci baru)</li>
                        </ul>
                    </li>
                </ul>

                <hr style="margin: 18px 0; border: 0; border-top: 1px solid #8c8c8c;">

                <div class="subhead">Catatan Penting:</div>
                <ul class="dot-list">
                    <li>Denda ini bertujuan untuk mengganti barang yang hilang atau rusak, bukan untuk keuntungan pihak pengelola.</li>
                    <li>Penghuni wajib melaporkan kejadian kepada pengelola.</li>
                    <li>Pengecekan akan dilakukan secara rutin setiap 1x dalam seminggu oleh Pengelola Kost BCL.</li>
                </ul>
            </div>
        </div>

        <div class="paper page-break">
            <div class="paper-top-line"></div>
            <div class="paper-inner">
                <div class="doc-title" style="font-size:18px;">Tatalaksana Refund untuk <em>Long Stay</em> (3 Bulan ke Atas)</div>

                <div class="subhead">Ketentuan Refund:</div>
                <div>
                    Untuk penghuni kost yang melakukan pembayaran untuk masa sewa <strong>3 bulan atau lebih</strong>, jika ingin melakukan refund, biaya refund akan dihitung berdasarkan ketentuan berikut:
                </div>

                <ol class="main-list" style="margin-top: 10px;">
                    <li>Harga normal: Tarif yang berlaku di kost pada saat pembayaran dilakukan.</li>
                    <li>
                        Refund akan dipotong sebagai berikut:
                        <ul class="dot-list tight">
                            <li>Harga normal bulan berjalan (harga yang berlaku untuk bulan yang sedang berjalan).</li>
                            <li>Pinalti sebesar 1 bulan sewa (harga normal).</li>
                            <li>Biaya Administrasi sebesar Rp100.000 per bulan masa sewa yang telah dibayar.</li>
                        </ul>
                    </li>
                </ol>

                <hr style="margin: 16px 0; border: 0; border-top: 1px solid #8c8c8c;">

                <div class="subhead">Contoh Perhitungan Refund:</div>
                <ol class="main-list">
                    <li>
                        <strong>Contoh 1: Pembayaran 3 bulan, baru ditempati 2 minggu, ingin refund</strong><br>
                        Pembayaran: Rp 4.650.000<br>
                        Sudah ditempati: 2 minggu<br>
                        Refund yang diterima:
                        <ul class="dot-list tight">
                            <li>Harga normal bulan berjalan: Rp 1.600.000</li>
                            <li>Pinalti 1 bulan (harga normal): Rp 1.600.000</li>
                            <li>Biaya Administrasi (Rp 100.000 x 3 bulan): Rp 300.000</li>
                        </ul>
                        <strong>Total refund:</strong><br>
                        Rp 4.650.000 - Rp 1.600.000 - Rp 1.600.000 - Rp 300.000 = Rp 1.150.000
                    </li>
                    <li style="margin-top: 12px;">
                        <strong>Contoh 2: Pembayaran 6 bulan, sudah ditempati 2 bulan, ingin refund</strong><br>
                        Pembayaran: Rp 9.000.000<br>
                        Sudah ditempati: 2 bulan<br>
                        Refund yang diterima:
                        <ul class="dot-list tight">
                            <li>Harga normal bulan berjalan (untuk bulan ke-3 dan seterusnya): Rp 3.200.000</li>
                            <li>Pinalti 1 bulan (harga normal): Rp 1.600.000</li>
                            <li>Biaya Administrasi (Rp 100.000 x 6 bulan): Rp 600.000</li>
                        </ul>
                        <strong>Total refund:</strong><br>
                        Rp 9.000.000 - Rp 3.200.000 - Rp 1.600.000 - Rp 600.000 = Rp 3.600.000
                    </li>
                </ol>

                <div class="subhead" style="margin-top: 18px;">Catatan:</div>
                <ul class="dot-list">
                    <li><strong>Deposit (DP) dan pembayaran bulanan tidak dapat dikembalikan (non-refundable).</strong></li>
                    <li>Refund akan diproses sesuai dengan perhitungan di atas setelah dilakukan pemeriksaan oleh pihak pengelola kost.</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>