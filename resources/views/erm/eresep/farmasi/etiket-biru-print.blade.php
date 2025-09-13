<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Etiket Biru</title>
    <style>
        .main-table, .main-table td, .main-table th {
            text-align: center;
        }
        .patient-box, .no-tanggal, .aturan-pakai-title, .obat-name, .usage-text, .checkbox-item, .expire-text, .clinic-info, .address-info, .pharmacist-info {
            text-align: center !important;
        }
        @page {
            margin: 0;
            size: 10cm 3.5cm;
        }
        html, body {
            box-sizing: border-box;
            width: 10cm;
            height: 3.5cm;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: Arial, sans-serif;
            width: 10cm;
            height: 3.5cm;
            overflow: hidden;
            background-color: #ffffff !important; /* page default white */
        }
        /* main label area stays blue */
        table { width: 100%; border-collapse: collapse; border-spacing: 0; }
        .main-table {
            position: fixed; /* fixed positions are respected by mPDF relative to the page */
            left: 0;
            top: 20mm; /* align below the white spacer */
            width: 100%;
            height: 15mm; /* 1.5cm */
            table-layout: fixed;
            background-color: #003cff;
            margin: 0;
            padding: 0;
            border-collapse: collapse;
            box-sizing: border-box; /* include borders in width */
        }
    /* DEBUG: visible borders to show actual bounds (remove after inspect) */
    body { border: 4px solid rgba(0,0,255,0.2) !important; box-sizing: border-box; }
    .main-table { border: 3px dashed red !important; box-sizing: border-box; }
        .main-table tr {
            height: 100%;
        }
        .main-table td {
            padding: 0.12cm 0.18cm;
            vertical-align: top;
            overflow: hidden;
            box-sizing: border-box;
        }
        .left-column, .middle-column, .right-column {
            vertical-align: top;
        }
        .left-column {
            width: 26%;
            border-right: 0.04cm solid #000; /* thin border in cm */
            box-sizing: border-box;
        }
        .middle-column {
            width: 44%;
            border-right: 0.04cm solid #000;
            box-sizing: border-box;
        }
        .right-column {
            width: 30%;
            font-size: 5pt; /* larger for right column */
            line-height: 1.05;
        }
        .no-tanggal {
            font-size: 4.5pt;
            font-weight: bold;
            margin-bottom: 0.5mm;
            white-space: nowrap;
        }
        .patient-box {
            border: none;
            border-radius: 0;
            background-color: transparent;
            padding: 0;
            text-align: center;
            font-weight: bold;
            font-size: 7pt;
            line-height: 1.2;
            width: 4cm;
            height: 1cm;
            display: table-cell;
            vertical-align: middle;
            display: block;
        }
        .aturan-pakai-title {
            font-weight: bold;
            font-size: 6pt;
            text-align: center;
            margin-bottom: 0.5mm;
        }
        .obat-name {
            font-weight: bold;
            font-size: 5pt;
            text-align: center;
            margin-bottom: 0.5mm;
            line-height: 1;
        }
        .usage-text {
            font-size: 4pt;
            text-align: center;
            margin-bottom: 0.5mm;
            line-height: 1;
        }
        .checkbox-container {
            width: 100%;
            margin-bottom: 0.05cm;
        }
        .checkbox-item {
            display: inline-block;
            text-align: center;
            font-size: 4pt;
            width: 0.4cm;
        }
        .checkbox {
            width: 0.4cm;
            height: 0.4cm;
            border: 1px solid #000;
            background-color: white;
            margin-bottom: 0.05cm;
        }
        .expire-text {
            font-size: 6pt; /* increased from 4pt to 6pt */
            text-align: center;
            font-weight: bold;
            line-height: 1;
        }
        .clinic-info {
            font-size: 5.5pt; /* larger */
            font-weight: bold;
            line-height: 1;
            text-align: center;
            margin-bottom: 0.15cm;
        }
        .address-info {
            font-size: 4.5pt; /* larger */
            line-height: 1;
            margin-top: 0.4cm;
            text-align: center;
        }
        .pharmacist-info {
            font-size: 4.5pt; /* larger */
            line-height: 1;
            padding-top: 0.35cm; /* use padding to ensure spacing inside table cell */
            display: block;
            text-align: center;
        }
    </style>
</head>
<body>
    <div style="height:2cm; background-color:#ffffff; width:100%;"></div>
    <table class="main-table" style="width:100%;height:1.5cm;border-collapse:collapse;">
        <tr>
            <td class="left-column">
                <div class="no-tanggal">No. ___ Tanggal ___/___/___</div>
                <hr style="margin: 0.05cm 0 0.3cm 0; border: none; border-top: 1px solid #000;">
                <div class="patient-box">
                    {{ strtoupper($pasien->nama ?? 'PATIENT') }}
                </div>
            </td>
            <td class="middle-column">
                <div class="obat-name">{{ strtoupper($obat->nama ?? 'OBAT LUAR') }}</div>
                <div class="aturan-pakai-title">OBAT LUAR</div>
                <div class="usage-text">
                        Oles tipis tipis pada wajah/kulit setiap
                </div>
                <div class="checkbox-container">
                    <table style="width:100%; margin:0 auto; border-collapse:collapse;">
                        <tr>
                            <td style="text-align:center; vertical-align:middle; width:25%;">
                                <input type="checkbox" style="width:0.4cm;height:0.4cm;vertical-align:middle;">
                                <span style="font-size:4pt; vertical-align:middle; margin-left:0.1cm;">Pagi</span>
                            </td>
                            <td style="text-align:center; vertical-align:middle; width:25%;">
                                <input type="checkbox" style="width:0.4cm;height:0.4cm;vertical-align:middle;">
                                <span style="font-size:4pt; vertical-align:middle; margin-left:0.1cm;">Siang</span>
                            </td>
                            <td style="text-align:center; vertical-align:middle; width:25%;">
                                <input type="checkbox" style="width:0.4cm;height:0.4cm;vertical-align:middle;">
                                <span style="font-size:4pt; vertical-align:middle; margin-left:0.1cm;">Sore</span>
                            </td>
                            <td style="text-align:center; vertical-align:middle; width:25%;">
                                <input type="checkbox" style="width:0.4cm;height:0.4cm;vertical-align:middle;">
                                <span style="font-size:4pt; vertical-align:middle; margin-left:0.1cm;">Malam</span>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="expire-text">
                    Baik digunakan sebelum : {{ $expire_date->format('d/m/Y') ?? '___/___/___' }}
                </div>
            </td>
            <td class="right-column">
                <div class="clinic-info">
                    INSTALASI FARMASI<br>
                    KLINIK PRATAMA<br>
                    BELOVA SKIN & BEAUTY CENTER
                </div>
                <div style="height:0.5cm;"></div>
                <div class="address-info">
                    Jl. Melon Raya 1 No.29,<br>
                    Laweyan, Surakarta 085100990319
                </div>
                <div class="pharmacist-info">
                    apt. Noor HesthisaraHudana Reswar, S.Farm <br>
                    SIP : NR33722503003873
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
