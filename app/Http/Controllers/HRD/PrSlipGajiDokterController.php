<?php
namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HRD\PrSlipGajiDokter;
use App\Models\ERM\Dokter;

class PrSlipGajiDokterController extends Controller
{
    public function index(Request $request)
    {
        $bulan = $request->get('bulan') ?? date('Y-m');
        $dokters = Dokter::orderBy('id')->get();
        return view('hrd.payroll.slip_gaji_dokter.index', compact('dokters', 'bulan'));
    }

    public function data(Request $request)
    {
        $bulan = $request->get('bulan');
    // eager load dokter.user so we can display dokter's user name in the table
    $query = PrSlipGajiDokter::with(['dokter.user'])->orderBy('id', 'desc');
        if ($bulan) {
            $query->where('bulan', $bulan);
        }
        $rows = $query->get();
        return response()->json(['data' => $rows]);
    }

    public function store(Request $request)
    {
        // validate basic fields first
        $validated = $request->validate([
            'dokter_id' => 'nullable|integer',
            'bulan' => 'required|string',
            'jasmed_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'status_gaji' => 'nullable|string',
            'pot_pajak' => 'nullable|numeric',
            'pendapatan_tambahan' => 'nullable|array',
            'pendapatan_tambahan.*.label' => 'nullable|string',
            'pendapatan_tambahan.*.amount' => 'nullable',
        ]);

        // Gather all numeric inputs explicitly to avoid missing keys
        $numericFields = [
            'jasa_konsultasi','jasa_tindakan','tunjangan_jabatan','overtime','uang_duduk',
            'peresepan_obat','rujuk_lab','pembuatan_konten','bagi_hasil','potongan_lain'
        ];

        $data = $validated;
        foreach ($numericFields as $f) {
            // Accept values from request; if missing or not numeric, default to 0
            $val = $request->input($f);
            if ($val === null || $val === '') {
                $data[$f] = 0;
            } else {
                // normalize thousand separators and cast to float
                $val = is_string($val) ? str_replace([',', ' '], ['', ''], $val) : $val;
                $data[$f] = is_numeric($val) ? (float) $val : 0;
            }
        }

        // process pendapatan_tambahan array (label + amount)
        $tambahan = [];
        $tambahanTotal = 0;
        if ($request->has('pendapatan_tambahan') && is_array($request->input('pendapatan_tambahan'))) {
            foreach ($request->input('pendapatan_tambahan') as $item) {
                $label = isset($item['label']) ? trim($item['label']) : null;
                $amt = isset($item['amount']) ? $item['amount'] : 0;
                $amt = is_string($amt) ? str_replace([',', ' '], ['', ''], $amt) : $amt;
                $amt = is_numeric($amt) ? (float) $amt : 0;
                if ($label && $amt != 0) {
                    $tambahan[] = ['label' => $label, 'amount' => $amt];
                    $tambahanTotal += $amt;
                }
            }
        }
        $data['pendapatan_tambahan'] = $tambahan ?: null;

        // calculate totals
        $basePendapatan = ($data['jasa_konsultasi'] + $data['jasa_tindakan'] + ($data['tunjangan_jabatan'] ?? 0) + ($data['overtime'] ?? 0) + $data['uang_duduk'] + $data['peresepan_obat'] + $data['rujuk_lab'] + $data['pembuatan_konten']);
        $data['total_pendapatan'] = $basePendapatan + $tambahanTotal;
        // pot_pajak is 2.5% of (base pendapatan (EXCLUDING pendapatan_tambahan) - bagi_hasil)
        $computedBase = max(0, $basePendapatan - ($data['bagi_hasil'] ?? 0));
        $computedPot = round($computedBase * 0.025, 2);
        // allow manual override from request if provided (editable pot_pajak input)
        if (isset($validated['pot_pajak']) && $validated['pot_pajak'] !== null && $validated['pot_pajak'] !== '') {
            $data['pot_pajak'] = (float) $validated['pot_pajak'];
        } else {
            $data['pot_pajak'] = $computedPot;
        }
        $data['total_potongan'] = ($data['pot_pajak'] + ($data['bagi_hasil'] ?? 0) + ($data['potongan_lain'] ?? 0));
        $data['total_gaji'] = $data['total_pendapatan'] - $data['total_potongan'];

        // handle jasmed_file upload if present
        if ($request->hasFile('jasmed_file')) {
            $file = $request->file('jasmed_file');
            $path = $file->store('jasmed_files', 'public');
            $data['jasmed_file'] = $path;
        }

        $slip = PrSlipGajiDokter::create($data);
        return response()->json(['success' => true, 'data' => $slip]);
    }

