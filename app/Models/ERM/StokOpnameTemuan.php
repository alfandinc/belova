<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class StokOpnameTemuan extends Model
{
    protected $table = 'erm_stok_opname_temuan';

    protected $fillable = [
        'stok_opname_id',
        'stok_opname_item_id',
        'qty',
        'jenis',
        'process_status',
        'keterangan',
        'created_by'
    ];

    public function item()
    {
        return $this->belongsTo(StokOpnameItem::class, 'stok_opname_item_id');
    }

    /**
     * SQL for the net temuan of a set of rows: minus/lebih (explains a selisih minus) = +qty,
     * plus/kurang (explains a selisih plus) = -qty. sisa belum terjelaskan = (fisik - sistem) + net.
     * $alias is the table alias prefix, e.g. 't.'.
     */
    public static function netSql(string $alias = ''): string
    {
        $a = $alias;
        return "COALESCE(SUM(CASE WHEN {$a}jenis IN ('lebih','minus') THEN {$a}qty WHEN {$a}jenis IN ('kurang','plus') THEN -{$a}qty ELSE 0 END),0)";
    }

    /**
     * Correlated subquery: net temuan of the erm_stok_opname_items row in the outer query.
     */
    public static function netSubquery(): string
    {
        return "(SELECT " . self::netSql('t.') . " FROM erm_stok_opname_temuan t WHERE t.stok_opname_item_id = erm_stok_opname_items.id)";
    }

    /**
     * Net temuan for one value of $column (stok_opname_id or stok_opname_item_id), keyed by item id.
     */
    public static function netPerItem(string $column, $values): array
    {
        return \Illuminate\Support\Facades\DB::table('erm_stok_opname_temuan')
            ->whereIn($column, (array) $values)
            ->groupBy('stok_opname_item_id')
            ->select('stok_opname_item_id', \Illuminate\Support\Facades\DB::raw(self::netSql() . ' as net'))
            ->pluck('net', 'stok_opname_item_id')
            ->all();
    }

    /**
     * Net temuan of one opname item.
     */
    public static function netForItem($itemId): float
    {
        return (float) (self::netPerItem('stok_opname_item_id', [$itemId])[$itemId] ?? 0);
    }
}
