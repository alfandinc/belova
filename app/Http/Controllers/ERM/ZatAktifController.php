<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\ZatAktif;

class ZatAktifController extends Controller
{
    /**
     * Return paged Zat Aktif for DataTables (server-side).
     */
    public function index(Request $request)
    {
        $req = $request;
        $query = ZatAktif::query();

        $total = $query->count();

        // Apply search filter
        $search = $req->input('search.value');
        if ($search) {
            $query->where('nama', 'LIKE', "%{$search}%");
        }

        $recordsFiltered = $query->count();

        // Ordering
        $orderColIndex = $req->input('order.0.column');
        $orderDir = $req->input('order.0.dir', 'asc');
        $columns = ['id','nama'];
        if (is_numeric($orderColIndex) && isset($columns[$orderColIndex])) {
            $query->orderBy($columns[$orderColIndex], $orderDir);
        } else {
            $query->orderBy('nama', 'asc');
        }

        // Paging
        $start = (int) $req->input('start', 0);
        $length = (int) $req->input('length', 10);
        $items = $query->skip($start)->take($length)->get(['id','nama']);

        return response()->json([
            'draw' => (int) $req->input('draw', 0),
            'recordsTotal' => $total,
            'recordsFiltered' => $recordsFiltered,
            'data' => $items,
        ]);
    }

    /**
     * Store a new Zat Aktif (AJAX).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => 'required|string|unique:erm_zataktif,nama',
        ]);

        $zat = ZatAktif::create(['nama' => $data['nama']]);

        return response()->json(['success' => true, 'message' => 'Zat Aktif ditambahkan', 'data' => $zat]);
    }
}
