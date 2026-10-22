<?php

namespace App\Models\Marketing;

use App\Models\Marketing\Concerns\HasStatusLogs;
use App\Models\Rnd\RndProduk;
use App\Models\User;
use App\Support\Marketing\MarketingAccess;
use Illuminate\Database\Eloquent\Model;

/**
 * A job for the graphic designer. Anyone can request; Marketing takes, works on and approves it.
 *
 * Flow: Baru → Dikerjakan → Review → (Revisi → Review)* → Selesai, or Dibatalkan.
 */
class DesignRequest extends Model
{
    use HasStatusLogs;

    protected $table = 'marketing_design_requests';

    public const JENIS = ['Konten Sosmed', 'Kemasan', 'Cetak', 'Lainnya'];

    public const STATUS_BARU = 'Baru';
    public const STATUS_DIKERJAKAN = 'Dikerjakan';
    public const STATUS_REVIEW = 'Review';
    public const STATUS_REVISI = 'Revisi';
    public const STATUS_SELESAI = 'Selesai';
    public const STATUS_DIBATALKAN = 'Dibatalkan';

    public const STATUSES = ['Baru', 'Dikerjakan', 'Review', 'Revisi', 'Selesai', 'Dibatalkan'];
    public const ACTIVE_STATUSES = ['Baru', 'Dikerjakan', 'Review', 'Revisi'];

    /** Label of each workflow action, also used in the "Riwayat" timeline. */
    public const ACTION_LABELS = [
        'created' => 'Membuat request',
        'updated' => 'Mengubah request',
        'take' => 'Mengambil pekerjaan',
        'assign' => 'Menugaskan desainer',
        'upload' => 'Mengunggah hasil',
        'submit_review' => 'Selesai dikerjakan',
        'approve' => 'Approved',
        'revise' => 'Revisi masuk',
        'cancel' => 'Membatalkan',
        'reopen' => 'Membuka kembali',
        'comment' => 'Komentar',
    ];

    protected $fillable = [
        'jenis',
        'judul',
        'brief',
        'spesifikasi',
        'deadline',
        'status',
        'requester_id',
        'designer_id',
        'content_plan_id',
        'rnd_produk_id',
        'rnd_kemasan_sisi',
        'completed_at',
    ];

    protected $casts = [
        'spesifikasi' => 'array',
        'deadline' => 'date',
        'completed_at' => 'datetime',
    ];

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function designer()
    {
        return $this->belongsTo(User::class, 'designer_id');
    }

    public function contentPlan()
    {
        return $this->belongsTo(ContentPlan::class, 'content_plan_id');
    }

    public function rndProduk()
    {
        return $this->belongsTo(RndProduk::class, 'rnd_produk_id');
    }

    public function files()
    {
        return $this->hasMany(DesignRequestFile::class, 'design_request_id')->orderBy('id');
    }

    public function scopeVisibleTo($query, User $user)
    {
        if (MarketingAccess::isMarketing($user)) {
            return $query;
        }

        return $query->where('requester_id', $user->id);
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }

    public function canView(User $user): bool
    {
        return MarketingAccess::isMarketing($user) || (int) $this->requester_id === (int) $user->id;
    }

    public function canEdit(User $user): bool
    {
        if (MarketingAccess::isMarketing($user)) {
            return $this->isActive();
        }

        return (int) $this->requester_id === (int) $user->id && $this->status === self::STATUS_BARU;
    }

    /** Production of a Content Plan item: approval happens on the plan and files go through the WA group. */
    public function isContentJob(): bool
    {
        return (bool) $this->content_plan_id && $this->jenis === 'Konten Sosmed';
    }

    /**
     * Final files can be kept here while the job is being worked on (optional: results are shared in the
     * WA group; useful for print / packaging files sent to a vendor). Not for content jobs.
     */
    public function canUpload(User $user): bool
    {
        return !$this->isContentJob()
            && MarketingAccess::isMarketing($user)
            && in_array($this->status, [self::STATUS_DIKERJAKAN, self::STATUS_REVISI, self::STATUS_REVIEW], true);
    }

