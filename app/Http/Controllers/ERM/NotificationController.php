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

        if ($request->boolean('queue')) {
            return response()->json($this->queuedFarmasiNotifications($request, $currentPage, $currentTime));
        }

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
            // Notifications with their own UI (jadwal card, journal reminder) must not be consumed here
            $dbNotif = Auth::user()->unreadNotifications()
                ->whereNotIn('type', \App\Support\NotificationTypes::OWN_UI)
                ->latest()
                ->first();
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
                        'kind' => $dbNotif->data['kind'] ?? null,
                        'multiline' => !empty($dbNotif->data['multiline']),
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
    
    /**
     * Queue mode (partials.farmasi-notif with erm.partials.notif-queue): the page's cache notification (a one-shot
     * "pasien keluar" signal, consumed here) plus every unread database notification of this login (first queue
     * poll of the session), max 15 minutes old, oldest first. Database ones stay unread until the user clicks OK on the popup, so one
     * arriving while another popup is open is shown afterwards instead of being lost. cache_only=1 skips them
     * (the farmasi create page, whose own popup is only about recipe changes).
     */
    private function queuedFarmasiNotifications(Request $request, string $currentPage, int $currentTime): array
    {
        $items = [];

        $notificationKey = 'farmasi_notification_' . $currentPage . '_' . Auth::id();
        if ($notification = Cache::pull($notificationKey)) {
            $items[] = [
                'id' => 'cache-' . $currentPage . '-' . ($notification['timestamp'] ?? $currentTime),
                'source' => 'cache',
                'type' => $notification['type'] ?? null,
                'message' => $notification['message'] ?? '',
            ];
        }

        if (!$request->boolean('cache_only')) {
            // Not the page's own time: a notification still queued when the user moves to another page must come back
            $sessionSince = (int) session('farmasi_notification_since', 0);
            if ($sessionSince <= 0) {
                $sessionSince = $currentTime;
                session(['farmasi_notification_since' => $sessionSince]);
            }
            $sinceTs = max($sessionSince, $currentTime - 15 * 60);
            try {
                Auth::user()->unreadNotifications()
                    ->whereNotIn('type', \App\Support\NotificationTypes::OWN_UI)
                    ->where('created_at', '>=', \Carbon\Carbon::createFromTimestamp($sinceTs, config('app.timezone')))
                    ->reorder('created_at') // the notifications relation orders newest first
                    ->limit(20)
                    ->get()
                    ->each(function ($n) use (&$items) {
                        $items[] = [
                            'id' => $n->id,
                            'source' => 'database',
                            'type' => $n->data['title'] ?? 'notification',
                            'message' => $n->data['message'] ?? '',
                            'sender' => $n->data['sender'] ?? null,
                            'kind' => $n->data['kind'] ?? null,
                            'multiline' => !empty($n->data['multiline']),
                        ];
                    });
            } catch (\Exception $e) {
                Log::error('Error checking DB notifications for Farmasi: ' . $e->getMessage());
            }
        }

        return ['hasNew' => !empty($items), 'items' => $items, 'timestamp' => $currentTime];
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
            $rows = DB::table('notifications')
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
     * Return count of unread notifications for the authenticated user.
     * Used for Farmasi "Old Notifications" button badge.
     */
    public function oldNotificationsUnreadCount(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['count' => 0], 200);
            }

            $count = DB::table('notifications')
                ->where('notifiable_id', $user->id)
                ->where('notifiable_type', get_class($user))
                ->whereNull('read_at')
                ->count();

            return response()->json(['count' => (int) $count], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching old notifications unread count: ' . $e->getMessage());
            return response()->json(['count' => 0], 200);
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

            $affected = DB::table('notifications')
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

    /**
     * Mark all notifications as read for the authenticated user.
     */
    public function markAllAsRead(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['success' => false], 401);
            }

            $affected = DB::table('notifications')
                ->where('notifiable_id', $user->id)
                ->where('notifiable_type', get_class($user))
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            return response()->json(['success' => true, 'affected' => (int) $affected]);
        } catch (\Exception $e) {
            Log::error('Error marking all notifications read: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan.'], 500);
        }
    }
}