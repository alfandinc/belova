<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RunningPeserta;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use App\Models\RunningWaScheduledMessage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\RunningWaMessageLog;
 

class RunningController extends Controller
{
    public function index()
    {
        return view('running.index');
    }

    /**
     * Enqueue a running ticket to be sent via WhatsApp (single)
     */
    public function sendWhatsapp(Request $request)
    {
        $request->validate([
            'peserta_id' => 'required|integer',
            'to' => 'nullable|string',
            'client_id' => 'nullable|string',
            'image_path' => 'nullable|string'
        ]);

        $peserta = RunningPeserta::find($request->input('peserta_id'));
        if (!$peserta) {
            return response()->json(['ok' => false, 'message' => 'Peserta not found'], 404);
        }

        $to = $request->input('to') ?: $peserta->no_hp;
        if (!$to) {
            return response()->json(['ok' => false, 'message' => 'No phone number available for peserta'], 422);
        }

        // normalize phone to WhatsApp-friendly format (e.g. 628...) — prefer stored peserta number
        $toClean = $this->normalizePhoneNumber($to);

        // Prepare templated message
        $template = "Halo {peserta_name} !👋\n\nTerimakasih banyak sudah melakukan pendaftaran di Belova Premiere Run 2 Wellness 🤩\nPengambilan racepack Belova Premiere Run 2 Wellness akan dilaksanakan pada :\n\n📅 Jumat, 13 Februari 2026\n⏰ 10.00 – 15.00 WIB\ndan\n📅 Sabtu, 14 Februari 2026\n⏰ 12.00 – 20.00 WIB\n\n📍 Lokasi : Klinik Utama Premiere Belova\nJl. Melon Raya 1 no. 27 Karangasem, Laweyan, Surakarta\n\nSaat pengambilan racepack, peserta wajib menunjukkan Registration Ticket serta menyerahkan formulir Waiver yang telah dicetak dan ditandatangani kepada panitia di lokasi pengambilan racepack.\n\nSampai jumpa di Belova Premiere Run 2 Wellness tanggal 15 Februari 2026 nanti! 👟✨";
        $messageText = str_replace('{peserta_name}', $peserta->nama_peserta ?? '', $template);

        $row = RunningWaScheduledMessage::create([
            'peserta_id' => $peserta->id,
            'client_id' => $request->input('client_id') ?: null,
            'to' => $toClean,
            'message' => $messageText,
            'image_path' => $request->input('image_path') ?: null,
            'schedule_at' => Carbon::now(),
            'status' => 'pending'
        ]);

        return response()->json(['ok' => true, 'id' => $row->id]);
    }

    /**
     * Enqueue multiple running tickets to be sent via WhatsApp (bulk)
     */
    public function sendWhatsappBulk(Request $request)
    {
        $request->validate([
            'peserta_ids' => 'required|array',
            'peserta_ids.*' => 'integer'
        ]);
        // optional client_id to select which WA session will be used
        $clientId = $request->input('client_id');

        $ids = $request->input('peserta_ids', []);
        $created = 0;
        $createdIds = [];

        foreach ($ids as $pid) {
            $p = RunningPeserta::find($pid);
            if (!$p) continue;
            $to = $p->no_hp;
            if (!$to) continue;
            $toClean = $this->normalizePhoneNumber($to);
            try {
                $row = RunningWaScheduledMessage::create([
                    'peserta_id' => $p->id,
                    'client_id' => $clientId ?: null,
                    'to' => $toClean,
                    'message' => null,
                    'schedule_at' => Carbon::now(),
                    'status' => 'pending'
                ]);
                $created++;
                $createdIds[] = $row->id;
            } catch (\Exception $e) {
                // skip failing rows
                continue;
            }
        }

        return response()->json(['ok' => true, 'created' => $created, 'ids' => $createdIds]);
    }

