<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
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
        $currentPage = $request->get('page', 'index'); // Get current page parameter
        $currentTime = time();
        
        // Check for new notifications in cache based on page
        $notificationKey = 'farmasi_notification_' . $currentPage . '_' . Auth::id();
        $notification = Cache::get($notificationKey);
        
        if ($notification && $notification['timestamp'] > $lastCheck) {
            // Clear the notification after reading
            Cache::forget($notificationKey);
            
            return response()->json([
                'hasNew' => true,
                'message' => $notification['message'],
                'type' => $notification['type'],
                'timestamp' => $currentTime,
                'source' => 'cache'
            ]);
        }

        // FALLBACK: if no cache-notification found, check database unread notifications
        try {
            $dbNotif = Auth::user()->unreadNotifications()->latest()->first();
            if ($dbNotif) {
                // compare created_at timestamp with lastCheck
                $notifTs = strtotime($dbNotif->created_at);
                if ($notifTs > $lastCheck) {
                    // mark as read so it's not returned again
                    $dbNotif->markAsRead();
                    Log::info('ERM NotificationController: returning DB notification for user ' . Auth::id() . ' notif_id: ' . $dbNotif->id);
                    return response()->json([
                        'hasNew' => true,
                        'message' => $dbNotif->data['message'] ?? '',
                        'type' => $dbNotif->data['title'] ?? 'notification',
                        'timestamp' => $currentTime,
                        'source' => 'database'
                    ]);
                }
            }
        } catch (\Exception $e) {
            // if DB or notifications fail, just continue and return no new
            Log::error('Error checking DB notifications for Farmasi: ' . $e->getMessage());
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
            // Notification for index page (existing functionality)
            $indexNotificationKey = 'farmasi_notification_index_' . $user->id;
            Cache::put($indexNotificationKey, [
                'message' => "Resep Pasien {$pasienName} (ID: {$pasienId}) sudah bisa diproses.",
                'type' => 'pasien_keluar',
                'timestamp' => time()
            ], 300); // Cache for 5 minutes
            
            // Notification for farmasi create page (new functionality)
            $createNotificationKey = 'farmasi_notification_create_' . $user->id;
            Cache::put($createNotificationKey, [
                'message' => "Dokter mungkin merubah/menambah resep pasien",
                'type' => 'resep_updated',
                'timestamp' => time()
            ], 300); // Cache for 5 minutes
        }
        
        return response()->json(['success' => true]);
    }

    /**
     * Return a list of recent notifications (read + unread) for the authenticated user.
     * This is used by the Old Notifications modal in Farmasi UI.
     */
    public function oldNotifications(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([], 200);
            }

            $perPage = 50;

            // Use DB query to avoid depending on Notifiable trait presence
            $rows = \DB::table('notifications')
                ->where('notifiable_id', $user->id)
                ->where('notifiable_type', get_class($user))
                ->orderBy('created_at', 'desc')
                ->limit($perPage)
                ->get();

            $data = $rows->map(function ($r) {
                $data = [];
                try {
                    $data = json_decode($r->data, true) ?? [];
                } catch (\Exception $e) {
                    $data = [];
                }

                return [
                    'id' => $r->id,
                    'message' => $data['message'] ?? $data['title'] ?? ($data['text'] ?? ''),
                    'type' => $data['type'] ?? null,
                    'created_at' => $r->created_at,
                    'read' => $r->read_at ? true : false,
                    'raw' => $data,
                ];
            })->toArray();

            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('Error fetching old notifications: ' . $e->getMessage());
            return response()->json([], 200);
        }
    }

    /**
     * Mark a notification as read for the authenticated user.
     */
    public function markAsRead(Request $request, $id)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['success' => false], 401);
            }

            $affected = \DB::table('notifications')
                ->where('id', $id)
                ->where('notifiable_id', $user->id)
                ->where('notifiable_type', get_class($user))
                ->update(['read_at' => now()]);

            if ($affected) {
                return response()->json(['success' => true]);
            }

            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        } catch (\Exception $e) {
            Log::error('Error marking notification read: ' . $e->getMessage());
            return response()->json(['success' => false], 500);
        }
    }
}