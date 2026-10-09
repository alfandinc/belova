{{-- Kontrak karyawan: modal content loaded into #contractModal on the karyawan list (script lives there) --}}
@php
    $statusColor = ['tetap' => 'success', 'kontrak' => 'warning', 'freelance' => 'info', 'tidak aktif' => 'danger'][$employee->status] ?? 'secondary';
    $activeContract = $contracts->firstWhere('status', 'active');
    $canRenew = $canManage; // any status: saving a contract makes the employee kontrak
    // Default for a new contract: the day after the last one ends (or today)
    $defaultStart = $lastContract ? $lastContract->end_date->copy()->addDay() : now();
    $defaultDuration = $lastContract->duration_months ?? 12;
    $fmt = fn($d) => $d->locale('id')->translatedFormat('j M Y');

    // Running contract: progress through its period
    if ($activeContract) {
        // Carbon 3: diffInDays() is signed and returns a float
        $totalDays = max(1, (int) round($activeContract->start_date->diffInDays($activeContract->end_date)) + 1);
        $passedDays = min($totalDays, max(0, (int) round($activeContract->start_date->diffInDays(today())) + 1));
        $leftDays = (int) round(today()->diffInDays($activeContract->end_date));
        $startsIn = (int) round(today()->diffInDays($activeContract->start_date)); // > 0: not started yet
        $pct = (int) round($passedDays / $totalDays * 100);
        $barColor = $leftDays < 0 ? 'bg-danger' : ($leftDays <= 30 ? 'bg-warning' : 'bg-success');
    }
    $openRenewForm = $openRenew || ($employee->status === 'kontrak' && !$activeContract);
@endphp
<div class="modal-header bg-primary text-white">
    <h5 class="modal-title"><i class="fas fa-file-contract mr-2"></i>Kontrak · {{ $employee->nama }}</h5>
    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
</div>
<div class="modal-body ck-body">
    {{-- Karyawan --}}
    <div class="ck-employee mb-3">
        <div>
            <div class="font-weight-bold">{{ $employee->nama }}
                <span class="badge badge-{{ $statusColor }} ml-1">{{ ucfirst($employee->status ?? '-') }}</span>
            </div>
            <div class="small text-muted">
                {{ optional($employee->position)->name ?? 'Tanpa posisi' }} · {{ optional($employee->division)->name ?? '-' }}
                · No Induk {{ $employee->no_induk ?: '-' }}
            </div>
        </div>
        <div class="small text-muted text-sm-right">
            Masuk {{ $employee->tanggal_masuk ? $fmt($employee->tanggal_masuk) : '-' }}
            @if($employee->masaKerja())<br>Masa kerja {{ $employee->masaKerja() }}@endif
        </div>
    </div>

    {{-- Kontrak berjalan --}}
    @if($activeContract)
        <div class="ck-current mb-3">
            <div class="d-flex justify-content-between align-items-start flex-wrap">
                <div>
                    <div class="small text-muted text-uppercase font-weight-bold">Kontrak berjalan</div>
                    <div class="h6 mb-1">{{ $fmt($activeContract->start_date) }} – {{ $fmt($activeContract->end_date) }}
                        <span class="text-muted small">({{ $activeContract->duration_months }} bulan)</span></div>
                </div>
                <div class="text-right">
                    @if($startsIn > 0)
                        <span class="badge badge-info px-2 py-1"><i class="fas fa-hourglass-start mr-1"></i>Belum dimulai · {{ $startsIn }} hari lagi</span>
                    @elseif($leftDays < 0)
                        <span class="badge badge-danger px-2 py-1"><i class="fas fa-exclamation-circle mr-1"></i>Sudah lewat {{ -$leftDays }} hari</span>
                    @elseif($leftDays === 0)
                        <span class="badge badge-danger px-2 py-1">Berakhir hari ini</span>
                    @else
                        <span class="badge {{ $leftDays <= 30 ? 'badge-warning' : 'badge-success' }} px-2 py-1">Sisa {{ $leftDays }} hari</span>
                    @endif
                </div>
            </div>
            <div class="progress mt-2" style="height: 8px;" title="{{ $pct }}% masa kontrak berjalan">
                <div class="progress-bar {{ $barColor }}" style="width: {{ $pct }}%"></div>
            </div>
            <div class="d-flex justify-content-between small text-muted mt-1">
                @if($startsIn > 0)
                    <span>Mulai {{ $fmt($activeContract->start_date) }}</span>
                    <span>{{ $totalDays }} hari</span>
                @else
                    <span>{{ $pct }}% berjalan</span>
                    <span>Hari ke-{{ $passedDays }} dari {{ $totalDays }}</span>
                @endif
            </div>
            @if($leftDays >= 0 && $leftDays <= 30)
                <div class="small text-warning mt-1"><i class="fas fa-bell mr-1"></i>Segera putuskan: perpanjang atau akhiri kontrak.</div>
            @endif
        </div>
    @elseif($employee->status === 'kontrak')
        <div class="alert alert-warning py-2 small mb-3"><i class="fas fa-exclamation-triangle mr-1"></i>Status karyawan <b>Kontrak</b> tetapi belum ada kontrak aktif, jadi tanggal berakhirnya tidak diketahui. Buat kontraknya di bawah.</div>
    @elseif($employee->status === 'tidak aktif' && $employee->nonaktif_alasan)
        <div class="alert alert-secondary py-2 small mb-3"><i class="fas fa-user-slash mr-1"></i>Tidak aktif{{ $employee->nonaktif_tanggal ? ' sejak ' . $fmt($employee->nonaktif_tanggal) : '' }} · {{ \App\Models\HRD\Employee::NONAKTIF_ALASAN[$employee->nonaktif_alasan] ?? $employee->nonaktif_alasan }}</div>
    @endif

    @if($canRenew)
        {{-- Actions --}}
        <div class="ck-actions mb-3">
            <button type="button" class="ck-action{{ $openRenewForm ? ' active' : '' }}" data-toggle="collapse" data-target="#ck-renew" aria-expanded="{{ $openRenewForm ? 'true' : 'false' }}">
                <i class="fas fa-file-signature"></i>
                <span><b>{{ $contracts->isEmpty() ? 'Buat Kontrak' : 'Perpanjang Kontrak' }}</b><small>Kontrak baru mulai {{ $fmt($defaultStart) }}</small></span>
            </button>
            @if($activeContract)
                <button type="button" class="ck-action ck-action-danger" data-toggle="collapse" data-target="#ck-terminate" aria-expanded="false">
                    <i class="fas fa-user-times"></i>
                    <span><b>Putus Kontrak</b><small>Karyawan menjadi tidak aktif</small></span>
                </button>
            @endif
        </div>

        {{-- Perpanjang / buat kontrak --}}
        <form id="ck-renew" class="collapse{{ $openRenewForm ? ' show' : '' }} ck-form mb-3" enctype="multipart/form-data"
              data-url="{{ route('hrd.employee.contracts.store', $employee->id) }}"
              data-prev-end="{{ $lastContract ? $lastContract->end_date->format('Y-m-d') : '' }}" novalidate>
            <div class="form-row">
                <div class="form-group col-md-5">
                    <label for="ck-start" class="small font-weight-bold mb-1">Tanggal Mulai <span class="text-danger">*</span></label>
                    <input type="date" id="ck-start" name="start_date" class="form-control" value="{{ $defaultStart->format('Y-m-d') }}" required>
                    <small class="form-text" id="ck-start-hint"></small>
                </div>
                <div class="form-group col-md-7">
                    <label for="ck-duration" class="small font-weight-bold mb-1">Durasi <span class="text-danger">*</span></label>
                    <div class="d-flex flex-wrap align-items-center" style="gap: 6px;">
                        @foreach([3, 6, 12, 24] as $months)
                            <button type="button" class="btn btn-sm btn-outline-primary ck-duration-chip" data-months="{{ $months }}">{{ $months }} bln</button>
                        @endforeach
                        <div class="input-group input-group-sm" style="width: 120px;">
                            <input type="number" id="ck-duration" name="duration_months" class="form-control" value="{{ $defaultDuration }}" min="1" max="60" required>
                            <div class="input-group-append"><span class="input-group-text">bulan</span></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="ck-preview mb-3" id="ck-preview"></div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="ck-doc" class="small font-weight-bold mb-1">Dokumen Kontrak</label>
                    <div class="custom-file">
                        <input type="file" class="custom-file-input" id="ck-doc" name="contract_document" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        <label class="custom-file-label text-truncate" for="ck-doc" data-default="Pilih file">Pilih file</label>
                    </div>
                    <small class="text-muted">PDF / gambar / Word, maks. 2MB (opsional)</small>
                </div>
                <div class="form-group col-md-6">
                    <label for="ck-notes" class="small font-weight-bold mb-1">Catatan</label>
                    <textarea id="ck-notes" name="notes" class="form-control" rows="2" placeholder="Opsional, mis. hasil evaluasi"></textarea>
                </div>
            </div>
            <div class="ck-effects small mb-3">
                <div class="font-weight-bold mb-1">Setelah disimpan:</div>
                <ul class="mb-0 pl-3">
                    <li>Kontrak baru tercatat dengan status <b>Aktif</b>.</li>
                    @if($activeContract)<li>Kontrak berjalan ({{ $fmt($activeContract->start_date) }} – {{ $fmt($activeContract->end_date) }}) ditandai <b>Diperpanjang</b>.</li>@endif
                    @if($employee->status !== 'kontrak')<li>Status karyawan berubah dari <b>{{ ucfirst($employee->status ?? '-') }}</b> menjadi <b>Kontrak</b>{{ $employee->status === 'tidak aktif' ? ' (aktif kembali)' : '' }}.</li>@endif
                    <li>Sisa kontrak di daftar karyawan mengikuti tanggal berakhir yang baru.</li>
                </ul>
            </div>
            <div class="alert alert-danger py-2 small" id="ck-renew-error" style="display:none;"></div>
            <div class="text-right">
                <button type="button" class="btn btn-light border mr-1" data-toggle="collapse" data-target="#ck-renew">Batal</button>
                <button type="submit" class="btn btn-success" id="ck-renew-save"><i class="fas fa-save mr-1"></i>Simpan Kontrak</button>
            </div>
        </form>

        {{-- Putus kontrak --}}
        @if($activeContract)
            <form id="ck-terminate" class="collapse ck-form ck-form-danger mb-3" novalidate
                  data-url="{{ route('hrd.employee.contracts.terminate', [$employee->id, $activeContract->id]) }}"
                  data-start="{{ $activeContract->start_date->format('Y-m-d') }}" data-end="{{ $activeContract->end_date->format('Y-m-d') }}">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="ck-nonaktif-alasan" class="small font-weight-bold mb-1">Alasan <span class="text-danger">*</span></label>
                        <select id="ck-nonaktif-alasan" name="nonaktif_alasan" class="form-control" required>
                            <option value="">-- Pilih Alasan --</option>
                            @foreach(\App\Models\HRD\Employee::NONAKTIF_ALASAN as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="ck-nonaktif-tanggal" class="small font-weight-bold mb-1">Hari terakhir / berlaku sejak <span class="text-danger">*</span></label>
                        <input type="date" id="ck-nonaktif-tanggal" name="nonaktif_tanggal" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                        <small class="form-text" id="ck-terminate-hint"></small>
                    </div>
                </div>
                <div class="form-group">
                    <label for="ck-termination-notes" class="small font-weight-bold mb-1">Keterangan <span class="text-danger">*</span></label>
                    <textarea id="ck-termination-notes" name="termination_notes" class="form-control" rows="2" required placeholder="Mis. surat resign tanggal …, hasil evaluasi …"></textarea>
                </div>
                <div class="ck-effects ck-effects-danger small mb-3">
                    <div class="font-weight-bold mb-1">Setelah dikonfirmasi:</div>
                    <ul class="mb-0 pl-3">
                        <li>Kontrak {{ $fmt($activeContract->start_date) }} – {{ $fmt($activeContract->end_date) }} berstatus <b>Diputus</b>.</li>
                        <li>Status karyawan menjadi <b>Tidak Aktif</b> dan tidak muncul lagi di jadwal, absensi, dan pengajuan.</li>
                        <li>Bisa diaktifkan kembali dengan membuat kontrak baru.</li>
                    </ul>
                </div>
                <div class="alert alert-danger py-2 small" id="ck-terminate-error" style="display:none;"></div>
                <div class="text-right">
                    <button type="button" class="btn btn-light border mr-1" data-toggle="collapse" data-target="#ck-terminate">Batal</button>
                    <button type="submit" class="btn btn-danger" id="ck-terminate-save"><i class="fas fa-user-times mr-1"></i>Putus Kontrak</button>
                </div>
            </form>
        @endif
    @endif

    {{-- Riwayat --}}
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0"><i class="fas fa-history mr-1"></i>Riwayat Kontrak</h6>
        <span class="small text-muted">{{ $contracts->count() }} kontrak</span>
    </div>
    @if($contracts->isEmpty())
        <div class="text-center text-muted small py-3 border rounded"><i class="far fa-folder-open fa-2x d-block mb-2"></i>Belum ada riwayat kontrak.</div>
    @else
        <div class="ck-timeline">
            @foreach($contracts as $contract)
                @php
                    // No job stores 'expired' when a contract runs out: an active contract past its end date is shown as Selesai
                    $status = $contract->status === 'active' && $contract->end_date->lt(today()) ? 'expired' : $contract->status;
                    [$badge, $label] = [
                        'active' => ['success', 'Aktif'],
                        'renewed' => ['info', 'Diperpanjang'],
                        'expired' => ['secondary', 'Selesai'],
                        'terminated' => ['danger', 'Diputus'],
                    ][$status] ?? ['secondary', ucfirst($status)];
                @endphp
                <div class="ck-item ck-item-{{ $badge }}{{ $status === 'active' ? ' ck-item-active' : '' }}">
                    <div class="d-flex justify-content-between align-items-start flex-wrap">
                        <div>
                            <span class="font-weight-bold">{{ $fmt($contract->start_date) }} – {{ $fmt($contract->end_date) }}</span>
                            <span class="text-muted small ml-1">{{ $contract->duration_months }} bulan</span>
                        </div>
                        <div>
                            @if($contract->contract_document)
                                <a href="{{ asset('storage/' . $contract->contract_document) }}" class="btn btn-sm btn-link py-0" target="_blank" title="Dokumen kontrak"><i class="fas fa-paperclip mr-1"></i>Dokumen</a>
                            @endif
                            <span class="badge badge-{{ $badge }}">{{ $label }}</span>
                        </div>
                    </div>
                    @if($contract->notes)
                        <div class="small mt-1" style="white-space: pre-line;">{{ $contract->notes }}</div>
                    @endif
                    <div class="small text-muted mt-1">Dibuat oleh {{ optional($contract->creator)->name ?? 'Sistem' }}, {{ $contract->created_at->format('d/m/Y H:i') }}</div>
                </div>
            @endforeach
        </div>
    @endif
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
</div>