    /**
     * Store ticket image uploaded from the browser (html2canvas) and attach to pending scheduled messages
     */
    public function storeTicketImage(Request $request)
    {
        $request->validate([
            'peserta_id' => 'required|integer',
            'image_data' => 'required|string'
        ]);

        $peserta = RunningPeserta::find($request->input('peserta_id'));
        if (!$peserta) return response()->json(['ok' => false, 'message' => 'Peserta not found'], 404);

        $data = $request->input('image_data');
        if (strpos($data, 'base64,') !== false) {
            $parts = explode('base64,', $data);
            $data = $parts[1];
        }

        $bin = base64_decode($data);
        if ($bin === false) return response()->json(['ok' => false, 'message' => 'Invalid image data'], 422);

        $dir = storage_path('app/public/running_tickets');
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $filename = 'ticket-' . $peserta->id . '-' . time() . '.png';
        $full = $dir . DIRECTORY_SEPARATOR . $filename;

        file_put_contents($full, $bin);

        // update pending scheduled messages for this peserta that don't yet have image_path
        try {
            RunningWaScheduledMessage::where('peserta_id', $peserta->id)
                ->where('status', 'pending')
                ->whereNull('image_path')
                ->update(['image_path' => $full]);
        } catch (\Exception $e) {
            // non-fatal
        }

        return response()->json(['ok' => true, 'image_path' => $full, 'public_url' => Storage::url('running_tickets/' . $filename)]);
    }

    /**
     * Data endpoint for Yajra DataTables
     */
    public function data(Request $request)
    {
        $query = RunningPeserta::select([
            'running_pesertas.id', 'unique_code', 'nama_peserta', 'kategori', 'no_hp', 'email', 'ukuran_kaos', 'notes', 'status', 'verified_at', 'registered_at',
            DB::raw('(select count(*) from running_wa_message_logs where running_wa_message_logs.peserta_id = running_pesertas.id and running_wa_message_logs.direction = "out") as sent_logs_count')
        ]);

        // apply status filter if provided (expect values: 'all', 'non verified', 'verified')
        $status = $request->input('status');
        if ($status && strtolower($status) !== 'all') {
            $query->where('status', $status);
        }

        // apply sent filter if provided (expect values: 'all', 'sent', 'not_sent')
        $sent = $request->input('sent');
        if ($sent && $sent !== 'all') {
            if ($sent === 'sent') {
                $query->whereExists(function($q){
                    $q->select(DB::raw(1))
                      ->from('running_wa_message_logs')
                      ->whereRaw('running_wa_message_logs.peserta_id = running_pesertas.id')
                      ->where('direction', 'out');
                });
            } elseif ($sent === 'not_sent') {
                $query->whereNotExists(function($q){
                    $q->select(DB::raw(1))
                      ->from('running_wa_message_logs')
                      ->whereRaw('running_wa_message_logs.peserta_id = running_pesertas.id')
                      ->where('direction', 'out');
                });
            }
        }

        return DataTables::of($query)->make(true);
    }

