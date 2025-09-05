<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class GudangMapping extends Model
{
    protected $table = 'erm_gudang_mapping';

    protected $fillable = [
        'transaction_type',
        'gudang_id',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relationship to Gudang
     */
    public function gudang()
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }

    /**
     * Get active mapping for specific transaction type
     *
     * @param string $transactionType
     * @return GudangMapping|null
     */
    public static function getActiveMapping($transactionType)
    {
        return self::where('transaction_type', $transactionType)
                   ->where('is_active', true)
                   ->with('gudang')
                   ->first();
    }

    /**
     * Set active mapping for transaction type (deactivate others first)
     *
     * @param string $transactionType
     * @param int $gudangId
     * @return GudangMapping
     */
    public static function setActiveMapping($transactionType, $gudangId)
    {
        // Deactivate existing mappings for this transaction type
        self::where('transaction_type', $transactionType)->update(['is_active' => false]);

        // Create or update the active mapping
        return self::updateOrCreate(
            [
                'transaction_type' => $transactionType,
                'gudang_id' => $gudangId
            ],
            [
                'is_active' => true
            ]
        );
    }

    /**
     * Get default gudang ID for transaction type
     *
     * @param string $transactionType
     * @return int|null
     */
    public static function getDefaultGudangId($transactionType)
    {
        $mapping = self::getActiveMapping($transactionType);
        return $mapping ? $mapping->gudang_id : null;
    }

    /**
     * Available transaction types
     *
     * @return array
     */
    public static function getTransactionTypes()
    {
        return [
            'resep' => 'Resep Farmasi',
            'tindakan' => 'Tindakan Medis',
        ];
    }
}
