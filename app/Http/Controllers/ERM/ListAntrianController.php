<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ERM\Pasien;
use App\Models\ERM\Visitation;

class ListAntrianController extends Controller
{
    // public function index()
    // {
    //     $events = [
    //         [
    //             'title' => 'Antrian A1',
    //             'start' => '2025-05-03',
    //             // 'names' => ['Budi', 'Sari']
    //         ],
    //         [
    //             'title' => 'Antrian A3',
    //             'start' => '2025-05-05',
    //             // 'names' => ['Andi']
    //         ],
    //         [
    //             'title' => 'Antrian A4',
    //             'start' => '2025-05-05',
    //             // 'names' => ['Andi']
    //         ],
    //         [
    //             'title' => 'Antrian A5',
    //             'start' => '2025-05-05',
    //             // 'names' => ['Andi']
    //         ],
    //         [
    //             'title' => 'Antrian A6',
    //             'start' => '2025-05-05',
    //             // 'names' => ['Andi']
    //         ],
    //         [
    //             'title' => 'Antrian A7',
    //             'start' => '2025-05-05',
    //             // 'names' => ['Andi']
    //         ],
    //         [
    //             'title' => 'Antrian A8',
    //             'start' => '2025-05-05',
    //             // 'names' => ['Andi']
    //         ],
    //         [
    //             'title' => 'Antrian A9',
    //             'start' => '2025-05-05',
    //             // 'names' => ['Andi']
    //         ]
    //     ];

    //     return view('erm.listantrian.index', compact('events'));
    // }
    // public function index()
    // {
    //     // Example grouped data (normally from DB)
    //     $grouped = [
    //         '2025-05-03' => ['Antrian A1'],
    //         '2025-05-05' => ['Antrian A3', 'Antrian A4', 'Antrian A5', 'Antrian A6', 'Antrian A7', 'Antrian A8', 'Antrian A9'],
    //     ];

    //     $events = [];

    //     foreach ($grouped as $date => $list) {
    //         $events[] = [
    //             'title' => count($list) . ' Antrian',
    //             'start' => $date,
    //             'extendedProps' => [
    //                 'antrian_list' => $list
    //             ]
    //         ];
    //     }

    //     return view('erm.listantrian.index', compact('events'));
    // }

    public function index()
    {
        // Ambil semua visitation beserta pasien yang terkait
        $visitation = Visitation::with('pasien')->get();

        // Kelompokkan data berdasarkan tanggal visitation
        $grouped = $visitation->groupBy('tanggal_visitation');

        $events = [];



        // Loop untuk setiap grup tanggal
        foreach ($grouped as $date => $list) {
            $events[] = [
                'title' => count($list) . ' Antrian', // Menghitung jumlah antrian
                'start' => $date,
                'extendedProps' => [
                    // 'antrian_list' => $list->pluck('title'), // Menampilkan judul antrian
                    'antrian_list' => $list->pluck('pasien.nama') // Menampilkan nama pasien yang berkunjung
                ]
            ];
        }
        // dd($events);

        return view('erm.listantrian.index', compact('events'));
    }
}
