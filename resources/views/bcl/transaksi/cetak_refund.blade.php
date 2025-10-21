@extends('layouts.bcl.app')

@section('content')
<?php
use Carbon\Carbon;
$transaksi = $transaksi ?? null;
$refund = $refund ?? null; // object with amount, tanggal, alasan, no_exp maybe
$journal_lines = $journal_lines ?? null; // optional Fin_jurnal lines
$__signedUserName = Auth::user()->name ?? '';
$__signedUserQrRemote = 'https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=' . urlencode($__signedUserName);
$__signedUserQr = $__signedUserQrRemote;
try {
    $qr_contents = @file_get_contents($__signedUserQrRemote);
    if ($qr_contents !== false) {
        $mime = 'image/png';
        $__signedUserQr = 'data:' . $mime . ';base64,' . base64_encode($qr_contents);
    }
} catch (\Exception $e) {
}
?>
@section('page_css')
<link href="{{asset('plugins/datatables/responsive.bootstrap4.min.css')}}" rel="stylesheet" type="text/css" />
<style>
.table td { vertical-align: top !important; }
.invoice-head { border-bottom: 0px white !important; }
</style>
@endsection

<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
            <div class="row">
                <div class="col">
                    <h4 class="page-title">Refund Transaksi</h4>
                    <span>{{config('app.name')}}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card" id="print_this">
            <div class="card-body invoice-head top-color">
                <div class="row align-items-center mb-2" style="border-bottom: 4px double #b6c2e4;">
                    <div class="col-sm-4 align-self-center">
                        <img src="{{asset('assets/images/')}}/logo_full.png" alt="logo-small" class="logo-sm mr-1" height="84">
                        <p class="mt-0 mb-1 text-muted">{{config('app.name')}} {{config('app.tagline')}}</p>
                    </div>
                    <div class="col-sm-8 text-right">
                        <h4 class="mb-2 mt-0 p-0"><b>REFUND</b></h4>
                        <h6 style="margin-top: 0px; padding-top: 0px;"><b>Nomor: </b><span class="inv_number">{{ $refund->doc_id ?? ($transaksi->trans_id ?? '') }}</span></h6>
                        <h6 class="mb-0"><b>Tanggal: </b> {{ $refund->tanggal ?? date('Y-m-d') }}</h6>
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col-sm-12">
                        <div class="p-3" style="border:1px solid #e9ecef;border-radius:6px;background:#fafafa;">
                            <div class="row">
                                <div class="col-md-8">
                                    <h6 class="mb-1"><strong>Ditujukan Kepada:</strong></h6>
                                    @if(isset($transaksi) && isset($transaksi->renter))
                                    <div><b>{{ $transaksi->renter->nama }}</b> <br>
                                        Hp: {{ $transaksi->renter->phone ?? '-' }} <br>
                                        {{ $transaksi->renter->alamat ?? '' }}
                                    </div>
                                    @else
                                    <div>- Tidak tersedia (dokumen: {{ $refund->doc_id ?? '-' }})</div>
                                    @endif
                                </div>
                                <div class="col-md-4 text-right">
                                    <h6 class="mb-1"><strong>Jumlah Refund</strong></h6>
                                    <div style="font-size:1.25rem;font-weight:700;color:#1b5e20;">Rp {{ number_format($refund->amount ?? 0, 0) }}</div>
                                    <div style="font-size:0.9rem;color:#6c757d;">Alasan: {{ $refund->alasan ?? ($refund->reason ?? '-') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-sm-12">
                        <p>Catatan transaksi terkait:</p>
                        <ul>
                            <li>Trans ID asli: {{ $transaksi->trans_id ?? '-' }}</li>
                            <li>Room: {{ $transaksi->room->room_name ?? '-' }}</li>
                            <li>Periode: {{ $transaksi->tgl_mulai ?? '-' }} s/d {{ $transaksi->tgl_selesai ?? '-' }}</li>
                        </ul>
                        @if($journal_lines && count($journal_lines)>0)
                        <h6>Rincian (dokumen: {{ $refund->doc_id ?? '' }})</h6>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th style="width:60px;">No</th>
                                    <th>Jumlah</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1</td>
                                    <td class="text-right">Rp {{ number_format($refund->amount ?? 0,0) }}</td>
                                    <td>
                                        @php
                                            // combine journal notes for clarity
                                            $notes = collect($journal_lines)->pluck('catatan')->filter()->unique()->values()->all();
                                        @endphp
                                        {{ implode(' | ', $notes) ?: '-' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        @endif
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-6 mt-2">
                        <h6>Syarat & Ketentuan:</h6>
                        <ul class="pl-3">
                            <li><small class="font-12">Refund akan diproses sesuai kebijakan internal.</small></li>
                            <li><small class="font-12">Simpan bukti refund ini sebagai bukti transaksi.</small></li>
                        </ul>
                    </div>
                    <div class="col-sm-6 text-center align-self-end">
                        <div style="display:flex;flex-direction:column;align-items:center;">
                            <img src="<?= $__signedUserQr ?>" alt="QR" style="height:80px;margin-bottom:6px;" />
                            <p class="border-top" style="margin-top:8px;font-size:12px;">{{ htmlspecialchars($__signedUserName) }}</p>
                        </div>
                    </div>
                </div>

                <hr>
                <div class="row d-print-none">
                    <div class="col-12 text-right">
                        <a class="btn btn-danger btn-sm mr-2" href="{{route('bcl.transaksi.index')}}"><i class="mdi mdi-chevron-left"></i> Kembali</a>
                        <button type="button" onclick="window.print()" class="btn btn-success btn-sm"><i class="mdi mdi-download"></i> Print / Download</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
@section('pagescript')
@stop
