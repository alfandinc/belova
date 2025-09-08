<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController,
    ERMDashboardController,
    HRDDashboardController,
    InventoryDashboardController,
    MarketingDashboardController,
    FinanceDashboardController,
    WorkdocDashboardController,
    AkreditasiDashboardController,
    CustSurveyController
};
use App\Http\Controllers\ERM\StokGudangController;
use App\Http\Controllers\Admin\{
    UserController,
    RoleController
};
use App\Http\Controllers\Finance\{
    BillingController,
    InvoiceController,
};

use App\Http\Controllers\ERM\{
    PasienController,
    DokterController,
    VisitationController,
    RawatJalanController,
    EresepController,
    AlergiController,
    ObatController,
    EradiologiController,
    ElabController,
    TindakanController,
    AsesmenController,
    AsesmenPerawatController,
    BirthdayController,
    CPPTController,
    Icd10Controller,
    KeluhanUtamaController,
    RiwayatKunjunganController,
    ListAntrianController,
    StatisticController,
    SuratIstirahatController,
    SuratMondokController,
    ResepCatatanController,
    NotificationController,
    ObatKeluarController,
    MutasiGudangController,
    GudangController
};

use App\Http\Controllers\HRD\{
    EmployeeController,
    EmployeeContractController,
    DivisionController,
    EmployeeSelfServiceController,
    PengajuanLiburController,
    PerformanceEvaluationController,
    PerformanceQuestionController,
    PerformanceScoreController
};
use App\Http\Controllers\AkreditasiController;
use App\Http\Controllers\Inventory\ItemController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\Marketing\MarketingController;
use App\Http\Controllers\Insiden\LaporanInsidenController;
use App\Http\Controllers\LaporanDashboardController;


use Illuminate\Support\Facades\Auth;
Route::get('/', function () {
    if (!Auth::check()) {
        return view('auth.main_login');
    }
    return view('mainmenu');
});

// Different login pages (GET requests only)

// Hilangkan login per modul, login hanya di halaman utama

// Single POST route for login processing (all forms submit here)
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Clinic choice session route
Route::post('/set-clinic-choice', [AuthController::class, 'setClinicChoice'])->name('set.clinic.choice');
Route::fallback(function () {
    if (!Auth::check()) {
        return redirect('/');
    }
});

Route::middleware(['auth'])->group(function () {
    // Hanya user dengan role ERM yang bisa akses modul ERM
    Route::get('/erm', [ERMDashboardController::class, 'index'])
        ->middleware('role:Dokter|Perawat|Pendaftaran|Admin|Farmasi|Beautician|Lab')
        ->name('erm.dashboard');

    Route::get('/finance', [FinanceDashboardController::class, 'index'])
        ->middleware('role:Kasir|Admin')
        ->name('finance.dashboard');

    Route::get('/hrd', [HRDDashboardController::class, 'index'])
        ->middleware('role:Hrd|Manager|Employee|Admin|Ceo')
        ->name('hrd.dashboard');

    Route::get('/inventory', [InventoryDashboardController::class, 'index'])
        ->middleware('role:Admin|Inventaris')   
        ->name('inventory.dashboard');

    Route::get('/marketing', [MarketingDashboardController::class, 'index'])
        ->middleware('role:Marketing|Admin')
        ->name('marketing.dashboard');

    Route::get('/workdoc', [WorkdocDashboardController::class, 'index'])
        ->middleware('role:Hrd|Manager|Employee|Admin')
        ->name('workdoc.dashboard');

    Route::get('/akreditasi', [AkreditasiDashboardController::class, 'index'])
        ->middleware('role:Hrd|Manager|Employee|Admin')
        ->name('akreditasi.dashboard');

    // Insiden menu (Admin & Hrd roles)
    Route::get('/insiden', [\App\Http\Controllers\InsidenDashboardController::class, 'index'])
        ->middleware('role:Admin|Hrd|Manager|Employee')
        ->name('insiden.dashboard');

    Route::get('/laporan', [LaporanDashboardController::class, 'index'])
        ->middleware('role:Hrd|Manager|Admin|Kasir')
        ->name('laporan.dashboard');



});



Route::get('/customersurvey', [CustSurveyController::class, 'index'])->name('customer.survey');
Route::post('/customersurvey', [CustSurveyController::class, 'store'])->name('customer.survey');

//LAPORAN Routes
Route::prefix('laporan')->middleware('role:Hrd|Manager|Admin')->group(function () {
    // AJAX endpoint for HRD Rekap Kehadiran DataTable
    Route::get('/hrd/rekap-kehadiran/data', [\App\Http\Controllers\Laporan\HRDController::class, 'rekapKehadiranData'])->name('laporan.hrd.rekap-kehadiran.data');
    Route::get('/farmasi/penjualan-obat/excel', [\App\Http\Controllers\Laporan\FarmasiController::class, 'exportPenjualanExcel'])->name('laporan.farmasi.penjualan-obat.excel');
    Route::get('/farmasi/penjualan-obat/pdf', [\App\Http\Controllers\Laporan\FarmasiController::class, 'exportPenjualanPdf'])->name('laporan.farmasi.penjualan-obat.pdf');
    Route::get('/farmasi/penjualan-obat', [\App\Http\Controllers\Laporan\FarmasiController::class, 'penjualanObat'])->name('laporan.farmasi.penjualan-obat');
    Route::get('/', [LaporanDashboardController::class, 'index'])->name('laporan.dashboard');
    Route::get('/farmasi', [\App\Http\Controllers\Laporan\FarmasiController::class, 'index'])->name('laporan.farmasi');
    Route::get('/farmasi/excel', [\App\Http\Controllers\Laporan\FarmasiController::class, 'exportExcel'])->name('laporan.farmasi.excel');
    Route::get('/farmasi/pdf', [\App\Http\Controllers\Laporan\FarmasiController::class, 'exportPdf'])->name('laporan.farmasi.pdf');

    // HRD Rekap Kehadiran
    Route::get('/hrd/rekap-kehadiran', [\App\Http\Controllers\Laporan\HRDController::class, 'rekapKehadiran'])->name('laporan.hrd.rekap-kehadiran');

    // Export Rekap Kehadiran
    Route::get('/hrd/rekap-kehadiran/excel', [\App\Http\Controllers\Laporan\HRDController::class, 'exportExcel'])->name('laporan.hrd.rekap-kehadiran.excel');
    Route::get('/hrd/rekap-kehadiran/pdf', [\App\Http\Controllers\Laporan\HRDController::class, 'exportPdf'])->name('laporan.hrd.rekap-kehadiran.pdf');

    // Laporan Laboratorium
    Route::get('/laboratorium', [\App\Http\Controllers\Laporan\LabController::class, 'index'])->name('laporan.laboratorium');
    Route::get('/laboratorium/data', [\App\Http\Controllers\Laporan\LabController::class, 'data'])->name('laporan.laboratorium.data');
    Route::get('/laboratorium/grouped-data', [\App\Http\Controllers\Laporan\LabController::class, 'groupedData']);
    Route::get('/laboratorium/permintaan-details/{visitationId}', [\App\Http\Controllers\Laporan\LabController::class, 'permintaanDetails']);
        Route::get('/laboratorium/monthly-stats', [\App\Http\Controllers\Laporan\LabController::class, 'monthlyStats']);
    Route::get('/laboratorium/chart', function() { return view('laporan.laboratorium.lab_chart'); });
    // Dokter & Klinik list for laporan filter (no middleware)
    Route::get('/dokters', [\App\Http\Controllers\Laporan\LabController::class, 'listDokters'])->name('laporan.dokters');
    Route::get('/kliniks', [\App\Http\Controllers\Laporan\LabController::class, 'listKliniks'])->name('laporan.kliniks');
    // Export & Print routes for Laporan Laboratorium
    Route::get('/laboratorium/export-excel', [\App\Http\Controllers\Laporan\LabController::class, 'exportExcel'])->name('laporan.laboratorium.exportExcel');
    Route::get('/laboratorium/print-pdf', [\App\Http\Controllers\Laporan\LabController::class, 'printPdf'])->name('laporan.laboratorium.printPdf');
});

