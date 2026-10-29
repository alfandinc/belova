<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

/**
 * Popup notifications from a dokter to the Perawat, Pendaftaran and Farmasi staff (rujukan, permintaan lab).
 * Messages are one "Label: value" per line; the popups render lines when the data has multiline = true.
 */
class ErmStaffNotifications
{
    public const ROLES = ['Perawat', 'Pendaftaran', 'Farmasi'];

    /** Everyone in ROLES (plus $extraRoles, e.g. Lab for a permintaan lab) except the sender. */
    public static function send(Notification $notification, array $extraRoles = []): void
    {
        $users = User::role(array_merge(self::ROLES, $extraRoles))->where('id', '!=', Auth::id())->get();
        foreach ($users as $user) {
            $user->notify($notification);
        }
    }

    /** "NAMA (RM)" */
    public static function pasienLabel($pasien): string
    {
        return trim((optional($pasien)->nama ?: '-') . ($pasien ? ' (' . $pasien->id . ')' : ''));
    }

    /** "dr. Nama — Spesialisasi", spesialisasi as set for the given klinik */
    public static function dokterLabel($dokter, $klinikId, $fallbackName = '-'): string
    {
        $name = optional(optional($dokter)->user)->name ?: $fallbackName;
        $spesialisasi = $dokter ? optional($dokter->spesialisasiForKlinik($klinikId))->nama : null;

        return $name . ($spesialisasi ? ' — ' . $spesialisasi : '');
    }

    /** Message for a permintaan penunjang (lab / radiologi) the logged-in dokter just added. */
    public static function permintaanMessage($visitationId, array $items): string
    {
        $visitation = \App\Models\ERM\Visitation::with('pasien:id,nama')->find($visitationId);
        $dokter = \App\Models\ERM\Dokter::with(['user:id,name', 'spesialisasi:id,nama'])->where('user_id', Auth::id())->first();

        return implode("\n", [
            'Pasien: ' . self::pasienLabel(optional($visitation)->pasien),
            'Dokter: ' . self::dokterLabel($dokter, optional($visitation)->klinik_id, optional(Auth::user())->name),
            'Pemeriksaan: ' . implode(', ', array_unique($items)),
            'Tanggal kunjungan: ' . self::tanggal(optional($visitation)->tanggal_visitation),
        ]);
    }

    public static function tanggal($date): string
    {
        return $date ? \Carbon\Carbon::parse($date)->format('d/m/Y') : '-';
    }
}
