<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\ERM\Visitation;
use Carbon\Carbon;

class VisitationImportController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('csv_file');
        $path = $file->getRealPath();

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return redirect()->back()->with('visit_import_status', 'Failed to open uploaded file');
        }

        $headers = [];
        $rowIndex = 0;
        $inserted = 0;
        $updated = 0;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $rowIndex++;
                // skip empty rows
                if (count($row) === 1 && trim($row[0]) === '') {
                    continue;
                }

                if ($rowIndex === 1) {
                    // detect header
                    $lower = array_map(function($h){ return strtolower(trim($h)); }, $row);
                    $hasHeader = in_array('id', $lower) || in_array('tanggal_visit', $lower);
                    if ($hasHeader) {
                        $headers = $lower;
                        continue;
                    }
                }

                // Map values by header or positional fallback
                if (!empty($headers)) {
                    $data = array_combine($headers, $row + array_fill(0, max(0, count($headers)-count($row)), null));
                    $id = isset($data['id']) ? trim($data['id']) : null;
                    $tanggalVisitRaw = $data['tanggal_visit'] ?? ($data['tanggal_visitation'] ?? null);
                    $klinikId = $data['klinik_id'] ?? null;
                    $status = $data['status_kunjungan'] ?? null;
                    $jenis = $data['jenis_kunjungan'] ?? null;
                } else {
                    // positional: id, tanggal_visit, klinik_id, status_kunjungan, jenis_kunjungan
                    $id = $row[0] ?? null;
                    $tanggalVisitRaw = $row[1] ?? null;
                    $klinikId = $row[2] ?? null;
                    $status = $row[3] ?? null;
                    $jenis = $row[4] ?? null;
                }

                if (empty($id)) {
                    // skip rows without id
                    continue;
                }

                // Normalize date
                $tanggal_visitation = null;
                if (!empty($tanggalVisitRaw)) {
                    $tanggalVisitRaw = trim($tanggalVisitRaw);
                    // Try several formats
                    $formats = ['d/m/Y','Y-m-d','d-m-Y','m/d/Y'];
                    foreach ($formats as $fmt) {
                        try {
                            $d = Carbon::createFromFormat($fmt, $tanggalVisitRaw);
                            $tanggal_visitation = $d->format('Y-m-d');
                            break;
                        } catch (\Exception $e) {
                            // try next
                        }
                    }
                }

                $attrs = ['id' => (string) $id];
                $values = [];
                if ($tanggal_visitation) {
                    $values['tanggal_visitation'] = $tanggal_visitation;
                }
                if ($klinikId !== null && $klinikId !== '') {
                    $values['klinik_id'] = trim($klinikId);
                }
                if ($status !== null && $status !== '') {
                    $values['status_kunjungan'] = trim($status);
                }
                if ($jenis !== null && $jenis !== '') {
                    $values['jenis_kunjungan'] = trim($jenis);
                }

                // upsert
                $existing = Visitation::find($id);
                if ($existing) {
                    $existing->fill($values);
                    $existing->save();
                    $updated++;
                } else {
                    $values = array_merge(['id' => (string)$id], $values);
                    Visitation::create($values);
                    $inserted++;
                }
            }

            fclose($handle);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            if (is_resource($handle)) fclose($handle);
            Log::error('Visitation import failed: '.$e->getMessage());
            return redirect()->back()->with('visit_import_status', 'Import failed: '.$e->getMessage());
        }

        $msg = "Import completed. Inserted: {$inserted}, Updated: {$updated}.";
        return redirect()->back()->with('visit_import_status', $msg);
    }

    public function preview(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('csv_file');
        $path = $file->getRealPath();

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return response()->json(['error' => 'Failed to open file'], 400);
        }

        $rows = [];
        $headers = [];
        $rowIndex = 0;
        $maxPreview = 50;

        try {
            while (($row = fgetcsv($handle)) !== false && count($rows) < $maxPreview) {
                $rowIndex++;
                if (count($row) === 1 && trim($row[0]) === '') {
                    continue;
                }

                if ($rowIndex === 1) {
                    $lower = array_map(function($h){ return strtolower(trim($h)); }, $row);
                    $hasHeader = in_array('id', $lower) || in_array('tanggal_visit', $lower);
                    if ($hasHeader) {
                        $headers = $lower;
                        continue;
                    }
                }

                if (!empty($headers)) {
                    $mapped = array_combine($headers, $row + array_fill(0, max(0, count($headers)-count($row)), null));
                    $rows[] = $mapped;
                } else {
                    // positional fallback
                    $rows[] = [
                        'id' => $row[0] ?? null,
                        'tanggal_visit' => $row[1] ?? null,
                        'klinik_id' => $row[2] ?? null,
                        'status_kunjungan' => $row[3] ?? null,
                        'jenis_kunjungan' => $row[4] ?? null,
                    ];
                }
            }

            fclose($handle);
        } catch (\Exception $e) {
            if (is_resource($handle)) fclose($handle);
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json(['headers' => $headers, 'rows' => $rows]);
    }
}
