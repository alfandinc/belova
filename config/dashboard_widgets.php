<?php

/*
|--------------------------------------------------------------------------
| Home dashboard widgets
|--------------------------------------------------------------------------
|
| Widgets live in code so local and production always have the same set. Who sees a widget is not set
| here: each widget gets the Spatie permission "dashboard.widget.{key}", which is given to roles on
| Admin Panel > Widget Dashboard. A user sees every widget any of their roles has, ordered by "order".
|
| key          => stable id (used in the permission name and DOM ids; never rename once in production)
| name         => title shown on the widget and the admin page
| icon         => Feather icon name
| class        => App\Support\Dashboard\Widgets\Widget that returns the data ($widget->data in the view)
| view         => Blade view, built from the x-dash.* components so every widget looks the same
| span         => Bootstrap columns on large screens (1-12); widgets flow left to right and wrap
| order        => position on the dashboard, lowest first
| uses_period  => true when the widget follows the dashboard date range (the filter shows only then)
|
| Suggested roles are a starting point for the admin page, nothing more.
|
*/

use App\Support\Dashboard\Widgets;

return [
    // "Perlu Perhatian" is not a widget: it is the bell in the topbar (App\Support\Navigation\AttentionItems)

    // Pelayanan klinik
    'kunjungan_hari_ini' => [
        'name' => 'Kunjungan Hari Ini',
        'description' => 'Antrian, pasien selesai, dan per klinik hari ini. Dokter hanya melihat pasiennya sendiri. Saran: Pendaftaran, Perawat, Dokter, Beautician.',
        'icon' => 'activity',
        'class' => Widgets\KunjunganHariIniWidget::class,
        'view' => 'dashboard.custom_widgets.kunjungan_hari_ini_widget',
        'span' => 6,
        'order' => 20,
    ],
    'jadwal_dokter' => [
        'name' => 'Dokter Praktik Hari Ini',
        'description' => 'Jadwal praktik dokter hari ini, siapa yang sedang praktik, dan jumlah pasiennya. Saran: Pendaftaran, Perawat, Kasir.',
        'icon' => 'user-check',
        'class' => Widgets\JadwalDokterWidget::class,
        'view' => 'dashboard.custom_widgets.jadwal_dokter_widget',
        'span' => 6,
        'order' => 30,
    ],
    'resep_farmasi' => [
        'name' => 'E-Resep Hari Ini',
        'description' => 'Resep hari ini yang belum dan sudah diproses farmasi. Saran: Farmasi.',
        'icon' => 'file-text',
        'class' => Widgets\ResepFarmasiWidget::class,
        'view' => 'dashboard.custom_widgets.resep_farmasi_widget',
        'span' => 6,
        'order' => 40,
    ],
    'stok_obat' => [
        'name' => 'Stok Obat',
        'description' => 'Obat kritis / perlu dipesan dan batch yang segera kedaluwarsa. Saran: Farmasi.',
        'icon' => 'package',
        'class' => Widgets\StokObatWidget::class,
        'view' => 'dashboard.custom_widgets.stok_obat_widget',
        'span' => 6,
        'order' => 50,
    ],
    'lab' => [
        'name' => 'Permintaan Lab',
        'description' => 'Permintaan pemeriksaan lab yang menunggu atau sedang diproses. Saran: Lab.',
        'icon' => 'thermometer',
        'class' => Widgets\LabWidget::class,
        'view' => 'dashboard.custom_widgets.lab_widget',
        'span' => 6,
        'order' => 60,
    ],

    // Kasir
    'kasir' => [
        'name' => 'Kasir Hari Ini',
        'description' => 'Pembayaran hari ini per metode, invoice belum dibayar, dan piutang. Saran: Kasir, Finance.',
        'icon' => 'credit-card',
        'class' => Widgets\KasirWidget::class,
        'view' => 'dashboard.custom_widgets.kasir_widget',
        'span' => 6,
        'order' => 70,
    ],
    // Eksekutif (CEO / Head Manager / Finance). Omset = invoice lunas / sebagian, menurut tanggal bayar.
    'total_revenue' => [
        'name' => 'Omset & Target',
        'description' => 'Omset MTD & YTD, growth vs bulan lalu dan tahun lalu, pencapaian target, proyeksi akhir bulan. Saran: Ceo, Head Manager, Finance.',
        'icon' => 'trending-up',
        'class' => Widgets\TotalRevenueWidget::class,
        'view' => 'dashboard.custom_widgets.total_revenue_widget',
        'span' => 6,
        'order' => 80,
        'uses_period' => true,
    ],
    'retensi_pasien' => [
        'name' => 'Retensi Pasien',
        'description' => 'Pasien kembali, retensi 30/60/90 hari per kohort, frekuensi kunjungan, pasien berisiko hilang. Saran: Ceo, Head Manager, Marketing.',
        'icon' => 'repeat',
        'class' => Widgets\RetensiPasienWidget::class,
        'view' => 'dashboard.custom_widgets.retensi_pasien_widget',
        'span' => 6,
        'order' => 82,
        'uses_period' => true,
    ],
    'revenue_klinik' => [
        'name' => 'Omset per Klinik',
        'description' => 'Per klinik: MTD dan YTD, growth, pencapaian target bulan dan tahun. Saran: Ceo, Head Manager, Finance.',
        'icon' => 'bar-chart-2',
        'class' => Widgets\RevenueKlinikWidget::class,
        'view' => 'dashboard.custom_widgets.revenue_klinik_widget',
        'span' => 12,
        'order' => 85,
        'uses_period' => true,
    ],
    'sumber_omset' => [
        'name' => 'Sumber Omset',
        'description' => 'Omset per kategori (tindakan, obat & produk, konsultasi, lab), per klinik, item terlaris, metode bayar. Saran: Ceo, Head Manager, Finance.',
        'icon' => 'pie-chart',
        'class' => Widgets\SumberOmsetWidget::class,
        'view' => 'dashboard.custom_widgets.sumber_omset_widget',
        'span' => 12,
        'order' => 90,
        'uses_period' => true,
    ],
    'akuisisi_pasien' => [
        'name' => 'Pasien Baru & Referral',
        'description' => 'Pasien baru dan growth-nya, sumber referral beserta omsetnya, belanja pasien baru vs lama, tren 12 bulan. Saran: Ceo, Head Manager, Marketing.',
        'icon' => 'user-plus',
        'class' => Widgets\AkuisisiPasienWidget::class,
        'view' => 'dashboard.custom_widgets.akuisisi_pasien_widget',
        'span' => 6,
        'order' => 95,
        'uses_period' => true,
    ],
    'omset_marketing' => [
        'name' => 'Omset Marketing & Sales',
        'description' => 'Omset dan jumlah invoice dari referral yang dipetakan sebagai Marketing & Sales (Admin Panel > Referral Marketing), porsi dari omset, per klinik, per sumber. Saran: Ceo, Head Manager, Marketing.',
        'icon' => 'share-2',
        'class' => Widgets\OmsetMarketingWidget::class,
        'view' => 'dashboard.custom_widgets.omset_marketing_widget',
        'span' => 6,
        'order' => 96,
        'uses_period' => true,
    ],
    'kinerja_dokter' => [
        'name' => 'Kinerja Dokter',
        'description' => 'Omset, pasien, dan rata-rata per kunjungan setiap dokter, dengan growth. Saran: Ceo, Head Manager.',
        'icon' => 'award',
        'class' => Widgets\KinerjaDokterWidget::class,
        'view' => 'dashboard.custom_widgets.kinerja_dokter_widget',
        'span' => 6,
        'order' => 96,
        'uses_period' => true,
    ],
    'pasien' => [
        'name' => 'Statistik Pasien',
        'description' => 'Demografi pasien (usia, jenis kelamin, status) dan belanja rata-rata per pasien, per kunjungan, per usia. Saran: Ceo, Head Manager, Marketing.',
        'icon' => 'users',
        'class' => Widgets\PasienWidget::class,
        'view' => 'dashboard.custom_widgets.pasien_widget',
        'span' => 12,
        'order' => 100,
        'uses_period' => true,
    ],

    // Organisasi
    'employee' => [
        'name' => 'Karyawan',
        'description' => 'Karyawan aktif, status kepegawaian, karyawan baru, dan kontrak yang segera berakhir. Saran: Hrd, Ceo, Head Manager.',
        'icon' => 'briefcase',
        'class' => Widgets\EmployeeWidget::class,
        'view' => 'dashboard.custom_widgets.employee_widget',
        'span' => 6,
        'order' => 110,
        'uses_period' => true,
    ],
    'daily_journal' => [
        'name' => 'Daily Journal',
        'description' => 'Progres task periode terpilih; manajer bisa melihat task timnya. Saran: semua role.',
        'icon' => 'book-open',
        'class' => Widgets\DailyJournalWidget::class,
        'view' => 'dashboard.custom_widgets.daily_journal_widget',
        'span' => 6,
        'order' => 120,
        'uses_period' => true,
    ],
    'ulang_tahun' => [
        'name' => 'Ulang Tahun',
        'description' => 'Rekan kerja yang berulang tahun hari ini dan 7 hari ke depan. Saran: semua role.',
        'icon' => 'gift',
        'class' => Widgets\UlangTahunWidget::class,
        'view' => 'dashboard.custom_widgets.ulang_tahun_widget',
        'span' => 6,
        'order' => 130,
    ],
    'sewa_kos' => [
        'name' => 'Sewa Kos BCL',
        'description' => 'Sewa kamar yang habis 7 hari ke depan (dengan link WhatsApp penyewa), okupansi, dan piutang sewa. Saran: Kos.',
        'icon' => 'key',
        'class' => Widgets\SewaKosWidget::class,
        'view' => 'dashboard.custom_widgets.sewa_kos_widget',
        'span' => 6,
        'order' => 135,
    ],
    'ringkasan_kos' => [
        'name' => 'Ringkasan Kos BCL',
        'description' => 'Okupansi dan status kamar hari ini, kas masuk/keluar sesuai periode (dibanding periode lalu), piutang sewa, dan daftar yang perlu ditindak (lewat masa sewa, habis ≤ 7 hari, belum lunas, inventaris telat dirawat). Saran: Kos, Admin.',
        'icon' => 'home',
        'class' => Widgets\RingkasanKosWidget::class,
        'view' => 'dashboard.custom_widgets.ringkasan_kos_widget',
        'span' => 6,
        'order' => 134,
        'uses_period' => true,
    ],
    'buku_menu' => [
        'name' => 'Buku Menu',
        'description' => 'Cari harga obat, tindakan, dan pemeriksaan lab. Saran: Pendaftaran, Kasir, Marketing, Beautician.',
        'icon' => 'book',
        'view' => 'dashboard.custom_widgets.buku_menu_widget',
        'span' => 6,
        'order' => 140,
    ],
];
