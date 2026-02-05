@extends('layouts.finance.app')
@section('title', 'Finance | Billing')
@section('navbar')
    @include('layouts.finance.navbar')
@endsection
@section('content')

@include('finance.partials.modal-billing-edititem')

<style>
    /* Highlight billing row when selected gudang stock is lower than required qty */
    tr.low-stock {
        background-color: #f8d7da !important; /* light red */
    }
    tr.low-stock .stock-cell {
        color: #721c24; /* dark red text for stock value */
        font-weight: 600;
    }
    /* Gender badge: rounded rectangle around the icon */
    .gender-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        padding: 0;
        border-radius: 5px;
        border: 1px solid rgba(0,0,0,0.06);
        background: #f8f9fa;
        line-height: 1;
        box-shadow: 0 1px 2px rgba(0,0,0,0.04);
    }
    .gender-badge .fa-mars, .gender-badge .fa-venus { color: #fff; font-size: 0.95rem; }
    .gender-badge.gender-male {
        background: #0d6efd; /* bootstrap primary */
        border-color: rgba(13,110,253,0.3);
    }
    .gender-badge.gender-female {
        background: #ff6fb3; /* soft pink */
        border-color: rgba(255,111,179,0.28);
    }
    /* Patient name + id styles */
    .patient-label { display:inline-flex; align-items:center; }
    .patient-name { font-weight:600; margin-left:8px; color:#0b1220; text-transform:uppercase; }
    .patient-id { font-weight:600; color:#2b6cb0; margin-left:8px; }
    .patient-meta { color:#6c757d; }
    .patient-age { color:#6c757d; font-weight:600; margin-left:8px; }
    /* Data Pasien card improvements */
    .data-pasien {
        border-radius: 6px;
    }
    .data-pasien .card-body {
        padding: 0.8rem 1rem;
    }
    .data-pasien .table {
        margin-bottom: 0;
    }
    .data-pasien .table td {
        padding: 0.32rem 0.5rem;
        vertical-align: middle;
    }
    .data-pasien .table td.label {
        width: 140px;
        font-weight: 600;
        color: #343a40;
        white-space: nowrap;
    }
    .data-pasien .invoice-number {
        font-weight: 700;
        color: #0d6efd;
    }
    .data-pasien .small-note { margin-top: .25rem; color: #6c757d; }
</style>

<div class="container-fluid">
    <!-- Prefill billing fields with old invoice data if available -->
    <script>
        window.oldInvoice = {
            global_discount: @json($invoice->discount_value ?? ''),
            global_discount_type: @json($invoice->discount_type ?? ''),
            tax_percentage: @json($invoice->tax_percentage ?? ''),
            admin_fee: @json($invoice?->items?->first(function($item) { return stripos($item->name ?? '', 'Biaya Administrasi') !== false; })?->unit_price ?? ''),
            shipping_fee: @json($invoice?->items?->where('name', 'Biaya Ongkir')->first()?->unit_price ?? ''),
            amount_paid: @json($invoice->amount_paid ?? ''),
            payment_method: @json($invoice->payment_method ?? ''),
            change_amount: @json($invoice->change_amount ?? '')
        };
        
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
    </script>
    <div class="row mb-1 mt-1">
        <div class="col text-right mb-0">
            <a href="{{ route('finance.billing.index') }}" class="btn btn-danger font-weight-bold px-3" title="Kembali ke daftar billing">
                <i class="fas fa-arrow-left mr-2"></i> Kembali ke Daftar Billing
            </a>
        </div>
    </div>

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
                        @if($invoice)
                            @php
                                $amountPaid = floatval($invoice->amount_paid ?? 0);
                                $totalAmount = floatval($invoice->total_amount ?? 0);
                                $statusHtml = '';
                                if ($totalAmount > 0 && $amountPaid >= $totalAmount) {
                                    $statusHtml = '<span style="color: #fff; background: #28a745; padding: 2px 8px; border-radius: 8px; font-size: 13px;">Lunas</span>';
                                } elseif ($amountPaid > 0 && $amountPaid < $totalAmount) {
                                    $statusHtml = '<span style="color: #fff; background: #ffc107; padding: 2px 8px; border-radius: 8px; font-size: 13px;">Belum Lunas</span>';
                                } else {
                                    $statusHtml = '<span style="color: #fff; background: #dc3545; padding: 2px 8px; border-radius: 8px; font-size: 13px;">Belum Dibayar</span>';
                                }
                            @endphp
                            {!! $statusHtml !!}
                        @else
                            @php
                                // If no invoice, check if there are any billings; if all trashed show Terhapus else Belum Dibayar
                                $totalBillings = \App\Models\Finance\Billing::withTrashed()->where('visitation_id', $visitation->id ?? null)->count();
                                $trashedBillings = \App\Models\Finance\Billing::onlyTrashed()->where('visitation_id', $visitation->id ?? null)->count();
                                if ($totalBillings > 0 && $trashedBillings === $totalBillings) {
                                    echo '<span style="color: #fff; background: #6c757d; padding: 2px 8px; border-radius: 8px; font-size: 13px;">Terhapus</span>';
                                } else {
                                    echo '<span style="color: #fff; background: #dc3545; padding: 2px 8px; border-radius: 8px; font-size: 13px;">Belum Dibayar</span>';
                                }
                            @endphp
                        @endif
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
                                    <th style="width: 12%">Stok Tersedia</th>
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
                    <div class="border-top pt-3 mt-3">
                        <h6 class="mb-3">Pembayaran</h6>
                        <div class="form-group row mb-2">
                            <div class="col-6">
                                <label for="amount_paid">Dibayar</label>
                                <input type="text" class="form-control" id="amount_paid" value="0" placeholder="Jumlah uang yang diberikan pasien">
                                <small class="text-muted">Masukkan jumlah uang yang diberikan oleh pasien</small>
                            </div>
                            <div class="col-6">
                                <label for="payment_method">Metode Pembayaran</label>
                                <select class="form-control" id="payment_method">
                                    <option value="cash">Tunai</option>
                                    {{-- <option value="non_cash">Non Tunai</option> --}}
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
                                </select>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Kembali:</span>
                            <span id="change_amount" class="font-weight-bold text-success">Rp 0</span>
                        </div>
                        <div class="d-flex justify-content-between" style="display:none;" id="shortage_label">
                            <span>Kekurangan:</span>
                            <span id="shortage_amount" class="font-weight-bold text-danger">Rp 0</span>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button id="createInvoiceBtn" class="btn btn-primary btn-block">
                            <i class="fas fa-file-invoice mr-1"></i> Buat Invoice
                        </button>
                        {{--<button id="saveAllChangesBtn" class="btn btn-outline-secondary btn-block mt-2">
                            <i class="fas fa-save mr-1"></i> Simpan Billing
                        </button>--}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
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
        $('.select2').select2({ width: '100%' });
        
        // Load gudang data first
        loadGudangData();
        
        // Store all billing data (with changes) here
        let billingData = [];
        let deletedItems = [];
        
        // Helper function to get default gudang for an item
        function getDefaultGudangForItem(item) {
            // Determine transaction type based on item
            let transactionType = 'tindakan'; // default
            
            // Check if this is an obat/resep item
            if (item.billable_type === 'App\\Models\\ERM\\ResepFarmasi' || 
                item.billable_type === 'App\\Models\\ERM\\Racikan' ||
                (item.deskripsi && item.deskripsi.toLowerCase().includes('obat')) ||
                (item.nama_item && item.nama_item.toLowerCase().includes('obat'))) {
                transactionType = 'resep';
            }
            // Check if this is a RiwayatTindakan with kode tindakan obat
            else if (item.billable_type === 'App\\Models\\ERM\\RiwayatTindakan') {
                // For riwayat tindakan, use kode_tindakan transaction type for obat stock
                transactionType = 'kode_tindakan';
            }
            // Check if this is a bundled obat from tindakan
            else if (item.billable_type === 'App\\Models\\ERM\\Obat' && 
                     item.keterangan && item.keterangan.includes('Obat Bundled:')) {
                transactionType = 'tindakan';
            }
            
            return window.gudangData.mappings[transactionType] || 
                   (window.gudangData.gudangs.length ? window.gudangData.gudangs[0].id : null);
        }
        
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
                dataSrc: function(json) {
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
                            
                            return item;
                        });
                        
                        // Update our billingData 
                        billingData = json.data;
                    }
                    
                    // Filter out deleted items from display
                    const visibleData = json.data.filter(item => !item.deleted);
                    
                    // Calculate totals after data is loaded
                    calculateTotals();
                    
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
                { data: 'nama_item', name: 'nama_item', width: "18%" },
                { data: 'jumlah', name: 'jumlah', width: "8%" },
                { data: 'qty', name: 'qty', width: "5%" },
                
                {
                    // New column: show stock available in the selected gudang for this item
                    data: null,
                    orderable: false,
                    searchable: false,
                    width: "12%",
                    render: function(data, type, row, meta) {
                        const isObatItem = row.billable_type === 'App\\Models\\ERM\\ResepFarmasi' || 
                                         row.billable_type === 'App\\Models\\ERM\\Racikan' ||
                                         (row.deskripsi && row.deskripsi.toLowerCase().includes('obat')) ||
                                         (row.nama_item && row.nama_item.toLowerCase().includes('obat'));

                        if (!isObatItem) {
                            return '<span class="text-muted">-</span>';
                        }


                        // Determine obat id if available (ResepFarmasi -> billable.obat.id) or direct Obat
                        let obatId = null;
                        try {
                            if (row.billable_type === 'App\\Models\\ERM\\ResepFarmasi' && row.billable && row.billable.obat) {
                                obatId = row.billable.obat.id;
                            } else if (row.billable_type === 'App\\Models\\ERM\\Obat' && row.billable_id) {
                                obatId = row.billable_id;
                            } else if (row.obat_id) {
                                // sometimes payload uses obat_id directly
                                obatId = row.obat_id;
                            } else if (row.billable && row.billable.obat) {
                                obatId = row.billable.obat.id;
                            } else {
                                // Fallback: some client-side-added rows use an id like 'obat-123'
                                const m = (row.id || '').toString().match(/^obat-(\d+)$/);
                                if (m) obatId = m[1];
                            }
                        } catch (e) {
                            console.debug('Error detecting obatId for row', row, e);
                            obatId = null;
                        }

                        // Placeholder - will be filled by AJAX after draw
                        const itemId = row.id;
                        // If this is a racikan and backend exposed component IDs, render a stock-cell per component
                        if (row.is_racikan && Array.isArray(row.racikan_obat_ids) && row.racikan_obat_ids.length) {
                            const names = Array.isArray(row.racikan_obat_list) ? row.racikan_obat_list : [];
                            const comps = Array.isArray(row.racikan_components) ? row.racikan_components : [];
                            let html = '<div class="racikan-stock-list">';
                            row.racikan_obat_ids.forEach(function(compId, idx) {
                                const label = names[idx] ? '<small>' + names[idx] + '</small> ' : '';
                                const stokD = (comps[idx] && typeof comps[idx].stok_dikurangi !== 'undefined') ? parseInt(comps[idx].stok_dikurangi) : null;
                                const stokDHtml = (stokD !== null && !isNaN(stokD)) ? '<small class="text-muted stok-dikurangi" title="Stok Dikurangi"> (-' + stokD + ')</small>' : '';
                                html += '<div class="racikan-stock-line">' + label + '<span class="stock-cell" data-item-id="' + itemId + '" data-obat-id="' + compId + '" data-child-index="' + idx + '">-</span>' + stokDHtml + '</div>';
                            });
                            html += '<div class="gudang-name small text-muted mt-1"></div>';
                            html += '</div>';
                            return html;
                        }

                        // Single-component fallback - include stok_dikurangi if available
                        let stokDsingle = null;
                        try {
                            if (row.billable && typeof row.billable.jumlah !== 'undefined') stokDsingle = parseInt(row.billable.jumlah) || null;
                        } catch (e) { stokDsingle = null; }
                        const stokDsingleHtml = (stokDsingle !== null && !isNaN(stokDsingle)) ? ' <small class="text-muted stok-dikurangi" title="Stok Dikurangi">(-' + stokDsingle + ')</small>' : '';
                        return '<div class="stock-wrap"><span class="stock-cell" data-item-id="' + itemId + '" data-obat-id="' + (obatId || '') + '">-</span>' + stokDsingleHtml + '<div class="gudang-name small text-muted mt-1"></div></div>';
                    }
                },
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
                                <button class="btn btn-sm btn-outline-danger delete-btn"
                                    data-id="${row.id}"
                                    data-row-index="${meta.row}">
                                    <i class="fas fa-trash"></i>
                                </button>
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
                { width: "12%", targets: 4, className: 'text-right' }, // Stock column (right-align)
                { width: "8%", targets: 5, className: 'text-right' }, // Right-align discount column
                { width: "8%", targets: 6, className: 'text-right' }, // Right-align total column
                { width: "10%", targets: 7 }
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
                // Update stock cells for visible rows
                try {
                    updateAllStockCells();
                } catch (e) {
                    console.error('Failed to update stock cells:', e);
                }
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
            
            console.log('Gudang selection updated for item', itemId, 'to gudang', selectedGudangId);
            // Refresh stock cell for this row
            const $cell = $('.stock-cell[data-item-id="' + itemId + '"]');
            if ($cell.length) {
                loadStockForCell($cell);
            }
        });

        // Load stock info for a given stock-cell element
        function loadStockForCell($cell) {
            const itemId = $cell.data('item-id');
            const obatId = $cell.data('obat-id');
            if (!obatId) {
                $cell.text('-');
                return;
            }

            // Try to get selected gudang from select element first
            let gudangId = $('select.gudang-selector[data-item-id="' + itemId + '"]').val();

            // If not available, try from billingData
            if (!gudangId) {
                const item = billingData.find(i => i.id == itemId);
                if (item && item.selected_gudang_id) gudangId = item.selected_gudang_id;
            }

            // If still not available, determine default gudang for this item via server-loaded mapping
            if (!gudangId) {
                const item = billingData.find(i => i.id == itemId);
                if (item) gudangId = getDefaultGudangForItem(item);
            }

            if (!gudangId) {
                $cell.text('-');
                return;
            }

                        // Call batch-details endpoint and sum stok
                        // Show loading indicator
                        $cell.text('...');
            $.getJSON("{{ route('erm.stok-gudang.batch-details') }}", { obat_id: obatId, gudang_id: gudangId })
                .done(function(resp) {
                    try {
                        const data = resp.data || [];
                        let total = 0;
                        data.forEach(function(d) {
                            // prefer numeric stok if present, fallback to stok_display parsing
                            if (typeof d.stok !== 'undefined') {
                                total += parseFloat(d.stok) || 0;
                            } else if (d.stok_display) {
                                // remove formatting
                                const num = d.stok_display.toString().replace(/[^0-9\-]/g, '');
                                total += parseFloat(num) || 0;
                            }
                        });

                        // If no batch rows found, show 0 instead of '-'
                        if (data.length === 0) {
                            $cell.text('0');
                        } else {
                            $cell.text(total);
                        }

                        // Also show the selected gudang name (if available) under the stock value
                        try {
                            let gudangName = '';
                            if (window.gudangData && Array.isArray(window.gudangData.gudangs)) {
                                const found = window.gudangData.gudangs.find(function(g) { return String(g.id) === String(gudangId) || g.id == gudangId; });
                                if (found) gudangName = found.nama || found.name || '';
                            }
                            if (gudangName) {
                                // For racikan rows, find the outer container; otherwise, find stock-wrap
                                const $racikanList = $cell.closest('.racikan-stock-list');
                                if ($racikanList.length) {
                                    $racikanList.find('.gudang-name').text(gudangName);
                                } else {
                                    $cell.closest('.stock-wrap').find('.gudang-name').text(gudangName);
                                }
                            }
                        } catch (e) {
                            console.debug('Failed to set gudang name', e);
                        }


                        // Determine required qty for this billing row or component.
                        // For racikan components prefer the stored `stok_dikurangi` per component.
                        let requiredQty = 1;
                        try {
                            const item = billingData.find(i => i.id == itemId);
                            if (item) {
                                if (item.is_racikan) {
                                    // If this cell represents a component (has data-child-index), prefer its stok_dikurangi
                                    const childIndex = $cell.data('child-index');
                                    if (typeof childIndex !== 'undefined' && Array.isArray(item.racikan_components) && item.racikan_components[childIndex]) {
                                        const comp = item.racikan_components[childIndex];
                                        if (comp && typeof comp.stok_dikurangi !== 'undefined' && comp.stok_dikurangi !== null) {
                                            requiredQty = Math.abs(parseInt(comp.stok_dikurangi)) || 0;
                                        } else {
                                            requiredQty = parseInt(item.racikan_bungkus) || 1;
                                        }
                                    } else {
                                        requiredQty = parseInt(item.racikan_bungkus) || 1;
                                    }
                                } else {
                                    // Non-racikan: prefer billable.jumlah if present (we persist stok_dikurangi there), else qty
                                    if (item.billable && typeof item.billable.jumlah !== 'undefined' && item.billable.jumlah !== null) {
                                        requiredQty = Math.abs(parseInt(item.billable.jumlah)) || parseInt(item.qty) || 1;
                                    } else {
                                        requiredQty = parseInt(item.qty) || 1;
                                    }
                                }
                            } else {
                                const qText = $cell.closest('tr').find('td').eq(4).text().trim();
                                const qNum = qText.replace(/[^0-9]/g, '');
                                requiredQty = qNum ? parseInt(qNum) : 1;
                            }
                        } catch (err) {
                            console.debug('Failed to determine requiredQty for item', itemId, err);
                            requiredQty = 1;
                        }

                        // Recalculate low-stock state for the whole row based on all component stock-cells
                        const $tr = $cell.closest('tr');
                        let anyLow = false;
                        $tr.find('.stock-cell').each(function() {
                            const txt = $(this).text().toString().replace(/[^0-9\-\.]/g, '');
                            const val = txt === '' ? NaN : parseFloat(txt);
                            if (!isNaN(val)) {
                                if (Number(val) < Number(requiredQty)) {
                                    anyLow = true;
                                }
                            }
                            // If value is non-numeric (unknown), don't treat as low-stock
                        });

                        if (anyLow) {
                            $tr.addClass('low-stock');
                        } else {
                            $tr.removeClass('low-stock');
                        }

                        console.debug('Batch details loaded', { obatId: obatId, gudangId: gudangId, total: total, requiredQty: requiredQty, raw: resp });
                    } catch (e) {
                        console.error('Error parsing batch-details response', e, resp);
                            $cell.text('-');
                            $cell.closest('tr').removeClass('low-stock');
                    }
                })
                .fail(function(xhr) {
                    console.error('Failed to load batch details for obat', obatId, 'gudang', gudangId, xhr);
                    // If server returned 404/500 or network failed, keep '-' to indicate unknown
                        $cell.text('-');
                        $cell.closest('tr').removeClass('low-stock');
                });
        }

        // Update all visible stock cells
        function updateAllStockCells() {
            $('.stock-cell').each(function() {
                loadStockForCell($(this));
            });
        }
        
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
            const id = $(this).data('id');
            const rowIndex = $(this).data('row-index');
            const jumlah = $(this).data('jumlah');
            const diskon = $(this).data('diskon');
            const diskon_type = $(this).data('diskon_type');
            const qty = $(this).data('qty');

            $('#edit_id').val(id);
            $('#edit_row_index').val(rowIndex);
            $('#jumlah').val(jumlah);
            $('#diskon').val(diskon);
            $('#diskon_type').val(diskon_type);
            $('#edit_qty').val(qty);

            // Debug: Show harga before edit
            // console.log('[DEBUG] Harga before edit:', jumlah);
            $('#editModal').modal('show');
        });
        
        // Fix: Use document delegation for delete button
        $(document).on('click', '.delete-btn', function() {
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
                    Swal.fire({
                        title: 'Error!',
                        text: 'Terjadi kesalahan: ' + xhr.responseText,
                        icon: 'error'
                    });
                    console.error('Error details:', xhr.responseText);
                }
            });
        }
    });
});
        
        // Create invoice button (single-Swal flow to avoid stacked alerts)
        $('#createInvoiceBtn').on('click', function() {
            Swal.fire({
                title: 'Konfirmasi',
                text: 'Simpan semua perubahan billing dan buat invoice sekarang?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Simpan & Buat Invoice!',
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
                    const requestData = {
                        _token: "{{ csrf_token() }}",
                        visitation_id: correctVisitationId,
                        edited_items: editedItems,
                        new_items: newItems,
                        deleted_items: deletedItems,
                        totals: JSON.stringify(window.billingTotals)
                    };

                    // Open a single loading modal and update it through the process
                    Swal.fire({
                        title: 'Memproses...',
                        text: 'Harap tunggu, sedang menyimpan dan membuat invoice.',
                        icon: 'info',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: () => { Swal.showLoading(); }
                    });

                    // First: save billing
                    $.ajax({
                        url: "{{ route('finance.billing.save') }}",
                        type: "POST",
                        data: requestData,
                        success: function(saveResponse) {
                            // After save, create invoice
                            const items = billingData.filter(item => !item.deleted);
                            if (items.length === 0) {
                                Swal.fire({
                                    title: 'Peringatan!',
                                    text: 'Tidak ada item billing yang valid!',
                                    icon: 'warning'
                                });
                                return;
                            }

                            // Update the same modal to indicate invoice creation
                            try {
                                Swal.update({ title: 'Membuat Invoice...', text: 'Harap tunggu, sedang memproses invoice.' });
                            } catch(e) {
                                // Fallback: if Swal.update isn't available, close and open a new loading modal
                                Swal.close();
                                Swal.fire({
                                    title: 'Membuat Invoice...',
                                    text: 'Harap tunggu, sedang memproses invoice.',
                                    icon: 'info',
                                    allowOutsideClick: false,
                                    showConfirmButton: false,
                                    didOpen: () => { Swal.showLoading(); }
                                });
                            }

                                // Debug: log payload being sent for invoice creation
                                console.debug('Creating invoice payload', {
                                    visitation_id: correctVisitationId,
                                    items: items,
                                    totals: window.billingTotals,
                                    gudang_selections: collectGudangSelections()
                                });

                                $.ajax({
                                url: "{{ route('finance.billing.createInvoice') }}",
                                type: "POST",
                                data: JSON.stringify({
                                    _token: "{{ csrf_token() }}",
                                    visitation_id: correctVisitationId,
                                    items: items,
                                    totals: JSON.stringify(window.billingTotals),
                                    gudang_selections: collectGudangSelections()
                                }),
                                contentType: 'application/json; charset=utf-8',
                                dataType: 'json',
                                success: function(invoiceResponse) {
                                    // The backend returns stock_reduced and stock_message
                                    var stockReduced = invoiceResponse.stock_reduced === true || invoiceResponse.stock_reduced === 1;
                                    var stockMessage = invoiceResponse.stock_message || '';
                                    var icon = stockReduced ? 'success' : 'warning';
                                    var html = 'Invoice berhasil dibuat dengan nomor: <strong>' + (invoiceResponse.invoice_number || invoiceResponse.invoice_number) + '</strong>';
                                    if (stockMessage) {
                                        // Convert the message to UPPERCASE and emphasize the 'STOK TIDAK DIKURANGI' part
                                        try {
                                            var emphasized = (stockMessage || '').toString().toUpperCase();
                                            emphasized = emphasized.replace(/(STOK TIDAK DIKURANGI)/g, '<strong>$1</strong>');
                                        } catch (e) {
                                            var emphasized = (stockMessage || '').toString().toUpperCase();
                                        }
                                        html += '<br><small style="display:block;margin-top:8px;color:#555;">' + emphasized + '</small>';
                                    }

                                    // Build print URL using the returned invoice id
                                    var invoiceId = invoiceResponse.invoice_id || invoiceResponse.id || (invoiceResponse.invoice && invoiceResponse.invoice.id) || null;
                                    var printUrl = invoiceId ? ('{{ url('/finance/invoice') }}/' + invoiceId + '/print-nota') : null;

                                    // Show the Swal with a side-by-side 'Cetak Nota' (confirm) and 'Tutup' (cancel) buttons
                                    Swal.fire({
                                        title: 'Berhasil!',
                                        html: html,
                                        icon: icon,
                                        showCancelButton: true,
                                        confirmButtonText: 'Cetak Nota',
                                        cancelButtonText: 'Tutup',
                                        allowOutsideClick: false
                                    }).then(function(result) {
                                        if (result.value) {
                                            if (printUrl) {
                                                // Show the print page inside a modal (iframe) and let user print from there
                                                // Render preview with an iframe and a custom Print button inside the HTML.
                                                // We hide the built-in confirm button and use the Cancel button as the "Tutup" close control.
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
                                            } else {
                                                // If print URL isn't available, show an informative alert
                                                Swal.fire({
                                                    title: 'Info',
                                                    text: 'URL cetak nota tidak tersedia.',
                                                    icon: 'info'
                                                });
                                            }
                                        }
                                        // If cancelled (Tutup) do nothing — keep user on billing page
                                    });
                                },
                                error: function(xhr) {
                                    Swal.fire({
                                        title: 'Error!',
                                        text: 'Terjadi kesalahan dalam pembuatan invoice: ' + xhr.responseText,
                                        icon: 'error'
                                    });
                                }
                            });
                        },
                        error: function(xhr) {
                            Swal.fire({
                                title: 'Error!',
                                text: 'Terjadi kesalahan saat menyimpan billing: ' + xhr.responseText,
                                icon: 'error'
                            });
                        }
                    });
                }
            });
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