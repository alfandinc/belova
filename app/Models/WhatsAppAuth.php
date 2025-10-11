<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppAuth extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_auth';
    
    protected $fillable = [
        'key_name',
        'data'
    ];

    protected $casts = [
        'data' => 'array'
    ];

    /**
     * Store auth data
     */
    public static function storeAuth($keyName, $data)
    {
        return static::updateOrCreate(
            ['key_name' => $keyName],
            ['data' => $data]
        );
    }

    /**
     * Get auth data
     */
    public static function getAuth($keyName)
    {
        $record = static::where('key_name', $keyName)->first();
        return $record ? $record->data : null;
    }

    /**
     * Delete auth data
     */
    public static function deleteAuth($keyName)
    {
        return static::where('key_name', $keyName)->delete();
    }

    /**
     * Clear all auth data
     */
    public static function clearAll()
    {
        return static::truncate();
    }
}