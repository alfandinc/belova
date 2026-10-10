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
    BCLDashboardController
};
use App\Http\Controllers\ERM\StokGudangController;
use App\Http\Controllers\Admin\{
    UserController,
    RoleController,
};
use App\Http\Controllers\Finance\{
    BillingController,
    FinanceAkunController,
    FinanceReportController,
    FinanceJurnalController,
    FinanceTransactionController,
    InvoiceController,
    PiutangController,
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
    SpkTindakanController,
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
    GudangController,
    MutasiStokController
    ,IcPendaftaranController
};
use App\Http\Controllers\ERM\ObatMappingController;
use App\Http\Controllers\ERM\AturanPakaiController;
use App\Http\Controllers\ERM\LabPaketController;

use App\Http\Controllers\HRD\{
    EmployeeController,
    EmployeeContractController,
    DivisionController,
    EmployeeSelfServiceController,
    PengajuanLiburController
    ,JobListController
};


use App\Http\Controllers\AkreditasiController;
use App\Http\Controllers\Inventory\ItemController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\Marketing\MarketingController;
use App\Http\Controllers\Marketing\PromoController;
use App\Http\Controllers\Insiden\LaporanInsidenController;

use App\Http\Controllers\BCL\{
    ChatTemplateController,
    HomeController,
    PricelistController,
    RoomsController,
    RenterController,
    tr_renterController,
    FinJurnalController,
    InventoriesController,
    RoomCategoryController,
    RoomCategoryImageController,
    pricelist_tambahanController,
    extra_rentController,
    RoomWifiController,
};


use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\BelovaMengajiController;
use App\Http\Controllers\Satusehat\PasienController as SatusehatPasienController;
use App\Http\Controllers\BukuMenuController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DailyJournalController;
use App\Http\Controllers\KPI\IndicatorController;
use App\Http\Controllers\KPI\KpiPeriodController;
use App\Http\Controllers\RndDashboardController;
use App\Http\Controllers\RndMasterController;
use App\Http\Controllers\RndProdukController;
Route::get('/', function () {
    if (!Auth::check()) {
        return view('auth.main_login', [
            'emotionOptions' => AuthController::emotionCatalog(),
        ]);
    }
    // Pass number of in-progress JobList items to the main menu for notification badge
    $user = Auth::user();
    if (!$user) {
        $inProgressCount = 0;
    } else {
        // CEO, Head Manager, Admin, and Hrd see all progress items
            if ($user->hasAnyRole(['Ceo', 'Head Manager', 'Admin', 'Hrd'])) {
            $inProgressCount = \App\Models\HRD\JobList::where('status', 'progress')->count();
        }
        // Manager sees items assigned to their division (including all_divisions)
            elseif ($user->hasRole('Manager')) {
            $divisionId = optional($user->employee)->division_id;
            $inProgressCount = \App\Models\HRD\JobList::where('status', 'progress')
                ->where(function($q) use ($divisionId) {
                    $q->where('all_divisions', 1)
                      ->orWhereHas('divisions', function($q2) use ($divisionId) {
                          $q2->where('hrd_division.id', $divisionId);
                      })
                      ->orWhere('division_id', $divisionId);
                })->count();
        }
        // Regular employees: only count non-manager items for their division
        else {
                $divisionId = optional($user->employee)->division_id;
            $inProgressCount = \App\Models\HRD\JobList::where('status', 'progress')
                ->where('for_manager', 0)
                ->where(function($q) use ($divisionId) {
                    $q->where('all_divisions', 1)
                      ->orWhereHas('divisions', function($q2) use ($divisionId) {
                          $q2->where('hrd_division.id', $divisionId);
                      })
                      ->orWhere('division_id', $divisionId);
                })->count();
        }
    }
    $activeUserEmotions = AuthController::getActiveUserEmotions();
    $emotionOptions = AuthController::emotionCatalog();
    $currentUserEmotion = session('user_emotion', AuthController::DEFAULT_EMOTION);

    return view('mainmenu', compact('inProgressCount', 'activeUserEmotions', 'emotionOptions', 'currentUserEmotion'));
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
    Route::post('/user-emotions/heartbeat', [AuthController::class, 'heartbeatEmotion'])->name('user-emotions.heartbeat');
    Route::post('/user-emotions/update', [AuthController::class, 'updateEmotion'])->name('user-emotions.update');
    Route::get('/chat/avatar/{user}', [ChatController::class, 'avatar'])->name('chat.avatar');
    Route::get('/chat/users', [ChatController::class, 'users'])->name('chat.users');
    Route::get('/chat/conversations/{user}', [ChatController::class, 'conversation'])->name('chat.conversation');
    Route::post('/chat/conversations/{user}', [ChatController::class, 'store'])->name('chat.store');

    // Buku Menu (accessible for all authenticated users)
    Route::get('/buku-menu', [BukuMenuController::class, 'index'])->name('buku-menu.index');
    Route::get('/buku-menu/data', [BukuMenuController::class, 'data'])->name('buku-menu.data');
    Route::get('/buku-menu/tindakan-data', [BukuMenuController::class, 'tindakanData'])->name('buku-menu.tindakan-data');
    Route::get('/buku-menu/labtest-data', [BukuMenuController::class, 'labtestData'])->name('buku-menu.labtest-data');

    // Daily Journal (accessible for all authenticated users)
    Route::get('/daily-journal', [DailyJournalController::class, 'index'])->name('daily-journal.index');
    Route::get('/daily-journal/division', [DailyJournalController::class, 'divisionIndex'])
        ->middleware('role:Manager|Head Manager|Hrd|Admin')
        ->name('daily-journal.division.index');
    Route::post('/daily-journal', [DailyJournalController::class, 'store'])->name('daily-journal.store');
    Route::patch('/daily-journal/{dailyJournalTask}', [DailyJournalController::class, 'update'])->name('daily-journal.update');
    Route::delete('/daily-journal/{dailyJournalTask}', [DailyJournalController::class, 'destroy'])->name('daily-journal.destroy');

    // Hanya user dengan role ERM yang bisa akses modul ERM
    Route::get('/erm', [ERMDashboardController::class, 'index'])
        ->middleware('role:Dokter|Perawat|Pendaftaran|Admin|Farmasi|Beautician|Lab|Finance')
        ->name('erm.dashboard');

    Route::get('/finance', [FinanceDashboardController::class, 'index'])
        ->middleware('role:Kasir|Admin|Finance|Marketing')
        ->name('finance.dashboard');

    // Piutang routes
    Route::middleware(['role:Kasir|Admin|Finance'])->prefix('finance')->group(function() {
        Route::get('piutang', [PiutangController::class, 'index'])->name('finance.piutang.index');
        Route::get('piutang/data', [PiutangController::class, 'data'])->name('finance.piutang.data');
        Route::post('piutang/{id}/receive', [PiutangController::class, 'receivePayment'])->name('finance.piutang.receive');
    });

    // HRD dashboard: accessible to every authenticated user
    Route::get('/hrd', [HRDDashboardController::class, 'index'])
        ->name('hrd.dashboard');
    Route::get('/dashboard', [\App\Http\Controllers\Dashboard\DashboardController::class, 'index'])
        ->name('dashboard.index');

    // Dashboard widgets/settings admin area
    Route::prefix('dashboard')->middleware('role:Admin|Ceo|CEO|Head Manager')->group(function () {
        Route::get('widgets', [\App\Http\Controllers\Dashboard\WidgetController::class, 'index'])->name('dashboard.widgets.index');
        Route::get('widgets/data', [\App\Http\Controllers\Dashboard\WidgetController::class, 'data'])->name('dashboard.widgets.data');
        Route::post('widgets', [\App\Http\Controllers\Dashboard\WidgetController::class, 'store'])->name('dashboard.widgets.store');
        Route::put('widgets/{id}', [\App\Http\Controllers\Dashboard\WidgetController::class, 'update'])->name('dashboard.widgets.update');
        Route::delete('widgets/{id}', [\App\Http\Controllers\Dashboard\WidgetController::class, 'destroy'])->name('dashboard.widgets.destroy');

        Route::get('widget-mappings/data', [\App\Http\Controllers\Dashboard\WidgetController::class, 'mappingsData'])->name('dashboard.widget-mappings.data');
        Route::post('widget-mappings', [\App\Http\Controllers\Dashboard\WidgetController::class, 'storeMapping'])->name('dashboard.widget-mappings.store');
        Route::put('widget-mappings/{mapId}', [\App\Http\Controllers\Dashboard\WidgetController::class, 'updateMapping'])->name('dashboard.widget-mappings.update');
        Route::delete('widget-mappings/{mapId}', [\App\Http\Controllers\Dashboard\WidgetController::class, 'destroyMapping'])->name('dashboard.widget-mappings.destroy');

        Route::get('widgets/{id}/positions', [\App\Http\Controllers\Dashboard\WidgetController::class, 'positions'])->name('dashboard.widgets.positions');
        Route::get('widgets/{id}/positions/data', [\App\Http\Controllers\Dashboard\WidgetController::class, 'positionsData'])->name('dashboard.widgets.positions.data');
        Route::post('widgets/{id}/positions', [\App\Http\Controllers\Dashboard\WidgetController::class, 'addPosition'])->name('dashboard.widgets.positions.add');
        Route::put('widgets/{id}/positions/{mapId}', [\App\Http\Controllers\Dashboard\WidgetController::class, 'updatePosition'])->name('dashboard.widgets.positions.update');
        Route::delete('widgets/{id}/positions/{mapId}', [\App\Http\Controllers\Dashboard\WidgetController::class, 'removePosition'])->name('dashboard.widgets.positions.remove');
    });
    // AJAX: generate next employee `no_induk` in YYMMXXX format
    Route::get('hrd/employee/next-no-induk', [App\Http\Controllers\HRD\EmployeeController::class, 'nextNoInduk'])
        ->name('hrd.employee.nextNoInduk')->middleware('role:Hrd|Admin');
    // Memorandum routes moved to Workdoc section
    // AJAX: Pending approvals filtered by date range (no reload)
    Route::get('/hrd/pending-approvals', [HRDDashboardController::class, 'pendingApprovals'])
        ->name('hrd.dashboard.pending');

    Route::get('/inventory', [InventoryDashboardController::class, 'index'])
        ->middleware('role:Admin|Inventaris')   
        ->name('inventory.dashboard');

    Route::get('/marketing', [MarketingDashboardController::class, 'index'])
        ->middleware('role:Marketing|Admin|Finance')
        ->name('marketing.dashboard');

    // compatibility redirect: old link without 'erm' prefix
    Route::get('/marketing/kategori-tindakan', function() {
        return redirect('/erm/marketing/kategori-tindakan');
    })->middleware('auth');

    Route::get('/workdoc', [WorkdocDashboardController::class, 'index'])
        ->middleware('role:Hrd|Manager|Head Manager|Employee|Admin')
        ->name('workdoc.dashboard');

    // Akreditasi has been merged into Dokumen Kerja (Workdoc); keep old links working
    Route::get('/akreditasi/{path?}', function ($path = null) {
        return redirect('/workdoc/akreditasi' . ($path ? '/' . $path : ''));
    })->where('path', '.*')->middleware('role:Hrd|Manager|Head Manager|Employee|Admin');

    // Laporan Insiden has been merged into Dokumen Kerja (Workdoc); keep old links working
    Route::get('/insiden/{path?}', function ($path = null) {
        return redirect('/workdoc/insiden/laporan_insiden' . ($path && $path !== 'laporan_insiden' ? '/' . preg_replace('#^laporan_insiden/#', '', $path) : ''));
    })->where('path', '.*')->middleware('role:Hrd|Manager|Head Manager|Employee|Admin');

    Route::get('/bcl', [BCLDashboardController::class, 'index'])
        ->middleware('role:Kos|Admin')
        ->name('bcl.dashboard');

    Route::get('/bcl/data', [BCLDashboardController::class, 'data'])
        ->middleware('role:Kos|Admin')
        ->name('bcl.dashboard.data');

    Route::get('/bcl/monthly-revenue-details', [BCLDashboardController::class, 'monthlyRevenueDetails'])
        ->middleware('role:Kos|Admin')
        ->name('bcl.dashboard.monthly-revenue-details');

    Route::get('/rnd', [RndDashboardController::class, 'index'])
        ->middleware('role:Admin|Rnd|rnd|RND')
        ->name('rnd.dashboard');
    Route::get('/rnd/produk', [RndProdukController::class, 'index'])
        ->middleware('role:Admin|Rnd|rnd|RND')
        ->name('rnd.products.index');
    Route::get('/rnd/produk/data', [RndProdukController::class, 'data'])
        ->middleware('role:Admin|Rnd|rnd|RND')
        ->name('rnd.products.data');
    Route::get('/rnd/produk/timeline-data', [RndProdukController::class, 'timelineData'])
        ->middleware('role:Admin|Rnd|rnd|RND')
        ->name('rnd.products.timeline-data');
    Route::put('/rnd/produk/sample-log/{sampleLog}', [RndProdukController::class, 'updateSampleLog'])
        ->middleware('role:Admin|Rnd|rnd|RND')
        ->name('rnd.products.sample-log.update');
    Route::delete('/rnd/produk/sample-log/{sampleLog}', [RndProdukController::class, 'destroySampleLog'])
        ->middleware('role:Admin|Rnd|rnd|RND')
        ->name('rnd.products.sample-log.destroy');
    Route::get('/rnd/produk/notif/{notif}/document', [RndProdukController::class, 'viewNotifDocument'])
        ->middleware('role:Admin|Rnd|rnd|RND')
        ->name('rnd.products.notif.document');
    Route::get('/rnd/produk/documents/{document}', [RndProdukController::class, 'viewProdukDocument'])
        ->middleware('role:Admin|Rnd|rnd|RND')
        ->name('rnd.products.documents.show');
    Route::delete('/rnd/produk/documents/{document}', [RndProdukController::class, 'destroyProdukDocument'])
        ->middleware('role:Admin|Rnd|rnd|RND')
        ->name('rnd.products.documents.destroy');
    Route::get('/rnd/produk/{produk}', [RndProdukController::class, 'show'])
        ->middleware('role:Admin|Rnd|rnd|RND')
        ->name('rnd.products.show');
    Route::post('/rnd/produk/{produk}/notes', [RndProdukController::class, 'storeProdukNote'])
        ->middleware('role:Admin|Rnd|rnd|RND')
        ->name('rnd.products.notes.store');
    Route::post('/rnd/produk/{produk}/timelines', [RndProdukController::class, 'storeProdukTimeline'])
        ->middleware('role:Admin|Rnd|rnd|RND')
        ->name('rnd.products.timelines.store');
    Route::post('/rnd/produk/{produk}/documents', [RndProdukController::class, 'uploadProdukDocuments'])
        ->middleware('role:Admin|Rnd|rnd|RND')
        ->name('rnd.products.documents.upload');
    Route::post('/rnd/produk', [RndProdukController::class, 'store'])
        ->middleware('role:Admin|Rnd|rnd|RND')
        ->name('rnd.products.store');
    Route::put('/rnd/produk/{produk}', [RndProdukController::class, 'update'])
        ->middleware('role:Admin|Rnd|rnd|RND')
        ->name('rnd.products.update');
    Route::delete('/rnd/produk/{produk}', [RndProdukController::class, 'destroy'])
        ->middleware('role:Admin|Rnd|rnd|RND')
        ->name('rnd.products.destroy');
    Route::get('/rnd/masters/{master}/data', [RndMasterController::class, 'data'])
        ->middleware('role:Admin|Rnd|rnd|RND')
        ->name('rnd.masters.data');
    Route::get('/rnd/masters/{master}/{id}', [RndMasterController::class, 'show'])
        ->middleware('role:Admin|Rnd|rnd|RND')
        ->name('rnd.masters.show');
    Route::post('/rnd/masters/{master}', [RndMasterController::class, 'store'])
        ->middleware('role:Admin|Rnd|rnd|RND')
        ->name('rnd.masters.store');
    Route::put('/rnd/masters/{master}/{id}', [RndMasterController::class, 'update'])
        ->middleware('role:Admin|Rnd|rnd|RND')
        ->name('rnd.masters.update');
    Route::delete('/rnd/masters/{master}/{id}', [RndMasterController::class, 'destroy'])
        ->middleware('role:Admin|Rnd|rnd|RND')
        ->name('rnd.masters.destroy');
});

// AJAX: delete (zero-out) all stok records for an obat in a gudang (requires auth + role)
Route::post('/erm/stok-gudang/delete', [StokGudangController::class, 'deleteObatFromGudang'])
    ->middleware(['auth','role:Admin|Farmasi|Beautician'])
    ->name('erm.stok-gudang.delete');



// Belova Mengaji module
Route::get('/belova-mengaji', [BelovaMengajiController::class, 'index'])->middleware('auth')->name('belova.mengaji.index');
Route::get('/belova-mengaji/employees-data', [BelovaMengajiController::class, 'employeesData'])->middleware('auth')->name('belova.mengaji.employees.data');
Route::post('/belova-mengaji/store', [BelovaMengajiController::class, 'store'])->middleware('auth')->name('belova.mengaji.store');
// Analytics page for Belova Mengaji (placed with other Belova routes)
Route::get('/belova-mengaji/analytics', [BelovaMengajiController::class, 'analytics'])->middleware('auth')->name('belova.mengaji.analytics');
Route::get('/belova-mengaji/analytics/data', [BelovaMengajiController::class, 'analyticsData'])->middleware('auth')->name('belova.mengaji.analytics.data');
Route::get('/belova-mengaji/history', [BelovaMengajiController::class, 'history'])->middleware('auth')->name('belova.mengaji.history');
// Export routes (per-tanggal) - only include employees that have records that day
Route::get('/belova-mengaji/export/pdf', [BelovaMengajiController::class, 'exportPdf'])->middleware('auth')->name('belova.mengaji.export.pdf');
Route::get('/belova-mengaji/export/excel', [BelovaMengajiController::class, 'exportExcel'])->middleware('auth')->name('belova.mengaji.export.excel');

// Events module (main menu "Events"): event list, patients per event and event billing - open to every logged-in user
Route::middleware('auth')->prefix('events')->name('events.')->group(function () {
    Route::get('/', [\App\Http\Controllers\EventsController::class, 'index'])->name('index');
    Route::get('/data', [\App\Http\Controllers\EventsController::class, 'data'])->name('data');
    Route::post('/', [\App\Http\Controllers\EventsController::class, 'store'])->name('store');
    Route::get('/{event}', [\App\Http\Controllers\EventsController::class, 'show'])->whereNumber('event')->name('show');
    Route::get('/{event}/edit-data', [\App\Http\Controllers\EventsController::class, 'editData'])->whereNumber('event')->name('edit-data');
    Route::put('/{event}', [\App\Http\Controllers\EventsController::class, 'update'])->whereNumber('event')->name('update');
    Route::delete('/{event}', [\App\Http\Controllers\EventsController::class, 'destroy'])->whereNumber('event')->name('destroy');
    Route::get('/{event}/patients', [\App\Http\Controllers\EventsController::class, 'patients'])->whereNumber('event')->name('patients');

    // Event billing (moved from Finance): the regular billing page in event mode
    Route::get('/{event}/billing', [\App\Http\Controllers\Finance\BillingController::class, 'eventCreate'])->whereNumber('event')->name('billing.create');
    Route::get('/{event}/billing/items/search', [\App\Http\Controllers\Finance\BillingController::class, 'searchEventItems'])->whereNumber('event')->name('billing.items');
    Route::post('/{event}/billing/start', [\App\Http\Controllers\Finance\BillingController::class, 'startEventBilling'])->whereNumber('event')->name('billing.start');

    Route::prefix('billing')->name('billing.')->group(function () {
        // read-only lookups the billing page needs (stock, gudang, patient search)
        Route::get('/gudang-data', [\App\Http\Controllers\Finance\BillingController::class, 'getGudangData'])->name('gudang-data');
        Route::get('/stock-info-modal', [\App\Http\Controllers\Finance\BillingController::class, 'stockInfoModal'])->name('stock-info-modal');
        Route::get('/riwayat-tindakan-obats', [\App\Http\Controllers\Finance\BillingController::class, 'riwayatTindakanObats'])->name('riwayat-tindakan-obats');
        Route::get('/tindakan-obats', [\App\Http\Controllers\Finance\BillingController::class, 'tindakanObats'])->name('tindakan-obats');
        Route::get('/batch-details', [StokGudangController::class, 'getBatchDetails'])->name('batch-details');
        Route::get('/pasien-select2', [\App\Http\Controllers\ERM\PasienController::class, 'select2'])->name('pasien-select2');

        // writes / invoice access: event visits only (jenis_kunjungan 4)
        Route::middleware('event.visitation')->group(function () {
            Route::get('/data/{visitation_id}', [\App\Http\Controllers\Finance\BillingController::class, 'create'])->name('data');
            Route::post('/save', [\App\Http\Controllers\Finance\BillingController::class, 'saveBilling'])->name('save');
            Route::post('/create-invoice', [\App\Http\Controllers\Finance\BillingController::class, 'createInvoice'])->name('create-invoice');
            Route::post('/receive-payment', [\App\Http\Controllers\Finance\BillingController::class, 'receivePayment'])->name('receive-payment');
            Route::get('/invoice/{invoice}/print-nota', [\App\Http\Controllers\EventsController::class, 'printNota'])->whereNumber('invoice')->name('print-nota');
        });
    });
});
Route::get('/events/lebaran', [\App\Http\Controllers\Events\LebaranController::class, 'index'])
    ->middleware('auth')
    ->name('events.lebaran.index');
Route::get('/events/lebaran/data', [\App\Http\Controllers\Events\LebaranController::class, 'data'])
    ->middleware('auth')
    ->name('events.lebaran.data');
Route::post('/events/lebaran/import', [\App\Http\Controllers\Events\LebaranController::class, 'import'])
    ->middleware('auth')
    ->name('events.lebaran.import');
Route::post('/events/lebaran/{lebaran}/mark-sent', [\App\Http\Controllers\Events\LebaranController::class, 'markSent'])
    ->middleware('auth')
    ->name('events.lebaran.mark_sent');

// Running section (index only)
Route::get('/running', [\App\Http\Controllers\RunningController::class, 'index'])
    ->middleware('auth')
    ->name('running.index');
