<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class GudangMapping extends Model
{
    protected $table = 'erm_gudang_mapping';

    protected $fillable = [
        'transaction_type',
        'gudang_id',
        'is_active',
        'entity_type',
        'entity_id'
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
                   ->whereNull('entity_type')
                   ->whereNull('entity_id')
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
        // Backwards-compatible wrapper: delegate to new signature without entity
        return self::setActiveMappingForEntity($transactionType, $gudangId, null, null);
    }

    /**
     * Resolve gudang mapping for a transaction type with optional entity override.
     * Tries to find an active mapping for the given entity (e.g. spesialisasi) first,
     * then falls back to the transaction_type-only default mapping.
     *
     * @param string $transactionType
     * @param string|null $entityType
     * @param int|null $entityId
     * @return GudangMapping|null
     */
    public static function resolveGudangForTransaction($transactionType, $entityType = null, $entityId = null)
    {
        if ($entityType && $entityId) {
            $m = self::where('transaction_type', $transactionType)
                ->where('entity_type', $entityType)
                ->where('entity_id', $entityId)
                ->where('is_active', true)
                ->with('gudang')
                ->first();

            if ($m) return $m;
        }

        // fallback to default mapping (no entity)
        return self::getActiveMapping($transactionType);
    }

    /**
     * Set active mapping for transaction type and optional entity (deactivate others first)
     *
     * @param string $transactionType
     * @param int $gudangId
     * @param string|null $entityType
     * @param int|null $entityId
     * @return GudangMapping
     */
    public static function setActiveMappingForEntity($transactionType, $gudangId, $entityType = null, $entityId = null)
    {
        // Deactivate existing mappings for this transaction type & same entity scope
        $query = self::where('transaction_type', $transactionType);
        if ($entityType && $entityId) {
            $query->where('entity_type', $entityType)->where('entity_id', $entityId);
        } else {
            $query->whereNull('entity_type')->whereNull('entity_id');
        }

        $query->update(['is_active' => false]);

        // Create or update the active mapping within the same entity scope
        return self::updateOrCreate(
            [
                'transaction_type' => $transactionType,
                'gudang_id' => $gudangId,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
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
            // 'tindakan' => 'Tindakan Medis',
            'kode_tindakan' => 'Kode Tindakan Obat',
            'retur_pembelian' => 'Retur Pembelian',
        ];
    }
}
