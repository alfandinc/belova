<?php

namespace App\Models\Rnd;

class RndMasterKemasan extends BaseRndMaster
{
    protected $table = 'rnd_master_kemasan';

    protected $fillable = [
        'nama_kemasan',
        'ukuran',
        'tipe_kemasan',
    ];

    public static function label(): string
    {
        return 'Master Kemasan';
    }

    public static function fields(): array
    {
        return [
            [
                'name' => 'nama_kemasan',
                'label' => 'Nama Kemasan',
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'ukuran',
                'label' => 'Ukuran',
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'tipe_kemasan',
                'label' => 'Tipe Kemasan',
                'type' => 'select',
                'required' => true,
                'options' => ['primer', 'sekunder'],
            ],
        ];
    }
}