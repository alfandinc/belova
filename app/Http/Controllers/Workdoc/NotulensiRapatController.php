<?php
namespace App\Http\Controllers\Workdoc;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Workdoc\NotulensiRapat;

class NotulensiRapatController extends Controller
{
    // Display index view (AJAX DataTable)
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return datatables()->of(NotulensiRapat::query())
                ->addColumn('action', function ($row) {
                    $url = route('workdoc.notulensi-rapat.show', $row->id);
                    return '<a href="' . $url . '" class="btn btn-sm btn-primary">View</a>';
                })
                ->make(true);
        }
        return view('workdoc.notulensi_rapat.index');
    }


    // Show notulensi rapat detail (reuse create view for display)
    public function show($id)
    {
        $notulensi = NotulensiRapat::findOrFail($id);
        return view('workdoc.notulensi_rapat.create', compact('notulensi'));
    }

    // Show create form
    public function create()
    {
        return view('workdoc.notulensi_rapat.create');
    }

    // Store new notulensi rapat
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'notulen' => 'required|string',
        ]);
        NotulensiRapat::create($request->only(['title', 'date', 'notulen']));
        return response()->json(['success' => true]);
    }
}
