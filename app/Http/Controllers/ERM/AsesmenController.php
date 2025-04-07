<?php

// app/Http/Controllers/ERM/AsesmenController.php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\Visitation;

class AsesmenController extends Controller
{
    public function create($id)
    {
        $visitation = Visitation::with('pasien')->findOrFail($id);

        return view('erm.asesmen.create', compact('visitation'));
    }
}