Route::get('/hrd/absensi-rekap/export-excel', [\App\Http\Controllers\HRD\AbsensiRekapController::class, 'exportExcel'])->name('hrd.absensi_rekap.export_excel');
// ERM Routes
Route::prefix('erm')->middleware('role:Dokter|Perawat|Pendaftaran|Admin|Farmasi|Beautician|Lab')->group(function () {
    // Dokter to Perawat notification
        Route::post('/send-notif-perawat', [App\Http\Controllers\ERM\RawatJalanController::class, 'sendNotifToPerawat'])->middleware('auth');
            // ...existing ERM routes...
        Route::get('fakturretur', [\App\Http\Controllers\ERM\FakturReturController::class, 'index'])->name('erm.fakturretur.index');
        Route::get('fakturretur/create', [\App\Http\Controllers\ERM\FakturReturController::class, 'create'])->name('erm.fakturretur.create');
        Route::post('fakturretur', [\App\Http\Controllers\ERM\FakturReturController::class, 'store'])->name('erm.fakturretur.store');
        Route::get('fakturretur/{id}', [\App\Http\Controllers\ERM\FakturReturController::class, 'show'])->name('erm.fakturretur.show');
        Route::post('fakturretur/{id}/approve', [\App\Http\Controllers\ERM\FakturReturController::class, 'approve'])->name('erm.fakturretur.approve');
    

    // AJAX endpoint for nilai stok gudang & keseluruhan
    Route::get('/stok-gudang/nilai-stok', [\App\Http\Controllers\ERM\StokGudangController::class, 'getNilaiStok'])->name('erm.stok-gudang.nilai-stok');
    Route::get('/get-notif', [App\Http\Controllers\ERM\RawatJalanController::class, 'getNotif'])->middleware('auth');
    // AJAX: Get allow_post value for InformConsent
    Route::get('/inform-consent/{id}/get', [App\Http\Controllers\ERM\TindakanController::class, 'getInformConsentAllowPost']);
    
    // Warehouse Stock Management
    Route::prefix('stok-gudang')->group(function () {
        Route::get('/', [StokGudangController::class, 'index'])->name('erm.stok-gudang.index');
        Route::get('/data', [StokGudangController::class, 'getData'])->name('erm.stok-gudang.data');
        Route::get('/batch-details', [StokGudangController::class, 'getBatchDetails'])->name('erm.stok-gudang.batch-details');
        Route::post('/update-batch-stok', [StokGudangController::class, 'updateBatchStok'])->name('erm.stok-gudang.update-batch-stok');
    });
    
    // Mutasi Gudang Routes
    Route::prefix('mutasi-gudang')->group(function () {
    Route::get('/', [MutasiGudangController::class, 'index'])->name('erm.mutasi-gudang.index');
    Route::get('/data', [MutasiGudangController::class, 'data'])->name('erm.mutasi-gudang.data');
    Route::post('/', [MutasiGudangController::class, 'store'])->name('erm.mutasi-gudang.store');
    // Route baru: get data obat sesuai stok di gudang asal
    Route::get('/obat', [MutasiGudangController::class, 'getObatGudang'])->name('erm.mutasi-gudang.obat');
    
    // Migration routes - untuk migrasi stok dari field stok obat ke gudang (HARUS SEBELUM {id})
    Route::get('/migration-preview', [MutasiGudangController::class, 'getMigrationPreview'])->name('erm.mutasi-gudang.migration-preview');
    Route::post('/migrate-stok', [MutasiGudangController::class, 'migrateStokToGudang'])->name('erm.mutasi-gudang.migrate-stok');
    Route::post('/cleanup-field-stok', [MutasiGudangController::class, 'cleanupFieldStok'])->name('erm.mutasi-gudang.cleanup-field-stok');
    
    // Route dengan parameter {id} HARUS DI AKHIR
    Route::get('/{id}', [MutasiGudangController::class, 'show'])->name('erm.mutasi-gudang.show');
    Route::post('/{id}/approve', [MutasiGudangController::class, 'approve'])->name('erm.mutasi-gudang.approve');
    Route::post('/{id}/reject', [MutasiGudangController::class, 'reject'])->name('erm.mutasi-gudang.reject');
    });

    // Gudang Routes
    Route::prefix('gudang')->group(function () {
        Route::get('/', [GudangController::class, 'index'])->name('erm.gudang.index');
        Route::get('/data', [GudangController::class, 'data'])->name('erm.gudang.data');
        Route::post('/', [GudangController::class, 'store'])->name('erm.gudang.store');
        Route::get('/{gudang}', [GudangController::class, 'show'])->name('erm.gudang.show');
        Route::put('/{gudang}', [GudangController::class, 'update'])->name('erm.gudang.update');
        Route::delete('/{gudang}', [GudangController::class, 'destroy'])->name('erm.gudang.destroy');
    });

    Route::get('/obat-masuk/detail', [App\Http\Controllers\ERM\ObatMasukController::class, 'detail'])->name('erm.obatmasuk.detail');
    Route::get('/fakturpembelian/{id}/print', [App\Http\Controllers\ERM\FakturBeliController::class, 'printFaktur'])->name('erm.fakturbeli.print');
    // DataTables AJAX for Mutasi Obat Masuk
    Route::get('/obat-masuk/data', [App\Http\Controllers\ERM\ObatMasukController::class, 'data'])->name('erm.obatmasuk.data');
    // Mutasi Obat Masuk
    Route::get('/obat-masuk', [App\Http\Controllers\ERM\ObatMasukController::class, 'index'])->name('erm.obatmasuk.index');
    // Update harga jual (nonfornas) via AJAX
    Route::post('/obat/{id}/update-harga', [App\Http\Controllers\ERM\ObatController::class, 'updateHargaJual'])->name('erm.obat.update-harga');
    // Export Obat data to Excel
    Route::get('/obat/export-excel', [App\Http\Controllers\ERM\ObatController::class, 'exportExcel'])->name('erm.obat.export-excel');
    // Monitor Profit
    Route::get('/monitor-profit', [App\Http\Controllers\ERM\ObatController::class, 'monitorProfit'])->name('erm.monitor-profit');
        // Inline update stok fisik for hasil stok opname
        Route::post('stokopname-item/{id}/update-stok-fisik', [App\Http\Controllers\ERM\StokOpnameController::class, 'updateStokFisik'])->name('erm.stokopnameitem.update-stok-fisik');
    // AJAX endpoints for select2 (controller)
    Route::get('ajax/obat', [App\Http\Controllers\ERM\MasterFakturController::class, 'ajaxObat']);
    Route::get('ajax/pemasok', [App\Http\Controllers\ERM\MasterFakturController::class, 'ajaxPemasok']);

        // Kartu Stok
        Route::get('/kartu-stok', [App\Http\Controllers\ERM\KartuStokController::class, 'index'])->name('erm.kartustok.index');
        Route::get('/kartu-stok/data', [App\Http\Controllers\ERM\KartuStokController::class, 'data'])->name('erm.kartustok.data');
        Route::get('/kartu-stok/detail', [App\Http\Controllers\ERM\KartuStokController::class, 'detail'])->name('erm.kartustok.detail');

        // Gudang Mapping Management
        Route::get('/gudang-mapping', [App\Http\Controllers\ERM\GudangMappingController::class, 'index'])->name('erm.gudang-mapping.index');
        Route::post('/gudang-mapping', [App\Http\Controllers\ERM\GudangMappingController::class, 'store'])->name('erm.gudang-mapping.store');
        Route::get('/gudang-mapping/{id}', [App\Http\Controllers\ERM\GudangMappingController::class, 'show'])->name('erm.gudang-mapping.show');
        Route::put('/gudang-mapping/{id}', [App\Http\Controllers\ERM\GudangMappingController::class, 'update'])->name('erm.gudang-mapping.update');
        Route::delete('/gudang-mapping/{id}', [App\Http\Controllers\ERM\GudangMappingController::class, 'destroy'])->name('erm.gudang-mapping.destroy');
        Route::get('/gudang-mapping-active', [App\Http\Controllers\ERM\GudangMappingController::class, 'getActiveMappings'])->name('erm.gudang-mapping.active');
        Route::get('/gudang-mapping-default/{transactionType}', [App\Http\Controllers\ERM\GudangMappingController::class, 'getDefaultGudang'])->name('erm.gudang-mapping.default');
    // Pasien Management
    // Route::get('/pasiens', [PasienController::class, 'index'])->name('erm.pasiens.index');
    Route::get('/pasiens/create', [PasienController::class, 'create'])->name('erm.pasiens.create');
    Route::post('/pasiens', [PasienController::class, 'store'])->name('erm.pasiens.store');
    Route::get('/pasiens/{id}/edit', [PasienController::class, 'edit'])->name('erm.pasiens.edit');
    Route::put('/pasiens/{id}', [PasienController::class, 'update'])->name('erm.pasiens.update');
    Route::post('/pasiens/{id}/update-status', [PasienController::class, 'updateStatus'])->name('erm.pasiens.update-status');
    Route::post('/pasiens/{id}/update-status-akses', [PasienController::class, 'updateStatusAkses'])->name('erm.pasiens.update-status-akses');
    Route::post('/pasiens/{id}/update-status-combined', [PasienController::class, 'updateStatusCombined'])->name('erm.pasiens.update-status-combined');
    Route::delete('/pasiens/{id}', [PasienController::class, 'destroy'])->name('erm.pasiens.destroy');
    Route::get('/erm/pasien/{id}', [PasienController::class, 'show'])->name('erm.pasien.show');

    // Master Pemasok AJAX CRUD
    Route::get('pemasok', [App\Http\Controllers\ERM\PemasokController::class, 'index']);
    Route::post('pemasok', [App\Http\Controllers\ERM\PemasokController::class, 'store']);
    Route::put('pemasok/{id}', [App\Http\Controllers\ERM\PemasokController::class, 'update']);
    Route::delete('pemasok/{id}', [App\Http\Controllers\ERM\PemasokController::class, 'destroy']);

    // Export to Excel
    Route::get('pemasok/export-excel', [App\Http\Controllers\ERM\PemasokController::class, 'exportExcel']);

   
    Route::get('permintaan/data', [App\Http\Controllers\ERM\PermintaanController::class, 'data'])->name('erm.permintaan.data');
    Route::get('permintaan/master-faktur', [App\Http\Controllers\ERM\PermintaanController::class, 'getMasterFaktur'])->name('erm.permintaan.masterfaktur');
    Route::get('permintaan/{id}/print', [App\Http\Controllers\ERM\PermintaanController::class, 'printSuratPermintaan'])->name('erm.permintaan.print');
    Route::resource('permintaan', App\Http\Controllers\ERM\PermintaanController::class)->names('erm.permintaan');
    Route::post('permintaan/{id}/approve', [App\Http\Controllers\ERM\PermintaanController::class, 'approve'])->name('erm.permintaan.approve');
    Route::resource('masterfaktur', App\Http\Controllers\ERM\MasterFakturController::class)->names('erm.masterfaktur');
    Route::get('masterfaktur-data', [App\Http\Controllers\ERM\MasterFakturController::class, 'data'])->name('erm.masterfaktur.data');

    // AJAX form for create/edit modal
    Route::get('masterfaktur/form/{id?}', [App\Http\Controllers\ERM\MasterFakturController::class, 'form'])->name('erm.masterfaktur.form');

    // Obat Keluar
    Route::get('/obat-keluar', [ObatKeluarController::class, 'index'])->name('erm.obatkeluar.index');
    Route::get('/obat-keluar/data', [ObatKeluarController::class, 'data'])->name('erm.obatkeluar.data');
    Route::get('/obat-keluar/detail', [ObatKeluarController::class, 'detail'])->name('erm.obatkeluar.detail');

    //Visitation
    Route::get('/pasiens', [PasienController::class, 'index'])->name('erm.pasiens.index');
    Route::post('/visitations', [VisitationController::class, 'store'])->name('erm.visitations.store');
    Route::post('/visitations/produk', [VisitationController::class, 'storeProduk'])->name('erm.visitations.produk.store');
    Route::post('/visitations/lab', [VisitationController::class, 'storeLab'])->name('erm.visitations.lab.store');
    Route::get('/visitation/cek-antrian', [VisitationController::class, 'cekAntrian'])->name('erm.visitations.cekAntrian');
    Route::get('/rawatjalans', [RawatJalanController::class, 'index'])->name('erm.rawatjalans.index');
    Route::get('/rawatjalans/stats', [RawatJalanController::class, 'getStats'])->name('erm.rawatjalans.stats');
    Route::post('/rawatjalans/create', [RawatJalanController::class, 'store'])->name('erm.rawatjalans.store');
    Route::get('/cek-antrian', [RawatJalanController::class, 'cekAntrian'])->name('erm.rawatjalans.cekAntrian');
    // AJAX: Get list of visitations by status for Rawat Jalan stats modal
    Route::get('/rawatjalans/list-by-status', [App\Http\Controllers\ERM\RawatJalanController::class, 'listByStatus']);
    // AJAX: Restore visitation status from dibatalkan (7) to tidak datang (0)
    Route::post('/rawatjalans/restore-status', [App\Http\Controllers\ERM\RawatJalanController::class, 'restoreStatus']);



    //Asesmen
    Route::get('asesmendokter/{visitation}/create', [AsesmenController::class, 'create'])->name('erm.asesmendokter.create');
    Route::post('asesmendokter/store', [AsesmenController::class, 'store'])->name('erm.asesmendokter.store');

    //asesmen perawat
    Route::get('asesmenperawat/{visitation}/create', [AsesmenPerawatController::class, 'create'])->name('erm.asesmenperawat.create');
    Route::post('asesmenperawat/store', [AsesmenPerawatController::class, 'store'])->name('erm.asesmenperawat.store');

    //screening batuk
    Route::post('screening/batuk/store', [RawatJalanController::class, 'storeScreeningBatuk'])->name('erm.screening.batuk.store');
    Route::get('screening/batuk/{visitation}', [RawatJalanController::class, 'getScreeningBatuk'])->name('erm.screening.batuk.get');
    Route::put('screening/batuk/update/{id}', [RawatJalanController::class, 'updateScreeningBatuk'])->name('erm.screening.batuk.update');

    //CPPT
    Route::get('cppt/{visitation_id}/create', [CPPTController::class, 'create'])->name('erm.cppt.create');
    Route::post('cppt/store', [CPPTController::class, 'store'])->name('erm.cppt.store');
    Route::post('cppt/{id}/mark-read', [CPPTController::class, 'markAsRead'])->name('erm.cppt.mark-read');
    Route::get('/cppt/history-json/{visitation}', [CPPTController::class, 'historyJson']);
    Route::post('cppt/{id}/update', [CPPTController::class, 'update']);
    Route::post('cppt/mark-all-read/{visitationId}', [CPPTController::class, 'markAllAsRead']);

    // E Resep Dokter
    Route::get('eresep/{visitation_id}/create', [EresepController::class, 'create'])->name('erm.eresep.create');
    Route::post('/resep/non-racikan', [EresepController::class, 'storeNonRacikan'])->name('resep.nonracikan.store');
    Route::post('/resep/racikan', [EresepController::class, 'storeRacikan'])->name('resep.racikan.store');
    Route::delete('/resep/nonracikan/{id}', [EresepController::class, 'destroyNonRacikan'])->name('resep.nonracikan.destroy');
    Route::delete('/resep/racikan/{racikanKe}', [EresepController::class, 'destroyRacikan'])->name('resep.racikan.destroy');
    Route::put('resep/nonracikan/{id}', [EresepController::class, 'updateNonRacikan'])->name('resep.nonracikan.update');
    Route::put('/resep/racikan/{racikanKe}', [EresepController::class, 'updateRacikan'])->name('resep.racikan.update');

    // E Resep Farmasi
    Route::get('/eresepfarmasi', [EresepController::class, 'index'])->name('erm.eresepfarmasi.index');
    Route::get('eresepfarmasi/{visitation_id}/create', [EresepController::class, 'farmasicreate'])->name('erm.eresepfarmasi.create');
    Route::post('/resepfarmasi/non-racikan', [EresepController::class, 'farmasistoreNonRacikan'])->name('resepfarmasi.nonracikan.store');
    Route::post('/resepfarmasi/racikan', [EresepController::class, 'farmasistoreRacikan'])->name('resepfarmasi.racikan.store');
    Route::delete('/resepfarmasi/nonracikan/{id}', [EresepController::class, 'farmasidestroyNonRacikan'])->name('resepfarmasi.nonracikan.destroy');
    Route::delete('/resepfarmasi/racikan/{racikanKe}', [EresepController::class, 'farmasidestroyRacikan'])->name('resepfarmasi.racikan.destroy');
    Route::put('resepfarmasi/nonracikan/{id}', [EresepController::class, 'farmasiupdateNonRacikan'])->name('resepfarmasi.nonracikan.update');
    Route::put('/resepfarmasi/racikan/{racikanKe}', [EresepController::class, 'farmasiupdateRacikan'])->name('resepfarmasi.racikan.update');

    Route::get('/eresepfarmasi/{visitation_id}/json', [EresepController::class, 'getFarmasiResepJson'])->name('erm.eresepfarmasi.json');
    Route::post('/eresepfarmasi/{visitation_id}/copy-from-dokter', [EresepController::class, 'copyFromDokter'])->name('erm.eresepfarmasi.copyfromdokter');

    Route::get('/eresepfarmasi/{visitation_id}/print', [EresepController::class, 'printResep'])->name('erm.eresepfarmasi.print');

    Route::post('/eresepfarmasi/copy-from-history', [EresepController::class, 'copyFromHistoryFarmasi'])->name('erm.eresepfarmasi.copyfromhistory');
    Route::post('/eresep/copy-from-history', [EresepController::class, 'copyFromHistory'])->name('erm.eresep.copyfromhistory');
    
    // Notification Routes
    Route::get('/check-notifications', [NotificationController::class, 'checkNewNotifications'])->name('erm.check.notifications');
    Route::post('/notify-pasien-keluar', [NotificationController::class, 'notifyPasienKeluar'])->name('erm.notify.pasien.keluar');
    
    // Paket Racikan Routes
    Route::get('/paket-racikan', [EresepController::class, 'paketRacikanIndex'])->name('erm.paket-racikan.index');
    Route::get('/paket-racikan/list', [EresepController::class, 'getPaketRacikanList'])->name('erm.paket-racikan.list');
    Route::post('/paket-racikan/copy', [EresepController::class, 'copyFromPaketRacikan'])->name('erm.paket-racikan.copy');
    Route::post('/paket-racikan/store', [EresepController::class, 'storePaketRacikan'])->name('erm.paket-racikan.store');
    Route::delete('/paket-racikan/{id}', [EresepController::class, 'deletePaketRacikan'])->name('erm.paket-racikan.delete');
    
    Route::get('/eresepfarmasi/{visitation_id}/print-etiket', [EresepController::class, 'printEtiket'])->name('erm.eresepfarmasi.print-etiket');

    // Statistik Farmasi Routes
    Route::get('/statistic', [StatisticController::class, 'index'])->name('erm.statistic.index');
    Route::get('/statistic/data', [StatisticController::class, 'getResepData'])->name('erm.statistic.data');

    Route::post('/edukasi-obat/store', [EresepController::class, 'storeEdukasiObat'])->name('edukasi.obat.store');
    Route::get('/edukasi-obat/{visitationId}/print', [EresepController::class, 'printEdukasiObat'])->name('edukasi.obat.print');
    // Add these routes to your routes/web.php file
    Route::get('/resep/dokter/{visitationId}/get', [EresepController::class, 'getResepDokterByVisitation'])->name('resep.dokter.get');
    Route::get('/resep/farmasi/{pasienId}', [EresepController::class, 'getRiwayatFarmasi'])->name('resep.farmasi.get');
    // Riwayat Farmasi
    Route::get('/riwayat-resep/dokter/{pasienId}', [EresepController::class, 'getRiwayatDokter'])->name('resep.historydokter');
    Route::get('/riwayat-resep/farmasi/{pasienId}', [EresepController::class, 'getRiwayatFarmasi'])->name('resep.historyfarmasi');
    Route::post('/resep/catatan/store', [ResepCatatanController::class, 'store'])->name('resep.catatan.store');
    //Alergi
    Route::post('/pasiens/{visitation}/alergi', [AlergiController::class, 'store'])->name('erm.alergi.store');

    //Radiologi
    Route::get('/eradiologi/{visitation_id}/create', [EradiologiController::class, 'create'])->name('erm.eradiologi.create');
    Route::post('/eradiologi/store', [EradiologiController::class, 'store'])->name('erm.eradiologi.store');
    Route::get('/eradiologi/tests/data', [EradiologiController::class, 'getRadiologiTestData'])->name('erm.eradiologi.tests.data');
    Route::get('/eradiologi/{visitation_id}/requests/data', [EradiologiController::class, 'getRadiologiPermintaanData'])->name('erm.eradiologi.requests.data');
    Route::delete('/eradiologi/permintaan/{id}', [EradiologiController::class, 'destroy'])->name('erm.eradiologi.destroy');
    Route::put('/eradiologi/permintaan/{id}/status', [EradiologiController::class, 'updateStatus'])->name('erm.eradiologi.update-status');
    Route::post('/eradiologi/permintaan/bulk-delete', [EradiologiController::class, 'bulkDelete'])->name('erm.eradiologi.bulk-delete');
    Route::post('/eradiologi/permintaan/bulk-update', [EradiologiController::class, 'bulkUpdate'])->name('erm.eradiologi.bulk-update');
    Route::get('/eradiologi/{visitation_id}/print', [EradiologiController::class, 'printPermintaan'])->name('erm.eradiologi.print');
    Route::get('/eradiologi/{visitation_id}/dokumen/data', [EradiologiController::class, 'getRadiologiDokumenData'])->name('erm.eradiologi.dokumen.data');
    Route::post('/eradiologi/hasil/upload', [EradiologiController::class, 'uploadRadiologiHasil'])->name('erm.eradiologi.hasil.upload');
    Route::get('/eradiologi/hasil/{id}', [EradiologiController::class, 'getRadiologiHasilDetails'])->name('erm.eradiologi.hasil.detail');
    
    //Lab
    Route::get('/elab', [ElabController::class, 'index'])->name('erm.elab.index');
    Route::get('/elab/{visitation_id}/create', [ElabController::class, 'create'])->name('erm.elab.create');
    Route::post('/elab/store', [ElabController::class, 'store'])->name('erm.elab.store');  
    Route::get('/elab/{visitation_id}/requests/data', [ElabController::class, 'getLabPermintaanData'])->name('erm.elab.requests.data');
    Route::get('/elab/patient/{pasien_id}/history', [ElabController::class, 'getPatientLabHistory'])->name('erm.elab.patient.history');
    Route::get('/elab/visitation/{visitation_id}/detail', [ElabController::class, 'getVisitationLabDetail'])->name('erm.elab.visitation.detail');
    Route::delete('/elab/permintaan/{id}', [ElabController::class, 'destroy'])->name('erm.elab.destroy');
    Route::put('/elab/permintaan/{id}/status', [ElabController::class, 'updateStatus'])->name('erm.elab.update-status');
    Route::post('/elab/permintaan/bulk-update', [ElabController::class, 'bulkUpdate'])->name('erm.elab.bulk-update');
    Route::get('/elab/{visitation_id}/hasil-lis/data', [ElabController::class, 'getHasilLisData'])->name('erm.elab.hasil-lis.data');
    Route::get('/elab/hasil-lis/{visitation_id}', [ElabController::class, 'getHasilLisDetails'])->name('erm.elab.hasil-lis.detail');
    Route::get('/elab/hasil-lis/{visitation_id}/pdf', [ElabController::class, 'generateHasilLisPdf'])->name('erm.elab.hasil-lis.pdf');
    Route::get('/elab/lembar-monitoring/{pasien_id}/pdf', [ElabController::class, 'generateLembarMonitoringPdf'])->name('erm.elab.lembar-monitoring.pdf');
    Route::get('/elab/{visitation_id}/hasil-eksternal/data', [ElabController::class, 'getHasilEksternalData'])->name('erm.elab.hasil-eksternal.data');
    Route::get('/elab/hasil-eksternal/{id}', [ElabController::class, 'getHasilEksternalDetail'])->name('erm.elab.hasil-eksternal.detail');
    Route::post('/elab/hasil-eksternal/store', [ElabController::class, 'storeHasilEksternal'])->name('erm.elab.hasil-eksternal.store');
    Route::post('/elab/hasil-lis/store', [ElabController::class, 'storeHasilLis'])->name('erm.elab.hasil-lis.store');

    //Tindakan & Inform Consent
    Route::get('/tindakan/{visitation_id}/create', [TindakanController::class, 'create'])->name('erm.tindakan.create');
    Route::get('/tindakan/data/{spesialisasi_id}', [TindakanController::class, 'getTindakanData'])->name('erm.tindakan.data');
    Route::get('/paket-tindakan/data/{spesialisasi_id}', [TindakanController::class, 'getPaketTindakanData'])->name('erm.paket-tindakan.data');
    Route::get('/tindakan/inform-consent/{id}', [TindakanController::class, 'informConsent'])->name('erm.tindakan.inform-consent');
    Route::post('/tindakan/inform-consent/save', [TindakanController::class, 'saveInformConsent'])->name('erm.tindakan.inform-consent.save');
    Route::get('/tindakan/history/{visitation}', [TindakanController::class, 'getRiwayatTindakanHistory'])->name('tindakan.history');
    Route::get('/tindakan/sop/{id}', [TindakanController::class, 'generateSopPdf'])->name('erm.tindakan.sop');

    Route::post('/tindakan/upload-foto/{id}', [TindakanController::class, 'uploadFoto'])->name('erm.tindakan.upload-foto');
    
    // SPK Routes
    Route::get('/tindakan/spk/by-riwayat/{riwayat_id}', [TindakanController::class, 'getSpkDataByRiwayat'])->name('erm.tindakan.spk.byriwayat');
    Route::post('/tindakan/spk/save', [TindakanController::class, 'saveSpk'])->name('erm.tindakan.spk.save');
    Route::get('/spk', [TindakanController::class, 'spkIndex'])->name('erm.spk.index');
    Route::get('/spk/create', [TindakanController::class, 'spkCreate'])->name('erm.spk.create');
    Route::get('/spk/modal', [TindakanController::class, 'spkModal'])->name('erm.spk.modal');
    Route::get('/spk/print/{riwayatId}', [TindakanController::class, 'printSpk'])->name('erm.spk.print');

    //Riwayat Kunjungan
    Route::get('/riwayat-kunjungan/{pasien}', [RiwayatKunjunganController::class, 'index'])->name('erm.riwayatkunjungan.index');

    Route::get('/resume-medis/{visitation}', [RiwayatKunjunganController::class, 'resumeMedis'])->name('resume.medis');
    Route::get('/riwayatkunjungan/{visitation}/get-data-diagnosis', [RiwayatKunjunganController::class, 'getDataDiagnosis']);
    Route::post('/riwayatkunjungan/store-surat-diagnosis', [RiwayatKunjunganController::class, 'storeSuratDiagnosis']);
    Route::get('/riwayatkunjungan/{visitation}/print-surat-diagnosis', [RiwayatKunjunganController::class, 'printSuratDiagnosis'])->name('riwayatkunjungan.print-surat-diagnosis');
        Route::get('/riwayatkunjungan/{pasien}/get-data-diagnosis-table', [RiwayatKunjunganController::class, 'getDataDiagnosisTable'])->name('riwayatkunjungan.get-data-diagnosis-table');
    Route::get('/riwayatkunjungan/{visitation}/print-surat-diagnosis-en', [RiwayatKunjunganController::class, 'printSuratDiagnosisEn'])->name('riwayatkunjungan.print-surat-diagnosis-en');


    //Antrian
    Route::get('/listantrian', [ListAntrianController::class, 'index']);
    Route::get('/api/patient-events', [ListAntrianController::class, 'getEvents']);

    // Obat
    Route::get('/obat', [ObatController::class, 'index'])->name('erm.obat.index');
    Route::get('/obat/create', [ObatController::class, 'create'])->name('erm.obat.create');
    Route::post('/obat', [ObatController::class, 'store'])->name('erm.obat.store');
    Route::get('/obat/{id}/edit', [ObatController::class, 'edit'])->name('erm.obat.edit');
    Route::delete('/obat/{id}', [ObatController::class, 'destroy'])->name('erm.obat.destroy');

    // Surat Istirahat & Surat Mondok (Combined)
    Route::get('/surat/{pasien_id}', [SuratIstirahatController::class, 'index'])->name('erm.suratistirahat.index');
    Route::post('/surat', [SuratIstirahatController::class, 'store'])->name('erm.suratistirahat.store');
    Route::get('/surat-istirahat/{id}', [SuratIstirahatController::class, 'suratIstirahat'])->name('surat.istirahat');
    
    // Surat Mondok routes
    Route::get('/suratmondok-data/{pasien_id}', [SuratIstirahatController::class, 'getMondokData'])->name('erm.suratmondok.data');
    Route::post('/suratmondok', [SuratIstirahatController::class, 'storeMondok'])->name('erm.suratmondok.store');
    Route::get('/surat-mondok/{id}', [SuratMondokController::class, 'suratMondok'])->name('surat.mondok');
    Route::get('/suratmondok/asesmen-data/{visitation_id}', [SuratIstirahatController::class, 'getAsesmenData'])->name('erm.suratmondok.asesmen-data');

    //Submit Billing Obat
    Route::post('/resepfarmasi/submit', [EResepController::class, 'submitResep'])->name('resepfarmasi.submit');

    Route::get('/birthday', [BirthdayController::class, 'index'])->name('erm.birthday.index');
    Route::get('/birthday/data', [BirthdayController::class, 'getData'])->name('erm.birthday.data');
    Route::post('/birthday/mark-sent', [BirthdayController::class, 'markAsSent'])->name('erm.birthday.mark-sent');
    Route::post('/birthday/generate-image', [BirthdayController::class, 'generateImage'])->name('erm.birthday.generate-image');
    Route::get('/birthday/image/{filename}', [BirthdayController::class, 'showImage'])->name('erm.birthday.show-image');

    Route::post('/rawatjalans/batalkan', [RawatJalanController::class, 'batalkan']);
    Route::post('/rawatjalans/edit-antrian', [RawatJalanController::class, 'editAntrian']);


    // Batalkan (delete) riwayat tindakan
    Route::delete('/tindakan/riwayat/{id}', [TindakanController::class, 'destroyRiwayatTindakan'])->name('erm.tindakan.riwayat.destroy');
    
    // SOP route
    Route::get('/tindakan/{id}/sop-list', [TindakanController::class, 'getSopList']);

    
    // Faktur Pembelian
    Route::get('/fakturpembelian', [\App\Http\Controllers\ERM\FakturBeliController::class, 'index'])->name('erm.fakturbeli.index');
    Route::get('/fakturpembelian/cari-by-no-permintaan', [\App\Http\Controllers\ERM\FakturBeliController::class, 'cariByNoPermintaan']);
    Route::get('/fakturpembelian/create', [\App\Http\Controllers\ERM\FakturBeliController::class, 'create'])->name('erm.fakturbeli.create');
    Route::post('/fakturpembelian', [\App\Http\Controllers\ERM\FakturBeliController::class, 'store'])->name('erm.fakturbeli.store');
    Route::get('/fakturpembelian/{id}/edit', [\App\Http\Controllers\ERM\FakturBeliController::class, 'edit'])->name('erm.fakturbeli.edit');
    Route::post('/fakturpembelian/{id}/update', [\App\Http\Controllers\ERM\FakturBeliController::class, 'update'])->name('erm.fakturbeli.update');
    Route::delete('/fakturpembelian/{id}', [\App\Http\Controllers\ERM\FakturBeliController::class, 'destroy'])->name('erm.fakturbeli.destroy');
    
    // Permintaan Pembelian (New routes)
    Route::get('/fakturpembelian/permintaan/create', [\App\Http\Controllers\ERM\FakturBeliController::class, 'createPermintaan'])->name('erm.fakturbeli.createPermintaan');
    Route::post('/fakturpembelian/permintaan', [\App\Http\Controllers\ERM\FakturBeliController::class, 'storePermintaan'])->name('erm.fakturbeli.storePermintaan');
    Route::get('/fakturpembelian/permintaan/{id}/edit', [\App\Http\Controllers\ERM\FakturBeliController::class, 'editPermintaan'])->name('erm.fakturbeli.editPermintaan');
    Route::post('/fakturpembelian/permintaan/{id}/update', [\App\Http\Controllers\ERM\FakturBeliController::class, 'updatePermintaan'])->name('erm.fakturbeli.updatePermintaan');
    Route::get('/fakturpembelian/{id}/complete', [\App\Http\Controllers\ERM\FakturBeliController::class, 'completeFaktur'])->name('erm.fakturbeli.completeFaktur');
    Route::post('/fakturpembelian/{id}/approve', [\App\Http\Controllers\ERM\FakturBeliController::class, 'approveFaktur'])->name('erm.fakturbeli.approveFaktur');
    Route::get('/fakturpembelian/{id}/debug-hpp', [\App\Http\Controllers\ERM\FakturBeliController::class, 'debugHpp'])->name('erm.fakturbeli.debugHpp');

    // Stok Opname Routes
    Route::prefix('/stokopname')->middleware('auth')->group(function () {
    Route::get('/', [\App\Http\Controllers\ERM\StokOpnameController::class, 'index'])->name('erm.stokopname.index');
    Route::post('/', [\App\Http\Controllers\ERM\StokOpnameController::class, 'store'])->name('erm.stokopname.store');
    Route::get('/{id}/create', [\App\Http\Controllers\ERM\StokOpnameController::class, 'create'])->name('erm.stokopname.create');
    Route::get('/{id}/download-excel', [\App\Http\Controllers\ERM\StokOpnameController::class, 'downloadExcel'])->name('erm.stokopname.downloadExcel');
    Route::post('/{id}/upload-excel', [\App\Http\Controllers\ERM\StokOpnameController::class, 'uploadExcel'])->name('erm.stokopname.uploadExcel');
    // Stok Opname Items DataTable AJAX
    Route::get('/{id}/items-data', [\App\Http\Controllers\ERM\StokOpnameController::class, 'itemsData'])->name('erm.stokopname.itemsData');
    // Update catatan/notes for stok opname item
    Route::post('/item/{itemId}/update-notes', [\App\Http\Controllers\ERM\StokOpnameController::class, 'updateItemNotes'])->name('erm.stokopname.item.updateNotes');
    // Update status for stok opname (AJAX)
    Route::post('/{id}/update-status', [\App\Http\Controllers\ERM\StokOpnameController::class, 'updateStatus'])->name('erm.stokopname.updateStatus');
    // Save stok fisik to stok obat
    Route::post('/{id}/save-stok-fisik', [\App\Http\Controllers\ERM\StokOpnameController::class, 'saveStokFisik'])->name('erm.stokopname.saveStokFisik');
    // AJAX sync totals
    Route::get('/{id}/sync-totals', [\App\Http\Controllers\ERM\StokOpnameController::class, 'getStokTotals'])->name('erm.stokopname.syncTotals');
    
    // New multi-gudang stock opname routes
    Route::post('/{id}/generate-items', [\App\Http\Controllers\ERM\StokOpnameController::class, 'generateStokOpnameItems'])->name('erm.stokopname.generateItems');
    Route::post('/{id}/update-stock-from-opname', [\App\Http\Controllers\ERM\StokOpnameController::class, 'updateStokFromOpname'])->name('erm.stokopname.updateStockFromOpname');
    });
    
});

