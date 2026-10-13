<?php

namespace App\Http\Controllers;

use App\Models\Rnd\RndProduk;
use App\Models\Rnd\RndProdukLog;
use App\Models\Rnd\RndProdukSertifikasi;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Legalitas & sertifikasi of an R&D product (tab "Legalitas" in the product modal).
 * A renewal is added as a new row, so the history of a certificate stays visible.
 */
class RndSertifikasiController extends Controller
{
    /** Rows for the product modal, current-first per jenis. */
    public static function listFor(RndProduk $produk): array
    {
        $today = Carbon::today();
        $rows = $produk->sertifikasi()->with('sampleLog:id,no_produksi')
            ->orderBy('jenis')->orderByDesc('tanggal_selesai')->orderByDesc('id')->get();
        $currentIds = $rows->groupBy('jenis')->map(fn ($group) => $group->first()->id)->values()->all();

        return $rows->map(fn (RndProdukSertifikasi $s) => [
            'id' => $s->id,
            'jenis' => $s->jenis,
            'jenis_label' => RndProdukSertifikasi::label($s->jenis),
            'nomor' => $s->nomor,
            'penerbit' => $s->penerbit,
            'tanggal_mulai' => $s->tanggal_mulai->toDateString(),
            'tanggal_selesai' => $s->tanggal_selesai->toDateString(),
            'sample_log_id' => $s->sample_log_id,
            'no_produksi' => optional($s->sampleLog)->no_produksi,
            'catatan' => $s->catatan,
            'original_name' => $s->original_name,
            'url' => $s->doc_path ? route('rnd.sertifikasi.document', $s) : null,
            'masa_berlaku' => $s->masaBerlaku($today),
            'terkini' => in_array($s->id, $currentIds, true),
        ])->values()->all();
    }

    public function store(Request $request, RndProduk $produk)
    {
        $validated = $this->validated($request, $produk, true);
        $file = $request->file('dokumen');

        $sertifikasi = DB::transaction(function () use ($produk, $validated, $file) {
            $sertifikasi = $produk->sertifikasi()->create($validated + [
                'doc_path' => $file->store('rnd/sertifikasi', 'public'),
                'original_name' => $file->getClientOriginalName(),
            ]);
            $this->syncNotifStatus($produk, $sertifikasi);

            return $sertifikasi;
        });

        $this->log($produk->id, 'sertifikasi-added', RndProdukSertifikasi::label($sertifikasi->jenis)
            . ($sertifikasi->nomor ? " {$sertifikasi->nomor}" : '') . ' ditambahkan, berlaku s/d ' . $sertifikasi->tanggal_selesai->toDateString() . '.');

        return $this->jsonResult($produk, 'Sertifikasi berhasil ditambahkan.');
    }

    public function update(Request $request, RndProdukSertifikasi $sertifikasi)
    {
        $produk = $sertifikasi->produk;
        $validated = $this->validated($request, $produk, false);
        $oldPath = null;

        if ($request->hasFile('dokumen')) {
            $file = $request->file('dokumen');
            $oldPath = $sertifikasi->doc_path;
            $validated['doc_path'] = $file->store('rnd/sertifikasi', 'public');
            $validated['original_name'] = $file->getClientOriginalName();
        }

        DB::transaction(function () use ($produk, $sertifikasi, $validated) {
            $sertifikasi->update($validated);
            $this->syncNotifStatus($produk, $sertifikasi);
        });

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        $this->log($produk->id, 'sertifikasi-updated', RndProdukSertifikasi::label($sertifikasi->jenis) . ' diperbarui, berlaku s/d ' . $sertifikasi->tanggal_selesai->toDateString() . '.');

        return $this->jsonResult($produk, 'Sertifikasi berhasil diperbarui.');
    }

    public function destroy(RndProdukSertifikasi $sertifikasi)
    {
        $produk = $sertifikasi->produk;
        $path = $sertifikasi->doc_path;
        $label = RndProdukSertifikasi::label($sertifikasi->jenis) . ($sertifikasi->nomor ? " {$sertifikasi->nomor}" : '');

        $sertifikasi->delete();
        if ($path) {
            Storage::disk('public')->delete($path);
        }

        $this->log($produk->id, 'sertifikasi-deleted', "{$label} dihapus.");

        return $this->jsonResult($produk, 'Sertifikasi berhasil dihapus.');
    }

    public function document(RndProdukSertifikasi $sertifikasi)
    {
        abort_unless($sertifikasi->doc_path && Storage::disk('public')->exists($sertifikasi->doc_path), 404);

        $name = str_replace('"', '', $sertifikasi->original_name ?: basename($sertifikasi->doc_path));

        return response()->file(Storage::disk('public')->path($sertifikasi->doc_path), [
            'Content-Disposition' => 'inline; filename="' . $name . '"',
        ]);
    }

    /** Tanggal mulai and berlaku s/d are required for every jenis; nomor for those marked nomor_wajib. */
    private function validated(Request $request, RndProduk $produk, bool $isNew): array
    {
        $jenis = (string) $request->input('jenis');
        $meta = RndProdukSertifikasi::JENIS[$jenis] ?? null;

        $validated = $request->validate([
            'jenis' => ['required', Rule::in(array_keys(RndProdukSertifikasi::JENIS))],
            'nomor' => [($meta['nomor_wajib'] ?? false) ? 'required' : 'nullable', 'string', 'max:255'],
            'penerbit' => ['nullable', 'string', 'max:255'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'sample_log_id' => ['nullable', 'integer', Rule::exists('rnd_sample_log', 'id')->where('produk_id', $produk->id)],
            'catatan' => ['nullable', 'string'],
            'dokumen' => [$isNew ? 'required' : 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ], [
            'nomor.required' => 'Nomor wajib diisi untuk ' . ($meta['label'] ?? 'jenis ini') . '.',
            'tanggal_mulai.required' => 'Tanggal mulai wajib diisi.',
            'tanggal_selesai.required' => 'Tanggal berlaku s/d wajib diisi.',
            'tanggal_selesai.after_or_equal' => 'Tanggal berlaku s/d tidak boleh sebelum tanggal mulai.',
            'dokumen.required' => 'Dokumen sertifikat wajib diupload.',
        ]);

        unset($validated['dokumen']);
        $validated['nomor'] = isset($validated['nomor']) ? trim($validated['nomor']) : null;
        // Only per-batch documents point at a sample
        if (!($meta['per_batch'] ?? false)) {
            $validated['sample_log_id'] = null;
        }

        return $validated;
    }

    /** A valid Notifikasi BPOM means the notif administration is done (same as the status badge flow). */
    private function syncNotifStatus(RndProduk $produk, RndProdukSertifikasi $sertifikasi): void
    {
        if ($sertifikasi->jenis === 'notif_bpom' && $produk->status_administrasi_notif !== 'done') {
            $produk->update(['status_administrasi_notif' => 'done']);
            $this->log($produk->id, 'updated', 'Status Administrasi Notif diubah menjadi done (Notifikasi BPOM ditambahkan).');
        }
    }

    private function jsonResult(RndProduk $produk, string $message)
    {
        $produk->unsetRelation('sertifikasi');

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'sertifikasi' => self::listFor($produk),
                'release_checklist' => $produk->releaseChecklist(),
            ],
        ]);
    }

    private function log(int $produkId, string $activity, string $notes): void
    {
        RndProdukLog::create([
            'produk_id' => $produkId,
            'log_date_time' => now(),
            'status_activity' => $activity,
            'notes' => $notes,
        ]);
    }
}
