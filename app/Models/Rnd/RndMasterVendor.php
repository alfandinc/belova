<?php

namespace App\Models\Rnd;

class RndMasterVendor extends BaseRndMaster
{
    protected $table = 'rnd_master_vendor';

    protected $casts = [
        'tipe_vendor' => 'array',
    ];

    protected $fillable = [
        'nama_vendor',
        'tipe_vendor',
        'no_hp',
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
                'name' => 'notes',
                'label' => 'Notes',
                'type' => 'textarea',
                'required' => false,
            ],
        ];
    }
}