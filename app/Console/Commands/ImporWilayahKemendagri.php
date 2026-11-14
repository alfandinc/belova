<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Melengkapi tabel wilayah (area_provinces / regencies / districts / villages) dari data Kepmendagri
 * No 300.2.2-2138 Tahun 2025 (database/data/wilayah_kemendagri_2025.csv, sumber github.com/cahyadsn/wilayah, MIT).
 *
 * Tabel area_* memakai kode BPS (sekitar 2017), yang berbeda dengan kode Kemendagri untuk kecamatan, desa, dan banyak
 * kab/kota. Karena itu wilayah dicocokkan lewat NAMA di bawah induk yang sama, bukan lewat kode:
 *   - ID lama tidak diubah (pasien memakai area_villages.id), dan tidak ada yang dihapus;
 *   - kecamatan yang ganti nama (mis. KOTA BLORA -> Blora) dikenali dari isi desanya (>= 50% sama);
 *   - wilayah baru mendapat ID dengan pola lama: induk * 1000 + nomor berikutnya;
 *   - kab/kota Papua dipindah ke 4 provinsi baru (Papua Tengah = 97, karena 94 sudah dipakai PAPUA).
 * Tanpa --apply hanya menampilkan rencana. Aman dijalankan ulang: yang sudah ada dilewati.
 */
class ImporWilayahKemendagri extends Command
{
    protected $signature = 'area:impor-kemendagri
        {--apply : Tulis perubahan ke database (tanpa ini hanya simulasi)}
        {--file= : CSV kode,nama (default database/data/wilayah_kemendagri_2025.csv)}
        {--ejaan-resmi= : Kode kab/kota Kemendagri dipisah koma (mis. 33.72,33.11): nama kecamatan/desa yang beda ejaan diganti ejaan resmi}';

    /** Solo Raya, the clinic area: `--ejaan-resmi=solo` */
    private const SOLO_RAYA = ['33.72', '33.11', '33.13', '33.09', '33.10', '33.14', '33.12'];

    protected $description = 'Lengkapi data wilayah dari Kepmendagri 2025 (cocok lewat nama, ID lama tetap, tanpa hapus)';

    private const PROV_ALIAS = ['DKIJAKARTA' => 'DAERAHKHUSUSIBUKOTAJAKARTA', 'DIYOGYAKARTA' => 'DAERAHISTIMEWAYOGYAKARTA'];

    // Provinsi baru 2022; ID 94 sudah dipakai PAPUA (kode BPS)
    private const NEW_PROV_ID = ['93' => 93, '94' => 97, '95' => 95, '96' => 96];

    // Kab/kota yang resmi ganti nama (nama di database => nama Kemendagri, keduanya ternormalisasi)
    private const REG_ALIAS = [
        'KABUPATENTOBASAMOSIR' => 'KABUPATENTOBA',
        'KABUPATENMAMUJUUTARA' => 'KABUPATENPASANGKAYU',
        'KABUPATENMALUKUTENGGARABARAT' => 'KABUPATENKEPULAUANTANIMBAR',
        'KABUPATENSIAUTAGULANDANGBIARO' => 'KABUPATENKEPULAUANSIAUTAGULANDANGBIARO',
        'KABUPATENMAHAKAMHULU' => 'KABUPATENMAHAKAMULU',
    ];

    // Desa yang ganti nama sekaligus pindah kecamatan / ejaan jauh, tidak tertangkap otomatis
    // (kode Kemendagri => id lama di database). Dicek manual untuk Solo Raya, 2026-11-14.
    private const VIL_ALIAS = [
        '33.09.20.2007' => 3309020017, // Ngargoloka (Gladagsari) = NGARGOLOKO (Ampel)
        '33.12.22.2012' => 3312240013, // Semagar (Girimarto) = SEMAGARDUWUR
    ];

    private array $nextCache = [];

    /** [table => [id => kode Kemendagri]] for every row matched or added */
    private array $codes = [];

    private bool $hasKode = false;

    private function code(string $table, int|string $id, string $code): void
    {
        $this->codes[$table][(int) $id] = $code;
    }

    /** One UPDATE ... CASE per 1000 rows instead of one query per row. */
    private function bulkSetCode(string $table, array $codes, $now): void
    {
        foreach (array_chunk($codes, 1000, true) as $chunk) {
            $case = '';
            $bindings = [];
            foreach ($chunk as $id => $code) {
                $case .= ' WHEN ? THEN ?';
                array_push($bindings, $id, $code);
            }
            $ids = array_keys($chunk);
            DB::update("UPDATE $table SET kode_kemendagri = CASE id$case END, updated_at = ? WHERE id IN (" . implode(',', array_fill(0, count($ids), '?')) . ')',
                array_merge($bindings, [$now], $ids));
        }
    }

