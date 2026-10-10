<?php
namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\HRD\PrSlipGajiDokter;
use App\Models\ERM\Dokter;

class PrSlipGajiDokterController extends Controller
{
    const NUMERIC_FIELDS = [
        'jasa_konsultasi', 'jasa_tindakan', 'tunjangan_jabatan', 'overtime', 'uang_duduk',
        'peresepan_obat', 'rujuk_lab', 'pembuatan_konten', 'bagi_hasil', 'potongan_lain',
    ];

    const STATUSES = ['draft', 'submitted', 'approved', 'rejected', 'paid'];

    // ---------- roles & status flow (mirrors slip gaji karyawan) ----------

    protected function isPayrollOperator($user)
    {
        return $user && $user->hasAnyRole(['Hrd', 'Admin', 'Manager']);
    }

    protected function isCeo($user)
    {
        return $user && $user->hasAnyRole(['Ceo', 'CEO']);
    }

    protected function isAdmin($user)
    {
        return $user && $user->hasRole('Admin');
    }

    protected function normalizeStatus($status)
    {
        $s = strtolower(trim((string) $status));
        return in_array($s, self::STATUSES, true) ? $s : 'draft';
    }

    protected function isEditable(PrSlipGajiDokter $slip)
    {
        return in_array($this->normalizeStatus($slip->status_gaji), ['draft', 'rejected'], true);
    }

    /**
     * Status transitions the user may perform from $current.
     * HRD: draft/rejected -> submitted, approved -> paid. CEO: submitted -> approved/rejected.
     * Admin: paid -> draft ("Unpaid", e.g. marked Paid by mistake; it must be submitted & approved again).
     * A user with several roles gets the union.
     */
    protected function allowedTransitions($user, $current)
    {
        $current = $this->normalizeStatus($current);
        $allowed = [];
        if ($this->isPayrollOperator($user)) {
            if (in_array($current, ['draft', 'rejected'], true)) $allowed[] = 'submitted';
            if ($current === 'approved') $allowed[] = 'paid';
        }
        if ($this->isCeo($user) && $current === 'submitted') {
            $allowed[] = 'approved';
            $allowed[] = 'rejected';
        }
        if ($this->isAdmin($user) && $current === 'paid') {
            $allowed[] = 'draft';
        }
        return $allowed;
    }

    public function index(Request $request)
    {
        $bulan = $request->get('bulan') ?? date('Y-m');
        $dokters = Dokter::with('user')->orderBy('id')->get();
        $user = Auth::user();
        $isCeoView = $this->isCeo($user);
        $canManage = $this->isPayrollOperator($user);
        $isAdmin = $this->isAdmin($user);
        return view('hrd.payroll.slip_gaji_dokter.index', compact('dokters', 'bulan', 'isCeoView', 'canManage', 'isAdmin'));
    }

    protected function filteredQuery(Request $request)
    {
        $bulan = $request->get('bulan');
        $status = strtolower(trim((string) $request->get('status', '')));
        // eager load dokter.user so we can display dokter's user name in the table
        $query = PrSlipGajiDokter::with(['dokter.user'])->orderBy('id', 'desc');
        if ($bulan) {
            $query->where('bulan', $bulan);
        }
        if (in_array($status, self::STATUSES, true)) {
            $query->where('status_gaji', $status);
        }
        return $query;
    }

    public function data(Request $request)
    {
        $user = Auth::user();
        $rows = $this->filteredQuery($request)->get();

        // last month's total per dokter (for the CEO comparison)
        $prevTotals = collect();
        if ($request->get('bulan') && preg_match('/^\d{4}-\d{2}$/', $request->get('bulan'))) {
            $prevBulan = \Carbon\Carbon::createFromFormat('Y-m-d', $request->get('bulan') . '-01')->subMonth()->format('Y-m');
            $prevTotals = PrSlipGajiDokter::where('bulan', $prevBulan)
                ->whereIn('dokter_id', $rows->pluck('dokter_id')->filter()->unique())
                ->pluck('total_gaji', 'dokter_id');
        }

        $rows->each(function ($row) use ($user, $prevTotals) {
            $row->status_gaji = $this->normalizeStatus($row->status_gaji);
            $row->setAttribute('allowed_transitions', $this->allowedTransitions($user, $row->status_gaji));
            $row->setAttribute('editable', $this->isEditable($row) && $this->isPayrollOperator($user));
            $row->setAttribute('last_month_total_gaji', $prevTotals->has($row->dokter_id) ? (float) $prevTotals[$row->dokter_id] : null);
        });

        return response()->json([
            'data' => $rows,
            'total_beban' => (float) $rows->sum('total_gaji'),
        ]);
    }

