@extends('layouts.erm.app')

@section('content')
<style>
.ticket-page {
    width: 600px;
    height: 800px;
    position: relative;
    background-image: url('{{ asset('img/templates/reg_ticket.jpg') }}');
    background-size: cover;
    background-position: center;
}
.ticket-name {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    top: calc(50% - 130px);
    font-size: 28px;
    font-weight: 700;
    color: #ffffff;
    text-shadow: 0 1px 2px rgba(0,0,0,0.6);
    text-align: center;
}
.ticket-category {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    top: calc(50% - 95px);
    font-size: 14px;
    color: #ffffff;
    text-align: center;
}
.ticket-barcode {
    position: absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
    background: transparent;
}
.ticket-barcode svg { width: 520px; height: auto; display: block; }
</style>

<div class="ticket-page">
    <div class="ticket-barcode" style="position:absolute;left:50%;top:44%;transform:translate(-50%,-50%);text-align:center;">
        <svg id="barcode"></svg>
        <div class="text-white small mt-2" style="font-weight:800;font-size:22px;margin-top:8px;">{{ $peserta->unique_code }}</div>
    </div>

    <div class="ticket-identity" style="position:absolute;left:50%;transform:translateX(-50%);top:62%;text-align:center;color:#ffffff;line-height:1.3;text-transform:uppercase;letter-spacing:0.5px;">
        <div style="font-size:18px;font-weight:800;margin-top:0;">Nama : <span style="font-weight:800;">{{ $peserta->nama_peserta }}</span></div>
        <div style="font-size:18px;font-weight:800;margin-top:6px;">No Telp : <span style="font-weight:800;">{{ $peserta->no_hp ?? '-' }}</span></div>
        <div style="font-size:18px;font-weight:800;margin-top:6px;">Email : <span style="font-weight:800;">{{ $peserta->email ?? '-' }}</span></div>
        <div style="font-size:18px;font-weight:800;margin-top:6px;">Ukuran Kaos : <span style="font-weight:800;">{{ strtoupper($peserta->ukuran_kaos ?? '-') }}</span></div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function(){
        try {
            JsBarcode('#barcode', '{{ addslashes($peserta->unique_code) }}', {
                format: 'CODE128',
                displayValue: false,
                width: 3,
                height: 120,
                margin: 10
            });
        } catch (e) {
            console.error('Barcode render error', e);
        }
    });
</script>
@endpush

@endsection
