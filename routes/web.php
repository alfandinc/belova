<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\{
    AuthController,
    ERMDashboardController,
    HRDDashboardController,
    InventoryDashboardController,
    MarketingDashboardController,
    FinanceDashboardController,
    WorkdocDashboardController,
    AkreditasiDashboardController
};
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
    SuratIstirahatController,
    SuratMondokController,
    ResepCatatanController,
    NotificationController
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

use App\Http\Controllers\Inventory\ItemController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\Marketing\MarketingController;

Route::get('/', function () {
    return view('mainmenu');
});

// Different login pages (GET requests only)
Route::get('/erm/login', [AuthController::class, 'showERMLoginForm'])->name('erm.login');
Route::get('/finance/login', [AuthController::class, 'showFinanceLoginForm'])->name('finance.login');
Route::get('/hrd/login', [AuthController::class, 'showHRDLoginForm'])->name('hrd.login');
Route::get('/inventory/login', [AuthController::class, 'showInventoryLoginForm'])->name('inventory.login');
Route::get('/marketing/login', [AuthController::class, 'showMarketingLoginForm'])->name('marketing.login');
Route::get('/workdoc/login', [AuthController::class, 'showWorkdocLoginForm'])->name('workdoc.login');
Route::get('/akreditasi/login', [AuthController::class, 'showAkreditasiLoginForm'])->name('akreditasi.login');

// Single POST route for login processing (all forms submit here)
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/erm', [ERMDashboardController::class, 'index'])->name('erm.dashboard');
    Route::get('/finance', [FinanceDashboardController::class, 'index'])->name('finance.dashboard');
    Route::get('/hrd', [HRDDashboardController::class, 'index'])->name('hrd.dashboard');
    Route::get('/inventory', [InventoryDashboardController::class, 'index'])->name('inventory.dashboard');
    Route::get('/marketing', [MarketingDashboardController::class, 'index'])->name('marketing.dashboard');
    Route::get('/workdoc', [WorkdocDashboardController::class, 'index'])->name('workdoc.dashboard');
    Route::get('/akreditasi', [AkreditasiDashboardController::class, 'index'])->name('akreditasi.dashboard');
});

Route::fallback(function () {
    if (!Auth::check()) {
        return redirect('/');
    }
});

// ERM Routes
Route::prefix('erm')->group(function () {
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

    // Dokter Management
    Route::get('/dokters', [DokterController::class, 'index'])->name('erm.dokters.index');
    Route::get('dokters/create', [DokterController::class, 'create'])->name('erm.dokters.create');
    Route::post('dokters', [DokterController::class, 'store'])->name('erm.dokters.store');
    Route::get('/dokters/{id}/edit', [DokterController::class, 'edit'])->name('erm.dokters.edit');
    Route::put('/dokters/{id}', [DokterController::class, 'update'])->name('erm.dokters.update');
    Route::delete('/dokters/{id}', [DokterController::class, 'destroy'])->name('erm.dokters.destroy');

    //Visitation
    Route::get('/pasiens', [PasienController::class, 'index'])->name('erm.pasiens.index');
    Route::post('/visitations', [VisitationController::class, 'store'])->name('erm.visitations.store');
    Route::post('/visitations/produk', [VisitationController::class, 'storeProduk'])->name('erm.visitations.produk.store');
    Route::post('/visitations/lab', [VisitationController::class, 'storeLab'])->name('erm.visitations.lab.store');
    Route::get('/visitation/cek-antrian', [VisitationController::class, 'cekAntrian'])->name('erm.visitations.cekAntrian');
    Route::get('/rawatjalans', [RawatJalanController::class, 'index'])->name('erm.rawatjalans.index');
    Route::post('/rawatjalans/create', [RawatJalanController::class, 'store'])->name('erm.rawatjalans.store');
    Route::get('/cek-antrian', [RawatJalanController::class, 'cekAntrian'])->name('erm.rawatjalans.cekAntrian');


    //Asesmen
    Route::get('asesmendokter/{visitation}/create', [AsesmenController::class, 'create'])->name('erm.asesmendokter.create');
    Route::post('asesmendokter/store', [AsesmenController::class, 'store'])->name('erm.asesmendokter.store');

    //asesmen perawat
    Route::get('asesmenperawat/{visitation}/create', [AsesmenPerawatController::class, 'create'])->name('erm.asesmenperawat.create');
    Route::post('asesmenperawat/store', [AsesmenPerawatController::class, 'store'])->name('erm.asesmenperawat.store');

    //CPPT
    Route::get('cppt/{visitation_id}/create', [CPPTController::class, 'create'])->name('erm.cppt.create');
    Route::post('cppt/store', [CPPTController::class, 'store'])->name('erm.cppt.store');
    Route::get('/cppt/history-json/{visitation}', [CPPTController::class, 'historyJson']);

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

    
    //Riwayat Kunjungan
    Route::get('/riwayat-kunjungan/{pasien}', [RiwayatKunjunganController::class, 'index'])->name('erm.riwayatkunjungan.index');

    Route::get('/resume-medis/{visitation}', [RiwayatKunjunganController::class, 'resumeMedis'])->name('resume.medis');

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


});

