<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AturanPakaiController extends Controller
{
    /**
     * Aturan pakai recommendations for the free-text field in the resep form, taken from resep history
     * (no master needed). Returns two lists, most used first:
     *  - obat  : used before for this obat (obat_id) or, with racikan=1, in racikan by this user
     *  - umum  : used in any resep
     * q filters both (case and spacing insensitive: "2x" matches "2 X"). Texts used only once are left out.
     */
    public function suggest(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $racikan = $request->boolean('racikan');

        $grouped = function ($query) use ($q, $racikan) {
            $query->select('aturan_pakai', DB::raw('COUNT(*) as c'))
                ->whereNotNull('aturan_pakai')
                ->where('aturan_pakai', '!=', '');
            $racikan ? $query->whereNotNull('racikan_ke') : $query->whereNull('racikan_ke');
            if ($q !== '') {
                $noSpace = str_replace(' ', '', $q);
                $query->where(function ($w) use ($q, $noSpace) {
                    $w->where('aturan_pakai', 'LIKE', "%{$q}%")
                        ->orWhereRaw("REPLACE(aturan_pakai, ' ', '') LIKE ?", ["%{$noSpace}%"]);
                });
            }

            return $query->groupBy('aturan_pakai')->orderByDesc('c')->limit(80)->get();
        };

        // Case/spacing variants of the same text are counted together; the most used spelling is shown
        $merge = function ($rows, $limit, array $exclude = []) {
            return collect($rows)
                ->map(fn ($r) => ['text' => trim(preg_replace('/\s+/', ' ', (string) $r->aturan_pakai)), 'c' => (int) $r->c])
                ->filter(fn ($r) => mb_strlen($r['text']) >= 3)
                ->groupBy(fn ($r) => mb_strtolower(preg_replace('/\s*,\s*/', ', ', $r['text'])))
                ->reject(fn ($g, $key) => in_array($key, $exclude, true))
                ->map(fn ($g) => ['text' => $g->sortByDesc('c')->first()['text'], 'count' => $g->sum('c')])
                ->filter(fn ($r) => $r['count'] >= 2)
                ->sortByDesc('count')
                ->take($limit)
                ->values();
        };
        $keyOf = fn ($text) => mb_strtolower(preg_replace('/\s*,\s*/', ', ', $text));

        // Both resep dokter and resep farmasi, so dokter and farmasi pages get each other's history
        $tables = ['erm_resepdokter', 'erm_resepfarmasi'];

        // Specific: this obat, or this user's racikan
        $specificRows = collect();
        if ($racikan) {
            foreach ($tables as $table) {
                $specificRows = $specificRows->merge($grouped(DB::table($table)->where('user_id', Auth::id())));
            }
        } elseif ($request->filled('obat_id')) {
            foreach ($tables as $table) {
                $specificRows = $specificRows->merge($grouped(DB::table($table)->where('obat_id', (int) $request->obat_id)));
            }
        }
        $specific = $merge($specificRows, 5);

        // General: every resep (only searched when typing, or when there is nothing specific)
        $general = collect();
        if ($q !== '' || $specific->isEmpty()) {
            $generalRows = collect();
            foreach ($tables as $table) {
                $generalRows = $generalRows->merge($grouped(DB::table($table)));
            }
            $exclude = $specific->map(fn ($r) => $keyOf($r['text']))->all();
            $general = $merge($generalRows, 8, $exclude);
        }

        return response()->json(['obat' => $specific, 'umum' => $general]);
    }
}
