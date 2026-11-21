<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ERM\Klinik;
use App\Models\PdfKop;
use App\Support\KopImage;
use App\Support\PdfLetterhead;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Klinik Setting > Kop & Logo: custom letterheads of the PDFs per klinik, for the company and for the lab.
 * Every upload is checked (KopImage::RULES) and converted at once, so it is live after the upload; "Hapus"
 * returns that image to the built-in artwork.
 */
class PdfKopController extends Controller
{
    /** Images each lingkup has. */
    private const GAMBAR_PER_LINGKUP = [
        PdfKop::LINGKUP_KLINIK => ['header', 'footer', 'logo', 'ikon'],
        PdfKop::LINGKUP_PERUSAHAAN => ['header', 'logo'],
        PdfKop::LINGKUP_LAB => ['header', 'footer'],
    ];

    /** Where each image shows up, per lingkup (settings page help text). */
    private const DIPAKAI_DI = [
        PdfKop::LINGKUP_KLINIK => [
            'header' => 'Resume medis, surat diagnosis, istirahat, mondok, rujukan, radiologi, memo/disposisi klinik ini',
            'footer' => 'Bagian bawah surat-surat di atas',
            'logo' => 'Invoice, nota kasir, inform consent, retur pembelian (dan kop surat bila belum ada banner)',
            'ikon' => 'Kop kecil resep farmasi & edukasi obat',
        ],
        PdfKop::LINGKUP_PERUSAHAAN => [
            'header' => 'Memo/disposisi tanpa klinik atau dari klinik tanpa banner (bawaan: logo Belova Corp selebar kertas)',
            'logo' => 'Pengajuan dana, slip gaji dokter (dan teks kop bila belum ada banner)',
        ],
        PdfKop::LINGKUP_LAB => [
            'header' => 'Hasil lab & lembar monitoring lab (semua klinik)',
            'footer' => 'Bagian bawah hasil lab',
        ],
    ];

    public function show(Request $request)
    {
        [$lingkup, $klinikId] = $this->target($request);
        $row = PdfKop::tabelAda() ? PdfKop::untuk($lingkup, $klinikId) : null;
        $bawaan = PdfLetterhead::bawaan($lingkup, $klinikId);

        $gambar = [];
        foreach (self::GAMBAR_PER_LINGKUP[$lingkup] as $g) {
            // asset() like the klinik logos: Storage::url() is built from APP_URL, which misses a subfolder install
            $custom = $row && $row->path($g) ? asset('storage/' . $row->{$g}) . '?v=' . $row->updated_at?->timestamp : null;
            $gambar[] = [
                'key' => $g,
                'label' => KopImage::RULES[$g]['label'],
                'aturan' => KopImage::ruleText($g),
                'dipakai_di' => self::DIPAKAI_DI[$lingkup][$g] ?? '',
                'custom_url' => $custom,
                'bawaan_url' => !empty($bawaan[$g]) && is_file(public_path($bawaan[$g])) ? asset($bawaan[$g]) : null,
                'template' => in_array($g, ['header', 'footer'], true),
                // same limits as KopImage::validate, for the crop popup (crop box kept inside them)
                'rules' => array_intersect_key(KopImage::RULES[$g], array_flip(['max_kb', 'min_w', 'min_h', 'min_long', 'ratio']))
                    + ['standar' => $g === 'header' ? KopImage::BANNER_W / KopImage::BANNER_H : ($g === 'ikon' ? 1 : null)],
            ];
        }

        return response()->json([
            'tabel_ada' => PdfKop::tabelAda(),
            'lingkup' => $lingkup,
            'klinik_id' => $klinikId,
            'gambar' => $gambar,
            'nama' => $row->nama ?? '',
            'alamat' => $row->alamat ?? '',
            'nama_bawaan' => PdfLetterhead::PERUSAHAAN_NAMA,
            'contoh_url' => route('admin.pdf_kop.contoh', array_filter(['lingkup' => $lingkup, 'klinik_id' => $klinikId])),
        ]);
    }

    public function upload(Request $request)
    {
        [$lingkup, $klinikId] = $this->target($request);
        $gambar = $this->gambar($request, $lingkup);
        if (!PdfKop::tabelAda()) {
            return response()->json(['success' => false, 'message' => 'Tabel pdf_kop belum ada. Jalankan php artisan migrate terlebih dahulu.'], 422);
        }
        $file = $request->file('file');
        if (!$file) {
            return response()->json(['success' => false, 'message' => 'Pilih file gambar terlebih dahulu.'], 422);
        }
        if ($error = KopImage::validate($file, $gambar)) {
            return response()->json(['success' => false, 'message' => $error], 422);
        }

        $stored = KopImage::store($file, $gambar, $this->folder($lingkup, $klinikId));
        if (!$stored) {
            return response()->json(['success' => false, 'message' => 'Gambar tidak bisa diproses. Coba simpan ulang sebagai PNG/JPG biasa.'], 422);
        }

        $row = PdfKop::ambilAtauBuat($lingkup, $klinikId);
        $this->hapusFile($row, $gambar);
        $row->update([$gambar => $stored['jpg'], $gambar . '_asli' => $stored['asli'], 'updated_by' => Auth::id()]);
        PdfKop::lupakan();

        return response()->json(['success' => true, 'message' => KopImage::RULES[$gambar]['label'] . ' tersimpan dan langsung dipakai di PDF.']);
    }

