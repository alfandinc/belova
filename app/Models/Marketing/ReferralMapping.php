<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;

/** One Marketing & Sales rule: a whole referral type (referral_key '') or one detail of it. */
class ReferralMapping extends Model
{
    protected $table = 'marketing_referral_mappings';

    protected $fillable = ['referral_type', 'referral_key', 'is_marketing', 'updated_by'];

    protected $casts = ['is_marketing' => 'boolean'];
}
