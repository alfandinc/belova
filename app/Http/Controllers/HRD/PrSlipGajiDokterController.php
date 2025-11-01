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
        $data = $request->validate([
            'dokter_id' => 'nullable|integer',
            'bulan' => 'required|string',
            'jasa_konsultasi' => 'nullable|numeric',
            'jasa_tindakan' => 'nullable|numeric',
            'uang_duduk' => 'nullable|numeric',
            'bagi_hasil' => 'nullable|numeric',
            'pot_pajak' => 'nullable|numeric',
            'jasmed_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'status_gaji' => 'nullable|string',
        ]);

        $data = array_merge([
            'jasa_konsultasi' => 0,
            'jasa_tindakan' => 0,
            'uang_duduk' => 0,
            'bagi_hasil' => 0,
            'pot_pajak' => 0,
            'total_pendapatan' => 0,
            'total_potongan' => 0,
            'total_gaji' => 0,
            'status_gaji' => $data['status_gaji'] ?? 'draft',
        ], $data);

        // calculate totals
        // Note: 'bagi_hasil' is treated as a potongan (deduction), not a pendapatan
        $data['total_pendapatan'] = ($data['jasa_konsultasi'] + $data['jasa_tindakan'] + $data['uang_duduk']);
        $data['total_potongan'] = ($data['pot_pajak'] + $data['bagi_hasil']);
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

    public function update(Request $request, $id)
    {
        $slip = PrSlipGajiDokter::findOrFail($id);
        $data = $request->only([
            'jasa_konsultasi', 'jasa_tindakan', 'uang_duduk', 'bagi_hasil', 'pot_pajak', 'status_gaji', 'bulan', 'dokter_id'
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

        foreach ($data as $k => $v) {
            if ($v !== null) $slip->{$k} = $v;
        }

        // recalc
    // Recalculate totals: bagi_hasil is a deduction
    $slip->total_pendapatan = ($slip->jasa_konsultasi + $slip->jasa_tindakan + $slip->uang_duduk);
    $slip->total_potongan = ($slip->pot_pajak + $slip->bagi_hasil);
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
            $mpdf->WriteHTML($html);

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
                    // embed image on a new page
                    $imgData = base64_encode(file_get_contents($attachmentPath));
                    $imgSrc = 'data:' . $mime . ';base64,' . $imgData;
                    $mpdf->AddPage();
                    $mpdf->WriteHTML('<div style="text-align:left;"><img src="' . $imgSrc . '" style="max-width:100%; height:auto;" /></div>');
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

                    $output = $mergedPdf->Output('', 'S');
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
}
