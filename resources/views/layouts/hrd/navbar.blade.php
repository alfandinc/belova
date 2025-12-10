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
        <a href="/hrd" class="logo">
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
            <li class="menu-label mt-0">Main</li>
                
            <li>
                <a href="javascript: void(0);"> <i data-feather="home" class="align-self-center menu-icon"></i><span>Dashboard</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                <ul class="nav-second-level" aria-expanded="false">
                    <li class="nav-item"><a class="nav-link" href="/hrd"><i class="ti-control-record"></i>Analytics</a></li>
                </ul>
            </li>
            <!-- Jadwal Karyawan -->
                {{-- @if(Auth::check() && Auth::user()->hasAnyRole('Hrd','Admin','Manager')) --}}
                    <!-- Jadwal dan Absensi Group -->
                    <li>
                        <a href="javascript: void(0);">
                            <i data-feather="calendar" class="align-self-center menu-icon"></i>
                            <span>Jadwal dan Absensi</span>
                            <span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span>
                        </a>
                        <ul class="nav-second-level" aria-expanded="false">
                            {{-- @if(Auth::user()->hasAnyRole('Hrd','Admin','Manager','Ceo')) --}}
                            @if(Auth::check() && Auth::user()->hasAnyRole('Hrd','Admin'))
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('hrd.schedule.index') }}">
                                    <i class="ti-control-record"></i>Jadwal Mingguan
                                </a>
                            </li>
                            
                            
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('hrd.absensi_rekap.index') }}">
                                    <i class="ti-control-record"></i>Rekap Absensi
                                </a>
                            </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('hrd.dokter-schedule.index') }}">
                                        <i class="ti-control-record"></i>Jadwal Dokter
                                    </a>
                                </li>
                            @endif
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('hrd.schedule.print') }}" target="_blank">
                                    <i class="ti-control-record"></i>Jadwal Saya
                                </a>
                            </li>
                        </ul>
                    </li>
                {{-- @endif --}}
            <!-- Catatan Dosa - New Feature -->
            @if(Auth::check() && Auth::user()->hasAnyRole('Hrd','Admin'))
                @php
                    // Query jumlah catatan dosa dengan status 'dalam proses'
                    try {
                        $catatanDosaProsesCount = \App\Models\HRD\CatatanDosa::where('status_tindaklanjut', 'dalam proses')->count();
                    } catch (Exception $e) {
                        $catatanDosaProsesCount = 0;
                    }
                @endphp
                <li>
                    <a href="{{ route('hrd.catatan-dosa.index') }}">
                        <i data-feather="alert-circle" class="align-self-center menu-icon"></i>
                        <span>Catatan Pelanggaran</span>
                        @if($catatanDosaProsesCount > 0)
                            <span class="badge badge-danger ml-1">{{ $catatanDosaProsesCount }}</span>
                        @endif
                    </a>
                </li>
            @endif
            <li>
                    @php
                        use App\Models\HRD\PengajuanLibur;
                        $pendingLiburHRD = 0;
                        $pendingLiburManager = 0;
                        if(Auth::check()) {
                            if(Auth::user()->hasAnyRole('Hrd','Admin')) {
                                $pendingLiburHRD = PengajuanLibur::where('status_manager', 'disetujui')
                                    ->where('status_hrd', 'menunggu')->count();
                            }
                            if(Auth::user()->hasAnyRole('Manager','Admin')) {
                                $pendingLiburManager = PengajuanLibur::where('status_manager', 'menunggu')->count();
                            }
                        }
                    @endphp
                    <a href="javascript: void(0);"> <i data-feather="calendar" class="align-self-center menu-icon"></i><span>Pengajuan Cuti/Libur</span>
                        @if($pendingLiburHRD > 0 && Auth::user()->hasAnyRole('Hrd','Admin'))
                            <span class="badge badge-warning ml-1">{{ $pendingLiburHRD }}</span>
                        @endif
                        @if($pendingLiburManager > 0 && Auth::user()->hasAnyRole('Manager','Admin'))
                            <span class="badge badge-info ml-1">{{ $pendingLiburManager }}</span>
                        @endif
                        <span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span>
                    </a>
                    <ul class="nav-second-level" aria-expanded="false">
                        <li class="nav-item"><a class="nav-link" href="{{ route('hrd.libur.index') }}"><i class="ti-control-record"></i>Pengajuan Saya</a></li>
                        <!-- For Managers: Approval cuti team -->
                        @if(Auth::check() && Auth::user()->hasAnyRole('Manager','Admin'))
                        <li class="nav-item"><a class="nav-link" href="{{ route('hrd.libur.index') }}?view=team"><i class="ti-control-record"></i>Persetujuan Tim
                            @if($pendingLiburManager > 0)
                                <span class="badge badge-info ml-1">{{ $pendingLiburManager }}</span>
                            @endif
                        </a></li>
                        @endif
                        <!-- For HRD: Full leave management -->
                        @if(Auth::check() && Auth::user()->hasAnyRole('Hrd','Admin'))
                        <li class="nav-item"><a class="nav-link" href="{{ route('hrd.libur.index') }}?view=approval"><i class="ti-control-record"></i>Persetujuan HRD
                            @if($pendingLiburHRD > 0)
                                <span class="badge badge-warning ml-1">{{ $pendingLiburHRD }}</span>
                            @endif
                        </a></li>
                        @endif
                    </ul>
            </li>

            <!-- Pengajuan Tidak Masuk (Sakit/Izin) - Visible to all authenticated users -->
            @php
                use App\Models\HRD\PengajuanTidakMasuk;
                $pendingTidakMasukHRD = 0;
                $pendingTidakMasukManager = 0;
                if(Auth::check()) {
                    if(Auth::user()->hasAnyRole('Hrd','Admin')) {
                        $pendingTidakMasukHRD = PengajuanTidakMasuk::where(function($q) {
                            $q->whereNull('status_manager')->whereNull('status_hrd')
                              ->orWhere(function($q2) {
                                  $q2->where('status_manager', 'disetujui')->whereNull('status_hrd');
                              });
                        })->count();
                    }
                    if(Auth::user()->hasAnyRole('Manager','Admin')) {
                        $pendingTidakMasukManager = PengajuanTidakMasuk::whereNull('status_manager')->count();
                    }
                }
            @endphp
            <li>
                <a href="{{ route('hrd.tidakmasuk.index') }}">
                    <i data-feather="user-x" class="align-self-center menu-icon"></i>
                    <span>Pengajuan Sakit/Izin</span>
                    @if($pendingTidakMasukHRD > 0 && Auth::user()->hasAnyRole('Hrd','Admin'))
                        <span class="badge badge-warning ml-1">{{ $pendingTidakMasukHRD }}</span>
                    @endif
                    @if($pendingTidakMasukManager > 0 && Auth::user()->hasAnyRole('Manager','Admin'))
                        <span class="badge badge-info ml-1">{{ $pendingTidakMasukManager }}</span>
                    @endif
                </a>
            </li>
            <!-- Pengajuan Lembur - Visible to all authenticated users -->
            @php
                if (!class_exists('App\\Models\\HRD\\PengajuanLembur')) {
                    // Only define if not already loaded
                    eval('namespace App\\Models\\HRD; class PengajuanLembur extends \\Illuminate\\Database\\Eloquent\\Model {}');
                }
                $pendingLemburHRD = 0;
                $pendingLemburManager = 0;
                if(Auth::check()) {
                    if(Auth::user()->hasAnyRole('Hrd','Admin')) {
                        $pendingLemburHRD = \App\Models\HRD\PengajuanLembur::where(function($q) {
                            $q->whereNull('status_manager')->whereNull('status_hrd')
                              ->orWhere(function($q2) {
                                  $q2->where('status_manager', 'disetujui')->whereNull('status_hrd');
                              });
                        })->count();
                    }
                    if(Auth::user()->hasAnyRole('Manager','Admin')) {
                        $pendingLemburManager = \App\Models\HRD\PengajuanLembur::whereNull('status_manager')->count();
                    }
                }
            @endphp
            <li>
                <a href="{{ route('hrd.lembur.index') }}">
                    <i data-feather="clock" class="align-self-center menu-icon"></i>
                    <span>Pengajuan Lembur</span>
                    @if($pendingLemburHRD > 0 && Auth::user()->hasAnyRole('Hrd','Admin'))
                        <span class="badge badge-warning ml-1">{{ $pendingLemburHRD }}</span>
                    @endif
                    @if($pendingLemburManager > 0 && Auth::user()->hasAnyRole('Manager','Admin'))
                        <span class="badge badge-info ml-1">{{ $pendingLemburManager }}</span>
                    @endif
                </a>
            </li>
            
            <!-- Pengajuan Ganti Shift - Visible to all authenticated users -->
            @php
                // Compute pending counts for Ganti/Tukar Shift safely
                $pendingGantiShiftHRD = 0;
                $pendingGantiShiftManager = 0;

                if (Auth::check()) {
                    try {
                        if (class_exists('\App\\Models\\HRD\\PengajuanGantiShift')) {
                            if (Auth::user()->hasAnyRole('Hrd','Admin')) {
                                // Pending for HRD: manager already approved and HRD waiting
                                $pendingGantiShiftHRD = \App\Models\HRD\PengajuanGantiShift::where('status_manager', 'disetujui')
                                    ->where(function($q) {
                                        $q->where('status_hrd', 'menunggu')->orWhereNull('status_hrd');
                                    })->count();
                            }

                            if (Auth::user()->hasAnyRole('Manager','Admin')) {
                                // Pending for Manager: status_manager explicitly 'menunggu' or null
                                $pendingGantiShiftManager = \App\Models\HRD\PengajuanGantiShift::where(function($q){
                                    $q->where('status_manager', 'menunggu')->orWhereNull('status_manager');
                                })->count();
                            }
                        }
                    } catch (Exception $e) {
                        // If model or query fails, keep counts at 0 to avoid breaking the navbar
                        $pendingGantiShiftHRD = 0;
                        $pendingGantiShiftManager = 0;
                    }
                }
            @endphp
            <li>
                <a href="{{ route('hrd.gantishift.index') }}">
                    <i data-feather="refresh-cw" class="align-self-center menu-icon"></i>
                    <span>Pengajuan Ganti/Tukar Shift</span>
                    @if($pendingGantiShiftHRD > 0 && Auth::user()->hasAnyRole('Hrd','Admin'))
                        <span class="badge badge-warning ml-1">{{ $pendingGantiShiftHRD }}</span>
                    @endif
                    @if($pendingGantiShiftManager > 0 && Auth::user()->hasAnyRole('Manager','Admin'))
                        <span class="badge badge-info ml-1">{{ $pendingGantiShiftManager }}</span>
                    @endif
                </a>
            </li>
            
            <!-- Performance Evaluations - Visible to all authenticated users -->
            <li>
                <a href="javascript: void(0);"> <i data-feather="award" class="align-self-center menu-icon"></i><span>Penilaian Kinerja</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                <ul class="nav-second-level" aria-expanded="false">
                    <li class="nav-item"><a class="nav-link" href="{{ route('hrd.performance.my-evaluations') }}"><i class="ti-control-record"></i>Evaluasi Saya</a></li>
                    
                    {{-- <!-- For Managers: Team Evaluations -->
                    @if(Auth::check() && Auth::user()->hasAnyRole('Manager','Admin'))
                    <li class="nav-item"><a class="nav-link" href="{{ route('hrd.performance.my-evaluations') }}"><i class="ti-control-record"></i>Evaluasi Tim</a></li>
                    @endif --}}
                    
                    <!-- For HRD and CEO: Full Performance Management -->
                    @if(Auth::check() && (Auth::user()->hasAnyRole('Hrd','Admin') || Auth::user()->hasAnyRole('Ceo','Admin')))
                    @php
                        if (!class_exists('App\\Models\\HRD\\PerformanceEvaluationPeriod')) {
                            eval('namespace App\\Models\\HRD; class PerformanceEvaluationPeriod extends \\Illuminate\\Database\\Eloquent\\Model {}');
                        }
                        $activePeriods = 0;
                        try {
                            $activePeriods = \App\Models\HRD\PerformanceEvaluationPeriod::where('status', 'Active')->count();
                        } catch (Exception $e) {
                            $activePeriods = 0;
                        }
                    @endphp
                    <li class="nav-item"><a class="nav-link" href="{{ route('hrd.performance.periods.index') }}"><i class="ti-control-record"></i>Periode Penilaian
                        @if($activePeriods > 0)
                            <span class="badge badge-success ml-1">{{ $activePeriods }}</span>
                        @endif
                    </a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('hrd.performance.questions.index') }}"><i class="ti-control-record"></i>Kelola Pertanyaan</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('hrd.performance.results.index') }}"><i class="ti-control-record"></i>Hasil Penilaian</a></li>
                    @endif
                </ul>
            </li>
            
            <!-- For Managers: Team Management -->
            @if(Auth::check() && Auth::user()->hasRole('Manager'))
            <li>
                <a href="javascript: void(0);"> <i data-feather="users" class="align-self-center menu-icon"></i><span>Divisi Saya</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                <ul class="nav-second-level" aria-expanded="false">
                    <li class="nav-item"><a class="nav-link" href="{{ route('hrd.division.mine') }}"><i class="ti-control-record"></i>Informasi Divisi</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('hrd.division.team') }}"><i class="ti-control-record"></i>Anggota Tim</a></li>
                </ul>
            </li>
            @endif
            
            <!-- For HRD and CEO: Employee Management -->
            @if(Auth::check() && (Auth::user()->hasAnyRole('Hrd','Admin') || Auth::user()->hasAnyRole('Ceo','Admin')))
            <li>
                <a href="javascript: void(0);"> <i data-feather="users" class="align-self-center menu-icon"></i><span>Kepegawaian</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                <ul class="nav-second-level" aria-expanded="false">
                    <li class="nav-item"><a class="nav-link" href="{{ route('hrd.employee.create') }}"><i class="ti-control-record"></i>Tambah Pegawai Baru</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('hrd.employee.index') }}"><i class="ti-control-record"></i>Data Pegawai</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('hrd.dokters.create') }}"><i class="ti-control-record"></i>Tambah Dokter Baru</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('hrd.dokters.index') }}"><i class="ti-control-record"></i>Data Dokter</a></li>
                    
                </ul>
            </li>
                <li>
                        {{-- Rekap Absensi now grouped under Jadwal dan Absensi --}}
                
            
            <!-- For HRD and CEO: Division and Position Management -->
            <li>
                <a href="javascript: void(0);"> <i data-feather="briefcase" class="align-self-center menu-icon"></i><span>Master Data</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                <ul class="nav-second-level" aria-expanded="false">
                    <li class="nav-item"><a class="nav-link" href="{{ route('hrd.master.division.index') }}"><i class="ti-control-record"></i>Divisi</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('hrd.master.position.index') }}"><i class="ti-control-record"></i>Jabatan</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('hrd.master.jatah-libur.index') }}"><i class="ti-control-record"></i>Jatah Libur</a></li>
                    {{-- <li class="nav-item"><a class="nav-link" href="#"><i class="ti-control-record"></i>Posisi/Jabatan</a></li>
                    <li class="nav-item"><a class="nav-link" href="#"><i class="ti-control-record"></i>Saldo Cuti</a></li> --}}
                </ul>
            </li>
            @endif
                <!-- Payroll Menu -->
                    <li>
                        <a href="javascript: void(0);"> <i data-feather="dollar-sign" class="align-self-center menu-icon"></i><span>Payroll</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                        <ul class="nav-second-level" aria-expanded="false">
                            <li class="nav-item"><a class="nav-link" href="#" onclick="checkSlipGaji(event)"><i class="ti-control-record"></i>Slip Gaji Saya</a></li>

                            @if(Auth::check() && (Auth::user()->hasAnyRole('Hrd','Admin') || Auth::user()->hasAnyRole('Ceo','Admin')))
                            <li class="nav-item"><a class="nav-link" href="{{ route('hrd.payroll.master.index') }}"><i class="ti-control-record"></i>Master Payroll</a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('hrd.payroll.insentif_omset.index') }}"><i class="ti-control-record"></i>Insentif Omset</a></li>
                                <li class="nav-item"><a class="nav-link" href="{{ route('hrd.payroll.kpi.index') }}"><i class="ti-control-record"></i>KPI</a></li>
                                <li class="nav-item"><a class="nav-link" href="{{ route('hrd.payroll.slip_gaji.index') }}"><i class="ti-control-record"></i>Slip Gaji</a></li>
                                <li class="nav-item"><a class="nav-link" href="{{ route('hrd.payroll.slip_gaji_dokter.index') }}"><i class="ti-control-record"></i>Slip Gaji Dokter</a></li>
                            @endif
                        </ul>
                    </li>
            
            <!-- For HRD and CEO: Reports -->
            {{-- <li>
                <a href="javascript: void(0);"> <i data-feather="bar-chart-2" class="align-self-center menu-icon"></i><span>Laporan</span><span class="menu-arrow"><i class="mdi mdi-chevron-right"></i></span></a>
                <ul class="nav-second-level" aria-expanded="false">
                    <li class="nav-item"><a class="nav-link" href="#"><i class="ti-control-record"></i>Statistik Pegawai</a></li>
                    <li class="nav-item"><a class="nav-link" href="#"><i class="ti-control-record"></i>Kontrak Berakhir</a></li>
                    <li class="nav-item"><a class="nav-link" href="#"><i class="ti-control-record"></i>Laporan Cuti</a></li>
                </ul>
            </li> --}}
            
        </ul>              
    </div>
