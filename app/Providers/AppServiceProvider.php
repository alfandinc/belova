<?php

namespace App\Providers;

use App\Models\ERM\Dokter;
use App\Models\ERM\Pasien;
use App\Models\HRD\Employee;
use App\Models\Marketing\MarketingEvent;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Database\Eloquent\Relations\Relation;
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
        Relation::morphMap([
            'pasien' => Pasien::class,
            'employee' => Employee::class,
            'dokter' => Dokter::class,
            'marketing_event' => MarketingEvent::class,
        ]);

        config(['app.locale' => 'id']);
	    Carbon::setLocale('id');
        Schema::defaultStringLength(191); 
        
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Create PDF cache directory if it doesn't exist
        $pdfCacheDir = storage_path('framework/cache/pdf');
        if (!file_exists($pdfCacheDir)) {
            mkdir($pdfCacheDir, 0755, true);
        }
    }
}
