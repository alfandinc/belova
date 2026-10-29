<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Sent to Perawat, Pendaftaran and Farmasi when a dokter makes a surat istirahat or surat mondok
 * (SuratIstirahatController) or a surat diagnosa (RiwayatKunjunganController).
 * The message is multi-line; see App\Support\ErmStaffNotifications.
 */
class SuratNotification extends Notification
{
    public $title;
    public $message;
    public $sender;
    public $printUrl;

    public function __construct($title, $message, $sender, $printUrl = null)
    {
        $this->title = $title;
        $this->message = $message;
        $this->sender = $sender;
        $this->printUrl = $printUrl;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'kind' => 'surat',
            'multiline' => true,
            'title' => $this->title,
            'message' => $this->message,
            'sender' => $this->sender,
            'print_url' => $this->printUrl,
        ];
    }
}
