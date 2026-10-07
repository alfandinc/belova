<?php

namespace App\Services\ERM;

use App\Models\ERM\Pasien;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Finds patients registered more than once and merges them into one record.
 */
class PasienMergeService
{
    // A value shared by more patients than this is a placeholder (e.g. 1900-01-01, a clinic phone), not a match
    private const MAX_SAME_BIRTHDATE = 25;
    private const MAX_SAME_PHONE = 8;
    private const MAX_SAME_IDENTITY = 5;

    // Titles and degrees that are not part of the name itself
    private const NAME_TITLES = [
        'TN', 'TUAN', 'NY', 'NYONYA', 'NN', 'NONA', 'AN', 'ANAK', 'BY', 'BAYI', 'SDR', 'SDRI', 'SAUDARA', 'SAUDARI',
        'BPK', 'BAPAK', 'IBU', 'BU', 'PAK', 'MR', 'MRS', 'MS', 'MISS', 'DR', 'DRG', 'DRS', 'DRA', 'IR', 'H', 'HJ',
        'ST', 'SH', 'SE', 'MM', 'MH', 'MPD', 'SPD', 'SKOM', 'SKED', 'SFARM', 'APT', 'AMD', 'SPKK', 'SPDV',
    ];

    // Empty fields on the kept patient are filled from the merged one
    private const FILLABLE_FIELDS = [
        'identity_number', 'tanggal_lahir', 'gender', 'agama', 'marital_status', 'pendidikan', 'pekerjaan',
        'gol_darah', 'alamat', 'village_id', 'no_hp', 'no_hp2', 'email', 'instagram', 'user_id', 'employee_id',
    ];

    private const STATUS_RANK = ['Regular' => 0, 'Familia' => 1, 'VIP' => 2, 'Black Card' => 3, 'Red Flag' => 4];