    public function show($id)
    {
        $slip = PrSlipGajiDokter::with('dokter')->findOrFail($id);
        return response()->json(['data' => $slip]);
    }

    /**
     * Return basic dokter info used by the create/edit JS (klinik_id).
     */
    public function dokterInfo($id)
    {
        $dokter = Dokter::with('klinik')->find($id);
        if (!$dokter) {
            return response()->json(['data' => null], 404);
        }
        return response()->json(['data' => [
            'id' => $dokter->id,
            'klinik_id' => $dokter->klinik_id,
            'klinik' => $dokter->klinik ? ['id' => $dokter->klinik->id, 'nama' => $dokter->klinik->nama ?? null] : null,
        ]]);
    }

    public function update(Request $request, $id)
    {
        $slip = PrSlipGajiDokter::findOrFail($id);
        $data = $request->only([
            'jasa_konsultasi', 'jasa_tindakan', 'tunjangan_jabatan', 'overtime', 'uang_duduk', 'peresepan_obat', 'rujuk_lab', 'pembuatan_konten', 'bagi_hasil', 'potongan_lain', 'pot_pajak', 'status_gaji', 'bulan', 'dokter_id'
        ]);

        // accept jasmed_file on update as well
        if ($request->hasFile('jasmed_file')) {
            $file = $request->file('jasmed_file');
            $path = $file->store('jasmed_files', 'public');
            $data['jasmed_file'] = $path;
            // delete old file if exists
            if ($slip->jasmed_file && file_exists(storage_path('app/public/' . $slip->jasmed_file))) {
                @unlink(storage_path('app/public/' . $slip->jasmed_file));
            }
        }

        // accept pendapatan_tambahan on update as well
        $tambahan = [];
        $tambahanTotal = 0;
        if ($request->has('pendapatan_tambahan') && is_array($request->input('pendapatan_tambahan'))) {
            foreach ($request->input('pendapatan_tambahan') as $item) {
                $label = isset($item['label']) ? trim($item['label']) : null;
                $amt = isset($item['amount']) ? $item['amount'] : 0;
                $amt = is_string($amt) ? str_replace([',', ' '], ['', ''], $amt) : $amt;
                $amt = is_numeric($amt) ? (float) $amt : 0;
                if ($label && $amt != 0) {
                    $tambahan[] = ['label' => $label, 'amount' => $amt];
                    $tambahanTotal += $amt;
                }
            }
        }
        if ($tambahan) $data['pendapatan_tambahan'] = $tambahan;

        foreach ($data as $k => $v) {
            if ($v !== null) $slip->{$k} = $v;
        }

    // recalc
    // Recalculate totals: include pendapatan_tambahan in total_pendapatan; bagi_hasil is a deduction
    $existingTambahan = is_array($slip->pendapatan_tambahan) ? array_column($slip->pendapatan_tambahan, 'amount') : [];
    $sumTambahan = array_sum($existingTambahan);
    $basePendapatan = ($slip->jasa_konsultasi + $slip->jasa_tindakan + ($slip->tunjangan_jabatan ?? 0) + ($slip->overtime ?? 0) + $slip->uang_duduk + ($slip->peresepan_obat ?? 0) + ($slip->rujuk_lab ?? 0) + ($slip->pembuatan_konten ?? 0));
    $slip->total_pendapatan = $basePendapatan + $sumTambahan;
    $computedBase = max(0, $basePendapatan - ($slip->bagi_hasil ?? 0));
    $computedPot = round($computedBase * 0.025, 2);
    // if pot_pajak was provided in the update payload, honor it; otherwise recompute
    if (isset($data['pot_pajak']) && $data['pot_pajak'] !== null && $data['pot_pajak'] !== '') {
        $slip->pot_pajak = (float) $data['pot_pajak'];
    } else {
        $slip->pot_pajak = $computedPot;
    }
    $slip->total_potongan = ($slip->pot_pajak + ($slip->bagi_hasil ?? 0) + ($slip->potongan_lain ?? 0));
    $slip->total_gaji = $slip->total_pendapatan - $slip->total_potongan;

        $slip->save();
        return response()->json(['success' => true, 'data' => $slip]);
    }