Route::get('/running/data', [\App\Http\Controllers\RunningController::class, 'data'])
    ->middleware('auth')
    ->name('running.data');
Route::post('/running/import', [\App\Http\Controllers\RunningController::class, 'import'])
    ->middleware('auth')
    ->name('running.import');
Route::get('/running/export-csv', [\App\Http\Controllers\RunningController::class, 'exportCsv'])
    ->middleware('auth')
    ->name('running.export_csv');
Route::post('/running/verify', [\App\Http\Controllers\RunningController::class, 'verify'])
    ->middleware('auth')
    ->name('running.verify');
Route::post('/running/{id}/mark-email-sent', [\App\Http\Controllers\RunningController::class, 'markEmailSent'])
    ->middleware('auth')
    ->name('running.mark_email_sent');
Route::post('/running/{id}/toggle-email-sent', [\App\Http\Controllers\RunningController::class, 'toggleEmailSent'])
    ->middleware('auth')
    ->name('running.toggle_email_sent');
Route::get('/running/find', [\App\Http\Controllers\RunningController::class, 'find'])
    ->middleware('auth')
    ->name('running.find');
Route::get('/running/ticket/{id}', [\App\Http\Controllers\RunningController::class, 'ticket'])
    ->middleware('auth')
    ->name('running.ticket');
Route::get('/running/ticket-html/{id}', [\App\Http\Controllers\RunningController::class, 'ticketHtml'])
    ->middleware('auth')
    ->name('running.ticket.html');

    

// Public-facing Belova Premiere Run page (no auth required)
Route::get('/belovapremiererun', function(){
    return view('belovapremiererun.index');
})->name('belovapremiererun.index');

// Public data endpoint for the Belova Premiere Run datatable
Route::get('/belovapremiererun/data', [\App\Http\Controllers\RunningController::class, 'data'])
    ->name('belovapremiererun.data');

// Public ticket HTML (fragment) for Belova Premiere Run, used by public page JS
Route::get('/belovapremiererun/ticket-html/{id}', [\App\Http\Controllers\RunningController::class, 'publicTicketHtml'])
    ->name('belovapremiererun.ticket_html');

// Public download route for generated ticket images (if available)
Route::get('/belovapremiererun/ticket-download/{id}', [\App\Http\Controllers\RunningController::class, 'publicTicketImageDownload'])
    ->name('belovapremiererun.ticket_download');

// Verify peserta with notes (AJAX)
Route::post('/running/{id}/verify-with-notes', [\App\Http\Controllers\RunningController::class, 'verifyWithNotes'])
    ->middleware('auth')
    ->name('running.verify_with_notes');

// Store generated ticket image for peserta (called from UI after html2canvas)
Route::post('/running/store-ticket-image', [\App\Http\Controllers\RunningController::class, 'storeTicketImage'])
    ->middleware('auth')
    ->name('running.store_ticket_image');
// Interstitial preview page for WhatsApp message (copies message and opens chat)
Route::get('/running/wa-preview', [\App\Http\Controllers\RunningController::class, 'waPreview'])
    ->middleware('auth')
    ->name('running.wa_preview');

// SatuSehat lives in the Admin Panel; klinik configs are edited in Admin > Klinik Setting (tab SatuSehat)
Route::get('/satusehat', fn () => redirect()->route('admin.klinik_settings.index'))->middleware(['auth','role:Satusehat|Admin'])->name('satusehat.index');

Route::prefix('satusehat')->middleware(['auth','role:Satusehat|Admin'])->group(function () {
    Route::get('/clinics/{any?}', fn () => redirect()->route('admin.klinik_settings.index'))->where('any', '.*')->name('satusehat.clinics.index');
    Route::post('/clinics/{clinicConfig}/link', [\App\Http\Controllers\SatusehatClinicController::class, 'link'])->name('satusehat.clinics.link');
    Route::delete('/clinics/{clinicConfig}', [\App\Http\Controllers\SatusehatClinicController::class, 'destroy'])->name('satusehat.clinics.destroy');
    Route::post('/clinics/{clinicConfig}/token', [\App\Http\Controllers\SatusehatClinicController::class, 'requestToken'])->name('satusehat.clinics.token');
});

// SatuSehat Locations CRUD
Route::prefix('satusehat')->middleware(['auth','role:Satusehat|Admin'])->group(function () {
    // Locations are edited per klinik in Admin > Klinik Setting (tab SatuSehat)
    Route::get('/locations/{any?}', fn () => redirect()->route('admin.klinik_settings.index'))->where('any', '.*')->name('satusehat.locations.index');
    Route::post('/locations/{location}/link', [\App\Http\Controllers\Satusehat\LocationController::class, 'link'])->name('satusehat.locations.link');
    Route::delete('/locations/{location}', [\App\Http\Controllers\Satusehat\LocationController::class, 'destroy'])->name('satusehat.locations.destroy');
});

// Dokter Mapping (SatuSehat)
Route::prefix('satusehat')->middleware(['auth','role:Satusehat|Admin'])->group(function () {
    Route::get('/mapping-dokter', [\App\Http\Controllers\Satusehat\DokterMappingController::class, 'index'])->name('satusehat.dokter_mapping.index');
    Route::match(['get','post'], '/mapping-dokter/data', [\App\Http\Controllers\Satusehat\DokterMappingController::class, 'data'])->name('satusehat.dokter_mapping.data');
    Route::post('/mapping-dokter', [\App\Http\Controllers\Satusehat\DokterMappingController::class, 'store'])->name('satusehat.dokter_mapping.store');
});

// Satusehat - Pasien (index + AJAX data for today's visitations)
Route::get('/satusehat/pasiens', [SatusehatPasienController::class, 'index'])->middleware(['auth','role:Satusehat|Admin'])->name('satusehat.pasiens.index');
Route::get('/satusehat/pasiens/data', [SatusehatPasienController::class, 'data'])->middleware(['auth','role:Satusehat|Admin'])->name('satusehat.pasiens.data');
Route::get('/satusehat/pasiens/{visitation}/get-data', [SatusehatPasienController::class, 'getKemkesPatient'])->middleware(['auth','role:Satusehat|Admin'])->name('satusehat.pasiens.get_kemkes');
Route::post('/satusehat/pasiens/{visitation}/create-encounter', [SatusehatPasienController::class, 'createKemkesEncounter'])->middleware(['auth','role:Satusehat|Admin'])->name('satusehat.pasiens.create_encounter');
Route::post('/satusehat/pasiens/{visitation}/send-condition', [SatusehatPasienController::class, 'createKemkesCondition'])->middleware(['auth','role:Satusehat|Admin'])->name('satusehat.pasiens.send_condition');
Route::post('/satusehat/pasiens/{visitation}/update-encounter', [SatusehatPasienController::class, 'updateKemkesEncounter'])->middleware(['auth','role:Satusehat|Admin'])->name('satusehat.pasiens.update_encounter');
Route::post('/satusehat/pasiens/{visitation}/finish-encounter', [SatusehatPasienController::class, 'finishKemkesEncounter'])->middleware(['auth','role:Satusehat|Admin'])->name('satusehat.pasiens.finish_encounter');
Route::post('/satusehat/pasiens/{visitation}/send-medication', [SatusehatPasienController::class, 'sendKemkesMedication'])->middleware(['auth','role:Satusehat|Admin'])->name('satusehat.pasiens.send_medication');

Route::prefix('bcl')->middleware('role:Kos|Admin')->group(function () {
    Route::post('/rooms/store', [RoomsController::class, 'store'])->name('bcl.rooms.store');
    Route::get('/rooms/data', [RoomsController::class, 'data'])->name('bcl.rooms.data');
    Route::get('/rooms/form-data', [RoomsController::class, 'formData'])->name('bcl.rooms.form_data');
    Route::get('/rooms/deleted-data', [RoomsController::class, 'deletedData'])->name('bcl.rooms.deleted_data');
    Route::get('/rooms/unpaid-data', [RoomsController::class, 'unpaidData'])->name('bcl.rooms.unpaid_data');
    Route::get('/rooms/extra-bed-data', [RoomsController::class, 'extraBedData'])->name('bcl.rooms.extra_bed_data');
    Route::get('/rooms/{id}/booking-queue', [RoomsController::class, 'bookingQueue'])->name('bcl.rooms.booking_queue');
    Route::get('/rooms/edit/{id}', [RoomsController::class, 'edit'])->name('bcl.rooms.edit');
    Route::post('/rooms/update', [RoomsController::class, 'update'])->name('bcl.rooms.update');
    Route::get('/rooms/delete/{id}', [RoomsController::class, 'destroy'])->name('bcl.rooms.delete');
    Route::get('/rooms/restore/{id}', [RoomsController::class, 'restore'])->name('bcl.rooms.restore');
    Route::get('/rooms', [RoomsController::class, 'index'])->name('bcl.rooms');
    Route::get('/rooms/{id}/wifi', [RoomsController::class, 'wifi'])->name('bcl.rooms.wifi');
    Route::get('/rooms/{id}/history', [RoomsController::class, 'history'])->name('bcl.rooms.history');
    Route::get('/rooms/{id}', [PricelistController::class, 'get_room_pricelist'])->name('bcl.pricelist.get_pl_room');
    Route::post('/tambahanpl', [pricelist_tambahanController::class, 'store'])->name('bcl.extra_pl.store');
    Route::get('/tambahanpl/edit/{id}', [pricelist_tambahanController::class, 'edit'])->name('bcl.extra_pl.edit');
    Route::post('/tambahanpl/update', [pricelist_tambahanController::class, 'update'])->name('bcl.extra_pl.update');
    Route::get('/tambahanpl/delete/{id}', [pricelist_tambahanController::class, 'destroy'])->name('bcl.extra_pl.delete');
    Route::post('/rooms/sewa', [tr_renterController::class, 'sewa'])->name('bcl.rooms.sewa');

    Route::get('/category', [RoomCategoryController::class, 'index'])->name('bcl.category.index');
    Route::post('/category/store', [RoomCategoryController::class, 'store'])->name('bcl.category.store');
    Route::get('/category/edit/{id}', [RoomCategoryController::class, 'edit'])->name('bcl.category.edit');
    Route::post('/category/update', [RoomCategoryController::class, 'update'])->name('bcl.category.update');
    Route::get('/category/delete/{id}', [RoomCategoryController::class, 'destroy'])->name('bcl.category.delete');
    Route::get('/category/restore/{id}', [RoomCategoryController::class, 'restore'])->name('bcl.category.restore');
    Route::post('/images/store', [RoomCategoryImageController::class, 'store'])->name('bcl.images.store');
    Route::get('/images/delete/{id}', [RoomCategoryImageController::class, 'destroy'])->name('bcl.images.delete');
    Route::get('/category/forcedelete/{id}', [RoomCategoryController::class, 'forcedelete'])->name('bcl.category.forcedelete');

    Route::get('/renter', [RenterController::class, 'index'])->name('bcl.renter.index');
    Route::post('/renter/store', [RenterController::class, 'store'])->name('bcl.renter.store');
    Route::get('/renter/detail/{id}', [RenterController::class, 'detail'])->name('bcl.renter.detail');
    Route::get('/renter/rules/{id}', [RenterController::class, 'rulesPreview'])->name('bcl.renter.rules');
    Route::get('/renter/edit/{id}', [RenterController::class, 'edit'])->name('bcl.renter.edit');
    Route::post('/renter/update', [RenterController::class, 'update'])->name('bcl.renter.update');
    Route::get('/renter/delete/{id}', [RenterController::class, 'destroy'])->name('bcl.renter.delete');

    Route::get('/chat-template', [ChatTemplateController::class, 'index'])->name('bcl.chat_template.index');
    Route::get('/chat-template/data', [ChatTemplateController::class, 'data'])->name('bcl.chat_template.data');
    Route::post('/chat-template/store', [ChatTemplateController::class, 'store'])->name('bcl.chat_template.store');
    Route::get('/chat-template/edit/{id}', [ChatTemplateController::class, 'edit'])->name('bcl.chat_template.edit');
    Route::post('/chat-template/update/{id}', [ChatTemplateController::class, 'update'])->name('bcl.chat_template.update');
    Route::get('/chat-template/delete/{id}', [ChatTemplateController::class, 'destroy'])->name('bcl.chat_template.delete');

    Route::get('/inventories', [InventoriesController::class, 'index'])->name('bcl.inventories.index');
    Route::post('/inventories/store', [InventoriesController::class, 'store'])->name('bcl.inventories.store');
    Route::get('/inventories/edit/{id}', [InventoriesController::class, 'edit'])->name('bcl.inventories.edit');
    Route::post('/inventories/update', [InventoriesController::class, 'update'])->name('bcl.inventories.update');
    Route::get('/inventories/delete/{id}', [InventoriesController::class, 'destroy'])->name('bcl.inventories.delete');
    Route::get('/inventories/show/{id}', [InventoriesController::class, 'show'])->name('bcl.inventories.show');
    Route::post('/inventories/maintenance', [InventoriesController::class, 'storeMaintenance'])->name('bcl.inventories.maintenance.store');
    Route::post('/inventories/maintenance/update', [InventoriesController::class, 'updateMaintenance'])->name('bcl.inventories.maintenance.update');
    Route::post('/inventories/maintenance/delete', [InventoriesController::class, 'deleteMaintenance'])->name('bcl.inventories.maintenance.delete');
    // Export inventories PDF (form posts with selected rooms)
    Route::post('/inventories/export/pdf', [InventoriesController::class, 'exportPdf'])->name('bcl.inventories.export.pdf');

    Route::get('/pricelist', [PricelistController::class, 'index'])->name('bcl.pricelist.index');
    Route::post('/pricelist/store', [PricelistController::class, 'store'])->name('bcl.pricelist.store');
    Route::get('/pricelist/edit/{id}', [PricelistController::class, 'edit'])->name('bcl.pricelist.edit');
    Route::post('/pricelist/update', [PricelistController::class, 'update'])->name('bcl.pricelist.update');
    Route::get('/pricelist/delete/{id}', [PricelistController::class, 'destroy'])->name('bcl.pricelist.delete');
    
    Route::any('/finance/income', [FinJurnalController::class, 'index'])->name('bcl.income.index');
    Route::get('/finance/income/delete/{id}', [FinJurnalController::class, 'income_delete'])->name('bcl.income.delete');
    Route::any('/finance/expense', [FinJurnalController::class, 'expense'])->name('bcl.expense.index');
    Route::get('/finance/expense/view/{id}', [FinJurnalController::class, 'expense_show'])->name('bcl.expense.show');
    Route::get('/finance/expense/delete/{id}', [FinJurnalController::class, 'expense_delete'])->name('bcl.expense.delete');
    Route::post('/finance/income/store', [FinJurnalController::class, 'store'])->name('bcl.income.store');
    Route::post('/finance/deposit/topup', [FinJurnalController::class, 'topup_deposit'])->name('bcl.deposit.topup');
    Route::post('/finance/expense/store', [FinJurnalController::class, 'store_expense'])->name('bcl.expense.store');

    Route::any('/transaksi', [tr_renterController::class, 'index'])->name('bcl.transaksi.index');
    Route::get('/transaksi/show/{id}', [tr_renterController::class, 'show'])->name('bcl.transaksi.show');
    Route::get('/transaksi/delete/{id}', [tr_renterController::class, 'destroy'])->name('bcl.transaksi.delete');
    Route::post('/transaksi/refund', [tr_renterController::class, 'refund'])->name('bcl.transaksi.refund');
    Route::post('/transaksi/reschedule', [tr_renterController::class, 'reschedule'])->name('bcl.transaksi.reschedule');
    Route::post('/transaksi/change-room', [tr_renterController::class, 'changeRoom'])->name('bcl.transaksi.change_room');
    // AJAX: list available target rooms with price matching current transaction duration
    Route::get('/transaksi/change-room/options/{id}', [tr_renterController::class, 'changeRoomOptions'])->name('bcl.transaksi.change_room.options');
    Route::get('/transaksi/cetak/{id}', [tr_renterController::class, 'cetak'])->name('bcl.transaksi.cetak');
    // Refund/Refund-print route for downgrade refunds (optional renter_id helps associate receipt)
    Route::get('/transaksi/refund/cetak/{doc_id}/{renter_id?}', [tr_renterController::class, 'cetakRefund'])->name('bcl.transaksi.cetak_refund');
    Route::get('/extrarent/availability', [extra_rentController::class, 'availability'])->name('bcl.extrarent.availability');
    Route::post('/extrarent/store', [extra_rentController::class, 'store'])->name('bcl.extrarent.store');
    // Room Wifi management (AJAX + Datatables)
    Route::get('/wifi', [RoomWifiController::class, 'index'])->name('bcl.roomwifi.index');
    Route::get('/wifi/data', [RoomWifiController::class, 'data'])->name('bcl.roomwifi.data');
    Route::post('/wifi/store', [RoomWifiController::class, 'store'])->name('bcl.roomwifi.store');
    Route::get('/wifi/edit/{id}', [RoomWifiController::class, 'edit'])->name('bcl.roomwifi.edit');
    Route::post('/wifi/update/{id}', [RoomWifiController::class, 'update'])->name('bcl.roomwifi.update');
    Route::get('/wifi/delete/{id}', [RoomWifiController::class, 'destroy'])->name('bcl.roomwifi.delete');
});
















Route::get('/hrd/absensi-rekap/export-excel', [\App\Http\Controllers\HRD\AbsensiRekapController::class, 'exportExcel'])->name('hrd.absensi_rekap.export_excel');
// Admin: visitation CSV import (dashboard form posts here)
Route::post('/admin/visitations/import', [\App\Http\Controllers\Admin\VisitationImportController::class, 'import'])
    ->name('admin.visitations.import')
    ->middleware(['auth','role:Admin']);

// Preview CSV before import (returns JSON preview)
Route::post('/admin/visitations/preview', [\App\Http\Controllers\Admin\VisitationImportController::class, 'preview'])
    ->name('admin.visitations.preview')
    ->middleware(['auth','role:Admin']);
