<?php

namespace App\Models\Workdoc;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kemitraan extends Model
{
    use HasFactory;

    protected $table = 'workdoc_kemitraans';

    protected $fillable = [
        'partner_name',
        'category',
        'perihal',
        'start_date',
        'end_date',
        'status',
        'notes',
        'dokumen_pks',
    ];
}
