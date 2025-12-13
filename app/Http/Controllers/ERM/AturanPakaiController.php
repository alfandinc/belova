<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\AturanPakai;
use Yajra\DataTables\Facades\DataTables;

class AturanPakaiController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = AturanPakai::orderBy('id', 'desc');
            return DataTables::of($query)
                ->addColumn('is_active', function ($it) {
                    return $it->is_active ? 'Aktif' : 'Non Aktif';
                })
                ->addColumn('created_at', function ($it) {
                    return $it->created_at ? $it->created_at->format('Y-m-d H:i') : '';
                })
                ->addColumn('aksi', function ($it) {
                    return '<button class="btn btn-sm btn-info" onclick="editAturan(' . $it->id . ')">Edit</button> <button class="btn btn-sm btn-danger" onclick="deleteAturan(' . $it->id . ')">Hapus</button>';
                })
                ->rawColumns(['aksi'])
                ->make(true);
        }

        return view('erm.aturan-pakai.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'template' => 'required|string',
        ]);
        $data = $request->only(['template']);
        $data['is_active'] = $request->has('is_active') ? (bool)$request->is_active : true;
        $m = AturanPakai::create($data);
        return response()->json(['success' => true, 'id' => $m->id]);
    }

    public function show($id)
    {
        $m = AturanPakai::findOrFail($id);
        return response()->json($m);
    }

    public function update(Request $request, $id)
    {
        $request->validate(['template' => 'required|string']);
        $m = AturanPakai::findOrFail($id);
        $m->template = $request->template;
        $m->is_active = $request->has('is_active') ? (bool)$request->is_active : false;
        $m->save();
        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        $m = AturanPakai::findOrFail($id);
        $m->delete();
        return response()->json(['success' => true]);
    }

    // Public API used by resep pages to fetch active templates
    public function listActive(Request $request)
    {
        $q = (string)$request->get('q', '');
        $q = trim($q);

        // Require at least 2 characters for server-side search to avoid returning everything
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $items = AturanPakai::where('is_active', true)
            ->where('template', 'like', "%{$q}%")
            ->orderBy('id','desc')
            ->limit(50)
            ->get(['id','template']);

        return response()->json($items);
    }
}
