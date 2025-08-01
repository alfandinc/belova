<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\Konsultasi;

class KonsultasiController extends Controller
{
    public function search(Request $request)
    {
        $q = $request->input('q');
        $query = Konsultasi::query();
        if ($q) {
            $query->where('nama', 'like', "%$q%");
        }
        $results = $query->limit(20)->get(['id', 'nama', 'harga']);
        return response()->json($results);
    }
}