Route::prefix('workdoc')->group(
    function () {
        // Route::get('/', [WorkdocDashboardController::class, 'index'])->name('workdoc.dashboard');
        // Add more Workdoc routes here as needed
    }
);
Route::prefix('akreditasi')->group(
    function () {
        Route::get('/', [AkreditasiDashboardController::class, 'index'])->name('akreditasi.dashboard');
        // Add more Akreditasi routes here as needed
    }
);


Route::prefix('finance')->group(
    function () {
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

        // Invoice routes
        // Route::post('/billing/create-invoice', [BillingController::class, 'createInvoice'])->name('billing.createInvoice');
        Route::get('/invoice', [InvoiceController::class, 'index'])->name('finance.invoice.index');
        Route::get('/invoice/{id}', [InvoiceController::class, 'show'])->name('finance.invoice.show');
        Route::put('/invoice/{id}/status', [InvoiceController::class, 'updateStatus'])->name('finance.invoice.updateStatus');
        Route::get('/invoice/{id}/print', [InvoiceController::class, 'printInvoice'])->name('finance.invoice.print');
        Route::get('/invoice/{id}/print-nota', [InvoiceController::class, 'printNota'])->name('finance.invoice.print-nota');
        
    }
);

Route::prefix('inventory')->group(
    function () {

        Route::get('/item', [ItemController::class, 'index'])->name('inventory.item.index');
        Route::get('/item/create', [ItemController::class, 'create'])->name('inventory.item.create');
        Route::post('/item', [ItemController::class, 'store'])->name('inventory.item.store');
        Route::get('/item/{id}/edit', [ItemController::class, 'edit'])->name('inventory.item.edit');
        Route::put('/item/{id}', [ItemController::class, 'update'])->name('inventory.item.update');
        Route::delete('/item/{id}', [ItemController::class, 'destroy'])->name('inventory.item.destroy');
    }
);

Route::prefix('hrd')->group(
    function () {
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
            Route::get('/periods/create', [PerformanceEvaluationController::class, 'create'])->name('periods.create');
            Route::post('/periods', [PerformanceEvaluationController::class, 'store'])->name('periods.store');
            Route::get('/periods/{period}', [PerformanceEvaluationController::class, 'show'])->name('periods.show');
            Route::get('/periods/{period}/edit', [PerformanceEvaluationController::class, 'edit'])->name('periods.edit');
            Route::put('/periods/{period}', [PerformanceEvaluationController::class, 'update'])->name('periods.update');
            Route::delete('/periods/{period}', [PerformanceEvaluationController::class, 'destroy'])->name('periods.destroy');
            Route::post('/periods/{period}/initiate', [PerformanceEvaluationController::class, 'initiate'])->name('periods.initiate');

            // Question Categories
            Route::get('/questions', [PerformanceQuestionController::class, 'index'])->name('questions.index');
            Route::get('/questions/categories/create', [PerformanceQuestionController::class, 'createCategory'])->name('categories.create');
            Route::post('/questions/categories', [PerformanceQuestionController::class, 'storeCategory'])->name('categories.store');
            Route::get('/questions/categories/{category}/edit', [PerformanceQuestionController::class, 'editCategory'])->name('categories.edit');
            Route::put('/questions/categories/{category}', [PerformanceQuestionController::class, 'updateCategory'])->name('categories.update');
            Route::delete('/questions/categories/{category}', [PerformanceQuestionController::class, 'destroyCategory'])->name('categories.destroy');

            // Questions
            Route::get('/questions/create', [PerformanceQuestionController::class, 'createQuestion'])->name('questions.create');
            Route::post('/questions', [PerformanceQuestionController::class, 'storeQuestion'])->name('questions.store');
            Route::get('/questions/{question}/edit', [PerformanceQuestionController::class, 'editQuestion'])->name('questions.edit');
            Route::put('/questions/{question}', [PerformanceQuestionController::class, 'updateQuestion'])->name('questions.update');
            Route::delete('/questions/{question}', [PerformanceQuestionController::class, 'destroyQuestion'])->name('questions.destroy');

            // My Evaluations
            Route::get('/my-evaluations', [PerformanceEvaluationController::class, 'myEvaluations'])->name('my-evaluations');
            Route::get('/evaluations/{evaluation}/fill', [PerformanceEvaluationController::class, 'fillEvaluation'])->name('evaluations.fill');
            Route::post('/evaluations/{evaluation}/submit', [PerformanceScoreController::class, 'submitScores'])->name('evaluations.submit');

            // Results (HRD only)
            Route::get('/results', [PerformanceEvaluationController::class, 'results'])->name('results.index');
            Route::get('/results/periods/{period}', [PerformanceEvaluationController::class, 'periodResults'])->name('results.period');
            Route::get('/results/periods/{period}/employees/{employee}', [PerformanceEvaluationController::class, 'employeeResults'])->name('results.employee');
        });
    }
);

