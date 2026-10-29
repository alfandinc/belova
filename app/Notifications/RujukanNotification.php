<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Sent to Perawat, Pendaftaran and Farmasi when a dokter makes a rujukan (VisitationController@storeRujuk).
 * The message is multi-line (Pasien / Dari / Ke / Tanggal); see App\Support\ErmStaffNotifications.
 */
class RujukanNotification extends Notification
{
    public $title;
    public $message;
    public $sender;
    public $rujukId;
    public $visitationId;

    public function __construct($title, $message, $sender, $rujukId = null, $visitationId = null)
    {
        $this->title = $title;
        $this->message = $message;
        $this->sender = $sender;
        $this->rujukId = $rujukId;
        $this->visitationId = $visitationId;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'kind' => 'rujukan',
            'multiline' => true,
            'title' => $this->title,
            'message' => $this->message,
            'sender' => $this->sender,
            'rujuk_id' => $this->rujukId,
            'visitation_id' => $this->visitationId,
        ];
    }
}
