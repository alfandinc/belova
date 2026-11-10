<?php

namespace App\Support;

use App\Models\ERM\Icd10;
use Illuminate\Support\Facades\DB;

/**
 * Reading ICD-10 codes out of the diagnosa kerja fields ("K02.1 - Caries of dentine").
 * Strict on purpose: a diagnosis is only coded when its leading code exists in the master,
 * never by guessing from the text (that could send a wrong diagnosis to SatuSehat).
 */
class Icd10Code
{
    public const FHIR_SYSTEM = 'http://hl7.org/fhir/sid/icd-10';

    /** ICD-10 coded diagnosa kerja fields; diagnosakerja_6 is the free-text "non ICD-10" one. */
    public const DIAGNOSA_FIELDS = ['diagnosakerja_1', 'diagnosakerja_2', 'diagnosakerja_3', 'diagnosakerja_4', 'diagnosakerja_5'];

    /** "K02.1 - Caries..." / "k02.1" / "K021" → "K02.1"; null when the text does not start with a code. */
    public static function extract(?string $text): ?string
    {
        $text = strtoupper(trim((string) $text));
        if (!preg_match('/^([A-Z])(\d{2})(?:\.?(\d{1,2}))?(?=$|[\s\-–:,;(])/', $text, $m)) {
            return null;
        }

        return $m[1] . $m[2] . (isset($m[3]) && $m[3] !== '' ? '.' . $m[3] : '');
    }

    /** The master row for the leading code of a diagnosis text, or null. */
    public static function find(?string $text): ?Icd10
    {
        $code = self::extract($text);

        return $code ? Icd10::where('code', $code)->first() : null;
    }

    /** A 3-character category that has 4-character subcategories must not be used as a diagnosis. */
    public static function isCategoryWithSubcodes(string $code): bool
    {
        return strlen($code) === 3 && Icd10::where('code', 'like', $code . '.%')->exists();
    }

    /** Of the given codes, the 3-character ones that have subcodes (one query). */
    public static function categoriesWithSubcodes(array $codes): array
    {
        $three = array_values(array_unique(array_filter($codes, fn ($c) => strlen((string) $c) === 3)));
        if (!$three) {
            return [];
        }

        return DB::table('erm_icd10')
            ->whereIn(DB::raw('LEFT(code, 3)'), $three)
            ->where('code', 'like', '%.%')
            ->distinct()
            ->pluck(DB::raw('LEFT(code, 3)'))
            ->all();
    }

    /**
     * The coded diagnoses of an asesmen penunjang in order (diagnosa 1 = utama), plus the entries that cannot be sent.
     *
     * @return array{coded: array<int, array{field: string, code: string, display: string}>, invalid: array<int, array{field: string, text: string, reason: string}>}
     */
    public static function diagnoses($asesmenPenunjang): array
    {
        $coded = [];
        $invalid = [];
        $seen = [];
        foreach (self::DIAGNOSA_FIELDS as $field) {
            $text = trim((string) ($asesmenPenunjang->{$field} ?? ''));
            if ($text === '' || $text === '-') {
                continue;
            }
            $icd = self::find($text);
            if (!$icd) {
                $invalid[] = ['field' => $field, 'text' => $text, 'reason' => 'bukan kode ICD-10 yang ada di master'];
                continue;
            }
            if (isset($seen[$icd->code])) {
                continue;
            }
            $seen[$icd->code] = true;
            $coded[] = ['field' => $field, 'code' => $icd->code, 'display' => $icd->description];
        }

        return ['coded' => $coded, 'invalid' => $invalid];
    }

    /** Readable reason for SatuSehat when no diagnosis can be sent. */
    public static function notSendableMessage(array $diagnoses): string
    {
        if (!$diagnoses['invalid']) {
            return 'Belum ada diagnosa kerja ICD-10 di asesmen dokter.';
        }
        $list = collect($diagnoses['invalid'])->map(fn ($d) => '"' . $d['text'] . '"')->implode(', ');

        return 'Diagnosa kerja ' . $list . ' bukan kode ICD-10. Pilih ulang diagnosa dari daftar ICD-10 di asesmen dokter.';
    }
}
