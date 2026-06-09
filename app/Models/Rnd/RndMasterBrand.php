<?php

namespace App\Models\Rnd;

class RndMasterBrand extends BaseRndMaster
{
    protected $table = 'rnd_master_brand';

    protected $fillable = [
        'nama_brand',
    ];

    public static function label(): string
    {
        return 'Master Brand';
    }

    public static function fields(): array
    {
        return [
            [
                'name' => 'nama_brand',
                'label' => 'Nama Brand',
                'type' => 'text',
                'required' => true,
            ],
        ];
    }
}