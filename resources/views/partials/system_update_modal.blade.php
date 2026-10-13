<!-- System Update Modal -->
{{-- Bump SYSTEM_UPDATE_VERSION (topbar_user_modals) with each new update so the modal auto-opens once again. --}}
<div class="modal fade" id="systemUpdateModal" tabindex="-1" role="dialog" aria-labelledby="systemUpdateModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="systemUpdateModalLabel">UPDATE SISTEM 05/10 – 12/10/2026</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p class="mb-3"><strong>Seminggu ini sistem berubah cukup banyak. Mohon dibaca, lalu cek menu yang biasa Anda pakai.</strong></p>
        <ul>
          <li><b>Tampilan &amp; Navigasi :</b>
            <ul>
              <li>Halaman utama sekarang <b>Dashboard</b>. Main menu lama dihapus; semua modul dibuka dari <b>sidebar kiri</b> yang sama di setiap modul</li>
              <li>Tema terang / gelap bisa diganti dari tombol di topbar</li>
              <li><b>Lonceng</b> di topbar: klik untuk melihat persetujuan, pengingat, dan tenggat yang menunggu tindakan Anda</li>
              <li>Topbar: user online &amp; mood, ganti mood, edit profil, dan informasi update</li>
              <li>Jadwal, My Payroll dan Gaji Dokter dibuka dari sidebar</li>
              <li>Notifikasi Farmasi / Perawat / Lab / Rawat Jalan hanya berjalan di satu tab per browser, jadi tidak dobel dan lebih ringan saat banyak tab terbuka</li>
            </ul>
          </li>
          <li><b>Dashboard :</b>
            <ul>
              <li>Widget tampil sesuai role: Kunjungan Hari Ini, Dokter Praktik, E-Resep, Stok Obat, Permintaan Lab, Kasir, Omset &amp; Target, Omset per Klinik, Sumber Omset, Retensi Pasien, Pasien Baru &amp; Referral, Kinerja Dokter, Statistik Pasien, Karyawan, Daily Journal, Ulang Tahun, Buku Menu</li>
              <li>Admin mengatur widget per role di <i>Admin Panel &gt; Widget Dashboard</i></li>
              <li>Daily Journal tim dilihat dari widget Daily Journal (pilihan Saya / Tim)</li>
              <li>Dashboard lama dihapus: HRD, Inventory, Marketing, BCL, R&amp;D, Analitik Lab, Social Media Analytics, Laporan, Insiden, SatuSehat</li>
            </ul>
          </li>
          <li><b>Pendaftaran &amp; Pasien :</b>
            <ul>
              <li>Tambah / edit pasien lewat form pop-up langsung dari daftar pasien</li>
              <li><b>Pasien Ganda</b>: sistem menampilkan pasien yang kemungkinan terdaftar lebih dari sekali dan bisa digabungkan menjadi satu</li>
              <li>Daftarkan kunjungan langsung dari follow up CRM (referral "Follow Up: nama sales")</li>
            </ul>
          </li>
          <li><b>Dokter &amp; E-Resep :</b>
            <ul>
              <li><b>Paket Racikan</b> punya halaman master sendiri; racikan mengingat paket asalnya, sehingga nama paket di resep, etiket dan billing selalu tepat</li>
              <li>Aturan pakai diisi dengan builder + saran dari riwayat resep (halaman master Aturan Pakai dihapus)</li>
              <li>Dokter bisa dinonaktifkan (tidak lagi dihapus): riwayat tetap ada, dokter tidak muncul di pilihan data baru</li>
            </ul>
          </li>
          <li><b>Farmasi – Master Obat :</b>
            <ul>
              <li>Tambah / edit obat lewat form pop-up; ada cek nama obat ganda</li>
              <li><b>Kode obat</b> dibuat ulang per kategori (PREFIX-00001), kode lama tetap tersimpan</li>
              <li><b>Satuan stok</b> dipisah dari satuan dosis; ejaan satuan diseragamkan, dan semua pilihan satuan diambil dari Master <b>Satuan</b></li>
              <li>Definisi harga: <b>HPP</b> = tanpa PPN, <b>HNA</b> = HPP + PPN (keduanya dari faktur), <b>Harga Jual</b> terpisah. Data lama yang terbalik sudah diperbaiki</li>
              <li>Principal (pabrik / pemilik merek) diisi di Master Obat; Pemasok &amp; Principal dikelola dari tombol "Kelola", dan data ganda <b>digabungkan</b> (yang sudah dipakai tidak bisa dihapus)</li>
              <li>Penanda obat <b>Wajib Ada</b> (mis. obat emergency, stok minimal tetap dijaga) dan <b>Stop Pengadaan</b> (masih dijual sampai stok habis, tidak disarankan dibeli lagi)</li>
            </ul>
          </li>
          <li><b>Farmasi – Harga Jual :</b>
            <ul>
              <li>Halaman baru <b>Harga Jual</b> (menggantikan Monitor Profit): margin per obat dan riwayat harga</li>
              <li>Perubahan harga jual lewat <b>pengajuan</b>: Farmasi / Admin mengajukan, Admin / Finance menyetujui</li>
              <li>Obat yang belum pernah dibeli menampilkan estimasi HPP / HNA dari Master Pembelian</li>
            </ul>
          </li>
          <li><b>Farmasi – Pembelian :</b>
            <ul>
              <li>Permintaan Pembelian: form pop-up, <b>saran jumlah dari forecast</b>, info obat, nilai pembelian, cetak Surat Permintaan &amp; Surat Pemesanan</li>
              <li>Master Pembelian: satu harga per obat + pemasok, input <b>penawaran vendor</b> (banyak obat sekaligus), bandingkan harga antar pemasok, export Excel</li>
              <li>Faktur Pembelian: form pop-up, preview sebelum approve, export item; satu obat bisa diterima dalam <b>beberapa batch</b>, dan ada baris <b>bonus</b> (gratis, harga 0, tidak mengubah HPP)</li>
              <li>Nomor dokumen pembelian unik; tercatat siapa yang membuat / menolak dan alasannya</li>
              <li>Retur Pembelian diperbarui (pilih faktur asal)</li>
              <li>Halaman baru <b>Laporan Pembelian</b> per principal / pemasok / obat, dengan retur dan export (menggantikan Data Pembelian)</li>
              <li>Forecast kebutuhan obat dihitung otomatis dari data penjualan (titik pesan, stok maksimal, safety stock)</li>
            </ul>
          </li>
          <li><b>Farmasi – Stok :</b>
            <ul>
              <li><b>Mutasi Stok</b> menjadi dokumen <b>Penyesuaian Stok</b> dengan alasan dan persetujuan oleh orang lain</li>
              <li>Transfer Antar Gudang bisa ditolak dengan alasan; stok hanya masuk lewat faktur, hibah atau penyesuaian</li>
              <li>Stok expired dipindahkan ke <b>gudang expired (karantina)</b> dan hanya keluar lewat Retur Pembelian atau Pemusnahan; pemusnahan punya <b>Berita Acara</b> yang bisa dicetak</li>
              <li><b>Audit batch</b>: menampilkan batch yang datanya janggal (ED kosong / default, nama batch tidak resmi, stok tidak cocok dengan kartu stok)</li>
              <li>Obat Hibah lewat form pop-up; Stok Opname hanya untuk role pengelola stok</li>
              <li>Kartu stok lebih cepat dibuka</li>
            </ul>
          </li>
          <li><b>Lab :</b>
            <ul>
              <li>Master Lab baru dengan tab Lab Test, Paket Lab dan Kategori, plus kartu "Data yang perlu action"</li>
              <li>Paket Lab menampilkan penghematan dibanding harga test satuan</li>
              <li>Test / paket yang pernah dipakai hanya bisa dibuat tidak tersedia, tidak dihapus</li>
              <li>Petugas Lab melihat daftar kunjungan yang ada pemeriksaan lab saja (menggantikan halaman E-Lab lama)</li>
            </ul>
          </li>
          <li><b>Tindakan &amp; Paket Tindakan :</b>
            <ul>
              <li>Istilah baru: <b>Tindakan</b> = isi obat / BHP (tanpa harga), <b>Paket Tindakan</b> = yang dijual ke pasien (punya harga). Paket wajib berisi minimal 1 tindakan</li>
              <li>Harga paket hanya berubah lewat <b>pengajuan harga</b> (halaman Harga Paket Tindakan): Marketing / Admin mengajukan, Admin / Finance menyetujui. Paket baru berharga 0 sampai pengajuan pertamanya disetujui</li>
              <li>Daftar Tindakan &amp; Paket: pencarian, filter status, switch aktif / nonaktif langsung di tabel, aksi massal, dan kartu "Data yang perlu action"</li>
              <li>Tindakan / paket yang pernah dipakai pasien tidak bisa dihapus, cukup dinonaktifkan agar riwayat tetap aman</li>
              <li>SOP tindakan dan halaman SPK Tindakan dihapus; SPK cukup mencatat jam mulai &amp; jam selesai</li>
            </ul>
          </li>
          <li><b>Marketing / CRM :</b>
            <ul>
              <li>Halaman baru <b>Pasien &amp; Follow Up</b> (tab Pasien, Follow Up, Ulang Tahun) menggantikan halaman pasien, follow up dan ulang tahun yang terpisah</li>
              <li>Ucapan ulang tahun lewat WhatsApp otomatis tercatat di riwayat follow up</li>
              <li>Kunjungan yang didaftarkan dari follow up otomatis membuat follow up menjadi Sukses</li>
              <li>Catatan Keluhan: filter status, dan bisa dibuka dari badge keluhan terbuka pasien</li>
              <li>Dihapus: analitik marketing lama (revenue, pasien, layanan, produk), Penawaran, Survey, dan modul WhatsApp lama</li>
            </ul>
          </li>
          <li><b>HRD :</b>
            <ul>
              <li>Data Karyawan: form baru dengan data payroll (NPWP, BPJS, rekening, status pernikahan / tanggungan), kontak darurat, dan bisa lebih dari satu perusahaan</li>
              <li>Karyawan tidak dihapus tetapi dibuat <b>Tidak Aktif</b> dengan alasan (resign, PHK, kontrak habis, ...) dan tanggalnya; kontrak dikelola langsung dari data karyawan</li>
              <li><b>Riwayat perubahan</b> data karyawan dan jadwal: siapa mengubah apa dan kapan</li>
              <li>Karyawan mendapat <b>notifikasi</b> ketika jadwalnya diubah HRD (tampil di tombol "Jadwal Saya")</li>
              <li>Jadwal: rekap hari libur, hari masuk pengganti, dan riwayat jatah libur langsung dari halaman jadwal</li>
              <li>Data Dokter dikelola di HRD; export Excel untuk karyawan, dokter dan slip gaji</li>
              <li>Dokter bisa melihat riwayat slip gajinya sendiri</li>
            </ul>
          </li>
          <li><b>R&amp;D :</b>
            <ul>
              <li>Halaman Produk R&amp;D baru: ringkasan per tahap (Proses, Siap Rilis, Dirilis, Ditunda, Perpanjangan), export Excel, PIC, target rilis, target HPP / harga</li>
              <li>Tab <b>Legalitas &amp; Sertifikasi</b> (Notifikasi BPOM, Uji Cemaran, Halal, DIP, COA, Uji Stabilitas, ...) dengan masa berlaku dan dokumen</li>
              <li><b>Rilis ke Master Obat</b>: produk yang lengkap persyaratannya bisa langsung dibuat sebagai obat, atau dihubungkan ke obat yang sudah ada</li>
              <li>Brand, Kemasan dan Vendor dikelola dari tombol "Kelola ..." di halaman produk; Bahan Aktif digabung ke master Zat Aktif</li>
              <li>Evaluasi sampel yang diterima</li>
            </ul>
          </li>
          <li><b>Admin &amp; Lainnya :</b>
            <ul>
              <li>User bisa <b>dinonaktifkan</b> (tidak bisa login, data tetap utuh); tambah / edit user lewat pop-up</li>
              <li>Obat Mapping &amp; Gudang Mapping digabung dalam satu halaman Admin; master gudang hanya dari Admin Panel</li>
              <li>SatuSehat dipindah ke <i>Admin &gt; Klinik Setting</i> (tab SatuSehat)</li>
              <li>Akreditasi dan Laporan Insiden digabung ke <b>Dokumen Kerja</b>; link lama tetap berfungsi</li>
            </ul>
          </li>
        </ul>
        <strong>*Jika ada menu yang tidak ditemukan atau error, segera hubungi tim IT.</strong>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>
