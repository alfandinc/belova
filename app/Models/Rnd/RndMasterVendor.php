<?php

namespace App\Models\Rnd;

class RndMasterVendor extends BaseRndMaster
{
    protected $table = 'rnd_master_vendor';

    protected $casts = [
        'tipe_vendor' => 'array',
        'cpkb_berlaku_sampai' => 'date:Y-m-d',
    ];

    protected $fillable = [
        'nama_vendor',
        'tipe_vendor',
        'no_hp',
        'no_cpkb',
        'cpkb_berlaku_sampai',
        'notes',
    ];

    public static function label(): string
    {
        return 'Master Vendor';
    }

    public static function fields(): array
    {
        return [
            [
                'name' => 'nama_vendor',
                'label' => 'Nama Vendor',
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'tipe_vendor',
                'label' => 'Tipe Vendor',
                'type' => 'multiselect',
                'required' => true,
                'options' => ['produsen', 'kemasan', 'desain'],
            ],
            [
                'name' => 'no_hp',
                'label' => 'No. HP',
                'type' => 'text',
                'required' => false,
            ],
            [
                'name' => 'no_cpkb',
                'label' => 'No. Sertifikat CPKB',
                'type' => 'text',
                'required' => false,
            ],
            [
                'name' => 'cpkb_berlaku_sampai',
                'label' => 'CPKB Berlaku s/d',
                'type' => 'date',
                'required' => false,
            ],
            [
                'name' => 'notes',
                'label' => 'Notes',
                'type' => 'textarea',
                'required' => false,
            ],
        ];
    }
}