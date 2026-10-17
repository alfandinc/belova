<?php

namespace App\Console\Commands;

use App\Models\ERM\Klinik;
use App\Models\ERM\Pasien;
use App\Models\ERM\Visitation;
use App\Models\Marketing\FollowUp;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Fills marketing_follow_ups.klinik_id for follow ups made before it existed. Only empty rows are touched,
 * so it can be run again and never overwrites a klinik set by hand. Rules, first match wins:
 *   1. visit registered from the follow up (Daftarkan, referral "Follow Up") -> that visit's klinik
 *   2. the pasien has only ever visited one klinik                            -> that klinik
 *   3. the pasien's visit closest before the follow up (within --before days),
 *      else closest after it (within --after days)                           -> that visit's klinik
 *   4. nothing fits                                                          -> stays empty ("Belum ditentukan")
 * Cancelled visits are ignored.
 */
class BackfillFollowUpKlinik extends Command
{
    protected $signature = 'followup:backfill-klinik {--dry-run : Report only, write nothing} {--before=30} {--after=14} {--list : Print every row and its rule}';

    protected $description = 'Fill the klinik of old marketing follow ups from the pasien\'s visits';

    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');
        $before = (int) $this->option('before');
        $after = (int) $this->option('after');
        $kliniks = Klinik::pluck('nama', 'id');
        $rules = ['1 kunjungan dari follow up' => 0, '2 hanya 1 klinik' => 0, '3 kunjungan terdekat' => 0, '4 tidak bisa ditentukan' => 0];
        $perKlinik = [];
        $listed = [];

        $visits = fn ($pasienId) => Visitation::where('pasien_id', $pasienId)
            ->where(fn ($q) => $q->whereNull('status_kunjungan')->orWhere('status_kunjungan', '!=', Visitation::STATUS_DIBATALKAN))
            ->whereNotNull('klinik_id');

        FollowUp::whereNull('klinik_id')->orderBy('id')->chunkById(500, function ($followUps) use ($dryRun, $before, $after, $kliniks, $visits, &$rules, &$perKlinik, &$listed) {
            foreach ($followUps as $fu) {
                $date = Carbon::parse($fu->created_at)->toDateString();

                $klinikId = Visitation::where('referral_type', Pasien::REFERRAL_TYPE_FOLLOW_UP)
                    ->where('referralable_id', (string) $fu->id)
                    ->orderByDesc('tanggal_visitation')
                    ->value('klinik_id');
                $rule = '1 kunjungan dari follow up';

                if (!$klinikId) {
                    $distinct = $visits($fu->pasien_id)->distinct()->pluck('klinik_id');
                    if ($distinct->count() === 1) {
                        $klinikId = $distinct->first();
                        $rule = '2 hanya 1 klinik';
                    }
                }

                if (!$klinikId) {
                    $klinikId = $visits($fu->pasien_id)
                        ->whereBetween('tanggal_visitation', [Carbon::parse($date)->subDays($before)->toDateString(), $date . ' 23:59:59'])
                        ->orderByDesc('tanggal_visitation')
                        ->value('klinik_id')
                        ?: $visits($fu->pasien_id)
                            ->whereBetween('tanggal_visitation', [Carbon::parse($date)->addDay()->toDateString(), Carbon::parse($date)->addDays($after)->toDateString() . ' 23:59:59'])
                            ->orderBy('tanggal_visitation')
                            ->value('klinik_id');
                    $rule = '3 kunjungan terdekat';
                }

                if (!$klinikId) {
                    $rule = '4 tidak bisa ditentukan';
                }

                $rules[$rule]++;
                if ($klinikId) {
                    $nama = $kliniks[$klinikId] ?? "#$klinikId";
                    $perKlinik[$nama] = ($perKlinik[$nama] ?? 0) + 1;
                    if (!$dryRun) {
                        FollowUp::whereKey($fu->id)->whereNull('klinik_id')->update(['klinik_id' => $klinikId]);
                    }
                }
                if ($this->option('list')) {
                    $listed[] = [$fu->id, $date, $fu->pasien_id, $klinikId ? ($kliniks[$klinikId] ?? $klinikId) : '-', $rule];
                }
            }
        });

        if ($listed) {
            $this->table(['FU id', 'Tanggal', 'Pasien', 'Klinik', 'Aturan'], $listed);
        }
        $this->table(['Aturan', 'Jumlah'], collect($rules)->map(fn ($n, $r) => [$r, $n])->values()->all());
        $this->table(['Klinik', 'Jumlah'], collect($perKlinik)->map(fn ($n, $k) => [$k, $n])->values()->all());
        $this->info($dryRun ? 'Dry run: nothing written.' : 'Done. Rows left empty show as "Belum ditentukan" on the Follow Up tab.');

        return 0;
    }
}
