<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Models\ERM\ResepDetail;
use Illuminate\Http\Request;
use App\Models\ErmResepDetail;

class ResepCatatanController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'visitation_id' => 'required|string',
            'catatan_dokter' => 'required|string',
        ]);

        $resepDetail = ResepDetail::updateOrCreate(
            ['visitation_id' => $request->visitation_id],
            [
                'catatan_dokter' => $request->catatan_dokter,
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Catatan dokter berhasil disimpan.',
            'data' => $resepDetail
        ]);
    }
}
