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
                    $hariPentingTodayCount = \App\Models\Marketing\HariPenting::whereDate('start_date', '<=', $today)
                        ->where(function($q) use ($today){
                            $q->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
                        })
                        ->count();
                } catch (Exception $e) { $hariPentingTodayCount = 0; }
            @endphp
            <li class="menu-label mt-0">Main</li>
            <li>
                <a href="/marketing/dashboard"> <i data-feather="home" class="align-self-center menu-icon"></i><span>Dashboard</span></a>
            </li>
            
            <li class="menu-label">Analytics</li>
            <li>
                <a href="/marketing/revenue"> <i data-feather="dollar-sign" class="align-self-center menu-icon"></i><span>Revenue</span></a>
            </li>
            <li>
                <a href="/marketing/patients"> <i data-feather="users" class="align-self-center menu-icon"></i><span>Patient Analytics</span></a>
            </li>
            <li>
                <a href="/marketing/services"> <i data-feather="activity" class="align-self-center menu-icon"></i><span>Service Analytics</span></a>
            </li>
            <li>
                <a href="/marketing/products"> <i data-feather="package" class="align-self-center menu-icon"></i><span>Product Analytics</span></a>
            </li>
            {{-- <li>
                <a href="/marketing/clinic-comparison"> <i data-feather="bar-chart-2" class="align-self-center menu-icon"></i><span>Clinic Performance</span></a>
            </li> --}}
            <li class="menu-label">MARKETING TOOLS</li>
            <li>
                <a href="/marketing/followup"> <i data-feather="check-square" class="align-self-center menu-icon"></i><span>Follow Up</span></a>
            </li>
            <li>
                <a href="/marketing/content-plan"> <i data-feather="calendar" class="align-self-center menu-icon"></i><span>Content Plan</span></a>
            </li>
            <li>
                <a href="/marketing/hari-penting"> <i data-feather="star" class="align-self-center menu-icon"></i><span>Hari Penting</span>
                    @if(!empty($hariPentingTodayCount))
                        <span class="badge badge-danger ml-1" style="font-size:10px;">{{ $hariPentingTodayCount }}</span>
                    @endif
                </a>
            </li>
            <li>
                <a href="/marketing/catatan-keluhan"> <i data-feather="alert-circle" class="align-self-center menu-icon"></i><span>Catatan Keluhan Customer</span></a>
            </li>

            
            <li class="menu-label">MASTER DATA</li>
            <li>
                <a href="/marketing/pasien-data"> <i data-feather="user" class="align-self-center menu-icon"></i><span>Pasien Data</span></a>
            </li>
            <li>
                <a href="/marketing/master-merchandise"> <i data-feather="package" class="align-self-center menu-icon"></i><span>Master Merchandise</span></a>
            </li>
            <li>
                <a href="/marketing/tindakan"> <i data-feather="bar-chart-2" class="align-self-center menu-icon"></i><span>Paket Tindakan</span></a>
            </li>
            <li>
                <a href="/marketing/kodetindakan"> <i data-feather="key" class="align-self-center menu-icon"></i><span>Kode Tindakan</span></a>
            </li>
            <li>
                <a href="/marketing/survey-questions"> <i data-feather="edit-3" class="align-self-center menu-icon"></i><span>Survey Questions</span></a>
            </li>
            
            
        </ul>              
    </div>
</div>
<!-- end left-sidenav-->