    /**
     * Workflow buttons this user may press now, in display order.
     * Each: [action, label, style, needs_note (null|optional|required)].
     */
    public function availableActions(User $user): array
    {
        $marketing = MarketingAccess::isMarketing($user);
        $isRequester = (int) $this->requester_id === (int) $user->id;
        $actions = [];
        // a job already handed to this user starts with "Mulai kerjakan"; otherwise it is taken over
        $takeLabel = (int) $this->designer_id === (int) $user->id ? 'Mulai kerjakan' : 'Ambil & kerjakan';

        // Content Plan job: the designer only takes it and marks it done; review, revisi and approval
        // are recorded on the content plan (decided in the team's WA group) and flow back here
        if ($this->isContentJob()) {
            if ($marketing && $this->status === self::STATUS_BARU) {
                $actions[] = ['take', $takeLabel, 'primary', null];
                $actions[] = ['assign', 'Tugaskan ke…', 'outline-primary', null];
            }
            if ($marketing && in_array($this->status, [self::STATUS_DIKERJAKAN, self::STATUS_REVISI], true)) {
                $actions[] = ['submit_review', 'Selesai dikerjakan', 'success', 'optional'];
                $actions[] = ['assign', 'Ganti designer', 'outline-secondary', null];
            }
            if ($this->canView($user)) {
                $actions[] = ['comment', 'Komentar', 'outline-secondary', 'required'];
            }

            return array_map(fn ($a) => ['action' => $a[0], 'label' => $a[1], 'style' => $a[2], 'note' => $a[3]], $actions);
        }

        // Other jobs follow the same steps as content: results are shared in the WA group and the
        // decision (who approved / who asked for a revision) is recorded here
        switch ($this->status) {
            case self::STATUS_BARU:
                if ($marketing) {
                    $actions[] = ['take', $takeLabel, 'primary', null];
                    $actions[] = ['assign', 'Tugaskan ke…', 'outline-primary', null];
                }
                break;
            case self::STATUS_DIKERJAKAN:
            case self::STATUS_REVISI:
                if ($marketing) {
                    $actions[] = ['submit_review', 'Selesai dikerjakan', 'success', 'optional'];
                    $actions[] = ['assign', 'Ganti designer', 'outline-secondary', null];
                }
                break;
            case self::STATUS_REVIEW:
                if ($marketing) {
                    $actions[] = ['approve', 'Approved', 'success', 'optional'];
                    $actions[] = ['revise', 'Revisi masuk', 'warning', 'required'];
                }
                break;
            case self::STATUS_SELESAI:
                if ($marketing) {
                    $actions[] = ['revise', 'Revisi masuk', 'outline-warning', 'required'];
                }
                break;
            case self::STATUS_DIBATALKAN:
                if ($marketing) {
                    $actions[] = ['reopen', 'Buka Kembali', 'outline-primary', 'optional'];
                }
                break;
        }

        if ($this->isActive() && ($marketing || ($isRequester && $this->status === self::STATUS_BARU))) {
            $actions[] = ['cancel', 'Batalkan', 'outline-danger', 'required'];
        }

        if ($this->canView($user)) {
            $actions[] = ['comment', 'Komentar', 'outline-secondary', 'required'];
        }

        return array_map(fn ($a) => [
            'action' => $a[0],
            'label' => $a[1],
            'style' => $a[2],
            'note' => $a[3],
        ], $actions);
    }

    /**
     * Keep the R&D product's "Status Desain" in step with a packaging request:
     * active → "in progress", finished → "done", cancelled → cleared again.
     */
    public function syncRndStatus(): void
    {
        if (!$this->rnd_produk_id || !in_array($this->rnd_kemasan_sisi, ['primer', 'sekunder'], true)) {
            return;
        }

        try {
            $produk = RndProduk::find($this->rnd_produk_id);
            if (!$produk) {
                return;
            }

            $field = 'status_desain_kemasan_' . $this->rnd_kemasan_sisi;
            if ($this->status === self::STATUS_SELESAI) {
                $produk->{$field} = 'done';
            } elseif ($this->status === self::STATUS_DIBATALKAN) {
                if ($produk->{$field} === 'in progress') {
                    $produk->{$field} = null;
                }
            } else {
                $produk->{$field} = 'in progress';
            }
            $produk->save();
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
