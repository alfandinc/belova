<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Surat Permintaan</title>
	<style>
		body { font-family: sans-serif; font-size: 12px; }
		table { width: 100%; border-collapse: collapse; margin-top: 20px; }
		th, td { border: 1px solid #333; padding: 4px; text-align: left; }
		th { background: #eee; }
	</style>
</head>
<body>
	<table style="width:100%; border:none; margin-bottom: 10px;">
		<tr>
			<td style="width:60px; border:none; vertical-align:top;">
				<img src="{{ public_path('img/header-belova.png') }}" alt="Logo" style="height:80px;">
			</td>
			<td style="text-align:right; border:none; vertical-align:top;">
				<div style="text-align:right;">
					<span style="font-size:22px; font-weight:bold;">Surat Permintaan Pembelian</span><br>
					<span style="font-size:14px; font-weight:500;">Klinik Pratama belova Skin &amp; Beauty Center.</span><br>
					<span style="font-size:12px;">Jl. Melon raya No.29 RT 03 RW 07 Karangasem, Laweyan, Surakarta</span>
				</div>
			</td>
		</tr>
	</table>
	<table>
		<tr><th>No Permintaan</th><td>{{ $permintaan->no_permintaan }}</td></tr>
		<tr><th>Tanggal Permintaan</th><td>{{ $permintaan->request_date }}</td></tr>
		<tr><th>Status</th><td>{{ $permintaan->status }}</td></tr>
	</table>
	<h4>Daftar Item</h4>
	<table>
		<thead>
			<tr>
				<th>No</th>
				<th>Obat</th>
				<th>Pemasok</th>
				<th>Jumlah Box</th>
				<th>Qty Total</th>
			</tr>
		</thead>
		<tbody>
			@foreach($permintaan->items as $i => $item)
			<tr>
				<td>{{ $i+1 }}</td>
				<td>{{ $item->obat->nama ?? '-' }}</td>
				<td>{{ $item->pemasok->nama ?? '-' }}</td>
				<td>{{ $item->jumlah_box }}</td>
				<td>{{ $item->qty_total }}</td>
			</tr>
			@endforeach
		</tbody>
	</table>
	<div style="position: fixed; left: 0; bottom: 0; width: 200px;">
		<div style="margin-top: 40px;">
			<!-- mPDF barcode: type Code128, value is no_permintaan -->
			<barcode code="{{ $permintaan->no_permintaan }}" type="C128A" size="1.2" height="1.2" text="1" />
		</div>
	</div>
	@if($permintaan->status === 'approved' && $permintaan->approved_by)
		<div style="position: fixed; right: 0; bottom: 0; width: 320px; text-align: right;">
			<div style="margin-bottom: 40px;">
				<span style="font-size:15px;">Approved by:</span><br><br>
				{{-- <img src="{{ public_path('img/ttd-thesa.png') }}" alt="Signature Therese" style="height:50px; display:block; margin:0 auto 8px auto;"> --}}
				<img src="{{ public_path('img/ttd-thesa.png') }}" alt="Signature Therese" style="height:90px; display:block; margin:0 auto 8px auto;">
				<span style="display:inline-block; border-top:1.5px solid #333; width:200px; margin-bottom:2px;"></span><br>
				<span style="font-size:18px; font-weight:bold;">{{ optional(App\Models\User::find($permintaan->approved_by))->name ?? 'User ID: '.$permintaan->approved_by }}</span><br>
				<span style="font-size:18px; font-weight:bold;">SIP. NR 33722503003873</span><br>
				<span style="font-size:12px;">{{ $permintaan->approved_date }}</span>
			</div>
		</div>
	@endif
</body>
</html>