    public function handle(): int
    {
        $file = $this->option('file') ?: database_path('data/wilayah_kemendagri_2025.csv');
        if (!is_readable($file)) {
            $this->error("File tidak ditemukan: $file");
            return self::FAILURE;
        }
        ini_set('memory_limit', '1024M');

        $kd = $this->readSource($file);
        $this->info(sprintf('Sumber: %d provinsi, %d kab/kota, %d kecamatan, %d desa/kel', count($kd[1]), count($kd[2]), count($kd[3]), count($kd[4])));

        $plan = $this->buildPlan($kd);

        $this->table(['Aksi', 'Jumlah'], [
            ['Provinsi ditambah', count($plan['provInsert'])],
            ['Kab/kota ganti nama', count($plan['regRename'])],
            ['Kab/kota pindah provinsi', count($plan['regMove'])],
            ['Kecamatan ganti nama', count($plan['disRename'])],
            ['Kecamatan ditambah', count($plan['disInsert'])],
            ['Desa/kel pindah kecamatan', count($plan['vilMove'])],
            ['Desa/kel ditambah', count($plan['vilInsert'])],
            ['Kecamatan/desa diganti ejaan resmi', count($plan['disSpell']) + count($plan['vilSpell'])],
            ['Kode Kemendagri diisi (wilayah yang sudah ada)', $this->hasKode ? array_sum(array_map('count', $plan['kode'])) : 'kolom belum ada, jalankan migrate'],
            ['Dilewati: beda ejaan (nama database tetap)', $plan['spelling']],
            ['Dilewati: induk tidak ditemukan', count($plan['skipped'])],
        ]);
        foreach (array_slice($plan['skipped'], 0, 20) as $line) {
            $this->line("  - $line");
        }
        // Few code changes on an already-imported database: list them (all of them with -v)
        $kodeLog = $plan['kodeLog'] ?? [];
        if ($kodeLog && (count($kodeLog) <= 50 || $this->getOutput()->isVerbose())) {
            foreach ($kodeLog as $line) $this->line("  kode: $line");
        }
        foreach (array_merge($plan['disSpellLog'], $plan['vilSpellLog']) as $line) {
            $this->line("  ejaan: $line");
        }

        $total = count($plan['provInsert']) + count($plan['regRename']) + count($plan['regMove']) + count($plan['disRename'])
            + count($plan['disInsert']) + count($plan['vilMove']) + count($plan['vilInsert'])
            + count($plan['disSpell']) + count($plan['vilSpell']) + array_sum(array_map('count', $plan['kode']));
        if (!$total) {
            $this->info('Data wilayah sudah lengkap, tidak ada yang perlu ditambah.');
            return self::SUCCESS;
        }
        if (!$this->option('apply')) {
            $this->warn('Simulasi saja. Jalankan dengan --apply untuk menyimpan.');
            return self::SUCCESS;
        }

        $now = now();
        DB::transaction(function () use ($plan, $now) {
            foreach ($plan['provInsert'] as $row) {
                DB::table('area_provinces')->insert($row + ['created_at' => $now, 'updated_at' => $now]);
            }
            foreach ($plan['regRename'] as $id => $name) {
                DB::table('area_regencies')->where('id', $id)->update(['name' => $name, 'updated_at' => $now]);
            }
            foreach ($plan['regMove'] as $id => $provinceId) {
                DB::table('area_regencies')->where('id', $id)->update(['province_id' => $provinceId, 'updated_at' => $now]);
            }
            foreach ($plan['disRename'] as $id => $name) {
                DB::table('area_districts')->where('id', $id)->update(['name' => $name, 'updated_at' => $now]);
            }
            foreach (array_chunk($plan['disInsert'], 500) as $chunk) {
                DB::table('area_districts')->insert(array_map(fn($r) => $r + ['created_at' => $now, 'updated_at' => $now], $chunk));
            }
            foreach ($plan['vilMove'] as $id => $districtId) {
                DB::table('area_villages')->where('id', $id)->update(['district_id' => $districtId, 'updated_at' => $now]);
            }
            foreach (array_chunk($plan['vilInsert'], 1000) as $chunk) {
                DB::table('area_villages')->insert(array_map(fn($r) => $r + ['created_at' => $now, 'updated_at' => $now], $chunk));
            }
            foreach ($plan['disSpell'] as $id => $name) {
                DB::table('area_districts')->where('id', $id)->update(['name' => $name, 'updated_at' => $now]);
            }
            foreach ($plan['vilSpell'] as $id => $name) {
                DB::table('area_villages')->where('id', $id)->update(['name' => $name, 'updated_at' => $now]);
            }
            foreach ($plan['kode'] as $table => $codes) {
                $this->bulkSetCode($table, $codes, $now);
            }
        });

        $this->info("Selesai: $total perubahan disimpan.");
        return self::SUCCESS;
    }

