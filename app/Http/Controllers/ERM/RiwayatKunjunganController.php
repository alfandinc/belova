<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ERM\Helper\PasienHelperController;
use App\Http\Controllers\ERM\Helper\KunjunganHelperController;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\ERM\Visitation;
use App\Models\ERM\SuratDiagnosa;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class RiwayatKunjunganController extends Controller
{
    public function getDataDiagnosisTable($pasienId)
    {
        $diagnosas = SuratDiagnosa::whereHas('visitation', function($q) use ($pasienId) {
            $q->where('pasien_id', $pasienId);
        })->with(['visitation.dokter.user', 'visitation.dokter.spesialisasi', 'visitation.spesialisasi'])
            ->orderByDesc('updated_at')->get();

        return \Yajra\DataTables\DataTables::of($diagnosas)
            ->addIndexColumn()
            ->addColumn('dokter', function($row) {
                return $row->visitation->dokter->user->name ?? '-';
            })
            ->addColumn('spesialisasi', function($row) {
                return $row->visitation->listSpesialisasiNama() ?? '-';
            })
            ->addColumn('tanggal', function($row) {
                return $row->created_at ? $row->created_at->format('d/m/Y') : '-';
            })
            ->editColumn('keterangan', fn ($row) => e($row->keterangan ?? '-'))
            ->addColumn('aksi', function($row) {
                // Printed straight away (.btn-pdf-print, public/js/erm-pdf-preview.js); Ubah fills the form again
                $printUrl = route('riwayatkunjungan.print-surat-diagnosis', $row->visitation_id);
                $printUrlEn = route('riwayatkunjungan.print-surat-diagnosis-en', $row->visitation_id);
                return '<div class="text-nowrap">'
                    . '<a href="' . $printUrl . '" class="btn btn-sm btn-outline-primary btn-pdf-print mr-1" data-title="Surat Keterangan Diagnosis"><i class="fas fa-print mr-1"></i>Cetak</a>'
                    . '<a href="' . $printUrlEn . '" class="btn btn-sm btn-outline-info btn-pdf-print mr-1" data-title="Medical Diagnosis Certificate"><i class="fas fa-print mr-1"></i>EN</a>'
                    . '<button type="button" class="btn btn-sm btn-outline-secondary btn-ubah-diagnosa" data-visit="' . (int) $row->visitation_id . '"><i class="fas fa-pen mr-1"></i>Ubah</button>'
                    . '</div>';
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }
    public function index(Request $request, $pasien)
    {
        // The table's own requests need none of the page data below
        if ($request->ajax()) {
            return $this->riwayatData($request, $pasien);
        }

        // The visit the page was opened from (navbar menu + identity card), so the menu keeps pointing at it;
        // the patient's latest visit when none is given
        $visitation = $request->filled('visitation_id')
            ? Visitation::where('pasien_id', $pasien)->whereKey($request->query('visitation_id'))->first()
            : null;
        $visitation ??= Visitation::where('pasien_id', $pasien)->latest()->first();
        abort_if(!$visitation, 404, 'Pasien ini belum punya kunjungan.');

        $pasienData = PasienHelperController::getDataPasien($visitation->id);
        $createKunjunganData = KunjunganHelperController::getCreateKunjungan($visitation->id);

        $invoices = \App\Models\Finance\Invoice::whereHas('visitation', fn ($q) => $q->where('pasien_id', $pasien));
        $lastVisit = Visitation::where('pasien_id', $pasien)->where('status_kunjungan', 2)->max('tanggal_visitation');

        return view('erm.riwayatkunjungan.index', array_merge([
            'visitation' => $visitation,
            'pasien' => $pasien,
            'jenisFilters' => Visitation::rawatJalanFilters(),
            'stats' => [
                'total_visits' => Visitation::where('pasien_id', $pasien)->where('status_kunjungan', 2)->count(),
                'last_visit' => $lastVisit ? \Carbon\Carbon::parse($lastVisit)->translatedFormat('d M Y') : '-',
                'total_tagihan' => (clone $invoices)->sum('total_amount'),
                'total_terbayar' => (clone $invoices)->sum('amount_paid'),
            ],
        ], $pasienData, $createKunjunganData));
    }

    private const STATUS_KUNJUNGAN = [
        0 => ['Tidak datang', '#94a3b8'],
        1 => ['Belum diperiksa', '#d97706'],
        2 => ['Sudah diperiksa', '#16a34a'],
        Visitation::STATUS_DIBATALKAN => ['Dibatalkan', '#dc2626'],
    ];

    /** Filters shared by the rows and the chip counts: status, date range and unpaid bill (jenis is the chips themselves). */
    private function applyRiwayatFilters($query, Request $request): void
    {
        if ($request->filled('status_kunjungan')) {
            $query->where('status_kunjungan', $request->get('status_kunjungan'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('tanggal_visitation', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('tanggal_visitation', '<=', $request->get('date_to'));
        }
        // Belum lunas: the visit's invoice is issued or partly paid
        if ($request->get('bayar') === 'belum') {
            $query->whereHas('invoice', fn ($q) => $q->whereIn('status', ['issued', 'partial']));
        }
    }

    private const INVOICE_LABEL = ['paid' => ['Lunas', '#16a34a'], 'partial' => ['Dibayar sebagian', '#0284c7'], 'issued' => ['Ditagih', '#d97706']];

    /** DataTable rows of the Riwayat Kunjungan page (+ the per-chip counts and how many visits the filters hide). */
    private function riwayatData(Request $request, $pasien)
    {
        $filters = Visitation::rawatJalanFilters();

        $kunjungan = Visitation::with([
                'metodeBayar:id,nama', 'dokter.user:id,name', 'dokter.spesialisasi:id,nama', 'spesialisasi:id,nama',
                'asesmenPenunjang:id,visitation_id,diagnosakerja_1,diagnosakerja_2,diagnosakerja_3,diagnosakerja_4,diagnosakerja_5',
                'invoice:id,visitation_id,total_amount,amount_paid,status',
            ])
            ->select('erm_visitations.*') // ini penting!
            ->withCount(['riwayatTindakan', 'resepDokter', 'labPermintaan'])
            ->selectSub(fn ($q) => $q->from('erm_radiologi_permintaan')->selectRaw('count(*)')
                ->whereColumn('erm_radiologi_permintaan.visitation_id', 'erm_visitations.id'), 'radiologi_count')
            ->where('pasien_id', $pasien);
        // Newest first unless the user sorts a column (a fixed orderBy here would override that sort)
        if (!$request->has('order')) {
            $kunjungan->orderByDesc('tanggal_visitation')->orderByDesc('erm_visitations.created_at');
        }
        $this->applyRiwayatFilters($kunjungan, $request);

        // Visits per jenis chip under the other filters
        $countQuery = Visitation::where('pasien_id', $pasien);
        $this->applyRiwayatFilters($countQuery, $request);
        $perType = $countQuery->selectRaw('jenis_kunjungan, count(*) as c')->groupBy('jenis_kunjungan')->pluck('c', 'jenis_kunjungan');
        $jenisCounts = collect($filters)->map(fn ($f) => $f['types'] ? collect($f['types'])->sum(fn ($t) => (int) ($perType[$t] ?? 0)) : (int) $perType->sum());

        if ($request->filled('jenis_kunjungan')) {
            // Same groups as the Rawat Jalan filter ('klinis', 'produk', or one type)
            $jenis = (string) $request->get('jenis_kunjungan');
            $kunjungan->whereIn('jenis_kunjungan', $filters[$jenis]['types'] ?? [(int) $jenis]);
        }

        $rupiah = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
        $badge = fn ($label, $color) => '<span class="rk-badge" style="--rk-c:' . $color . '">' . e($label) . '</span>';

        return DataTables::of($kunjungan)
            ->with('jenis_counts', $jenisCounts)
            ->with('total_all', Visitation::where('pasien_id', $pasien)->count())
            ->addIndexColumn()
            ->addColumn('kunjungan', function ($row) use ($badge) {
                [$label, $color] = self::STATUS_KUNJUNGAN[(int) $row->status_kunjungan] ?? ['Status ' . $row->status_kunjungan, '#64748b'];
                return '<div class="rk-date">' . e(\Carbon\Carbon::parse($row->tanggal_visitation)->translatedFormat('d F Y')) . '</div>'
                    . '<div class="rk-badges">' . $badge(Visitation::typeLabel($row->jenis_kunjungan), Visitation::TYPE_COLORS[(int) $row->jenis_kunjungan] ?? '#64748b')
                    . $badge($label, $color) . '</div>';
            })
            ->addColumn('dokter', function ($row) {
                $spesialisasi = $row->listSpesialisasiNama();
                return '<div class="rk-dokter">' . e(optional(optional($row->dokter)->user)->name ?? '-') . '</div>'
                    . ($spesialisasi ? '<div class="rk-sub">' . e($spesialisasi) . '</div>' : '');
            })
            ->addColumn('ringkasan', function ($row) {
                $dx = $row->asesmenPenunjang
                    ? collect(range(1, 5))->map(fn ($i) => trim((string) $row->asesmenPenunjang->{'diagnosakerja_' . $i}))
                        ->reject(fn ($d) => $d === '' || in_array(strtolower($d), ['-', '0', '1'], true))->values()
                    : collect();
                $html = $dx->isNotEmpty()
                    ? '<div class="rk-dx" title="' . e($dx->implode(' · ')) . '">' . e($dx->first()) . ($dx->count() > 1 ? ' <span class="rk-sub">+' . ($dx->count() - 1) . '</span>' : '') . '</div>'
                    : '';
                $chips = collect([
                    ['Tindakan', $row->riwayat_tindakan_count, 'fa-syringe'],
                    ['Resep', $row->resep_dokter_count, 'fa-prescription'],
                    ['Lab', $row->lab_permintaan_count, 'fa-flask'],
                    ['Radiologi', $row->radiologi_count, 'fa-x-ray'],
                ])->filter(fn ($c) => (int) $c[1] > 0)
                    ->map(fn ($c) => '<span class="rk-chip-sm"><i class="fas ' . $c[2] . '"></i>' . $c[0] . ' ' . (int) $c[1] . '</span>')->implode('');

                return ($html . ($chips ? '<div class="rk-chips-sm">' . $chips . '</div>' : '')) ?: '<span class="text-muted">-</span>';
            })
            ->addColumn('tagihan', function ($row) use ($rupiah, $badge) {
                // Line 1: amount + status; line 2: what is still due (only when partly paid) and the metode bayar
                $metode = $row->metodeBayar->nama ?? null;
                $invoice = $row->invoice;
                $total = (float) ($invoice->total_amount ?? 0);
                if (!$invoice) {
                    $top = '<span class="rk-amount is-muted">-</span>' . $badge('Belum ditagih', '#94a3b8');
                    $sub = [];
                } elseif ($total <= 0) {
                    $top = '<span class="rk-amount">' . $rupiah(0) . '</span>' . $badge('Tanpa biaya', '#94a3b8');
                    $sub = [];
                } else {
                    [$label, $color] = self::INVOICE_LABEL[$invoice->status] ?? [ucfirst((string) $invoice->status), '#64748b'];
                    $sisa = $total - (float) $invoice->amount_paid;
                    $top = '<span class="rk-amount">' . $rupiah($total) . '</span>' . $badge($label, $color);
                    $sub = $invoice->status === 'partial' && $sisa > 0 ? ['<span class="is-due">Sisa ' . $rupiah($sisa) . '</span>'] : [];
                }
                if ($metode) {
                    $sub[] = e($metode);
                }

                return '<div class="rk-bill">' . $top . '</div>' . ($sub ? '<div class="rk-sub">' . implode(' &middot; ', $sub) . '</div>' : '');
            })
            ->addColumn('aksi', function ($row) {
                $jenis = (int) $row->jenis_kunjungan;
                $btn = fn ($url, $label, $icon, $class) => '<a href="' . e($url) . '" class="btn btn-sm ' . $class . '" target="_blank"><i class="fas ' . $icon . ' mr-1"></i>' . e($label) . '</a>';

                // Beli Produk / Reseller: only the resep; Laboratorium: only the lab page
                if ($jenis === Visitation::TYPE_PRODUK || $jenis === Visitation::TYPE_RESELLER) {
                    return '<div class="rk-actions">' . $btn(route('erm.eresep.create', $row->id), 'Resep', 'fa-prescription', 'btn-outline-success') . '</div>';
                }
                if ($jenis === Visitation::TYPE_LAB) {
                    return '<div class="rk-actions">' . $btn(route('erm.elab.create', $row->id), 'Lab', 'fa-flask', 'btn-outline-warning') . '</div>';
                }

                // Resume: PDF preview in the page's modal (erm-pdf-preview.js)
                $buttons = '<a href="' . e(route('resume.medis', $row->id)) . '" class="btn btn-sm btn-outline-primary btn-pdf-preview" data-title="Resume Medis '
                    . e(\Carbon\Carbon::parse($row->tanggal_visitation)->translatedFormat('d M Y')) . '"><i class="fas fa-file-medical mr-1"></i>Resume</a>';
                // Dokumen: the asesmen or CPPT the doctor filled (none: the Resume above is all there is)
                $dokumen = match (strtolower((string) $row->status_dokumen)) {
                    'cppt' => [route('erm.cppt.create', $row->id), 'CPPT'],
                    'asesmen' => [route('erm.asesmendokter.create', $row->id), 'Asesmen'],
                    default => null,
                };
                if ($dokumen) {
                    $buttons .= $btn($dokumen[0], $dokumen[1], 'fa-notes-medical', 'btn-outline-secondary');
                }
                $buttons .= '<button type="button" class="btn btn-sm btn-outline-success diagnosis-btn" data-id="' . e($row->id) . '"><i class="fas fa-file-signature mr-1"></i>Surat Diagnosis</button>';
                // Slimming / Vaksin / MCU: also their own page
                $servicePage = Visitation::servicePage($jenis, (string) $row->id);
                if ($servicePage) {
                    $buttons .= $btn($servicePage['url'], $servicePage['label'], 'fa-external-link-alt', 'btn-outline-info');
                }

                return '<div class="rk-actions">' . $buttons . '</div>';
            })
            ->rawColumns(['kunjungan', 'dokter', 'ringkasan', 'tagihan', 'aksi'])
            ->make(true);
    }

    // ---- Patient PDFs: resume medis + surat diagnosis (ID / EN) ----

    /** The visit with what the PDFs need; the patient comes from the relation (no full getDataPasien). */
    private function loadVisitForPdf($visitationId): Visitation
    {
        return Visitation::with([
            'pasien', 'klinik', 'dokter.user', 'dokter.spesialisasi', 'asesmenPenunjang',
            'asesmenDalam', 'asesmenTht', 'asesmenAnak', 'asesmenEstetika', 'asesmenSaraf',
            'asesmenGigi', 'asesmenUmum', 'asesmenPerawat', 'resepDokter.obat',
        ])->findOrFail($visitationId);
    }

    /** Diagnosa kerja 1-5, renumbered and without filler values ('-', '0', '1'). */
    private function diagnosisList(Visitation $visitation): array
    {
        $penunjang = $visitation->asesmenPenunjang;
        if (!$penunjang) {
            return [];
        }

        return collect(range(1, 5))
            ->map(fn ($i) => trim((string) $penunjang->{'diagnosakerja_' . $i}))
            ->reject(fn ($d) => $d === '' || in_array(strtolower($d), ['-', '0', '1'], true))
            ->values()
            ->all();
    }

    /** Doctor's QR signature ('' when there is none), as a small JPEG: DomPDF needs ~0.4 s for the 500px PNG. */
    private function ttdImage(Visitation $visitation): string
    {
        $file = basename((string) ($visitation->dokter->ttd ?? ''));

        return $file !== '' ? (\App\Support\PdfLetterhead::logo('img/qr/' . $file, 300) ?? '') : '';
    }

    /** Fields shared by every patient PDF. */
    private function pdfCommon(Visitation $visitation): array
    {
        $pasien = $visitation->pasien;
        abort_if(!$pasien, 404, 'Data pasien kunjungan ini tidak ditemukan.');

        return [
            'kop' => \App\Support\PdfLetterhead::forVisitation($visitation),
            'visitation_id' => $visitation->id,
            'tanggal_visit' => $visitation->tanggal_visitation,
            'nama_dokter' => $visitation->dokter->user->name ?? 'Dokter',
            'spesialisasi' => $visitation->resolvedSpesialisasi()?->nama ?? '-',
            'pasien' => $pasien,
            // erm_pasiens.gender holds 'Laki-laki' / 'Perempuan' (or nothing)
            'gender' => in_array($pasien->gender, ['Laki-laki', 'Perempuan'], true) ? $pasien->gender : null,
            'umur' => $pasien->tanggal_lahir ? \Carbon\Carbon::parse($pasien->tanggal_lahir)->age : null,
            'ttd_image_path' => $this->ttdImage($visitation),
        ];
    }

    /** "<what>_<No RM>_<tanggal>.pdf", so a download keeps a meaningful name. */
    private function pdfFileName(string $what, Visitation $visitation): string
    {
        return $what . '_' . preg_replace('/[^A-Za-z0-9-]/', '', (string) $visitation->pasien_id) . '_'
            . \Carbon\Carbon::parse($visitation->tanggal_visitation)->format('Ymd') . '.pdf';
    }

    private function streamPdf(string $view, array $data, string $fileName, string $errorMessage)
    {
        try {
            return Pdf::loadView($view, $data)->stream($fileName);
        } catch (\Exception $e) {
            Log::error($errorMessage . ': ' . $e->getMessage());
            return response()->view('errors.custom', ['message' => $errorMessage . '. Silakan coba lagi atau hubungi admin.', 'exception' => $e], 500);
        }
    }

    public function resumeMedis($visitationId)
    {
        $visitation = $this->loadVisitForPdf($visitationId);

        // The doctor's asesmen of this visit; the perawat's for what the doctor left empty
        $asesmen = collect(['asesmenDalam', 'asesmenTht', 'asesmenAnak', 'asesmenEstetika', 'asesmenSaraf', 'asesmenGigi', 'asesmenUmum'])
            ->map(fn ($relation) => $visitation->$relation)->filter()->first();
        $perawat = $visitation->asesmenPerawat;
        $pick = function (array $sources) {
            foreach ($sources as [$model, $field]) {
                $value = $model ? trim((string) $model->$field) : '';
                if ($value !== '' && $value !== '-') {
                    return $value;
                }
            }
            return '-';
        };

        // Penunjang: lab / radiologi requested in this visit
        $lab = $visitation->labPermintaan()->with('labTest:id,nama')->get()->pluck('labTest.nama')->filter()->unique();
        $radiologi = \App\Models\ERM\RadiologiPermintaan::with('radiologiTest:id,nama')->where('visitation_id', $visitation->id)
            ->get()->pluck('radiologiTest.nama')->filter()->unique();
        $penunjang = collect([
            $lab->isNotEmpty() ? 'Lab: ' . $lab->implode(', ') : null,
            $radiologi->isNotEmpty() ? 'Radiologi: ' . $radiologi->implode(', ') : null,
        ])->filter()->implode('; ');

        $diagnosis = $this->diagnosisList($visitation);
        if (!$diagnosis && $asesmen) {
            $fallback = $pick([[$asesmen, 'diagnosa'], [$asesmen, 'diagnosis'], [$asesmen, 'diagnosa_kerja'], [$asesmen, 'assessment']]);
            $diagnosis = $fallback !== '-' ? [$fallback] : [];
        }

        $obat = $visitation->resepDokter->map(fn ($resep) => $resep->obat->nama ?? null)->filter()->unique()->values();
        $keadaanUmum = $pick([[$asesmen, 'keadaan_umum']]);

        $data = array_merge($this->pdfCommon($visitation), [
            'keluhan_utama' => $pick([[$asesmen, 'keluhan_utama'], [$perawat, 'keluhan_utama']]),
            'keadaan_umum' => $keadaanUmum === '-' ? 'Baik' : $keadaanUmum,
            'td' => $pick([[$asesmen, 'td'], [$perawat, 'td']]),
            'n' => $pick([[$asesmen, 'n'], [$perawat, 'nadi']]),
            's' => $pick([[$asesmen, 's'], [$perawat, 'suhu']]),
            // asesmen perawat: respirasi is 'rr' ('r' there is the pain location of PQRST)
            'r' => $pick([[$asesmen, 'r'], [$perawat, 'rr']]),
            'penunjang' => $penunjang !== '' ? $penunjang : '-',
            'nama_obat' => $obat->isNotEmpty() ? $obat->implode(', ') : '-',
            'diagnosis' => $diagnosis ? implode(', ', $diagnosis) : '-',
            'tindak_lanjut' => $pick([[$visitation->asesmenPenunjang, 'standing_order']]),
        ]);

        return $this->streamPdf('erm.riwayatkunjungan.resume-medis', $data, $this->pdfFileName('Resume_Medis', $visitation), 'Gagal membuat resume medis');
    }

    public function getDataDiagnosis($visitationId)
    {
        $visitation = Visitation::with(['pasien', 'asesmenPenunjang'])->findOrFail($visitationId);
        $pasien = $visitation->pasien;

        return response()->json([
            'pasien' => $pasien ? [
                'id' => $pasien->id,
                'nama' => $pasien->nama,
                'tanggal_lahir' => $pasien->tanggal_lahir,
                'gender' => in_array($pasien->gender, ['Laki-laki', 'Perempuan'], true) ? $pasien->gender : null,
                'alamat' => $pasien->alamat,
            ] : null,
            'diagnoses' => $this->diagnosisList($visitation),
            'suratDiagnosa' => SuratDiagnosa::where('visitation_id', $visitationId)->first(['keterangan']),
        ]);
    }

    public function storeSuratDiagnosis(Request $request)
    {
        $request->validate([
            'visitation_id' => 'required|exists:erm_visitations,id',
            // erm_suratdiagnosa.keterangan is varchar(191)
            'keterangan' => 'nullable|string|max:191'
        ], [
            'keterangan.max' => 'Keterangan maksimal 191 karakter.',
        ]);

        // One surat diagnosa per visit: saving again updates its keterangan
        $surat = SuratDiagnosa::updateOrCreate(
            ['visitation_id' => $request->visitation_id],
            ['keterangan' => $request->keterangan]
        );

        return response()->json([
            'success' => true,
            'created' => $surat->wasRecentlyCreated,
            'message' => $surat->wasRecentlyCreated ? 'Surat diagnosa dibuat.' : 'Surat diagnosa kunjungan ini diperbarui.',
        ]);
    }

    /** Data of the surat diagnosis (ID and EN share it). */
    private function suratDiagnosisData($visitationId): array
    {
        $visitation = $this->loadVisitForPdf($visitationId);
        $keterangan = trim((string) SuratDiagnosa::where('visitation_id', $visitationId)->value('keterangan'));

        return [$visitation, array_merge($this->pdfCommon($visitation), [
            'diagnosis_list' => $this->diagnosisList($visitation),
            'keterangan' => $keterangan !== '' ? $keterangan : '-',
        ])];
    }

    public function printSuratDiagnosis($visitationId)
    {
        [$visitation, $data] = $this->suratDiagnosisData($visitationId);

        return $this->streamPdf('erm.riwayatkunjungan.surat-diagnosis', $data, $this->pdfFileName('Surat_Diagnosis', $visitation), 'Gagal membuat surat diagnosis');
    }

    public function printSuratDiagnosisEn($visitationId)
    {
        [$visitation, $data] = $this->suratDiagnosisData($visitationId);

        return $this->streamPdf('erm.riwayatkunjungan.surat-diagnosis-en', $data, $this->pdfFileName('Diagnosis_Certificate', $visitation), 'Gagal membuat surat diagnosis (EN)');
    }
}
