<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RunningPeserta;
use Yajra\DataTables\Facades\DataTables;

class RunningController extends Controller
{
    public function index()
    {
        return view('running.index');
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
}
