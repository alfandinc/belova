<?php

namespace App\Support\Dashboard\Widgets;

use App\Models\ERM\GudangMapping;
use App\Models\ERM\Obat;
use App\Models\ERM\ObatStokGudang;
use App\Models\User;
use App\Services\ERM\ForecastService;
use Carbon\Carbon;

/**
 * Medicines to reorder (same ForecastService statuses as Manajemen Stok: kritis / perlu pesan / menunggu)
 * and batches that are expired or expire within 30 days (outside the expired gudang).
 */
class StokObatWidget implements Widget
{
    private const LIST_LIMIT = 6;

    private const EXPIRY_DAYS = 30;

    public function data(User $user, array $filter): array
    {
        $forecastService = app(ForecastService::class);
        $forecast = collect($forecastService->computeAll($forecastService->defaultParams()));

        $byStatus = $forecast->countBy('status');
        $rank = ['kritis' => 0, 'pesan' => 1];
        $reorder = $forecast
            ->filter(fn ($f) => isset($rank[$f['status']]))
            ->sortBy([
                fn ($a, $b) => $rank[$a['status']] <=> $rank[$b['status']],
                fn ($a, $b) => ($a['days_of_cover'] ?? 0) <=> ($b['days_of_cover'] ?? 0),
            ])
            ->take(self::LIST_LIMIT);

        $obats = Obat::whereIn('id', $reorder->keys()->all() ?: [0])->get(['id', 'nama', 'satuan', 'satuan_stok'])->keyBy('id');
        $reorderList = $reorder->map(function ($f, $obatId) use ($obats) {
            $obat = $obats->get($obatId);

            return (object) [
                'nama' => $obat->nama ?? ('Obat #' . $obatId),
                'satuan' => $obat ? ($obat->satuan_stok ?: $obat->satuan) : '',
                'stok' => (float) ($f['total_stock'] ?? 0),
                'days' => $f['days_of_cover'] !== null ? (int) floor($f['days_of_cover']) : null,
                'saran' => (float) ($f['qty_pesan'] ?? 0),
                'status' => $f['status'],
                'wajib' => !empty($f['wajib_stok']),
            ];
        })->values();

        $expiredGudangId = GudangMapping::getDefaultGudangId('expired');
        $batches = fn () => ObatStokGudang::query()
            ->where('stok', '>', 0)
            ->edTerverifikasi()
            ->when($expiredGudangId, fn ($q) => $q->where('gudang_id', '!=', $expiredGudangId));
        $today = Carbon::today();

        $expiring = $batches()
            ->with(['obat:id,nama', 'gudang:id,nama'])
            ->whereDate('expiration_date', '<=', $today->copy()->addDays(self::EXPIRY_DAYS))
            ->orderBy('expiration_date')
            ->limit(self::LIST_LIMIT)
            ->get()
            ->map(fn ($b) => (object) [
                'nama' => $b->obat->nama ?? 'Obat',
                'batch' => $b->batch,
                'gudang' => $b->gudang->nama ?? null,
                'stok' => (float) $b->stok,
                'days' => (int) $today->diffInDays(Carbon::parse($b->expiration_date)->startOfDay(), false),
                'date' => Carbon::parse($b->expiration_date),
            ]);

        return [
            'kritis' => (int) ($byStatus['kritis'] ?? 0),
            'pesan' => (int) ($byStatus['pesan'] ?? 0),
            'menunggu' => (int) ($byStatus['menunggu'] ?? 0),
            'reorder' => $reorderList->all(),
            'expired' => $batches()->whereDate('expiration_date', '<', $today)->count(),
            'expiringSoon' => $batches()->whereDate('expiration_date', '>=', $today)
                ->whereDate('expiration_date', '<=', $today->copy()->addDays(self::EXPIRY_DAYS))->count(),
            'expiring' => $expiring->all(),
            'expiryDays' => self::EXPIRY_DAYS,
        ];
    }
}
