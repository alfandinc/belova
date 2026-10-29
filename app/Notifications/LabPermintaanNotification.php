<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Sent to Perawat, Pendaftaran, Farmasi and Lab when a dokter adds lab tests (ElabController@bulkUpdate).
 * The message is multi-line (Pasien / Dokter / Pemeriksaan / Tanggal); see App\Support\ErmStaffNotifications.
 */
class LabPermintaanNotification extends Notification
{
    public $message;
    public $sender;
    public $visitationId;

    public function __construct($message, $sender, $visitationId = null)
    {
        $this->message = $message;
        $this->sender = $sender;
        $this->visitationId = $visitationId;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'kind' => 'lab',
            'multiline' => true,
            'title' => 'Permintaan Lab Baru',
            'message' => $this->message,
            'sender' => $this->sender,
            'visitation_id' => $this->visitationId,
        ];
    }
}
