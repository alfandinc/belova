<?php

namespace App\Services\BCL;

use App\Models\BCL\ExtraBedAsset;
use App\Models\BCL\Fin_jurnal;
use App\Models\BCL\renter;
use App\Models\BCL\tb_extra_rent;
use App\Models\BCL\tr_renter;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Shared rules of the Kos BCL module: document numbers, rental periods,
 * outstanding balances, extra bed tracking and renter deposits.
 *
 * Conventions:
 * - A rental period is [tgl_mulai, tgl_selesai): the end date is exclusive.
 * - A payment towards a document is a 4-10101 credit line whose doc_id is the
 *   transaction code (tr_renter.trans_id or tb_extra_rent.kode).
 * - The renter deposit is liability 2-99999; kredit adds to it, debet uses it.
 */
class RentalService
{
    public const ACCOUNT_CASH = '1-10101';
    public const ACCOUNT_DEPOSIT = '2-99999';
    public const ACCOUNT_REVENUE = '4-10101';
    public const ACCOUNT_REFUND = '5-10102';
    /** Security deposit (uang jaminan): returned at check-out minus damages. Kept apart from the prepaid balance (2-99999). */
    public const ACCOUNT_GUARANTEE = '2-99998';
    /** Other income: kept guarantee deductions, forfeited booking deposits. */
    public const ACCOUNT_OTHER_INCOME = '4-10102';

    /** Payment methods offered on every money-in form; the first one needs no transfer proof. */
    public const METHODS = ['Tunai', 'Transfer', 'QRIS', 'EDC'];

    protected ?Collection $unpaidCache = null;

    public static function methodNeedsProof(?string $method): bool
    {
        return $method !== null && $method !== '' && $method !== 'Tunai';
    }

    /** Store an uploaded payment proof on the public disk and return its path. */
    public function storeProof(?\Illuminate\Http\UploadedFile $file, string $prefix): ?string
    {
        if (!$file) {
            return null;
        }

        return $file->storeAs('bcl/transfer-proof', $prefix . '-' . \Illuminate\Support\Str::random(10) . '.' . $file->getClientOriginalExtension(), 'public');
    }

