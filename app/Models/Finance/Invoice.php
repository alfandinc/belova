<?php

namespace App\Models\Finance;

use App\Models\ERM\Visitation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Finance\Piutang;

class Invoice extends Model
{
    use HasFactory;

    protected $table = 'finance_invoices';

    /** Payment methods the kasir can choose, with their printed label. */
    public const PAYMENT_METHODS = [
        'cash' => 'Tunai',
        'piutang' => 'Piutang',
        'edc_bca' => 'EDC BCA',
        'edc_bni' => 'EDC BNI',
        'edc_bri' => 'EDC BRI',
        'edc_mandiri' => 'EDC Mandiri',
        'qris' => 'QRIS',
        'transfer' => 'Transfer',
        'shopee' => 'Shopee',
        'tiktokshop' => 'Tiktokshop',
        'tokopedia' => 'Tokopedia',
        'asuransi_inhealth' => 'Asuransi InHealth',
        'asuransi_brilife' => 'Asuransi Brilife',
        'asuransi_admedika' => 'Asuransi Admedika',
        'asuransi_bcalife' => 'Asuransi BCA Life',
    ];

    protected $fillable = [
        'visitation_id',
        'invoice_number',
        'subtotal',
        'discount',
        'tax',
        'total_amount',
        'amount_paid',
        'change_amount',
        'shortage_amount',
        'retur_amount',
        'payment_method',
        'status',
        'transaction_type',
        'payment_date',
        'transaction_date',
        'notes',
        'user_id',
        'discount_type',
        'discount_value',
        'tax_percentage'
    ];

    protected $casts = [
        'payment_date' => 'datetime',
        'transaction_date' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'shortage_amount' => 'decimal:2',
        'retur_amount' => 'decimal:2',
        'transaction_type' => 'string',
    ];

    protected static function booted(): void
    {
        // One invoice per visit. Older visits may still hold two; those stay, and the newest one is the one used.
        static::creating(function (Invoice $invoice) {
            if (self::where('visitation_id', $invoice->visitation_id)->exists()) {
                throw new \RuntimeException('Kunjungan ini sudah memiliki invoice.');
            }
        });

        // Transaction date = first time the payment is processed (paid / piutang / asuransi; stock cut).
        // Set once and never moved: omset is dated by it.
        static::saving(function (Invoice $invoice) {
            if (!empty($invoice->payment_method) && empty($invoice->transaction_date) && self::hasTransactionDateColumn()) {
                $invoice->transaction_date = now();
            }
        });
    }

    private static ?bool $hasTransactionDate = null;

    /** Guard for servers that haven't run the transaction_date migration yet. */
    public static function hasTransactionDateColumn(): bool
    {
        return self::$hasTransactionDate ??= \Illuminate\Support\Facades\Schema::hasColumn('finance_invoices', 'transaction_date');
    }

    /** Payment processed for this visit (paid or piutang): what it is billed for can't change any more. */
    public static function isLockedForVisitation($visitationId): bool
    {
        $invoice = self::where('visitation_id', $visitationId)->orderByDesc('id')->first(['id', 'payment_method', 'amount_paid']);
        if (!$invoice) {
            return false;
        }

        return !empty($invoice->payment_method)
            || (float) $invoice->amount_paid > 0
            || \App\Models\ERM\KartuStok::where('ref_type', 'invoice_penjualan')->where('ref_id', $invoice->id)->exists();
    }

    /** @throws \App\Exceptions\InvoiceLockedException */
    public static function assertNotLocked($visitationId, ?string $message = null): void
    {
        if (self::isLockedForVisitation($visitationId)) {
            throw $message ? new \App\Exceptions\InvoiceLockedException($message) : new \App\Exceptions\InvoiceLockedException();
        }
    }

    public function paymentMethodLabel(): string
    {
        if (empty($this->payment_method)) {
            return 'BELUM DIBAYAR';
        }
        if ($this->payment_method === 'non_cash') {
            return 'Non Tunai'; // older invoices
        }

        return self::PAYMENT_METHODS[$this->payment_method] ?? 'Campuran';
    }

    /**
     * Fully settled: paid up to the total, or a zero total (100% discount, free voucher) that was processed.
     * Piutang / asuransi settled through a piutang record are checked by the callers.
     */
    public function isLunas(): bool
    {
        if ($this->status === 'canceled') {
            return false;
        }

        return (float) ($this->total_amount ?? 0) > 0
            ? (float) ($this->amount_paid ?? 0) + 0.005 >= $this->billAfterRetur()
            : $this->status === 'paid';
    }

    /** What the patient has to pay in the end: approved returs leave the invoice total as sold. */
    public function billAfterRetur(): float
    {
        return max(0.0, (float) ($this->total_amount ?? 0) - (float) ($this->retur_amount ?? 0));
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id');
    }

    public function visitation()
    {
        return $this->belongsTo(Visitation::class);
    }

    /**
     * User (kasir) who last saved / processed the invoice payment.
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function piutangs()
    {
        return $this->hasMany(Piutang::class, 'invoice_id');
    }

    /**
     * Approved returs only (pending/rejected returs have no effect on the invoice yet).
     */
    public function returPembelians()
    {
        return $this->hasMany(ReturPembelian::class, 'invoice_id')
            ->where('finance_retur_pembelian.status', ReturPembelian::STATUS_APPROVED);
    }

    public function returPembelianItems()
    {
        return $this->hasManyThrough(
            ReturPembelianItem::class,
            ReturPembelian::class,
            'invoice_id',
            'retur_pembelian_id',
            'id',
            'id'
        )->where('finance_retur_pembelian.status', ReturPembelian::STATUS_APPROVED);
    }

    // Generate a unique invoice number
    public static function generateInvoiceNumber()
    {
        // Add hour, minute, second to prefix for uniqueness
        $prefix = 'INV-' . date('Ymd-His');
        // Check if invoice with this prefix already exists
        $exists = self::where('invoice_number', $prefix)->exists();
        if (!$exists) {
            return $prefix;
        }
        // If exists, append a random 3-digit number
        return $prefix . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    }
}
