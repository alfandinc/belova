<?php

namespace App\Models\Finance;

use App\Models\ERM\Klinik;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Customer-facing display (tablet) at the kasir desk. `state` is what the tablet shows:
 *   null / mode=idle  → logo screen
 *   mode=billing      → live bill of the patient the kasir has open
 *   mode=survey       → satisfaction survey for a processed invoice (expires after a few minutes)
 */
class LayarPelanggan extends Model
{
    protected $table = 'finance_layar_pelanggan';

    protected $fillable = ['nama', 'token', 'klinik_id', 'is_active', 'state', 'state_version', 'last_seen_at'];

    protected $casts = [
        'is_active' => 'boolean',
        'state' => 'array',
        'state_version' => 'integer',
        'last_seen_at' => 'datetime',
    ];

    protected $hidden = ['state'];

    /** Survey stays on screen this long when nobody fills it. */
    public const SURVEY_SECONDS = 180;

    /** A bill nobody touched for this long goes back to the logo screen (privacy). */
    public const BILLING_STALE_SECONDS = 1800;

    public static function newToken(): string
    {
        return Str::random(40);
    }

    public function klinik()
    {
        return $this->belongsTo(Klinik::class, 'klinik_id');
    }

    public function isOnline(): bool
    {
        return $this->last_seen_at !== null && $this->last_seen_at->gt(now()->subSeconds(30));
    }

    /** State as the tablet should see it now: expired survey / stale bill fall back to idle. */
    public function currentState(): array
    {
        $state = is_array($this->state) ? $this->state : [];
        $mode = $state['mode'] ?? 'idle';

        if ($mode === 'survey' && (int) ($state['expires_at'] ?? 0) < time()) {
            return ['mode' => 'idle'];
        }
        if ($mode === 'billing' && (int) ($state['at'] ?? 0) < time() - self::BILLING_STALE_SECONDS) {
            return ['mode' => 'idle'];
        }

        return $state ?: ['mode' => 'idle'];
    }

    public function writeState(array $state): void
    {
        $this->state = $state;
        $this->state_version = ((int) $this->state_version) + 1;
        $this->save();
    }

    public function displayUrl(): string
    {
        return url('/layar-pelanggan/' . $this->token);
    }
}