    public function hapus(Request $request)
    {
        [$lingkup, $klinikId] = $this->target($request);
        $gambar = $this->gambar($request, $lingkup);
        $row = PdfKop::tabelAda() ? PdfKop::untuk($lingkup, $klinikId) : null;
        if ($row) {
            $this->hapusFile($row, $gambar);
            $row->update([$gambar => null, $gambar . '_asli' => null, 'updated_by' => Auth::id()]);
            PdfKop::lupakan();
        }

        return response()->json(['success' => true, 'message' => KopImage::RULES[$gambar]['label'] . ' kembali ke gambar bawaan.']);
    }

    /** Company name + address lines (text letterhead when the company has no banner). */
    public function teks(Request $request)
    {
        if (!PdfKop::tabelAda()) {
            return response()->json(['success' => false, 'message' => 'Tabel pdf_kop belum ada. Jalankan php artisan migrate terlebih dahulu.'], 422);
        }
        $data = $request->validate([
            'nama' => 'nullable|string|max:255',
            'alamat' => 'nullable|string|max:1000',
        ]);
        $row = PdfKop::ambilAtauBuat(PdfKop::LINGKUP_PERUSAHAAN);
        $row->update([
            'nama' => trim((string) ($data['nama'] ?? '')) ?: null,
            'alamat' => trim((string) ($data['alamat'] ?? '')) ?: null,
            'updated_by' => Auth::id(),
        ]);
        PdfKop::lupakan();

        return response()->json(['success' => true, 'message' => 'Nama & alamat perusahaan tersimpan.']);
    }

    /** Sample PDF with the current letterhead of a lingkup (dummy content). */
    public function contoh(Request $request)
    {
        [$lingkup, $klinikId] = $this->target($request);
        if ($lingkup === PdfKop::LINGKUP_KLINIK) {
            $klinik = Klinik::findOrFail($klinikId);
            $kop = PdfLetterhead::forKlinik($klinik);
            $judul = $klinik->nama;
        } elseif ($lingkup === PdfKop::LINGKUP_PERUSAHAAN) {
            $kop = PdfLetterhead::perusahaan();
            $judul = $kop['nama'];
        } else {
            $kop = PdfLetterhead::lab() + ['nama' => 'LABORATORIUM', 'logo' => null, 'icon' => null, 'lines' => []];
            $judul = 'Kop Lab';
        }

        return Pdf::loadView('admin.pdf_kop.contoh', compact('kop', 'lingkup', 'judul'))
            ->setPaper('a4', 'portrait')
            ->stream('contoh-kop.pdf');
    }

    /** Banner template PNG (2000x381 with the safe area marked). */
    public function template(string $jenis)
    {
        abort_unless(in_array($jenis, ['header', 'footer'], true), 404);

        return response(KopImage::templatePng($jenis), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="template-kop-' . ($jenis === 'footer' ? 'bawah' : 'atas') . '.png"',
        ]);
    }

    /** @return array{0: string, 1: ?int} */
    private function target(Request $request): array
    {
        $lingkup = (string) $request->input('lingkup');
        abort_unless(isset(self::GAMBAR_PER_LINGKUP[$lingkup]), 422, 'Lingkup tidak dikenal.');
        $klinikId = null;
        if ($lingkup === PdfKop::LINGKUP_KLINIK) {
            $klinikId = (int) $request->input('klinik_id');
            abort_unless($klinikId > 0 && Klinik::whereKey($klinikId)->exists(), 422, 'Klinik tidak ditemukan.');
        }

        return [$lingkup, $klinikId];
    }

    private function gambar(Request $request, string $lingkup): string
    {
        $gambar = (string) $request->input('gambar');
        abort_unless(in_array($gambar, self::GAMBAR_PER_LINGKUP[$lingkup], true), 422, 'Jenis gambar tidak dikenal.');

        return $gambar;
    }

    private function folder(string $lingkup, ?int $klinikId): string
    {
        return 'pdf_kop/' . $lingkup . ($klinikId ? '-' . $klinikId : '');
    }

    private function hapusFile(PdfKop $row, string $gambar): void
    {
        $disk = Storage::disk('public');
        foreach ([$row->{$gambar}, $row->{$gambar . '_asli'}] as $path) {
            if ($path && $disk->exists($path)) {
                $disk->delete($path);
            }
        }
    }
}
