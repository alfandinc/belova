<?php

namespace App\Support;

/**
 * Odontogram for asesmen gigi: FDI tooth numbers (ISO 3950), the 3-letter condition codes used on
 * Indonesian dental records, tooth surfaces, DMF-T / def-t, and the Status Intra Oral choices.
 * The view and the controller both read from here.
 *
 * Stored as {"36": {"car": ["O", "D"], "rct": []}}: per tooth, condition => surfaces ([] = whole tooth).
 */
class Odontogram
{
    /** Rows as they appear on the form, each split into the patient's right | left side. */
    public const ROWS = [
        'Permanen atas' => [[18, 17, 16, 15, 14, 13, 12, 11], [21, 22, 23, 24, 25, 26, 27, 28]],
        'Susu atas' => [[55, 54, 53, 52, 51], [61, 62, 63, 64, 65]],
        'Susu bawah' => [[85, 84, 83, 82, 81], [71, 72, 73, 74, 75]],
        'Permanen bawah' => [[48, 47, 46, 45, 44, 43, 42, 41], [31, 32, 33, 34, 35, 36, 37, 38]],
    ];

    /** M = mesial, O = oklusal / insisal, D = distal, V = vestibular (bukal / labial), L = lingual / palatal */
    public const SURFACES = ['M' => 'Mesial', 'O' => 'Oklusal / Insisal', 'D' => 'Distal', 'V' => 'Vestibular (bukal / labial)', 'L' => 'Lingual / palatal'];

    /**
     * code => [label, colour, group, recorded per surface, ICD-10 suggestion, DMF role]
     * DMF role: D decayed, M missing, F filled (null = not counted).
     */
    public const CONDITIONS = [
        // Karies & tumpatan (per permukaan)
        'car' => ['Karies', '#dc3545', 'Karies & tumpatan', true, 'K02.1', 'D'],
        'amf' => ['Tumpatan amalgam', '#1d4ed8', 'Karies & tumpatan', true, null, 'F'],
        'cof' => ['Tumpatan komposit', '#0891b2', 'Karies & tumpatan', true, null, 'F'],
        'gif' => ['Tumpatan GIC (glass ionomer)', '#0e7490', 'Karies & tumpatan', true, null, 'F'],
        'fis' => ['Fissure sealant', '#16a34a', 'Karies & tumpatan', true, null, null],
        'inl' => ['Inlay', '#0d9488', 'Karies & tumpatan', true, null, 'F'],
        'onl' => ['Onlay', '#0f766e', 'Karies & tumpatan', true, null, 'F'],
        // Kondisi gigi
        'nvt' => ['Non-vital', '#ea580c', 'Kondisi gigi', false, 'K04.1', null],
        'rct' => ['Perawatan saluran akar', '#c2410c', 'Kondisi gigi', false, null, null],
        'rrx' => ['Sisa akar (radix)', '#92400e', 'Kondisi gigi', false, 'K08.3', 'D'],
        'cfr' => ['Fraktur mahkota', '#b91c1c', 'Kondisi gigi', false, 'S02.5', null],
        'mis' => ['Gigi hilang (missing)', '#6b7280', 'Kondisi gigi', false, 'K08.1', 'M'],
        'une' => ['Belum erupsi (un-erupted)', '#64748b', 'Kondisi gigi', false, 'K01.0', null],
        'pre' => ['Erupsi sebagian (partial erupt)', '#64748b', 'Kondisi gigi', false, null, null],
        'imv' => ['Impaksi', '#8a7f72', 'Kondisi gigi', false, 'K01.1', null],
        'ano' => ['Anomali (pegshaped, mikro, fusi)', '#b45309', 'Kondisi gigi', false, 'K00.2', null],
        'per' => ['Persistensi gigi sulung', '#a16207', 'Kondisi gigi', false, 'K00.6', null],
        // Restorasi & protesa
        'fmc' => ['Mahkota logam penuh', '#6d28d9', 'Restorasi & protesa', false, null, 'F'],
        'poc' => ['Mahkota porselen', '#9333ea', 'Restorasi & protesa', false, null, 'F'],
        'mpc' => ['Mahkota metal-porselen', '#7e22ce', 'Restorasi & protesa', false, null, 'F'],
        'gmc' => ['Mahkota emas', '#a16207', 'Restorasi & protesa', false, null, 'F'],
        'vnc' => ['Veneer', '#a21caf', 'Restorasi & protesa', false, null, 'F'],
        'ipx' => ['Implan', '#4b7f99', 'Restorasi & protesa', false, null, null],
        'meb' => ['Jembatan logam (bridge)', '#4338ca', 'Restorasi & protesa', false, null, null],
        'pob' => ['Jembatan porselen (bridge)', '#4f46e5', 'Restorasi & protesa', false, null, null],
        'prd' => ['Gigi tiruan sebagian', '#7a8494', 'Restorasi & protesa', false, null, null],
        'fld' => ['Gigi tiruan lengkap', '#9a6b4f', 'Restorasi & protesa', false, null, null],
    ];

