<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\ERM\Gudang;

class GudangMapping extends Model
{
    use HasFactory;

    protected $table = 'erm_gudang_mapping';

    protected $fillable = [
        'transaction_type',
        'gudang_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the gudang that owns the mapping
     */
    public function gudang()
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }

    /**
     * Get active mappings
     */
    public static function getActiveMappings()
    {
        return self::with('gudang')
            ->where('is_active', true)
            ->get()
            ->keyBy('transaction_type');
    }

    /**
     * Set active mapping for transaction type
     */
    public static function setActiveMapping($transactionType, $gudangId)
    {
        // Deactivate all mappings for this transaction type
        self::where('transaction_type', $transactionType)
            ->update(['is_active' => false]);

        // Find existing mapping or create new one
        $mapping = self::firstOrNew([
            'transaction_type' => $transactionType,
            'gudang_id' => $gudangId,
        ]);

        $mapping->is_active = true;
        $mapping->save();

        return $mapping;
    }

    /**
     * Get default gudang ID for transaction type
     */
    public static function getDefaultGudangId($transactionType)
    {
        $mapping = self::where('transaction_type', $transactionType)
            ->where('is_active', true)
            ->first();

        return $mapping ? $mapping->gudang_id : null;
    }

    /**
     * Get default gudang for transaction type
     */
    public static function getDefaultGudang($transactionType)
    {
        $gudangId = self::getDefaultGudangId($transactionType);
        
        if ($gudangId) {
            return Gudang::find($gudangId);
        }

        return null;
    }
}
