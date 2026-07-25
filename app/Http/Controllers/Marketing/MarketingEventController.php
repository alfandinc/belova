<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\ERM\Klinik;
use App\Models\Marketing\MarketingEvent;
use App\Models\Marketing\Promo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MarketingEventController extends Controller
{
    public function index()
    {
        $kliniks = Klinik::select('id', 'nama')->orderBy('nama')->get();
        $today = now()->toDateString();
        $promos = Promo::query()
            ->where(function ($query) use ($today) {
                $query->where(function ($q) use ($today) {
                    $q->whereNotNull('start_date')
                        ->whereNotNull('end_date')
                        ->where('start_date', '<=', $today)
                        ->where('end_date', '>=', $today);
                })->orWhere(function ($q) use ($today) {
                    $q->whereNotNull('start_date')
                        ->whereNull('end_date')
                        ->where('start_date', '<=', $today);
                })->orWhere(function ($q) use ($today) {
                    $q->whereNull('start_date')
                        ->whereNotNull('end_date')
                        ->where('end_date', '>=', $today);
                });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'start_date', 'end_date']);

        return view('marketing.events.index', compact('kliniks', 'promos'));
    }

    public function data()
    {
        $rows = MarketingEvent::query()
            ->with(['klinik:id,nama', 'promos:id,name'])
            ->orderByDesc('tanggal_mulai')
            ->orderByDesc('id')
            ->get();

        return response()->json(['data' => $rows]);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request);
        $promoIds = $data['promo_ids'] ?? [];
        unset($data['promo_ids']);

        if ($request->hasFile('dokumen_proposal')) {
            $data['dokumen_proposal'] = $request->file('dokumen_proposal')->store('marketing/events/proposal', 'public');
        }

        if ($request->hasFile('dokumen_laporan')) {
            $data['dokumen_laporan'] = $request->file('dokumen_laporan')->store('marketing/events/laporan', 'public');
        }

        $event = MarketingEvent::create($data);
        $event->promos()->sync($promoIds);

        return response()->json(['success' => true, 'item' => $event->load('promos:id,name')]);
    }

    public function show($id)
    {
        $event = MarketingEvent::with('promos:id,name')->findOrFail($id);

        return response()->json(['item' => $event]);
    }

    public function update(Request $request, $id)
    {
        $data = $this->validateRequest($request);
        $event = MarketingEvent::findOrFail($id);
        $promoIds = $data['promo_ids'] ?? [];
        unset($data['promo_ids']);

        if ($request->hasFile('dokumen_proposal')) {
            if ($event->dokumen_proposal) {
                Storage::disk('public')->delete($event->dokumen_proposal);
            }
            $data['dokumen_proposal'] = $request->file('dokumen_proposal')->store('marketing/events/proposal', 'public');
        }

        if ($request->hasFile('dokumen_laporan')) {
            if ($event->dokumen_laporan) {
                Storage::disk('public')->delete($event->dokumen_laporan);
            }
            $data['dokumen_laporan'] = $request->file('dokumen_laporan')->store('marketing/events/laporan', 'public');
        }

        $event->update($data);
        $event->promos()->sync($promoIds);

        return response()->json(['success' => true, 'item' => $event->load('promos:id,name')]);
    }

    public function destroy($id)
    {
        $event = MarketingEvent::findOrFail($id);

        if ($event->dokumen_proposal) {
            Storage::disk('public')->delete($event->dokumen_proposal);
        }

        if ($event->dokumen_laporan) {
            Storage::disk('public')->delete($event->dokumen_laporan);
        }

        $event->delete();

        return response()->json(['success' => true]);
    }

    private function validateRequest(Request $request)
    {
        return $request->validate([
            'kode_event' => 'required|string|max:100',
            'nama_event' => 'required|string|max:255',
            'deskripsi_event' => 'nullable|string',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'klinik_id' => 'required|exists:erm_klinik,id',
            'lokasi' => 'nullable|string|max:255',
            'target_market' => 'nullable|string|max:255',
            'status' => 'required|in:aktif,selesai',
            'promo_ids' => 'nullable|array',
            'promo_ids.*' => 'integer|exists:marketing_promos,id',
            'dokumen_proposal' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
            'dokumen_laporan' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ]);
    }
}