    /**
     * Groups of patients that are probably the same person, strongest matches first.
     */
    public function findDuplicateGroups(): Collection
    {
        $patients = DB::table('erm_pasiens')
            ->select(['id', 'nama', 'tanggal_lahir', 'identity_number', 'no_hp', 'no_hp2'])
            ->get()
            ->map(function ($p) {
                $p->id = (string) $p->id;
                $p->name_key = $this->normalizeName($p->nama);
                $p->identity_key = $this->normalizeIdentity($p->identity_number);
                $p->phone_keys = array_values(array_unique(array_filter([
                    $this->normalizePhone($p->no_hp),
                    $this->normalizePhone($p->no_hp2),
                ])));
                $p->birth_key = !empty($p->tanggal_lahir) && $p->tanggal_lahir > '1900-12-31' ? substr((string) $p->tanggal_lahir, 0, 10) : null;
                return $p;
            })
            ->keyBy('id');

        $pairs = [];
        $addPair = function ($a, $b, string $reason, bool $strong) use (&$pairs) {
            $key = $a->id < $b->id ? $a->id . '|' . $b->id : $b->id . '|' . $a->id;
            $pairs[$key] ??= ['a' => $a->id, 'b' => $b->id, 'reasons' => [], 'strong' => false];
            $pairs[$key]['reasons'][$reason] = true;
            $pairs[$key]['strong'] = $pairs[$key]['strong'] || $strong;
        };

        // Same identity number
        foreach ($this->blocks($patients, fn ($p) => $p->identity_key ? [$p->identity_key] : [], self::MAX_SAME_IDENTITY) as $block) {
            $this->eachPair($block, function ($a, $b) use ($addPair) {
                $nameMatch = $this->namesMatch($a->name_key, $b->name_key, false);
                $addPair($a, $b, $nameMatch ? 'No. identitas sama' : 'No. identitas sama (nama berbeda, cek dulu)', $nameMatch !== null);
            });
        }

        // Same birth date and a similar name
        foreach ($this->blocks($patients, fn ($p) => $p->birth_key ? [$p->birth_key] : [], self::MAX_SAME_BIRTHDATE) as $block) {
            $this->eachPair($block, function ($a, $b) use ($addPair) {
                $match = $this->namesMatch($a->name_key, $b->name_key, false);
                if ($match === 'same') {
                    $addPair($a, $b, 'Nama & tgl lahir sama', true);
                } elseif ($match === 'similar') {
                    $addPair($a, $b, 'Nama mirip & tgl lahir sama', false);
                }
            });
        }

        // Same phone and a similar name; families often share one number, so the name must match closely
        foreach ($this->blocks($patients, fn ($p) => $p->phone_keys, self::MAX_SAME_PHONE) as $block) {
            $this->eachPair($block, function ($a, $b) use ($addPair) {
                if ($a->birth_key && $b->birth_key && $a->birth_key !== $b->birth_key) {
                    return;
                }
                $match = $this->namesMatch($a->name_key, $b->name_key, true);
                if ($match !== null) {
                    $addPair($a, $b, $match === 'same' ? 'Nama & no. HP sama' : 'Nama mirip & no. HP sama', false);
                }
            });
        }

        if (empty($pairs)) {
            return collect();
        }

        // Join pairs that share a patient into one group
        $parent = [];
        $find = function ($x) use (&$parent, &$find) {
            if (!isset($parent[$x])) {
                $parent[$x] = $x;
            }
            return $parent[$x] === $x ? $x : ($parent[$x] = $find($parent[$x]));
        };
        foreach ($pairs as $pair) {
            $parent[$find($pair['a'])] = $find($pair['b']);
        }

        $groups = [];
        foreach ($pairs as $pair) {
            $root = $find($pair['a']);
            $groups[$root] ??= ['ids' => [], 'reasons' => [], 'strong' => false];
            $groups[$root]['ids'][$pair['a']] = true;
            $groups[$root]['ids'][$pair['b']] = true;
            $groups[$root]['reasons'] += $pair['reasons'];
            $groups[$root]['strong'] = $groups[$root]['strong'] || $pair['strong'];
        }

        $allIds = array_keys($parent);
        $details = $this->patientDetails($allIds);

        return collect($groups)
            ->map(function ($group) use ($details) {
                $members = collect(array_keys($group['ids']))
                    ->map(fn ($id) => $details[(string) $id] ?? null)
                    ->filter()
                    ->sortBy('id')
                    ->values();

                // Keep the record with the most visits; on a tie the oldest one
                $suggested = $members->sortBy([['visit_count', 'desc'], ['id', 'asc']])->first();

                return [
                    'key' => $members->pluck('id')->implode('-'),
                    'level' => $group['strong'] ? 'tinggi' : 'sedang',
                    'reasons' => array_keys($group['reasons']),
                    'suggested_target_id' => $suggested['id'] ?? null,
                    'patients' => $members,
                ];
            })
            ->filter(fn ($group) => $group['patients']->count() > 1)
            ->sortBy([
                fn ($a, $b) => ($a['level'] === 'tinggi' ? 0 : 1) <=> ($b['level'] === 'tinggi' ? 0 : 1),
                fn ($a, $b) => strcmp((string) $a['patients'][0]['nama'], (string) $b['patients'][0]['nama']),
            ])
            ->values();
    }

