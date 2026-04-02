<?php

namespace App\Models\BCL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class extra_pricelist extends Model
{
    use HasFactory;
    protected $table = 'bcl_extra_pricelist';
    protected $guarded = ['id'];

    public function requiresExtraBedTracking(): bool
    {
        return preg_match('/extra\s*bed/i', (string) $this->nama) === 1;
    }
}
