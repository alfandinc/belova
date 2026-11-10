<?php

namespace App\Http\Controllers\Satusehat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\Pasien as ErmPasien;
use App\Models\ERM\Visitation;
use App\Models\Satusehat\ClinicConfig;
use App\Models\Satusehat\PatientGet;
use App\Models\Satusehat\DokterMapping;
use App\Models\Satusehat\Location as SatusehatLocation;
use App\Models\Satusehat\Encounter as SatusehatEncounter;
use App\Models\ERM\Icd10;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PasienController extends Controller
{
    public function index()
    {
        $kliniks = \App\Models\ERM\Klinik::orderBy('nama')->get();
        $statuses = [
            'arrived' => 'Arrived',
            'in-progress' => 'In Progress',
            'finished' => 'Finished'
        ];
        return view('satusehat.pasiens.index', compact('kliniks','statuses'));
    }

    /**
     * Create Encounter resource in Kemkes FHIR using visitation data
     */
    public function createKemkesEncounter(Request $request, $visitationId)
    {
        $visitation = Visitation::with(['pasien','dokter','klinik'])->findOrFail($visitationId);

        $klinikId = $visitation->klinik_id;
        $clinicConfig = ClinicConfig::where('klinik_id', $klinikId)->first();
        if (!$clinicConfig || !$clinicConfig->base_url) {
            return response()->json(['ok' => false, 'error' => 'Clinic config or base_url not found'], 404);
        }

        // Get satusehat patient id from previous patient get mapping
        $patientMap = PatientGet::where('pasien_id', $visitation->pasien_id)->latest()->first();
        if (!$patientMap || !$patientMap->satusehat_patient_id) {
            return response()->json(['ok' => false, 'error' => 'Satusehat patient id not found. Please Get Data first.'], 400);
        }

        $subjectRef = 'Patient/' . $patientMap->satusehat_patient_id;

        // Practitioner mapping
        $practMapping = null;
        if ($visitation->dokter && $visitation->dokter->id) {
            $practMapping = DokterMapping::where('dokter_id', $visitation->dokter->id)->first();
        }

        $practRef = null;
        if ($practMapping && $practMapping->mapping_code) {
            $practRef = 'Practitioner/' . $practMapping->mapping_code;
        }

        // Location mapping: prefer satusehat_locations table if available for this klinik
        $loc = SatusehatLocation::where('klinik_id', $klinikId)->first();
        $locRef = null;
        if ($loc && $loc->location_id) {
            $locRef = 'Location/' . $loc->location_id;
        }

        // Build Encounter payload
        $base = rtrim($clinicConfig->base_url, '/');

        $periodStart = null;
        try {
            $dt = null;
            if ($visitation->tanggal_visitation && $visitation->waktu_kunjungan) {
                $dt = \Carbon\Carbon::parse($visitation->tanggal_visitation . ' ' . $visitation->waktu_kunjungan);
            } elseif ($visitation->tanggal_visitation) {
                $dt = \Carbon\Carbon::parse($visitation->tanggal_visitation);
            }
            if ($dt) $periodStart = $dt->toIso8601String();
        } catch (\Throwable $e) { $periodStart = null; }

        $encounter = [
            'resourceType' => 'Encounter',
            'status' => 'arrived',
            'class' => [
                'system' => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                'code' => 'AMB',
                'display' => 'ambulatory'
            ],
            'subject' => [
                'reference' => $subjectRef,
                'display' => $visitation->pasien->nama ?? null
            ],
        ];

        if ($practRef) {
            $encounter['participant'] = [[
                'type' => [[ 'coding' => [[ 'system' => 'http://terminology.hl7.org/CodeSystem/v3-ParticipationType', 'code' => 'ATND', 'display' => 'attender' ]]]],
                'individual' => ['reference' => $practRef, 'display' => $visitation->dokter->nama ?? null]
            ]];
        }

        if ($periodStart) {
            $encounter['period'] = ['start' => $periodStart];
            $encounter['statusHistory'] = [['status' => 'arrived', 'period' => ['start' => \Carbon\Carbon::now()->toIso8601String()]]];
        }

        if ($locRef) {
            $encounter['location'] = [['location' => ['reference' => $locRef, 'display' => $loc->name ?? null]]];
        }

        if (!empty($clinicConfig->organization_id)) {
            $encounter['serviceProvider'] = ['reference' => 'Organization/' . $clinicConfig->organization_id];
        }

        // identifier
        $identifierSystem = 'http://sys-ids.kemkes.go.id/encounter/' . ($clinicConfig->organization_id ?? '');
        $identifierValue = 'P' . uniqid();
        $encounter['identifier'] = [['system' => $identifierSystem, 'value' => $identifierValue]];

        // Prepare headers and bearer token (reuse logic from getKemkesPatient)
        $bearer = null;
        if (!empty($clinicConfig->token)) {
            $decoded = null;
            try { $decoded = json_decode($clinicConfig->token, true); } catch (\Throwable $e) { $decoded = null; }
            if (is_array($decoded)) {
                if (!empty($decoded['access_token'])) $bearer = $decoded['access_token'];
                elseif (!empty($decoded['accessToken'])) $bearer = $decoded['accessToken'];
                elseif (!empty($decoded['token'])) $bearer = $decoded['token'];
            }
            if (!$bearer) $bearer = $clinicConfig->token;
        }

        $headers = ['Accept' => 'application/json', 'Content-Type' => 'application/json'];
        if (!empty($clinicConfig->organization_id)) {
            $headers['Organization-Id'] = $clinicConfig->organization_id;
            $headers['organization-id'] = $clinicConfig->organization_id;
        }

        $url = $base . '/Encounter';

        $insecureRetry = false;
        try {
            $req = Http::timeout(15)->withHeaders($headers);
            if ($bearer) $req = $req->withToken($bearer);
            $res = $req->post($url, $encounter);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (stripos($msg, 'cURL error 60') !== false || stripos($msg, 'self-signed') !== false || stripos($msg, 'certificate') !== false) {
                try {
                    $insecureRetry = true;
                    $req = Http::withoutVerifying()->timeout(15)->withHeaders($headers);
                    if ($bearer) $req = $req->withToken($bearer);
                    $res = $req->post($url, $encounter);
                } catch (\Throwable $e2) {
                    return response()->json(['ok' => false, 'error' => 'SSL verify failed and insecure retry failed: ' . $e2->getMessage()], 500);
                }
            } else {
                return response()->json(['ok' => false, 'error' => $msg], 500);
            }
        }

        if (!isset($res) || !$res->successful()) {
            $body = null; try { $body = $res ? $res->json() : null; } catch (\Throwable $e) { $body = $res ? $res->body() : null; }
            $status = $res ? $res->status() : 500;
            return response()->json(['ok' => false, 'status' => $status, 'body' => $body], $status);
        }

        $body = $res->json();

        $payload = ['ok' => true, 'data' => $body, 'payload_sent' => $encounter];

        // Persist the successful encounter mapping
        try {
            $returnedId = null;
            if (is_array($body) && !empty($body['id'])) $returnedId = $body['id'];
            if (!$returnedId && is_array($body) && !empty($body['resource']['id'])) $returnedId = $body['resource']['id'];
            // Upsert so there's only one record per visitation_id
            SatusehatEncounter::updateOrCreate(
                ['visitation_id' => $visitation->id],
                [
                    'pasien_id' => $visitation->pasien_id,
                    'klinik_id' => $visitation->klinik_id,
                    'satusehat_encounter_id' => $returnedId,
                    'raw_response' => json_encode($body, JSON_UNESCAPED_UNICODE),
                    'status' => 'arrived'
                ]
            );
        } catch (\Throwable $e) {
            // don't fail the request if DB persist fails; just continue
        }

        if ($insecureRetry) $payload['warning'] = 'Request retried with SSL verification disabled';
        return response()->json($payload);
    }

    /**
     * Update existing Encounter resource on Kemkes FHIR (PUT /Encounter/{id})
     */
    public function updateKemkesEncounter(Request $request, $visitationId)
    {
        $visitation = Visitation::with(['pasien','dokter','klinik'])->findOrFail($visitationId);

        $klinikId = $visitation->klinik_id;
        $clinicConfig = ClinicConfig::where('klinik_id', $klinikId)->first();
        if (!$clinicConfig || !$clinicConfig->base_url) {
            return response()->json(['ok' => false, 'error' => 'Clinic config or base_url not found'], 404);
        }

        // need existing encounter id saved previously
        $saved = SatusehatEncounter::where('visitation_id', $visitationId)->orderBy('created_at','desc')->first();
        if (!$saved || !$saved->satusehat_encounter_id) {
            return response()->json(['ok' => false, 'error' => 'No existing satusehat encounter id found. Please create an encounter first.'], 400);
        }
        $encounterUuid = $saved->satusehat_encounter_id;

        // subject (patient) mapping
        $patientMap = PatientGet::where('pasien_id', $visitation->pasien_id)->latest()->first();
        if (!$patientMap || !$patientMap->satusehat_patient_id) {
            return response()->json(['ok' => false, 'error' => 'Satusehat patient id not found. Please Get Data first.'], 400);
        }
        $subjectRef = 'Patient/' . $patientMap->satusehat_patient_id;

        // Practitioner mapping
        $practMapping = null;
        if ($visitation->dokter && $visitation->dokter->id) {
            $practMapping = DokterMapping::where('dokter_id', $visitation->dokter->id)->first();
        }
        $practRef = null;
        if ($practMapping && $practMapping->mapping_code) {
            $practRef = 'Practitioner/' . $practMapping->mapping_code;
        }

        // Location mapping
        $loc = SatusehatLocation::where('klinik_id', $klinikId)->first();
        $locRef = null;
        if ($loc && $loc->location_id) {
            $locRef = 'Location/' . $loc->location_id;
        }

        // Build Encounter payload following provided template
        $base = rtrim($clinicConfig->base_url, '/');

        $periodStart = null;
        try {
            $dt = null;
            if ($visitation->tanggal_visitation && $visitation->waktu_kunjungan) {
                $dt = \Carbon\Carbon::parse($visitation->tanggal_visitation . ' ' . $visitation->waktu_kunjungan);
            } elseif ($visitation->tanggal_visitation) {
                $dt = \Carbon\Carbon::parse($visitation->tanggal_visitation);
            }
            if ($dt) $periodStart = $dt->toIso8601String();
        } catch (\Throwable $e) { $periodStart = null; }

        $periodEnd = null;
        if ($periodStart) {
            try { $periodEnd = \Carbon\Carbon::parse($periodStart)->addHour()->toIso8601String(); } catch (\Throwable $e) { $periodEnd = null; }
        }

        $identifierSystem = 'http://sys-ids.kemkes.go.id/encounter/' . ($clinicConfig->organization_id ?? '');
        $identifierValue = 'P' . uniqid();

        $encounter = [
            'resourceType' => 'Encounter',
            'id' => $encounterUuid,
            'identifier' => [['system' => $identifierSystem, 'value' => $identifierValue]],
            'status' => 'in-progress',
            'class' => [
                'system' => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                'code' => 'AMB',
                'display' => 'ambulatory'
            ],
            'subject' => ['reference' => $subjectRef, 'display' => $visitation->pasien->nama ?? null],
        ];

        if ($practRef) {
            $encounter['participant'] = [[
                'type' => [[ 'coding' => [[ 'system' => 'http://terminology.hl7.org/CodeSystem/v3-ParticipationType', 'code' => 'ATND', 'display' => 'attender' ]]]],
                'individual' => ['reference' => $practRef, 'display' => $visitation->dokter->nama ?? null]
            ]];
        }

        if ($periodStart) {
            $encounter['period'] = ['start' => $periodStart, 'end' => $periodEnd];
            $encounter['statusHistory'] = [
                ['status' => 'arrived', 'period' => ['start' => $periodStart, 'end' => $periodStart]],
                ['status' => 'in-progress', 'period' => ['start' => $periodEnd ?? $periodStart, 'end' => $periodEnd ?? $periodStart]]
            ];
        }

        if ($locRef) {
            $encounter['location'] = [['location' => ['reference' => $locRef, 'display' => $loc->name ?? null]]];
        }

        if (!empty($clinicConfig->organization_id)) {
            $encounter['serviceProvider'] = ['reference' => 'Organization/' . $clinicConfig->organization_id];
        }

        // bearer and headers
        $bearer = null;
        if (!empty($clinicConfig->token)) {
            $decoded = null; try { $decoded = json_decode($clinicConfig->token, true); } catch (\Throwable $e) { $decoded = null; }
            if (is_array($decoded)) {
                if (!empty($decoded['access_token'])) $bearer = $decoded['access_token'];
                elseif (!empty($decoded['accessToken'])) $bearer = $decoded['accessToken'];
                elseif (!empty($decoded['token'])) $bearer = $decoded['token'];
            }
            if (!$bearer) $bearer = $clinicConfig->token;
        }

        $headers = ['Accept' => 'application/json', 'Content-Type' => 'application/json'];
        if (!empty($clinicConfig->organization_id)) {
            $headers['Organization-Id'] = $clinicConfig->organization_id;
            $headers['organization-id'] = $clinicConfig->organization_id;
        }

        $url = $base . '/Encounter/' . $encounterUuid;

        $insecureRetry = false;
        try {
            $req = Http::timeout(15)->withHeaders($headers);
            if ($bearer) $req = $req->withToken($bearer);
            $res = $req->put($url, $encounter);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (stripos($msg, 'cURL error 60') !== false || stripos($msg, 'self-signed') !== false || stripos($msg, 'certificate') !== false) {
                try {
                    $insecureRetry = true;
                    $req = Http::withoutVerifying()->timeout(15)->withHeaders($headers);
                    if ($bearer) $req = $req->withToken($bearer);
                    $res = $req->put($url, $encounter);
                } catch (\Throwable $e2) {
                    return response()->json(['ok' => false, 'error' => 'SSL verify failed and insecure retry failed: ' . $e2->getMessage()], 500);
                }
            } else {
                return response()->json(['ok' => false, 'error' => $msg], 500);
            }
        }

        if (!isset($res) || !$res->successful()) {
            $body = null; try { $body = $res ? $res->json() : null; } catch (\Throwable $e) { $body = $res ? $res->body() : null; }
            $status = $res ? $res->status() : 500;
            return response()->json(['ok' => false, 'status' => $status, 'body' => $body], $status);
        }

        $body = $res->json();
        $payload = ['ok' => true, 'data' => $body, 'payload_sent' => $encounter];

        // update raw_response in DB
        try {
            SatusehatEncounter::updateOrCreate(
                ['visitation_id' => $visitation->id],
                [
                    'pasien_id' => $visitation->pasien_id,
                    'klinik_id' => $visitation->klinik_id,
                    'satusehat_encounter_id' => $encounterUuid,
                    'raw_response' => json_encode($body, JSON_UNESCAPED_UNICODE),
                    'status' => 'in-progress'
                ]
            );
        } catch (\Throwable $e) { }

        if ($insecureRetry) $payload['warning'] = 'Request retried with SSL verification disabled';
        return response()->json($payload);
    }

    /**
     * Finish existing Encounter resource on Kemkes FHIR (PUT /Encounter/{id})
     * This will attempt to create a Condition (diagnosa) first, then PUT the Encounter
     * with status 'finished' and include the diagnosis array referencing the Condition.
     */
    public function finishKemkesEncounter(Request $request, $visitationId)
    {
        $visitation = Visitation::with(['pasien','dokter','klinik'])->findOrFail($visitationId);

        $klinikId = $visitation->klinik_id;
        $clinicConfig = ClinicConfig::where('klinik_id', $klinikId)->first();
        if (!$clinicConfig || !$clinicConfig->base_url) {
            return response()->json(['ok' => false, 'error' => 'Clinic config or base_url not found'], 404);
        }

        $saved = SatusehatEncounter::where('visitation_id', $visitationId)->orderBy('created_at','desc')->first();
        if (!$saved || !$saved->satusehat_encounter_id) {
            return response()->json(['ok' => false, 'error' => 'No existing satusehat encounter id found. Please create an encounter first.'], 400);
        }
        $encounterUuid = $saved->satusehat_encounter_id;

        // subject (patient) mapping
        $patientMap = PatientGet::where('pasien_id', $visitation->pasien_id)->latest()->first();
        if (!$patientMap || !$patientMap->satusehat_patient_id) {
            return response()->json(['ok' => false, 'error' => 'Satusehat patient id not found. Please Get Data first.'], 400);
        }
        $subjectRef = 'Patient/' . $patientMap->satusehat_patient_id;

        // Diagnosa utama = the first diagnosa kerja that is a real ICD-10 code (never guessed from free text)
        $asesmen = \App\Models\ERM\AsesmenPenunjang::where('visitation_id', $visitationId)->first();
        $diagnoses = \App\Support\Icd10Code::diagnoses($asesmen);
        if (!$diagnoses['coded']) {
            return response()->json([
                'ok' => false,
                'status' => 400,
                'error' => \App\Support\Icd10Code::notSendableMessage($diagnoses),
                'invalid' => $diagnoses['invalid'],
            ], 400);
        }
        $codingSystem = \App\Support\Icd10Code::FHIR_SYSTEM;

        $bearer = null;
        if (!empty($clinicConfig->token)) {
            $decoded = null; try { $decoded = json_decode($clinicConfig->token, true); } catch (\Throwable $e) { $decoded = null; }
            if (is_array($decoded)) {
                if (!empty($decoded['access_token'])) $bearer = $decoded['access_token'];
                elseif (!empty($decoded['accessToken'])) $bearer = $decoded['accessToken'];
                elseif (!empty($decoded['token'])) $bearer = $decoded['token'];
            }
            if (!$bearer) $bearer = $clinicConfig->token;
        }

        $headers = ['Accept' => 'application/json', 'Content-Type' => 'application/json'];
        if (!empty($clinicConfig->organization_id)) {
            $headers['Organization-Id'] = $clinicConfig->organization_id;
            $headers['organization-id'] = $clinicConfig->organization_id;
        }

        $base = rtrim($clinicConfig->base_url, '/');

        // One Condition per coded diagnosa kerja (1 = utama). The utama must succeed; a failed secondary
        // diagnosis is reported but does not stop finishing the encounter.
        $insecureRetry = false;
        $sentDiagnoses = [];
        $conditionResponses = [];
        $diagnosisWarnings = [];
        foreach ($diagnoses['coded'] as $i => $diag) {
            $condition = [
                'resourceType' => 'Condition',
                'clinicalStatus' => ['coding' => [[
                    'system' => 'http://terminology.hl7.org/CodeSystem/condition-clinical',
                    'code' => 'active',
                    'display' => 'Active'
                ]]],
                'category' => [['coding' => [[
                    'system' => 'http://terminology.hl7.org/CodeSystem/condition-category',
                    'code' => 'encounter-diagnosis',
                    'display' => 'Encounter Diagnosis'
                ]]]],
                'code' => ['coding' => [[
                    'system' => $codingSystem,
                    'code' => $diag['code'],
                    'display' => $diag['display']
                ]]],
                'subject' => ['reference' => $subjectRef, 'display' => $visitation->pasien->nama ?? null],
                'encounter' => ['reference' => 'Encounter/' . $encounterUuid, 'display' => 'Kunjungan ' . ($visitation->pasien->nama ?? '')]
            ];

            $sent = $this->postCondition($condition, $base, $headers, $bearer, $subjectRef, $encounterUuid, $diag['code'], $visitationId, $insecureRetry);
            $conditionResponses[] = $sent['body'];
            if (empty($sent['id'])) {
                if ($i === 0) {
                    return response()->json(['ok' => false, 'status' => $sent['status'], 'error' => $sent['error'] ?? null, 'body' => $sent['body']], $sent['status']);
                }
                $diagnosisWarnings[] = 'Diagnosa ' . $diag['code'] . ' gagal dikirim: ' . ($sent['error'] ?? ('HTTP ' . $sent['status']));
                continue;
            }
            $sentDiagnoses[] = ['id' => $sent['id'], 'display' => $diag['display']];
        }

        // Build Encounter payload with diagnosis referencing created Condition
        $periodStart = null;
        try {
            $dt = null;
            if ($visitation->tanggal_visitation && $visitation->waktu_kunjungan) {
                $dt = \Carbon\Carbon::parse($visitation->tanggal_visitation . ' ' . $visitation->waktu_kunjungan);
            } elseif ($visitation->tanggal_visitation) {
                $dt = \Carbon\Carbon::parse($visitation->tanggal_visitation);
            }
            if ($dt) $periodStart = $dt->toIso8601String();
        } catch (\Throwable $e) { $periodStart = null; }
        $periodEnd = null; if ($periodStart) { try { $periodEnd = \Carbon\Carbon::parse($periodStart)->addHour()->toIso8601String(); } catch (\Throwable $e) { $periodEnd = null; } }

        $identifierSystem = 'http://sys-ids.kemkes.go.id/encounter/' . ($clinicConfig->organization_id ?? '');
        $identifierValue = 'P' . uniqid();

        $encounter = [
            'resourceType' => 'Encounter',
            'id' => $encounterUuid,
            'identifier' => [['system' => $identifierSystem, 'value' => $identifierValue]],
            'status' => 'finished',
            'class' => [
                'system' => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                'code' => 'AMB',
                'display' => 'ambulatory'
            ],
            'subject' => ['reference' => $subjectRef, 'display' => $visitation->pasien->nama ?? null],
        ];

        $practMapping = null;
        if ($visitation->dokter && $visitation->dokter->id) {
            $practMapping = DokterMapping::where('dokter_id', $visitation->dokter->id)->first();
        }
        if ($practMapping && $practMapping->mapping_code) {
            $practRef = 'Practitioner/' . $practMapping->mapping_code;
            $encounter['participant'] = [[
                'type' => [[ 'coding' => [[ 'system' => 'http://terminology.hl7.org/CodeSystem/v3-ParticipationType', 'code' => 'ATND', 'display' => 'attender' ]]]],
                'individual' => ['reference' => $practRef, 'display' => $visitation->dokter->nama ?? null]
            ]];
        }

        if ($periodStart) {
            $encounter['period'] = ['start' => $periodStart, 'end' => $periodEnd];
            $encounter['statusHistory'] = [
                ['status' => 'arrived', 'period' => ['start' => $periodStart, 'end' => $periodStart]],
                ['status' => 'in-progress', 'period' => ['start' => $periodEnd ?? $periodStart, 'end' => $periodEnd ?? $periodStart]],
                ['status' => 'finished', 'period' => ['start' => $periodEnd ?? $periodStart, 'end' => $periodEnd ?? $periodStart]]
            ];
        }

        $loc = SatusehatLocation::where('klinik_id', $klinikId)->first();
        if ($loc && $loc->location_id) {
            $locRef = 'Location/' . $loc->location_id;
            $encounter['location'] = [['location' => ['reference' => $locRef, 'display' => $loc->name ?? null]]];
        }

        if (!empty($clinicConfig->organization_id)) {
            $encounter['serviceProvider'] = ['reference' => 'Organization/' . $clinicConfig->organization_id];
        }

        // diagnosis array: every sent Condition, rank 1 = diagnosa utama
        if ($sentDiagnoses) {
            $encounter['diagnosis'] = array_map(fn ($d, $i) => [
                'condition' => ['reference' => 'Condition/' . $d['id'], 'display' => $d['display']],
                'use' => ['coding' => [[
                    'system' => 'http://terminology.hl7.org/CodeSystem/diagnosis-role',
                    'code' => 'DD',
                    'display' => 'Discharge diagnosis'
                ]]],
                'rank' => $i + 1
            ], $sentDiagnoses, array_keys($sentDiagnoses));
        }

        // PUT Encounter
        $url = $base . '/Encounter/' . $encounterUuid;
        try {
            $req = Http::timeout(15)->withHeaders($headers);
            if ($bearer) $req = $req->withToken($bearer);
            $res = $req->put($url, $encounter);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (stripos($msg, 'cURL error 60') !== false || stripos($msg, 'self-signed') !== false || stripos($msg, 'certificate') !== false) {
                try {
                    $insecureRetry = true;
                    $req = Http::withoutVerifying()->timeout(15)->withHeaders($headers);
                    if ($bearer) $req = $req->withToken($bearer);
                    $res = $req->put($url, $encounter);
                } catch (\Throwable $e2) {
                    return response()->json(['ok' => false, 'error' => 'SSL verify failed and insecure retry failed: ' . $e2->getMessage()], 500);
                }
            } else {
                return response()->json(['ok' => false, 'error' => $msg], 500);
            }
        }

        if (!isset($res) || !$res->successful()) {
            $body = null; try { $body = $res ? $res->json() : null; } catch (\Throwable $e) { $body = $res ? $res->body() : null; }
            $status = $res ? $res->status() : 500;
            return response()->json(['ok' => false, 'status' => $status, 'body' => $body], $status);
        }

        $body = $res->json();
        $payload = ['ok' => true, 'data' => $body, 'payload_sent' => $encounter];

        // update DB record
        try {
            SatusehatEncounter::updateOrCreate(
                ['visitation_id' => $visitation->id],
                [
                    'pasien_id' => $visitation->pasien_id,
                    'klinik_id' => $visitation->klinik_id,
                    'satusehat_encounter_id' => $encounterUuid,
                    'raw_response' => json_encode($body, JSON_UNESCAPED_UNICODE),
                    'status' => 'finished'
                ]
            );
        } catch (\Throwable $e) { }

        if ($insecureRetry) $payload['warning'] = 'Request retried with SSL verification disabled';
        // include condition response for debugging
        $payload['condition_response'] = $conditionResponses[0] ?? null;
        $payload['condition_responses'] = $conditionResponses;
        $payload['diagnoses_sent'] = count($sentDiagnoses);
        if ($diagnosisWarnings) $payload['diagnosis_warnings'] = $diagnosisWarnings;
        if ($diagnoses['invalid']) {
            $payload['diagnosis_skipped'] = array_map(fn ($d) => $d['text'], $diagnoses['invalid']);
        }
        return response()->json($payload);
    }

    /**
     * POST one Condition. On a "duplicate" OperationOutcome the existing Condition is looked up
     * (subject + encounter + code, then subject + code) and used instead.
     *
     * @return array{id: ?string, body: mixed, status: int, error: ?string}
     */
    private function postCondition(array $condition, string $base, array $headers, ?string $bearer, string $subjectRef, string $encounterUuid, string $codingCode, $visitationId, bool &$insecureRetry): array
    {
        $resCond = null;
        try {
            $req = Http::timeout(15)->withHeaders($headers);
            if ($bearer) $req = $req->withToken($bearer);
            $resCond = $req->post($base . '/Condition', $condition);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (stripos($msg, 'cURL error 60') !== false || stripos($msg, 'self-signed') !== false || stripos($msg, 'certificate') !== false) {
                try {
                    $insecureRetry = true;
                    $req = Http::withoutVerifying()->timeout(15)->withHeaders($headers);
                    if ($bearer) $req = $req->withToken($bearer);
                    $resCond = $req->post($base . '/Condition', $condition);
                } catch (\Throwable $e2) {
                    return ['id' => null, 'body' => null, 'status' => 500, 'error' => 'SSL verify failed and insecure retry failed when creating Condition: ' . $e2->getMessage()];
                }
            } else {
                return ['id' => null, 'body' => null, 'status' => 500, 'error' => $msg];
            }
        }

        if ($resCond && $resCond->successful()) {
            $body = $resCond->json();
            $id = null;
            if (is_array($body) && !empty($body['id'])) $id = $body['id'];
            if (!$id && is_array($body) && !empty($body['resource']['id'])) $id = $body['resource']['id'];
            return ['id' => $id, 'body' => $body, 'status' => $resCond->status(), 'error' => $id ? null : 'Condition id not found in response'];
        }

        $body = null; try { $body = $resCond ? $resCond->json() : null; } catch (\Throwable $e) { $body = $resCond ? $resCond->body() : null; }
        $status = $resCond ? $resCond->status() : 500;

        // Duplicate: use the existing Condition
        $isDuplicate = false;
        if (is_array($body) && ($body['resourceType'] ?? null) === 'OperationOutcome' && !empty($body['issue']) && is_array($body['issue'])) {
            foreach ($body['issue'] as $iss) {
                if (!empty($iss['code']) && strtolower($iss['code']) === 'duplicate') { $isDuplicate = true; break; }
                if (!empty($iss['details']['text']) && stripos($iss['details']['text'], 'duplicate') !== false) { $isDuplicate = true; break; }
            }
        }
        if ($isDuplicate) {
            $searches = [
                $base . '/Condition?subject=' . urlencode($subjectRef) . '&encounter=' . urlencode('Encounter/' . $encounterUuid) . '&code=' . urlencode($codingCode),
                $base . '/Condition?subject=' . urlencode($subjectRef) . '&code=' . urlencode($codingCode),
            ];
            foreach ($searches as $searchUrl) {
                try {
                    $req = Http::timeout(15)->withHeaders($headers);
                    if ($bearer) $req = $req->withToken($bearer);
                    $res = $req->get($searchUrl);
                } catch (\Throwable $e) {
                    continue;
                }
                $found = $res->successful() ? ($res->json()['entry'][0]['resource']['id'] ?? null) : null;
                if ($found) {
                    try { Log::warning('Duplicate Condition detected; using existing Condition', ['visitation_id' => $visitationId, 'condition_id' => $found, 'operation_outcome' => $body]); } catch (\Throwable $e) { }
                    return ['id' => $found, 'body' => $body, 'status' => 200, 'error' => null];
                }
            }
        }

        return ['id' => null, 'body' => $body, 'status' => $status, 'error' => null];
    }

    /**
     * Send Medication resources to Kemkes FHIR for resep farmasi rows mapped to KFA codes.
     */
    public function sendKemkesMedication(Request $request, $visitationId)
    {
        $visitation = Visitation::with(['pasien','dokter','klinik'])->findOrFail($visitationId);

        $klinikId = $visitation->klinik_id;
        $clinicConfig = ClinicConfig::where('klinik_id', $klinikId)->first();
        if (!$clinicConfig || !$clinicConfig->base_url) {
            return response()->json(['ok' => false, 'error' => 'Clinic config or base_url not found'], 404);
        }

        $base = rtrim($clinicConfig->base_url, '/');

        // gather resep farmasi rows
        $resepRows = \App\Models\ERM\ResepFarmasi::where('visitation_id', $visitationId)->with('obat')->get();
        if ($resepRows->isEmpty()) {
            return response()->json(['ok' => false, 'error' => 'No resep farmasi found for this visitation'], 400);
        }

        // fetch KFA mappings for obat ids
        $obatIds = $resepRows->pluck('obat_id')->filter()->unique()->values()->all();
        $kfaMap = \App\Models\Satusehat\ObatKfa::whereIn('obat_id', $obatIds)->get()->keyBy('obat_id');

        // prepare headers & bearer
        $bearer = null;
        if (!empty($clinicConfig->token)) {
            $decoded = null; try { $decoded = json_decode($clinicConfig->token, true); } catch (\Throwable $e) { $decoded = null; }
            if (is_array($decoded)) {
                if (!empty($decoded['access_token'])) $bearer = $decoded['access_token'];
                elseif (!empty($decoded['accessToken'])) $bearer = $decoded['accessToken'];
                elseif (!empty($decoded['token'])) $bearer = $decoded['token'];
            }
            if (!$bearer) $bearer = $clinicConfig->token;
        }

        $headers = ['Accept' => 'application/json', 'Content-Type' => 'application/json'];
        if (!empty($clinicConfig->organization_id)) {
            $headers['Organization-Id'] = $clinicConfig->organization_id;
            $headers['organization-id'] = $clinicConfig->organization_id;
        }

        $responses = [];
        foreach ($resepRows as $row) {
            $obat = $row->obat;
            $mapping = $kfaMap[$row->obat_id] ?? null;
            if (!$mapping || empty($mapping->kfa_code)) {
                // skip unmapped obat
                $responses[] = ['skipped' => true, 'obat_id' => $row->obat_id, 'reason' => 'no kfa mapping'];
                continue;
            }

            $kfaCode = $mapping->kfa_code;

            $med = [
                'resourceType' => 'Medication',
                'meta' => ['profile' => ['https://fhir.kemkes.go.id/r4/StructureDefinition/Medication']],
                'identifier' => [[
                    'system' => 'http://sys-ids.kemkes.go.id/medication/' . ($clinicConfig->organization_id ?? ''),
                    'use' => 'official',
                    'value' => 'M' . uniqid()
                ]],
                'code' => ['coding' => [[
                    'system' => 'http://sys-ids.kemkes.go.id/kfa',
                    'code' => $kfaCode,
                    'display' => $obat->nama ?? ($obat->name ?? null)
                ]]],
                'status' => 'active',
            ];

            if (!empty($clinicConfig->organization_id)) {
                $med['manufacturer'] = ['reference' => 'Organization/' . $clinicConfig->organization_id];
            }

            // optional ingredient: single item referencing same kfa
            $med['ingredient'] = [[
                'itemCodeableConcept' => ['coding' => [[ 'system' => 'http://sys-ids.kemkes.go.id/kfa', 'code' => $kfaCode, 'display' => $obat->nama ?? null ]]],
                'isActive' => true
            ]];

            // extension medication type (Non-compound)
            $med['extension'] = [[
                'url' => 'https://fhir.kemkes.go.id/r4/StructureDefinition/MedicationType',
                'valueCodeableConcept' => ['coding' => [[ 'system' => 'http://terminology.kemkes.go.id/CodeSystem/medication-type', 'code' => 'NC', 'display' => 'Non-compound' ]]]
            ]];

            // send to Kemkes
            try {
                $req = Http::timeout(15)->withHeaders($headers);
                if ($bearer) $req = $req->withToken($bearer);
                $res = $req->post($base . '/Medication', $med);
            } catch (\Throwable $e) {
                $msg = $e->getMessage();
                if (stripos($msg, 'cURL error 60') !== false || stripos($msg, 'self-signed') !== false || stripos($msg, 'certificate') !== false) {
                    try {
                        $req = Http::withoutVerifying()->timeout(15)->withHeaders($headers);
                        if ($bearer) $req = $req->withToken($bearer);
                        $res = $req->post($base . '/Medication', $med);
                    } catch (\Throwable $e2) {
                        $responses[] = ['ok' => false, 'error' => 'SSL verify failed and insecure retry failed: ' . $e2->getMessage(), 'obat_id' => $row->obat_id];
                        continue;
                    }
                } else {
                    $responses[] = ['ok' => false, 'error' => $msg, 'obat_id' => $row->obat_id];
                    continue;
                }
            }

            if (!isset($res) || !$res->successful()) {
                $body = null; try { $body = $res ? $res->json() : null; } catch (\Throwable $e) { $body = $res ? $res->body() : null; }
                $status = $res ? $res->status() : 500;
                $responses[] = ['ok' => false, 'status' => $status, 'body' => $body, 'obat_id' => $row->obat_id];
                continue;
            }

            $responses[] = ['ok' => true, 'data' => $res->json(), 'payload_sent' => $med, 'obat_id' => $row->obat_id];
        }

        return response()->json(['ok' => true, 'results' => $responses]);
    }

    /**
     * Create Condition resource in Kemkes FHIR using asesmen penunjang (diagnosa kerja)
     */
    public function createKemkesCondition(Request $request, $visitationId)
    {
        $visitation = Visitation::with(['pasien','dokter','klinik'])->findOrFail($visitationId);

        $klinikId = $visitation->klinik_id;
        $clinicConfig = ClinicConfig::where('klinik_id', $klinikId)->first();
        if (!$clinicConfig || !$clinicConfig->base_url) {
            return response()->json(['ok' => false, 'error' => 'Clinic config or base_url not found'], 404);
        }

        // need encounter id (prefer from request)
        $encounterId = $request->input('encounter_id');
        if (!$encounterId) {
            // fallback: look up last saved encounter for this visitation
            $saved = SatusehatEncounter::where('visitation_id', $visitationId)->orderBy('created_at','desc')->first();
            if ($saved && $saved->satusehat_encounter_id) {
                $encounterId = $saved->satusehat_encounter_id;
            }
        }
        if (!$encounterId) {
            return response()->json(['ok' => false, 'error' => 'Encounter id is required (create encounter first)'], 400);
        }

        // subject (patient) mapping
        $patientMap = PatientGet::where('pasien_id', $visitation->pasien_id)->latest()->first();
        if (!$patientMap || !$patientMap->satusehat_patient_id) {
            return response()->json(['ok' => false, 'error' => 'Satusehat patient id not found. Please Get Data first.'], 400);
        }
        $subjectRef = 'Patient/' . $patientMap->satusehat_patient_id;

        // Diagnosa utama = the first diagnosa kerja that is a real ICD-10 code (never guessed from free text)
        $asesmen = \App\Models\ERM\AsesmenPenunjang::where('visitation_id', $visitationId)->first();
        $diagnoses = \App\Support\Icd10Code::diagnoses($asesmen);
        if (!$diagnoses['coded']) {
            return response()->json([
                'ok' => false,
                'status' => 400,
                'error' => \App\Support\Icd10Code::notSendableMessage($diagnoses),
                'invalid' => $diagnoses['invalid'],
            ], 400);
        }
        $codingSystem = \App\Support\Icd10Code::FHIR_SYSTEM;
        $codingCode = $diagnoses['coded'][0]['code'];
        $codingDisplay = $diagnoses['coded'][0]['display'];

        $condition = [
            'resourceType' => 'Condition',
            'clinicalStatus' => ['coding' => [[
                'system' => 'http://terminology.hl7.org/CodeSystem/condition-clinical',
                'code' => 'active',
                'display' => 'Active'
            ]]],
            'category' => [['coding' => [[
                'system' => 'http://terminology.hl7.org/CodeSystem/condition-category',
                'code' => 'encounter-diagnosis',
                'display' => 'Encounter Diagnosis'
            ]]]],
            'code' => ['coding' => [[
                'system' => $codingSystem,
                'code' => $codingCode,
                'display' => $codingDisplay
            ]]],
            'subject' => ['reference' => $subjectRef, 'display' => $visitation->pasien->nama ?? null],
            'encounter' => ['reference' => 'Encounter/' . $encounterId, 'display' => 'Kunjungan ' . ($visitation->pasien->nama ?? '')]
        ];

        // headers & bearer token (reuse logic)
        $bearer = null;
        if (!empty($clinicConfig->token)) {
            $decoded = null; try { $decoded = json_decode($clinicConfig->token, true); } catch (\Throwable $e) { $decoded = null; }
            if (is_array($decoded)) {
                if (!empty($decoded['access_token'])) $bearer = $decoded['access_token'];
                elseif (!empty($decoded['accessToken'])) $bearer = $decoded['accessToken'];
                elseif (!empty($decoded['token'])) $bearer = $decoded['token'];
            }
            if (!$bearer) $bearer = $clinicConfig->token;
        }

        $headers = ['Accept' => 'application/json', 'Content-Type' => 'application/json'];
        if (!empty($clinicConfig->organization_id)) {
            $headers['Organization-Id'] = $clinicConfig->organization_id;
            $headers['organization-id'] = $clinicConfig->organization_id;
        }

        $url = rtrim($clinicConfig->base_url, '/') . '/Condition';

        $insecureRetry = false;
        try {
            $req = Http::timeout(15)->withHeaders($headers);
            if ($bearer) $req = $req->withToken($bearer);
            $res = $req->post($url, $condition);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (stripos($msg, 'cURL error 60') !== false || stripos($msg, 'self-signed') !== false || stripos($msg, 'certificate') !== false) {
                try {
                    $insecureRetry = true;
                    $req = Http::withoutVerifying()->timeout(15)->withHeaders($headers);
                    if ($bearer) $req = $req->withToken($bearer);
                    $res = $req->post($url, $condition);
                } catch (\Throwable $e2) {
                    return response()->json(['ok' => false, 'error' => 'SSL verify failed and insecure retry failed: ' . $e2->getMessage()], 500);
                }
            } else {
                return response()->json(['ok' => false, 'error' => $msg], 500);
            }
        }

        if (!isset($res) || !$res->successful()) {
            $body = null; try { $body = $res ? $res->json() : null; } catch (\Throwable $e) { $body = $res ? $res->body() : null; }
            $status = $res ? $res->status() : 500;
            return response()->json(['ok' => false, 'status' => $status, 'body' => $body], $status);
        }

        $body = $res->json();
        $payload = ['ok' => true, 'data' => $body, 'payload_sent' => $condition];
        if ($insecureRetry) $payload['warning'] = 'Request retried with SSL verification disabled';
        return response()->json($payload);
    }

    public function data(Request $request)
    {
        // Accept optional date range parameters `start` and `end` (YYYY-MM-DD).
        $start = $request->get('start');
        $end = $request->get('end');

        $query = Visitation::with(['pasien', 'dokter', 'klinik', 'asesmenPenunjang']);

        // Restrict to active visitations only (status_kunjungan = 2)
        $query = $query->where('status_kunjungan', 2);
        // Restrict to specific jenis kunjungan (jenis_kunjungan = 1)
        $query = $query->where('jenis_kunjungan', 1);

        if ($start && $end) {
            try {
                $startDate = Carbon::parse($start)->toDateString();
                $endDate = Carbon::parse($end)->toDateString();
                $query = $query->whereBetween('tanggal_visitation', [$startDate, $endDate]);
            } catch (\Throwable $e) {
                $query = $query->whereDate('tanggal_visitation', Carbon::today()->toDateString());
            }
        } elseif ($start) {
            try {
                $date = Carbon::parse($start)->toDateString();
                $query = $query->whereDate('tanggal_visitation', $date);
            } catch (\Throwable $e) {
                $query = $query->whereDate('tanggal_visitation', Carbon::today()->toDateString());
            }
        } else {
            $query = $query->whereDate('tanggal_visitation', Carbon::today()->toDateString());
        }

        $query = $query->orderBy('waktu_kunjungan', 'asc');

        // Apply optional filters: klinik_id and encounter_status
        $filterKlinik = request()->get('klinik_id');
        if ($filterKlinik) {
            $query = $query->where('klinik_id', $filterKlinik);
        }
        $filterStatus = request()->get('encounter_status');
        if ($filterStatus) {
            // filter visitations that have a satusehat_encounters record with matching status
            $query = $query->whereExists(function($q) use ($filterStatus) {
                $q->select(DB::raw(1))
                    ->from('satusehat_encounters')
                    ->whereRaw('satusehat_encounters.visitation_id = erm_visitations.id')
                    ->where('satusehat_encounters.status', $filterStatus);
            });
        }

        // Filter by diagnosa presence (default: only rows that have diagnosa)
        $filterDiagnosa = $request->get('has_diagnosa', '1');
        if ($filterDiagnosa === '1') {
            // only visitations with at least one diagnosakerja_* filled
            $query = $query->whereHas('asesmenPenunjang', function ($q) {
                $q->where(function ($qq) {
                    $qq->whereNotNull('diagnosakerja_1')
                       ->orWhereNotNull('diagnosakerja_2')
                       ->orWhereNotNull('diagnosakerja_3')
                       ->orWhereNotNull('diagnosakerja_4')
                       ->orWhereNotNull('diagnosakerja_5')
                       ->orWhereNotNull('diagnosakerja_6');
                });
            });
        } elseif ($filterDiagnosa === '0') {
            // only visitations without any diagnosakerja_* (or no asesmenPenunjang at all)
            $query = $query->where(function ($q) {
                $q->whereDoesntHave('asesmenPenunjang')
                  ->orWhereHas('asesmenPenunjang', function ($qq) {
                      $qq->whereNull('diagnosakerja_1')
                         ->whereNull('diagnosakerja_2')
                         ->whereNull('diagnosakerja_3')
                         ->whereNull('diagnosakerja_4')
                         ->whereNull('diagnosakerja_5')
                         ->whereNull('diagnosakerja_6');
                  });
            });
        }

        $rows = $query->get()->map(function ($v) {
            $pasienId = $v->pasien_id;
            $patientUrl = $pasienId ? route('erm.pasien.show', $pasienId) : '#';
            $nik = $v->pasien->nik ?? null;
            $identityDocument = $v->pasien->identity_document ?? null;
            $diagnosa = null;
            $asesmen = $v->asesmenPenunjang ?? null;
            if ($asesmen) {
                foreach (['diagnosakerja_1','diagnosakerja_2','diagnosakerja_3','diagnosakerja_4','diagnosakerja_5','diagnosakerja_6'] as $f) {
                    if (!empty($asesmen->{$f})) { $diagnosa = $asesmen->{$f}; break; }
                }
            }
                $dokterName = optional(optional($v->dokter)->user)->name ?: ($v->dokter->nama ?? null);
                $klinikName = $v->klinik->nama ?? $v->klinik->name ?? null;

                $dokterHtml = null;
                // determine klinik id if available
                $klinikId = $v->klinik->id ?? $v->klinik->klinik_id ?? null;
                // choose badge HTML based on klinik id: 1 = blue, 2 = pink, default = info
                $klinikBadge = '';
                if ($klinikName) {
                    if ($klinikId == 1) {
                        $klinikBadge = '<span class="badge badge-primary">' . e($klinikName) . '</span>';
                    } elseif ($klinikId == 2) {
                        $klinikBadge = '<span class="badge" style="background:#ff69b4;color:#fff;">' . e($klinikName) . '</span>';
                    } else {
                        $klinikBadge = '<span class="badge badge-info">' . e($klinikName) . '</span>';
                    }
                }

                if ($dokterName) {
                    $dokterHtml = $dokterName;
                    if ($klinikBadge) {
                        $dokterHtml .= '<br>' . $klinikBadge;
                    }
                } else {
                    if ($klinikBadge) {
                        $dokterHtml = $klinikBadge;
                    }
                }

                // compute encounter status badge before returning the row array
                $enc = optional(\App\Models\Satusehat\Encounter::where('visitation_id', $v->id)->orderBy('created_at','desc')->first())->status;
                $encounterBadge = '';
                if ($enc) {
                    $encLower = strtolower($enc);
                    if ($encLower === 'finished') {
                        $encounterBadge = '<span class="badge badge-success">' . e($enc) . '</span>';
                    } elseif ($encLower === 'arrived') {
                        $encounterBadge = '<span class="badge badge-primary">' . e($enc) . '</span>';
                    } elseif ($encLower === 'in-progress' || $encLower === 'in progress' || $encLower === 'inprogress') {
                        $encounterBadge = '<span class="badge badge-warning">' . e($enc) . '</span>';
                    } else {
                        $encounterBadge = '<span class="badge badge-secondary">' . e($enc) . '</span>';
                    }
                }

                $identityDisplay = $v->pasien->identity_display ?? ((isset($v->pasien->nik) && $v->pasien->nik) ? 'NIK: ' . $v->pasien->nik : '');
                $isKtpIdentity = $identityDocument === ErmPasien::IDENTITY_DOCUMENT_KTP;
                $satusehatWarning = $isKtpIdentity ? null : 'Kemkes lookup hanya untuk pasien dengan dokumen KTP/NIK.';
                $getDataButtonAttributes = $isKtpIdentity
                    ? 'data-visitation-id="' . $v->id . '" class="btn btn-sm btn-outline-info btn-get-data"'
                    : 'data-visitation-id="' . $v->id . '" class="btn btn-sm btn-outline-secondary" disabled title="' . e($satusehatWarning) . '"';

                return [
                'id' => $v->id,
                'tanggal_visitation' => $v->tanggal_visitation,
                'nik' => $nik,
                'identity_display' => $identityDisplay,
                'satusehat_warning' => $satusehatWarning,
                'waktu_kunjungan' => $v->waktu_kunjungan,
                'no_antrian' => $v->no_antrian,
                // render pasien name with identity document below (muted small text)
                'pasien' => (isset($v->pasien->nama) ? e($v->pasien->nama) : '')
                    . (!empty($identityDisplay) && $identityDisplay !== '-' ? '<br><small class="text-muted">' . e($identityDisplay) . '</small>' : '')
                    . ($satusehatWarning ? '<br><small class="text-warning">' . e($satusehatWarning) . '</small>' : ''),
                'encounter_status' => $encounterBadge,
                'diagnosa' => $diagnosa,
                'dokter' => $dokterHtml,
                'klinik' => $klinikName,
                'status_kunjungan' => $v->status_kunjungan,
                    'aksi' => '<div class="btn-group" role="group" aria-label="Aksi">'
                        . '<button ' . $getDataButtonAttributes . '>Get Data</button> '
                        . '<button data-visitation-id="' . $v->id . '" class="btn btn-sm btn-success btn-create-encounter">Create Encounter</button> '
                        . '<button data-visitation-id="' . $v->id . '" class="btn btn-sm btn-info btn-update-encounter">Update Encounter</button> '
                        . '<button data-visitation-id="' . $v->id . '" class="btn btn-sm btn-danger btn-finish-encounter">Finish Encounter</button> '
                        . '<button data-visitation-id="' . $v->id . '" class="btn btn-sm btn-warning btn-send-condition">Send Condition</button> '
                        . '<button data-visitation-id="' . $v->id . '" class="btn btn-sm btn-secondary btn-send-medication">Send Medication</button>'
                        . '</div>'
            ];
        });

        return response()->json(['data' => $rows]);
    }

    /**
     * Fetch patient data from Kemkes FHIR based on visitation's klinik config and pasien KTP/NIK.
     */
    public function getKemkesPatient(Request $request, $visitationId)
    {
        $visitation = Visitation::with('pasien')->findOrFail($visitationId);

        $klinikId = $visitation->klinik_id;
        $clinicConfig = ClinicConfig::where('klinik_id', $klinikId)->first();
        if (!$clinicConfig || !$clinicConfig->base_url) {
            return response()->json(['ok' => false, 'error' => 'Clinic config or base_url not found'], 404);
        }

        $identityDocument = $visitation->pasien->identity_document ?? null;
        if ($identityDocument !== ErmPasien::IDENTITY_DOCUMENT_KTP) {
            $documentLabel = $visitation->pasien->identity_label ?? 'Identitas';

            return response()->json([
                'ok' => false,
                'error' => 'Kemkes lookup hanya mendukung pasien dengan dokumen KTP/NIK. Dokumen pasien ini: ' . $documentLabel,
            ], 400);
        }

        $nik = $visitation->pasien->identity_number ?? $visitation->pasien->nik ?? null;
        if (!$nik) {
            return response()->json(['ok' => false, 'error' => 'Nomor KTP/NIK pasien tidak tersedia'], 400);
        }

        $identifier = 'https://fhir.kemkes.go.id/id/nik|' . $nik;
        $base = rtrim($clinicConfig->base_url, '/');
        $url = $base . '/Patient?identifier=' . urlencode($identifier);

        // Prepare bearer and headers
        $bearer = null;
        if (!empty($clinicConfig->token)) {
            $decoded = null;
            try { $decoded = json_decode($clinicConfig->token, true); } catch (\Throwable $e) { $decoded = null; }
            if (is_array($decoded)) {
                if (!empty($decoded['access_token'])) $bearer = $decoded['access_token'];
                elseif (!empty($decoded['accessToken'])) $bearer = $decoded['accessToken'];
                elseif (!empty($decoded['token'])) $bearer = $decoded['token'];
            }
            if (!$bearer) $bearer = $clinicConfig->token;
        }

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ];
        if (!empty($clinicConfig->organization_id)) {
            $headers['Organization-Id'] = $clinicConfig->organization_id;
            $headers['organization-id'] = $clinicConfig->organization_id;
        }

        // Attempt request normally first. On SSL cert errors, retry with verification disabled.
        $insecureRetry = false;
        try {
            $req = Http::timeout(10)->withHeaders($headers);
            if ($bearer) $req = $req->withToken($bearer);
            $res = $req->get($url);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            // Detect common SSL certificate error indicators (cURL error 60 or self-signed cert)
            if (stripos($msg, 'cURL error 60') !== false || stripos($msg, 'self-signed') !== false || stripos($msg, 'certificate') !== false) {
                // Retry insecurely
                try {
                    $insecureRetry = true;
                    $req = Http::withoutVerifying()->timeout(10)->withHeaders($headers);
                    if ($bearer) $req = $req->withToken($bearer);
                    $res = $req->get($url);
                } catch (\Throwable $e2) {
                    return response()->json(['ok' => false, 'error' => 'SSL verify failed and insecure retry failed: ' . $e2->getMessage()], 500);
                }
            } else {
                return response()->json(['ok' => false, 'error' => $msg], 500);
            }
        }

        if (!isset($res) || !$res->successful()) {
            $body = null;
            try { $body = $res ? $res->json() : null; } catch (\Throwable $e) { $body = $res ? $res->body() : null; }
            $status = $res ? $res->status() : 500;
            return response()->json(['ok' => false, 'status' => $status, 'body' => $body], $status);
        }

        $body = $res->json();

        // Try to extract satusehat patient id from response (bundle -> entry[0].resource.id)
        $satusehatId = null;
        if (is_array($body) && !empty($body['entry']) && is_array($body['entry'])) {
            $first = $body['entry'][0] ?? null;
            if ($first && !empty($first['resource']['id'])) {
                $satusehatId = $first['resource']['id'];
            }
        }

        // Save mapping to satusehat_patients table when we have an id
        if ($satusehatId) {
            try {
                $pasienId = $visitation->pasien_id ?? null;
                $pasienName = $visitation->pasien->nama ?? null;
                PatientGet::updateOrCreate(
                    ['pasien_id' => $pasienId, 'visitation_id' => $visitation->id],
                    [
                        'pasien_name' => $pasienName,
                        'satusehat_patient_id' => $satusehatId,
                        'raw_response' => json_encode($body, JSON_UNESCAPED_UNICODE)
                    ]
                );
            } catch (\Throwable $e) {
                // ignore DB save errors but include warning
            }
        }

        $payload = ['ok' => true, 'data' => $body];
        if ($insecureRetry) $payload['warning'] = 'Request retried with SSL verification disabled';
        if ($satusehatId) $payload['satusehat_id'] = $satusehatId;
        return response()->json($payload);
    }
}
