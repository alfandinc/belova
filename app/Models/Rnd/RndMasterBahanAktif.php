<?php

namespace App\Models\Rnd;

class RndMasterBahanAktif extends BaseRndMaster
{
    protected $table = 'rnd_master_bahan_aktif';

    protected $fillable = [
        'nama_bahan_aktif',
    ];

    public static function label(): string
    {
        return 'Master Bahan Aktif';
    }

    public static function fields(): array
    {
        return [
            [
                'name' => 'nama_bahan_aktif',
                'label' => 'Nama Bahan Aktif',
                'type' => 'text',
                'required' => true,
            ],
        ];
    }

    public function products()
    {
        return $this->belongsToMany(
            RndProduk::class,
            'rnd_produk_bahan_aktif',
            'bahan_aktif_id',
            'produk_id'
        );
    }
}