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
    // Single page: BAB/Standar list on the left, EPs + documents of the selected Standar on the right
    public function index()
    {
        return $this->page(null);
    }

    public function showStandar(Standar $standar)
    {
        return $this->page($standar);
    }

    private function page(?Standar $standar)
    {
        $babs = Bab::with(['standars' => function ($q) {
            $q->orderBy('id')->withCount([
                'eps',
                'eps as eps_done_count' => function ($q) { $q->has('documents'); },
            ]);
        }])->orderBy('id')->get();

        if (!$standar) {
            $standar = $babs->pluck('standars')->flatten()->first();
        }
        if ($standar) {
            $standar->load(['bab', 'eps' => function ($q) {
                $q->orderBy('id')->with(['documents' => function ($q) { $q->orderByDesc('created_at'); }]);
            }]);
        }

        $isAdmin = auth()->user()->hasRole('Admin');

        return view('akreditasi.index', compact('babs', 'standar', 'isAdmin'));
    }

    // BAB
    public function storeBab(Request $request)
    {
        $bab = Bab::create($request->validate(['name' => 'required|string|max:255']));
        return response()->json(['success' => true, 'data' => $bab]);
    }
    public function updateBab(Request $request, Bab $bab)
    {
        $bab->update($request->validate(['name' => 'required|string|max:255']));
        return response()->json(['success' => true, 'data' => $bab]);
    }
    public function destroyBab(Bab $bab)
    {
        // Deleting cascades in the database, so refuse while it still has content
        if ($bab->standars()->exists()) {
            return response()->json(['success' => false, 'message' => 'BAB masih memiliki Standar. Hapus Standar di dalamnya terlebih dahulu.'], 422);
        }
        $bab->delete();
        return response()->json(['success' => true]);
    }

    // Standar
    public function storeStandar(Request $request, Bab $bab)
    {
        $standar = $bab->standars()->create($request->validate(['name' => 'required|string|max:255']));
        return response()->json(['success' => true, 'data' => $standar]);
    }
    public function updateStandar(Request $request, Standar $standar)
    {
        $standar->update($request->validate(['name' => 'required|string|max:255']));
        return response()->json(['success' => true, 'data' => $standar]);
    }
    public function destroyStandar(Standar $standar)
    {
        if ($standar->eps()->exists()) {
            return response()->json(['success' => false, 'message' => 'Standar masih memiliki EP. Hapus EP di dalamnya terlebih dahulu.'], 422);
        }
        $standar->delete();
        return response()->json(['success' => true]);
    }

    // EP
    private function validateEp(Request $request)
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'elemen_penilaian' => 'nullable|string|max:255',
            'kelengkapan_bukti' => 'required|string|max:255',
            'skor_maksimal' => 'required|integer|min:0',
        ]);
    }
    public function storeEp(Request $request, Standar $standar)
    {
        $ep = $standar->eps()->create($this->validateEp($request));
        return response()->json(['success' => true, 'data' => $ep]);
    }
    public function updateEp(Request $request, Ep $ep)
    {
        $ep->update($this->validateEp($request));
        return response()->json(['success' => true, 'data' => $ep]);
    }
    public function destroyEp(Ep $ep)
    {
        if ($ep->documents()->exists()) {
            return response()->json(['success' => false, 'message' => 'EP masih memiliki dokumen. Hapus dokumennya terlebih dahulu.'], 422);
        }
        $ep->delete();
        return response()->json(['success' => true]);
    }

    // Documents
    public function uploadDocument(Request $request, Ep $ep)
    {
        $request->validate([
            'document' => 'required|file',
            'custom_filename' => 'nullable|string|max:200',
        ]);
        $file = $request->file('document');
        $ext = $file->getClientOriginalExtension();
        $base = trim((string) $request->input('custom_filename'));
        $base = $base !== '' ? $base : pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $base = trim(preg_replace('/[\\\\\/:*?"<>|]+/', '-', $base)) ?: 'dokumen';

        // Never overwrite an existing file: add a counter when the name is taken
        $filename = $base . ($ext ? '.' . $ext : '');
        $i = 1;
        while (Storage::disk('public')->exists('akreditasi/' . $filename)) {
            $filename = $base . ' (' . $i++ . ')' . ($ext ? '.' . $ext : '');
        }

        $path = $file->storeAs('akreditasi', $filename, 'public');
        $doc = $ep->documents()->create([
            'filename' => $filename,
            'filepath' => $path,
        ]);
        return response()->json(['success' => true, 'data' => $doc]);
    }
    public function destroyDocument(Document $document)
    {
        // Older uploads could share one file; only remove it when no other record uses it
        $shared = Document::where('filepath', $document->filepath)->where('id', '!=', $document->id)->exists();
        if (!$shared) {
            Storage::disk('public')->delete($document->filepath);
        }
        $document->delete();
        return response()->json(['success' => true]);
    }
}