    /**
     * Move everything that belongs to the source patients to the target, then delete the sources.
     *
     * @return array{visits:int, rows:int, merged:array}
     */
    public function merge(Pasien $target, Collection $sources): array
    {
        $sources = $sources->reject(fn ($s) => (string) $s->id === (string) $target->id)->values();
        if ($sources->isEmpty()) {
            return ['visits' => 0, 'rows' => 0, 'merged' => []];
        }

        return DB::transaction(function () use ($target, $sources) {
            $target = Pasien::lockForUpdate()->findOrFail($target->id);
            $columns = $this->pasienColumns();
            $visits = 0;
            $rows = 0;

            foreach ($sources as $source) {
                $source = Pasien::lockForUpdate()->findOrFail($source->id);
                $visits += DB::table('erm_visitations')->where('pasien_id', $source->id)->count();

                $before = [];
                foreach (array_keys($columns) as $table) {
                    $before[$table] = DB::table($table)->whereIn('pasien_id', [$source->id, $target->id])->count();
                }
                $dropped = array_fill_keys(array_keys($columns), 0);

                // A patient may be allergic to a zat aktif only once
                $targetZat = DB::table('erm_alergi')->where('pasien_id', $target->id)->whereNotNull('zataktif_id')->pluck('zataktif_id');
                $dropped['erm_alergi'] += DB::table('erm_alergi')->where('pasien_id', $source->id)->whereIn('zataktif_id', $targetZat)->delete();

                foreach ($columns as $table => $info) {
                    $dropped[$table] += $this->dropUniqueConflicts($table, $info['unique_indexes'], $source->id, $target->id);
                    $rows += DB::table($table)->where('pasien_id', $source->id)->update(['pasien_id' => $target->id]);
                }

                // Patients and visits referred by the merged patient now point to the kept one
                $morph = $target->getMorphClass();
                foreach (['erm_pasiens', 'erm_visitations'] as $table) {
                    DB::table($table)->where('referralable_type', $morph)->where('referralable_id', $source->id)
                        ->update(['referralable_id' => $target->id]);
                    DB::table($table)->where('referral_type', Pasien::REFERRAL_TYPE_PASIEN)
                        ->where('referral_detail', $source->id)->update(['referral_detail' => $target->id]);
                }
                $target->refresh();

                $this->absorbProfile($target, $source);
                $target->save();

                // Deleting the source cascades to some tables, so it must not be referenced anywhere any more
                $this->assertFullyMoved($columns, $before, $dropped, $source->id, $target->id);

                $snapshot = $source->getAttributes();
                $source->delete();

                // Logged only once the merge is committed, so a rolled back merge leaves no trace
                DB::afterCommit(fn () => Log::info('Pasien merged', [
                    'target_id' => $target->id,
                    'source' => $snapshot,
                    'by_user_id' => Auth::id(),
                ]));
            }

            $target->syncSourceReferralToFirstVisit();

            // Patient header data is cached per visit (RiwayatKunjunganController)
            DB::table('erm_visitations')->where('pasien_id', $target->id)->pluck('id')
                ->each(fn ($visitId) => Cache::forget("pasien_data_{$visitId}"));

            return ['visits' => $visits, 'rows' => $rows, 'merged' => $sources->pluck('id')->map(fn ($id) => (string) $id)->all()];
        });
    }

    /**
     * Fill the kept patient's empty data from the merged one; never overwrite what is already there.
     */
    private function absorbProfile(Pasien $target, Pasien $source): void
    {
        foreach (self::FILLABLE_FIELDS as $field) {
            $value = $source->getAttributes()[$field] ?? null;
            if ($this->isBlank($target->getAttributes()[$field] ?? null) && !$this->isBlank($value)) {
                if ($field === 'identity_number') {
                    $target->identity_document = $source->getAttributes()['identity_document'] ?? Pasien::IDENTITY_DOCUMENT_KTP;
                }
                $target->{$field} = $value;
            }
        }

        // Keep a second phone number instead of losing it
        $sourcePhone = $source->getAttributes()['no_hp'] ?? null;
        if (!$this->isBlank($sourcePhone) && $sourcePhone !== $target->no_hp && $this->isBlank($target->no_hp2)) {
            $target->no_hp2 = $sourcePhone;
        }

        // The merged patient's referral wins only when the kept one is a plain walk-in
        $selfReferral = $target->referralable_type === $target->getMorphClass() && (string) $target->referralable_id === (string) $target->id;
        if (($selfReferral || in_array($target->referral_type, [null, '', Pasien::REFERRAL_TYPE_WALK_IN], true))
            && !in_array($source->referral_type, [null, '', Pasien::REFERRAL_TYPE_WALK_IN], true)
            && !((string) $source->referralable_id === (string) $target->id && $source->referralable_type === $target->getMorphClass())) {
            $target->referral_type = $source->referral_type;
            $target->referral_detail = $source->referral_detail;
            $target->referralable_type = $source->referralable_type;
            $target->referralable_id = $source->referralable_id;
        } elseif ($selfReferral) {
            $target->referral_type = Pasien::REFERRAL_TYPE_WALK_IN;
            $target->referral_detail = null;
            $target->referralable_type = null;
            $target->referralable_id = null;
        }

        // Keep the more notable status (VIP, Red Flag, ...) and fast access
        $sourceRank = self::STATUS_RANK[$source->status_pasien ?? 'Regular'] ?? 0;
        $targetRank = self::STATUS_RANK[$target->status_pasien ?? 'Regular'] ?? 0;
        if ($sourceRank > $targetRank) {
            $target->status_pasien = $source->status_pasien;
        }
        if ($source->status_akses === 'akses cepat') {
            $target->status_akses = 'akses cepat';
        }

        if (!$this->isBlank($source->notes) && !str_contains((string) $target->notes, (string) $source->notes)) {
            $target->notes = trim(($target->notes ? $target->notes . "\n" : '') . '[Gabungan RM ' . $source->id . '] ' . $source->notes);
        }

        if ($source->created_at && $target->created_at && $source->created_at->lt($target->created_at)) {
            $target->created_at = $source->created_at;
        }
    }

