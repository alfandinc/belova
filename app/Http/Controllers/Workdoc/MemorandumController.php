<?php

namespace App\Http\Controllers\Workdoc;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Workdoc\Memorandum;
use App\Models\HRD\Division;
use App\Models\ERM\Klinik;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class MemorandumController extends Controller
{
    public function index()
    {
        $divisions = Division::orderBy('name')->get(['id','name']);
        $clinics = Klinik::orderBy('nama')->get(['id','nama']);
        return view('workdoc.memorandum.index', compact('divisions', 'clinics'));
    }

    public function create()
    {
        $divisions = Division::orderBy('name')->get(['id','name']);
        $clinics = Klinik::orderBy('nama')->get(['id','nama']);
        $defaultDivisionId = optional(Auth::user()->employee)->division_id;
        return view('workdoc.memorandum.create', compact('divisions', 'clinics', 'defaultDivisionId'));
    }

    public function edit(Memorandum $memorandum)
    {
        $divisions = Division::orderBy('name')->get(['id','name']);
        $clinics = Klinik::orderBy('nama')->get(['id','nama']);
        $defaultDivisionId = optional(Auth::user()->employee)->division_id;
        return view('workdoc.memorandum.create', compact('divisions', 'clinics', 'memorandum', 'defaultDivisionId'));
    }

    public function data(Request $request)
    {
        $query = Memorandum::with(['division','klinik','user'])->orderByDesc('tanggal');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('klinik_id')) {
            $query->where('klinik_id', $request->klinik_id);
        }
        if ($request->filled('start_date') && $request->filled('end_date')) {
            try {
                $start = \Carbon\Carbon::parse($request->start_date)->startOfDay();
                $end = \Carbon\Carbon::parse($request->end_date)->endOfDay();
                $query->whereBetween('tanggal', [$start, $end]);
            } catch (\Throwable $e) {
                // Ignore invalid date input, fallback to unfiltered
            }
        }

        $self = $this;
        $rows = $query->get()->map(function ($m) use ($self) {
            return [
                'id' => $m->id,
            'tanggal' => optional($m->tanggal)->translatedFormat('j F Y'),
                'nomor_memo' => $m->nomor_memo,
                'perihal' => $m->perihal,
                'division' => optional($m->division)->name,
                'kepada' => $m->kepada,
                'klinik' => optional($m->klinik)->nama,
                'klinik_short' => $self->clinicShortName(optional($m->klinik)->nama),
                'status' => $m->status,
                'user' => optional($m->user)->name,
                'user_id' => $m->user_id,
                'dokumen_path' => $m->dokumen_path,
            ];
        });

        return response()->json(['data' => $rows]);
    }

    public function uploadDokumen(Request $request, Memorandum $memorandum)
    {
        $request->validate([
            'dokumen' => ['required','file','mimes:pdf,jpeg,jpg,png,webp','max:10240'],
        ]);

        $file = $request->file('dokumen');
        $original = $file->getClientOriginalName();
        $safeName = time().'_'.preg_replace('/[^A-Za-z0-9_.-]/', '_', $original);
        $path = $file->storeAs('workdoc/memorandums/'.$memorandum->id, $safeName, 'public');

        $memorandum->dokumen_path = $path;
        $memorandum->save();

        return response()->json([
            'success' => true,
            'message' => 'Dokumen pendukung diunggah',
            'url' => route('workdoc.memorandum.dokumen.view', ['memorandum' => $memorandum->id]),
            'path' => $path,
        ]);
    }

    public function viewDokumen(Memorandum $memorandum)
    {
        $path = $memorandum->dokumen_path;
        if (!$path) {
            abort(404);
        }
        $disk = Storage::disk('public');
        if (!$disk->exists($path)) {
            abort(404);
        }
        $absolute = storage_path('app/public/'.$path);
        $mime = $disk->mimeType($path) ?? 'application/octet-stream';
        return response()->file($absolute, [
            'Content-Type' => $mime,
        ]);
    }

    private function romanMonth(int $month): string
    {
        $map = [1=>'I',2=>'II',3=>'III',4=>'IV',5=>'V',6=>'VI',7=>'VII',8=>'VIII',9=>'IX',10=>'X',11=>'XI',12=>'XII'];
        return $map[$month] ?? '';
    }

    private function clinicCode(?int $klinikId): string
    {
        if (!$klinikId) { return 'KP-BL'; }
        $klinik = Klinik::find($klinikId);
        if (!$klinik) { return 'KP-BL'; }
        return (stripos($klinik->nama, 'Premiere') !== false) ? 'KU-PB' : 'KP-BL';
    }

    private function nextSequence(?int $klinikId, int $year): int
    {
        $last = Memorandum::query()
            ->when($klinikId, fn($q) => $q->where('klinik_id', $klinikId))
            ->whereYear('tanggal', $year)
            ->whereNotNull('nomor_memo')
            ->orderByDesc('id')
            ->value('nomor_memo');

        if ($last && preg_match('/MEMO-(\d{3})/i', $last, $m)) {
            return ((int)$m[1]) + 1;
        }
        return 1;
    }

    private function generateNomorMemoFrom(string $tanggal, ?int $klinikId): string
    {
        $dt = \Carbon\Carbon::parse($tanggal);
        $year = (int)$dt->format('Y');
        $month = (int)$dt->format('n');
        $seq = $this->nextSequence($klinikId, $year);
        $code = $this->clinicCode($klinikId);
        $roman = $this->romanMonth($month);
        return 'MEMO-'.str_pad((string)$seq, 3, '0', STR_PAD_LEFT).'/'.$code.'/'.$roman.'/'.$year;
    }

    private function clinicShortName(?string $name): string
    {
        $n = strtolower($name ?? '');
        if ($n === '') return '';
        if (strpos($n, 'premiere') !== false) return 'Premiere';
        if (strpos($n, 'skin') !== false) return 'Belovaskin';
        return 'Belova';
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => ['required','date'],
            'nomor_memo' => ['nullable','string','max:100'],
            'perihal' => ['required','string','max:255'],
            'dari_division_id' => ['nullable','exists:hrd_division,id'],
            'kepada' => ['nullable','string','max:255'],
            'isi' => ['nullable','string'],
            'klinik_id' => ['nullable','exists:erm_klinik,id'],
            'status' => ['nullable','string','max:50'],
        ]);

        if (empty($validated['nomor_memo'])) {
            $validated['nomor_memo'] = $this->generateNomorMemoFrom($validated['tanggal'], $validated['klinik_id'] ?? null);
        }
        // Default status to 'draft' when not provided
        if (!array_key_exists('status', $validated) || ($validated['status'] ?? '') === '') {
            $validated['status'] = 'draft';
        }
        $validated['user_id'] = Auth::id();

        $memo = Memorandum::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Memorandum created',
            'data' => $memo,
        ]);
    }

    public function show(Memorandum $memorandum)
    {
        return response()->json([
            'success' => true,
            'data' => $memorandum->load(['division','klinik','user']),
        ]);
    }

    public function update(Request $request, Memorandum $memorandum)
    {
        $validated = $request->validate([
            'tanggal' => ['required','date'],
            'nomor_memo' => ['nullable','string','max:100'],
            'perihal' => ['required','string','max:255'],
            'dari_division_id' => ['nullable','exists:hrd_division,id'],
            'kepada' => ['nullable','string','max:255'],
            'isi' => ['nullable','string'],
            'klinik_id' => ['nullable','exists:erm_klinik,id'],
            'status' => ['nullable','string','max:50'],
        ]);

        if (empty($validated['nomor_memo'])) {
            $validated['nomor_memo'] = $this->generateNomorMemoFrom($validated['tanggal'], $validated['klinik_id'] ?? null);
        }

        // If status is not provided or empty during update, avoid overriding existing value
        if (!array_key_exists('status', $validated) || ($validated['status'] ?? '') === '') {
            unset($validated['status']);
        }

        $memorandum->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Memorandum updated',
            'data' => $memorandum->refresh(),
        ]);
    }

    public function destroy(Memorandum $memorandum)
    {
        $memorandum->delete();
        return response()->json(['success' => true, 'message' => 'Memorandum deleted']);
    }

    public function printPdf(Memorandum $memorandum)
    {
        $memorandum->load(['division','klinik','user']);

        // Resolve clinic header image (premiere-header or belova-header) and embed as base64
        $clinicName = $memorandum->klinik->nama ?? '';
        $headerPath = public_path('img/belova-header.png');
        if (stripos($clinicName, 'Premiere') !== false) {
            $headerPath = public_path('img/premiere-header.png');
        } else {
            // Default to Belova Skin header if clinic contains Skin; else Belova Corp header
            if (stripos($clinicName, 'Skin') !== false) {
                $headerPath = public_path('img/belova-header.png');
            } elseif (file_exists(public_path('img/header-belovacorp.png'))) {
                $headerPath = public_path('img/header-belovacorp.png');
            }
        }
        $headerBase64 = '';
        try {
            if (file_exists($headerPath)) {
                $headerBase64 = 'data:image/'.pathinfo($headerPath, PATHINFO_EXTENSION).';base64,'.base64_encode(file_get_contents($headerPath));
            }
        } catch (\Throwable $e) {
            $headerBase64 = '';
        }

        $pdf = Pdf::setOptions([
                'isRemoteEnabled' => false,
                'dpi' => 96,
                'defaultFont' => 'DejaVu Sans',
            ])
            ->loadView('workdoc.memorandum.pdf', [
                'memorandum' => $memorandum,
                'headerBase64' => $headerBase64,
            ])
            ->setPaper('A4', 'portrait');

        $filename = 'Memorandum-'.$memorandum->id.'-'.optional($memorandum->tanggal)->format('Ymd').'.pdf';
        return $pdf->stream($filename);
    }

    public function generateNumber(Request $request)
    {
        $request->validate([
            'tanggal' => ['required','date'],
            'klinik_id' => ['nullable','exists:erm_klinik,id'],
        ]);
        $nomor = $this->generateNomorMemoFrom($request->tanggal, $request->klinik_id);
        return response()->json(['nomor_memo' => $nomor]);
    }
}
