<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Models\ERM\Icd10;
use App\Support\Icd10Code;
use Illuminate\Http\Request;

class Icd10Controller extends Controller
{
    /** Common Indonesian clinical words → the English terms used in the ICD-10 descriptions. */
    private const SINONIM = [
        'karies' => ['caries'], 'gigi' => ['tooth', 'teeth', 'dental'], 'gusi' => ['gingiv'], 'pulpa' => ['pulp'],
        'akar' => ['root'], 'impaksi' => ['impacted'], 'abses' => ['abscess'], 'radang' => ['inflamm', 'itis'],
        'hipertensi' => ['hypertension'], 'darah' => ['blood'], 'tinggi' => ['high'], 'diabetes' => ['diabetes'],
        'kencing' => ['urin'], 'manis' => ['diabetes'], 'demam' => ['fever'], 'batuk' => ['cough'], 'pilek' => ['rhinitis', 'nasopharyngitis'],
        'flu' => ['influenza'], 'diare' => ['diarrhoea', 'diarrhea'], 'mual' => ['nausea'], 'muntah' => ['vomiting'],
        'nyeri' => ['pain'], 'sakit' => ['pain', 'ache'], 'kepala' => ['head'], 'pusing' => ['dizziness', 'vertigo'],
        'perut' => ['abdominal'], 'lambung' => ['gastr', 'stomach'], 'maag' => ['gastritis', 'dyspepsia'],
        'jerawat' => ['acne'], 'kulit' => ['skin', 'derma'], 'gatal' => ['pruritus'], 'alergi' => ['allerg'],
        'eksim' => ['eczema', 'dermatitis'], 'luka' => ['wound', 'injury'], 'patah' => ['fracture'], 'tulang' => ['bone'],
        'sendi' => ['joint', 'arthr'], 'otot' => ['muscle', 'myalgia'], 'telinga' => ['ear'], 'hidung' => ['nose', 'nasal'],
        'tenggorokan' => ['throat', 'pharyn'], 'amandel' => ['tonsil'], 'mata' => ['eye'], 'jantung' => ['heart', 'cardi'],
        'paru' => ['lung', 'pulmonary'], 'sesak' => ['dyspnoea', 'asthma'], 'asma' => ['asthma'], 'ginjal' => ['kidney', 'renal'],
        'hati' => ['liver', 'hepat'], 'kolesterol' => ['cholesterol', 'lipid'], 'asam' => ['acid'], 'urat' => ['gout', 'uric'],
        'stroke' => ['stroke', 'cerebral infarction'], 'kejang' => ['convulsion', 'seizure'], 'cemas' => ['anxiety'],
        'hamil' => ['pregnan'], 'kehamilan' => ['pregnan'], 'anemia' => ['anaemia', 'anemia'], 'infeksi' => ['infection'],
        'saluran' => ['tract'], 'napas' => ['respiratory'], 'pernapasan' => ['respiratory'], 'kemih' => ['urinary'],
        'wasir' => ['haemorrhoid', 'hemorrhoid'], 'ambeien' => ['haemorrhoid'], 'obesitas' => ['obesity'], 'kegemukan' => ['obesity'],
        'vaksin' => ['vaccination', 'immunization'], 'imunisasi' => ['immunization'], 'pemeriksaan' => ['examination'],
        'kontrol' => ['follow-up'], 'penuaan' => ['ageing', 'aging'], 'kerutan' => ['wrinkl'], 'flek' => ['pigment', 'melan'],
        'rambut' => ['hair', 'alopecia'], 'rontok' => ['alopecia', 'hair loss'], 'kutil' => ['wart', 'verruca'],
    ];

    /**
     * ICD-10 picker search. Code matches come first (exact, then prefix), then description words.
     * A 3-character category that has subcodes comes back with selectable=false: coding uses the subcode.
     */
    public function search(Request $request)
    {
        $query = trim((string) $request->input('q'));
        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        $code = Icd10Code::extract($query) ?? strtoupper(str_replace(' ', '', $query));
        $words = array_filter(preg_split('/\s+/', mb_strtolower($query)));

        $results = Icd10::query()
            ->where('code', '!=', '')
            ->where(function ($q) use ($code, $words) {
                $q->where('code', 'like', $code . '%')
                    ->orWhere(function ($q) use ($words) {
                        // Every word must match; an Indonesian word also matches its English term
                        foreach ($words as $word) {
                            $q->where(function ($q) use ($word) {
                                $q->where('description', 'like', '%' . $word . '%');
                                foreach (self::SINONIM[$word] ?? [] as $english) {
                                    $q->orWhere('description', 'like', '%' . $english . '%');
                                }
                            });
                        }
                    });
            })
            ->orderByRaw('CASE WHEN code = ? THEN 0 WHEN code LIKE ? THEN 1 ELSE 2 END', [$code, $code . '%'])
            ->orderBy('code')
            ->limit(30)
            ->get(['code', 'description']);

        $categories = array_flip(Icd10Code::categoriesWithSubcodes($results->pluck('code')->all()));

        return response()->json($results->map(fn ($r) => [
            'code' => $r->code,
            'description' => $r->description,
            'selectable' => !isset($categories[$r->code]),
        ])->values());
    }
}
