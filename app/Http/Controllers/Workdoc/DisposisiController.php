<?php

namespace App\Http\Controllers\Workdoc;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Workdoc\Disposisi;
use App\Models\HRD\Division;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class DisposisiController extends Controller
{
    public function divisions()
    {
        $divisions = Division::orderBy('name')->get(['id','name']);
        return response()->json(['data' => $divisions]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'memorandum_id' => ['required','exists:workdoc_memorandums,id'],
            'tanggal_terima' => ['required','date'],
            'disposisi_pimpinan' => ['nullable','array'],
            'disposisi_pimpinan.*' => ['string','max:100'],
            'tujuan_disposisi' => ['nullable','array'],
            'tujuan_disposisi.*' => ['integer','exists:hrd_division,id'],
            'catatan' => ['nullable','string'],
        ]);

        $disposisi = Disposisi::create([
            'memorandum_id' => $validated['memorandum_id'],
            'tanggal_terima' => $validated['tanggal_terima'],
            'disposisi_pimpinan' => $validated['disposisi_pimpinan'] ?? [],
            'tujuan_disposisi' => $validated['tujuan_disposisi'] ?? [],
            'catatan' => $validated['catatan'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Disposisi dibuat',
            'data' => $disposisi,
        ]);
    }

    public function update(Request $request, Disposisi $disposisi)
    {
        $validated = $request->validate([
            'tanggal_terima' => ['required','date'],
            'disposisi_pimpinan' => ['nullable','array'],
            'disposisi_pimpinan.*' => ['string','max:100'],
            'tujuan_disposisi' => ['nullable','array'],
            'tujuan_disposisi.*' => ['integer','exists:hrd_division,id'],
            'catatan' => ['nullable','string'],
        ]);

        $disposisi->update([
            'tanggal_terima' => $validated['tanggal_terima'],
            'disposisi_pimpinan' => $validated['disposisi_pimpinan'] ?? [],
            'tujuan_disposisi' => $validated['tujuan_disposisi'] ?? [],
            'catatan' => $validated['catatan'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Disposisi diperbarui',
            'data' => $disposisi->refresh(),
        ]);
    }

    public function latestForMemorandum(int $memorandumId)
    {
        $latest = Disposisi::where('memorandum_id', $memorandumId)
            ->orderByDesc('id')
            ->first();
        if (!$latest) {
            return response()->json(['success' => true, 'data' => null]);
        }
        return response()->json(['success' => true, 'data' => $latest]);
    }

    public function printPdf(Disposisi $disposisi)
    {
        $disposisi->load(['memorandum.klinik','memorandum.division']);

        $clinicName = optional($disposisi->memorandum->klinik)->nama ?? '';
        $headerPath = public_path('img/belova-header.png');
        if (stripos($clinicName, 'Premiere') !== false) {
            $headerPath = public_path('img/premiere-header.png');
        } else {
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

        $ids = collect($disposisi->tujuan_disposisi ?? [])->filter()->unique()->values()->map(fn($v) => (int)$v)->all();
        $allDivisions = Division::orderBy('name')->get(['id','name']);

        $pdf = Pdf::setOptions([
                'isRemoteEnabled' => false,
                'dpi' => 96,
                'defaultFont' => 'DejaVu Sans',
            ])
            ->loadView('workdoc.disposisi.pdf', [
                'disposisi' => $disposisi,
                'allDivisions' => $allDivisions,
                'selectedDivisionIds' => $ids,
                'headerBase64' => $headerBase64,
            ])
            ->setPaper('A4', 'portrait');

        $filename = 'Disposisi-'.$disposisi->id.'-'.optional($disposisi->tanggal_terima)->format('Ymd').'.pdf';
        return $pdf->stream($filename);
    }
}
