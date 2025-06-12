<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Resep Dokter</title>
    <style>
        @page {
            size: a4 landscape;
            margin: 8mm; /* Reduced page margins */
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10px; /* Reduced base font size */
            padding: 0;
            margin: 0;
            line-height: 1.1; /* Tighter line height */
        }
        .header-left {
            text-align: left;
            padding-bottom: 3px; /* Reduced padding */
            margin-bottom: 10px; /* Reduced margin */
        }
        .header-left h2 {
            margin: 0;
            font-size: 14px;
            font-weight: bold;
        }
        .header-left p {
            margin: 1px 0; /* Reduced margin */
            font-size: 9px; /* Smaller text */
        }
        .resep-header {
            text-align: center;
            font-weight: bold;
            margin: 3px 0; /* Reduced margin */
            font-size: 12px;
        }

        table.info-table {
            width: 100%;
            margin-bottom: 3px; /* Reduced margin */
            border-spacing: 0;
        }
        table.info-table td {
            padding: 0; /* Removed padding */
            font-size: 10px; /* Smaller text */
            vertical-align: top;
        }
        .med-list {
            width: 100%;
            border-collapse: collapse;
        }
        .med-list td {
            vertical-align: top;
            padding: 0; /* Removed padding */
            line-height: 1.1;
        }
        .checklist {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px; /* Reduced margin */
        }
        .checklist th, .checklist td {
            border: 1px solid black;
            padding: 3px 3px; /* Reduced padding */
            font-size: 10px; /* Smaller text */
        }
        .checklist th {
            background-color: #f0f0f0;
            text-align: left;
            font-weight: bold;
            padding: 3px 3px; /* Reduced padding */
        }
        .checklist td.center {
            text-align: center;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px; /* Reduced margin */
        }
        .footer-table td {
            border: 1px solid black;
            padding: 2px; /* Reduced padding */
            font-size: 8px; /* Smaller text */
            vertical-align: top;
        }
        .verification-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }
        .verification-table td,
        .verification-table th {
            border: 1px solid black;
            font-size: 10px; /* Smaller text */
            padding: 1px 2px; /* Reduced padding */
        }
        .no-resep {
            position: absolute;
            top: 2px;
            right: 15px;
            font-size: 32px; /* Smaller text */
            font-weight: bold;
        }
        .action-buttons {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px; /* Reduced margin */
        }
        .action-buttons td {
            border: 1px solid black;
            text-align: center;
            padding: 2px; /* Reduced padding */
            font-size: 9px; /* Smaller text */
        }
        .date-print {
            text-align: right;
            font-size: 10px; /* Smaller text */
            margin-top: 2px; /* Reduced margin */
        }
        .main-table {
            width: 100%;
            border-collapse: collapse;
        }
        .checklist-header {
            font-weight: bold;
            text-align: center;
            border: none;
            margin-bottom: 2px; /* Reduced margin */
            font-size: 12px; /* Smaller text */
        }
    </style>
