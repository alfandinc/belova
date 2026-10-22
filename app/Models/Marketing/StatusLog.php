<?php

namespace App\Models\Marketing;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * One workflow action on a marketing record (approval, status change, comment, ...).
 * Shown as the "Riwayat" timeline; rows are never updated.
 */
class StatusLog extends Model
{
    protected $table = 'marketing_status_logs';

    protected $fillable = [
        'loggable_type',
        'loggable_id',
        'action',
        'from_status',
        'to_status',
        'notes',
        'user_id',
    ];

    public function loggable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Shape used by every "Riwayat" timeline on the front-end. */
    public function toTimeline(array $statusLabels = []): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'from_status' => $this->from_status,
            'to_status' => $this->to_status,
            'from_label' => $this->from_status ? ($statusLabels[$this->from_status] ?? $this->from_status) : null,
            'to_label' => $this->to_status ? ($statusLabels[$this->to_status] ?? $this->to_status) : null,
            'notes' => $this->notes,
            'user' => $this->user ? $this->user->name : 'Sistem',
            'at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}
