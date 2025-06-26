<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class NotificationController extends Controller
{
    public function checkNewNotifications(Request $request)
    {
        // Check if user has farmasi role
        if (!Auth::user()->hasRole('Farmasi')) {
            return response()->json(['hasNew' => false]);
        }

        $lastCheck = $request->get('lastCheck', 0);
        $currentTime = time();
        
        // Check for new notifications in cache
        $notificationKey = 'farmasi_notification_' . Auth::id();
        $notification = Cache::get($notificationKey);
        
        if ($notification && $notification['timestamp'] > $lastCheck) {
            // Clear the notification after reading
            Cache::forget($notificationKey);
            
            return response()->json([
                'hasNew' => true,
                'message' => $notification['message'],
                'type' => $notification['type'],
                'timestamp' => $currentTime
            ]);
        }
        
        return response()->json([
            'hasNew' => false,
            'timestamp' => $currentTime
        ]);
    }
    
    public function notifyPasienKeluar(Request $request)
    {
        $pasienName = $request->input('pasien_name');
        $pasienId = $request->input('pasien_id');
        
        // Get all users with farmasi role
        $farmasiUsers = User::role('farmasi')->get();
        
        foreach ($farmasiUsers as $user) {
            $notificationKey = 'farmasi_notification_' . $user->id;
            
            Cache::put($notificationKey, [
                'message' => "Resep Pasien {$pasienName} (ID: {$pasienId}) sudah bisa diproses.",
                'type' => 'pasien_keluar',
                'timestamp' => time()
            ], 300); // Cache for 5 minutes
        }
        
        return response()->json(['success' => true]);
    }
}