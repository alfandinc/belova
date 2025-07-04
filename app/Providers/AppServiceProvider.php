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
        config(['app.locale' => 'id']);
	    Carbon::setLocale('id');
        Schema::defaultStringLength(191); // untuk menghindari error pada MySQL 5.7 ke bawah
    }
}
