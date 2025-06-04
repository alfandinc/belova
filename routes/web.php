<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\{
    AuthController,
    ERMDashboardController,
    HRDDashboardController,
    InventoryDashboardController,
    MarketingDashboardController,
    FinanceDashboardController
};
use App\Http\Controllers\Admin\{
    UserController,
    RoleController
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
    CPPTController,
    Icd10Controller,
    KeluhanUtamaController,
    RiwayatKunjunganController,
    ListAntrianController,
    SuratIstirahatController
};
use App\Http\Controllers\HRD\EmployeeController;
use App\Http\Controllers\Inventory\ItemController;
use App\Http\Controllers\AddressController;
use App\Models\ERM\SuratIstirahat;

Route::get('/', function () {
    return view('mainmenu');
});

// Different login pages (GET requests only)
Route::get('/erm/login', [AuthController::class, 'showERMLoginForm'])->name('erm.login');
Route::get('/finance/login', [AuthController::class, 'showFinanceLoginForm'])->name('finance.login');
Route::get('/hrd/login', [AuthController::class, 'showHRDLoginForm'])->name('hrd.login');
Route::get('/inventory/login', [AuthController::class, 'showInventoryLoginForm'])->name('inventory.login');
Route::get('/marketing/login', [AuthController::class, 'showMarketingLoginForm'])->name('marketing.login');

// Single POST route for login processing (all forms submit here)
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/erm', [ERMDashboardController::class, 'index'])->name('erm.dashboard');
    Route::get('/finance', [FinanceDashboardController::class, 'index'])->name('finance.dashboard');
    Route::get('/hrd', [HRDDashboardController::class, 'index'])->name('hrd.dashboard');
    Route::get('/inventory', [InventoryDashboardController::class, 'index'])->name('inventory.dashboard');
    Route::get('/marketing', [MarketingDashboardController::class, 'index'])->name('marketing.dashboard');
});

Route::fallback(function () {
    if (!Auth::check()) {
        return redirect('/');
    }
});

Route::prefix('erm')->group(function () {
    // Pasien Management
    // Route::get('/pasiens', [PasienController::class, 'index'])->name('erm.pasiens.index');
    Route::get('/pasiens/create', [PasienController::class, 'create'])->name('erm.pasiens.create');
    Route::post('/pasiens', [PasienController::class, 'store'])->name('erm.pasiens.store');
    Route::get('/pasiens/{id}/edit', [PasienController::class, 'edit'])->name('erm.pasiens.edit');
    Route::put('/pasiens/{id}', [PasienController::class, 'update'])->name('erm.pasiens.update');
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

    // E Resep Farmasi
    Route::get('/eresepfarmasi', [EresepController::class, 'index'])->name('erm.eresepfarmasi.index');
    Route::get('eresepfarmasi/{visitation_id}/create', [EresepController::class, 'farmasicreate'])->name('erm.eresepfarmasi.create');
    Route::post('/resepfarmasi/non-racikan', [EresepController::class, 'farmasistoreNonRacikan'])->name('resepfarmasi.nonracikan.store');
    Route::post('/resepfarmasi/racikan', [EresepController::class, 'farmasistoreRacikan'])->name('resepfarmasi.racikan.store');
    Route::delete('/resepfarmasi/nonracikan/{id}', [EresepController::class, 'farmasidestroyNonRacikan'])->name('resepfarmasi.nonracikan.destroy');
    Route::delete('/resepfarmasi/racikan/{racikanKe}', [EresepController::class, 'farmasidestroyRacikan'])->name('resepfarmasi.racikan.destroy');
    Route::put('resepfarmasi/nonracikan/{id}', [EresepController::class, 'farmasiupdateNonRacikan'])->name('resepfarmasi.nonracikan.update');

    Route::get('/eresepfarmasi/{visitation_id}/json', [EresepController::class, 'getFarmasiResepJson'])->name('erm.eresepfarmasi.json');
    Route::post('/eresepfarmasi/{visitation_id}/copy-from-dokter', [EresepController::class, 'copyFromDokter'])->name('erm.eresepfarmasi.copyfromdokter');

    // Riwayat Farmasi
    Route::get('/riwayat-resep/dokter/{pasienId}', [EresepController::class, 'getRiwayatDokter'])->name('resep.historydokter');
    Route::get('/riwayat-resep/farmasi/{pasienId}', [EresepController::class, 'getRiwayatFarmasi'])->name('resep.historyfarmasi');

    //Alergi
    Route::post('/pasiens/{visitation}/alergi', [AlergiController::class, 'store'])->name('erm.alergi.store');

    //Radiologi
    Route::get('/eradiologi/{visitation_id}/create', [EradiologiController::class, 'create'])->name('erm.eradiologi.create');

    //Lab
    Route::get('/elab/{visitation_id}/create', [ElabController::class, 'create'])->name('erm.elab.create');

    //Tindakan & Inform Consent
    Route::get('/tindakan/{visitation_id}/create', [TindakanController::class, 'create'])->name('erm.tindakan.create');
    Route::get('/tindakan/data/{spesialisasi_id}', [TindakanController::class, 'getTindakanData'])->name('erm.tindakan.data');
    Route::get('/paket-tindakan/data/{spesialisasi_id}', [TindakanController::class, 'getPaketTindakanData'])->name('erm.paket-tindakan.data');
    Route::get('/tindakan/inform-consent/{id}', [TindakanController::class, 'informConsent'])->name('erm.tindakan.inform-consent');
    Route::post('/tindakan/inform-consent/save', [TindakanController::class, 'saveInformConsent'])->name('erm.tindakan.inform-consent.save');
    // Route::post('/tindakan/transaksi/create', [TindakanController::class, 'transaksiTindakan'])->name('erm.tindakan.transaksi.create');
    Route::post('tindakan/transaksi/create', [TindakanController::class, 'transaksiTindakan'])->name('erm.tindakan.transaksi.create');
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

    // Surat Istirahat

    Route::get('/surat/{pasien_id}', [SuratIstirahatController::class, 'index'])->name('erm.suratistirahat.index');
    Route::post('/surat', [SuratIstirahatController::class, 'store'])->name('erm.suratistirahat.store');
    Route::get('erm/surat/{id}/cetak', [SuratIstirahatController::class, 'cetak'])->name('erm.suratistirahat.cetak');

    Route::get('/surat-istirahat/{id}', [SuratIstirahatController::class, 'suratIstirahat'])->name('surat.istirahat');

    //Submit Billing
    Route::post('/resepfarmasi/submit', [EResepController::class, 'submitResep'])->name('resepfarmasi.submit');
});

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

        Route::get('/employee', [EmployeeController::class, 'index'])->name('hrd.employee.index');
        Route::get('/employee/create', [EmployeeController::class, 'create'])->name('hrd.employee.create');
        Route::post('/employee', [EmployeeController::class, 'store'])->name('hrd.employee.store');
        Route::get('/employee/{id}/edit', [EmployeeController::class, 'edit'])->name('hrd.employee.edit');
        Route::put('/employee/{id}', [EmployeeController::class, 'update'])->name('hrd.employee.update');
        Route::delete('/item/{id}', [EmployeeController::class, 'destroy'])->name('hrd.employee.destroy');
    }
);

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
