<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\ERM\HasilSkincheck;
use App\Models\ERM\Visitation;
use Illuminate\Support\Str;
use Zxing\QrReader;
use Yajra\DataTables\Facades\DataTables;

class HasilSkincheckController extends Controller
{
    /**
     * Store a newly uploaded QR image and decoded url for a visitation.
     * This is intentionally standalone and not associated with asesmen models.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'visitation_id' => ['required', 'string', 'exists:erm_visitations,id'],
            'pasien_id' => ['nullable', 'string', 'exists:erm_pasiens,id'],
            'qr_image' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:5120'],
            'decoded_text' => ['nullable', 'string'],
        ]);

        // store image in public disk
        $path = $request->file('qr_image')->store('hasil_skinchecks', 'public');

        // prefer decoded_text; if client sent only 'url' for backward compatibility, use that
        $decoded = $data['decoded_text'] ?? $request->input('url') ?? null;

        // If client didn't provide decoded text, attempt server-side decoding as a fallback
        if (empty($decoded)) {
            $fullPath = storage_path('app/public/' . $path);
            try {
                $decoded = $this->attemptDecode($fullPath);
            } catch (\Throwable $e) {
                logger()->warning('HasilSkincheck server decode error: ' . $e->getMessage());
            }
        }

        // if decoded looks like URL, set url field, otherwise leave null
        $url = null;
        if ($decoded && filter_var($decoded, FILTER_VALIDATE_URL)) {
            $url = $decoded;
        }

        $record = HasilSkincheck::create([
            'visitation_id' => $data['visitation_id'],
            'pasien_id' => $data['pasien_id'] ?? $request->input('pasien_id') ?? null,
            'qr_image' => $path,
            'decoded_text' => $decoded,
            'url' => $url,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Hasil skincheck tersimpan',
            'data' => $record,
        ]);
    }

    /**
     * Decode endpoint (no DB save) - accepts an uploaded image and returns decoded text/url
     */
    public function decode(Request $request)
    {
        $data = $request->validate([
            'qr_image' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:10240'],
        ]);

        // store temporarily in app/tmp with a stable unique filename (keep extension)
        $uploaded = $request->file('qr_image');
        $ext = $uploaded->getClientOriginalExtension() ?: 'jpg';
        $filename = 'qr_' . uniqid() . '.' . $ext;
        $tmpDir = storage_path('app/tmp');
        if (!file_exists($tmpDir)) {
            @mkdir($tmpDir, 0777, true);
        }
        $tmp = 'tmp/' . $filename;
        $uploaded->move($tmpDir, $filename);
        $fullPath = storage_path('app/' . $tmp);

        logger()->debug('HasilSkincheck decode tmp path: ' . $fullPath . ' exists:' . (file_exists($fullPath) ? 'yes' : 'no'));

        try {
            // temporarily increase memory for image decoding (may be large uploads)
            @ini_set('memory_limit', '256M');
            $decoded = $this->attemptDecode($fullPath);
        } catch (\Throwable $e) {
            logger()->warning('HasilSkincheck decode endpoint error: ' . $e->getMessage());
            // if it's an OutOfMemory error, return clear info
            $msg = 'Gagal mendecode gambar.';
            if ($e instanceof \Error && stripos($e->getMessage(), 'Allowed memory') !== false) {
                $msg = 'Gagal mendecode: memory PHP tidak cukup. Silakan unggah gambar lebih kecil atau tingkatkan memory_limit.';
            }
            // clean up
            @unlink($fullPath);
            return response()->json(['decoded_text' => null, 'url' => null, 'message' => $msg], 500);
        }

        // clean up
        @unlink($fullPath);

        $url = null;
        if ($decoded && filter_var($decoded, FILTER_VALIDATE_URL)) {
            $url = $decoded;
        }

        return response()->json(['decoded_text' => $decoded, 'url' => $url], 200);
    }

    /**
     * Return recent skincheck riwayat for a pasien (AJAX JSON)
     */
    public function riwayat(Request $request)
    {
        $data = $request->validate([
            'pasien_id' => ['required', 'string', 'exists:erm_pasiens,id'],
        ]);

        // Build an Eloquent query so Yajra can handle paging/searching/sorting server-side
        $query = HasilSkincheck::where('pasien_id', $data['pasien_id'])->orderBy('created_at', 'desc')->select(['id', 'created_at', 'qr_image', 'decoded_text', 'url']);

        return DataTables::of($query)
            ->editColumn('created_at', function ($r) {
                return $r->created_at ? $r->created_at->format('Y-m-d H:i') : null;
            })
            ->addColumn('qr_image', function ($r) {
                return $r->qr_image ? asset('storage/' . $r->qr_image) : null;
            })
            ->make(true);
    }

    /**
     * Attempt to decode QR from a local image path.
     * Tries direct decoding first, then a few GD-based preprocessing + rotation attempts.
     * Returns decoded string or null.
     */
    private function attemptDecode(string $fullPath): ?string
    {
        // try direct decode
        try {
            $q = new QrReader($fullPath);
            $text = trim((string) $q->text());
            if ($text !== '') return $text;
        } catch (\Throwable $e) {
            // continue to preprocessing
            logger()->debug('QrReader direct decode failed: ' . $e->getMessage());
        }

        // ensure GD is available for preprocessing
        if (!function_exists('imagecreatefromstring')) {
            return null;
        }

        $raw = @file_get_contents($fullPath);
        if ($raw === false) return null;

        $img = @imagecreatefromstring($raw);
        if (!$img) return null;

        $w = imagesx($img);
        $h = imagesy($img);

        // downscale if very large to speed up processing
        $maxSide = 1024;
        $scale = 1.0;
        if (max($w, $h) > $maxSide) {
            $scale = $maxSide / max($w, $h);
        }

        $newW = max(1, (int)($w * $scale));
        $newH = max(1, (int)($h * $scale));

        $proc = imagecreatetruecolor($newW, $newH);
        imagecopyresampled($proc, $img, 0, 0, 0, 0, $newW, $newH, $w, $h);
        imagedestroy($img);

        // convert to grayscale and increase contrast
        @imagefilter($proc, IMG_FILTER_GRAYSCALE);
        @imagefilter($proc, IMG_FILTER_CONTRAST, -25);

        $tempFiles = [];
        try {
            // try multiple rotations including 0
            $angles = [0, 90, 180, 270];
            foreach ($angles as $angle) {
                $tryImg = $proc;
                if ($angle !== 0) {
                    $tryImg = imagerotate($proc, $angle, 0);
                }

                $tmpPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'qr_' . uniqid() . '.jpg';
                imagejpeg($tryImg, $tmpPath, 90);
                $tempFiles[] = $tmpPath;

                try {
                    $q2 = new QrReader($tmpPath);
                    $t = trim((string) $q2->text());
                    if ($t !== '') {
                        // cleanup
                        foreach ($tempFiles as $f) { @unlink($f); }
                        if ($tryImg !== $proc) {@imagedestroy($tryImg);} 
                        @imagedestroy($proc);
                        return $t;
                    }
                } catch (\Throwable $e) {
                    // ignore and try next rotation
                    logger()->debug('QrReader preprocessing attempt failed: ' . $e->getMessage());
                }

                if ($tryImg !== $proc) {@imagedestroy($tryImg);} 
            }
        } finally {
            // free base processed image
            @imagedestroy($proc);
        }

        // cleanup temp files
        foreach ($tempFiles as $f) { @unlink($f); }

        return null;
    }
}

