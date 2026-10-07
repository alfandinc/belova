<?php

namespace App\Models\ERM;

use App\Models\Finance\Billing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Finance\InvoiceItem;
use App\Models\Finance\Invoice;
use Illuminate\Support\Facades\Log;

class ResepFarmasi extends Model
{
    use SoftDeletes;

    protected $table = 'erm_resepfarmasi';
    public $incrementing = false; // non auto-increment
    protected $keyType = 'string'; // jika ID-nya string (bukan integer)

    protected $fillable = [
        'id',
        'visitation_id',
        'obat_id',
        'jumlah',
        'jumlah_racikan',
        'dosis',
        'bungkus',
        'racikan_ke',
        'paket_racikan_id',
        'paket_racikan_nama',
        'aturan_pakai',
        'wadah_id',

        'harga',
        'diskon',
        'total',

        'dokter_id',
        'created_at',
        'user_id'
    ];

    protected $casts = [
        'jumlah_racikan' => 'float',
    ];

    // Relasi ke Obat
    public function obat()
    {
        return $this->belongsTo(Obat::class, 'obat_id');
    }

    public function wadah()
    {
        return $this->belongsTo(WadahObat::class, 'wadah_id');
    }

    // Relasi ke Visitation
    public function visitation()
    {
        return $this->belongsTo(Visitation::class, 'visitation_id');
    }

    public function billing()
    {
        return $this->morphOne(Billing::class, 'billable');
    }

    // Relasi ke ResepDetail (untuk mendapatkan no_resep)
    public function resepDetail()
    {
        return $this->belongsTo(ResepDetail::class, 'visitation_id', 'visitation_id');
    }

    /**
     * Append paket_racikan_name to model JSON when available. First the paket stored on the row when the
     * racikan was made from it (paket_racikan_nama, name at that moment); otherwise the paket whose isi
     * (obat + dosis) is exactly the whole racikan. Paket isi is unique (PaketRacikan::assertUniqueComposition).
     */
    protected $appends = ['paket_racikan_name'];

    /** visitation_id|racikan_ke => paket name (or null), so every row of a racikan costs one lookup */
    protected static array $paketNameCache = [];

    /**
     * Harga of one racikan component per bungkus: harga jual (harga_nonfornas) prorated by
     * dosis racik / dosis obat (full harga when either dosis is unknown).
     */
    public static function hargaRacikan(?Obat $obat, $dosis): float
    {
        if (!$obat) {
            return 0.0;
        }
        $harga = (float) ($obat->harga_nonfornas ?? 0);
        $dosisRacik = (float) PaketRacikanDetail::normalizeDosis($dosis);
        $dosisObat = (float) PaketRacikanDetail::normalizeDosis($obat->dosis);

        return ($dosisRacik > 0 && $dosisObat > 0) ? $harga * $dosisRacik / $dosisObat : $harga;
    }

    public static function forgetPaketNameCache(): void
    {
        self::$paketNameCache = [];
    }

    public function paketRacikan()
    {
        return $this->belongsTo(PaketRacikan::class, 'paket_racikan_id');
    }

    public function getPaketRacikanNameAttribute()
    {
        if (!$this->racikan_ke || !$this->visitation_id) {
            return null;
        }
        if ($this->paket_racikan_nama) {
            return $this->paket_racikan_nama;
        }
        $cacheKey = $this->visitation_id . '|' . $this->racikan_ke;
        if (array_key_exists($cacheKey, self::$paketNameCache)) {
            return self::$paketNameCache[$cacheKey];
        }

        $name = null;
        try {
            $items = self::query()->toBase()
                ->where('visitation_id', $this->visitation_id)
                ->where('racikan_ke', $this->racikan_ke)
                ->get(['obat_id', 'dosis'])
                ->map(fn ($r) => ['obat_id' => $r->obat_id, 'dosis' => $r->dosis])
                ->all();
            if (!empty($items)) {
                $name = PaketRacikan::findSameComposition($items)->nama_paket ?? null;
            }
        } catch (\Exception $e) {
            Log::warning('Error finding paket racikan for resep: ' . $e->getMessage());
        }

        return self::$paketNameCache[$cacheKey] = $name;
    }

