<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ERM\Helper\KunjunganHelperController;
use App\Http\Controllers\ERM\Helper\PasienHelperController;
use App\Models\ERM\Slimming;
use App\Models\ERM\Visitation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class SlimmingController extends Controller
{
    public function create($visitationId)
    {
        $visitation = Visitation::with([
            'pasien.village.district.regency.province',
            'dokter.user',
            'riwayatTindakan.tindakan',
        ])->findOrFail($visitationId);

        $pasienData = PasienHelperController::getDataPasien($visitationId);
        $createKunjunganData = KunjunganHelperController::getCreateKunjungan($visitationId);
        $riwayatTindakanOptions = $visitation->riwayatTindakan()
            ->with('tindakan')
            ->whereHas('tindakan', function ($query) {
                $query->where('is_slimming', true);
            })
            ->orderByDesc('tanggal_tindakan')
            ->get();

        return view('erm.slimming.create', array_merge([
            'visitation' => $visitation,
            'riwayatTindakanOptions' => $riwayatTindakanOptions,
        ], $pasienData, $createKunjunganData));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'visitation_id' => ['required', 'string', 'exists:erm_visitations,id'],
            'riwayat_tindakan_id' => ['required', 'integer', 'exists:erm_riwayat_tindakan,id'],
            'tb' => ['nullable', 'numeric'],
            'bb' => ['nullable', 'numeric'],
            'target_weight' => ['nullable', 'numeric'],
            'weight_control' => ['nullable', 'numeric'],
            'lingkar_perut' => ['nullable', 'numeric'],
            'lingkar_lengan_kanan' => ['nullable', 'numeric'],
            'lingkar_lengan_kiri' => ['nullable', 'numeric'],
            'muscle_fat_weight' => ['nullable', 'numeric'],
            'muscle_fat_muscle' => ['nullable', 'numeric'],
            'muscle_fat_body_fat_mass' => ['nullable', 'numeric'],
            'obesity_bmi' => ['nullable', 'numeric'],
            'obesity_analysis' => ['nullable', 'string', 'max:255'],
            'obesity_eval_bmi' => ['nullable', 'numeric'],
            'obesity_eval' => ['nullable', 'string', 'max:255'],
            'pbf' => ['nullable', 'numeric'],
            'subcutaneous_fat' => ['nullable', 'string', 'max:255'],
            'subcutaneous_whole_body' => ['nullable', 'numeric'],
            'subcutaneous_trunk' => ['nullable', 'numeric'],
            'subcutaneous_arms' => ['nullable', 'numeric'],
            'subcutaneous_legs' => ['nullable', 'numeric'],
            'skeletal_muscle' => ['nullable', 'string', 'max:255'],
            'skeletal_whole_body' => ['nullable', 'numeric'],
            'skeletal_trunk' => ['nullable', 'numeric'],
            'skeletal_arms' => ['nullable', 'numeric'],
            'skeletal_legs' => ['nullable', 'numeric'],
            'research_basal_metabolic_rate' => ['nullable', 'numeric'],
            'visceral_fat_level' => ['nullable', 'numeric'],
        ]);

        $visitation = Visitation::findOrFail($data['visitation_id']);

        $ownsRiwayat = $visitation->riwayatTindakan()
            ->whereKey($data['riwayat_tindakan_id'])
            ->whereHas('tindakan', function ($query) {
                $query->where('is_slimming', true);
            })
            ->exists();

        if (!$ownsRiwayat) {
            return back()
                ->withErrors(['riwayat_tindakan_id' => 'Riwayat tindakan slimming tidak cocok dengan visitation ini.'])
                ->withInput();
        }

        $pasien = $visitation->pasien;
        $bmi = $this->calculateBmi($data['tb'] ?? null, $data['bb'] ?? null);
        $pbf = $this->calculatePbf($bmi, $pasien?->tanggal_lahir, $pasien?->gender);
        $bmiStatus = $this->classifyBmiStatus($bmi);
        $pbfStatus = $this->classifyPbfStatus($pbf, $pasien?->gender);

        Slimming::create(array_merge($data, [
            'pasien_id' => $visitation->pasien_id,
            'dokter_id' => $visitation->dokter_id,
            'obesity_bmi' => $bmi,
            'obesity_eval_bmi' => $bmi,
            'pbf' => $pbf,
            'obesity_eval' => $this->buildObesityEval($bmiStatus, $pbfStatus),
        ]));

        return redirect()
            ->route('erm.slimming.create', $visitation->id)
            ->with('success', 'Data slimming berhasil disimpan.');
    }

    public function data($visitationId)
    {
        $visitation = Visitation::findOrFail($visitationId);

        $records = Slimming::with(['visitation'])
            ->where('pasien_id', $visitation->pasien_id)
            ->get()
            ->sortBy(function (Slimming $slimming) {
                return ($slimming->visitation->tanggal_visitation ?? '') . ' ' . ($slimming->created_at ?? '');
            })
            ->values()
            ->map(function (Slimming $slimming) {
                return [
                    'visitation_date' => $slimming->visitation->tanggal_visitation ?? '-',
                    'weight' => $slimming->bb,
                    'muscle_mass' => $slimming->muscle_fat_muscle,
                    'body_fat' => $slimming->muscle_fat_body_fat_mass,
                ];
            });

        return response()->json([
            'records' => $records,
        ]);
    }

    private function calculateBmi($heightCm, $weightKg): ?float
    {
        if (!is_numeric($heightCm) || !is_numeric($weightKg) || $heightCm <= 0 || $weightKg <= 0) {
            return null;
        }

        $heightMeters = $heightCm / 100;

        if ($heightMeters <= 0) {
            return null;
        }

        return round($weightKg / ($heightMeters * $heightMeters), 2);
    }

    private function calculatePbf(?float $bmi, $birthDate, ?string $gender): ?float
    {
        $age = $this->calculateAge($birthDate);
        $genderFactor = $this->genderFactor($gender);

        if ($bmi === null || $age === null || $genderFactor === null) {
            return null;
        }

        return round((1.20 * $bmi) + (0.23 * $age) - (10.8 * $genderFactor) - 5.4, 2);
    }

    private function calculateAge($birthDate): ?int
    {
        if (empty($birthDate)) {
            return null;
        }

        try {
            return Carbon::parse($birthDate)->age;
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function genderFactor(?string $gender): ?int
    {
        if (!$gender) {
            return null;
        }

        $normalized = strtolower(trim($gender));

        if (in_array($normalized, ['l', 'male', 'man', 'pria', 'laki-laki', 'lakilaki'], true)) {
            return 1;
        }

        if (in_array($normalized, ['p', 'f', 'female', 'woman', 'wanita', 'perempuan'], true)) {
            return 0;
        }

        return null;
    }

    private function classifyBmiStatus(?float $bmi): ?string
    {
        if ($bmi === null) {
            return null;
        }

        if ($bmi < 18.5) {
            return 'under';
        }

        if ($bmi <= 25) {
            return 'normal';
        }

        return 'over';
    }

    private function classifyPbfStatus(?float $pbf, ?string $gender): ?string
    {
        if ($pbf === null) {
            return null;
        }

        $genderFactor = $this->genderFactor($gender);

        if ($genderFactor === 1) {
            if ($pbf < 10) {
                return 'under';
            }

            if ($pbf <= 20) {
                return 'normal';
            }

            return 'over';
        }

        if ($genderFactor === 0) {
            if ($pbf < 18) {
                return 'under';
            }

            if ($pbf <= 28) {
                return 'normal';
            }

            return 'over';
        }

        if ($pbf < 18) {
            return 'under';
        }

        if ($pbf <= 28) {
            return 'normal';
        }

        return 'over';
    }

    private function buildObesityEval(?string $bmiStatus, ?string $pbfStatus): ?string
    {
        $parts = [];

        if ($bmiStatus) {
            $parts[] = 'BMI:' . $bmiStatus;
        }

        if ($pbfStatus) {
            $parts[] = 'PBF:' . $pbfStatus;
        }

        return empty($parts) ? null : implode(';', $parts);
    }
}