<?php


namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class BirthdayGreeting extends Model
{
    use HasFactory;

    protected $table = 'erm_birthday_greetings';
    
    protected $fillable = [
        'pasien_id',
        'greeting_date',
        'greeting_year',
        'greeting_by',
        'greeting_message',
    ];

    protected $casts = [
        'greeting_date' => 'date',
    ];

    public function pasien()
    {
        return $this->belongsTo(Pasien::class, 'pasien_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'greeting_by');
    }
}