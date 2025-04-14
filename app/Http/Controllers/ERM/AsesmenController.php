<?php

// app/Http/Controllers/ERM/AsesmenController.php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ERM\Visitation;
use App\Models\ERM\AsesmenPerawat;
use App\Models\ERM\PenyakitDalam;
use App\Models\ERM\Diagnosa;

class AsesmenController extends Controller
{
    public function create($visitationId)
    {
        $visitation = Visitation::findOrFail($visitationId);
        return view('erm.asesmendokter.create', compact('visitation'));
    }

    public function store(Request $request)
    {
        DB::transaction(function () use ($request) {
            AsesmenPerawat::create([
                'visitation_id' => $request->visitation_id,
                'keluhan_utama' => $request->keluhan_utama,
                'alergi' => $request->alergi,
            ]);

            PenyakitDalam::create([
                'visitation_id' => $request->visitation_id,
                'tekanan_darah' => $request->tekanan_darah,
                'suhu' => $request->suhu,
                'berat_badan' => $request->berat_badan,
                'tinggi_badan' => $request->tinggi_badan,
            ]);

            Diagnosa::create([
                'visitation_id' => $request->visitation_id,
                'diagnosa' => $request->diagnosa,
                'tindakan' => $request->tindakan,
            ]);
        });

        return redirect()->route('erm.rawatjalans.index')->with('success', 'Asesmen berhasil disimpan!');
    }
}
