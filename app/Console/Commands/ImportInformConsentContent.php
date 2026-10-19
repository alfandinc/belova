<?php

namespace App\Console\Commands;

use App\Models\ERM\Tindakan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Fills erm_tindakan.consent_content from the old per-tindakan inform consent templates, kept as
 * database/data/inform_consent_templates.json (template name => HTML with {tindakan} / {klinik}). A template
 * belonged to the tindakan whose name gives its file name (spaces -> _, case, punctuation ignored), the same
 * rule the templates were looked up with. Tindakan that already have content are left alone unless --force.
 */
class ImportInformConsentContent extends Command
{
    protected $signature = 'inform-consent:import {--dry-run : Report only, write nothing} {--force : Also overwrite consent text already filled}';

    protected $description = 'Isi teks inform consent master tindakan dari template lama (database/data/inform_consent_templates.json)';

    public function handle(): int
    {
        if (!Schema::hasColumn('erm_tindakan', 'consent_content')) {
            $this->error('Kolom erm_tindakan.consent_content belum ada: jalankan php artisan migrate dulu.');
            return self::FAILURE;
        }
        $templates = json_decode((string) @file_get_contents(database_path('data/inform_consent_templates.json')), true);
        if (!is_array($templates) || !$templates) {
            $this->error('database/data/inform_consent_templates.json tidak ditemukan atau kosong.');
            return self::FAILURE;
        }

        $loose = fn ($name) => trim(preg_replace('/[^a-z0-9]+/', '_', strtolower(str_replace('&', ' dan ', (string) $name))), '_');
        $byExact = $templates;
        $byLoose = collect($templates)->mapWithKeys(fn ($html, $name) => [$loose($name) => $html])->all();

        $filled = 0;
        $kept = 0;
        $used = [];
        foreach (Tindakan::orderBy('id')->get(['id', 'nama', 'consent_content']) as $tindakan) {
            $exact = strtolower(str_replace(' ', '_', (string) $tindakan->nama));
            $key = isset($byExact[$exact]) ? $exact : null;
            $html = $key ? $byExact[$key] : ($byLoose[$loose($tindakan->nama)] ?? null);
            if ($html === null) {
                continue;
            }
            $used[$key ?? $loose($tindakan->nama)] = true;
            if (trim((string) $tindakan->consent_content) !== '' && !$this->option('force')) {
                $kept++;
                continue;
            }
            if (!$this->option('dry-run')) {
                $tindakan->forceFill(['consent_content' => $html])->saveQuietly();
            }
            $filled++;
        }

        $withoutTemplate = Tindakan::count() - $filled - $kept;
        $this->info(($this->option('dry-run') ? '[dry-run] ' : '') . "Diisi: {$filled} tindakan, sudah ada isi (dilewati): {$kept}, tanpa template (pakai template umum): {$withoutTemplate}.");
        $unused = collect($templates)->keys()->reject(fn ($name) => isset($used[$name]) || isset($used[$loose($name)]))->values();
        if ($unused->isNotEmpty()) {
            $this->line('Template tanpa tindakan dengan nama yang cocok (' . $unused->count() . '): ' . $unused->implode(', '));
        }

        return self::SUCCESS;
    }
}
