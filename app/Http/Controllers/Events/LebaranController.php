<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Models\Events\Lebaran;
use App\Models\ERM\Pasien;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class LebaranController extends Controller
{
    public function index()
    {
        return view('events.lebaran.index');
    }

    public function data(Request $request)
    {
        $query = Lebaran::query()
            ->leftJoin('erm_pasiens', 'event_lebarans.pasien_id', '=', 'erm_pasiens.id')
            ->select([
                'event_lebarans.id',
                'event_lebarans.nama_pasien as imported_nama_pasien',
                'event_lebarans.pasien_id',
                'event_lebarans.status',
                'event_lebarans.nohp as event_nohp',
                'erm_pasiens.nama as master_nama_pasien',
                'erm_pasiens.no_hp as pasien_nohp',
            ]);

        return DataTables::of($query)
            ->filterColumn('nama_pasien', function ($query, $keyword) {
                $query->where(function ($subQuery) use ($keyword) {
                    $subQuery->where('event_lebarans.nama_pasien', 'like', '%' . $keyword . '%')
                        ->orWhere('erm_pasiens.nama', 'like', '%' . $keyword . '%');
                });
            })
            ->filterColumn('pasien_id', function ($query, $keyword) {
                $query->where('event_lebarans.pasien_id', 'like', '%' . $keyword . '%');
            })
            ->filterColumn('nohp', function ($query, $keyword) {
                $query->where(function ($subQuery) use ($keyword) {
                    $subQuery->where('event_lebarans.nohp', 'like', '%' . $keyword . '%')
                        ->orWhere('erm_pasiens.no_hp', 'like', '%' . $keyword . '%');
                });
            })
            ->editColumn('nama_pasien', function ($row) {
                return $row->imported_nama_pasien ?: ($row->master_nama_pasien ?: '-');
            })
            ->addColumn('nohp', function ($row) {
                return $row->event_nohp ?: '-';
            })
            ->editColumn('status', function ($row) {
                return $row->status ?: '-';
            })
            ->make(true);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $sheets = Excel::toArray([], $request->file('file'));
        $rows = $sheets[0] ?? [];

        if (count($rows) < 2) {
            return redirect()
                ->route('events.lebaran.index')
                ->with('error', 'File import kosong atau tidak memiliki data.');
        }

        $headerRow = array_map([$this, 'normalizeHeader'], $rows[0] ?? []);
        $pasienIdIndex = $this->findHeaderIndex($headerRow, ['pasienid', 'pasien_id', 'pasien id', 'norm', 'normrm', 'normor', 'nomorrm', 'no rm', 'rm', 'nomorrekammedis']);
        $namaIndex = $this->findHeaderIndex($headerRow, ['namapasien', 'nama pasien', 'nama']);
        $noHpIndex = $this->findHeaderIndex($headerRow, ['nohp', 'no hp', 'nomorhp', 'nomor hp', 'hp', 'telepon', 'no telepon']);

        if ($noHpIndex === null) {
            $noHpIndex = $this->guessNoHpIndex($rows, $namaIndex, $pasienIdIndex);
        }

        if ($pasienIdIndex === null) {
            return redirect()
                ->route('events.lebaran.index')
                ->with('error', 'Kolom pasien ID tidak ditemukan. Gunakan header seperti pasien id atau no rm.');
        }

        $imported = 0;
        $skipped = 0;

        foreach (array_slice($rows, 1) as $row) {
            if (!is_array($row)) {
                $skipped++;
                continue;
            }

            $pasienId = trim((string) ($row[$pasienIdIndex] ?? ''));
            if ($pasienId === '') {
                $skipped++;
                continue;
            }

            $pasien = Pasien::query()->where('id', $pasienId)->first();
            if (!$pasien) {
                $skipped++;
                continue;
            }

            $namaPasien = trim((string) ($row[$namaIndex] ?? ''));
            $noHp = trim((string) ($row[$noHpIndex] ?? ''));

            $lebaran = Lebaran::firstOrNew([
                'pasien_id' => $pasienId,
            ]);

            if (!$lebaran->exists) {
                $lebaran->status = 'pending';
            }

            $lebaran->nama_pasien = $namaPasien !== '' ? $namaPasien : ($lebaran->nama_pasien ?: $pasien->nama);
            if ($noHp !== '') {
                $lebaran->nohp = $noHp;
            }
            $lebaran->save();

            $imported++;
        }

        $message = 'Import selesai. ' . $imported . ' data diproses, ' . $skipped . ' data dilewati.';
        if ($namaIndex === null) {
            $message .= ' Kolom nama pasien tidak dipakai karena pasien dicocokkan dari pasien ID.';
        }

        return redirect()
            ->route('events.lebaran.index')
            ->with('success', $message);
    }

    public function markSent(Lebaran $lebaran)
    {
        $lebaran->status = 'sent';
        $lebaran->save();

        return response()->json([
            'ok' => true,
            'id' => $lebaran->id,
            'status' => $lebaran->status,
        ]);
    }

    private function normalizeHeader($value): string
    {
        return Str::of((string) $value)
            ->lower()
            ->replace(['_', '-', '.', '/', '\\'], ' ')
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->replace(' ', '')
            ->toString();
    }

    private function findHeaderIndex(array $headers, array $aliases): ?int
    {
        $normalizedAliases = array_map(function ($alias) {
            return $this->normalizeHeader($alias);
        }, $aliases);

        foreach ($headers as $index => $header) {
            if (in_array($header, $normalizedAliases, true)) {
                return $index;
            }
        }

        return null;
    }

    private function guessNoHpIndex(array $rows, ?int $namaIndex, ?int $pasienIdIndex): ?int
    {
        $header = $rows[0] ?? [];
        if (!is_array($header)) {
            return null;
        }

        $candidateIndexes = array_filter(array_keys($header), function ($index) use ($namaIndex, $pasienIdIndex) {
            return $index !== $namaIndex && $index !== $pasienIdIndex;
        });

        foreach ($candidateIndexes as $index) {
            $matchesPhonePattern = 0;

            foreach (array_slice($rows, 1, 5) as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $value = trim((string) ($row[$index] ?? ''));
                $digits = preg_replace('/\D+/', '', $value);

                if (strlen($digits) >= 10) {
                    $matchesPhonePattern++;
                }
            }

            if ($matchesPhonePattern > 0) {
                return (int) $index;
            }
        }

        return null;
    }
}