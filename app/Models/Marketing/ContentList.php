<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContentList extends Model
{
    use HasFactory;

    protected $table = 'marketing_content_lists';

    protected $fillable = [
        'judul',
        'brand',
        'platform',
        'assigned_to',
        'jenis_konten',
        'konten_pilar',
        'link_referensi',
        'gambar_referensi',
        'catatan',
        'approval_status',
        'approval_notes',
        'approved_by',
        'approved_at',
        'scheduled_plan_id',
    ];

    protected $casts = [
        'platform' => 'array',
        'jenis_konten' => 'array',
        'brand' => 'array',
        'approved_at' => 'datetime',
    ];

    public function assignedTo()
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_to');
    }

    public function approvedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function scheduledPlan()
    {
        return $this->belongsTo(ContentPlan::class, 'scheduled_plan_id');
    }
}