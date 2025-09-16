<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Support\Facades\Auth;

class BillingToFarmasiNotification extends Notification
{
    public $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'Pesan dari Finance (Billing)',
            'message' => $this->message,
            'sender' => Auth::user() ? Auth::user()->name : 'Finance'
        ];
    }
}
