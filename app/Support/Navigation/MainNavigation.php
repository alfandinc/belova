<?php

namespace App\Support\Navigation;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

/**
 * Single left sidebar for every module (replaces the old main menu tiles and the per-module navbars).
 *
 * Structure: sections -> modules -> items.
 *  - module: ['key', 'label', 'icon', 'url' (direct link) | 'items' (collapsible)]
 *  - item:   ['label', 'icon', 'url', 'badges', 'attrs'] or ['heading' => 'Label'] for a small group label
 * Role checks mirror the previous navbars / main menu tiles and the route middleware.
 */
class MainNavigation
{
    private const BADGE_TTL_SECONDS = 60;

    private User $user;
    private array $badges;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->badges = $this->loadBadges();
    }

    public static function forUser(?User $user, ?string $currentPath = null): array
    {
        if (!$user) {
            return [];
        }

        $navigation = new self($user);
        $sections = $navigation->build();

        return $navigation->markActive($sections, $currentPath ?? request()->path());
    }

    private function build(): array
    {
        $sections = [
            // No section label: the "Utama" group heads the menu on its own
            ['label' => null, 'modules' => [
                $this->utama(),
            ]],
            ['label' => 'Klinik', 'modules' => [
                $this->erm(),
                $this->farmasiPembelian(),
                $this->farmasiPenjualan(),
                $this->farmasiStok(),
                $this->masterKlinik(),
            ]],
            ['label' => 'Bisnis', 'modules' => [
                $this->akuntansi(),
                $this->crm(),
                $this->sosmed(),
                $this->inventory(),
                $this->bcl(),
                $this->events(),
            ]],
            ['label' => 'Organisasi', 'modules' => [
                $this->kepegawaian(),
                $this->jadwalAbsensi(),
                $this->payroll(),
                $this->workdoc(),
                $this->kpi(),
            ]],
            ['label' => 'Sistem', 'modules' => [
                $this->admin(),
            ]],
        ];

        return collect($sections)
            ->map(function (array $section) {
                $section['modules'] = collect($section['modules'])
                    ->filter()
                    ->map(fn (array $module) => $this->finalizeModule($module))
                    ->filter()
                    ->values()
                    ->all();

                return $section;
            })
            ->filter(fn (array $section) => !empty($section['modules']))
            ->values()
            ->all();
    }

    // ----------------------------------------------------------------- modules

    /** Everyday pages for every user, stacked in one group. Widget & position mapping lives under Admin Panel > Pengaturan. */
    private function utama(): array
    {
        return $this->module('utama', 'Utama', 'home', [
            $this->item('Dashboard', 'grid', url('/')),
            $this->item('Jadwal', 'calendar', '#', $this->badge('jadwal_unread', 'danger', 'Jadwal Anda diubah', 'jv-unread'), ['class' => 'js-open-jadwal']),
            $this->item('Daily Journal', 'book-open', $this->route('daily-journal.index'), $this->badge('daily_journal_deadline', 'warning', 'Task dengan deadline belum selesai'), ['class' => 'js-open-journal']),
        ]);
    }

    private function erm(): array
    {
        // Daily patient-facing pages; each item keeps the roles it had in its old module
        return $this->module('erm', 'ERM', 'heart', [
            $this->can('Pendaftaran', 'Perawat', 'Farmasi', 'Admin', 'Beautician', 'Lab', 'Manager') ? $this->item('Data Pasien', 'user', url('/erm/pasiens')) : null,
            $this->can('Dokter', 'Perawat', 'Admin', 'Lab', 'Manager') ? $this->item('Rawat Jalan', 'activity', url('/erm/rawatjalans')) : null,
            $this->can('Farmasi', 'Admin', 'Manager') ? $this->item('E-Resep', 'file-text', url('/erm/eresepfarmasi')) : null,
        ]);
    }

    // Farmasi is split into three groups; each item keeps the roles it had before.

    private function farmasiPembelian(): array
    {
        $farmasi = $this->can('Farmasi', 'Admin');
        $stok = $this->can('Farmasi', 'Admin', 'Beautician', 'Lab');

        return $this->module('farmasi-pembelian', 'Pembelian', 'shopping-cart', [
            $farmasi ? $this->item('Harga Pembelian', 'dollar-sign', url('/erm/masterfaktur')) : null,
            $farmasi ? $this->item('Permintaan Pembelian', 'clipboard', url('/erm/permintaan')) : null,
            $farmasi ? $this->item('Faktur Pembelian', 'file-plus', url('/erm/fakturpembelian')) : null,
            $stok ? $this->item('Obat Hibah', 'inbox', url('/erm/obat-hibah')) : null,
            $farmasi ? $this->item('Retur ke Supplier', 'rotate-ccw', url('/erm/fakturretur')) : null,
            $stok ? $this->item('Forecast Stok', 'trending-up', $this->route('erm.obat.forecast.index')) : null,
            $farmasi ? $this->item('Laporan Pembelian', 'bar-chart-2', $this->route('erm.laporan-pembelian.index')) : null,
            
        ]);
    }

    private function farmasiPenjualan(): array
    {
        return $this->module('farmasi-penjualan', 'Penjualan', 'tag', [
            $this->can('Kasir', 'Admin', 'Finance', 'Marketing') ? $this->item('Daftar Billing', 'credit-card', url('/finance/billing')) : null,
            $this->can('Farmasi', 'Admin', 'Finance')
                ? $this->item('Harga Jual Obat', 'tag', $this->route('erm.harga-jual.index'), $this->badge('harga_menunggu', 'warning', 'Pengajuan harga menunggu'))
                : null,
            $this->can('Marketing', 'Admin', 'Finance')
                ? $this->item('Harga Paket Tindakan', 'tag', $this->route('marketing.harga-tindakan.index'), $this->badge('harga_tindakan_menunggu', 'warning', 'Pengajuan harga paket tindakan menunggu'))
                : null,
            $this->can('Marketing', 'Admin', 'Finance') ? $this->item('Promo', 'percent', url('/marketing/promo')) : null,
            $this->item('Buku Menu', 'book', url('/buku-menu')),
        ]);
    }

    private function farmasiStok(): array
    {
        $stok = $this->can('Farmasi', 'Admin', 'Beautician', 'Lab');
        // Marketing: everything except Stok Opname
        $stokMarketing = $stok || $this->can('Marketing');

        return $this->module('farmasi-stok', 'Stok', 'archive', [
            $stokMarketing ? $this->item('Manajemen Stok', 'package', url('/erm/stok-gudang')) : null,
            $stokMarketing ? $this->item('Transfer Antar Gudang', 'truck', url('/erm/mutasi-gudang')) : null,
            $stokMarketing ? $this->item('Penyesuaian Stok', 'sliders', url('/erm/mutasi-stok')) : null,
            $stok ? $this->item('Stok Opname', 'check-square', url('/erm/stokopname')) : null,
        ]);
    }

    private function akuntansi(): array
    {
        $akuntansi = $this->can('Admin', 'Finance');

        return $this->module('akuntansi', 'Keuangan', 'book', [
            
            $akuntansi ? $this->item('Daftar Akun', 'list', $this->route('finance.akun.index')) : null,
            $akuntansi ? $this->item('Jurnal Umum', 'edit-3', $this->route('finance.jurnal.index')) : null,
            $this->can('Admin', 'Finance', 'Manager', 'Ceo') ? $this->item('Laporan Omset', 'trending-up', $this->route('finance.omset.index')) : null,
            $this->can('Admin', 'Finance', 'Kasir') ? $this->item('Laporan Keuangan', 'pie-chart', $this->route('finance.laporan-keuangan.index')) : null,
            // Company-wide fund request: open to more roles than the accounting pages above
            $this->can('Kasir', 'Admin', 'Farmasi', 'Finance', 'Employee', 'Manager', 'Head Manager', 'Hrd', 'Ceo')
                ? $this->item('Pengajuan Dana', 'send', $this->route('finance.pengajuan.index'))
                : null,
        ]);
    }

    // Marketing role pages are split into CRM and Sosmed (Promo sits under Penjualan); all keep the Marketing role check.

    /** Patient / customer relationship pages. */
    private function crm(): ?array
    {
        if (!$this->can('Marketing', 'Admin', 'Finance')) {
            return null;
        }

        return $this->module('crm', 'CRM', 'users', [
            // Pasien data, follow up and birthday greetings are tabs of one page
            $this->item('Pasien & Follow Up', 'phone-call', url('/marketing/pasien-data')),
            $this->item('Keluhan Customer', 'message-square', url('/marketing/catatan-keluhan')),
            $this->item('Kunjungan Instansi', 'map-pin', $this->route('marketing.kunjungan.index')),
            $this->item('Merchandise', 'shopping-bag', url('/marketing/master-merchandise')),
        ]);
    }

    /**
     * Social media & design work. Request Desain is open to every user (anyone can ask the designer
     * for print/packaging work); the other pages keep their Marketing/Admin/Finance roles.
     */
    private function sosmed(): ?array
    {
        $marketing = $this->can('Marketing', 'Admin', 'Finance');

        return $this->module('sosmed', 'Sosmed & Desain', 'instagram', [
            $marketing ? $this->item('Content Plan', 'layout', url('/marketing/content-plan'), $this->badge('content_scheduled', 'warning', 'Konten menunggu review')) : null,
            $marketing ? $this->item('Analisa Konten', 'bar-chart-2', url('/marketing/content-analytics')) : null,
            $this->item('Request Desain', 'pen-tool', $this->route('design-request.index'), $this->badge('design_request_action', 'warning', 'Request desain perlu tindakan')),
            $marketing ? $this->item('Before After Gallery', 'image', $this->route('marketing.before_after_gallery.index')) : null,
            $marketing ? $this->item('Hari Penting', 'star', url('/marketing/hari-penting'), $this->badge('hari_penting_today', 'danger', 'Hari penting hari ini')) : null,
        ]);
    }

    private function inventory(): ?array
    {
        if (!$this->can('Admin', 'Inventaris')) {
            return null;
        }

        return $this->module('inventory', 'Inventory', 'box', [
            $this->item('List Barang', 'list', url('/inventory/barang')),
            $this->item('Pembelian', 'shopping-cart', url('/inventory/pembelian')),
            $this->item('Maintenance', 'tool', url('/inventory/maintenance')),
            $this->item('Kartu Stok', 'file-text', url('/inventory/kartu-stok')),
            $this->heading('Master'),
            $this->item('Gedung', 'home', url('/inventory/gedung')),
            $this->item('Ruangan', 'map', url('/inventory/ruangan')),
            $this->item('Tipe Barang', 'tag', url('/inventory/tipe-barang')),
        ]);
    }

    /**
     * Clinic master data in one group: obat, tindakan, racikan, R&D products and lab.
     * Master data of other modules lives under a "Master" heading inside that module.
     * Each item keeps the roles it had in its original module.
     */
    private function masterKlinik(): array
    {
        $farmasi = $this->can('Farmasi', 'Admin');
        $marketing = $this->can('Marketing', 'Admin', 'Finance');
        $rnd = $this->can('Admin', 'Rnd');
        $lab = $this->can('Lab', 'Admin');

        return $this->module('master-klinik', 'Master Data', 'database', [
            $this->heading('Obat & Produk'),
            $farmasi ? $this->item('Obat & BHP', 'droplet', url('/erm/obat')) : null,
            // Brand, Kemasan and Vendor are managed from this page (sediaan uses the Satuan master)
            $rnd ? $this->item('Produk R&D', 'award', $this->route('rnd.products.index'), $this->badge('rnd_sertifikasi', 'danger', 'Produk dengan sertifikat habis / segera habis (≤ 90 hari)')) : null,
            $farmasi ? $this->item('Obat & Produk Racikan', 'shuffle', $this->route('erm.paket-racikan.index')) : null,
            $this->heading('Tindakan'),
            $marketing ? $this->item('Paket Tindakan', 'layers', url('/marketing/tindakan')) : null,
            $marketing ? $this->item('Tindakan', 'hash', url('/marketing/kodetindakan')) : null,
            $marketing ? $this->item('Kategori Tindakan', 'folder', url('/erm/marketing/kategori-tindakan')) : null,
            
            $this->heading('Laboratorium'),
            $lab ? $this->item('Pemeriksaan Lab', 'thermometer', $this->route('erm.labtests.master')) : null,
        ]);
    }

    private function bcl(): ?array
    {
        if (!$this->can('Kos', 'Admin')) {
            return null;
        }

        return $this->module('bcl', 'KOS BCL', 'key', [
            $this->heading('Operasional'),
            $this->item('Daftar Kamar', 'key', $this->route('bcl.rooms')),
            $this->item('Transaksi Sewa', 'repeat', $this->route('bcl.transaksi.index')),
            $this->item('Daftar Penyewa', 'users', $this->route('bcl.renter.index')),
            $this->heading('Administrasi'),
            $this->item('Inventaris', 'archive', $this->route('bcl.inventories.index')),
            $this->item('Keuangan', 'trending-up', $this->route('bcl.finance')),
            $this->item('Pengaturan', 'settings', $this->route('bcl.settings')),
        ]);
    }

    private function events(): array
    {
        return $this->module('events', 'Events', 'flag', [
            $this->item('Events', 'flag', url('/events')),
            $this->heading('Event Lainnya'),
            $this->item('Belova Mengaji', 'book-open', url('/belova-mengaji')),
            $this->item('Belova Mengaji Analytics', 'bar-chart-2', url('/belova-mengaji/analytics')),
            $this->item('Run 2 Wellness', 'activity', url('/running')),
            $this->item('Lebaran', 'moon', $this->route('events.lebaran.index')),
        ]);
    }

    // HRD is split into Kepegawaian, Jadwal & Absensi and Payroll; each item keeps the roles it had before.

    private function kepegawaian(): array
    {
        $kepegawaian = $this->can('Hrd', 'Admin', 'Ceo', 'Head Manager');

        return $this->module('kepegawaian', 'Kepegawaian', 'users', [
            $kepegawaian ? $this->item('Data Pegawai', 'users', $this->route('hrd.employee.index')) : null,
            $kepegawaian ? $this->item('Data Dokter', 'user-check', $this->route('hrd.dokters.index'), $this->badge('dokter_izin', 'danger', 'Dokter dengan SIP / STR habis atau segera habis (≤ 30 hari)')) : null,
            $this->can('Hrd', 'Admin', 'Manager', 'Head Manager')
                ? $this->item('Catatan Pelanggaran', 'alert-triangle', $this->route('hrd.catatan-dosa.index'), $this->badge('catatan_dosa', 'danger', 'Catatan pelanggaran dalam proses'))
                : null,
            $kepegawaian ? $this->heading('Master') : null,
            $kepegawaian ? $this->item('Divisi & Jabatan', 'briefcase', $this->route('hrd.master.division.index')) : null,
        ]);
    }

    private function jadwalAbsensi(): array
    {
        $hrdAdmin = $this->can('Hrd', 'Admin');
        $jadwalDokter = $this->can('Hrd', 'Admin', 'Manager', 'Head Manager');

        return $this->module('jadwal-absensi', 'Jadwal & Absensi', 'calendar', [
            $hrdAdmin || $jadwalDokter ? $this->heading('Jadwal') : null,
            $hrdAdmin ? $this->item('Jadwal Karyawan', 'calendar', $this->route('hrd.schedule.index')) : null,
            $jadwalDokter ? $this->item('Jadwal Dokter', 'user-check', $this->route('hrd.dokter-schedule.index')) : null,
            $this->heading('Absensi'),
            $this->item('Cuti & Libur', 'sun', $this->route('hrd.libur.index'), $this->approvalBadges('libur')),
            $this->item('Izin & Sakit', 'thermometer', $this->route('hrd.tidakmasuk.index'), $this->approvalBadges('tidak_masuk')),
            $this->item('Lembur', 'clock', $this->route('hrd.lembur.index'), $this->approvalBadges('lembur')),
            $this->item('Ganti/Tukar Shift', 'refresh-cw', $this->route('hrd.gantishift.index'), $this->approvalBadges('ganti_shift')),
            $hrdAdmin ? $this->item('Rekap Absensi', 'bar-chart-2', $this->route('hrd.absensi_rekap.index')) : null,
        ]);
    }

    private function payroll(): array
    {
        $kepegawaian = $this->can('Hrd', 'Admin', 'Ceo', 'Head Manager');
        $hasEmployeeOrDokter = (bool) $this->user->employee || (bool) $this->user->dokter;
        $gajiDokter = $this->user->hasRole('Hrd')
            || ($this->can('Manager', 'Head Manager') && $this->user->hasRole('Finance'));

        return $this->module('payroll', 'Payroll', 'dollar-sign', [
            $kepegawaian ? $this->item('Payroll Karyawan', 'credit-card', $this->route('hrd.payroll.slip_gaji.index')) : null,
            $kepegawaian ? $this->item('Payroll Dokter', 'credit-card', $this->route('hrd.payroll.slip_gaji_dokter.index')) : null,
            $gajiDokter ? $this->item('Gaji Dokter', 'dollar-sign', '#', [], ['onclick' => 'checkGajiDokter(event)']) : null,
            $hasEmployeeOrDokter ? $this->item('My Payroll', 'pocket', '#', [], ['onclick' => 'checkSlipGaji(event)']) : null,
            $kepegawaian ? $this->heading('Master') : null,
            $kepegawaian ? $this->item('Master Payroll', 'sliders', $this->route('hrd.payroll.master.index')) : null,
        ]);
    }

    private function workdoc(): ?array
    {
        if (!$this->can('Hrd', 'Manager', 'Head Manager', 'Employee', 'Admin')) {
            return null;
        }

        return $this->module('workdoc', 'Workdoc', 'folder', [
            $this->item('Home', 'home', $this->route('workdoc.dashboard')),
            $this->item('Document Manager', 'folder', $this->route('workdoc.documents.index')),
            $this->item('Surat Keluar', 'mail', $this->route('workdoc.surat-keluar.index')),
            $this->item('Notulensi Rapat', 'edit', $this->route('workdoc.notulensi-rapat.index')),
            $this->item('Kemitraan', 'link', $this->route('workdoc.kemitraan.index'), $this->badge('kemitraan_soon', 'danger', 'Kemitraan berakhir ≤ 6 bulan')),
            $this->item('Memorandum', 'file-text', $this->route('workdoc.memorandum.index')),
            $this->item('Akreditasi', 'award', $this->route('akreditasi.index')),
            $this->item('Laporan Insiden', 'alert-octagon', $this->route('insiden.laporan_insiden.index')),
        ]);
    }

    private function kpi(): ?array
    {
        if (!$this->can('Employee', 'Manager', 'Hrd', 'Ceo', 'Head Manager', 'Admin')) {
            return null;
        }

        $master = $this->can('Admin', 'Hrd', 'Ceo', 'Head Manager');

        return $this->module('kpi', 'Assessment', 'target', [
            $this->item('My Evaluations', 'check-square', $this->route('kpi.evaluatees.index')),
            $master ? $this->item('Periods', 'calendar', $this->route('kpi.periods.index')) : null,
            $master ? $this->heading('Master') : null,
            $master ? $this->item('Indikator KPI', 'target', $this->route('indicator.index')) : null,
        ]);
    }

    private function admin(): ?array
    {
        $admin = $this->can('Admin');
        // Same roles as the dashboard widget routes: CEO / Head Manager see Admin Panel with only this item
        $dashboardWidgets = $this->can('Admin', 'Ceo', 'Head Manager');

        return $this->module('admin', 'Admin Panel', 'shield', [
            $admin ? $this->item('Dashboard', 'grid', url('/admin')) : null,
            $admin ? $this->item('Users & Roles', 'users', $this->route('admin.users.index')) : null,
            $admin ? $this->heading('SatuSehat') : null,
            $admin ? $this->item('Pasien SatuSehat', 'user', $this->route('satusehat.pasiens.index')) : null,
            $admin ? $this->item('Obat KFA Mapping', 'link', url('/satusehat/obat-kfa')) : null,
            $admin ? $this->item('Mapping Dokter', 'user-check', $this->route('satusehat.dokter_mapping.index')) : null,
            $admin ? $this->heading('Master Data') : null,
            $admin ? $this->item('Gudang & Mapping Obat', 'archive', $this->route('admin.obat_gudang_mapping.index')) : null,
            $admin ? $this->item('Biaya Konsultasi dan Lain Lain', 'dollar-sign', url('/marketing/biaya-konsultasi')) : null,
            $admin ? $this->item('ICD 10', 'hash', $this->route('admin.icd10.index')) : null,
            $admin ? $this->item('Satuan', 'layers', $this->route('erm.satuan.index')) : null,
            $admin ? $this->item('Metode Bayar', 'credit-card', $this->route('finance.metode-bayar.index')) : null,
            $this->heading('Pengaturan'),
            $dashboardWidgets ? $this->item('Widget Dashboard', 'layout', $this->route('dashboard.widgets.index')) : null,
            $admin ? $this->item('Klinik Setting', 'settings', $this->route('admin.klinik_settings.index')) : null,
            $admin ? $this->item('Settings', 'tool', url('/admin/settings')) : null,
        ]);
    }

    // ----------------------------------------------------------------- builders

    private function module(string $key, string $label, string $icon, array $items): array
    {
        return ['key' => $key, 'label' => $label, 'icon' => $icon, 'items' => $items];
    }

    /** $icon is a Feather icon name (same set as the module icons). */
    private function item(string $label, string $icon, string $url, array $badges = [], array $attrs = []): array
    {
        return ['label' => $label, 'icon' => $icon, 'url' => $url, 'badges' => $badges, 'attrs' => $attrs];
    }

    private function heading(string $label): array
    {
        return ['heading' => $label];
    }

    /**
     * Drop hidden items and headings left without links; a module with no links is hidden.
     * The module header shows the total of its item badges so a collapsed module still signals work.
     */
    private function finalizeModule(array $module): ?array
    {
        if (!isset($module['items'])) {
            return $module;
        }

        $items = array_values(array_filter($module['items']));
        $clean = [];
        foreach ($items as $index => $item) {
            if (isset($item['heading'])) {
                $next = $items[$index + 1] ?? null;
                if (!$next || isset($next['heading'])) {
                    continue;
                }
            }
            $clean[] = $item;
        }

        $links = array_filter($clean, fn ($item) => !isset($item['heading']));
        if (empty($links)) {
            return null;
        }

        $total = collect($links)->flatMap(fn ($item) => $item['badges'])->sum('count');
        $module['items'] = $clean;
        $module['badges'] = $total > 0 ? [['count' => $total, 'class' => 'danger', 'title' => 'Perlu tindak lanjut', 'extra' => '']] : [];

        return $module;
    }

    /** Mark the item whose URL path is the longest prefix of the current path, and open its module. */
    private function markActive(array $sections, string $currentPath): array
    {
        $current = '/' . trim($currentPath, '/');
        $best = null;
        $bestLength = -1;

        foreach ($sections as $s => $section) {
            foreach ($section['modules'] as $m => $module) {
                $candidates = isset($module['items']) ? $module['items'] : [$module];
                foreach ($candidates as $i => $item) {
                    if (empty($item['url']) || $item['url'] === '#') {
                        continue;
                    }
                    $path = '/' . trim((string) parse_url($item['url'], PHP_URL_PATH), '/');
                    $matches = $path === '/'
                        ? $current === '/'
                        : ($current === $path || str_starts_with($current, $path . '/'));
                    if ($matches && strlen($path) > $bestLength) {
                        $best = [$s, $m, isset($module['items']) ? $i : null];
                        $bestLength = strlen($path);
                    }
                }
            }
        }

        if ($best) {
            [$s, $m, $i] = $best;
            $sections[$s]['modules'][$m]['active'] = true;
            if ($i !== null) {
                $sections[$s]['modules'][$m]['items'][$i]['active'] = true;
            }
        }

        return $sections;
    }

    // ----------------------------------------------------------------- helpers

    private function can(string ...$roles): bool
    {
        return $this->user->hasAnyRole($roles);
    }

    private function route(string $name): string
    {
        return Route::has($name) ? route($name) : '#';
    }

    private function badge(string $key, string $class, string $title = '', string $extraClass = ''): array
    {
        $count = (int) ($this->badges[$key] ?? 0);

        return $count > 0 ? [['count' => $count, 'class' => $class, 'title' => $title, 'extra' => $extraClass]] : [];
    }

    /** Absensi approvals: yellow = waiting for HRD, blue = waiting for me as direct approver. */
    private function approvalBadges(string $type): array
    {
        return array_merge(
            $this->badge($type . '_hrd', 'warning', 'Menunggu persetujuan HRD'),
            $this->badge($type . '_manager', 'info', 'Menunggu persetujuan Anda')
        );
    }

    private function loadBadges(): array
    {
        return self::badgesFor($this->user);
    }

    /** Badge counts (NavigationBadges), cached per user; shared with the dashboard shortcuts. */
    public static function badgesFor(User $user): array
    {
        return Cache::remember('main_nav_badges:' . $user->id, self::BADGE_TTL_SECONDS, function () use ($user) {
            return (new NavigationBadges($user))->all();
        });
    }
}