Route::prefix('workdoc')->middleware('role:Hrd|Manager|Employee|Admin')->group(function () {
    Route::get('/', [WorkdocDashboardController::class, 'index'])->name('workdoc.dashboard');
    Route::get('/documents', [App\Http\Controllers\Workdoc\DocumentController::class, 'index'])->name('workdoc.documents.index');
    
    // AJAX route for folder contents
    Route::get('/documents/folder/{folder_id}/contents', [App\Http\Controllers\Workdoc\DocumentController::class, 'getFolderContents'])->name('documents.folder.contents');
    
    // File operations
    Route::post('/documents', [App\Http\Controllers\Workdoc\DocumentController::class, 'store'])->name('workdoc.documents.store');
    Route::get('/documents/{document}/download', [App\Http\Controllers\Workdoc\DocumentController::class, 'download'])->name('workdoc.documents.download');
    Route::delete('/documents/{document}', [App\Http\Controllers\Workdoc\DocumentController::class, 'destroy'])->name('workdoc.documents.destroy');
    
    // Folder operations
    Route::post('/folders', [App\Http\Controllers\Workdoc\FolderController::class, 'store'])->name('workdoc.folders.store');
    Route::put('/folders/{folder}', [App\Http\Controllers\Workdoc\FolderController::class, 'update'])->name('folders.update');
    Route::delete('/folders/{folder}', [App\Http\Controllers\Workdoc\FolderController::class, 'destroy'])->name('workdoc.folders.destroy');
    Route::put('/folders/{folder}', [App\Http\Controllers\Workdoc\FolderController::class, 'rename'])->name('workdoc.folders.rename');
    Route::put('/documents/{document}', [App\Http\Controllers\Workdoc\DocumentController::class, 'rename'])->name('documents.rename');
    // Optionally, add a preview route if you want to serve files securely
    // Route::get('/documents/{document}/preview', [App\Http\Controllers\Workdoc\DocumentController::class, 'preview'])->name('documents.preview');
    // Notulensi Rapat routes
    Route::get('/notulensi-rapat', [App\Http\Controllers\Workdoc\NotulensiRapatController::class, 'index'])->name('workdoc.notulensi-rapat.index');
    Route::get('/notulensi-rapat/create', [App\Http\Controllers\Workdoc\NotulensiRapatController::class, 'create'])->name('workdoc.notulensi-rapat.create');
    Route::post('/notulensi-rapat', [App\Http\Controllers\Workdoc\NotulensiRapatController::class, 'store'])->name('workdoc.notulensi-rapat.store');
    Route::get('/notulensi-rapat/{id}', [App\Http\Controllers\Workdoc\NotulensiRapatController::class, 'show'])->name('workdoc.notulensi-rapat.show');
    // To-Do routes for Notulensi Rapat
    Route::get('/notulensi-rapat/{id}/todos', [App\Http\Controllers\Workdoc\NotulensiRapatController::class, 'todos'])->name('workdoc.notulensi-rapat.todos');
    Route::post('/notulensi-rapat/{id}/todos', [App\Http\Controllers\Workdoc\NotulensiRapatController::class, 'storeTodo'])->name('workdoc.notulensi-rapat.todos.store');
    Route::delete('/notulensi-rapat/{notulensiId}/todos/{todoId}', [App\Http\Controllers\Workdoc\NotulensiRapatController::class, 'deleteTodo'])->name('workdoc.notulensi-rapat.todos.delete');
    Route::post('/notulensi-rapat/{notulensiId}/todos/{todoId}/approve', [App\Http\Controllers\Workdoc\NotulensiRapatController::class, 'approveTodo'])->name('workdoc.notulensi-rapat.todos.approve');
});


