<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Read-only report of every user's roles, employee/position and doctor link, with the problems found.
 * Meant to be run on production (data differs from local); writes a CSV that is used to plan role cleanup.
 */
class AuditRoles extends Command
{
    protected $signature = 'roles:audit
        {--path= : Lokasi file CSV (default storage/app/roles_audit_YYYYmmdd_His.csv)}
        {--show : Tampilkan laporan per user di terminal (untuk screenshot)}
        {--all : Bersama --show, tampilkan juga user tanpa masalah}';

    protected $description = 'Laporan role per user (hanya membaca, tidak mengubah data)';

    private const LEVEL_ROLES = ['Employee', 'Manager', 'Head Manager', 'Ceo'];

    /** Role names whose spelling differs from the one the code expects. */
    private const NON_CANONICAL = ['CEO' => 'Ceo', 'RND' => 'Rnd', 'rnd' => 'Rnd', 'HRD' => 'Hrd'];

    public function handle(): int
    {
        $path = $this->option('path') ?: storage_path('app/roles_audit_' . now()->format('Ymd_His') . '.csv');

        $employees = DB::table('hrd_employee')
            ->whereNotNull('user_id')
            ->get(['id', 'user_id', 'nama', 'status'])
            ->keyBy('user_id');

        $positions = DB::table('hrd_employee_position as ep')
            ->join('hrd_position as p', 'p.id', '=', 'ep.position_id')
            ->leftJoin('hrd_division as d', 'd.id', '=', 'p.division_id')
            ->where('ep.is_primary', 1)
            ->get(['ep.employee_id', 'p.name as position', 'd.name as division'])
            ->keyBy('employee_id');

        $dokterUserIds = DB::table('erm_dokters')->whereNotNull('user_id')->pluck('user_id')->flip();

        $users = User::with('roles:id,name')->orderBy('name')->get(['id', 'name', 'email']);

        $rows = [];
        foreach ($users as $user) {
            $roles = $user->roles->pluck('name')->sort()->values();
            $employee = $employees[$user->id] ?? null;
            $position = $employee ? ($positions[$employee->id] ?? null) : null;
            $isDokter = isset($dokterUserIds[$user->id]);
            $issues = [];

            if ($roles->isEmpty()) {
                $issues[] = 'Tidak punya role';
            } else {
                $levels = $roles->intersect(self::LEVEL_ROLES);
                if ($levels->isEmpty() && !$isDokter) {
                    $issues[] = 'Tidak punya role level';
                } elseif ($levels->count() > 1) {
                    $issues[] = 'Role level ganda: ' . $levels->implode('/');
                }
                foreach ($roles as $role) {
                    if (isset(self::NON_CANONICAL[$role])) {
                        $issues[] = "Nama role '{$role}' seharusnya '" . self::NON_CANONICAL[$role] . "'";
                    }
                }
                if ($roles->contains('Admin')) {
                    $issues[] = 'Admin (cek: khusus IT?)';
                }
            }

            if (!$employee && !$isDokter) {
                $issues[] = 'Tidak terhubung ke karyawan/dokter';
            }
            if ($employee && strtolower((string) $employee->status) === 'tidak aktif' && $roles->isNotEmpty()) {
                $issues[] = 'Karyawan nonaktif tapi masih punya role';
            }
            if ($employee && !$position) {
                $issues[] = 'Karyawan tanpa posisi utama';
            }

            $rows[] = [
                $user->id,
                $user->name,
                $user->email,
                $roles->implode(', '),
                $employee ? ($employee->nama . ' (' . $employee->status . ')') : '',
                $position->position ?? '',
                $position->division ?? '',
                $isDokter ? 'ya' : '',
                implode('; ', $issues),
            ];
        }

        $header = ['user_id', 'nama', 'email', 'roles', 'karyawan', 'posisi_utama', 'divisi', 'dokter', 'masalah'];

        $handle = fopen($path, 'w');
        fwrite($handle, "\xEF\xBB\xBF"); // BOM so Excel opens the UTF-8 names correctly
        fputcsv($handle, $header);
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);

        // Summary: users per role, so unused / misspelled roles stand out
        $roleCounts = DB::table('roles')
            ->leftJoin('model_has_roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->groupBy('roles.id', 'roles.name')
            ->orderBy('roles.name')
            ->selectRaw('roles.name, COUNT(model_has_roles.model_id) as total')
            ->get()
            ->map(fn ($r) => [$r->name, $r->total])
            ->all();

        $this->table(['Role', 'Jumlah user'], $roleCounts);

        if ($this->option('show')) {
            // Narrow columns so the table fits a screenshot; the CSV keeps every field
            $shown = collect($rows)
                ->filter(fn ($r) => $this->option('all') || $r[8] !== '')
                ->map(fn ($r) => [
                    $r[1],
                    $r[2],
                    $r[3],
                    $r[5] ?: ($r[7] ? 'Dokter' : '-'),
                    str_replace('; ', "\n", $r[8]),
                ])
                ->values()
                ->all();

            $this->table(['Nama', 'Email', 'Roles', 'Posisi', 'Masalah'], $shown);
        }
        $this->info(count($rows) . ' user, ' . collect($rows)->filter(fn ($r) => $r[8] !== '')->count() . ' dengan catatan masalah.');
        $this->info("CSV: {$path}");

        return self::SUCCESS;
    }
}