    public function export(Request $request)
    {
        $rows = $this->filteredQuery($request)->get()->sortBy(function ($s) {
            return optional(optional($s->dokter)->user)->name;
        })->values();
        $bulan = $request->get('bulan') ?: 'semua';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\SlipGajiDokterExport($rows),
            'slip-gaji-dokter-' . $bulan . '.xlsx'
        );
    }

    /**
     * Validation shared by store & update.
     */
    protected function validateSlip(Request $request)
    {
        $rules = [
            'dokter_id' => 'required|integer|exists:erm_dokters,id',
            'bulan' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'jasmed_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'pot_pajak' => 'nullable|numeric',
            'pendapatan_tambahan' => 'nullable|array',
            'pendapatan_tambahan.*.label' => 'nullable|string|max:255',
            'pendapatan_tambahan.*.amount' => 'nullable',
        ];
        foreach (self::NUMERIC_FIELDS as $f) {
            $rules[$f] = 'nullable|numeric';
        }

        return $request->validate($rules, [
            'dokter_id.required' => 'Dokter wajib dipilih.',
            'dokter_id.exists' => 'Dokter tidak ditemukan.',
            'bulan.regex' => 'Format bulan harus YYYY-MM.',
            'jasmed_file.mimes' => 'Lampiran harus berupa PDF, JPG, atau PNG.',
        ]);
    }

    /**
     * One slip per dokter per bulan.
     */
    protected function duplicateSlipExists($dokterId, $bulan, $ignoreId = null)
    {
        return PrSlipGajiDokter::where('dokter_id', $dokterId)
            ->where('bulan', $bulan)
            ->when($ignoreId, function ($q) use ($ignoreId) {
                $q->where('id', '!=', $ignoreId);
            })
            ->exists();
    }

    protected function parseTambahan(Request $request)
    {
        $tambahan = [];
        if (is_array($request->input('pendapatan_tambahan'))) {
            foreach ($request->input('pendapatan_tambahan') as $item) {
                $label = isset($item['label']) ? trim($item['label']) : null;
                $amt = isset($item['amount']) ? $item['amount'] : 0;
                $amt = is_string($amt) ? str_replace([',', ' '], ['', ''], $amt) : $amt;
                $amt = is_numeric($amt) ? (float) $amt : 0;
                if ($label && $amt != 0) {
                    $tambahan[] = ['label' => $label, 'amount' => $amt];
                }
            }
        }
        return $tambahan;
    }

    /**
     * Fill numeric fields, pendapatan tambahan and totals on the slip.
     * pot_pajak = 2.5% of (base pendapatan EXCLUDING pendapatan_tambahan - bagi_hasil), unless overridden.
     */
    protected function fillAmounts(PrSlipGajiDokter $slip, Request $request)
    {
        foreach (self::NUMERIC_FIELDS as $f) {
            $val = $request->input($f);
            $slip->{$f} = ($val === null || $val === '' || !is_numeric($val)) ? 0 : (float) $val;
        }

        $tambahan = $this->parseTambahan($request);
        // The create/edit form always posts the full list, so an empty list clears it
        $slip->pendapatan_tambahan = $tambahan ?: null;
        $tambahanTotal = array_sum(array_column($tambahan, 'amount'));

        $basePendapatan = $slip->jasa_konsultasi + $slip->jasa_tindakan + $slip->tunjangan_jabatan
            + $slip->overtime + $slip->uang_duduk + $slip->peresepan_obat + $slip->rujuk_lab + $slip->pembuatan_konten;
        $slip->total_pendapatan = $basePendapatan + $tambahanTotal;

        $potPajak = $request->input('pot_pajak');
        if ($potPajak !== null && $potPajak !== '' && is_numeric($potPajak)) {
            $slip->pot_pajak = (float) $potPajak;
        } else {
            $slip->pot_pajak = round(max(0, $basePendapatan - $slip->bagi_hasil) * 0.025, 2);
        }

        $slip->total_potongan = $slip->pot_pajak + $slip->bagi_hasil + $slip->potongan_lain;
        $slip->total_gaji = $slip->total_pendapatan - $slip->total_potongan;
    }

    protected function forbidden($message)
    {
        return response()->json(['success' => false, 'message' => $message], 403);
    }

    public function store(Request $request)
    {
        if (!$this->isPayrollOperator(Auth::user())) {
            return $this->forbidden('Anda tidak memiliki izin untuk membuat slip gaji dokter.');
        }

        $validated = $this->validateSlip($request);

        if ($this->duplicateSlipExists($validated['dokter_id'], $validated['bulan'])) {
            return response()->json([
                'success' => false,
                'message' => 'Slip gaji untuk dokter ini pada bulan tersebut sudah ada. Silakan edit slip yang sudah ada.'
            ], 422);
        }

        $slip = new PrSlipGajiDokter();
        $slip->dokter_id = $validated['dokter_id'];
        $slip->bulan = $validated['bulan'];
        // new slips always start as Draft; status changes go through changeStatus()
        $slip->status_gaji = 'draft';
        $this->fillAmounts($slip, $request);

        if ($request->hasFile('jasmed_file')) {
            $slip->jasmed_file = $request->file('jasmed_file')->store('jasmed_files', 'public');
        }

        $slip->save();
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

        if (!$this->isPayrollOperator(Auth::user())) {
            return $this->forbidden('Anda tidak memiliki izin untuk mengubah slip gaji dokter.');
        }
        if (!$this->isEditable($slip)) {
            return $this->forbidden('Hanya slip dengan status Draft atau Rejected yang bisa diedit.');
        }

        $validated = $this->validateSlip($request);

        if ($this->duplicateSlipExists($validated['dokter_id'], $validated['bulan'], $slip->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Slip gaji untuk dokter ini pada bulan tersebut sudah ada.'
            ], 422);
        }

        $slip->dokter_id = $validated['dokter_id'];
        $slip->bulan = $validated['bulan'];
        $this->fillAmounts($slip, $request);

        if ($request->hasFile('jasmed_file')) {
            $oldFile = $slip->jasmed_file;
            $slip->jasmed_file = $request->file('jasmed_file')->store('jasmed_files', 'public');
            if ($oldFile) {
                Storage::disk('public')->delete($oldFile);
            }
        }

        $slip->save();
        return response()->json(['success' => true, 'data' => $slip]);
    }

    public function changeStatus(Request $request, $id)
    {
        $slip = PrSlipGajiDokter::findOrFail($id);
        $next = strtolower(trim((string) $request->input('status_gaji')));
        $current = $this->normalizeStatus($slip->status_gaji);

        if (!in_array($next, $this->allowedTransitions(Auth::user(), $current), true)) {
            return $this->forbidden('Perubahan status dari ' . ucfirst($current) . ' ke ' . ucfirst($next ?: '-') . ' tidak diizinkan.');
        }

        $slip->status_gaji = $next;
        $slip->save();
        return response()->json(['success' => true, 'status' => $next]);
    }

    /**
     * Submit every Draft/Rejected slip of a month to the CEO.
     */
    public function bulkSubmit(Request $request)
    {
        if (!$this->isPayrollOperator(Auth::user())) {
            return $this->forbidden('Anda tidak memiliki izin untuk submit slip gaji dokter.');
        }
        $request->validate(['bulan' => ['required', 'regex:/^\d{4}-\d{2}$/']]);

        $count = PrSlipGajiDokter::where('bulan', $request->input('bulan'))
            ->whereIn('status_gaji', ['draft', 'rejected'])
            ->update(['status_gaji' => 'submitted', 'updated_at' => now()]);

        return response()->json([
            'success' => true,
            'updated' => $count,
            'message' => $count ? "{$count} slip berhasil disubmit ke CEO." : 'Tidak ada slip Draft/Rejected di bulan ini.',
        ]);
    }

    public function destroy($id)
    {
        $slip = PrSlipGajiDokter::findOrFail($id);

        if (!$this->isPayrollOperator(Auth::user())) {
            return $this->forbidden('Anda tidak memiliki izin untuk menghapus slip gaji dokter.');
        }
        if (!$this->isEditable($slip)) {
            return $this->forbidden('Hanya slip dengan status Draft atau Rejected yang bisa dihapus.');
        }

        if ($slip->jasmed_file) {
            Storage::disk('public')->delete($slip->jasmed_file);
        }
        $slip->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Serve the lampiran through the authenticated route (role-restricted by the route group).
     */
    public function serveJasmed($id)
    {
        $slip = PrSlipGajiDokter::findOrFail($id);
        if (!$slip->jasmed_file || !Storage::disk('public')->exists($slip->jasmed_file)) {
            abort(404);
        }
        $fullPath = Storage::disk('public')->path($slip->jasmed_file);
        $mime = @mime_content_type($fullPath) ?: 'application/octet-stream';
        return response()->file($fullPath, ['Content-Type' => $mime]);
    }

    // ---------- Dokter's own slips ("My Payroll" for dokter users) ----------

    protected function currentDokter()
    {
        $user = Auth::user();
        return $user ? Dokter::where('user_id', $user->id)->first() : null;
    }

    public function myHistoryPage()
    {
        $dokter = $this->currentDokter();
        $verified = PrSlipGajiController::isSlipAccessVerified();

        $years = [date('Y')];
        $currentYear = date('Y');
        if ($dokter && $verified) {
            $slipYears = PrSlipGajiDokter::where('dokter_id', $dokter->id)
                ->where('status_gaji', 'paid')
                ->pluck('bulan')
                ->map(fn($b) => substr((string) $b, 0, 4))
                ->filter(fn($y) => preg_match('/^\d{4}$/', $y))
                ->unique()->values()->all();
            // default to the latest year that actually has a slip
            if (!empty($slipYears) && !in_array($currentYear, $slipYears, true)) {
                $currentYear = max($slipYears);
            }
            $years = array_values(array_unique(array_merge($years, $slipYears)));
            rsort($years);
        }

        return view('hrd.payroll.slip_gaji_dokter.my_history', [
            'hasDokter' => (bool) $dokter,
            'verified' => $verified,
            'years' => $years,
            'currentYear' => $currentYear,
        ]);
    }

    public function myHistoryData(Request $request)
    {
        $dokter = $this->currentDokter();
        if (!$dokter) {
            return response()->json(['data' => []]);
        }
        if (!PrSlipGajiController::isSlipAccessVerified()) {
            return response()->json(['message' => 'Sesi verifikasi slip gaji sudah habis. Silakan verifikasi password lagi.'], 403);
        }

        $year = (string) $request->input('year');
        $query = PrSlipGajiDokter::where('dokter_id', $dokter->id)->where('status_gaji', 'paid');
        if (preg_match('/^\d{4}$/', $year)) {
            $query->where('bulan', 'like', $year . '-%');
        }

        $months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $rows = $query->orderBy('bulan', 'desc')->get()->values();

        $data = $rows->map(function ($s, $i) use ($rows, $months) {
            $m = (int) substr((string) $s->bulan, 5, 2);
            $prev = $rows->get($i + 1);
            $trend = null;
            if ($prev) {
                $trend = $s->total_gaji > $prev->total_gaji ? 'up' : ($s->total_gaji < $prev->total_gaji ? 'down' : 'same');
            }
            return [
                'id' => $s->id,
                'bulan' => $s->bulan,
                'bulan_label' => ($months[$m] ?? $s->bulan) . ' ' . substr((string) $s->bulan, 0, 4),
                'total_pendapatan' => (float) $s->total_pendapatan,
                'total_potongan' => (float) $s->total_potongan,
                'total_gaji' => (float) $s->total_gaji,
                'total_gaji_trend' => $trend,
                'view_url' => route('hrd.payroll.slip_gaji_dokter.my.download', $s->id),
                'download_url' => route('hrd.payroll.slip_gaji_dokter.my.download', ['id' => $s->id, 'download' => 1]),
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function myDownload(Request $request, $id)
    {
        $dokter = $this->currentDokter();
        if (!$dokter) {
            abort(403);
        }
        if (!PrSlipGajiController::isSlipAccessVerified()) {
            return redirect()->route('hrd.payroll.slip_gaji_dokter.my');
        }

        $slip = PrSlipGajiDokter::with('dokter.user')
            ->where('id', $id)
            ->where('dokter_id', $dokter->id)
            ->where('status_gaji', 'paid')
            ->firstOrFail();

        return $this->renderSlipPdf($slip, $request->boolean('download') ? 'attachment' : 'inline');
    }

    public function print($id)
    {
        $slip = PrSlipGajiDokter::with('dokter.user')->findOrFail($id);
        return $this->renderSlipPdf($slip);
    }

    /**
     * Render the slip (plus its lampiran) as a PDF response.
     * $disposition: 'inline' to view in the browser, 'attachment' to download.
     */
    protected function renderSlipPdf(PrSlipGajiDokter $slip, $disposition = 'inline')
    {
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

            $dokterName = $slip->dokter && $slip->dokter->user ? $slip->dokter->user->name : 'dokter';
            // names contain dots/commas ("dr. X, Sp.PD") -> keep the header filename safe
            $filename = 'slip-gaji-dokter-' . trim(preg_replace('/[^A-Za-z0-9_\-]+/', '-', $dokterName), '-') . '-' . $slip->bulan . '.pdf';

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
                        'Content-Disposition' => $disposition . '; filename="' . $filename . '"',
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
                        'Content-Disposition' => $disposition . '; filename="' . $filename . '"',
                        'Content-Length' => strlen($output),
                    ]);
                }
            }

            // default: just return the generated slip PDF
            $pdfString = $mpdf->Output($filename, 'S');
            return response($pdfString, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => $disposition . '; filename="' . $filename . '"',
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