    /**
     * Every table with a pasien_id column, with the unique indexes that include it.
     */
    private function pasienColumns(): array
    {
        $database = DB::getDatabaseName();

        $tables = DB::table('information_schema.COLUMNS as c')
            ->join('information_schema.TABLES as t', function ($join) {
                $join->on('t.TABLE_SCHEMA', '=', 'c.TABLE_SCHEMA')->on('t.TABLE_NAME', '=', 'c.TABLE_NAME');
            })
            ->where('c.TABLE_SCHEMA', $database)
            ->where('c.COLUMN_NAME', 'pasien_id')
            ->where('t.TABLE_TYPE', 'BASE TABLE')
            ->pluck('c.TABLE_NAME');

        $indexes = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $database)
            ->whereIn('TABLE_NAME', $tables)
            ->where('NON_UNIQUE', 0)
            ->orderBy('SEQ_IN_INDEX')
            ->get(['TABLE_NAME', 'INDEX_NAME', 'COLUMN_NAME'])
            ->groupBy(fn ($row) => $row->TABLE_NAME . '.' . $row->INDEX_NAME);

        $result = [];
        foreach ($tables as $table) {
            $result[$table] = ['unique_indexes' => []];
        }
        foreach ($indexes as $rows) {
            $cols = $rows->pluck('COLUMN_NAME')->all();
            if (in_array('pasien_id', $cols, true)) {
                $result[$rows->first()->TABLE_NAME]['unique_indexes'][] = array_values(array_diff($cols, ['pasien_id']));
            }
        }

