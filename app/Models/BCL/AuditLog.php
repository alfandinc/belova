<?php

namespace App\Models\BCL;

use Illuminate\Database\Eloquent\Model;

/** Who corrected, cancelled or deleted what in Kos BCL. */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'bcl_audit_logs';
    protected $guarded = ['id'];
    protected $casts = ['data' => 'array'];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