    /**
     * Keep billing/invoice in sync when resep is updated or deleted
     */
    protected static function booted()
    {
        // A racikan's isi changed, so its paket name must be looked up again
        static::saved(fn () => self::$paketNameCache = []);
        static::deleted(fn () => self::$paketNameCache = []);

        // On update: sync billing and invoice item values
        static::updated(function (ResepFarmasi $resep) {
            try {
                // Update billing record if exists
                $billing = Billing::where('billable_type', ResepFarmasi::class)
                                  ->where('billable_id', $resep->id)
                                  ->first();
                if ($billing) {
                    $billing->jumlah = $resep->harga ?? $billing->jumlah;
                    $billing->qty = $resep->racikan_ke ? ($resep->bungkus ?? $billing->qty) : ($resep->jumlah ?? $billing->qty);
                    $billing->keterangan = 'Obat: ' . (($resep->obat->nama ?? null) ?: 'Tanpa Nama') .
                        ($resep->racikan_ke ? ' (Racikan #' . $resep->racikan_ke . ')' : '');
                    $billing->save();
                }

                // Update any invoice items referencing this resep
                $items = InvoiceItem::where('billable_type', ResepFarmasi::class)
                                    ->where('billable_id', $resep->id)
                                    ->get();
                foreach ($items as $item) {
                    $item->unit_price = $resep->harga ?? $item->unit_price;
                    $item->quantity = $resep->racikan_ke ? ($resep->bungkus ?? $item->quantity) : ($resep->jumlah ?? $item->quantity);
                    $item->description = 'Obat: ' . (($resep->obat->nama ?? null) ?: 'Tanpa Nama') .
                        ($resep->racikan_ke ? ' (Racikan #' . $resep->racikan_ke . ')' : '');
                    // recompute final_amount conservatively using existing discount fields
                    $unit = (float) $item->unit_price;
                    $discount = (float) ($item->discount ?? 0);
                    if (!empty($discount) && $item->discount_type === '%') {
                        $unitAfter = $unit - ($unit * ($discount / 100));
                    } else {
                        $unitAfter = $unit - $discount;
                    }
                    $unitAfter = max(0, $unitAfter);
                    $item->final_amount = $unitAfter * (float) $item->quantity;
                    $item->save();

                    // Recalculate parent invoice totals
                    if ($item->invoice_id) {
                        try {
                            $inv = Invoice::find($item->invoice_id);
                            if ($inv) {
                                $subtotal = (float) $inv->items()->sum('final_amount');
                                $inv->subtotal = $subtotal;
                                // Keep other fields intact; set total_amount to subtotal unless tax/discount exist
                                $inv->total_amount = $subtotal;
                                $inv->save();
                            }
                        } catch (\Exception $e) {
                            Log::warning('Failed to recalc invoice after resep update: ' . $e->getMessage());
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Error syncing billing/invoice on resep update: ' . $e->getMessage());
            }
        });

        // On delete: remove related billing and invoice items, then recalc invoices
        static::deleted(function (ResepFarmasi $resep) {
            try {
                if (method_exists($resep, 'isForceDeleting') && !$resep->isForceDeleting()) {
                    return;
                }

                // Delete billing(s)
                Billing::where('billable_type', ResepFarmasi::class)
                       ->where('billable_id', $resep->id)
                       ->delete();

                // Find invoice items referencing this resep
                $items = InvoiceItem::where('billable_type', ResepFarmasi::class)
                                    ->where('billable_id', $resep->id)
                                    ->get();
                $affectedInvoiceIds = $items->pluck('invoice_id')->unique()->filter()->all();

                // Delete the items
                foreach ($items as $it) {
                    $it->delete();
                }

                // Recalculate invoices and if they became empty, optionally delete them
                foreach ($affectedInvoiceIds as $invId) {
                    try {
                        $inv = Invoice::find($invId);
                        if (!$inv) continue;
                        $subtotal = (float) $inv->items()->sum('final_amount');
                        if ($subtotal <= 0) {
                            // If invoice has no positive subtotal, delete it
                            $inv->delete();
                        } else {
                            $inv->subtotal = $subtotal;
                            $inv->total_amount = $subtotal;
                            $inv->save();
                        }
                    } catch (\Exception $e) {
                        Log::warning('Failed to recalc/delete invoice after resep delete: ' . $e->getMessage());
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Error syncing billing/invoice on resep delete: ' . $e->getMessage());
            }
        });
    }
}