</div>
<!-- end left-sidenav-->
<!-- Password Verification Modal -->
<div class="modal fade" id="passwordVerificationModal" tabindex="-1" role="dialog" aria-labelledby="passwordVerificationModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="passwordVerificationModalLabel">Verifikasi Password</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="passwordVerificationForm">
                    <div class="form-group">
                        <label for="password">Masukkan password Anda untuk melihat slip gaji:</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="verifyPasswordAndGetSlip()">Verifikasi</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function checkSlipGaji(e) {
    e.preventDefault();
    // Show password verification modal
    $('#passwordVerificationModal').modal('show');
}

function verifyPasswordAndGetSlip() {
    const password = $('#password').val();
    
    if (!password) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Password harus diisi'
        });
        return;
    }

    $.ajax({
        url: '{{ route('hrd.payroll.slip_gaji.my_slip') }}',
        type: 'POST',
        data: {
            password: password,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            $('#passwordVerificationModal').modal('hide');
            
            if (!response.success) {
                Swal.fire({
                    icon: response.type,
                    title: response.title,
                    text: response.message
                });
            } else {
                // Password is correct and slip is available
                $('#passwordVerificationModal').modal('hide');
                // Redirect user to their slip history page
                window.location.href = response.url;
            }
            
            // Clear the password field
            $('#password').val('');
        },
        error: function(xhr) {
            if (xhr.status === 401) {
                Swal.fire({
                    icon: 'error',
                    title: 'Password Salah',
                    text: 'Password yang Anda masukkan tidak sesuai.'
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Terjadi kesalahan saat mengambil slip gaji.'
                });
            }
            // Clear the password field
            $('#password').val('');
        }
    });
}

// Handle enter key in password field
$('#password').keypress(function(e) {
    if (e.which == 13) {
        e.preventDefault();
        verifyPasswordAndGetSlip();
    }
});
</script>
@endpush