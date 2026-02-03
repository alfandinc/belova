<div class="ticket-page" style="width:600px;height:800px;position:relative;background-image:url('{{ asset('img/templates/reg_ticket.jpg') }}');background-size:cover;background-position:center;">
    <!-- barcode container is centered; contents stack vertically: code above, barcode SVG, then name/category below -->
    <div class="ticket-barcode" style="position:absolute;left:50%;top:44%;transform:translate(-50%,-50%);text-align:center;background:transparent;">
        <div id="modal-unique-code" style="color:#ffffff;font-weight:800;margin-bottom:6px;letter-spacing:2px;font-size:24px;">{{ $peserta->unique_code }}</div>
        <svg id="modal-barcode" style="display:block;margin:0 auto;width:460px;height:auto;"></svg>
    </div>

    <!-- identity block moved lower on the ticket -->
    <div class="ticket-identity" style="position:absolute;left:50%;transform:translateX(-50%);top:62%;text-align:center;color:#ffffff;line-height:1.3;text-transform:uppercase;letter-spacing:0.5px;">
        <div style="font-size:18px;font-weight:800;margin-top:0;">Nama : <span style="font-weight:800;">{{ $peserta->nama_peserta }}</span></div>
        <div style="font-size:18px;font-weight:800;margin-top:6px;">No Telp : <span style="font-weight:800;">{{ $peserta->no_hp ?? '-' }}</span></div>
        <div style="font-size:18px;font-weight:800;margin-top:6px;">Email : <span style="font-weight:800;">{{ $peserta->email ?? '-' }}</span></div>
        <div style="font-size:18px;font-weight:800;margin-top:6px;">Ukuran Kaos : <span style="font-weight:800;">{{ strtoupper($peserta->ukuran_kaos ?? '-') }}</span></div>
    </div>
</div>
