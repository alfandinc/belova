@extends('layouts.finance.app')
@section('title', 'Finance | Billing')
@section('navbar')
    @include('layouts.finance.navbar')
@endsection
@section('content')

@include('finance.partials.modal-billing-edititem')

@include('finance.billing.partials.create-styles')

<div class="container-fluid">
    <div id="pageLoadingOverlay" class="page-loading-overlay" aria-live="polite" aria-busy="true">
        <div class="text-center">
            <div class="spinner-border text-primary" role="status" aria-label="Memuat">
                <span class="sr-only">Memuat...</span>
            </div>
            <div class="mt-2 text-muted">Memuat data...</div>
        </div>
    </div>
    <!-- Prefill billing fields with old invoice data if available -->
    @php
        $latestPiutang = $invoice?->piutangs?->sortByDesc('id')->first();
    @endphp
    <script>
        window.oldInvoice = {
            id: @json($invoice?->id ?? null),
            invoice_number: @json($invoice?->invoice_number ?? null),
            total_amount: @json($invoice?->total_amount ?? null),
            global_discount: @json($invoice?->discount_value ?? ''),
            global_discount_type: @json($invoice?->discount_type ?? ''),
            tax_percentage: @json($invoice?->tax_percentage ?? ''),
            admin_fee: @json($invoice?->items?->first(function($item) { return stripos($item->name ?? '', 'Biaya Administrasi') !== false; })?->unit_price ?? ''),
            shipping_fee: @json($invoice?->items?->where('name', 'Biaya Ongkir')->first()?->unit_price ?? ''),
            amount_paid: @json($invoice?->amount_paid ?? ''),
            payment_method: @json($invoice?->payment_method ?? ''),
            piutang_payment_status: @json($latestPiutang?->payment_status ?? null),
            piutang_id: @json($latestPiutang?->id ?? null),
            piutang_amount: @json($latestPiutang?->amount ?? null),
            piutang_paid_amount: @json($latestPiutang?->paid_amount ?? null),
            change_amount: @json($invoice?->change_amount ?? '')
        };

        window.visitationMetodeBayarName = @json(optional($visitation->metodeBayar)->nama ?? null);

        // True when there is an existing invoice but billing was changed afterwards (server-side detection).
        window.invoiceNeedsUpdate = @json($invoiceNeedsUpdate ?? false);
        
        // Global variables for gudang data
        window.gudangData = {
            gudangs: [],
            mappings: {},
            loaded: false
        };
        
        // Load gudang data on page load
        function loadGudangData() {
            return $.ajax({
                url: '{{ route('finance.billing.gudang-data') }}',
                type: 'GET',
                success: function(response) {
                    window.gudangData.gudangs = response.gudangs || [];
                    window.gudangData.mappings = response.mappings || {};
                    window.gudangData.loaded = true;
                },
                error: function(xhr, status, error) {
                    console.error('Failed to load gudang data:', error);
                }
            });
        }

            function setInvoiceHeaderUi(invoiceNumber, invoiceId, amountPaid, totalAmount, paymentMethod, piutangPaymentStatus) {
                try {
                    if (invoiceNumber) {
                        $('.invoice-number').text(String(invoiceNumber));
                    }

                    const $badge = $('#invoiceStatusBadge');
                    if (!$badge.length) return;

                    // If all billings are trashed, status should stay "Terhapus".
                    if (window.allBillingsTrashed) {
                        $badge
                            .show()
                            .text('Terhapus')
                            .css({
                                color: '#fff',
                                background: '#6c757d',
                                padding: '2px 8px',
                                borderRadius: '8px',
                                fontSize: '13px'
                            });
                        return;
                    }

                    let text = 'Belum Transaksi';
                    let bg = '#dc3545';

                    const pm = (paymentMethod || '').toString().trim().toLowerCase();
                    const paid = Math.ceil(Number(amountPaid || 0)) >= Math.ceil(Number(totalAmount || 0)) && Number(totalAmount || 0) > 0;
                    const partial = Number(amountPaid || 0) > 0 && Number(totalAmount || 0) > 0 && Number(amountPaid || 0) < Number(totalAmount || 0);

                    if (pm === 'piutang') {
                        const ps = (piutangPaymentStatus || '').toString().trim().toLowerCase();
                        if (ps === 'paid') {
                            text = 'Lunas';
                            bg = '#28a745';
                        } else if (ps === 'partial') {
                            text = 'Belum Lunas';
                            bg = '#ffc107';
                        } else {
                            text = 'Piutang';
                            bg = '#17a2b8';
                        }
                    } else {
                        if (paid) {
                            text = 'Lunas';
                            bg = '#28a745';
                        } else if (partial) {
                            text = 'Belum Lunas';
                            bg = '#ffc107';
                        } else {
                            // invoice not paid yet (or no invoice): keep Belum Transaksi
                            text = 'Belum Transaksi';
                            bg = '#dc3545';
                        }
                    }

                    $badge
                        .show()
                        .text(text)
                        .css({
                            color: '#fff',
                            background: bg,
                            padding: '2px 8px',
                            borderRadius: '8px',
                            fontSize: '13px'
                        });
                } catch (e) {
                    // ignore
                }
            }

        // Simple page-ready gate to avoid UI flashing before data is ready
        window.__pageReadyGate = {
            gudangDone: false,
            tableDone: false,
            hideOverlayIfReady: function() {
                try {
                    if (!this.gudangDone || !this.tableDone) return;
                    var $ov = $('#pageLoadingOverlay');
                    if (!$ov.length) return;
                    $ov.addClass('is-hidden');
                    setTimeout(function() {
                        try { $ov.remove(); } catch (e) {}
                    }, 220);
                } catch (e) {
                    // ignore
                }
            }
        };
    </script>
    

    <div class="row mb-2">
        <div class="col-md-8">
            <div class="card shadow-sm mt-2 data-pasien">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user-circle mr-2"></i>Data Pasien
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td class="label"><strong>Nama</strong></td>
                                    <td>:
                                        <span class="patient-label">
                                            @if(isset($visitation->pasien->gender) && strtolower($visitation->pasien->gender) === 'laki-laki')
                                                <span class="gender-badge gender-male" title="Laki-laki" aria-label="Laki-laki"><i class="fas fa-mars" aria-hidden="true"></i><span class="sr-only">Laki-laki</span></span>
                                            @elseif(isset($visitation->pasien->gender) && strtolower($visitation->pasien->gender) === 'perempuan')
                                                <span class="gender-badge gender-female" title="Perempuan" aria-label="Perempuan"><i class="fas fa-venus" aria-hidden="true"></i><span class="sr-only">Perempuan</span></span>
                                            @endif
                                            <span class="patient-name">{{ strtoupper($visitation->pasien->nama) }}</span>
                                            <span class="patient-id">/ {{ $visitation->pasien->id }}</span>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label"><strong>Tanggal Lahir</strong></td>
                                    <td>:
                                        <span class="patient-meta">
                                            @if(!empty($visitation->pasien->tanggal_lahir))
                                                {{ \Carbon\Carbon::parse($visitation->pasien->tanggal_lahir)->locale('id')->translatedFormat('j F Y') }}
                                                <span class="patient-age">({{ \Carbon\Carbon::parse($visitation->pasien->tanggal_lahir)->age }} th)</span>
                                            @else
                                                -
                                            @endif
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label"><strong>Alamat</strong></td>
                                    <td>: <span class="patient-meta">{{ $visitation->pasien->alamat }}</span></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td class="label"><strong>Tanggal Kunjungan</strong></td>
                                    <td>:
                                        @if(!empty($visitation->tanggal_visitation))
                                            {{ \Carbon\Carbon::parse($visitation->tanggal_visitation)->locale('id')->translatedFormat('j F Y') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label"><strong>Dokter</strong></td>
                                    <td>:
                                        @if($visitation && $visitation->dokter)
                                            @php
                                                $specName = optional($visitation->dokter->spesialisasi)->nama;
                                                $specColors = ['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary'];
                                                $specBadgeClass = 'badge-secondary';
                                                if(!empty($specName)) {
                                                    $specBadgeClass = $specColors[abs(crc32($specName)) % count($specColors)];
                                                }
                                            @endphp
                                            {{ optional($visitation->dokter->user)->name ?? $visitation->dokter->nama ?? $visitation->dokter->name ?? '-' }}
                                            @if(!empty($specName))
                                                <span class="badge {{ $specBadgeClass }} ml-2">{{ $specName }}</span>
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label"><strong>Klinik</strong></td>
                                    <td>: {{ optional($visitation->klinik)->nama ?? optional($visitation->klinik)->name ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm mt-2">
                <div class="card-header d-flex align-items-center">
                    <h5 class="card-title mb-0"><i class="fas fa-tags mr-2"></i>Active Promos</h5>
                </div>
                <div class="card-body">
                    @php
                        $today = \Carbon\Carbon::today()->format('Y-m-d');
                        $activePromos = \App\Models\Marketing\Promo::where(function($q) use ($today){
                            $q->whereNotNull('start_date')->whereNotNull('end_date')
                                ->where('start_date','<=',$today)
                                ->where('end_date','>=',$today);
                        })->orWhere(function($q) use ($today){
                            $q->whereNotNull('start_date')->whereNull('end_date')
                                ->where('start_date','<=',$today);
                        })->orWhere(function($q) use ($today){
                            $q->whereNull('start_date')->whereNotNull('end_date')
                                ->where('end_date','>=',$today);
                        })->orderBy('start_date','desc')->take(5)->get();
                    @endphp
                    @if($activePromos->isEmpty())
                        <p class="mb-0 text-muted">No active promos</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless mb-0">
                                <tbody>
                                @foreach($activePromos as $p)
                                    <tr>
                                        <td style="width:45%">
                                            <div><strong>{{ $p->name }}</strong></div>
                                            <div><small class="text-muted">@if($p->start_date){{ \Carbon\Carbon::parse($p->start_date)->locale('id')->translatedFormat('j F Y') }}@endif @if($p->end_date) - {{ \Carbon\Carbon::parse($p->end_date)->locale('id')->translatedFormat('j F Y') }}@endif</small></div>
                                        </td>
                                        <td style="width:55%">{{ $p->description ?? '' }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-2">
        <div class="col">
            <div class="card shadow-sm">
                <div class="card-body py-2">
                    <div class="row">
                        {{--
                        <div class="col-md-3 mb-2">
                            <label for="select-tindakan">Tambah Tindakan</label>
                            <select id="select-tindakan" class="form-control select2"></select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label for="select-lab">Tambah Lab</label>
                            <select id="select-lab" class="form-control select2"></select>
                        </div>
                        --}}
                        <div class="col-md-3 mb-2">
                            <label for="select-konsultasi">Tambah Biaya Lain-Lain</label>
                            <select id="select-konsultasi" class="form-control select2"></select>
                        </div>
                        <div class="col-md-9 mb-2 text-right d-flex align-items-end justify-content-end">
                            <button id="closeBillingTabBtn" type="button" class="btn btn-danger font-weight-bold px-3" title="Tutup tab">
                                <i class="fas fa-times mr-2"></i> Tutup
                            </button>
                        </div>
                        {{--
                        <div class="col-md-3 mb-2">
                            <label for="select-obat">Tambah Produk/Obat</label>
                            <select id="select-obat" class="form-control select2"></select>
                        </div>
                        --}}
                    </div>
                </div>
                {{--
                <div class="card-body pt-0 pb-3">
                    <small class="form-text text-danger font-weight-bold">*Harap konfirmasi ke unit terkait jika melakukan penambahan item dari halaman billing.</small>
                </div>
                --}}
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-9">
            <div class="card shadow-sm mb-2">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-file-invoice-dollar mr-2"></i>Rincian Billing
                    </h5>
                    <div>
                        <strong class="invoice-number">{{ $invoice?->invoice_number ?? '-' }}</strong>
                        @php
                            $totalBillings = \App\Models\Finance\Billing::withTrashed()->where('visitation_id', $visitation->id)->count();
                            $trashedBillings = \App\Models\Finance\Billing::onlyTrashed()->where('visitation_id', $visitation->id)->count();
                            $allBillingsTrashed = ($totalBillings > 0 && $trashedBillings === $totalBillings);

                            $hasInvoice = !empty($invoice);
                            $amountPaid = $hasInvoice ? floatval($invoice->amount_paid ?? 0) : 0;
                            $totalAmount = $hasInvoice ? floatval($invoice->total_amount ?? 0) : 0;
                            $paymentMethod = $hasInvoice ? strtolower((string)($invoice->payment_method ?? '')) : '';
                            $piutangStatus = null;
                            if ($hasInvoice && $paymentMethod === 'piutang') {
                                try {
                                    $piutang = $invoice->piutangs ? $invoice->piutangs->first() : null;
                                    $piutangStatus = $piutang && isset($piutang->payment_status) ? strtolower((string)$piutang->payment_status) : null;
                                } catch (\Exception $e) {
                                    $piutangStatus = null;
                                }
                            }

                            // Match index page statuses: Terhapus / Belum Transaksi / Belum Lunas / Piutang / Lunas
                            $badgeText = 'Belum Transaksi';
                            $badgeBg = '#dc3545';

                            if ($allBillingsTrashed) {
                                $badgeText = 'Terhapus';
                                $badgeBg = '#6c757d';
                            } elseif (!$hasInvoice) {
                                $badgeText = 'Belum Transaksi';
                                $badgeBg = '#dc3545';
                            } elseif ($paymentMethod === 'piutang') {
                                if ($piutangStatus === 'paid') {
                                    $badgeText = 'Lunas';
                                    $badgeBg = '#28a745';
                                } elseif ($piutangStatus === 'partial') {
                                    $badgeText = 'Belum Lunas';
                                    $badgeBg = '#ffc107';
                                } else {
                                    $badgeText = 'Piutang';
                                    $badgeBg = '#17a2b8';
                                }
                            } else {
                                $isPaid = ($totalAmount > 0 && $amountPaid >= $totalAmount);
                                $isPartial = ($amountPaid > 0 && $amountPaid < $totalAmount);
                                if ($isPaid) {
                                    $badgeText = 'Lunas';
                                    $badgeBg = '#28a745';
                                } elseif ($isPartial) {
                                    $badgeText = 'Belum Lunas';
                                    $badgeBg = '#ffc107';
                                } else {
                                    $badgeText = 'Belum Transaksi';
                                    $badgeBg = '#dc3545';
                                }
                            }
                        @endphp
                        <script>
                            window.allBillingsTrashed = @json($allBillingsTrashed ?? false);
                        </script>
                        <span id="invoiceStatusBadge" style="color:#fff;background:{{ $badgeBg }};padding:2px 8px;border-radius:8px;font-size:13px;">{{ $badgeText }}</span>
                    </div>
                </div>
                <div class="card-body px-4 py-3">
                    <div class="table-responsive">
                        <table id="billingTable" class="table table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 5%">No.</th>
                                    <th style="width: 30%">Nama Item</th>
                                    <th style="width: 8%">Harga</th>
                                    <th style="width: 5%">Qty</th>
                                    <th style="width: 8%">Diskon</th>
                                    <th style="width: 8%">Total</th>
                                    <th style="width: 10%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card shadow-sm mb-2">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-calculator mr-2"></i>Total Pembayaran
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <span>Subtotal:</span>
                        <span id="subtotal" class="font-weight-bold">Rp 0</span>
                    </div>
                    
                    <div class="form-group row mb-2">
                        <div class="col-6">
                            <label for="global_discount">Diskon Global</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="global_discount" min="0" value="0" readonly>
                                <div class="input-group-append">
                                    <select class="form-control" id="global_discount_type">
                                        <option value="%">%</option>
                                        <option value="nominal">Rp</option>
                                    </select>
                                </div>
                            </div>
                            <div class="text-right mt-1">
                                <small id="global_discount_amount" class="text-muted">- Rp 0</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <label for="tax_percentage">Pajak (%)</label>
                            <input type="number" class="form-control" id="tax_percentage" min="0" value="0">
                            <div class="text-right mt-1">
                                <small id="tax_amount" class="text-muted">+ Rp 0</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group row mb-2">
                        <div class="col-6">
                            <label for="admin_fee">Biaya Administrasi</label>
                            <select class="form-control" id="admin_fee">
                                <option value="0">Rp 0</option>
                                <option value="10000">Rp 10.000</option>
                                <option value="15000">Rp 15.000</option>
                                <option value="20000">Rp 20.000</option>
                                <option value="25000">Rp 25.000</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label for="shipping_fee">Biaya Ongkir</label>
                            <input type="number" class="form-control" id="shipping_fee" min="0" value="0">
                        </div>
                    </div>
                    
                    <div class="border-top pt-3 mt-3">
                        <div class="d-flex justify-content-between">
                            <h5>Total:</h5>
                            <h5 id="grand_total" class="text-primary font-weight-bold">Rp 0</h5>
                        </div>
                    </div>
                    
                    <!-- Payment Section -->
                    <div id="paymentSection" style="display:none;">
                        <input type="hidden" id="amount_paid" value="0">
                        <select id="payment_method" style="display:none;">
                            <option value="cash">Tunai</option>
                            <option value="piutang">Piutang</option>
                            <option value="edc_bca">EDC BCA</option>
                            <option value="edc_bni">EDC BNI</option>
                            <option value="edc_bri">EDC BRI</option>
                            <option value="edc_mandiri">EDC Mandiri</option>
                            <option value="qris">QRIS</option>
                            <option value="transfer">Transfer</option>
                            <option value="shopee">Shopee</option>
                            <option value="tiktokshop">Tiktokshop</option>
                            <option value="tokopedia">Tokopedia</option>
                            <option value="asuransi_inhealth">Asuransi InHealth</option>
                            <option value="asuransi_brilife">Asuransi Brilife</option>
                            <option value="asuransi_admedika">Asuransi Admedika</option>
                            <option value="asuransi_bcalife">Asuransi BCA Life</option>
                        </select>

                        <span id="change_amount" style="display:none;"></span>
                        <span id="shortage_amount" style="display:none;"></span>
                        <div id="shortage_label" style="display:none;"></div>
                    </div>
                    
                    <div class="mt-4">
                        <div class="row" id="invoiceActionRow">
                            <div class="col-12" id="createInvoiceCol" @if($invoice) style="display:none;" @endif>
                                <button id="createInvoiceBtn" class="btn btn-primary btn-block">
                                    <i class="fas fa-file-invoice mr-1"></i> Buat Invoice
                                </button>
                            </div>
                            <div class="col-6 pr-1" id="terimaPembayaranCol" @if(!$invoice) style="display:none;" @endif>
                                <button id="terimaPembayaranBtn" class="btn btn-success btn-block">
                                    <i class="fas fa-file-invoice mr-1"></i> Pembayaran
                                </button>
                            </div>
                            <div class="col-6 pl-1" id="cetakNotaCol" @if(!$invoice) style="display:none;" @endif>
                                <button id="printNotaBtn" class="btn btn-outline-primary btn-block">
                                    Cetak Nota
                                </button>
                            </div>
                        </div>
                        {{--<button id="saveAllChangesBtn" class="btn btn-outline-secondary btn-block mt-2">
                            <i class="fas fa-save mr-1"></i> Simpan Billing
                        </button>--}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('finance.billing.partials.payment-modal')
@include('finance.billing.partials.modal-terima-pembayaran')
@endsection

@section('scripts')
    @include('finance.billing.partials.stock-info-modal-loader')
    <script>
        $(document).ready(function() {
        // Close button: try to close the browser tab; fallback to billing index
        $('#closeBillingTabBtn').on('click', function(e) {
            e.preventDefault();
            try {
                window.close();
            } catch (err) {
                // ignore
            }
            // If window.close() did not work (most browsers block it), redirect back
            setTimeout(function() {
                window.location.href = "{{ route('finance.billing.index') }}";
            }, 300);
        });

        // Prefill billing fields if old invoice exists
        if (window.oldInvoice) {
            if (window.oldInvoice.global_discount !== '') $('#global_discount').val(window.oldInvoice.global_discount);
            if (window.oldInvoice.global_discount_type !== '') $('#global_discount_type').val(window.oldInvoice.global_discount_type);
            if (window.oldInvoice.tax_percentage !== '') $('#tax_percentage').val(window.oldInvoice.tax_percentage);
            if (window.oldInvoice.admin_fee !== '') {
                let adminFeeValue = window.oldInvoice.admin_fee.toString().replace(/[,\.].*$/, '');
                $('#admin_fee').val(adminFeeValue).trigger('change');
            }
            if (window.oldInvoice.shipping_fee !== '') $('#shipping_fee').val(window.oldInvoice.shipping_fee);
            if (window.oldInvoice.amount_paid !== '') $('#amount_paid').val(window.oldInvoice.amount_paid);
            if (window.oldInvoice.payment_method !== '') $('#payment_method').val(window.oldInvoice.payment_method);
            if (window.oldInvoice.change_amount !== '') $('#change_amount').text('Rp ' + formatCurrency(window.oldInvoice.change_amount));
        }

        // --- 2-step invoice/payment flow UI state ---
        let currentInvoiceId = (window.oldInvoice && window.oldInvoice.id) ? window.oldInvoice.id : null;
        let currentInvoiceIsPaid = false;
        const canDeleteBilling = @json(auth()->check() && method_exists(auth()->user(), 'hasRole') && auth()->user()->hasRole('Admin'));
        // Billing becomes locked (non-editable) after payment is processed (including piutang).
        // Backend marks unpaid invoice creation with payment_method = null.
        let billingLocked = false;
        try {
            billingLocked = !!(window.oldInvoice && window.oldInvoice.payment_method);
        } catch (e) {
            billingLocked = false;
        }
        try {
            const paidRaw = parseFloat((window.oldInvoice && window.oldInvoice.amount_paid) ? window.oldInvoice.amount_paid : 0);
            const totalRaw = parseFloat((window.oldInvoice && window.oldInvoice.total_amount) ? window.oldInvoice.total_amount : 0);
            currentInvoiceIsPaid = (Number(totalRaw) > 0) && (Math.ceil(paidRaw) >= Math.ceil(totalRaw));
        } catch (e) {
            currentInvoiceIsPaid = false;
        }

        // For Piutang invoices, payment_status=paid means invoice is effectively paid.
        try {
            const pm = (window.oldInvoice && window.oldInvoice.payment_method) ? String(window.oldInvoice.payment_method).toLowerCase() : '';
            const ps = (window.oldInvoice && window.oldInvoice.piutang_payment_status) ? String(window.oldInvoice.piutang_payment_status).toLowerCase() : '';
            if (currentInvoiceId && pm === 'piutang' && ps === 'paid') {
                currentInvoiceIsPaid = true;
            }
        } catch (e) {
            // ignore
        }

        function setInvoiceFlowUi() {
            if (currentInvoiceId) {
                // When invoice exists, show actions based on paid status
                $('#createInvoiceCol').hide();
                $('#cetakNotaCol').show();

                if (currentInvoiceIsPaid) {
                    // Lunas: show only Cetak Nota
                    $('#terimaPembayaranCol').hide();
                    $('#cetakNotaCol').removeClass('col-6 pl-1').addClass('col-12');
                } else {
                    // Not lunas: show both buttons side-by-side
                    $('#terimaPembayaranCol').show();
                    $('#cetakNotaCol').removeClass('col-12').addClass('col-6 pl-1');
                }
            } else {
                $('#createInvoiceCol').show();
                $('#terimaPembayaranCol').hide();
                $('#cetakNotaCol').hide();
                // reset layout class in case it was expanded previously
                try {
                    $('#cetakNotaCol').removeClass('col-12').addClass('col-6 pl-1');
                } catch (e) {
                    // ignore
                }
                // ensure unpaid on first creation
                $('#amount_paid').val('0');
            }
        }

        function applyBillingLockUi() {
            // Disable add/select inputs
            try {
                ['#select-konsultasi', '#select-tindakan', '#select-lab', '#select-obat'].forEach(function(sel) {
                    const $el = $(sel);
                    if ($el && $el.length) {
                        $el.prop('disabled', !!billingLocked);
                        // select2 needs a change to reflect disabled state
                        try { $el.trigger('change.select2'); } catch (e) { /* ignore */ }
                    }
                });
            } catch (e) {
                // ignore
            }

            // When invoice is paid (Lunas), prevent changing totals inputs
            try {
                const lockTotalsInputs = !!currentInvoiceId && !!currentInvoiceIsPaid;
                ['#tax_percentage', '#admin_fee', '#shipping_fee'].forEach(function(sel) {
                    const $el = $(sel);
                    if ($el && $el.length) {
                        $el.prop('disabled', lockTotalsInputs);
                        try { $el.trigger('change'); } catch (e) { /* ignore */ }
                    }
                });
            } catch (e) {
                // ignore
            }
        }

        function calculatePaymentSummaryForModal() {
            // Ensure we have the latest grand total
            calculateTotals();
            const grandTotalInt = (window.billingTotals && window.billingTotals.grandTotalInt) ? window.billingTotals.grandTotalInt : 0;
            const modalPaid = parseHarga($('#modal_amount_paid').val() || 0);
            const modalPaidInt = Math.ceil(modalPaid);
            const changeAmount = Math.max(0, modalPaidInt - grandTotalInt);
            const shortageAmount = Math.max(0, grandTotalInt - modalPaidInt);

            $('#modal_change_amount').text('Rp ' + formatCurrency(changeAmount));
            if (shortageAmount > 0) {
                $('#modal_shortage_amount').text('Rp ' + formatCurrency(shortageAmount));
                $('#modal_shortage_label').removeClass('d-none');
                $('#modal_piutang_info').removeClass('d-none');
            } else {
                $('#modal_shortage_amount').text('Rp 0');
                $('#modal_shortage_label').addClass('d-none');
                $('#modal_piutang_info').addClass('d-none');
            }
        }

        function getPaymentActionLabel(methodRaw) {
            const method = (methodRaw || 'cash').toString();
            return method === 'piutang' ? 'Buat Piutang' : 'Pembayaran';
        }

        function updatePaymentActionButtons(methodRaw) {
            // If invoice has an active piutang, primary action should be "Lunasi Pembayaran"
            // (handled via Terima Pembayaran modal like billing index page).
            try {
                const piutangId = window.oldInvoice && window.oldInvoice.piutang_id ? window.oldInvoice.piutang_id : null;
                const piutangStatus = (window.oldInvoice && window.oldInvoice.piutang_payment_status) ? String(window.oldInvoice.piutang_payment_status).toLowerCase() : '';
                const piutangAmount = Number(window.oldInvoice && window.oldInvoice.piutang_amount ? window.oldInvoice.piutang_amount : 0);
                const piutangPaid = Number(window.oldInvoice && window.oldInvoice.piutang_paid_amount ? window.oldInvoice.piutang_paid_amount : 0);
                const remaining = piutangAmount - piutangPaid;
                const hasActivePiutang = !!piutangId && (!piutangStatus || piutangStatus === 'unpaid' || piutangStatus === 'partial') && isFinite(remaining) && remaining > 0;

                if (hasActivePiutang && $('#terimaPembayaranBtn').length) {
                    $('#terimaPembayaranBtn').html('<i class="fas fa-file-invoice mr-1"></i> Lunasi Pembayaran');
                    $('#terimaPembayaranBtn').removeClass('btn-success').addClass('btn-warning');
                    // Keep the modal confirm button label consistent when user opens paymentModal manually.
                    $('#confirmPaymentBtn').text('Lunasi Pembayaran');
                    try {
                        $('#confirmPaymentBtn').removeClass('btn-success').addClass('btn-warning');
                    } catch (e) {
                        // ignore
                    }
                    return;
                }
            } catch (e) {
                // ignore
            }

            // If invoice exists but billing changed, force the primary action to "Update Invoice".
            try {
                if (currentInvoiceId && !currentInvoiceIsPaid && !billingLocked && invoiceNeedsUpdateNow()) {
                    $('#terimaPembayaranBtn').html('<i class="fas fa-file-invoice mr-1"></i> Update Invoice');
                    $('#terimaPembayaranBtn').removeClass('btn-success').addClass('btn-warning');
                    return;
                }
            } catch (e) {
                // ignore
            }

            const label = getPaymentActionLabel(methodRaw);
            const method = (methodRaw || 'cash').toString();
            const isPiutang = method === 'piutang';

            function applyBtnColor($btn) {
                if (!$btn || !$btn.length) return;
                $btn.removeClass('btn-success btn-warning');
                $btn.addClass(isPiutang ? 'btn-warning' : 'btn-success');
            }

            $('#confirmPaymentBtn').text(label);
            applyBtnColor($('#confirmPaymentBtn'));
            if ($('#terimaPembayaranBtn').length) {
                $('#terimaPembayaranBtn').html('<i class="fas fa-file-invoice mr-1"></i> ' + label);
                applyBtnColor($('#terimaPembayaranBtn'));
            }
        }

        let invoiceNeedsUpdateServer = false;
        try {
            invoiceNeedsUpdateServer = (window.invoiceNeedsUpdate === true || window.invoiceNeedsUpdate === 1 || window.invoiceNeedsUpdate === '1');
        } catch (e) {
            invoiceNeedsUpdateServer = false;
        }

        function isTempBillingId(id) {
            try {
                const s = (id || '').toString();
                return s.startsWith('tindakan-') || s.startsWith('lab-') || s.startsWith('konsultasi-') || s.startsWith('obat-') || s.startsWith('racikan-');
            } catch (e) {
                return false;
            }
        }

        function hasLocalBillingChanges() {
            try {
                if (!Array.isArray(billingData)) return false;
                const itemsChanged = billingData.some(function(item) {
                    if (!item) return false;
                    return !!item.edited || !!item.deleted || isTempBillingId(item.id);
                }) || (Array.isArray(deletedItems) && deletedItems.length > 0);

                // Also treat header totals inputs as invoice changes (tax/admin/shipping/etc)
                // so user can update invoice without changing table items.
                let totalsChanged = false;
                try {
                    if (currentInvoiceId && window.oldInvoice && !currentInvoiceIsPaid && !billingLocked) {
                        const oldTax = parseFloat((window.oldInvoice.tax_percentage !== null && typeof window.oldInvoice.tax_percentage !== 'undefined' && window.oldInvoice.tax_percentage !== '') ? window.oldInvoice.tax_percentage : 0) || 0;
                        const nowTax = parseFloat($('#tax_percentage').val() || 0) || 0;

                        const oldAdmin = parseHarga((window.oldInvoice.admin_fee !== null && typeof window.oldInvoice.admin_fee !== 'undefined' && window.oldInvoice.admin_fee !== '') ? window.oldInvoice.admin_fee : 0) || 0;
                        const nowAdmin = parseHarga($('#admin_fee').val() || 0) || 0;

                        const oldShip = parseHarga((window.oldInvoice.shipping_fee !== null && typeof window.oldInvoice.shipping_fee !== 'undefined' && window.oldInvoice.shipping_fee !== '') ? window.oldInvoice.shipping_fee : 0) || 0;
                        const nowShip = parseHarga($('#shipping_fee').val() || 0) || 0;

                        const oldDisc = parseHarga((window.oldInvoice.global_discount !== null && typeof window.oldInvoice.global_discount !== 'undefined' && window.oldInvoice.global_discount !== '') ? window.oldInvoice.global_discount : 0) || 0;
                        const nowDisc = parseHarga($('#global_discount').val() || 0) || 0;

                        const oldDiscType = (window.oldInvoice.global_discount_type !== null && typeof window.oldInvoice.global_discount_type !== 'undefined') ? String(window.oldInvoice.global_discount_type) : '';
                        const nowDiscType = String($('#global_discount_type').val() || '');

                        totalsChanged = (Math.abs(oldTax - nowTax) > 0.0001)
                            || (Math.ceil(oldAdmin) !== Math.ceil(nowAdmin))
                            || (Math.ceil(oldShip) !== Math.ceil(nowShip))
                            || (Math.ceil(oldDisc) !== Math.ceil(nowDisc))
                            || (oldDiscType !== nowDiscType);
                    }
                } catch (e) {
                    totalsChanged = false;
                }

                return !!itemsChanged || !!totalsChanged;
            } catch (e) {
                return false;
            }
        }

        function invoiceNeedsUpdateNow() {
            return !!invoiceNeedsUpdateServer || hasLocalBillingChanges();
        }

        let lastManualCashPaidModal = null;

        function getCurrentGrandTotalInt() {
            calculateTotals();
            return (window.billingTotals && window.billingTotals.grandTotalInt) ? window.billingTotals.grandTotalInt : 0;
        }

        function isNonCashNonPiutang(methodRaw) {
            const method = (methodRaw || '').toString();
            return method !== '' && method !== 'cash' && method !== 'piutang';
        }

        function syncModalPaidFromMethod() {
            const method = ($('#modal_payment_method').val() || '').toString();

            // If user picks any non-tunai method (transfer/debit/qris/asuransi/etc),
            // auto-fill Dibayar to the grand total.
            if (isNonCashNonPiutang(method)) {
                const grandTotalInt = getCurrentGrandTotalInt();
                $('#modal_amount_paid').val(formatCurrency(grandTotalInt));
                return;
            }

            // If user picks piutang, keep Dibayar empty (treated as 0).
            if (method === 'piutang') {
                $('#modal_amount_paid').val('');
            }
        }

        function syncModalMethodFromPaid() {
            const paid = parseHarga($('#modal_amount_paid').val() || 0);
            const currentMethod = ($('#modal_payment_method').val() || '').toString();

            // If payment method is locked (insurance), never override the method.
            if (lockedPaymentMethod) {
                return;
            }

            // If user already selected a non-cash method, keep it.
            if (isNonCashNonPiutang(currentMethod)) {
                return;
            }

            // Old rule (still applies for cash/piutang flow):
            // - paid == 0 => piutang
            // - paid > 0 => cash (only if method is empty/piutang)
            if (paid > 0) {
                if (!currentMethod || currentMethod === 'piutang') {
                    $('#modal_payment_method').val('cash');
                }
            } else {
                $('#modal_payment_method').val('piutang');
            }
        }

        function openPrintNota(invoiceId) {
            if (!invoiceId) {
                Swal.fire({
                    title: 'Info',
                    text: 'Invoice belum tersedia untuk dicetak.',
                    icon: 'info'
                });
                return;
            }

            var printUrl = ('{{ url('/finance/invoice') }}/' + invoiceId + '/print-nota');
            Swal.fire({
                title: 'Preview Cetak Nota',
                html: '<div style="min-height:70vh"><iframe id="print-frame" src="' + printUrl + '" frameborder="0" style="width:100%;height:68vh"></iframe></div>',
                width: '90%',
                showCloseButton: true,
                showCancelButton: false,
                showConfirmButton: false,
                allowOutsideClick: false,
                allowEscapeKey: false
            });
        }

        setInvoiceFlowUi();
        applyBillingLockUi();
        // Default: dibayar starts 0 => method piutang (unless locked by insurer)
        try {
            const paidInit = parseHarga($('#amount_paid').val() || 0);
            if (!lockedPaymentMethod && paidInit <= 0) {
                $('#payment_method').val('piutang');
            }
        } catch (e) {
            // ignore
        }

        updatePaymentActionButtons($('#payment_method').val() || 'piutang');
        $('.select2').select2({ width: '100%' });
        applyBillingLockUi();

            // Initialize header badge state from server-provided invoice (if any)
            try {
                const invId = (window.oldInvoice && window.oldInvoice.id) ? window.oldInvoice.id : null;
                setInvoiceHeaderUi(
                    (window.oldInvoice && window.oldInvoice.invoice_number) ? window.oldInvoice.invoice_number : null,
                    invId,
                    (window.oldInvoice && typeof window.oldInvoice.amount_paid !== 'undefined') ? window.oldInvoice.amount_paid : 0,
                        (window.oldInvoice && typeof window.oldInvoice.total_amount !== 'undefined') ? window.oldInvoice.total_amount : 0,
                        (window.oldInvoice && typeof window.oldInvoice.payment_method !== 'undefined') ? window.oldInvoice.payment_method : null,
                        (window.oldInvoice && typeof window.oldInvoice.piutang_payment_status !== 'undefined') ? window.oldInvoice.piutang_payment_status : null
                );
            } catch (e) {
                // ignore
            }

        function resolveLockedPaymentMethod(visitationNameRaw) {
            if (!visitationNameRaw) return null;
            const v = visitationNameRaw.toString().trim().toLowerCase();
            if (!v) return null;

            // Match by contains to handle variations like "Asuransi InHealth"
            if (v.includes('inhealth')) return 'asuransi_inhealth';
            if (v.includes('bri') && v.includes('life')) return 'asuransi_brilife';
            if (v.includes('brilife')) return 'asuransi_brilife';
            if (v.includes('admedika')) return 'asuransi_admedika';
            if (v.includes('bca') && v.includes('life')) return 'asuransi_bcalife';
            if (v.includes('bcalife')) return 'asuransi_bcalife';
            return null;
        }

        function applyLockedPaymentMethodToSelect($select, lockedValue) {
            if (!$select || !$select.length) return;
            if (lockedValue) {
                $select.val(lockedValue);
                $select.prop('disabled', true);
            } else {
                $select.prop('disabled', false);
            }
        }

        function isUmumMetodeBayar(visitationNameRaw) {
            if (!visitationNameRaw) return false;
            try {
                const v = visitationNameRaw.toString().trim().toLowerCase();
                return v === 'umum' || v.includes('umum');
            } catch (e) {
                return false;
            }
        }

        function setInsuranceOptionsVisible($select, visible) {
            if (!$select || !$select.length) return;
            const $insuranceOptions = $select.find('option').filter(function() {
                const val = ($(this).attr('value') || '').toString();
                return val.startsWith('asuransi_');
            });

            $insuranceOptions.each(function() {
                // hide + disable so it can't be selected
                $(this).prop('hidden', !visible);
                $(this).prop('disabled', !visible);
            });

            // If current value is now hidden, reset to cash
            const currentVal = $select.val();
            if (!visible && currentVal && currentVal.toString().startsWith('asuransi_')) {
                $select.val('cash');
            }
        }

        const lockedPaymentMethod = resolveLockedPaymentMethod(window.visitationMetodeBayarName);
        const isUmumVisit = isUmumMetodeBayar(window.visitationMetodeBayarName);

        // If visit is Umum, hide all insurance options
        setInsuranceOptionsVisible($('#payment_method'), !isUmumVisit);

        // Keep hidden field aligned too
        applyLockedPaymentMethodToSelect($('#payment_method'), lockedPaymentMethod);
        
        // Load gudang data first (used by stock modal + mappings)
        const gudangLoadXhr = loadGudangData();
        if (gudangLoadXhr && typeof gudangLoadXhr.always === 'function') {
            gudangLoadXhr.always(function() {
                try {
                    if (window.__pageReadyGate) {
                        window.__pageReadyGate.gudangDone = true;
                        window.__pageReadyGate.hideOverlayIfReady();
                    }
                } catch (e) {}
            });
        } else {
            try {
                if (window.__pageReadyGate) {
                    window.__pageReadyGate.gudangDone = true;
                }
            } catch (e) {}
        }
        
        // Store all billing data (with changes) here
        let billingData = [];
        let deletedItems = [];
        let billingTableAutoRefreshIntervalId = null;
        let isManualTableUpdateInProgress = false;
        let isAutoRefreshInFlight = false;
        let lastAutoRefreshAt = 0;

        // When true, the next DataTable AJAX request asks backend for a lighter response.
        // Used only for periodic polling refresh.
        window.__billingLightRefresh = false;

        // Simple in-memory cache for stok per (obat_id + gudang_id)
        const stockCache = new Map();
        const STOCK_CACHE_TTL_MS = 8000; // keep slightly > refresh interval to avoid flicker

        @include('finance.billing.partials.create-script-stockinfo')
        
        // Function to collect gudang selections for invoice creation
        function collectGudangSelections() {
            const selections = {};
            
            $('.gudang-selector').each(function() {
                const itemId = $(this).data('item-id');
                const gudangId = $(this).val();
                if (itemId && gudangId) {
                    selections[itemId] = gudangId;
                }
            });

            // Also include selections stored in billingData (e.g. set by stock modal for tindakan)
            try {
                if (Array.isArray(billingData)) {
                    billingData.forEach(function(item) {
                        if (!item || !item.id) return;
                        if (selections[item.id]) return;
                        if (item.selected_gudang_id) {
                            selections[item.id] = item.selected_gudang_id;
                        }
                    });
                }
            } catch (e) {
                // ignore
            }
            
            return selections;
        }
        
        // Initialize DataTable
        const table = $('#billingTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: false, // Turn off responsive to avoid column collapsing
            scrollX: false,    // Disable horizontal scrolling
            autoWidth: false,  // Don't automatically calculate column widths
            paging: true,
            // Default to show all items on first load ("Semua")
            pageLength: -1,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Semua"]],
            stripeClasses: ['odd', 'even'], // Add zebra-striping
            ajax: {
                url: "{{ route('finance.billing.create', $visitation->id) }}",
                type: "GET",
                data: function(d) {
                    try {
                        if (window.__billingLightRefresh) {
                            d.light = 1;
                        }
                    } catch (e) {
                        // ignore
                    }
                },
                dataSrc: function(json) {
                    // When invoice is locked (payment processed), backend serves snapshot rows from invoice items.
                    // Refresh billingData from response so totals and UI reflect frozen invoice data.
                    try {
                        if (json && (json.locked_invoice_items === 1 || json.locked_invoice_items === '1' || json.locked_invoice_items === true)) {
                            json.data = (json.data || []).map(function(item) {
                                if (!item) return item;
                                // Ensure raw numeric values exist
                                if (typeof item.harga_akhir_raw === 'undefined' && item.harga_akhir) {
                                    const hargaString = String(item.harga_akhir).replace(/Rp\s?/g, '').replace(/\./g, '');
                                    item.harga_akhir_raw = parseInt(hargaString) || 0;
                                }
                                if (typeof item.jumlah_raw === 'undefined' && item.jumlah) {
                                    const jumlahString = String(item.jumlah).replace(/Rp\s?/g, '').replace(/\./g, '');
                                    item.jumlah_raw = parseInt(jumlahString) || 0;
                                }
                                if (typeof item.diskon_raw === 'undefined') {
                                    item.diskon_raw = 0;
                                }
                                if (typeof item.qty === 'undefined') {
                                    item.qty = 1;
                                }
                                return item;
                            });

                            billingData = json.data;
                        }
                    } catch (e) {
                        // ignore
                    }

                    // Update invoice-needs-update flag from backend so button can change without page refresh.
                    try {
                        if (typeof json.invoice_needs_update !== 'undefined') {
                            invoiceNeedsUpdateServer = (json.invoice_needs_update === true || json.invoice_needs_update === 1 || json.invoice_needs_update === '1');
                        }
                    } catch (e) {
                        // ignore
                    }

                    // Store the initial data
                    if (!billingData.length) {
                        // Process each item to ensure it has proper raw values
                        json.data = json.data.map(function(item) {
                            // Extract raw numeric values from formatted strings
                            if (!item.harga_akhir_raw) {
                                // Remove currency symbol and dot separators, then parse
                                const hargaString = item.harga_akhir.replace(/Rp\s?/g, '').replace(/\./g, '');
                                item.harga_akhir_raw = parseInt(hargaString) || 0;
                                
                                // Also ensure other raw values are set
                                if (!item.jumlah_raw) {
                                    const jumlahString = item.jumlah.replace(/Rp\s?/g, '').replace(/\./g, '');
                                    item.jumlah_raw = parseInt(jumlahString) || 0;
                                }
                            }
                            return item;
                        });
                        
                        billingData = json.data;
                    } else {
                        // Merge new data with existing data that has deletion flags
                        json.data = json.data.map(function(item) {
                            // Find matching item in our existing data
                            const existingItem = billingData.find(i => i.id === item.id);
                            
                            // If it exists and is marked as deleted, keep deleted flag
                            if (existingItem && existingItem.deleted) {
                                item.deleted = true;
                            }
                            
                            // If it exists and has edited values, keep those values
                            if (existingItem && existingItem.edited) {
                                item.jumlah_raw = existingItem.jumlah_raw;
                                item.diskon_raw = existingItem.diskon_raw;
                                item.diskon_type = existingItem.diskon_type;
                                item.harga_akhir_raw = existingItem.harga_akhir_raw;
                                item.jumlah = existingItem.jumlah;
                                item.diskon = existingItem.diskon;
                                item.harga_akhir = existingItem.harga_akhir;
                                item.edited = true;
                            } else if (!item.harga_akhir_raw) {
                                // Extract raw numeric values for new or unchanged items
                                const hargaString = item.harga_akhir.replace(/Rp\s?/g, '').replace(/\./g, '');
                                item.harga_akhir_raw = parseInt(hargaString) || 0;
                                
                                if (!item.jumlah_raw) {
                                    const jumlahString = item.jumlah.replace(/Rp\s?/g, '').replace(/\./g, '');
                                    item.jumlah_raw = parseInt(jumlahString) || 0;
                                }
                            }

                            // Preserve last-known stock hint and selected gudang when backend responds in light mode.
                            if (existingItem) {
                                if (typeof item.is_out_of_stock === 'undefined' && typeof existingItem.is_out_of_stock !== 'undefined') {
                                    item.is_out_of_stock = existingItem.is_out_of_stock;
                                }
                                if (typeof item.selected_gudang_id === 'undefined' && existingItem.selected_gudang_id) {
                                    item.selected_gudang_id = existingItem.selected_gudang_id;
                                }
                            }
                            
                            return item;
                        });
                        
                        // Update our billingData 
                        billingData = json.data;
                    }
                    
                    // Filter out deleted items from display
                    const visibleData = json.data.filter(item => !item.deleted);
                    
                    // Calculate totals after data is loaded
                    calculateTotals();

                    // Ensure primary action button reflects latest state
                    try {
                        updatePaymentActionButtons($('#payment_method').val() || 'piutang');
                    } catch (e) {
                        // ignore
                    }
                    
                    return visibleData;
                }
            },
            columns: [
                { 
                    data: null, 
                    orderable: false,
                    searchable: false,
                    width: "5%",
                    render: function (data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                },
                {
                    data: 'nama_item',
                    name: 'nama_item',
                    width: "18%",
                    render: function(data, type, row) {
                        if (type !== 'display') return data;
                        const label = escapeHtml(data || '-');

                        // Locked invoices use snapshot rows (invoice items). Do not offer stock link.
                        if (billingLocked) {
                            return '<span>' + label + '</span>';
                        }

                        const isOut = !!(row && (row.is_out_of_stock === true || row.is_out_of_stock === 1 || row.is_out_of_stock === '1'));
                        const linkClass = 'stock-info-link text-decoration-none' + (isOut ? ' text-danger' : '');
                        const iconClass = 'stock-warning-icon' + (isOut ? '' : ' d-none');
                        return (
                            '<a href="#" class="' + linkClass + '" data-id="' + row.id + '">' +
                                label +
                                ' <span class="' + iconClass + '" data-id="' + row.id + '" title="Stok tidak cukup">' +
                                    '<i class="fas fa-exclamation-triangle text-danger stock-warning-blink"></i>' +
                                '</span>' +
                            '</a>'
                        );
                    }
                },
                { data: 'jumlah', name: 'jumlah', width: "8%" },
                { data: 'qty', name: 'qty', width: "5%" },
                { data: 'diskon', name: 'diskon', width: "8%" },
                { data: 'harga_akhir', name: 'harga_akhir', width: "8%",
                                  render: function(data, type, row) {
                                      const qty = row.qty ? Number(row.qty) : 1;

                                      // Prefer server-provided promo base when present
                                      if (typeof row.promo_price_base !== 'undefined' && row.promo_price_base && row.diskon_type === '%' && row.diskon_raw) {
                                          const base = Number(row.promo_price_base) || 0;
                                          const percent = Number(row.diskon_raw) || 0;
                                          const unitAfter = base - (base * (percent / 100));
                                          const totalAfter = unitAfter * qty;
                                          return 'Rp ' + formatCurrency(totalAfter);
                                      }

                                      // If server provided harga_akhir_raw (line total after discount), use it
                                      if (typeof row.harga_akhir_raw !== 'undefined' && !isNaN(row.harga_akhir_raw) && (!row.edited)) {
                                          const lineTotal = Number(row.harga_akhir_raw) || 0;
                                          return 'Rp ' + formatCurrency(lineTotal);
                                      }

                                      // Fallback calculation: compute discount from (unit * qty)
                                      const harga = (typeof row.jumlah_raw !== 'undefined' && !isNaN(row.jumlah_raw)) ? Number(row.jumlah_raw) : 0;
                                      const lineNoDisc = harga * qty;
                                      let lineAfter = lineNoDisc;
                                      if (row.diskon_raw && row.diskon_raw > 0) {
                                          if (row.diskon_type === '%') {
                                              lineAfter = lineNoDisc - (lineNoDisc * (row.diskon_raw / 100));
                                          } else {
                                              lineAfter = lineNoDisc - row.diskon_raw;
                                          }
                                      }
                                      lineAfter = Math.max(0, lineAfter);
                                      return 'Rp ' + formatCurrency(lineAfter);
                                  }
                                },
                { 
                    data: null,
                    width: "10%",
                    className: 'text-center',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row, meta) {
                        if (billingLocked) {
                            return '<span class="text-muted">-</span>';
                        }
                        const deleteBtnHtml = canDeleteBilling ? `
                                <button class="btn btn-sm btn-outline-danger delete-btn"
                                    data-id="${row.id}"
                                    data-row-index="${meta.row}">
                                    <i class="fas fa-trash"></i>
                                </button>
                        ` : '';
                        return `
                            <div class="d-flex justify-content-center">
                                <button class="btn btn-sm btn-outline-primary mr-1 edit-btn" 
                                    data-id="${row.id}" 
                                    data-row-index="${meta.row}"
                                    data-jumlah="${row.is_racikan ? row.racikan_total_price : row.jumlah_raw}" 
                                    data-diskon="${row.diskon_raw || ''}" 
                                    data-diskon_type="${row.diskon_type || ''}"
                                    data-qty="${row.qty || 1}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                ${deleteBtnHtml}
                            </div>
                        `;
                    }
                }
            ],
            columnDefs: [
                { width: "5%", targets: 0 },
                { width: "22%", targets: 1 },
                { width: "8%", targets: 2, className: 'text-right' }, // Right-align price column
                { width: "5%", targets: 3 },
                { width: "8%", targets: 4, className: 'text-right' }, // Right-align discount column
                { width: "8%", targets: 5, className: 'text-right' }, // Right-align total column
                { width: "10%", targets: 6 }
            ],
            language: {
                emptyTable: "Tidak ada item billing untuk kunjungan ini",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ item",
                search: "Cari:",
                paginate: {
                    first: "Pertama",
                    last: "Terakhir",
                    next: "Selanjutnya",
                    previous: "Sebelumnya"
                },
                lengthMenu: "Tampilkan _MENU_ item"
            },
            drawCallback: function() {
                // Force table width to match parent container
                $(this).css('width', '100%');
                calculateTotals();

                // Background-check stock for visible rows and toggle warning icon
                try {
                    updateStockWarningsForVisibleRows();
                } catch (e) {
                    // ignore
                }
            }
        });

        // Mark table as ready after first AJAX response (or error) so overlay can be hidden
        try {
            table.one('xhr.dt', function() {
                if (window.__pageReadyGate) {
                    window.__pageReadyGate.tableDone = true;
                    window.__pageReadyGate.hideOverlayIfReady();
                }
            });
            table.one('error.dt', function() {
                if (window.__pageReadyGate) {
                    window.__pageReadyGate.tableDone = true;
                    window.__pageReadyGate.hideOverlayIfReady();
                }
            });
        } catch (e) {
            if (window.__pageReadyGate) {
                window.__pageReadyGate.tableDone = true;
                window.__pageReadyGate.hideOverlayIfReady();
            }
        }

        // --- Stock warning icon logic (out-of-stock) ---
    let stockWarningServerHintsValid = true;
        const itemStockWarningCache = new Map(); // itemId -> { outOfStock, at }
        const ITEM_STOCK_WARN_TTL_MS = 8000;
        const stockWarningInFlight = new Set();

        const tindakanObatRowsCache = new Map(); // riwayatId -> { rows, suggestedGudangId, at }
        const TINDAKAN_ROWS_TTL_MS = 60000;

        function setStockWarningIcon(itemId, shouldShow) {
            const $el = $('#billingTable').find('.stock-warning-icon[data-id="' + itemId + '"]');
            if (!$el.length) return;
            const $link = $('#billingTable').find('.stock-info-link[data-id="' + itemId + '"]');
            if (shouldShow) $el.removeClass('d-none');
            else $el.addClass('d-none');

            if ($link.length) {
                if (shouldShow) $link.addClass('text-danger');
                else $link.removeClass('text-danger');
            }
        }

        function getRiwayatTindakanObatRowsCached(riwayatTindakanId) {
            const key = String(riwayatTindakanId);
            const cached = tindakanObatRowsCache.get(key);
            const now = Date.now();
            if (cached && cached.at && (now - cached.at) <= TINDAKAN_ROWS_TTL_MS) {
                return $.Deferred().resolve({ rows: cached.rows || [], suggestedGudangId: cached.suggestedGudangId || null }).promise();
            }

            return loadRiwayatTindakanObatRows(riwayatTindakanId)
                .then(function(resp) {
                    const rows = resp && resp.rows ? resp.rows : [];
                    const suggestedGudangId = resp && resp.suggestedGudangId ? resp.suggestedGudangId : null;
                    tindakanObatRowsCache.set(key, { rows: rows, suggestedGudangId: suggestedGudangId, at: Date.now() });
                    return { rows: rows, suggestedGudangId: suggestedGudangId };
                });
        }

        function isItemOutOfStock(itemId, item) {
            if (!itemId || !item) {
                return $.Deferred().resolve(false).promise();
            }

            // Only check tracked stock types (obat/racikan/tindakan)
            const isTindakan = item.billable_type === 'App\\Models\\ERM\\RiwayatTindakan';
            const isObat = item.billable_type === 'App\\Models\\ERM\\ResepFarmasi' || item.billable_type === 'App\\Models\\ERM\\Obat' || !!item.obat_id;
            const isRacikan = !!item.is_racikan;
            if (!isTindakan && !isObat && !isRacikan) {
                return $.Deferred().resolve(false).promise();
            }

            let gudangId = getSelectedGudangIdForItem(itemId, item);
            if (!gudangId) {
                return $.Deferred().resolve(false).promise();
            }

            const baseRows = buildObatRowsForStockModal(item);

            // Special-case tindakan: pull obat needs from pivot table
            if ((!baseRows || !baseRows.length) && isTindakan && item.billable_id) {
                return getRiwayatTindakanObatRowsCached(item.billable_id)
                    .then(function(resp) {
                        const pivotRows = resp && resp.rows ? resp.rows : [];
                        const suggestedGudangId = resp && resp.suggestedGudangId ? resp.suggestedGudangId : null;

                        const explicitGudangFromItem = (item && item.selected_gudang_id) ? item.selected_gudang_id : null;
                        if (!explicitGudangFromItem && suggestedGudangId) {
                            gudangId = suggestedGudangId;
                            try { item.selected_gudang_id = gudangId; } catch (e) { }
                        }

                        if (!pivotRows || !pivotRows.length) return false;

                        const requests = pivotRows.map(function(r) {
                            return loadStockTotal(r.obatId, gudangId);
                        });

                        return $.when.apply($, requests)
                            .then(function() {
                                const args = Array.prototype.slice.call(arguments);
                                const normalized = (requests.length === 1) ? [arguments[0]] : args;
                                let out = false;
                                pivotRows.forEach(function(r, idx) {
                                    const stok = (normalized[idx] && typeof normalized[idx].total !== 'undefined') ? Number(normalized[idx].total) : 0;
                                    const needed = Number(r.needed) || 0;
                                    if (needed > 0 && stok < needed) out = true;
                                });
                                return out;
                            }, function() {
                                return false;
                            });
                    });
            }

            if (!baseRows || !baseRows.length) {
                return $.Deferred().resolve(false).promise();
            }

            const requests = baseRows.map(function(r) {
                return loadStockTotal(r.obatId, gudangId);
            });

            return $.when.apply($, requests)
                .then(function() {
                    const args = Array.prototype.slice.call(arguments);
                    const normalized = (requests.length === 1) ? [arguments[0]] : args;
                    let out = false;
                    baseRows.forEach(function(r, idx) {
                        const stok = (normalized[idx] && typeof normalized[idx].total !== 'undefined') ? Number(normalized[idx].total) : 0;
                        const needed = Number(r.needed) || 0;
                        if (needed > 0 && stok < needed) out = true;
                    });
                    return out;
                }, function() {
                    return false;
                });
        }

        function updateStockWarningsForVisibleRows() {
            if (!table) return;
            if (billingLocked) return;
            if (!Array.isArray(billingData) || !billingData.length) return;

            const visibleRows = table.rows({ page: 'current' }).data().toArray();
            const now = Date.now();

            visibleRows.forEach(function(row) {
                if (!row || !row.id) return;
                const itemId = row.id;
                const item = billingData.find(function(i) { return i && i.id == itemId; });
                if (!item) return;

                // Use server hint on initial render to avoid delayed icon appearance
                const hintVal = row.is_out_of_stock;
                const hasHint = (
                    hintVal === 0 || hintVal === 1 ||
                    hintVal === '0' || hintVal === '1' ||
                    hintVal === true || hintVal === false
                );
                if (stockWarningServerHintsValid && hasHint) {
                    const hinted = !!(hintVal === true || hintVal === 1 || hintVal === '1');
                    itemStockWarningCache.set(String(itemId), { outOfStock: hinted, at: Date.now() });
                    setStockWarningIcon(itemId, hinted);
                    return;
                }

                const cached = itemStockWarningCache.get(String(itemId));
                if (cached && cached.at && (now - cached.at) <= ITEM_STOCK_WARN_TTL_MS) {
                    setStockWarningIcon(itemId, !!cached.outOfStock);
                    return;
                }

                if (stockWarningInFlight.has(String(itemId))) return;
                stockWarningInFlight.add(String(itemId));

                isItemOutOfStock(itemId, item)
                    .then(function(out) {
                        itemStockWarningCache.set(String(itemId), { outOfStock: !!out, at: Date.now() });
                        setStockWarningIcon(itemId, !!out);
                    })
                    .always(function() {
                        stockWarningInFlight.delete(String(itemId));
                    });
            });
        }

        // Auto-refresh rincian billing every 5 seconds
        // - Pause while edit modal is open (avoid disrupting user edits)
        // - Skip during manual client-side redraws (updateTable) to prevent race conditions
        billingTableAutoRefreshIntervalId = setInterval(function() {
            try {
                if (document.hidden) return;
                if (isManualTableUpdateInProgress) return;
                if ($('#editModal').hasClass('show')) return;
                if ($('#stockInfoModal').hasClass('show')) return;
                if (isAutoRefreshInFlight) return;
                // Avoid accidental bursts
                const now = Date.now();
                if (now - lastAutoRefreshAt < 4500) return;

                if (table && table.ajax && typeof table.ajax.reload === 'function') {
                    isAutoRefreshInFlight = true;
                    lastAutoRefreshAt = now;

                    // Light refresh to reduce backend work during polling.
                    window.__billingLightRefresh = true;
                    // Safety: reset the flag in case the request errors and callback doesn't run.
                    setTimeout(function() { window.__billingLightRefresh = false; }, 8000);

                    table.ajax.reload(function() {
                        window.__billingLightRefresh = false;
                        isAutoRefreshInFlight = false;
                    }, false); // keep paging
                }
            } catch (e) {
                console.debug('Auto-refresh skipped:', e);
                window.__billingLightRefresh = false;
                isAutoRefreshInFlight = false;
            }
        }, 5000);

        $(window).on('beforeunload', function() {
            if (billingTableAutoRefreshIntervalId) {
                clearInterval(billingTableAutoRefreshIntervalId);
                billingTableAutoRefreshIntervalId = null;
            }
        });
        
        // Fix for action column - ensure the table fits its container
        $(window).resize(function() {
            table.columns.adjust();
        });
        
        // Event handler for gudang selector changes
        $(document).on('change', '.gudang-selector', function() {
            const itemId = $(this).data('item-id');
            const selectedGudangId = $(this).val();
            const rowIndex = $(this).data('row-index');
            
            // Update the billingData with selected gudang
            if (billingData[rowIndex]) {
                billingData[rowIndex].selected_gudang_id = selectedGudangId;
            }

            // After changing gudang, server hints may be stale; force recalculation
            stockWarningServerHintsValid = false;
            try { itemStockWarningCache.delete(String(itemId)); } catch (e) {}
            
            console.log('Gudang selection updated for item', itemId, 'to gudang', selectedGudangId);
        });

        // Click item name -> open modal and load stok tersedia
        $(document).on('click', '.stock-info-link', function(e) {
            e.preventDefault();
            const itemId = $(this).data('id');
            const item = billingData.find(i => i.id == itemId);

            window.ensureStockInfoModalLoaded()
                .done(function() {
                    openStockInfoModalForItem(itemId, item);
                })
                .fail(function() {
                    Swal.fire({
                        title: 'Error',
                        text: 'Gagal memuat tampilan modal stok. Silakan coba lagi.',
                        icon: 'error'
                    });
                });
        });
        
        // Refresh table when gudang data is loaded
        function refreshTableAfterGudangLoad() {
            if (window.gudangData.loaded) {
                table.ajax.reload(null, false); // Don't reset paging
            }
        }
        
        // Check if gudang data is loaded, if not wait for it
        if (window.gudangData.loaded) {
            refreshTableAfterGudangLoad();
        } else {
            // Poll until loaded
            const checkInterval = setInterval(function() {
                if (window.gudangData.loaded) {
                    clearInterval(checkInterval);
                    refreshTableAfterGudangLoad();
                }
            }, 100);
        }
        
        // Fix: Directly attach event handlers using document delegation
        $(document).on('click', '.edit-btn', function() {
            if (billingLocked) {
                Swal.fire({
                    title: 'Tidak Bisa Diedit',
                    text: 'Billing sudah diproses pembayaran. Setelah pembayaran, stok dikurangi dan billing tidak dapat diedit.',
                    icon: 'info'
                });
                return;
            }
            const id = $(this).data('id');
            const rowIndex = $(this).data('row-index');
            const jumlah = $(this).data('jumlah');
            const diskon = $(this).data('diskon');
            const diskon_type = $(this).data('diskon_type');
            const qty = $(this).data('qty');

            // Use billingData as source-of-truth (data-* attributes can be formatted/localized)
            const currentItem = (Array.isArray(billingData) ? billingData.find(i => i && i.id == id) : null);
            const diskonRaw = (currentItem && typeof currentItem.diskon_raw !== 'undefined') ? currentItem.diskon_raw : diskon;
            const diskonTypeRaw = (currentItem && typeof currentItem.diskon_type !== 'undefined') ? currentItem.diskon_type : diskon_type;

            // Parse localized numeric strings like "10.000" or "10000,00"
            function parseNumericValue(value) {
                if (value === null || typeof value === 'undefined' || value === '') return 0;
                if (typeof value === 'number') return isNaN(value) ? 0 : value;
                let s = String(value).trim();
                s = s.replace(/[^0-9,.-]/g, '');
                if (s.includes(',') && s.includes('.')) {
                    // Assume '.' thousands and ',' decimals
                    s = s.replace(/\./g, '').replace(',', '.');
                } else if (s.includes(',')) {
                    s = s.replace(',', '.');
                }
                const n = parseFloat(s);
                return isNaN(n) ? 0 : n;
            }

            // If diskon_type is empty but discount exists, default to nominal
            const diskonValue = parseNumericValue(diskonRaw);
            const normalizedDiskonType = (!diskonTypeRaw && diskonValue > 0) ? 'nominal' : (diskonTypeRaw || '');

            $('#edit_id').val(id);
            $('#edit_row_index').val(rowIndex);
            $('#jumlah').val(jumlah);
            $('#diskon').val(diskonRaw);
            $('#diskon_type').val(normalizedDiskonType).trigger('change');
            $('#edit_qty').val(qty);

            // Extra safety: if diskon already filled and type is empty, flip it to nominal
            if (diskonValue > 0 && !$('#diskon_type').val()) {
                $('#diskon_type').val('nominal').trigger('change');
            }

            // Debug: Show harga before edit
            // console.log('[DEBUG] Harga before edit:', jumlah);
            $('#editModal').modal('show');
        });

        // Edit modal UX safety: if user inputs diskon but type is still "Tidak Ada",
        // assume nominal (Rp) to avoid silently ignoring the discount.
        $(document).on('input change', '#diskon', function() {
            const diskonValue = Number($(this).val() || 0);
            const currentType = $('#diskon_type').val();
            if (diskonValue > 0 && !currentType) {
                $('#diskon_type').val('nominal').trigger('change');
            }
        });
        
        // Fix: Use document delegation for delete button
        $(document).on('click', '.delete-btn', function() {
            if (!canDeleteBilling) {
                Swal.fire({
                    title: 'Tidak Bisa Dihapus',
                    text: 'Anda tidak memiliki akses untuk menghapus item billing.',
                    icon: 'info'
                });
                return;
            }
            if (billingLocked) {
                Swal.fire({
                    title: 'Tidak Bisa Dihapus',
                    text: 'Billing sudah diproses pembayaran. Setelah pembayaran, stok dikurangi dan billing tidak dapat diedit.',
                    icon: 'info'
                });
                return;
            }
            const id = $(this).data('id');
            Swal.fire({
                title: 'Konfirmasi Hapus',
                text: 'Apakah Anda yakin ingin menghapus item ini?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.value) {
                    try {
                        // console.log('Deleting item with id:', id);
                        const idx = billingData.findIndex(item => item.id == id);
                        if (idx !== -1) {
                            billingData[idx].deleted = true;
                            // Only push numeric IDs (real DB items)
                            if (!isNaN(Number(billingData[idx].id))) {
                                deletedItems.push(billingData[idx].id);
                            }
                            updateTable();
                            calculateTotals();
                            // console.log('Item deleted successfully');
                            Swal.fire({
                                title: 'Berhasil!',
                                text: 'Item berhasil dihapus.',
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            });
                        }
                    } catch(e) {
                        console.error('Error deleting row:', e);
                        Swal.fire({
                            title: 'Error!',
                            text: 'Terjadi kesalahan saat menghapus item: ' + e.message,
                            icon: 'error'
                        });
                    }
                }
            });
        });
        
        // Save changes button in modal
        $('#saveChangesBtn').on('click', function(e) {
            e.preventDefault();
            const id = $('#edit_id').val();
            const jumlah = parseFloat($('#jumlah').val());
            const diskon = $('#diskon').val() ? parseFloat($('#diskon').val()) : 0;
            const diskon_type = $('#diskon_type').val();
            const qty = parseInt($('#edit_qty').val()) || 1;
            // Update local data using id
            const idx = billingData.findIndex(item => item.id == id);
            if (idx !== -1) {
                billingData[idx].jumlah_raw = jumlah;
                billingData[idx].diskon_raw = diskon;
                billingData[idx].diskon_type = diskon_type;
                billingData[idx].qty = qty;
                billingData[idx].jumlah = 'Rp ' + formatCurrency(jumlah);
                if (diskon && diskon > 0) {
                    if (diskon_type === '%') {
                        billingData[idx].diskon = diskon + '%';
                    } else {
                        billingData[idx].diskon = 'Rp ' + formatCurrency(diskon);
                    }
                } else {
                    billingData[idx].diskon = '-';
                }
                const lineNoDisc = jumlah * qty;
                let lineAfter = lineNoDisc;
                if (diskon && diskon > 0) {
                    if (diskon_type === '%') {
                        lineAfter = lineNoDisc - (lineNoDisc * (diskon / 100));
                    } else {
                        // Nominal discount is treated as line discount: (unit * qty) - diskon_rp
                        lineAfter = lineNoDisc - diskon;
                    }
                }
                lineAfter = Math.max(0, lineAfter);
                // Store the final line total (already multiplied by qty)
                billingData[idx].harga_akhir_raw = lineAfter;
                billingData[idx].harga_akhir = 'Rp ' + formatCurrency(lineAfter);
                billingData[idx].edited = true;
                // Ensure racikan_ke is included for racikan items
                if (billingData[idx].is_racikan && billingData[idx].billable && billingData[idx].billable.racikan_ke) {
                    billingData[idx].racikan_ke = billingData[idx].billable.racikan_ke;
                    // Set racikan_total_price to the edited value
                    billingData[idx].racikan_total_price = jumlah;
                }
            }
            $('#editModal').modal('hide');

            // Item amounts changed; server hints are no longer authoritative
            stockWarningServerHintsValid = false;
            try { itemStockWarningCache.delete(String(id)); } catch (e) {}
            // Debug: Show harga after edit
            // console.log('[DEBUG] Harga after edit:', jumlah);
            updateTable();
            calculateTotals();

            // Debug: Show total for this item in DataTable
            setTimeout(function() {
                const item = billingData.find(item => item.id == id);
                if (item) {
                    const total = item.harga_akhir_raw;
                    // console.log('[DEBUG] Total in DataTable for item', id, ':', total);
                }
            }, 200);
        });
        
        // Function to update the table with all billingData (for add/edit/delete)
        function updateTable() {
    isManualTableUpdateInProgress = true;

    // Temporarily disable Ajax source & server-side processing
    const settings = table.settings()[0];
    const previousServerSide = settings.oFeatures.bServerSide;
    const previousAjax = settings.ajax;
    settings.oFeatures.bServerSide = false;
    settings.ajax = null;

    // Only show non-deleted items
    const currentData = billingData.filter(item => !item.deleted);
    table.clear().rows.add(currentData).draw();

    // Restore server-side settings AFTER drawing is complete
    setTimeout(function() {
        settings.oFeatures.bServerSide = previousServerSide;
        settings.ajax = previousAjax;
        table.columns.adjust();
        isManualTableUpdateInProgress = false;

        // Immediately reflect local billing changes in the primary action button
        try {
            updatePaymentActionButtons($('#payment_method').val() || 'piutang');
        } catch (e) {
            // ignore
        }
    }, 100);
}
        
        // Helper function for formatting currency (removes unnecessary 0s)
        function formatCurrency(value) {
            // Round to 2 decimal places
            let rounded = Math.round(value * 100) / 100;
            // If it's a whole number, don't show decimals
            if (rounded === Math.floor(rounded)) {
                return rounded.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            }
            // Otherwise format with up to 2 decimals
            return rounded.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }
        
        // Helper to parse harga string with comma/dot
        function parseHarga(hargaStr) {
            // console.log('parseHarga input:', hargaStr, 'type:', typeof hargaStr);
            
            if (typeof hargaStr === 'number') {
                // console.log('parseHarga returning number:', hargaStr);
                return hargaStr;
            }
            
            if (typeof hargaStr === 'string') {
                // Handle different formats:
                // "150000.00" -> 150000
                // "150.000,00" -> 150000 (European format)
                // "150,000.00" -> 150000 (US format)
                // "150000" -> 150000
                
                let cleaned = hargaStr.trim();
                // Strip currency text and any non-numeric chars except separators.
                // This allows inputs like: "Rp 1.350.000,00" or "1.350.000".
                cleaned = cleaned.replace(/[^0-9.,\-]/g, '');
                
                // Check if it's in format like "150000.00" (decimal with dot, no thousand separators)
                if (/^\d+\.\d{1,2}$/.test(cleaned)) {
                    const result = parseFloat(cleaned);
                    // console.log('parseHarga decimal format:', hargaStr, '->', result);
                    return result;
                }
                
                // Check if it's in format like "150.000,00" (European: dots for thousands, comma for decimal)
                if (/^\d{1,3}(\.\d{3})*,\d{1,2}$/.test(cleaned)) {
                    cleaned = cleaned.replace(/\./g, '').replace(',', '.');
                    const result = parseFloat(cleaned);
                    // console.log('parseHarga European format:', hargaStr, '->', cleaned, '->', result);
                    return result;
                }
                
                // Check if it's in format like "150,000.00" (US: commas for thousands, dot for decimal)
                if (/^\d{1,3}(,\d{3})*\.\d{1,2}$/.test(cleaned)) {
                    cleaned = cleaned.replace(/,/g, '');
                    const result = parseFloat(cleaned);
                    // console.log('parseHarga US format:', hargaStr, '->', cleaned, '->', result);
                    return result;
                }
                
                // Handle plain numbers (no decimal)
                if (/^\d+$/.test(cleaned)) {
                    const result = parseInt(cleaned);
                    // console.log('parseHarga plain number:', hargaStr, '->', result);
                    return result;
                }
                
                // Handle Indonesian format (dots for thousands, no decimal or comma for decimal)
                if (/^\d{1,3}(\.\d{3})*$/.test(cleaned)) {
                    cleaned = cleaned.replace(/\./g, '');
                    const result = parseInt(cleaned);
                    // console.log('parseHarga Indonesian thousands:', hargaStr, '->', cleaned, '->', result);
                    return result;
                }
                
                // Fallback: try to parse as float
                const result = parseFloat(cleaned);
                // console.log('parseHarga fallback:', hargaStr, '->', result);
                return isNaN(result) ? 0 : result;
            }
            
            // console.log('parseHarga returning 0 for:', hargaStr);
            return 0;
        }

        // Add selected Racikan to billingData (example, adapt as needed)
        function addRacikanToBillingData(data) {
            // data.harga should be the unit price, data.qty the quantity, or adapt as needed
            const harga = parseHarga(data.harga);
            const qty = parseInt(data.qty) || 1;
            
            // Always use 'Obat Racikan' as the name for consistency
            const racikanName = 'Obat Racikan';
                
            // Build the description with the list of medications
            let description = '';
            if (data.obat_list && Array.isArray(data.obat_list)) {
                description = data.obat_list.map(obat => `- ${obat}`).join('<br>');
            } else if (data.deskripsi) {
                description = data.deskripsi;
            }
            
            billingData.push({
                id: 'racikan-' + (data.id || Date.now()),
                billable_id: data.id,
                billable_type: 'App\\Models\\ERM\\ResepFarmasi',
                nama_item: racikanName,
                jumlah: 'Rp ' + formatCurrency(harga),
                qty: qty,
                diskon: 0,
                diskon_type: 'nominal',
                harga_akhir: 'Rp ' + formatCurrency(harga * qty),
                harga_akhir_raw: harga * qty,
                deleted: false,
                deskripsi: description,
                is_racikan: true,
                racikan_ke: data.racikan_ke || 0,
                racikan_obat_list: data.obat_list || []
            });
            updateTable();
            calculateTotals();
        }
        
        // Calculate totals for the bottom section
        function calculateTotals() {
            // console.log('Current billingData for totals:', billingData);
            let subtotal = 0;
            // Sum up all line totals (harga_akhir_raw) from non-deleted items
            billingData.forEach(function(item) {
                if (!item.deleted && !isNaN(item.harga_akhir_raw) && item.harga_akhir_raw > 0) {
                    subtotal += parseFloat(item.harga_akhir_raw);
                }
            });
            // Display subtotal
            $('#subtotal').text('Rp ' + formatCurrency(subtotal));
            // Calculate global discount
            const globalDiscount = parseFloat($('#global_discount').val() || 0);
            const globalDiscountType = $('#global_discount_type').val();
            let discountAmount = 0;
            if (globalDiscount > 0) {
                if (globalDiscountType === '%') {
                    discountAmount = subtotal * (globalDiscount / 100);
                } else {
                    discountAmount = globalDiscount;
                }
            }
            // Display discount amount
            $('#global_discount_amount').text('- Rp ' + formatCurrency(discountAmount));
            // Calculate tax
            const taxPercentage = parseFloat($('#tax_percentage').val() || 0);
            const afterDiscount = subtotal - discountAmount;
            const taxAmount = afterDiscount * (taxPercentage / 100);
            // Display tax amount
            $('#tax_amount').text('+ Rp ' + formatCurrency(taxAmount));
            // Get admin fee and shipping fee
            const adminFee = parseFloat($('#admin_fee').val() || 0);
            const shippingFee = parseFloat($('#shipping_fee').val() || 0);
            // Calculate and display grand total
            const grandTotal = afterDiscount + taxAmount + adminFee + shippingFee;
            // integer ceil versions to align with backend (always round up)
            const grandTotalInt = Math.ceil(grandTotal);
            $('#grand_total').text('Rp ' + formatCurrency(grandTotalInt));

            // Calculate change amount using integer ceil values to match displayed total
            const amountPaid = parseHarga($('#amount_paid').val() || 0);
            const amountPaidInt = Math.ceil(amountPaid);
            const changeAmount = Math.max(0, amountPaidInt - grandTotalInt);
            const shortageAmount = Math.max(0, grandTotalInt - amountPaidInt);
            $('#change_amount').text('Rp ' + formatCurrency(changeAmount));
            // Update shortage display (kekurangan)
            if (shortageAmount > 0) {
                $('#shortage_amount').text('Rp ' + formatCurrency(shortageAmount)).show();
                $('#shortage_label').show();
            } else {
                $('#shortage_amount').hide();
                $('#shortage_label').hide();
            }
            
            // Store these values for later use when saving/creating invoice
            window.billingTotals = {
                subtotal: subtotal,
                discountAmount: discountAmount,
                discountType: globalDiscountType,
                discountValue: globalDiscount,
                taxPercentage: taxPercentage,
                taxAmount: taxAmount,
                adminFee: adminFee,
                shippingFee: shippingFee,
                grandTotal: grandTotal,
                // integer-rounded rupiah values to avoid mismatch when frontend strips decimals
                // use Math.ceil to always round up so payment value will never be under the actual price
                grandTotalInt: grandTotalInt,
                amountPaid: amountPaid,
                amountPaidInt: amountPaidInt,
                changeAmount: changeAmount,
                shortageAmount: shortageAmount,
                paymentMethod: $('#payment_method').val()
            };

            // Keep primary action button in sync without requiring page refresh
            try {
                updatePaymentActionButtons($('#payment_method').val() || 'piutang');
            } catch (e) {
                // ignore
            }
        }
        
        // Event listeners for total calculation inputs
        $('#global_discount, #global_discount_type, #tax_percentage, #admin_fee, #shipping_fee, #payment_method').on('change input', function() {
            calculateTotals();
        });

        // Handle amount_paid input separately: parse/ceil on blur and recalc
        $('#amount_paid').on('change input', function() {
            // live updates while typing
            calculateTotals();
        }).on('blur', function() {
            // On blur, normalize: parse, ceil, and format for display
            const raw = $(this).val();
            const parsed = parseHarga(raw);
            const ceilVal = Math.ceil(parsed);
            // formatCurrency returns e.g. '257.000' for integers; append ',00' to match user's style
            $(this).val(formatCurrency(ceilVal) + ',00');
            calculateTotals();
        });
        
        // Save all changes button
$('#saveAllChangesBtn').on('click', function() {
    Swal.fire({
        title: 'Konfirmasi Simpan',
        text: 'Simpan semua perubahan billing?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Simpan!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.value) {
            // Force the visitation ID to be treated as a string
            const correctVisitationId = "{{ $visitation->id }}";
            
            // console.log('=== SAVE BILLING DEBUG START ===');
            // console.log('All billingData:', billingData);
            // console.log('Visitation ID being sent:', correctVisitationId);
            // console.log('Type of visitation ID:', typeof correctVisitationId);
            
            // Categorize items
            const editedItems = billingData.filter(item => item.edited && !item.deleted);
            const newItems = billingData.filter(item => 
                !item.edited && 
                !item.deleted && 
                (item.id.toString().startsWith('tindakan-') || 
                 item.id.toString().startsWith('lab-') || 
                 item.id.toString().startsWith('konsultasi-') ||
                 item.id.toString().startsWith('obat-') ||
                 item.id.toString().startsWith('racikan-'))
            );
            
            // console.log('Edited items:', editedItems);
            // console.log('New items:', newItems);
            // console.log('Deleted items:', deletedItems);
            // console.log('=== SAVE BILLING DEBUG END ===');
            
            const requestData = {
                _token: "{{ csrf_token() }}",
                visitation_id: correctVisitationId,
                edited_items: editedItems,
                new_items: newItems,
                deleted_items: deletedItems,
                totals: window.billingTotals
            };
            
            // console.log('Request data being sent:', requestData);
            
            // Show loading
            Swal.fire({
                title: 'Menyimpan...',
                text: 'Harap tunggu, sedang memproses data billing.',
                icon: 'info',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            $.ajax({
                url: "{{ route('finance.billing.save') }}",
                type: "POST",
                data: JSON.stringify(requestData),
                contentType: 'application/json; charset=utf-8',
                dataType: 'json',
                success: function(response) {
                    // console.log('Save response:', response);
                    Swal.fire({
                        title: 'Berhasil!',
                        text: 'Data billing berhasil disimpan',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        // Refresh the table to get the new IDs from database
                        location.reload();
                    });
                },
                error: function(xhr) {
                    console.error('Save error:', xhr);
                    showReadableAjaxError('Gagal Menyimpan', xhr, 'Terjadi kesalahan:');
                    console.error('Error details:', xhr.responseText);
                }
            });
        }
    });
});
        
        function escapeHtml(text) {
            try {
                return $('<div>').text(text === null || typeof text === 'undefined' ? '' : String(text)).html();
            } catch (e) {
                return '';
            }
        }

        function parseXhrJson(xhr) {
            if (!xhr) return null;
            if (xhr.responseJSON) return xhr.responseJSON;
            const raw = xhr.responseText;
            if (!raw) return null;
            try {
                return JSON.parse(raw);
            } catch (e) {
                return null;
            }
        }

        function showReadableAjaxError(title, xhr, fallbackPrefix) {
            const resp = parseXhrJson(xhr);
            const rawText = (xhr && xhr.responseText) ? xhr.responseText : '';

            const message = resp && resp.message ? String(resp.message) : (rawText || 'Terjadi kesalahan.');
            // Backend may send literal "\n" in JSON (PHP single-quoted strings), normalize those too.
            const normalizedMessage = message
                .replace(/\\r\\n/g, '\n')
                .replace(/\\n/g, '\n')
                .replace(/\r\n/g, '\n');

            const lines = normalizedMessage
                .split('\n')
                .map(function(l) { return (l || '').trim(); })
                .filter(Boolean);

            const isStockError = (resp && typeof resp.success !== 'undefined' && resp.success === false && message.toLowerCase().includes('stok'))
                || message.toLowerCase().includes('stok tidak mencukupi');

            // Build nicer HTML for multi-line messages
            let html = '<div style="text-align:left">';
            if (fallbackPrefix) {
                html += '<div class="mb-2">' + escapeHtml(fallbackPrefix) + '</div>';
            }

            if (lines.length > 1) {
                const first = lines[0] || '';
                const rest = lines.slice(1);
                // If the first line looks like a generic header, show it separately
                if (first.toLowerCase().includes('stok tidak mencukupi')) {
                    html += '<div class="mb-2"><strong>' + escapeHtml(first) + '</strong></div>';
                    html += '<ul class="mb-0" style="padding-left:18px">' +
                        rest.map(function(l) { return '<li>' + escapeHtml(l) + '</li>'; }).join('') +
                        '</ul>';
                } else {
                    html += '<ul class="mb-0" style="padding-left:18px">' +
                        lines.map(function(l) { return '<li>' + escapeHtml(l) + '</li>'; }).join('') +
                        '</ul>';
                }
            } else {
                html += '<div>' + escapeHtml(lines[0] || normalizedMessage) + '</div>';
            }
            html += '</div>';

            Swal.fire({
                title: title || (isStockError ? 'Stok Tidak Mencukupi' : 'Error!'),
                html: html,
                icon: isStockError ? 'warning' : 'error',
                confirmButtonText: 'OK'
            });
        }

        function runInvoiceFlow(isCreatingInvoice) {
            // Ensure totals calculated before sending
            calculateTotals();

            const invoiceIdBefore = currentInvoiceId;
            const isUpdatingInvoice = !!invoiceIdBefore && isCreatingInvoice;

            const selectedMethod = ($('#payment_method').val() || 'cash').toString();
            const isPiutangAction = !isCreatingInvoice && selectedMethod === 'piutang';

            const unpaidTotals = Object.assign({}, window.billingTotals || {}, {
                amountPaid: 0,
                amountPaidInt: 0,
                changeAmount: 0,
                shortageAmount: (window.billingTotals && window.billingTotals.grandTotalInt) ? window.billingTotals.grandTotalInt : 0,
                paymentMethod: null
            });

            function buildPaymentConfirmHtml() {
                // Recalculate in case user just changed modal fields
                calculateTotals();
                const grandTotalInt = (window.billingTotals && window.billingTotals.grandTotalInt) ? Number(window.billingTotals.grandTotalInt) : 0;
                const amountPaidInt = (window.billingTotals && window.billingTotals.amountPaidInt) ? Number(window.billingTotals.amountPaidInt) : 0;
                const shortageInt = Math.max(0, grandTotalInt - amountPaidInt);
                const isLunas = grandTotalInt > 0 && amountPaidInt >= grandTotalInt;

                let html = '<div style="text-align:left">';
                html += '<div class="mb-2">Setelah proses ini:</div>';
                html += '<ul style="padding-left:18px;margin:0">';
                html += '<li><strong>Stok akan dikurangi</strong>.</li>';
                html += '<li><strong>Billing tidak dapat diedit</strong> (item/qty/harga/diskon tidak bisa diubah).</li>';
                if (shortageInt > 0) {
                    html += '<li>Kekurangan <strong>Rp ' + formatCurrency(shortageInt) + '</strong> akan <strong>dimasukkan ke piutang</strong>.</li>';
                } else if (isLunas) {
                    html += '<li>Pembayaran <strong>lunas</strong> (status invoice menjadi <strong>PAID</strong>).</li>';
                }
                html += '</ul>';
                html += '</div>';
                return html;
            }

            Swal.fire({
                title: 'Konfirmasi',
                html: isCreatingInvoice
                    ? (isUpdatingInvoice
                        ? '<div>Update invoice sesuai billing terbaru <strong>(belum dibayar)</strong>?</div>'
                        : '<div>Simpan semua perubahan billing dan buat invoice <strong>(belum dibayar)</strong>?</div>')
                    : buildPaymentConfirmHtml(),
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: isCreatingInvoice
                    ? (isUpdatingInvoice ? 'Ya, Update Invoice!' : 'Ya, Buat Invoice!')
                    : (isPiutangAction ? 'Ya, Buat Piutang!' : 'Ya, Pembayaran!'),
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.value) {
                    // Prepare request data
                    const correctVisitationId = "{{ $visitation->id }}";
                    const editedItems = billingData.filter(item => item.edited && !item.deleted);
                    const newItems = billingData.filter(item =>
                        !item.edited &&
                        !item.deleted &&
                        (item.id.toString().startsWith('tindakan-') ||
                         item.id.toString().startsWith('lab-') ||
                         item.id.toString().startsWith('konsultasi-') ||
                         item.id.toString().startsWith('obat-') ||
                         item.id.toString().startsWith('racikan-'))
                    );
                    const items = billingData.filter(item => !item.deleted);
                    if (items.length === 0) {
                        Swal.fire({
                            title: 'Peringatan!',
                            text: 'Tidak ada item billing yang valid!',
                            icon: 'warning'
                        });
                        return;
                    }
                    const requestData = {
                        _token: "{{ csrf_token() }}",
                        visitation_id: correctVisitationId,
                        edited_items: editedItems,
                        new_items: newItems,
                        deleted_items: deletedItems,
                        totals: JSON.stringify(isCreatingInvoice ? unpaidTotals : window.billingTotals)
                    };

                    // Open a single loading modal and update it through the process
                    Swal.fire({
                        title: 'Memproses...',
                        text: isCreatingInvoice
                            ? (isUpdatingInvoice ? 'Harap tunggu, sedang menyimpan dan mengupdate invoice.' : 'Harap tunggu, sedang menyimpan dan membuat invoice.')
                            : 'Harap tunggu, sedang menyimpan dan memproses pembayaran.',
                        icon: 'info',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: () => { Swal.showLoading(); }
                    });

                    function proceedToInvoiceRequest() {
                        // Update the same modal to indicate invoice creation / payment processing
                        try {
                            Swal.update({
                                title: isCreatingInvoice ? 'Membuat Invoice...' : 'Memproses Pembayaran...',
                                text: isCreatingInvoice ? 'Harap tunggu, sedang memproses invoice.' : 'Harap tunggu, sedang memproses pembayaran.'
                            });
                        } catch (e) {
                            // ignore
                        }

                        // Debug: log payload being sent for invoice creation
                        console.debug('Creating invoice payload', {
                            visitation_id: correctVisitationId,
                            items: items,
                            totals: isCreatingInvoice ? unpaidTotals : window.billingTotals,
                            gudang_selections: collectGudangSelections()
                        });

                        const invoiceEndpointUrl = isCreatingInvoice
                            ? "{{ route('finance.billing.createInvoice') }}"
                            : "{{ route('finance.billing.receivePayment') }}";

                        $.ajax({
                            url: invoiceEndpointUrl,
                            type: "POST",
                            data: JSON.stringify({
                                _token: "{{ csrf_token() }}",
                                visitation_id: correctVisitationId,
                                invoice_id: currentInvoiceId,
                                items: items,
                                totals: JSON.stringify(isCreatingInvoice ? unpaidTotals : window.billingTotals),
                                gudang_selections: collectGudangSelections()
                            }),
                            contentType: 'application/json; charset=utf-8',
                            dataType: 'json',
                            success: function(invoiceResponse) {
                                // The backend returns stock_reduced and stock_message
                                var stockReduced = invoiceResponse.stock_reduced === true || invoiceResponse.stock_reduced === 1;
                                var stockMessage = invoiceResponse.stock_message || '';

                                var invoiceId = invoiceResponse.invoice_id || invoiceResponse.id || (invoiceResponse.invoice && invoiceResponse.invoice.id) || null;
                                if (invoiceId) {
                                    currentInvoiceId = invoiceId;
                                    // prefer backend truth; fallback to local compare
                                    if (typeof invoiceResponse.is_paid !== 'undefined') {
                                        currentInvoiceIsPaid = (invoiceResponse.is_paid === true || invoiceResponse.is_paid === 1 || invoiceResponse.is_paid === '1');
                                    } else {
                                        try {
                                            const paidRaw = parseFloat(invoiceResponse.amount_paid || 0);
                                            const totalRaw = parseFloat(invoiceResponse.total_amount || 0);
                                            currentInvoiceIsPaid = (Number(totalRaw) > 0) && (Math.ceil(paidRaw) >= Math.ceil(totalRaw));
                                        } catch (e) {
                                            currentInvoiceIsPaid = false;
                                        }
                                    }

                                    // For Piutang invoices, payment_status=paid means invoice is effectively paid.
                                    try {
                                        const pm = String((invoiceResponse && invoiceResponse.payment_method) ? invoiceResponse.payment_method : (window.oldInvoice && window.oldInvoice.payment_method) ? window.oldInvoice.payment_method : '').toLowerCase();
                                        const ps = String(
                                            (invoiceResponse && invoiceResponse.piutang_payment_status) ? invoiceResponse.piutang_payment_status :
                                            (invoiceResponse && invoiceResponse.piutang && invoiceResponse.piutang.payment_status) ? invoiceResponse.piutang.payment_status :
                                            (window.oldInvoice && window.oldInvoice.piutang_payment_status) ? window.oldInvoice.piutang_payment_status :
                                            ''
                                        ).toLowerCase();
                                        if (pm === 'piutang' && ps === 'paid') {
                                            currentInvoiceIsPaid = true;
                                        }
                                    } catch (e) {
                                        // ignore
                                    }
                                    setInvoiceFlowUi();

                                    // Keep window.oldInvoice in sync so UI updates immediately without requiring a page reload.
                                    try {
                                        window.oldInvoice = window.oldInvoice || {};
                                        window.oldInvoice.id = currentInvoiceId;
                                        window.oldInvoice.invoice_number = invoiceResponse.invoice_number || (invoiceResponse.invoice && invoiceResponse.invoice.invoice_number) || window.oldInvoice.invoice_number || null;
                                        if (typeof invoiceResponse.total_amount !== 'undefined') window.oldInvoice.total_amount = invoiceResponse.total_amount;
                                        if (typeof invoiceResponse.amount_paid !== 'undefined') window.oldInvoice.amount_paid = invoiceResponse.amount_paid;
                                        if (typeof invoiceResponse.payment_method !== 'undefined') window.oldInvoice.payment_method = invoiceResponse.payment_method;

                                        if (invoiceResponse.piutang) {
                                            window.oldInvoice.piutang_id = invoiceResponse.piutang.id || null;
                                            window.oldInvoice.piutang_amount = (typeof invoiceResponse.piutang.amount !== 'undefined') ? invoiceResponse.piutang.amount : null;
                                            window.oldInvoice.piutang_paid_amount = (typeof invoiceResponse.piutang.paid_amount !== 'undefined') ? invoiceResponse.piutang.paid_amount : null;
                                            window.oldInvoice.piutang_payment_status = invoiceResponse.piutang.payment_status || invoiceResponse.piutang_payment_status || null;
                                        } else if (typeof invoiceResponse.piutang_payment_status !== 'undefined') {
                                            window.oldInvoice.piutang_payment_status = invoiceResponse.piutang_payment_status;
                                        }
                                    } catch (e) {
                                        // ignore
                                    }

                                    try {
                                        setInvoiceHeaderUi(
                                            invoiceResponse.invoice_number || (invoiceResponse.invoice && invoiceResponse.invoice.invoice_number) || null,
                                            currentInvoiceId,
                                            (typeof invoiceResponse.amount_paid !== 'undefined') ? invoiceResponse.amount_paid : ((invoiceResponse.invoice && invoiceResponse.invoice.amount_paid) || 0),
                                            (typeof invoiceResponse.total_amount !== 'undefined') ? invoiceResponse.total_amount : ((invoiceResponse.invoice && invoiceResponse.invoice.total_amount) || 0),
                                            invoiceResponse.payment_method || (invoiceResponse.invoice && invoiceResponse.invoice.payment_method) || ($('#payment_method').val() || null),
                                            invoiceResponse.piutang_payment_status || (invoiceResponse.piutang && invoiceResponse.piutang.payment_status) || (window.oldInvoice && window.oldInvoice.piutang_payment_status) || null
                                        );
                                    } catch (e) {
                                        // ignore
                                    }

                                    try {
                                        updatePaymentActionButtons((invoiceResponse && invoiceResponse.payment_method) ? invoiceResponse.payment_method : ($('#payment_method').val() || 'piutang'));
                                    } catch (e) {
                                        // ignore
                                    }
                                }

                                if (isCreatingInvoice) {
                                    if (!invoiceIdBefore) {
                                        openPrintNota(currentInvoiceId);
                                        return;
                                    }

                                    invoiceNeedsUpdateServer = false;
                                    try {
                                        billingData = [];
                                        deletedItems = [];
                                        window.__billingLightRefresh = false;
                                        table.ajax.reload(null, false);
                                    } catch (e) {
                                        // ignore
                                    }
                                    updatePaymentActionButtons($('#payment_method').val() || 'piutang');

                                    Swal.fire({
                                        title: 'Berhasil!',
                                        text: 'Invoice berhasil diupdate sesuai billing terbaru.',
                                        icon: 'success',
                                        confirmButtonText: 'OK',
                                        allowOutsideClick: false
                                    });
                                    return;
                                }

                                // After payment is processed (including piutang), lock billing edits.
                                billingLocked = true;
                                applyBillingLockUi();
                                try { table.ajax.reload(null, false); } catch (e) { /* ignore */ }

                                invoiceNeedsUpdateServer = false;

                                var icon = stockReduced ? 'success' : 'warning';
                                var html = 'Pembayaran berhasil diproses untuk invoice: <strong>' + (invoiceResponse.invoice_number || '') + '</strong>';
                                if (stockMessage) {
                                    try {
                                        var emphasized = (stockMessage || '').toString().toUpperCase();
                                        emphasized = emphasized.replace(/(STOK TIDAK DIKURANGI)/g, '<strong>$1</strong>');
                                    } catch (e) {
                                        var emphasized = (stockMessage || '').toString().toUpperCase();
                                    }
                                    html += '<br><small style="display:block;margin-top:8px;color:#555;">' + emphasized + '</small>';
                                }

                                Swal.fire({
                                    title: 'Berhasil!',
                                    html: html,
                                    icon: icon,
                                    confirmButtonText: 'OK',
                                    allowOutsideClick: false
                                });
                            },
                            error: function(xhr) {
                                const title = isCreatingInvoice ? 'Gagal Membuat Invoice' : 'Gagal Memproses Pembayaran';
                                const prefix = isCreatingInvoice ? 'Terjadi kesalahan dalam pembuatan invoice:' : 'Terjadi kesalahan saat memproses pembayaran:';
                                showReadableAjaxError(title, xhr, prefix);
                            }
                        });
                    }

                    // If we're processing payment and billing is already locked (e.g., piutang exists),
                    // don't try to save billing again; proceed directly to payment.
                    if (!isCreatingInvoice && billingLocked) {
                        proceedToInvoiceRequest();
                        return;
                    }

                    // First: save billing
                    $.ajax({
                        url: "{{ route('finance.billing.save') }}",
                        type: "POST",
                        data: requestData,
                        success: function(saveResponse) {
                            proceedToInvoiceRequest();
                        },
                        error: function(xhr) {
                            // If billing is already locked, saving will be rejected, but payment can still proceed.
                            // This happens e.g. when invoice already processed and user just wants to "Lunasi Pembayaran".
                            if (!isCreatingInvoice && xhr && Number(xhr.status) === 423) {
                                proceedToInvoiceRequest();
                                return;
                            }
                            showReadableAjaxError('Gagal Menyimpan Billing', xhr, 'Terjadi kesalahan saat menyimpan billing:');
                        }
                    });
                }
            });
        }

        // Buat Invoice (only creates unpaid invoice)
        $('#createInvoiceBtn').on('click', function() {
            runInvoiceFlow(true);
        });

        // Terima Pembayaran (process payment)
        $('#terimaPembayaranBtn').on('click', function() {
            // If invoice has active piutang, open the same Terima Pembayaran modal used on billing index.
            try {
                const piutangId = window.oldInvoice && window.oldInvoice.piutang_id ? window.oldInvoice.piutang_id : null;
                const invoiceNo = window.oldInvoice && window.oldInvoice.invoice_number ? window.oldInvoice.invoice_number : '';
                const piutangStatus = (window.oldInvoice && window.oldInvoice.piutang_payment_status) ? String(window.oldInvoice.piutang_payment_status).toLowerCase() : '';
                const piutangAmount = Number(window.oldInvoice && window.oldInvoice.piutang_amount ? window.oldInvoice.piutang_amount : 0);
                const piutangPaid = Number(window.oldInvoice && window.oldInvoice.piutang_paid_amount ? window.oldInvoice.piutang_paid_amount : 0);
                let remaining = piutangAmount - piutangPaid;
                if (!isFinite(remaining) || remaining < 0) remaining = 0;

                const hasActivePiutang = !!piutangId && (!piutangStatus || piutangStatus === 'unpaid' || piutangStatus === 'partial') && remaining > 0;
                if (hasActivePiutang) {
                    // Fill modal fields
                    $('#piutang_id').val(piutangId);
                    $('#piutang_invoice').val(invoiceNo);

                    // Prefill amount = 0 (user inputs payment to add)
                    $('#piutang_amount').val('0');

                    // Update inline "kekurangan" label
                    var $label = $('#piutang_kekurangan_label');
                    try {
                        var remFmt = 'kurang : RP ' + Number(remaining).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
                        $label.removeClass('text-success').addClass('text-danger').text(remFmt);
                    } catch (e) {
                        $label.removeClass('text-success').addClass('text-danger').text('kurang : RP ' + remaining);
                    }

                    // Default payment date to now
                    var now = new Date();
                    var pad = function(n){return n<10?'0'+n:n};
                    var local = now.getFullYear() + '-' + pad(now.getMonth()+1) + '-' + pad(now.getDate()) + 'T' + pad(now.getHours()) + ':' + pad(now.getMinutes());
                    $('#piutang_payment_date').val(local);

                    // Recalculate label when user types amount
                    function parseMoney(val) {
                        if (val === null || val === undefined) return 0;
                        if (typeof val === 'number') return val;
                        var s = String(val).trim();
                        if (!s) return 0;
                        s = s.replace(/[^0-9.,-]/g, '');
                        if (s.indexOf('.') !== -1 && s.indexOf(',') !== -1) {
                            s = s.replace(/\./g, '');
                            s = s.replace(/,/g, '.');
                        } else if (s.indexOf(',') !== -1 && s.indexOf('.') === -1) {
                            s = s.replace(/,/g, '.');
                        }
                        var f = parseFloat(s);
                        return isNaN(f) ? 0 : f;
                    }

                    $('#piutang_amount').off('input.piutangCreate').on('input.piutangCreate', function() {
                        var entered = parseMoney($(this).val());
                        var newRem = remaining - (isNaN(entered) ? 0 : entered);
                        if (!isFinite(newRem) || newRem < 0) newRem = 0;
                        if (newRem <= 0) {
                            $label.removeClass('text-danger').addClass('text-success').text('LUNAS');
                        } else {
                            try {
                                $label.removeClass('text-success').addClass('text-danger').text('kurang : RP ' + Number(newRem).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 }));
                            } catch (e) {
                                $label.removeClass('text-success').addClass('text-danger').text('kurang : RP ' + newRem);
                            }
                        }
                    });

                    // Submit handler (post to piutang receive endpoint) then reload the page for updated status
                    $('#btn-submit-terima').off('click.piutangCreate').on('click.piutangCreate', function() {
                        var submitId = $('#piutang_id').val();
                        if (!submitId) return;
                        var payload = {
                            amount: $('#piutang_amount').val(),
                            payment_date: $('#piutang_payment_date').val(),
                            payment_method: $('#piutang_payment_method').val(),
                            _token: '{{ csrf_token() }}'
                        };

                        $.post('{{ url('/finance/piutang') }}' + '/' + submitId + '/receive', payload)
                            .done(function(res) {
                                if (res && res.success) {
                                    $('#modalTerimaPembayaran').modal('hide');
                                    Swal.fire('Sukses', res.message || 'Pembayaran tercatat', 'success').then(function() {
                                        window.location.reload();
                                    });
                                } else {
                                    Swal.fire('Gagal', (res && res.message) ? res.message : 'Gagal menyimpan pembayaran', 'error');
                                }
                            }).fail(function(xhr) {
                                var msg = 'Terjadi kesalahan';
                                try { msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : msg; } catch(e){}
                                Swal.fire('Gagal', msg, 'error');
                            });
                    });

                    $('#modalTerimaPembayaran').modal('show');
                    return;
                }
            } catch (e) {
                // ignore and fallback to regular flow
            }

            if (!currentInvoiceId) {
                Swal.fire({
                    title: 'Info',
                    text: 'Invoice belum dibuat. Silakan klik Buat Invoice terlebih dahulu.',
                    icon: 'info'
                });
                return;
            }

            // If invoice exists but billing differs from invoice (unpaid), update invoice first.
            if (!currentInvoiceIsPaid && !billingLocked && invoiceNeedsUpdateNow()) {
                runInvoiceFlow(true);
                return;
            }

            // Open modal for payment input
            calculateTotals();
            const currentPaid = $('#amount_paid').val() || '0';
            const currentMethod = $('#payment_method').val() || 'cash';
            // If paid is 0, keep the input empty so user can type immediately.
            try {
                const paidNum = parseHarga(currentPaid || 0);
                $('#modal_amount_paid').val(paidNum > 0 ? currentPaid : '');
            } catch (e) {
                $('#modal_amount_paid').val('');
            }
            // Default method based on paid amount (rule)
            const methodInitRaw = (currentMethod || '').toString();
            const methodInit = methodInitRaw ? methodInitRaw : (parseHarga(currentPaid) > 0 ? 'cash' : 'piutang');
            $('#modal_payment_method').val(methodInit);
            // Apply Umum filtering + insurance lock (if any)
            setInsuranceOptionsVisible($('#modal_payment_method'), !isUmumVisit);
            applyLockedPaymentMethodToSelect($('#modal_payment_method'), lockedPaymentMethod);

            // Apply new rule: non-tunai auto-fill dibayar = total
            syncModalPaidFromMethod();
            // Keep piutang/cash rule consistent with current paid
            syncModalMethodFromPaid();
            updatePaymentActionButtons($('#modal_payment_method').val() || 'piutang');

            // Reset memory each open, then enforce rule
            lastManualCashPaidModal = null;
            syncModalPaidFromMethod();
            syncModalMethodFromPaid();
            calculatePaymentSummaryForModal();
            $('#paymentModal').modal('show');
        });

        // Auto-focus Dibayar field when modal is visible
        $('#paymentModal').on('shown.bs.modal', function() {
            try {
                const $input = $('#modal_amount_paid');
                $input.trigger('focus');
                // Select existing text (if any) for quick overwrite
                const el = $input.get(0);
                if (el && typeof el.select === 'function') {
                    el.select();
                }
            } catch (e) {
                // ignore
            }
        });

        // Auto-focus Jumlah field when Lunasi/Terima Pembayaran (Piutang) modal is visible
        $('#modalTerimaPembayaran').on('shown.bs.modal', function() {
            try {
                const $input = $('#piutang_amount');
                $input.trigger('focus');
                const el = $input.get(0);
                if (el && typeof el.select === 'function') {
                    el.select();
                }
            } catch (e) {
                // ignore
            }
        });

        // If user cancels/closes the modal, revert labels to the last confirmed method
        $('#paymentModal').on('hidden.bs.modal', function() {
            updatePaymentActionButtons($('#payment_method').val() || 'cash');
        });

        // When method changes: auto-fill paid for non-tunai, keep rules consistent
        $('#modal_payment_method').on('change', function() {
            syncModalPaidFromMethod();
            syncModalMethodFromPaid();
            updatePaymentActionButtons($('#modal_payment_method').val() || 'piutang');
            calculatePaymentSummaryForModal();
        });

        // When typing paid amount: only derive method for cash/piutang flow
        $('#modal_amount_paid').on('change input', function() {
            syncModalMethodFromPaid();
            updatePaymentActionButtons($('#modal_payment_method').val() || 'piutang');
            calculatePaymentSummaryForModal();
        });

        // Confirm payment from modal
        $('#confirmPaymentBtn').on('click', function() {
            const method = ($('#modal_payment_method').val() || 'cash').toString();
            const paid = parseHarga($('#modal_amount_paid').val() || 0);

            if (method !== 'piutang' && paid <= 0) {
                Swal.fire({
                    title: 'Info',
                    text: 'Masukkan jumlah dibayar terlebih dahulu.',
                    icon: 'info'
                });
                return;
            }

            // Sync modal values into hidden fields used by calculateTotals() + backend payload
            $('#amount_paid').val(method === 'piutang' ? '0' : ($('#modal_amount_paid').val() || '0'));
            $('#payment_method').val(method || 'cash');
            $('#paymentModal').modal('hide');
            updatePaymentActionButtons($('#payment_method').val() || 'cash');
            runInvoiceFlow(false);
        });

        // Print nota button
        $('#printNotaBtn').on('click', function() {
            openPrintNota(currentInvoiceId);
        });
        
        // --- Select2 AJAX for Tindakan ---
        $('#select-tindakan').select2({
            placeholder: 'Cari tindakan...',
            ajax: {
                url: '/tindakan/search',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return { q: params.term };
                },
                processResults: function(data) {
                    // Support both {results: [...]} and plain array
                    var items = Array.isArray(data) ? data : (data.results || []);
                    return {
                        results: items.map(function(item) {
                            return { id: item.id, text: item.text || item.nama, harga: item.harga };
                        })
                    };
                },
                cache: true
            },
            minimumInputLength: 1
        });
        // --- Select2 AJAX for Lab ---
        $('#select-lab').select2({
            placeholder: 'Cari lab...',
            ajax: {
                url: '/labtest/search',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return { q: params.term };
                },
                processResults: function(data) {
                    return {
                        results: data.map(function(item) {
                            return { id: item.id, text: item.nama, harga: item.harga };
                        })
                    };
                },
                cache: true
            },
            minimumInputLength: 1
        });
        // --- Select2 AJAX for Konsultasi ---
        $('#select-konsultasi').select2({
            placeholder: 'Cari biaya konsultasi...',
            ajax: {
                url: '/konsultasi/search',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return { q: params.term };
                },
                processResults: function(data) {
                    return {
                        results: data.map(function(item) {
                            return { id: item.id, text: item.nama, harga: item.harga };
                        })
                    };
                },
                cache: true
            },
            minimumInputLength: 1
        });

        // --- Select2 AJAX for Obat/Produk ---
        $('#select-obat').select2({
            placeholder: 'Cari obat/produk...',
            ajax: {
                url: '/obat/search',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return { q: params.term };
                },
                processResults: function(data) {
                    // Support both {results: [...]} and array response
                    var items = Array.isArray(data) ? data : (data.results || []);
                    return {
                        results: items.map(function(item) {
                            return { id: item.id, text: item.text || item.nama, harga: item.harga_nonfornas || item.harga || 0 };
                        })
                    };
                },
                cache: true
            },
            minimumInputLength: 1
        });

        // Add selected Tindakan to billingData
        $('#select-tindakan').on('select2:select', function(e) {
            const data = e.params.data;
            const harga = parseHarga(data.harga);
            const qty = parseInt(data.qty) || 1;
            const total = harga * qty;
            billingData.push({
                id: 'tindakan-' + data.id,
                billable_id: data.id,
                billable_type: 'App\\Models\\ERM\\Tindakan',
                nama_item: data.text,
                jumlah: 'Rp ' + formatCurrency(harga),
                jumlah_raw: harga,
                qty: qty,
                diskon: 0,
                diskon_type: 'nominal',
                harga_akhir: 'Rp ' + formatCurrency(total),
                harga_akhir_raw: total,
                deleted: false,
                deskripsi: ''
            });
            updateTable();
            calculateTotals();
            $(this).val(null).trigger('change');
            Swal.fire({
                icon: 'info',
                title: 'Perhatian',
                text: 'Harap informasikan perawat untuk menambah tindakan ini ke riwayat tindakan.',
                timer: 2500,
                showConfirmButton: false
            });
        });
        // Add selected Lab to billingData
        $('#select-lab').on('select2:select', function(e) {
            const data = e.params.data;
            const harga = parseHarga(data.harga);
            const qty = parseInt(data.qty) || 1;
            const total = harga * qty;
            billingData.push({
                id: 'lab-' + data.id,
                billable_id: data.id,
                billable_type: 'App\\Models\\ERM\\LabTest',
                nama_item: data.text,
                jumlah: 'Rp ' + formatCurrency(harga),
                jumlah_raw: harga,
                qty: qty,
                diskon: 0,
                diskon_type: 'nominal',
                harga_akhir: 'Rp ' + formatCurrency(total),
                harga_akhir_raw: total,
                deleted: false,
                deskripsi: ''
            });
            updateTable();
            calculateTotals();
            $(this).val(null).trigger('change');
            Swal.fire({
                icon: 'info',
                title: 'Perhatian',
                text: 'Harap informasikan petugas lab untuk menambah pemeriksaan ini ke riwayat lab.',
                timer: 2500,
                showConfirmButton: false
            });
        });
        // Add selected Konsultasi to billingData
        $('#select-konsultasi').on('select2:select', function(e) {
            const data = e.params.data;
            const harga = parseHarga(data.harga);
            const qty = parseInt(data.qty) || 1;
            const total = harga * qty;
            billingData.push({
                id: 'konsultasi-' + data.id,
                billable_id: data.id,
                billable_type: 'App\\Models\\ERM\\Konsultasi',
                nama_item: data.text,
                jumlah: 'Rp ' + formatCurrency(harga),
                jumlah_raw: harga,
                qty: qty,
                diskon: 0,
                diskon_type: 'nominal',
                harga_akhir: 'Rp ' + formatCurrency(total),
                harga_akhir_raw: total,
                deleted: false,
                deskripsi: ''
            });
            updateTable();
            calculateTotals();
            $(this).val(null).trigger('change');
        });

        // Add selected Obat/Produk to billingData
        $('#select-obat').on('select2:select', function(e) {
            const data = e.params.data;
            const harga = parseHarga(data.harga);
            const qty = parseInt(data.qty) || 1;
            const total = harga * qty;
            billingData.push({
                id: 'obat-' + data.id,
                billable_id: data.id,
                billable_type: 'App\\Models\\ERM\\Obat',
                nama_item: data.text,
                jumlah: 'Rp ' + formatCurrency(harga),
                jumlah_raw: harga,
                qty: qty,
                diskon: 0,
                diskon_type: 'nominal',
                harga_akhir: 'Rp ' + formatCurrency(total),
                harga_akhir_raw: total,
                deleted: false,
                deskripsi: ''
            });
            updateTable();
            calculateTotals();
            $(this).val(null).trigger('change');
            Swal.fire({
                icon: 'info',
                title: 'Perhatian',
                text: 'Harap informasikan farmasi untuk menambah produk/obat ini ke riwayat farmasi.',
                timer: 2500,
                showConfirmButton: false
            });
        });
        
        // Initial calculation of totals
        calculateTotals();
        
        // Force column widths to be applied immediately after init
        setTimeout(function() {
            table.columns.adjust();
        }, 100);
    });
</script>
@endsection