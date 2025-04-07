<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\{
    AuthController,
    ERMDashboardController,
    HRDDashboardController,
    InventoryDashboardController,
    MarketingDashboardController,
};
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\ERM\PasienController;
use App\Http\Controllers\ERM\VisitationController;
use App\Http\Controllers\ERM\RawatJalanController;

use App\Http\Controllers\ERM\AsesmenController;


Route::get('/', function () {
    return view('mainmenu');
});
Route::get('/erm/datapasien', function () {
    return view('erm.datapasien');
});

// Different login pages (GET requests only)
Route::get('/erm/login', [AuthController::class, 'showERMLoginForm'])->name('erm.login');
Route::get('/hrd/login', [AuthController::class, 'showHRDLoginForm'])->name('hrd.login');
Route::get('/inventory/login', [AuthController::class, 'showInventoryLoginForm'])->name('inventory.login');
Route::get('/marketing/login', [AuthController::class, 'showMarketingLoginForm'])->name('marketing.login');

// Single POST route for login processing (all forms submit here)
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');


Route::middleware(['auth'])->group(function () {
    Route::get('/erm', [ERMDashboardController::class, 'index'])->name('erm.dashboard');
    Route::get('/hrd', [HRDDashboardController::class, 'index'])->name('hrd.dashboard');
    Route::get('/inventory', [InventoryDashboardController::class, 'index'])->name('inventory.dashboard');
    Route::get('/marketing', [MarketingDashboardController::class, 'index'])->name('marketing.dashboard');
});

Route::fallback(function () {
    if (!Auth::check()) {
        return redirect('/');
    }
});

// Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
//     Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');
//     Route::get('/users/create', [UserController::class, 'create'])->name('admin.users.create');
//     Route::post('/users', [UserController::class, 'store'])->name('admin.users.store');
//     Route::get('/users/{id}/edit', [UserController::class, 'edit'])->name('admin.users.edit');
//     Route::put('/users/{id}', [UserController::class, 'update'])->name('admin.users.update');
//     Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('admin.users.destroy');
// });

// Route::resource('erm/pasiens', PasienController::class)->names('erm.pasiens');
// Route::post('/erm/pasiens', [PasienController::class, 'store'])->name('erm.pasiens.store');


Route::prefix('erm')->group(function () {
    // Pasien Management
    Route::get('/pasiens', [PasienController::class, 'index'])->name('erm.pasiens.index');
    Route::get('/pasiens/create', [PasienController::class, 'create'])->name('erm.pasiens.create');
    Route::post('/pasiens', [PasienController::class, 'store'])->name('erm.pasiens.store');
    Route::get('/pasiens/{id}/edit', [PasienController::class, 'edit'])->name('erm.pasiens.edit');
    Route::put('/pasiens/{id}', [PasienController::class, 'update'])->name('erm.pasiens.update');
    Route::delete('/pasiens/{id}', [PasienController::class, 'destroy'])->name('erm.pasiens.destroy');

    //Visitation
    Route::get('/visitations', [VisitationController::class, 'index'])->name('erm.visitations.index');
    Route::post('/visitations', [VisitationController::class, 'store'])->name('erm.visitations.store');

    Route::get('/rawatjalans', [RawatJalanController::class, 'index'])->name('erm.rawatjalans.index');

    Route::get('/asesmen/{id}/create', [AsesmenController::class, 'create'])->name('erm.asesmen.create');
});

Route::prefix('admin')->group(function () {

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
});