// ERM Routes
Route::prefix('erm')->middleware('role:Dokter|Perawat|Pendaftaran|Admin|Farmasi|Beautician|Lab|Finance|Kasir')->group(function () {
    // Dokter to Perawat notification
    Route::get('/pasien/{pasien}/merchandises', [RawatJalanController::class, 'getPasienMerchandises'])->name('erm.pasien.merchandises');
        Route::post('/send-notif-perawat', [App\Http\Controllers\ERM\RawatJalanController::class, 'sendNotifToPerawat'])->middleware('auth');
        Route::post('/send-notif-dokter', [App\Http\Controllers\ERM\RawatJalanController::class, 'sendNotifToDokter'])->middleware('auth');
            // ...existing ERM routes...
        Route::get('fakturretur', [\App\Http\Controllers\ERM\FakturReturController::class, 'index'])->name('erm.fakturretur.index');
        Route::get('fakturretur/create', [\App\Http\Controllers\ERM\FakturReturController::class, 'create'])->name('erm.fakturretur.create');
        Route::post('fakturretur', [\App\Http\Controllers\ERM\FakturReturController::class, 'store'])->name('erm.fakturretur.store');
        Route::get('fakturretur/{id}', [\App\Http\Controllers\ERM\FakturReturController::class, 'show'])->name('erm.fakturretur.show');
        Route::post('fakturretur/{id}/approve', [\App\Http\Controllers\ERM\FakturReturController::class, 'approve'])->name('erm.fakturretur.approve');
    

    // AJAX endpoint for nilai stok gudang & keseluruhan
    Route::get('/stok-gudang/nilai-stok', [\App\Http\Controllers\ERM\StokGudangController::class, 'getNilaiStok'])->name('erm.stok-gudang.nilai-stok');
    // Export stok gudang to Excel
    Route::get('/stok-gudang/export', [\App\Http\Controllers\ERM\StokGudangController::class, 'exportToExcel'])->name('erm.stok-gudang.export');
    // Export FakturBeli items to CSV/Excel-compatible file
    Route::get('/fakturbeli/items/export', [\App\Http\Controllers\ERM\FakturBeliController::class, 'exportItemsExcel'])->name('erm.fakturbeli.items.export');
    Route::get('/get-notif', [App\Http\Controllers\ERM\RawatJalanController::class, 'getNotif'])->middleware('auth');
    Route::post('/notif-mark-read', [App\Http\Controllers\ERM\RawatJalanController::class, 'markNotifRead'])->middleware('auth')->name('erm.rawatjalans.notification-mark-read');
    Route::post('/notif-mark-all-read', [App\Http\Controllers\ERM\RawatJalanController::class, 'markAllNotifRead'])->middleware('auth')->name('erm.rawatjalans.notification-mark-all-read');
    Route::get('/notif-unread-count', [App\Http\Controllers\ERM\RawatJalanController::class, 'notificationUnreadCount'])->middleware('auth')->name('erm.rawatjalans.notification-unread-count');
    Route::get('/notif-history', [App\Http\Controllers\ERM\RawatJalanController::class, 'notificationHistory'])->middleware('auth')->name('erm.rawatjalans.notification-history');

    // Allow ERM pages to search kode tindakan and fetch bundled obats without requiring Marketing role
    Route::get('/kodetindakan/search', [\App\Http\Controllers\Marketing\KodeTindakanController::class, 'search']);
    Route::get('/kodetindakan/{id}/obats', [\App\Http\Controllers\Marketing\KodeTindakanController::class, 'getObats']);
    // Kategori Tindakan routes (marketing master data)
    Route::get('/marketing/kategori-tindakan', [\App\Http\Controllers\Marketing\KategoriTindakanController::class, 'index'])->name('marketing.kategori.index');
    Route::post('/marketing/kategori-tindakan', [\App\Http\Controllers\Marketing\KategoriTindakanController::class, 'store'])->name('marketing.kategori.store');
    Route::delete('/marketing/kategori-tindakan/{id}', [\App\Http\Controllers\Marketing\KategoriTindakanController::class, 'destroy'])->name('marketing.kategori.destroy');
    Route::post('/marketing/kategori-tindakan/import', [\App\Http\Controllers\Marketing\KategoriTindakanController::class, 'importPreview'])->name('marketing.kategori.import.preview');
    Route::post('/marketing/kategori-tindakan/import/apply', [\App\Http\Controllers\Marketing\KategoriTindakanController::class, 'importApply'])->name('marketing.kategori.import.apply');
    // Select2 search for kategori (used by kode tindakan form)
    Route::get('/erm/kategori-tindakan/search', [\App\Http\Controllers\Marketing\KategoriTindakanController::class, 'search'])->name('marketing.kategori.search');
    // Provide endpoints to toggle active status for bulk actions (accept POST here to avoid being shadowed by the {id} route)
    Route::post('/kodetindakan/action/make-all-inactive', [\App\Http\Controllers\Marketing\KodeTindakanController::class, 'makeAllInactive']);
    Route::post('/kodetindakan/action/make-all-active', [\App\Http\Controllers\Marketing\KodeTindakanController::class, 'makeAllActive']);
    // Bulk activate/deactivate with date-range preview
    Route::get('/kodetindakan/by-date', [\App\Http\Controllers\Marketing\KodeTindakanController::class, 'getByDate']);
    Route::post('/kodetindakan/action/bulk-set-active', [\App\Http\Controllers\Marketing\KodeTindakanController::class, 'bulkSetActive']);
    Route::post('/marketing/kodetindakan/import', [\App\Http\Controllers\Marketing\KodeTindakanController::class, 'importCsv']);
    // Allow ERM pages to get kode tindakan details (includes hpp)
    Route::get('/kodetindakan/{id}', [\App\Http\Controllers\Marketing\KodeTindakanController::class, 'show']);

    // AJAX: Get allow_post value for InformConsent
    Route::get('/inform-consent/{id}/get', [App\Http\Controllers\ERM\TindakanController::class, 'getInformConsentAllowPost']);
    
    // Warehouse Stock Management
    Route::prefix('stok-gudang')->group(function () {
        Route::get('/', [StokGudangController::class, 'index'])->name('erm.stok-gudang.index');
        Route::get('/data', [StokGudangController::class, 'getData'])->name('erm.stok-gudang.data');
        Route::get('/low-stock', [StokGudangController::class, 'getLowStockData'])->name('erm.stok-gudang.low-stock');
        Route::get('/low-stock/count', [StokGudangController::class, 'getLowStockCount'])->name('erm.stok-gudang.low-stock.count');
        Route::get('/expiring', [StokGudangController::class, 'getExpiringData'])->name('erm.stok-gudang.expiring');
        Route::get('/expiring/count', [StokGudangController::class, 'getExpiringCount'])->name('erm.stok-gudang.expiring.count');
        Route::post('/expiring/move-to-expired', [StokGudangController::class, 'moveBatchToExpiredGudang'])->name('erm.stok-gudang.expiring.move');
        Route::post('/expired-follow-up', [StokGudangController::class, 'storeExpiredFollowUp'])->name('erm.stok-gudang.expired-follow-up');
        Route::get('/expiring/batches', [StokGudangController::class, 'getExpiringBatches'])->name('erm.stok-gudang.expiring.batches');
        Route::get('/batch-details', [StokGudangController::class, 'getBatchDetails'])->name('erm.stok-gudang.batch-details');
        Route::post('/update-batch-stok', [StokGudangController::class, 'updateBatchStok'])->name('erm.stok-gudang.update-batch-stok');
        Route::post('/update-batch-exp', [StokGudangController::class, 'updateBatchExpiration'])->name('erm.stok-gudang.update-batch-exp');
        Route::get('/stok-migrate', [StokGudangController::class, 'stokMigrate'])->name('erm.stok-gudang.stok-migrate');
        Route::post('/update-batch-name', [StokGudangController::class, 'updateBatchName'])->name('erm.stok-gudang.update-batch-name');
        Route::post('/update-minmax', [StokGudangController::class, 'updateMinMax'])->name('erm.stok-gudang.update-minmax');
        Route::post('/sync-missing-minmax', [StokGudangController::class, 'syncMissingMinMax'])->name('erm.stok-gudang.sync-missing-minmax');
    });
    
    // Mutasi Gudang Routes
    Route::prefix('mutasi-gudang')->group(function () {
    Route::get('/', [MutasiGudangController::class, 'index'])->name('erm.mutasi-gudang.index');
    Route::get('/data', [MutasiGudangController::class, 'data'])->name('erm.mutasi-gudang.data');
    Route::post('/', [MutasiGudangController::class, 'store'])->name('erm.mutasi-gudang.store');
    // Route baru: get data obat sesuai stok di gudang asal
    Route::get('/obat', [MutasiGudangController::class, 'getObatGudang'])->name('erm.mutasi-gudang.obat');
    
    // Obat Baru routes - untuk menambah obat yang belum ada stok di gudang manapun
    Route::get('/obat-without-stock', [MutasiGudangController::class, 'getObatWithoutStock'])->name('erm.mutasi-gudang.obat-without-stock');
    Route::get('/bulk-obat-preview', [MutasiGudangController::class, 'getBulkObatPreview'])->name('erm.mutasi-gudang.bulk-obat-preview');
    Route::post('/obat-baru', [MutasiGudangController::class, 'storeObatBaru'])->name('erm.mutasi-gudang.store-obat-baru');
    Route::post('/bulk-obat-baru', [MutasiGudangController::class, 'storeBulkObatBaru'])->name('erm.mutasi-gudang.store-bulk-obat-baru');
    // Cetak/print mutasi (PDF)
    Route::get('/{id}/print', [MutasiGudangController::class, 'print'])->name('erm.mutasi-gudang.print');
    
    // Route dengan parameter {id} HARUS DI AKHIR
    Route::get('/{id}', [MutasiGudangController::class, 'show'])->name('erm.mutasi-gudang.show');
    Route::post('/{id}/approve', [MutasiGudangController::class, 'approve'])->name('erm.mutasi-gudang.approve');
    Route::post('/{id}/reject', [MutasiGudangController::class, 'reject'])->name('erm.mutasi-gudang.reject');
    });

    Route::prefix('mutasi-stok')->middleware('role:Farmasi|Admin|Beautician')->group(function () {
        Route::get('/', [MutasiStokController::class, 'index'])->name('erm.mutasi-stok.index');
        Route::get('/data', [MutasiStokController::class, 'data'])->name('erm.mutasi-stok.data');
        Route::get('/obat', [MutasiStokController::class, 'getObatGudang'])->name('erm.mutasi-stok.obat');
        Route::post('/', [MutasiStokController::class, 'store'])->name('erm.mutasi-stok.store');
        Route::get('/{id}/print', [MutasiStokController::class, 'print'])->name('erm.mutasi-stok.print');
        Route::post('/{id}/cancel', [MutasiStokController::class, 'cancel'])->name('erm.mutasi-stok.cancel');
        Route::get('/{id}', [MutasiStokController::class, 'show'])->name('erm.mutasi-stok.show');
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
    Route::get('/fakturpembelian/{id}/json', [App\Http\Controllers\ERM\FakturBeliController::class, 'showJson'])->name('erm.fakturbeli.json');
    // DataTables AJAX for Mutasi Obat Masuk
    Route::get('/obat-masuk/data', [App\Http\Controllers\ERM\ObatMasukController::class, 'data'])->name('erm.obatmasuk.data');
    // Mutasi Obat Masuk
    Route::get('/obat-masuk', [App\Http\Controllers\ERM\ObatMasukController::class, 'index'])->name('erm.obatmasuk.index');
    // Update harga jual (nonfornas) via AJAX
    Route::post('/obat/{id}/update-harga', [App\Http\Controllers\ERM\ObatController::class, 'updateHargaJual'])->name('erm.obat.update-harga');
    // Add PUT/PATCH route for updating Obat
    Route::put('/obat/{id}', [App\Http\Controllers\ERM\ObatController::class, 'update'])->name('erm.obat.update');
    Route::patch('/obat/{id}', [App\Http\Controllers\ERM\ObatController::class, 'update']);
    // Export Obat data to Excel
    Route::get('/obat/export-excel', [App\Http\Controllers\ERM\ObatController::class, 'exportExcel'])->name('erm.obat.export-excel');
    // Monitor Profit
    Route::get('/monitor-profit', [App\Http\Controllers\ERM\ObatController::class, 'monitorProfit'])->name('erm.monitor-profit');
        // Inline update stok fisik for hasil stok opname
        Route::post('stokopname-item/{id}/update-stok-fisik', [App\Http\Controllers\ERM\StokOpnameController::class, 'updateStokFisik'])->name('erm.stokopnameitem.update-stok-fisik');
        Route::post('stokopname-item/{id}/submit-temuan', [App\Http\Controllers\ERM\StokOpnameController::class, 'submitTemuan'])->name('erm.stokopnameitem.submit-temuan');
        Route::get('stokopname-item/{id}/temuan-history', [App\Http\Controllers\ERM\StokOpnameController::class, 'getTemuanHistory'])->name('erm.stokopnameitem.temuan-history');
        // Record-only temuan entries (added from modal)
        Route::post('stokopname-item/{id}/add-temuan-record', [App\Http\Controllers\ERM\StokOpnameController::class, 'addTemuanRecord'])->name('erm.stokopnameitem.add-temuan-record');
    // AJAX endpoints for select2 (controller)
    Route::get('ajax/obat', [App\Http\Controllers\ERM\MasterFakturController::class, 'ajaxObat']);
    // Zat Aktif AJAX endpoints (moved to controller)
    Route::get('ajax/zataktif', [App\Http\Controllers\ERM\ZatAktifController::class, 'index']);
    Route::post('ajax/zataktif', [App\Http\Controllers\ERM\ZatAktifController::class, 'store']);
    Route::put('ajax/zataktif/{id}', [App\Http\Controllers\ERM\ZatAktifController::class, 'update']);
    Route::post('ajax/zataktif/{id}/merge', [App\Http\Controllers\ERM\ZatAktifController::class, 'merge']);
    Route::delete('ajax/zataktif/{id}', [App\Http\Controllers\ERM\ZatAktifController::class, 'destroy']);
    // Single obat details for AJAX (used by various JS fallbacks)
    Route::get('ajax/obat/{id}', [App\Http\Controllers\ERM\ObatController::class, 'edit']);
    Route::get('ajax/pemasok', [App\Http\Controllers\ERM\MasterFakturController::class, 'ajaxPemasok']);
    Route::get('ajax/principal', [App\Http\Controllers\ERM\MasterFakturController::class, 'ajaxPrincipal']);

        // Kartu Stok
        Route::get('/kartu-stok', [App\Http\Controllers\ERM\KartuStokController::class, 'index'])->name('erm.kartustok.index');
        Route::get('/kartu-stok/data', [App\Http\Controllers\ERM\KartuStokController::class, 'data'])->name('erm.kartustok.data');
        Route::get('/kartu-stok/detail', [App\Http\Controllers\ERM\KartuStokController::class, 'detail'])->name('erm.kartustok.detail');
        Route::get('/kartu-stok/analytics', [App\Http\Controllers\ERM\KartuStokController::class, 'analytics'])->name('erm.kartustok.analytics');
    Route::get('/kartu-stok/export', [App\Http\Controllers\ERM\KartuStokController::class, 'export'])->name('erm.kartustok.export');
    Route::get('/kartu-stok/export-stok-terakhir', [App\Http\Controllers\ERM\KartuStokController::class, 'exportStokTerakhir'])->name('erm.kartustok.export_stok_terakhir');

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
    Route::post('/pasiens/{id}/update-status-review', [PasienController::class, 'updateStatusReview'])->name('erm.pasiens.update-status-review');
    Route::post('/pasiens/{id}/update-status-combined', [PasienController::class, 'updateStatusCombined'])->name('erm.pasiens.update-status-combined');
    Route::delete('/pasiens/{id}', [PasienController::class, 'destroy'])->name('erm.pasiens.destroy');
    Route::get('/erm/pasien/{id}', [PasienController::class, 'show'])->name('erm.pasien.show');

    // Pasien merchandise listing
    Route::get('/pasiens/{id}/merchandises', [\App\Http\Controllers\ERM\PasienMerchandiseController::class, 'index'])->name('erm.pasiens.merchandises.index');
    Route::post('/pasiens/{id}/merchandises', [\App\Http\Controllers\ERM\PasienMerchandiseController::class, 'store'])->name('erm.pasiens.merchandises.store');
    Route::put('/pasiens/{id}/merchandises/{pmId}', [\App\Http\Controllers\ERM\PasienMerchandiseController::class, 'update'])->name('erm.pasiens.merchandises.update');
    Route::delete('/pasiens/{id}/merchandises/{pmId}', [\App\Http\Controllers\ERM\PasienMerchandiseController::class, 'destroy'])->name('erm.pasiens.merchandises.destroy');

    // Master Pemasok AJAX CRUD (managed from the Pemasok & Principal modal on Master Obat)
    Route::get('pemasok', [App\Http\Controllers\ERM\PemasokController::class, 'index']);
    Route::post('pemasok', [App\Http\Controllers\ERM\PemasokController::class, 'store']);
    Route::put('pemasok/{id}', [App\Http\Controllers\ERM\PemasokController::class, 'update']);
    Route::delete('pemasok/{id}', [App\Http\Controllers\ERM\PemasokController::class, 'destroy']);
    Route::post('pemasok/{id}/merge', [App\Http\Controllers\ERM\PemasokController::class, 'merge']);

    // Export to Excel
    Route::get('pemasok/export-excel', [App\Http\Controllers\ERM\PemasokController::class, 'exportExcel']);

    // Master Principal AJAX CRUD (mirror pemasok)
    Route::get('principal', [App\Http\Controllers\ERM\PrincipalController::class, 'index']);
    Route::post('principal', [App\Http\Controllers\ERM\PrincipalController::class, 'store']);
    Route::put('principal/{id}', [App\Http\Controllers\ERM\PrincipalController::class, 'update']);
    Route::delete('principal/{id}', [App\Http\Controllers\ERM\PrincipalController::class, 'destroy']);
    Route::post('principal/{id}/merge', [App\Http\Controllers\ERM\PrincipalController::class, 'merge']);

    // Export to Excel
    Route::get('principal/export-excel', [App\Http\Controllers\ERM\PrincipalController::class, 'exportExcel']);

   
    Route::get('permintaan/data', [App\Http\Controllers\ERM\PermintaanController::class, 'data'])->name('erm.permintaan.data');
    Route::get('permintaan/master-faktur', [App\Http\Controllers\ERM\PermintaanController::class, 'getMasterFaktur'])->name('erm.permintaan.masterfaktur');
    Route::get('permintaan/obat-options', [App\Http\Controllers\ERM\PermintaanController::class, 'getObatOptions'])->name('erm.permintaan.obat-options');
    Route::get('permintaan/forecast-preview', [App\Http\Controllers\ERM\PermintaanController::class, 'forecastPreview'])->name('erm.permintaan.forecast-preview');
    Route::get('permintaan/{id}/nilai-pembelian', [App\Http\Controllers\ERM\PermintaanController::class, 'nilaiPembelian'])->name('erm.permintaan.nilai-pembelian');
    Route::get('permintaan/{id}/print', [App\Http\Controllers\ERM\PermintaanController::class, 'printSuratPermintaan'])->name('erm.permintaan.print');
        Route::get('permintaan/{id}/print-surat-pemesanan', [App\Http\Controllers\ERM\PermintaanController::class, 'printSuratPemesanan'])->name('erm.permintaan.print-surat-pemesanan');
    Route::resource('permintaan', App\Http\Controllers\ERM\PermintaanController::class)->names('erm.permintaan');
    Route::post('permintaan/{id}/approve', [App\Http\Controllers\ERM\PermintaanController::class, 'approve'])->name('erm.permintaan.approve');
    Route::post('permintaan/{id}/reject', [App\Http\Controllers\ERM\PermintaanController::class, 'reject'])->name('erm.permintaan.reject');
    Route::resource('masterfaktur', App\Http\Controllers\ERM\MasterFakturController::class)->names('erm.masterfaktur');
    Route::get('masterfaktur-data', [App\Http\Controllers\ERM\MasterFakturController::class, 'data'])->name('erm.masterfaktur.data');
    Route::get('masterfaktur-bandingkan', [App\Http\Controllers\ERM\MasterFakturController::class, 'bandingkan'])->name('erm.masterfaktur.bandingkan');
    // Input penawaran vendor (many obat from one pemasok at once)
    Route::get('masterfaktur-penawaran/info', [App\Http\Controllers\ERM\MasterFakturController::class, 'penawaranInfo'])->name('erm.masterfaktur.penawaran.info');
    Route::post('masterfaktur-penawaran/match', [App\Http\Controllers\ERM\MasterFakturController::class, 'penawaranMatch'])->name('erm.masterfaktur.penawaran.match');
    Route::post('masterfaktur-penawaran/save', [App\Http\Controllers\ERM\MasterFakturController::class, 'penawaranSave'])->name('erm.masterfaktur.penawaran.save');
    Route::get('masterfaktur/export-excel', [App\Http\Controllers\ERM\MasterFakturController::class, 'exportExcel'])->name('erm.masterfaktur.export-excel');

    // AJAX form for create/edit modal
    Route::get('masterfaktur/form/{id?}', [App\Http\Controllers\ERM\MasterFakturController::class, 'form'])->name('erm.masterfaktur.form');

    // Obat Keluar
    Route::get('/obat-keluar', [ObatKeluarController::class, 'index'])->name('erm.obatkeluar.index');
    Route::get('/obat-keluar/data', [ObatKeluarController::class, 'data'])->name('erm.obatkeluar.data');
    Route::get('/obat-keluar/detail', [ObatKeluarController::class, 'detail'])->name('erm.obatkeluar.detail');

    //Visitation
    Route::get('/pasiens', [PasienController::class, 'index'])->name('erm.pasiens.index');
    Route::get('/pasiens/select2', [PasienController::class, 'select2'])->name('erm.pasiens.select2');
    Route::get('/pasiens/check-identity-number', [PasienController::class, 'checkIdentityNumber'])->name('erm.pasiens.check-identity-number');
    Route::get('/pasiens/check-duplicate-name-birthdate', [PasienController::class, 'checkDuplicateNameBirthdate'])->name('erm.pasiens.check-duplicate-name-birthdate');
    Route::get('/pasiens/marketplace/check-duplicate', [PasienController::class, 'checkMarketplaceDuplicate'])->name('erm.pasiens.marketplace.check-duplicate');
    Route::get('/pasiens/duplicates', [App\Http\Controllers\ERM\PasienMergeController::class, 'duplicates'])->name('erm.pasiens.duplicates');
    Route::post('/pasiens/merge', [App\Http\Controllers\ERM\PasienMergeController::class, 'merge'])->name('erm.pasiens.merge');
    Route::post('/visitations', [VisitationController::class, 'store'])->name('erm.visitations.store');
    Route::post('/visitations/produk', [VisitationController::class, 'storeProduk'])->name('erm.visitations.produk.store');
    Route::post('/visitations/lab', [VisitationController::class, 'storeLab'])->name('erm.visitations.lab.store');
    Route::post('/visitations/marketplace', [VisitationController::class, 'storeMarketplace'])->name('erm.visitations.marketplace.store');
    Route::get('/visitations/referral-context', [VisitationController::class, 'referralContext'])->name('erm.visitations.referral-context');
    Route::get('/visitation/cek-antrian', [VisitationController::class, 'cekAntrian'])->name('erm.visitations.cekAntrian');

    Route::get('/rawatjalans',[RawatJalanController::class, 'index'])->name('erm.rawatjalans.index');
    Route::get('/rawatjalans/assets/index.js', [RawatJalanController::class, 'assetsJs'])->name('erm.rawatjalans.assets.js');
    Route::get('/rawatjalans/modals/common', [RawatJalanController::class, 'commonModals'])->name('erm.rawatjalans.modals.common');
    Route::get('/rawatjalans/modals/screening-batuk', [RawatJalanController::class, 'screeningBatukModals'])->name('erm.rawatjalans.modals.screeningBatuk');
    Route::get('/rawatjalans/modals/screening-vaksin', [RawatJalanController::class, 'screeningVaksinModals'])->name('erm.rawatjalans.modals.screeningVaksin');
    Route::get('/rawatjalans/queue-calendar', [RawatJalanController::class, 'queueCalendar'])->name('erm.rawatjalans.queueCalendar');
    Route::get('/rawatjalans/stats', [RawatJalanController::class, 'getStats'])->name('erm.rawatjalans.stats');
    Route::post('/rawatjalans/merchandise-stock-out', [RawatJalanController::class, 'merchandiseStockOut'])->name('erm.rawatjalans.merchandise.stock-out');
    Route::post('/rawatjalans/update-metode', [RawatJalanController::class, 'updateMetodeBayar'])->name('erm.rawatjalans.updateMetodeBayar');
    Route::post('/rawatjalans/update-referral', [RawatJalanController::class, 'updateReferral'])->name('erm.rawatjalans.updateReferral');
    Route::get('/rawatjalans/rujuks', [App\Http\Controllers\ERM\RawatJalanController::class, 'listRujuks'])->name('erm.rawatjalans.rujuks');
    Route::get('/rujuk/{id}/surat', [App\Http\Controllers\ERM\RawatJalanController::class, 'printRujukSurat'])->name('erm.rujuk.surat');
    Route::get('/rawatjalans/lab-permintaan', [App\Http\Controllers\ERM\RawatJalanController::class, 'listLabPermintaan'])->name('erm.rawatjalans.labpermintaan');
    Route::get('/rawatjalans/lab-permintaan/visitation/{visitationId}', [App\Http\Controllers\ERM\RawatJalanController::class, 'labPermintaanByVisitation'])->name('erm.rawatjalans.labpermintaan.visitation');
    Route::get('/rawatjalans/patients/{pasienId}/visit-history', [App\Http\Controllers\ERM\RawatJalanController::class, 'patientVisitHistory'])->name('erm.rawatjalans.patientVisitHistory');
    Route::post('/rawatjalans/create', [RawatJalanController::class, 'store'])->name('erm.rawatjalans.store');
    Route::get('/cek-antrian', [RawatJalanController::class, 'cekAntrian'])->name('erm.rawatjalans.cekAntrian');
    // AJAX: Get list of visitations by status for Rawat Jalan stats modal
    Route::get('/rawatjalans/list-by-status', [App\Http\Controllers\ERM\RawatJalanController::class, 'listByStatus']);
    // AJAX: Restore visitation status from dibatalkan (7) to tidak datang (0)
    Route::post('/rawatjalans/restore-status', [App\Http\Controllers\ERM\RawatJalanController::class, 'restoreStatus']);
    // AJAX: Permanently delete a visitation that is in 'dibatalkan' status (7)
    Route::post('/rawatjalans/force-destroy', [App\Http\Controllers\ERM\RawatJalanController::class, 'forceDestroy'])->name('erm.rawatjalans.forceDestroy');



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

    //screening vaksin
    Route::post('screening/vaksin/store', [RawatJalanController::class, 'storeScreeningVaksin'])->name('erm.screening.vaksin.store');
    Route::get('screening/vaksin/{visitation}', [RawatJalanController::class, 'getScreeningVaksin'])->name('erm.screening.vaksin.get');
    Route::put('screening/vaksin/update/{id}', [RawatJalanController::class, 'updateScreeningVaksin'])->name('erm.screening.vaksin.update');

    //CPPT
    Route::get('cppt/{visitation_id}/create', [CPPTController::class, 'create'])->name('erm.cppt.create');
    Route::post('cppt/store', [CPPTController::class, 'store'])->name('erm.cppt.store');
    Route::post('cppt/{id}/mark-read', [CPPTController::class, 'markAsRead'])->name('erm.cppt.mark-read');
    Route::get('/cppt/history-json/{visitation}', [CPPTController::class, 'historyJson']);
    Route::post('cppt/{id}/update', [CPPTController::class, 'update']);
    Route::post('cppt/mark-all-read/{visitationId}', [CPPTController::class, 'markAllAsRead']);

    // E Resep Dokter
    Route::get('eresep/{visitation_id}/create', [EresepController::class, 'create'])->name('erm.eresep.create');
    Route::get('eresep/{visitation_id}/invoice-lock-status', [EresepController::class, 'invoiceLockStatus'])->name('erm.eresep.invoice-lock-status');
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

    Route::post('/eresepfarmasi/{visitation_id}/selesai', [EresepController::class, 'markResepFarmasiSelesai'])->name('erm.eresepfarmasi.selesai');

    Route::get('/eresepfarmasi/{visitation_id}/print', [EresepController::class, 'printResep'])->name('erm.eresepfarmasi.print');

    Route::post('/eresepfarmasi/copy-from-history', [EresepController::class, 'copyFromHistoryFarmasi'])->name('erm.eresepfarmasi.copyfromhistory');
    Route::post('/eresep/copy-from-history', [EresepController::class, 'copyFromHistory'])->name('erm.eresep.copyfromhistory');
    
    // Notification Routes
    Route::get('/check-notifications', [NotificationController::class, 'checkNewNotifications'])->name('erm.check.notifications');
    Route::post('/notify-pasien-keluar', [NotificationController::class, 'notifyPasienKeluar'])->name('erm.notify.pasien.keluar');
    // Return old notifications (used by Farmasi "Old Notifications" modal)
    Route::get('/farmasi/notifications/old', [NotificationController::class, 'oldNotifications'])->name('erm.farmasi.notifications.old');
    // Return unread count (used by Farmasi "Old Notifications" button badge)
    Route::get('/farmasi/notifications/old-count', [NotificationController::class, 'oldNotificationsUnreadCount'])->name('erm.farmasi.notifications.old.count');
    // Mark single notification as read
    Route::post('/farmasi/notifications/{id}/mark-read', [NotificationController::class, 'markAsRead'])->name('erm.farmasi.notifications.markread');
    // Mark all notifications as read
    Route::post('/farmasi/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('erm.farmasi.notifications.markallread');
    
    // Paket Racikan Routes (picker in e-resep; master page is under /erm/master/paket-racikan)
    Route::get('/paket-racikan/list', [EresepController::class, 'getPaketRacikanList'])->name('erm.paket-racikan.list');
    Route::post('/paket-racikan/copy', [EresepController::class, 'copyFromPaketRacikan'])->name('erm.paket-racikan.copy');
    // Farmasi-specific copy endpoint: copy paket racikan into resep farmasi
    Route::post('/paket-racikan/copy-farmasi', [EresepController::class, 'copyFromPaketRacikanToFarmasi'])->name('erm.paket-racikan.copy.farmasi');
    Route::post('/paket-racikan/store', [EresepController::class, 'storePaketRacikan'])->name('erm.paket-racikan.store');
    Route::put('/paket-racikan/{id}', [EresepController::class, 'updatePaketRacikan'])->name('erm.paket-racikan.update');
    Route::delete('/paket-racikan/{id}', [EresepController::class, 'deletePaketRacikan'])->name('erm.paket-racikan.delete');
    
    Route::get('/eresepfarmasi/{visitation_id}/print-etiket', [EresepController::class, 'printEtiket'])->name('erm.eresepfarmasi.print-etiket');
    
    // Etiket Biru Routes
    Route::get('/eresepfarmasi/{visitation_id}/get-obat', [EresepController::class, 'getVisitationObat'])->name('erm.eresepfarmasi.get-visitation-obat');
    Route::post('/eresepfarmasi/etiket-biru/print', [EresepController::class, 'printEtiketBiru'])->name('erm.eresepfarmasi.etiket-biru.print');

    // Statistik Farmasi Routes
    Route::get('/statistic/summary', [StatisticController::class, 'summary'])->name('erm.statistic.summary');

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
    Route::put('/eradiologi/hasil/{id}/deskripsi', [EradiologiController::class, 'updateRadiologiHasilDescription'])->name('erm.eradiologi.hasil.update-deskripsi');
    
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
    // Lab notifications (completed tests polling)
    Route::get('/elab/notifications/completed', [\App\Http\Controllers\API\LabNotificationController::class, 'completed'])->name('erm.elab.notifications.completed');

    // Lab analytics dashboard
    Route::get('/elab/analytics', [\App\Http\Controllers\ERM\ElabAnalyticsController::class, 'index'])->name('erm.elab.analytics');
    Route::get('/elab/analytics/visits-per-day', [\App\Http\Controllers\ERM\ElabAnalyticsController::class, 'visitsPerDay'])->name('erm.elab.analytics.visits-per-day');
    Route::get('/elab/analytics/tests-per-category', [\App\Http\Controllers\ERM\ElabAnalyticsController::class, 'testsPerCategory'])->name('erm.elab.analytics.tests-per-category');
    Route::get('/elab/analytics/patients-type', [\App\Http\Controllers\ERM\ElabAnalyticsController::class, 'patientsType'])->name('erm.elab.analytics.patients-type');
    Route::get('/elab/analytics/payment-status', [\App\Http\Controllers\ERM\ElabAnalyticsController::class, 'paymentStatus'])->name('erm.elab.analytics.payment-status');
    Route::get('/elab/analytics/top-tests', [\App\Http\Controllers\ERM\ElabAnalyticsController::class, 'topTests'])->name('erm.elab.analytics.top-tests');
    Route::get('/elab/analytics/top-patients-visits', [\App\Http\Controllers\ERM\ElabAnalyticsController::class, 'topPatientsByVisits'])->name('erm.elab.analytics.top-patients-visits');
    Route::get('/elab/analytics/top-patients-spending', [\App\Http\Controllers\ERM\ElabAnalyticsController::class, 'topPatientsBySpending'])->name('erm.elab.analytics.top-patients-spending');
    Route::get('/elab/analytics/totals-summary', [\App\Http\Controllers\ERM\ElabAnalyticsController::class, 'totalsSummary'])->name('erm.elab.analytics.totals-summary');

    // Lightweight unauthenticated health/test endpoint for analytics (debug only)
    Route::get('/elab/analytics/test-json', function(){
        return response()->json(['ok' => true, 'msg' => 'analytics test endpoint reachable']);
    });

    //Tindakan & Inform Consent
    Route::get('/tindakan/{visitation_id}/create', [TindakanController::class, 'create'])->name('erm.tindakan.create');
    Route::get('/tindakan/data/{spesialisasi_id}', [TindakanController::class, 'getTindakanData'])->name('erm.tindakan.data');
    // Custom Tindakan creation (AJAX) route removed
    Route::get('/paket-tindakan/data/{spesialisasi_id}', [TindakanController::class, 'getPaketTindakanData'])->name('erm.paket-tindakan.data');
    Route::get('/tindakan/inform-consent/{id}', [TindakanController::class, 'informConsent'])->name('erm.tindakan.inform-consent');
    Route::get('/tindakan/{id}/prices', [TindakanController::class, 'getPrices']);
    Route::get('/tindakan/{id}/multi-visit-status', [TindakanController::class, 'getMultiVisitStatus']);
    Route::get('/tindakan/{id}/exists-in-visitation', [TindakanController::class, 'existsInVisitation']);
    Route::post('/tindakan/inform-consent/save', [TindakanController::class, 'saveInformConsent'])->name('erm.tindakan.inform-consent.save');
    Route::get('/tindakan/history/{visitation}', [TindakanController::class, 'getRiwayatTindakanHistory'])->name('tindakan.history');
    // Print detail riwayat tindakan (grouped by visitation for the same patient)
    Route::get('/tindakan/history/{visitation}/print-detail', [TindakanController::class, 'printHistoryDetail'])->name('erm.tindakan.history.print');
    Route::get('/tindakan/sop/{id}', [TindakanController::class, 'generateSopPdf'])->name('erm.tindakan.sop');

    Route::post('/tindakan/upload-foto/{id}', [TindakanController::class, 'uploadFoto'])->name('erm.tindakan.upload-foto');

    // Slimming
    Route::get('/slimming/{visitation_id}/create', [\App\Http\Controllers\ERM\SlimmingController::class, 'create'])->name('erm.slimming.create');
    Route::post('/slimming/store', [\App\Http\Controllers\ERM\SlimmingController::class, 'store'])->name('erm.slimming.store');
    Route::get('/slimming/{visitation_id}/data', [\App\Http\Controllers\ERM\SlimmingController::class, 'data'])->name('erm.slimming.data');
    
    // SPK Routes (Old system - keep for backward compatibility)
    Route::get('/tindakan/spk/by-riwayat/{riwayat_id}', [TindakanController::class, 'getSpkDataByRiwayat'])->name('erm.tindakan.spk.byriwayat');
    Route::post('/tindakan/spk/save', [TindakanController::class, 'saveSpk'])->name('erm.tindakan.spk.save');
    Route::get('/spk/create', [TindakanController::class, 'spkCreate'])->name('erm.spk.create');
    Route::get('/spk/modal', [TindakanController::class, 'spkModal'])->name('erm.spk.modal');
    Route::get('/spk/print/{riwayatId}', [TindakanController::class, 'printSpk'])->name('erm.spk.print');

    // SPK Tindakan Routes (New detailed system)
    Route::get('/spktindakan', [SpkTindakanController::class, 'index'])->name('erm.spktindakan.index');
    Route::get('/spktindakan/{id}/items', [SpkTindakanController::class, 'showItems'])->name('erm.spktindakan.items');
    Route::post('/spktindakan/{id}/items', [SpkTindakanController::class, 'updateItems'])->name('erm.spktindakan.items.update');
    Route::post('/spktindakan/{id}/status', [SpkTindakanController::class, 'updateStatus'])->name('erm.spktindakan.status.update');

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
    Route::get('/obat/forecast', [ObatController::class, 'forecastIndex'])->name('erm.obat.forecast.index');
    Route::get('/obat/forecast-keluar', [ObatController::class, 'forecastKeluar'])->name('erm.obat.forecast-keluar');
    Route::get('/obat/forecast-keluar/{id}/detail', [ObatController::class, 'forecastKeluarDetail'])->name('erm.obat.forecast-keluar.detail');
    Route::get('/obat/forecast-all', [ObatController::class, 'forecastAll'])->name('erm.obat.forecast-all');
    Route::post('/obat/forecast-all/export', [ObatController::class, 'exportForecastDisplayed'])->name('erm.obat.forecast-all.export');
    Route::get('/obat/check-nama', [ObatController::class, 'checkNama'])->name('erm.obat.check-nama');
    Route::get('/obat/{id}/similar', [ObatController::class, 'similarObats'])->name('erm.obat.similar');
    Route::patch('/obat/{id}/toggle-favorite', [ObatController::class, 'toggleFavorite'])->name('erm.obat.toggle-favorite');
    Route::post('/obat', [ObatController::class, 'store'])->name('erm.obat.store');
    Route::get('/obat/{id}/edit', [ObatController::class, 'edit'])->name('erm.obat.edit');
    Route::get('/obat/{id}/forecast', [ObatController::class, 'forecast'])->name('erm.obat.forecast');
    Route::get('/obat/{id}/relations', [ObatController::class, 'relations'])->name('erm.obat.relations');
    // CSV import preview & import (AJAX)
    Route::post('/obat/import-csv-preview', [ObatController::class, 'importCsvPreview'])->name('erm.obat.import_csv_preview');
    Route::post('/obat/import-csv', [ObatController::class, 'importCsv'])->name('erm.obat.import_csv');
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

    Route::post('/rawatjalans/batalkan', [RawatJalanController::class, 'batalkan']);
    Route::post('/rawatjalans/edit-antrian', [RawatJalanController::class, 'editAntrian']);


    // Batalkan (delete) riwayat tindakan
    Route::delete('/tindakan/riwayat/{id}', [TindakanController::class, 'destroyRiwayatTindakan'])->name('erm.tindakan.riwayat.destroy');
    
    // SOP route
    Route::get('/tindakan/{id}/sop-list', [TindakanController::class, 'getSopList']);

    
    // Faktur Pembelian
    Route::get('/fakturpembelian', [\App\Http\Controllers\ERM\FakturBeliController::class, 'index'])->name('erm.fakturbeli.index');
    Route::get('/fakturpembelian/select2', [\App\Http\Controllers\ERM\FakturBeliController::class, 'select2'])->name('erm.fakturbeli.select2');
    Route::get('/fakturpembelian/cari-by-no-permintaan', [\App\Http\Controllers\ERM\FakturBeliController::class, 'cariByNoPermintaan']);
    Route::get('/fakturpembelian/create', [\App\Http\Controllers\ERM\FakturBeliController::class, 'create'])->name('erm.fakturbeli.create');
    Route::post('/fakturpembelian', [\App\Http\Controllers\ERM\FakturBeliController::class, 'store'])->name('erm.fakturbeli.store');
    Route::get('/fakturpembelian/{id}/edit', [\App\Http\Controllers\ERM\FakturBeliController::class, 'edit'])->name('erm.fakturbeli.edit');
    Route::post('/fakturpembelian/{id}/update', [\App\Http\Controllers\ERM\FakturBeliController::class, 'update'])->name('erm.fakturbeli.update');
    Route::delete('/fakturpembelian/{id}', [\App\Http\Controllers\ERM\FakturBeliController::class, 'destroy'])->name('erm.fakturbeli.destroy');

    // Obat Hibah
    Route::get('/obat-hibah', [\App\Http\Controllers\ERM\ObatHibahController::class, 'index'])->name('erm.obat-hibah.index');
    Route::get('/obat-hibah/create', [\App\Http\Controllers\ERM\ObatHibahController::class, 'create'])->name('erm.obat-hibah.create');
    Route::post('/obat-hibah', [\App\Http\Controllers\ERM\ObatHibahController::class, 'store'])->name('erm.obat-hibah.store');
    Route::post('/obat-hibah/{id}/approve', [\App\Http\Controllers\ERM\ObatHibahController::class, 'approve'])->name('erm.obat-hibah.approve');
    
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
    // Export stok opname results (items) to Excel
    Route::get('/{id}/export-results', [\App\Http\Controllers\ERM\StokOpnameController::class, 'exportResultsExcel'])->name('erm.stokopname.exportResults');
    // Export temuan (record-only temuan entries) to Excel
    Route::get('/{id}/export-temuan', [\App\Http\Controllers\ERM\StokOpnameController::class, 'exportTemuanExcel'])->name('erm.stokopname.exportTemuan');
    // AJAX: temuan list JSON for DataTable modal
    Route::get('/{id}/temuan-data', [\App\Http\Controllers\ERM\StokOpnameController::class, 'temuanData'])->name('erm.stokopname.temuanData');
    Route::post('/{id}/upload-excel', [\App\Http\Controllers\ERM\StokOpnameController::class, 'uploadExcel'])->name('erm.stokopname.uploadExcel');
    // Stok Opname Items DataTable AJAX
    Route::get('/{id}/items-data', [\App\Http\Controllers\ERM\StokOpnameController::class, 'itemsData'])->name('erm.stokopname.itemsData');
    // Update catatan/notes for stok opname item
    Route::post('/item/{itemId}/update-notes', [\App\Http\Controllers\ERM\StokOpnameController::class, 'updateItemNotes'])->name('erm.stokopname.item.updateNotes');
    // Update status for stok opname (AJAX)
    Route::post('/{id}/update-status', [\App\Http\Controllers\ERM\StokOpnameController::class, 'updateStatus'])->name('erm.stokopname.updateStatus');
    // AJAX sync totals
    Route::get('/{id}/sync-totals', [\App\Http\Controllers\ERM\StokOpnameController::class, 'getStokTotals'])->name('erm.stokopname.syncTotals');
    
    // New multi-gudang stock opname routes
    Route::post('/{id}/generate-items', [\App\Http\Controllers\ERM\StokOpnameController::class, 'generateStokOpnameItems'])->name('erm.stokopname.generateItems');
    Route::post('/{id}/add-item', [\App\Http\Controllers\ERM\StokOpnameController::class, 'addItem'])->name('erm.stokopname.addItem');
    Route::get('/{id}/available-obats', [\App\Http\Controllers\ERM\StokOpnameController::class, 'getAvailableObats'])->name('erm.stokopname.availableObats');
    Route::post('/{id}/update-stock-from-opname', [\App\Http\Controllers\ERM\StokOpnameController::class, 'updateStokFromOpname'])->name('erm.stokopname.updateStockFromOpname');
    Route::post('/generate-lembur-so', [\App\Http\Controllers\ERM\StokOpnameController::class, 'generateLemburSo'])->name('erm.stokopname.generateLemburSo');
    });

    Route::post('/rujuk', [App\Http\Controllers\ERM\VisitationController::class, 'storeRujuk'])->name('erm.rujuk.store');
    
});

// Aturan pakai suggestions (from resep history) used by resep dokter/farmasi forms.
Route::prefix('erm')->middleware('role:Dokter|Admin|Farmasi')->group(function () {
    Route::get('/aturan-pakai/list/suggest', [AturanPakaiController::class, 'suggest'])->name('erm.aturan-pakai.list.suggest');
});

Route::prefix('workdoc')->middleware('role:Hrd|Manager|Head Manager|Employee|Admin')->group(function () {
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
    Route::get('/folders/{folder}/download', [App\Http\Controllers\Workdoc\DocumentController::class, 'downloadFolder'])->name('workdoc.folders.download');
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
    // Update notulensi (AJAX)
    Route::put('/notulensi-rapat/{id}', [App\Http\Controllers\Workdoc\NotulensiRapatController::class, 'update'])->name('workdoc.notulensi-rapat.update');

    // Kemitraan (Workdoc) - CRUD via modal + DataTable
    Route::get('/kemitraan', [App\Http\Controllers\Workdoc\KemitraanController::class, 'index'])->name('workdoc.kemitraan.index');
    Route::get('/kemitraan/data', [App\Http\Controllers\Workdoc\KemitraanController::class, 'data'])->name('workdoc.kemitraan.data');
    Route::post('/kemitraan', [App\Http\Controllers\Workdoc\KemitraanController::class, 'store'])->name('workdoc.kemitraan.store');
    Route::get('/kemitraan/{id}', [App\Http\Controllers\Workdoc\KemitraanController::class, 'show'])->name('workdoc.kemitraan.show');
    Route::put('/kemitraan/{id}', [App\Http\Controllers\Workdoc\KemitraanController::class, 'update'])->name('workdoc.kemitraan.update');
    Route::delete('/kemitraan/{id}', [App\Http\Controllers\Workdoc\KemitraanController::class, 'destroy'])->name('workdoc.kemitraan.destroy');

    // Workdoc Memorandum routes
    Route::get('/memorandums', [\App\Http\Controllers\Workdoc\MemorandumController::class, 'index'])->name('workdoc.memorandum.index');
    Route::get('/memorandums/create', [\App\Http\Controllers\Workdoc\MemorandumController::class, 'create'])->name('workdoc.memorandum.create');
    Route::get('/memorandums/data', [\App\Http\Controllers\Workdoc\MemorandumController::class, 'data'])->name('workdoc.memorandum.data');
    Route::get('/memorandums/generate-number', [\App\Http\Controllers\Workdoc\MemorandumController::class, 'generateNumber'])->name('workdoc.memorandum.generate_number');
    Route::post('/memorandums', [\App\Http\Controllers\Workdoc\MemorandumController::class, 'store'])->name('workdoc.memorandum.store');
    Route::get('/memorandums/{memorandum}', [\App\Http\Controllers\Workdoc\MemorandumController::class, 'show'])->name('workdoc.memorandum.show');
    Route::get('/memorandums/{memorandum}/edit', [\App\Http\Controllers\Workdoc\MemorandumController::class, 'edit'])->name('workdoc.memorandum.edit');
    Route::get('/memorandums/{memorandum}/print-pdf', [\App\Http\Controllers\Workdoc\MemorandumController::class, 'printPdf'])->name('workdoc.memorandum.print_pdf');
    Route::put('/memorandums/{memorandum}', [\App\Http\Controllers\Workdoc\MemorandumController::class, 'update'])->name('workdoc.memorandum.update');
    Route::delete('/memorandums/{memorandum}', [\App\Http\Controllers\Workdoc\MemorandumController::class, 'destroy'])->name('workdoc.memorandum.destroy');
    // Dokumen pendukung
    Route::post('/memorandums/{memorandum}/dokumen', [\App\Http\Controllers\Workdoc\MemorandumController::class, 'uploadDokumen'])->name('workdoc.memorandum.dokumen.upload');
    Route::get('/memorandums/{memorandum}/dokumen', [\App\Http\Controllers\Workdoc\MemorandumController::class, 'viewDokumen'])->name('workdoc.memorandum.dokumen.view');
    // Disposisi endpoints
    Route::get('/disposisi/divisions', [\App\Http\Controllers\Workdoc\DisposisiController::class, 'divisions'])->name('workdoc.disposisi.divisions');
    Route::post('/disposisi', [\App\Http\Controllers\Workdoc\DisposisiController::class, 'store'])->name('workdoc.disposisi.store');
    Route::put('/disposisi/{disposisi}', [\App\Http\Controllers\Workdoc\DisposisiController::class, 'update'])->name('workdoc.disposisi.update');
    Route::get('/disposisi/memorandums/{memorandum}/latest', [\App\Http\Controllers\Workdoc\DisposisiController::class, 'latestForMemorandum'])->name('workdoc.disposisi.latest');
    Route::get('/disposisi/{disposisi}/print-pdf', [\App\Http\Controllers\Workdoc\DisposisiController::class, 'printPdf'])->name('workdoc.disposisi.print_pdf');
});


Route::prefix('workdoc/akreditasi')->middleware('role:Hrd|Manager|Head Manager|Employee|Admin')->group(function () {
    // Single page: BAB/Standar list + EPs & documents of the selected Standar
    Route::get('/', [AkreditasiController::class, 'index'])->name('akreditasi.index');
    Route::get('/standar/{standar}', [AkreditasiController::class, 'showStandar'])->name('akreditasi.standar.detail');

    // Old standalone pages now open the single page
    Route::get('/bab', fn () => redirect()->route('akreditasi.index'));
    Route::get('/bab/{bab}/standars', fn () => redirect()->route('akreditasi.index'));
    Route::get('/standar/{standar}/eps', fn (\App\Models\Akreditasi\Standar $standar) => redirect()->route('akreditasi.standar.detail', $standar));
    Route::get('/ep/{ep}', fn (\App\Models\Akreditasi\Ep $ep) => redirect()->route('akreditasi.standar.detail', $ep->standar_id));

    // BAB
    Route::post('/bab', [AkreditasiController::class, 'storeBab'])->name('akreditasi.bab.store');
    Route::put('/bab/{bab}', [AkreditasiController::class, 'updateBab'])->name('akreditasi.bab.update');
    Route::delete('/bab/{bab}', [AkreditasiController::class, 'destroyBab'])->name('akreditasi.bab.destroy');

    // Standar
    Route::post('/bab/{bab}/standar', [AkreditasiController::class, 'storeStandar'])->name('akreditasi.standar.store');
    Route::put('/standar/{standar}', [AkreditasiController::class, 'updateStandar'])->name('akreditasi.standar.update');
    Route::delete('/standar/{standar}', [AkreditasiController::class, 'destroyStandar'])->name('akreditasi.standar.destroy');

    // EP
    Route::post('/standar/{standar}/ep', [AkreditasiController::class, 'storeEp'])->name('akreditasi.ep.store');
    Route::put('/ep/{ep}', [AkreditasiController::class, 'updateEp'])->name('akreditasi.ep.update');
    Route::delete('/ep/{ep}', [AkreditasiController::class, 'destroyEp'])->name('akreditasi.ep.destroy');

    // Documents
    Route::post('/ep/{ep}/document', [AkreditasiController::class, 'uploadDocument'])->name('akreditasi.ep.document.upload');
    Route::delete('/document/{document}', [AkreditasiController::class, 'destroyDocument'])->name('akreditasi.document.destroy');
});


Route::prefix('finance')->middleware('role:Kasir|Admin|Farmasi|Finance|Employee|Manager|Head Manager|Hrd|Marketing')->group(function () {
        Route::get('/billing', [BillingController::class, 'index'])->name('finance.billing.index');
    Route::middleware('role:Kasir|Admin|Farmasi|Finance|Employee|Manager|Head Manager|Hrd')->group(function () {
        // Riwayat Transaksi is a modal on the Billing page now; keep the old URL working
        Route::get('/transactions', fn () => redirect()->route('finance.billing.index', ['riwayat_transaksi' => 1]))->name('finance.transactions.index');
        Route::get('/transactions/data', [FinanceTransactionController::class, 'data'])->name('finance.transactions.data');
        Route::get('/transactions/stats', [FinanceTransactionController::class, 'stats'])->name('finance.transactions.stats');
        Route::get('/transactions/download', [FinanceTransactionController::class, 'downloadExcel'])->name('finance.transactions.download');
        Route::get('/transactions/backfill-change/preview', [FinanceTransactionController::class, 'previewBackfillChangeTransactions'])->name('finance.transactions.backfill.preview');
        Route::post('/transactions/backfill-change/process', [FinanceTransactionController::class, 'processBackfillChangeTransactions'])->name('finance.transactions.backfill.process');
        Route::middleware('role:Admin|Finance|Kasir')->group(function () {
            Route::get('/laporan-keuangan', [FinanceReportController::class, 'index'])->name('finance.laporan-keuangan.index');
            Route::post('/laporan-keuangan/cutoffs', [FinanceReportController::class, 'updateCutoffs'])->name('finance.laporan-keuangan.cutoffs.update');
            Route::get('/laporan-keuangan/daily-data', [FinanceReportController::class, 'dailyData'])->name('finance.laporan-keuangan.daily-data');
            Route::get('/laporan-keuangan/monthly-data', [FinanceReportController::class, 'monthlyData'])->name('finance.laporan-keuangan.monthly-data');
            Route::get('/laporan-keuangan/detail-data', [FinanceReportController::class, 'detailData'])->name('finance.laporan-keuangan.detail-data');
        });
        Route::middleware('role:Admin|Finance')->group(function () {
            Route::get('/akun', [FinanceAkunController::class, 'index'])->name('finance.akun.index');
            Route::get('/akun/data', [FinanceAkunController::class, 'data'])->name('finance.akun.data');
            Route::post('/akun', [FinanceAkunController::class, 'store'])->name('finance.akun.store');
            Route::get('/akun/{akun}', [FinanceAkunController::class, 'show'])->name('finance.akun.show');
            Route::put('/akun/{akun}', [FinanceAkunController::class, 'update'])->name('finance.akun.update');
            Route::delete('/akun/{akun}', [FinanceAkunController::class, 'destroy'])->name('finance.akun.destroy');
            Route::get('/metode-bayar', [\App\Http\Controllers\Finance\MetodeBayarController::class, 'index'])->name('finance.metode-bayar.index');
            Route::get('/metode-bayar/data', [\App\Http\Controllers\Finance\MetodeBayarController::class, 'data'])->name('finance.metode-bayar.data');
            Route::post('/metode-bayar', [\App\Http\Controllers\Finance\MetodeBayarController::class, 'store'])->name('finance.metode-bayar.store');
            Route::get('/metode-bayar/{metodeBayar}', [\App\Http\Controllers\Finance\MetodeBayarController::class, 'show'])->name('finance.metode-bayar.show');
            Route::put('/metode-bayar/{metodeBayar}', [\App\Http\Controllers\Finance\MetodeBayarController::class, 'update'])->name('finance.metode-bayar.update');
            Route::delete('/metode-bayar/{metodeBayar}', [\App\Http\Controllers\Finance\MetodeBayarController::class, 'destroy'])->name('finance.metode-bayar.destroy');
            Route::get('/jurnal', [FinanceJurnalController::class, 'index'])->name('finance.jurnal.index');
            Route::get('/jurnal/data', [FinanceJurnalController::class, 'data'])->name('finance.jurnal.data');
            Route::post('/jurnal', [FinanceJurnalController::class, 'store'])->name('finance.jurnal.store');
            Route::get('/jurnal/{jurnal}', [FinanceJurnalController::class, 'show'])->name('finance.jurnal.show');
            Route::put('/jurnal/{jurnal}', [FinanceJurnalController::class, 'update'])->name('finance.jurnal.update');
            Route::delete('/jurnal/{jurnal}', [FinanceJurnalController::class, 'destroy'])->name('finance.jurnal.destroy');
            Route::get('/revenue-targets', [\App\Http\Controllers\Finance\FinanceRevenueTargetController::class, 'index'])->name('finance.revenue-targets.index');
            Route::post('/revenue-targets', [\App\Http\Controllers\Finance\FinanceRevenueTargetController::class, 'store'])->name('finance.revenue-targets.store');
        });
        });
        Route::get('/billing/create/{visitation_id}', [BillingController::class, 'create'])->name('finance.billing.create');
        Route::post('/billing/save', [BillingController::class, 'saveBilling'])->name('finance.billing.save');
        Route::post('/billing/create-invoice', [BillingController::class, 'createInvoice'])->name('finance.billing.createInvoice');
    Route::post('/billing/receive-payment', [BillingController::class, 'receivePayment'])->name('finance.billing.receivePayment');


        Route::post('/billing', [BillingController::class, 'store'])->name('finance.billing.store');
        Route::get('/billing/{id}/edit', [BillingController::class, 'edit'])->name('finance.billing.edit');
        Route::put('/billing/{id}', [BillingController::class, 'update'])->name('finance.billing.update');
        Route::delete('/billing/{id}', [BillingController::class, 'destroy'])->name('finance.billing.destroy');
    Route::post('/billing/{id}/restore', [BillingController::class, 'restore'])->name('finance.billing.restore');
    Route::delete('/billing/{id}/force', [BillingController::class, 'forceDelete'])->name('finance.billing.forceDelete');
    // Visitation-level bulk actions from index
    Route::post('/billing/visitation/{visitation_id}/trash', [BillingController::class, 'trashByVisitation'])->name('finance.billing.trashByVisitation');
    Route::post('/billing/visitation/{visitation_id}/restore', [BillingController::class, 'restoreByVisitation'])->name('finance.billing.restoreByVisitation');
    Route::delete('/billing/visitation/{visitation_id}/force', [BillingController::class, 'forceDeleteByVisitation'])->name('finance.billing.forceDeleteByVisitation');
        Route::get('/billing/data', [BillingController::class, 'getVisitationsData'])->name('finance.billing.data');
        Route::get('/billing/tab-counts', [BillingController::class, 'getBillingTabCounts'])->name('finance.billing.tab-counts');
    // Billing -> Send notification to Farmasi
    Route::post('/send-notif-farmasi', [BillingController::class, 'sendNotifToFarmasi'])->middleware('auth');
    Route::get('/get-notif', [BillingController::class, 'getNotif'])->middleware('auth');
        // (routes duplicated above; keep single source of truth)
        Route::get('/billing/filters', [BillingController::class, 'filters'])->name('finance.billing.filters');
        // Lazy-loaded modal markup for billing index page
        Route::get('/billing/index-modals', function () {
            return view('finance.billing.partials.index-modals');
        })->name('finance.billing.index-modals');
        Route::get('/billing/gudang-data', [BillingController::class, 'getGudangData'])->name('finance.billing.gudang-data');
        // Lazy-loaded modal markup (loaded on-demand by billing create page)
        Route::get('/billing/stock-info-modal', [BillingController::class, 'stockInfoModal'])->name('finance.billing.stock-info-modal');
        // Stock modal (tindakan): load obat rows from riwayat tindakan pivot
        Route::get('/billing/riwayat-tindakan-obats', [BillingController::class, 'riwayatTindakanObats'])->name('finance.billing.riwayat-tindakan-obats');
        Route::get('/billing/tindakan-obats', [BillingController::class, 'tindakanObats'])->name('finance.billing.tindakan-obats');

        // Invoice routes
        // Route::post('/billing/create-invoice', [BillingController::class, 'createInvoice'])->name('billing.createInvoice');
        Route::get('/invoice', [InvoiceController::class, 'index'])->name('finance.invoice.index');
        Route::get('/invoice/{id}', [InvoiceController::class, 'show'])->name('finance.invoice.show');
        Route::put('/invoice/{id}/status', [InvoiceController::class, 'updateStatus'])->name('finance.invoice.updateStatus');
        Route::get('/invoice/{id}/print', [InvoiceController::class, 'printInvoice'])->name('finance.invoice.print');
        Route::get('/invoice/{id}/print-nota', [InvoiceController::class, 'printNota'])->name('finance.invoice.print-nota');
        Route::get('/invoice/{id}/print-nota-v2', [InvoiceController::class, 'printNotaV2'])->name('finance.invoice.print-nota-v2');
        Route::middleware('role:Kasir|Admin|Farmasi|Finance|Employee|Manager|Head Manager|Hrd')->group(function () {
        // Rekap Penjualan / Invoice export: "Download Invoice" modal on the Billing page (old page URLs open it)
        Route::get('/rekap-penjualan', fn () => redirect()->route('finance.billing.index', ['download_invoice' => 1]))->name('finance.rekap-penjualan.form');
        Route::get('/rekap-penjualan/download', [BillingController::class, 'downloadRekapPenjualanExcel'])->name('finance.rekap-penjualan.download');
        Route::get('/rekap-penjualan/preview', [BillingController::class, 'previewRekapPenjualan'])->name('finance.rekap-penjualan.preview');
        Route::get('/invoice-export', fn () => redirect()->route('finance.billing.index', ['download_invoice' => 1]))->name('finance.invoice.export.form');
        Route::get('/invoice-export/download', [InvoiceController::class, 'downloadInvoiceExcel'])->name('finance.invoice.export.download');
        Route::get('/invoice-export/preview', [BillingController::class, 'previewInvoiceExport'])->name('finance.invoice.export.preview');
        Route::get('/rekap-penjualan/statistik', [BillingController::class, 'statistikPendapatanAjax'])->name('finance.rekap-penjualan.statistik');
        
        // Retur Pembelian routes
        Route::get('/retur-pembelian', [\App\Http\Controllers\Finance\ReturPembelianController::class, 'index'])->name('finance.retur-pembelian.index');
        Route::post('/retur-pembelian', [\App\Http\Controllers\Finance\ReturPembelianController::class, 'store'])->name('finance.retur-pembelian.store');
        Route::get('/retur-pembelian/pending-count', [\App\Http\Controllers\Finance\ReturPembelianController::class, 'pendingCount'])->name('finance.retur-pembelian.pending-count');
        Route::post('/retur-pembelian/{id}/approve', [\App\Http\Controllers\Finance\ReturPembelianController::class, 'approve'])->name('finance.retur-pembelian.approve');
        Route::post('/retur-pembelian/{id}/reject', [\App\Http\Controllers\Finance\ReturPembelianController::class, 'reject'])->name('finance.retur-pembelian.reject');
        Route::get('/retur-pembelian/{id}', [\App\Http\Controllers\Finance\ReturPembelianController::class, 'show'])->name('finance.retur-pembelian.show');
        Route::get('/retur-pembelian/{id}/print', [\App\Http\Controllers\Finance\ReturPembelianController::class, 'print'])->name('finance.retur-pembelian.print');
        Route::get('/retur-pembelian/invoices/filter', [\App\Http\Controllers\Finance\ReturPembelianController::class, 'getInvoices'])->name('finance.retur-pembelian.invoices');
        Route::get('/retur-pembelian/invoice/{id}/items', [\App\Http\Controllers\Finance\ReturPembelianController::class, 'getInvoiceItems'])->name('finance.retur-pembelian.invoice-items');
        
    // Pengajuan paid history (AJAX DataTable)
    Route::get('/pengajuan/paid-data', [\App\Http\Controllers\Finance\FinancePengajuanDanaController::class, 'paidData'])->name('finance.pengajuan.paid.data');

    // Pengajuan Dana (AJAX + DataTables)
    Route::get('/pengajuan-dana', [\App\Http\Controllers\Finance\FinancePengajuanDanaController::class, 'index'])->name('finance.pengajuan.index');
    Route::get('/pengajuan-dana/data', [\App\Http\Controllers\Finance\FinancePengajuanDanaController::class, 'data'])->name('finance.pengajuan.data');
    Route::get('/pengajuan-dana/generate-kode', [\App\Http\Controllers\Finance\FinancePengajuanDanaController::class, 'generateKode'])->name('finance.pengajuan.generate_kode');
    Route::post('/pengajuan-dana', [\App\Http\Controllers\Finance\FinancePengajuanDanaController::class, 'store'])->name('finance.pengajuan.store');
    Route::get('/pengajuan-dana/{id}', [\App\Http\Controllers\Finance\FinancePengajuanDanaController::class, 'show'])->name('finance.pengajuan.show');
    Route::get('/pengajuan-dana/{id}/download-bukti/{index}', [\App\Http\Controllers\Finance\FinancePengajuanDanaController::class, 'downloadBukti'])->name('finance.pengajuan.download_bukti');
    Route::get('/pengajuan-dana/{id}/pdf', [\App\Http\Controllers\Finance\FinancePengajuanDanaController::class, 'pdf'])->name('finance.pengajuan.pdf');
    Route::get('/pengajuan-dana/{id}/approvals', [\App\Http\Controllers\Finance\FinancePengajuanDanaController::class, 'approvalsDetails'])->name('finance.pengajuan.approvals');
    Route::put('/pengajuan-dana/{id}', [\App\Http\Controllers\Finance\FinancePengajuanDanaController::class, 'update'])->name('finance.pengajuan.update');
    Route::post('/pengajuan-dana/{id}/approve', [\App\Http\Controllers\Finance\FinancePengajuanDanaController::class, 'approve'])->name('finance.pengajuan.approve');
    Route::post('/pengajuan-dana/bulk-approve', [\App\Http\Controllers\Finance\FinancePengajuanDanaController::class, 'bulkApprove'])->name('finance.pengajuan.bulk_approve');
    Route::post('/pengajuan-dana/{id}/pay', [\App\Http\Controllers\Finance\FinancePengajuanDanaController::class, 'markPaid'])->name('finance.pengajuan.pay');    Route::post('/pengajuan-dana/{id}/decline', [\App\Http\Controllers\Finance\FinancePengajuanDanaController::class, 'decline'])->name('finance.pengajuan.decline');
    Route::post('/pengajuan-dana/{id}/upload-bukti', [\App\Http\Controllers\Finance\FinancePengajuanDanaController::class, 'uploadBukti'])->name('finance.pengajuan.upload_bukti');
    Route::post('/pengajuan-dana/{id}/realisasi', [\App\Http\Controllers\Finance\FinancePengajuanDanaController::class, 'submitRealisasi'])->name('finance.pengajuan.realisasi');
    Route::post('/pengajuan-dana/{id}/realisasi/confirm', [\App\Http\Controllers\Finance\FinancePengajuanDanaController::class, 'confirmRealisasi'])->name('finance.pengajuan.realisasi_confirm');
    Route::delete('/pengajuan-dana/{id}', [\App\Http\Controllers\Finance\FinancePengajuanDanaController::class, 'destroy'])->name('finance.pengajuan.destroy');

    // Rekening management
    Route::get('/pengajuan-rekening', [\App\Http\Controllers\Finance\FinanceRekeningController::class, 'index'])->name('finance.rekening.index');
    Route::get('/pengajuan-rekening/data', [\App\Http\Controllers\Finance\FinanceRekeningController::class, 'data'])->name('finance.rekening.data');
    Route::post('/pengajuan-rekening', [\App\Http\Controllers\Finance\FinanceRekeningController::class, 'store'])->name('finance.rekening.store');
    Route::get('/pengajuan-rekening/{id}', [\App\Http\Controllers\Finance\FinanceRekeningController::class, 'show'])->name('finance.rekening.show');
    Route::put('/pengajuan-rekening/{id}', [\App\Http\Controllers\Finance\FinanceRekeningController::class, 'update'])->name('finance.rekening.update');
    Route::delete('/pengajuan-rekening/{id}', [\App\Http\Controllers\Finance\FinanceRekeningController::class, 'destroy'])->name('finance.rekening.destroy');

    // Approver management (Kelola Approver modal on the pengajuan page), Admin only
    Route::middleware('role:Admin')->group(function () {
        Route::get('/pengajuan-dana-approvers/data', [\App\Http\Controllers\Finance\FinanceApproverController::class, 'data'])->name('finance.pengajuan.approver.data');
        Route::post('/pengajuan-dana-approvers', [\App\Http\Controllers\Finance\FinanceApproverController::class, 'store'])->name('finance.pengajuan.approver.store');
        Route::get('/pengajuan-dana-approvers/{id}', [\App\Http\Controllers\Finance\FinanceApproverController::class, 'show'])->name('finance.pengajuan.approver.show');
        Route::put('/pengajuan-dana-approvers/{id}', [\App\Http\Controllers\Finance\FinanceApproverController::class, 'update'])->name('finance.pengajuan.approver.update');
        Route::delete('/pengajuan-dana-approvers/{id}', [\App\Http\Controllers\Finance\FinanceApproverController::class, 'destroy'])->name('finance.pengajuan.approver.destroy');
    });
    });
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
    // Select2 AJAX search for barang (must be declared before resource routes to avoid conflict with {barang} parameter)
    Route::get('/barang/search', [App\Http\Controllers\Inventory\BarangController::class, 'search'])->name('inventory.barang.search');
    Route::resource('barang', App\Http\Controllers\Inventory\BarangController::class);
        Route::post('barang/update-stok', [App\Http\Controllers\Inventory\BarangController::class, 'updateStok'])->name('inventory.barang.update-stok');
        
        // Kartu Stok
    Route::get('/kartu-stok', [App\Http\Controllers\Inventory\KartuStokController::class, 'index'])->name('inventory.kartustok.index');
    Route::get('/kartu-stok/data', [App\Http\Controllers\Inventory\KartuStokController::class, 'data'])->name('inventory.kartustok.data');
    Route::get('/kartu-stok/detail', [App\Http\Controllers\Inventory\KartuStokController::class, 'detail'])->name('inventory.kartustok.detail');
        
        // Pembelian Barang
        Route::resource('pembelian', App\Http\Controllers\Inventory\PembelianBarangController::class);
        
        // Maintenance Barang
        Route::resource('maintenance', App\Http\Controllers\Inventory\MaintenanceBarangController::class);
    }
);

Route::prefix('hrd')->middleware('role:Hrd|Manager|Head Manager|Employee|Admin|Ceo')->group(function () {
    Route::get('/dokter-schedule/print', [\App\Http\Controllers\HRD\DokterSchedulePrintController::class, 'print'])->name('hrd.dokter-schedule.print');
    // Jadwal Karyawan Print (Employee)
    Route::get('/schedule/print', [\App\Http\Controllers\HRD\EmployeeScheduleController::class, 'print'])->name('hrd.schedule.print');
    Route::post('/dokter-schedule/delete/{id}', [\App\Http\Controllers\HRD\DokterScheduleController::class, 'deleteJadwal']);

     // Jadwal Karyawan
     // ...existing code...
    Route::get('/schedule/print', [\App\Http\Controllers\HRD\EmployeeScheduleController::class, 'print'])->name('hrd.schedule.print');
    Route::get('schedule', [\App\Http\Controllers\HRD\EmployeeScheduleController::class, 'index'])->name('hrd.schedule.index');
    Route::post('schedule', [\App\Http\Controllers\HRD\EmployeeScheduleController::class, 'store'])->name('hrd.schedule.store')->middleware('role:Hrd|Admin');
    Route::post('schedule/copy-week', [\App\Http\Controllers\HRD\EmployeeScheduleController::class, 'copyWeek'])->name('hrd.schedule.copy_week')->middleware('role:Hrd|Admin');
    Route::post('schedule/delete', [\App\Http\Controllers\HRD\EmployeeScheduleController::class, 'delete'])->name('hrd.schedule.delete')->middleware('role:Hrd|Admin');
    Route::get('schedule/rekap-hari-libur', [\App\Http\Controllers\HRD\EmployeeScheduleController::class, 'rekapHariLibur'])->name('hrd.schedule.rekap_hari_libur')->middleware('role:Hrd|Admin');
    Route::get('schedule/hari-masuk-tersedia', [\App\Http\Controllers\HRD\EmployeeScheduleController::class, 'hariMasukTersedia'])->name('hrd.schedule.hari_masuk_tersedia')->middleware('role:Hrd|Admin');
    Route::get('schedule/riwayat-jatah', [\App\Http\Controllers\HRD\EmployeeScheduleController::class, 'riwayatJatah'])->name('hrd.schedule.riwayat_jatah');
    Route::post('schedule/perubahan/baca', [\App\Http\Controllers\HRD\EmployeeScheduleController::class, 'bacaPerubahan'])->name('hrd.schedule.baca_perubahan');
    Route::get('schedule/logs',[\App\Http\Controllers\HRD\EmployeeScheduleController::class, 'scheduleLogs'])->name('hrd.schedule.logs')->middleware('role:Hrd|Admin');
        Route::post('/dokter-schedule/update-jam/{id}', [\App\Http\Controllers\HRD\DokterScheduleController::class, 'updateJam']);
    // Jadwal Dokter
    Route::get('dokter-schedule', [\App\Http\Controllers\HRD\DokterScheduleController::class, 'index'])->name('hrd.dokter-schedule.index');
    Route::get('dokter-schedule/get', [\App\Http\Controllers\HRD\DokterScheduleController::class, 'getSchedules'])->name('hrd.dokter-schedule.get');
    Route::post('dokter-schedule/store-single', [\App\Http\Controllers\HRD\DokterScheduleController::class, 'storeSingle'])->name('hrd.dokter-schedule.store_single');
    Route::post('dokter-schedule/move/{id}', [\App\Http\Controllers\HRD\DokterScheduleController::class, 'moveJadwal'])->name('hrd.dokter-schedule.move');
    Route::post('dokter-schedule/store', [\App\Http\Controllers\HRD\DokterScheduleController::class, 'store'])->name('hrd.dokter-schedule.store');
    Route::post('dokter-schedule/save-week', [\App\Http\Controllers\HRD\DokterScheduleController::class, 'saveWeek'])->name('hrd.dokter-schedule.save_week');
    Route::get('dokter-schedule/view-data', [\App\Http\Controllers\HRD\DokterScheduleController::class, 'viewData'])->name('hrd.dokter-schedule.view_data');
    Route::get('schedule/view-data', [\App\Http\Controllers\HRD\EmployeeScheduleController::class, 'viewData'])->name('hrd.schedule.view_data');
    Route::post('dokter-schedule/copy-week',[\App\Http\Controllers\HRD\DokterScheduleController::class, 'copyWeek'])->name('hrd.dokter-schedule.copy_week');
    // Create or update doctor shifts (HRD)
    Route::post('dokter-shifts/store', [\App\Http\Controllers\HRD\DokterScheduleController::class, 'storeShift'])->name('hrd.dokter-shifts.store');
    Route::post('dokter-shifts/update/{id}', [\App\Http\Controllers\HRD\DokterScheduleController::class, 'updateShift'])->name('hrd.dokter-shifts.update');
        // HRD Absensi Rekap routes

    // Absensi Rekap routes (no double prefix)
    Route::get('absensi-rekap', [\App\Http\Controllers\HRD\AbsensiRekapController::class, 'index'])->name('hrd.absensi_rekap.index');
    Route::post('absensi-rekap/upload', [\App\Http\Controllers\HRD\AbsensiRekapController::class, 'upload'])->name('hrd.absensi_rekap.upload');
    Route::get('absensi-rekap/data', [\App\Http\Controllers\HRD\AbsensiRekapController::class, 'data'])->name('hrd.absensi_rekap.data');
    Route::get('absensi-rekap/employee/{employee}/details', [\App\Http\Controllers\HRD\AbsensiRekapController::class, 'employeeDetails'])->name('hrd.absensi_rekap.employee_details');
    Route::get('absensi-rekap/statistics', [\App\Http\Controllers\HRD\AbsensiRekapController::class, 'statistics'])->name('hrd.absensi_rekap.statistics');
    Route::post('absensi-rekap/{id}/ignore-flags', [\App\Http\Controllers\HRD\AbsensiRekapController::class, 'updateIgnoreFlags'])->name('hrd.absensi_rekap.ignore_flags');
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
        Route::post('tidakmasuk', [\App\Http\Controllers\HRD\PengajuanTidakMasukController::class, 'store'])->name('hrd.tidakmasuk.store');
        Route::get('tidakmasuk/{id}', [\App\Http\Controllers\HRD\PengajuanTidakMasukController::class, 'show'])->name('hrd.tidakmasuk.show');
        Route::get('tidakmasuk/{id}/approval-status', [\App\Http\Controllers\HRD\PengajuanTidakMasukController::class, 'getApprovalStatus']);
        Route::put('tidakmasuk/{id}/manager', [\App\Http\Controllers\HRD\PengajuanTidakMasukController::class, 'persetujuanManager']);
        Route::put('tidakmasuk/{id}/hrd', [\App\Http\Controllers\HRD\PengajuanTidakMasukController::class, 'persetujuanHRD']);
        
        // Pengajuan Ganti Shift
        Route::get('gantishift/available-shifts', [\App\Http\Controllers\HRD\PengajuanGantiShiftController::class, 'getAvailableShifts'])->name('hrd.gantishift.available-shifts');
        Route::get('gantishift/same-shift-employees', [\App\Http\Controllers\HRD\PengajuanGantiShiftController::class, 'getEmployeesSameShift'])->name('hrd.gantishift.same-shift-employees');
        Route::get('gantishift', [\App\Http\Controllers\HRD\PengajuanGantiShiftController::class, 'index'])->name('hrd.gantishift.index');
        Route::post('gantishift', [\App\Http\Controllers\HRD\PengajuanGantiShiftController::class, 'store'])->name('hrd.gantishift.store');
        Route::get('gantishift/{id}', [\App\Http\Controllers\HRD\PengajuanGantiShiftController::class, 'show'])->name('hrd.gantishift.show');
        Route::get('gantishift/{id}/approval-status', [\App\Http\Controllers\HRD\PengajuanGantiShiftController::class, 'getApprovalStatus']);
        Route::put('gantishift/{id}/manager', [\App\Http\Controllers\HRD\PengajuanGantiShiftController::class, 'persetujuanManager'])->name('hrd.gantishift.manager');
        Route::put('gantishift/{id}/hrd', [\App\Http\Controllers\HRD\PengajuanGantiShiftController::class, 'persetujuanHRD'])->name('hrd.gantishift.hrd');
        Route::put('gantishift/{id}/target-approval', [\App\Http\Controllers\HRD\PengajuanGantiShiftController::class, 'targetEmployeeApproval'])->name('hrd.gantishift.target-approval');

        // Shift Management (used from Jadwal Karyawan page)
        Route::prefix('master/shift')->name('hrd.master.shift.')->middleware('role:Hrd|Admin')->group(function () {
            Route::post('/', [\App\Http\Controllers\HRD\ShiftController::class, 'store'])->name('store');
            Route::put('/{shift}', [\App\Http\Controllers\HRD\ShiftController::class, 'update'])->name('update');
            Route::delete('/{shift}', [\App\Http\Controllers\HRD\ShiftController::class, 'destroy'])->name('destroy');
        });
        
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
            Route::get('/form-options', [App\Http\Controllers\HRD\PositionMasterController::class, 'formOptions'])->name('form-options');
            Route::post('/', [App\Http\Controllers\HRD\PositionMasterController::class, 'store'])->name('store');
            Route::get('/{id}', [App\Http\Controllers\HRD\PositionMasterController::class, 'show'])->name('show');
            Route::put('/{id}', [App\Http\Controllers\HRD\PositionMasterController::class, 'update'])->name('update');
            Route::delete('/{id}', [App\Http\Controllers\HRD\PositionMasterController::class, 'destroy'])->name('destroy');
        });

        // Libur Nasional (holidays earn jatah ganti libur when scheduled)
        Route::prefix('master/libur-nasional')->name('hrd.master.libur-nasional.')->middleware('role:Hrd|Admin')->group(function () {
            Route::get('/', [App\Http\Controllers\HRD\LiburNasionalController::class, 'index'])->name('index');
            Route::post('/', [App\Http\Controllers\HRD\LiburNasionalController::class, 'store'])->name('store');
            Route::put('/{id}', [App\Http\Controllers\HRD\LiburNasionalController::class, 'update'])->name('update');
            Route::delete('/{id}', [App\Http\Controllers\HRD\LiburNasionalController::class, 'destroy'])->name('destroy');
        });

        // Jatah Libur: HRD actions used from the schedule page (saldo & riwayat are shown there)
        Route::prefix('master/jatah-libur')->name('hrd.master.jatah-libur.')->middleware('role:Hrd|Admin')->group(function () {
            Route::post('/reset-annual', [App\Http\Controllers\HRD\JatahLiburController::class, 'resetAnnualLeave'])->name('reset_annual');
            Route::get('/leave-capacity', [App\Http\Controllers\HRD\JatahLiburController::class, 'getLeaveCapacity'])->name('leave_capacity.get');
            Route::post('/leave-capacity', [App\Http\Controllers\HRD\JatahLiburController::class, 'updateLeaveCapacity'])->name('leave_capacity.update');
            Route::post('/pasangkan-otomatis', [App\Http\Controllers\HRD\JatahLiburController::class, 'pasangkanOtomatis'])->name('pasangkan_otomatis');
            Route::post('/{employee}/jadikan-ganti-libur', [App\Http\Controllers\HRD\JatahLiburController::class, 'jadikanGantiLibur'])->name('jadikan_ganti_libur');
            Route::put('/{employee}/cuti',[App\Http\Controllers\HRD\JatahLiburController::class, 'updateCuti'])->name('cuti.update');
            Route::put('/{employee}/hari-masuk', [App\Http\Controllers\HRD\JatahLiburController::class, 'updateHariMasuk'])->name('hari-masuk.update');
        });

        // Employee Management Routes: Ceo / Head Manager may view (same audience as the "Data Pegawai" menu),
        // only Hrd / Admin may change data
        Route::middleware('role:Hrd|Admin|Ceo|Head Manager')->group(function () {
            Route::get('/employee', [EmployeeController::class, 'index'])->name('hrd.employee.index');
            Route::get('/employee/export', [EmployeeController::class, 'export'])->name('hrd.employee.export');
            Route::get('/employee/{id}/get-details', [EmployeeController::class, 'getDetails'])->name('hrd.employee.get-details');
            Route::get('/employee/{id}/logs', [EmployeeController::class, 'logs'])->name('hrd.employee.logs');
            Route::get('/employee/{employeeId}/contracts', [EmployeeContractController::class, 'index'])->name('hrd.employee.contracts.index');
        });
        Route::middleware('role:Hrd|Admin')->group(function () {
            Route::get('/employee/create', [EmployeeController::class, 'create'])->name('hrd.employee.create');
            Route::post('/employee', [EmployeeController::class, 'store'])->name('hrd.employee.store');
            Route::get('/employee/{id}/edit', [EmployeeController::class, 'edit'])->name('hrd.employee.edit');
            Route::put('/employee/{id}', [EmployeeController::class, 'update'])->name('hrd.employee.update');
            Route::get('/employee/users-search', [EmployeeController::class, 'searchUsers'])->name('hrd.employee.users-search');
            // No delete: employees are referenced by absensi, slip gaji, jadwal, ...; status Tidak Aktif instead
            Route::post('/employee/{employeeId}/contracts', [EmployeeContractController::class, 'store'])->name('hrd.employee.contracts.store');
            Route::post('/employee/{employeeId}/contracts/{contractId}/terminate', [EmployeeContractController::class, 'terminate'])->name('hrd.employee.contracts.terminate');
        });
        Route::get('/employee/{id}', [EmployeeController::class, 'show'])->name('hrd.employee.show'); // redirect to the list

        // Dokter Management: same access as Data Karyawan (Ceo / Head Manager view only)
        Route::middleware('role:Hrd|Admin|Ceo|Head Manager')->group(function () {
            Route::get('/dokters', [\App\Http\Controllers\ERM\DokterController::class, 'index'])->name('hrd.dokters.index');
            Route::get('/dokters/export', [\App\Http\Controllers\ERM\DokterController::class, 'export'])->name('hrd.dokters.export');
        });
        Route::middleware('role:Hrd|Admin')->group(function () {
            Route::get('/dokters/create', [\App\Http\Controllers\ERM\DokterController::class, 'create'])->name('hrd.dokters.create');
            Route::get('/dokters/{id}/edit', [\App\Http\Controllers\ERM\DokterController::class, 'edit'])->name('hrd.dokters.edit');
            Route::post('/dokters', [\App\Http\Controllers\ERM\DokterController::class, 'store'])->name('hrd.dokters.store');
            Route::post('/dokters/{id}/status', [\App\Http\Controllers\ERM\DokterController::class, 'updateStatus'])->name('hrd.dokters.status');
            Route::delete('/dokters/{id}', [\App\Http\Controllers\ERM\DokterController::class, 'destroy'])->name('hrd.dokters.destroy');
        });

        // Employee Self Service Routes

        Route::get('/profile', [EmployeeSelfServiceController::class, 'profile'])->name('hrd.employee.profile');

        Route::get('/profile/edit-modal', [EmployeeSelfServiceController::class, 'getEditProfileModal'])->name('hrd.employee.profile.modal');
        Route::put('/profile', [EmployeeSelfServiceController::class, 'updateProfile'])->name('hrd.employee.profile.update');

        Route::put('/profile/password', [EmployeeSelfServiceController::class, 'updatePassword'])->name('hrd.employee.password.update');

        
        // Division routes (for managers)
        Route::get('/my-division', [DivisionController::class, 'showMyDivision'])->name('hrd.division.mine');
        Route::get('/my-team', [DivisionController::class, 'showMyTeam'])->name('hrd.division.team');

        // JobList Module (CRUD via AJAX + Datatables)
        Route::get('/joblist', [JobListController::class, 'index'])->name('hrd.joblist.index');
        // New dashboard view for JobList stats
        Route::get('/joblist/dashboard', [JobListController::class, 'dashboard'])->name('hrd.joblist.dashboard');
        Route::get('/joblist/data', [JobListController::class, 'data'])->name('hrd.joblist.data');
        Route::get('/joblist/summary', [JobListController::class, 'summary'])->name('hrd.joblist.summary');
        // Serve uploaded joblist documents via controller to ensure proper access and avoid webserver forbidden errors
        Route::get('/joblist/{id}/document/{index}', [JobListController::class, 'downloadDocument'])->name('hrd.joblist.document');
        Route::post('/joblist/{id}/upload-documents', [JobListController::class, 'uploadDocuments'])->name('hrd.joblist.upload_documents');
        Route::post('/joblist/{id}/notes', [JobListController::class, 'saveNotes'])->name('hrd.joblist.save_notes');
        Route::post('/joblist', [JobListController::class, 'store'])->name('hrd.joblist.store');
        Route::get('/joblist/{id}', [JobListController::class, 'show'])->name('hrd.joblist.show');
        Route::post('/joblist/{id}', [JobListController::class, 'update'])->name('hrd.joblist.update');
        // Inline update (used by DataTable inline controls)
        Route::post('/joblist/{id}/inline-update', [JobListController::class, 'inlineUpdate'])->name('hrd.joblist.inline_update');
        Route::delete('/joblist/{id}', [JobListController::class, 'destroy'])->name('hrd.joblist.destroy');
        // Mark as read (dibaca)
        Route::post('/joblist/{id}/dibaca', [JobListController::class, 'markRead'])->name('hrd.joblist.mark_read');

        Route::prefix('libur')->name('hrd.libur.')->middleware(['auth'])->group(function () {
                Route::get('/', [PengajuanLiburController::class, 'index'])->name('index');
                Route::post('/', [PengajuanLiburController::class, 'store'])->name('store');
                // Must be registered before /{id}, otherwise "check-capacity" is captured as an id
                Route::get('/check-capacity', [PengajuanLiburController::class, 'checkCapacity'])->name('check_capacity');
                Route::get('/hari-masuk-tersedia', [PengajuanLiburController::class, 'hariMasukTersedia'])->name('hari_masuk_tersedia');
                Route::get('/{id}', [PengajuanLiburController::class, 'show'])->name('show');
                Route::get('/{id}/approval-status', [PengajuanLiburController::class, 'getApprovalStatus'])->name('approval.status');
                Route::put('/{id}/manager', [PengajuanLiburController::class, 'persetujuanManager'])->name('manager.approve');
                Route::put('/{id}/hrd', [PengajuanLiburController::class, 'persetujuanHRD'])->name('hrd.approve');
            });

    }
);