    /** Codes used by the first version of this form (2026-11-10), read as their standard code. */
    private const LEGACY = ['rad' => 'rrx', 'pe' => 'pre', 'rpd' => 'prd', 'brg' => 'meb'];

    /** field => [label, options, has a "keterangan" text box] */
    public const INTRA_ORAL = [
        'oklusi' => ['Oklusi', ['Normal bite', 'Cross bite', 'Steep bite'], false],
        'torus_palatinus' => ['Torus Palatinus', ['Tidak ada', 'Kecil', 'Sedang', 'Besar', 'Multiple'], false],
        'torus_mandibularis' => ['Torus Mandibularis', ['Tidak ada', 'Sisi kiri', 'Sisi kanan', 'Kedua sisi'], false],
        'palatum' => ['Palatum', ['Dalam', 'Sedang', 'Rendah'], false],
        'supernumerary' => ['Supernumerary', ['Tidak ada', 'Ada'], true],
        'diastema' => ['Diastema', ['Tidak ada', 'Ada'], true],
        'gigi_anomali' => ['Gigi anomali', ['Tidak ada', 'Ada'], true],
    ];

    public static function teeth(): array
    {
        return collect(self::ROWS)->flatten()->map(fn ($n) => (string) $n)->all();
    }

    public static function isDeciduous(string $tooth): bool
    {
        return in_array((int) substr($tooth, 0, 1), [5, 6, 7, 8], true);
    }

