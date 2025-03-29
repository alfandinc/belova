<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\{
    AuthController,
    ERMDashboardController,
    HRDDashboardController,
    InventoryDashboardController,
    MarketingDashboardController,
    PasienController as ControllersPasienController,
    RoleController as ControllersRoleController
};
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\ERM\PasienController;


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

Route::resource('erm/pasiens', PasienController::class)->names('erm.pasiens');

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
