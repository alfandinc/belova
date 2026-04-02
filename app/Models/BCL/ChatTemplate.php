<?php

namespace App\Models\BCL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'bcl_chat_templates';

    protected $fillable = [
        'name',
        'context',
        'content',
    ];
}