Route::prefix('akreditasi')->middleware('role:Hrd|Manager|Employee|Admin')->group(function () {
    // BAB CRUD
    Route::get('/bab', [AkreditasiController::class, 'index'])->name('akreditasi.index');
    Route::post('/bab', [AkreditasiController::class, 'storeBab'])->name('akreditasi.bab.store');
    Route::put('/bab/{bab}', [AkreditasiController::class, 'updateBab'])->name('akreditasi.bab.update');
    Route::delete('/bab/{bab}', [AkreditasiController::class, 'destroyBab'])->name('akreditasi.bab.destroy');

    // Standar CRUD
    Route::get('/bab/{bab}/standars', [AkreditasiController::class, 'standars'])->name('akreditasi.standars');
    Route::post('/bab/{bab}/standar', [AkreditasiController::class, 'storeStandar'])->name('akreditasi.standar.store');
    Route::put('/standar/{standar}', [AkreditasiController::class, 'updateStandar'])->name('akreditasi.standar.update');
    Route::delete('/standar/{standar}', [AkreditasiController::class, 'destroyStandar'])->name('akreditasi.standar.destroy');

    // EP CRUD
    Route::get('/standar/{standar}/eps', [AkreditasiController::class, 'eps'])->name('akreditasi.eps');
    Route::post('/standar/{standar}/ep', [AkreditasiController::class, 'storeEp'])->name('akreditasi.ep.store');
    Route::put('/ep/{ep}', [AkreditasiController::class, 'updateEp'])->name('akreditasi.ep.update');
    Route::delete('/ep/{ep}', [AkreditasiController::class, 'destroyEp'])->name('akreditasi.ep.destroy');

    // EP Detail & Document CRUD
    Route::get('/ep/{ep}', [AkreditasiController::class, 'showEp'])->name('akreditasi.ep');
    Route::post('/ep/{ep}/document', [AkreditasiController::class, 'uploadDocument'])->name('akreditasi.ep.document.upload');
    Route::delete('/document/{document}', [AkreditasiController::class, 'destroyDocument'])->name('akreditasi.document.destroy');

    // Standar detail with all EPs as tabs
    Route::get('/standar/{standar}', [AkreditasiController::class, 'showStandar'])->name('akreditasi.standar.detail');
});