        return $result;
    }

    /**
     * Every row of the source now belongs to the target (minus the deliberately dropped duplicates)
     * and nothing points to the source any more; otherwise the whole merge is rolled back.
     */
    private function assertFullyMoved(array $columns, array $before, array $dropped, string $sourceId, string $targetId): void
    {
        $problems = [];

        foreach (array_keys($columns) as $table) {
            if (DB::table($table)->where('pasien_id', $sourceId)->exists()) {
                $problems[] = "{$table} masih memakai RM {$sourceId}";
            }
            $after = DB::table($table)->where('pasien_id', $targetId)->count();
            if ($after !== $before[$table] - $dropped[$table]) {
                $problems[] = "{$table}: {$before[$table]} baris sebelum, {$after} sesudah, {$dropped[$table]} duplikat dihapus";
            }
        }

        $morph = (new Pasien())->getMorphClass();
        foreach (['erm_pasiens', 'erm_visitations'] as $table) {
            $left = DB::table($table)
                ->where('id', '!=', $sourceId) // the source row itself is deleted next
                ->where(fn ($q) => $q
                    ->where(fn ($q) => $q->where('referralable_type', $morph)->where('referralable_id', $sourceId))
                    ->orWhere(fn ($q) => $q->where('referral_type', Pasien::REFERRAL_TYPE_PASIEN)->where('referral_detail', $sourceId)))
                ->count();
            if ($left > 0) {
                $problems[] = "{$table}: {$left} referral masih menunjuk RM {$sourceId}";
            }
        }

        if (!empty($problems)) {
            throw new \RuntimeException('Penggabungan dibatalkan, data belum pindah semua: ' . implode('; ', $problems));
        }
    }

    /**
     * Delete source rows that would collide with the target on a unique index (e.g. one birthday greeting per year).
     */
    private function dropUniqueConflicts(string $table, array $uniqueIndexes, string $sourceId, string $targetId): int
    {
        $deleted = 0;
        foreach ($uniqueIndexes as $otherColumns) {
            $query = DB::table($table . ' as s')->where('s.pasien_id', $sourceId)
                ->whereExists(function ($q) use ($table, $otherColumns, $targetId) {
                    $q->select(DB::raw(1))->from($table . ' as t')->where('t.pasien_id', $targetId);
                    foreach ($otherColumns as $col) {
                        $q->whereColumn('t.' . $col, 's.' . $col);
                    }
                });
            $ids = $query->pluck('s.id');
            if ($ids->isNotEmpty()) {
                $deleted += DB::table($table)->whereIn('id', $ids)->delete();
            }
        }

        return $deleted;
    }

    private function patientDetails(array $ids): array
    {
        $visits = DB::table('erm_visitations')
            ->whereIn('pasien_id', $ids)
            ->groupBy('pasien_id')
            ->selectRaw('pasien_id, COUNT(*) as total, MAX(tanggal_visitation) as last_visit')
            ->get()
            ->keyBy(fn ($row) => (string) $row->pasien_id);

        return Pasien::query()
            ->whereIn('id', $ids)
            ->get(['id', 'nama', 'tanggal_lahir', 'gender', 'identity_document', 'identity_number', 'no_hp', 'alamat', 'status_pasien', 'created_at'])
            ->mapWithKeys(function ($p) use ($visits) {
                $visit = $visits[(string) $p->id] ?? null;
                return [(string) $p->id => [
                    'id' => (string) $p->id,
                    'nama' => $p->nama,
                    'tanggal_lahir' => $p->tanggal_lahir ? substr((string) $p->tanggal_lahir, 0, 10) : null,
                    'gender' => $p->gender,
                    'identity' => $p->identity_display,
                    'no_hp' => $p->no_hp,
                    'alamat' => $p->alamat,
                    'status_pasien' => $p->status_pasien,
                    'visit_count' => (int) ($visit->total ?? 0),
                    'last_visit' => $visit && $visit->last_visit ? substr((string) $visit->last_visit, 0, 10) : null,
                    'created_at' => optional($p->created_at)->format('Y-m-d'),
                ]];
            })
            ->all();
    }

    /**
     * Buckets of patients sharing a key; buckets that are too big hold a placeholder value and are skipped.
     */
    private function blocks(Collection $patients, callable $keys, int $maxSize): array
    {
        $blocks = [];
        foreach ($patients as $p) {
            foreach ($keys($p) as $key) {
                $blocks[$key][] = $p;
            }
        }

        return array_filter($blocks, fn ($block) => count($block) > 1 && count($block) <= $maxSize);
    }

    private function eachPair(array $block, callable $callback): void
    {
        $n = count($block);
        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                $callback($block[$i], $block[$j]);
            }
        }
    }

    /**
     * 'same', 'similar' or null. Strict mode (no shared birth date to back it up) needs a closer match.
     */
    private function namesMatch(string $a, string $b, bool $strict): ?string
    {
        if ($a === '' || $b === '') {
            return null;
        }
        $compactA = str_replace(' ', '', $a);
        $compactB = str_replace(' ', '', $b);
        if ($compactA === $compactB) {
            return 'same';
        }

        // Typos: "SRI NURUL HAYATI" vs "SARI NURUL HAYATI"
        $maxLen = max(strlen($compactA), strlen($compactB));
        if ($maxLen >= 5 && levenshtein($compactA, $compactB) <= max(1, (int) floor($maxLen * ($strict ? 0.1 : 0.15)))) {
            return 'similar';
        }

        // Short vs full name: "IMAM SUWANGSA" vs "R IMAM SUWANGSA"
        $tokensA = explode(' ', $a);
        $tokensB = explode(' ', $b);
        [$short, $long] = count($tokensA) <= count($tokensB) ? [$tokensA, $tokensB] : [$tokensB, $tokensA];
        if (empty(array_diff($short, $long))) {
            $enough = $strict ? count($short) >= 2 : strlen(implode('', $short)) >= 4;
            if ($enough) {
                return 'similar';
            }
        }

        return null;
    }

    private function normalizeName(?string $name): string
    {
        // Notes in brackets are not part of the name: "PARNI (MEMBER)", "NY. RAHENI (SALAH)"
        $name = preg_replace('/\([^)]*\)|\[[^\]]*\]/', ' ', strtoupper((string) $name));
        $tokens = preg_split('/[^A-Z]+/', $name, -1, PREG_SPLIT_NO_EMPTY);
        $tokens = array_values(array_filter($tokens, fn ($t) => !in_array($t, self::NAME_TITLES, true)));

        return implode(' ', $tokens);
    }

    private function normalizeIdentity(?string $value): ?string
    {
        $value = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $value));
        if (strlen($value) < 8 || preg_match('/^(.)\1+$/', $value)) {
            return null;
        }

        return $value;
    }

    private function normalizePhone(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);
        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62' . $digits;
        }

        return strlen($digits) >= 10 ? $digits : null;
    }

    private function isBlank($value): bool
    {
        return $value === null || trim((string) $value) === '' || $value === '-';
    }
}
