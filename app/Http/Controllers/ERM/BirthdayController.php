<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Models\ERM\Pasien;
use App\Models\ERM\Visitation;
use App\Models\ERM\BirthdayGreeting;
use App\Models\Marketing\FollowUp;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

use App\Services\BirthdayImageService;
use Illuminate\Support\Facades\Storage;

class BirthdayController extends Controller
{
    public function index()
    {
        // The page itself is the "Ulang Tahun" tab of Pasien & Follow Up
        return redirect()->route('marketing.pasien-data', ['tab' => 'ulang-tahun']);
    }

    public function getData(Request $request)
    {
        $today = Carbon::today();
        $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : $today->copy();
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->startOfDay() : $today->copy();
        if ($endDate->lt($startDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }
        $currentYear = $today->year;

        // Birthdays (month*100 + day) inside the range; a range crossing new year (e.g. 28 Des - 5 Jan) wraps around
        $from = $startDate->month * 100 + $startDate->day;
        $to = $endDate->month * 100 + $endDate->day;
        $monthDay = '(MONTH(tanggal_lahir) * 100 + DAY(tanggal_lahir))';
        $query = Pasien::whereNotNull('tanggal_lahir');
        if ($startDate->diffInDays($endDate) >= 364) {
            // whole year: every birthday
        } elseif ($from <= $to) {
            $query->whereRaw("$monthDay BETWEEN ? AND ?", [$from, $to]);
        } else {
            $query->whereRaw("($monthDay >= ? OR $monthDay <= ?)", [$from, $to]);
        }

        // Patients who ever visited the clinic
        if ($request->klinik_id) {
            $query->whereExists(function ($q) use ($request) {
                $q->select(DB::raw(1))
                    ->from('erm_visitations')
                    ->whereColumn('erm_visitations.pasien_id', 'erm_pasiens.id')
                    ->where('erm_visitations.klinik_id', $request->klinik_id);
            });
        }

        $lastVisitKlinik = Visitation::select('klinik_id')
            ->whereColumn('pasien_id', 'erm_pasiens.id')
            ->orderByDesc('tanggal_visitation')
            ->limit(1);
        $pasiens = $query->select('id', 'nama', 'no_hp', 'gender', 'tanggal_lahir')
            ->addSelect(['last_klinik_id' => $lastVisitKlinik])
            ->get();

        $greetings = BirthdayGreeting::with('user:id,name')
            ->whereIn('pasien_id', $pasiens->pluck('id'))
            ->where('greeting_year', $currentYear)
            ->get()
            ->keyBy('pasien_id');
        $klinikNames = \App\Models\ERM\Klinik::pluck('nama', 'id');
        $monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        $rows = $pasiens->map(function ($pasien) use ($startDate, $today, $greetings, $klinikNames, $monthNames) {
            $born = Carbon::parse($pasien->tanggal_lahir);
            // The birthday as it falls inside the range (29 Feb moves to 28 Feb in other years)
            $occurs = $born->copy()->year($startDate->year);
            if ($born->month === 2 && $born->day === 29 && !$occurs->isLeapYear()) {
                $occurs = Carbon::create($startDate->year, 2, 28);
            }
            if ($occurs->lt($startDate)) {
                $occurs->addYear();
            }
            $daysAway = (int) round($today->diffInDays($occurs, false));
            $greeting = $greetings->get($pasien->id);
            $turning = $occurs->year - $born->year;

            return [
                'id' => $pasien->id,
                'nama' => $pasien->nama,
                'no_hp' => $pasien->no_hp,
                'tanggal' => $born->day . ' ' . $monthNames[$born->month - 1] . ' ' . $born->year,
                'occurs' => $occurs->toDateString(),
                'kapan' => $daysAway === 0 ? 'Hari ini' : ($daysAway === 1 ? 'Besok' : ($daysAway === -1 ? 'Kemarin'
                    : $occurs->day . ' ' . $monthNames[$occurs->month - 1])),
                'is_today' => $daysAway === 0,
                'usia' => $turning,
                'prefix' => $this->getGreetingPrefix($turning, $pasien->gender),
                'klinik_id' => $pasien->last_klinik_id,
                'klinik' => $klinikNames[$pasien->last_klinik_id] ?? '-',
                'greeted' => (bool) $greeting,
                'greeted_by' => $greeting ? ($greeting->user->name ?? '-') : null,
                'greeted_at' => $greeting && $greeting->greeting_date ? $greeting->greeting_date->format('d/m/Y') : null,
            ];
        })->sortBy('occurs')->values();

        if ($request->status === 'belum') {
            $rows = $rows->where('greeted', false)->values();
        } elseif ($request->status === 'sudah') {
            $rows = $rows->where('greeted', true)->values();
        }

        return DataTables::of($rows)->make(true);
    }
    
    /**
     * Get the appropriate greeting prefix based on age and gender
     */
    private function getGreetingPrefix($age, $gender)
    {
        // gender is stored as "Laki-laki" / "Perempuan" (older rows may hold "L" / "P")
        $male = str_starts_with(strtolower((string) $gender), 'l');
        if ($age >= 40) {
            return $male ? 'Bapak' : 'Ibu';
        } elseif ($age >= 17) {
            return 'Kakak';
        } else {
            return 'Adik';
        }
    }
    
    /**
     * Mark birthday greeting as sent
     */
    public function markAsSent(Request $request)
    {
        $request->validate([
            'pasien_id' => 'required',
            'message' => 'required',
        ]);
        
        $currentYear = Carbon::now()->year;
        
        // Create or update the greeting record
        $greeting = BirthdayGreeting::updateOrCreate(
            [
                'pasien_id' => $request->pasien_id,
                'greeting_year' => $currentYear,
            ],
            [
                'greeting_date' => Carbon::now(),
                'greeting_by' => Auth::id(),
                'greeting_message' => $request->message,
            ]
        );

        // The first greeting of the year also lands in the patient's follow up history
        if ($greeting->wasRecentlyCreated) {
            FollowUp::create([
                'pasien_id' => $request->pasien_id,
                'kategori' => json_encode([FollowUp::KATEGORI_ULANG_TAHUN]),
                'sales_id' => Auth::user()->employee->id ?? null,
                'status_respon' => 'Menunggu', // greeting sent, reply not in yet
                'catatan' => 'Ucapan ulang tahun dikirim via WhatsApp',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Greeting marked as sent'
        ]);
    }

    protected $birthdayImageService;

// Add this to your constructor or create one if it doesn't exist
public function __construct(BirthdayImageService $birthdayImageService)
{
    $this->birthdayImageService = $birthdayImageService;
}

/**
 * Generate birthday greeting image
 */
public function generateImage(Request $request)
{
    $request->validate([
        'name' => 'required',
        'age' => 'required|numeric',
        'prefix' => 'nullable|string',
        'klinik_id' => 'nullable|numeric',
    ]);
    
    $name = $request->name;
    $age = $request->age;
    $prefix = $request->prefix;
    $klinikId = $request->klinik_id;
    
    $imagePath = $this->birthdayImageService->generateBirthdayImage($name, $age, $prefix, $klinikId);
    
    return response()->json([
        'success' => true,
        'image_url' => Storage::url($imagePath),
        'image_path' => $imagePath
    ]);
}

/**
 * Display birthday greeting image
 */
public function showImage($filename)
{
    $path = 'birthday_cards/' . $filename;
    
    if (!Storage::disk('public')->exists($path)) {
        abort(404);
    }
    
    $file = Storage::disk('public')->get($path);
    $type = Storage::disk('public')->mimeType($path);

    return response($file, 200)->header('Content-Type', $type);
}
}