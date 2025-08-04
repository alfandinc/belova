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
use App\Http\Controllers\AkreditasiController;
use App\Http\Controllers\Inventory\ItemController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\Marketing\MarketingController;


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
        ->middleware('role:Hrd|Manager|Employee|Admin')
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
});



Route::get('/customersurvey', [CustSurveyController::class, 'index'])->name('customer.survey');
Route::post('/customersurvey', [CustSurveyController::class, 'store'])->name('customer.survey');

// ERM Routes
Route::prefix('erm')->middleware('role:Dokter|Perawat|Pendaftaran|Admin|Farmasi|Beautician|Lab')->group(function () {
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

    // ...existing code...

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
    Route::get('/spk/print/{riwayatId}', [TindakanController::class, 'printSpk'])->name('erm.spk.print');

    //Riwayat Kunjungan
    Route::get('/riwayat-kunjungan/{pasien}', [RiwayatKunjunganController::class, 'index'])->name('erm.riwayatkunjungan.index');

    Route::get('/resume-medis/{visitation}', [RiwayatKunjunganController::class, 'resumeMedis'])->name('resume.medis');
    Route::get('/riwayatkunjungan/{visitation}/get-data-diagnosis', [RiwayatKunjunganController::class, 'getDataDiagnosis']);
    Route::post('/riwayatkunjungan/store-surat-diagnosis', [RiwayatKunjunganController::class, 'storeSuratDiagnosis']);
    Route::get('/riwayatkunjungan/{visitation}/print-surat-diagnosis', [RiwayatKunjunganController::class, 'printSuratDiagnosis']);

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

        // Invoice routes
        // Route::post('/billing/create-invoice', [BillingController::class, 'createInvoice'])->name('billing.createInvoice');
        Route::get('/invoice', [InvoiceController::class, 'index'])->name('finance.invoice.index');
        Route::get('/invoice/{id}', [InvoiceController::class, 'show'])->name('finance.invoice.show');
        Route::put('/invoice/{id}/status', [InvoiceController::class, 'updateStatus'])->name('finance.invoice.updateStatus');
        Route::get('/invoice/{id}/print', [InvoiceController::class, 'printInvoice'])->name('finance.invoice.print');
        Route::get('/invoice/{id}/print-nota', [InvoiceController::class, 'printNota'])->name('finance.invoice.print-nota');
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

Route::prefix('hrd')->middleware('role:Hrd|Manager|Employee|Admin')->group(function () {
        // Pengajuan Lembur (Overtime)
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
    // Add pasien to follow up from pasien data
    Route::post('/followup/add-from-pasien', [\App\Http\Controllers\Marketing\FollowUpController::class, 'addFromPasien'])->name('marketing.followup.add-from-pasien');
    // AJAX search for SOPs
    Route::get('/sop/search', [App\Http\Controllers\Marketing\TindakanController::class, 'searchSop']);

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

// AJAX route for patient analytics charts
Route::get('/marketing/patients-analytics-data', [\App\Http\Controllers\Marketing\MarketingController::class, 'patientsAnalyticsData'])->name('marketing.patients.analytics.data');

// AJAX route for services analytics charts
Route::get('/marketing/services-analytics-data', [\App\Http\Controllers\Marketing\MarketingController::class, 'servicesAnalyticsData'])->name('marketing.services.analytics.data');

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