Route::prefix('finance')->middleware('role:Kasir|Admin')->group(function () {
        Route::get('/billing', [BillingController::class, 'index'])->name('finance.billing.index');
        Route::get('/billing/create/{visitation_id}', [BillingController::class, 'create'])->name('finance.billing.create');
        Route::post('/billing/save', [BillingController::class, 'saveBilling'])->name('finance.billing.save');
        Route::post('/billing/create-invoice', [BillingController::class, 'createInvoice'])->name('finance.billing.createInvoice');


        Route::post('/billing', [BillingController::class, 'store'])->name('finance.billing.store');
        Route::get('/billing/{id}/edit', [BillingController::class, 'edit'])->name('finance.billing.edit');
        Route::put('/billing/{id}', [BillingController::class, 'update'])->name('finance.billing.update');
        Route::delete('/billing/{id}', [BillingController::class, 'destroy'])->name('finance.billing.destroy');
        Route::get('/billing/data', [BillingController::class, 'getVisitationsData'])->name('finance.billing.data');
        Route::post('/billing/save', [BillingController::class, 'saveBilling'])->name('finance.billing.save');
        Route::post('/billing/create-invoice', [BillingController::class, 'createInvoice'])->name('finance.billing.createInvoice');
        Route::get('/billing/filters', [BillingController::class, 'filters'])->name('finance.billing.filters');
        Route::get('/billing/gudang-data', [BillingController::class, 'getGudangData'])->name('finance.billing.gudang-data');

        // Invoice routes
        // Route::post('/billing/create-invoice', [BillingController::class, 'createInvoice'])->name('billing.createInvoice');
        Route::get('/invoice', [InvoiceController::class, 'index'])->name('finance.invoice.index');
        Route::get('/invoice/{id}', [InvoiceController::class, 'show'])->name('finance.invoice.show');
        Route::put('/invoice/{id}/status', [InvoiceController::class, 'updateStatus'])->name('finance.invoice.updateStatus');
        Route::get('/invoice/{id}/print', [InvoiceController::class, 'printInvoice'])->name('finance.invoice.print');
        Route::get('/invoice/{id}/print-nota', [InvoiceController::class, 'printNota'])->name('finance.invoice.print-nota');
        Route::get('/invoice/{id}/print-nota-v2', [InvoiceController::class, 'printNotaV2'])->name('finance.invoice.print-nota-v2');
        // Rekap Penjualan
        Route::get('/rekap-penjualan', [BillingController::class, 'rekapPenjualanForm'])->name('finance.rekap-penjualan.form');
        Route::get('/rekap-penjualan/download', [BillingController::class, 'downloadRekapPenjualanExcel'])->name('finance.rekap-penjualan.download');
        // Invoice Excel Export
        Route::get('/invoice-export', [InvoiceController::class, 'invoiceExportForm'])->name('finance.invoice.export.form');
        Route::get('/invoice-export/download', [InvoiceController::class, 'downloadInvoiceExcel'])->name('finance.invoice.export.download');
        Route::get('/rekap-penjualan/statistik', [BillingController::class, 'statistikPendapatanAjax'])->name('finance.rekap-penjualan.statistik');
    }
);

Route::prefix('inventory')->middleware('role:Admin|Inventaris')->group(function () {
        Route::get('/', [App\Http\Controllers\InventoryDashboardController::class, 'index'])->name('inventory.dashboard');
        
        // Existing Item routes
        Route::get('/item', [ItemController::class, 'index'])->name('inventory.item.index');
        Route::get('/item/create', [ItemController::class, 'create'])->name('inventory.item.create');
        Route::post('/item', [ItemController::class, 'store'])->name('inventory.item.store');
        Route::get('/item/{id}/edit', [ItemController::class, 'edit'])->name('inventory.item.edit');
        Route::put('/item/{id}', [ItemController::class, 'update'])->name('inventory.item.update');
        Route::delete('/item/{id}', [ItemController::class, 'destroy'])->name('inventory.item.destroy');
        
        // Master Inventory
        Route::resource('gedung', App\Http\Controllers\Inventory\GedungController::class);
        Route::resource('ruangan', App\Http\Controllers\Inventory\RuanganController::class);
        Route::resource('tipe-barang', App\Http\Controllers\Inventory\TipeBarangController::class);
        
        // Manajemen Barang
        Route::resource('barang', App\Http\Controllers\Inventory\BarangController::class);
        Route::post('barang/update-stok', [App\Http\Controllers\Inventory\BarangController::class, 'updateStok'])->name('inventory.barang.update-stok');
        
        // Pembelian Barang
        Route::resource('pembelian', App\Http\Controllers\Inventory\PembelianBarangController::class);
        
        // Maintenance Barang
        Route::resource('maintenance', App\Http\Controllers\Inventory\MaintenanceBarangController::class);
    }
);

