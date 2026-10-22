<?php

namespace App\Support\Marketing;

use App\Models\User;

/**
 * Who may run the marketing workflows. Every Marketing user can approve (agreed 2026-10-20);
 * each approval is recorded in the status log, so "who and when" stays visible.
 */
class MarketingAccess
{
    public const ROLES = ['Marketing', 'Admin'];

    public static function isMarketing(?User $user): bool
    {
        return $user !== null && $user->hasAnyRole(self::ROLES);
    }

    /** Users that can be assigned marketing work (content PIC, designer). */
    public static function users()
    {
        try {
            return User::role('Marketing')->orderBy('name')->get(['id', 'name']);
        } catch (\Throwable $e) {
            return User::orderBy('name')->get(['id', 'name']);
        }
    }
}
