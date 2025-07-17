<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Akreditasi\Bab;
use App\Models\Akreditasi\Standar;
use App\Models\Akreditasi\Ep;
use App\Models\Akreditasi\Document;
use Illuminate\Support\Facades\Storage;

class AkreditasiController extends Controller
{
    // BAB CRUD
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return datatables()->of(Bab::query())
                ->addColumn('action', function($row) {
                    return '<button class="btn btn-sm btn-warning edit-btn">Edit</button> '
                        . '<button class="btn btn-sm btn-danger delete-btn">Delete</button>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('akreditasi.index');
    }
    public function storeBab(Request $request)
    {
        $bab = Bab::create($request->only('name'));
        return response()->json(['success' => true, 'data' => $bab]);
    }
    public function updateBab(Request $request, Bab $bab)
    {
        $bab->update($request->only('name'));
        return response()->json(['success' => true, 'data' => $bab]);
    }
    public function destroyBab(Bab $bab)
    {
        $bab->delete();
        return response()->json(['success' => true]);
    }

    // Standar CRUD
    public function standars(Request $request, Bab $bab)
    {
        if ($request->ajax()) {
            return datatables()->of($bab->standars())
                ->addColumn('action', function($row) {
                    return '<button class="btn btn-sm btn-warning edit-btn">Edit</button> '
                        . '<button class="btn btn-sm btn-danger delete-btn">Delete</button>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('akreditasi.standar', compact('bab'));
    }
    public function storeStandar(Request $request, Bab $bab)
    {
        $standar = $bab->standars()->create($request->only('name'));
        return response()->json(['success' => true, 'data' => $standar]);
    }
    public function updateStandar(Request $request, Standar $standar)
    {
        $standar->update($request->only('name'));
        return response()->json(['success' => true, 'data' => $standar]);
    }
    public function destroyStandar(Standar $standar)
    {
        $standar->delete();
        return response()->json(['success' => true]);
    }

    // EP CRUD
    public function eps(Request $request, Standar $standar)
    {
        if ($request->ajax()) {
            return datatables()->of($standar->eps())
                ->addColumn('elemen_penilaian', function($row) {
                    return $row->elemen_penilaian ?? '';
                })
                ->addColumn('action', function($row) {
                    return '<button class="btn btn-sm btn-warning edit-btn">Edit</button> '
                        . '<button class="btn btn-sm btn-danger delete-btn">Delete</button>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('akreditasi.ep', compact('standar'));
    }
    public function storeEp(Request $request, Standar $standar)
    {
        $ep = $standar->eps()->create($request->only(['name', 'elemen_penilaian', 'kelengkapan_bukti', 'skor_maksimal']));
        return response()->json(['success' => true, 'data' => $ep]);
    }
    public function updateEp(Request $request, Ep $ep)
    {
        $ep->update($request->only(['name', 'elemen_penilaian', 'kelengkapan_bukti', 'skor_maksimal']));
        return response()->json(['success' => true, 'data' => $ep]);
    }
    public function destroyEp(Ep $ep)
    {
        $ep->delete();
        return response()->json(['success' => true]);
    }

    // EP Detail & Document CRUD
    public function showEp(Request $request, Ep $ep)
    {
        if ($request->ajax()) {
            return datatables()->of($ep->documents())
                ->addColumn('preview', function($row) {
                    $ext = strtolower(pathinfo($row->filename, PATHINFO_EXTENSION));
                    $url = asset('storage/' . $row->filepath);
                    if (in_array($ext, ['jpg','jpeg','png','gif','bmp','webp'])) {
                        return '<img src="' . $url . '" alt="preview" style="max-width:80px;max-height:80px">';
                    } elseif (in_array($ext, ['mp4','webm','ogg','mov','avi','mkv'])) {
                        return '<video src="' . $url . '" controls style="max-width:120px;max-height:80px"></video>';
                    } elseif ($ext === 'pdf') {
                        return '<a href="' . $url . '" target="_blank">PDF</a>';
                    } else {
                        return '<a href="' . $url . '" target="_blank">Download</a>';
                    }
                })
                ->addColumn('created_at', function($row) {
                    return $row->created_at ? $row->created_at->format('Y-m-d H:i:s') : '';
                })
                ->addColumn('updated_at', function($row) {
                    return $row->updated_at ? $row->updated_at->format('Y-m-d H:i:s') : '';
                })
                ->addColumn('action', function($row) {
                    return '<a href="' . asset('storage/' . $row->filepath) . '" target="_blank" class="btn btn-sm btn-info">View</a> '
                        . '<button class="btn btn-sm btn-danger delete-btn">Delete</button>';
                })
                ->rawColumns(['action','preview'])
                ->make(true);
        }
        return view('akreditasi.ep_detail', compact('ep'));
    }
    public function uploadDocument(Request $request, Ep $ep)
    {
        $request->validate(['document' => 'required|file']);
        $file = $request->file('document');
        $customFilename = $request->input('custom_filename');
        $filename = $customFilename ? $customFilename . '.' . $file->getClientOriginalExtension() : $file->getClientOriginalName();
        $path = $file->storeAs('akreditasi', $filename, 'public');
        $doc = $ep->documents()->create([
            'filename' => $filename,
            'filepath' => $path,
        ]);
        return response()->json(['success' => true, 'data' => $doc]);
    }
    public function destroyDocument(Document $document)
    {
        Storage::disk('public')->delete($document->filepath);
        $document->delete();
        return response()->json(['success' => true]);
    }
}
