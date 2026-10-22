<?php

namespace App\Models\Marketing;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use App\Support\Marketing\PublicFile;

/** A reference from the requester or a result version from the designer (file or link). */
class DesignRequestFile extends Model
{
    protected $table = 'marketing_design_request_files';

    public const KIND_REFERENSI = 'referensi';
    public const KIND_HASIL = 'hasil';

    protected $fillable = [
        'design_request_id',
        'kind',
        'version',
        'path',
        'url',
        'original_name',
        'mime',
        'size',
        'notes',
        'user_id',
    ];

    public function designRequest()
    {
        return $this->belongsTo(DesignRequest::class, 'design_request_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function publicUrl(): ?string
    {
        if ($this->path) {
            return PublicFile::url($this->path);
        }

        return $this->url;
    }

    public function isImage(): bool
    {
        return $this->path && str_starts_with((string) $this->mime, 'image/');
    }

    public function toCard(): array
    {
        return [
            'id' => $this->id,
            'kind' => $this->kind,
            'version' => $this->version,
            'url' => $this->publicUrl(),
            'is_link' => !$this->path,
            'is_image' => $this->isImage(),
            'name' => $this->original_name ?: ($this->url ?: 'File'),
            'size' => $this->size,
            'notes' => $this->notes,
            'user' => optional($this->user)->name,
            'user_id' => $this->user_id,
            'at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}
