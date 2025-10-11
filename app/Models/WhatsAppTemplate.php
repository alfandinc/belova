<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppTemplate extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_templates';

    protected $fillable = [
        'key',
        'name',
        'description',
        'content',
        'variables',
        'is_active'
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean'
    ];

    /**
     * Scope to get only active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get template by key
     */
    public static function getByKey($key)
    {
        return static::where('key', $key)->active()->first();
    }

    /**
     * Replace variables in template content
     */
    public function processContent($variables = [])
    {
        $content = $this->content;
        
        foreach ($variables as $key => $value) {
            $content = str_replace('{' . $key . '}', $value, $content);
        }
        
        return $content;
    }

    /**
     * Get available variables as formatted array
     */
    public function getFormattedVariables()
    {
        if (!$this->variables) {
            return [];
        }

        $formatted = [];
        foreach ($this->variables as $variable => $description) {
            $formatted[] = [
                'variable' => $variable,
                'description' => $description
            ];
        }

        return $formatted;
    }
}