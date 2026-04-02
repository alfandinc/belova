<?php

namespace App\Models\BCL;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tb_extra_rent extends Model
{
    use HasFactory;
    protected $table = 'bcl_extra_rent';
    protected $guarded = ['id'];
    protected $appends = ['assigned_asset_codes', 'current_room_name'];

    public function jurnal()
    {
        return $this->hasMany(Fin_jurnal::class, 'doc_id', 'kode')
            ->where('identity', 'Tambahan Sewa')
            ->where('pos', 'K');
    }

    public function parentTransaction()
    {
        return $this->belongsTo(tr_renter::class, 'parent_trans', 'trans_id');
    }

    public function assetAssignments()
    {
        return $this->hasMany(ExtraBedAssignment::class, 'extra_rent_id')->with('asset');
    }

    public function getAssignedAssetCodesAttribute(): array
    {
        $assignments = $this->relationLoaded('assetAssignments')
            ? $this->assetAssignments
            : $this->assetAssignments()->get();

        return $assignments
            ->map(function ($assignment) {
                return optional($assignment->asset)->asset_code;
            })
            ->filter()
            ->values()
            ->all();
    }

    public function getCurrentRoomNameAttribute(): ?string
    {
        $parentTransaction = $this->relationLoaded('parentTransaction')
            ? $this->parentTransaction
            : $this->parentTransaction()->with('room', 'renter')->first();

        if (!$parentTransaction) {
            return null;
        }

        $activeTransaction = tr_renter::with('room')
            ->where('id_renter', $parentTransaction->id_renter)
            ->whereDate('tgl_mulai', '<=', Carbon::today()->format('Y-m-d'))
            ->whereDate('tgl_selesai', '>', Carbon::today()->format('Y-m-d'))
            ->orderByDesc('tgl_mulai')
            ->first();

        return optional(optional($activeTransaction)->room ?: $parentTransaction->room)->room_name;
    }
}
