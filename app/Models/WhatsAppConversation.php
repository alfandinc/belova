<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class WhatsAppConversation extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_conversations';

    protected $fillable = [
        'phone_number',
        'conversation_type',
        'conversation_state',
        'context_data',
        'expires_at'
    ];

    protected $casts = [
        'context_data' => 'array',
        'expires_at' => 'datetime'
    ];

    // Conversation types
    const TYPE_VISITATION_CONFIRMATION = 'visitation_confirmation';
    const TYPE_APPOINTMENT_REMINDER = 'appointment_reminder';
    const TYPE_PAYMENT_REMINDER = 'payment_reminder';

    // Conversation states
    const STATE_PENDING = 'pending';
    const STATE_CONFIRMED = 'confirmed';
        // Stub model to keep references safe; integration removed
    const STATE_CANCELLED = 'cancelled';
    const STATE_EXPIRED = 'expired';

    /**
     * Scope to get active (non-expired) conversations
     */
    public function scopeActive($query)
    {
        return $query->where('conversation_state', self::STATE_PENDING)
                    ->where(function($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    /**
     * Find active conversation for phone number and type
     */
    public static function findActiveConversation($phoneNumber, $type = null)
    {
        $query = static::where('phone_number', $phoneNumber)->active();
        
        if ($type) {
            $query->where('conversation_type', $type);
        }
        
        return $query->orderBy('created_at', 'desc')->first();
    }

    /**
     * Create a new conversation
     */
    public static function createConversation($phoneNumber, $type, $contextData = [], $expiresInHours = 24)
    {
        return static::create([
            'phone_number' => $phoneNumber,
            'conversation_type' => $type,
            'conversation_state' => self::STATE_PENDING,
            'context_data' => $contextData,
            'expires_at' => now()->addHours($expiresInHours)
        ]);
    }

    /**
     * Find conversation by visitation ID
     */
    public static function findByVisitationId($visitationId)
    {
        return static::where('conversation_type', self::TYPE_VISITATION_CONFIRMATION)
                    ->whereJsonContains('context_data->visitation_id', $visitationId)
                    ->orderBy('created_at', 'desc')
                    ->first();
    }

    /**
     * Confirm this conversation
     */
    public function confirm()
    {
        $this->update(['conversation_state' => self::STATE_CONFIRMED]);
        return $this;
    }

    /**
     * Cancel this conversation
     */
    public function cancel()
    {
        $this->update(['conversation_state' => self::STATE_CANCELLED]);
        return $this;
    }

    /**
     * Check if conversation is expired
     */
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Get the related visitation if applicable
     */
    public function getVisitation()
    {
        if ($this->conversation_type === self::TYPE_VISITATION_CONFIRMATION && 
            isset($this->context_data['visitation_id'])) {
            return \App\Models\ERM\Visitation::find($this->context_data['visitation_id']);
        }
        
        return null;
    }
}