    public function destroy($id)
    {
        $slip = PrSlipGajiDokter::findOrFail($id);
        $slip->delete();
        return response()->json(['success' => true]);
    }

    public function print($id)
    {
        $slip = PrSlipGajiDokter::with('dokter')->findOrFail($id);
        // provide terbilang helper closure like employee controller does
        $terbilang = function($angka) {
            $raw = '';
            if (class_exists('\\App\\Helpers\\TerbilangHelper')) {
                $raw = \App\Helpers\TerbilangHelper::terbilang($angka);
            }
            // if helper returned empty, bail
            if (!is_string($raw) || trim($raw) === '') return '';

            // Fix common concatenation issues: ensure a space after scale/connector words
            // (e.g. "jutasembilan" -> "juta sembilan", "ratustujuh" -> "ratus tujuh")
            $patterns = '/(juta|ribu|ratus|puluh|belas)(?=[a-z])/i';
            $fixed = preg_replace($patterns, '$1 ', $raw);

            // Normalize whitespace and capitalize words
            $fixed = preg_replace('/\s+/', ' ', trim($fixed));
            $fixed = ucwords(strtolower($fixed));
            return $fixed;
        };
        $html = view('hrd.payroll.slip_gaji_dokter.print', compact('slip', 'terbilang'))->render();
        // try use mPDF if present (project already uses it elsewhere)
        if (class_exists('\\Mpdf\\Mpdf')) {
            // Use A4 landscape to match employee slip layout
            $mpdf = new \Mpdf\Mpdf(['format' => 'A4-L', 'margin_top' => 5, 'margin_bottom' => 5]);
            // Write HTML using chunked writes to avoid hitting pcre.backtrack_limit
            $this->writeHtmlInChunks($mpdf, $html);

            // If jasmed_file exists, attach it as next page. Handle images directly; merge PDFs using FPDI if the attachment is a PDF.
            $attachmentPath = null;
            if (!empty($slip->jasmed_file)) {
                $possible = storage_path('app/public/' . $slip->jasmed_file);
                if (file_exists($possible)) $attachmentPath = $possible;
            }

            $filename = 'slip-gaji-dokter-' . ($slip->dokter && $slip->dokter->user ? $slip->dokter->user->name : 'dokter') . '-' . $slip->bulan . '.pdf';

            if ($attachmentPath) {
                $mime = @mime_content_type($attachmentPath) ?: 'application/octet-stream';
                if (strpos($mime, 'image/') === 0) {
                    // add image on a new page using mPDF Image() to avoid embedding huge base64 data
                    $mpdf->AddPage();
                    try {
                        // place image with small margin; let mPDF resize to fit
                        $mpdf->Image($attachmentPath, 10, 10, 0, 0, '', '', true, true);
                    } catch (\Exception $e) {
                        // fallback: small HTML that references the file path (should be much smaller than base64)
                        $mpdf->WriteHTML('<div style="text-align:left;"><img src="' . $attachmentPath . '" style="max-width:100%; height:auto;" /></div>');
                    }
                    $pdfString = $mpdf->Output($filename, 'S');
                    return response($pdfString, 200, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'inline; filename="' . $filename . '"',
                        'Content-Length' => strlen($pdfString),
                    ]);
                }

                if ($mime === 'application/pdf' && class_exists('\\setasign\\Fpdi\\Fpdi')) {
                    // Merge the slip PDF and the attachment PDF using FPDI
                    $pdfMain = $mpdf->Output('', 'S');
                    $tmpMain = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'slip_main_' . uniqid() . '.pdf';
                    file_put_contents($tmpMain, $pdfMain);

                    /** @var \setasign\Fpdi\Fpdi $mergedPdf */
                    $mergedPdf = new \setasign\Fpdi\Fpdi();
                    // Import main slip pages
                    $pageCount = $mergedPdf->setSourceFile($tmpMain);
                    for ($p = 1; $p <= $pageCount; $p++) {
                        $tpl = $mergedPdf->importPage($p);
                        $size = $mergedPdf->getTemplateSize($tpl);
                        $mergedPdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                        $mergedPdf->useTemplate($tpl);
                    }
                    // Import attachment pages
                    $attachCount = $mergedPdf->setSourceFile($attachmentPath);
                    for ($p = 1; $p <= $attachCount; $p++) {
                        $tpl = $mergedPdf->importPage($p);
                        $size = $mergedPdf->getTemplateSize($tpl);
                        $mergedPdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                        $mergedPdf->useTemplate($tpl);
                    }

                    $output = call_user_func([$mergedPdf, 'Output'], '', 'S');
                    // cleanup temp main file
                    @unlink($tmpMain);
                    return response($output, 200, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'inline; filename="' . $filename . '"',
                        'Content-Length' => strlen($output),
                    ]);
                }
            }

