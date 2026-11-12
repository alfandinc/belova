<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Dokter -> Farmasi: obat on a resep are short in the resep gudang but another gudang still has them,
 * so farmasi is asked to transfer the stok (E-Resep dokter page, "Minta Farmasi Transfer").
 * Shown by the farmasi notification popup (partials.farmasi-notif).
 */
class MintaTransferStokNotification extends Notification
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
            'kind' => 'transfer_stok',
            'multiline' => true,
            'title' => 'Permintaan Transfer Stok ke Apotek',
            'message' => $this->message,
            'sender' => $this->sender,
            'visitation_id' => $this->visitationId,
        ];
    }
}
