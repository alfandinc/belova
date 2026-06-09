<?php

namespace App\Models\Rnd;

class RndMasterSediaan extends BaseRndMaster
{
    protected $table = 'rnd_master_sediaan';

    protected $fillable = [
        'nama_sediaan',
    ];

    public static function label(): string
    {
        return 'Master Sediaan';
    }

    public static function fields(): array
    {
        return [
            [
                'name' => 'nama_sediaan',
                'label' => 'Nama Sediaan',
                'type' => 'text',
                'required' => true,
            ],
        ];
    }
}