Route::prefix('marketing')->middleware('role:Marketing|Admin|Beautician|Finance|Perawat|Pendaftaran')->group(function () {

    // // Main dashboard and analytics
    Route::get('/', [MarketingController::class, 'dashboard'])->name('marketing.dashboard');
    Route::get('/dashboard', [MarketingController::class, 'dashboard'])->name('marketing.dashboard');

    // Birthday patient greetings
    Route::get('/birthday', [BirthdayController::class, 'index'])->name('marketing.birthday.index');
    Route::get('/birthday/data', [BirthdayController::class, 'getData'])->name('marketing.birthday.data');
    Route::post('/birthday/mark-sent', [BirthdayController::class, 'markAsSent'])->name('marketing.birthday.mark-sent');
    Route::post('/birthday/generate-image', [BirthdayController::class, 'generateImage'])->name('marketing.birthday.generate-image');
    Route::get('/birthday/image/{filename}', [BirthdayController::class, 'showImage'])->name('marketing.birthday.show-image');

    // Marketing Events moved to the Events module (main menu)
    Route::get('/events', fn () => redirect()->route('events.index'))->name('marketing.events.index');

    // Content Plan: status list modal data endpoint
    Route::get('/content-plan/status-list', [\App\Http\Controllers\Marketing\ContentPlanController::class, 'statusList'])
        ->name('marketing.content-plan.status_list');

    // Master Merchandise (Marketing)
    Route::get('/master-merchandise', [\App\Http\Controllers\Marketing\MasterMerchandiseController::class, 'index'])->name('marketing.master_merchandise.index');
    Route::get('/master-merchandise/data', [\App\Http\Controllers\Marketing\MasterMerchandiseController::class, 'data'])->name('marketing.master_merchandise.data');
    Route::get('/master-merchandise/{id}/stock-history', [\App\Http\Controllers\Marketing\MasterMerchandiseController::class, 'stockHistory'])->name('marketing.master_merchandise.stock_history');
    Route::post('/master-merchandise', [\App\Http\Controllers\Marketing\MasterMerchandiseController::class, 'store'])->name('marketing.master_merchandise.store');
    Route::post('/master-merchandise/{id}/add-stock', [\App\Http\Controllers\Marketing\MasterMerchandiseController::class, 'addStock'])->name('marketing.master_merchandise.add_stock');
    Route::get('/master-merchandise/{id}/edit', [\App\Http\Controllers\Marketing\MasterMerchandiseController::class, 'edit']);
    Route::put('/master-merchandise/{id}', [\App\Http\Controllers\Marketing\MasterMerchandiseController::class, 'update']);
    Route::delete('/master-merchandise/{id}', [\App\Http\Controllers\Marketing\MasterMerchandiseController::class, 'destroy']);
    Route::get('/biaya-konsultasi', [\App\Http\Controllers\Marketing\KonsultasiMasterController::class, 'index'])->name('marketing.konsultasi.index');
    Route::post('/biaya-konsultasi', [\App\Http\Controllers\Marketing\KonsultasiMasterController::class, 'store'])->name('marketing.konsultasi.store');
    Route::put('/biaya-konsultasi/{konsultasi}', [\App\Http\Controllers\Marketing\KonsultasiMasterController::class, 'update'])->name('marketing.konsultasi.update');
    Route::delete('/biaya-konsultasi/{konsultasi}', [\App\Http\Controllers\Marketing\KonsultasiMasterController::class, 'destroy'])->name('marketing.konsultasi.destroy');
    // AJAX search for kode tindakan (for Select2 in tindakan modal)
    Route::get('kodetindakan/search', [\App\Http\Controllers\Marketing\KodeTindakanController::class, 'search'])->name('marketing.kode_tindakan.search');

    // Kode Tindakan CRUD & DataTable
    Route::get('/kodetindakan', [\App\Http\Controllers\Marketing\KodeTindakanController::class, 'index'])->name('marketing.kode_tindakan.index');
    Route::get('/kodetindakan/data', [\App\Http\Controllers\Marketing\KodeTindakanController::class, 'data'])->name('marketing.kode_tindakan.data');
    Route::post('/kodetindakan', [\App\Http\Controllers\Marketing\KodeTindakanController::class, 'store']);
    Route::post('/kodetindakan/action/make-all-inactive', [\App\Http\Controllers\Marketing\KodeTindakanController::class, 'makeAllInactive']);
    Route::post('/kodetindakan/action/make-all-active', [\App\Http\Controllers\Marketing\KodeTindakanController::class, 'makeAllActive']);
    Route::get('/kodetindakan/{id}', [\App\Http\Controllers\Marketing\KodeTindakanController::class, 'show']);
    Route::put('/kodetindakan/{id}', [\App\Http\Controllers\Marketing\KodeTindakanController::class, 'update']);
    Route::delete('/kodetindakan/{id}', [\App\Http\Controllers\Marketing\KodeTindakanController::class, 'destroy']);
    // AJAX: Get obats for kode tindakan (for tindakan modal)
    Route::get('/kodetindakan/{id}/obats', [App\Http\Controllers\Marketing\KodeTindakanController::class, 'getObats']);

    
    
    // Analytics pages  
    Route::get('/revenue', [MarketingController::class, 'revenue'])->name('marketing.revenue');
    Route::get('/patients', [MarketingController::class, 'patients'])->name('marketing.patients');
    // Social Media analytics (new)
    Route::get('/social-media-analytics', [\App\Http\Controllers\Marketing\SocialMediaAnalyticsController::class, 'index'])->name('marketing.social-analytics.index');
    Route::get('/social-media-analytics/data', [\App\Http\Controllers\Marketing\SocialMediaAnalyticsController::class, 'data'])->name('marketing.social-analytics.data');
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

    // Before After Gallery
    Route::get('/before-after-gallery', [App\Http\Controllers\Marketing\BeforeAfterGalleryController::class, 'index'])->name('marketing.before_after_gallery.index');
    Route::get('/before-after-gallery/search', [App\Http\Controllers\Marketing\BeforeAfterGalleryController::class, 'search'])->name('marketing.before_after_gallery.search');
    Route::get('/before-after-gallery/pasien/search', [App\Http\Controllers\Marketing\BeforeAfterGalleryController::class, 'pasienSearch'])->name('marketing.before_after_gallery.pasien.search');
    
    // Tindakan Management
    Route::get('/tindakan', [App\Http\Controllers\Marketing\TindakanController::class, 'index'])->name('marketing.tindakan.index');
    Route::get('/tindakan/data', [App\Http\Controllers\Marketing\TindakanController::class, 'getTindakanData'])->name('marketing.tindakan.data');
    Route::post('/tindakan/import', [App\Http\Controllers\Marketing\TindakanController::class, 'importCsv']);
    Route::get('/tindakan/list', [App\Http\Controllers\Marketing\TindakanController::class, 'getTindakanList'])->name('marketing.tindakan.list');
    Route::post('/tindakan', [App\Http\Controllers\Marketing\TindakanController::class, 'store'])->name('marketing.tindakan.store');
    Route::post('/tindakan/import-relations', [App\Http\Controllers\Marketing\TindakanController::class, 'importRelationsCsv']);
    Route::get('/tindakan/by-date', [App\Http\Controllers\Marketing\TindakanController::class, 'getByDate']);
    Route::post('/tindakan/action/bulk-set-active', [App\Http\Controllers\Marketing\TindakanController::class, 'bulkSetActive']);
    Route::get('/tindakan/{id}', [App\Http\Controllers\Marketing\TindakanController::class, 'getTindakan']);
    Route::delete('/tindakan/{id}', [App\Http\Controllers\Marketing\TindakanController::class, 'destroy']);

    // Bulk active/inactive actions for tindakan
    Route::post('/tindakan/action/make-all-inactive', [App\Http\Controllers\Marketing\TindakanController::class, 'makeAllInactive']);
    Route::post('/tindakan/action/make-all-active', [App\Http\Controllers\Marketing\TindakanController::class, 'makeAllActive']);
    // Toggle single tindakan active state
    Route::post('/tindakan/{id}/toggle-active', [App\Http\Controllers\Marketing\TindakanController::class, 'toggleActive']);

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

    // Content Plan Management
    Route::get('content-plan', [\App\Http\Controllers\Marketing\ContentPlanController::class, 'index'])->name('marketing.content-plan.index');
    Route::get('content-plan/week', [\App\Http\Controllers\Marketing\ContentPlanController::class, 'week'])->name('marketing.content-plan.week');
    Route::get('content-plan/{id}', [\App\Http\Controllers\Marketing\ContentPlanController::class, 'show'])->name('marketing.content-plan.show');
    Route::post('content-plan', [\App\Http\Controllers\Marketing\ContentPlanController::class, 'store'])->name('marketing.content-plan.store');
    Route::put('content-plan/{id}', [\App\Http\Controllers\Marketing\ContentPlanController::class, 'update'])->name('marketing.content-plan.update');
    // Inline update for specific fields (brand/platform/jenis_konten/status)
    Route::post('content-plan/{id}/inline-update', [\App\Http\Controllers\Marketing\ContentPlanController::class, 'inlineUpdate'])->name('marketing.content-plan.inline-update');
    Route::delete('content-plan/{id}', [\App\Http\Controllers\Marketing\ContentPlanController::class, 'destroy'])->name('marketing.content-plan.destroy');

    // Content List Management
    Route::get('content-list/datatable', [\App\Http\Controllers\Marketing\ContentListController::class, 'datatable'])->name('marketing.content-list.datatable');
    Route::get('content-list/stats', [\App\Http\Controllers\Marketing\ContentListController::class, 'stats'])->name('marketing.content-list.stats');
    Route::get('content-list/{id}', [\App\Http\Controllers\Marketing\ContentListController::class, 'show'])->name('marketing.content-list.show');
    Route::get('content-list/{id}/schedule-payload', [\App\Http\Controllers\Marketing\ContentListController::class, 'schedulePayload'])->name('marketing.content-list.schedule-payload');
    Route::post('content-list', [\App\Http\Controllers\Marketing\ContentListController::class, 'store'])->name('marketing.content-list.store');
    Route::put('content-list/{id}', [\App\Http\Controllers\Marketing\ContentListController::class, 'update'])->name('marketing.content-list.update');
    Route::post('content-list/{id}/approve', [\App\Http\Controllers\Marketing\ContentListController::class, 'approve'])->name('marketing.content-list.approve');
    Route::delete('content-list/{id}', [\App\Http\Controllers\Marketing\ContentListController::class, 'destroy'])->name('marketing.content-list.destroy');

    // Content Report
    Route::get('content-report', [\App\Http\Controllers\Marketing\ContentReportController::class, 'index'])->name('marketing.content-report.index');
    Route::post('content-report', [\App\Http\Controllers\Marketing\ContentReportController::class, 'store'])->name('marketing.content-report.store');
    Route::get('content-report/by-plan/{id}', [\App\Http\Controllers\Marketing\ContentReportController::class, 'byPlan'])->name('marketing.content-report.by-plan');
    Route::get('content-report/{id}', [\App\Http\Controllers\Marketing\ContentReportController::class, 'show'])->name('marketing.content-report.show');
    Route::put('content-report/{id}', [\App\Http\Controllers\Marketing\ContentReportController::class, 'update'])->name('marketing.content-report.update');

    // Hari Penting (Important Days) Calendar
    Route::get('hari-penting', [\App\Http\Controllers\Marketing\HariPentingController::class, 'index'])->name('marketing.hari-penting.index');
    Route::get('hari-penting/events', [\App\Http\Controllers\Marketing\HariPentingController::class, 'events'])->name('marketing.hari-penting.events');
    Route::post('hari-penting/store', [\App\Http\Controllers\Marketing\HariPentingController::class, 'store'])->name('marketing.hari-penting.store');
    Route::delete('hari-penting/{id}', [\App\Http\Controllers\Marketing\HariPentingController::class, 'destroy'])->name('marketing.hari-penting.destroy');

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

    // Content Briefs - store via AJAX from modal
    Route::post('content-brief', [\App\Http\Controllers\Marketing\ContentBriefController::class, 'store'])->name('marketing.content-brief.store');
    Route::get('content-brief/by-plan/{id}', [\App\Http\Controllers\Marketing\ContentBriefController::class, 'latestByPlan'])->name('marketing.content-brief.latest-by-plan');
    
    // Kunjungan Marketing CRUD
    Route::get('/kunjungan', [\App\Http\Controllers\Marketing\KunjunganMarketingController::class, 'index'])->name('marketing.kunjungan.index');
    Route::get('/kunjungan/data', [\App\Http\Controllers\Marketing\KunjunganMarketingController::class, 'data'])->name('marketing.kunjungan.data');
    Route::post('/kunjungan', [\App\Http\Controllers\Marketing\KunjunganMarketingController::class, 'store'])->name('marketing.kunjungan.store');
    Route::get('/kunjungan/{id}', [\App\Http\Controllers\Marketing\KunjunganMarketingController::class, 'show'])->name('marketing.kunjungan.show');
    Route::put('/kunjungan/{id}', [\App\Http\Controllers\Marketing\KunjunganMarketingController::class, 'update'])->name('marketing.kunjungan.update');
    Route::delete('/kunjungan/{id}', [\App\Http\Controllers\Marketing\KunjunganMarketingController::class, 'destroy'])->name('marketing.kunjungan.destroy');
    
    // Promo management (Marketing)
    Route::get('/promo', [PromoController::class, 'index'])->name('marketing.promo.index');
    Route::get('/promo/data', [PromoController::class, 'data'])->name('marketing.promo.data');
    Route::get('/promo/check-item-conflict', [PromoController::class, 'checkItemConflict'])->name('marketing.promo.check-item-conflict');
    Route::post('/promo', [PromoController::class, 'store'])->name('marketing.promo.store');
    Route::get('/promo/{promo}', [PromoController::class, 'show']);
    Route::put('/promo/{promo}', [PromoController::class, 'update'])->name('marketing.promo.update');
    Route::delete('/promo/{promo}', [PromoController::class, 'destroy'])->name('marketing.promo.destroy');
});


