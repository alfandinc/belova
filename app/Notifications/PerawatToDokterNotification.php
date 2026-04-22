<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class PerawatToDokterNotification extends Notification
{
    public $message;
    public $title;
    public $dokumenUrl;

    public function __construct($message, $title = 'Pesan dari Perawat', $dokumenUrl = null)
    {
        $this->message = $message;
        $this->title = $title;
        $this->dokumenUrl = $dokumenUrl;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'sender' => Auth::user()->name,
            'dokumen_url' => $this->dokumenUrl,
        ];
    }
}