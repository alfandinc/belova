<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;

/** Numbers of a connected Instagram account on one day (followers, new followers, unfollows, reach). */
class SocialAccountStat extends Model
{
    protected $table = 'marketing_social_account_stats';

    protected $fillable = [
        'social_account_id',
        'tanggal',
        'followers',
        'new_followers',
        'unfollows',
        'reach',
        'demographics',
        'fetched_at',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'demographics' => 'array',
        'fetched_at' => 'datetime',
    ];
}