Route::prefix('workdoc/insiden')->middleware('role:Hrd|Manager|Head Manager|Employee|Admin')->group(function () {

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
// AJAX route for full services list (for modal / export)
Route::get('/marketing/services-analytics-all', [\App\Http\Controllers\Marketing\MarketingController::class, 'servicesAnalyticsAllData'])->name('marketing.services.analytics.all');

// AJAX route for products analytics charts
Route::get('/marketing/products-analytics-data', [\App\Http\Controllers\Marketing\MarketingController::class, 'productsAnalyticsData'])->name('marketing.products.analytics.data');
// AJAX route for full products list (for modal / export)
Route::get('/marketing/products-analytics-all', [\App\Http\Controllers\Marketing\MarketingController::class, 'productsAnalyticsAllData'])->name('marketing.products.analytics.all');

// (moved) HRD Libur capacity check route is defined under the HRD libur group below

// AJAX route for revenue analytics charts
Route::get('/marketing/revenue-analytics-data', [\App\Http\Controllers\Marketing\MarketingController::class, 'getRevenueData'])->name('marketing.revenue.analytics.data');
Route::get('/marketing/revenue-analytics/treatments/{treatmentId}/visitations', [\App\Http\Controllers\Marketing\MarketingController::class, 'getTreatmentVisitations'])->name('marketing.revenue.analytics.treatment-visitations');

// AJAX route for patient analytics charts
Route::get('/marketing/analytics/patients-data', [\App\Http\Controllers\Marketing\MarketingController::class, 'patientsAnalyticsData'])->name('marketing.patients.analytics.data');

// AJAX route for clinics data
Route::get('/marketing/clinics', [\App\Http\Controllers\Marketing\MarketingController::class, 'getClinics'])->name('marketing.clinics');

// AJAX route for new patients list (modal DataTable)
Route::get('/marketing/patients/new-list', [\App\Http\Controllers\Marketing\MarketingController::class, 'newPatientsList'])->name('marketing.patients.new_list');

Route::prefix('indicator')->name('indicator.')->middleware(['auth', 'role:Employee|Manager|Hrd|Ceo|Head Manager|Admin'])->group(function () {
    Route::get('/', [IndicatorController::class, 'index'])->name('index');
    Route::get('/meta', [IndicatorController::class, 'meta'])->name('meta');

    Route::get('/categories/data', [IndicatorController::class, 'categoryData'])->name('categories.data');
    Route::get('/categories/total', [IndicatorController::class, 'categoryTotal'])->name('categories.total');
    Route::post('/categories', [IndicatorController::class, 'storeCategory'])->name('categories.store');
    Route::get('/categories/{category}', [IndicatorController::class, 'showCategory'])->name('categories.show');
    Route::get('/categories/{category}/shared-indicators', [IndicatorController::class, 'sharedIndicators'])->name('categories.shared-indicators');
    Route::post('/categories/{category}/shared-indicators', [IndicatorController::class, 'sharedIndicatorsSave'])->name('categories.shared-indicators.save');
    Route::put('/categories/{category}', [IndicatorController::class, 'updateCategory'])->name('categories.update');
    Route::delete('/categories/{category}', [IndicatorController::class, 'destroyCategory'])->name('categories.destroy');

    Route::get('/indicators/data', [IndicatorController::class, 'indicatorData'])->name('indicators.data');
    Route::get('/indicators/reset-preview', [IndicatorController::class, 'resetIndicatorsPreview'])->middleware('role:Hrd|Admin')->name('indicators.reset-preview');
    Route::post('/indicators/reset', [IndicatorController::class, 'resetIndicators'])->middleware('role:Hrd|Admin')->name('indicators.reset');
    Route::post('/indicators', [IndicatorController::class, 'storeIndicator'])->name('indicators.store');
    Route::get('/indicators/{indicator}', [IndicatorController::class, 'showIndicator'])->name('indicators.show');
    Route::put('/indicators/{indicator}', [IndicatorController::class, 'updateIndicator'])->name('indicators.update');
    Route::delete('/indicators/{indicator}', [IndicatorController::class, 'destroyIndicator'])->name('indicators.destroy');
    Route::get('/positions/{position}/editor', [IndicatorController::class, 'positionEditor'])->name('positions.editor');
    Route::get('/positions/{position}/mappings', [IndicatorController::class, 'positionMappings'])->name('positions.mappings');
    Route::post('/positions/{position}/mappings', [IndicatorController::class, 'positionMappingsUpdate'])->name('positions.mappings.update');
    Route::post('/positions/{position}/mappings/bulk', [IndicatorController::class, 'positionMappingsBulkUpdate'])->name('positions.mappings.bulk-update');
    Route::post('/positions/{position}/indicators', [IndicatorController::class, 'positionIndicatorsSave'])->name('positions.indicators.save');
    Route::get('/positions/data', [IndicatorController::class, 'positionData'])->name('positions.data');
    Route::post('/import/preview', [IndicatorController::class, 'importPreview'])->name('import.preview');
    Route::post('/import/commit', [IndicatorController::class, 'importCommit'])->name('import.commit');
});

// KPI Periods
Route::prefix('kpi/periods')->name('kpi.periods.')->middleware(['auth', 'role:Employee|Manager|Hrd|Ceo|Head Manager|Admin'])->group(function () {
    Route::get('/', [KpiPeriodController::class, 'index'])->name('index');
    Route::get('/data', [KpiPeriodController::class, 'data'])->name('data');
    Route::get('/{period}', [KpiPeriodController::class, 'show'])->name('show');
    Route::post('/', [KpiPeriodController::class, 'store'])->name('store');
    Route::put('/{period}', [KpiPeriodController::class, 'update'])->name('update');
    Route::delete('/{period}', [KpiPeriodController::class, 'destroy'])->name('destroy');
    Route::get('/{period}/start/preview', [KpiPeriodController::class, 'previewStart'])->name('start.preview');
    Route::get('/{period}/details', [KpiPeriodController::class, 'details'])->name('details');
    Route::post('/{period}/start', [KpiPeriodController::class, 'startAssessment'])->name('start');
    Route::post('/{period}/open', [KpiPeriodController::class, 'openPeriod'])->name('open');
    Route::post('/{period}/close', [KpiPeriodController::class, 'closePeriod'])->name('close');
});

// My evaluatees - list pending assessments for logged-in employee
Route::get('/kpi/evaluatees', [\App\Http\Controllers\KPI\EvaluateeController::class, 'index'])
    ->name('kpi.evaluatees.index')
    ->middleware(['auth']);
Route::get('/kpi/evaluatees/data', [\App\Http\Controllers\KPI\EvaluateeController::class, 'data'])
    ->name('kpi.evaluatees.data')
    ->middleware(['auth']);

// KPI assessments (KPI module) - fill and submit routes (used by Evaluatees modal)
Route::get('/kpi/assessments/{assessment}', [\App\Http\Controllers\KPI\KpiAssessmentController::class, 'fill'])
    ->name('kpi.kpi_assessments.fill')
    ->middleware(['auth']);
Route::post('/kpi/assessments/{assessment}/submit', [\App\Http\Controllers\KPI\KpiAssessmentController::class, 'submit'])
    ->name('kpi.kpi_assessments.submit')
    ->middleware(['auth']);

Route::prefix('admin')->middleware(['auth', 'role:Admin'])->group(function () {
// Route::prefix('admin')->group(
    // function () {
        //User Management
        Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');
        Route::get('/users/create', fn () => redirect()->route('admin.users.index'));
        Route::get('/users/{id}/edit', fn () => redirect()->route('admin.users.index'));
        Route::post('/users', [UserController::class, 'store'])->name('admin.users.store');
        Route::get('/users/{id}', [UserController::class, 'show'])->whereNumber('id')->name('admin.users.show');
        Route::put('/users/{id}', [UserController::class, 'update'])->name('admin.users.update');
        Route::post('/users/{id}/toggle-active', [UserController::class, 'toggleActive'])->name('admin.users.toggle_active');
        Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('admin.users.destroy');

        // ICD10 management
        Route::get('/icd10', [\App\Http\Controllers\Admin\Icd10Controller::class, 'index'])->name('admin.icd10.index');
        Route::post('/icd10', [\App\Http\Controllers\Admin\Icd10Controller::class, 'store'])->name('admin.icd10.store');
        Route::get('/icd10/{id}', [\App\Http\Controllers\Admin\Icd10Controller::class, 'show'])->name('admin.icd10.show');
        Route::put('/icd10/{id}', [\App\Http\Controllers\Admin\Icd10Controller::class, 'update'])->name('admin.icd10.update');
        Route::delete('/icd10/{id}', [\App\Http\Controllers\Admin\Icd10Controller::class, 'destroy'])->name('admin.icd10.destroy');

        // Klinik settings
        Route::get('/klinik-settings', [\App\Http\Controllers\Admin\KlinikSettingController::class, 'index'])->name('admin.klinik_settings.index');
        Route::post('/klinik-settings', [\App\Http\Controllers\Admin\KlinikSettingController::class, 'store'])->name('admin.klinik_settings.store');
        Route::get('/klinik-settings/{id}', [\App\Http\Controllers\Admin\KlinikSettingController::class, 'show'])->name('admin.klinik_settings.show');
        Route::put('/klinik-settings/{id}', [\App\Http\Controllers\Admin\KlinikSettingController::class, 'update'])->name('admin.klinik_settings.update');
        Route::delete('/klinik-settings/{id}', [\App\Http\Controllers\Admin\KlinikSettingController::class, 'destroy'])->name('admin.klinik_settings.destroy');

        // Obat & Gudang Mapping (CRUD endpoints remain under erm.obat-mapping.* / erm.gudang-mapping.*)
        Route::get('/obat-gudang-mapping', [\App\Http\Controllers\Admin\ObatGudangMappingController::class, 'index'])->name('admin.obat_gudang_mapping.index');

        //Role Management
        Route::get('/roles', [RoleController::class, 'index'])->name('admin.roles.index');
        Route::post('/roles', [RoleController::class, 'store'])->name('admin.roles.store');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('admin.roles.destroy');
            // Admin dashboard
            Route::get('/', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'index'])->name('admin.dashboard');
            // Activity data for dashboard chart
            Route::get('/activity-data', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'activityData'])->name('admin.activity.data');
    });

