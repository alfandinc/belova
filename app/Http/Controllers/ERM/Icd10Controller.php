<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\Icd10;

class Icd10Controller extends Controller
{
    public function search(Request $request)
    {
        $query = $request->input('q');

        $results = Icd10::where('code', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->limit(20)
            ->get(['code', 'description']);

        return response()->json($results);
    }
}
