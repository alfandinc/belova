<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;

/** An Instagram professional account connected for reading post insights (token stored encrypted). */
class SocialAccount extends Model
{
    protected $table = 'marketing_social_accounts';

    protected $fillable = [
        'platform',
        'external_id',
        'username',
        'brand',
        'access_token',
        'token_expires_at',
        'token_refreshed_at',
        'last_synced_at',
        'last_error',
        'is_active',
        'created_by',
    ];

    protected $hidden = ['access_token'];

    protected $casts = [
        'access_token' => 'encrypted',
        'token_expires_at' => 'datetime',
        'token_refreshed_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'is_active' => 'boolean',
    ];
}