Route::prefix('hrd')->middleware('role:Hrd|Manager|Employee|Admin|Ceo')->group(function () {
    Route::get('/dokter-schedule/print', [\App\Http\Controllers\HRD\DokterSchedulePrintController::class, 'print'])->name('hrd.dokter-schedule.print');
    // Jadwal Karyawan Print (Employee)
    Route::get('/schedule/print', [\App\Http\Controllers\HRD\EmployeeScheduleController::class, 'print'])->name('hrd.schedule.print');
    Route::post('/dokter-schedule/delete/{id}', [\App\Http\Controllers\HRD\DokterScheduleController::class, 'deleteJadwal']);

     // Jadwal Karyawan
     // ...existing code...
    Route::get('/schedule/print', [\App\Http\Controllers\HRD\EmployeeScheduleController::class, 'print'])->name('hrd.schedule.print');
    Route::get('schedule', [\App\Http\Controllers\HRD\EmployeeScheduleController::class, 'index'])->name('hrd.schedule.index');
    Route::post('schedule', [\App\Http\Controllers\HRD\EmployeeScheduleController::class, 'store'])->name('hrd.schedule.store');
    Route::post('schedule/delete', [\App\Http\Controllers\HRD\EmployeeScheduleController::class, 'delete'])->name('hrd.schedule.delete');
        Route::post('/dokter-schedule/update-jam/{id}', [\App\Http\Controllers\HRD\DokterScheduleController::class, 'updateJam']);
    // Jadwal Dokter
    Route::get('dokter-schedule', [\App\Http\Controllers\HRD\DokterScheduleController::class, 'index'])->name('hrd.dokter-schedule.index');
    Route::get('dokter-schedule/get', [\App\Http\Controllers\HRD\DokterScheduleController::class, 'getSchedules'])->name('hrd.dokter-schedule.get');
    Route::post('dokter-schedule/store', [\App\Http\Controllers\HRD\DokterScheduleController::class, 'store'])->name('hrd.dokter-schedule.store');
        // HRD Absensi Rekap routes

    // Absensi Rekap routes (no double prefix)
    Route::get('absensi-rekap', [\App\Http\Controllers\HRD\AbsensiRekapController::class, 'index'])->name('hrd.absensi_rekap.index');
    Route::post('absensi-rekap/upload', [\App\Http\Controllers\HRD\AbsensiRekapController::class, 'upload'])->name('hrd.absensi_rekap.upload');
    Route::get('absensi-rekap/data', [\App\Http\Controllers\HRD\AbsensiRekapController::class, 'data'])->name('hrd.absensi_rekap.data');
    Route::get('absensi-rekap/statistics', [\App\Http\Controllers\HRD\AbsensiRekapController::class, 'statistics'])->name('hrd.absensi_rekap.statistics');
    Route::post('absensi-rekap/{id}/update', [\App\Http\Controllers\HRD\AbsensiRekapController::class, 'update'])->name('hrd.absensi_rekap.update');
    Route::get('absensi-rekap/sync-shifts', [\App\Http\Controllers\HRD\AbsensiRekapController::class, 'syncShiftData'])->name('hrd.absensi_rekap.sync_shifts');
    Route::get('absensi-rekap/debug-shifts', [\App\Http\Controllers\HRD\AbsensiRekapController::class, 'debugShiftData'])->name('hrd.absensi_rekap.debug_shifts');
    Route::get('absensi-rekap/test-cross-date', [\App\Http\Controllers\HRD\AbsensiRekapController::class, 'testCrossDateSelection'])->name('hrd.absensi_rekap.test_cross_date');

        // AJAX: Get schedule for employee and date
        Route::get('absensi-rekap/schedule', [\App\Http\Controllers\HRD\AbsensiRekapController::class, 'getSchedule']);
        // Store new absensi
        Route::post('absensi-rekap', [\App\Http\Controllers\HRD\AbsensiRekapController::class, 'store']);
        Route::post('absensi-rekap/submit-lateness-recap', [\App\Http\Controllers\HRD\AbsensiRekapController::class, 'submitLatenessRecap']);
        Route::get('lembur', [\App\Http\Controllers\HRD\PengajuanLemburController::class, 'index'])->name('hrd.lembur.index');
        Route::get('lembur/create', [\App\Http\Controllers\HRD\PengajuanLemburController::class, 'create'])->name('hrd.lembur.create');
        Route::post('lembur', [\App\Http\Controllers\HRD\PengajuanLemburController::class, 'store'])->name('hrd.lembur.store');
        Route::get('lembur/{id}', [\App\Http\Controllers\HRD\PengajuanLemburController::class, 'show'])->name('hrd.lembur.show');
        Route::get('lembur/{id}/approval-status', [\App\Http\Controllers\HRD\PengajuanLemburController::class, 'getApprovalStatus']);
        Route::post('lembur/{id}/persetujuan-manager', [\App\Http\Controllers\HRD\PengajuanLemburController::class, 'persetujuanManager'])->name('hrd.lembur.persetujuan-manager');
        Route::post('lembur/{id}/persetujuan-hrd', [\App\Http\Controllers\HRD\PengajuanLemburController::class, 'persetujuanHRD'])->name('hrd.lembur.persetujuan-hrd');
        // Catatan Dosa routes
        Route::get('catatan-dosa', [\App\Http\Controllers\HRD\CatatanDosaController::class, 'index'])->name('hrd.catatan-dosa.index');
        Route::get('catatan-dosa/{id}', [\App\Http\Controllers\HRD\CatatanDosaController::class, 'show'])->name('hrd.catatan-dosa.show');
        Route::post('catatan-dosa', [\App\Http\Controllers\HRD\CatatanDosaController::class, 'store'])->name('hrd.catatan-dosa.store');
        Route::post('catatan-dosa/{id}', [\App\Http\Controllers\HRD\CatatanDosaController::class, 'update'])->name('hrd.catatan-dosa.update');
        Route::delete('catatan-dosa/{id}', [\App\Http\Controllers\HRD\CatatanDosaController::class, 'destroy'])->name('hrd.catatan-dosa.destroy');
        // Pengajuan Tidak Masuk (Sakit/Izin)
        Route::get('tidakmasuk', [\App\Http\Controllers\HRD\PengajuanTidakMasukController::class, 'index'])->name('hrd.tidakmasuk.index');
        Route::get('tidakmasuk/create', [\App\Http\Controllers\HRD\PengajuanTidakMasukController::class, 'create'])->name('hrd.tidakmasuk.create');
        Route::post('tidakmasuk', [\App\Http\Controllers\HRD\PengajuanTidakMasukController::class, 'store'])->name('hrd.tidakmasuk.store');
        Route::get('tidakmasuk/{id}', [\App\Http\Controllers\HRD\PengajuanTidakMasukController::class, 'show'])->name('hrd.tidakmasuk.show');
        Route::get('tidakmasuk/{id}/approval-status', [\App\Http\Controllers\HRD\PengajuanTidakMasukController::class, 'getApprovalStatus']);
        Route::put('tidakmasuk/{id}/manager', [\App\Http\Controllers\HRD\PengajuanTidakMasukController::class, 'persetujuanManager']);
        Route::put('tidakmasuk/{id}/hrd', [\App\Http\Controllers\HRD\PengajuanTidakMasukController::class, 'persetujuanHRD']);
        
        // Pengajuan Ganti Shift
        Route::get('gantishift/available-shifts', [\App\Http\Controllers\HRD\PengajuanGantiShiftController::class, 'getAvailableShifts'])->name('hrd.gantishift.available-shifts');
        Route::get('gantishift', [\App\Http\Controllers\HRD\PengajuanGantiShiftController::class, 'index'])->name('hrd.gantishift.index');
        Route::get('gantishift/create', [\App\Http\Controllers\HRD\PengajuanGantiShiftController::class, 'create'])->name('hrd.gantishift.create');
        Route::post('gantishift', [\App\Http\Controllers\HRD\PengajuanGantiShiftController::class, 'store'])->name('hrd.gantishift.store');
        Route::get('gantishift/{id}', [\App\Http\Controllers\HRD\PengajuanGantiShiftController::class, 'show'])->name('hrd.gantishift.show');
        Route::get('gantishift/{id}/approval-status', [\App\Http\Controllers\HRD\PengajuanGantiShiftController::class, 'getApprovalStatus']);
        Route::put('gantishift/{id}/manager', [\App\Http\Controllers\HRD\PengajuanGantiShiftController::class, 'persetujuanManager'])->name('hrd.gantishift.manager');
        Route::put('gantishift/{id}/hrd', [\App\Http\Controllers\HRD\PengajuanGantiShiftController::class, 'persetujuanHRD'])->name('hrd.gantishift.hrd');
        
        // Master Data Routes
        // Division Management
        Route::prefix('master/division')->name('hrd.master.division.')->group(function () {
            Route::get('/', [App\Http\Controllers\HRD\DivisionMasterController::class, 'index'])->name('index');
            Route::get('/data', [App\Http\Controllers\HRD\DivisionMasterController::class, 'getData'])->name('data');
            Route::post('/', [App\Http\Controllers\HRD\DivisionMasterController::class, 'store'])->name('store');
            Route::get('/{id}', [App\Http\Controllers\HRD\DivisionMasterController::class, 'show'])->name('show');
            Route::put('/{id}', [App\Http\Controllers\HRD\DivisionMasterController::class, 'update'])->name('update');
            Route::delete('/{id}', [App\Http\Controllers\HRD\DivisionMasterController::class, 'destroy'])->name('destroy');
        });

        // Position Management
        Route::prefix('master/position')->name('hrd.master.position.')->group(function () {
            Route::get('/', [App\Http\Controllers\HRD\PositionMasterController::class, 'index'])->name('index');
            Route::get('/data', [App\Http\Controllers\HRD\PositionMasterController::class, 'getData'])->name('data');
            Route::post('/', [App\Http\Controllers\HRD\PositionMasterController::class, 'store'])->name('store');
            Route::get('/{id}', [App\Http\Controllers\HRD\PositionMasterController::class, 'show'])->name('show');
            Route::put('/{id}', [App\Http\Controllers\HRD\PositionMasterController::class, 'update'])->name('update');
            Route::delete('/{id}', [App\Http\Controllers\HRD\PositionMasterController::class, 'destroy'])->name('destroy');
        });

        // Jatah Libur Management
        Route::prefix('master/jatah-libur')->name('hrd.master.jatah-libur.')->group(function () {
            Route::get('/', [App\Http\Controllers\HRD\JatahLiburController::class, 'index'])->name('index');
            Route::get('/data', [App\Http\Controllers\HRD\JatahLiburController::class, 'getData'])->name('data');
            Route::get('/employees-without-jatah-libur', [App\Http\Controllers\HRD\JatahLiburController::class, 'getEmployeesWithoutJatahLibur'])->name('employees-without-jatah-libur');
            Route::post('/', [App\Http\Controllers\HRD\JatahLiburController::class, 'store'])->name('store');
            Route::get('/{id}', [App\Http\Controllers\HRD\JatahLiburController::class, 'show'])->name('show');
            Route::put('/{id}', [App\Http\Controllers\HRD\JatahLiburController::class, 'update'])->name('update');
        });

        // Employee Management Routes
        // Add these routes if they're missing
        Route::get('/employee', [EmployeeController::class, 'index'])->name('hrd.employee.index');
        Route::get('/employee/create', [EmployeeController::class, 'create'])->name('hrd.employee.create');
        Route::post('/employee', [EmployeeController::class, 'store'])->name('hrd.employee.store');
        Route::get('/employee/{id}', [EmployeeController::class, 'show'])->name('hrd.employee.show');
        Route::get('/employee/{id}/get-details', [EmployeeController::class, 'getDetails'])->name('hrd.employee.get-details');
        Route::get('/employee/{id}/edit', [EmployeeController::class, 'edit'])->name('hrd.employee.edit');
        Route::put('/employee/{id}', [EmployeeController::class, 'update'])->name('hrd.employee.update');
        Route::delete('/employee/{id}', [EmployeeController::class, 'destroy'])->name('hrd.employee.destroy');

        // Dokter Management (HRD access, full CRUD)
        Route::get('/dokters', [\App\Http\Controllers\ERM\DokterController::class, 'index'])->name('hrd.dokters.index');
        Route::get('/dokters/create', [\App\Http\Controllers\ERM\DokterController::class, 'create'])->name('hrd.dokters.create');
        Route::get('/dokters/{id}/edit', [\App\Http\Controllers\ERM\DokterController::class, 'edit'])->name('hrd.dokters.edit');
        Route::post('/dokters', [\App\Http\Controllers\ERM\DokterController::class, 'store'])->name('hrd.dokters.store');
        Route::put('/dokters/{id}', [\App\Http\Controllers\ERM\DokterController::class, 'update'])->name('hrd.dokters.update');
        Route::delete('/dokters/{id}', [\App\Http\Controllers\ERM\DokterController::class, 'destroy'])->name('hrd.dokters.destroy');

        // Employee Self Service Routes

        Route::get('/profile', [EmployeeSelfServiceController::class, 'profile'])->name('hrd.employee.profile');
        Route::get('/profile/edit-modal', [EmployeeSelfServiceController::class, 'getEditProfileModal'])->name('hrd.employee.profile.modal');
        Route::put('/profile', [EmployeeSelfServiceController::class, 'updateProfile'])->name('hrd.employee.profile.update');

        Route::put('/profile/password', [EmployeeSelfServiceController::class, 'updatePassword'])->name('hrd.employee.password.update');

        // Employee Contract Routes
        Route::get('/employee/{employeeId}/contracts', [EmployeeContractController::class, 'index'])->name('hrd.employee.contracts.index');
        Route::get('/employee/{employeeId}/contracts/create', [EmployeeContractController::class, 'create'])->name('hrd.employee.contracts.create');
        Route::post('/employee/{employeeId}/contracts', [EmployeeContractController::class, 'store'])->name('hrd.employee.contracts.store');
        Route::get('/employee/{employeeId}/contracts/{contractId}', [EmployeeContractController::class, 'show'])->name('hrd.employee.contracts.show');
        Route::post('/employee/{employeeId}/contracts/{contractId}/terminate', [EmployeeContractController::class, 'terminate'])->name('hrd.employee.contracts.terminate');
        // AJAX routes for modals
        Route::get('/employee/{employeeId}/contracts/modal/create', [EmployeeContractController::class, 'getCreateModal'])->name('hrd.employee.contracts.modal.create');
        Route::get('/employee/{employeeId}/contracts/{contractId}/modal', [EmployeeContractController::class, 'getShowModal'])->name('hrd.employee.contracts.modal.show');
        
        // Division routes (for managers)
        Route::get('/my-division', [DivisionController::class, 'showMyDivision'])->name('hrd.division.mine');
        Route::get('/my-team', [DivisionController::class, 'showMyTeam'])->name('hrd.division.team');

        Route::prefix('libur')->name('hrd.libur.')->middleware(['auth'])->group(function () {
                Route::get('/', [PengajuanLiburController::class, 'index'])->name('index');
                Route::get('/buat', [PengajuanLiburController::class, 'create'])->name('create');
                Route::post('/', [PengajuanLiburController::class, 'store'])->name('store');
                Route::get('/{id}', [PengajuanLiburController::class, 'show'])->name('show');
                Route::get('/{id}/approval-status', [PengajuanLiburController::class, 'getApprovalStatus'])->name('approval.status');
                Route::put('/{id}/manager', [PengajuanLiburController::class, 'persetujuanManager'])->name('manager.approve');
                Route::put('/{id}/hrd', [PengajuanLiburController::class, 'persetujuanHRD'])->name('hrd.approve');
            });

        // Performance Evaluation Routes
        Route::prefix('performance')->name('hrd.performance.')->middleware(['auth'])->group(function () {
            // Evaluation Periods
            Route::get('/periods', [PerformanceEvaluationController::class, 'index'])->name('periods.index');
            Route::post('/periods', [PerformanceEvaluationController::class, 'store'])->name('periods.store');
            Route::get('/periods/{period}', [PerformanceEvaluationController::class, 'show'])->name('periods.show');
            Route::put('/periods/{period}', [PerformanceEvaluationController::class, 'update'])->name('periods.update');
            Route::delete('/periods/{period}', [PerformanceEvaluationController::class, 'destroy'])->name('periods.destroy');
            Route::post('/periods/{period}/initiate', [PerformanceEvaluationController::class, 'initiate'])->name('periods.initiate');

            // Questions & Categories with AJAX
            Route::get('/questions', [PerformanceQuestionController::class, 'index'])->name('questions.index');
            
            // Categories AJAX routes
            Route::get('/questions/categories/data', [PerformanceQuestionController::class, 'getCategories'])->name('categories.data');
            Route::get('/questions/categories/active', [PerformanceQuestionController::class, 'getActiveCategories'])->name('categories.active');
            Route::get('/questions/categories/{id}', [PerformanceQuestionController::class, 'getCategoryById'])->name('categories.get');
            Route::post('/questions/categories', [PerformanceQuestionController::class, 'storeCategory'])->name('categories.store');
            Route::put('/questions/categories/{id}', [PerformanceQuestionController::class, 'updateCategory'])->name('categories.update');
            Route::delete('/questions/categories/{id}', [PerformanceQuestionController::class, 'destroyCategory'])->name('categories.destroy');
            
            // Questions AJAX routes
            Route::get('/questions/data', [PerformanceQuestionController::class, 'getQuestions'])->name('questions.data');
            Route::get('/questions/grouped', [PerformanceQuestionController::class, 'getGroupedQuestions'])->name('questions.grouped');
            Route::get('/questions/all', [PerformanceQuestionController::class, 'getAllQuestions'])->name('questions.getAll');
            Route::get('/questions/{id}', [PerformanceQuestionController::class, 'getQuestionById'])->name('questions.get');
            Route::post('/questions', [PerformanceQuestionController::class, 'storeQuestion'])->name('questions.store');
            Route::put('/questions/{id}', [PerformanceQuestionController::class, 'updateQuestion'])->name('questions.update');
            Route::delete('/questions/{id}', [PerformanceQuestionController::class, 'destroyQuestion'])->name('questions.destroy');

            // My Evaluations
            Route::get('/my-evaluations', [PerformanceEvaluationController::class, 'myEvaluations'])->name('my-evaluations');
            Route::get('/evaluations/{evaluation}/fill', [PerformanceEvaluationController::class, 'fillEvaluation'])->name('evaluations.fill');
            Route::post('/evaluations/{evaluation}/submit', [PerformanceEvaluationController::class, 'submitEvaluation'])->name('evaluations.submit');

            // Results (HRD only)
            Route::get('/results', [PerformanceEvaluationController::class, 'results'])->name('results.index');
            Route::get('/results/data', [PerformanceEvaluationController::class, 'resultsData'])->name('results.data');
            Route::get('/results/periods/{period}', [PerformanceEvaluationController::class, 'periodResults'])->name('results.period');
            Route::get('/results/periods/{period}/data', [PerformanceEvaluationController::class, 'periodResultsData'])->name('results.period.data');
            Route::get('/results/periods/{period}/employees/{employee}', [PerformanceEvaluationController::class, 'employeeResults'])->name('results.employee');
            Route::get('/results/periods/{period}/download-score', [PerformanceEvaluationController::class, 'downloadScore'])->name('results.download-score');
        });
    }
);