    /** [level => [kode => nama]], level 1 provinsi .. 4 desa */
    private function readSource(string $file): array
    {
        $kd = [1 => [], 2 => [], 3 => [], 4 => []];
        $handle = fopen($file, 'r');
        fgetcsv($handle); // header
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 2 || !preg_match('/^[0-9.]+$/', $row[0])) continue;
            $kd[count(explode('.', $row[0]))][$row[0]] = trim($row[1]);
        }
        fclose($handle);

        return $kd;
    }

    private function norm(string $s): string
    {
        $s = strtoupper(trim($s));
        $s = preg_replace('/\bKEP\.\s*/', 'KEPULAUAN ', $s);
        $s = preg_replace('/\b(ADMINISTRASI|ADM\.?)\s+/', '', $s);
        $s = preg_replace('/\bKAB\.\s*/', 'KABUPATEN ', $s);

        return preg_replace('/[^A-Z0-9]/', '', $s);
    }

    /** Same name, or at most 1-2 letters apart (spelling variants). */
    private function similar(string $a, string $b): bool
    {
        $a = $this->norm($a);
        $b = $this->norm($b);
        if ($a === $b) return true;

        return levenshtein($a, $b) <= (max(strlen($a), strlen($b)) >= 8 ? 2 : 1);
    }

    private function upper(string $s): string
    {
        return mb_strtoupper(trim(preg_replace('/\s+/', ' ', str_replace('Kep. ', 'Kepulauan ', $s))));
    }

    /** New id under a parent with the old pattern: parent * 1000 + (largest suffix + 1). */
    private function nextId(string $table, int $parentId, Collection $existing): int
    {
        if (!isset($this->nextCache[$parentId])) {
            $this->nextCache[$parentId] = (int) $existing->max(fn($e) => $e->id % 1000);
        }
        // Skip ids already taken anywhere in the table: a row may sit under another parent than its id suggests
        do {
            if (++$this->nextCache[$parentId] > 999) {
                throw new \RuntimeException("Nomor ID penuh di bawah $parentId");
            }
            $id = $parentId * 1000 + $this->nextCache[$parentId];
        } while (isset($this->usedIds[$table][$id]));
        $this->usedIds[$table][$id] = true;

        return $id;
    }

    /** [table => [id => true]] every id that exists or is planned, so a new id never collides */
    private array $usedIds = [];

    private function buildPlan(array $kd): array
    {
        $plan = ['provInsert' => [], 'regRename' => [], 'regMove' => [], 'disRename' => [], 'disInsert' => [],
            'vilMove' => [], 'vilInsert' => [], 'spelling' => 0, 'skipped' => [],
            'disSpell' => [], 'vilSpell' => [], 'disSpellLog' => [], 'vilSpellLog' => [], 'kode' => []];
        // Column from migration 2026_11_14_000001; without it the import still runs, codes are skipped
        $this->hasKode = \Illuminate\Support\Facades\Schema::hasColumn('area_villages', 'kode_kemendagri');
        $this->codes = [];
        $this->nextCache = [];
        $this->usedIds = [
            'area_districts' => DB::table('area_districts')->pluck('id')->flip()->map(fn() => true)->all(),
            'area_villages' => DB::table('area_villages')->pluck('id')->flip()->map(fn() => true)->all(),
        ];

        // Kab/kota whose kecamatan / desa take the official spelling (only names that differ beyond case / spaces)
        $opt = (string) $this->option('ejaan-resmi');
        $official = $opt === 'solo' ? self::SOLO_RAYA : array_filter(array_map('trim', explode(',', $opt)));
        $official = array_flip($official);

        $prov = DB::table('area_provinces')->get()->keyBy('id');
        $reg = DB::table('area_regencies')->get()->keyBy('id');
        $dis = DB::table('area_districts')->orderBy('id')->get();
        $disByReg = $dis->groupBy('regency_id');
        // Ordered by id and with the stored code: twin names (kelurahan + desa with one name in one kecamatan,
        // e.g. Urimessing 1011 / 2005) keep the code they already have instead of swapping on every run
        $vilByDis = DB::table('area_villages')->orderBy('id')
            ->get($this->hasKode ? ['id', 'name', 'district_id', 'kode_kemendagri'] : ['id', 'name', 'district_id'])
            ->groupBy('district_id');

        // Provinsi
        $provMap = [];
        foreach ($kd[1] as $code => $name) {
            $hit = $prov->first(fn($p) => $this->norm($p->name) === $this->norm($name)
                || (self::PROV_ALIAS[$this->norm($p->name)] ?? null) === $this->norm($name));
            if ($hit) {
                $provMap[$code] = (int) $hit->id;
                $this->code('area_provinces', $hit->id, $code);
                continue;
            }
            $id = self::NEW_PROV_ID[$code] ?? (int) $code;
            if ($prov->has($id)) {
                $plan['skipped'][] = "Provinsi $name: ID $id sudah dipakai {$prov[$id]->name}";
                continue;
            }
            $provMap[$code] = $id;
            $plan['provInsert'][] = ['id' => $id, 'name' => $this->upper($name)];
            $this->code('area_provinces', $id, $code);
        }

        // Kab/kota: nama (kode BPS berbeda), lalu alias ganti nama
        $regMap = [];
        foreach ($kd[2] as $code => $name) {
            $hit = $reg->first(fn($r) => $this->norm($r->name) === $this->norm($name))
                ?? $reg->first(fn($r) => (self::REG_ALIAS[$this->norm($r->name)] ?? null) === $this->norm($name));
            if (!$hit) {
                $plan['skipped'][] = "Kab/kota $code $name tidak ditemukan (tidak dibuat otomatis)";
                continue;
            }
            $regMap[$code] = (int) $hit->id;
            $this->code('area_regencies', $hit->id, $code);
            if ($this->norm($hit->name) !== $this->norm($name)) {
                $plan['regRename'][$hit->id] = $this->upper($name);
            }
            $toProv = $provMap[substr($code, 0, 2)] ?? null;
            if ($toProv && $toProv !== (int) $hit->province_id) {
                $plan['regMove'][$hit->id] = $toProv;
            }
        }

        // Kecamatan: pass 1 by name, pass 2 by village overlap (renamed), else new
        $kdVillages = [];
        foreach ($kd[4] as $vc => $vn) {
            $kdVillages[substr($vc, 0, 8)][$this->norm($vn)] = true;
        }
        $disMap = [];
        $disTaken = [];
        foreach ($kd[3] as $code => $name) {
            $rid = $regMap[substr($code, 0, 5)] ?? null;
            if (!$rid) continue;
            $cands = $disByReg[$rid] ?? collect();
            $hit = $cands->first(fn($d) => !isset($disTaken[$d->id]) && $this->norm($d->name) === $this->norm($name))
                ?? $cands->first(fn($d) => !isset($disTaken[$d->id]) && $this->similar($d->name, $name));
            if ($hit) {
                $disMap[$code] = (int) $hit->id;
                $this->code('area_districts', $hit->id, $code);
                $disTaken[$hit->id] = true;
                if ($this->norm($hit->name) !== $this->norm($name)) {
                    if (isset($official[substr($code, 0, 5)])) {
                        $plan['disSpell'][$hit->id] = $this->upper($name);
                        $plan['disSpellLog'][] = "kecamatan {$hit->name} -> {$this->upper($name)} ({$hit->id})";
                    } else {
                        $plan['spelling']++;
                    }
                }
            }
        }
        foreach ($kd[3] as $code => $name) {
            if (isset($disMap[$code])) continue;
            $rid = $regMap[substr($code, 0, 5)] ?? null;
            if (!$rid) {
                $plan['skipped'][] = "Kecamatan $code $name: kab/kota induk tidak ditemukan";
                continue;
            }
            $cands = $disByReg[$rid] ?? collect();
            $mine = $kdVillages[$code] ?? [];
            $best = null;
            foreach ($cands as $d) {
                if (isset($disTaken[$d->id])) continue;
                $theirs = ($vilByDis[$d->id] ?? collect());
                if ($theirs->isEmpty()) continue;
                $overlap = $theirs->filter(fn($v) => isset($mine[$this->norm($v->name)]))->count() / max(count($mine), 1);
                if ($overlap >= 0.5 && (!$best || $overlap > $best[1])) $best = [$d, $overlap];
            }
            if ($best) {
                $disMap[$code] = (int) $best[0]->id;
                $this->code('area_districts', $best[0]->id, $code);
                $disTaken[$best[0]->id] = true;
                $plan['disRename'][$best[0]->id] = $this->upper($name);
                continue;
            }
            $id = $this->nextId('area_districts', $rid, $cands);
            $disMap[$code] = $id;
            $plan['disInsert'][] = ['id' => $id, 'name' => $this->upper($name), 'regency_id' => $rid];
            $this->code('area_districts', $id, $code);
        }

        // Desa: manual alias, exact, spelling, same name in another kecamatan (pemekaran), else new
        $matched = [];
        $missing = [];
        $allVillages = $vilByDis->flatten(1)->keyBy('id');
        foreach ($kd[4] as $code => $name) {
            $aliasId = self::VIL_ALIAS[$code] ?? null;
            $did = $disMap[substr($code, 0, 8)] ?? null;
            if ($aliasId && $did && ($old = $allVillages->get($aliasId))) {
                $matched[$aliasId] = true;
                $this->code('area_villages', $aliasId, $code);
                if ((int) $old->district_id !== $did) $plan['vilMove'][$aliasId] = $did;
                if ($this->norm($old->name) !== $this->norm($name)) {
                    $plan['vilSpell'][$aliasId] = $this->upper($name);
                    $plan['vilSpellLog'][] = "desa {$old->name} -> {$this->upper($name)} ({$aliasId}, alias)";
                }
                continue;
            }
        }
        foreach ($kd[4] as $code => $name) {
            if (isset(self::VIL_ALIAS[$code]) && isset($matched[self::VIL_ALIAS[$code]])) continue;
            $did = $disMap[substr($code, 0, 8)] ?? null;
            $pool = $did ? ($vilByDis[$did] ?? collect()) : collect();
            $hit = $pool->first(fn($v) => !isset($matched[$v->id]) && ($v->kode_kemendagri ?? null) === $code)
                ?? $pool->first(fn($v) => !isset($matched[$v->id]) && $this->norm($v->name) === $this->norm($name));
            if ($hit) {
                $matched[$hit->id] = true;
                $this->code('area_villages', $hit->id, $code);
            } else {
                $missing[$code] = $name;
            }
        }
        foreach ($missing as $code => $name) {
            $did = $disMap[substr($code, 0, 8)] ?? null;
            $hit = ($did ? ($vilByDis[$did] ?? collect()) : collect())
                ->first(fn($v) => !isset($matched[$v->id]) && $this->similar($v->name, $name));
            if ($hit) {
                $matched[$hit->id] = true;
                unset($missing[$code]);
                $this->code('area_villages', $hit->id, $code);
                if (isset($official[substr($code, 0, 5)])) {
                    $plan['vilSpell'][$hit->id] = $this->upper($name);
                    $plan['vilSpellLog'][] = "desa {$hit->name} -> {$this->upper($name)} ({$hit->id})";
                } else {
                    $plan['spelling']++;
                }
            }
        }
        foreach ($missing as $code => $name) {
            $did = $disMap[substr($code, 0, 8)] ?? null;
            $rid = $regMap[substr($code, 0, 5)] ?? null;
            if (!$did) {
                $plan['skipped'][] = "Desa $code $name: kecamatan induk tidak ditemukan";
                continue;
            }
            $moved = null;
            foreach (($disByReg[$rid] ?? []) as $d) {
                if ((int) $d->id === $did) continue;
                $moved = ($vilByDis[$d->id] ?? collect())
                    ->first(fn($v) => !isset($matched[$v->id]) && $this->norm($v->name) === $this->norm($name));
                if ($moved) break;
            }
            if ($moved) {
                $matched[$moved->id] = true;
                $this->code('area_villages', $moved->id, $code);
                $plan['vilMove'][$moved->id] = $did;
                continue;
            }
            $id = $this->nextId('area_villages', $did, $vilByDis[$did] ?? collect());
            $plan['vilInsert'][] = ['id' => $id, 'name' => $this->upper($name), 'district_id' => $did];
            $this->code('area_villages', $id, $code);
        }

        // Kode Kemendagri: new rows carry it in the insert, existing rows only when empty / different
        if ($this->hasKode) {
            foreach (['provInsert' => 'area_provinces', 'disInsert' => 'area_districts', 'vilInsert' => 'area_villages'] as $key => $table) {
                foreach ($plan[$key] as $i => $row) {
                    $plan[$key][$i]['kode_kemendagri'] = $this->codes[$table][$row['id']] ?? null;
                    unset($this->codes[$table][$row['id']]);
                }
            }
            foreach ($this->codes as $table => $codes) {
                $current = DB::table($table)->whereNotNull('kode_kemendagri')->pluck('kode_kemendagri', 'id');
                foreach ($codes as $id => $code) {
                    if (($current[$id] ?? null) !== $code) {
                        $plan['kode'][$table][$id] = $code;
                        $plan['kodeLog'][] = "$table $id: " . ($current[$id] ?? 'kosong') . " -> $code";
                    }
                }
            }
        }

        return $plan;
    }
}
