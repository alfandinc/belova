<?php

namespace App\Models\Rnd;

class RndMasterBrand extends BaseRndMaster
{
    protected $table = 'rnd_master_brand';

    protected $fillable = [
        'nama_brand',
        'no_hki',
        'hki_berlaku_sampai',
    ];

    protected $casts = [
        'hki_berlaku_sampai' => 'date:Y-m-d',
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
            [
                'name' => 'no_hki',
                'label' => 'No. Pendaftaran Merek (HKI)',
                'type' => 'text',
                'required' => false,
            ],
            [
                'name' => 'hki_berlaku_sampai',
                'label' => 'Merek Berlaku s/d',
                'type' => 'date',
                'required' => false,
            ],
        ];
    }
}
