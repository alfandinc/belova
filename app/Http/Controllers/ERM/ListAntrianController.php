<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ERM\Pasien;
use App\Models\ERM\Visitation;

class ListAntrianController extends Controller
{
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