            // default: just return the generated slip PDF
            $pdfString = $mpdf->Output($filename, 'S');
            return response($pdfString, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
                'Content-Length' => strlen($pdfString),
            ]);
        }

        return response($html);
    }

    /**
     * Write large HTML to mPDF in smaller chunks to avoid PCRE backtrack_limit errors.
     * Tries to split at common closing tags near the chunk boundary to avoid breaking HTML structure.
     *
     * @param \Mpdf\Mpdf $mpdf
     * @param string $html
     * @param int $chunkSize
     * @return void
     */
    private function writeHtmlInChunks($mpdf, $html, $chunkSize = 20000)
    {
        $len = strlen($html);
        $pos = 0;
        $closingTags = ["</div>", "</table>", "</section>", "</article>", "</header>", "</footer>", "</p>"];

        while ($pos < $len) {
            // if remaining is small, write it and break
            if ($len - $pos <= $chunkSize) {
                $part = substr($html, $pos);
                $mpdf->WriteHTML($part);
                break;
            }

            $target = $pos + $chunkSize;

            // Look backward from target within a small lookback window to find a safe split point
            $lookback = 2000; // bytes to look back for a closing tag
            $searchEnd = min($len, $target);
            $searchStart = max($pos, $target - $lookback);
            $fragment = substr($html, $searchStart, $searchEnd - $searchStart);

            $bestEnd = false;
            foreach ($closingTags as $tag) {
                $found = strrpos($fragment, $tag);
                if ($found !== false) {
                    $candidate = $searchStart + $found + strlen($tag);
                    if ($bestEnd === false || $candidate > $bestEnd) {
                        $bestEnd = $candidate;
                    }
                }
            }

            if ($bestEnd !== false && $bestEnd > $pos) {
                $end = $bestEnd;
            } else {
                // fallback: cut at target
                $end = $target;
            }

            $part = substr($html, $pos, $end - $pos);
            $mpdf->WriteHTML($part);
            $pos = $end;
        }
    }
}