Route::prefix('marketing')->middleware('role:Marketing|Admin')->group(function () {
    // Galeri Before After gallery for tindakan
Route::get('/tindakan/{id}/galeri-before-after', [\App\Http\Controllers\Marketing\TindakanController::class, 'galeriBeforeAfter']);
    // Main dashboard and analytics
    Route::get('/', [MarketingController::class, 'dashboard'])->name('marketing.dashboard');
    Route::get('/dashboard', [MarketingController::class, 'dashboard'])->name('marketing.dashboard');
    
    // Analytics pages  
    Route::get('/revenue', [MarketingController::class, 'revenue'])->name('marketing.revenue');
    Route::get('/patients', [MarketingController::class, 'patients'])->name('marketing.patients');
    Route::get('/services', [MarketingController::class, 'services'])->name('marketing.services');
    Route::get('/products', [MarketingController::class, 'products'])->name('marketing.products');
    Route::get('/clinic-comparison', [MarketingController::class, 'clinicComparison'])->name('marketing.clinic-comparison');

    // Patient data management
    Route::get('/pasien-data', [MarketingController::class, 'pasienData'])->name('marketing.pasien.data');
    Route::get('pasien/{pasien}/riwayat-rm', [MarketingController::class, 'riwayatRM']);
    
    // Add pasien to follow up from pasien data
    Route::post('/followup/add-from-pasien', [\App\Http\Controllers\Marketing\FollowUpController::class, 'addFromPasien'])->name('marketing.followup.add-from-pasien');
    // AJAX search for SOPs
    Route::get('/sop/search', [App\Http\Controllers\Marketing\TindakanController::class, 'searchSop']);
    
    // Tindakan Management
    Route::get('/tindakan', [App\Http\Controllers\Marketing\TindakanController::class, 'index'])->name('marketing.tindakan.index');
    Route::get('/tindakan/data', [App\Http\Controllers\Marketing\TindakanController::class, 'getTindakanData'])->name('marketing.tindakan.data');
    Route::get('/tindakan/list', [App\Http\Controllers\Marketing\TindakanController::class, 'getTindakanList'])->name('marketing.tindakan.list');
    Route::post('/tindakan', [App\Http\Controllers\Marketing\TindakanController::class, 'store'])->name('marketing.tindakan.store');
    Route::get('/tindakan/{id}', [App\Http\Controllers\Marketing\TindakanController::class, 'getTindakan']);
    Route::delete('/tindakan/{id}', [App\Http\Controllers\Marketing\TindakanController::class, 'destroy']);

    // SOP Tindakan endpoints for modal
    Route::get('/tindakan/{id}/sop', [App\Http\Controllers\Marketing\TindakanController::class, 'getSopTindakan']);
    Route::post('/tindakan/{id}/sop', [App\Http\Controllers\Marketing\TindakanController::class, 'updateSopTindakan']);
    
    // Get list of specialists (for dropdown)
    Route::get('/spesialisasi/list', [App\Http\Controllers\Marketing\TindakanController::class, 'getSpesialisasiList'])->name('marketing.spesialisasi.list');
    
    // Paket Tindakan Management
    Route::get('/paket-tindakan', [App\Http\Controllers\Marketing\TindakanController::class, 'indexPaket'])->name('marketing.tindakan.paket.index');
    Route::get('/tindakan/paket/data', [App\Http\Controllers\Marketing\TindakanController::class, 'getPaketData'])->name('marketing.tindakan.paket.data');
    Route::post('/tindakan/paket', [App\Http\Controllers\Marketing\TindakanController::class, 'storePaket'])->name('marketing.tindakan.paket.store');
    Route::get('/tindakan/paket/{id}', [App\Http\Controllers\Marketing\TindakanController::class, 'getPaket']);
    Route::delete('/tindakan/paket/{id}', [App\Http\Controllers\Marketing\TindakanController::class, 'destroyPaket']);

    Route::get('/pasien-data', [App\Http\Controllers\Marketing\MarketingController::class, 'pasienData'])->name('marketing.pasien-data');

    // Survey Question Management
    Route::get('survey-questions', [\App\Http\Controllers\Marketing\SurveyQuestionController::class, 'index']);
    Route::get('survey-questions/datatable', [\App\Http\Controllers\Marketing\SurveyQuestionController::class, 'datatable']);
    Route::get('survey-questions/{id}', function($id) {
        $q = \App\Models\Survey\SurveyQuestion::findOrFail($id);
        return response()->json(['data' => $q]);
    });
    Route::post('survey-questions', [\App\Http\Controllers\Marketing\SurveyQuestionController::class, 'store']);
    Route::put('survey-questions/{id}', [\App\Http\Controllers\Marketing\SurveyQuestionController::class, 'update']);
    Route::delete('survey-questions/{id}', [\App\Http\Controllers\Marketing\SurveyQuestionController::class, 'destroy']);

    // Content Plan Management
    Route::get('content-plan', [\App\Http\Controllers\Marketing\ContentPlanController::class, 'index'])->name('marketing.content-plan.index');
    Route::get('content-plan/{id}', [\App\Http\Controllers\Marketing\ContentPlanController::class, 'show'])->name('marketing.content-plan.show');
    Route::post('content-plan', [\App\Http\Controllers\Marketing\ContentPlanController::class, 'store'])->name('marketing.content-plan.store');
    Route::put('content-plan/{id}', [\App\Http\Controllers\Marketing\ContentPlanController::class, 'update'])->name('marketing.content-plan.update');
    Route::delete('content-plan/{id}', [\App\Http\Controllers\Marketing\ContentPlanController::class, 'destroy'])->name('marketing.content-plan.destroy');

    // Catatan Keluhan Customer
    Route::get('catatan-keluhan', [\App\Http\Controllers\Marketing\CatatanKeluhanController::class, 'index'])->name('marketing.catatan-keluhan.index');
    Route::post('catatan-keluhan', [\App\Http\Controllers\Marketing\CatatanKeluhanController::class, 'store'])->name('marketing.catatan-keluhan.store');
    Route::get('catatan-keluhan/{id}', [\App\Http\Controllers\Marketing\CatatanKeluhanController::class, 'show'])->name('marketing.catatan-keluhan.show');
    Route::put('catatan-keluhan/{id}', [\App\Http\Controllers\Marketing\CatatanKeluhanController::class, 'update'])->name('marketing.catatan-keluhan.update');
    Route::delete('catatan-keluhan/{id}', [\App\Http\Controllers\Marketing\CatatanKeluhanController::class, 'destroy'])->name('marketing.catatan-keluhan.destroy');
    Route::get('catatan-keluhan/{id}/print', [\App\Http\Controllers\Marketing\CatatanKeluhanController::class, 'print'])->name('marketing.catatan-keluhan.print');
    // AJAX pasien search for select2
    Route::get('catatan-keluhan-pasien-search', [\App\Http\Controllers\Marketing\CatatanKeluhanController::class, 'pasienSearch'])->name('marketing.catatan-keluhan.pasien-search');

    // Follow Up Customer routes
    Route::get('followup/pasien-search', [\App\Http\Controllers\Marketing\FollowUpController::class, 'pasienSearch'])->name('marketing.followup.pasien-search');
    Route::get('followup', [\App\Http\Controllers\Marketing\FollowUpController::class, 'index'])->name('marketing.followup.index');
    Route::post('followup', [\App\Http\Controllers\Marketing\FollowUpController::class, 'store'])->name('marketing.followup.store');
    Route::get('followup/count-today', [\App\Http\Controllers\Marketing\FollowUpController::class, 'countToday'])->name('marketing.followup.count-today');
    Route::get('followup/{id}', [\App\Http\Controllers\Marketing\FollowUpController::class, 'show'])->name('marketing.followup.show');
    Route::put('followup/{id}', [\App\Http\Controllers\Marketing\FollowUpController::class, 'update'])->name('marketing.followup.update');
    Route::delete('followup/{id}', [\App\Http\Controllers\Marketing\FollowUpController::class, 'destroy'])->name('marketing.followup.destroy');
    // Route::get('followup/count-today', [\App\Http\Controllers\Marketing\FollowUpController::class, 'countToday'])->name('marketing.followup.count-today');
    // AJAX: Riwayat RM by pasien
    Route::get('pasien/{pasien}/riwayat-rm', [\App\Http\Controllers\Marketing\MarketingController::class, 'riwayatRM']);
});


Route::prefix('insiden')->middleware('role:Hrd|Manager|Employee|Admin')->group(function () {

    Route::get('laporan_insiden/division-select2', [LaporanInsidenController::class, 'divisionSelect2'])->name('insiden.laporan_insiden.division-select2');
    Route::get('laporan_insiden', [LaporanInsidenController::class, 'index'])->name('insiden.laporan_insiden.index');
    Route::get('laporan_insiden/data', [LaporanInsidenController::class, 'data'])->name('insiden.laporan_insiden.data');
    Route::get('laporan_insiden/create', [LaporanInsidenController::class, 'create'])->name('insiden.laporan_insiden.create');
    Route::post('laporan_insiden', [LaporanInsidenController::class, 'upsert'])->name('insiden.laporan_insiden.store');
    Route::get('laporan_insiden/{id}/edit', [LaporanInsidenController::class, 'edit'])->name('insiden.laporan_insiden.edit');
    Route::put('laporan_insiden/{id}', [LaporanInsidenController::class, 'upsert'])->name('insiden.laporan_insiden.update');
    // AJAX pasien search for Select2
    Route::get('laporan_insiden/pasien-search', [LaporanInsidenController::class, 'searchPasien'])->name('insiden.laporan_insiden.pasien-search');
    Route::delete('laporan_insiden/{id}', [LaporanInsidenController::class, 'destroy'])->name('insiden.laporan_insiden.destroy');
    Route::post('laporan_insiden/{id}/diterima', [LaporanInsidenController::class, 'diterima'])->name('insiden.laporan_insiden.diterima');
});
// AJAX route for patient analytics charts
Route::get('/marketing/patients-analytics-data', [\App\Http\Controllers\Marketing\MarketingController::class, 'patientsAnalyticsData'])->name('marketing.patients.analytics.data');

// AJAX route for services analytics charts
Route::get('/marketing/services-analytics-data', [\App\Http\Controllers\Marketing\MarketingController::class, 'servicesAnalyticsData'])->name('marketing.services.analytics.data');

// AJAX route for products analytics charts
Route::get('/marketing/products-analytics-data', [\App\Http\Controllers\Marketing\MarketingController::class, 'productsAnalyticsData'])->name('marketing.products.analytics.data');

// AJAX route for revenue analytics charts
Route::get('/marketing/revenue-analytics-data', [\App\Http\Controllers\Marketing\MarketingController::class, 'getRevenueData'])->name('marketing.revenue.analytics.data');

// AJAX route for patient analytics charts
Route::get('/marketing/analytics/patients-data', [\App\Http\Controllers\Marketing\MarketingController::class, 'patientsAnalyticsData'])->name('marketing.patients.analytics.data');

// AJAX route for clinics data
Route::get('/marketing/clinics', [\App\Http\Controllers\Marketing\MarketingController::class, 'getClinics'])->name('marketing.clinics');