// Get Master Data
Route::get('/get-provinces', [AddressController::class, 'getProvinces']);
Route::get('/get-regencies/{province_id}', [AddressController::class, 'getRegencies']);
Route::get('/get-districts/{regency_id}', [AddressController::class, 'getDistricts']);
Route::get('/get-villages/{district_id}', [AddressController::class, 'getVillages']);
Route::get('/search-villages', [AddressController::class, 'searchVillages']);
Route::get('/address-form', [AddressController::class, 'index']);
Route::get('/icd10/search', [Icd10Controller::class, 'search'])->name('icd10.search');
// IC Pendaftaran (signature -> generate PDF)
Route::post('/erm/ic-pendaftaran/store', [IcPendaftaranController::class, 'store'])->name('erm.ic_pendaftaran.store');
// Batch check and PDF render for IC
Route::post('/erm/ic-pendaftaran/check', [IcPendaftaranController::class, 'check'])->name('erm.ic_pendaftaran.check');
Route::get('/erm/ic-pendaftaran/{pasien}/pdf', [IcPendaftaranController::class, 'pdf'])->name('erm.ic_pendaftaran.pdf');
Route::get('/obat/search', [ObatController::class, 'search'])->name('obat.search');
// Import CSV to update obat by ID (handled inside ERM route group)
Route::get('/wadah/search', [EresepController::class, 'search'])->name('wadah.search');
Route::get('/keluhan-utama/search', [KeluhanUtamaController::class, 'search'])->name('keluhan-utama.search');
Route::get('/get-dokters/{klinik_id}', [VisitationController::class, 'getDoktersByKlinik'])->name('erm.get-dokters');
// AJAX: list of users with role Beautician for print selection
Route::get('/erm/beauticians', function() {
    try {
        $users = \App\Models\User::whereHas('roles', function($q){ $q->where('name', 'Beautician'); })->select('id','name')->orderBy('name')->get();
        return response()->json(['success' => true, 'data' => $users]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'data' => []]);
    }
});
Route::get('/get-apotekers', [EresepController::class, 'getApotekers'])->name('erm.get-apotekers');
Route::get('/tindakan/search', [App\Http\Controllers\Marketing\TindakanController::class, 'searchTindakan'])->name('marketing.tindakan.search');
Route::get('/generate-missing-resep-details', [App\Http\Controllers\ERM\VisitationController::class, 'generateMissingResepDetails']);


