<?php

namespace App\Models\BCL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExtraBedAsset extends Model
{
    use HasFactory;

    protected $table = 'bcl_extra_bed_assets';

    protected $fillable = [
        'asset_code',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function assignments()
    {
        return $this->hasMany(ExtraBedAssignment::class, 'extra_bed_asset_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailableBetween($query, string $startDate, string $endDate)
    {
        return $query
            ->active()
            ->whereDoesntHave('assignments.extraRent', function ($assignmentQuery) use ($startDate, $endDate) {
                $assignmentQuery
                    ->whereDate('tgl_mulai', '<', $endDate)
                    ->whereDate('tgl_selesai', '>', $startDate);
            });
    }

    public static function ensureDefaultAssets(): void
    {
        if (static::query()->exists()) {
            return;
        }

        $rows = collect(range(1, 3))->map(function ($number) {
            return [
                'asset_code' => 'EB-' . str_pad((string) $number, 2, '0', STR_PAD_LEFT),
                'notes' => 'Auto-generated default extra bed asset',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->all();

        static::query()->insert($rows);
    }
}