Route::prefix('admin')->group(
    function () {
        //User Management
        Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('admin.users.create');
        Route::post('/users', [UserController::class, 'store'])->name('admin.users.store');
        Route::get('/users/{id}/edit', [UserController::class, 'edit'])->name('admin.users.edit');
        Route::put('/users/{id}', [UserController::class, 'update'])->name('admin.users.update');
        Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('admin.users.destroy');

        //Role Management
        Route::get('/roles', [RoleController::class, 'index'])->name('admin.roles.index');
        Route::post('/roles', [RoleController::class, 'store'])->name('admin.roles.store');
    }
);

// Get Master Data
Route::get('/get-regencies/{province_id}', [AddressController::class, 'getRegencies']);
Route::get('/get-districts/{regency_id}', [AddressController::class, 'getDistricts']);
Route::get('/get-villages/{district_id}', [AddressController::class, 'getVillages']);
Route::get('/address-form', [AddressController::class, 'index']);
Route::get('/icd10/search', [Icd10Controller::class, 'search'])->name('icd10.search');
Route::get('/obat/search', [ObatController::class, 'search'])->name('obat.search');
Route::get('/wadah/search', [EresepController::class, 'search'])->name('wadah.search');
Route::get('/keluhan-utama/search', [KeluhanUtamaController::class, 'search'])->name('keluhan-utama.search');
Route::get('/get-dokters/{klinik_id}', [VisitationController::class, 'getDoktersByKlinik'])->name('erm.get-dokters');
Route::get('/get-apotekers', [EresepController::class, 'getApotekers'])->name('erm.get-apotekers');
Route::get('/tindakan/search', [App\Http\Controllers\Marketing\TindakanController::class, 'searchTindakan'])->name('marketing.tindakan.search');
Route::get('/generate-missing-resep-details', [App\Http\Controllers\ERM\VisitationController::class, 'generateMissingResepDetails']);

// AJAX route for most frequent patient
Route::get('/erm/dashboard/most-frequent-patient', [\App\Http\Controllers\ERMDashboardAjaxController::class, 'mostFrequentPatient'])->name('erm.dashboard.most-frequent-patient');

// AJAX route for visitation count (dashboard box)
Route::get('/erm/dashboard/visitation-count', [App\Http\Controllers\ERMDashboardController::class, 'visitationCount'])->name('erm.dashboard.visitation-count');

// AJAX route for visitation detail modal
Route::get('/erm/dashboard/visitation-detail', [App\Http\Controllers\ERMDashboardController::class, 'visitationDetail'])->name('erm.dashboard.visitation-detail');
Route::get('/labtest/search', [\App\Http\Controllers\ERM\LabTestController::class, 'search'])->name('labtest.search');
Route::get('/konsultasi/search', [\App\Http\Controllers\ERM\KonsultasiController::class, 'search'])->name('konsultasi.search');

// AJAX: Get ruangan by gedung for filter (move to RuanganController for consistency)
Route::get('/inventory/ruangan/by-gedung/{gedungId}', [App\Http\Controllers\Inventory\RuanganController::class, 'getRuanganByGedung']);

Route::get('/api/hrd/employees', [App\Http\Controllers\HRD\EmployeeController::class, 'searchForSelect2']);

// Select2 AJAX for pemasok

// Select2 AJAX for pemasok
Route::get('/get-pemasok-select2', [\App\Http\Controllers\ERM\FakturBeliController::class, 'getPemasokSelect2'])->name('get-pemasok-select2');
// Select2 AJAX for obat
Route::get('/get-obat-select2', [\App\Http\Controllers\ERM\FakturBeliController::class, 'getObatSelect2'])->name('get-obat-select2');
// Select2 AJAX for gudang
Route::get('/get-gudang-select2', [\App\Http\Controllers\ERM\FakturBeliController::class, 'getGudangSelect2'])->name('get-gudang-select2');
    // AJAX spesialisasi select2
    Route::get('/erm/spesialisasi-select2', [\App\Http\Controllers\Insiden\LaporanInsidenController::class, 'spesialisasiSelect2'])->name('erm.spesialisasi.select2');
    // AJAX division select2 (unit penyebab)
    Route::get('/erm/division-select2', [\App\Http\Controllers\Insiden\LaporanInsidenController::class, 'divisionSelect2'])->name('erm.division.select2');

    // Payroll Master Routes
Route::prefix('hrd/payroll/master')->middleware(['auth', 'role:Hrd|Admin|Manager|Ceo'])->group(function () {
    Route::get('/gajipokok', [App\Http\Controllers\HRD\PayrollMasterController::class, 'datatableGajiPokok']);
    Route::post('/gajipokok', [App\Http\Controllers\HRD\PayrollMasterController::class, 'storeGajiPokok']);
    Route::put('/gajipokok/{id}', [App\Http\Controllers\HRD\PayrollMasterController::class, 'updateGajiPokok']);
    Route::delete('/gajipokok/{id}', [App\Http\Controllers\HRD\PayrollMasterController::class, 'destroyGajiPokok']);

    Route::get('/tunjangan-jabatan', [App\Http\Controllers\HRD\PayrollMasterController::class, 'datatableTunjanganJabatan']);
    Route::post('/tunjangan-jabatan', [App\Http\Controllers\HRD\PayrollMasterController::class, 'storeTunjanganJabatan']);
    Route::put('/tunjangan-jabatan/{id}', [App\Http\Controllers\HRD\PayrollMasterController::class, 'updateTunjanganJabatan']);
    Route::delete('/tunjangan-jabatan/{id}', [App\Http\Controllers\HRD\PayrollMasterController::class, 'destroyTunjanganJabatan']);

    Route::get('/tunjangan-lain', [App\Http\Controllers\HRD\PayrollMasterController::class, 'datatableTunjanganLain']);
    Route::post('/tunjangan-lain', [App\Http\Controllers\HRD\PayrollMasterController::class, 'storeTunjanganLain']);
    Route::put('/tunjangan-lain/{id}', [App\Http\Controllers\HRD\PayrollMasterController::class, 'updateTunjanganLain']);
    Route::delete('/tunjangan-lain/{id}', [App\Http\Controllers\HRD\PayrollMasterController::class, 'destroyTunjanganLain']);

    Route::get('/benefit', [App\Http\Controllers\HRD\PayrollMasterController::class, 'datatableBenefit']);
    Route::post('/benefit', [App\Http\Controllers\HRD\PayrollMasterController::class, 'storeBenefit']);
    Route::put('/benefit/{id}', [App\Http\Controllers\HRD\PayrollMasterController::class, 'updateBenefit']);
    Route::delete('/benefit/{id}', [App\Http\Controllers\HRD\PayrollMasterController::class, 'destroyBenefit']);

    Route::get('/potongan', [App\Http\Controllers\HRD\PayrollMasterController::class, 'datatablePotongan']);
    Route::post('/potongan', [App\Http\Controllers\HRD\PayrollMasterController::class, 'storePotongan']);
    Route::put('/potongan/{id}', [App\Http\Controllers\HRD\PayrollMasterController::class, 'updatePotongan']);
    Route::delete('/potongan/{id}', [App\Http\Controllers\HRD\PayrollMasterController::class, 'destroyPotongan']);

    Route::get('/', [App\Http\Controllers\HRD\PayrollMasterController::class, 'index'])->name('hrd.payroll.master.index');
});

// Payroll Insentif Omset Routes
Route::prefix('hrd/payroll/insentif-omset')->middleware(['auth', 'role:Hrd|Admin|Manager|Ceo'])->group(function () {
    Route::get('/', [App\Http\Controllers\HRD\PrInsentifOmsetController::class, 'index'])->name('hrd.payroll.insentif_omset.index');
    Route::get('/data', [App\Http\Controllers\HRD\PrInsentifOmsetController::class, 'data'])->name('hrd.payroll.insentif_omset.data');
    Route::post('/', [App\Http\Controllers\HRD\PrInsentifOmsetController::class, 'store'])->name('hrd.payroll.insentif_omset.store');
    Route::put('/{id}', [App\Http\Controllers\HRD\PrInsentifOmsetController::class, 'update'])->name('hrd.payroll.insentif_omset.update');
    Route::delete('/{id}', [App\Http\Controllers\HRD\PrInsentifOmsetController::class, 'destroy'])->name('hrd.payroll.insentif_omset.destroy');
});

// Payroll KPI Routes
Route::prefix('hrd/payroll/kpi')->middleware(['auth', 'role:Hrd|Admin|Manager|Ceo'])->group(function () {
    Route::get('/', [App\Http\Controllers\HRD\PrKpiController::class, 'index'])->name('hrd.payroll.kpi.index');
    Route::get('/data', [App\Http\Controllers\HRD\PrKpiController::class, 'data'])->name('hrd.payroll.kpi.data');
    Route::post('/', [App\Http\Controllers\HRD\PrKpiController::class, 'store'])->name('hrd.payroll.kpi.store');
    Route::put('/{id}', [App\Http\Controllers\HRD\PrKpiController::class, 'update'])->name('hrd.payroll.kpi.update');
    Route::delete('/{id}', [App\Http\Controllers\HRD\PrKpiController::class, 'destroy'])->name('hrd.payroll.kpi.destroy');
});

// Add personal slip gaji route before the admin routes
Route::match(['get', 'post'], 'hrd/payroll/slip-gaji/my-slip', [App\Http\Controllers\HRD\PrSlipGajiController::class, 'mySlip'])
    ->middleware(['auth'])
    ->name('hrd.payroll.slip_gaji.my_slip');

Route::get('hrd/payroll/slip-gaji/download/{id}', [App\Http\Controllers\HRD\PrSlipGajiController::class, 'downloadSlip'])
    ->middleware(['auth'])
    ->name('hrd.payroll.slip_gaji.download');

// Payroll Slip Gaji Routes
Route::prefix('hrd/payroll/slip-gaji')->middleware(['auth', 'role:Hrd|Admin|Manager|Ceo'])->group(function () {
    Route::get('/', [App\Http\Controllers\HRD\PrSlipGajiController::class, 'index'])->name('hrd.payroll.slip_gaji.index');
    Route::get('/data', [App\Http\Controllers\HRD\PrSlipGajiController::class, 'data'])->name('hrd.payroll.slip_gaji.data');
    Route::get('/detail/{id}', [App\Http\Controllers\HRD\PrSlipGajiController::class, 'detail'])->name('hrd.payroll.slip_gaji.detail');
    Route::put('/status/{id}', [App\Http\Controllers\HRD\PrSlipGajiController::class, 'changeStatus'])->name('hrd.payroll.slip_gaji.status');
    Route::post('/update/{id}', [App\Http\Controllers\HRD\PrSlipGajiController::class, 'update'])->name('hrd.payroll.slip_gaji.update');
    Route::get('/print/{id}', [\App\Http\Controllers\HRD\PrSlipGajiController::class, 'print']);
});

// Omset Bulanan AJAX for Slip Gaji
Route::get('hrd/payroll/slip-gaji/omset-bulanan', [App\Http\Controllers\HRD\PrSlipGajiController::class, 'getOmsetInputs']);
Route::post('hrd/payroll/slip-gaji/omset-bulanan', [App\Http\Controllers\HRD\PrSlipGajiController::class, 'store']);
Route::get('hrd/payroll/slip-gaji/omset-bulanan-total', [App\Http\Controllers\HRD\PrSlipGajiController::class, 'getTotal']);
Route::post('hrd/payroll/slip-gaji/store-all', [App\Http\Controllers\HRD\PrSlipGajiController::class, 'storeAll']);
// AJAX route for periode penilaian options for modal
Route::get('hrd/performance-evaluation-periods-for-month', [App\Http\Controllers\HRD\PerformanceEvaluationPeriodController::class, 'getPeriodsForMonth']);

// KPI summary route
Route::get('hrd/payroll/slip-gaji/kpi-summary', [\App\Http\Controllers\HRD\PrSlipGajiController::class, 'getKpiSummary']);
// Generate Uang KPI for all employees in selected month
Route::post('hrd/payroll/slip-gaji/generate-uang-kpi', [App\Http\Controllers\HRD\PrSlipGajiController::class, 'generateUangKpi'])->name('hrd.payroll.slip_gaji.generate_uang_kpi');
