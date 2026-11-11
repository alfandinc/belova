<?php

namespace App\Providers;

use App\Models\ERM\Dokter;
use App\Models\ERM\Pasien;
use App\Models\HRD\Employee;
use App\Models\Marketing\MarketingEvent;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use App\View\Composers\DaftarKunjunganModalComposer;
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
            'follow_up' => \App\Models\Marketing\FollowUp::class,
        ]);

        View::composer('erm.rawatjalans.partials.modal-daftar-kunjungan', DaftarKunjunganModalComposer::class);

        // Layar Pelanggan tablet: counted per display token (a plain throttle:x,y shares one per-IP counter
        // across routes, so the tablet's polling would use up the survey's allowance)
        \Illuminate\Support\Facades\RateLimiter::for('layar-pelanggan-state', fn ($request) =>
            \Illuminate\Cache\RateLimiting\Limit::perMinute(120)->by('lp-state:' . $request->route('token')));
        \Illuminate\Support\Facades\RateLimiter::for('layar-pelanggan-survey', fn ($request) =>
            \Illuminate\Cache\RateLimiting\Limit::perMinute(20)->by('lp-survey:' . $request->route('token')));

        // Kos BCL: deleting, refunding, cancelling and correcting money records needs a supervisor.
        \Illuminate\Support\Facades\Gate::define('bcl-supervise', function ($user) {
            return $user->hasRole('Admin') || $user->checkPermissionTo('bcl.supervisor');
        });

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
