<!-- Left Sidenav -->
<div class="left-sidenav">
    <!-- LOGO -->
    @php
        $clinicChoice = session('clinic_choice');
        if ($clinicChoice === 'premiere') {
            $lightLogo = asset('img/logo-premiere-bw.png');
            $darkLogo = asset('img/logo-premiere.png');
            $logoHeight = '50px';
        } elseif ($clinicChoice === 'skin') {
            $lightLogo = asset('img/logo-belovaskin-bw.png');
            $darkLogo = asset('img/logo-belovaskin.png');
            $logoHeight = '50px';
        } else {
            $lightLogo = asset('img/logo-belovacorp-bw.png');
            $darkLogo = asset('img/logo-belovacorp.png');
            $logoHeight = '50px';
        }
    @endphp
    <div class="brand mt-3">
        <a href="/marketing" class="logo">
            <span>
                <!-- Light-theme logo (for dark background) -->
                <img src="{{ $lightLogo }}" alt="logo" class="logo-light" style="width: auto; height: {{ $logoHeight }};">

                <!-- Dark-theme logo (for light background) -->
                <img src="{{ $darkLogo }}" alt="logo" class="logo-dark" style="width: auto; height: {{ $logoHeight }};">
            </span>
        </a>
    </div>
    <!--end logo-->
    <div class="menu-content h-100" data-simplebar>
        <ul class="metismenu left-sidenav-menu">
            @php
                try {
                    $today = \Carbon\Carbon::today();
                    $hariPentingTodayCount = \App\Models\Marketing\HariPenting::where(function($q) use ($today){
                            // Single day events: start_date == today
                            $q->where(function($q2) use ($today){
                                $q2->whereDate('start_date', $today)->whereNull('end_date');
                            })
                            // Multi-day span: start_date <= today <= end_date
                            ->orWhere(function($q2) use ($today){
                                $q2->whereDate('start_date','<=',$today)->whereDate('end_date','>=',$today);
                            });
                        })
                        ->count();
                    // Count content plans that are scheduled so we can show a badge in the menu
                    try {
                        $scheduledCount = \App\Models\Marketing\ContentPlan::where('status', 'Scheduled')->count();
                    } catch (Exception $e) { $scheduledCount = 0; }
                } catch (Exception $e) { $hariPentingTodayCount = 0; }
            @endphp
            @php
                $isAnalyticsOpen = request()->is('marketing/revenue')
                    || request()->is('marketing/social-media-analytics')
                    || request()->is('marketing/patients')
                    || request()->is('marketing/services')
                    || request()->is('marketing/products');
                $isMarkomOpen = request()->is('marketing/followup')
                    || request()->is('marketing/promo')
                    || request()->is('marketing/penawaran')
                    || request()->is('marketing/penawaran/*')
                    || request()->is('marketing/kunjungan')
                    || request()->is('marketing/kunjungan/*')
                    || request()->is('marketing/catatan-keluhan')
                    || request()->is('marketing/pasien-data')
                    || request()->is('marketing/birthday')
                    || request()->is('marketing/birthday/*');
                $isSosmedOpen = request()->is('marketing/content-plan')
                    || request()->is('marketing/content-plan/*')
                    || request()->is('marketing/before-after-gallery')
                    || request()->is('marketing/hari-penting');
                $isMasterDataOpen = request()->is('marketing/master-merchandise')
                    || request()->is('marketing/tindakan')
                    || request()->is('marketing/tindakan/*')
                    || request()->is('marketing/kodetindakan')
                    || request()->is('marketing/kodetindakan/*')
                    || request()->is('marketing/survey-questions')
                    || request()->is('marketing/survey-questions/*');
            @endphp
            <li class="menu-label mt-0">Main</li>
            <li>
                <a href="/marketing/dashboard"> <i data-feather="home" class="align-self-center menu-icon"></i><span>Dashboard</span></a>
            </li>
            <li class="{{ $isAnalyticsOpen ? 'mm-active' : '' }}">
                <a href="javascript: void(0);">
                    <i data-feather="bar-chart-2" class="align-self-center menu-icon"></i>
                    <span>Analytics</span>
                    <span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span>
                </a>
                <ul class="nav-second-level" aria-expanded="{{ $isAnalyticsOpen ? 'true' : 'false' }}">
                    <li><a href="/marketing/revenue"><i data-feather="dollar-sign" class="align-self-center menu-icon"></i>Revenue</a></li>
                    <li><a href="/marketing/social-media-analytics"><i data-feather="share-2" class="align-self-center menu-icon"></i>Social Media Analytics</a></li>
                    <li><a href="/marketing/patients"><i data-feather="users" class="align-self-center menu-icon"></i>Patient Analytics</a></li>
                    <li><a href="/marketing/services"><i data-feather="activity" class="align-self-center menu-icon"></i>Service Analytics</a></li>
                    <li><a href="/marketing/products"><i data-feather="package" class="align-self-center menu-icon"></i>Product Analytics</a></li>
                </ul>
            </li>
            {{-- <li>
                <a href="/marketing/clinic-comparison"> <i data-feather="bar-chart-2" class="align-self-center menu-icon"></i><span>Clinic Performance</span></a>
            </li> --}}
            <li class="menu-label">MARKETING TOOLS</li>
            <li class="{{ $isMarkomOpen ? 'mm-active' : '' }}">
                <a href="javascript: void(0);">
                    <i data-feather="briefcase" class="align-self-center menu-icon"></i>
                    <span>Markom</span>
                    <span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span>
                </a>
                <ul class="nav-second-level" aria-expanded="{{ $isMarkomOpen ? 'true' : 'false' }}">
                    <li><a href="/marketing/followup"><i data-feather="check-square" class="align-self-center menu-icon"></i>Follow Up</a></li>
                    <li><a href="/marketing/promo"><i data-feather="tag" class="align-self-center menu-icon"></i>Promo</a></li>
                    <li><a href="{{ route('marketing.penawaran.index') }}"><i data-feather="file-text" class="align-self-center menu-icon"></i>Penawaran</a></li>
                    <li><a href="{{ route('marketing.kunjungan.index') }}"><i data-feather="map-pin" class="align-self-center menu-icon"></i>Kunjungan</a></li>
                    <li><a href="/marketing/catatan-keluhan"><i data-feather="alert-circle" class="align-self-center menu-icon"></i>Catatan Keluhan Customer</a></li>
                    <li><a href="/marketing/pasien-data"><i data-feather="user" class="align-self-center menu-icon"></i>Pasien Data</a></li>
                    <li><a href="{{ route('marketing.birthday.index') }}"><i data-feather="gift" class="align-self-center menu-icon"></i>Ulang Tahun Pasien</a></li>
                </ul>
            </li>
            <li class="{{ $isSosmedOpen ? 'mm-active' : '' }}">
                <a href="javascript: void(0);">
                    <i data-feather="instagram" class="align-self-center menu-icon"></i>
                    <span>Sosmed</span>
                    <span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span>
                </a>
                <ul class="nav-second-level" aria-expanded="{{ $isSosmedOpen ? 'true' : 'false' }}">
                    <li>
                        <a href="/marketing/content-plan"><i data-feather="calendar" class="align-self-center menu-icon"></i>Content Plan
                            @if(!empty($scheduledCount) && $scheduledCount > 0)
                                <span class="badge badge-warning ml-1" style="font-size:10px;">{{ $scheduledCount }}</span>
                            @endif
                        </a>
                    </li>
                    <li><a href="{{ route('marketing.before_after_gallery.index') }}"><i data-feather="image" class="align-self-center menu-icon"></i>Before After Gallery</a></li>
                    <li><a href="/marketing/hari-penting"><i data-feather="star" class="align-self-center menu-icon"></i>Hari Penting
                        @if(!empty($hariPentingTodayCount))
                            <span class="badge badge-danger ml-1" style="font-size:10px;">{{ $hariPentingTodayCount }}</span>
                        @endif
                    </a></li>
                </ul>
            </li>
            <li class="menu-label">MASTER DATA</li>
            <li class="{{ $isMasterDataOpen ? 'mm-active' : '' }}">
                <a href="javascript: void(0);">
                    <i data-feather="database" class="align-self-center menu-icon"></i>
                    <span>Master Data</span>
                    <span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span>
                </a>
                <ul class="nav-second-level" aria-expanded="{{ $isMasterDataOpen ? 'true' : 'false' }}">
                    <li><a href="/marketing/master-merchandise"><i data-feather="package" class="align-self-center menu-icon"></i>Master Merchandise</a></li>
                    <li><a href="/marketing/tindakan"><i data-feather="bar-chart-2" class="align-self-center menu-icon"></i>Paket Tindakan</a></li>
                    <li><a href="/marketing/kodetindakan"><i data-feather="key" class="align-self-center menu-icon"></i>Kode Tindakan</a></li>
                    <li><a href="/marketing/survey-questions"><i data-feather="edit-3" class="align-self-center menu-icon"></i>Survey Questions</a></li>
                </ul>
            </li>
            
            
        </ul>              
    </div>
</div>
<!-- end left-sidenav-->