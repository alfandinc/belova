<?php

namespace App\Models\BCL;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class renter extends Model
{
    use HasFactory;
    protected $fillable = [
        'nama',
        'alamat',
        'phone',
        'phone2',
        'identitas',
        'no_identitas',
        'kendaraan',
        'nopol',
        'birthday',
        'deposit_balance',
    ];
    protected $table = 'bcl_renter';
    protected $primaryKey = 'id';
    protected $casts = [
        'deposit_balance' => 'decimal:2',
    ];

    public function tr_renter()
    {
        return $this->hasMany(tr_renter::class, 'id_renter');
    }

    /**
     * Credit renter deposit balance and persist change.
     * Returns new balance.
     */
    public function creditDeposit(float $amount)
    {
        $this->deposit_balance = (float)$this->deposit_balance + round($amount, 2);
        $this->save();
        return $this->deposit_balance;
    }

    /**
     * Debit renter deposit balance (if available). Throws exception on insufficient funds.
     */
    public function debitDeposit(float $amount)
    {
        $amount = round($amount, 2);
        if ((float)$this->deposit_balance < $amount) {
            throw new \Exception('Insufficient deposit balance');
        }
        $this->deposit_balance = (float)$this->deposit_balance - $amount;
        $this->save();
        return $this->deposit_balance;
    }
    public function document()
    {
        return $this->hasMany(renter_document::class, 'id_renter');
    }

    public function current_room()
    {
        // join against actual rooms table name
        return $this->hasOne(tr_renter::class, 'id_renter')
            ->leftjoin('bcl_rooms', 'bcl_tr_renter.room_id', '=', 'bcl_rooms.id')
            ->where('bcl_tr_renter.tgl_mulai', '<=', Carbon::now())
            ->where('bcl_tr_renter.tgl_selesai', '>=', Carbon::now());
    }


}
