<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\ERM\InformConsent;
use App\Models\ERM\Pasien;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BeforeAfterGalleryController extends Controller
{
    public function index()
    {
        return view('marketing.before_after_gallery.index');
    }

    public function search(Request $request)
    {
        $validated = $request->validate([
            'tindakan_id' => 'nullable|exists:erm_tindakan,id',
            'kode_tindakan_id' => 'nullable|exists:erm_kode_tindakan,id',
            'pasien_id' => 'nullable|exists:erm_pasiens,id',
        ]);

        $query = InformConsent::query()
            ->with([
                'tindakan.kodeTindakans',
                'visitation.pasien',
                'visitation.dokter.user',
            ])
            ->where(function ($imageQuery) {
                $imageQuery
                    ->where(function ($beforeQuery) {
                        $beforeQuery->whereNotNull('before_image_path')
                            ->where('before_image_path', '!=', '');
                    })
                    ->orWhere(function ($afterQuery) {
                        $afterQuery->whereNotNull('after_image_path')
                            ->where('after_image_path', '!=', '');
                    });
            });

        if (!empty($validated['tindakan_id'])) {
            $query->where('tindakan_id', $validated['tindakan_id']);
        }

        if (!empty($validated['pasien_id'])) {
            $pasienId = $validated['pasien_id'];
            $query->whereHas('visitation', function ($visitationQuery) use ($pasienId) {
                $visitationQuery->where('pasien_id', $pasienId);
            });
        }

        if (!empty($validated['kode_tindakan_id'])) {
            $kodeTindakanId = $validated['kode_tindakan_id'];
            $query->whereHas('tindakan.kodeTindakans', function ($kodeQuery) use ($kodeTindakanId) {
                $kodeQuery->where('erm_kode_tindakan.id', $kodeTindakanId);
            });
        }

        $records = $query
            ->orderByDesc('created_at')
            ->get()
            ->map(function (InformConsent $informConsent) {
                $visitation = $informConsent->visitation;
                $pasien = optional($visitation)->pasien;
                $dokter = optional(optional($visitation)->dokter);
                $kodeTindakans = $informConsent->tindakan
                    ? $informConsent->tindakan->kodeTindakans->map(function ($kodeTindakan) {
                        $kode = trim((string) ($kodeTindakan->kode ?? ''));
                        $nama = trim((string) ($kodeTindakan->nama ?? ''));

                        if ($kode !== '' && $nama !== '') {
                            return $kode . ' - ' . $nama;
                        }

                        return $kode !== '' ? $kode : $nama;
                    })->filter()->values()->all()
                    : [];

                return [
                    'id' => $informConsent->id,
                    'tindakan_nama' => optional($informConsent->tindakan)->nama ?? '-',
                    'kode_tindakans' => $kodeTindakans,
                    'pasien_nama' => optional($pasien)->nama ?? '-',
                    'pasien_id' => optional($pasien)->id ?? '-',
                    'dokter_nama' => optional($dokter->user)->name ?? ($dokter->nama ?? '-'),
                    'tanggal_visit' => optional($visitation)->tanggal_visitation
                        ? Carbon::parse($visitation->tanggal_visitation)->format('d M Y')
                        : '-',
                    'allow_post' => (bool) $informConsent->allow_post,
                    'before_image_url' => $this->buildImageUrl($informConsent->before_image_path),
                    'after_image_url' => $this->buildImageUrl($informConsent->after_image_path),
                ];
            })
            ->values();

        return response()->json([
            'count' => $records->count(),
            'data' => $records,
        ]);
    }

    public function pasienSearch(Request $request)
    {
        $term = trim((string) $request->get('q', ''));

        if (mb_strlen($term) < 2) {
            return response()->json(['results' => []]);
        }

        $results = Pasien::query()
            ->where(function ($query) use ($term) {
                $query->where('nama', 'like', '%' . $term . '%')
                    ->orWhere('id', 'like', '%' . $term . '%')
                    ->orWhere('nik', 'like', '%' . $term . '%');
            })
            ->orderBy('nama')
            ->limit(20)
            ->get(['id', 'nama'])
            ->map(function (Pasien $pasien) {
                $name = preg_replace('/\s+/', ' ', trim((string) $pasien->nama));
                $label = $name !== ''
                    ? mb_strtoupper($name) . ' (' . $pasien->id . ')'
                    : '(' . $pasien->id . ')';

                return [
                    'id' => $pasien->id,
                    'text' => $label,
                ];
            })
            ->values();

        return response()->json(['results' => $results]);
    }

    private function buildImageUrl(?string $path): ?string
    {
        if ($path === null) {
            return null;
        }

        $trimmedPath = trim($path);
        if ($trimmedPath === '') {
            return null;
        }

        if (Str::startsWith($trimmedPath, ['http://', 'https://'])) {
            return $trimmedPath;
        }

        if (Str::startsWith($trimmedPath, ['/storage/', 'storage/'])) {
            return asset(ltrim($trimmedPath, '/'));
        }

        return asset(ltrim(Storage::url($trimmedPath), '/'));
    }
}