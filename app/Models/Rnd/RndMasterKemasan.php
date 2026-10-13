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

    /** Same name is fine in another size or tipe (e.g. Botol 30 ml and Botol 100 ml). */
    public static function duplicateColumns(): array
    {
        return ['nama_kemasan', 'ukuran', 'tipe_kemasan'];
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