Route::get('/erm/dashboard/most-frequent-patient', [\App\Http\Controllers\ERMDashboardAjaxController::class, 'mostFrequentPatient'])->name('erm.dashboard.most-frequent-patient');

// AJAX route for visitation count (dashboard box)
Route::get('/erm/dashboard/visitation-count', [App\Http\Controllers\ERMDashboardController::class, 'visitationCount'])->name('erm.dashboard.visitation-count');

// AJAX route for visitation detail modal
Route::get('/erm/dashboard/visitation-detail', [App\Http\Controllers\ERMDashboardController::class, 'visitationDetail'])->name('erm.dashboard.visitation-detail');
Route::get('/labtest/search', [\App\Http\Controllers\ERM\LabTestController::class, 'search'])->name('labtest.search');
Route::get('/konsultasi/search', [\App\Http\Controllers\ERM\KonsultasiController::class, 'search'])->name('konsultasi.search');
// Hasil Skincheck - standalone upload endpoint (store image + decoded url)
Route::post('/erm/hasil-skincheck', [\App\Http\Controllers\ERM\HasilSkincheckController::class, 'store'])->name('erm.hasil_skincheck.store');
// Decode-only endpoint: accepts image, attempts server-side decode, returns decoded_text/url (no DB save)
Route::post('/erm/hasil-skincheck/decode', [\App\Http\Controllers\ERM\HasilSkincheckController::class, 'decode'])->name('erm.hasil_skincheck.decode');
// Riwayat (pasien) - return last records as JSON for AJAX DataTable
Route::get('/erm/hasil-skincheck/riwayat', [\App\Http\Controllers\ERM\HasilSkincheckController::class, 'riwayat'])->name('erm.hasil_skincheck.riwayat');
// Cancel visitation (Lab index) - change status_kunjungan to 7
Route::post('/erm/elab/visitation/{id}/cancel', [\App\Http\Controllers\ERM\ElabController::class, 'cancelVisitation'])
    ->middleware('role:Lab|Admin')
    ->name('erm.elab.visitation.cancel');
