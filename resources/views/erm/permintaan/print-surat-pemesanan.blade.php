<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Pemesanan - {{ $permintaan->no_permintaan }}</title>
    <style>
        @page {
            size: 10.7cm 20cm;
            margin: 0;
        }

        :root {
            --page-width: 10.7cm;
            --page-height: 20cm;
            --font-main: Arial, Helvetica, sans-serif;
            --font-size-base: 10pt;
            --no-top: 3.9cm;
            --no-left: 3cm;
            --tanggal-top: 3.9cm;
            --tanggal-left: 8cm;
            --kepada-top: 4.63cm;
            --kepada-left: 3cm;
            --table-top: 6.18cm;
            --row-height: 0.92cm;
            --no-col-left: 0.9cm;
            --obat-col-left: 2.2cm;
            --obat-col-width: 5.45cm;
            --jumlah-col-left: 8.2cm;
            --jumlah-col-width: 1.5cm;
            --print-scale: 0.97;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            width: var(--page-width);
            min-height: var(--page-height);
            font-family: var(--font-main);
            font-size: var(--font-size-base);
            color: #000;
            background: #fff;
        }

        body {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .screen-toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            gap: 8px;
            align-items: center;
            padding: 12px;
            width: fit-content;
            margin: 12px auto;
            border: 1px solid #d7dbe4;
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        }

        .screen-toolbar button {
            border: 0;
            border-radius: 6px;
            padding: 8px 12px;
            font: inherit;
            cursor: pointer;
            background: #2563eb;
            color: #fff;
        }

        .screen-toolbar .secondary {
            background: #475569;
        }

        .screen-note {
            font-size: 9pt;
            color: #334155;
        }

        .sheet {
            position: relative;
            width: var(--page-width);
            height: var(--page-height);
            margin: 0 auto 12px;
            background: #fff;
            overflow: visible;
        }

        .field {
            position: absolute;
            white-space: nowrap;
        }

        .field-no {
            top: var(--no-top);
            left: var(--no-left);
            font-weight: 600;
            letter-spacing: 0.2px;
        }

        .field-tanggal {
            top: var(--tanggal-top);
            left: var(--tanggal-left);
            font-weight: 600;
            letter-spacing: 0.2px;
        }

        .field-kepada {
            top: var(--kepada-top);
            left: var(--kepada-left);
            width: 7cm;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-weight: 600;
        }

        .row {
            position: absolute;
            left: 0;
            width: 100%;
            height: var(--row-height);
            line-height: var(--row-height);
        }

        .cell {
            position: absolute;
            top: 0;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: clip;
        }

        .cell-no {
            left: var(--no-col-left);
            width: 0.8cm;
            text-align: center;
            font-weight: 600;
        }

        .cell-obat {
            left: var(--obat-col-left);
            width: var(--obat-col-width);
            padding-right: 0.1cm;
            font-size: 9pt;
        }

        .cell-jumlah {
            left: var(--jumlah-col-left);
            width: var(--jumlah-col-width);
            text-align: center;
            font-weight: 600;
        }

        @media print {
            html,
            body {
                overflow: visible;
            }

            .screen-toolbar {
                display: none;
            }

            .sheet {
                margin: 0;
                transform: scale(var(--print-scale));
                transform-origin: top left;
                page-break-after: always;
            }

            .sheet:last-child {
                page-break-after: auto;
            }
        }
    </style>
</head>
<body>
    <div class="screen-toolbar">
        <button type="button" onclick="window.print()">Print</button>
        <button type="button" class="secondary" onclick="window.close()">Tutup</button>
        <span class="screen-note">Jika posisi belum pas di kertas fisik, sesuaikan offset CSS di view ini.</span>
    </div>

    @foreach($pages as $pageIndex => $page)
        <div class="sheet">
            <div class="field field-no">{{ $permintaan->no_permintaan }}</div>
            <div class="field field-tanggal">{{ $requestDate }}</div>
            <div class="field field-kepada">{{ $pemasokName }}</div>

            @foreach($page as $rowIndex => $row)
                <div class="row" style="top: calc(var(--table-top) + {{ $rowIndex }} * var(--row-height));">
                    <div class="cell cell-no">{{ $row ? $rowIndex + 1 : '' }}</div>
                    <div class="cell cell-obat">{{ $row['nama_obat'] ?? '' }}</div>
                    <div class="cell cell-jumlah">{{ $row['jumlah'] ?? '' }}</div>
                </div>
            @endforeach
        </div>
    @endforeach

    <script>
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 300);
        });
    </script>
</body>
</html>