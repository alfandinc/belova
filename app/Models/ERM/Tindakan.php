<?php

namespace App\Models\ERM;

use App\Models\Finance\Billing;
use Illuminate\Database\Eloquent\Model;

class Tindakan extends Model
{
    /**
     * Many-to-many relationship to KodeTindakan
     */
    public function kodeTindakans()
    {
        return $this->belongsToMany(KodeTindakan::class, 'erm_tindakan_kode_tindakan', 'tindakan_id', 'kode_tindakan_id');
    }
    protected $table = 'erm_tindakan';
    protected $fillable = ['nama', 'deskripsi', 'consent_content', 'consent_kategori_id', 'harga', 'spesialis_id', 'harga_diskon', 'diskon_active', 'is_active', 'harga_paket_visit', 'is_slimming', 'is_vaksin', 'multi_visit_total', 'total_rf', 'total_inject'];
    protected $casts = [
        'is_active' => 'boolean',
        'is_slimming' => 'boolean',
        'is_vaksin' => 'boolean',
        'harga_paket_visit' => 'float',
        'multi_visit_total' => 'integer',
        'total_rf' => 'integer',
        'total_inject' => 'integer',
    ];

    /** Formatting tags the inform consent text may use (what Summernote and the old templates produce). */
    private const CONSENT_TAGS = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 'ol', 'ul', 'li', 'h4', 'h5', 'h6', 'blockquote', 'div', 'table', 'thead', 'tbody', 'tr', 'th', 'td'];

    /**
     * Inform consent text (HTML from master Tindakan) reduced to formatting tags without any attribute, so it can be
     * shown and printed as it is: no scripts, styles, links or event handlers.
     */
    public static function sanitizeConsentHtml(?string $html): string
    {
        $tags = implode('|', self::CONSENT_TAGS);
        // Script / style blocks go with their content (strip_tags would leave the code as text)
        $clean = preg_replace('#<(script|style)\b[^>]*>.*?</\1\s*>#is', '', (string) $html);
        $clean = strip_tags((string) $clean, '<' . implode('><', self::CONSENT_TAGS) . '>');
        $clean = preg_replace('#<(/?)(' . $tags . ')\b[^>]*?(/?)>#i', '<$1$2$3>', $clean);

        return trim((string) $clean);
    }

    /** Kategori whose inform consent text this tindakan uses when it has no text of its own. */
    public function consentKategori()
    {
        return $this->belongsTo(KategoriTindakan::class, 'consent_kategori_id');
    }

    public function informConsent()
    {
        return $this->hasMany(InformConsent::class);
    }

    public function billing()
    {
        return $this->morphMany(Billing::class, 'billable');
    }
    
    public function spesialis()
    {
        return $this->belongsTo(\App\Models\ERM\Spesialisasi::class, 'spesialis_id');
    }
    

        /**
         * The obat that belong to the tindakan (bundle).
         */
}