    public function audit(string $action, ?string $subject, string $description, array $data = []): void
    {
        \App\Models\BCL\AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject' => $subject,
            'description' => $description,
            'data' => $data,
            'created_at' => now(),
        ]);
    }

    /** Security deposit held per renter (kredit - debet on 2-99998). */
    public function guaranteeBalances(iterable $renterIds = null): Collection
    {
        return Fin_jurnal::where('kode_akun', self::ACCOUNT_GUARANTEE)
            ->when($renterIds !== null, fn ($q) => $q->whereIn('kode_subledger', collect($renterIds)->filter()->unique()->values()))
            ->groupBy('kode_subledger')
            ->selectRaw('kode_subledger, SUM(kredit) - SUM(debet) as saldo')
            ->pluck('saldo', 'kode_subledger')
            ->map(fn ($v) => (float) $v)
            ->filter(fn ($v) => abs($v) > 0.009);
    }

    public function guaranteeBalance($renterId): float
    {
        return (float) ($this->guaranteeBalances([$renterId])[$renterId] ?? 0);
    }

    /**
     * Suggested price for a custom stay [start, end) in a room category: as many month/year packages as fit
     * (largest first), the remaining days at the daily rate (1-day package, else monthly price / 30).
     *
     * @return array{total: float, days: int, parts: array<int, array{label: string, amount: float}>, daily_rate: float}
     */
    public function suggestPrice($category, $start, $end): array
    {
        $start = Carbon::parse($start)->startOfDay();
        $end = Carbon::parse($end)->startOfDay();
        $days = max((int) $start->diffInDays($end, false), 0);
        $lists = \App\Models\BCL\Pricelist::where('room_category', $category)->get();

        // Month-based packages expressed in months: "3 Bulan" = 3, "1 Tahun" = 12.
        $monthPackages = $lists->map(function ($pl) {
            $unit = strtolower((string) $pl->jangka_sewa);
            $months = $unit === 'bulan' ? (int) $pl->jangka_waktu : ($unit === 'tahun' ? 12 * (int) $pl->jangka_waktu : 0);

            return $months > 0 ? ['months' => $months, 'price' => (float) $pl->price, 'label' => $pl->jangka_waktu . ' ' . $pl->jangka_sewa] : null;
        })->filter()->sortByDesc('months')->values();

        $daily = $lists->filter(fn ($pl) => strtolower((string) $pl->jangka_sewa) === 'hari')->sortBy('jangka_waktu')->first();
        $monthly = $monthPackages->firstWhere('months', 1);
        $dailyRate = $daily
            ? round((float) $daily->price / max((int) $daily->jangka_waktu, 1))
            : ($monthly ? round($monthly['price'] / 30) : ($monthPackages->isNotEmpty() ? round($monthPackages->last()['price'] / ($monthPackages->last()['months'] * 30)) : 0));

        $parts = [];
        $cursor = $start->copy();
        foreach ($monthPackages as $package) {
            $count = 0;
            while ($cursor->copy()->addMonthsNoOverflow($package['months'])->lte($end)) {
                $cursor->addMonthsNoOverflow($package['months']);
                $count++;
            }
            if ($count > 0) {
                $parts[] = ['label' => $count . ' × paket ' . $package['label'], 'amount' => $count * $package['price']];
            }
        }
        $rest = (int) $cursor->diffInDays($end);
        if ($rest > 0) {
            $parts[] = ['label' => $rest . ' hari × ' . number_format($dailyRate, 0, ',', '.') . ($daily ? '' : ' (prorata)'), 'amount' => $rest * $dailyRate];
        }

        return [
            'total' => (float) array_sum(array_column($parts, 'amount')),
            'days' => $days,
            'parts' => $parts,
            'daily_rate' => (float) $dailyRate,
        ];
    }

    /**
     * Daily price for a rental's room: the 1-day package of its category when it exists,
     * otherwise the rental price spread over the package length.
     */
    public function dailyRate(tr_renter $trx): float
    {
        $category = optional($trx->room)->room_category;
        $daily = \App\Models\BCL\Pricelist::where('room_category', $category)
            ->whereRaw('LOWER(jangka_sewa) = ?', ['hari'])
            ->orderBy('jangka_waktu')
            ->first();
        if ($daily) {
            return round((float) $daily->price / max((int) $daily->jangka_waktu, 1));
        }

        $package = \App\Models\BCL\Pricelist::where('room_category', $category)
            ->where('jangka_waktu', (int) $trx->lama_sewa)
            ->whereRaw('LOWER(jangka_sewa) = ?', [strtolower((string) $trx->jangka_sewa)])
            ->first();
        $price = $package ? (float) $package->price : (float) $trx->harga;
        $start = $this->packageStart($trx);
        $days = max($start->diffInDays($this->addUnits($start, max((int) $trx->lama_sewa, 1), $trx->jangka_sewa)), 1);

        return round($price / $days);
    }

    /**
     * Parse a typed amount into a float. Accepts "1600000", "1,600,000.00",
     * "1.600.000" and "1.600.000,50"; a dot followed by exactly three digits is
     * a thousands separator (rupiah has no 3-digit decimals).
     */
    public static function amount($value): float
    {
        $value = preg_replace('/[^0-9.,\-]/', '', (string) $value);
        if ($value === '' || $value === null) {
            return 0;
        }

        if (preg_match('/,\d{1,2}$/', $value)) {
            $value = str_replace(['.', ','], ['', '.'], $value);
        } else {
            $value = str_replace(',', '', $value);
            if (substr_count($value, '.') > 1 || preg_match('/\.\d{3}$/', $value)) {
                $value = str_replace('.', '', $value);
            }
        }

        return (float) $value;
    }

    // ------------------------------------------------------------ numbering

    public function nextTransId(): string
    {
        $prefix = 'BL-' . now()->format('my');
        $last = DB::selectOne(
            'SELECT MAX(CAST(RIGHT(trans_id, 4) AS UNSIGNED)) AS n FROM bcl_tr_renter WHERE trans_id LIKE ? FOR UPDATE',
            [$prefix . '%']
        );

        return $prefix . str_pad((string) (((int) ($last->n ?? 0)) + 1), 4, '0', STR_PAD_LEFT);
    }

    public function nextExtraKode(): string
    {
        $prefix = 'TBH-' . now()->format('my');
        $last = DB::selectOne(
            'SELECT MAX(CAST(RIGHT(kode, 4) AS UNSIGNED)) AS n FROM bcl_extra_rent WHERE kode LIKE ? FOR UPDATE',
            [$prefix . '%']
        );

        return $prefix . str_pad((string) (((int) ($last->n ?? 0)) + 1), 4, '0', STR_PAD_LEFT);
    }

    public function nextNoJurnal(): string
    {
        $last = DB::selectOne('SELECT MAX(CAST(SUBSTR(no_jurnal, 3) AS UNSIGNED)) AS n FROM bcl_fin_jurnal FOR UPDATE');

        return now()->format('y') . str_pad((string) (((int) ($last->n ?? 0)) + 1), 7, '0', STR_PAD_LEFT);
    }

    public function nextNoExp(): string
    {
        $last = DB::selectOne("SELECT MAX(CAST(SUBSTR(doc_id, 7) AS UNSIGNED)) AS n FROM bcl_fin_jurnal WHERE doc_id LIKE 'EX%' FOR UPDATE");

        return 'EX' . now()->format('my') . str_pad((string) (((int) ($last->n ?? 0)) + 1), 7, '0', STR_PAD_LEFT);
    }

    // ------------------------------------------------------------ periods

    public function addUnits($date, int $amount, ?string $unit): Carbon
    {
        $date = Carbon::parse($date)->startOfDay();

        switch (strtolower((string) $unit)) {
            case 'minggu':
                return $date->addWeeks($amount);
            case 'bulan':
                return $date->addMonthsNoOverflow($amount);
            case 'tahun':
                return $date->addYearsNoOverflow($amount);
            default:
                return $date->addDays($amount);
        }
    }

    /** Whole units elapsed between two dates (rounded down, never negative). */
    public function wholeUnitsBetween($from, $to, ?string $unit): int
    {
        $from = Carbon::parse($from)->startOfDay();
        $to = Carbon::parse($to)->startOfDay();
        if ($to->lessThanOrEqualTo($from)) {
            return 0;
        }

        switch (strtolower((string) $unit)) {
            case 'minggu':
                $value = $from->diffInWeeks($to);
                break;
            case 'bulan':
                $value = $from->diffInMonths($to);
                break;
            case 'tahun':
                $value = $from->diffInYears($to);
                break;
            default:
                $value = $from->diffInDays($to);
                break;
        }

        return (int) floor($value + 1e-9);
    }

    /** End date (exclusive) of a package starting on $start, bonus period included. */
    public function packageEnd($start, int $lama, ?string $jangka, int $bonus = 0, ?string $bonusJangka = null): Carbon
    {
        $end = $this->addUnits($start, $lama, $jangka);

        return $bonus > 0 ? $this->addUnits($end, $bonus, $bonusJangka ?: 'Hari') : $end;
    }

    /**
     * Start date of the original package a transaction belongs to. A room change
     * keeps the package (lama_sewa/jangka_sewa/bonus) and its end date, so the
     * start is derived backwards from the end date.
     */
    public function packageStart(tr_renter $trx): Carbon
    {
        if ($trx->identity !== 'Pindah Kamar') {
            return Carbon::parse($trx->tgl_mulai)->startOfDay();
        }

        $end = Carbon::parse($trx->tgl_selesai)->startOfDay();
        if ((int) $trx->free_sewa > 0) {
            $end = $this->addUnits($end, -(int) $trx->free_sewa, $trx->free_jangka ?: 'Hari');
        }

        return $this->addUnits($end, -(int) $trx->lama_sewa, $trx->jangka_sewa);
    }

    /**
     * Rentals whose period has ended but the tenant was neither extended nor confirmed as checked out,
     * keyed by room id (latest per room). Such a room is still lived in ("lewat masa sewa").
     */
    public function overstaysByRoom(?int $roomId = null): Collection
    {
        $today = Carbon::today()->format('Y-m-d');
        $ended = tr_renter::with('renter')
            ->whereNull('checked_out_at')
            ->whereNull('cancelled_at')
            ->whereDate('tgl_selesai', '<=', $today)
            ->when($roomId, fn ($q) => $q->where('room_id', $roomId))
            ->orderByDesc('tgl_selesai')
            ->get();
        if ($ended->isEmpty()) {
            return collect();
        }

        // Rentals that already took over: the same renter continuing (extension or room change, legacy changes
        // started one day later) or anybody who has moved into the same room since.
        $others = tr_renter::where(function ($q) use ($ended) {
            $q->whereIn('id_renter', $ended->pluck('id_renter')->unique())
                ->orWhereIn('room_id', $ended->pluck('room_id')->unique());
        })->whereDate('tgl_mulai', '<=', $today)->get(['id', 'id_renter', 'room_id', 'tgl_mulai']);

        return $ended->filter(function ($trx) use ($others) {
            $nextDay = Carbon::parse($trx->tgl_selesai)->addDay()->format('Y-m-d');

            return !$others->contains(fn ($o) => $o->id !== $trx->id && $o->tgl_mulai >= $trx->tgl_selesai && (
                $o->room_id == $trx->room_id || ($o->id_renter == $trx->id_renter && $o->tgl_mulai <= $nextDay)
            ));
        })->unique('room_id')->keyBy('room_id');
    }

    /** Confirm the tenant of a rental has left (ends the "lewat masa sewa" state). */
    public function markCheckedOut(tr_renter $trx): void
    {
        $trx->forceFill(['checked_out_at' => now(), 'checked_out_by' => auth()->id()])->save();
    }

    public function roomHasOverlap(int $roomId, $start, $end, ?string $exceptTransId = null): bool
    {
        return tr_renter::where('room_id', $roomId)
            ->when($exceptTransId, fn ($q) => $q->where('trans_id', '!=', $exceptTransId))
            ->whereDate('tgl_mulai', '<', Carbon::parse($end)->format('Y-m-d'))
            ->whereDate('tgl_selesai', '>', Carbon::parse($start)->format('Y-m-d'))
            ->exists();
    }

    // ------------------------------------------------------------ payments

    /** Paid amount per document code (sum of revenue credits). */
    public function paidByDoc(iterable $docIds): Collection
    {
        $docIds = collect($docIds)->filter()->unique()->values();
        if ($docIds->isEmpty()) {
            return collect();
        }

        return Fin_jurnal::whereIn('doc_id', $docIds)
            ->where('kode_akun', self::ACCOUNT_REVENUE)
            ->where('pos', 'K')
            ->groupBy('doc_id')
            ->selectRaw('doc_id, SUM(kredit) as paid')
            ->pluck('paid', 'doc_id')
            ->map(fn ($v) => (float) $v);
    }

    public function extraTotal(tb_extra_rent $extra): float
    {
        return (float) $extra->harga * (float) $extra->lama_sewa * (float) $extra->qty;
    }

    /**
     * Outstanding room and extra transactions. A room transaction without any
     * revenue line is a legacy room-change record with nothing to collect.
     */
    public function unpaidItems(): Collection
    {
        // Same request may ask several times (dashboard summary + list): compute once.
        if ($this->unpaidCache !== null) {
            return $this->unpaidCache;
        }

        $paidSub = DB::table('bcl_fin_jurnal')
            ->where('kode_akun', self::ACCOUNT_REVENUE)
            ->where('pos', 'K')
            ->groupBy('doc_id')
            ->selectRaw('doc_id, SUM(kredit) as paid');

        $regular = DB::table('bcl_tr_renter as t')
            ->joinSub($paidSub, 'p', 'p.doc_id', '=', 't.trans_id')
            ->leftJoin('bcl_renter as r', 'r.id', '=', 't.id_renter')
            ->where('t.harga', '>', 0)
            ->whereRaw('t.harga - p.paid > 0.009')
            ->get(['t.trans_id', 't.tanggal', 't.identity', 't.harga', 't.id_renter', 'r.nama', 'r.deposit_balance', 'p.paid'])
            ->map(function ($row) {
                $isUpgrade = $row->identity === 'Pindah Kamar';
                $catatan = trim(($isUpgrade ? 'Upgrade kamar ' : 'Sewa kamar ') . $row->nama);

                return [
                    'transaksi' => $row->trans_id,
                    'tanggal' => $row->tanggal,
                    'nomor' => $row->trans_id,
                    'tipe' => $isUpgrade ? 'Upgrade Kamar' : 'Pendapatan Sewa',
                    'catatan' => $catatan,
                    'renter_id' => $row->id_renter,
                    'renter_name' => $row->nama,
                    'deposit' => (float) $row->deposit_balance,
                    'jumlah' => (float) $row->harga,
                    'kurang' => (float) $row->harga - (float) $row->paid,
                    'section' => $isUpgrade ? 'Upgrade Kamar' : 'Sewa Kamar',
                    'option_label' => $row->trans_id . ' - ' . $catatan,
                ];
            });

        $extra = DB::table('bcl_extra_rent as e')
            ->leftJoinSub($paidSub, 'p', 'p.doc_id', '=', 'e.kode')
            ->leftJoin('bcl_tr_renter as t', 't.trans_id', '=', 'e.parent_trans')
            ->leftJoin('bcl_renter as r', 'r.id', '=', 't.id_renter')
            ->whereRaw('e.harga * e.lama_sewa * e.qty - COALESCE(p.paid, 0) > 0.009')
            ->get(['e.kode', 'e.nama', 'e.qty', 'e.lama_sewa', 'e.jangka_sewa', 'e.tgl_mulai', 'e.harga', 'r.id as renter_id', 'r.nama as renter_name', 'r.deposit_balance', 'p.paid'])
            ->map(function ($row) {
                $total = (float) $row->harga * (float) $row->lama_sewa * (float) $row->qty;
                $catatan = trim($row->nama . ' ' . $row->lama_sewa . ' ' . $row->jangka_sewa . ' ' . $row->renter_name);

                return [
                    'transaksi' => $row->kode,
                    'tanggal' => $row->tgl_mulai,
                    'nomor' => $row->kode,
                    'tipe' => 'Tambahan Sewa',
                    'catatan' => $catatan,
                    'renter_id' => $row->renter_id,
                    'renter_name' => $row->renter_name,
                    'deposit' => (float) $row->deposit_balance,
                    'jumlah' => $total,
                    'kurang' => $total - (float) $row->paid,
                    'section' => 'Tambahan Sewa',
                    'option_label' => $row->kode . ' - ' . $catatan,
                ];
            });

        return $this->unpaidCache = $regular->concat($extra)->sortByDesc('tanggal')->values();
    }

    /** Active room per renter (today), for a set of renters in one query. */
    public function activeRoomsByRenter(iterable $renterIds): Collection
    {
        $today = Carbon::today()->format('Y-m-d');

        return tr_renter::with('room')
            ->whereIn('id_renter', collect($renterIds)->filter()->unique()->values())
            ->whereDate('tgl_mulai', '<=', $today)
            ->whereDate('tgl_selesai', '>', $today)
            ->orderByDesc('tgl_mulai')
            ->get()
            ->unique('id_renter')
            ->mapWithKeys(fn ($trx) => [$trx->id_renter => $trx->room]);
    }

    // ------------------------------------------------------------ extra bed

    public function extraBedAssets(): Collection
    {
        ExtraBedAsset::ensureDefaultAssets();
        $today = Carbon::today()->format('Y-m-d');

        $assets = ExtraBedAsset::with([
            'assignments.extraRent.parentTransaction.room',
            'assignments.extraRent.parentTransaction.renter',
        ])->orderBy('asset_code')->get();
        $currentOf = fn ($asset) => $asset->assignments
            ->filter(fn ($a) => $a->extraRent && $a->extraRent->tgl_mulai <= $today && $a->extraRent->tgl_selesai > $today)
            ->sortByDesc(fn ($a) => $a->extraRent->tgl_mulai)
            ->first();
        $rooms = $this->activeRoomsByRenter($assets->map(fn ($a) => optional(optional(optional($currentOf($a))->extraRent)->parentTransaction)->id_renter));

        return $assets->map(function ($asset) use ($currentOf, $rooms) {
            $extraRent = optional($currentOf($asset))->extraRent;
            $parent = optional($extraRent)->parentTransaction;

            return [
                'asset_code' => $asset->asset_code,
                'status' => $extraRent ? 'Dipakai' : 'Tersedia',
                'room_name' => optional($parent ? ($rooms[$parent->id_renter] ?? $parent->room) : null)->room_name,
                'renter_name' => optional(optional($parent)->renter)->nama,
                'extra_rent_code' => optional($extraRent)->kode,
                'period' => $extraRent ? ($extraRent->tgl_mulai . ' s/d ' . $extraRent->tgl_selesai) : null,
            ];
        })->values();
    }

    /** Receive a security deposit (uang jaminan) from a renter. */
    public function receiveGuarantee(\App\Models\BCL\renter $renter, float $amount, string $method, ?string $proof, string $date, string $note = ''): string
    {
        $noJurnal = $this->nextNoJurnal();
        $base = ['no_jurnal' => $noJurnal, 'tanggal' => $date, 'doc_id' => 'JM' . $noJurnal, 'identity' => 'Uang Jaminan', 'catatan' => 'Uang jaminan dari ' . $renter->nama . ($note ? '. ' . $note : '')];
        $this->journal($base + ['kode_akun' => self::ACCOUNT_CASH, 'debet' => $amount, 'pos' => 'D', 'metode_bayar' => $method, 'bukti' => $proof]);
        $this->journal($base + ['kode_akun' => self::ACCOUNT_GUARANTEE, 'kredit' => $amount, 'pos' => 'K', 'kode_subledger' => $renter->id]);
        $this->audit('guarantee', 'JM' . $noJurnal, 'Terima uang jaminan ' . $renter->nama, ['amount' => $amount, 'method' => $method]);

        return 'JM' . $noJurnal;
    }
    /** Return / deduct / move the security deposit of a renter. Returns human-readable notes. */
    public function settleGuarantee(\App\Models\BCL\renter $renter, float $return, float $deduct, bool $toBalance, string $method, string $reason): array
    {
        if ($return <= 0 && $deduct <= 0) {
            return [];
        }
        $balance = $this->guaranteeBalance($renter->id);
        if ($return + $deduct > $balance + 0.009) {
            throw new \RuntimeException('Uang jaminan ' . $renter->nama . ' hanya Rp ' . number_format($balance, 0, ',', '.') . '.');
        }
        $notes = [];
        $noJurnal = $this->nextNoJurnal();
        $doc = 'JM' . $noJurnal;
        if ($deduct > 0) {
            $base = ['no_jurnal' => $noJurnal, 'tanggal' => date('Y-m-d'), 'doc_id' => $doc, 'identity' => 'Potongan Jaminan', 'catatan' => 'Potongan uang jaminan ' . $renter->nama . ($reason ? ': ' . $reason : '')];
            $this->journal($base + ['kode_akun' => self::ACCOUNT_GUARANTEE, 'debet' => $deduct, 'pos' => 'D', 'kode_subledger' => $renter->id]);
            $this->journal($base + ['kode_akun' => self::ACCOUNT_OTHER_INCOME, 'kredit' => $deduct, 'pos' => 'K', 'kode_subledger' => $renter->id]);
            $notes[] = 'jaminan dipotong Rp ' . number_format($deduct, 0, ',', '.');
        }
        if ($return > 0) {
            $base = ['no_jurnal' => $noJurnal, 'tanggal' => date('Y-m-d'), 'doc_id' => $doc, 'catatan' => 'Pengembalian uang jaminan ' . $renter->nama . ($toBalance ? ' ke saldo deposit' : '')];
            $this->journal($base + ['kode_akun' => self::ACCOUNT_GUARANTEE, 'debet' => $return, 'pos' => 'D', 'kode_subledger' => $renter->id, 'identity' => 'Pengembalian Jaminan']);
            if ($toBalance) {
                $this->journal($base + ['kode_akun' => self::ACCOUNT_DEPOSIT, 'kredit' => $return, 'pos' => 'K', 'kode_subledger' => $renter->id, 'identity' => 'Topup Deposit (Jaminan)']);
                $renter->creditDeposit($return);
            } else {
                $this->journal($base + ['kode_akun' => self::ACCOUNT_CASH, 'kredit' => $return, 'pos' => 'K', 'identity' => 'Pengembalian Jaminan', 'metode_bayar' => $method]);
            }
            $notes[] = 'jaminan dikembalikan Rp ' . number_format($return, 0, ',', '.') . ($toBalance ? ' ke deposit' : '');
        }
        $this->audit('guarantee', $doc, 'Penyelesaian uang jaminan ' . $renter->nama, ['return' => $return, 'deduct' => $deduct, 'to_balance' => $toBalance, 'reason' => $reason]);

        return $notes;
    }

    // ------------------------------------------------------------ journal

    public function journal(array $attributes): Fin_jurnal
    {
        return Fin_jurnal::create(array_merge([
            'debet' => 0,
            'kredit' => 0,
            'kode_subledger' => null,
            'index_kas' => 0,
            'user_id' => auth()->id(),
            'csrf' => time(),
        ], $attributes));
    }

    /**
     * Undo the effect of deposit lines on renter balances before the lines are deleted.
     */
    public function revertDepositLines(Collection $lines): void
    {
        $lines->where('kode_akun', self::ACCOUNT_DEPOSIT)
            ->groupBy('kode_subledger')
            ->each(function (Collection $rows, $renterId) {
                $renter = renter::find($renterId);
                if (!$renter) {
                    return;
                }
                $net = (float) $rows->sum('kredit') - (float) $rows->sum('debet');
                $renter->deposit_balance = (float) $renter->deposit_balance - $net;
                $renter->save();
            });
    }

    /** Deposit ledger of a renter, oldest first, with running balance. */
    public function depositLedger(int $renterId): Collection
    {
        $balance = 0;

        return Fin_jurnal::where('kode_akun', self::ACCOUNT_DEPOSIT)
            ->where('kode_subledger', $renterId)
            ->orderBy('tanggal')->orderBy('id')
            ->get()
            ->map(function ($line) use (&$balance) {
                $in = (float) $line->kredit;
                $out = (float) $line->debet;
                $balance += $in - $out;

                return [
                    'tanggal' => $line->tanggal,
                    'doc_id' => $line->doc_id,
                    'keterangan' => $line->identity . ($line->catatan ? ' - ' . $line->catatan : ''),
                    'masuk' => $in,
                    'keluar' => $out,
                    'saldo' => $balance,
                ];
            });
    }
}
