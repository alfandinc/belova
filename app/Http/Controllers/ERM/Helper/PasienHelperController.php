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
            // Format hasil usia
            $usia = "$years tahun $months bulan $days hari";
        }

        // Find last visit date (excluding current visit)
        $lastVisit = Visitation::where('pasien_id', $pasien->id)
            ->where('id', '!=', $visitationId)
            ->latest('tanggal_visitation')
            ->first();
        
        $lastVisitDate = $lastVisit ? Carbon::parse($lastVisit->tanggal_visitation)->translatedFormat('d F Y') : '-';

        $zatAktif = ZatAktif::all();
        $alergiPasien = Alergi::where('pasien_id', $pasien->id)
            ->with('zataktif') // Eager load zataktif relationship
            ->get();

        // Extract zataktif IDs and names
        $alergiNames = $alergiPasien->pluck('zataktif.nama')->toArray();
        $alergiIds = $alergiPasien->pluck('zataktif_id')->toArray();

        $firstAlergi = $alergiPasien->first();
        $alergistatus = $firstAlergi?->status ?? null;
        $alergikatakunci = $firstAlergi?->katakunci ?? null;

        return [
            'pasien' => $visitation->pasien,
            'usia' => $usia,
            'alergiNames' => $alergiNames,
            'alergiIds' => $alergiIds,
            'zatAktif' => $zatAktif,
            'alergistatus' => $alergistatus,
            'alergikatakunci' => $alergikatakunci,
            'lastVisitDate' => $lastVisitDate,
            // 'alergiPasien' => $alergiPasien,
        ];
    }
}