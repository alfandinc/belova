{{-- filepath: resources/views/erm/tindakan/inform-consent/pdf.blade.php --}}
<h3>Inform Consent</h3>
<p>Nama Pasien: {{ $nama_pasien }}</p>
<p>No. RM: {{ $no_rm }}</p>
<p>Tanggal: {{ $tanggal }}</p>
<p>Inform Consent: {{ $inform_consent_text }}</p>
{{-- Add other fields as needed --}}
<img src="{{ $signature }}" alt="Tanda Tangan" style="width:200px;">