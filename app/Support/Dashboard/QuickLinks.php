<?php

namespace App\Support\Dashboard;

use App\Models\User;
use App\Support\Navigation\MainNavigation;
use Illuminate\Support\Facades\Route;

/**
 * "Akses Cepat" shortcuts in the dashboard header: the pages a role opens most.
 * Listed from most role-specific to most generic, so a user with several roles sees their core pages first;
 * role checks match the sidebar (App\Support\Navigation\MainNavigation), counts come from its cached badges.
 */
class QuickLinks
{
    private const LIMIT = 8;

    public static function forUser(User $user): array
    {
        $badges = MainNavigation::badgesFor($user);
        $count = fn (string ...$keys) => array_sum(array_map(fn ($k) => (int) ($badges[$k] ?? 0), $keys));
        $can = fn (string ...$roles) => $user->hasAnyRole($roles);
        $route = fn (string $name) => Route::has($name) ? route($name) : null;

        $links = [
            // Klinik
            $can('Dokter', 'Perawat', 'Admin', 'Lab') ? self::link('Rawat Jalan', 'Rekam medis & antrian pasien', 'activity', url('/erm/rawatjalans')) : null,
            $can('Pendaftaran', 'Perawat', 'Farmasi', 'Admin', 'Beautician', 'Lab') ? self::link('Data Pasien', 'Cari & daftarkan pasien', 'user', url('/erm/pasiens')) : null,
            $can('Farmasi', 'Admin') ? self::link('E-Resep', 'Resep masuk dari dokter', 'file-text', url('/erm/eresepfarmasi')) : null,
            $can('Kasir', 'Admin', 'Finance', 'Marketing') ? self::link('Billing', 'Tagihan & pembayaran', 'credit-card', url('/finance/billing')) : null,
            $can('Farmasi', 'Admin', 'Beautician', 'Lab') ? self::link('Stok Obat', 'Stok per gudang', 'package', url('/erm/stok-gudang')) : null,
            $can('Farmasi', 'Admin') ? self::link('Faktur Pembelian', 'Penerimaan barang', 'file-plus', url('/erm/fakturpembelian')) : null,
            $can('Farmasi', 'Admin', 'Finance') ? self::link('Harga Jual Obat', 'Pengajuan harga', 'tag', $route('erm.harga-jual.index'), $count('harga_menunggu')) : null,

            // Bisnis
            $can('Marketing', 'Admin', 'Finance') ? self::link('Pasien & Follow Up', 'CRM pasien', 'phone-call', url('/marketing/pasien-data')) : null,
            $can('Marketing', 'Admin', 'Finance') ? self::link('Content Plan', 'Jadwal konten sosmed', 'layout', url('/marketing/content-plan'), $count('content_scheduled')) : null,
            $can('Admin', 'Finance', 'Kasir') ? self::link('Laporan Keuangan', 'Omset & laporan', 'pie-chart', $route('finance.laporan-keuangan.index')) : null,
            $can('Admin', 'Inventaris') ? self::link('Inventory', 'Daftar barang', 'box', url('/inventory/barang')) : null,
            $can('Kos', 'Admin') ? self::link('Kamar BCL', 'Kamar & penyewa', 'key', $route('bcl.rooms')) : null,

            // Organisasi
            $can('Hrd', 'Admin', 'Ceo', 'Head Manager') ? self::link('Data Pegawai', 'Karyawan & dokter', 'users', $route('hrd.employee.index')) : null,
            $can('Hrd', 'Admin') ? self::link('Jadwal Karyawan', 'Atur shift', 'calendar', $route('hrd.schedule.index')) : null,
            $can('Hrd', 'Admin', 'Manager', 'Head Manager') ? self::link('Jadwal Dokter', 'Praktik dokter', 'user-check', $route('hrd.dokter-schedule.index')) : null,
            $can('Kasir', 'Admin', 'Farmasi', 'Finance', 'Employee', 'Manager', 'Head Manager', 'Hrd') ? self::link('Pengajuan Dana', 'Ajukan & pantau dana', 'send', $route('finance.pengajuan.index')) : null,

            // Everyone
            self::link('Cuti & Libur', 'Ajukan cuti', 'sun', $route('hrd.libur.index'), $count('libur_hrd', 'libur_manager')),
            self::link('Izin & Sakit', 'Ajukan izin', 'thermometer', $route('hrd.tidakmasuk.index'), $count('tidak_masuk_hrd', 'tidak_masuk_manager')),
            self::link('Lembur', 'Ajukan lembur', 'clock', $route('hrd.lembur.index'), $count('lembur_hrd', 'lembur_manager')),
        ];

        return collect($links)
            ->filter(fn ($link) => $link && $link['url'])
            ->take(self::LIMIT)
            ->values()
            ->all();
    }

    private static function link(string $label, string $hint, string $icon, ?string $url, int $count = 0): array
    {
        return compact('label', 'hint', 'icon', 'url', 'count');
    }
}
