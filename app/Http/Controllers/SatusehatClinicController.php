<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Satusehat\ClinicConfig;
use App\Models\ERM\Klinik;
use Yajra\DataTables\Facades\DataTables;
use GuzzleHttp\Client;

class SatusehatClinicController extends Controller
{
    public function index()
    {
        return view('satusehat.clinics.index');
    }

    /**
     * Data for Yajra DataTable (server-side)
     */
    public function data(Request $request)
    {
        $query = ClinicConfig::with('klinik')->select('satusehat_clinic_configs.*');
        return DataTables::of($query)
            ->addColumn('klinik', function ($row) {
                return $row->klinik->nama ?? '-';
            })
            ->addColumn('actions', function ($row) {
                $edit = '<button data-id="' . $row->id . '" class="btn btn-sm btn-secondary btn-edit">Edit</button>';
                $token = '<button data-id="' . $row->id . '" class="btn btn-sm btn-info btn-token">Token</button>';
                $del = '<button data-id="' . $row->id . '" class="btn btn-sm btn-danger btn-delete">Hapus</button>';
                return $edit . ' ' . $token . ' ' . $del;
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    /**
     * Request access token from the configured auth_url using client credentials
     */
    public function requestToken(Request $request, ClinicConfig $clinicConfig)
    {
        $authUrl = rtrim($clinicConfig->auth_url ?? '', '/');
        if (empty($authUrl) || empty($clinicConfig->client_id) || empty($clinicConfig->client_secret)) {
            return response()->json(['ok' => false, 'message' => 'Auth URL, client_id or client_secret belum dikonfigurasi'], 422);
        }

        $tokenEndpoint = $authUrl . '/accesstoken';

        // Check cached token: if exists and not expired, return it
        if (!empty($clinicConfig->token) && !empty($clinicConfig->token_expires_at)) {
            $expiresAt = $clinicConfig->token_expires_at;
            if ($expiresAt instanceof \DateTimeInterface ? $expiresAt->getTimestamp() > now()->getTimestamp() : strtotime($expiresAt) > time()) {
                return response()->json(['ok' => true, 'data' => $clinicConfig->token, 'cached' => true, 'message' => 'Menggunakan token yang masih valid']);
            }
        }

        // Guzzle verify option: allow control from .env
        // SATUSEHAT_SSL_VERIFY=false  -> disable verification (development only)
        // SATUSEHAT_CACERT_PATH=/path/to/cacert.pem -> use custom CA bundle
        $verify = true;
        if (env('SATUSEHAT_SSL_VERIFY') === 'false' || env('SATUSEHAT_SSL_VERIFY') === false) {
            $verify = false;
        } elseif ($path = env('SATUSEHAT_CACERT_PATH')) {
            $verify = $path;
        }

        $guzzleOptions = ['timeout' => 10, 'verify' => $verify];
        $client = new Client($guzzleOptions);

        try {
            // Try to mimic Postman: POST to /accesstoken?grant_type=client_credentials
            $endpointWithGrant = $tokenEndpoint . '?grant_type=client_credentials';

            $res = $client->post($endpointWithGrant, [
                'headers' => [ 'Accept' => 'application/json' ],
                'form_params' => [
                    'client_id' => trim($clinicConfig->client_id),
                    'client_secret' => trim($clinicConfig->client_secret),
                ],
                'http_errors' => false,
            ]);

            $status = $res->getStatusCode();

            if ($status === 429) {
                $retryAfter = $res->getHeaderLine('Retry-After');
                $message = 'Terkena batasan (429 Too Many Requests)';
                if (!empty($retryAfter)) {
                    $message .= ", coba lagi setelah {$retryAfter} detik";
                }
                return response()->json(['ok' => false, 'message' => $message, 'retry_after' => $retryAfter], 429);
            }

            $body = json_decode((string)$res->getBody(), true);

            // If Postman-style request succeeded, save token
            if ($status >= 200 && $status < 300 && $body) {
                $access = $body['access_token'] ?? null;
                $clinicConfig->token = $access;
                if (!empty($body['expires_in'])) {
                    $clinicConfig->token_expires_at = now()->addSeconds((int)$body['expires_in']);
                } else {
                    $clinicConfig->token_expires_at = null;
                }
                $clinicConfig->save();
                return response()->json(['ok' => true, 'data' => ['access_token' => $access, 'expires_in' => $body['expires_in'] ?? null], 'message' => 'Token berhasil diambil dan disimpan (postman-style)']);
            }

            // If Postman-style failed with 400/401, try Basic auth as fallback
            if (in_array($status, [400, 401])) {
                $basic = base64_encode(trim($clinicConfig->client_id) . ':' . trim($clinicConfig->client_secret));
                $res2 = $client->post($tokenEndpoint, [
                    'headers' => [
                        'Authorization' => 'Basic ' . $basic,
                        'Accept' => 'application/json',
                    ],
                    'form_params' => [ 'grant_type' => 'client_credentials' ],
                    'http_errors' => false,
                ]);

                $status2 = $res2->getStatusCode();
                if ($status2 === 429) {
                    $retryAfter = $res2->getHeaderLine('Retry-After');
                    $message = 'Terkena batasan (429 Too Many Requests)';
                    if (!empty($retryAfter)) {
                        $message .= ", coba lagi setelah {$retryAfter} detik";
                    }
                    return response()->json(['ok' => false, 'message' => $message, 'retry_after' => $retryAfter], 429);
                }

                $body2 = json_decode((string)$res2->getBody(), true);
                if ($status2 >= 200 && $status2 < 300 && $body2) {
                    $access2 = $body2['access_token'] ?? null;
                    $clinicConfig->token = $access2;
                    if (!empty($body2['expires_in'])) {
                        $clinicConfig->token_expires_at = now()->addSeconds((int)$body2['expires_in']);
                    } else {
                        $clinicConfig->token_expires_at = null;
                    }
                    $clinicConfig->save();
                    return response()->json(['ok' => true, 'data' => ['access_token' => $access2, 'expires_in' => $body2['expires_in'] ?? null], 'message' => 'Token berhasil diambil dan disimpan (basic auth)']);
                }

                return response()->json(['ok' => false, 'status' => $status2, 'data' => $body2, 'message' => 'Gagal autentikasi (basic auth)'], $status2 >= 400 && $status2 < 600 ? $status2 : 500);
            }

            // Otherwise return the provider response (do not save error responses)
            return response()->json(['ok' => false, 'status' => $status, 'data' => $body, 'message' => 'Gagal autentikasi (postman-style)'], $status >= 400 && $status < 600 ? $status : 500);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'message' => 'Gagal mengambil token: ' . $e->getMessage()], 500);
        }
    }

    public function create()
    {
        // not used (modal form used instead)
        $kliniks = Klinik::orderBy('nama')->get();
        return view('satusehat.clinics.form', ['kliniks' => $kliniks, 'config' => new ClinicConfig()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'klinik_id' => 'nullable|exists:erm_klinik,id',
            'auth_url' => 'nullable|string|max:255',
            'base_url' => 'nullable|string|max:255',
            'consent_url' => 'nullable|string|max:255',
            'client_id' => 'nullable|string|max:255',
            'client_secret' => 'nullable|string|max:255',
            'organization_id' => 'nullable|string|max:255',
            'token' => 'nullable|string',
        ]);

        // default SatuSehat endpoints
        $defaults = [
            'auth_url' => 'https://api-satusehat.kemkes.go.id/oauth2/v1',
            'base_url' => 'https://api-satusehat.kemkes.go.id/fhir-r4/v1',
            'consent_url' => 'https://api-satusehat.kemkes.go.id/consent/v1',
        ];

        $data = array_merge($defaults, $data);

        $config = ClinicConfig::create($data);

        if ($request->ajax()) {
            return response()->json(['ok' => true, 'data' => $config, 'message' => 'Konfigurasi klinik berhasil ditambahkan']);
        }

        return redirect()->route('satusehat.clinics.index')->with('success','Konfigurasi klinik berhasil ditambahkan');
    }

    public function edit(Request $request, ClinicConfig $clinicConfig)
    {
        // if requested via AJAX, return JSON for modal population
        if ($request->ajax()) {
            return response()->json($clinicConfig->load('klinik'));
        }

        $kliniks = Klinik::orderBy('nama')->get();
        return view('satusehat.clinics.form', ['kliniks' => $kliniks, 'config' => $clinicConfig]);
    }

    public function update(Request $request, ClinicConfig $clinicConfig)
    {
        $data = $request->validate([
            'klinik_id' => 'nullable|exists:erm_klinik,id',
            'auth_url' => 'nullable|string|max:255',
            'base_url' => 'nullable|string|max:255',
            'consent_url' => 'nullable|string|max:255',
            'client_id' => 'nullable|string|max:255',
            'client_secret' => 'nullable|string|max:255',
            'organization_id' => 'nullable|string|max:255',
            'token' => 'nullable|string',
        ]);

        // ensure defaults exist if not provided during update
        $defaults = [
            'auth_url' => 'https://api-satusehat.kemkes.go.id/oauth2/v1',
            'base_url' => 'https://api-satusehat.kemkes.go.id/fhir-r4/v1',
            'consent_url' => 'https://api-satusehat.kemkes.go.id/consent/v1',
        ];

        $data = array_merge($defaults, $data);

        $clinicConfig->update($data);

        if ($request->ajax()) {
            return response()->json(['ok' => true, 'data' => $clinicConfig, 'message' => 'Konfigurasi klinik berhasil diperbarui']);
        }

        return redirect()->route('satusehat.clinics.index')->with('success','Konfigurasi klinik berhasil diperbarui');
    }

    public function destroy(Request $request, ClinicConfig $clinicConfig)
    {
        $clinicConfig->delete();

        if ($request->ajax()) {
            return response()->json(['ok' => true, 'message' => 'Konfigurasi klinik dihapus']);
        }

        return redirect()->route('satusehat.clinics.index')->with('success','Konfigurasi klinik dihapus');
    }
}
