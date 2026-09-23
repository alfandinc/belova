<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ERM\Helper\KunjunganHelperController;
use App\Http\Controllers\ERM\Helper\PasienHelperController;
use App\Models\ERM\Slimming;
use App\Models\ERM\Tindakan;
use App\Models\ERM\Visitation;
use Carbon\Carbon;
use Illuminate\Http\Request;

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

        $spesialisasiId = optional($visitation->dokter)->spesialisasi_id;
        if (!$spesialisasiId) {
            $spesialisasiId = \App\Models\ERM\Spesialisasi::where('nama', 'Umum')->value('id');
        }

        $umumId = \App\Models\ERM\Spesialisasi::where('nama', 'Umum')->value('id');
        $availableSlimmingTindakanQuery = Tindakan::query()
            ->where('is_active', true)
            ->where('is_slimming', true);

        if ($umumId) {
            $availableSlimmingTindakanQuery->where(function ($query) use ($spesialisasiId, $umumId) {
                $query->where('spesialis_id', $spesialisasiId)
                    ->orWhere('spesialis_id', $umumId);
            })->orderByRaw(
                "CASE WHEN spesialis_id = ? THEN 0 WHEN spesialis_id = ? THEN 1 ELSE 2 END",
                [$spesialisasiId, $umumId]
            );
        } elseif ($spesialisasiId) {
            $availableSlimmingTindakanQuery->where('spesialis_id', $spesialisasiId)
                ->orderByRaw("CASE WHEN spesialis_id = ? THEN 0 ELSE 1 END", [$spesialisasiId]);
        }

        $availableSlimmingTindakan = $availableSlimmingTindakanQuery
            ->orderBy('nama')
            ->get();

        return view('erm.slimming.create', array_merge([
            'visitation' => $visitation,
            'riwayatTindakanOptions' => $riwayatTindakanOptions,
            'availableSlimmingTindakan' => $availableSlimmingTindakan,
        ], $pasienData, $createKunjunganData));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'visitation_id' => ['required', 'string', 'exists:erm_visitations,id'],
            'usia' => ['nullable', 'integer', 'min:0'],
            'tb' => ['nullable', 'numeric'],
            'bb' => ['nullable', 'numeric'],
            'base_weight' => ['nullable', 'numeric'],
            'base_fat' => ['nullable', 'numeric'],
            'base_visceral_fat' => ['nullable', 'numeric'],
            'base_kcal' => ['nullable', 'numeric'],
            'base_bmi' => ['nullable', 'numeric'],
            'base_body_age' => ['nullable', 'integer', 'min:0'],
            'lingkar_perut' => ['nullable', 'numeric'],
            'lingkar_lengan_kanan' => ['nullable', 'numeric'],
            'lingkar_lengan_kiri' => ['nullable', 'numeric'],
            'lingkar_paha_kanan' => ['nullable', 'numeric'],
            'lingkar_paha_kiri' => ['nullable', 'numeric'],
            'subcutaneous_whole_body' => ['nullable', 'numeric'],
            'subcutaneous_trunk' => ['nullable', 'numeric'],
            'subcutaneous_arms' => ['nullable', 'numeric'],
            'subcutaneous_legs' => ['nullable', 'numeric'],
            'skeletal_whole_body' => ['nullable', 'numeric'],
            'skeletal_trunk' => ['nullable', 'numeric'],
            'skeletal_arms' => ['nullable', 'numeric'],
            'skeletal_legs' => ['nullable', 'numeric'],
        ]);

        $visitation = Visitation::findOrFail($data['visitation_id']);
        $pasien = $visitation->pasien;
        $usia = $data['usia'] ?? $this->calculateAge($pasien?->tanggal_lahir);
        $baseWeight = $data['base_weight'] ?? $data['bb'] ?? null;
        $baseBmi = $data['base_bmi'] ?? $this->calculateBmi($data['tb'] ?? null, $baseWeight);
        $baseBodyAge = $data['base_body_age'] ?? $usia;

        Slimming::create(array_merge($data, [
            'pasien_id' => $visitation->pasien_id,
            'dokter_id' => $visitation->dokter_id,
            'usia' => $usia,
            'base_weight' => $baseWeight,
            'base_bmi' => $baseBmi,
            'base_body_age' => $baseBodyAge,
        ]));

        return redirect()
            ->route('erm.slimming.create', $visitation->id)
            ->with('success', 'Data slimming berhasil disimpan.');
    }

    public function data($visitationId)
    {
        $visitation = Visitation::findOrFail($visitationId);

        $records = Slimming::with(['visitation', 'dokter.user'])
            ->where('pasien_id', $visitation->pasien_id)
            ->orderByDesc('created_at')
            ->get()
            ->values()
            ->map(function (Slimming $slimming) {
                return [
                    'visitation_id' => $slimming->visitation_id,
                    'visitation_date' => $slimming->visitation->tanggal_visitation ?? '-',
                    'dokter_name' => $slimming->dokter && $slimming->dokter->user
                        ? ($slimming->dokter->user->name ?? $slimming->dokter_id)
                        : ($slimming->dokter_id ?? '-'),
                    'tb' => $slimming->tb,
                    'bb' => $slimming->bb,
                    'base_weight' => $slimming->base_weight,
                    'base_kcal' => $slimming->base_kcal,
                    'base_bmi' => $slimming->base_bmi,
                    'base_fat' => $slimming->base_fat,
                    'base_visceral_fat' => $slimming->base_visceral_fat,
                    'base_body_age' => $slimming->base_body_age,
                    'lingkar_perut' => $slimming->lingkar_perut,
                    'lingkar_lengan_kanan' => $slimming->lingkar_lengan_kanan,
                    'lingkar_lengan_kiri' => $slimming->lingkar_lengan_kiri,
                    'lingkar_paha_kanan' => $slimming->lingkar_paha_kanan,
                    'lingkar_paha_kiri' => $slimming->lingkar_paha_kiri,
                    'subcutaneous_whole_body' => $slimming->subcutaneous_whole_body,
                    'subcutaneous_trunk' => $slimming->subcutaneous_trunk,
                    'subcutaneous_arms' => $slimming->subcutaneous_arms,
                    'subcutaneous_legs' => $slimming->subcutaneous_legs,
                    'skeletal_whole_body' => $slimming->skeletal_whole_body,
                    'skeletal_trunk' => $slimming->skeletal_trunk,
                    'skeletal_arms' => $slimming->skeletal_arms,
                    'skeletal_legs' => $slimming->skeletal_legs,
                    'created_at' => optional($slimming->created_at)->format('d/m/Y H:i'),
                ];
            });

        $latestRecord = $records->first();

        return response()->json([
            'records' => $records,
            'summary' => [
                'tb' => $latestRecord['tb'] ?? null,
                'base_weight' => $latestRecord['base_weight'] ?? null,
                'base_bmi' => $latestRecord['base_bmi'] ?? null,
                'base_body_age' => $latestRecord['base_body_age'] ?? null,
                'base_fat' => $latestRecord['base_fat'] ?? null,
                'base_visceral_fat' => $latestRecord['base_visceral_fat'] ?? null,
                'subcutaneous_whole_body' => $latestRecord['subcutaneous_whole_body'] ?? null,
                'skeletal_whole_body' => $latestRecord['skeletal_whole_body'] ?? null,
            ],
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
}