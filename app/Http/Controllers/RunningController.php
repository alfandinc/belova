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
        $query = RunningPeserta::select(['id', 'unique_code', 'nama_peserta', 'kategori', 'status', 'created_at']);
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
            } else {
                // assume order: nama_peserta, kategori, status
                $nama = $data[0] ?? null;
                $kategori = $data[1] ?? null;
                $status = $data[2] ?? 'non verified';
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
        $peserta->save();

        $message = 'Peserta with code ' . $peserta->unique_code . ' marked as verified.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['ok' => true, 'message' => $message]);
        }

        return back()->with('success', $message);
    }
}
