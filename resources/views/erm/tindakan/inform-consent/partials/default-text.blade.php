{{-- Generic inform consent text: used for a tindakan whose master has no consent text of its own --}}
<p>Saya yang bertanda tangan di bawah ini memberikan persetujuan untuk menjalani tindakan <strong>{{ $tindakan->nama }}</strong> di {{ $klinikNama }}.</p>

<p><strong>Deskripsi Tindakan:</strong>
{{ $tindakan->deskripsi ?: 'Tindakan ini merupakan prosedur medis/estetika yang dilakukan sesuai standar yang berlaku untuk membantu perbaikan kondisi pasien.' }}</p>

<p><strong>Manfaat:</strong></p>
<ol>
    <li>Mendapatkan perbaikan kondisi sesuai tujuan tindakan.</li>
    <li>Meningkatkan kenyamanan pasien.</li>
    <li>Memperbaiki kualitas hidup/kesehatan kulit sesuai indikasi.</li>
</ol>

<p><strong>Risiko dan Efek Samping:</strong></p>
<ol>
    <li>Kemerahan atau rasa tidak nyaman (sementara).</li>
    <li>Reaksi alergi atau iritasi (jarang).</li>
    <li>Hasil dapat bervariasi tergantung kondisi individu.</li>
</ol>

<p><strong>Alternatif:</strong> Saya memahami bahwa terdapat alternatif lain sesuai rekomendasi dokter atau tenaga medis yang berwenang.</p>

<p><strong>Persetujuan:</strong> Saya telah mendapatkan penjelasan mengenai tindakan <strong>{{ $tindakan->nama }}</strong>, termasuk manfaat, risiko, dan alternatifnya. Saya menyetujui untuk menjalani tindakan ini secara sukarela dan sadar sepenuhnya.</p>