</head>
<body>
    <!-- Using a table for the main content to ensure proper column layout -->
    <table class="main-table">
        <tr>
            <!-- Left side - Header and Prescription information -->
            <td style="width: 50%; vertical-align: top; padding-right: 5px;">
                <!-- Left Header -->
                <div class="header-left">
                    <h2>KLINIK UTAMA PREMIERE BELOVA</h2>
                    
                    <p>Jl. Melon Raya No.27, Karangasem, Laweyan, Surakarta<br>
                        Telp. 0821-1600-0093 <br>
                        www.premierebelova.id <br>
                    </p>
                </div>
                
                <div class="resep-header">RESEP DOKTER</div>
                
                <div style="text-align: right; margin-top: -15px; font-size: 10px;">Tanggal Resep : 12 / 06 / 2025 10:05</div>
                
                <!-- Patient Information -->
                <table class="info-table">
                    <tr>
                        <td width="20%">No. RM</td>
                        <td width="2%">:</td>
                        <td width="78%">48392</td>
                    </tr>
                    <tr>
                        <td>Nama Pasien</td>
                        <td>:</td>
                        <td>MRS FATHLA ANANI</td>
                    </tr>
                    <tr>
                        <td>Tgl Lahir / Usia</td>
                        <td>:</td>
                        <td>17 December 1982 / 42 th 5 bl 26 hr</td>
                    </tr>
                    <tr>
                        <td>Alamat</td>
                        <td>:</td>
                        <td>JL MM NO 8 RT 01 RW 04 JAKARTA SELATAN</td>
                    </tr>
                    <tr>
                        <td>Nama Dokter</td>
                        <td>:</td>
                        <td>dr. Ahmad Akbar Sp. PD.</td>
                    </tr>
                </table>

                <!-- Medications List -->
                <table class="med-list">
                    <tr>
                        <td width="5%">1</td>
                        <td width="45%">VELTHROM 10MG TABLET</td>
                        <td width="20%">30 TAB</td>
                        <td width="30%">1x1</td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>PROPRANOLOL HCL 10 MG TAB*</td>
                        <td>60 TAB</td>
                        <td>2x1</td>
                    </tr>
                </table>

                <!-- Action Buttons -->
                <table class="action-buttons">
                    <tr>
                        <td width="33.3%">UNDUH</td>
                        <td width="33.4%">AMBIL</td>
                        <td width="33.3%">RACIK</td>
                    </tr>
                    <tr>
                        <td height="40px" ></td>
                        <td ></td>
                        <td ></td>
                    </tr>
                </table>
                
                <div class="date-print">Tanggal Cetak : 12 / 06 / 2025 15:28</div>
            </td>
            
            <!-- Right side - Checklist forms -->
            <td style="width: 50%; vertical-align: top;">
                <div class="checklist-header">CHECKLIST PENGKAJIAN RESEP</div>
                
                
                <!-- 1. Persyaratan Administrasi -->
                <table class="checklist">
                    <tr>
                        <th colspan="3">1. Persyaratan Administrasi</th>
                        <th width="30%">KETERANGAN</th>
                    </tr>
                    <tr>
                        <td colspan="1" style="width:60%">Persyaratan</td>
                        <td style="width:10%; text-align:center">Ada*</td>
                        <td style="width:10%; text-align:center">Tidak*</td>
                        <td rowspan="1"></td>
                    </tr>
                    <tr>
                        <td>Nama/ no. RM/ umur/ jenis kelamin/ bangsal (barcode pasien)</td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Berat badan pasien</td>
                        <td></td>
                        <td></td>
                        <td>60</td>
                    </tr>
                    <tr>
                        <td>Nama dokter</td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Paraf dokter</td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Tanggal resep</td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                </table>

                <!-- 2. Persyaratan Farmasi -->
                <table class="checklist">
                    <tr>
                        <th colspan="3">2. Persyaratan Farmasi</th>
                        <th width="30%">KETERANGAN</th>
                    </tr>
                    <tr>
                        <td colspan="1" style="width:60%">Persyaratan</td>
                        <td style="width:10%; text-align:center">Ada*</td>
                        <td style="width:10%; text-align:center">Tidak*</td>
                        <td rowspan="1"></td>
                    </tr>
                    <tr><td>Nama obat</td><td></td><td></td><td></td></tr>
                    <tr><td>Bentuk sediaan</td><td></td><td></td><td></td></tr>
                    <tr><td>Kekuatan sediaan</td><td></td><td></td><td></td></tr>
                    <tr><td>Dosis obat</td><td></td><td></td><td></td></tr>
                    <tr><td>Jumlah obat</td><td></td><td></td><td></td></tr>
                    <tr><td>Aturan obat</td><td></td><td></td><td></td></tr>
                    <tr><td>Cara penggunaan</td><td></td><td></td><td></td></tr>
                </table>

                <!-- 3. Persyaratan Klinis -->
                <table class="checklist">
                    <tr>
                        <th colspan="3">3. Persyaratan Klinis</th>
                        <th width="30%">KETERANGAN</th>
                    </tr>
                    <tr>
                        <td colspan="1" style="width:60%">Persyaratan</td>
                        <td style="width:10%; text-align:center">Ada*</td>
                        <td style="width:10%; text-align:center">Tidak*</td>
                        <td rowspan="1"></td>
                    </tr>
                    <tr><td>Ketepatan indikasi</td><td></td><td></td><td></td></tr>
                    <tr><td>Ketepatan waktu penggunaan</td><td></td><td></td><td></td></tr>
                    <tr><td>Duplikasi pengobatan</td><td></td><td></td><td></td></tr>
                    <tr><td>Ketepatan dosis</td><td></td><td></td><td></td></tr>
                    <tr><td>Kontraindikasi</td><td></td><td></td><td></td></tr>
                    <tr><td>Alergi dan Hipersensitifitas</td><td></td><td></td><td></td></tr>
                    <tr><td>Interaksi obat dengan obat</td><td></td><td></td><td></td></tr>
                    <tr><td>Interaksi obat dengan makanan</td><td></td><td></td><td></td></tr>
                </table>

                <!-- DIAGNOSA -->
                <table class="checklist">
                    <tr>
                        <th style="text-align:left; font-weight:bold; background-color:#f0f0f0; padding:1px 2px;">DIAGNOSA</th>
                    </tr>
                    <tr>
                        <td style="padding:1px 2px;">Hipertrofd primer ec grave disease, dyspepsia</td>
                    </tr>
                </table>

                <!-- MASALAH & TINDAK LANJUT -->
                <table class="checklist">
                    <tr>
                        <th style="text-align:left; font-weight:bold; background-color:#f0f0f0; padding:1px 2px;">MASALAH & TINDAK LANJUT</th>
                    </tr>
                    <tr>
                        <td style="height:15px; padding:1px 2px;"></td> <!-- Reduced height -->
                    </tr>
                </table>
                
                <!-- Allergy Information and Pharmacist -->
                <table class="checklist" style="margin-bottom:2px;">
                    <tr>
                        <td style="width:50%; vertical-align:top; border-right:1px solid black; padding:1px 2px;">
                            <strong>ALERGI : </strong>debu
                        </td>
                        <td style="width:50%; vertical-align:top; padding:1px 2px;">
                            <strong>APOTEKER PENGKAJI : </strong>
                        </td>
                    </tr>
                </table>

                <!-- Verification Table - Complete remake to match exactly with example -->
                <table class="verification-table">
                    <tr>
                        <td style="width:20%; text-align:center; vertical-align:top;">
                            Double check obat<br>High Alert
                        </td>
                        <td style="width:25%; text-align:center; font-weight:bold; vertical-align:middle;">
                            VERIFIKASI OBAT
                        </td>
                        <td style="width:25%; text-align:center; font-weight:bold; vertical-align:middle;">
                            PENYERAHAN
                        </td>
                        <td style="width:20%; text-align:center; vertical-align:middle; padding:1px 2px;">
                            TTD Penerima Obat
                        </td>
                    </tr>
                    <tr>
                        <td rowspan="3" style="text-align:center; vertical-align:bottom;">
                            Paraf Penyiapan
                        </td>
                        <td>Identitas Pasien</td>
                        <td>Identitas Pasien</td>
                        <td rowspan="6"></td>
                    </tr>
                    <tr>
                        
                        <td>Ketepatan Obat</td>
                        <td>Ketepatan Obat</td>
                    </tr>
                    <tr>
                        <td>Dosis & jumlah obat</td>
                        <td>Dosis & jumlah obat</td>
                    </tr>
                    <tr>
                        <td rowspan="3" style="text-align:center; vertical-align:bottom;">
                            Paraf Pengecekan
                        </td>
                        <td>rute pemberian</td>
                        <td>rute pemberian</td>
                    </tr>
                    <tr>
                        <td>waktu pemberian</td>
                        <td>waktu pemberian</td>
                    </tr>
                    <tr>
                        
                        <td>paraf :</td>
                        <td>paraf :</td>
                    </tr>
                    
                </table>
            </td>
        </tr>
    </table>
</body>
</html>