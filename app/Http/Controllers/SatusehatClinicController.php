<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Satusehat\ClinicConfig;
use GuzzleHttp\Client;

class SatusehatClinicController extends Controller
{
    // Klinik configs are managed in Admin > Klinik Setting; this controller keeps the SatuSehat-specific actions
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

    // Attach a config that has no klinik (or is a second config for a klinik) to a klinik without one
    public function link(Request $request, ClinicConfig $clinicConfig)
    {
        $data = $request->validate(['klinik_id' => 'required|exists:erm_klinik,id']);
        if (ClinicConfig::where('klinik_id', $data['klinik_id'])->where('id', '!=', $clinicConfig->id)->exists()) {
            return response()->json(['ok' => false, 'message' => 'Klinik ini sudah memiliki konfigurasi SatuSehat'], 422);
        }
        $clinicConfig->update(['klinik_id' => $data['klinik_id']]);

        return response()->json(['ok' => true, 'message' => 'Konfigurasi dihubungkan ke klinik']);
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