    /**
     * Import peserta from uploaded CSV file.
     */
    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('csv_file');
        $path = $file->getRealPath();

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return back()->with('error', 'Failed to open uploaded file.');
        }

        $row = 0;
        $created = 0;
        $skipped = 0;
        $header = null;

        while (($data = fgetcsv($handle, 0, ',')) !== false) {
            // skip empty lines
            if (count($data) === 1 && trim($data[0]) === '') continue;

            if ($row === 0) {
                // detect header row if it contains known column names (case-insensitive)
                $lower = array_map(function($v){ return strtolower(trim($v)); }, $data);
                // common header names we accept: nama, nama_peserta, nohp, no_hp, email, jersey, ukuran_kaos, kategori, regdate, registered_at
                $known = ['nama','nama_peserta','nohp','no_hp','hp','telepon','email','jersey','ukuran','ukuran_kaos','ukuran_tshirt','kategori','regdate','registered_at','notes','keterangan'];
                $hasKnown = false;
                foreach ($lower as $col) { if (in_array($col, $known)) { $hasKnown = true; break; } }
                if ($hasKnown) { $header = $lower; $row++; continue; }
            }

            // map columns
            $registeredAt = null;
            if ($header) {
                $mapped = array_combine($header, $data + array_fill(0, max(0, count($header) - count($data)), null));
                $nama = $mapped['nama_peserta'] ?? ($mapped['nama'] ?? null);
                $kategori = $mapped['kategori'] ?? null;
                $status = $mapped['status'] ?? 'non verified';
                $no_hp = $mapped['no_hp'] ?? ($mapped['nohp'] ?? ($mapped['hp'] ?? $mapped['telepon'] ?? null));
                $email = $mapped['email'] ?? null;
                $ukuran = $mapped['ukuran_kaos'] ?? ($mapped['jersey'] ?? ($mapped['ukuran'] ?? $mapped['ukuran_tshirt'] ?? null));
                $notes = $mapped['notes'] ?? ($mapped['keterangan'] ?? $mapped['note'] ?? null);
                // registration date parsing
                $regRaw = $mapped['registered_at'] ?? ($mapped['regdate'] ?? ($mapped['reg_date'] ?? null));
                if ($regRaw) {
                    try { $registeredAt = \Carbon\Carbon::parse(trim($regRaw)); } catch (\Exception $e) { $registeredAt = null; }
                }
            } else {
                // assume order: nama_peserta, kategori, status, no_hp, email, ukuran_kaos
                $nama = $data[0] ?? null;
                $kategori = $data[1] ?? null;
                $status = $data[2] ?? 'non verified';
                $no_hp = $data[3] ?? null;
                $email = $data[4] ?? null;
                $ukuran = $data[5] ?? null;
                $notes = $data[6] ?? null;
            }

            $nama = trim((string) $nama);
            if ($nama === '') { $skipped++; $row++; continue; }

            // avoid duplicates by exact name (simple approach)
            $exists = RunningPeserta::where('nama_peserta', $nama)->exists();
            if ($exists) { $skipped++; $row++; continue; }

            // normalize phone number from various CSV formats into 62... numeric format
            $no_hp = $this->normalizePhoneNumber($no_hp);

            $attrs = [
                'nama_peserta' => $nama,
                'kategori' => $kategori ? trim($kategori) : null,
                'status' => $status ? trim($status) : 'non verified',
                'no_hp' => $no_hp ? trim($no_hp) : null,
                'email' => $email ? trim($email) : null,
                'ukuran_kaos' => $ukuran ? trim($ukuran) : null,
                'notes' => $notes ? trim($notes) : null,
            ];
            if (!empty($registeredAt)) {
                $attrs['registered_at'] = $registeredAt;
            }
            RunningPeserta::create($attrs);
            $created++;
            $row++;
        }

        fclose($handle);

        $message = "Import finished. Created: {$created}. Skipped: {$skipped}.";
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['ok' => true, 'message' => $message, 'created' => $created, 'skipped' => $skipped]);
        }

        return back()->with('success', $message);
    }

    /**
     * Export peserta data to CSV. Honors optional `status` query param ('all' for all)
     */
    public function exportCsv(Request $request)
    {
        $status = $request->input('status');

        $query = RunningPeserta::select(['id', 'unique_code', 'nama_peserta', 'no_hp', 'email', 'ukuran_kaos', 'kategori', 'status', 'notes', 'verified_at', 'registered_at'])->orderBy('id', 'asc');
        if ($status && strtolower($status) !== 'all') {
            $query->where('status', $status);
        }

        $fileName = 'running_peserta_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        $callback = function() use ($query) {
            $handle = fopen('php://output', 'w');
            // header row
            fputcsv($handle, ['id','unique_code','nama_peserta','no_hp','email','ukuran_kaos','kategori','notes','registered_at']);

            foreach ($query->cursor() as $p) {
                fputcsv($handle, [
                    $p->id,
                    $p->unique_code,
                    $p->nama_peserta,
                    $p->no_hp,
                    $p->email,
                    $p->ukuran_kaos,
                    $p->kategori,
                    $p->notes,
                    $p->registered_at ? (is_string($p->registered_at) ? $p->registered_at : $p->registered_at->toDateTimeString()) : null,
                ]);
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, $fileName, $headers);
    }

    /**
     * Verify peserta by unique code (AJAX)
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $code = trim($request->input('code'));
        // case-insensitive match
        $peserta = RunningPeserta::where('unique_code', $code)->first();

        if (!$peserta) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => 'Code not found.'], 404);
            }
            return back()->with('error', 'Code not found.');
        }

        $peserta->status = 'verified';
        $peserta->verified_at = now();
        $peserta->save();

        $message = 'Peserta with code ' . $peserta->unique_code . ' marked as verified.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['ok' => true, 'message' => $message]);
        }

        return back()->with('success', $message);
    }

    /**
     * Verify a peserta by id and store notes. Used by AJAX Verif button.
     */
    public function verifyWithNotes(Request $request, $id)
    {
        $request->validate([
            'notes' => 'nullable|string'
        ]);

        $peserta = RunningPeserta::find($id);
        if (!$peserta) {
            return response()->json(['ok' => false, 'message' => 'Peserta not found'], 404);
        }

        // store notes (append if existing)
        $notes = trim($request->input('notes', ''));
        if ($notes !== '') {
            $existing = $peserta->notes ? trim($peserta->notes) : '';
            $peserta->notes = $existing ? ($existing . "\n" . $notes) : $notes;
        }

        $peserta->status = 'verified';
        $peserta->verified_at = now();
        $peserta->save();

        return response()->json(['ok' => true, 'message' => 'Peserta verified']);
    }

    /**
     * Find peserta by code (no mutation). Used for preview before verify.
     */
    public function find(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $code = trim($request->input('code'));
        $peserta = RunningPeserta::where('unique_code', $code)->first();

        if (!$peserta) {
            return response()->json(['ok' => false, 'message' => 'Code not found.'], 404);
        }

        return response()->json(['ok' => true, 'data' => $peserta]);
    }

    /**
     * Render a ticket view for printing / download
     */
    public function ticket($id)
    {
        $peserta = RunningPeserta::find($id);
        if (!$peserta) {
            abort(404);
        }
        return view('running.ticket', compact('peserta'));
    }

    /**
     * Return ticket HTML fragment (no layout) for modal preview.
     */
    public function ticketHtml($id)
    {
        $peserta = RunningPeserta::find($id);
        if (!$peserta) {
            return response('Not found', 404);
        }
        return view('running.ticket_fragment', compact('peserta'));
    }

    /**
     * Public (token-protected) ticket HTML for bots / headless access.
     * If WA_BOT_TOKEN is set in env, the request must include ?wa_bot_token=VALUE
     */
    public function ticketHtmlForBot(Request $request, $id)
    {
        $peserta = RunningPeserta::find($id);
        if (!$peserta) {
            return response('Not found', 404);
        }

        $token = env('WA_BOT_TOKEN');
        if ($token && $request->query('wa_bot_token') !== $token) {
            return response('Unauthorized', 401);
        }

        return view('running.ticket_fragment', compact('peserta'));
    }

    /**
     * Show an interstitial preview page for WhatsApp message.
     * Expects query params: phone, message, optional image (public URL)
     */
    public function waPreview(Request $request)
    {
        $phone = $request->query('phone');
        $message = $request->query('message');
        $image = $request->query('image');

        if (!$phone) {
            abort(400, 'phone is required');
        }

        return view('running.wa_preview', [
            'phone' => $phone,
            'message' => $message ?: '',
            'image' => $image ?: null
        ]);
    }

    /**
     * Mark a peserta as having been sent (manual override) by creating an outgoing message log.
     */
    public function markSent(Request $request, $id)
    {
        $peserta = RunningPeserta::find($id);
        if (!$peserta) return response()->json(['ok' => false, 'message' => 'Peserta not found'], 404);

        try {
            $user = Auth::user();
            $body = 'Marked as sent by ' . ($user ? $user->name : 'system');
            $log = RunningWaMessageLog::create([
                'peserta_id' => $peserta->id,
                'scheduled_message_id' => null,
                'client_id' => null,
                'direction' => 'out',
                'to' => $peserta->no_hp,
                'body' => $body,
                'response' => null,
                'message_id' => null,
                'raw' => json_encode(['manual_mark' => true, 'by' => $user ? $user->id : null])
            ]);

            return response()->json(['ok' => true, 'message' => 'Marked as sent']);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'message' => 'Failed to mark sent: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Normalize phone number to WhatsApp-friendly international format (Indonesia default).
     * Examples:
     *  - "082142522812" -> "6282142522812"
     *  - "+62 857-2854-4497" -> "6285728544497"
     *  - "0857 1234" -> "628571234"
     */
    private function normalizePhoneNumber($raw)
    {
        if (empty($raw)) return null;
        $s = trim((string) $raw);
        // keep plus temporarily, remove other non-digit/plus
        $clean = preg_replace('/[^0-9+]/', '', $s);
        // drop leading plus
        if (strpos($clean, '+') === 0) $clean = substr($clean, 1);
        // now only digits
        $digits = preg_replace('/[^0-9]/', '', $clean);
        if ($digits === '') return null;
        // if starts with 0, replace with 62
        if (preg_match('/^0(.*)$/', $digits, $m)) {
            return '62' . $m[1];
        }
        // if already starts with 62, keep
        if (preg_match('/^62[0-9]+$/', $digits)) return $digits;
        // if starts with 8 (local without leading 0), prefix 62
        if (preg_match('/^8[0-9]+$/', $digits)) return '62' . $digits;
        // fallback: return digits as-is
        return $digits;
    }
}
