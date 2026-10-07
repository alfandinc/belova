<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Models\ERM\Pasien;
use App\Services\ERM\PasienMergeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Pasien ganda: list probable duplicates and merge them (Admin only).
 */
class PasienMergeController extends Controller
{
    public function __construct(private PasienMergeService $service)
    {
    }

    private function isAdmin(): bool
    {
        return (bool) optional(Auth::user())->hasAnyRole(['Admin']);
    }

    public function duplicates()
    {
        if (!$this->isAdmin()) {
            return response()->json(['message' => 'Hanya Admin yang dapat mengelola pasien ganda.'], 403);
        }

        $groups = $this->service->findDuplicateGroups();

        return response()->json([
            'data' => $groups,
            'group_count' => $groups->count(),
            'patient_count' => $groups->sum(fn ($g) => $g['patients']->count()),
        ]);
    }

    public function merge(Request $request)
    {
        if (!$this->isAdmin()) {
            return response()->json(['message' => 'Hanya Admin yang dapat menggabungkan pasien.'], 403);
        }
        $validated = $request->validate([
            'target_id' => 'required|exists:erm_pasiens,id',
            'source_ids' => 'required|array|min:1',
            'source_ids.*' => 'required|distinct|exists:erm_pasiens,id|different:target_id',
        ], [
            'target_id.required' => 'Pilih pasien utama.',
            'target_id.exists' => 'Pasien utama tidak ditemukan.',
            'source_ids.required' => 'Pilih pasien yang akan digabung.',
            'source_ids.*.exists' => 'Pasien yang akan digabung tidak ditemukan (mungkin sudah digabung).',
            'source_ids.*.different' => 'Pasien utama tidak bisa digabung ke dirinya sendiri.',
        ]);

        $target = Pasien::findOrFail($validated['target_id']);
        $sources = Pasien::whereIn('id', $validated['source_ids'])->get();

        try {
            $result = $this->service->merge($target, $sources);
        } catch (\RuntimeException $e) {
            // Rolled back: nothing was changed
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => count($result['merged']) . ' pasien (RM ' . implode(', ', $result['merged']) . ') digabung ke "'
                . $target->nama . '" (RM ' . $target->id . '), ' . $result['visits'] . ' kunjungan dipindahkan.',
            'target_id' => (string) $target->id,
        ]);
    }
}
