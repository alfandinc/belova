<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\LabConfig;
use App\Models\ERM\Dokter;

class LabConfigController extends Controller
{
    public function getConfig()
    {
        $config = LabConfig::first();
        return response()->json(['data' => $config ? ['id' => $config->id, 'dokter_id' => $config->dokter_id, 'dokter' => $config->dokter ? $config->dokter->user->name : null] : null]);
    }

    public function listDokters()
    {
        $dokters = Dokter::with('user','spesialisasi')
            ->whereHas('spesialisasi', function($q){ $q->where('nama','Laboratorium'); })
            ->get()
            ->map(function($d){
                return ['id' => $d->id, 'name' => $d->user ? $d->user->name : ($d->nama ?? 'Dokter '.$d->id)];
            });

        return response()->json(['data' => $dokters]);
    }

    public function save(Request $request)
    {
        $request->validate(['dokter_id' => 'nullable|exists:erm_dokters,id']);

        // ensure selected dokter has spesialisasi Laboratorium
        if($request->dokter_id){
            $dok = Dokter::with('spesialisasi')->find($request->dokter_id);
            if(!$dok || !$dok->spesialisasi || strtolower(trim($dok->spesialisasi->nama)) !== 'laboratorium'){
                return response()->json(['message' => 'Selected dokter is not in Laboratorium spesialisasi'], 422);
            }
        }

        $config = LabConfig::first();
        if(!$config){
            $config = LabConfig::create(['dokter_id' => $request->dokter_id]);
        } else {
            $config->dokter_id = $request->dokter_id;
            $config->save();
        }

        return response()->json(['message' => 'Saved', 'data' => $config]);
    }
}
