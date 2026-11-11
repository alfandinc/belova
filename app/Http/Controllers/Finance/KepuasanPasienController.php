<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\ERM\Klinik;
use App\Models\Finance\KepuasanPasien;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

/** Report of the satisfaction surveys filled on the customer display. */
class KepuasanPasienController extends Controller
{
    public function index()
    {
        $kliniks = Klinik::orderBy('nama')->get(['id', 'nama']);

        return view('finance.kepuasan-pasien.index', compact('kliniks'));
    }

    public function summary(Request $request)
    {
        $base = $this->filtered($request, false);

        $total = (clone $base)->count();
        $avg = $total ? round((float) (clone $base)->avg('rating'), 2) : null;
        $dist = (clone $base)->selectRaw('rating, COUNT(*) as n')->groupBy('rating')->pluck('n', 'rating');
        $puas = (int) ($dist[4] ?? 0) + (int) ($dist[5] ?? 0);

        return response()->json([
            'total' => $total,
            'avg' => $avg,
            'puas_pct' => $total ? round($puas / $total * 100, 1) : null,
            'keluhan' => (clone $base)->whereNotNull('keluhan')->count(),
            'distribusi' => collect(range(1, 5))->mapWithKeys(fn ($r) => [$r => (int) ($dist[$r] ?? 0)]),
        ]);
    }

    public function data(Request $request)
    {
        $query = $this->filtered($request, true)
            ->with(['pasien:id,nama', 'klinik:id,nama', 'dokter.user:id,name', 'kasir:id,name', 'invoice:id,invoice_number']);

        return DataTables::eloquent($query)
            ->addColumn('tanggal', fn ($r) => $r->created_at?->format('d/m/Y H:i'))
            ->addColumn('pasien', fn ($r) => trim(($r->pasien_id ? $r->pasien_id . ' - ' : '') . (optional($r->pasien)->nama ?? '')))
            ->addColumn('klinik', fn ($r) => optional($r->klinik)->nama)
            ->addColumn('dokter', fn ($r) => optional(optional($r->dokter)->user)->name)
            ->addColumn('kasir', fn ($r) => optional($r->kasir)->name)
            ->addColumn('invoice', fn ($r) => optional($r->invoice)->invoice_number)
            ->addColumn('rating_label', fn ($r) => (KepuasanPasien::EMOJI[$r->rating] ?? '') . ' ' . (KepuasanPasien::LABELS[$r->rating] ?? $r->rating))
            ->orderColumn('tanggal', 'created_at $1')
            ->filterColumn('pasien', function ($q, $keyword) {
                $q->where(function ($w) use ($keyword) {
                    $w->where('pasien_id', 'like', "%{$keyword}%")
                        ->orWhereHas('pasien', fn ($p) => $p->where('nama', 'like', "%{$keyword}%"));
                });
            })
            ->toJson();
    }

    private function filtered(Request $request, bool $withRating)
    {
        $from = $this->date($request->input('from')) ?? now()->startOfMonth();
        $to = $this->date($request->input('to')) ?? now();

        $q = KepuasanPasien::query()
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);

        if ($request->filled('klinik_id')) {
            $q->where('klinik_id', (int) $request->input('klinik_id'));
        }
        if ($withRating && $request->filled('rating')) {
            $q->where('rating', (int) $request->input('rating'));
        }
        if ($withRating && $request->boolean('hanya_keluhan')) {
            $q->whereNotNull('keluhan');
        }

        return $q;
    }

    private function date($value): ?Carbon
    {
        try {
            return $value ? Carbon::parse($value) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
