<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        Carbon::setLocale(config('app.locale')); // otomatis 'id' dari .env
        setlocale(LC_TIME, 'id_ID.utf8'); // agar format tanggal ikut bahasa
        Schema::defaultStringLength(191); // untuk menghindari error pada MySQL 5.7 ke bawah
    }
}