Route::prefix('marketing')->group(function () {

    Route::get('/revenue', [MarketingController::class, 'revenue'])->name('marketing.revenue');
    Route::get('/patients', [MarketingController::class, 'patients'])->name('marketing.patients');
    Route::get('/services', [MarketingController::class, 'services'])->name('marketing.services');
    Route::get('/products', [MarketingController::class, 'products'])->name('marketing.products');
    Route::get('/clinic-comparison', [MarketingController::class, 'clinicComparison'])->name('marketing.clinic-comparison');
    
    // Tindakan Management
    Route::get('/tindakan', [App\Http\Controllers\Marketing\TindakanController::class, 'index'])->name('marketing.tindakan.index');
    Route::get('/tindakan/data', [App\Http\Controllers\Marketing\TindakanController::class, 'getTindakanData'])->name('marketing.tindakan.data');
    Route::get('/tindakan/list', [App\Http\Controllers\Marketing\TindakanController::class, 'getTindakanList'])->name('marketing.tindakan.list');
    Route::post('/tindakan', [App\Http\Controllers\Marketing\TindakanController::class, 'store'])->name('marketing.tindakan.store');
    Route::get('/tindakan/{id}', [App\Http\Controllers\Marketing\TindakanController::class, 'getTindakan']);
    Route::delete('/tindakan/{id}', [App\Http\Controllers\Marketing\TindakanController::class, 'destroy']);
    
    // Get list of specialists (for dropdown)
    Route::get('/spesialisasi/list', [App\Http\Controllers\Marketing\TindakanController::class, 'getSpesialisasiList'])->name('marketing.spesialisasi.list');
    
    // Paket Tindakan Management
    Route::get('/paket-tindakan', [App\Http\Controllers\Marketing\TindakanController::class, 'indexPaket'])->name('marketing.tindakan.paket.index');
    Route::get('/tindakan/paket/data', [App\Http\Controllers\Marketing\TindakanController::class, 'getPaketData'])->name('marketing.tindakan.paket.data');
    Route::post('/tindakan/paket', [App\Http\Controllers\Marketing\TindakanController::class, 'storePaket'])->name('marketing.tindakan.paket.store');
    Route::get('/tindakan/paket/{id}', [App\Http\Controllers\Marketing\TindakanController::class, 'getPaket']);
    Route::delete('/tindakan/paket/{id}', [App\Http\Controllers\Marketing\TindakanController::class, 'destroyPaket']);

    Route::get('/pasien-data', [App\Http\Controllers\Marketing\MarketingController::class, 'pasienData'])->name('marketing.pasien-data');
});

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
Route::get('/labtest/search', [\App\Http\Controllers\ERM\LabTestController::class, 'search'])->name('labtest.search');
Route::get('/konsultasi/search', [\App\Http\Controllers\ERM\KonsultasiController::class, 'search'])->name('konsultasi.search');