Route::post('/erm/elab/visitation/{id}/force-destroy', [\App\Http\Controllers\ERM\ElabController::class, 'forceDestroy'])
    ->middleware('role:Lab|Admin')
    ->name('erm.elab.visitation.forceDestroy');
Route::get('/erm/elab/canceled', [\App\Http\Controllers\ERM\ElabController::class, 'canceledList'])
    ->middleware('role:Lab|Admin')
    ->name('erm.elab.canceled.list');
Route::post('/erm/elab/visitation/{id}/restore', [\App\Http\Controllers\ERM\ElabController::class, 'restoreVisitation'])
    ->middleware('role:Lab|Admin')
    ->name('erm.elab.visitation.restore');

// ===================== MASTER LAB TEST & KATEGORI (AJAX CRUD) =====================
// Dedicated group restricted to Lab & Admin roles only
Route::prefix('erm')->middleware('role:Lab|Admin')->group(function () {
    // Index page (single page containing both tables & modals)
    Route::get('/master-lab', [\App\Http\Controllers\ERM\LabTestController::class, 'index'])->name('erm.labtests.master');

    // Data endpoints for DataTables
    Route::get('/lab-tests/data', [\App\Http\Controllers\ERM\LabTestController::class, 'data'])->name('erm.labtests.data');
    Route::get('/lab-tests/list', [\App\Http\Controllers\ERM\LabTestController::class, 'list'])->name('erm.labtests.list');
    Route::get('/lab-kategories/data', [\App\Http\Controllers\ERM\LabKategoriController::class, 'data'])->name('erm.labkategories.data');
    Route::get('/lab-pakets/data', [LabPaketController::class, 'data'])->name('erm.labpakets.data');
    // Export master lab tests to Excel
    Route::get('/lab-tests/export', [\App\Http\Controllers\ERM\LabTestController::class, 'export'])->name('erm.labtests.export');

    // Lab Kategori CRUD
    Route::post('/lab-kategories', [\App\Http\Controllers\ERM\LabKategoriController::class, 'store'])->name('erm.labkategories.store');
    Route::put('/lab-kategories/{id}', [\App\Http\Controllers\ERM\LabKategoriController::class, 'update'])->name('erm.labkategories.update');
    Route::delete('/lab-kategories/{id}', [\App\Http\Controllers\ERM\LabKategoriController::class, 'destroy'])->name('erm.labkategories.destroy');

    // Lab Test CRUD
    // Show single lab test (used by edit modal)
    Route::get('/lab-tests/{id}', [\App\Http\Controllers\ERM\LabTestController::class, 'show'])->name('erm.labtests.show');
    Route::post('/lab-tests', [\App\Http\Controllers\ERM\LabTestController::class, 'store'])->name('erm.labtests.store');
    Route::put('/lab-tests/{id}', [\App\Http\Controllers\ERM\LabTestController::class, 'update'])->name('erm.labtests.update');
    Route::delete('/lab-tests/{id}', [\App\Http\Controllers\ERM\LabTestController::class, 'destroy'])->name('erm.labtests.destroy');
    Route::get('/lab-pakets/{id}', [LabPaketController::class, 'show'])->name('erm.labpakets.show');
    Route::post('/lab-pakets', [LabPaketController::class, 'store'])->name('erm.labpakets.store');
    Route::put('/lab-pakets/{id}', [LabPaketController::class, 'update'])->name('erm.labpakets.update');
    Route::delete('/lab-pakets/{id}', [LabPaketController::class, 'destroy'])->name('erm.labpakets.destroy');
    // Toggle availability
    Route::post('/lab-tests/{id}/toggle-availability', [\App\Http\Controllers\ERM\LabTestController::class, 'toggleAvailability'])->name('erm.labtests.toggle_availability');
});
// ================================================================================

// AJAX route for riwayat tindakan detail modal (obat substitution)
Route::get('/erm/riwayat-tindakan/{id}/detail', [App\Http\Controllers\ERM\TindakanController::class, 'getRiwayatDetail']);
// AJAX: get batch list for a stok opname item (shows batch, stok, expiration)
Route::get('/erm/stokopname-item/{itemId}/batches', [\App\Http\Controllers\ERM\StokOpnameController::class, 'getItemBatches'])->name('erm.stokopname.item.batches');
// POST route for saving substituted obat
Route::post('/erm/riwayat-tindakan/{id}/obat', [App\Http\Controllers\ERM\TindakanController::class, 'updateRiwayatObat']);

// AJAX: Get ruangan by gedung for filter (move to RuanganController for consistency)
Route::get('/inventory/ruangan/by-gedung/{gedungId}', [App\Http\Controllers\Inventory\RuanganController::class, 'getRuanganByGedung']);

Route::get('/api/hrd/employees', [App\Http\Controllers\HRD\EmployeeController::class, 'searchForSelect2'])->middleware('auth');

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
Route::prefix('hrd/payroll/master')->middleware(['auth', 'role:Hrd|Admin|Manager|Head Manager|Ceo'])->group(function () {
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
Route::prefix('hrd/payroll/insentif-omset')->middleware(['auth', 'role:Hrd|Admin|Manager|Head Manager|Ceo'])->group(function () {
    Route::get('/', [App\Http\Controllers\HRD\PrInsentifOmsetController::class, 'index'])->name('hrd.payroll.insentif_omset.index');
    Route::get('/data', [App\Http\Controllers\HRD\PrInsentifOmsetController::class, 'data'])->name('hrd.payroll.insentif_omset.data');
    Route::post('/', [App\Http\Controllers\HRD\PrInsentifOmsetController::class, 'store'])->name('hrd.payroll.insentif_omset.store');
    Route::put('/{id}', [App\Http\Controllers\HRD\PrInsentifOmsetController::class, 'update'])->name('hrd.payroll.insentif_omset.update');
    Route::delete('/{id}', [App\Http\Controllers\HRD\PrInsentifOmsetController::class, 'destroy'])->name('hrd.payroll.insentif_omset.destroy');
});

// Payroll KPI Routes
Route::prefix('hrd/payroll/kpi')->middleware(['auth', 'role:Hrd|Admin|Manager|Head Manager|Ceo'])->group(function () {
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

// Serve jasmed file (secure)
Route::get('hrd/payroll/slip-gaji/jasmed/{id}', [App\Http\Controllers\HRD\PrSlipGajiController::class, 'serveJasmed'])
    ->middleware(['auth'])
    ->name('hrd.payroll.slip_gaji.jasmed');

// Employee slip history page & data (after password verification user will be redirected here)
Route::get('hrd/payroll/slip-gaji/history', [App\Http\Controllers\HRD\PrSlipGajiController::class, 'historyPage'])
    ->middleware(['auth'])
    ->name('hrd.payroll.slip_gaji.history');

Route::get('hrd/payroll/slip-gaji/history/data', [App\Http\Controllers\HRD\PrSlipGajiController::class, 'historyData'])
    ->middleware(['auth'])
    ->name('hrd.payroll.slip_gaji.history.data');

// Payroll Slip Gaji Routes (admin only; employees use my-slip / history / download above)
Route::prefix('hrd/payroll/slip-gaji')->middleware(['auth', 'role:Manager|Head Manager|Hrd|Admin|Ceo'])->group(function () {
    Route::get('/', [App\Http\Controllers\HRD\PrSlipGajiController::class, 'index'])->name('hrd.payroll.slip_gaji.index');
    Route::get('/data', [App\Http\Controllers\HRD\PrSlipGajiController::class, 'data'])->name('hrd.payroll.slip_gaji.data');
    Route::get('/detail/{id}', [App\Http\Controllers\HRD\PrSlipGajiController::class, 'detail'])->name('hrd.payroll.slip_gaji.detail');
    Route::put('/status/{id}', [App\Http\Controllers\HRD\PrSlipGajiController::class, 'changeStatus'])->name('hrd.payroll.slip_gaji.status');
    Route::post('/update/{id}', [App\Http\Controllers\HRD\PrSlipGajiController::class, 'update'])->name('hrd.payroll.slip_gaji.update');
    Route::post('/bulk-status', [App\Http\Controllers\HRD\PrSlipGajiController::class, 'bulkStatus'])
        ->middleware(['role:Hrd|Admin|Manager|Head Manager|Ceo'])
        ->name('hrd.payroll.slip_gaji.bulk_status');
    Route::post('/sync', [App\Http\Controllers\HRD\PrSlipGajiController::class, 'sync'])->name('hrd.payroll.slip_gaji.sync');
    Route::get('/export', [App\Http\Controllers\HRD\PrSlipGajiController::class, 'export'])->name('hrd.payroll.slip_gaji.export');
    Route::get('/print/{id}', [\App\Http\Controllers\HRD\PrSlipGajiController::class, 'print']);
});

// Omset Bulanan, Buat Slip, KPI tools for Slip Gaji (payroll operators only)
Route::middleware(['auth', 'role:Hrd|Admin|Manager'])->group(function () {
    Route::get('hrd/payroll/slip-gaji/omset-bulanan', [App\Http\Controllers\HRD\PrSlipGajiController::class, 'getOmsetInputs']);
    Route::post('hrd/payroll/slip-gaji/omset-bulanan', [App\Http\Controllers\HRD\PrSlipGajiController::class, 'storeOmsetBulanan']);
    Route::get('hrd/payroll/slip-gaji/omset-bulanan-total', [App\Http\Controllers\HRD\PrSlipGajiController::class, 'getTotal']);
    Route::post('hrd/payroll/slip-gaji/store-all', [App\Http\Controllers\HRD\PrSlipGajiController::class, 'storeAll']);

    // KPI summary route
    Route::get('hrd/payroll/slip-gaji/kpi-summary', [\App\Http\Controllers\HRD\PrSlipGajiController::class, 'getKpiSummary']);
    // Generate Uang KPI for all employees in selected month
    Route::post('hrd/payroll/slip-gaji/generate-uang-kpi', [App\Http\Controllers\HRD\PrSlipGajiController::class, 'generateUangKpi'])->name('hrd.payroll.slip_gaji.generate_uang_kpi');
    // KPI simulation preview route for HRD
    Route::post('/hrd/payroll/slip_gaji/simulate-kpi', [\App\Http\Controllers\HRD\PrSlipGajiController::class, 'simulateKpiPreview'])->name('hrd.payroll.slip_gaji.simulate_kpi');
});
// Public (auth) endpoint for Farmasi clients to poll finance notifications
Route::get('/finance/get-notif', [App\Http\Controllers\Finance\BillingController::class, 'getNotif'])->middleware('auth');
// Finance notifications endpoints (used by Finance UI to show old notifications)
Route::get('/finance/notifications/old', [\App\Http\Controllers\ERM\NotificationController::class, 'oldNotifications'])->middleware('auth')->name('finance.notifications.old');
Route::post('/finance/notifications/{id}/mark-read', [\App\Http\Controllers\ERM\NotificationController::class, 'markAsRead'])->middleware('auth')->name('finance.notifications.markread');
// Dokter's own slip history (password-verified via my-slip; must be registered before the /{id} admin routes)
Route::prefix('hrd/payroll/slip-gaji-dokter/my')->middleware(['auth'])->group(function () {
    Route::get('/', [\App\Http\Controllers\HRD\PrSlipGajiDokterController::class, 'myHistoryPage'])->name('hrd.payroll.slip_gaji_dokter.my');
    Route::get('/data', [\App\Http\Controllers\HRD\PrSlipGajiDokterController::class, 'myHistoryData'])->name('hrd.payroll.slip_gaji_dokter.my.data');
    Route::get('/download/{id}', [\App\Http\Controllers\HRD\PrSlipGajiDokterController::class, 'myDownload'])->whereNumber('id')->name('hrd.payroll.slip_gaji_dokter.my.download');
});

// Payroll Slip Gaji Dokter (standalone slips for Dokter)
Route::prefix('hrd/payroll/slip-gaji-dokter')->middleware(['auth', 'role:Hrd|Admin|Manager|Head Manager|Ceo'])->group(function () {
    Route::get('/', [\App\Http\Controllers\HRD\PrSlipGajiDokterController::class, 'index'])->name('hrd.payroll.slip_gaji_dokter.index');
    Route::get('/data', [\App\Http\Controllers\HRD\PrSlipGajiDokterController::class, 'data'])->name('hrd.payroll.slip_gaji_dokter.data');
    Route::get('/export', [\App\Http\Controllers\HRD\PrSlipGajiDokterController::class, 'export'])->name('hrd.payroll.slip_gaji_dokter.export');
    Route::post('/store', [\App\Http\Controllers\HRD\PrSlipGajiDokterController::class, 'store'])->name('hrd.payroll.slip_gaji_dokter.store');
    // Approval flow: draft/rejected -> submitted (HRD), submitted -> approved/rejected (CEO), approved -> paid (HRD), paid -> draft (Admin "Unpaid")
    Route::post('/status/{id}', [\App\Http\Controllers\HRD\PrSlipGajiDokterController::class, 'changeStatus'])->whereNumber('id')->name('hrd.payroll.slip_gaji_dokter.status');
    Route::post('/bulk-submit', [\App\Http\Controllers\HRD\PrSlipGajiDokterController::class, 'bulkSubmit'])->name('hrd.payroll.slip_gaji_dokter.bulk_submit');
    Route::get('/{id}', [\App\Http\Controllers\HRD\PrSlipGajiDokterController::class, 'show'])->whereNumber('id')->name('hrd.payroll.slip_gaji_dokter.show');
    Route::post('/update/{id}', [\App\Http\Controllers\HRD\PrSlipGajiDokterController::class, 'update'])->name('hrd.payroll.slip_gaji_dokter.update');
    Route::delete('/{id}', [\App\Http\Controllers\HRD\PrSlipGajiDokterController::class, 'destroy'])->whereNumber('id')->name('hrd.payroll.slip_gaji_dokter.destroy');
    Route::get('/print/{id}', [\App\Http\Controllers\HRD\PrSlipGajiDokterController::class, 'print'])->name('hrd.payroll.slip_gaji_dokter.print');
    // AJAX: get dokter info (klinik) to adjust form fields in create/edit modal
    Route::get('/dokter/{id}', [\App\Http\Controllers\HRD\PrSlipGajiDokterController::class, 'dokterInfo']);
    // Serve lampiran (jasmed) through auth instead of a public /storage link
    Route::get('/jasmed/{id}', [\App\Http\Controllers\HRD\PrSlipGajiDokterController::class, 'serveJasmed'])->name('hrd.payroll.slip_gaji_dokter.jasmed');
});

// Obat KFA mapping (index + AJAX endpoints)
// Moved under satusehat prefix so URL becomes `/satusehat/obat-kfa`.
Route::prefix('satusehat')->middleware(['auth','role:Satusehat|Admin'])->group(function () {
    Route::get('/obat-kfa', [\App\Http\Controllers\ERM\ObatKfaController::class, 'index'])->name('erm.obat_kfa.index');
    Route::match(['get','post'], '/obat-kfa/data', [\App\Http\Controllers\ERM\ObatKfaController::class, 'data'])->name('erm.obat_kfa.data');
    Route::post('/obat-kfa', [\App\Http\Controllers\ERM\ObatKfaController::class, 'store'])->name('erm.obat_kfa.store');
});

// Process temuan record (apply to stok gudang)
Route::post('/erm/stokopname-temuan/{id}/process', [\App\Http\Controllers\ERM\StokOpnameController::class, 'processTemuanRecord'])->middleware('auth');
// Bulk process temuan records
Route::post('/erm/stokopname-temuan/bulk-process', [\App\Http\Controllers\ERM\StokOpnameController::class, 'processTemuanBulk'])->middleware('auth');
// Delete temuan record
Route::post('/erm/stokopname-temuan/{id}/delete', [\App\Http\Controllers\ERM\StokOpnameController::class, 'deleteTemuanRecord'])->middleware('auth');

// Obat Mapping UI (Farmasi/Admin)
Route::prefix('erm')->middleware('role:Farmasi|Admin')->group(function () {
    Route::get('/obat-mapping', [ObatMappingController::class, 'index'])->name('erm.obat-mapping.index');
    Route::get('/obat-mapping/{id}', [ObatMappingController::class, 'show']);
    Route::post('/obat-mapping', [ObatMappingController::class, 'store'])->name('erm.obat-mapping.store');
    Route::put('/obat-mapping/{id}', [ObatMappingController::class, 'update'])->name('erm.obat-mapping.update');
    Route::delete('/obat-mapping/{id}', [ObatMappingController::class, 'destroy'])->name('erm.obat-mapping.destroy');

    // Master Data - Paket Racikan
    Route::get('/master/paket-racikan', [\App\Http\Controllers\ERM\PaketRacikanController::class, 'index'])->name('erm.paket-racikan.index');
    Route::post('/master/paket-racikan', [\App\Http\Controllers\ERM\PaketRacikanController::class, 'store'])->name('erm.paket-racikan.master.store');
    Route::get('/master/paket-racikan/{id}', [\App\Http\Controllers\ERM\PaketRacikanController::class, 'show'])->name('erm.paket-racikan.master.show');
    Route::put('/master/paket-racikan/{id}', [\App\Http\Controllers\ERM\PaketRacikanController::class, 'update'])->name('erm.paket-racikan.master.update');
    Route::post('/master/paket-racikan/{id}/toggle', [\App\Http\Controllers\ERM\PaketRacikanController::class, 'toggle'])->name('erm.paket-racikan.master.toggle');
    Route::delete('/master/paket-racikan/{id}', [\App\Http\Controllers\ERM\PaketRacikanController::class, 'destroy'])->name('erm.paket-racikan.master.destroy');
});

// Workdoc - Surat Keluar
Route::prefix('workdoc')->middleware('role:Hrd|Manager|Head Manager|Employee|Admin')->group(function () {
    Route::get('/surat-keluar', [App\Http\Controllers\Workdoc\SuratKeluarController::class, 'index'])->name('workdoc.surat-keluar.index');
    Route::get('/surat-keluar/list', [App\Http\Controllers\Workdoc\SuratKeluarController::class, 'list'])->name('workdoc.surat-keluar.list');
    // generator route (role-protected) - placed before parameterized routes
    Route::get('/surat-keluar/generate-number', [App\Http\Controllers\Workdoc\SuratKeluarController::class, 'generateNumber'])->name('workdoc.surat-keluar.generate_number');
    // jenis surat list for select2 / select
    Route::get('/surat-jenis/list', [App\Http\Controllers\Workdoc\SuratKeluarController::class, 'jenisList'])->name('workdoc.surat-jenis.list');
    Route::get('/surat-diajukan-for/list', [App\Http\Controllers\Workdoc\SuratKeluarController::class, 'diajukanForList'])->name('workdoc.surat-diajukan-for.list');
    Route::post('/surat-keluar', [App\Http\Controllers\Workdoc\SuratKeluarController::class, 'store'])->name('workdoc.surat-keluar.store');
    Route::post('/surat-keluar/{id}/done', [App\Http\Controllers\Workdoc\SuratKeluarController::class, 'done'])->name('workdoc.surat-keluar.done');
    Route::get('/surat-keluar/{id}', [App\Http\Controllers\Workdoc\SuratKeluarController::class, 'show'])->name('workdoc.surat-keluar.show');
    Route::put('/surat-keluar/{id}', [App\Http\Controllers\Workdoc\SuratKeluarController::class, 'update'])->name('workdoc.surat-keluar.update');
    Route::delete('/surat-keluar/{id}', [App\Http\Controllers\Workdoc\SuratKeluarController::class, 'destroy'])->name('workdoc.surat-keluar.destroy');
    Route::get('/surat-keluar/{id}/download', [App\Http\Controllers\Workdoc\SuratKeluarController::class, 'download'])->name('workdoc.surat-keluar.download');
});



// Lab config: select active Dokter for Lembar Monitoring (only dokters with Spesialisasi 'Laboratorium')
Route::prefix('erm')->middleware('role:Lab|Admin')->group(function () {
    Route::get('/lab-config', [\App\Http\Controllers\ERM\LabConfigController::class, 'getConfig'])->name('erm.labconfig.get');
    Route::get('/lab-config/dokters', [\App\Http\Controllers\ERM\LabConfigController::class, 'listDokters'])->name('erm.labconfig.dokters');
    Route::post('/lab-config/save', [\App\Http\Controllers\ERM\LabConfigController::class, 'save'])->name('erm.labconfig.save');
});


