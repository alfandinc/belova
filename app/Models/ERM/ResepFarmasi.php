<?php

namespace App\Models\ERM;

use App\Models\Finance\Billing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
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

    /** The obat even when it was deactivated after the resep was written (stock must still be cut at billing). */
    public function obatAny()
    {
        return $this->belongsTo(Obat::class, 'obat_id')->withoutGlobalScope('active');
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

    /** What decides the price or the stock of a resep, as opposed to labels such as aturan pakai or the paket link */
    private const CHARGE_FIELDS = ['visitation_id', 'obat_id', 'jumlah', 'jumlah_racikan', 'dosis', 'bungkus', 'racikan_ke', 'wadah_id', 'harga', 'diskon', 'total'];

    /**
     * Keep billing in sync when resep is updated or deleted. The invoice itself is not touched: while unpaid the
     * billing page shows it needs "Update Invoice" (which applies discount and tax); once payment is processed the
     * resep can't change at all. A retur goes around this on purpose (saveQuietly / deleteQuietly).
     */
    protected static function booted()
    {
        // A racikan's isi changed, so its paket name must be looked up again
        static::saved(fn () => self::$paketNameCache = []);
        static::deleted(fn () => self::$paketNameCache = []);

        static::saving(function (ResepFarmasi $resep) {
            if ($resep->isDirty(self::CHARGE_FIELDS)) {
                Invoice::assertNotLocked($resep->visitation_id, 'Resep tidak dapat diubah karena pembayaran sudah diproses (lunas / piutang). Gunakan retur.');
            }
        });
        static::deleting(function (ResepFarmasi $resep) {
            Invoice::assertNotLocked($resep->visitation_id, 'Resep tidak dapat dihapus karena pembayaran sudah diproses (lunas / piutang). Gunakan retur.');
        });

        static::updated(function (ResepFarmasi $resep) {
            // Only labels can still change here (saving above): the bill stays as it was paid
            if (Invoice::isLockedForVisitation($resep->visitation_id)) {
                return;
            }

            try {
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
            } catch (\App\Exceptions\InvoiceLockedException $e) {
                throw $e;
            } catch (\Exception $e) {
                Log::warning('Error syncing billing on resep update: ' . $e->getMessage());
            }
        });

        static::deleted(function (ResepFarmasi $resep) {
            try {
                if (method_exists($resep, 'isForceDeleting') && !$resep->isForceDeleting()) {
                    return;
                }

                Billing::where('billable_type', ResepFarmasi::class)
                       ->where('billable_id', $resep->id)
                       ->delete();
            } catch (\App\Exceptions\InvoiceLockedException $e) {
                throw $e;
            } catch (\Exception $e) {
                Log::warning('Error syncing billing on resep delete: ' . $e->getMessage());
            }
        });
    }
}
