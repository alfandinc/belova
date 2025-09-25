<?php

namespace App\Http\Controllers\BCL;
use App\Http\Controllers\Controller;
use App\Models\BCL\tb_testimoni;
use Illuminate\Http\Request;

class tb_testimoniController extends Controller
{
    public function index()
    {
        $testimoni = tb_testimoni::all();
        return view('testimoni.index')->with('testimoni', $testimoni);
    }
}
