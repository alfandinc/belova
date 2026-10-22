<?php

namespace App\Models\Marketing;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** The numbers of a published post on one day (snapshot taken by "Update insight"). */
class ContentInsight extends Model
{
    protected $table = 'marketing_content_insights';

    public const METRICS = ['views', 'reach', 'likes', 'comments', 'saves', 'shares'];

    protected $fillable = [
        'content_plan_id',
        'platform',
        'social_account_id',
        'media_id',
        'tanggal',
        'views',
        'reach',
        'likes',
        'comments',
        'saves',
        'shares',
        'user_id',
        'fetched_at',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'fetched_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
