<?php

namespace App\Http\Controllers\Satusehat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\Visitation;
use App\Models\Satusehat\ClinicConfig;
use App\Models\Satusehat\PatientGet;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class PasienController extends Controller
{
    public function index()
    {
        return view('satusehat.pasiens.index');
    }

    public function data(Request $request)
    {
        $today = Carbon::today()->toDateString();

        $query = Visitation::with(['pasien', 'dokter', 'klinik'])
            ->whereDate('tanggal_visitation', $today)
            ->orderBy('waktu_kunjungan', 'asc');

        $rows = $query->get()->map(function ($v) {
            $pasienId = $v->pasien_id;
            $patientUrl = $pasienId ? route('erm.pasien.show', $pasienId) : '#';
            return [
                'id' => $v->id,
                'tanggal_visitation' => $v->tanggal_visitation,
                'waktu_kunjungan' => $v->waktu_kunjungan,
                'no_antrian' => $v->no_antrian,
                'pasien' => $v->pasien->nama ?? null,
                'dokter' => $v->dokter->nama ?? null,
                'klinik' => $v->klinik->nama ?? $v->klinik->name ?? null,
                'status_kunjungan' => $v->status_kunjungan,
                'aksi' => '<a href="' . $patientUrl . '" class="btn btn-sm btn-primary mr-1">Lihat</a>'
                    . '<button data-visitation-id="' . $v->id . '" class="btn btn-sm btn-outline-info btn-get-data">Get Data</button>'
            ];
        });

        return response()->json(['data' => $rows]);
    }

    /**
     * Fetch patient data from Kemkes FHIR based on visitation's klinik config and pasien nik
     */
    public function getKemkesPatient(Request $request, $visitationId)
    {
        $visitation = Visitation::with('pasien')->findOrFail($visitationId);

        $klinikId = $visitation->klinik_id;
        $clinicConfig = ClinicConfig::where('klinik_id', $klinikId)->first();
        if (!$clinicConfig || !$clinicConfig->base_url) {
            return response()->json(['ok' => false, 'error' => 'Clinic config or base_url not found'], 404);
        }

        $nik = $visitation->pasien->nik ?? null;
        if (!$nik) {
            return response()->json(['ok' => false, 'error' => 'Patient NIK not available'], 400);
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
