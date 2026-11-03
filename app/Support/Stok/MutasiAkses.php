<?php

namespace App\Support\Stok;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Who may request and approve stok documents, per role per gudang (Admin Panel > Akses Mutasi Stok).
 *  - Penyesuaian Stok: both checked on the document's gudang.
 *  - Transfer Antar Gudang: request when the role may request for the gudang asal OR tujuan;
 *    approve when the role may approve for the gudang asal (the gudang the stok leaves).
 * Admin may always do everything. Before the tables exist the old hard-coded rules apply.
 */
class MutasiAkses
{
    public const PENYESUAIAN = 'penyesuaian';
    public const MUTASI_GUDANG = 'mutasi_gudang';

    public const MODUL_LABELS = [
        self::PENYESUAIAN => 'Penyesuaian Stok',
        self::MUTASI_GUDANG => 'Transfer Antar Gudang',
    ];

    // Roles that see both menus (route middleware) besides Admin
    public const ROLES = ['Farmasi', 'Beautician', 'Lab', 'Marketing'];

    // The rules before the mapping page: [can_request, can_approve] for every gudang
    private const FALLBACK = [
        self::PENYESUAIAN => ['Farmasi' => [true, true], 'Beautician' => [true, true], 'Lab' => [true, false], 'Marketing' => [true, true]],
        self::MUTASI_GUDANG => ['Farmasi' => [true, true], 'Beautician' => [true, true], 'Lab' => [true, true], 'Marketing' => [true, true]],
    ];

    private static ?array $rules = null;
    private static ?array $modul = null;

    public static function migrated(): bool
    {
        return Schema::hasTable('stok_mutasi_akses') && Schema::hasTable('stok_mutasi_akses_modul');
    }

    /** [modul][role][gudang_id] => ['request' => bool, 'approve' => bool]; null before the migration */
    public static function rules(): ?array
    {
        if (self::$rules === null && self::migrated()) {
            self::$rules = [];
            foreach (DB::table('stok_mutasi_akses')->get() as $row) {
                self::$rules[$row->modul][$row->role][(int) $row->gudang_id] = ['request' => (bool) $row->can_request, 'approve' => (bool) $row->can_approve];
            }
        }

        return self::$rules;
    }

    public static function forget(): void
    {
        self::$rules = null;
        self::$modul = null;
    }

    public static function bolehApproveSendiri(string $modul): bool
    {
        if (self::$modul === null) {
            self::$modul = self::migrated()
                ? DB::table('stok_mutasi_akses_modul')->pluck('boleh_approve_sendiri', 'modul')->map(fn ($v) => (bool) $v)->all()
                : [self::PENYESUAIAN => (bool) config('stok.penyesuaian.boleh_approve_sendiri', true), self::MUTASI_GUDANG => true];
        }

        return self::$modul[$modul] ?? true;
    }

    public static function canRequest(string $modul, $gudangId, $user = null): bool
    {
        return self::allows($modul, 'request', $gudangId, $user);
    }

    public static function canApprove(string $modul, $gudangId, $user = null): bool
    {
        return self::allows($modul, 'approve', $gudangId, $user);
    }

    /** Gudang ids the user may request/approve for; null = every gudang (Admin) */
    public static function gudangIds(string $modul, string $ability, $user = null): ?array
    {
        $user = $user ?: Auth::user();
        if (!$user) {
            return [];
        }
        if ($user->hasRole('Admin')) {
            return null;
        }

        $rules = self::rules();
        $ids = [];
        foreach ($user->getRoleNames() as $role) {
            if ($rules === null) {
                if (self::FALLBACK[$modul][$role][$ability === 'request' ? 0 : 1] ?? false) {
                    return null;
                }
                continue;
            }
            foreach ($rules[$modul][$role] ?? [] as $gudangId => $rule) {
                if ($rule[$ability]) {
                    $ids[$gudangId] = $gudangId;
                }
            }
        }

        return array_values($ids);
    }

    public static function canAny(string $modul, string $ability, $user = null): bool
    {
        $ids = self::gudangIds($modul, $ability, $user);

        return $ids === null || count($ids) > 0;
    }

    private static function allows(string $modul, string $ability, $gudangId, $user): bool
    {
        $ids = self::gudangIds($modul, $ability, $user);

        return $ids === null || in_array((int) $gudangId, $ids, true);
    }
}
