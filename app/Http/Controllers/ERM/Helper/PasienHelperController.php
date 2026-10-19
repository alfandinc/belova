<?php

namespace App\Http\Controllers\ERM\Helper;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Carbon\Carbon;
use App\Models\ERM\Visitation;
use App\Models\ERM\Alergi;
use App\Models\ERM\ZatAktif;

class PasienHelperController
{
    public static function getDataPasien($visitationId)
    {
        $visitation = Visitation::findOrFail($visitationId);
        $pasien = $visitation->pasien;

        $tanggal_lahir = $pasien->tanggal_lahir;
        $usia = '-';
        $usiaLengkap = '-';

        if ($tanggal_lahir) {
            $dob = Carbon::parse($tanggal_lahir);
            $now = Carbon::now();
            // Menghitung tahun
            $years = $dob->diffInYears($now);
            // Menghitung bulan
            $dob = $dob->addYears($years);
            $months = $dob->diffInMonths($now);
            // Menghitung hari
            $dob = $dob->addMonths($months);
            $days = $dob->diffInDays($now);
            // Mengubah semua hasil ke format integer untuk menghindari desimal
            $years = (int) $years;
            $months = (int) $months;
            $days = (int) $days;
            $usiaLengkap = "$years tahun $months bulan $days hari";
            // Short form: months / days only matter for small children
            $usia = match (true) {
                $years >= 2 => "$years tahun",
                $years === 1 => $months ? "1 tahun $months bulan" : '1 tahun',
                $months > 0 => "$months bulan $days hari",
                default => "$days hari",
            };
        }

        // Last completed clinical visit before this one (Konsultasi / Slimming / Vaksin / MCU; not a product purchase).
        // Raw Y-m-d: a translated date ("05 Januari 2026") can't be parsed back by Carbon.
        $currentDate = $visitation->tanggal_visitation;
        $previousVisits = Visitation::where('pasien_id', $pasien->id)
            ->where('id', '!=', $visitationId)
            ->where('status_kunjungan', 2)
            ->whereIn('jenis_kunjungan', Visitation::CLINICAL_TYPES)
            ->whereDate('tanggal_visitation', '<=', $currentDate);
        $lastVisit = (clone $previousVisits)->latest('tanggal_visitation')->first(['tanggal_visitation']);
        $lastVisitDate = $lastVisit ? Carbon::parse($lastVisit->tanggal_visitation)->toDateString() : null;
        // This visit's number among the patient's clinical visits (1 = first visit)
        $visitNumber = (clone $previousVisits)->count() + 1;

        // Find last lab for this patient (from all visitations)
        $lastLab = null;
        $lastLabVisitDate = null;
        $lastLabPermintaanList = [];
        $allVisitationIds = $pasien->visitations()->pluck('id');
        if ($allVisitationIds->count() > 0) {
            $lastLab = \App\Models\ERM\LabPermintaan::whereIn('visitation_id', $allVisitationIds)
                ->latest('created_at')
                ->with('labTest')
                ->first();
            if ($lastLab) {
                // Get the visitation related to this lab permintaan
                $labVisitation = \App\Models\ERM\Visitation::find($lastLab->visitation_id);
                $lastLabVisitDate = $labVisitation ? $labVisitation->tanggal_visitation : null;
                // Get all permintaan lab for that visit
                if ($labVisitation) {
                    $lastLabPermintaanList = $labVisitation->labPermintaan()->with('labTest')->get();
                }
            }
        }

        $alergiPasien = Alergi::where('pasien_id', $pasien->id)
            ->with('zataktif') // Eager load zataktif relationship
            ->get();
        // Only the patient's own zat aktif go in the allergy picker; the rest is searched (AlergiController::searchZatAktif)
        $zatAktif = $alergiPasien->pluck('zataktif')->filter()->unique('id')->values();

        // Extract zataktif IDs and names (rows with only a kata kunci have no zat aktif name)
        $alergiNames = $alergiPasien->pluck('zataktif.nama')->filter()->values()->toArray();
        $alergiIds = $alergiPasien->pluck('zataktif_id')->toArray();

        $firstAlergi = $alergiPasien->first();
        $alergistatus = $firstAlergi?->status ?? null;
        $alergikatakunci = $firstAlergi?->katakunci ?? null;

        // Pass the raw alergi rows for blade logic
        $alergiList = $alergiPasien;

        return [
            'pasien' => $visitation->pasien,
            'usia' => $usia,
            'usiaLengkap' => $usiaLengkap,
            'visitNumber' => $visitNumber,
            'alergiNames' => $alergiNames,
            'alergiIds' => $alergiIds,
            'zatAktif' => $zatAktif,
            'alergistatus' => $alergistatus,
            'alergikatakunci' => $alergikatakunci,
            'lastVisitDate' => $lastVisitDate,
            'alergiList' => $alergiList,
            'lastLab' => $lastLab,
            'lastLabVisitDate' => $lastLabVisitDate,
            'lastLabPermintaanList' => $lastLabPermintaanList,        ];
    }
}