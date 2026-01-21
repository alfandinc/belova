<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WaMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WaMessageController extends Controller
{
    // Public endpoint for Node to POST message logs
    public function store(Request $request)
    {
        $data = $request->validate([
            'session_client_id' => 'nullable|string',
            'direction' => 'required|string',
            'from' => 'nullable|string',
            'to' => 'nullable|string',
            'body' => 'nullable|string',
            'message_id' => 'nullable|string',
            'raw' => 'nullable'
        ]);

        // attempt to normalize phone-like values
        $normalizeAddr = function ($val) {
            if (empty($val)) return null;
            // if raw meta exists (wa-bot sends meta.from_normalized), prefer that
            // otherwise strip domain suffix after '@'
            if (is_string($val) && strpos($val, '@') !== false) {
                return explode('@', $val)[0];
            }
            return $val;
        };

        // If raw contains JSON with meta.normalized values, prefer them
        if (!empty($data['raw'])) {
            $decoded = null;
            try { $decoded = json_decode($data['raw'], true); } catch (\Exception $e) { $decoded = null; }
            if (is_array($decoded) && isset($decoded['meta'])) {
                if (isset($decoded['meta']['from_normalized'])) {
                    $data['from'] = $decoded['meta']['from_normalized'];
                }
                if (isset($decoded['meta']['to_normalized'])) {
                    $data['to'] = $decoded['meta']['to_normalized'];
                }
            }
        }

        // fallback normalization
        $data['from'] = $normalizeAddr($data['from'] ?? null);
        $data['to'] = $normalizeAddr($data['to'] ?? null);

        // attempt to auto-link pasien by phone number
        $direction = strtolower($data['direction'] ?? '');
        // choose candidate: incoming -> from, outgoing -> to (prefer the pasien number)
        $candidate = null;
        if (strpos($direction, 'in') === 0 || strpos($direction, 'incoming') === 0) {
            $candidate = $data['from'] ?? null;
        } else {
            $candidate = $data['to'] ?? null;
        }

        if ($candidate) {
            // normalize digits-only
            $digits = preg_replace('/\D+/', '', $candidate);
            // canonicalize: convert leading 0 or 8 to 62-prefixed form
            $canon = function($num){
                if (!$num) return null;
                if (strpos($num, '62') === 0) return $num;
                if (strpos($num, '0') === 0) return '62' . substr($num, 1);
                if (strpos($num, '8') === 0) return '62' . $num;
                return $num;
            };
            $canonCandidate = $canon($digits);

            // fetch possible matches by loose contains on digits to reduce rows, then verify canonical equality in PHP
            $rows = DB::table('erm_pasiens')
                ->whereRaw("REPLACE(no_hp, ' ', '') LIKE ?", ["%{$digits}%"] )
                ->orWhereRaw("REPLACE(no_hp2, ' ', '') LIKE ?", ["%{$digits}%"] )
                ->limit(10)
                ->get(['id','no_hp','no_hp2']);

            foreach ($rows as $pas) {
                $n1 = preg_replace('/\\D+/', '', $pas->no_hp ?: '');
                $n2 = preg_replace('/\\D+/', '', $pas->no_hp2 ?: '');
                if ($canon($n1) === $canonCandidate || $canon($n2) === $canonCandidate) {
                    $data['pasien_id'] = $pas->id;
                    break;
                }
            }

            // If still not matched, log debug info to help diagnose formats
            if (empty($data['pasien_id'])) {
                try {
                    Log::info('WaMessage: no pasien match', [
                        'direction' => $direction,
                        'candidate_raw' => $candidate,
                        'digits' => $digits,
                        'canon' => $canonCandidate,
                        'rows_inspected' => array_map(function($r){ return ['id'=>$r->id,'no_hp'=>$r->no_hp,'no_hp2'=>$r->no_hp2]; }, (array)$rows->toArray()),
                        'payload_example' => isset($data['raw']) ? (strlen($data['raw']) > 1000 ? substr($data['raw'],0,1000) : $data['raw']) : null
                    ]);
                } catch (\Exception $e) {
                    // swallow logging errors
                }
            }
        }

        // If still no pasien_id and incoming used a non-phone 'from' (WhatsApp internal id),
        // attempt to search the decoded raw payload for any phone-like strings.
        if (empty($data['pasien_id']) && !empty($data['raw'])) {
            try {
                $decoded = json_decode($data['raw'], true);
                $found = null;
                $searchPhone = function($v) use (&$searchPhone, &$found) {
                    if ($found) return;
                    if (is_string($v)) {
                        // strip non-digits and check plausible phone length
                        $d = preg_replace('/\\D+/', '', $v);
                        if (strlen($d) >= 9 && strlen($d) <= 15) {
                            // heuristic: Indonesian numbers often start with 8 or 62
                            if (strpos($d, '62') === 0 || strpos($d, '8') === 0 || strpos($d, '0') === 0) {
                                $found = $d; return;
                            }
                        }
                    } elseif (is_array($v)) {
                        foreach ($v as $sub) { $searchPhone($sub); if ($found) return; }
                    }
                };
                if (is_array($decoded)) $searchPhone($decoded);
                if ($found) {
                    // try to match this phone
                    $digits = $found;
                    $canon = function($num){
                        if (!$num) return null;
                        if (strpos($num, '62') === 0) return $num;
                        if (strpos($num, '0') === 0) return '62' . substr($num, 1);
                        if (strpos($num, '8') === 0) return '62' . $num;
                        return $num;
                    };
                    $canonCandidate = $canon($digits);
                    $rows = DB::table('erm_pasiens')
                        ->whereRaw("REPLACE(no_hp, ' ', '') LIKE ?", ["%{$digits}%"] )
                        ->orWhereRaw("REPLACE(no_hp2, ' ', '') LIKE ?", ["%{$digits}%"] )
                        ->limit(10)
                        ->get(['id','no_hp','no_hp2']);
                    foreach ($rows as $pas) {
                        $n1 = preg_replace('/\\D+/', '', $pas->no_hp ?: '');
                        $n2 = preg_replace('/\\D+/', '', $pas->no_hp2 ?: '');
                        if ($canon($n1) === $canonCandidate || $canon($n2) === $canonCandidate) {
                            $data['pasien_id'] = $pas->id; break;
                        }
                    }
                    if (empty($data['pasien_id'])) {
                        Log::info('WaMessage: found phone in raw but no pasien match', ['found'=>$found,'canon'=>$canonCandidate]);
                    } else {
                        Log::info('WaMessage: matched pasien from raw-found phone', ['found'=>$found,'pasien_id'=>$data['pasien_id']]);
                    }
                }
            } catch (\Exception $e) {
                // ignore
            }
        }

        // Heuristic: incoming messages where 'from' is internal id — infer pasien by recent outgoing messages
        if (empty($data['pasien_id']) && !empty($direction) && (strpos($direction, 'in') === 0 || strpos($direction, 'incoming') === 0)) {
            try {
                $botNumber = preg_replace('/\\D+/', '', $data['to'] ?? '');
                if ($botNumber) {
                    $lastOut = DB::table('wa_messages')
                        ->where('session_client_id', $data['session_client_id'] ?? null)
                        ->where('direction', 'out')
                        ->whereRaw("REPLACE(`from`, ' ', '') = ?", [$botNumber])
                        ->orderByDesc('created_at')
                        ->first();
                    if ($lastOut && !empty($lastOut->to)) {
                        $candidatePhone = preg_replace('/\\D+/', '', $lastOut->to);
                        $canon = function($num){
                            if (!$num) return null;
                            if (strpos($num, '62') === 0) return $num;
                            if (strpos($num, '0') === 0) return '62' . substr($num, 1);
                            if (strpos($num, '8') === 0) return '62' . $num;
                            return $num;
                        };
                        $canonCandidate = $canon($candidatePhone);
                        $rows = DB::table('erm_pasiens')
                            ->whereRaw("REPLACE(no_hp, ' ', '') LIKE ?", ["%{$candidatePhone}%"] )
                            ->orWhereRaw("REPLACE(no_hp2, ' ', '') LIKE ?", ["%{$candidatePhone}%"] )
                            ->limit(10)
                            ->get(['id','no_hp','no_hp2']);
                        foreach ($rows as $pas) {
                            $n1 = preg_replace('/\\D+/', '', $pas->no_hp ?: '');
                            $n2 = preg_replace('/\\D+/', '', $pas->no_hp2 ?: '');
                            if ($canon($n1) === $canonCandidate || $canon($n2) === $canonCandidate) {
                                $data['pasien_id'] = $pas->id; break;
                            }
                        }
                        if (!empty($data['pasien_id'])) {
                            Log::info('WaMessage: inferred pasien from recent outgoing', ['session'=>$data['session_client_id'],'candidate'=>$candidatePhone,'pasien_id'=>$data['pasien_id']]);
                        } else {
                            Log::info('WaMessage: heuristic found last outgoing but no pasien match', ['session'=>$data['session_client_id'],'candidate'=>$candidatePhone]);
                        }
                    }
                }
            } catch (\Exception $e) {
                // ignore heuristic failures
            }
        }

        $m = WaMessage::create($data);

        return response()->json(['ok' => true, 'id' => $m->id]);
    }
}
