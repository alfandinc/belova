<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Models\ERM\Pasien;
use App\Models\ERM\Visitation;
use App\Models\ERM\Klinik;
use App\Models\ERM\BirthdayGreeting;
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
        $today = Carbon::now()->format('Y-m-d');
        
        // Get list of clinics for the filter
        $kliniks = Klinik::all();
        
        return view('erm.birthday.index', compact('today', 'kliniks'));
    }

    public function getData(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now();
        $klinikId = $request->klinik_id;
        $currentYear = Carbon::now()->year;

        // Base query for patients with birthdays in the date range
        $query = Pasien::whereRaw("(MONTH(tanggal_lahir) * 100 + DAY(tanggal_lahir)) BETWEEN (? * 100 + ?) AND (? * 100 + ?)",
            [
                $startDate->month,
                $startDate->day,
                $endDate->month,
                $endDate->day
            ]
        );

        // Filter by klinik if specified
        if ($klinikId) {
            $query->whereExists(function($query) use ($klinikId) {
                $query->select(DB::raw(1))
                    ->from('erm_visitations')
                    ->whereRaw('erm_visitations.pasien_id = erm_pasiens.id')
                    ->where('erm_visitations.klinik_id', $klinikId);
            });
        }

        // Order by month and day
        $query->orderByRaw("MONTH(tanggal_lahir), DAY(tanggal_lahir)");

        $pasiens = $query->get();

        return DataTables::of($pasiens)
            ->addColumn('usia', function($pasien) {
                return Carbon::parse($pasien->tanggal_lahir)->age;
            })
            ->addColumn('tanggal', function($pasien) {
                $date = Carbon::parse($pasien->tanggal_lahir);
                $monthNames = [
                    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
                    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
                ];
                return $date->format('j') . ' ' . $monthNames[$date->month - 1] . ' ' . $date->format('Y');
            })
            ->addColumn('klinik', function($pasien) {
                $latestVisitation = Visitation::where('pasien_id', $pasien->id)
                    ->orderBy('tanggal_visitation', 'desc')
                    ->first();
                    
                return $latestVisitation ? $latestVisitation->klinik->nama ?? '-' : '-';
            })
            ->addColumn('status', function($pasien) {
                $currentYear = Carbon::now()->year;
                $greeting = BirthdayGreeting::where('pasien_id', $pasien->id)
                    ->where('greeting_year', $currentYear)
                    ->first();
                    
                if ($greeting) {
                    $user = $greeting->user ? $greeting->user->name : 'Unknown';
                    $date = $greeting->greeting_date->format('d/m/Y');
                    return '<span class="badge badge-success">Sudah diucapkan</span><br>
                            <small class="text-muted">oleh: ' . $user . '<br>' . $date . '</small>';
                } else {
                    return '<span class="badge badge-warning">Belum diucapkan</span>';
                }
            })
            ->addColumn('action', function($pasien) {
    $age = Carbon::parse($pasien->tanggal_lahir)->age;
    $gender = strtolower($pasien->gender);
    $currentYear = Carbon::now()->year;
    $klinikId = null;
    
    // Get klinik_id from latest visitation
    $latestVisitation = Visitation::where('pasien_id', $pasien->id)
        ->orderBy('tanggal_visitation', 'desc')
        ->first();
    
    if ($latestVisitation && $latestVisitation->klinik) {
        $klinikId = $latestVisitation->klinik->id;
    }
    
    // Determine prefix based on age and gender
    $prefix = $this->getGreetingPrefix($age, $gender);
    
    // Check if greeting has been sent this year
    $greeting = BirthdayGreeting::where('pasien_id', $pasien->id)
        ->where('greeting_year', $currentYear)
        ->first();
    
    if ($greeting) {
        // If already greeted, show a disabled button or a different action
        return '<button type="button" class="btn btn-sm btn-secondary" disabled>Sudah Diucapkan</button>';
    } else {
        return '<button type="button" class="btn btn-sm btn-success send-greeting" 
            data-id="'.$pasien->id.'" 
            data-name="'.$pasien->nama.'" 
            data-phone="'.$pasien->no_hp.'"
            data-prefix="'.$prefix.'"
            data-age="'.$age.'"
            data-klinik="'.$klinikId.'">
            Ucapkan
        </button>';
    }
})
            ->rawColumns(['status', 'action'])
            ->make(true);
    }
    
    /**
     * Get the appropriate greeting prefix based on age and gender
     */
    private function getGreetingPrefix($age, $gender)
    {
        if ($age >= 40) {
            return $gender == 'l' ? 'Bapak' : 'Ibu';
        } elseif ($age >= 17) {
            return $gender == 'l' ? 'Kakak' : 'Kakak';
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
        BirthdayGreeting::updateOrCreate(
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