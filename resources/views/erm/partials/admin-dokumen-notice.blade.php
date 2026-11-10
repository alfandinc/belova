{{-- Admin opening a dokumen page (not as Dokter / Perawat): saving changes the real visit --}}
@php $u = auth()->user(); @endphp
@if ($u && $u->hasRole('Admin') && !$u->hasAnyRole(['Dokter', 'Perawat']))
    <div class="alert alert-warning py-2 mb-3" role="alert">
        <i class="fas fa-user-shield mr-1"></i>
        <b>Akses Admin.</b> Halaman ini bisa dibuka untuk pengecekan dan testing, tapi <b>Simpan</b> di sini menyimpan ke kunjungan pasien sungguhan
        (asesmen dokter juga menandai kunjungan selesai). Untuk testing, pakai pasien / kunjungan uji.
    </div>
@endif
