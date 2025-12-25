<?php

namespace App\Models\ERM;

use App\Models\Finance\Billing;
use Illuminate\Database\Eloquent\Model;
use App\Models\Finance\InvoiceItem;
use App\Models\Finance\Invoice;
use Illuminate\Support\Facades\Log;

class ResepFarmasi extends Model
{
    protected $table = 'erm_resepfarmasi';
    public $incrementing = false; // non auto-increment
    protected $keyType = 'string'; // jika ID-nya string (bukan integer)

    protected $fillable = [
        'id',
        'visitation_id',
        'obat_id',
        'jumlah',
        'dosis',
        'bungkus',
        'racikan_ke',
        'aturan_pakai',
        'wadah_id',

        'harga',
        'diskon',
        'total',

        'dokter_id',
        'created_at',
        'user_id'
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
     * Keep billing/invoice in sync when resep is updated or deleted
     */
    protected static function booted()
    {
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
