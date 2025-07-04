<div class="modal-header bg-primary text-white">
    <h5 class="modal-title" id="contractModalLabel"><i class="fas fa-file-contract mr-2"></i>Detail Kontrak</h5>
    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<div class="modal-body">
    <div class="employee-info mb-4 p-3 bg-light rounded">
        <div class="row">
            <div class="col-12 mb-2">
                <h5>{{ $employee->nama }}</h5>
            </div>
            <div class="col-md-4">
                <p class="mb-1"><strong>NIK:</strong> {{ $employee->nik }}</p>
                <p class="mb-1"><strong>No Induk:</strong> {{ $employee->no_induk }}</p>
            </div>
            <div class="col-md-4">
                <p class="mb-1"><strong>Posisi:</strong> {{ optional($employee->position)->name ?? '-' }}</p>
                <p class="mb-1"><strong>Divisi:</strong> {{ optional($employee->division)->name ?? '-' }}</p>
            </div>
            <div class="col-md-4">
                <p class="mb-1"><strong>Status:</strong> 
                    <span class="badge badge-{{ $employee->status == 'tetap' ? 'success' : ($employee->status == 'kontrak' ? 'warning' : 'danger') }}">
                        {{ ucfirst($employee->status) }}
                    </span>
                </p>
            </div>
        </div>
    </div>
    
    <div class="contract-details">
        <h5 class="border-bottom pb-2">Informasi Kontrak</h5>
        <div class="row">
            <div class="col-md-6">
                <table class="table table-borderless">
                    <tr>
                        <th width="40%">Tanggal Mulai</th>
                        <td width="60%">{{ $contract->start_date->format('d M Y') }}</td>
                    </tr>
                    <tr>
                        <th>Tanggal Berakhir</th>
                        <td>{{ $contract->end_date->format('d M Y') }}</td>
                    </tr>
                    <tr>
                        <th>Durasi</th>
                        <td>{{ $contract->duration_months }} bulan</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            @php
                                $statusBadge = '';
                                $statusText = '';
                                switch($contract->status) {
                                    case 'active':
                                        $statusBadge = 'success';
                                        $statusText = 'Aktif';
                                        break;
                                    case 'renewed':
                                        $statusBadge = 'info';
                                        $statusText = 'Diperpanjang';
                                        break;
                                    case 'expired':
                                        $statusBadge = 'warning';
                                        $statusText = 'Habis';
                                        break;
                                    case 'terminated':
                                        $statusBadge = 'danger';
                                        $statusText = 'Diputus';
                                        break;
                                }
                            @endphp
                            <span class="badge badge-{{ $statusBadge }}">{{ $statusText }}</span>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-borderless">
                    <tr>
                        <th width="40%">Dibuat Oleh</th>
                        <td width="60%">{{ $contract->creator ? $contract->creator->name : 'Sistem' }}</td>
                    </tr>
                    <tr>
                        <th>Tanggal Dibuat</th>
                        <td>{{ $contract->created_at->format('d M Y H:i') }}</td>
                    </tr>
                    <tr>
                        <th>Terakhir Diupdate</th>
                        <td>{{ $contract->updated_at->format('d M Y H:i') }}</td>
                    </tr>
                </table>
            </div>
        </div>
        
        @if($contract->notes)
        <div class="row mt-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="m-0 font-weight-bold">Catatan Kontrak</h6>
                    </div>
                    <div class="card-body">
                        {!! nl2br(e($contract->notes)) !!}
                    </div>
                </div>
            </div>
        </div>
        @endif
        
        @if($contract->contract_document)
        <div class="row mt-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="m-0 font-weight-bold">Dokumen Kontrak</h6>
                    </div>
                    <div class="card-body">
                        <a href="{{ asset('storage/' . $contract->contract_document) }}" class="btn btn-info" target="_blank">
                            <i class="fas fa-file-download mr-1"></i> Download Dokumen Kontrak
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
<div class="modal-footer">
    @if($contract->status == 'active')
    <button class="btn btn-danger terminate-contract" 
            data-id="{{ $contract->id }}"
            data-employee="{{ $employee->id }}">
        <i class="fas fa-times mr-1"></i> Putus Kontrak
    </button>
    @endif
    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
</div>
