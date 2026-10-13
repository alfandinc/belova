<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use Carbon\Carbon;

class AuthController extends Controller
{
    public const DEFAULT_EMOTION = 'calm';

    public static function emotionCatalog(): array
    {
        return [
            'happy' => ['label' => 'Happy', 'emoji' => '😄', 'color' => '#22c55e'],
            'excited' => ['label' => 'Excited', 'emoji' => '🤩', 'color' => '#f59e0b'],
            'calm' => ['label' => 'Calm', 'emoji' => '😌', 'color' => '#3b82f6'],
            'sad' => ['label' => 'Sad', 'emoji' => '😢', 'color' => '#64748b'],
            'angry' => ['label' => 'Angry', 'emoji' => '😠', 'color' => '#ef4444'],
        ];
    }

    public static function getActiveUserEmotions(): array
    {
        $entries = Cache::get('active_user_emotions', []);
        $cutoff = now()->subMinutes(15);
        $filtered = [];

        foreach ($entries as $userId => $entry) {
            $updatedAt = isset($entry['updated_at']) ? Carbon::parse($entry['updated_at']) : null;
            if (!$updatedAt || $updatedAt->lt($cutoff)) {
                continue;
            }
            $filtered[$userId] = $entry;
        }

        if ($filtered !== $entries) {
            Cache::forever('active_user_emotions', $filtered);
        }

        return array_values($filtered);
    }

    public static function storeUserEmotion(User $user, string $emotion): array
    {
        $catalog = self::emotionCatalog();
        if (!isset($catalog[$emotion])) {
            $emotion = self::DEFAULT_EMOTION;
        }

        $entries = Cache::get('active_user_emotions', []);
        $entries[$user->id] = [
            'user_id' => $user->id,
            'name' => $user->name,
            'avatar_url' => self::resolveUserAvatarUrl($user),
            'initials' => self::buildUserInitials($user->name),
            'emotion' => $emotion,
            'label' => $catalog[$emotion]['label'],
            'emoji' => $catalog[$emotion]['emoji'],
            'color' => $catalog[$emotion]['color'],
            'updated_at' => now()->toIso8601String(),
        ];

        Cache::forever('active_user_emotions', $entries);

        return $entries[$user->id];
    }

    public static function removeUserEmotion(?User $user): void
    {
        if (!$user) {
            return;
        }

        $entries = Cache::get('active_user_emotions', []);
        unset($entries[$user->id]);
        Cache::forever('active_user_emotions', $entries);
    }

    protected static function resolveUserAvatarUrl(User $user): ?string
    {
        try {
            if ($user->relationLoaded('employee')) {
                $employee = $user->employee;
            } else {
                $employee = $user->employee()->first();
            }

            if ($employee && !empty($employee->photo)) {
                return Storage::url($employee->photo);
            }

            if ($user->relationLoaded('dokter')) {
                $dokter = $user->dokter;
            } else {
                $dokter = $user->dokter()->first();
            }

            if ($dokter && !empty($dokter->photo)) {
                return Storage::url($dokter->photo);
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    protected static function buildUserInitials(?string $name): string
    {
        $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
        $initials = collect($parts)
            ->filter()
            ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
            ->take(2)
            ->implode('');

        return $initials ?: 'U';
    }

    // Show different login forms based on the module

    // Set clinic choice in session
    public function setClinicChoice(Request $request)
    {
        $request->validate([
            'clinic_choice' => 'required|in:skin,premiere,dental',
        ]);
        session(['clinic_choice' => $request->clinic_choice]);
        return response()->json(['status' => 'ok']);
    }
    public function showERMLoginForm()
    {
        return view('auth.erm_login'); // ERM Login Page
    }

    public function showHRDLoginForm()
    {
        return view('auth.hrd_login'); // HRD Login Page
    }

    public function showInventoryLoginForm()
    {
        return view('auth.inventory_login'); // Inventory Login Page
    }

    public function showMarketingLoginForm()
    {
        return view('auth.marketing_login'); // Marketing Login Page
    }
    public function showFinanceLoginForm()
    {
        return view('auth.finance_login'); // Finance Login Page
    }
    public function showWorkdocLoginForm()
    {
        return view('auth.workdoc_login'); // Workdoc Login Page
    }
    public function showAkreditasiLoginForm()
    {
        return view('auth.akreditasi_login'); // Akreditasi Login Page
    }

    // Handle login request

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'emotion' => 'nullable|in:' . implode(',', array_keys(self::emotionCatalog())),
            'clinic_choice' => 'required|in:skin,premiere,dental',
        ], [
            'clinic_choice.required' => 'Pilih klinik terlebih dahulu.',
        ]);

        // Find user with given email
        $user = User::where('email', $request->email)->first();

        // Check if user exists
        if (!$user) {
            return $this->loginFailed($request, 'Email atau password salah.');
        }

        // Attempt to login (nonaktif accounts are rejected)
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password, 'is_active' => true])) {
            // New session id after login (prevents session fixation)
            $request->session()->regenerate();

            $loggedInUser = Auth::user();
            // An empty hidden input arrives as null, so fall back explicitly (storeUserEmotion needs a string)
            $emotion = $request->input('emotion') ?: self::DEFAULT_EMOTION;
            if ($loggedInUser) {
                self::storeUserEmotion($loggedInUser, $emotion);
            }
            session([
                'user_emotion' => $emotion,
                'clinic_choice' => $request->clinic_choice,
            ]);

            // Back to the page the user was trying to open, otherwise the dashboard
            $redirect = redirect()->intended('/');

            // The login page submits via fetch: hand it the target URL instead of a redirect
            return $request->expectsJson()
                ? response()->json(['redirect' => $redirect->getTargetUrl()])
                : $redirect;
        }

        if (!$user->is_active && Hash::check($request->password, $user->password)) {
            return $this->loginFailed($request, 'Akun Anda dinonaktifkan. Hubungi Admin.');
        }

        return $this->loginFailed($request, 'Email atau password salah.');
    }

    protected function loginFailed(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 422);
        }

        return back()->withErrors(['email' => $message])->withInput();
    }

    // Handle logout
    public function logout(Request $request)
    {
        self::removeUserEmotion(Auth::user());
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function heartbeatEmotion(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            $emotion = session('user_emotion', self::DEFAULT_EMOTION);
            self::storeUserEmotion($user, $emotion);
        }

        return response()->json([
            'data' => self::getActiveUserEmotions(),
        ]);
    }

    public function updateEmotion(Request $request)
    {
        $request->validate([
            'emotion' => 'required|in:' . implode(',', array_keys(self::emotionCatalog())),
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        $emotion = $request->input('emotion', self::DEFAULT_EMOTION);
        session(['user_emotion' => $emotion]);
        $current = self::storeUserEmotion($user, $emotion);

        return response()->json([
            'message' => 'Mood updated',
            'current' => $current,
            'data' => self::getActiveUserEmotions(),
        ]);
    }
}
