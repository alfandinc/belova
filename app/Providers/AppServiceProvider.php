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
        
        // Create PDF cache directory if it doesn't exist
        $pdfCacheDir = storage_path('framework/cache/pdf');
        if (!file_exists($pdfCacheDir)) {
            mkdir($pdfCacheDir, 0755, true);
        }
    }
}
