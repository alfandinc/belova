<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Marketing\FollowUp;
use App\Models\ERM\Pasien;
use App\Models\HRD\Employee;
use Illuminate\Support\Facades\DB;
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
                ->select('marketing_follow_ups.*', 'erm_pasiens.nama as pasien_nama', 'hrd_employee.nama as sales_nama')
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
                ->addColumn('tanggal', fn ($row) => $row->created_at ? $row->created_at->format('d/m/Y H:i') : '-')
                ->editColumn('visit_date', fn ($row) => $row->visit_date ? \Carbon\Carbon::parse($row->visit_date)->format('d/m/Y') : null)
                ->make(true);
        }
        // The page itself is the "Follow Up" tab of Pasien & Follow Up
        return redirect()->route('marketing.pasien-data', ['tab' => 'followup']);
    }

    /** Follow ups created within start_date..end_date (whole days, today when not given). */
    private function filtered($query, Request $request)
    {
        $start = $request->input('start_date') ?: now()->toDateString();
        $end = $request->input('end_date') ?: $start;

        return $query->whereBetween('marketing_follow_ups.created_at', [
            \Carbon\Carbon::parse($start)->startOfDay(),
            \Carbon\Carbon::parse($end)->endOfDay(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pasien_id' => 'required|exists:erm_pasiens,id',
            'kategori' => 'required|array|min:1',
            'kategori.*' => 'string',
            'sales_id' => 'nullable|exists:hrd_employee,id',
            'status_respon' => 'required|in:' . implode(',', FollowUp::STATUS_RESPON),
            'bukti_respon' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:5120',
            'rencana_tindak_lanjut' => 'nullable|string',
            'status_booking' => 'nullable|string',
            'catatan' => 'nullable|string',
        ]);
        $validated['kategori'] = json_encode($validated['kategori']);
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
        $followup = FollowUp::with(['pasien', 'sales'])->findOrFail($id);
        return response()->json($followup);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'pasien_id' => 'required|exists:erm_pasiens,id',
            'kategori' => 'required|array|min:1',
            'kategori.*' => 'string',
            'sales_id' => 'nullable|exists:hrd_employee,id',
            'status_respon' => 'required|in:' . implode(',', FollowUp::STATUS_RESPON),
            'bukti_respon' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:5120',
            'rencana_tindak_lanjut' => 'nullable|string',
            'status_booking' => 'nullable|string',
            'catatan' => 'nullable|string',
        ]);
        $validated['kategori'] = json_encode($validated['kategori']);
        $followup = FollowUp::findOrFail($id);
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

    public function destroy($id)
    {
        $followup = FollowUp::findOrFail($id);
        $followup->delete();
        return response()->json(['success' => true, 'message' => 'Follow up berhasil dihapus']);
    }

    // AJAX search pasien for select2
    public function pasienSearch(Request $request)
    {
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
        $kategoriProduk = (clone $baseQuery)->whereJsonContains('kategori', 'Produk')->count();
        $kategoriPerawatan = (clone $baseQuery)->whereJsonContains('kategori', 'Perawatan')->count();
        $kategoriReseller = (clone $baseQuery)->whereJsonContains('kategori', 'Reseller')->count();
        $kategoriSlimming = (clone $baseQuery)->whereJsonContains('kategori', 'Slimming')->count();
        $kategoriUlangTahun = (clone $baseQuery)->whereJsonContains('kategori', FollowUp::KATEGORI_ULANG_TAHUN)->count();

        // Top 5 sales by count
        $topSales = (clone $baseQuery)
            ->selectRaw('sales_id, COUNT(*) as jumlah')
            ->whereNotNull('sales_id')
            ->groupBy('sales_id')
            ->orderByDesc('jumlah')
            ->limit(5)
            ->get();

        // Get sales names
        $salesList = [];
        foreach ($topSales as $row) {
            $sales = \App\Models\HRD\Employee::withInactive()->find($row->sales_id);
            $salesList[] = [
                'nama' => $sales ? $sales->nama : '-',
                'jumlah' => $row->jumlah
            ];
        }

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
            'kategori_produk' => $kategoriProduk,
            'kategori_perawatan' => $kategoriPerawatan,
            'kategori_reseller' => $kategoriReseller,
            'kategori_slimming' => $kategoriSlimming,
            'kategori_ulang_tahun' => $kategoriUlangTahun,
            'top_sales' => $salesList
        ]);
    }
}
