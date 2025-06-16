
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Permintaan Radiologi</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.3;
            margin: 20px;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
        }
        .header-text {
            font-size: 10px;
        }
        .horizontal-line {
            border-top: 1px solid #000;
            margin: 5px 0;
        }
        .patient-info {
            width: 100%;
            margin-bottom: 20px;
        }
        .patient-info td {
            padding: 2px 5px;
            vertical-align: top;
        }
        .section-row {
            width: 100%;
            margin-bottom: 15px;
            clear: both;
        }
        .section {
            float: left;
            width: 23%;
            margin-right: 2%;
            min-height: 25px;
            margin-bottom: 10px;
        }
        .section-title {
            font-weight: bold;
            text-transform: uppercase;
            background-color: #003366;
            color: white;
            padding: 3px 0;
            margin-bottom: 5px;
            text-align: center;
        }
        .test-container {
            padding-bottom: 5px;
            /* Removed the border-bottom line */
        }
        .test-item {
            display: block;
            margin-bottom: 2px; /* Reduced from 4px to 2px */
            text-align: left;
            padding-left: 5px;
            font-size: 9.5px; /* Made text smaller */
            line-height: 1.1; /* Reduced line height */
        }
        .checkbox {
            display: inline-block;
            width: 9px; /* Made checkbox slightly smaller */
            height: 9px; /* Made checkbox slightly smaller */
            border: 1px solid #000;
            margin-right: 4px;
            position: relative;
            top: 1px;
        }
        .checked {
            background-color: #000;
        }
        .footer {
            margin-top: 30px;
            clear: both;
            width: 100%;
        }
        .signature-section {
            float: left;
            width: 30%;
            margin-right: 5%;
            text-align: center;
        }
        .clear {
            clear: both;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-text">
            Jl. Melon Raya I, No. 27, Karangasem, Laweyan, Surakarta, Jawa Tengah
            <br>☎ 0821 1600 0093 ✉ premierebelova@gmail.com
        </div>
    </div>
    <div class="horizontal-line"></div>
    
    <table class="patient-info">
        <tr>
            <td width="15%">Nama Pasien</td>
            <td width="1%">:</td>
            <td width="33%">{{ $pasienData['pasien']->nama }}</td>
            <td width="15%">Pengirim</td>
            <td width="1%">:</td>
            <td width="35%">{{ $visitation->dokter->user->name ?? '-' }}</td>
        </tr>
        <tr>
            <td>Tanggal Lahir</td>
            <td>:</td>
            <td>{{ $pasienData['pasien']->tanggal_lahir ? date('d-m-Y', strtotime($pasienData['pasien']->tanggal_lahir)) : '-' }}</td>
            <td>Alamat</td>
            <td>:</td>
            <td>{{ $pasienData['pasien']->alamat }}</td>
        </tr>
        <tr>
            <td>Jenis Kelamin</td>
            <td>:</td>
            <td>{{ $pasienData['pasien']->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
            <td>No HP</td>
            <td>:</td>
            <td>{{ $pasienData['pasien']->no_hp }}</td>
        </tr>
        <tr>
            <td>Diagnosa</td>
            <td>:</td>
            <td>{{ $visitation->diagnosa ?? '-' }}</td>
            <td>NO RM</td>
            <td>:</td>
            <td>{{ $pasienData['pasien']->id }}</td>
        </tr>
    </table>
    <div class="horizontal-line"></div>
    
    @php
        $categories = $radiologiCategories->toArray();
        $totalCategories = count($categories);
        $categoriesPerRow = 4;
    @endphp
    
    @for ($i = 0; $i < $totalCategories; $i += $categoriesPerRow)
        <div class="section-row">
            @for ($j = $i; $j < min($i + $categoriesPerRow, $totalCategories); $j++)
                <div class="section">
                    <div class="section-title">{{ strtoupper($radiologiCategories[$j]->nama) }}</div>
                    <div class="test-container">
                        @foreach($radiologiCategories[$j]->radiologiTests as $test)
                            <div class="test-item">
                                <span class="checkbox {{ in_array($test->id, $radiologiRequests) ? 'checked' : '' }}"></span>
                                {{ $test->nama }}
                            </div>
                        @endforeach
                    </div>
                </div>
            @endfor
            <div class="clear"></div>
        </div>
    @endfor
    
    <div class="horizontal-line"></div>
    
    <div class="footer">
        <div class="signature-section">
            <p>TTD PENGIRIM</p>
            <br><br><br>
            <p>__________________</p>
        </div>
        <div class="signature-section">
            <p>KETERANGAN:</p>
            <p style="text-align: left; padding-left: 20px;">
                * Puasa 10-12 jam<br>
                * Interpretasi hasil dan pengaturan terapi menjadi wewenang Call Center
            </p>
        </div>
        <div class="signature-section">
            <p>PEMERIKSAAN LAIN</p>
            <br><br><br>
            <p>__________________</p>
        </div>
        <div class="clear"></div>
    </div>
</body>
</html>