    /**
     * Keeps only real teeth, known conditions and surfaces (only for conditions recorded per surface).
     * Accepts the JSON string posted by the form, an array, or the first version's {"16": ["car"]}.
     * Returns null when nothing is marked.
     */
    public static function clean($value): ?array
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }
        if (!is_array($value)) {
            return null;
        }

        $teeth = array_flip(self::teeth());
        $result = [];
        foreach ($value as $tooth => $conditions) {
            $tooth = (string) $tooth;
            if (!isset($teeth[$tooth]) || !is_array($conditions)) {
                continue;
            }
            // First version: a plain list of codes
            if (array_is_list($conditions)) {
                $conditions = array_fill_keys(array_filter($conditions, 'is_string'), []);
            }

            $clean = [];
            foreach ($conditions as $code => $surfaces) {
                $code = self::LEGACY[$code] ?? $code;
                if (!is_string($code) || !isset(self::CONDITIONS[$code])) {
                    continue;
                }
                $perSurface = self::CONDITIONS[$code][3];
                $surfaces = $perSurface && is_array($surfaces)
                    ? array_values(array_intersect(array_keys(self::SURFACES), $surfaces))
                    : [];
                $clean[$code] = $surfaces;
            }
            if ($clean) {
                $result[$tooth] = $clean;
            }
        }
        uksort($result, fn ($a, $b) => array_search($a, self::teeth()) <=> array_search($b, self::teeth()));

        return $result ?: null;
    }

    /**
     * DMF-T for permanent teeth and def-t for deciduous teeth. A tooth counts once:
     * decayed wins over filled (a filled tooth with new caries is D); missing is M / e.
     */
    public static function dmf(?array $odontogram): array
    {
        $result = ['D' => 0, 'M' => 0, 'F' => 0, 'd' => 0, 'e' => 0, 'f' => 0];
        foreach ($odontogram ?? [] as $tooth => $conditions) {
            $roles = array_filter(array_map(fn ($code) => self::CONDITIONS[$code][5] ?? null, array_keys($conditions)));
            $role = in_array('M', $roles, true) ? 'M' : (in_array('D', $roles, true) ? 'D' : (in_array('F', $roles, true) ? 'F' : null));
            if (!$role) {
                continue;
            }
            if (self::isDeciduous((string) $tooth)) {
                $result[['D' => 'd', 'M' => 'e', 'F' => 'f'][$role]]++;
            } else {
                $result[$role]++;
            }
        }
        $result['DMFT'] = $result['D'] + $result['M'] + $result['F'];
        $result['deft'] = $result['d'] + $result['e'] + $result['f'];

        return $result;
    }

    /** "36: Karies (O, D); 26: Gigi hilang" — for summaries and print. */
    public static function summary(?array $odontogram): string
    {
        return collect($odontogram ?? [])->map(function ($conditions, $tooth) {
            $parts = collect($conditions)->map(fn ($surfaces, $code) => (self::CONDITIONS[$code][0] ?? $code) . ($surfaces ? ' (' . implode(', ', $surfaces) . ')' : ''));
            return $tooth . ': ' . $parts->implode(', ');
        })->implode('; ');
    }

    /**
     * What changed between two odontograms, one line per tooth and condition, in chart order:
     * "36: + Karies (O, D)", "46: − Gigi hilang", "36: Karies O → O, D".
     */
    public static function changes(?array $before, ?array $after): array
    {
        $before = $before ?? [];
        $after = $after ?? [];
        $label = fn ($code) => self::CONDITIONS[$code][0] ?? $code;
        $surf = fn ($s) => $s ? implode(', ', $s) : 'seluruh gigi';

        $lines = [];
        foreach (self::teeth() as $tooth) {
            $old = $before[$tooth] ?? [];
            $new = $after[$tooth] ?? [];
            foreach ($new as $code => $surfaces) {
                if (!array_key_exists($code, $old)) {
                    $lines[] = $tooth . ': + ' . $label($code) . ($surfaces ? ' (' . implode(', ', $surfaces) . ')' : '');
                } elseif ($old[$code] != $surfaces) {
                    $lines[] = $tooth . ': ' . $label($code) . ' ' . $surf($old[$code]) . ' → ' . $surf($surfaces);
                }
            }
            foreach ($old as $code => $surfaces) {
                if (!array_key_exists($code, $new)) {
                    $lines[] = $tooth . ': − ' . $label($code);
                }
            }
        }

        return $lines;
    }

    /** Only the known Intra Oral fields and options; free text is trimmed and capped. */
    public static function cleanIntraOral($value): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        $result = [];
        foreach (self::INTRA_ORAL as $field => [, $options, $hasKet]) {
            $choice = $value[$field] ?? null;
            if (is_string($choice) && in_array($choice, $options, true)) {
                $result[$field] = $choice;
            }
            if ($hasKet) {
                $ket = trim((string) ($value[$field . '_ket'] ?? ''));
                if ($ket !== '') {
                    $result[$field . '_ket'] = mb_substr($ket, 0, 255);
                }
            }
        }
        $lain = trim((string) ($value['lain_lain'] ?? ''));
        if ($lain !== '') {
            $result['lain_lain'] = mb_substr($lain, 0, 1000);
        }

        return $result ?: null;
    }
}
