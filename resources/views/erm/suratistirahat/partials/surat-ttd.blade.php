{{-- Date, doctor's QR signature (left out when the doctor has none) and name, bottom right --}}
<table class="ttd-table">
  <tr>
    <td style="width: 45%;"></td>
    <td style="width: 55%; text-align: center;">
      Surakarta, {{ $tanggal_surat }}<br>
      Dokter Pemeriksa,
      <div class="ttd-box">
        @if(!empty($ttd))
          <img src="{{ $ttd }}" alt="Tanda Tangan">
        @endif
      </div>
      <div class="ttd-name">{{ $nama_dokter }}</div>
      @if(!empty($spesialisasi))
        <div style="font-size: 10px;">{{ $spesialisasi }}</div>
      @endif
    </td>
  </tr>
</table>
