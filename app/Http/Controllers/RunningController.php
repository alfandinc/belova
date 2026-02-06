<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RunningPeserta;
use Yajra\DataTables\Facades\DataTables;
use App\Models\RunningWaScheduledMessage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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

        // sanitize phone (keep digits and plus)
        $toClean = preg_replace('/[^0-9+]/', '', $to);

        // Prepare templated message
        $template = "Halo {peserta_name} !👋\n\nPengambilan racepack Belova Premiere Run 2 Wellness akan dilaksanakan pada:\n\n📅 Tanggal : Jumat, 13 Februari 2026\n⏰ Waktu : 10.00 – 15.00 WIB\ndan\n📅 Tanggal : Sabtu, 14 Februari 2026\n⏰ Waktu : 11.00 – 20.00 WIB\n\n📍 Lokasi : Klinik Utama Premiere Belova,\n\nSaat pengambilan, wajib menunjukkan Registration Ticket dan menyerahkan Waiver yang suda ditandatangani kepada panitia pelaksana di lokasi pengambilan.\n\nSampai jumpa di Belova Premiere Run 2 Wellness tanggal 15 Februari nanti! 👟✨";
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

        $ids = $request->input('peserta_ids', []);
        $created = 0;
        $createdIds = [];

        foreach ($ids as $pid) {
            $p = RunningPeserta::find($pid);
            if (!$p) continue;
            $to = $p->no_hp;
            if (!$to) continue;
            $toClean = preg_replace('/[^0-9+]/', '', $to);
            try {
                $row = RunningWaScheduledMessage::create([
                    'peserta_id' => $p->id,
                    'client_id' => null,
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
        $query = RunningPeserta::select(['id', 'unique_code', 'nama_peserta', 'kategori', 'no_hp', 'email', 'ukuran_kaos', 'notes', 'status', 'verified_at']);

        // apply status filter if provided (expect values: 'all', 'non verified', 'verified')
        $status = $request->input('status');
        if ($status && strtolower($status) !== 'all') {
            $query->where('status', $status);
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
                // detect header row if it contains known column names
                $lower = array_map(function($v){ return strtolower(trim($v)); }, $data);
                if (in_array('nama_peserta', $lower) || in_array('nama', $lower)) {
                    $header = $lower;
                    $row++;
                    continue;
                }
            }

            // map columns
            if ($header) {
                $mapped = array_combine($header, $data + array_fill(0, max(0, count($header) - count($data)), null));
                $nama = $mapped['nama_peserta'] ?? ($mapped['nama'] ?? null);
                $kategori = $mapped['kategori'] ?? null;
                $status = $mapped['status'] ?? 'non verified';
                $no_hp = $mapped['no_hp'] ?? ($mapped['hp'] ?? $mapped['telepon'] ?? null);
                $email = $mapped['email'] ?? null;
                $ukuran = $mapped['ukuran_kaos'] ?? $mapped['ukuran'] ?? $mapped['ukuran_tshirt'] ?? null;
                $notes = $mapped['notes'] ?? $mapped['keterangan'] ?? $mapped['note'] ?? null;
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

            RunningPeserta::create([
                'nama_peserta' => $nama,
                'kategori' => $kategori ? trim($kategori) : null,
                'status' => $status ? trim($status) : 'non verified',
                'no_hp' => $no_hp ? trim($no_hp) : null,
                'email' => $email ? trim($email) : null,
                'ukuran_kaos' => $ukuran ? trim($ukuran) : null,
                'notes' => $notes ? trim($notes) : null,
            ]);
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
}
