<?php

namespace App\Models\ERM;

use App\Models\Finance\Billing;
use Illuminate\Database\Eloquent\Model;

class LabPermintaan extends Model
{
    protected $table = 'erm_lab_permintaan';
    protected $fillable = ['visitation_id', 'lab_test_id', 'status', 'hasil', 'dokter_id', 'requested_at', 'processed_at', 'completed_at', 'cancelled_at'];
    protected $casts = [
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function($m) {
            // Set initial requested_at if status is requested (or default) and not manually set
            if (!$m->requested_at && ($m->status === 'requested' || !$m->status)) {
                $m->requested_at = now();
            }
        });
        static::updating(function($m) {
            if ($m->isDirty('status')) {
                $new = $m->status;
                if (($new === 'requested' || !$new) && !$m->requested_at) {
                    $m->requested_at = now();
                }
                if (in_array($new, ['processing','diproses']) && !$m->processed_at) {
                    $m->processed_at = now();
                }
                if (in_array($new, ['completed','selesai']) && !$m->completed_at) {
                    $m->completed_at = now();
                }
                if (in_array($new, ['cancelled','batal']) && !$m->cancelled_at) {
                    $m->cancelled_at = now();
                }
            }
        });
    }

    // Durations in minutes
    public function getDurationWaitingAttribute()
    {
        if ($this->requested_at && $this->processed_at) {
            return $this->processed_at->diffInMinutes($this->requested_at);
        }
        return null;
    }

    public function getDurationProcessingAttribute()
    {
        if ($this->processed_at && $this->completed_at) {
            return $this->completed_at->diffInMinutes($this->processed_at);
        }
        return null;
    }

    public function getDurationTotalAttribute()
    {
        if ($this->requested_at && $this->completed_at) {
            return $this->completed_at->diffInMinutes($this->requested_at);
        }
        return null;
    }
    
    public function visitation()
    {
        return $this->belongsTo(Visitation::class);
    }
    
    public function labTest()
    {
        return $this->belongsTo(LabTest::class);
    }
    
    public function dokter()
    {
        return $this->belongsTo(Dokter::class);
    }

    public function billings()
    {
        return $this->morphMany(Billing::class, 'billable');
    }
    
}
