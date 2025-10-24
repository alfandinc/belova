<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Dana - {{ $pengajuan->kode_pengajuan ?? $pengajuan->id }}</title>
    <style>
    @page { margin: 18mm 12mm; }
    body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 12px; color: #222; }
    /* Use a table-based header for reliable DomPDF rendering: logo and text in one row */
    .header { display: block; margin-bottom: 6px; }
    .header-table { width:100%; border-collapse:collapse; border:none; }
    .brand { display:block; }
    .brand img { max-height:60px; margin:0; }
    /* header meta should be right aligned (logo stays left) */
    .header-meta { text-align: right; }
    .brand .title { font-size:16px; font-weight:700; color:#0b3d91; }
    .meta { margin-top:6px; margin-bottom:10px; }
    .meta .left, .meta .right { width:48%; display:inline-block; vertical-align:top; }
    .meta .right { text-align:right; }
    hr.separator { border: none; border-top: 2px solid #0b3d91; margin: 8px 0 12px; }
    table { width:100%; border-collapse: collapse; }
    th, td { border: 1px solid #ddd; padding: 8px; }
    th { background: #f5f7fb; color:#111; font-weight:600; }
    tbody tr:nth-child(even) { background: #fbfdff; }
    .text-right { text-align: right; }
    .totals { margin-top:10px; width:300px; float:right; border:1px solid #e1e6ef; padding:8px; background:#f8fbff; }
    .totals .row { display:flex; justify-content:space-between; padding:4px 0; }
    /* Signatures layout: table-based 3-per-row for DomPDF compatibility */
    .signatures-table { width:100%; border-collapse:collapse; margin-top:24px; border: none; }
    /* remove borders for signature table so boxes aren't outlined */
    .signatures-table td, .signatures-table th, .signature-cell { border: none; }
    .signature-cell { width:33%; vertical-align:top; text-align:center; padding:6px 8px; page-break-inside:avoid; }
    .signature { display:block; }
    .signature .label { font-size:11px; font-weight:700; margin-bottom:6px; display:block; }
    .signature .name { font-size:11px; font-weight:600; margin-top:6px; }
    .signature .date { font-size:11px; color:#666; }
    .signature .jabatan { font-size:11px; color:#444; font-style:italic; margin-top:4px; }
    .small { font-size:11px; color:#666; }
    .page-break { page-break-before: always; }
    .faktur-header { text-align:center; margin-bottom:8px; }
    </style>
</head>
<body>

    <div class="header">
        <table class="header-table">
            <tr>
                <td style="width:1%; vertical-align:middle; border:none; padding:0;">
                    <div class="brand">
                        @if(!empty($logoPath) && file_exists($logoPath))
                            <img src="{{ $logoPath }}" alt="Logo">
                        @endif
                    </div>
                </td>
                <td class="header-meta" style="vertical-align:middle; border:none; padding-left:12px;">
                    <div style="font-weight:700; font-size:16px; color:#0b3d91;">Pengajuan Dana</div>
                    <div class="small">Kode: <strong>{{ $pengajuan->kode_pengajuan }}</strong></div>
                    <div class="small">Tanggal: {{ $pengajuan->tanggal_pengajuan }}</div>
                </td>
            </tr>
        </table>
    </div>
    <hr class="separator">

    <div class="meta">
        <div class="left">
            <div><strong>Employee:</strong> {{ optional($pengajuan->employee)->nama ?? optional($pengajuan->employee->user ?? null)->name ?? '' }}</div>
            <div><strong>Division:</strong> {{ optional($pengajuan->division)->name ?? '-' }}</div>
            <div><strong>Jenis:</strong> {{ $pengajuan->jenis_pengajuan ?? '-' }}</div>
        </div>
    
    <h4 style="margin-top:6px; margin-bottom:6px;">Detail Items</h4>
    <table>
        <thead>
            <tr>
                <th style="width:4%">#</th>
                <th>Nama Item</th>
                <th style="width:10%">Qty</th>
                <th style="width:18%">Harga Satuan</th>
                <th style="width:18%">Total</th>
            </tr>
        </thead>
        <tbody>
        @foreach($pengajuan->items as $i => $it)
            <tr>
                <td class="text-center">{{ $i+1 }}</td>
                <td>{{ $it->nama_item }}</td>
                <td class="text-right">{{ $it->jumlah ?? $it->qty ?? 1 }}</td>
    
                <td class="text-right">Rp {{ number_format($it->harga_satuan ?? 0, 2, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format((($it->jumlah ?? 1) * ($it->harga_satuan ?? 0)), 2, ',', '.') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div class="row"><div>Subtotal</div><div>Rp {{ number_format($pengajuan->grand_total ?? 0, 2, ',', '.') }}</div></div>
    </div>

    <div style="clear:both"></div>

    <h4 style="margin-top:24px;">Tanda Tangan / Persetujuan</h4>

    @php
        $signs = isset($signatures) && is_array($signatures) ? $signatures : [];
        if (empty($signs)) {
            $creatorName = optional($pengajuan->employee->user)->name ?? optional($pengajuan->employee)->nama ?? '';
            $signs = [ ['label' => 'Dibuat oleh', 'name' => $creatorName, 'date' => ($pengajuan->tanggal_pengajuan ? \Carbon\Carbon::parse($pengajuan->tanggal_pengajuan)->format('d M Y') : ''), 'qr' => null] ];
        }
        $chunks = array_chunk($signs, 3);
    @endphp

    <table class="signatures-table">
        <tbody>
        @foreach($chunks as $row)
            <tr>
                @foreach($row as $s)
                    <td class="signature-cell">
                        <div class="signature">
                            <div class="label">{{ $s['label'] }}</div>
                            @if(!empty($s['qr']))
                                <img src="{{ $s['qr'] }}" alt="QR TTD" style="height:70px; display:block; margin:0 auto 6px;" />
                            @else
                                <div style="height:70px"></div>
                            @endif
                            <div class="name">{{ $s['name'] }}</div>
                            @if(!empty($s['jabatan']))
                                <div class="jabatan">{{ $s['jabatan'] }}</div>
                            @endif
                            <div class="date">{{ $s['date'] }}</div>
                        </div>
                    </td>
                @endforeach
                @if(count($row) < 3)
                    @for($i = 0; $i < 3 - count($row); $i++)
                        <td class="signature-cell"></td>
                    @endfor
                @endif
            </tr>
        @endforeach
        </tbody>
    </table>

    <div style="margin-top:12px; font-size:11px; color:#666;">Printed at: {{ \Carbon\Carbon::now()->format('d M Y H:i') }}</div>

    @if(!empty($fakturs) && count($fakturs))
        @foreach($fakturs as $faktur)
            <div class="page-break">
                <div class="faktur-header">
                    <h3>Faktur Pembelian (Terlampir)</h3>
                    <div class="small">No Faktur: <strong>{{ $faktur->no_faktur }}</strong> &nbsp;•&nbsp; Tanggal: {{ $faktur->received_date ?? $faktur->created_at }}</div>
                    <div class="small">Pemasok: {{ $faktur->pemasok->nama ?? '-' }}</div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Obat</th>
                            <th>Diminta</th>
                            <th>Diterima</th>
                            <th>Amount</th>
                            <th>Diskon</th>
                            <th>Tax</th>
                            <th>Total Amount</th>
                            <th>Gudang</th>
                            <th>Batch</th>
                            <th>Exp. Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($faktur->items as $i => $item)
                            <tr>
                                <td>{{ $i+1 }}</td>
                                <td>{{ $item->obat->nama ?? $item->obat_nama ?? '-' }}</td>
                                <td class="text-right">{{ $item->diminta ?? '' }}</td>
                                <td class="text-right">{{ $item->qty ?? '' }}</td>
                                <td class="text-right">{{ number_format($item->harga ?? 0, 2, ',', '.') }}</td>
                                <td class="text-right">{{ $item->diskon ?? 0 }} {{ ($item->diskon_type ?? '') == 'percent' ? '%' : 'Rp' }}</td>
                                <td class="text-right">{{ $item->tax ?? 0 }} {{ ($item->tax_type ?? '') == 'percent' ? '%' : 'Rp' }}</td>
                                <td class="text-right">{{ number_format($item->total_amount ?? 0, 2, ',', '.') }}</td>
                                <td>{{ $item->gudang->nama ?? '-' }}</td>
                                <td>{{ $item->batch ?? '' }}</td>
                                <td>{{ $item->expiration_date ?? '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <table style="width:100%; margin-top:10px;">
                    <tr>
                        <td style="border:none; text-align:right; font-weight:700;">Subtotal:</td>
                        <td style="border:1px solid #eaeef7; padding:6px; width:180px; text-align:right;">{{ number_format($faktur->subtotal ?? 0, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td style="border:none; text-align:right; font-weight:700;">Global Diskon:</td>
                        <td style="border:1px solid #eaeef7; padding:6px; text-align:right;">{{ number_format($faktur->global_diskon ?? 0, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td style="border:none; text-align:right; font-weight:700;">Global Pajak:</td>
                        <td style="border:1px solid #eaeef7; padding:6px; text-align:right;">{{ number_format($faktur->global_pajak ?? 0, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td style="border:none; text-align:right; font-weight:700;">Total:</td>
                        <td style="border:1px solid #eaeef7; padding:6px; text-align:right;">{{ number_format($faktur->total ?? 0, 2, ',', '.') }}</td>
                    </tr>
                </table>

                <div style="margin-top:20px;">Catatan: {{ $faktur->notes ?? '-' }}</div>
            </div>
        @endforeach
    @endif

</body>
</html>
