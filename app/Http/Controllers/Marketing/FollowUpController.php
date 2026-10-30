<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Marketing\FollowUp;
use App\Models\ERM\Pasien;
use App\Models\ERM\Klinik;
use App\Models\HRD\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\DataTables;

class FollowUpController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            // Names are joined in so the table can search and sort on them
            $data = $this->filtered(FollowUp::query(), $request)
                ->leftJoin('erm_pasiens', 'erm_pasiens.id', '=', 'marketing_follow_ups.pasien_id')
                ->leftJoin('hrd_employee', 'hrd_employee.id', '=', 'marketing_follow_ups.sales_id')
                ->leftJoin('erm_klinik', 'erm_klinik.id', '=', 'marketing_follow_ups.klinik_id')
                ->select('marketing_follow_ups.*', 'erm_pasiens.nama as pasien_nama', 'hrd_employee.nama as sales_nama', 'erm_klinik.nama as klinik_nama', 'erm_klinik.color as klinik_color', 'erm_klinik.logo as klinik_logo')
                // Visit booked from this follow up (Daftarkan Kunjungan with referral "Follow Up")
                ->addSelect(['visit_date' => \App\Models\ERM\Visitation::select('tanggal_visitation')
                    ->where('referral_type', \App\Models\ERM\Pasien::REFERRAL_TYPE_FOLLOW_UP)
                    ->whereColumn('referralable_id', DB::raw('CAST(marketing_follow_ups.id AS CHAR)'))
                    ->orderByDesc('tanggal_visitation')
                    ->limit(1)]);

            if ($request->filled('kategori')) {
                $data->where(function ($q) use ($request) {
                    foreach ((array) $request->input('kategori') as $kategori) {
                        $q->orWhereJsonContains('marketing_follow_ups.kategori', $kategori);
                    }
                });
            }
            if ($request->filled('status_respon')) {
                $data->where('marketing_follow_ups.status_respon', $request->input('status_respon'));
            }
            if ($request->filled('status_booking')) {
                $data->where('marketing_follow_ups.status_booking', $request->input('status_booking'));
            }

            // Newest first unless the user sorts by a column
            if (!$request->filled('order')) {
                $data->orderByDesc('marketing_follow_ups.created_at');
            }

            return DataTables::of($data)
                ->filterColumn('pasien_nama', fn ($q, $keyword) => $q->where('erm_pasiens.nama', 'like', "%{$keyword}%"))
                ->filterColumn('sales_nama', fn ($q, $keyword) => $q->where('hrd_employee.nama', 'like', "%{$keyword}%"))
                ->orderColumn('pasien_nama', 'erm_pasiens.nama $1')
                ->orderColumn('sales_nama', 'hrd_employee.nama $1')
                ->editColumn('kategori', fn ($row) => json_decode($row->kategori, true) ?: [])
                ->editColumn('klinik_nama', fn ($row) => $row->klinik_nama ? Klinik::shortName($row->klinik_nama) : null)
                ->editColumn('klinik_logo', fn ($row) => Klinik::logoUrl($row->klinik_logo))
                ->addColumn('tanggal', fn ($row) => $row->created_at ? $row->created_at->format('d/m/Y H:i') : '-')
                ->editColumn('visit_date', fn ($row) => $row->visit_date ? \Carbon\Carbon::parse($row->visit_date)->format('d/m/Y') : null)
                ->make(true);
        }
        // The page itself is the "Follow Up" tab of Pasien & Follow Up
        return redirect()->route('marketing.pasien-data', ['tab' => 'followup']);
    }

    /** Follow ups created within start_date..end_date (whole days, today when not given). */
    private function inDateRange($query, Request $request)
    {
        $start = $request->input('start_date') ?: now()->toDateString();
        $end = $request->input('end_date') ?: $start;

        return $query->whereBetween('marketing_follow_ups.created_at', [
            \Carbon\Carbon::parse($start)->startOfDay(),
            \Carbon\Carbon::parse($end)->endOfDay(),
        ]);
    }

    /** inDateRange plus the klinik filter: a klinik id, "none" (not set yet) or empty for every klinik. */
    private function filtered($query, Request $request)
    {
        $this->inDateRange($query, $request);
        $klinik = $request->input('klinik_id');
        if ($klinik === 'none') {
            $query->whereNull('marketing_follow_ups.klinik_id');
        } elseif ($klinik) {
            $query->where('marketing_follow_ups.klinik_id', $klinik);
        }
        return $query;
    }

    private function rules(): array
    {
        return [
            'pasien_id' => 'required|exists:erm_pasiens,id',
            'klinik_id' => 'required|exists:erm_klinik,id',
            'kategori' => 'required|array|min:1',
            'kategori.*' => 'string|in:' . implode(',', array_keys(FollowUp::KATEGORI)),
            'sales_id' => 'nullable|exists:hrd_employee,id',
            'status_respon' => 'required|in:' . implode(',', FollowUp::STATUS_RESPON),
            'bukti_respon' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:5120',
            'catatan' => 'nullable|string',
            'booking_batal' => 'nullable|boolean',
        ];
    }

    // Every kategori must be offered for the klinik (FollowUp::KATEGORI), except ones the follow up already had
    private function assertKategoriForKlinik(array $kategori, $klinikId, array $kept = []): void
    {
        $refused = array_filter($kategori, fn ($k) => !in_array($k, $kept, true) && !FollowUp::kategoriOfferedFor($k, $klinikId));
        if ($refused) {
            throw ValidationException::withMessages([
                'kategori' => 'Kategori ' . implode(', ', $refused) . ' tidak tersedia untuk klinik ini.',
            ]);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), [], ['klinik_id' => 'klinik']);
        $this->assertKategoriForKlinik($validated['kategori'], $validated['klinik_id']);
        $validated['kategori'] = json_encode($validated['kategori']);
        // A new follow up waits for its booking; the pasien's visit turns it Sukses (FollowUp::markBookedBy)
        unset($validated['booking_batal']);
        $validated['status_booking'] = 'Menunggu';
        if ($request->hasFile('bukti_respon')) {
            $file = $request->file('bukti_respon');
            $path = $file->store('marketing/followup', 'public');
            $validated['bukti_respon'] = '/storage/' . $path;
        }
        $followup = FollowUp::create($validated);
        return response()->json(['success' => true, 'message' => 'Follow up berhasil ditambahkan', 'id' => $followup->id]);
    }

    public function show($id)
    {
        $followup = FollowUp::with(['pasien', 'sales', 'klinik'])->findOrFail($id);
        return response()->json($followup);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate($this->rules(), [], ['klinik_id' => 'klinik']);
        $followup = FollowUp::findOrFail($id);
        $this->assertKategoriForKlinik($validated['kategori'], $validated['klinik_id'], (array) json_decode($followup->kategori ?? '[]', true));
        $validated['kategori'] = json_encode($validated['kategori']);

        // Booking by hand is only Batal (or undoing it); Sukses is left to the visit
        unset($validated['booking_batal']);
        if ($request->boolean('booking_batal')) {
            $validated['status_booking'] = 'Batal';
        } elseif ($followup->status_booking === 'Batal' || !$followup->status_booking) {
            $validated['status_booking'] = 'Menunggu';
        }
        // The form's one Catatan already holds the old Rencana text (merged when the form loaded)
        $validated['rencana_tindak_lanjut'] = null;

        if ($request->hasFile('bukti_respon')) {
            $file = $request->file('bukti_respon');
            $path = $file->store('marketing/followup', 'public');
            $validated['bukti_respon'] = '/storage/' . $path;
        } else {
            unset($validated['bukti_respon']);
        }
        $followup->update($validated);
        return response()->json(['success' => true, 'message' => 'Follow up berhasil diupdate', 'id' => $followup->id]);
    }

    /** Status respon only: the one-click change from the table's Respon badge. */
    public function updateRespon(Request $request, $id)
    {
        $validated = $request->validate(['status_respon' => 'required|in:' . implode(',', FollowUp::STATUS_RESPON)]);
        FollowUp::findOrFail($id)->update($validated);
        return response()->json(['success' => true, 'message' => 'Respon: ' . $validated['status_respon']]);
    }

    public function destroy($id)
    {
        $followup = FollowUp::findOrFail($id);
        $followup->delete();
        return response()->json(['success' => true, 'message' => 'Follow up berhasil dihapus']);
    }

    // AJAX search pasien for select2
    public function pasienSearch(Request $request)
    {
        // One pasien by id: its last visit's klinik, the default klinik of a new follow up
        if ($request->filled('id')) {
            return response()->json(['last_klinik_id' => FollowUp::lastKlinikId($request->input('id'))]);
        }

        // By name or No RM
        $search = $request->input('search');
        $query = Pasien::query();
        if ($search) {
            $query->where(fn ($q) => $q->where('nama', 'like', "%$search%")->orWhere('id', 'like', "$search%"));
        }
        $results = $query->orderBy('nama')->limit(20)->get(['id', 'nama', 'no_hp']);
        return response()->json($results);
    }
    
    // Get total follow up for today
    public function countToday(Request $request)
    {
        $query = $this->filtered(FollowUp::query(), $request);
        $count = $query->count();
        $baseQuery = $query;
        $countDirespon = (clone $baseQuery)->where('status_respon', 'Direspon')->count();
        $countTidakDirespon = (clone $baseQuery)->where('status_respon', 'Tidak Direspon')->count();
        $countMenungguRespon = (clone $baseQuery)->where('status_respon', 'Menunggu')->count();
        $percentDirespon = $count > 0 ? round($countDirespon / $count * 100, 1) : 0;
        $percentTidakDirespon = $count > 0 ? round($countTidakDirespon / $count * 100, 1) : 0;
        $percentMenungguRespon = $count > 0 ? round($countMenungguRespon / $count * 100, 1) : 0;
        
        $countBatal = (clone $baseQuery)->where('status_booking', 'Batal')->count();
        $countMenunggu = (clone $baseQuery)->where('status_booking', 'Menunggu')->count();
        $countSukses = (clone $baseQuery)->where('status_booking', 'Sukses')->count();
        
        // Kategori counts (JSON field)
        $perKategori = [];
        foreach (array_keys(FollowUp::KATEGORI) as $kategori) {
            $perKategori[$kategori] = (clone $baseQuery)->whereJsonContains('kategori', $kategori)->count();
        }

        // Per klinik ignores the klinik filter so every klinik stays visible; "none" = not set yet
        $klinikCounts = $this->inDateRange(FollowUp::query(), $request)
            ->selectRaw('klinik_id, COUNT(*) as jumlah')
            ->groupBy('klinik_id')
            ->get();
        $perKlinik = Klinik::orderBy('id')->get(['id', 'nama', 'color', 'logo'])
            ->map(fn ($k) => [
                'id' => $k->id,
                'nama' => Klinik::shortName($k->nama),
                'color' => $k->color,
                'logo' => Klinik::logoUrl($k->logo),
                'jumlah' => (int) optional($klinikCounts->firstWhere('klinik_id', $k->id))->jumlah,
            ])->all();
        $tanpaKlinik = (int) optional($klinikCounts->whereNull('klinik_id')->first())->jumlah;
        if ($tanpaKlinik) {
            $perKlinik[] = ['id' => 'none', 'nama' => 'Belum ditentukan', 'color' => null, 'jumlah' => $tanpaKlinik];
        }

        // Top 5 sales by bookings won, then by follow ups sent
        $topSales = (clone $baseQuery)
            ->selectRaw("sales_id, COUNT(*) as jumlah, SUM(status_booking = 'Sukses') as booking")
            ->whereNotNull('sales_id')
            ->groupBy('sales_id')
            ->orderByDesc('booking')
            ->orderByDesc('jumlah')
            ->limit(5)
            ->get();

        $salesNames = \App\Models\HRD\Employee::withInactive()->whereIn('id', $topSales->pluck('sales_id'))->pluck('nama', 'id');
        $salesList = $topSales->map(fn ($row) => [
            'nama' => $salesNames[$row->sales_id] ?? '-',
            'jumlah' => (int) $row->jumlah,
            'booking' => (int) $row->booking,
            'persen' => $row->jumlah > 0 ? round($row->booking / $row->jumlah * 100) : 0,
        ])->all();

        return response()->json([
            'count' => $count,
            'direspon' => $countDirespon,
            'tidak_direspon' => $countTidakDirespon,
            'percent_direspon' => $percentDirespon,
            'percent_tidak_direspon' => $percentTidakDirespon,
            'menunggu_respon' => $countMenungguRespon,
            'percent_menunggu_respon' => $percentMenungguRespon,
            'batal' => $countBatal,
            'menunggu' => $countMenunggu,
            'sukses' => $countSukses,
            'per_kategori' => $perKategori,
            'per_klinik' => $perKlinik,
            'top_sales' => $salesList
        ]);
    }
}
