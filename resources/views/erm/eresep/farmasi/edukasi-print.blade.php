<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Edukasi Obat</title>
    <style>
        @page {
            margin: 1.5cm 1.5cm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 9pt;
            line-height: 1.2;
            margin: 10px;
            position: relative;
            min-height: 29.7cm;
        }
        /* Modified header styles */
        .header-container {
            border-bottom: 1px solid #ddd;
            margin-bottom: 8px;
            padding-bottom: 4px;
            position: relative;
        }
        .header-left {
            text-align: left;
        }
        .header-left h2 {
            margin: 0 0 3px 0;
            font-size: 12pt;
        }
        .header-left p {
            margin: 3px 0;
            font-size: 9pt;
        }
        .header-title {
            text-align: center;
            margin: 15px 0 10px 0;
        }
        .title {
            font-size: 12pt;
            font-weight: bold;
            display: inline-block;
            padding: 3px 20px;
        }
        .logo {
            max-height: 50px;
        }
        .clinic-name {
            font-size: 10pt;
            margin-top: 2px;
        }
        .date {
            font-size: 9pt;
            margin-top: 2px;
        }
        
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        .info-table th {
            background-color: #f2f2f2;
            text-align: left;
            padding: 3px;
            font-weight: bold;
            border: 1px solid #ddd;
        }
        .info-table td {
            padding: 3px;
            border: 1px solid #ddd;
        }
        
        .section {
            margin-bottom: 6px;
            padding-bottom: 4px;
        }
        
        .section-title {
            background-color: #f2f2f2;
            font-weight: bold;
            padding: 3px;
            margin-top: 6px;
            margin-bottom: 3px;
            border-left: 4px solid #666;
            font-size: 10pt;
        }
        
        .medication-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        .medication-table th {
            background-color: #f2f2f2;
            text-align: left;
            padding: 3px;
            border: 1px solid #ddd;
            font-size: 8pt;
        }
        .medication-table td {
            padding: 3px;
            border: 1px solid #ddd;
            font-size: 8pt;
        }
        
        .signature-section {
            position: fixed;
            bottom: 10px;
            left: 10px;
            right: 10px;
        }
        
        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }
        .signature-table td {
            width: 50%;
            padding: 5px;
            text-align: center;
            vertical-align: top;
            font-size: 8pt;
        }
        
        .barcode {
            margin: 8px 0;
            height: 100px;
        }
        
        .footer {
            text-align: center;
            font-weight: bold;
            padding-top: 5px;
            border-top: 1px solid #ddd;
            margin-top: 5px;
            font-size: 8pt;
        }
        
        .attention-box {
            background-color: #fff9e6;
            border: 1px solid #ffd699;
            padding: 4px;
            border-radius: 3px;
            font-size: 8pt;
        }
        
        ul {
            margin: 3px 0;
            padding-left: 18px;
        }
        
        h4 {
            margin: 5px 0 2px 0;
            font-size: 10pt;
        }
        
        .numbered-list {
            counter-reset: item;
            list-style-type: none;
            padding-left: 0;
            margin: 3px 0;
        }
        
        .numbered-list li {
            counter-increment: item;
            margin-bottom: 2px;
            display: flex;
            align-items: center;
        }
        
        .numbered-list li:before {
            content: counter(item) ".";
            font-weight: bold;
            margin-right: 5px;
            min-width: 15px;
        }
        
        .content-wrapper {
            padding-bottom: 120px; /* Space for fixed signature section */
        }
        
        p {
            margin: 3px 0;
        }
        
        .compact-section {
            display: inline-block;
            width: 32%;
            vertical-align: top;
        }
        
        .date-right {
            text-align: right;
            margin-top: -8px;
            font-size: 9pt;
        }
        
        /* Two-column table for side-by-side sections */
        .two-col-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 5px 0;
            margin-bottom: 6px;
        }
        
        .two-col-table td {
            width: 50%;
            vertical-align: top;
            padding: 0;
        }
        
        .storage-item {
            opacity: 0.3;
        }
        
        .storage-active {
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="content-wrapper">
        <!-- Restructured header with left-aligned info and centered title -->
        <div class="header-container">
            <div class="header-left">
                <h2>{{ strtoupper($visitation->klinik->nama ?? 'KLINIK UTAMA PREMIERE BELOVA') }}</h2>
                <p>
                    Jl. Melon Raya No.27, Karangasem, Laweyan, Surakarta<br>
                    Telp. 0821-1600-0093<br>
                    www.premierebelova.id
                </p>
            </div>
        </div>
        
        <div class="header-title">
            <div class="title">EDUKASI OBAT OLEH APOTEKER</div>
        </div>
        
        <div class="date-right">
            Tanggal Resep : {{ \Carbon\Carbon::parse($visitation->tanggal_visitation)->format('d F Y') }}
        </div>

        <!-- Patient Information -->
        <table class="info-table">
            <tr>
                <th width="20%">No Rekam Medis</th>
                <td width="30%">{{ $visitation->pasien->id }}</td>
                <th width="20%">Nama Pasien</th>
                <td width="30%">{{ $visitation->pasien->nama }}</td>
            </tr>
            <tr>
                <th>NIK</th>
                <td>{{ $visitation->pasien->nik }}</td>
                <th>Tanggal Lahir</th>
                <td>{{ \Carbon\Carbon::parse($visitation->pasien->tanggal_lahir)->format('d-m-Y') }}</td>
            </tr>
        </table>

        <!-- Allergy & Storage Section - Side by side using table layout -->
        <table class="two-col-table">
            <tr>
                <td>
                    <div class="section-title">Riwayat Alergi</div>
                    <div class="section">
                        @if($alergis->count() > 0)
                            <ul style="margin: 2px 0; padding-left: 15px;">
                                @foreach($alergis as $alergi)
                                    <li>{{ $alergi->zataktif_nama }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p>Tidak ada riwayat alergi</p>
                        @endif
                    </div>
                </td>
                <td>
                    <div class="section-title">PENYIMPANAN OBAT</div>
                    <div class="section">
        @php
            $hasStorage = false;
            $storageItems = [];
            
            if($edukasi->simpan_etiket_label) {
                $storageItems[] = 'Simpan obat dalam Etiket Label';
                $hasStorage = true;
            }
            if($edukasi->simpan_suhu_kulkas) {
                $storageItems[] = 'Simpan di suhu kulkas';
                $hasStorage = true;
            }
            if($edukasi->simpan_tempat_kering) {
                $storageItems[] = 'Simpan di tempat kering, suhu kamar';
                $hasStorage = true;
            }
            if($edukasi->hindarkan_jangkauan_anak) {
                $storageItems[] = 'Hindarkan dari jangkauan anak';
                $hasStorage = true;
            }
        @endphp
        
        @if($hasStorage)
            <ul class="numbered-list">
                @foreach($storageItems as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        @else
            <p>-</p>
        @endif
    </div>
                </td>
            </tr>
        </table>

        <!-- Medication Section -->
        <div class="section-title">DAFTAR OBAT</div>
        <div class="section">
            @if($nonRacikans->count() > 0)
                <h4 style="margin: 3px 0 1px 0;">Non Racikan</h4>
                <table class="medication-table">
                    <thead>
                        <tr>
                            <th width="4%">No</th>
                            <th width="41%">Nama Obat</th>
                            <th width="15%">Jumlah</th>
                            <th width="40%">Aturan Pakai</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($nonRacikans as $index => $resep)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $resep->obat->nama }}</td>
                                <td>{{ $resep->jumlah }} {{ $resep->satuan }}</td>
                                <td>{{ $resep->aturan_pakai }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p>Tidak Ada Obat Non Racikan</p>
            @endif
            
            @if($racikans->count() > 0)
                <h4 style="margin: 3px 0 1px 0;">Racikan</h4>
                <table class="medication-table">
                    <thead>
                        <tr>
                            <th width="4%">No</th>
                            <th width="51%">Nama Racikan</th>
                            <th width="15%">Jumlah</th>
                            <th width="30%">Aturan Pakai</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $racikanIndex = 1; @endphp
                        @foreach($racikans as $key => $items)
                            <tr>
                                <td>{{ $racikanIndex }}</td>
                                <td>Racikan {{ $key }} (
                                    @foreach($items as $index => $resep)
                                        {{ $resep->obat->nama }}{{ $index + 1 < count($items) ? ', ' : '' }}
                                    @endforeach
                                )</td>
                                <td>{{ $items->first()->jumlah }} bungkus</td>
                                <td>{{ $items->first()->aturan_pakai }}</td>
                            </tr>
                            @php $racikanIndex++; @endphp
                        @endforeach
                    </tbody>
                </table>
            @else
                <p>Tidak Ada Obat Racikan</p>
            @endif
        </div>

        <!-- Attention Section -->
        <div class="section-title">PERHATIAN</div>
        <div class="section attention-box">
            <p>Bila muncul reaksi alergi atau reaksi efek samping yang berlebihan, hentikan penggunaan obat, segera konsultasikan ke dokter.</p>
        </div>

        <!-- Usage Instructions Section -->
        <div class="section-title">CARA PEMAKAIAN OBAT</div>
        <div class="section">
            <table class="info-table">
                <tr>
                    <th width="15%">Insulin</th>
                    <td width="35%">{{ $edukasi->insulin_brosur ? 'Lihat brosur edukasi no '.$edukasi->insulin_brosur : '-' }}</td>
                    <th width="15%">Inhalasi</th>
                    <td width="35%">{{ $edukasi->inhalasi_brosur ? 'Lihat brosur edukasi no '.$edukasi->inhalasi_brosur : '-' }}</td>
                </tr>
            </table>
            <p style="font-style: italic; font-size: 8pt;">*Jika masih ragu dengan EDUKASI/INFORMASI OBAT yang kami berikan silahkan hubungi nomor WA kami.</p>
        </div>
    </div>

    <!-- Signature Section -->
    <div class="signature-section">
        <table class="signature-table">
            <tr>
                <td>
                    <div><strong>APOTEKER PEMBERI EDUKASI</strong></div>
                    <div class="barcode">
                        {{-- <img src="data:image/png;base64,{{ DNS2D::getBarcodePNG('Apoteker: '.$edukasi->apoteker->name, 'QRCODE', 2, 2) }}" alt="QR Code"> --}}
                    </div>
                    <div>{{ $edukasi->apoteker->name }}</div>
                </td>
                <td>
                    <div><strong>PASIEN/KELUARGA PASIEN PENERIMA EDUKASI OBAT</strong></div>
                    <div class="barcode">
                        {{-- <img src="data:image/png;base64,{{ DNS1D::getBarcodePNG($visitation->pasien->id, 'C128', 1.2, 40) }}" alt="Barcode"> --}}
                    </div>
                    <div>{{ $visitation->pasien->nama }}</div>
                </td>
            </tr>
        </table>

        <div class="footer">
            Bawa Selalu Obat Saat Berkunjung Ke Klinik
        </div>
    </div>
</body>
</html>